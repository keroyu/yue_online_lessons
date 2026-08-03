<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LessonAddedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public ?string $htmlBody = null;
    public ?string $textBody = null;
    private string $resolvedSubject;
    private bool $useTemplate = false;

    public function __construct(
        public Course $course,
        public Lesson $lesson
    ) {
        $template = EmailTemplate::forEvent('lesson_added')->first();

        if ($template) {
            $vars = [
                '{{course_name}}' => $this->course->name,
                '{{lesson_title}}' => $this->lesson->title,
                '{{classroom_url}}' => config('app.url') . '/member/classroom/' . $this->course->id,
            ];

            $this->resolvedSubject = $template->renderSubject($vars);
            $this->htmlBody = $template->renderBody($vars);
            $this->textBody = $template->renderText($vars);
            $this->useTemplate = true;
        } else {
            $typeLabel = match($this->course->type) {
                'ebook' => '電子書',
                'lecture' => '講座',
                'mini'    => '迷你課',
                default   => '課程',
            };

            $this->resolvedSubject = "您擁有的{$typeLabel}更新了：新小節「{$this->lesson->title}」上線囉";
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->resolvedSubject,
        );
    }

    public function content(): Content
    {
        if ($this->useTemplate) {
            return new Content(
                view: 'emails.high-ticket-booking',
                text: 'emails.template-text',
            );
        }

        return new Content(
            text: 'emails.lesson-added',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
