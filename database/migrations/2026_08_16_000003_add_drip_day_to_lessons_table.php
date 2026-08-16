<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which day after subscribing this lesson's email goes out.
     *
     * Null keeps the old evenly-spaced formula (position x
     * courses.drip_interval_days), so existing drip courses and their running
     * subscriptions need no backfill.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedSmallInteger('drip_day')->nullable()->after('video_access_hours');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('drip_day');
        });
    }
};
