<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the source dimension to the daily aggregate (002 US13, D30): the
     * channel column alone cannot tell Instagram from Threads. Rows written
     * before this migration keep source = '' and render as "未分類".
     *
     * Index order matters: course_id's foreign key needs a leading-column
     * index at all times, so the wider unique is created before the old one
     * is dropped (MySQL errno 150 otherwise).
     */
    public function up(): void
    {
        Schema::table('course_daily_stats', function (Blueprint $table) {
            // 100 chars: unmatched platforms store the raw referrer host (FR-023).
            $table->string('source', 100)->default('')->after('channel');
        });

        Schema::table('course_daily_stats', function (Blueprint $table) {
            $table->unique(['course_id', 'date', 'channel', 'source']);
        });

        Schema::table('course_daily_stats', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'date', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::table('course_daily_stats', function (Blueprint $table) {
            $table->unique(['course_id', 'date', 'channel']);
        });

        Schema::table('course_daily_stats', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'date', 'channel', 'source']);
        });

        Schema::table('course_daily_stats', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
