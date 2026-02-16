<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\VideoEmbedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(
        protected VideoEmbedService $videoEmbedService
    ) {}

    /**
     * Store a newly created lesson.
     */
    public function store(StoreLessonRequest $request, Course $course): RedirectResponse
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

        $course->lessons()->create($data);

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

        return redirect()
            ->route('admin.chapters.index', $lesson->course_id)
            ->with('success', '小節更新成功');
    }

    /**
     * Remove the specified lesson.
     */
    public function destroy(Lesson $lesson): RedirectResponse
    {
        $courseId = $lesson->course_id;

        // Delete progress records for this lesson
        $lesson->progress()->delete();
        $lesson->delete();

        return redirect()
            ->route('admin.chapters.index', $courseId)
            ->with('success', '小節已刪除');
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
