<?php

namespace App\Jobs;

use App\Mail\DripLessonMail;
use App\Models\DripEmailEvent;
use App\Models\DripSubscription;
use App\Models\Lesson;
use App\Models\User;
use App\Services\DripService;
use App\Services\EmailLinkTagger;
use App\Services\EmailMarkdownService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

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

        $course = $lesson->course;
        $classroomUrl = config('app.url') . "/member/classroom/{$course->id}?lesson_id={$lesson->id}";
        $unsubscribeUrl = config('app.url') . "/drip/unsubscribe/{$subscription->unsubscribe_token}";

        // Source attribution for every first-party link in this letter (002
        // US14). Stamped here rather than stored, so existing lessons are
        // covered and the rules can change without rewriting any content.
        $tagger = app(EmailLinkTagger::class);
        $utm = [
            'utm_source'   => 'drip',
            'utm_medium'   => 'email',
            'utm_campaign' => $course->slug ?: (string) $course->id,
            'utm_content'  => 'lesson-' . app(DripService::class)->lessonNumber($lesson),
        ];

        $hasVideo = (bool) $lesson->has_video;
        $rawMd = str_replace('{{classroom_url}}', $tagger->tagUrl($classroomUrl, $utm), $lesson->md_content ?: '');
        // Single Enter is a real line break here too (011 FR-021).
        $htmlContent = $rawMd
            ? $tagger->tagHtml($this->stripStylesForEmail(EmailMarkdownService::toHtml($rawMd)), $utm)
            : '';

        // Generate open-tracking pixel URL (signed, 180-day expiry)
        $openPixelUrl = URL::signedRoute('drip.track.open', [
            'sub' => $subscription->id,
            'les' => $lesson->id,
        ], now()->addDays(180));

        try {
            Mail::to($user->email)->send(new DripLessonMail(
                lessonTitle: $lesson->title,
                htmlContent: $htmlContent,
                hasVideo: $hasVideo,
                classroomUrl: $classroomUrl,
                unsubscribeUrl: $unsubscribeUrl,
                courseName: $course->name,
                openPixelUrl: $openPixelUrl,
                videoAccessHours: $lesson->video_access_hours,
                greetingName: $this->resolveGreetingName($user),
            ));

            Log::info('Drip email sent', [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'course_id' => $course->id,
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

    /**
     * Resolve the greeting name from the user's nickname or real_name.
     * If exactly 3 Chinese characters, returns the last 2 (e.g., 王小明 → 小明).
     * Otherwise returns the full name. Returns empty string if no name is set.
     */
    private function resolveGreetingName(User $user): string
    {
        $name = $user->nickname ?: $user->real_name ?: '';
        if (!$name) {
            return '';
        }

        if (mb_strlen($name) === 3 && preg_match('/^[\x{4e00}-\x{9fff}]+$/u', $name)) {
            return mb_substr($name, 1);
        }

        return $name;
    }

    /**
     * Strip style/class attributes from HTML for cleaner email delivery.
     */
    private function stripStylesForEmail(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Remove style and class attributes
        $html = preg_replace('/\s*(style|class)="[^"]*"/i', '', $html);

        // Remove <style> blocks
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);

        return trim($html);
    }
}
