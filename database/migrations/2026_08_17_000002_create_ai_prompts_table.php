<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Site-wide AI prompt registry (000 US10).
 *
 * One row per call site. `key` / `feature` / `label` belong to the code — the
 * admin panel may only edit `instructions`, `model`, and `max_output_tokens`
 * (FR-027). A row with no matching call site is an orphan nothing will ever
 * read, which is why there is no "add prompt" button.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('feature', 50);
            $table->string('label', 100);
            $table->string('description', 255)->nullable();
            $table->text('instructions');

            // null = follow site_settings.openai_default_model. Per-prompt so a
            // mechanical step (proofreading) and a judgement step (summarising)
            // inside one feature can run on different models (D30).
            $table->string('model', 50)->nullable();
            $table->unsignedInteger('max_output_tokens')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('feature');
        });

        // Insert-if-absent, never update (FR-028). The owner edits these bodies
        // in the admin panel and that wording is theirs — updateOrCreate would
        // silently revert it on every deploy.
        foreach (self::prompts() as $prompt) {
            if (DB::table('ai_prompts')->where('key', $prompt['key'])->exists()) {
                continue;
            }

            DB::table('ai_prompts')->insert(array_merge($prompt, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompts');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function prompts(): array
    {
        return [
            [
                'key'               => 'consultation_transcript_proofread',
                'feature'           => 'consultation',
                'label'             => '逐字稿校訂',
                'description'       => '面談錄影的 VTT 逐字稿分段送出時使用；負責修錯字、去贅詞、統一講者稱謂。',
                'instructions'      => self::proofreadInstructions(),
                'model'             => 'gpt-5.6-luna',
                'max_output_tokens' => 8000,
                'sort_order'        => 1,
            ],
            [
                'key'               => 'consultation_summary',
                'feature'           => 'consultation',
                'label'             => '面談摘要',
                'description'       => '以校訂後的逐字稿為輸入，產生固定結構的客戶摘要。',
                'instructions'      => self::summaryInstructions(),
                'model'             => null,
                'max_output_tokens' => 2000,
                'sort_order'        => 2,
            ],
        ];
    }

    private static function proofreadInstructions(): string
    {
        return <<<'TXT'
你會收到一段語音辨識產生的面談對話逐字稿。請校訂它並原樣輸出。

必須遵守：
- 逐句保留原意與資訊量。這是逐字稿不是摘要，**嚴禁**摘要、省略、合併或增補任何內容。
- 只修正語音辨識造成的錯字、同音字、標點與斷句，以及移除「嗯」「啊」「那個」「就是」這類無意義贅詞。
- 講者標籤只能輸出「顧問」或「客戶」兩種。已經標好的不要改動；若某段的講者標籤不是這兩者，依語境判斷：提問、說明方案、報價、引導流程的是「顧問」，描述自身處境、提問價格與成效、表達顧慮的是「客戶」。
- 輸出格式維持逐行的 `顧問: 內容` 或 `客戶: 內容`，不要加標題、編號、時間或任何說明文字。
- 直接輸出校訂後的內容，不要有任何開場白或結語。

輸出長度應與輸入相當。若你發現自己正在縮短內容，那是錯的。
TXT;
    }

    private static function summaryInstructions(): string
    {
        return <<<'TXT'
你會收到一場銷售面談的完整逐字稿（講者已匿名為「顧問」與「客戶」）。請萃取成客戶摘要。

輸出格式必須是以下 Markdown 結構，標題原樣照抄、不增不減：

## 客戶背景
## 現況與痛點
## 目標與期待
## 預算與決策權
## 主要異議
## 下一步 / 承諾事項
## 成交機率

必須遵守：
- 全部使用繁體中文。
- 每一節以條列呈現，1 到 4 點，每點一行、盡量精簡。
- 逐字稿中沒有談到的節，寫「未提及」即可，**不要臆測或推論**。
- 不要複述開場寒暄、客套話與閒聊。
- 「成交機率」一節寫「高」「中」「低」其中之一，後面接一句話說明判斷依據。
- 直接輸出摘要，不要有任何開場白或結語。
TXT;
    }
};
