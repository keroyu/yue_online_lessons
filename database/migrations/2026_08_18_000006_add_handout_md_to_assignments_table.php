<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The teaching notes an AI grading draft is written against (003 US10 / FR-016).
 *
 * Lives on the assignment rather than the lesson (D14): a lesson has at most one
 * question, so this is per-lesson either way, and keeping the three things a
 * draft needs — handout, question, submission — on one row is what lets the
 * whole feature be maintained on the grading page itself.
 *
 * Context for the model only. Nothing student-facing ever renders it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->text('handout_md')->nullable()->after('question_md');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('handout_md');
        });
    }
};
