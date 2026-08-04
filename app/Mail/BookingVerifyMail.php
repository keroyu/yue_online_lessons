<?php

namespace App\Mail;

use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The "confirm within the hour" mail (011 US11 / FR-058).
 *
 * Not driven by `email_templates` like the other booking mails, and on purpose:
 * this one is a mechanism, not a message. Its whole job is to carry a link that
 * makes the booking real, so there is nothing an owner would want to reword —
 * while a missing template row used to take the entire application down with a
 * 422 (「預約待確認信模板不存在」). Same shape as VerificationCodeMail.
 */
class BookingVerifyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $courseName,
        public string $slotLabel,
        public int $minutes,
        public string $confirmUrl,
        public string $expiresLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "【請於 1 小時內確認】{$this->courseName} 預約時段保留中",
        );
    }

    /**
     * multipart/alternative: a confirmation link landing in spam breaks the
     * whole booking, and an HTML-only body is what trips MIME_HTML_ONLY
     * (比照 FR-020).
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-verify',
            text: 'emails.booking-verify-text',
            with: ['supportEmail' => SiteSetting::supportEmail()],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
