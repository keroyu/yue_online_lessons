<?php

namespace App\Support;

/**
 * One canonical form for phone numbers (011 US16 / FR-064 / D62).
 *
 * Exists because the number became a deduplication key: `0912-345-678` and
 * `+886912345678` are the same person, and string equality says otherwise.
 *
 * Deliberately not libphonenumber — the audience is Taiwan, the handful of
 * local spellings below is the whole problem, and that package brings a
 * numbering-plan database that has to be kept current. Anything foreign
 * degrades to digits-only, which is still stable enough to compare.
 */
class PhoneNumber
{
    /** Taiwan's country code, written either way. */
    private const COUNTRY_CODE = '886';

    /**
     * Digits only, with the country code folded back into the leading zero.
     * Null for anything with no digits at all — the column is nullable and a
     * blank string would be a second way to say "unknown".
     */
    public static function normalise(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        // 886912345678 → 0912345678. The `+` is already gone with the other
        // non-digits, so both spellings arrive here identically.
        if (str_starts_with($digits, self::COUNTRY_CODE)) {
            $digits = '0' . substr($digits, strlen(self::COUNTRY_CODE));
        }

        return $digits;
    }
}
