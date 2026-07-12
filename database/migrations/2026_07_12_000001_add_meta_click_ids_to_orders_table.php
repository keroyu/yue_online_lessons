<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Browser cookie snapshot taken at checkout initiate; the CAPI Purchase
            // event fires at webhook time when no browser cookies are readable.
            $table->string('meta_fbp', 100)->nullable()->after('ttclid');
            $table->string('meta_fbc', 255)->nullable()->after('meta_fbp');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['meta_fbp', 'meta_fbc']);
        });
    }
};
