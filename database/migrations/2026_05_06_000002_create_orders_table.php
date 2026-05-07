<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('buyer_name', 100);
            $table->string('buyer_email', 255);
            $table->string('buyer_phone', 20);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 10)->default('TWD');
            $table->string('payment_gateway', 20);
            $table->string('merchant_order_no', 30)->nullable()->unique();
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('gateway_trade_no', 100)->nullable();
            $table->timestamp('webhook_received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
