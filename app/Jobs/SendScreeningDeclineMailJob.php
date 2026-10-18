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

/**
 * The refusal email after the screening gate (011 US43 / FR-260).
 *
 * Dispatched with a delay so it does not land the moment the countdown ends,
 * and so whoever re-screens and passes inside that window never gets it — the
 * verdict is re-read here, not trusted from the moment it was scheduled (D175).
 */
class SendScreeningDeclineMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const DELAY_MINUTES = 60;

    public const EVENT_TYPE = 'high_ticket_screening_declined';

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public int $leadId) {}

    public function handle(): void
    {
        // Claim before sending: several re-screens inside the hour queue several
        // jobs, and only a single conditional UPDATE can pick one of them.
        $claimed = HighTicketLead::whereKey($this->leadId)
            ->where('status', 'declined')
            ->whereNull('confirmed_at')
            ->whereNull('screening_decline_mailed_at')
            ->update(['screening_decline_mailed_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        $lead = HighTicketLead::with('course')->find($this->leadId);
        $template = EmailTemplate::forEvent(self::EVENT_TYPE)->first();

        if (!$template || !$lead->course) {
            Log::warning('SendScreeningDeclineMailJob: missing template or course', [
                'lead_id' => $this->leadId,
            ]);
            $this->releaseClaim();

            return;
        }

        $course = $lead->course;

        // The course may replace the body only; the subject stays site-wide.
        // Never saved: the override must not leak into the shared template.
        if ($course->high_ticket_decline_email_md !== null) {
            $template = $template->replicate()->forceFill([
                'body_md'   => $course->high_ticket_decline_email_md,
                'body_type' => 'markdown',
            ]);
        }

        $vars = [
            '{{user_name}}'   => $lead->name,
            '{{user_email}}'  => $lead->email,
            '{{course_name}}' => $course->name,
        ];

        try {
            // Marketing: the point of this mail is nurture and the address was
            // never verified, so the suppression list must apply (D175).
            $mail = (new TemplatedMail(
                $template->renderSubject($vars),
                $template->renderBody($vars),
                $template->renderText($vars),
            ))->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('X-Mail-Class', 'marketing');
            });

            Mail::to($lead->email)->send($mail);
        } catch (\Exception $e) {
            Log::error('SendScreeningDeclineMailJob: email failed', [
                'lead_id' => $this->leadId,
                'error'   => $e->getMessage(),
            ]);
            $this->releaseClaim();

            throw $e;
        }
    }

    /** Hand the lead back so a retry (or a later fix) can still send. */
    private function releaseClaim(): void
    {
        HighTicketLead::whereKey($this->leadId)->update(['screening_decline_mailed_at' => null]);
    }
}
