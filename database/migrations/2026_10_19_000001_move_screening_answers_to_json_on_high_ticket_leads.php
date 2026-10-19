<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Screening answers move from seven fixed columns into one JSON map
     * (011 US46 / FR-266): questions are now edited on 1v1 漏斗設定 and can be
     * added, which a column per question cannot hold. Keys stay what they were
     * (`screen_timeline` …), so the stored map reads exactly like the old row.
     */
    private const COLUMNS = [
        'screen_timeline',
        'screen_budget',
        'screen_authority',
        'screen_pain',
        'screen_next_step',
        'screen_experience',
        'screen_learning',
    ];

    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->json('screen_answers')->nullable()->after('social_url');
        });

        DB::table('high_ticket_leads')
            ->select(array_merge(['id'], self::COLUMNS))
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $answers = array_filter(
                        array_intersect_key((array) $row, array_flip(self::COLUMNS)),
                        fn ($value) => $value !== null && $value !== '',
                    );

                    if ($answers !== []) {
                        DB::table('high_ticket_leads')->where('id', $row->id)->update([
                            'screen_answers' => json_encode($answers, JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                }
            });

        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNS);
        });
    }

    public function down(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            foreach (array_reverse(self::COLUMNS) as $column) {
                $table->string($column, 20)->nullable()->after('social_url');
            }
        });

        DB::table('high_ticket_leads')
            ->whereNotNull('screen_answers')
            ->select(['id', 'screen_answers'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    // Answers to questions added after this migration have no
                    // column to go back to; they are dropped with the JSON.
                    $answers = array_intersect_key(
                        json_decode($row->screen_answers, true) ?: [],
                        array_flip(self::COLUMNS),
                    );

                    if ($answers !== []) {
                        DB::table('high_ticket_leads')->where('id', $row->id)->update($answers);
                    }
                }
            });

        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropColumn('screen_answers');
        });
    }
};
