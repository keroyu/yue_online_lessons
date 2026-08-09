<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            // One reminder per booking (011 US19 / FR-078). The daily schedule
            // running once is not a guarantee — a manual re-run while debugging,
            // or a retry after a deploy, would put a second copy of the same
            // reminder in a customer's inbox. Rescheduling clears it back to
            // null so the new date gets its own reminder (D72).
            $table->timestamp('reminder_sent_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
