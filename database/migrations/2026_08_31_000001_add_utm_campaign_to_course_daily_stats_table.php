<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the campaign dimension to the daily aggregate (002 US18, D48): the
     * source column answers "which platform" but not "which post / which
     * letter", so a link that brought 300 visitors and no sale was invisible.
     * Rows written before this migration keep utm_campaign = '' — the same
     * value a link with no campaign gets, which the report renders as "—"
     * (the two are deliberately not told apart, see the Schema section).
     *
     * Index order matters: course_id's foreign key needs a leading-column
     * index at all times, so the wider unique is created before the old one
     * is dropped (MySQL errno 150 otherwise) — same three-step shape as
     * 2026_08_02_000001.
     *
     * The index is named explicitly: Laravel's generated name for five columns
     * is 68 characters, and MySQL rejects identifiers over 64. SQLite has no
     * such limit, so the test suite would not have caught it.
     */
    private const UNIQUE = 'cds_course_date_channel_source_campaign_unique';

    public function up(): void
    {
        Schema::table('course_daily_stats', function (Blueprint $table) {
            // 100 chars, matching orders.utm_campaign; stored lowercased (FR-042).
            $table->string('utm_campaign', 100)->default('')->after('source');
        });

        Schema::table('course_daily_stats', function (Blueprint $table) {
            $table->unique(['course_id', 'date', 'channel', 'source', 'utm_campaign'], self::UNIQUE);
        });

        Schema::table('course_daily_stats', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'date', 'channel', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('course_daily_stats', function (Blueprint $table) {
            $table->unique(['course_id', 'date', 'channel', 'source']);
        });

        Schema::table('course_daily_stats', function (Blueprint $table) {
            $table->dropUnique(self::UNIQUE);
        });

        Schema::table('course_daily_stats', function (Blueprint $table) {
            $table->dropColumn('utm_campaign');
        });
    }
};
