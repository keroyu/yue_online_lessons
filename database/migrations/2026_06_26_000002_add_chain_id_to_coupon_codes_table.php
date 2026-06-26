<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupon_codes', function (Blueprint $table) {
            $table->foreignId('chain_id')
                ->nullable()
                ->after('note')
                ->constrained('coupon_chains')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('coupon_codes', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\CouponChain::class);
            $table->dropColumn('chain_id');
        });
    }
};
