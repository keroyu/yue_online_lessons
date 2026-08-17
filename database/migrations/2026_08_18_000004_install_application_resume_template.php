<?php

use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Install the 申請未完成提醒 template on production (011 US26 / FR-136).
 *
 * Same generic loop as 2026_08_09_000003: seeders only run on a fresh install,
 * so a new template needs a migration to reach a live database. It walks the
 * whole canonical set and inserts only what is absent, which makes it the
 * safety net for anything an earlier pass missed as well.
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
