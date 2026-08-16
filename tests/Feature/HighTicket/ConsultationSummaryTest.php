<?php

namespace Tests\Feature\HighTicket;

use App\Models\AiPrompt;
use App\Models\ConsultationNote;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\OpenAiService;
use App\Services\ZoomWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 011 US23 — Zoom transcript intake, proofreading and summarising.
 *
 * The invariants worth defending here are the quiet ones: the raw speaker names
 * must never reach the database (FR-109), a model that starts summarising a
 * transcript must be caught (FR-108), and a human edit must survive a webhook
 * retry (FR-110).
 */
class ConsultationSummaryTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'zoom-secret-token';
    private const MEETING_ID = '81234567890';

    protected function setUp(): void
    {
        parent::setUp();
        SiteSetting::set(ZoomWebhookService::SECRET_KEY, self::SECRET);
    }

    private function vtt(string $consultantName = '王顧問', string $customerName = '陳小明'): string
    {
        return <<<VTT
        WEBVTT

        1
        00:00:01.000 --> 00:00:04.000
        {$consultantName}: 今天想先了解你目前的狀況

        2
        00:00:04.500 --> 00:00:09.000
        {$customerName}: 我的收入很不穩定 嗯 大概就是這樣

        3
        00:00:09.500 --> 00:00:14.000
        {$consultantName}: 了解 那預算大概抓多少
        VTT;
    }

    private function note(array $overrides = []): ConsultationNote
    {
        return ConsultationNote::create(array_merge([
            'email'           => 'booker@example.com',
            'source'          => ConsultationNote::SOURCE_HIGH_TICKET,
            'met_at'          => now()->subHour(),
            'zoom_meeting_id' => self::MEETING_ID,
        ], $overrides));
    }

    private function payload(string $event = 'recording.transcript_completed'): array
    {
        return [
            'event'   => $event,
            'payload' => [
                'object' => [
                    'id'              => (int) self::MEETING_ID,
                    'recording_files' => [
                        ['file_type' => 'MP4', 'download_url' => 'https://zoom.us/rec/video.mp4'],
                        [
                            'file_type'      => 'TRANSCRIPT',
                            'file_extension' => 'VTT',
                            'download_url'   => 'https://zoom.us/rec/transcript.vtt',
                        ],
                    ],
                ],
            ],
            'download_token' => 'dl-token',
        ];
    }

    private function sendWebhook(array $body, ?string $signature = null, ?int $timestamp = null): \Illuminate\Testing\TestResponse
    {
        $raw = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $ts = $timestamp ?? time();
        $sig = $signature ?? 'v0=' . hash_hmac('sha256', "v0:{$ts}:{$raw}", self::SECRET);

        return $this->call(
            'POST',
            '/api/webhooks/zoom',
            [],
            [],
            [],
            [
                'CONTENT_TYPE'              => 'application/json',
                'HTTP_X_ZM_SIGNATURE'         => $sig,
                'HTTP_X_ZM_REQUEST_TIMESTAMP' => (string) $ts,
            ],
            $raw
        );
    }

    private function fakeZoomAndOpenAi(?string $proofread = null, ?string $summary = null): void
    {
        $proofread ??= "顧問: 今天想先了解你目前的狀況\n客戶: 我的收入很不穩定，大概就是這樣\n顧問: 了解，那預算大概抓多少";
        $summary ??= "## 客戶背景\n- 未提及\n## 成交機率\n- 中：預算尚未確認";

        Http::fake([
            'zoom.us/rec/*'    => Http::response($this->vtt(), 200),
            'api.openai.com/*' => Http::sequence()
                ->push(['output_text' => $proofread])
                ->push(['output_text' => $summary])
                ->whenEmpty(Http::response(['output_text' => $summary], 200)),
        ]);
    }

    private function enableOpenAi(): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-test');
    }

    // ── URL 驗證與驗簽 ──

    public function test_url_validation_challenge_is_answered(): void
    {
        $res = $this->postJson('/api/webhooks/zoom', [
            'event'   => 'endpoint.url_validation',
            'payload' => ['plainToken' => 'abc123'],
        ]);

        $res->assertOk();
        $res->assertJson([
            'plainToken'     => 'abc123',
            'encryptedToken' => hash_hmac('sha256', 'abc123', self::SECRET),
        ]);
    }

    public function test_a_wrong_signature_is_rejected(): void
    {
        $this->note();

        $this->sendWebhook($this->payload(), signature: 'v0=deadbeef')->assertStatus(401);

        $this->assertNull(ConsultationNote::first()->transcript);
    }

    public function test_a_stale_timestamp_is_rejected(): void
    {
        $this->note();

        $this->sendWebhook($this->payload(), timestamp: time() - 600)->assertStatus(401);
    }

    public function test_an_unrelated_event_is_acknowledged_and_ignored(): void
    {
        $this->note();
        Http::fake();

        $this->sendWebhook($this->payload('meeting.started'))->assertOk();

        Http::assertNothingSent();
        $this->assertNull(ConsultationNote::first()->transcript);
    }

    public function test_an_unknown_meeting_id_is_acknowledged_without_creating_anything(): void
    {
        Http::fake();

        $body = $this->payload();
        $body['payload']['object']['id'] = 99999999;

        $this->sendWebhook($body)->assertOk();

        $this->assertSame(0, ConsultationNote::count());
        Http::assertNothingSent();
    }

    // ── 主流程 ──

    public function test_a_valid_webhook_stores_the_transcript_and_summary(): void
    {
        $note = $this->note();
        $this->enableOpenAi();
        $this->fakeZoomAndOpenAi();

        $this->sendWebhook($this->payload())->assertOk();

        $note->refresh();
        $this->assertStringContainsString('顧問:', $note->transcript);
        $this->assertStringContainsString('客戶:', $note->transcript);
        $this->assertNotNull($note->transcript_fetched_at);
        $this->assertStringContainsString('## 客戶背景', $note->summary);
        $this->assertNotNull($note->summary_generated_at);
        $this->assertNull($note->transcript_edited_at);
        $this->assertNull($note->summary_edited_at);
    }

    public function test_real_names_never_reach_the_database(): void
    {
        $consultant = User::factory()->create(['nickname' => '王顧問', 'email' => 'consultant@example.com']);
        User::factory()->create(['nickname' => '陳小明', 'email' => 'booker@example.com']);
        $note = $this->note(['consultant_id' => $consultant->id]);
        $this->enableOpenAi();

        // The model is told to hand back exactly what it was given, so anything
        // that survives here came out of the mechanical pass.
        Http::fake([
            'zoom.us/rec/*'    => Http::response($this->vtt(), 200),
            'api.openai.com/*' => function ($request) {
                return Http::response(['output_text' => $request['input']], 200);
            },
        ]);

        $this->sendWebhook($this->payload())->assertOk();

        $note->refresh();
        $this->assertStringNotContainsString('王顧問', $note->transcript);
        $this->assertStringNotContainsString('陳小明', $note->transcript);
    }

    public function test_vtt_headers_cues_and_timestamps_are_stripped(): void
    {
        $note = $this->note();
        $this->enableOpenAi();
        Http::fake([
            'zoom.us/rec/*'    => Http::response($this->vtt(), 200),
            'api.openai.com/*' => fn ($request) => Http::response(['output_text' => $request['input']], 200),
        ]);

        $this->sendWebhook($this->payload())->assertOk();

        $transcript = $note->refresh()->transcript;
        $this->assertStringNotContainsString('WEBVTT', $transcript);
        $this->assertStringNotContainsString('-->', $transcript);
        $this->assertStringNotContainsString('00:00:01', $transcript);
    }

    // ── 防縮水（FR-108）──

    public function test_a_shrunken_proofread_result_is_discarded(): void
    {
        $note = $this->note();
        $this->enableOpenAi();

        // Well under 60% of the input — the model started summarising.
        $this->fakeZoomAndOpenAi(proofread: '顧問: 聊了一下', summary: '## 客戶背景
- 未提及');

        $this->sendWebhook($this->payload())->assertOk();

        $note->refresh();
        $this->assertStringNotContainsString('聊了一下', $note->transcript);
        $this->assertStringContainsString('收入很不穩定', $note->transcript);
    }

    // ── 覆寫守門（FR-110）──

    public function test_an_edited_summary_is_not_overwritten(): void
    {
        $note = $this->note(['summary' => '顧問手寫的摘要', 'summary_edited_at' => now()]);
        $this->enableOpenAi();
        $this->fakeZoomAndOpenAi();

        $this->sendWebhook($this->payload())->assertOk();

        $note->refresh();
        $this->assertSame('顧問手寫的摘要', $note->summary);
        $this->assertNotNull($note->transcript, '逐字稿仍應更新');
    }

    public function test_an_edited_transcript_is_not_overwritten_and_costs_no_tokens(): void
    {
        $note = $this->note([
            'transcript'           => '顧問: 人工修訂過的版本',
            'transcript_edited_at' => now(),
            'summary_edited_at'    => now(),
        ]);
        $this->enableOpenAi();
        $this->fakeZoomAndOpenAi();

        $this->sendWebhook($this->payload())->assertOk();

        $note->refresh();
        $this->assertSame('顧問: 人工修訂過的版本', $note->transcript);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.openai.com'));
    }

    // ── 未設定憑證（FR-112）──

    public function test_without_an_openai_key_the_mechanical_transcript_is_still_stored(): void
    {
        $note = $this->note();
        Http::fake(['zoom.us/rec/*' => Http::response($this->vtt(), 200)]);

        $this->sendWebhook($this->payload())->assertOk();

        $note->refresh();
        $this->assertStringContainsString('顧問:', $note->transcript);
        $this->assertNull($note->summary);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.openai.com'));
    }

    // ── 後台編輯與重跑 ──

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_edit_the_summary_which_locks_it(): void
    {
        $note = $this->note();

        $this->actingAs($this->admin())
            ->patchJson("/admin/consultation-notes/{$note->id}/summary", ['summary' => '人工摘要'])
            ->assertOk();

        $note->refresh();
        $this->assertSame('人工摘要', $note->summary);
        $this->assertNotNull($note->summary_edited_at);
    }

    public function test_admin_can_edit_the_transcript_which_locks_it(): void
    {
        $note = $this->note(['transcript' => '顧問: 原本的']);

        $this->actingAs($this->admin())
            ->patchJson("/admin/consultation-notes/{$note->id}/transcript", ['transcript' => '顧問: 修過的'])
            ->assertOk();

        $note->refresh();
        $this->assertSame('顧問: 修過的', $note->transcript);
        $this->assertNotNull($note->transcript_edited_at);
    }

    public function test_regenerating_the_summary_uses_the_stored_transcript_and_never_calls_zoom(): void
    {
        $note = $this->note([
            'transcript'         => "顧問: 預算大概多少\n客戶: 大概三十萬",
            'summary'            => '舊摘要',
            'summary_edited_at'  => now(),
        ]);
        $this->enableOpenAi();
        Http::fake(['api.openai.com/*' => Http::response(['output_text' => '## 客戶背景
- 新摘要'], 200)]);

        $this->actingAs($this->admin())
            ->postJson("/admin/consultation-notes/{$note->id}/regenerate-summary")
            ->assertOk();

        $note->refresh();
        $this->assertStringContainsString('新摘要', $note->summary);
        $this->assertNull($note->summary_edited_at, '重新產生後應解鎖');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'zoom.us'));
    }

    public function test_regenerating_without_a_transcript_returns_a_friendly_422(): void
    {
        $note = $this->note();
        $this->enableOpenAi();
        Http::fake();

        $this->actingAs($this->admin())
            ->postJson("/admin/consultation-notes/{$note->id}/regenerate-summary")
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_the_proofread_and_summary_prompts_are_both_installed(): void
    {
        $this->assertNotNull(AiPrompt::for('consultation_transcript_proofread'));
        $this->assertNotNull(AiPrompt::for('consultation_summary'));
    }
}
