<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Register the follow-up email prompt (011 US35 / FR-188).
 *
 * The definition lives here and nowhere else — the same shape as
 * `install_homework_grading_prompt`, and the reason the table's own migration
 * was not touched: production ran that one months ago and will never run it
 * again, so a row added there would reach a fresh install and nothing else.
 * A migration of its own reaches both.
 *
 * Insert-if-absent, never update (000 FR-028). Once the owner rewrites this
 * wording in `/admin/settings/ai` it is theirs.
 */
return new class extends Migration
{
    private const KEY = 'consultation_followup_email';

    public function up(): void
    {
        if (DB::table('ai_prompts')->where('key', self::KEY)->exists()) {
            return;
        }

        DB::table('ai_prompts')->insert([
            'key'               => self::KEY,
            'feature'           => 'consultation',
            'label'             => '追銷 Email',
            'description'       => '面談紀錄列的「產生追銷信」按鈕；以客戶暱稱、面談摘要與逐字稿為輸入，先判斷這位客戶真正卡住的購買障礙，再寫一封針對那個障礙的追蹤信。',
            // null = follow the site default. Writing a letter is judgement
            // work, but which model writes the best Chinese prose is a thing
            // the owner will want to try out — leaving it null puts that choice
            // on the settings page rather than in this file (D133).
            'model'             => null,
            'max_output_tokens' => 2000,
            'sort_order'        => 3,
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
你是這場銷售面談的顧問。你會收到三段材料：一行客戶暱稱（可能沒有）、一份面談摘要（可能沒有）、以及完整的面談逐字稿（講者已匿名為「顧問」與「客戶」）。

請先在心裡完成一件事，但**不要寫出來**：判斷這位客戶目前真正卡住的地方是什麼。常見的障礙有五種 ——
1. 價格：認同價值，但覺得金額超出現在拿得出來的範圍。
2. 時機：認同也負擔得起，但手上有別的事，覺得「現在不是時候」。
3. 信任：不確定這套方法在「他這種情況」也有效，或不確定你會不會真的帶他。
4. 決策權：自己不能拍板，要問另一半、合夥人或主管。
5. 需求不明：講不出具體想解決什麼，或想要的其實不是你在賣的東西。

判斷依據**只能來自逐字稿與摘要裡實際出現的話**，不要臆測。同時成立時取最強的那一個；真的看不出來就當作「需求不明」處理。

接著寫一封要寄給這位客戶本人的追蹤信，直接輸出信件內容本身。

必須遵守：
- 全部使用繁體中文，300 到 400 字。
- 開頭以客戶暱稱稱呼對方；沒有暱稱那一行時，一律以「您」稱呼，**不要編一個名字**。
- 結構依序四段，不要加小標題：
  1. 謝謝對方撥出時間，並用一到兩句回顧這次談到的處境 —— 要具體，引用對方自己說過的話或數字，不要寫成任何人都適用的範本。
  2. **正面接住你判斷出來的那個障礙**：先說你理解他為什麼會這樣想，不要否定它。
  3. 針對那個障礙給一件具體、對方這週就做得到的事（一個可以先動手的小步驟、一個可以先確認的資訊、一次可以先安排的討論）。若障礙是決策權，這一步應該是幫他把資訊整理成可以拿去跟對方談的樣子。
  4. 一句話收尾，把球留在對方手上，語氣輕。
- **不要報價、不要施壓、不要製造急迫感**，不要出現「限時」「名額」「最後機會」「錯過就沒有了」這類字眼。
- **不要把內部判斷寫進信裡**：成交機率、主要異議、預算級距這些是寫給顧問看的，絕不能出現。也不要寫出「我判斷你的障礙是價格」這種話 —— 障礙要用理解的語氣接住，不是標籤化地指出來。
- 以純文字書寫，不用 Markdown 標題、粗體或條列。
- 署名處寫「（顧問署名）」讓顧問自行替換。
- 若逐字稿內容太少、不足以寫出具體的信，直接輸出一行「逐字稿內容不足，無法產生追銷信」，不要編造。
TXT;
    }
};
