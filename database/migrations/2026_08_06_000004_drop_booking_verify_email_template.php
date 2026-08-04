<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remove the now-unread `high_ticket_booking_verify` template (011 FR-058).
 *
 * The verify mail became a hardcoded Mailable, so this row is no longer read by
 * anything. Leaving it would be worse than deleting it: it would keep showing
 * up in 後台 → Email 模板管理 as something the owner can edit, and edits to it
 * would silently do nothing.
 *
 * Irreversible by design — down() cannot restore text nobody can see anymore,
 * and re-inserting the seeder's old copy would resurrect a dead template.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('email_templates')
            ->where('event_type', 'high_ticket_booking_verify')
            ->delete();
    }

    public function down(): void
    {
        // No-op: nothing reads this event type anymore.
    }
};
