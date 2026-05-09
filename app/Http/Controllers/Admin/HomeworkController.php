<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignmentRequest;
use App\Models\Assignment;
use App\Models\Comment;
use App\Models\Course;
use App\Models\HomeworkNotification;
use App\Models\Lesson;
use App\Models\User;
use App\Services\AssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeworkController extends Controller
{
    public function __construct(protected AssignmentService $assignmentService) {}

    public function index(Request $request): Response
    {
        $courseId = $request->input('course_id');
        $lessonId = $request->input('lesson_id');

        $query = Comment::topLevel()
            ->with(['assignment.lesson.course', 'user', 'replies.user', 'assignment.completions'])
            ->whereHas('assignment');

        if ($courseId) {
            $query->whereHas('assignment.lesson.course', fn ($q) => $q->where('id', $courseId));
        }

        if ($lessonId) {
            $query->whereHas('assignment.lesson', fn ($q) => $q->where('id', $lessonId));
        }

        $submissions = $query->latest()->paginate(20)->through(function ($comment) {
            $completion = $comment->assignment->completions
                ->where('user_id', $comment->user_id)
                ->first();

            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'is_edited' => $comment->is_edited,
                'created_at' => $comment->created_at,
                'assignment' => [
                    'id' => $comment->assignment->id,
                    'md_content' => $comment->assignment->md_content,
                    'is_published' => $comment->assignment->is_published,
                    'lesson' => [
                        'id' => $comment->assignment->lesson->id,
                        'title' => $comment->assignment->lesson->title,
                        'course' => [
                            'id' => $comment->assignment->lesson->course->id,
                            'name' => $comment->assignment->lesson->course->name,
                        ],
                    ],
                ],
                'user' => [
                    'id' => $comment->user->id,
                    'nickname' => $comment->user->nickname,
                    'email' => $comment->user->email,
                ],
                'replies' => $comment->replies->map(fn ($reply) => [
                    'id' => $reply->id,
                    'content' => $reply->content,
                    'is_edited' => $reply->is_edited,
                    'created_at' => $reply->created_at,
                    'user' => [
                        'id' => $reply->user->id,
                        'nickname' => $reply->user->nickname,
                        'is_admin' => $reply->user->isAdmin(),
                    ],
                ]),
                'completion' => $completion ? [
                    'id' => $completion->id,
                    'created_at' => $completion->created_at,
                ] : null,
            ];
        });

        $courses = Course::orderBy('name')
            ->get(['id', 'name']);

        $lessons = $lessonId
            ? Lesson::where('id', $lessonId)->get(['id', 'title'])
            : collect();

        $allLessonsForCourse = $courseId
            ? Lesson::where('course_id', $courseId)->orderBy('sort_order')->get(['id', 'title'])
            : collect();

        return Inertia::render('Admin/Homework/Index', [
            'submissions' => $submissions,
            'courses' => $courses,
            'lessons' => $allLessonsForCourse,
            'filters' => [
                'course_id' => $courseId ? (int) $courseId : null,
                'lesson_id' => $lessonId ? (int) $lessonId : null,
            ],
            'assignmentsMap' => $this->getAssignmentsMap(),
        ]);
    }

    public function store(AssignmentRequest $request, Lesson $lesson): RedirectResponse
    {
        if ($lesson->assignment()->exists()) {
            return redirect()->back()->withErrors(['lesson' => '此小節已有作業題目，請使用編輯功能']);
        }

        $lesson->assignment()->create($request->validated());

        return redirect()->back()->with('success', '題目已建立');
    }

    public function update(AssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $assignment->update($request->validated());

        return redirect()->back()->with('success', '題目已更新');
    }

    public function publish(Assignment $assignment): RedirectResponse
    {
        $assignment->update(['is_published' => true]);

        return redirect()->back()->with('success', '題目已上架');
    }

    public function unpublish(Assignment $assignment): RedirectResponse
    {
        $assignment->update(['is_published' => false]);

        return redirect()->back()->with('success', '題目已下架');
    }

    public function storeComment(Request $request, Assignment $assignment): RedirectResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'parent_id' => ['required', 'exists:comments,id'],
        ]);

        $admin = $request->user();

        Comment::create([
            'assignment_id' => $assignment->id,
            'user_id' => $admin->id,
            'parent_id' => $request->parent_id,
            'content' => $request->content,
        ]);

        $parentComment = Comment::find($request->parent_id);
        $studentId = $parentComment->user_id;
        $course = $assignment->lesson->course;

        HomeworkNotification::create([
            'user_id' => $studentId,
            'type' => 'reply',
            'course_name' => $course->name,
            'course_id' => $course->id,
            'lesson_id' => $assignment->lesson_id,
            'is_read' => false,
        ]);

        return redirect()->back()->with('success', '回覆已送出');
    }

    public function updateComment(Request $request, Assignment $assignment, Comment $comment): RedirectResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $comment->update([
            'content' => $request->content,
            'is_edited' => true,
        ]);

        return redirect()->back()->with('success', '已更新');
    }

    public function destroyComment(Assignment $assignment, Comment $comment): RedirectResponse
    {
        $comment->delete();

        return redirect()->back()->with('success', '已刪除');
    }

    public function markComplete(Assignment $assignment, User $user): RedirectResponse
    {
        $result = $this->assignmentService->markComplete($user, $assignment);

        if (isset($result['success']) && !$result['success']) {
            return redirect()->back()->withErrors(['complete' => $result['error']]);
        }

        return redirect()->back()->with('success', '已標記完成，積分 +100');
    }

    private function getAssignmentsMap(): array
    {
        return Assignment::with('lesson.course')
            ->withCount('completions')
            ->get()
            ->keyBy('lesson_id')
            ->map(fn ($a) => [
                'id' => $a->id,
                'md_content' => $a->md_content,
                'is_published' => $a->is_published,
                'lesson_id' => $a->lesson_id,
                'lesson_title' => $a->lesson->title,
                'course_name' => $a->lesson->course->name,
                'course_id' => $a->lesson->course->id,
                'completions_count' => $a->completions_count,
            ])
            ->values()
            ->toArray();
    }
}
