<?php

namespace App\Services;

use App\Mail\HighTicketBookingMail;
use App\Models\Course;
use App\Models\EmailTemplate;
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

        Mail::to($data['email'])->send(new HighTicketBookingMail($subject, $body));

        return ['success' => true];
    }
}
