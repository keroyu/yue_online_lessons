<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 011 US43 / FR-260 — one screening refusal email per lead. Set when a job
 * claims the send, cleared again only if that send fails.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->timestamp('screening_decline_mailed_at')->nullable()->after('declined_at');
        });
    }

    public function down(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropColumn('screening_decline_mailed_at');
        });
    }
};
