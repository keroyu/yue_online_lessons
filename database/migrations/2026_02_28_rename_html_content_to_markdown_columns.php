<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->renameColumn('description_html', 'description_md');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->renameColumn('html_content', 'md_content');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->renameColumn('description_md', 'description_html');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->renameColumn('md_content', 'html_content');
        });
    }
};
