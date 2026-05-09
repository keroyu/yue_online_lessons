<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreCommentRequest;
use App\Models\Assignment;
use App\Models\Comment;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssignmentCommentController extends Controller
{
    public function store(StoreCommentRequest $request, Course $course, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();

        if (!$course->hasAccessForUser($user)) {
            abort(403, '您尚未購買此課程');
        }

        if ($assignment->lesson->course_id !== $course->id) {
            abort(404);
        }

        if (!$assignment->is_published) {
            abort(403, '此作業已下架，不接受新提交');
        }

        Comment::create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'parent_id' => $request->parent_id,
            'content' => $request->content,
        ]);

        return redirect()->back()->with('success', '作業已提交');
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
            'is_edited' => true,
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
}
