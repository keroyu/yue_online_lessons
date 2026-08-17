<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use League\HTMLToMarkdown\HtmlConverter;

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

    /**
     * Machine-readable unsubscribe, same as the newsletter mailables. Without it
     * the only signal a mail client has is the wording in the body, which is a
     * weak spam-filter defence for bulk mail.
     */
    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<' . $this->unsubscribeUrl . '>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            'X-Mail-Class' => 'marketing',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.drip-lesson',
            text: 'emails.drip-lesson-text',
            with: ['textContent' => $this->plainTextBody()],
        );
    }

    /**
     * The plain-text half of the multipart mail. An HTML-only bulk mail is one
     * of the cheaper spam signals to give away, and this body round-trips well:
     * it was authored as Markdown (lessons.content_md) and comes back as
     * Markdown, keeping the UTM stamping that was applied to the HTML links.
     */
    private function plainTextBody(): string
    {
        if ($this->htmlContent === '') {
            return '';
        }

        return trim((new HtmlConverter([
            'strip_tags'      => true,
            'suppress_errors' => true,
        ]))->convert($this->htmlContent));
    }

    public function attachments(): array
    {
        return [];
    }
}
