<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'declined' (011 US24 / FR-127): the applicant was turned away by the
     * automatic screening before any booking existed.
     *
     * Not folded into 'closed'. Closed means "we talked and it went cold";
     * mixing the two would make the funnel pill unable to answer the one
     * question this feature exists to answer — how many people the gate stopped.
     *
     * Schema::change() (not raw MODIFY) so the sqlite test DB updates its CHECK
     * constraint too; the default must be restated or it is dropped.
     */
    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->enum('status', ['pending', 'contacted', 'no_response', 'converted', 'closed', 'cancelled', 'declined'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        // Unlike the 'cancelled' migration this one clears the rows itself:
        // declined leads are produced automatically and in volume, so a rollback
        // would otherwise fail on data nobody created by hand. 'closed' is the
        // nearest surviving meaning.
        DB::table('high_ticket_leads')->where('status', 'declined')->update(['status' => 'closed']);

        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->enum('status', ['pending', 'contacted', 'no_response', 'converted', 'closed', 'cancelled'])
                ->default('pending')
                ->change();
        });
    }
};
