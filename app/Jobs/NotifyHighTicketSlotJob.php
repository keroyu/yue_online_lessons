<?php

namespace App\Jobs;

use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyHighTicketSlotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public int $leadId,
        public int $templateId,
    ) {}

    public function handle(): void
    {
        $lead = HighTicketLead::with('course')->find($this->leadId);
        $template = EmailTemplate::find($this->templateId);

        if (!$lead || !$template) {
            Log::warning('NotifyHighTicketSlotJob: Missing lead or template', [
                'lead_id' => $this->leadId,
                'template_id' => $this->templateId,
            ]);
            return;
        }

        $vars = [
            '{{user_name}}' => $lead->name,
            '{{course_name}}' => $lead->course?->name ?? '',
            '{{booking_url}}' => $this->bookingUrl($lead),
        ];

        $subject = $template->renderSubject($vars);

        try {
            // Marketing header only for this instance — TemplatedMail is also
            // used for booking confirm/reschedule/cancel, which are transactional
            // and must not be tagged (D24).
            $mail = (new TemplatedMail(
                $subject,
                $template->renderBody($vars),
                $template->renderText($vars),
            ))->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('X-Mail-Class', 'marketing');
            });

            Mail::to($lead->email)->send($mail);

            $lead->increment('notified_count');
            $lead->update(['last_notified_at' => now()]);
        } catch (\Exception $e) {
            Log::error('NotifyHighTicketSlotJob: Email failed', [
                'lead_id' => $this->leadId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Deep link back into the wizard's slot picker (FR-042).
     *
     * Telling somebody "we have slots now" and then making them retype the
     * whole questionnaire is how you lose the application you already earned.
     *
     * Delegated to the service since US26 needed the same link for its own
     * mail — two copies of a URL shape is one copy too many.
     */
    private function bookingUrl(HighTicketLead $lead): string
    {
        return app(\App\Services\HighTicketBookingService::class)->resumeUrl($lead);
    }
}
