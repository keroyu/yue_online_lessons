<?php

namespace App\Console\Commands;

use App\Services\HighTicketBookingService;
use Illuminate\Console\Command;

/**
 * Sweep out applications nobody confirmed (011 FR-035 / FR-068 / D33).
 *
 * Releasing the slot is housekeeping — availability already treats an expired
 * hold as free, so nothing a visitor sees depends on it. Deleting the lead is
 * not: the Leads list keeps showing abandoned applications until this runs.
 */
class ReleaseExpiredBookingHolds extends Command
{
    protected $signature = 'booking:release-holds';

    protected $description = '釋出逾時未確認的預約時段，並刪除該筆未完成的申請';

    public function handle(HighTicketBookingService $booking): int
    {
        $purged = $booking->purgeExpiredApplications();

        $this->info("已釋出逾時暫留時段，並刪除 {$purged} 筆未完成的申請");

        return self::SUCCESS;
    }
}
