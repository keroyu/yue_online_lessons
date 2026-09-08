<?php

namespace Tests\Feature\HighTicket;

use App\Jobs\ProcessZoomTranscriptJob;
use App\Models\ConsultationNote;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ConsultationTranscriptService;
use App\Services\OpenAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * 011 US34 — replacing a wrong transcript by hand.
 *
 * The invariant this defends is FR-183: what the admin uploads is raw material,
 * not the finished article. It has to come out the other end of the same
 * anonymising pipeline as the Zoom path, or consultation_notes ends up holding
 * two grades of data — one anonymised, one not — while its access controls were
 * designed for the first.
 */
class TranscriptUploadTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function note(array $overrides = []): ConsultationNote
    {
        return ConsultationNote::create(array_merge([
            'email'           => 'booker@example.com',
            'source'          => ConsultationNote::SOURCE_HIGH_TICKET,
            'met_at'          => now()->subHour(),
            'zoom_meeting_id' => '81234567890',
        ], $overrides));
    }

    private function upload(string $name, string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    private function srt(): string
    {
        return <<<SRT
        1
        00:00:01,000 --> 00:00:04,000
        王顧問: 今天想先了解你目前的狀況

        2
        00:00:04,500 --> 00:00:09,000
        陳小明: 我的收入很不穩定
        SRT;
    }

    // ── The parser is format-neutral (FR-184) ──────────────────────────────

    public function test_srt_sequence_numbers_and_comma_timings_are_stripped(): void
    {
        $dialogue = app(ConsultationTranscriptService::class)->toDialogue($this->srt());

        $this->assertStringNotContainsString('-->', $dialogue);
        $this->assertStringNotContainsString('00:00:01', $dialogue);
        $this->assertStringContainsString('今天想先了解你目前的狀況', $dialogue);
        // The bare "1" / "2" cue numbers must not survive as dialogue lines.
        $this->assertDoesNotMatchRegularExpression('/^\d+$/m', $dialogue);
    }

    public function test_plain_text_without_speaker_labels_still_produces_dialogue(): void
    {
        $dialogue = app(ConsultationTranscriptService::class)
            ->toDialogue("我們今天談了三件事\n第一是定位");

        $this->assertStringContainsString('我們今天談了三件事', $dialogue);
        $this->assertStringContainsString('第一是定位', $dialogue);
    }

    // ── The endpoint ───────────────────────────────────────────────────────

    public function test_upload_queues_the_job_with_the_raw_contents(): void
    {
        Queue::fake();
        $note = $this->note(['transcript' => '舊的錯逐字稿', 'transcript_fetched_at' => now()]);

        $this->actingAs($this->staff())
            ->post("/admin/consultation-notes/{$note->id}/upload-transcript", [
                'file' => $this->upload('meeting.srt', $this->srt()),
            ])
            ->assertStatus(202);

        Queue::assertPushed(ProcessZoomTranscriptJob::class, function ($job) use ($note) {
            return $job->noteId === $note->id
                && $job->force === true
                && str_contains((string) $job->rawTranscript, '今天想先了解你目前的狀況');
        });
    }

    public function test_upload_replaces_a_transcript_and_a_human_edited_summary(): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-test');
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push(['output_text' => "顧問: 今天想先了解你目前的狀況\n客戶: 我的收入很不穩定"])
                ->push(['output_text' => "## 客戶背景\n- 新的摘要"])
                ->whenEmpty(Http::response(['output_text' => "## 客戶背景\n- 新的摘要"], 200)),
        ]);

        $note = $this->note([
            'transcript'            => '舊的錯逐字稿',
            'transcript_fetched_at' => now()->subDay(),
            'summary'               => '人工修過的舊摘要',
            'summary_edited_at'     => now()->subDay(),
        ]);

        $this->actingAs($this->staff())
            ->post("/admin/consultation-notes/{$note->id}/upload-transcript", [
                'file' => $this->upload('meeting.srt', $this->srt()),
            ])
            ->assertStatus(202);

        $note->refresh();

        // FR-185: both guards are skipped deliberately — the premise of an
        // upload is that the transcript itself changed.
        $this->assertStringNotContainsString('舊的錯逐字稿', (string) $note->transcript);
        $this->assertStringContainsString('今天想先了解你目前的狀況', (string) $note->transcript);
        $this->assertNotSame('人工修過的舊摘要', $note->summary);
        $this->assertNull($note->summary_edited_at);

        // FR-183: the real names must not survive into the database.
        $this->assertStringNotContainsString('王顧問', (string) $note->transcript);
        $this->assertStringNotContainsString('陳小明', (string) $note->transcript);
    }

    // ── Refusals leave the record alone ────────────────────────────────────

    public function test_a_file_with_no_content_lines_is_refused_and_changes_nothing(): void
    {
        Queue::fake();
        $note = $this->note(['transcript' => '原本的逐字稿', 'transcript_fetched_at' => now()]);

        $timingsOnly = "WEBVTT\n\n1\n00:00:01.000 --> 00:00:04.000\n\n2\n00:00:05.000 --> 00:00:08.000\n";

        $this->actingAs($this->staff())
            ->postJson("/admin/consultation-notes/{$note->id}/upload-transcript", [
                'file' => $this->upload('empty.vtt', $timingsOnly),
            ])
            ->assertStatus(422);

        $this->assertSame('原本的逐字稿', $note->fresh()->transcript);
        Queue::assertNothingPushed();
    }

    public function test_an_unsupported_extension_is_refused(): void
    {
        Queue::fake();
        $note = $this->note();

        $this->actingAs($this->staff())
            ->postJson("/admin/consultation-notes/{$note->id}/upload-transcript", [
                'file' => $this->upload('recording.mp4', 'not a transcript'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        Queue::assertNothingPushed();
    }

    public function test_a_file_over_the_size_cap_is_refused(): void
    {
        Queue::fake();
        $note = $this->note();

        $big = UploadedFile::fake()->create('huge.txt', 3000); // KB

        $this->actingAs($this->staff())
            ->postJson("/admin/consultation-notes/{$note->id}/upload-transcript", ['file' => $big])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        Queue::assertNothingPushed();
    }

    public function test_guests_cannot_upload(): void
    {
        Queue::fake();
        $note = $this->note();

        $this->post("/admin/consultation-notes/{$note->id}/upload-transcript", [
            'file' => $this->upload('meeting.srt', $this->srt()),
        ])->assertRedirect();

        Queue::assertNothingPushed();
    }

    public function test_members_cannot_upload(): void
    {
        Queue::fake();
        $note = $this->note();
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)
            ->post("/admin/consultation-notes/{$note->id}/upload-transcript", [
                'file' => $this->upload('meeting.srt', $this->srt()),
            ])
            // StaffMiddleware redirects rather than aborting; what matters here
            // is that the request never reaches the handler.
            ->assertRedirect('/');

        Queue::assertNothingPushed();
    }
}
