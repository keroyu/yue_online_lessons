<?php

namespace Tests\Feature\Platform;

use App\Models\AiPrompt;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\OpenAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AiSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function consultant(): User
    {
        return User::factory()->create(['role' => 'member', 'is_sales_consultant' => true]);
    }

    // ── 設定頁存取 ──

    public function test_admin_can_open_ai_settings_page(): void
    {
        $res = $this->actingAs($this->admin())->get('/admin/settings/ai');

        $res->assertOk();
    }

    public function test_sales_consultant_cannot_open_ai_settings_page(): void
    {
        $res = $this->actingAs($this->consultant())->get('/admin/settings/ai');

        $res->assertRedirect('/');
    }

    public function test_guest_cannot_open_ai_settings_page(): void
    {
        $this->get('/admin/settings/ai')->assertRedirect();
    }

    // ── 憑證 ──

    public function test_api_key_is_saved_and_blank_submission_keeps_the_old_value(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/settings/ai', [
            'openai_api_key'       => 'sk-first',
            'openai_default_model' => 'gpt-5.6-sol',
        ]);

        $this->assertSame('sk-first', SiteSetting::get(OpenAiService::API_KEY));
        $this->assertSame('gpt-5.6-sol', SiteSetting::get(OpenAiService::DEFAULT_MODEL_KEY));

        // Blank means "keep it" — same contract as every other secret field (D3).
        $this->actingAs($admin)->post('/admin/settings/ai', [
            'openai_api_key'       => '',
            'openai_default_model' => 'gpt-5.6-luna',
        ]);

        $this->assertSame('sk-first', SiteSetting::get(OpenAiService::API_KEY));
        $this->assertSame('gpt-5.6-luna', SiteSetting::get(OpenAiService::DEFAULT_MODEL_KEY));
    }

    public function test_api_key_is_never_sent_back_to_the_browser_in_full(): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-supersecretvalue');

        $res = $this->actingAs($this->admin())->get('/admin/settings/ai');

        $res->assertOk();
        $res->assertDontSee('sk-supersecretvalue');
    }

    // ── Prompt 編輯 ──

    public function test_prompt_instructions_model_and_token_cap_can_be_updated(): void
    {
        $prompt = AiPrompt::for('consultation_summary');
        $this->assertNotNull($prompt, 'migration 應已安裝 consultation_summary');

        $this->actingAs($this->admin())->post('/admin/settings/ai', [
            'prompts' => [
                [
                    'id'                => $prompt->id,
                    'instructions'      => '改寫後的指示',
                    'model'             => 'gpt-5.6-terra',
                    'max_output_tokens' => 1234,
                ],
            ],
        ]);

        $prompt->refresh();
        $this->assertSame('改寫後的指示', $prompt->instructions);
        $this->assertSame('gpt-5.6-terra', $prompt->model);
        $this->assertSame(1234, $prompt->max_output_tokens);
    }

    public function test_blank_model_means_follow_the_site_default(): void
    {
        $prompt = AiPrompt::for('consultation_transcript_proofread');

        $this->actingAs($this->admin())->post('/admin/settings/ai', [
            'prompts' => [
                ['id' => $prompt->id, 'instructions' => $prompt->instructions, 'model' => ''],
            ],
        ]);

        $prompt->refresh();
        $this->assertNull($prompt->model);

        SiteSetting::set(OpenAiService::DEFAULT_MODEL_KEY, 'gpt-5.6-sol');
        $this->assertSame('gpt-5.6-sol', $prompt->resolvedModel());
    }

    public function test_key_feature_and_label_are_ignored_on_update(): void
    {
        $prompt = AiPrompt::for('consultation_summary');

        $this->actingAs($this->admin())->post('/admin/settings/ai', [
            'prompts' => [
                [
                    'id'           => $prompt->id,
                    'instructions' => '仍然可以改',
                    'key'          => 'hijacked_key',
                    'feature'      => 'hijacked_feature',
                    'label'        => '被竄改的名稱',
                ],
            ],
        ]);

        $prompt->refresh();
        $this->assertSame('consultation_summary', $prompt->key);
        $this->assertSame('consultation', $prompt->feature);
        $this->assertSame('面談摘要', $prompt->label);
        $this->assertSame('仍然可以改', $prompt->instructions);
    }

    public function test_update_cannot_create_new_prompt_rows(): void
    {
        $before = AiPrompt::count();

        $this->actingAs($this->admin())->post('/admin/settings/ai', [
            'prompts' => [
                ['id' => 99999, 'instructions' => '不存在的列'],
            ],
        ]);

        $this->assertSame($before, AiPrompt::count());
    }

    public function test_sales_consultant_cannot_update_ai_settings(): void
    {
        $prompt = AiPrompt::for('consultation_summary');
        $original = $prompt->instructions;

        $this->actingAs($this->consultant())->post('/admin/settings/ai', [
            'prompts' => [
                ['id' => $prompt->id, 'instructions' => '顧問不該改得動'],
            ],
        ]);

        $prompt->refresh();
        $this->assertSame($original, $prompt->instructions);
    }

    // ── OpenAiService ──

    public function test_respond_returns_null_when_no_api_key_is_configured(): void
    {
        Http::fake();

        $this->assertNull(app(OpenAiService::class)->respond('consultation_summary', '任意輸入'));
        Http::assertNothingSent();
    }

    public function test_respond_returns_null_for_an_unknown_prompt_key(): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-test');
        Http::fake();

        $this->assertNull(app(OpenAiService::class)->respond('no_such_prompt', '任意輸入'));
        Http::assertNothingSent();
    }

    public function test_respond_sends_the_prompt_model_and_returns_output_text(): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-test');
        AiPrompt::for('consultation_summary')->update(['model' => 'gpt-5.6-terra']);

        Http::fake([
            'api.openai.com/*' => Http::response(['output_text' => '摘要內容'], 200),
        ]);

        $result = app(OpenAiService::class)->respond('consultation_summary', '逐字稿');

        $this->assertSame('摘要內容', $result);
        Http::assertSent(function ($request) {
            return $request['model'] === 'gpt-5.6-terra'
                && $request['input'] === '逐字稿'
                && $request->hasHeader('Authorization', 'Bearer sk-test');
        });
    }

    public function test_respond_falls_back_to_the_nested_output_path(): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-test');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'output' => [
                    ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => '巢狀取值']]],
                ],
            ], 200),
        ]);

        $this->assertSame('巢狀取值', app(OpenAiService::class)->respond('consultation_summary', '逐字稿'));
    }

    /**
     * The shape that actually shipped and broke: a reasoning model answers with
     * an empty `reasoning` item first and the message second, so reading
     * `output.0` finds nothing and the caller concludes the model said nothing.
     * The transcripts came back unproofread and the summaries empty, with a
     * completed request and billed output tokens sitting in the response.
     */
    public function test_respond_skips_the_reasoning_item_and_reads_the_message(): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-test');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'status' => 'completed',
                'output' => [
                    ['type' => 'reasoning', 'summary' => [], 'content' => []],
                    ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => '## 客戶背景']]],
                ],
            ], 200),
        ]);

        $this->assertSame('## 客戶背景', app(OpenAiService::class)->respond('consultation_summary', '逐字稿'));
    }

    public function test_respond_joins_multiple_message_parts(): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-test');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'output' => [
                    ['type' => 'reasoning', 'content' => []],
                    ['type' => 'message', 'content' => [
                        ['type' => 'output_text', 'text' => '第一段'],
                        ['type' => 'output_text', 'text' => '第二段'],
                    ]],
                ],
            ], 200),
        ]);

        $this->assertSame("第一段\n第二段", app(OpenAiService::class)->respond('consultation_summary', '逐字稿'));
    }

    /**
     * A 200 we cannot read is a bug, not a configuration choice — it must not be
     * as quiet as the "no API key" null.
     */
    public function test_an_unreadable_success_is_logged(): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-test');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'status' => 'completed',
                'output' => [['type' => 'reasoning', 'content' => []]],
            ], 200),
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $message) => str_contains($message, 'no text could be extracted'));

        $this->assertNull(app(OpenAiService::class)->respond('consultation_summary', '逐字稿'));
    }

    public function test_respond_throws_with_the_response_body_on_failure(): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-test');

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'model_not_found']], 404),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/model_not_found/');

        app(OpenAiService::class)->respond('consultation_summary', '逐字稿');
    }

    // ── 兩個 prompt 可各自用不同模型（D30）──

    public function test_two_prompts_can_run_on_different_models(): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-test');
        AiPrompt::for('consultation_transcript_proofread')->update(['model' => 'gpt-5.6-luna']);
        AiPrompt::for('consultation_summary')->update(['model' => 'gpt-5.6-terra']);

        Http::fake(['api.openai.com/*' => Http::response(['output_text' => 'ok'], 200)]);

        $service = app(OpenAiService::class);
        $service->respond('consultation_transcript_proofread', 'a');
        $service->respond('consultation_summary', 'b');

        $models = [];
        Http::assertSent(function ($request) use (&$models) {
            $models[] = $request['model'];

            return true;
        });

        $this->assertSame(['gpt-5.6-luna', 'gpt-5.6-terra'], $models);
    }
}
