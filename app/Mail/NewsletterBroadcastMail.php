<?php

namespace App\Mail;

use App\Models\Broadcast;
use App\Models\Post;
use App\Models\User;
use App\Services\VideoEmbedService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class NewsletterBroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $postUrl;
    public string $unsubscribeUrl;
    public string $openPixelUrl;
    public ?string $videoThumbUrl = null;

    public function __construct(
        public Broadcast $broadcast,
        public User $user,
        public Post $post,
    ) {
        $this->postUrl = url("/blog/{$post->slug}");
        $this->unsubscribeUrl = url('/newsletter/unsubscribe/' . $user->newsletter_unsubscribe_token);
        $this->openPixelUrl = URL::temporarySignedRoute(
            'newsletter.track.open',
            now()->addDays(180),
            ['broadcast' => $broadcast->id, 'user' => $user->id],
        );
        $this->videoThumbUrl = $this->firstYoutubeThumb($post->body_md);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->broadcast->subject);
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<' . $this->unsubscribeUrl . '>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter-broadcast',
            text: 'emails.newsletter-broadcast-text',
        );
    }

    /**
     * Thumbnail of the first YouTube video in the body (email can't embed iframes). (FR-006)
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
