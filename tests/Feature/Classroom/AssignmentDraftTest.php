<?php

namespace Tests\Feature\Classroom;

use App\Models\Assignment;
use App\Models\AssignmentCompletion;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Course;
use App\Models\HomeworkNotification;
use App\Models\Lesson;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 003 US12 / FR-034–FR-043 — homework drafts vs formal submissions.
 */
class AssignmentDraftTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;
    private Lesson $lesson;
    private Assignment $assignment;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::create([
            'name'            => 'Draft Course',
            'slug'            => 'draft-course',
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 1000,
            'instructor_name' => 'Tester',
            'type'            => 'full',
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ]);

        $chapter = Chapter::create([
            'course_id'  => $this->course->id,
            'title'      => 'Chapter 1',
            'sort_order' => 0,
        ]);

        $this->lesson = Lesson::create([
            'course_id'   => $this->course->id,
            'chapter_id'  => $chapter->id,
            'title'       => 'Lesson 1',
            'sort_order'  => 0,
            'content_md'  => 'body',
        ]);

        $this->assignment = Assignment::create([
            'lesson_id'    => $this->lesson->id,
            'question_md'  => '請說明你的變現主題',
            'is_published' => true,
        ]);

        $this->student = User::factory()->create();

        Purchase::create([
            'user_id'   => $this->student->id,
            'course_id' => $this->course->id,
            'amount'    => $this->course->price,
            'status'    => 'paid',
            'type'      => 'purchase',
        ]);
    }

    private function commentsUrl(): string
    {
        return "/member/classroom/{$this->course->id}/assignment/{$this->assignment->id}/comments";
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function saveDraft(string $content = '半成品'): Comment
    {
        $this->actingAs($this->student)
            ->post($this->commentsUrl(), ['content' => $content, 'status' => 'draft'])
            ->assertRedirect();

        return Comment::where('user_id', $this->student->id)->whereNull('submitted_at')->firstOrFail();
    }

    private function submitted(string $content = '完成的答案'): Comment
    {
        $this->actingAs($this->student)
            ->post($this->commentsUrl(), ['content' => $content, 'status' => 'submitted'])
            ->assertRedirect();

        return Comment::where('user_id', $this->student->id)->whereNotNull('submitted_at')->latest('id')->firstOrFail();
    }

    /** FR-035: a draft never reaches the instructor's list. */
    public function test_draft_is_hidden_from_admin_grading_list(): void
    {
        $this->saveDraft();

        $this->actingAs($this->admin())
            ->get('/admin/homework')
            ->assertInertia(fn ($page) => $page->where('submissions.data', []));
    }

    /** FR-035: nor the admin's "student view" preview of the classroom. */
    public function test_draft_is_hidden_from_admin_student_view_preview(): void
    {
        $this->saveDraft();

        $this->actingAs($this->admin())
            ->get("/member/classroom/{$this->course->id}?lesson_id={$this->lesson->id}&preview_user_id={$this->student->id}")
            ->assertInertia(fn ($page) => $page->where('currentLesson.assignment_comments', []));
    }

    /** FR-035: the student still sees their own draft. */
    public function test_student_sees_own_draft_in_classroom(): void
    {
        $draft = $this->saveDraft();

        $this->actingAs($this->student)
            ->get("/member/classroom/{$this->course->id}?lesson_id={$this->lesson->id}")
            ->assertInertia(fn ($page) => $page
                ->where('currentLesson.assignment_comments.0.id', $draft->id)
                ->where('currentLesson.assignment_comments.0.is_draft', true));
    }

    /** FR-036: one draft per (assignment, user) — saving again overwrites it. */
    public function test_saving_a_draft_twice_keeps_a_single_row(): void
    {
        $first = $this->saveDraft('第一版');
        $this->saveDraft('第二版');

        $drafts = Comment::where('user_id', $this->student->id)->whereNull('submitted_at')->get();

        $this->assertCount(1, $drafts);
        $this->assertSame($first->id, $drafts->first()->id);
        $this->assertSame('第二版', $drafts->first()->content);
    }

    /** FR-037: turning a draft into a submission reuses the same row. */
    public function test_submitting_reuses_the_draft_row(): void
    {
        $draft = $this->saveDraft('草稿內容');

        $submission = $this->submitted('補完後的答案');

        $this->assertSame($draft->id, $submission->id);
        $this->assertSame('補完後的答案', $submission->content);
        $this->assertNotNull($submission->submitted_at);
        $this->assertEquals(
            $draft->created_at->timestamp,
            $submission->created_at->timestamp,
            'created_at must survive the draft → submitted transition',
        );
        $this->assertSame(1, Comment::where('user_id', $this->student->id)->count());
    }

    /** FR-038: no instructor reply yet → the student may pull it back. */
    public function test_student_can_revert_submission_without_reply(): void
    {
        $submission = $this->submitted();

        $this->actingAs($this->student)
            ->post("{$this->commentsUrl()}/{$submission->id}/revert")
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNull($submission->fresh()->submitted_at);
        $this->assertSame(0, HomeworkNotification::count(), 'reverting on your own must not notify anyone (FR-040)');
    }

    /** FR-038: once the instructor has replied, the student is locked out. */
    public function test_student_cannot_revert_after_instructor_reply(): void
    {
        $submission = $this->submitted();

        Comment::create([
            'assignment_id' => $this->assignment->id,
            'user_id'       => $this->admin()->id,
            'parent_id'     => $submission->id,
            'content'       => '寫得不錯',
            'submitted_at'  => now(),
        ]);

        $this->actingAs($this->student)
            ->post("{$this->commentsUrl()}/{$submission->id}/revert")
            ->assertRedirect()
            ->assertSessionHasErrors('draft');

        $this->assertNotNull($submission->fresh()->submitted_at);
    }

    /** FR-038: another student may never touch it. */
    public function test_other_student_cannot_revert_submission(): void
    {
        $submission = $this->submitted();
        $other = User::factory()->create();

        $this->actingAs($other)
            ->post("{$this->commentsUrl()}/{$submission->id}/revert")
            ->assertForbidden();

        $this->assertNotNull($submission->fresh()->submitted_at);
    }

    /** FR-039: completion freezes the state on both paths. */
    public function test_neither_side_can_revert_once_marked_complete(): void
    {
        $submission = $this->submitted();

        AssignmentCompletion::create([
            'assignment_id' => $this->assignment->id,
            'user_id'       => $this->student->id,
        ]);

        $this->actingAs($this->student)
            ->post("{$this->commentsUrl()}/{$submission->id}/revert")
            ->assertSessionHasErrors('draft');

        $this->actingAs($this->admin())
            ->post("/admin/homework/{$this->assignment->id}/comments/{$submission->id}/return")
            ->assertSessionHasErrors('draft');

        $this->assertNotNull($submission->fresh()->submitted_at);
    }

    /** FR-039/FR-040: the instructor may push work back, and that does notify. */
    public function test_instructor_can_return_submission_to_draft_with_notification(): void
    {
        $submission = $this->submitted();
        $admin = $this->admin();

        Comment::create([
            'assignment_id' => $this->assignment->id,
            'user_id'       => $admin->id,
            'parent_id'     => $submission->id,
            'content'       => '請補上第三題',
            'submitted_at'  => now(),
        ]);

        $this->actingAs($admin)
            ->post("/admin/homework/{$this->assignment->id}/comments/{$submission->id}/return")
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNull($submission->fresh()->submitted_at);

        $notification = HomeworkNotification::where('user_id', $this->student->id)->firstOrFail();
        $this->assertSame('returned', $notification->type);
        $this->assertSame($this->lesson->id, $notification->lesson_id);
    }

    /** FR-040: the bell names the lesson the work belongs to. */
    public function test_returned_notification_message_names_the_lesson(): void
    {
        $submission = $this->submitted();

        $this->actingAs($this->admin())
            ->post("/admin/homework/{$this->assignment->id}/comments/{$submission->id}/return")
            ->assertSessionHasNoErrors();

        $this->actingAs($this->student)
            ->get('/member/learning')
            ->assertInertia(fn ($page) => $page->where(
                'notifications.0.message',
                "你在【{$this->lesson->title}】的作業看起來還沒完成喔，補完後再提交一次",
            ));
    }

    /** FR-036: the one-draft invariant also guards the return paths. */
    public function test_return_is_blocked_when_the_student_already_holds_a_draft(): void
    {
        $submission = $this->submitted();
        $this->saveDraft('另一份草稿');

        $this->actingAs($this->admin())
            ->post("/admin/homework/{$this->assignment->id}/comments/{$submission->id}/return")
            ->assertSessionHasErrors('draft');

        $this->actingAs($this->student)
            ->post("{$this->commentsUrl()}/{$submission->id}/revert")
            ->assertSessionHasErrors('draft');

        $this->assertNotNull($submission->fresh()->submitted_at);
    }

    /** FR-042: the grading list is ordered by submission time, not creation time. */
    public function test_admin_list_orders_by_submitted_at(): void
    {
        // Older row, submitted last — it must come first.
        $old = Comment::create([
            'assignment_id' => $this->assignment->id,
            'user_id'       => $this->student->id,
            'content'       => '很早就開始寫',
            'created_at'    => now()->subDays(3),
            'submitted_at'  => now(),
        ]);

        $newer = Comment::create([
            'assignment_id' => $this->assignment->id,
            'user_id'       => User::factory()->create()->id,
            'content'       => '今天才寫',
            'created_at'    => now()->subMinute(),
            'submitted_at'  => now()->subMinute(),
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/homework')
            ->assertInertia(fn ($page) => $page
                ->where('submissions.data.0.id', $old->id)
                ->where('submissions.data.1.id', $newer->id));
    }

    /** FR-035/FR-021: no AI grading draft off a draft submission. */
    public function test_ai_draft_endpoint_rejects_a_draft_submission(): void
    {
        $draft = $this->saveDraft();

        $this->actingAs($this->admin())
            ->postJson("/admin/homework/{$this->assignment->id}/comments/{$draft->id}/ai-draft")
            ->assertNotFound();
    }
}
