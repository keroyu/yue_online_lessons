<?php

namespace App\Console\Commands;

use App\Services\NewsletterService;
use Illuminate\Console\Command;

class CleanDormantSubscribers extends Command
{
    protected $signature = 'newsletter:clean-dormant {--days=60}';

    protected $description = 'Move subscribers who were sent broadcasts but opened none within the window to dormant';

    public function handle(NewsletterService $newsletterService): int
    {
        $days = (int) $this->option('days');
        $count = $newsletterService->markDormantInactive($days);

        $this->info("Marked {$count} subscriber(s) as dormant.");

        return Command::SUCCESS;
    }
}
