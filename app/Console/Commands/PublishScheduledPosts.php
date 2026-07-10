<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'Publish scheduled posts whose published_at time has passed';

    public function handle(): int
    {
        $published = Post::where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published']);

        if ($published > 0) {
            $this->info("Published {$published} scheduled post(s).");
        }

        return Command::SUCCESS;
    }
}
