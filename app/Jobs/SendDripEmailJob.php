<?php

namespace App\Jobs;

use App\Models\DripEmailEvent;
use App\Models\DripSubscription;
use App\Models\Lesson;
use App\Models\User;
use App\Services\DripService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDripEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public int $userId,
        public int $lessonId,
        public int $subscriptionId
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        $lesson = Lesson::with('course')->find($this->lessonId);
        $subscription = DripSubscription::find($this->subscriptionId);

        if (!$user || !$lesson || !$subscription) {
            Log::warning('Drip email: Missing data', [
                'user_id' => $this->userId,
                'lesson_id' => $this->lessonId,
                'subscription_id' => $this->subscriptionId,
            ]);
            return;
        }

        // Don't send once the sequence has stopped — unsubscribed, or the funnel
        // goal was reached (booked / bought a target course).
        // 'completed' is allowed: status is set to completed at the same time the last
        // job is dispatched, so the job must still run to deliver that final email.
        if (in_array($subscription->status, DripSubscription::STOPS_SENDING)) {
            Log::info('Drip email: Sequence stopped for this subscription, skipping', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
            ]);
            return;
        }

        // Composition lives in DripService so the admin preview renders the very
        // same mail (US17, FR-031).
        $mail = app(DripService::class)->buildLessonMail($lesson, $subscription, $user);

        try {
            Mail::to($user->email)->send($mail);

            Log::info('Drip email sent', [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'course_id' => $lesson->course_id,
            ]);

            // Record the actual send time as the video free-viewing window anchor.
            // Recorded only after a successful send so a delivery failure (which
            // triggers a retry) never leaves a stale anchor. firstOrCreate keeps it
            // idempotent across retries; a write failure only logs (same policy as
            // open/click events) and must not re-trigger the email.
            try {
                DripEmailEvent::firstOrCreate([
                    'subscription_id' => $subscription->id,
                    'lesson_id' => $lesson->id,
                    'event_type' => 'sent',
                ]);
            } catch (\Throwable $e) {
                Log::warning('Drip email: failed to record sent event', [
                    'subscription_id' => $subscription->id,
                    'lesson_id' => $lesson->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Drip email failed', [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
