<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer consultation records — one row per session (011 US23 / D92).
 *
 * Keyed on `email` rather than `lead_id` or `user_id`: a lead only exists on the
 * pre-sale path, and someone who buys repeat 1-on-1 coaching accumulates
 * sessions that belong to no lead at all. Email is the one key every channel
 * shares (same reasoning as 000 D22 for `email_suppressions`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_notes', function (Blueprint $table) {
            $table->id();

            // The customer identity. Always lowercase — normalised on write.
            $table->string('email', 255)->index();

            // Which flow produced this row. No DB enum: the upcoming
            // self-service coaching feature writes its own value (比照 004 的
            // content_category 作法).
            $table->string('source', 30)->default('high_ticket_booking');

            // Provenance, not identity — all three may be null.
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('consultant_id')->nullable()->index();
            $table->unsignedBigInteger('course_id')->nullable();

            $table->dateTime('met_at')->index();

            // Nullable so a phone or in-person session fits here later; unique so
            // one Zoom meeting can only ever map to one record.
            $table->string('zoom_meeting_id', 50)->nullable()->unique();

            // Proofread and anonymised only. The raw VTT and the real display
            // names never reach the database (FR-109).
            $table->longText('transcript')->nullable();
            $table->timestamp('transcript_fetched_at')->nullable();
            $table->timestamp('transcript_edited_at')->nullable();

            $table->text('summary')->nullable();
            $table->timestamp('summary_generated_at')->nullable();
            $table->timestamp('summary_edited_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_notes');
    }
};
