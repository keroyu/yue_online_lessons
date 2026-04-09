<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use League\CommonMark\CommonMarkConverter;

class CourseGiftedMail extends Mailable
{
    use Queueable, SerializesModels;

    public ?string $htmlBody = null;
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
            $body = str_replace(array_keys($vars), array_values($vars), $template->body_md);
            $converter = new CommonMarkConverter();
            $this->htmlBody = $converter->convert($body)->getContent();
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
