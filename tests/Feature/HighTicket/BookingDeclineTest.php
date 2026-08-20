<?php

namespace Tests\Feature\HighTicket;

use App\Mail\TemplatedMail;
use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use App\Models\User;
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
 * 011 US27 — declining a live booking from the leads list.
 *
 * The mechanism is shared with US14's cancellation (D104), so what these tests
 * pin is the part that is *not* shared: which mail goes out, which status the
 * lead lands on, and the two timestamps that have to move together (FR-139).
 */
class BookingDeclineTest extends TestCase
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
     * updateOrCreate for the same reason BookingChangeTest does it: the data
     * migrations already inserted these rows and `event_type` carries no unique
     * index, so a plain create would leave forEvent()->first() reading the old copy.
     */
    private function seedTemplates(): void
    {
        $templates = [
            [
                'name'       => '預約確認',
                'event_type' => 'high_ticket_booking_confirmation',
                'subject'    => '{{course_name}} 預約確認',
                'body_md'    => "時段 {{slot_time}}\n\n連結：{{zoom_join_url}}",
            ],
            [
                'name'       => '預約已取消',
                'event_type' => 'high_ticket_booking_cancelled',
                'subject'    => '{{course_name}} 預約已取消',
                'body_md'    => "原訂 {{slot_time}} 已取消\n\n重新預約：{{course_url}}",
            ],
            [
                'name'       => '預約婉拒通知',
                'event_type' => 'high_ticket_booking_declined',
                'subject'    => '關於您的 {{course_name}} 諮詢申請',
                'body_md'    => "{{user_name}} 您好，原訂 {{slot_time}} 的面談不會進行。",
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(['event_type' => $template['event_type']], $template);
        }
    }

    /** A confirmed booking with slots held permanently. */
    private function confirmedLead(Course $course, array $overrides = []): HighTicketLead
    {
        $this->seedTemplates();
        Mail::fake();
        Queue::fake();

        $this->applyAndConfirm($course, $overrides);

        return HighTicketLead::where('email', $overrides['email'] ?? 'booker@example.com')
            ->latest('id')
            ->firstOrFail();
    }

    /** @return array{0: string, 1: string} */
    private function readAttachment(\Illuminate\Mail\Mailables\Attachment $attachment): array
    {
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

    // ------------------------------------------------------------- service

    /** FR-138: one action, four consequences. */
    public function test_decline_releases_the_units_and_marks_the_lead(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        app(HighTicketBookingService::class)->decline($lead);

        $this->assertSame(0, ConsultationSlot::where('lead_id', $lead->id)->count());

        $lead->refresh();
        $this->assertSame('declined', $lead->status);
    }

    /**
     * FR-139: both stamps, always. `cancelled_at` is what isActiveBooking()
     * reads, so leaving it null keeps a slot-less lead looking live.
     */
    public function test_decline_stamps_both_declined_at_and_cancelled_at(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        app(HighTicketBookingService::class)->decline($lead);

        $lead->refresh();
        $this->assertNotNull($lead->declined_at);
        $this->assertNotNull($lead->cancelled_at);
        $this->assertFalse($lead->isActiveBooking());
    }

    public function test_decline_clears_the_zoom_columns(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $lead->update(['zoom_meeting_id' => '999', 'zoom_join_url' => 'https://zoom.us/j/999']);

        app(HighTicketBookingService::class)->decline($lead);

        $lead->refresh();
        $this->assertNull($lead->zoom_meeting_id);
        $this->assertNull($lead->zoom_join_url);
    }

    /** FR-140: the decline template, carrying the calendar cancellation. */
    public function test_decline_sends_the_declined_template_with_a_cancel_ics(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        Mail::fake();
        app(HighTicketBookingService::class)->decline($lead);

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            if ($mail->emailSubject !== '關於您的 HT Course 諮詢申請') {
                return false;
            }

            [$content, $mime] = $this->readAttachment($mail->fileAttachments[0]);

            return str_contains($mime, 'method=CANCEL')
                && str_contains($content, 'METHOD:CANCEL')
                && str_contains($content, 'STATUS:CANCELLED');
        });
    }

    /** FR-140: two mails with two different stories about the same event. */
    public function test_decline_does_not_also_send_the_cancellation_mail(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        Mail::fake();
        app(HighTicketBookingService::class)->decline($lead);

        Mail::assertSent(TemplatedMail::class, 1);
        Mail::assertNotSent(
            TemplatedMail::class,
            fn (TemplatedMail $mail) => $mail->emailSubject === 'HT Course 預約已取消'
        );
    }

    /** FR-140 / FR-062: nobody is copied on a refusal. */
    public function test_decline_mail_has_no_cc(): void
    {
        SiteSetting::set('high_ticket_lead_notify_cc', "ops@example.com\nsales@example.com");

        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        Mail::fake();
        app(HighTicketBookingService::class)->decline($lead);

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->cc === []);
    }

    /** D107: the sequence was already stopped at booking; declining leaves it there. */
    public function test_decline_does_not_touch_the_drip_subscription(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        $dripCourse = Course::create([
            'name'            => 'Drip',
            'slug'            => 'drip-' . uniqid(),
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 0,
            'instructor_name' => 'Tester',
            'type'            => 'lecture',
            'status'          => 'selling',
            'course_type'     => 'drip',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ]);

        $user = User::factory()->create(['email' => $lead->email]);
        $subscription = DripSubscription::create([
            'user_id'       => $user->id,
            'course_id'     => $dripCourse->id,
            'subscribed_at' => now(),
            'emails_sent'   => 3,
            'status'        => 'booked',
        ]);

        app(HighTicketBookingService::class)->decline($lead);

        $this->assertSame('booked', $subscription->fresh()->status);
    }

    /** FR-139: the endpoint's own guard, not just the button's visibility. */
    public function test_declining_without_a_live_booking_is_refused(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        app(HighTicketBookingService::class)->decline($lead);
        $result = app(HighTicketBookingService::class)->decline($lead->fresh());

        $this->assertFalse($result['success']);
    }

    /** D104: the shared mechanism still reaches Zoom. */
    public function test_decline_deletes_the_zoom_meeting_inline(): void
    {
        SiteSetting::set(ZoomMeetingService::ACCOUNT_ID_KEY, 'acc-1');
        SiteSetting::set(ZoomMeetingService::CLIENT_ID_KEY, 'cid-1');
        SiteSetting::set(ZoomMeetingService::CLIENT_SECRET_KEY, 'secret-1');

        Http::fake([
            'zoom.us/oauth/token'         => Http::response(['access_token' => 't'], 200),
            'api.zoom.us/v2/meetings/555' => Http::response('', 204),
        ]);

        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $lead->update(['zoom_meeting_id' => '555']);

        $result = app(HighTicketBookingService::class)->decline($lead);

        $this->assertTrue($result['zoom_synced']);
        Http::assertSent(fn ($request) => $request->method() === 'DELETE');
    }

    /** FR-050: a Zoom outage must not undo a refusal already sent by mail. */
    public function test_a_zoom_failure_does_not_fail_the_decline(): void
    {
        SiteSetting::set(ZoomMeetingService::ACCOUNT_ID_KEY, 'acc-1');
        SiteSetting::set(ZoomMeetingService::CLIENT_ID_KEY, 'cid-1');
        SiteSetting::set(ZoomMeetingService::CLIENT_SECRET_KEY, 'secret-1');

        Http::fake([
            'zoom.us/oauth/token'         => Http::response(['access_token' => 't'], 200),
            'api.zoom.us/v2/meetings/555' => Http::response(['message' => 'boom'], 500),
        ]);

        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);
        $lead->update(['zoom_meeting_id' => '555']);

        $result = app(HighTicketBookingService::class)->decline($lead);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['zoom_synced'], '管理員要看得出 Zoom 沒同步到');
        $this->assertSame('declined', $lead->fresh()->status);
        $this->assertSame(0, $lead->slots()->count());
    }

    /**
     * FR-139: re-applying revives the lead, and the rose 已婉拒 badge must not
     * survive it — `declined_at` was only cleared on the screened path before.
     */
    public function test_reapplying_after_a_decline_clears_declined_at(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        app(HighTicketBookingService::class)->decline($lead);
        $this->assertSame('declined', $lead->fresh()->status);

        Mail::fake();
        $this->applyForBooking($course, [
            'slot_starts_at' => $this->freeStartLaterThan(now())->toIso8601String(),
        ]);

        $lead->refresh();
        $this->assertSame('pending', $lead->status);
        $this->assertNull($lead->declined_at);
    }

    // ------------------------------------------------------------ endpoint

    public function test_decline_endpoint_requires_staff(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        $this->post("/admin/high-ticket-leads/{$lead->id}/decline")->assertRedirect('/login');
    }

    public function test_staff_can_decline_through_the_endpoint(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->confirmedLead($course);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post("/admin/high-ticket-leads/{$lead->id}/decline")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('declined', $lead->fresh()->status);
        $this->assertSame(0, $lead->slots()->count());
    }

    /** FR-139: a stale button press reports itself instead of passing silently. */
    public function test_decline_endpoint_flashes_an_error_without_a_live_booking(): void
    {
        $lead = HighTicketLead::create([
            'name'      => 'Legacy',
            'email'     => 'legacy@example.com',
            'course_id' => 1,
            'status'    => 'pending',
            'booked_at' => now(),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post("/admin/high-ticket-leads/{$lead->id}/decline")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('pending', $lead->fresh()->status);
    }

    /** FR-141: the confirm dialog quotes the mail that will actually go out. */
    public function test_leads_page_ships_the_decline_reason(): void
    {
        $this->seedTemplates();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/high-ticket-leads')
            ->assertInertia(fn ($page) => $page
                ->where('declineReason', "{{user_name}} 您好，原訂 {{slot_time}} 的面談不會進行。"));
    }
}
