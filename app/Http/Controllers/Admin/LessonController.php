<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonRequest;
use App\Mail\LessonAddedNotification;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Purchase;
use App\Services\DripService;
use App\Services\VideoEmbedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LessonController extends Controller
{
    public function __construct(
        protected VideoEmbedService $videoEmbedService,
        protected DripService $dripService,
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

        $lesson = $course->lessons()->create($data);

        $this->updateCourseDuration($course);

        // Reactivate completed subscribers so they receive the new lesson
        if ($course->course_type === 'drip') {
            $this->dripService->reactivateCompletedSubscriptions($course);
        }

        // Send notification email to course owners (standard courses only, published only)
        if ($notifyMembers && $course->status !== 'draft' && $course->course_type !== 'drip') {
            $recipients = Purchase::where('course_id', $course->id)
                ->where('status', '!=', 'refunded')
                ->where('type', '!=', 'system_assigned')
                ->with('user')
                ->get();

            foreach ($recipients as $purchase) {
                if ($purchase->user && $purchase->user->email) {
                    try {
                        Mail::to($purchase->user->email)
                            ->send(new LessonAddedNotification($course, $lesson));
                    } catch (\Exception $e) {
                        Log::error('Failed to send lesson notification', [
                            'purchase_id' => $purchase->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return redirect()
            ->route('admin.chapters.index', $course)
            ->with('success', '小節建立成功');
    }

    /**
     * Update the specified lesson.
     */
    public function update(StoreLessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validated();

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
            ->with('success', '小節更新成功');
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
