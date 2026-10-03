<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Self-booked consultations ride on the lead table (011 US38 / D150).
 *
 * Slots, the week grid, reschedule/cancel, Zoom and the .ics UID are all keyed
 * on a lead, so a paying customer's booking becomes a lead of kind `credit`
 * instead of forking every one of those paths. The default puts every existing
 * row on the sales-funnel side.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->enum('kind', ['application', 'credit'])->default('application')->after('course_id')->index();
            $table->foreignId('purchase_id')->nullable()->after('kind')->constrained('purchases')->nullOnDelete();
            // What this booking actually took off the balance — the number a
            // cancellation gives back (D152). 0 for unlimited holders.
            $table->unsignedTinyInteger('credits_spent')->default(0)->after('purchase_id');
        });
    }

    public function down(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_id');
            $table->dropIndex(['kind']);
            $table->dropColumn(['kind', 'credits_spent']);
        });
    }
};
