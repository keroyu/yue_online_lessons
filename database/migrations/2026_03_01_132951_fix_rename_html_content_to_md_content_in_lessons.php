<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Fix: the 005-drip-email branch's migration (2026_03_01_104839) renamed
    // md_content back to html_content. The merged codebase uses md_content,
    // so we restore the correct column name.

    public function up(): void
    {
        if (Schema::hasColumn('lessons', 'html_content')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->renameColumn('html_content', 'md_content');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lessons', 'md_content')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->renameColumn('md_content', 'html_content');
            });
        }
    }
};
