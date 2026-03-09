<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LessonAddedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Course $course,
        public Lesson $lesson
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "【{$this->course->name}】新小節「{$this->lesson->title}」上線囉！",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lesson-added',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
