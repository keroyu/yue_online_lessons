<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // null 或 0 = 不可兌換、僅能購買；> 0 = 可用該積分數兌換。不綁定 type。
            $table->unsignedInteger('redeem_points')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('redeem_points');
        });
    }
};
