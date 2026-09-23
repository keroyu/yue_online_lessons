<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roadmap_checkpoint_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Named explicitly: the generated constraint name
            // (roadmap_checkpoint_completions_course_roadmap_checkpoint_id_foreign)
            // is 67 chars and MySQL caps identifiers at 64. sqlite does not
            // enforce that, so the test suite stays green either way.
            $table->unsignedBigInteger('course_roadmap_checkpoint_id');
            $table->foreign('course_roadmap_checkpoint_id', 'roadmap_completion_checkpoint_fk')
                ->references('id')
                ->on('course_roadmap_checkpoints')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'course_roadmap_checkpoint_id'], 'roadmap_completion_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roadmap_checkpoint_completions');
    }
};
