<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_roadmap_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_roadmap_stage_id')->constrained()->cascadeOnDelete();
            $table->string('label', 500);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['course_roadmap_stage_id', 'sort_order'], 'roadmap_checkpoints_stage_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_roadmap_checkpoints');
    }
};
