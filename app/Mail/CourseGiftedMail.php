<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseGiftedMail extends Mailable
{
    use Queueable, SerializesModels;

    public ?string $htmlBody = null;
    public ?string $textBody = null;
    private string $resolvedSubject;
    private bool $useTemplate = false;

    public function __construct(
        public string $courseName,
        public string $courseDescription
    ) {
        $template = EmailTemplate::forEvent('course_gifted')->first();

        if ($template) {
            $vars = [
                '{{course_name}}' => $this->courseName,
                '{{course_description}}' => $this->courseDescription,
                '{{app_url}}' => config('app.url'),
            ];

            $this->resolvedSubject = $template->renderSubject($vars);
            $this->htmlBody = $template->renderBody($vars);
            $this->textBody = $template->renderText($vars);
            $this->useTemplate = true;
        } else {
            $this->resolvedSubject = "您已獲得課程：{$this->courseName}";
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
            text: 'emails.course-gifted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
