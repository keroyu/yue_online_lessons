<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            // A snapshot taken when the booking is confirmed, not a live lookup
            // (011 US15 / FR-061 / D58). The slot's own consultant can be
            // reassigned, and cancelling releases the slot back to the pool
            // where somebody else may claim it — so asking the slot months
            // later would name the wrong person, or nobody at all.
            $table->unsignedBigInteger('consultant_id')->nullable()->after('course_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropColumn('consultant_id');
        });
    }
};
