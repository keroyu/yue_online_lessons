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
                return ['success' => false, 'error' => '此課程已無法再次訂閱'];
            }
            return ['success' => false, 'error' => '您已訂閱此課程'];
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

        return $lesson->sort_order < $subscription->emails_sent;
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
        $unlockDay = $lesson->sort_order * $interval;
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
            ->addDays($lesson->sort_order * $subscription->course->drip_interval_days);

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

        $lessonStats = $lessons->map(function (Lesson $lesson) use ($eventStats, $course) {
            // sent_count = subscriptions where emails_sent > sort_order (i.e., this lesson was sent)
            $sentCount = DripSubscription::where('course_id', $course->id)
                ->where('emails_sent', '>', $lesson->sort_order)
                ->count();

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
