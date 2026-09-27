<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'redeem_bundle' (011 US37 / FR-206): points spent on a bundle credit
     * rather than on a whole course. The ledger itself belongs to 007; this is
     * a standalone alter so the create migration stays the record of what 007
     * shipped.
     *
     * Schema::change() (not raw MODIFY) so the sqlite test DB updates its CHECK
     * constraint too.
     */
    public function up(): void
    {
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->enum('type', [
                'earn_homework',
                'redeem_course',
                'earn_referral',
                'refund_reversal',
                'admin_grant',
                'redeem_bundle',
            ])->change();
        });
    }

    public function down(): void
    {
        // Fold the new rows back before narrowing, or the constraint rejects
        // them on rollback.
        DB::table('point_transactions')->where('type', 'redeem_bundle')->update(['type' => 'redeem_course']);

        Schema::table('point_transactions', function (Blueprint $table) {
            $table->enum('type', [
                'earn_homework',
                'redeem_course',
                'earn_referral',
                'refund_reversal',
                'admin_grant',
            ])->change();
        });
    }
};
