<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * 002 US20 — retire the hero CTA button and the sidebar owner avatar (FR-062).
 *
 * The rows are deleted rather than left unread: `site_settings` is a flat
 * key-value table, and three keys nothing reads any more force the next person
 * to grep the whole repo before they dare touch them. `sns_profile_image_path`
 * also points at a real file, so the file goes first — the other order leaves
 * an orphan nobody knows the path of.
 */
return new class extends Migration
{
    private const RETIRED_KEYS = [
        'hero_button_label',
        'hero_button_url',
        'sns_profile_image_path',
    ];

    public function up(): void
    {
        $path = SiteSetting::get('sns_profile_image_path');

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        SiteSetting::whereIn('key', self::RETIRED_KEYS)->delete();
    }

    public function down(): void
    {
        // Intentionally a no-op. What was deleted is content the admin typed
        // and a file they uploaded; a `down()` that pretended to restore either
        // would be worse than none at all.
    }
};
