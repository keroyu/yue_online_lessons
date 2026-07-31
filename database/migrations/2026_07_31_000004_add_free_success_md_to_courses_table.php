<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Shown in place of the default success card after a free claim
            // (drip subscribe / free enrollment). Null keeps the old card.
            $table->text('free_success_md')->nullable()->after('description_md');
            // Sales-page delayed promo block. promo_delay_seconds: null = off,
            // 0 = reveal immediately. Parallel to the same-named lesson columns
            // but unrelated — this one lives on the sales page.
            $table->text('promo_html')->nullable()->after('free_success_md');
            $table->unsignedInteger('promo_delay_seconds')->nullable()->after('promo_html');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['free_success_md', 'promo_html', 'promo_delay_seconds']);
        });
    }
};
