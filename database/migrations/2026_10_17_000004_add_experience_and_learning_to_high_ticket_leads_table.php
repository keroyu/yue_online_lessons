<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 011 FR-254 — Q6 (how long the applicant has seriously worked on social
 * media) and Q7 (what they do when stuck). Neither scores; together they feed
 * a combo veto. Null on every lead screened before this question existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->string('screen_experience', 20)->nullable()->after('screen_next_step');
            $table->string('screen_learning', 20)->nullable()->after('screen_experience');
        });
    }

    public function down(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropColumn(['screen_experience', 'screen_learning']);
        });
    }
};
