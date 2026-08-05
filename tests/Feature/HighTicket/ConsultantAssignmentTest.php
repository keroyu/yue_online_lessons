<?php

namespace Tests\Feature\HighTicket;

use App\Mail\BookingVerifyMail;
use App\Mail\TemplatedMail;
use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ConsultationSlotService;
use App\Services\HighTicketBookingService;
use App\Services\ZoomMeetingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\BooksHighTicket;
use Tests\TestCase;

/**
 * 011 US15 — slot ownership, the snapshot onto the lead, the CC rules that
 * collapse to a single mail, and the optional Zoom host.
 */
class ConsultantAssignmentTest extends TestCase
{
    use BooksHighTicket, RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function consultant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'                => 'member',
            'is_sales_consultant' => true,
        ], $overrides));
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

    private function seedTemplates(): void
    {
        foreach ([
            ['預約確認', 'high_ticket_booking_confirmation', '{{course_name}} 預約確認', '時段 {{slot_time}}'],
            ['預約已改期', 'high_ticket_booking_rescheduled', '{{course_name}} 時段已更新', '{{old_slot_time}} → {{slot_time}}'],
            ['預約已取消', 'high_ticket_booking_cancelled', '{{course_name}} 預約已取消', '{{slot_time}} 已取消'],
        ] as [$name, $event, $subject, $body]) {
            EmailTemplate::updateOrCreate(['event_type' => $event], [
                'name' => $name, 'event_type' => $event, 'subject' => $subject, 'body_md' => $body,
            ]);
        }
    }

    private function slots(): ConsultationSlotService
    {
        return app(ConsultationSlotService::class);
    }

    /** Taipei wall-clock, the way the grid posts it. */
    private function taipei(string $local): array
    {
        $at = Carbon::parse($local, ConsultationSlotService::DISPLAY_TZ);

        return ['date' => $at->format('Y-m-d'), 'start_time' => $at->format('H:i')];
    }

    // ------------------------------------------------------- slot ownership

    public function test_generating_slots_records_the_consultant(): void
    {
        $consultant = $this->consultant();
        $from = Carbon::parse('+2 days 10:00', ConsultationSlotService::DISPLAY_TZ)->utc();

        $this->slots()->generate($from, $from->copy()->addMinutes(30), $consultant->id);

        $this->assertSame(2, ConsultationSlot::where('consultant_id', $consultant->id)->count());
    }

    public function test_slots_may_be_left_unassigned(): void
    {
        $from = Carbon::parse('+2 days 10:00', ConsultationSlotService::DISPLAY_TZ)->utc();

        $this->slots()->generate($from, $from->copy()->addMinutes(30));

        $this->assertSame(2, ConsultationSlot::whereNull('consultant_id')->count());
    }

    public function test_a_consultant_creating_slots_gets_them_assigned_to_themselves(): void
    {
        $consultant = $this->consultant();
        $payload = $this->taipei('+2 days 10:00');

        $this->actingAs($consultant)
            ->post('/admin/consultation-slots', array_merge($payload, ['end_time' => '10:30']))
            ->assertRedirect();

        $this->assertSame(2, ConsultationSlot::where('consultant_id', $consultant->id)->count());
    }

    /** FR-060: the lock in the UI is a courtesy; the endpoint is the control. */
    public function test_a_consultant_cannot_assign_slots_to_somebody_else(): void
    {
        $consultant = $this->consultant();
        $other = $this->consultant();
        $payload = $this->taipei('+2 days 10:00');

        $this->actingAs($consultant)
            ->post('/admin/consultation-slots', array_merge($payload, [
                'end_time'      => '10:30',
                'consultant_id' => $other->id,
            ]))
            ->assertSessionHasErrors('consultant_id');

        $this->assertSame(0, ConsultationSlot::count());
    }

    public function test_an_admin_may_assign_slots_to_any_consultant(): void
    {
        $consultant = $this->consultant();
        $payload = $this->taipei('+2 days 10:00');

        $this->actingAs($this->admin())
            ->post('/admin/consultation-slots', array_merge($payload, [
                'end_time'      => '10:30',
                'consultant_id' => $consultant->id,
            ]))
            ->assertRedirect();

        $this->assertSame(2, ConsultationSlot::where('consultant_id', $consultant->id)->count());
    }

    // ------------------------------------------------------------- snapshot

    public function test_confirming_snapshots_the_consultant_onto_the_lead(): void
    {
        $consultant = $this->consultant();
        $this->seedTemplates();
        Mail::fake();

        $course = $this->makeHighTicketCourse();
        $start = $this->seedSlots();
        ConsultationSlot::query()->update(['consultant_id' => $consultant->id]);

        $this->applyAndConfirm($course, ['slot_starts_at' => $start->toIso8601String()]);

        $lead = HighTicketLead::firstOrFail();
        $this->assertSame($consultant->id, $lead->consultant_id);
    }

    /** D58: reassigning the slot afterwards must not rewrite settled history. */
    public function test_the_snapshot_survives_the_slot_being_reassigned(): void
    {
        $first = $this->consultant();
        $second = $this->consultant();
        $this->seedTemplates();
        Mail::fake();

        $course = $this->makeHighTicketCourse();
        $start = $this->seedSlots();
        ConsultationSlot::query()->update(['consultant_id' => $first->id]);
        $this->applyAndConfirm($course, ['slot_starts_at' => $start->toIso8601String()]);

        ConsultationSlot::query()->update(['consultant_id' => $second->id]);

        $this->assertSame($first->id, HighTicketLead::firstOrFail()->fresh()->consultant_id);
    }

    // ----------------------------------------------------------- CC rules

    /**
     * FR-062: the confirmation copies the consultant and nobody else — the
     * support list was a second copy of the same news.
     */
    public function test_the_confirmation_ccs_only_the_consultant(): void
    {
        $consultant = $this->consultant(['email' => 'consultant@example.com']);
        SiteSetting::set(HighTicketBookingService::NOTIFY_CC_SETTING_KEY, 'ops@example.com');
        $this->seedTemplates();
        Mail::fake();

        $course = $this->makeHighTicketCourse();
        $start = $this->seedSlots();
        ConsultationSlot::query()->update(['consultant_id' => $consultant->id]);

        $this->applyAndConfirm($course, ['slot_starts_at' => $start->toIso8601String()]);

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            if (!str_contains($mail->emailSubject, '預約確認')) {
                return false;
            }

            $cc = collect($mail->cc)->pluck('address');

            return $cc->contains('consultant@example.com')
                && !$cc->contains('ops@example.com');
        });
    }

    /** The support list survives only here: an unassigned booking must still land somewhere. */
    public function test_an_unassigned_booking_still_ccs_support(): void
    {
        SiteSetting::set(HighTicketBookingService::NOTIFY_CC_SETTING_KEY, 'ops@example.com');
        $this->seedTemplates();
        Mail::fake();

        $this->applyAndConfirm($this->makeHighTicketCourse());

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => str_contains($mail->emailSubject, '預約確認')
            && collect($mail->cc)->pluck('address')->contains('ops@example.com'));
    }

    public function test_the_verify_mail_ccs_nobody(): void
    {
        SiteSetting::set(HighTicketBookingService::NOTIFY_CC_SETTING_KEY, 'ops@example.com');
        Mail::fake();

        $this->applyForBooking($this->makeHighTicketCourse());

        Mail::assertSent(BookingVerifyMail::class, fn (BookingVerifyMail $mail) => $mail->cc === []);
    }

    public function test_the_change_mails_cc_nobody(): void
    {
        $consultant = $this->consultant(['email' => 'consultant@example.com']);
        SiteSetting::set(HighTicketBookingService::NOTIFY_CC_SETTING_KEY, 'ops@example.com');
        $this->seedTemplates();
        Mail::fake();

        $course = $this->makeHighTicketCourse();
        $start = $this->seedSlots();
        ConsultationSlot::query()->update(['consultant_id' => $consultant->id]);
        $this->applyAndConfirm($course, ['slot_starts_at' => $start->toIso8601String()]);

        $lead = HighTicketLead::firstOrFail();
        $later = $start->copy()->addHours(3);
        for ($i = 0; $i < 2; $i++) {
            ConsultationSlot::firstOrCreate(['starts_at' => $later->copy()->addMinutes($i * 15)]);
        }

        Mail::fake();
        app(HighTicketBookingService::class)->reschedule($lead, $later);
        app(HighTicketBookingService::class)->cancel($lead->fresh());

        foreach (['時段已更新', '預約已取消'] as $subject) {
            Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => str_contains($mail->emailSubject, $subject)
                && $mail->cc === []);
        }
    }

    // ---------------------------------------------------------- Zoom host

    public function test_the_meeting_is_created_under_the_consultant(): void
    {
        $this->configureZoom();
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 't']),
            'api.zoom.us/v2/users/*' => Http::response(['id' => 1, 'join_url' => 'https://zoom.us/j/1']),
        ]);

        app(ZoomMeetingService::class)->createMeeting(
            Carbon::parse('2030-01-01 06:00:00', 'UTC'),
            30,
            'topic',
            'consultant@example.com'
        );

        Http::assertSent(fn ($request) => str_contains($request->url(), '/users/consultant@example.com/meetings'));
    }

    /** D60: no Zoom seat yet is the expected state, not a failure. */
    public function test_a_consultant_without_a_zoom_seat_falls_back_to_the_owner(): void
    {
        $this->configureZoom();
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 't']),
            'api.zoom.us/v2/users/consultant@example.com/meetings' => Http::response(['code' => 1001], 404),
            'api.zoom.us/v2/users/me/meetings' => Http::response(['id' => 9, 'join_url' => 'https://zoom.us/j/9']),
        ]);

        $meeting = app(ZoomMeetingService::class)->createMeeting(
            Carbon::parse('2030-01-01 06:00:00', 'UTC'),
            30,
            'topic',
            'consultant@example.com'
        );

        $this->assertSame('9', $meeting['meeting_id']);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/users/me/meetings'));
    }

    public function test_no_host_given_uses_the_owner_account(): void
    {
        $this->configureZoom();
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 't']),
            'api.zoom.us/v2/users/me/meetings' => Http::response(['id' => 3, 'join_url' => 'https://zoom.us/j/3']),
        ]);

        app(ZoomMeetingService::class)->createMeeting(Carbon::parse('2030-01-01 06:00:00', 'UTC'), 30, 'topic');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/users/me/meetings'));
    }

    // -------------------------------------------------------- reassignment

    public function test_an_admin_can_reassign_a_confirmed_booking(): void
    {
        $first = $this->consultant();
        $second = $this->consultant();
        $this->seedTemplates();
        Mail::fake();

        $course = $this->makeHighTicketCourse();
        $start = $this->seedSlots();
        ConsultationSlot::query()->update(['consultant_id' => $first->id]);
        $this->applyAndConfirm($course, ['slot_starts_at' => $start->toIso8601String()]);
        $lead = HighTicketLead::firstOrFail();

        $this->actingAs($this->admin())
            ->put("/admin/consultation-slots/bookings/{$lead->id}/consultant", ['consultant_id' => $second->id])
            ->assertRedirect();

        $lead->refresh();
        $this->assertSame($second->id, $lead->consultant_id);
        $this->assertSame($second->id, $lead->slots()->first()->consultant_id);
    }

    public function test_reassignment_requires_staff(): void
    {
        $consultant = $this->consultant();
        $lead = HighTicketLead::create([
            'name' => 'X', 'email' => 'x@example.com', 'course_id' => 1,
            'status' => 'pending', 'booked_at' => now(),
        ]);

        $this->put("/admin/consultation-slots/bookings/{$lead->id}/consultant", ['consultant_id' => $consultant->id])
            ->assertRedirect('/login');
    }

    private function configureZoom(): void
    {
        SiteSetting::set(ZoomMeetingService::ACCOUNT_ID_KEY, 'acc-1');
        SiteSetting::set(ZoomMeetingService::CLIENT_ID_KEY, 'cid-1');
        SiteSetting::set(ZoomMeetingService::CLIENT_SECRET_KEY, 'secret-1');
    }
}
