<?php

namespace App\Console\Commands;

use App\Services\ConsultationSlotService;
use Illuminate\Console\Command;

/**
 * Housekeeping only (011 FR-035 / D33).
 *
 * Availability queries already treat an expired hold as free, so nothing a
 * visitor can see depends on this command running. It exists so the admin slot
 * page does not show stale owners on units nobody actually booked.
 */
class ReleaseExpiredBookingHolds extends Command
{
    protected $signature = 'booking:release-holds';

    protected $description = '清除逾時未確認的預約時段暫留（資料整理，非正確性來源）';

    public function handle(ConsultationSlotService $slots): int
    {
        $released = $slots->releaseExpired();

        $this->info("已釋出 {$released} 個逾時暫留時段");

        return self::SUCCESS;
    }
}
