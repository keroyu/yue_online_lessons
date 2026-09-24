<?php

namespace App\Http\Requests\Concerns;

use Carbon\Carbon;

/**
 * Turns a bare wall-clock string from a `datetime-local` input into a real
 * instant before validation runs.
 *
 * The browser posts `2026-10-01T09:00` with no offset, and the admin means
 * 09:00 in Taipei. Left alone, Laravel parses it in app.timezone (UTC), so the
 * course opens at 17:00 Taipei and `after:now` compares against the wrong
 * moment — the rule can pass a time that is already 8 hours in the past.
 * Normalising here rather than in the controller means the validator and the
 * controller both see the same corrected instant (D: DB stores UTC, the
 * Taipei↔UTC edge lives at the request boundary).
 */
trait NormalizesTaipeiInput
{
    protected const INPUT_TZ = 'Asia/Taipei';

    /**
     * Re-read the named inputs as Taipei wall-clock and hand them on in UTC.
     *
     * UTC, not a +08:00 offset: Eloquent writes a Carbon by formatting it in
     * whatever timezone the instance carries, with no conversion of its own, so
     * a Taipei-offset instance would be stored as its Taipei wall-clock and
     * read back as UTC — the same eight hours, just moved one step later.
     *
     * Missing, empty and unparseable values are left untouched so the `date`
     * rule still reports them; a value that already carries an offset is
     * respected as sent.
     */
    protected function readAsTaipei(string ...$keys): void
    {
        foreach ($keys as $key) {
            $value = $this->input($key);

            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            try {
                $this->merge([
                    $key => Carbon::parse($value, self::INPUT_TZ)->utc()->toIso8601String(),
                ]);
            } catch (\Exception) {
                // Not a date at all — leave it for the `date` rule to reject.
            }
        }
    }
}
