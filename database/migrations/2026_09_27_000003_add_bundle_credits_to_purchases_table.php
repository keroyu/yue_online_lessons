<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bundle credit balance, carried on the purchase itself (011 US37 / D141).
     *
     * This table is already unique(user_id, course_id) and already carries
     * course_plan_id, so "which tier, how many left, joined when" end up as
     * three columns of one row — which is why the roster is a single-table
     * query and consuming credits is one conditional UPDATE.
     *
     * unsigned is the second line of defence behind the guarded decrement
     * (FR-205); there is no ledger to reconcile against.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->unsignedSmallInteger('bundle_balance')->default(0)->after('course_plan_id');
            // How many the plan should have granted in total — the idempotency
            // key for FR-203. Top-ups and consumption never touch it.
            $table->unsignedSmallInteger('bundle_granted')->default(0)->after('bundle_balance');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['bundle_balance', 'bundle_granted']);
        });
    }
};
