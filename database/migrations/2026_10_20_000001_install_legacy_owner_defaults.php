<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The support address, the lead CC list, the Portaly store handle and the
 * navbar/OG logo used to be built-in defaults that belonged to the original
 * install. They are owner-specific, so the code no longer ships them.
 *
 * An existing install (one with homepage settings) gets exactly what its pages
 * and mail used before this migration, written as ordinary settings. A fresh
 * database gets nothing — it belongs to somebody else.
 *
 * Never updates an existing row — the admin panel owns these from now on.
 */
return new class extends Migration
{
    private const LEGACY = [
        'support_email'              => 'themustbig+learn@gmail.com',
        'high_ticket_lead_notify_cc' => 'themustbig+leads@gmail.com',
        'portaly_store_slug'         => 'kyontw',
    ];

    private const LOGO_KEY = 'site_logo_path';

    private const LOGO_SOURCE = 'database/legacy/og-logo.png';

    private const LOGO_TARGET = 'site-icons/legacy-logo.png';

    public function up(): void
    {
        $isExistingInstall = DB::table('site_settings')->where('key', 'hero_title')->exists();

        if (! $isExistingInstall) {
            return;
        }

        foreach (self::LEGACY as $key => $value) {
            $this->fillIfBlank($key, $value);
        }

        $source = base_path(self::LOGO_SOURCE);
        if ($this->isBlank(self::LOGO_KEY) && is_file($source)) {
            Storage::disk('public')->put(self::LOGO_TARGET, file_get_contents($source));
            $this->fillIfBlank(self::LOGO_KEY, self::LOGO_TARGET);
        }
    }

    public function down(): void
    {
        // Only remove what this migration could have written; an admin may
        // have changed any of these since.
        foreach (self::LEGACY as $key => $value) {
            DB::table('site_settings')->where('key', $key)->where('value', $value)->delete();
        }

        DB::table('site_settings')->where('key', self::LOGO_KEY)->where('value', self::LOGO_TARGET)->delete();
    }

    private function isBlank(string $key): bool
    {
        return trim((string) DB::table('site_settings')->where('key', $key)->value('value')) === '';
    }

    private function fillIfBlank(string $key, string $value): void
    {
        if (! $this->isBlank($key)) {
            return;
        }

        DB::table('site_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'created_at' => now(), 'updated_at' => now()],
        );
    }
};
