<?php

namespace App\Console\Commands;

use App\Services\BroadcastService;
use Illuminate\Console\Command;

class SendScheduledBroadcasts extends Command
{
    protected $signature = 'newsletter:send-scheduled';

    protected $description = 'Dispatch scheduled newsletter broadcasts whose time has come';

    public function handle(BroadcastService $broadcastService): int
    {
        $sent = $broadcastService->dispatchDue();

        if ($sent > 0) {
            $this->info("Dispatched {$sent} scheduled broadcast(s).");
        }

        return Command::SUCCESS;
    }
}
