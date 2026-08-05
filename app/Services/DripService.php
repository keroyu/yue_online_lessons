<?php

namespace App\Services;

use App\Jobs\SendDripEmailJob;
use App\Models\Course;
use App\Models\DripConversionTarget;
use App\Models\DripEmailEvent;
use App\Models\DripSubscription;
use App\Models\Lesson;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DripService
{
    /** course_id => [lesson_id => 0-based position], memoised per request. */
    private array $lessonPositions = [];

    /**
     * Where a lesson sits in its course's running order, counting from 0.
     *
     * The drip counts emails — "have we sent the n-th lesson yet" — and until
     * 2026-08-05 it read `sort_order` as that count. `sort_order` is only a
     * sort key: `LessonController` numbers new lessons `max + 1`, so a course
     * built through the admin runs 1, 2, 3, and reordering can leave gaps.
     * Every drip calculation was therefore one lesson behind on live data
     * (subscribers could not open the lesson they had just been emailed).
     *
     * `processSubscription()` always picked lessons by position, so position is
     * the definition that matches what actually went out.
     */
    private function lessonPosition(Lesson $lesson): int
    {
        $positions = $this->lessonPositions[$lesson->course_id] ??= Lesson::where('course_id', $lesson->course_id)
            ->orderBy('sort_order')
            ->pluck('id')
            ->flip()
            ->all();

        // A lesson that is not in its own course cannot happen; treating the
        // unknown as "past the end" keeps it locked rather than opening it.
        return $positions[$lesson->id] ?? count($positions);
    }

    /**
     * Subscribe a user to a drip course.
     *
     * @return array{success: bool, error?: string, subscription?: DripSubscription}
     */
    public function subscribe(User $user, Course $course): array
    {
        // Check if course is a drip course
        if ($course->course_type !== 'drip') {
            return ['success' => false, 'error' => '此課程不是連鎖課程'];
        }

        // Check for existing subscription
        $existing = DripSubscription::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            if ($existing->status === 'unsubscribed') {
                return ['success' => false, 'error' => '您已停止接收此商品的信件，無法再次領取'];
            }
            return ['success' => false, 'error' => '您已領取過此商品'];
        }

        // Create subscription
        $subscription = DripSubscription::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'subscribed_at' => now(),
            'emails_sent' => 0,
            'status' => 'active',
        ]);

        // Send welcome email (first lesson) immediately
        $firstLesson = $course->lessons()->orderBy('sort_order')->first();

        if ($firstLesson) {
            SendDripEmailJob::dispatchAfterResponse(
                $user->id,
                $firstLesson->id,
                $subscription->id
            );

            // Track dispatched count; status=completed is set by the job after actual send
            $subscription->update(['emails_sent' => 1]);
        }

        Log::info('Drip subscription created', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'subscription_id' => $subscription->id,
        ]);

        return ['success' => true, 'subscription' => $subscription];
    }

    /**
     * Calculate unlocked lesson count for a subscription.
     */
    public function getUnlockedLessonCount(DripSubscription $subscription): int
    {
        $daysSince = (int) $subscription->subscribed_at->diffInDays(now());
        $interval = $subscription->course->drip_interval_days;

        if ($interval <= 0) {
            return $subscription->course->lessons()->count();
        }

        $totalLessons = $subscription->course->lessons()->count();

        return min(
            (int) floor($daysSince / $interval) + 1,
            $totalLessons
        );
    }

    /**
     * Check if a specific lesson is unlocked for a subscription.
     *
     * The drip is a promo funnel, not a course: reaching the goal ends it
     * rather than unlocking the rest as a reward. Everyone therefore stays on
     * the emails_sent cursor. The two exceptions are 'completed' (every email
     * already went out) and unlock_all, the compatibility flag carried by
     * subscribers who converted before that rule changed.
     */
    public function isLessonUnlocked(DripSubscription $subscription, Lesson $lesson): bool
    {
        if ($subscription->unlock_all || $subscription->status === 'completed') {
            return true;
        }

        return $this->lessonPosition($lesson) < $subscription->emails_sent;
    }

    /**
     * Calculate days until a lesson unlocks.
     * Returns -1 once the sequence has stopped (lesson will never unlock).
     */
    public function daysUntilUnlock(DripSubscription $subscription, Lesson $lesson): int
    {
        if ($this->isLessonUnlocked($subscription, $lesson)) {
            return 0;
        }

        // Stopped sequences (booked / converted / unsubscribed) get no new unlocks
        if (in_array($subscription->status, DripSubscription::STOPS_SENDING)) {
            return -1;
        }

        $interval = $subscription->course->drip_interval_days;
        $unlockDay = $this->lessonPosition($lesson) * $interval;
        $daysSince = (int) $subscription->subscribed_at->diffInDays(now());

        return max(0, $unlockDay - $daysSince);
    }

    /**
     * Check if a purchased course triggers drip conversion.
     *
     * Booked subscribers are included: booking then actually buying is the
     * normal funnel path, and leaving them at 'booked' would understate sales.
     */
    public function checkAndConvert(User $user, Course $purchasedCourse): void
    {
        $this->reachGoal($user, $purchasedCourse, 'converted', ['active', 'booked']);
    }

    /**
     * Check if a completed high-ticket booking ends the sequence.
     *
     * Bookings come from a guest form, so the email is all we have. Subscribers
     * are always users (D1), so no matching user means no subscription to stop —
     * creating an account here is 011's job, not the drip's.
     */
    public function checkAndBook(string $email, Course $bookedCourse): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return;
        }

        $this->reachGoal($user, $bookedCourse, 'booked', ['active']);
    }

    /**
     * Move a user's subscriptions to a funnel-exit status when the course they
     * just booked/bought is a conversion target of the drip course.
     *
     * @param array<int, string> $fromStatuses
     */
    private function reachGoal(User $user, Course $targetCourse, string $newStatus, array $fromStatuses): void
    {
        $dripCourseIds = DripConversionTarget::where('target_course_id', $targetCourse->id)
            ->pluck('drip_course_id');

        if ($dripCourseIds->isEmpty()) {
            return;
        }

        $subscriptions = DripSubscription::where('user_id', $user->id)
            ->whereIn('course_id', $dripCourseIds)
            ->whereIn('status', $fromStatuses)
            ->get();

        foreach ($subscriptions as $subscription) {
            $subscription->update([
                'status' => $newStatus,
                'status_changed_at' => now(),
            ]);

            Log::info('Drip subscription reached funnel goal', [
                'subscription_id' => $subscription->id,
                'status' => $newStatus,
                'target_course_id' => $targetCourse->id,
            ]);
        }
    }

    /**
     * Map of lesson_id => actual send time (Carbon) for a subscription's 'sent'
     * events, fetched in one query. Callers pass the per-lesson value as the
     * $sentAt anchor to the video-access methods below to avoid N+1 lookups.
     */
    public function getSentAtMap(DripSubscription $subscription): Collection
    {
        return DripEmailEvent::where('subscription_id', $subscription->id)
            ->where('event_type', 'sent')
            ->pluck('created_at', 'lesson_id')
            ->map(fn ($ts) => $ts instanceof Carbon ? $ts : Carbon::parse($ts));
    }

    /**
     * Calculate when the video free viewing window expires for a lesson.
     *
     * Anchors on $sentAt (the actual email send time) when known; otherwise
     * falls back to the theoretical unlock time — pre-change subscriptions with
     * no 'sent' event, or the brief window after dispatch before the job runs.
     */
    public function getVideoAccessExpiresAt(DripSubscription $subscription, Lesson $lesson, ?Carbon $sentAt = null): ?Carbon
    {
        $hours = $lesson->video_access_hours; // null = unlimited access, no countdown UI
        if ($hours === null) {
            return null;
        }

        $anchor = $sentAt ?? $subscription->subscribed_at->copy()
            ->addDays($this->lessonPosition($lesson) * $subscription->course->drip_interval_days);

        return $anchor->copy()->addHours($hours);
    }

    /**
     * Check if the video free viewing window has expired.
     */
    public function isVideoAccessExpired(DripSubscription $subscription, Lesson $lesson, ?Carbon $sentAt = null): bool
    {
        $expiresAt = $this->getVideoAccessExpiresAt($subscription, $lesson, $sentAt);

        return $expiresAt !== null && now()->greaterThan($expiresAt);
    }

    /**
     * Get remaining seconds in the video free viewing window.
     */
    public function getVideoAccessRemainingSeconds(DripSubscription $subscription, Lesson $lesson, ?Carbon $sentAt = null): ?int
    {
        $expiresAt = $this->getVideoAccessExpiresAt($subscription, $lesson, $sentAt);
        if ($expiresAt === null || now()->greaterThan($expiresAt)) {
            return null;
        }

        return (int) now()->diffInSeconds($expiresAt);
    }

    /**
     * Reactivate completed subscriptions when a new lesson is added to a drip course.
     * This allows completed subscribers to receive emails for the new content.
     */
    public function reactivateCompletedSubscriptions(Course $course): int
    {
        $count = DripSubscription::where('course_id', $course->id)
            ->where('status', 'completed')
            ->update([
                'status' => 'active',
                'status_changed_at' => now(),
            ]);

        if ($count > 0) {
            Log::info('Reactivated completed drip subscriptions due to new lesson', [
                'course_id' => $course->id,
                'reactivated_count' => $count,
            ]);
        }

        return $count;
    }

    /**
     * Process daily drip emails for all active subscriptions.
     */
    public function processDailyEmails(): int
    {
        $sentCount = 0;

        $subscriptions = DripSubscription::where('status', 'active')
            ->whereHas('course', fn ($q) => $q->where('course_type', 'drip')->published())
            ->with(['user', 'course.lessons' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        foreach ($subscriptions as $subscription) {
            $sentCount += $this->processSubscription($subscription);
        }

        return $sentCount;
    }

    /**
     * Get subscriber stats for admin Lesson analytics table.
     *
     * Returns lesson_stats (Collection) and conversion_rate (?float).
     */
    public function getSubscriberStats(Course $course): array
    {
        $lessons = $course->lessons()->orderBy('sort_order')->get(['id', 'title', 'sort_order', 'promo_url']);

        $totalSubscribers = DripSubscription::where('course_id', $course->id)->count();
        $convertedCount = DripSubscription::where('course_id', $course->id)->where('status', 'converted')->count();
        $bookedCount = DripSubscription::where('course_id', $course->id)->where('status', 'booked')->count();

        // Aggregate open/click counts per lesson from drip_email_events
        $subIds = DripSubscription::where('course_id', $course->id)->pluck('id');

        $eventStats = DripEmailEvent::whereIn('subscription_id', $subIds)
            ->select(
                'lesson_id',
                DB::raw("SUM(CASE WHEN event_type = 'opened' THEN 1 ELSE 0 END) as open_count"),
                DB::raw("SUM(CASE WHEN event_type = 'clicked' THEN 1 ELSE 0 END) as click_count"),
                DB::raw("MAX(CASE WHEN event_type = 'sent' THEN created_at END) as last_sent_at")
            )
            ->groupBy('lesson_id')
            ->get()
            ->keyBy('lesson_id');

        // How many subscribers sit at each point of the sequence, in one query —
        // the per-lesson figures below are running totals of this.
        $sentDistribution = DripSubscription::where('course_id', $course->id)
            ->selectRaw('emails_sent, count(*) as total')
            ->groupBy('emails_sent')
            ->pluck('total', 'emails_sent');

        $lessonStats = $lessons->values()->map(function (Lesson $lesson, int $position) use ($eventStats, $sentDistribution) {
            // Sent to everyone whose cursor is past this position — the cursor
            // counts emails, so it is compared against the position in the
            // running order, never against sort_order (see lessonPosition()).
            $sentCount = (int) $sentDistribution
                ->filter(fn ($total, $emailsSent) => (int) $emailsSent > $position)
                ->sum();

            $stats = $eventStats->get($lesson->id);
            $openCount = (int) ($stats?->open_count ?? 0);
            $clickCount = (int) ($stats?->click_count ?? 0);
            $hasPromoUrl = !empty($lesson->promo_url);

            return [
                'lesson_id' => $lesson->id,
                'title' => $lesson->title,
                'sort_order' => $lesson->sort_order,
                'sent_count' => $sentCount,
                'last_sent_at' => $stats?->last_sent_at
                    ? Carbon::parse($stats->last_sent_at)->toIso8601String()
                    : null,
                'open_count' => $openCount,
                'open_rate' => $sentCount > 0 ? round($openCount / $sentCount, 4) : null,
                'has_promo_url' => $hasPromoUrl,
                'click_count' => $clickCount,
                'click_rate' => ($sentCount > 0 && $hasPromoUrl)
                    ? round($clickCount / $sentCount, 4)
                    : null,
            ];
        });

        return [
            'lesson_stats' => $lessonStats,
            // Booking and buying are counted separately: a booked lead is not a sale.
            'conversion_rate' => $totalSubscribers > 0
                ? round($convertedCount / $totalSubscribers, 4)
                : null,
            'booking_rate' => $totalSubscribers > 0
                ? round($bookedCount / $totalSubscribers, 4)
                : null,
        ];
    }

    /**
     * Assemble everything the admin subscriber list needs for one drip course:
     * the paginated rows (with per-row event metrics), the unfiltered status
     * summary, and the per-lesson send/open/click analytics.
     *
     * Lives here rather than in a controller because every piece of it is drip
     * domain logic; the leads admin page (011 US8) is only the host.
     *
     * @param  string|null  $status  filters the rows only — the summary always covers the whole course
     * @return array{course: array, subscribers: mixed, stats: array, lessonStats: mixed, conversionRate: ?float, bookingRate: ?float}
     */
    public function subscriberPageData(Course $course, ?string $status = null, string $pageName = 'page'): array
    {
        $query = DripSubscription::where('course_id', $course->id)
            ->with('user:id,email,nickname');

        if ($status && in_array($status, ['active', 'booked', 'converted', 'completed', 'unsubscribed'], true)) {
            $query->where('status', $status);
        }

        $subscribers = $query->orderByDesc('subscribed_at')
            ->paginate(20, ['*'], $pageName)
            ->withQueryString();

        // Status summary aggregation
        $statusStats = DripSubscription::where('course_id', $course->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as booked_count,
                SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed_count
            ")
            ->first();

        // Per-lesson analytics (opened/clicked counts)
        $analyticsData = $this->getSubscriberStats($course);

        // Per-subscriber event metrics (open count + has_clicked)
        $eventCounts = $this->getSubscriberEventCounts($subscribers->pluck('id'));

        $subscribers->getCollection()->transform(function ($sub) use ($eventCounts) {
            $events = $eventCounts->get($sub->id);
            $sub->opened_count = (int) ($events?->opened_count ?? 0);
            $sub->has_clicked = (bool) ($events?->has_clicked ?? false);

            return $sub;
        });

        return [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'total_lessons' => $course->lessons()->count(),
            ],
            'subscribers' => $subscribers,
            'stats' => [
                'total' => (int) $statusStats->total,
                'active' => (int) $statusStats->active_count,
                'booked' => (int) $statusStats->booked_count,
                'converted' => (int) $statusStats->converted_count,
                'completed' => (int) $statusStats->completed_count,
                'unsubscribed' => (int) $statusStats->unsubscribed_count,
            ],
            'lessonStats' => $analyticsData['lesson_stats'],
            'conversionRate' => $analyticsData['conversion_rate'],
            'bookingRate' => $analyticsData['booking_rate'],
        ];
    }

    /**
     * Get per-subscription open count and has_clicked (bool) for subscriber list rows.
     *
     * @param Collection $subscriptionIds
     * @return Collection keyed by subscription_id
     */
    public function getSubscriberEventCounts(Collection $subscriptionIds): Collection
    {
        if ($subscriptionIds->isEmpty()) {
            return collect();
        }

        return DripEmailEvent::whereIn('subscription_id', $subscriptionIds)
            ->select(
                'subscription_id',
                DB::raw("SUM(CASE WHEN event_type = 'opened' THEN 1 ELSE 0 END) as opened_count"),
                DB::raw("MAX(CASE WHEN event_type = 'clicked' THEN 1 ELSE 0 END) as has_clicked")
            )
            ->groupBy('subscription_id')
            ->get()
            ->keyBy('subscription_id');
    }

    /**
     * Process a single subscription's pending emails.
     */
    public function processSubscription(DripSubscription $subscription): int
    {
        $shouldHaveSent = $this->getUnlockedLessonCount($subscription);
        $alreadySent = $subscription->emails_sent;

        if ($alreadySent >= $shouldHaveSent) {
            return 0;
        }

        $lessons = $subscription->course->lessons()
            ->orderBy('sort_order')
            ->get();

        $sentCount = 0;

        for ($i = $alreadySent; $i < $shouldHaveSent && $i < $lessons->count(); $i++) {
            $lesson = $lessons[$i];

            SendDripEmailJob::dispatch(
                $subscription->user_id,
                $lesson->id,
                $subscription->id
            );

            $sentCount++;
        }

        $newEmailsSent = $alreadySent + $sentCount;
        $totalLessons = $lessons->count();

        $updateData = ['emails_sent' => $newEmailsSent];

        // Mark as completed if all lessons sent
        if ($newEmailsSent >= $totalLessons) {
            $updateData['status'] = 'completed';
            $updateData['status_changed_at'] = now();
        }

        $subscription->update($updateData);

        return $sentCount;
    }
}
