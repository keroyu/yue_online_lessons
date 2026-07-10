<?php

namespace App\Jobs;

use App\Mail\NewsletterBroadcastMail;
use App\Models\Broadcast;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBroadcastEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public int $broadcastId,
        public int $userId,
    ) {}

    public function handle(): void
    {
        $broadcast = Broadcast::with('post')->find($this->broadcastId);
        $user = User::find($this->userId);

        if (! $broadcast || ! $broadcast->post || ! $user) {
            return;
        }

        // Recipient may have unsubscribed/gone dormant between snapshot and send.
        if ($user->newsletter_status !== 'subscribed') {
            return;
        }

        Mail::to($user->email)->send(new NewsletterBroadcastMail($broadcast, $user, $broadcast->post));

        $broadcast->increment('sent_count');
    }
}
