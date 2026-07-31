<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'booked': the subscriber completed a high-ticket booking for a
        // target course. Behaves like 'converted' (stops the sequence, exits the
        // funnel) but is counted separately so booking vs actual sales stay
        // distinguishable in the admin stats.
        //
        // Schema::change() (not raw MODIFY) so the sqlite test DB updates its
        // CHECK constraint too; the default must be restated or it is dropped.
        Schema::table('drip_subscriptions', function (Blueprint $table) {
            $table->enum('status', ['active', 'booked', 'converted', 'completed', 'unsubscribed'])
                ->default('active')
                ->change();
        });
    }

    public function down(): void
    {
        // Revert to the original four values. Callers must ensure no 'booked'
        // rows remain before rolling back, or the constraint will reject them.
        Schema::table('drip_subscriptions', function (Blueprint $table) {
            $table->enum('status', ['active', 'converted', 'completed', 'unsubscribed'])
                ->default('active')
                ->change();
        });
    }
};
