<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed 站名 / 經營者 / 地址 for installs that already exist.
 *
 * The navbar, the footer and the legal modal used to print these three values
 * as literals in the Vue source. They are settings now, which means an install
 * that upgrades would render an empty operator line on 服務條款 and 購買須知 —
 * legally required text — until somebody noticed and typed it back in.
 *
 * So the values move rather than disappear: an existing install (recognised by
 * it already having homepage settings) gets exactly what its pages showed
 * before this migration. A fresh database gets nothing, because a fresh
 * database belongs to somebody else and their company is not ours.
 *
 * Never updates an existing row — the admin panel owns these from now on.
 */
return new class extends Migration
{
    private const LEGACY = [
        'site_name'     => '經營者時間銀行',
        'site_operator' => '投好壯壯有限公司',
        'site_address'  => '臺北市文山區辛亥路4段128之1號1樓',
    ];

    public function up(): void
    {
        $isExistingInstall = DB::table('site_settings')->where('key', 'hero_title')->exists();

        if (! $isExistingInstall) {
            return;
        }

        foreach (self::LEGACY as $key => $value) {
            if (DB::table('site_settings')->where('key', $key)->exists()) {
                continue;
            }

            // The site name already had a home: whatever the homepage hero says
            // is the name this site has been calling itself.
            if ($key === 'site_name') {
                $value = trim((string) DB::table('site_settings')->where('key', 'hero_title')->value('value')) ?: $value;
            }

            DB::table('site_settings')->insert([
                'key'        => $key,
                'value'      => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('site_settings')->whereIn('key', array_keys(self::LEGACY))->delete();
    }
};
