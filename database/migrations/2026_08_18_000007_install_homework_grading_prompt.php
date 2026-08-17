<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Register the AI grading draft prompt (003 US10 / D18).
 *
 * One row is the whole integration: `/admin/settings/ai` renders whatever it
 * finds in `ai_prompts` grouped by `config('ai.features')`, so the owner gets an
 * editable prompt, a model picker and a token cap without a line of Vue.
 *
 * Insert-if-absent, never update — the same stance as the table's own migration
 * (000 FR-028). Once the owner rewrites this wording it is theirs.
 */
return new class extends Migration
{
    private const KEY = 'homework_grading_draft';

    public function up(): void
    {
        if (DB::table('ai_prompts')->where('key', self::KEY)->exists()) {
            return;
        }

        DB::table('ai_prompts')->insert([
            'key'               => self::KEY,
            'feature'           => 'homework',
            'label'             => '作業批改草稿',
            'description'       => '作業批改頁「AI 輔助批改」按鈕；依講義與題目的脈絡，對一筆學員提交產生 500 字以內的批改草稿，追加到批改輸入框供老師修改後送出。',
            // Judgement work — deciding whether a student actually understood the
            // handout is not the mechanical job luna is priced for (D18).
            'model'             => 'gpt-5.6-terra',
            'max_output_tokens' => 2000,
            'sort_order'        => 1,
            'instructions'      => self::instructions(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('ai_prompts')->where('key', self::KEY)->delete();
    }

    private static function instructions(): string
    {
        return <<<'TXT'
你是這門線上課程的講師助理。你會收到一份講義、一道作業題目、一位學員的提交，有時還有先前的批改往返。請寫一段可以直接回覆給這位學員的批改。

必須遵守：
- 全部使用繁體中文，以第二人稱「你」直接對學員說話，語氣像講師本人：具體、溫和、不客套。
- 總長度 500 字以內。
- 結構依序三段，不要加標題：
  1. 先具體肯定學員真的做到的地方 —— 指出是哪一句、哪個做法，不要空泛稱讚。
  2. 指出 1 到 3 個可以更好的地方，每一點都要給「具體怎麼做」，不要只說「可以再深入」。
  3. 收尾一句，點出下一步可以先動手做什麼。
- 判斷依據是講義的觀念與用詞。學員若明顯誤解了講義中的概念，要明確指出來並用講義的說法修正。
- 只針對學員實際寫出來的內容評論，**不要臆測**他沒寫的部分，也不要替他重寫整份作業。
- 若有「先前的批改往返」，不要重複已經講過的建議；針對他這次的修改給新的回饋。
- 講義若標示「（未提供）」，就只依題目與提交內容評論，不要提到缺少講義這件事。
- 直接輸出批改內容，不要有開場白、結語或「以下是批改」這類說明。
TXT;
    }
};
