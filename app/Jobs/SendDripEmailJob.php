<?php

namespace App\Jobs;

use App\Mail\DripLessonMail;
use App\Models\DripSubscription;
use App\Models\Lesson;
use App\Models\User;
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

        // Don't send if subscription is no longer active
        if ($subscription->status !== 'active') {
            Log::info('Drip email: Subscription no longer active, skipping', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
            ]);
            return;
        }

        $course = $lesson->course;
        $classroomUrl = config('app.url') . "/member/classroom/{$course->id}?lesson_id={$lesson->id}";
        $unsubscribeUrl = config('app.url') . "/drip/unsubscribe/{$subscription->unsubscribe_token}";

        $hasVideo = (bool) $lesson->has_video;
        $htmlContent = $lesson->html_content ?: '';

        try {
            Mail::to($user->email)->send(new DripLessonMail(
                lessonTitle: $lesson->title,
                htmlContent: $htmlContent,
                hasVideo: $hasVideo,
                classroomUrl: $classroomUrl,
                unsubscribeUrl: $unsubscribeUrl,
                courseName: $course->name,
            ));

            Log::info('Drip email sent', [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'course_id' => $course->id,
            ]);
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
