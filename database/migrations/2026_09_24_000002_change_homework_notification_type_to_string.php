<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 003 US12 / FR-043 — notification type enum → string(20).
 *
 * Same move as video_platform (D10): the set of valid values lives in the code,
 * so adding a kind of notification ('returned' here) stops being a schema change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homework_notifications', function (Blueprint $table) {
            $table->string('type', 20)->change();
        });
    }

    public function down(): void
    {
        Schema::table('homework_notifications', function (Blueprint $table) {
            $table->enum('type', ['reply', 'completion'])->change();
        });
    }
};
