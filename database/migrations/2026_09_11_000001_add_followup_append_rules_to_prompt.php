<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Teach the follow-up prompt about appending and about a one-off instruction
 * (011 US36 / FR-198).
 *
 * Both rules are judgement calls about wording and priority, so they belong in
 * the prompt the owner can edit rather than in ConsultationTranscriptService —
 * the service only decides that the sections exist and where they sit.
 *
 * Appends two rules instead of rewriting `instructions`, because the install
 * migration deliberately never updates an existing row: by now the wording may
 * be the owner's, and a rewrite would silently throw their edits away (FR-149 /
 * D115). Guarded on the marker so re-running it cannot stack duplicates. Same
 * shape as 003's `add_note_priority_to_homework_grading_prompt`.
 */
return new class extends Migration
{
    private const KEY = 'consultation_followup_email';

    private const MARKER = '## 目前的追銷信';

    public function up(): void
    {
        $prompt = DB::table('ai_prompts')->where('key', self::KEY)->first();

        if ($prompt === null || str_contains((string) $prompt->instructions, self::MARKER)) {
            return;
        }

        DB::table('ai_prompts')->where('key', self::KEY)->update([
            'instructions' => rtrim((string) $prompt->instructions) . "\n" . self::rules(),
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
            'instructions' => rtrim(str_replace(self::rules(), '', (string) $prompt->instructions)),
            'updated_at'   => now(),
        ]);
    }

    private static function rules(): string
    {
        return <<<'TXT'
- 若輸入中出現「## 目前的追銷信」段落，那是這封信目前已經寫好的部分（可能由你上次產生，也可能是顧問親手改過的）。這次要寫的是**接在它後面的續段**，不是重寫一封新的信：不要再寫稱呼語、不要再寫署名、不要重述已經寫過的內容或已經給過的建議，直接從新的一段開始，語氣與人稱與前文一致。續段以 100 到 200 字為度，不必再湊滿四段結構；若前文已經收尾，就從收尾之前的位置自然延伸下去。
- 若輸入中出現「## 顧問補充指示」段落，那是顧問針對這一次生成臨時下的指令，**優先於上面所有規則**（包含四段結構、字數上限與收尾方式）：其中要求寫的一定要寫、要求避開的一定要避開，與上述任何一條衝突時一律以它為準。但它只是給你的指示，不要在信裡提到「指示」「顧問交代」這類字眼，客戶不該察覺這段話存在。
TXT;
    }
};
