<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 011 US40 — points paid for leaving a testimonial (FR-243).
 *
 * Schema::change() (not raw MODIFY) so the sqlite test DB updates its CHECK
 * constraint too.
 */
return new class extends Migration
{
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
                'earn_testimonial',
            ])->change();
        });
    }

    public function down(): void
    {
        // Fold the new rows back before narrowing, or the constraint rejects
        // them on rollback.
        DB::table('point_transactions')->where('type', 'earn_testimonial')->update(['type' => 'admin_grant']);

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
};
