<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'no_response': the admin reached out but the lead went silent.
        // Sits between contacted and converted in the funnel — still warm enough
        // to keep in the list, cold enough to hand over to the drip sequence.
        //
        // Schema::change() (not raw MODIFY) so the sqlite test DB updates its
        // CHECK constraint too; the default must be restated or it is dropped.
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->enum('status', ['pending', 'contacted', 'no_response', 'converted', 'closed'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        // Revert to the original four values. Callers must ensure no
        // 'no_response' rows remain before rolling back, or the constraint
        // will reject them.
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->enum('status', ['pending', 'contacted', 'converted', 'closed'])
                ->default('pending')
                ->change();
        });
    }
};
