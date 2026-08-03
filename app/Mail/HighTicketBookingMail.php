<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HighTicketBookingMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Takes an already-rendered body: EmailTemplate::renderBody() is the single
     * place that knows whether the template is Markdown or raw HTML (FR-019).
     */
    public function __construct(
        public string $emailSubject,
        public string $htmlBody,
        public string $textBody
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.high-ticket-booking',
            text: 'emails.template-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
