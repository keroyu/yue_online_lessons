<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // nullable + unique：先建欄位，既有會員由 backfill migration 補發碼。
            $table->string('referral_code', 12)->nullable()->unique()->after('points');
            $table->timestamp('referral_activated_at')->nullable()->after('referral_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['referral_code']);
            $table->dropColumn(['referral_code', 'referral_activated_at']);
        });
    }
};
