<?php

namespace App\Console\Commands;

use App\Services\HighTicketBookingService;
use Illuminate\Console\Command;

/**
 * Retire applications that cleared the screening and never came back (011 US32).
 *
 * Daily rather than hourly: the boundary is a week old, so the hour it crosses
 * at does not matter to anybody. Sends nothing — the one nudge this population
 * gets went out inside that week (US26).
 */
class CancelStaleApplications extends Command
{
    protected $signature = 'booking:cancel-stale-applications';

    protected $description = '把通過資格審核後 7 天仍未完成申請的名單歸類為已取消';

    public function handle(HighTicketBookingService $booking): int
    {
        $cancelled = $booking->cancelStaleApplications();

        $this->info("已將 {$cancelled} 筆未完成的申請歸類為已取消");

        return self::SUCCESS;
    }
}
