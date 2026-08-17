<?php

namespace Tests\Feature\HighTicket;

use App\Mail\TemplatedMail;
use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Services\ConsultationSlotService;
use App\Services\HighTicketBookingService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 011 US19 — the day-before reminder.
 *
 * Every assertion here is really about one thing: "tomorrow" is a Taipei date
 * on a server that runs UTC. The two boundary tests (00:00 and 23:30 Taipei)
 * are the ones that fail the moment somebody computes the window in UTC — and
 * they fail silently in production, by sending nothing.
 */
class ConsultationReminderTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = ConsultationSlotService::DISPLAY_TZ;

    protected function setUp(): void
    {
        parent::setUp();

        // A fixed "now" so tomorrow is always 2026-08-11 Taipei. 09:00 rather
        // than 17:00 because the command must not care what time it is run —
        // only the schedule decides that.
        // Mocked as UTC on purpose: Carbon hands its test-now timezone to every
        // instance it creates, including the ones Eloquent builds from datetime
        // columns. A Taipei-flavoured mock would label stored UTC values +08:00
        // and every comparison in here would be quietly eight hours out —
        // production runs UTC and the test has to agree with it.
        Carbon::setTestNow(Carbon::parse('2026-08-10 09:00', self::TZ)->utc());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeHighTicketCourse(): Course
    {
        return Course::create([
            'name'                   => 'HT Course',
            'slug'                   => 'ht-' . uniqid(),
            'tagline'                => 'tag',
            'description'            => 'desc',
            'price'                  => 50000,
            'instructor_name'        => 'Tester',
            'type'                   => 'high_ticket',
            'status'                 => 'selling',
            'course_type'            => 'standard',
            'is_published'           => true,
            'is_visible'             => true,
            'payment_gateway'        => 'payuni',
            'high_ticket_hide_price' => true,
        ])->fresh();
    }

    private function seedReminderTemplate(): void
    {
        EmailTemplate::updateOrCreate(['event_type' => 'high_ticket_consultation_reminder'], [
            'name'       => '面談提醒',
            'event_type' => 'high_ticket_consultation_reminder',
            'subject'    => '【明天見】{{course_name}} — {{slot_time}}',
            'body_md'    => "{{user_name}} 您好\n\n時段：{{slot_time}}（{{consult_minutes}} 分鐘）\n\n連結：{{zoom_join_url}}",
        ]);
    }

    /**
     * A confirmed booking sitting on real slot rows, built directly rather than
     * through apply()/confirm(): the boundary cases here (00:00, 23:30) are
     * legitimate stored state, but the public picker only ever offers starts on
     * the hour or half hour (FR-069), so the front door cannot produce them.
     */
    private function bookingAt(Course $course, string $taipeiTime, int $minutes = 30, array $attrs = []): HighTicketLead
    {
        $start = Carbon::parse($taipeiTime, self::TZ)->utc();

        $lead = HighTicketLead::create(array_merge([
            'name'         => 'Booker',
            'email'        => 'booker' . uniqid() . '@example.com',
            'course_id'    => $course->id,
            'status'       => 'pending',
            'booked_at'    => now(),
            'confirmed_at' => now(),
            'zoom_join_url' => 'https://zoom.us/j/123',
        ], $attrs));

        $units = intdiv($minutes, ConsultationSlot::UNIT_MINUTES);

        for ($i = 0; $i < $units; $i++) {
            ConsultationSlot::updateOrCreate(
                ['starts_at' => $start->copy()->addMinutes($i * ConsultationSlot::UNIT_MINUTES)],
                ['lead_id' => $lead->id, 'held_until' => null],
            );
        }

        return $lead->fresh();
    }

    // ------------------------------------------------------------- schedule

    /** FR-076: 17:00 Taipei, stated as such — the server clock is UTC. */
    public function test_reminder_is_scheduled_at_five_pm_taipei_time(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'booking:send-reminders'));

        $this->assertNotNull($event, '排程未註冊 booking:send-reminders');
        $this->assertSame('0 17 * * *', $event->expression);
        $this->assertSame(self::TZ, (string) $event->timezone);
    }

    // ---------------------------------------------------------------- window

    public function test_it_reminds_a_confirmed_booking_scheduled_for_tomorrow(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedReminderTemplate();
        Mail::fake();

        $lead = $this->bookingAt($course, '2026-08-11 10:00');

        $this->artisan('booking:send-reminders')
            ->expectsOutputToContain('已寄出 1 封面談提醒')
            ->assertSuccessful();

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($lead->email));
        $this->assertNotNull($lead->fresh()->reminder_sent_at);
    }

    /**
     * Taipei 00:00 tomorrow is 16:00 UTC *today*, and Taipei 23:30 tomorrow is
     * 15:30 UTC tomorrow — a UTC-based window catches neither.
     */
    public function test_it_covers_both_ends_of_the_taipei_day(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedReminderTemplate();
        Mail::fake();

        $earliest = $this->bookingAt($course, '2026-08-11 00:00');
        $latest = $this->bookingAt($course, '2026-08-11 23:30');

        $this->artisan('booking:send-reminders')->assertSuccessful();

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($earliest->email));
        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($latest->email));
    }

    public function test_it_ignores_bookings_today_and_the_day_after_tomorrow(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedReminderTemplate();
        Mail::fake();

        $this->bookingAt($course, '2026-08-10 15:00');
        $this->bookingAt($course, '2026-08-12 10:00');

        $this->artisan('booking:send-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    /**
     * FR-077: the anchor is the lead's earliest unit. A consultation that starts
     * tonight and runs past midnight belongs to today — yesterday's run already
     * had its chance.
     */
    public function test_a_booking_that_starts_today_and_crosses_midnight_is_not_reminded(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedReminderTemplate();
        Mail::fake();

        $this->bookingAt($course, '2026-08-10 23:30', 45);

        $this->artisan('booking:send-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    /** ...and the mirror case is reminded exactly once, not once per day it touches. */
    public function test_a_booking_that_crosses_midnight_into_the_day_after_is_reminded_once(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedReminderTemplate();
        Mail::fake();

        $this->bookingAt($course, '2026-08-11 23:30', 45);

        $this->artisan('booking:send-reminders')->assertSuccessful();

        Mail::assertSentCount(1);
    }

    // ------------------------------------------------------------ who counts

    public function test_it_skips_unconfirmed_and_cancelled_bookings(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedReminderTemplate();
        Mail::fake();

        // Applied but never clicked the verify link: the slot is only held.
        $held = $this->bookingAt($course, '2026-08-11 10:00', 30, ['confirmed_at' => null]);
        $held->slots()->update(['held_until' => now()->addHour()]);

        $this->bookingAt($course, '2026-08-11 14:00', 30, ['cancelled_at' => now(), 'status' => 'cancelled']);

        $this->artisan('booking:send-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    /** FR-078: status is a sales-funnel label, not an attendance one. */
    public function test_funnel_status_does_not_filter_the_reminder(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedReminderTemplate();
        Mail::fake();

        $lead = $this->bookingAt($course, '2026-08-11 10:00', 30, ['status' => 'contacted']);

        $this->artisan('booking:send-reminders')->assertSuccessful();

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($lead->email));
    }

    // ------------------------------------------------------------ one a day

    public function test_running_the_command_twice_does_not_send_a_second_reminder(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedReminderTemplate();
        Mail::fake();

        $this->bookingAt($course, '2026-08-11 10:00');

        $this->artisan('booking:send-reminders')->assertSuccessful();
        $this->artisan('booking:send-reminders')->assertSuccessful();

        Mail::assertSentCount(1);
    }

    /** D72: moved to another day, so it is owed another reminder. */
    public function test_rescheduling_clears_the_reminder_flag(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedReminderTemplate();
        Mail::fake();

        $lead = $this->bookingAt($course, '2026-08-11 10:00');

        $this->artisan('booking:send-reminders')->assertSuccessful();
        $this->assertNotNull($lead->fresh()->reminder_sent_at);

        $newStart = Carbon::parse('2026-08-13 10:00', self::TZ)->utc();

        foreach ([0, 15] as $offset) {
            ConsultationSlot::firstOrCreate(['starts_at' => $newStart->copy()->addMinutes($offset)]);
        }

        app(HighTicketBookingService::class)->reschedule($lead->fresh(), $newStart);

        $this->assertNull($lead->fresh()->reminder_sent_at);
    }

    // --------------------------------------------------------------- content

    public function test_the_reminder_carries_the_slot_time_and_no_attachment(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedReminderTemplate();
        Mail::fake();

        $lead = $this->bookingAt($course, '2026-08-11 10:00');
        $label = app(ConsultationSlotService::class)->label($lead->slots()->first()->starts_at);

        $this->artisan('booking:send-reminders')->assertSuccessful();

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) use ($label) {
            return str_contains($mail->emailSubject, $label)
                && str_contains($mail->htmlBody, $label)
                && str_contains($mail->htmlBody, 'https://zoom.us/j/123')
                && $mail->fileAttachments === [];
        });
    }

    /**
     * FR-078 (2026-08-17 改): the consultant is the other person who has to show
     * up tomorrow, and nothing else in the system tells them so — the invite was
     * mailed at confirmation, possibly weeks ago.
     */
    public function test_the_reminder_ccs_the_assigned_consultant(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedReminderTemplate();
        Mail::fake();

        $consultant = \App\Models\User::factory()->create([
            'role'  => 'member',
            'email' => 'consultant@example.com',
        ]);

        $lead = $this->bookingAt($course, '2026-08-11 10:00');
        $lead->forceFill(['consultant_id' => $consultant->id])->save();

        $this->artisan('booking:send-reminders')->assertSuccessful();

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) =>
            collect($mail->cc)->pluck('address')->contains('consultant@example.com'));
    }

    /** Nobody assigned still means somebody has to be there (FR-062 fallback). */
    public function test_an_unassigned_reminder_falls_back_to_the_notify_list(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedReminderTemplate();
        \App\Models\SiteSetting::set(HighTicketBookingService::NOTIFY_CC_SETTING_KEY, 'ops@example.com');
        Mail::fake();

        $this->bookingAt($course, '2026-08-11 10:00');

        $this->artisan('booking:send-reminders')->assertSuccessful();

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) =>
            collect($mail->cc)->pluck('address')->contains('ops@example.com'));
    }

    // -------------------------------------------------------------- failures

    /** FR-081: a missing template must not turn the schedule red. */
    public function test_a_missing_template_sends_nothing_and_still_succeeds(): void
    {
        $course = $this->makeHighTicketCourse();
        EmailTemplate::where('event_type', 'high_ticket_consultation_reminder')->delete();
        Mail::fake();

        $lead = $this->bookingAt($course, '2026-08-11 10:00');

        $this->artisan('booking:send-reminders')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertNull($lead->fresh()->reminder_sent_at);
    }
}
