<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align every phone column on varchar(30).
     *
     * `high_ticket_leads.phone` was created at 30 because people write
     * "+886 912-345-678" or add an extension; carrying that value over to the
     * member record (011 US9) would have silently truncated it at 20.
     *
     * This also closes a pre-existing gap: FreePurchaseController already
     * accepted `max:30` while users.phone could only hold 20.
     *
     * Widening is lossless — no existing value can fail to fit.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('buyer_phone', 30)->change();
        });
    }

    public function down(): void
    {
        // Narrowing back would truncate anything already stored above 20.
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('buyer_phone', 20)->change();
        });
    }
};
