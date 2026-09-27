<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\DripService;
use App\Services\LessonNotificationService;
use App\Services\VideoEmbedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(
        protected VideoEmbedService $videoEmbedService,
        protected DripService $dripService,
        protected LessonNotificationService $notifications,
    ) {}

    /**
     * Store a newly created lesson.
     */
    public function store(StoreLessonRequest $request, Course $course): RedirectResponse
    {
        $notifyMembers = $request->boolean('notify_members');
        $data = $request->safe()->except(['notify_members']);

        // Ensure duration_seconds is never null (DB NOT NULL constraint)
        $data['duration_seconds'] = $data['duration_seconds'] ?? 0;

        // Parse video URL if provided
        if (!empty($data['video_url'])) {
            $videoInfo = $this->videoEmbedService->parse($data['video_url']);
            if ($videoInfo) {
                $data['video_platform'] = $videoInfo['platform'];
                $data['video_id'] = $videoInfo['video_id'];
            }
        }

        // Calculate sort order
        if (!empty($data['chapter_id'])) {
            $maxSortOrder = Lesson::where('chapter_id', $data['chapter_id'])->max('sort_order') ?? 0;
        } else {
            $maxSortOrder = Lesson::where('course_id', $course->id)
                ->whereNull('chapter_id')
                ->max('sort_order') ?? 0;
        }
        $data['sort_order'] = $maxSortOrder + 1;

        // A drip course running an explicit schedule must not gain a lesson
        // with no send day: the fallback formula (position x interval) would
        // put it before the lesson it follows, and the strictly-increasing
        // check only runs on the course form (010 FR-038).
        if ($course->course_type === 'drip') {
            $lastDay = $course->lessons()->max('drip_day');

            if ($lastDay !== null) {
                $data['drip_day'] = $lastDay + ($course->drip_interval_days ?: 7);
            }
        }

        $lesson = $course->lessons()->create($data);

        $this->updateCourseDuration($course);

        // Reactivate completed subscribers so they receive the new lesson
        if ($course->course_type === 'drip') {
            $this->dripService->reactivateCompletedSubscriptions($course);
        }

        return redirect()
            ->route('admin.chapters.index', $course)
            ->with('success', $this->notifyAndDescribe($lesson, $notifyMembers, '小節建立成功'));
    }

    /**
     * Update the specified lesson.
     */
    public function update(StoreLessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $notifyMembers = $request->boolean('notify_members');
        // Explicit, not "it happens not to be in $fillable" (D27).
        $data = $request->safe()->except(['notify_members']);

        // Ensure duration_seconds is never null (DB NOT NULL constraint)
        $data['duration_seconds'] = $data['duration_seconds'] ?? 0;

        // Parse video URL if provided
        if (!empty($data['video_url'])) {
            $videoInfo = $this->videoEmbedService->parse($data['video_url']);
            if ($videoInfo) {
                $data['video_platform'] = $videoInfo['platform'];
                $data['video_id'] = $videoInfo['video_id'];
            }
        } else {
            $data['video_platform'] = null;
            $data['video_id'] = null;
            $data['video_url'] = null;
        }

        $lesson->update($data);

        $this->updateCourseDuration($lesson->course);

        return redirect()
            ->route('admin.chapters.index', $lesson->course_id)
            ->with('success', $this->notifyAndDescribe($lesson, $notifyMembers, '小節更新成功'));
    }

    /**
     * Notify holders if asked, and fold the real outcome into the flash
     * message. Silence here is what let a multi-plan course report success
     * while mailing nobody (FR-025 / FR-026).
     */
    private function notifyAndDescribe(Lesson $lesson, bool $notifyMembers, string $base): string
    {
        $course = $lesson->course;

        if (!$notifyMembers || $course->status === 'draft' || $course->course_type === 'drip') {
            return $base;
        }

        $result = $this->notifications->notify($lesson);
        $message = "{$base}，已通知 {$result['sent']} 位學員";

        if ($result['sent'] === 0 && $reason = $this->notifications->emptyReason($lesson)) {
            $message .= "（{$reason}）";
        }

        if ($result['failed'] > 0) {
            $message .= "；{$result['failed']} 封寄送失敗，詳見 log";
        }

        return $message;
    }

    /**
     * Remove the specified lesson.
     */
    public function destroy(Lesson $lesson): RedirectResponse
    {
        $course = $lesson->course;

        // Delete progress records for this lesson
        $lesson->progress()->delete();
        $lesson->delete();

        $this->updateCourseDuration($course);

        return redirect()
            ->route('admin.chapters.index', $course->id)
            ->with('success', '小節已刪除');
    }

    /**
     * Recalculate and update course duration_minutes from video lessons.
     */
    private function updateCourseDuration(Course $course): void
    {
        $totalSeconds = $course->lessons()
            ->whereNotNull('video_id')
            ->sum('duration_seconds');

        $course->update([
            'duration_minutes' => (int) round($totalSeconds / 60),
        ]);
    }

    /**
     * Reorder lessons for a course.
     */
    public function reorder(Request $request, Course $course): RedirectResponse
    {
        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:lessons,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
            'items.*.chapter_id' => ['nullable', 'integer', 'exists:chapters,id'],
        ]);

        foreach ($request->items as $item) {
            Lesson::where('id', $item['id'])
                ->where('course_id', $course->id)
                ->update([
                    'sort_order' => $item['sort_order'],
                    'chapter_id' => $item['chapter_id'] ?? null,
                ]);
        }

        return back();
    }
}
