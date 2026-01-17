<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseGiftedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $courseName,
        public string $courseDescription
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "您已獲得課程：{$this->courseName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course-gifted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
