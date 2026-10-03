<?php

use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Install the 諮詢預約成立 template on production (011 US38 / FR-230).
 *
 * Same insert-only loop as 2026_08_20_000001: seeders never run on a live
 * database, and bodies the owner has edited are theirs.
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
        // No-op, as in every template installer.
    }
};
