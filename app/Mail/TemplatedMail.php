<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Carrier for every `email_templates`-driven mail — booking confirmation, slot
 * notification, lead conversion. It knows nothing about any of them: the caller
 * looks the template up and hands over finished strings.
 */
class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Takes an already-rendered body: EmailTemplate::renderBody() is the single
     * place that knows whether the template is Markdown or raw HTML (FR-019).
     *
     * `$fileAttachments` holds ready-made Attachment instances (FR-053). The
     * MIME type is the caller's call on purpose — a calendar invite and a
     * calendar cancellation differ only by `method=REQUEST` vs `method=CANCEL`
     * in the content type, and this class has no business knowing that.
     *
     * @param array<int, Attachment> $fileAttachments
     */
    public function __construct(
        public string $emailSubject,
        public string $htmlBody,
        public string $textBody,
        public array $fileAttachments = []
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
        return $this->fileAttachments;
    }
}
