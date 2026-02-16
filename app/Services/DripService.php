<?php

namespace App\Services;

use App\Jobs\SendDripEmailJob;
use App\Models\Course;
use App\Models\DripConversionTarget;
use App\Models\DripSubscription;
use App\Models\Lesson;
use App\Models\User;
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
            SendDripEmailJob::dispatchSync(
                $user->id,
                $firstLesson->id,
                $subscription->id
            );

            $subscription->update(['emails_sent' => 1]);

            // Check if this was the only lesson
            $totalLessons = $course->lessons()->count();
            if ($subscription->emails_sent >= $totalLessons) {
                $subscription->update([
                    'status' => 'completed',
                    'status_changed_at' => now(),
                ]);
            }
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
     */
    public function isLessonUnlocked(DripSubscription $subscription, Lesson $lesson): bool
    {
        // Converted or completed subscribers can see all lessons
        if (in_array($subscription->status, ['converted', 'completed'])) {
            return true;
        }

        // Unsubscribed users: only show lessons unlocked up to unsubscription (based on emails_sent)
        if ($subscription->status === 'unsubscribed') {
            return $lesson->sort_order < $subscription->emails_sent;
        }

        return $lesson->sort_order < $this->getUnlockedLessonCount($subscription);
    }

    /**
     * Calculate days until a lesson unlocks.
     * Returns -1 for unsubscribed users (lesson will never unlock).
     */
    public function daysUntilUnlock(DripSubscription $subscription, Lesson $lesson): int
    {
        if ($this->isLessonUnlocked($subscription, $lesson)) {
            return 0;
        }

        // Unsubscribed users won't get new unlocks
        if ($subscription->status === 'unsubscribed') {
            return -1;
        }

        $interval = $subscription->course->drip_interval_days;
        $unlockDay = $lesson->sort_order * $interval;
        $daysSince = (int) $subscription->subscribed_at->diffInDays(now());

        return max(0, $unlockDay - $daysSince);
    }

    /**
     * Check if a purchased course triggers drip conversion.
     */
    public function checkAndConvert(User $user, Course $purchasedCourse): void
    {
        $dripCourseIds = DripConversionTarget::where('target_course_id', $purchasedCourse->id)
            ->pluck('drip_course_id');

        if ($dripCourseIds->isEmpty()) {
            return;
        }

        $subscriptions = DripSubscription::where('user_id', $user->id)
            ->whereIn('course_id', $dripCourseIds)
            ->where('status', 'active')
            ->get();

        foreach ($subscriptions as $subscription) {
            $subscription->update([
                'status' => 'converted',
                'status_changed_at' => now(),
            ]);

            Log::info('Drip subscription converted', [
                'subscription_id' => $subscription->id,
                'purchased_course_id' => $purchasedCourse->id,
            ]);
        }
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
