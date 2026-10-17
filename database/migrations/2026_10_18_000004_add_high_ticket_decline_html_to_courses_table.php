<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 011 US42 / FR-259 — per-course copy for the screening refusal screen.
 * Null means the default (`BookingScreening::DEFAULT_DECLINE_HTML`), so every
 * existing course keeps the wording it shows today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->text('high_ticket_decline_html')->nullable()->after('high_ticket_screening');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('high_ticket_decline_html');
        });
    }
};
