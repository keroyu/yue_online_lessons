<?php

namespace App\Services;

use App\Mail\HighTicketBookingMail;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HighTicketBookingService
{
    public function book(Course $course, array $data): array
    {
        if (!$course->is_high_ticket || !$course->high_ticket_hide_price) {
            return ['success' => false, 'message' => '此課程不接受預約'];
        }

        $template = EmailTemplate::forEvent('high_ticket_booking_confirmation')->first();

        if (!$template) {
            return ['success' => false, 'message' => '預約確認信模板不存在，請聯絡管理員'];
        }

        $vars = [
            '{{user_name}}' => $data['name'],
            '{{user_email}}' => $data['email'],
            '{{course_name}}' => $course->name,
        ];

        $subject = $template->renderSubject($vars);
        $body = str_replace(array_keys($vars), array_values($vars), $template->body_md);

        // A failed send must not fail the booking — the contact details are worth
        // more than the email — but the caller has to know, so the page can stop
        // telling the visitor to go check an inbox that has nothing in it.
        $mailSent = true;

        try {
            Mail::to($data['email'])
                ->cc('themustbig+learn@gmail.com')
                ->send(new HighTicketBookingMail($subject, $body));
        } catch (\Exception $e) {
            $mailSent = false;

            Log::error('High ticket booking email failed', [
                'email' => $data['email'],
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);
        }

        HighTicketLead::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'course_id' => $course->id,
            'status' => 'pending',
            'booked_at' => now(),
        ]);

        // Booking is the goal for drip funnels that point at this course (010 US13):
        // stop the sequence. Best-effort — a drip failure must not fail the booking.
        try {
            app(DripService::class)->checkAndBook($data['email'], $course);
        } catch (\Exception $e) {
            Log::error('High ticket booking: drip stop failed', [
                'email' => $data['email'],
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);
        }

        // High-ticket booking is the Lead conversion for ad optimization (000 US7).
        $meta = app(MetaConversionsService::class);
        $meta->send('Lead', array_merge($meta->userDataFromRequest(request()), [
            'em' => $meta->hashEmail($data['email']),
        ]), [
            'content_ids'  => [$course->id],
            'content_type' => 'product',
            'content_name' => $course->name,
        ]);

        return ['success' => true, 'mail_sent' => $mailSent];
    }
}
