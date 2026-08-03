<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Services\EmailMarkdownService;
use Illuminate\Queue\SerializesModels;

class BatchEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $htmlBody;

    public function __construct(
        public string $emailSubject,
        public string $emailBody
    ) {
        // Single Enter is a real line break here too (011 FR-021).
        $this->htmlBody = EmailMarkdownService::toHtml($emailBody);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.batch-email',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
