<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lower the 「不確定」 budget ceiling from 7 to 6 on the leads already scored
 * (011 FR-172, revised 2026-09-05).
 *
 * One condition catches both groups that need it: the rows that were 8 or 9
 * before any cap existed, and the rows the previous backfill wrote as 7. Where
 * a score came from is not recorded anywhere, so `> 6` is both the simplest
 * rule and the only one that does not need to know.
 *
 * `down()` does not restore the old totals — same reason as the first cap
 * migration: the uncapped score is recoverable from the five stored answers at
 * any time, and writing one back would undo the rule rather than the migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('high_ticket_leads')
            ->where('screen_budget', 'unsure')
            ->where('screening_score', '>', 6)
            ->update(['screening_score' => 6]);
    }

    public function down(): void
    {
        // Intentionally irreversible — see the class comment.
    }
};
