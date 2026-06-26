<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_chains', function (Blueprint $table) {
            $table->id();
            $table->string('alias', 50)->unique();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['fixed', 'ratio']);
            $table->decimal('value', 10, 2);
            $table->unsignedInteger('code_max_uses')->default(1); // 0 = unlimited per code (no auto-regen)
            $table->boolean('is_active')->default(true);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_chains');
    }
};
