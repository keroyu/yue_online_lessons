<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 與既有折扣碼欄位（coupon_code/original_amount/discount_amount）並存。
            // FK 已自帶索引，供推薦成效統計查詢用。
            $table->foreignId('referrer_user_id')->nullable()->after('discount_amount')
                ->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('referral_rate')->nullable()->after('referrer_user_id'); // 快照 %
            $table->unsignedInteger('referral_reward_points')->default(0)->after('referral_rate');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referrer_user_id');
            $table->dropColumn(['referral_rate', 'referral_reward_points']);
        });
    }
};
