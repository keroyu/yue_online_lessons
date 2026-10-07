<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Start instants of the "today / last 7 days / last 30 days" windows the admin
 * inflow cards report (011 US39 / FR-236).
 *
 * Calendar days are Taipei's; the instants come back in UTC because query
 * bindings are not converted. Both inflow counts take their edges from here so
 * the two numbers sitting side by side always cover the same period.
 */
final class RecentDayWindows
{
    /**
     * @return array{today: CarbonImmutable, last_7_days: CarbonImmutable, last_30_days: CarbonImmutable}
     */
    public static function starts(): array
    {
        $today = CarbonImmutable::now('Asia/Taipei')->startOfDay();

        return [
            'today'        => $today->utc(),
            'last_7_days'  => $today->subDays(6)->utc(),
            'last_30_days' => $today->subDays(29)->utc(),
        ];
    }
}
