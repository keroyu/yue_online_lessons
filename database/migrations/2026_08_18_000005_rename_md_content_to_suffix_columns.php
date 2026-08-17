<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bring the last two Markdown columns onto the `*_md` convention (003 FR-015).
 *
 * `md_content` is what the 2026-02 `html_content` rename left behind; every
 * Markdown column added since (`body_md`, `description_md`, `free_success_md`)
 * uses the suffix. Renaming here rather than living with both spellings is what
 * keeps the next new column from having to pick a side — `assignments` was
 * about to hold `md_content` and `handout_md` side by side (D15).
 *
 * Pure rename: type, nullability and contents are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->renameColumn('md_content', 'content_md');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->renameColumn('md_content', 'question_md');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->renameColumn('content_md', 'md_content');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->renameColumn('question_md', 'md_content');
        });
    }
};
