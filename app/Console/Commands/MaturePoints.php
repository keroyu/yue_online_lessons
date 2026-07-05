<?php

namespace App\Console\Commands;

use App\Services\PointService;
use Illuminate\Console\Command;

class MaturePoints extends Command
{
    protected $signature = 'points:mature';

    protected $description = 'Backstop batch: fold due referral rewards into users.points cache';

    public function __construct(protected PointService $pointService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = $this->pointService->matureDue();

        $this->info($count > 0 ? "Matured {$count} point transaction(s)." : 'No point transactions to mature.');

        return self::SUCCESS;
    }
}
