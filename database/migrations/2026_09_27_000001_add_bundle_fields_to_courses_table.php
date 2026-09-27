<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bundle perk sold alongside a high-ticket course (011 US37 / FR-201).
     *
     * `bundle_name` is the feature switch: empty means this course has no perk
     * and the whole mechanism is invisible to it — same shape as plans, where
     * the row count is the switch (D82 / D141).
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('bundle_name', 50)->nullable()->after('redeem_points');
            // Cost of ONE top-up. Null = cannot be bought with points, matching
            // the null semantics of courses.redeem_points.
            $table->unsignedInteger('bundle_redeem_points')->nullable()->after('bundle_name');
            // Only read when the course has no plans; otherwise the plan's own
            // quantity wins (FR-202).
            $table->unsignedSmallInteger('bundle_default_quantity')->default(0)->after('bundle_redeem_points');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['bundle_name', 'bundle_redeem_points', 'bundle_default_quantity']);
        });
    }
};
