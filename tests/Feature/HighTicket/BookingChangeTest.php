<?php

namespace Tests\Feature\HighTicket;

use App\Exceptions\SlotUnavailableException;
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
use Illuminate\Support\Facades\Queue;
use Tests\Support\BooksHighTicket;
use Tests\TestCase;

/**
 * 011 US14 — rescheduling and cancelling a confirmed booking, and the calendar
 * attachment that rides along with every one of those mails.
 *
 * The slot table and the mail are the facts here; Zoom is a side effect that
 * runs in a job and must never be able to undo either (FR-050).
 */
class BookingChangeTest extends TestCase
{
    use BooksHighTicket, RefreshDatabase;

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

    /**
     * updateOrCreate, not create: 2026_08_06_000003 already inserts the two
     * change templates, and `event_type` carries no unique index — a second row
     * would sit there quietly while forEvent()->first() kept returning the
     * migration's copy (FR-010).
     */
    private function seedChangeTemplates(): void
    {
        $templates = [
            [
                'name'       => '預約確認',
                'event_type' => 'high_ticket_booking_confirmation',
                'subject'    => '{{course_name}} 預約確認',
                'body_md'    => "時段 {{slot_time}}\n\n連結：{{zoom_join_url}}",
            ],
            [
                'name'       => '預約已改期',
                'event_type' => 'high_ticket_booking_rescheduled',
                'subject'    => '{{course_name}} 時段已更新',
                'body_md'    => "原 {{old_slot_time}} 改為 {{slot_time}}\n\n連結：{{zoom_join_url}}",
            ],
            [
                'name'       => '預約已取消',
                'event_type' => 'high_ticket_booking_cancelled',
                'subject'    => '{{course_name}} 預約已取消',
                'body_md'    => "原訂 {{slot_time}} 已取消\n\n重新預約：{{course_url}}",
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(['event_type' => $template['event_type']], $template);
        }
    }

    /** A confirmed booking with slots held permanently. */
    private function confirmedLead(Course $course, array $overrides = []): HighTicketLead
    {
        $this->seedChangeTemplates();
        Mail::fake();
        Queue::fake();

        $this->applyAndConfirm($course, $overrides);

        return HighTicketLead::where('email', $overrides['email'] ?? 'booker@example.com')
            ->latest('id')
            ->firstOrFail();
    }

    /** A start time that is free and far from the seeded 10:00 block. */
    private function freeStartLaterThan(Carbon $after, int $units = 2): Carbon
    {
        $at = $after->copy()->addHours(3);

        for ($i = 0; $i < $units; $i++) {
            ConsultationSlot::firstOrCreate([
                'starts_at' => $at->copy()->addMinutes($i * ConsultationSlot::UNIT_MINUTES),
            ]);
        }

        return $at;
    }

    // ---------------------------------------------------------------- .ics

    /** FR-046: the calendar entry ships even when Zoom is switched off. */
    public function test_confirmation_mail_carries_an_ics_attachment_without_zoom(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedChangeTemplates();
        Mail::fake();

        $this->applyAndConfirm($course);

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            if ($mail->emailSubject !== 'HT Course 預約確認') {
                return false;
            }

            return count($mail->fileAttachments) === 1;
        });
    }

    public function test_confirmation_attachment_is_a_request_method_calendar(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedChangeTemplates();
        Mail::fake();

        $this->applyAndConfirm($course);

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            if ($mail->emailSubject !== 'HT Course 預約確認') {
                return false;
            }

            $attachment = $mail->fileAttachments[0] ?? null;

            if (!$attachment) {
                return false;
            }

            [$content, $mime] = $this->readAttachment($attachment);

            return str_contains($mime, 'text/calendar')
                && str_contains($mime, 'method=REQUEST')
                && str_contains($content, 'BEGIN:VEVENT');
        });
    }

    /** @return array{0: string, 1: string} */
    private function readAttachment(\Illuminate\Mail\Mailables\Attachment $attachment): array
    {


        // attachWith(pathStrategy, dataStrategy) — the strategies only receive
        // the source and the Attachment itself; the MIME type is a public
        // property on the attachment, not an argument.
        $content = '';

        $attachment->attachWith(
            function ($path) use (&$content) {
                $content = (string) file_get_contents($path);
            },
            function ($resolver) use (&$content) {
                $content = (string) $resolver();
            }
        );

        return [$content, (string) $attachment->mime];
    }

    // ---------------------------------------------------------- reschedule

    public function test_reschedule_moves_the_units_and_keeps_them_confirmed(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $oldStart = $lead->slots()->first()->starts_at->copy();
        $newStart = $this->freeStartLaterThan($oldStart);

        app(HighTicketBookingService::class)->reschedule($lead, $newStart);

        $this->assertSame(0, ConsultationSlot::where('starts_at', $oldStart)->whereNotNull('lead_id')->count());

        $held = ConsultationSlot::where('lead_id', $lead->id)->orderBy('starts_at')->get();
        $this->assertCount(2, $held);
        $this->assertTrue($held->first()->starts_at->equalTo($newStart));
        $this->assertNull($held->first()->held_until, '改期後的單位必須是已確認佔用，不是暫留');
    }

    /**
     * The leads list has to show the moved consultation, not a stale one.
     *
     * It used to render `booked_at` under a column headed 預約時間 — the time
     * the application was submitted, which a reschedule never touches — so the
     * list looked frozen while the week grid showed the new slot. The column
     * now reads the eager-loaded `slots`, and this pins that they are both
     * present and current after a move.
     */
    public function test_leads_list_reflects_the_new_slot_after_a_reschedule(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $oldStart = $lead->slots()->first()->starts_at->copy();
        $newStart = $this->freeStartLaterThan($oldStart);

        app(HighTicketBookingService::class)->reschedule($lead, $newStart);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/high-ticket-leads')
            ->assertInertia(function ($page) use ($lead, $newStart, $oldStart) {
                $row = collect($page->toArray()['props']['leads']['data'])
                    ->firstWhere('id', $lead->id);

                $starts = collect($row['slots'])->pluck('starts_at')
                    ->map(fn ($s) => Carbon::parse($s)->utc()->toIso8601String())
                    ->all();

                $this->assertContains($newStart->copy()->utc()->toIso8601String(), $starts,
                    '改期後的時段必須出現在 Leads 名單的 slots 上');
                $this->assertNotContains($oldStart->copy()->utc()->toIso8601String(), $starts,
                    '舊時段不得殘留在 Leads 名單上');
            });
    }

    /**
     * FR-084: a cross-week move takes the booking off the screen the admin is
     * looking at. Landing on the week that now holds it costs nothing and is
     * the only way they can see the change actually happened.
     */
    public function test_the_reschedule_endpoint_lands_on_the_week_of_the_new_slot(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        // Two weeks out, well clear of the seeded block.
        $newStart = Carbon::parse('+15 days 14:00', ConsultationSlotService::DISPLAY_TZ)->utc();

        foreach ([0, 15] as $offset) {
            ConsultationSlot::firstOrCreate(['starts_at' => $newStart->copy()->addMinutes($offset)]);
        }

        $local = $newStart->copy()->timezone(ConsultationSlotService::DISPLAY_TZ);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/high-ticket-leads/{$lead->id}/booking", [
                'date'       => $local->format('Y-m-d'),
                'start_time' => $local->format('H:i'),
            ])
            ->assertRedirect('/admin/consultation-slots?week=' . $local->format('Y-m-d'))
            ->assertSessionHas('success');
    }

    public function test_reschedule_bumps_the_calendar_sequence(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $newStart = $this->freeStartLaterThan($lead->slots()->first()->starts_at);

        $this->assertSame(0, $lead->calendar_sequence);

        app(HighTicketBookingService::class)->reschedule($lead, $newStart);

        $this->assertSame(1, $lead->fresh()->calendar_sequence);
    }

    public function test_reschedule_sends_the_reschedule_mail_with_a_fresh_ics(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $newStart = $this->freeStartLaterThan($lead->slots()->first()->starts_at);

        Mail::fake();
        app(HighTicketBookingService::class)->reschedule($lead, $newStart);

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            if ($mail->emailSubject !== 'HT Course 時段已更新') {
                return false;
            }

            [$content, $mime] = $this->readAttachment($mail->fileAttachments[0]);

            return str_contains($mime, 'method=REQUEST')
                && str_contains($content, 'SEQUENCE:1');
        });
    }

    /** FR-062: change mails copy nobody — the admin was in that conversation. */
    public function test_reschedule_mail_ccs_nobody(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $newStart = $this->freeStartLaterThan($lead->slots()->first()->starts_at);

        SiteSetting::set(HighTicketBookingService::NOTIFY_CC_SETTING_KEY, 'ops@example.com');

        Mail::fake();
        app(HighTicketBookingService::class)->reschedule($lead, $newStart);

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->emailSubject === 'HT Course 時段已更新'
            && $mail->cc === []);
    }

    /**
     * FR-048: `reserve()` frees the lead's own units before checking, so moving
     * 10:00 to 10:15 must not be blocked by the booking itself.
     */
    public function test_reschedule_into_an_overlapping_range_is_allowed(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $oldStart = $lead->slots()->first()->starts_at->copy();
        $newStart = $oldStart->copy()->addMinutes(15);

        app(HighTicketBookingService::class)->reschedule($lead, $newStart);

        $held = ConsultationSlot::where('lead_id', $lead->id)->orderBy('starts_at')->get();
        $this->assertCount(2, $held);
        $this->assertTrue($held->first()->starts_at->equalTo($newStart));
    }

    public function test_reschedule_onto_somebody_elses_booking_is_rejected(): void
    {
        $course = $this->makeHighTicketCourse();
        $first = $this->confirmedLead($course);
        $second = $this->confirmedLead($course, [
            'email'          => 'other@example.com',
            // A different person needs a different number too — US16 treats a
            // shared phone as the same applicant and would refuse this booking.
            'phone'          => '0987654321',
            'slot_starts_at' => $this->freeStartLaterThan($first->slots()->first()->starts_at)->toIso8601String(),
        ]);

        $taken = $second->slots()->first()->starts_at->copy();

        $this->expectException(SlotUnavailableException::class);
        app(HighTicketBookingService::class)->reschedule($first, $taken);
    }

    public function test_reschedule_refuses_an_unconfirmed_application(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->seedChangeTemplates();
        Mail::fake();

        $this->applyForBooking($course);
        $lead = HighTicketLead::where('email', 'booker@example.com')->firstOrFail();
        $newStart = $this->freeStartLaterThan($lead->slots()->first()->starts_at);

        $result = app(HighTicketBookingService::class)->reschedule($lead, $newStart);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $lead->fresh()->calendar_sequence);
    }

    public function test_reschedule_refuses_an_already_cancelled_booking(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $newStart = $this->freeStartLaterThan($lead->slots()->first()->starts_at);

        app(HighTicketBookingService::class)->cancel($lead);

        $result = app(HighTicketBookingService::class)->reschedule($lead->fresh(), $newStart);

        $this->assertFalse($result['success']);
    }

    // -------------------------------------------------------------- cancel

    public function test_cancel_releases_the_units_and_marks_the_lead(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        app(HighTicketBookingService::class)->cancel($lead);

        $this->assertSame(0, ConsultationSlot::where('lead_id', $lead->id)->count());

        $lead->refresh();
        $this->assertSame('cancelled', $lead->status);
        $this->assertNotNull($lead->cancelled_at);
    }

    public function test_cancel_clears_the_zoom_columns(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $lead->update(['zoom_meeting_id' => '999', 'zoom_join_url' => 'https://zoom.us/j/999']);

        app(HighTicketBookingService::class)->cancel($lead);

        $lead->refresh();
        $this->assertNull($lead->zoom_meeting_id);
        $this->assertNull($lead->zoom_join_url);
    }

    public function test_cancel_sends_a_cancellation_ics(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        Mail::fake();
        app(HighTicketBookingService::class)->cancel($lead);

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            if ($mail->emailSubject !== 'HT Course 預約已取消') {
                return false;
            }

            [$content, $mime] = $this->readAttachment($mail->fileAttachments[0]);

            return str_contains($mime, 'method=CANCEL')
                && str_contains($content, 'METHOD:CANCEL')
                && str_contains($content, 'STATUS:CANCELLED');
        });
    }

    /** FR-049: a cancelled lead re-applying is live again, and keeps its token. */
    public function test_reapplying_after_a_cancellation_returns_to_pending(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        app(HighTicketBookingService::class)->cancel($lead);
        $this->assertSame('cancelled', $lead->fresh()->status);

        Mail::fake();
        $this->applyForBooking($course, ['slot_starts_at' => $this->freeStartLaterThan(now())->toIso8601String()]);

        $this->assertSame('pending', $lead->fresh()->status);
    }

    public function test_cancelling_twice_is_refused(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        app(HighTicketBookingService::class)->cancel($lead);
        $result = app(HighTicketBookingService::class)->cancel($lead->fresh());

        $this->assertFalse($result['success']);
    }

    // ---------------------------------------------------------------- Zoom

    /** D55: inline, so a missing queue worker cannot leave Zoom stale. */
    public function test_reschedule_patches_zoom_inline(): void
    {
        $this->configureZoom();
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 't'], 200),
            'api.zoom.us/v2/meetings/555' => Http::response('', 204),
        ]);

        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $lead->update(['zoom_meeting_id' => '555']);
        $newStart = $this->freeStartLaterThan($lead->slots()->first()->starts_at);

        Queue::fake();
        $result = app(HighTicketBookingService::class)->reschedule($lead, $newStart);

        $this->assertTrue($result['zoom_synced']);
        Http::assertSent(fn ($request) => $request->method() === 'PATCH');
        Queue::assertNothingPushed();
    }

    public function test_cancel_deletes_the_zoom_meeting_inline(): void
    {
        $this->configureZoom();
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 't'], 200),
            'api.zoom.us/v2/meetings/555' => Http::response('', 204),
        ]);

        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $lead->update(['zoom_meeting_id' => '555']);

        $result = app(HighTicketBookingService::class)->cancel($lead);

        $this->assertTrue($result['zoom_synced']);
        Http::assertSent(fn ($request) => $request->method() === 'DELETE');
    }

    /** A Zoom outage must not undo a change the applicant has already been told about. */
    public function test_a_zoom_failure_does_not_fail_the_cancellation(): void
    {
        $this->configureZoom();
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 't'], 200),
            'api.zoom.us/v2/meetings/555' => Http::response(['message' => 'boom'], 500),
        ]);

        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $lead->update(['zoom_meeting_id' => '555']);

        $result = app(HighTicketBookingService::class)->cancel($lead);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['zoom_synced'], '管理員要看得出 Zoom 沒同步到');
        $this->assertSame('cancelled', $lead->fresh()->status);
    }

    public function test_no_zoom_call_when_the_lead_has_no_meeting(): void
    {
        Http::fake();
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        $result = app(HighTicketBookingService::class)->cancel($lead);

        $this->assertNull($result['zoom_synced'], '沒有會議可同步不算失敗');
        Http::assertNothingSent();
    }

    /** D52: PATCH, never delete-and-recreate — the join_url has to survive. */
    public function test_zoom_update_patches_the_existing_meeting(): void
    {
        $this->configureZoom();
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 't'], 200),
            'api.zoom.us/v2/meetings/555' => Http::response('', 204),
        ]);

        app(ZoomMeetingService::class)->updateMeeting('555', Carbon::parse('2026-09-01 06:00:00', 'UTC'), 45);

        Http::assertSent(fn ($request) => $request->method() === 'PATCH'
            && str_contains($request->url(), '/meetings/555')
            && $request['duration'] === 45);
    }

    public function test_reschedule_keeps_the_join_url(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $lead->update(['zoom_meeting_id' => '555', 'zoom_join_url' => 'https://zoom.us/j/555']);
        $newStart = $this->freeStartLaterThan($lead->slots()->first()->starts_at);

        Queue::fake();
        app(HighTicketBookingService::class)->reschedule($lead, $newStart);

        $this->assertSame('https://zoom.us/j/555', $lead->fresh()->zoom_join_url);
    }

    /** A meeting somebody already deleted by hand is the outcome we wanted. */
    public function test_zoom_delete_treats_404_as_success(): void
    {
        $this->configureZoom();
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 't'], 200),
            'api.zoom.us/v2/meetings/555' => Http::response(['code' => 3001], 404),
        ]);

        app(ZoomMeetingService::class)->deleteMeeting('555');

        Http::assertSent(fn ($request) => $request->method() === 'DELETE');
    }

    public function test_zoom_delete_still_raises_other_errors(): void
    {
        $this->configureZoom();
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 't'], 200),
            'api.zoom.us/v2/meetings/555' => Http::response(['code' => 124], 401),
        ]);

        $this->expectException(\RuntimeException::class);
        app(ZoomMeetingService::class)->deleteMeeting('555');
    }

    // ----------------------------------------------------------- endpoints

    public function test_reschedule_endpoint_requires_staff(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $newStart = $this->freeStartLaterThan($lead->slots()->first()->starts_at);

        $this->put("/admin/high-ticket-leads/{$lead->id}/booking", $this->taipei($newStart))
            ->assertRedirect('/login');
    }

    public function test_cancel_endpoint_requires_staff(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        $this->delete("/admin/high-ticket-leads/{$lead->id}/booking")->assertRedirect('/login');
    }

    public function test_staff_can_reschedule_through_the_endpoint(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $newStart = $this->freeStartLaterThan($lead->slots()->first()->starts_at);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/high-ticket-leads/{$lead->id}/booking", $this->taipei($newStart))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($lead->slots()->first()->starts_at->equalTo($newStart));
    }

    public function test_staff_can_cancel_through_the_endpoint(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete("/admin/high-ticket-leads/{$lead->id}/booking")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $lead->fresh()->status);
    }

    public function test_reschedule_endpoint_rejects_an_off_grid_time(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $offGrid = $this->freeStartLaterThan($lead->slots()->first()->starts_at)->addMinutes(7);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/high-ticket-leads/{$lead->id}/booking", $this->taipei($offGrid))
            ->assertSessionHasErrors('start_time');
    }

    public function test_reschedule_endpoint_returns_409_when_the_slot_was_taken(): void
    {
        $course = $this->makeHighTicketCourse();
        $first = $this->confirmedLead($course);
        $second = $this->confirmedLead($course, [
            'email'          => 'other@example.com',
            // A different person needs a different number too — US16 treats a
            // shared phone as the same applicant and would refuse this booking.
            'phone'          => '0987654321',
            'slot_starts_at' => $this->freeStartLaterThan($first->slots()->first()->starts_at)->toIso8601String(),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/high-ticket-leads/{$first->id}/booking", $this->taipei($second->slots()->first()->starts_at))
            ->assertStatus(409);
    }

    /**
     * FR-054: with a live booking, labelling it cancelled without releasing the
     * slot, killing the Zoom meeting and telling them would make the status a lie.
     */
    public function test_status_endpoint_refuses_cancelled_while_a_booking_is_live(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->patchJson("/admin/high-ticket-leads/{$lead->id}/status", ['status' => 'cancelled'])
            ->assertStatus(422);

        $this->assertSame('pending', $lead->fresh()->status);
        $this->assertSame(2, $lead->slots()->count());
    }

    /**
     * But a lead with no booking may be filed as cancelled from the list —
     * refusing both left the leads carried over from 未回應 (no slot, no
     * confirmation) with no way back once nudged out of `cancelled`.
     */
    public function test_status_endpoint_allows_cancelled_when_there_is_no_booking(): void
    {
        $lead = HighTicketLead::create([
            'name' => 'Legacy', 'email' => 'legacy@example.com', 'course_id' => 1,
            'status' => 'pending', 'booked_at' => now(),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->patchJson("/admin/high-ticket-leads/{$lead->id}/status", ['status' => 'cancelled'])
            ->assertOk();

        $this->assertSame('cancelled', $lead->fresh()->status);
    }

    /** The grid posts Taipei wall-clock, split into date + time. */
    private function taipei(Carbon $at): array
    {
        $local = $at->copy()->timezone(ConsultationSlotService::DISPLAY_TZ);

        return ['date' => $local->format('Y-m-d'), 'start_time' => $local->format('H:i')];
    }

    private function configureZoom(): void
    {
        SiteSetting::set(ZoomMeetingService::ACCOUNT_ID_KEY, 'acc-1');
        SiteSetting::set(ZoomMeetingService::CLIENT_ID_KEY, 'cid-1');
        SiteSetting::set(ZoomMeetingService::CLIENT_SECRET_KEY, 'secret-1');
    }
}
