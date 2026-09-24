<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentCompletion;
use App\Models\Comment;
use App\Models\HomeworkNotification;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    public function markComplete(User $student, Assignment $assignment): array
    {
        if (AssignmentCompletion::where('assignment_id', $assignment->id)->where('user_id', $student->id)->exists()) {
            return ['success' => false, 'error' => '此學員的作業已標記為完成'];
        }

        $course = $assignment->lesson->course;

        $rewardPoints = (int) SiteSetting::get('homework_reward_points', 100);

        DB::transaction(function () use ($student, $assignment, $course, $rewardPoints) {
            AssignmentCompletion::create([
                'assignment_id' => $assignment->id,
                'user_id' => $student->id,
            ]);

            // 積分一律經帳本發放（PointService 為 users.points 唯一寫入點）
            app(PointService::class)->award(
                $student,
                $rewardPoints,
                'earn_homework',
                'assignment',
                $assignment->id,
            );

            HomeworkNotification::create([
                'user_id' => $student->id,
                'type' => 'completion',
                'course_name' => $course->name,
                'course_id' => $course->id,
                'lesson_id' => $assignment->lesson_id,
                'is_read' => false,
            ]);
        });

        return ['success' => true];
    }

    /**
     * Save (or overwrite) this learner's single draft for the assignment.
     *
     * One draft per (assignment, user) is the invariant (FR-036). MySQL's unique
     * index ignores NULLs, so it lives here instead of in the schema — which is
     * also why every transition below funnels through this service (D39).
     */
    public function saveDraft(User $student, Assignment $assignment, string $content): Comment
    {
        return Comment::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'user_id'       => $student->id,
                'parent_id'     => null,
                'submitted_at'  => null,
            ],
            ['content' => $content],
        );
    }

    /**
     * Hand the work in. An existing draft is promoted in place (FR-037): same row,
     * same created_at, so "I finished my draft and pressed submit" cannot leave a
     * stale draft next to a near-identical submission (D38).
     */
    public function submit(User $student, Assignment $assignment, ?string $content = null): Comment
    {
        $draft = $this->draftFor($student, $assignment);

        if ($draft) {
            $draft->update([
                'content'      => $content !== null && trim($content) !== '' ? $content : $draft->content,
                'submitted_at' => now(),
            ]);

            return $draft;
        }

        return Comment::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $student->id,
            'parent_id'     => null,
            'content'       => $content,
            'submitted_at'  => now(),
        ]);
    }

    /**
     * Promote an existing draft the learner is looking at (the draft card's own
     * "提交答案" button), optionally with edited content.
     */
    public function submitDraft(Comment $draft, ?string $content = null): array
    {
        if (!$draft->isDraft()) {
            return ['success' => false, 'error' => '此作業已提交'];
        }

        $draft->update([
            'content'      => $content !== null && trim($content) !== '' ? $content : $draft->content,
            'submitted_at' => now(),
        ]);

        return ['success' => true];
    }

    /**
     * The learner pulls their own submission back (FR-038). An instructor reply is
     * the line: there is no read receipt on this site, so a reply is the only
     * observable sign the teacher has already put time in (D40).
     */
    public function revertToDraft(Comment $comment, User $student): array
    {
        if ($comment->isDraft()) {
            return ['success' => false, 'error' => '此作業目前就是草稿'];
        }

        if ($comment->replies()->exists()) {
            return ['success' => false, 'error' => '老師已批改這份作業，無法改回草稿；你仍可編輯內容或追加補充'];
        }

        if ($error = $this->draftStateGuards($comment, $student)) {
            return ['success' => false, 'error' => $error];
        }

        $comment->update(['submitted_at' => null]);

        return ['success' => true];
    }

    /**
     * The instructor pushes the work back (FR-039). Unaffected by "already has a
     * reply" — that reply is his own — but still blocked once the assignment is
     * marked complete, because completion already paid out points (FR-007).
     * Content is never touched: the learner picks up where they left off (D41).
     */
    public function returnToDraft(Comment $comment): array
    {
        if ($comment->isDraft()) {
            return ['success' => false, 'error' => '此作業目前就是草稿'];
        }

        $student = $comment->user;

        if ($error = $this->draftStateGuards($comment, $student)) {
            return ['success' => false, 'error' => $error];
        }

        $course = $comment->assignment->lesson->course;

        DB::transaction(function () use ($comment, $student, $course) {
            $comment->update(['submitted_at' => null]);

            HomeworkNotification::create([
                'user_id'     => $student->id,
                'type'        => 'returned',
                'course_name' => $course->name,
                'course_id'   => $course->id,
                'lesson_id'   => $comment->assignment->lesson_id,
                'is_read'     => false,
            ]);
        });

        return ['success' => true];
    }

    /** Shared between both back-to-draft paths: completion freezes, one draft only. */
    private function draftStateGuards(Comment $comment, User $student): ?string
    {
        $assignment = $comment->assignment;

        if ($assignment->isCompletedBy($student)) {
            return '此作業已標記完成，無法改回草稿';
        }

        if ($this->draftFor($student, $assignment)) {
            return '這一題已經有一份草稿，請先處理那份草稿';
        }

        return null;
    }

    private function draftFor(User $student, Assignment $assignment): ?Comment
    {
        return Comment::where('assignment_id', $assignment->id)
            ->where('user_id', $student->id)
            ->topLevel()
            ->drafts()
            ->first();
    }
}
