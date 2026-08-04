<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consultation availability, one row per 15-minute unit (011 US10 / FR-028).
     *
     * There is deliberately no status column: availability is derived from
     * lead_id + held_until (FR-029 / D33), so a row can never claim to be
     * booked while pointing at nobody.
     *
     * A 30-minute consultation occupies 2 consecutive rows, 45 minutes 3.
     */
    public function up(): void
    {
        Schema::create('consultation_slots', function (Blueprint $table) {
            $table->id();
            // Stored in UTC (Laravel default); displayed in Asia/Taipei (D32).
            // Unique so the same instant can never exist twice.
            $table->dateTime('starts_at')->unique('uniq_starts_at');
            // No FK: leads outlive courses and soft deletes here (比照 D7).
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->timestamp('held_until')->nullable();
            $table->timestamps();

            $table->index('lead_id', 'idx_lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_slots');
    }
};
