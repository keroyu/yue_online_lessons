<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 003 US12 / FR-034 — draft vs submitted homework.
 *
 * NULL submitted_at is the draft state, so every row that already exists must be
 * backfilled: leaving them NULL would drop every past submission out of the
 * instructor's grading list the moment this ships. created_at (not now()) keeps
 * the historical order intact once the list sorts by submitted_at (FR-042).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('is_edited');
            $table->index(['assignment_id', 'user_id', 'submitted_at']);
        });

        DB::table('comments')->update(['submitted_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['assignment_id', 'user_id', 'submitted_at']);
            $table->dropColumn('submitted_at');
        });
    }
};
