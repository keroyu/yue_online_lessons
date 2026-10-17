<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 011 US41 / FR-255 — per-course switch for the booking wizard's screening
 * step. Defaults on, so every existing high-ticket course keeps its gate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('high_ticket_screening')->default(true)->after('high_ticket_hide_price');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('high_ticket_screening');
        });
    }
};
