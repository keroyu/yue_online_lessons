<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Many-to-many on purpose: plans are allowed to overlap on the same lesson
 * (A: 1,2,3 and B: 2,4,5), which a column on `lessons` could not express.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_plan_lesson', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();

            $table->unique(['course_plan_id', 'lesson_id']);
            $table->index('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_plan_lesson');
    }
};
