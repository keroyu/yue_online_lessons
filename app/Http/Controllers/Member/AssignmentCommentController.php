<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreCommentRequest;
use App\Models\Assignment;
use App\Models\Comment;
use App\Models\Course;
use App\Services\AssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssignmentCommentController extends Controller
{
    public function __construct(protected AssignmentService $assignmentService) {}

    public function store(StoreCommentRequest $request, Course $course, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();

        $this->guardSubmission($course, $assignment, $user);

        // Both buttons post here and differ only by status (D37).
        if ($request->status === 'draft') {
            $this->assignmentService->saveDraft($user, $assignment, $request->content);

            return redirect()->back()->with('success', '草稿已儲存，只有你看得到');
        }

        if ($request->parent_id !== null) {
            Comment::create([
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
                'parent_id' => $request->parent_id,
                'content' => $request->content,
                'submitted_at' => now(),
            ]);

            return redirect()->back()->with('success', '作業已提交');
        }

        $this->assignmentService->submit($user, $assignment, $request->content);

        return redirect()->back()->with('success', '作業已提交');
    }

    /** Hand in an existing draft from the draft card itself (003 US12). */
    public function submit(Request $request, Course $course, Assignment $assignment, Comment $comment): RedirectResponse
    {
        $user = $request->user();

        if (!$comment->isOwnedBy($user)) {
            abort(403, '您沒有權限提交此留言');
        }

        $this->guardSubmission($course, $assignment, $user);

        $result = $this->assignmentService->submitDraft($comment, $request->input('content'));

        if (!$result['success']) {
            return redirect()->back()->withErrors(['draft' => $result['error']]);
        }

        return redirect()->back()->with('success', '作業已提交');
    }

    /** Pull a submission back to draft (003 US12 / FR-038). */
    public function revertToDraft(Request $request, Course $course, Assignment $assignment, Comment $comment): RedirectResponse
    {
        $user = $request->user();

        if (!$comment->isOwnedBy($user)) {
            abort(403, '您沒有權限修改此留言');
        }

        $result = $this->assignmentService->revertToDraft($comment, $user);

        if (!$result['success']) {
            return redirect()->back()->withErrors(['draft' => $result['error']]);
        }

        return redirect()->back()->with('success', '已改回草稿，老師看不到了');
    }

    public function update(Request $request, Course $course, Assignment $assignment, Comment $comment): RedirectResponse
    {
        $user = $request->user();

        if (!$comment->isOwnedBy($user)) {
            abort(403, '您沒有權限編輯此留言');
        }

        $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $comment->update([
            'content' => $request->content,
            // A draft is not published work yet, so editing it needs no "edited" badge.
            'is_edited' => $comment->isDraft() ? $comment->is_edited : true,
        ]);

        return redirect()->back()->with('success', '已更新');
    }

    public function destroy(Request $request, Course $course, Assignment $assignment, Comment $comment): RedirectResponse
    {
        $user = $request->user();

        if (!$comment->isOwnedBy($user)) {
            abort(403, '您沒有權限刪除此留言');
        }

        $comment->delete();

        return redirect()->back()->with('success', '已刪除');
    }

    private function guardSubmission(Course $course, Assignment $assignment, $user): void
    {
        if (!$course->hasAccessForUser($user)) {
            abort(403, '您尚未購買此課程');
        }

        if ($assignment->lesson->course_id !== $course->id) {
            abort(404);
        }

        if (!$assignment->is_published) {
            abort(403, '此作業已下架，不接受新提交');
        }
    }
}
