<?php

use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Install any canonical email template production does not have (011 FR-052).
 *
 * The same shape of bug has now bitten three times: a template is added to the
 * seeder, works everywhere in development, and never reaches production —
 * because seeders run on a fresh install and deploys run `migrate`. The
 * symptom differs each time and is always quiet:
 *
 *   high_ticket_booking_verify        → 送出申請 died with a 422
 *   booking_rescheduled / _cancelled  → change mails logged a warning, sent nothing
 *   high_ticket_slot_available        → 「通知新時段」 refuses to dispatch
 *
 * So this closes the class rather than the instance: every event_type the code
 * reads is checked, and only the absent ones are inserted.
 *
 * Never updates. The owner has edited several of these in the admin panel and
 * their wording is theirs — a "repair" that overwrote it would be a worse bug
 * than the one being fixed (which is exactly why the seeder's updateOrCreate
 * cannot be used here).
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
        // No-op: there is no record of which rows this created, and deleting a
        // template the owner has since edited would destroy their copy.
    }
};
