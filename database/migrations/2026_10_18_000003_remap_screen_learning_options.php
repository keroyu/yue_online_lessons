<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 011 FR-258 / D173 — Q7 was reworded from a habit question to "the furthest
 * step taken on the current problem". The three retired keys are folded onto
 * the new ones so the old keys can leave the rubric entirely.
 */
return new class extends Migration
{
    private const MAP = [
        'free_only'   => 'passive',
        'self_only'   => 'passive',
        'free_active' => 'searched',
    ];

    public function up(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table('high_ticket_leads')
                ->where('screen_learning', $old)
                ->update(['screen_learning' => $new]);
        }
    }

    /** Not reversible: `passive` cannot be split back into its two sources. */
    public function down(): void
    {
    }
};
