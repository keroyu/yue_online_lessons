<?php

namespace App\Mail;

use App\Models\Post;
use App\Models\User;
use App\Services\EmailLinkTagger;
use App\Services\PostService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Welcome mail. Sends an existing article when there is one (012 US9).
 *
 * The article body reuses the broadcast templates rather than a second set of
 * its own — they read `$post` / `$postUrl` / `$bodyHtml` / `$bodyText` /
 * `$unsubscribeUrl` / `$openPixelUrl` and nothing else, with no reference to a
 * Broadcast (FR-018, FR-036).
 * The short template stays as the last fallback: a site with no published posts
 * yet is exactly when a welcome mail matters most (FR-017).
 */
class NewsletterWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $unsubscribeUrl;
    public ?string $postUrl = null;
    public string $bodyHtml = '';
    public string $bodyText = '';

    /**
     * Always null here. `newsletter_email_events` keys opens on a broadcast id,
     * and the welcome mail has none — a fake one would break the meaning of
     * every report that reads that table (D15). The blade skips the pixel when
     * this is null.
     */
    public ?string $openPixelUrl = null;

    public function __construct(public User $user, public ?Post $post = null)
    {
        $this->unsubscribeUrl = url('/newsletter/unsubscribe/' . $user->newsletter_unsubscribe_token);

        if ($post) {
            $utm = [
                'utm_source'   => 'newsletter',
                'utm_medium'   => 'email',
                'utm_campaign' => 'welcome',
                'utm_content'  => $post->slug,
            ];
            $this->postUrl = app(EmailLinkTagger::class)->tagUrl(url("/blog/{$post->slug}"), $utm);

            // Same full-text body as a broadcast (012 US10, FR-018).
            $posts = app(PostService::class);
            $this->bodyHtml = $posts->toEmailHtml($post, $this->postUrl, $utm);
            $this->bodyText = $posts->toEmailText($this->bodyHtml, $this->postUrl);
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->post?->title ?? '訂閱成功，歡迎加入');
    }

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
        if ($this->post) {
            return new Content(
                view: 'emails.newsletter-broadcast',
                text: 'emails.newsletter-broadcast-text',
            );
        }

        return new Content(view: 'emails.newsletter-welcome');
    }
}
