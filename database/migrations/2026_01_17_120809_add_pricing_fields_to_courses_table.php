<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedInteger('original_price')->nullable()->after('price');
            $table->timestamp('promo_ends_at')->nullable()->after('sale_at');
            $table->index('promo_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['promo_ends_at']);
            $table->dropColumn(['original_price', 'promo_ends_at']);
        });
    }
};
