<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('courses', 'payment_gateway')) {
            return;
        }
        Schema::table('courses', function (Blueprint $table) {
            $table->string('payment_gateway', 20)->notNull()->default('payuni')->after('portaly_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('payment_gateway');
        });
    }
};
