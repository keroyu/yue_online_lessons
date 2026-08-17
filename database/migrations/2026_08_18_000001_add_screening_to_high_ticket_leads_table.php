<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The five qualifying answers, their score and the decline stamp (011 US24).
     *
     * Five columns rather than one JSON blob: these answers exist to be counted
     * ("what is the close rate for people who ticked X"), which is a GROUP BY,
     * and the test suite runs on sqlite where JSON querying is a second dialect
     * to keep working. No DB-level enum either — the options move whenever the
     * pricing does, and `BookingScreening` is where that list lives (FR-123).
     *
     * Every column is nullable: leads created before this feature, and anybody
     * resuming from a 「通知新時段」 mail, have no screening at all (FR-129).
     */
    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->string('screen_timeline', 20)->nullable()->after('social_url');
            $table->string('screen_budget', 20)->nullable()->after('screen_timeline');
            $table->string('screen_authority', 20)->nullable()->after('screen_budget');
            $table->string('screen_pain', 20)->nullable()->after('screen_authority');
            $table->string('screen_next_step', 20)->nullable()->after('screen_pain');

            // Stored, not recomputed on read: the scoring table changes with the
            // pricing, and "why was this person turned away in August" has to
            // stay answerable in October.
            $table->unsignedTinyInteger('screening_score')->nullable()->after('screen_next_step');
            $table->timestamp('screened_at')->nullable()->after('screening_score');

            $table->timestamp('declined_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropColumn([
                'screen_timeline',
                'screen_budget',
                'screen_authority',
                'screen_pain',
                'screen_next_step',
                'screening_score',
                'screened_at',
                'declined_at',
            ]);
        });
    }
};
