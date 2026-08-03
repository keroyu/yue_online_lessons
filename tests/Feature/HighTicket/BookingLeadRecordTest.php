<?php

namespace Tests\Feature\HighTicket;

use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\HighTicketBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 011 US2 — one person booking one course is one lead however many times they
 * submit the form, and the CC list is an admin setting rather than a constant
 * that needs a deploy to change.
 */
class BookingLeadRecordTest extends TestCase
{
    use RefreshDatabase;

    /** @see BookingMailFailureTest — sqlite's CHECK predates the 'high_ticket' type. */
    private function makeHighTicketCourse(): Course
    {
        $course = Course::create([
            'name'                   => 'HT Course',
            'slug'                   => 'ht-' . uniqid(),
            'tagline'                => 'tag',
            'description'            => 'desc',
            'price'                  => 50000,
            'instructor_name'        => 'Tester',
            'type'                   => 'lecture',
            'status'                 => 'selling',
            'course_type'            => 'standard',
            'is_published'           => true,
            'is_visible'             => true,
            'payment_gateway'        => 'payuni',
            'high_ticket_hide_price' => true,
        ]);
        $course->type = 'high_ticket';

        return $course;
    }

    private function bookingTemplate(): void
    {
        EmailTemplate::create([
            'name'       => '預約確認信',
            'event_type' => 'high_ticket_booking_confirmation',
            'subject'    => '{{course_name}} 預約確認',
            'body_md'    => 'Hi {{user_name}}',
        ]);
    }

    public function test_re_booking_the_same_course_refreshes_the_lead_instead_of_duplicating(): void
    {
        Mail::fake();
        $this->bookingTemplate();
        $course = $this->makeHighTicketCourse();
        $service = app(HighTicketBookingService::class);

        $service->book($course, ['name' => 'Booker', 'email' => 'booker@example.com']);
        $service->book($course, ['name' => 'Booker 改名', 'email' => 'booker@example.com']);

        $leads = HighTicketLead::where('email', 'booker@example.com')->get();
        $this->assertCount(1, $leads, '同一人重複預約同一課程不該產生第二筆 lead');
        $this->assertSame('Booker 改名', $leads->first()->name);
    }

    public function test_same_email_on_a_different_course_is_a_separate_lead(): void
    {
        Mail::fake();
        $this->bookingTemplate();
        $service = app(HighTicketBookingService::class);

        $service->book($this->makeHighTicketCourse(), ['name' => 'Booker', 'email' => 'booker@example.com']);
        $service->book($this->makeHighTicketCourse(), ['name' => 'Booker', 'email' => 'booker@example.com']);

        $this->assertCount(2, HighTicketLead::where('email', 'booker@example.com')->get());
    }

    public function test_closed_lead_reopens_on_re_booking_but_converted_stays_converted(): void
    {
        Mail::fake();
        $this->bookingTemplate();
        $service = app(HighTicketBookingService::class);

        $closedCourse = $this->makeHighTicketCourse();
        $service->book($closedCourse, ['name' => 'Cold', 'email' => 'cold@example.com']);
        HighTicketLead::where('email', 'cold@example.com')->update(['status' => 'closed']);
        $service->book($closedCourse, ['name' => 'Cold', 'email' => 'cold@example.com']);
        $this->assertSame('pending', HighTicketLead::where('email', 'cold@example.com')->first()->status);

        $wonCourse = $this->makeHighTicketCourse();
        $service->book($wonCourse, ['name' => 'Won', 'email' => 'won@example.com']);
        HighTicketLead::where('email', 'won@example.com')->update(['status' => 'converted']);
        $service->book($wonCourse, ['name' => 'Won', 'email' => 'won@example.com']);
        $this->assertSame('converted', HighTicketLead::where('email', 'won@example.com')->first()->status);
    }

    public function test_lead_is_saved_even_when_the_confirmation_mail_throws(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp is down'));
        $this->bookingTemplate();

        app(HighTicketBookingService::class)->book(
            $this->makeHighTicketCourse(),
            ['name' => 'Booker', 'email' => 'booker@example.com']
        );

        $this->assertDatabaseHas('high_ticket_leads', ['email' => 'booker@example.com', 'status' => 'pending']);
    }

    public function test_configured_cc_replaces_the_built_in_default(): void
    {
        Mail::fake();
        $this->bookingTemplate();
        SiteSetting::set(HighTicketBookingService::NOTIFY_CC_SETTING_KEY, 'sales@example.com, boss@example.com');

        app(HighTicketBookingService::class)->book(
            $this->makeHighTicketCourse(),
            ['name' => 'Booker', 'email' => 'booker@example.com']
        );

        Mail::assertSent(\App\Mail\TemplatedMail::class, function ($mail) {
            return $mail->hasCc('sales@example.com')
                && $mail->hasCc('boss@example.com')
                && !$mail->hasCc(HighTicketBookingService::DEFAULT_NOTIFY_CC[0]);
        });
    }

    public function test_blank_setting_falls_back_to_the_default_recipient(): void
    {
        Mail::fake();
        $this->bookingTemplate();
        SiteSetting::set(HighTicketBookingService::NOTIFY_CC_SETTING_KEY, '');

        app(HighTicketBookingService::class)->book(
            $this->makeHighTicketCourse(),
            ['name' => 'Booker', 'email' => 'booker@example.com']
        );

        Mail::assertSent(\App\Mail\TemplatedMail::class, fn ($mail) => $mail->hasCc(HighTicketBookingService::DEFAULT_NOTIFY_CC[0]));
    }

    public function test_admin_can_save_the_cc_list_and_bad_emails_are_rejected(): void
    {
        $admin = User::create(['email' => 'admin@example.com', 'role' => 'admin']);

        $this->actingAs($admin)
            ->put('/admin/email-templates/notify-cc', ['notify_cc' => 'a@example.com,b@example.com'])
            ->assertRedirect(route('admin.email-templates.index'));

        $this->assertSame(
            'a@example.com, b@example.com',
            SiteSetting::get(HighTicketBookingService::NOTIFY_CC_SETTING_KEY)
        );

        $this->actingAs($admin)
            ->put('/admin/email-templates/notify-cc', ['notify_cc' => 'a@example.com, not-an-email'])
            ->assertSessionHasErrors('notify_cc');
    }
}
