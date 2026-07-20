<?php

namespace Tests\Feature\Drip;

use App\Jobs\SendDripEmailJob;
use App\Models\Course;
use App\Models\DripEmailEvent;
use App\Models\DripSubscription;
use App\Models\Lesson;
use App\Models\User;
use App\Services\DripService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 010 US12: video free-viewing window anchors on the lesson's actual send time
 * (a 'sent' drip_email_event) when known, and falls back to the theoretical
 * unlock formula otherwise. SendDripEmailJob records the anchor after a
 * successful send, idempotently.
 */
class VideoAccessAnchorTest extends TestCase
{
    use RefreshDatabase;

    private function makeDripCourse(int $intervalDays = 3): Course
    {
        return Course::create([
            'name' => 'Drip C', 'slug' => 'drip-c', 'tagline' => 't', 'description' => 'd',
            'price' => 0, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'drip', 'drip_interval_days' => $intervalDays,
            'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);
    }

    private function makeLesson(Course $course, int $sortOrder, ?int $accessHours): Lesson
    {
        return Lesson::create([
            'course_id' => $course->id,
            'title' => "L{$sortOrder}",
            'video_platform' => 'vimeo',
            'video_id' => '1032766965',
            'video_access_hours' => $accessHours,
            'sort_order' => $sortOrder,
        ]);
    }

    private function makeSubscription(Course $course, User $user, Carbon $subscribedAt): DripSubscription
    {
        return DripSubscription::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'subscribed_at' => $subscribedAt,
            'emails_sent' => 3,
            'status' => 'active',
        ]);
    }

    public function test_expiry_falls_back_to_theoretical_unlock_when_no_sent_event(): void
    {
        $course = $this->makeDripCourse(intervalDays: 3);
        $lesson = $this->makeLesson($course, sortOrder: 2, accessHours: 48);
        $user = User::create(['email' => 'a@example.com', 'role' => 'member']);
        $sub = $this->makeSubscription($course, $user, Carbon::parse('2026-01-01 00:00:00'));

        // unlockDay = 2 * 3 = 6 days → 2026-01-07 00:00:00; + 48h → 2026-01-09 00:00:00
        $expiresAt = app(DripService::class)->getVideoAccessExpiresAt($sub, $lesson, null);

        $this->assertNotNull($expiresAt);
        $this->assertSame('2026-01-09 00:00:00', $expiresAt->format('Y-m-d H:i:s'));
    }

    public function test_expiry_anchors_on_actual_send_time_when_sent_event_exists(): void
    {
        $course = $this->makeDripCourse(intervalDays: 3);
        $lesson = $this->makeLesson($course, sortOrder: 2, accessHours: 48);
        $user = User::create(['email' => 'b@example.com', 'role' => 'member']);
        $sub = $this->makeSubscription($course, $user, Carbon::parse('2026-01-01 00:00:00'));

        // Send happened late (delayed catch-up) — 9 days after subscribe.
        DripEmailEvent::create([
            'subscription_id' => $sub->id,
            'lesson_id' => $lesson->id,
            'event_type' => 'sent',
        ]);
        DripEmailEvent::where('subscription_id', $sub->id)
            ->where('lesson_id', $lesson->id)
            ->where('event_type', 'sent')
            ->update(['created_at' => '2026-01-10 08:00:00']);

        $service = app(DripService::class);
        $sentAt = $service->getSentAtMap($sub)->get($lesson->id);

        $this->assertInstanceOf(Carbon::class, $sentAt);

        // Anchor 2026-01-10 08:00:00 + 48h → 2026-01-12 08:00:00 (NOT the theoretical 01-09).
        $expiresAt = $service->getVideoAccessExpiresAt($sub, $lesson, $sentAt);
        $this->assertSame('2026-01-12 08:00:00', $expiresAt->format('Y-m-d H:i:s'));
    }

    public function test_null_access_hours_yields_no_countdown(): void
    {
        $course = $this->makeDripCourse();
        $lesson = $this->makeLesson($course, sortOrder: 1, accessHours: null);
        $user = User::create(['email' => 'c@example.com', 'role' => 'member']);
        $sub = $this->makeSubscription($course, $user, Carbon::parse('2026-01-01 00:00:00'));

        $this->assertNull(app(DripService::class)->getVideoAccessExpiresAt($sub, $lesson, null));
    }

    public function test_job_records_sent_event_idempotently_after_send(): void
    {
        Mail::fake();

        $course = $this->makeDripCourse();
        $lesson = $this->makeLesson($course, sortOrder: 0, accessHours: 48);
        $user = User::create(['email' => 'd@example.com', 'role' => 'member']);
        $sub = $this->makeSubscription($course, $user, Carbon::parse('2026-01-01 00:00:00'));

        (new SendDripEmailJob($user->id, $lesson->id, $sub->id))->handle();
        (new SendDripEmailJob($user->id, $lesson->id, $sub->id))->handle(); // retry-safe

        $this->assertSame(1, DripEmailEvent::where('subscription_id', $sub->id)
            ->where('lesson_id', $lesson->id)
            ->where('event_type', 'sent')
            ->count());
    }
}
