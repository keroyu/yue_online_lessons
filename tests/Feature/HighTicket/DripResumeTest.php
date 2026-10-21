<?php

namespace Tests\Feature\HighTicket;

use App\Jobs\SendDripEmailJob;
use App\Models\Course;
use App\Models\DripConversionTarget;
use App\Models\DripEmailEvent;
use App\Models\DripSubscription;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\Lesson;
use App\Models\User;
use App\Services\DripService;
use App\Services\HighTicketBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Support\BooksHighTicket;
use Tests\TestCase;

/**
 * 011 US48 — the sequence stops at a confirmed booking and nowhere earlier.
 *
 * Two ways a stopped sequence comes back: somebody the screening turned away
 * re-answers and passes, or a confirmed booking is cancelled (not refused).
 * Either way the owed emails go out one every three days, never in a burst.
 */
class DripResumeTest extends TestCase
{
    use BooksHighTicket, RefreshDatabase;

    private const EMAIL = 'booker@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Queue::fake();

        EmailTemplate::updateOrCreate(['event_type' => 'high_ticket_booking_confirmation'], [
            'name'       => '預約確認信',
            'event_type' => 'high_ticket_booking_confirmation',
            'subject'    => '{{course_name}} 預約確認',
            'body_md'    => 'Hi {{user_name}}',
        ]);
    }

    private function makeCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name'            => 'Course ' . uniqid(),
            'slug'            => 'course-' . uniqid(),
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 0,
            'instructor_name' => 'Tester',
            'type'            => 'lecture',
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ], $overrides));
    }

    private function makeHighTicketCourse(): Course
    {
        return $this->makeCourse([
            'type'                   => 'high_ticket',
            'price'                  => 58000,
            'high_ticket_hide_price' => true,
        ])->fresh();
    }

    /** A drip course converting into $target, lessons on days 0/3/7/14/30. */
    private function makeDrip(Course $target, array $days = [0, 3, 7, 14, 30]): Course
    {
        $drip = $this->makeCourse(['course_type' => 'drip', 'drip_interval_days' => 3]);
        DripConversionTarget::create(['drip_course_id' => $drip->id, 'target_course_id' => $target->id]);

        foreach ($days as $i => $day) {
            Lesson::create([
                'course_id'      => $drip->id,
                'title'          => "L{$i}",
                'video_platform' => 'vimeo',
                'video_id'       => '1032766965',
                'sort_order'     => $i,
                'drip_day'       => $day,
            ]);
        }

        return $drip;
    }

    private function subscribe(Course $drip, string $status, int $emailsSent = 1, int $daysAgo = 30): DripSubscription
    {
        $user = User::firstOrCreate(['email' => self::EMAIL], ['role' => 'member']);

        return DripSubscription::create([
            'user_id'       => $user->id,
            'course_id'     => $drip->id,
            'subscribed_at' => now()->subDays($daysAgo),
            'emails_sent'   => $emailsSent,
            'status'        => $status,
        ]);
    }

    private function screeningPayload(array $overrides = []): array
    {
        return array_merge([
            'name'              => 'Booker',
            'email'             => self::EMAIL,
            'screen_ack'        => true,
            'screen_timeline'   => 'immediate',
            'screen_budget'     => '10k_50k',
            'screen_authority'  => 'self',
            'screen_pain'       => 'severe',
            'screen_next_step'  => 'start_now',
            'screen_experience' => '1_3y',
            'screen_learning'   => 'paid_course',
        ], $overrides);
    }

    private function sentEvent(DripSubscription $sub, Carbon $at): void
    {
        $event = DripEmailEvent::create([
            'subscription_id' => $sub->id,
            'lesson_id'       => $sub->course->lessons()->orderBy('sort_order')->first()->id,
            'event_type'      => 'sent',
        ]);
        $event->forceFill(['created_at' => $at])->save();
    }

    // ── 重答審核通過（FR-274） ──────────────────────────────────────────────

    public function test_passing_a_re_screen_resumes_the_sequence_the_refusal_stopped(): void
    {
        $target = $this->makeHighTicketCourse();
        $sub = $this->subscribe($this->makeDrip($target), 'active');

        $this->postJson("/course/{$target->id}/screen", $this->screeningPayload(['screen_budget' => 'none']))
            ->assertOk()->assertJson(['passed' => false]);
        $this->assertSame('declined', $sub->fresh()->status);

        $this->postJson("/course/{$target->id}/screen", $this->screeningPayload())
            ->assertOk()->assertJson(['passed' => true]);

        $sub->refresh();
        $this->assertSame('active', $sub->status);
        $this->assertNotNull($sub->resumed_at);
    }

    /** A status an admin moved by hand is not a pending application. */
    public function test_a_re_screen_on_a_lead_an_admin_moved_leaves_the_sequence_stopped(): void
    {
        $target = $this->makeHighTicketCourse();
        $sub = $this->subscribe($this->makeDrip($target), 'declined');

        HighTicketLead::create([
            'name' => 'Booker', 'email' => self::EMAIL, 'course_id' => $target->id,
            'status' => 'contacted', 'booked_at' => now(),
        ]);

        $this->postJson("/course/{$target->id}/screen", $this->screeningPayload())
            ->assertOk()->assertJson(['passed' => true]);

        $this->assertSame('declined', $sub->fresh()->status);
    }

    // ── 取消預約（FR-275） ─────────────────────────────────────────────────

    public function test_cancelling_a_confirmed_booking_resumes_the_sequence(): void
    {
        $target = $this->makeHighTicketCourse();
        $sub = $this->subscribe($this->makeDrip($target), 'active');

        $this->applyAndConfirm($target);
        $this->assertSame('booked', $sub->fresh()->status);

        $lead = HighTicketLead::where('email', self::EMAIL)->firstOrFail();
        app(HighTicketBookingService::class)->cancel($lead);

        $sub->refresh();
        $this->assertSame('active', $sub->status);
        $this->assertNotNull($sub->resumed_at);
    }

    /** D107 — refused people get no marketing mail. */
    public function test_declining_a_confirmed_booking_keeps_the_sequence_stopped(): void
    {
        $target = $this->makeHighTicketCourse();
        $sub = $this->subscribe($this->makeDrip($target), 'active');

        $this->applyAndConfirm($target);
        $lead = HighTicketLead::where('email', self::EMAIL)->firstOrFail();
        app(HighTicketBookingService::class)->decline($lead);

        $this->assertSame('booked', $sub->fresh()->status);
    }

    public function test_cancelling_one_of_two_live_bookings_keeps_the_sequence_stopped(): void
    {
        $target = $this->makeHighTicketCourse();
        $sub = $this->subscribe($this->makeDrip($target), 'booked');

        $lead = HighTicketLead::create([
            'name' => 'Booker', 'email' => self::EMAIL, 'course_id' => $target->id,
            'status' => 'pending', 'booked_at' => now(), 'confirmed_at' => now(),
        ]);
        HighTicketLead::create([
            'name' => 'Booker', 'email' => self::EMAIL, 'course_id' => $target->id,
            'status' => 'pending', 'booked_at' => now(), 'confirmed_at' => now(),
        ]);

        app(HighTicketBookingService::class)->cancel($lead);

        $this->assertSame('booked', $sub->fresh()->status);
    }

    /** A paying customer's consultation never stopped the sequence (011 FR-232). */
    public function test_cancelling_a_credit_booking_leaves_the_sequence_alone(): void
    {
        $target = $this->makeHighTicketCourse();
        $sub = $this->subscribe($this->makeDrip($target), 'booked');

        $lead = HighTicketLead::create([
            'name' => 'Booker', 'email' => self::EMAIL, 'course_id' => $target->id, 'kind' => 'credit',
            'status' => 'pending', 'booked_at' => now(), 'confirmed_at' => now(),
        ]);

        app(HighTicketBookingService::class)->cancel($lead);

        $this->assertSame('booked', $sub->fresh()->status);
    }

    /** Best-effort like every other drip side effect (比照 FR-016). */
    public function test_a_failing_resume_does_not_break_the_cancellation(): void
    {
        $target = $this->makeHighTicketCourse();
        $this->subscribe($this->makeDrip($target), 'booked');

        $lead = HighTicketLead::create([
            'name' => 'Booker', 'email' => self::EMAIL, 'course_id' => $target->id,
            'status' => 'pending', 'booked_at' => now(), 'confirmed_at' => now(),
        ]);

        $this->mock(DripService::class)
            ->shouldReceive('resumeSequence')->andThrow(new \RuntimeException('boom'));

        $result = app(HighTicketBookingService::class)->cancel($lead);

        $this->assertTrue($result['success']);
        $this->assertSame('cancelled', $lead->fresh()->status);
    }

    // ── resumeSequence() 白名單（FR-276） ──────────────────────────────────

    public function test_only_screening_and_booking_stops_can_be_resumed(): void
    {
        $target = $this->makeHighTicketCourse();

        $this->expectException(\InvalidArgumentException::class);

        app(DripService::class)->resumeSequence(self::EMAIL, $target, ['converted']);
    }

    public function test_finished_or_unsubscribed_sequences_are_never_picked_up(): void
    {
        $target = $this->makeHighTicketCourse();
        $drip = $this->makeDrip($target);

        foreach (['converted', 'unsubscribed', 'completed'] as $status) {
            $sub = $this->subscribe($drip, $status);

            $count = app(DripService::class)->resumeSequence(self::EMAIL, $target, ['declined', 'booked']);

            $this->assertSame(0, $count);
            $this->assertSame($status, $sub->fresh()->status);
            $sub->delete();
        }
    }

    // ── 恢復後每 3 天 1 封（FR-276） ───────────────────────────────────────

    public function test_a_resumed_sequence_sends_one_email_now_instead_of_everything_owed(): void
    {
        $target = $this->makeHighTicketCourse();
        $sub = $this->subscribe($this->makeDrip($target), 'declined', emailsSent: 1);
        $this->sentEvent($sub, now()->subDays(20));

        app(DripService::class)->resumeSequence(self::EMAIL, $target, ['declined']);

        // Thirty days in, all five lessons are owed; only one goes out.
        $this->assertSame(1, app(DripService::class)->processDailyEmails());
        $this->assertSame(2, $sub->fresh()->emails_sent);
        Queue::assertPushed(SendDripEmailJob::class, 1);
    }

    public function test_the_next_email_waits_three_taipei_calendar_days_after_the_last_one(): void
    {
        $target = $this->makeHighTicketCourse();
        $sub = $this->subscribe($this->makeDrip($target), 'declined', emailsSent: 2);
        app(DripService::class)->resumeSequence(self::EMAIL, $target, ['declined']);

        // Sent 23:30 Taipei on the 20th; the 22nd is two calendar days later.
        $this->travelTo(Carbon::parse('2026-10-22 09:00', 'Asia/Taipei'));
        $this->sentEvent($sub, Carbon::parse('2026-10-20 23:30', 'Asia/Taipei')->utc());
        $this->assertSame(0, app(DripService::class)->processDailyEmails());

        $this->travelTo(Carbon::parse('2026-10-23 09:00', 'Asia/Taipei'));
        $this->assertSame(1, app(DripService::class)->processDailyEmails());
        $this->assertSame(3, $sub->fresh()->emails_sent);
    }

    /** Resuming never makes a slower part of the schedule go faster. */
    public function test_the_original_schedule_still_wins_when_it_is_slower(): void
    {
        $target = $this->makeHighTicketCourse();
        // Day 9: lessons on days 0/3/7 are due, day 14 is not.
        $sub = $this->subscribe($this->makeDrip($target), 'declined', emailsSent: 3, daysAgo: 9);
        $this->sentEvent($sub, now()->subDays(5));

        app(DripService::class)->resumeSequence(self::EMAIL, $target, ['declined']);

        $this->assertSame(0, app(DripService::class)->processDailyEmails());
        $this->assertSame(3, $sub->fresh()->emails_sent);
    }

    public function test_a_subscription_that_was_never_stopped_keeps_its_catch_up(): void
    {
        $target = $this->makeHighTicketCourse();
        $sub = $this->subscribe($this->makeDrip($target), 'active', emailsSent: 1);

        $this->assertSame(4, app(DripService::class)->processDailyEmails());
        $this->assertSame('completed', $sub->fresh()->status);
    }

    // ── 既有資料回補（FR-277） ─────────────────────────────────────────────

    public function test_the_backfill_resumes_only_unconfirmed_or_cancelled_leads(): void
    {
        $target = $this->makeHighTicketCourse();
        $drip = $this->makeDrip($target);

        $cases = [
            // [drip status, latest lead attributes, expected drip status]
            'passed re-screen'  => ['declined', ['status' => 'pending'], 'active'],
            'still declined'    => ['declined', ['status' => 'declined'], 'declined'],
            'cancelled booking' => ['booked', ['status' => 'cancelled', 'confirmed_at' => now(), 'cancelled_at' => now()], 'active'],
            'never confirmed'   => ['booked', ['status' => 'cancelled'], 'active'],
            'live booking'      => ['booked', ['status' => 'closed', 'confirmed_at' => now()], 'booked'],
            'unconfirmed admin' => ['booked', ['status' => 'contacted'], 'booked'],
        ];

        $subs = [];
        foreach ($cases as $label => [$dripStatus, $lead, $expected]) {
            $email = str_replace(' ', '-', $label) . '@example.com';
            $user = User::create(['email' => $email, 'role' => 'member']);
            $subs[$label] = DripSubscription::create([
                'user_id' => $user->id, 'course_id' => $drip->id, 'subscribed_at' => now()->subDays(30),
                'emails_sent' => 1, 'status' => $dripStatus,
            ]);
            HighTicketLead::create(array_merge([
                'name' => $label, 'email' => $email, 'course_id' => $target->id, 'booked_at' => now(),
            ], $lead));
        }

        // An older live booking under a newer cancelled one still counts as live.
        $older = User::create(['email' => 'older@example.com', 'role' => 'member']);
        $olderSub = DripSubscription::create([
            'user_id' => $older->id, 'course_id' => $drip->id, 'subscribed_at' => now()->subDays(30),
            'emails_sent' => 1, 'status' => 'booked',
        ]);
        HighTicketLead::create(['name' => 'o', 'email' => 'older@example.com', 'course_id' => $target->id,
            'booked_at' => now(), 'status' => 'closed', 'confirmed_at' => now()]);
        HighTicketLead::create(['name' => 'o', 'email' => 'older@example.com', 'course_id' => $target->id,
            'booked_at' => now(), 'status' => 'cancelled']);

        (require database_path('migrations/2026_10_22_000002_resume_stopped_drips_for_unconfirmed_leads.php'))->up();

        foreach ($cases as $label => [, , $expected]) {
            $this->assertSame($expected, $subs[$label]->fresh()->status, $label);
        }
        $this->assertNotNull($subs['passed re-screen']->fresh()->resumed_at);
        $this->assertSame('booked', $olderSub->fresh()->status);
    }
}
