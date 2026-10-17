<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'declined' (011 FR-257): the subscriber was turned away by the
        // high-ticket screening for a target course. Stops the sequence like
        // 'booked', but counted on its own so neither the booking rate nor the
        // unsubscribe rate absorbs it.
        //
        // Schema::change() (not raw MODIFY) so the sqlite test DB updates its
        // CHECK constraint too; the default must be restated or it is dropped.
        Schema::table('drip_subscriptions', function (Blueprint $table) {
            $table->enum('status', ['active', 'booked', 'declined', 'converted', 'completed', 'unsubscribed'])
                ->default('active')
                ->change();
        });
    }

    public function down(): void
    {
        // Callers must ensure no 'declined' rows remain before rolling back,
        // or the constraint will reject them.
        Schema::table('drip_subscriptions', function (Blueprint $table) {
            $table->enum('status', ['active', 'booked', 'converted', 'completed', 'unsubscribed'])
                ->default('active')
                ->change();
        });
    }
};
