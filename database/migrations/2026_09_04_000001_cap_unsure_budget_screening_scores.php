<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Apply the 「不確定」 budget ceiling to the leads already scored (011 FR-172).
 *
 * Without this the rule only holds for applications submitted from now on, and
 * the leads list would show two people with identical answers wearing different
 * badges — with no way to tell from the row which side of the deploy they are
 * on. The ceiling is 7, and the highest reachable total alongside 「不確定」 is
 * 9, so this touches only the 8s and 9s.
 *
 * `down()` does not restore the old totals: the original score is recoverable
 * from the five stored answers at any time, and writing back an uncapped number
 * would undo the rule rather than the migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('high_ticket_leads')
            ->where('screen_budget', 'unsure')
            ->where('screening_score', '>', 7)
            ->update(['screening_score' => 7]);
    }

    public function down(): void
    {
        // Intentionally irreversible — see the class comment.
    }
};
