<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'cancelled' (011 US14 / FR-051): the consultation was booked and
        // then called off. Folding this into 'closed' would lose the distinction
        // that matters most when reading the list — closed means "we talked and
        // it went cold", cancelled means the conversation never happened.
        //
        // Schema::change() (not raw MODIFY) so the sqlite test DB updates its
        // CHECK constraint too; the default must be restated or it is dropped.
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->enum('status', ['pending', 'contacted', 'no_response', 'converted', 'closed', 'cancelled'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        // Callers must ensure no 'cancelled' rows remain before rolling back,
        // or the constraint will reject them.
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->enum('status', ['pending', 'contacted', 'no_response', 'converted', 'closed'])
                ->default('pending')
                ->change();
        });
    }
};
