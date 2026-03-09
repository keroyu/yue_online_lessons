<?php

namespace App\Mail;

use App\Models\Chapter;
use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChapterAddedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Course $course,
        public Chapter $chapter
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "【{$this->course->name}】新章節「{$this->chapter->title}」上線囉！",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.chapter-added',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
