<?php

namespace App\Mail;

use App\Models\Post;
use App\Models\User;
use App\Services\EmailLinkTagger;
use App\Services\VideoEmbedService;
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
 * its own — they read `$post` / `$postUrl` / `$unsubscribeUrl` / `$openPixelUrl`
 * / `$videoThumbUrl` and nothing else, with no reference to a Broadcast (FR-018).
 * The short template stays as the last fallback: a site with no published posts
 * yet is exactly when a welcome mail matters most (FR-017).
 */
class NewsletterWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $unsubscribeUrl;
    public ?string $postUrl = null;
    public ?string $videoThumbUrl = null;

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
            $this->postUrl = app(EmailLinkTagger::class)->tagUrl(url("/blog/{$post->slug}"), [
                'utm_source'   => 'newsletter',
                'utm_medium'   => 'email',
                'utm_campaign' => 'welcome',
                'utm_content'  => $post->slug,
            ]);
            $this->videoThumbUrl = $this->firstYoutubeThumb($post->body_md);
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

    /**
     * Thumbnail of the first YouTube video in the body (email can't embed iframes).
     */
    private function firstYoutubeThumb(?string $md): ?string
    {
        $embed = app(VideoEmbedService::class);

        foreach (preg_split('/\r\n|\r|\n/', (string) $md) as $line) {
            $line = trim($line);
            if ($line === '' || ! preg_match('#^https?://\S+$#u', $line)) {
                continue;
            }
            $parsed = $embed->parse($line);
            if ($parsed && $parsed['platform'] === 'youtube') {
                return "https://img.youtube.com/vi/{$parsed['video_id']}/hqdefault.jpg";
            }
        }

        return null;
    }
}
