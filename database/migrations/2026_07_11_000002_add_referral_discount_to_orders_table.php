<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * US7: buyer-side referral discount snapshot. total_amount already includes
     * the discount; this column records how much was taken off at order time.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('referral_discount_amount')->default(0)->after('referral_reward_points');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('referral_discount_amount');
        });
    }
};
