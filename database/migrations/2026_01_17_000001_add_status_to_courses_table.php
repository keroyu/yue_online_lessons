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
            $table->enum('status', ['draft', 'preorder', 'selling'])->default('draft')->after('is_published');
            $table->timestamp('sale_at')->nullable()->after('status');
            $table->longText('description_html')->nullable()->after('description');
            $table->unsignedInteger('duration_minutes')->nullable()->after('portaly_product_id');
            $table->softDeletes();

            $table->index('status');
            $table->index('sale_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['sale_at']);
            $table->dropSoftDeletes();
            $table->dropColumn(['status', 'sale_at', 'description_html', 'duration_minutes']);
        });
    }
};
