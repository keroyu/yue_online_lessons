<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How many bundle credits this tier grants on a sale (011 US37 / FR-202).
     *
     * Default 0 so every existing plan behaves exactly as it did before.
     */
    public function up(): void
    {
        Schema::table('course_plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('bundle_quantity')->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('course_plans', function (Blueprint $table) {
            $table->dropColumn('bundle_quantity');
        });
    }
};
