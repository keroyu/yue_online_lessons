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
    public string $lessonUrl;
    private string $resolvedSubject;
    private bool $useTemplate = false;

    public function __construct(
        public Course $course,
        public Lesson $lesson
    ) {
        // One link for both the template and the fallback view (004 FR-029):
        // slug-first via Course::getRouteKey(), opened straight on the new lesson.
        $this->lessonUrl = route('member.classroom', [
            'course' => $this->course,
            'lesson_id' => $this->lesson->id,
        ]);

        $template = EmailTemplate::forEvent('lesson_added')->first();

        if ($template) {
            $vars = [
                '{{course_name}}' => $this->course->name,
                '{{lesson_title}}' => $this->lesson->title,
                '{{classroom_url}}' => $this->lessonUrl,
            ];

            $this->resolvedSubject = $template->renderSubject($vars);
            $this->htmlBody = $template->renderBody($vars);
            $this->textBody = $template->renderText($vars);
            $this->useTemplate = true;
        } else {
            $this->resolvedSubject = "您擁有的課程新增了小節：「{$this->lesson->title}」";
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
