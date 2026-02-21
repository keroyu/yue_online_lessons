<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreChapterRequest;
use App\Models\Chapter;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChapterController extends Controller
{
    /**
     * Display the chapters page for a course.
     */
    public function index(Course $course): Response
    {
        $chapters = $course->chapters()
            ->with(['lessons' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($chapter) => [
                'id' => $chapter->id,
                'title' => $chapter->title,
                'sort_order' => $chapter->sort_order,
                'lessons' => $chapter->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'duration_formatted' => $lesson->duration_formatted,
                    'duration_seconds' => $lesson->duration_seconds,
                    'has_video' => $lesson->has_video,
                    'video_url' => $lesson->video_url,
                    'video_platform' => $lesson->video_platform,
                    'html_content' => $lesson->html_content,
                    'sort_order' => $lesson->sort_order,
                    'promo_delay_seconds' => $lesson->promo_delay_seconds,
                    'promo_html' => $lesson->promo_html,
                    'reward_html' => $lesson->reward_html,
                ]),
            ]);

        // Get standalone lessons (without chapter)
        $standaloneLessons = $course->lessons()
            ->whereNull('chapter_id')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'duration_formatted' => $lesson->duration_formatted,
                'duration_seconds' => $lesson->duration_seconds,
                'has_video' => $lesson->has_video,
                'video_url' => $lesson->video_url,
                'video_platform' => $lesson->video_platform,
                'html_content' => $lesson->html_content,
                'sort_order' => $lesson->sort_order,
                'promo_delay_seconds' => $lesson->promo_delay_seconds,
                'promo_html' => $lesson->promo_html,
                'reward_html' => $lesson->reward_html,
            ]);

        return Inertia::render('Admin/Courses/Chapters', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'course_type' => $course->course_type,
            ],
            'chapters' => $chapters,
            'standaloneLessons' => $standaloneLessons,
        ]);
    }

    /**
     * Store a newly created chapter.
     */
    public function store(StoreChapterRequest $request, Course $course): RedirectResponse
    {
        $maxSortOrder = $course->chapters()->max('sort_order') ?? 0;

        $course->chapters()->create([
            'title' => $request->validated('title'),
            'sort_order' => $maxSortOrder + 1,
        ]);

        return redirect()
            ->route('admin.chapters.index', $course)
            ->with('success', '章節建立成功');
    }

    /**
     * Update the specified chapter.
     */
    public function update(StoreChapterRequest $request, Chapter $chapter): RedirectResponse
    {
        $chapter->update([
            'title' => $request->validated('title'),
        ]);

        return redirect()
            ->route('admin.chapters.index', $chapter->course_id)
            ->with('success', '章節更新成功');
    }

    /**
     * Remove the specified chapter.
     */
    public function destroy(Chapter $chapter): RedirectResponse
    {
        $courseId = $chapter->course_id;

        // Delete all lessons under this chapter
        $chapter->lessons()->delete();
        $chapter->delete();

        return redirect()
            ->route('admin.chapters.index', $courseId)
            ->with('success', '章節已刪除');
    }

    /**
     * Reorder chapters for a course.
     */
    public function reorder(Request $request, Course $course): RedirectResponse
    {
        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:chapters,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->items as $item) {
            Chapter::where('id', $item['id'])
                ->where('course_id', $course->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return back();
    }
}
