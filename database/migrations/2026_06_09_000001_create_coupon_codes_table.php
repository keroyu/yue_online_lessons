<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 6)->unique();
            $table->enum('type', ['fixed', 'ratio']);
            $table->decimal('value', 10, 2);
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_codes');
    }
};
