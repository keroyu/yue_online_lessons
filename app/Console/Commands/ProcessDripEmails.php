<?php

namespace App\Console\Commands;

use App\Services\DripService;
use Illuminate\Console\Command;

class ProcessDripEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drip:process-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send scheduled drip emails';

    public function __construct(protected DripService $dripService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = $this->dripService->processDailyEmails();

        if ($count > 0) {
            $this->info("Sent {$count} drip email(s).");
        } else {
            $this->info('No drip emails to send.');
        }

        return Command::SUCCESS;
    }
}
