<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DripLessonMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $lessonTitle,
        public string $htmlContent,
        public bool $hasVideo,
        public string $classroomUrl,
        public string $unsubscribeUrl,
        public string $courseName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->lessonTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.drip-lesson',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
