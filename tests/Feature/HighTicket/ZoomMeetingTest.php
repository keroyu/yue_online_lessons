<?php

namespace Tests\Feature\HighTicket;

use App\Jobs\CreateZoomMeetingJob;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use App\Services\ZoomMeetingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Support\BooksHighTicket;
use Tests\TestCase;

/**
 * 011 US12 — Zoom is optional, and a Zoom failure must never cost the applicant
 * their confirmation email.
 *
 * Every case runs against a faked HTTP client: a test that needs real
 * credentials is a test that gets skipped (FR-039).
 */
class ZoomMeetingTest extends TestCase
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

    private function confirmationTemplate(): void
    {
        EmailTemplate::create([
            'name'       => '預約確認',
            'event_type' => 'high_ticket_booking_confirmation',
            'subject'    => '{{course_name}} 預約確認',
            'body_md'    => "時段 {{slot_time}}\n\n會議連結：{{zoom_join_url}}",
        ]);
    }

    private function configureZoom(): void
    {
        SiteSetting::set(ZoomMeetingService::ACCOUNT_ID_KEY, 'acc-1');
        SiteSetting::set(ZoomMeetingService::CLIENT_ID_KEY, 'client-1');
        SiteSetting::set(ZoomMeetingService::CLIENT_SECRET_KEY, 'secret-1');
    }

    public function test_zoom_is_disabled_until_all_three_credentials_are_set(): void
    {
        $zoom = app(ZoomMeetingService::class);
        $this->assertFalse($zoom->isEnabled());

        SiteSetting::set(ZoomMeetingService::ACCOUNT_ID_KEY, 'acc-1');
        SiteSetting::set(ZoomMeetingService::CLIENT_ID_KEY, 'client-1');
        $this->assertFalse($zoom->isEnabled(), '少一個憑證就不算啟用');

        SiteSetting::set(ZoomMeetingService::CLIENT_SECRET_KEY, 'secret-1');
        $this->assertTrue($zoom->isEnabled());
    }

    public function test_without_credentials_the_confirmation_mail_is_sent_synchronously(): void
    {
        Mail::fake();
        Queue::fake();
        $this->confirmationTemplate();

        $this->applyAndConfirm($this->makeHighTicketCourse());

        Queue::assertNotPushed(CreateZoomMeetingJob::class);
        Mail::assertSent(\App\Mail\TemplatedMail::class, fn ($mail) => str_contains($mail->emailSubject, '預約確認'));
    }

    public function test_with_credentials_the_job_is_queued_instead(): void
    {
        Mail::fake();
        Queue::fake();
        $this->confirmationTemplate();
        $this->configureZoom();

        $this->applyAndConfirm($this->makeHighTicketCourse());

        Queue::assertPushed(CreateZoomMeetingJob::class);
        // The confirmation is the job's responsibility now (D38) — only the
        // verify mail has gone out at this point.
        Mail::assertNotSent(\App\Mail\TemplatedMail::class, fn ($mail) => str_contains($mail->emailSubject, '預約確認'));
    }

    public function test_job_creates_the_meeting_and_mails_the_link(): void
    {
        Mail::fake();
        $this->confirmationTemplate();
        $this->configureZoom();

        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 'tok-1', 'expires_in' => 3600]),
            'api.zoom.us/v2/users/me/meetings' => Http::response([
                'id'       => 987654321,
                'join_url' => 'https://zoom.us/j/987654321',
            ]),
        ]);

        $course = $this->makeHighTicketCourse();
        $this->applyForBooking($course);
        $lead = HighTicketLead::first();
        app(\App\Services\HighTicketBookingService::class)->confirm($lead->confirm_token);

        (new CreateZoomMeetingJob($lead->id))->handle(
            app(ZoomMeetingService::class),
            app(\App\Services\HighTicketBookingService::class),
            app(\App\Services\ConsultationSlotService::class)
        );

        $lead->refresh();
        $this->assertSame('987654321', $lead->zoom_meeting_id);
        $this->assertSame('https://zoom.us/j/987654321', $lead->zoom_join_url);

        Mail::assertSent(\App\Mail\TemplatedMail::class, fn ($mail) =>
            str_contains($mail->emailSubject, '預約確認')
            && str_contains($mail->htmlBody, 'https://zoom.us/j/987654321'));
    }

    public function test_giving_up_still_sends_the_confirmation_with_a_fallback_line(): void
    {
        Mail::fake();
        // Fake the queue so confirming does not run the job for real — this
        // test is about what happens after the retries are exhausted.
        Queue::fake();
        $this->confirmationTemplate();
        $this->configureZoom();

        $course = $this->makeHighTicketCourse();
        $this->applyForBooking($course);
        $lead = HighTicketLead::first();
        app(\App\Services\HighTicketBookingService::class)->confirm($lead->confirm_token);

        (new CreateZoomMeetingJob($lead->id))->failed(new \RuntimeException('zoom is down'));

        // The applicant already did their part — they must hear that the
        // booking stands, even without a link (D39).
        Mail::assertSent(\App\Mail\TemplatedMail::class, fn ($mail) =>
            str_contains($mail->emailSubject, '預約確認')
            && str_contains($mail->htmlBody, '會議連結將另行寄出'));
    }

    public function test_meeting_request_carries_the_slot_time_and_length(): void
    {
        Mail::fake();
        $this->confirmationTemplate();
        $this->configureZoom();
        SiteSetting::set(\App\Services\ConsultationSlotService::BONUS_CODES_KEY, 'VIP2026');

        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 'tok-1', 'expires_in' => 3600]),
            'api.zoom.us/v2/users/me/meetings' => Http::response(['id' => 1, 'join_url' => 'https://zoom.us/j/1']),
        ]);

        $course = $this->makeHighTicketCourse();
        $this->applyForBooking($course, ['code' => 'VIP2026']);
        $lead = HighTicketLead::first();
        app(\App\Services\HighTicketBookingService::class)->confirm($lead->confirm_token);

        (new CreateZoomMeetingJob($lead->id))->handle(
            app(ZoomMeetingService::class),
            app(\App\Services\HighTicketBookingService::class),
            app(\App\Services\ConsultationSlotService::class)
        );

        Http::assertSent(function ($request) use ($lead) {
            if (!str_contains($request->url(), '/users/me/meetings')) {
                return false;
            }

            return $request['duration'] === 45
                && $request['timezone'] === 'Asia/Taipei'
                && $request['start_time'] === $lead->slots()->first()->starts_at->format('Y-m-d\TH:i:s\Z');
        });
    }
}
