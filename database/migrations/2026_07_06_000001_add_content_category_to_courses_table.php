<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Content category: 思維升級 / 財務覺醒 / 知識變現 (default)
            $table->enum('content_category', ['mindset', 'finance', 'monetization'])
                ->default('monetization')
                ->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('content_category');
        });
    }
};
