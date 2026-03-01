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
        public string $courseName,
        public string $openPixelUrl = '',
        public ?int $videoAccessHours = null,
        public string $greetingName = '',
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->greetingName
            ? "{$this->greetingName}，{$this->lessonTitle}"
            : $this->lessonTitle;

        return new Envelope(
            subject: $subject,
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
