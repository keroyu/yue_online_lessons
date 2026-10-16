<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 011 US40 — client testimonials collected after a consultation.
 *
 * Course and consultation time are snapshotted from the lead so a testimonial
 * keeps rendering after the lead row is reused or deleted (D158). `lead_id` is
 * unique: one testimonial per lead is the last line of defence against a
 * double reward (FR-242).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->unique()->constrained('high_ticket_leads')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('nickname', 20);
            $table->string('title', 40)->nullable();
            $table->text('content');
            // Sort key and admin reference only — never shown publicly (FR-248).
            $table->timestamp('consulted_at');
            $table->enum('identity', ['consultation', 'student'])->default('consultation');
            $table->enum('status', ['pending', 'published', 'hidden'])->default('pending');
            $table->unsignedInteger('reward_points')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'status', 'consulted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
