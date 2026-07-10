<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class NewsletterWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $unsubscribeUrl;

    public function __construct(public User $user)
    {
        $this->unsubscribeUrl = url('/newsletter/unsubscribe/' . $user->newsletter_unsubscribe_token);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '訂閱成功，歡迎加入');
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
        return new Content(view: 'emails.newsletter-welcome');
    }
}
