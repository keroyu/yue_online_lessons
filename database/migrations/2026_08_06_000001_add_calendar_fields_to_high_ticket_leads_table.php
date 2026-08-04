<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            // The iCalendar SEQUENCE (011 US14 / FR-047). A counter is the one
            // thing about the invite that cannot be derived — the UID comes from
            // the lead id, the times come from the slots, but "how many times has
            // this been revised" only exists if we keep it. Calendar clients use
            // it to decide whether an incoming .ics supersedes the copy they
            // already hold; a stale SEQUENCE means the update is silently ignored.
            $table->unsignedTinyInteger('calendar_sequence')->default(0)->after('zoom_join_url');

            // Distinct from `status = cancelled` on purpose: status is an admin
            // funnel label that anyone can flip by hand, this is the fact.
            $table->timestamp('cancelled_at')->nullable()->after('calendar_sequence');
        });
    }

    public function down(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropColumn(['calendar_sequence', 'cancelled_at']);
        });
    }
};
