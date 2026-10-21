<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 011 US48 / FR-276 — when a stopped sequence was last resumed. Non-null puts
 * the subscription on the one-email-every-three-days pace that follows a
 * resume; written only by DripService::resumeSequence().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drip_subscriptions', function (Blueprint $table) {
            $table->timestamp('resumed_at')->nullable()->after('status_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('drip_subscriptions', function (Blueprint $table) {
            $table->dropColumn('resumed_at');
        });
    }
};
