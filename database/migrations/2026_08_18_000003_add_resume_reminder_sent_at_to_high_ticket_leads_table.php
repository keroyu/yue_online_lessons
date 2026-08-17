<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One nudge per abandoned application, ever (011 US26 / FR-136).
     *
     * Its own column rather than `last_notified_at`, which belongs to 「通知新時段」
     * (US4): sharing one timestamp between two unrelated mails means sending
     * either one silently cancels the other.
     */
    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->timestamp('resume_reminder_sent_at')->nullable()->after('reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropColumn('resume_reminder_sent_at');
        });
    }
};
