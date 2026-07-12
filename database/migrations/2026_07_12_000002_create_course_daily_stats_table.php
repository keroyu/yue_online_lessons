<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('channel', 20); // paid/social/search/email/video/referral/direct
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('add_to_cart')->default(0);
            $table->unsignedInteger('checkouts')->default(0);
            $table->unsignedInteger('purchases')->default(0);
            $table->unsignedInteger('revenue')->default(0);
            $table->timestamps();

            $table->unique(['course_id', 'date', 'channel']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_daily_stats');
    }
};
