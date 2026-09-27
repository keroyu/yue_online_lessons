<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unlimited bundle perk (011 US37 / FR-213).
     *
     * The flag sits on the tier, next to the quantity it replaces — and NOT on
     * `purchases`, unlike the balance. A quantity is a batch already handed
     * over, so it is snapshotted; "unlimited" is a standing entitlement, so it
     * is read from the tier every time and stays current for everyone holding
     * it (D148).
     *
     * The course-level column is the no-plans fallback only, mirroring
     * bundle_default_quantity.
     */
    public function up(): void
    {
        Schema::table('course_plans', function (Blueprint $table) {
            $table->boolean('bundle_unlimited')->default(false)->after('bundle_quantity');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('bundle_unlimited')->default(false)->after('bundle_default_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('course_plans', function (Blueprint $table) {
            $table->dropColumn('bundle_unlimited');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('bundle_unlimited');
        });
    }
};
