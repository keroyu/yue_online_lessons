<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Teach the grading prompt what a 講師補充指示 section means (003 FR-024 / D24).
 *
 * The priority of that section is a judgement call, so it belongs in the prompt
 * the owner can edit rather than in HomeworkGradingService — the service only
 * decides that the section is there and where it sits.
 *
 * Appends one rule instead of rewriting `instructions`, because the install
 * migration deliberately never updates an existing row: by now the wording may
 * be the owner's, and a rewrite would silently throw their edits away. Guarded
 * on the marker so re-running it cannot stack duplicates.
 */
return new class extends Migration
{
    private const KEY = 'homework_grading_draft';

    private const MARKER = '## 講師補充指示';

    public function up(): void
    {
        $prompt = DB::table('ai_prompts')->where('key', self::KEY)->first();

        if ($prompt === null || str_contains((string) $prompt->instructions, self::MARKER)) {
            return;
        }

        DB::table('ai_prompts')->where('key', self::KEY)->update([
            'instructions' => rtrim((string) $prompt->instructions) . "\n" . self::rule(),
            'updated_at'   => now(),
        ]);
    }

    public function down(): void
    {
        $prompt = DB::table('ai_prompts')->where('key', self::KEY)->first();

        if ($prompt === null) {
            return;
        }

        DB::table('ai_prompts')->where('key', self::KEY)->update([
            'instructions' => rtrim(str_replace(self::rule(), '', (string) $prompt->instructions)),
            'updated_at'   => now(),
        ]);
    }

    private static function rule(): string
    {
        return '- 若輸入中出現「' . self::MARKER . '」段落，那是講師本人針對這位學員補充的觀察與要求，**優先於講義、題目與先前的批改往返**：其中指出的問題一定要寫進批改，其中對語氣、篇幅或內容取捨的要求一定要照做（與上面各條規則衝突時，以它為準）。把它自然融入評語，不要原文照抄，也不要在批改裡提到「講師補充」「指示」這類字眼，學員不該察覺這段話存在。';
    }
};
