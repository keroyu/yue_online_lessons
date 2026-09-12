<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * 002 US21 — `hero_claim_course_id` → `hero_promo_course_id` (FR-070 / D63).
 *
 * The hero form no longer claims that course; it subscribes to the newsletter,
 * and the setting now only drives the 📌 line underneath. Keeping the old name
 * would cost nothing today and mislead for years.
 *
 * Unlike the US20 retirement migration, this one moves a value rather than
 * deleting content, so `down()` can genuinely reverse it.
 */
return new class extends Migration
{
    private const OLD_KEY = 'hero_claim_course_id';
    private const NEW_KEY = 'hero_promo_course_id';

    public function up(): void
    {
        $this->move(self::OLD_KEY, self::NEW_KEY);
    }

    public function down(): void
    {
        $this->move(self::NEW_KEY, self::OLD_KEY);
    }

    private function move(string $from, string $to): void
    {
        $value = SiteSetting::get($from);

        if ($value !== null) {
            SiteSetting::set($to, $value);
        }

        SiteSetting::where('key', $from)->delete();
    }
};
