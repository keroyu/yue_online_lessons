<?php

namespace Tests\Feature\Classroom;

use App\Models\AiPrompt;
use App\Models\Assignment;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\OpenAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * AI 快速批改 (003 US10).
 *
 * The endpoint is a draft generator, not a grader: everything here asserts that
 * it produces text and touches nothing else — no comment is created, no column
 * is written, and the admin still has to press 送出.
 */
class AiGradingTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin', 'nickname' => '老師']);
    }

    private function student(): User
    {
        return User::create(['email' => 'student@example.com', 'role' => 'member', 'nickname' => '小明']);
    }

    private function makeAssignment(string $question = '寫一篇 500 字心得', ?string $handout = '第一章：定價策略'): Assignment
    {
        $this->seq++;

        $course = Course::create([
            'name' => 'C' . $this->seq, 'slug' => 'c-' . $this->seq, 'tagline' => 't', 'description' => 'd',
            'price' => 1000, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);
        $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch', 'sort_order' => 1]);
        $lesson = Lesson::create(['course_id' => $course->id, 'chapter_id' => $chapter->id, 'title' => 'L']);

        return Assignment::create([
            'lesson_id'    => $lesson->id,
            'question_md'  => $question,
            'handout_md'   => $handout,
            'is_published' => true,
        ]);
    }

    private function submission(Assignment $assignment, User $student, string $content = '我的作業內容'): Comment
    {
        return Comment::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $student->id,
            'parent_id'     => null,
            'content'       => $content,
        ]);
    }

    /** A configured OpenAI that always answers with $text. */
    private function fakeOpenAi(string $text = 'AI 批改草稿'): void
    {
        SiteSetting::set(OpenAiService::API_KEY, 'sk-test');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'output' => [
                    ['type' => 'reasoning', 'summary' => []],
                    ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => $text]]],
                ],
            ]),
        ]);
    }

    private function draftUrl(Assignment $assignment, Comment $comment): string
    {
        return "/admin/homework/{$assignment->id}/comments/{$comment->id}/ai-draft";
    }

    public function test_handout_is_saved_with_the_question_and_stays_admin_only(): void
    {
        $admin = $this->admin();
        $assignment = $this->makeAssignment(handout: null);

        $this->actingAs($admin)
            ->put("/admin/homework/{$assignment->id}", [
                'question_md' => '新題目',
                'handout_md'  => '## 講義\n重點是定價',
            ])
            ->assertRedirect();

        $this->assertSame('## 講義\n重點是定價', $assignment->fresh()->handout_md);

        // 學員端（教室）永遠拿不到講義
        $this->actingAs($admin)
            ->get('/admin/homework')
            ->assertOk();
    }

    public function test_a_handout_without_a_question_is_rejected_with_a_readable_message(): void
    {
        $admin = $this->admin();
        $assignment = $this->makeAssignment(handout: null);

        // Saving only the handout used to fail silently: the page rendered no
        // errors at all, so 儲存 looked like it did nothing.
        $this->actingAs($admin)
            ->put("/admin/homework/{$assignment->id}", [
                'question_md' => '',
                'handout_md'  => '只有講義',
            ])
            ->assertSessionHasErrors(['question_md' => '請填寫作業題目（只填講義無法建立題目）']);

        $this->assertNull($assignment->fresh()->handout_md);
    }

    public function test_a_long_handout_is_accepted(): void
    {
        $admin = $this->admin();
        $assignment = $this->makeAssignment(handout: null);
        $handout = str_repeat('講義', 15000); // 30000 字

        $this->actingAs($admin)
            ->put("/admin/homework/{$assignment->id}", [
                'question_md' => '題目',
                'handout_md'  => $handout,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(30000, mb_strlen($assignment->fresh()->handout_md));
    }

    public function test_draft_sends_handout_question_and_submission_to_the_model(): void
    {
        $admin = $this->admin();
        $assignment = $this->makeAssignment('請分析你的定價', '第一章：定價策略');
        $comment = $this->submission($assignment, $this->student(), '我打算收 3000 元');

        $this->fakeOpenAi('先肯定，再給建議');

        $this->actingAs($admin)
            ->postJson($this->draftUrl($assignment, $comment))
            ->assertOk()
            ->assertJson(['draft' => '先肯定，再給建議']);

        Http::assertSent(function ($request) {
            $input = $request['input'];

            return str_contains($input, '## 講義')
                && str_contains($input, '第一章：定價策略')
                && str_contains($input, '## 作業題目')
                && str_contains($input, '請分析你的定價')
                && str_contains($input, '## 學員提交')
                && str_contains($input, '我打算收 3000 元');
        });
    }

    public function test_draft_includes_previous_grading_exchange_without_names(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $assignment = $this->makeAssignment();
        $comment = $this->submission($assignment, $student);

        Comment::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $admin->id,
            'parent_id'     => $comment->id,
            'content'       => '第一段可以再具體一點',
        ]);
        Comment::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $student->id,
            'parent_id'     => $comment->id,
            'content'       => '我補上了案例',
        ]);

        $this->fakeOpenAi();

        $this->actingAs($admin)->postJson($this->draftUrl($assignment, $comment))->assertOk();

        Http::assertSent(function ($request) {
            $input = $request['input'];

            return str_contains($input, '## 先前的批改往返')
                && str_contains($input, '老師: 第一段可以再具體一點')
                && str_contains($input, '學員: 我補上了案例')
                // PII never reaches the prompt (FR-020)
                && !str_contains($input, 'student@example.com')
                && !str_contains($input, '小明');
        });
    }

    public function test_draft_works_without_a_handout(): void
    {
        $admin = $this->admin();
        $assignment = $this->makeAssignment(handout: null);
        $comment = $this->submission($assignment, $this->student());

        $this->fakeOpenAi();

        $this->actingAs($admin)->postJson($this->draftUrl($assignment, $comment))->assertOk();

        Http::assertSent(fn ($request) => str_contains($request['input'], '## 講義')
            && str_contains($request['input'], '（未提供）'));
    }

    public function test_long_sections_are_truncated(): void
    {
        $admin = $this->admin();
        // A character that appears nowhere in the section headings, so the count
        // below measures the handout body and nothing else.
        $assignment = $this->makeAssignment('題目', str_repeat('喵', 9000));
        $comment = $this->submission($assignment, $this->student());

        $this->fakeOpenAi();

        $this->actingAs($admin)->postJson($this->draftUrl($assignment, $comment))->assertOk();

        Http::assertSent(function ($request) {
            $input = $request['input'];

            return mb_substr_count($input, '喵') === 8000
                && str_contains($input, '（內容過長，已截斷）');
        });
    }

    public function test_teacher_note_becomes_the_last_section(): void
    {
        $admin = $this->admin();
        $assignment = $this->makeAssignment();
        $comment = $this->submission($assignment, $this->student());

        $this->fakeOpenAi();

        $this->actingAs($admin)
            ->postJson($this->draftUrl($assignment, $comment), [
                'note' => '第三段的運鏡明顯抖，語氣溫和一點',
            ])
            ->assertOk();

        Http::assertSent(function ($request) {
            $input = $request['input'];

            return str_contains($input, '## 講師補充指示')
                && str_contains($input, '第三段的運鏡明顯抖，語氣溫和一點')
                // Last section wins the model's attention (FR-024).
                && mb_strpos($input, '## 講師補充指示') > mb_strpos($input, '## 先前的批改往返');
        });
    }

    public function test_input_is_untouched_when_no_note_is_given(): void
    {
        $admin = $this->admin();
        $assignment = $this->makeAssignment();
        $comment = $this->submission($assignment, $this->student());

        $this->fakeOpenAi();

        // Both the absent key and a whitespace-only one mean "no instruction":
        // the section is dropped entirely rather than sent as （未提供）, so the
        // prompt is byte-for-byte what it was before this field existed.
        foreach ([[], ['note' => '   ']] as $payload) {
            $this->actingAs($admin)
                ->postJson($this->draftUrl($assignment, $comment), $payload)
                ->assertOk();
        }

        Http::assertSent(fn ($request) => !str_contains($request['input'], '## 講師補充指示'));
    }

    public function test_an_over_long_note_is_rejected(): void
    {
        $admin = $this->admin();
        $assignment = $this->makeAssignment();
        $comment = $this->submission($assignment, $this->student());

        $this->fakeOpenAi();

        $this->actingAs($admin)
            ->postJson($this->draftUrl($assignment, $comment), [
                'note' => str_repeat('喵', 2001),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('note');

        Http::assertNothingSent();
    }

    public function test_teacher_note_is_never_persisted(): void
    {
        $admin = $this->admin();
        $assignment = $this->makeAssignment();
        $comment = $this->submission($assignment, $this->student());

        $this->fakeOpenAi();

        $this->actingAs($admin)
            ->postJson($this->draftUrl($assignment, $comment), ['note' => '只給 AI 看的指示'])
            ->assertOk();

        // It is prompt context, never content: FR-025.
        $this->assertDatabaseCount('comments', 1);
        $this->assertDatabaseMissing('comments', ['content' => '只給 AI 看的指示']);
    }

    public function test_draft_is_not_persisted_anywhere(): void
    {
        $admin = $this->admin();
        $assignment = $this->makeAssignment();
        $comment = $this->submission($assignment, $this->student());

        $this->fakeOpenAi('這段草稿不該進資料庫');

        $this->actingAs($admin)->postJson($this->draftUrl($assignment, $comment))->assertOk();

        $this->assertDatabaseCount('comments', 1);
        $this->assertDatabaseMissing('comments', ['content' => '這段草稿不該進資料庫']);
    }

    public function test_missing_api_key_returns_a_readable_422(): void
    {
        $admin = $this->admin();
        $assignment = $this->makeAssignment();
        $comment = $this->submission($assignment, $this->student());

        Http::fake();

        $this->actingAs($admin)
            ->postJson($this->draftUrl($assignment, $comment))
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        Http::assertNothingSent();
    }

    public function test_comment_from_another_assignment_is_rejected(): void
    {
        $admin = $this->admin();
        $assignment = $this->makeAssignment();
        $other = $this->makeAssignment();
        $foreign = $this->submission($other, $this->student());

        $this->fakeOpenAi();

        $this->actingAs($admin)
            ->postJson($this->draftUrl($assignment, $foreign))
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_reply_comment_cannot_be_used_as_the_submission(): void
    {
        $admin = $this->admin();
        $assignment = $this->makeAssignment();
        $comment = $this->submission($assignment, $this->student());

        $reply = Comment::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $admin->id,
            'parent_id'     => $comment->id,
            'content'       => '批改',
        ]);

        $this->fakeOpenAi();

        $this->actingAs($admin)
            ->postJson($this->draftUrl($assignment, $reply))
            ->assertNotFound();
    }

    public function test_endpoint_is_admin_only(): void
    {
        $assignment = $this->makeAssignment();
        $comment = $this->submission($assignment, $student = $this->student());

        $this->fakeOpenAi();

        // AdminMiddleware redirects rather than 403s — site-wide convention.
        $this->actingAs($student)
            ->post($this->draftUrl($assignment, $comment))
            ->assertRedirect('/');

        Http::assertNothingSent();
    }

    public function test_prompt_row_is_installed_and_grouped_under_homework(): void
    {
        $prompt = AiPrompt::for('homework_grading_draft');

        $this->assertNotNull($prompt);
        $this->assertSame('homework', $prompt->feature);
        $this->assertSame('gpt-5.6-terra', $prompt->model);
        $this->assertArrayHasKey('homework', (array) config('ai.features'));

        // The priority of the teacher note lives in the prompt, not in the
        // service (D24) — so its absence here is a silent behaviour change.
        $this->assertStringContainsString('## 講師補充指示', $prompt->instructions);
    }
}
