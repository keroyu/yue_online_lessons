<?php

namespace Tests\Feature\HighTicket;

use App\Jobs\ProcessZoomTranscriptJob;
use App\Models\ConsultationNote;
use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ConsultationTranscriptService;
use App\Services\OpenAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 011 US35 — the follow-up email as a column of its own.
 *
 * Two invariants are worth the effort here. The first is what goes *into* the
 * call (FR-189): the summary carries the conclusions about the customer's
 * objection, the transcript carries the words to quote back, and a missing
 * nickname must vanish rather than become a placeholder the model reads as a
 * name. The second is what stays *out* of it — no automatic path may write this
 * column (D134), because the letter is the one artefact a consultant polishes
 * by hand before sending.
 */
class ConsultationFollowupEmailTest extends TestCase
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
            'transcript'      => "顧問: 你目前最卡的是什麼\n客戶: 我覺得價格有點超出預算",
        ], $overrides));
    }

    /** Capture the request body the service actually sends to OpenAI. */
    private function fakeAi(string $reply = '陳先生您好，謝謝您today撥空'): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-test');
        Http::fake(['api.openai.com/*' => Http::response(['output_text' => $reply], 200)]);
    }

    private function sentInput(): string
    {
        $body = [];

        Http::assertSent(function ($request) use (&$body) {
            $body = $request->data();

            return true;
        });

        return (string) ($body['input'] ?? '');
    }

    // ── What the model is given (FR-189) ───────────────────────────────────

    public function test_the_input_carries_the_nickname_the_summary_and_the_transcript(): void
    {
        $this->fakeAi();

        $lead = HighTicketLead::create([
            'name'      => '陳小明',
            'email'     => 'booker@example.com',
            'course_id' => Course::create([
                'name'        => 'HT ' . uniqid(),
                'slug'        => 'ht-' . uniqid(),
                'tagline'     => 't',
                'description' => 'd',
                'price'       => 50000,
                'instructor_name' => 'I',
                'type'        => 'high_ticket',
                'status'      => 'selling',
                'course_type' => 'standard',
            ])->id,
            'booked_at' => now(),
        ]);

        $note = $this->note([
            'lead_id' => $lead->id,
            'summary' => "## 主要異議\n- 覺得價格偏高",
        ]);

        app(ConsultationTranscriptService::class)->followupEmail($note);

        $input = $this->sentInput();

        $this->assertStringContainsString('客戶暱稱：陳小明', $input);
        $this->assertStringContainsString('## 面談摘要', $input);
        $this->assertStringContainsString('覺得價格偏高', $input);
        $this->assertStringContainsString('## 逐字稿', $input);
        $this->assertStringContainsString('我覺得價格有點超出預算', $input);
    }

    public function test_the_nickname_line_is_absent_entirely_when_nobody_has_a_name(): void
    {
        $this->fakeAi();

        app(ConsultationTranscriptService::class)->followupEmail($this->note());

        $this->assertStringNotContainsString('客戶暱稱', $this->sentInput());
    }

    public function test_the_summary_section_is_absent_entirely_when_there_is_no_summary(): void
    {
        $this->fakeAi();

        app(ConsultationTranscriptService::class)->followupEmail($this->note(['summary' => null]));

        $input = $this->sentInput();

        $this->assertStringNotContainsString('## 面談摘要', $input);
        $this->assertStringContainsString('## 逐字稿', $input);
    }

    public function test_no_transcript_means_no_call_at_all(): void
    {
        $this->fakeAi();

        $result = app(ConsultationTranscriptService::class)
            ->followupEmail($this->note(['transcript' => null, 'summary' => '有摘要但沒有逐字稿']));

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    // ── The endpoint ───────────────────────────────────────────────────────

    public function test_generating_overwrites_a_hand_edited_letter_and_clears_the_lock(): void
    {
        $this->fakeAi('陳先生您好，這是新的追銷信');

        $note = $this->note([
            'followup_email'           => '顧問手改過的舊信',
            'followup_email_edited_at' => now()->subDay(),
        ]);

        $this->actingAs($this->staff())
            ->postJson("/admin/consultation-notes/{$note->id}/generate-followup-email")
            ->assertOk()
            ->assertJsonPath('followup_email', '陳先生您好，這是新的追銷信');

        $note->refresh();

        $this->assertSame('陳先生您好，這是新的追銷信', $note->followup_email);
        $this->assertNull($note->followup_email_edited_at);
        $this->assertNotNull($note->followup_email_generated_at);
    }

    public function test_generating_without_a_transcript_is_refused(): void
    {
        $this->fakeAi();

        $note = $this->note(['transcript' => null, 'followup_email' => '既有的信']);

        $this->actingAs($this->staff())
            ->postJson("/admin/consultation-notes/{$note->id}/generate-followup-email")
            ->assertStatus(422);

        $this->assertSame('既有的信', $note->fresh()->followup_email);
    }

    public function test_generating_without_ai_configured_is_refused_and_changes_nothing(): void
    {
        $note = $this->note(['followup_email' => '既有的信']);

        $this->actingAs($this->staff())
            ->postJson("/admin/consultation-notes/{$note->id}/generate-followup-email")
            ->assertStatus(422);

        $this->assertSame('既有的信', $note->fresh()->followup_email);
    }

    public function test_saving_stamps_the_edit_time(): void
    {
        $note = $this->note();

        $this->actingAs($this->staff())
            ->patchJson("/admin/consultation-notes/{$note->id}/followup-email", [
                'followup_email' => '我自己重寫的信',
            ])
            ->assertOk();

        $note->refresh();

        $this->assertSame('我自己重寫的信', $note->followup_email);
        $this->assertNotNull($note->followup_email_edited_at);
    }

    public function test_non_staff_cannot_reach_either_endpoint(): void
    {
        $note = $this->note();
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)
            ->post("/admin/consultation-notes/{$note->id}/generate-followup-email")
            ->assertRedirect('/');

        $this->actingAs($member)
            ->patch("/admin/consultation-notes/{$note->id}/followup-email", ['followup_email' => 'x'])
            ->assertRedirect('/');

        $this->assertNull($note->fresh()->followup_email);
    }

    // ── Nothing automatic may touch this column (D134) ─────────────────────

    public function test_replacing_the_transcript_leaves_the_follow_up_email_alone(): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-test');
        Http::fake([
            'api.openai.com/*' => Http::response(['output_text' => "## 客戶背景\n- 新的摘要"], 200),
        ]);

        $note = $this->note([
            'transcript_fetched_at'    => now()->subDay(),
            'summary'                  => '舊摘要',
            'followup_email'           => '顧問已經改好、準備寄出的信',
            'followup_email_edited_at' => now()->subDay(),
        ]);

        $srt = "1\n00:00:01,000 --> 00:00:04,000\n王顧問: 我們重新談一次\n";

        $this->actingAs($this->staff())
            ->post("/admin/consultation-notes/{$note->id}/upload-transcript", [
                'file' => UploadedFile::fake()->createWithContent('meeting.srt', $srt),
            ])
            ->assertStatus(202);

        $note->refresh();

        // The summary is regenerated (FR-185) — the letter is not (D134).
        $this->assertNotSame('舊摘要', $note->summary);
        $this->assertSame('顧問已經改好、準備寄出的信', $note->followup_email);
        $this->assertNotNull($note->followup_email_edited_at);
    }
}
