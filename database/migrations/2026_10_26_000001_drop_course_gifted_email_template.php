<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remove the `course_gifted` template (008 FR-012).
 *
 * Gifting a course was removed in favour of member import, so nothing sends
 * this mail anymore. Left in place it would keep showing up in 後台 → Email
 * 模板管理 as something the owner can edit, with edits silently doing nothing.
 *
 * Irreversible by design, same as the booking-verify drop before it.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('email_templates')
            ->where('event_type', 'course_gifted')
            ->delete();
    }

    public function down(): void
    {
        // No-op: nothing reads this event type anymore.
    }
};
