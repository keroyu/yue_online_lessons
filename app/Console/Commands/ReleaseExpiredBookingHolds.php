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

    protected $description = '釋出逾時未確認的預約時段，申請保留為未完成 Email 確認';

    public function handle(HighTicketBookingService $booking): int
    {
        $expired = $booking->expireUnconfirmedApplications();

        $this->info("已釋出逾時暫留時段，{$expired} 筆申請標為未完成 Email 確認");

        return self::SUCCESS;
    }
}
