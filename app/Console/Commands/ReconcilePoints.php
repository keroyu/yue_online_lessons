<?php

namespace App\Console\Commands;

use App\Services\PointService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcilePoints extends Command
{
    protected $signature = 'points:reconcile';

    protected $description = 'Assert users.points == SUM(matured ledger); report any drift (SC-002)';

    public function __construct(protected PointService $pointService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $drift = $this->pointService->reconcile();

        if (empty($drift)) {
            $this->info('Points reconciled: no drift.');

            return self::SUCCESS;
        }

        $this->error(count($drift) . ' user(s) drifted between cache and ledger:');
        $this->table(['User ID', 'Cached', 'Ledger'], array_map(fn ($d) => [
            $d['user_id'], $d['cached'], $d['ledger'],
        ], $drift));

        Log::warning('points:reconcile detected drift', ['drift' => $drift]);

        return self::FAILURE;
    }
}
