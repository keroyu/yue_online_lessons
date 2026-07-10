<?php

namespace App\Services;

use App\Jobs\SendBroadcastEmailJob;
use App\Models\Broadcast;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Carbon;

class BroadcastService
{
    /**
     * Send a post as a broadcast right now.
     */
    public function createImmediate(Post $post): Broadcast
    {
        $broadcast = Broadcast::create([
            'post_id' => $post->id,
            'subject' => $post->title,
            'status' => 'draft',
            'recipients_count' => 0,
            'sent_count' => 0,
        ]);

        $this->dispatchTo($broadcast, $post);

        return $broadcast;
    }

    /**
     * Queue a post to be broadcast at a future time. Recipients are snapshotted when
     * it actually sends (via dispatchDue), not now.
     */
    public function schedule(Post $post, Carbon $when): Broadcast
    {
        return Broadcast::create([
            'post_id' => $post->id,
            'subject' => $post->title,
            'status' => 'scheduled',
            'scheduled_at' => $when,
            'recipients_count' => 0,
            'sent_count' => 0,
        ]);
    }

    /**
     * Dispatch all scheduled broadcasts whose time has come. Returns the count sent.
     */
    public function dispatchDue(): int
    {
        $due = Broadcast::with('post')
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $sent = 0;
        foreach ($due as $broadcast) {
            // Skip (and drop) if the post was unpublished/deleted before send time.
            if (! $broadcast->post || $broadcast->post->status !== 'published') {
                $broadcast->update(['status' => 'sent', 'sent_at' => now()]);
                continue;
            }
            $this->dispatchTo($broadcast, $broadcast->post);
            $sent++;
        }

        return $sent;
    }

    /**
     * Snapshot current subscribers, enqueue one job each, then mark sent.
     */
    private function dispatchTo(Broadcast $broadcast, Post $post): void
    {
        $recipientIds = User::newsletterSubscribed()->pluck('id');

        $broadcast->update([
            'status' => 'sending',
            'recipients_count' => $recipientIds->count(),
        ]);

        foreach ($recipientIds as $userId) {
            SendBroadcastEmailJob::dispatch($broadcast->id, $userId);
        }

        $broadcast->update(['status' => 'sent', 'sent_at' => now()]);
    }
}
