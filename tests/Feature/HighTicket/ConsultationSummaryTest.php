<?php

namespace Tests\Feature\HighTicket;

use App\Jobs\ProcessZoomTranscriptJob;
use App\Models\AiPrompt;
use App\Models\ConsultationNote;
use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\OpenAiService;
use App\Services\ZoomMeetingService;
use App\Services\ZoomWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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

    /** A lead the admin list will render, with one recorded session behind it. */
    private function leadWithNote(string $transcript): HighTicketLead
    {
        $course = Course::create([
            'name'                   => 'HT Course',
            'slug'                   => 'ht-' . uniqid(),
            'tagline'                => 'tag',
            'description'            => 'desc',
            'price'                  => 50000,
            'instructor_name'        => 'Tester',
            'type'                   => 'high_ticket',
            'status'                 => 'selling',
            'course_type'            => 'standard',
            'is_published'           => true,
            'is_visible'             => true,
            'payment_gateway'        => 'payuni',
            'high_ticket_hide_price' => true,
        ]);

        $this->note(['transcript' => $transcript, 'course_id' => $course->id]);

        return HighTicketLead::create([
            'name'      => 'Booker',
            'email'     => 'booker@example.com',
            'course_id' => $course->id,
            'booked_at' => now(),
        ]);
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

    /**
     * Zoom sends two recording events and we subscribe to both (D88), so the
     * second job for a session is routine — it must not re-buy the same tokens.
     */
    public function test_an_already_fetched_transcript_is_not_reprocessed(): void
    {
        $note = $this->note([
            'transcript'            => '顧問: 已經取回並校訂過的版本',
            'transcript_fetched_at' => now(),
            'summary_edited_at'     => now(),
        ]);
        $this->enableOpenAi();
        $this->fakeZoomAndOpenAi();

        $this->sendWebhook($this->payload())->assertOk();

        $note->refresh();
        $this->assertSame('顧問: 已經取回並校訂過的版本', $note->transcript);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.openai.com'));
    }

    /**
     * `recording.completed` arrives with the video and audio only — the VTT is a
     * later, separate event. Retrying this payload would re-read the same file
     * list, so it must end the job quietly rather than fail it (D88, revised).
     */
    public function test_a_payload_without_a_transcript_file_is_not_treated_as_a_failure(): void
    {
        $note = $this->note();
        $this->enableOpenAi();
        Http::fake();

        // Straight to the job: the webhook controller swallows exceptions by
        // design, which would hide the very thing under test.
        ProcessZoomTranscriptJob::dispatchSync($note->id, [
            'object' => [
                'id'              => (int) self::MEETING_ID,
                'recording_files' => [
                    ['file_type' => 'MP4', 'file_extension' => 'MP4', 'download_url' => 'https://zoom.us/rec/video.mp4'],
                    ['file_type' => 'M4A', 'file_extension' => 'M4A', 'download_url' => 'https://zoom.us/rec/audio.m4a'],
                ],
            ],
            'download_token' => 'dl-token',
        ]);

        $note->refresh();
        $this->assertNull($note->transcript);
        $this->assertNull($note->transcript_fetched_at);
        $this->assertNull($note->summary, '沒有逐字稿就不該產生摘要');
        Http::assertNothingSent();
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

    // ── 手動抓取逐字稿（US25 / FR-133）──

    private function enableZoom(): void
    {
        SiteSetting::set(ZoomMeetingService::ACCOUNT_ID_KEY, 'acc-1');
        SiteSetting::set(ZoomMeetingService::CLIENT_ID_KEY, 'client-1');
        SiteSetting::set(ZoomMeetingService::CLIENT_SECRET_KEY, 'secret-1');
    }

    /** Zoom's `GET /meetings/{id}/recordings` reply, with or without the VTT. */
    private function fakeRecordings(bool $withTranscript, int $status = 200): void
    {
        $files = [
            ['file_type' => 'MP4', 'file_extension' => 'MP4', 'download_url' => 'https://zoom.us/rec/video.mp4'],
        ];

        if ($withTranscript) {
            $files[] = [
                'file_type'      => 'TRANSCRIPT',
                'file_extension' => 'VTT',
                'download_url'   => 'https://zoom.us/rec/transcript.vtt',
            ];
        }

        Http::fake([
            'zoom.us/oauth/token'    => Http::response(['access_token' => 'tok-1', 'expires_in' => 3600]),
            'api.zoom.us/v2/meetings/*/recordings' => $status === 200
                ? Http::response(['id' => self::MEETING_ID, 'recording_files' => $files], 200)
                : Http::response(['code' => 3301, 'message' => '此錄製不存在。'], $status),
        ]);
    }

    private function fetchTranscript(ConsultationNote $note): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin())
            ->postJson("/admin/consultation-notes/{$note->id}/fetch-transcript");
    }

    public function test_manual_fetch_queues_the_job_when_zoom_has_the_transcript(): void
    {
        Queue::fake();
        $note = $this->note();
        $this->enableZoom();
        $this->fakeRecordings(withTranscript: true);

        $this->fetchTranscript($note)->assertStatus(202)->assertJson(['queued' => true]);

        Queue::assertPushed(
            ProcessZoomTranscriptJob::class,
            fn (ProcessZoomTranscriptJob $job) => $job->noteId === $note->id
        );
    }

    /**
     * The common case this button exists for: Zoom is still transcribing. That
     * is an answer, not a failure — and it must not cost a queued job (FR-132).
     */
    public function test_manual_fetch_reports_a_transcript_that_is_not_ready_yet(): void
    {
        Queue::fake();
        $note = $this->note();
        $this->enableZoom();
        $this->fakeRecordings(withTranscript: false);

        $this->fetchTranscript($note)->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_manual_fetch_reports_a_recording_zoom_no_longer_has(): void
    {
        Queue::fake();
        $note = $this->note();
        $this->enableZoom();
        $this->fakeRecordings(withTranscript: true, status: 404);

        $this->fetchTranscript($note)->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_manual_fetch_without_zoom_credentials_never_calls_zoom(): void
    {
        Queue::fake();
        Http::fake();
        $note = $this->note();

        $this->fetchTranscript($note)->assertStatus(422);

        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_manual_fetch_needs_a_meeting_id(): void
    {
        Queue::fake();
        Http::fake();
        $note = $this->note(['zoom_meeting_id' => null]);
        $this->enableZoom();

        $this->fetchTranscript($note)->assertStatus(422);

        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_a_guest_cannot_trigger_a_manual_fetch(): void
    {
        Queue::fake();
        Http::fake();
        $note = $this->note();

        $this->postJson("/admin/consultation-notes/{$note->id}/fetch-transcript")->assertStatus(401);

        Queue::assertNothingPushed();
    }

    // ── 逐字稿下載（FR-120）──

    public function test_the_transcript_downloads_as_a_text_file(): void
    {
        $note = $this->note(['transcript' => "顧問: 預算大概多少\n客戶: 大概三十萬"]);

        $res = $this->actingAs($this->admin())
            ->get("/admin/consultation-notes/{$note->id}/transcript.txt");

        $res->assertOk();
        $res->assertHeader('content-type', 'text/plain; charset=UTF-8');
        $this->assertStringContainsString("attachment; filename=\"consultation-", $res->headers->get('content-disposition'));

        $body = $res->getContent();
        $this->assertStringContainsString('大概三十萬', $body);
        $this->assertStringContainsString('booker@example.com', $body);
        // The BOM is what keeps Windows Notepad from mangling the Chinese.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
    }

    public function test_downloading_a_missing_transcript_is_a_404(): void
    {
        $note = $this->note();

        $this->actingAs($this->admin())
            ->get("/admin/consultation-notes/{$note->id}/transcript.txt")
            ->assertNotFound();
    }

    public function test_a_guest_cannot_download_a_transcript(): void
    {
        $note = $this->note(['transcript' => '顧問: 機密內容']);

        $this->get("/admin/consultation-notes/{$note->id}/transcript.txt")
            ->assertRedirect();
    }

    /**
     * The body is heavy enough that shipping it with the leads list was the
     * reason for the download in the first place (FR-120).
     */
    public function test_the_leads_payload_carries_a_size_but_not_the_transcript(): void
    {
        $transcript = str_repeat('顧問: 這是一段逐字稿內容。', 50);
        $lead = $this->leadWithNote($transcript);

        $res = $this->actingAs($this->admin())->get('/admin/high-ticket-leads');
        $res->assertOk();

        $note = $res->viewData('page')['props']['leads']['data'][0]['consultation_notes'][0];

        $this->assertArrayNotHasKey('transcript', $note);
        $this->assertGreaterThan(0, $note['transcript_bytes']);
        $this->assertStringNotContainsString('這是一段逐字稿內容', json_encode($res->viewData('page'), JSON_UNESCAPED_UNICODE));
        unset($lead);
    }

    // ── 刪除場次（FR-118）──

    public function test_admin_can_delete_an_empty_session(): void
    {
        $note = $this->note();

        $this->actingAs($this->admin())
            ->deleteJson("/admin/consultation-notes/{$note->id}")
            ->assertOk();

        $this->assertDatabaseMissing('consultation_notes', ['id' => $note->id]);
    }

    /**
     * A short accidental meeting still produces a recording, so "only empty
     * ones may be deleted" would block the case this exists for.
     */
    public function test_admin_can_delete_a_session_that_already_has_content(): void
    {
        $note = $this->note([
            'transcript' => '顧問: 走錯房間了',
            'summary'    => '## 客戶背景\n未提及',
        ]);

        $this->actingAs($this->admin())
            ->deleteJson("/admin/consultation-notes/{$note->id}")
            ->assertOk();

        $this->assertDatabaseMissing('consultation_notes', ['id' => $note->id]);
    }

    public function test_a_guest_cannot_delete_a_session(): void
    {
        $note = $this->note();

        $this->deleteJson("/admin/consultation-notes/{$note->id}")->assertStatus(401);

        $this->assertDatabaseHas('consultation_notes', ['id' => $note->id]);
    }

    /**
     * Deletion has to stick. Zoom resends `recording.completed` for days, and a
     * record that quietly came back would make the button useless.
     */
    public function test_a_deleted_session_is_not_recreated_by_a_later_webhook(): void
    {
        $note = $this->note();
        $this->enableOpenAi();
        $this->fakeZoomAndOpenAi();

        $this->actingAs($this->admin())
            ->deleteJson("/admin/consultation-notes/{$note->id}")
            ->assertOk();

        $this->sendWebhook($this->payload())->assertOk();

        $this->assertSame(0, ConsultationNote::count());
    }
}
