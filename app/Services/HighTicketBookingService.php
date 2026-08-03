<?php

namespace App\Services;

use App\Mail\HighTicketBookingMail;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HighTicketBookingService
{
    /**
     * Fallback recipients copied on every booking confirmation — a new lead has
     * to reach a human even if nobody is watching the admin panel. Editable in
     * 後台 → Email 模板管理; this list is only what an unconfigured site uses
     * (the customer-service address used on the payment and legal pages is a
     * different role, not a lead recipient).
     */
    public const DEFAULT_NOTIFY_CC = [
        'themustbig+leads@gmail.com',  // 管理員
    ];

    public const NOTIFY_CC_SETTING_KEY = 'high_ticket_lead_notify_cc';

    public function book(Course $course, array $data): array
    {
        if (!$course->is_high_ticket || !$course->high_ticket_hide_price) {
            return ['success' => false, 'message' => '此課程不接受預約'];
        }

        $template = EmailTemplate::forEvent('high_ticket_booking_confirmation')->first();

        if (!$template) {
            return ['success' => false, 'message' => '預約確認信模板不存在，請聯絡管理員'];
        }

        // The contact details are the point of the booking, so they land in the
        // DB before anything that can hang or throw (mail, drip, CAPI dispatch).
        $this->recordLead($course, $data);

        $vars = [
            '{{user_name}}' => $data['name'],
            '{{user_email}}' => $data['email'],
            '{{course_name}}' => $course->name,
        ];

        $subject = $template->renderSubject($vars);
        $body = str_replace(array_keys($vars), array_values($vars), $template->body_md);

        // A failed send must not fail the booking — the lead is already saved —
        // but the caller has to know, so the page can stop telling the visitor
        // to go check an inbox that has nothing in it.
        $mailSent = true;

        try {
            Mail::to($data['email'])
                ->cc($this->notifyCc())
                ->send(new HighTicketBookingMail($subject, $body));
        } catch (\Exception $e) {
            $mailSent = false;

            Log::error('High ticket booking email failed', [
                'email' => $data['email'],
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);
        }

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

    /**
     * Re-booking the same course with the same email is the same person, not a
     * second lead — refresh the existing row so the follow-up history stays in
     * one place. A lead that was closed (cold / moved to the drip sequence) is
     * live again; contacted and converted keep whatever the admin set.
     */
    private function recordLead(Course $course, array $data): HighTicketLead
    {
        $lead = HighTicketLead::where('email', $data['email'])
            ->where('course_id', $course->id)
            ->latest('id')
            ->first();

        if (!$lead) {
            return HighTicketLead::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'course_id' => $course->id,
                'status' => 'pending',
                'booked_at' => now(),
            ]);
        }

        $lead->update([
            'name' => $data['name'],
            'booked_at' => now(),
            'status' => $lead->status === 'closed' ? 'pending' : $lead->status,
        ]);

        return $lead;
    }

    /**
     * @return array<int, string>
     */
    private function notifyCc(): array
    {
        $configured = self::parseRecipients((string) SiteSetting::get(self::NOTIFY_CC_SETTING_KEY, ''));

        return $configured ?: self::DEFAULT_NOTIFY_CC;
    }

    /**
     * Split an admin-entered recipient list (comma / whitespace separated).
     *
     * @return array<int, string>
     */
    public static function parseRecipients(string $raw): array
    {
        $parts = preg_split('/[,;\s]+/', trim($raw)) ?: [];

        return array_values(array_unique(array_filter($parts, fn ($p) => $p !== '')));
    }
}
