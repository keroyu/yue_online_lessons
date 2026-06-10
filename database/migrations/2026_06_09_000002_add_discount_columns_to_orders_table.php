<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_code', 6)->nullable()->after('total_amount');
            $table->decimal('original_amount', 10, 2)->nullable()->after('coupon_code');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('original_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['coupon_code', 'original_amount', 'discount_amount']);
        });
    }
};
