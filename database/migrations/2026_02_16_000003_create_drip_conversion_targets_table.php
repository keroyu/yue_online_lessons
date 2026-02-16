<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('drip_conversion_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drip_course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('target_course_id')->constrained('courses')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['drip_course_id', 'target_course_id']);
            $table->index('target_course_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drip_conversion_targets');
    }
};
