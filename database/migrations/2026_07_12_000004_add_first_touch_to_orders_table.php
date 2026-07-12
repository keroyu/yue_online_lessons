<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // First-touch source snapshot; existing utm/click id columns keep
            // their last-touch semantics (002 US10).
            $table->json('first_touch')->nullable()->after('meta_fbc');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('first_touch');
        });
    }
};
