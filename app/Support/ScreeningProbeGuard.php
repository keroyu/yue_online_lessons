<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Stops an IP from learning the screening rubric by trial and error (011 US49).
 *
 * Every valid submission counts, whatever the email or verdict (FR-280). Past
 * MAX_ATTEMPTS the IP is banned for a day, and the caller answers with the
 * ordinary refusal — a ban that announced itself would just send the prober
 * to another IP (D183).
 */
class ScreeningProbeGuard
{
    public const MAX_ATTEMPTS = 2;

    public const WINDOW_SECONDS = 86400;

    public const BAN_SECONDS = 86400;

    /**
     * Whether this submission may be screened. Counts it as a side effect.
     *
     * @param  array<string, mixed>  $context  logged when the ban triggers
     */
    public static function admit(string $ip, array $context = []): bool
    {
        $banKey = "screening-ban:{$ip}";

        // Submissions during a ban neither count nor extend it
        if (Cache::has($banKey)) {
            return false;
        }

        $countKey = "screening-probe:{$ip}";

        if (RateLimiter::hit($countKey, self::WINDOW_SECONDS) <= self::MAX_ATTEMPTS) {
            return true;
        }

        Cache::put($banKey, true, self::BAN_SECONDS);
        RateLimiter::clear($countKey);
        Log::warning('Screening probe: IP banned', ['ip' => $ip] + $context);

        return false;
    }
}
