<?php

namespace App\Mail;

use App\Models\Broadcast;
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
use Illuminate\Support\Facades\URL;

class NewsletterBroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $postUrl;
    public string $unsubscribeUrl;
    public string $openPixelUrl;
    public string $bodyHtml;
    public string $bodyText;

    public function __construct(
        public Broadcast $broadcast,
        public User $user,
        public Post $post,
    ) {
        // Stamped once here so the HTML and plain-text bodies — which both link
        // to the post — carry the same attribution (002 US14).
        $utm = [
            'utm_source'   => 'newsletter',
            'utm_medium'   => 'email',
            'utm_campaign' => "broadcast-{$broadcast->id}",
            'utm_content'  => $post->slug,
        ];
        $this->postUrl = app(EmailLinkTagger::class)->tagUrl(url("/blog/{$post->slug}"), $utm);
        $this->unsubscribeUrl = url('/newsletter/unsubscribe/' . $user->newsletter_unsubscribe_token);
        $this->openPixelUrl = URL::temporarySignedRoute(
            'newsletter.track.open',
            now()->addDays(180),
            ['broadcast' => $broadcast->id, 'user' => $user->id],
        );

        // The whole post goes out, not a teaser (012 US10).
        $posts = app(PostService::class);
        $this->bodyHtml = $posts->toEmailHtml($post, $this->postUrl, $utm);
        $this->bodyText = $posts->toEmailText($this->bodyHtml, $this->postUrl);
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
            'X-Mail-Class' => 'marketing',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter-broadcast',
            text: 'emails.newsletter-broadcast-text',
        );
    }
}
