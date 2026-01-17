<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    /**
     * Display the classroom page for a course.
     */
    public function show(Request $request, Course $course): Response|JsonResponse
    {
        $user = $request->user();

        // Check if user has purchased this course
        $hasPurchased = $user->purchases()
            ->where('course_id', $course->id)
            ->where('status', 'paid')
            ->exists();

        if (!$hasPurchased) {
            return Inertia::render('Member/ClassroomUnauthorized', [
                'course' => [
                    'id' => $course->id,
                    'name' => $course->name,
                ],
                'message' => '您尚未購買此課程',
            ]);
        }

        // Get user's completed lesson IDs
        $completedLessonIds = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $course->lessons()->pluck('id'))
            ->pluck('lesson_id')
            ->toArray();

        // Load chapters with their lessons
        $chapters = $course->chapters()
            ->with(['lessons' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($chapter) => [
                'id' => $chapter->id,
                'title' => $chapter->title,
                'lessons' => $chapter->lessons->map(fn ($lesson) => $this->formatLesson($lesson, $completedLessonIds)),
            ]);

        // Get standalone lessons (no chapter)
        $standaloneLessons = $course->lessons()
            ->whereNull('chapter_id')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($lesson) => $this->formatLesson($lesson, $completedLessonIds));

        // Find first uncompleted lesson or first lesson
        $allLessons = $course->lessons()->orderBy('sort_order')->get();
        $currentLesson = $allLessons->first(fn ($lesson) => !in_array($lesson->id, $completedLessonIds))
            ?? $allLessons->first();

        return Inertia::render('Member/Classroom', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
            ],
            'chapters' => $chapters,
            'standaloneLessons' => $standaloneLessons,
            'currentLesson' => $currentLesson ? $this->formatLessonFull($currentLesson, $completedLessonIds) : null,
            'hasContent' => $allLessons->count() > 0,
        ]);
    }

    /**
     * Mark a lesson as complete.
     */
    public function markComplete(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        $user = $request->user();

        // Verify lesson belongs to course
        if ($lesson->course_id !== $course->id) {
            return response()->json(['error' => '小節不屬於此課程'], 404);
        }

        // Verify user has purchased the course
        $hasPurchased = $user->purchases()
            ->where('course_id', $course->id)
            ->where('status', 'paid')
            ->exists();

        if (!$hasPurchased) {
            return response()->json(['error' => '您尚未購買此課程'], 403);
        }

        // Create progress record (or ignore if exists)
        LessonProgress::firstOrCreate([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        return response()->json(['success' => true, 'is_completed' => true]);
    }

    /**
     * Mark a lesson as incomplete.
     */
    public function markIncomplete(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        $user = $request->user();

        // Verify lesson belongs to course
        if ($lesson->course_id !== $course->id) {
            return response()->json(['error' => '小節不屬於此課程'], 404);
        }

        // Verify user has purchased the course
        $hasPurchased = $user->purchases()
            ->where('course_id', $course->id)
            ->where('status', 'paid')
            ->exists();

        if (!$hasPurchased) {
            return response()->json(['error' => '您尚未購買此課程'], 403);
        }

        // Delete progress record
        LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->delete();

        return response()->json(['success' => true, 'is_completed' => false]);
    }

    /**
     * Format lesson for list display.
     */
    private function formatLesson(Lesson $lesson, array $completedLessonIds): array
    {
        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'duration_formatted' => $lesson->duration_formatted,
            'has_video' => $lesson->has_video,
            'is_completed' => in_array($lesson->id, $completedLessonIds),
        ];
    }

    /**
     * Format lesson with full content for display.
     */
    private function formatLessonFull(Lesson $lesson, array $completedLessonIds): array
    {
        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'duration_formatted' => $lesson->duration_formatted,
            'has_video' => $lesson->has_video,
            'video_platform' => $lesson->video_platform,
            'video_id' => $lesson->video_id,
            'embed_url' => $lesson->embed_url,
            'html_content' => $lesson->html_content,
            'is_completed' => in_array($lesson->id, $completedLessonIds),
        ];
    }
}
