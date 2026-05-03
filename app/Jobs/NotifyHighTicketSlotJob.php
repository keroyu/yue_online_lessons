<?php

namespace App\Jobs;

use App\Mail\HighTicketBookingMail;
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
        ];

        $subject = $template->renderSubject($vars);
        $body = str_replace(array_keys($vars), array_values($vars), $template->body_md);

        try {
            Mail::to($lead->email)->send(new HighTicketBookingMail($subject, $body));

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
}
