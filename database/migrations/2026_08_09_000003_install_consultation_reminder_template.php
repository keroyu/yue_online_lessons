<?php

use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Install the 面談提醒 template on production (011 US19 / FR-079).
 *
 * Same loop as 2026_08_08_000003: seeders only run on a fresh install, so every
 * new template needs a migration to reach a live database. Written generically
 * rather than for this one event_type, which makes it the safety net for
 * anything the previous pass missed as well — it walks the whole canonical set
 * and inserts only what is absent.
 *
 * Never updates. The owner edits these bodies in the admin panel and that
 * wording is theirs.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (EmailTemplateSeeder::templates() as $template) {
            $exists = DB::table('email_templates')
                ->where('event_type', $template['event_type'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('email_templates')->insert(array_merge($template, [
                'body_type'  => 'markdown',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        // No-op: nothing records which rows this created, and dropping a
        // template the owner has since edited would destroy their copy.
    }
};
