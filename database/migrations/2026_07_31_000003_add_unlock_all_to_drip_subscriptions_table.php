<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "This subscription ignores the unlock cursor." Converted subscribers
        // used to see every lesson as a reward; the funnel now stops at the
        // goal instead. Backfill the existing converted rows so nobody loses
        // content they already had — new conversions all get false.
        Schema::table('drip_subscriptions', function (Blueprint $table) {
            $table->boolean('unlock_all')->default(false)->after('status');
        });

        DB::table('drip_subscriptions')
            ->where('status', 'converted')
            ->update(['unlock_all' => true]);
    }

    public function down(): void
    {
        Schema::table('drip_subscriptions', function (Blueprint $table) {
            $table->dropColumn('unlock_all');
        });
    }
};
