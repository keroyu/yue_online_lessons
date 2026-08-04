<?php

namespace Tests\Feature\HighTicket;

use App\Models\Course;
use App\Models\DripConversionTarget;
use App\Models\DripSubscription;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\HighTicketBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\BooksHighTicket;
use Tests\TestCase;

/**
 * 011 US2 / FR-003 / FR-013 — a failed confirmation email must be reported
 * honestly instead of telling the visitor to go check their inbox. The lead,
 * the CAPI event and the drip stop all still happen: the contact details are
 * worth more than the email.
 */
class BookingMailFailureTest extends TestCase
{
    use BooksHighTicket, RefreshDatabase;

    /**
     * sqlite's CHECK on the type enum predates 'high_ticket' (that ALTER is
     * MySQL-only), so the type is set in memory — book() only reads it.
     */
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


    public function test_successful_send_reports_mail_sent_true(): void
    {
        Mail::fake();
        $this->bookingTemplate();

        $result = $this->applyForBooking($this->makeHighTicketCourse());

        $this->assertTrue($result['success']);
        $this->assertTrue($result['mail_sent']);
    }

    public function test_confirmation_is_copied_to_the_admin_only(): void
    {
        Mail::fake();
        $this->bookingTemplate();

        $this->applyForBooking($this->makeHighTicketCourse());

        Mail::assertSent(\App\Mail\BookingVerifyMail::class, function ($mail) {
            // The customer-service address is not a lead recipient
            return $mail->hasTo('booker@example.com')
                && $mail->hasCc('themustbig+leads@gmail.com')
                && !$mail->hasCc('themustbig+learn@gmail.com');
        });
    }

    public function test_failed_send_reports_mail_sent_false_but_keeps_the_lead(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp is down'));
        $this->bookingTemplate();

        $result = $this->applyForBooking($this->makeHighTicketCourse());

        $this->assertTrue($result['success'], '寄信失敗不該讓預約失敗');
        $this->assertFalse($result['mail_sent']);
        $this->assertDatabaseHas('high_ticket_leads', [
            'email'  => 'booker@example.com',
            'status' => 'pending',
        ]);
    }

    /**
     * The drip stop moved to confirmation (D35), so a failed verify mail leaves
     * the sequence running — the applicant never proved the address is theirs.
     */
    public function test_failed_send_leaves_the_drip_sequence_running(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp is down'));
        $this->bookingTemplate();

        $target = $this->makeHighTicketCourse();
        $drip = Course::create([
            'name' => 'Drip', 'slug' => 'drip-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 0, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'drip', 'drip_interval_days' => 3,
            'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);
        DripConversionTarget::create(['drip_course_id' => $drip->id, 'target_course_id' => $target->id]);

        $user = User::create(['email' => 'booker@example.com', 'role' => 'member']);
        $sub = DripSubscription::create([
            'user_id'       => $user->id,
            'course_id'     => $drip->id,
            'subscribed_at' => now()->subDays(6),
            'emails_sent'   => 2,
            'status'        => 'active',
        ]);

        $this->applyForBooking($target);

        $this->assertSame('active', $sub->fresh()->status);
    }
}
