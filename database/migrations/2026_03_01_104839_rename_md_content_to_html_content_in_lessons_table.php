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
        // Only needed on DBs that ran the since-removed 2026_02_28 rename migration
        if (Schema::hasColumn('lessons', 'md_content')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->renameColumn('md_content', 'html_content');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('lessons', 'html_content')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->renameColumn('html_content', 'md_content');
            });
        }
    }
};
