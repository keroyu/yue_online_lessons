<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\DripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function __construct(protected DripService $dripService) {}

    /**
     * Display the classroom page for a course.
     */
    public function show(Request $request, Course $course): Response|JsonResponse
    {
        $user = $request->user();
        $isDrip = $course->course_type === 'drip';
        $dripSubscription = null;

        // Check access: purchased OR drip subscription
        $hasPurchased = $user->purchases()
            ->where('course_id', $course->id)
            ->where('status', 'paid')
            ->exists();

        if ($isDrip) {
            $dripSubscription = DripSubscription::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
        }

        $hasAccess = $hasPurchased || ($dripSubscription !== null);

        if (!$hasAccess) {
            return Inertia::render('Member/ClassroomUnauthorized', [
                'course' => [
                    'id' => $course->id,
                    'name' => $course->name,
                ],
                'message' => $isDrip ? '您尚未訂閱此課程' : '您尚未購買此課程',
            ]);
        }

        // Get user's completed lesson IDs
        $completedLessonIds = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $course->lessons()->pluck('id'))
            ->pluck('lesson_id')
            ->toArray();

        // For drip courses, calculate unlock status per lesson
        $lessonUnlockMap = [];
        if ($isDrip && $dripSubscription) {
            $allOrderedLessons = $course->lessons()->orderBy('sort_order')->get();
            foreach ($allOrderedLessons as $lesson) {
                $lessonUnlockMap[$lesson->id] = [
                    'is_unlocked' => $this->dripService->isLessonUnlocked($dripSubscription, $lesson),
                    'unlock_in_days' => $this->dripService->daysUntilUnlock($dripSubscription, $lesson),
                ];
            }
        }

        // Load chapters with their lessons
        $chapters = $course->chapters()
            ->with(['lessons' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($chapter) => [
                'id' => $chapter->id,
                'title' => $chapter->title,
                'lessons' => $chapter->lessons->map(fn ($lesson) => $this->formatLesson($lesson, $completedLessonIds, $lessonUnlockMap)),
            ]);

        // Get standalone lessons (no chapter)
        $standaloneLessons = $course->lessons()
            ->whereNull('chapter_id')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($lesson) => $this->formatLesson($lesson, $completedLessonIds, $lessonUnlockMap));

        // Find the requested lesson, or first uncompleted, or first lesson
        $allLessons = $course->lessons()->orderBy('sort_order')->get();
        $requestedLessonId = $request->input('lesson_id');

        if ($requestedLessonId) {
            $currentLesson = $allLessons->first(fn ($lesson) => $lesson->id == $requestedLessonId);
            // For drip courses, block access to locked lessons
            if ($isDrip && $currentLesson && isset($lessonUnlockMap[$currentLesson->id]) && !$lessonUnlockMap[$currentLesson->id]['is_unlocked']) {
                $currentLesson = null;
            }
        }

        if (!isset($currentLesson) || !$currentLesson) {
            // For drip courses, find first unlocked uncompleted lesson
            if ($isDrip) {
                $currentLesson = $allLessons->first(fn ($lesson) =>
                    (!isset($lessonUnlockMap[$lesson->id]) || $lessonUnlockMap[$lesson->id]['is_unlocked'])
                    && !in_array($lesson->id, $completedLessonIds)
                ) ?? $allLessons->first(fn ($lesson) =>
                    !isset($lessonUnlockMap[$lesson->id]) || $lessonUnlockMap[$lesson->id]['is_unlocked']
                );
            } else {
                $currentLesson = $allLessons->first(fn ($lesson) => !in_array($lesson->id, $completedLessonIds))
                    ?? $allLessons->first();
            }
        }

        $pageProps = [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'is_drip' => $isDrip,
            ],
            'chapters' => $chapters,
            'standaloneLessons' => $standaloneLessons,
            'currentLesson' => $currentLesson ? $this->formatLessonFull($currentLesson, $completedLessonIds, $lessonUnlockMap) : null,
            'hasContent' => $allLessons->count() > 0,
        ];

        // Add drip subscription info
        if ($isDrip && $dripSubscription) {
            $pageProps['dripSubscription'] = [
                'status' => $dripSubscription->status,
                'subscribed_at' => $dripSubscription->subscribed_at->toDateString(),
                'emails_sent' => $dripSubscription->emails_sent,
            ];
        }

        return Inertia::render('Member/Classroom', $pageProps);
    }

    /**
     * Check if user has access to a course (purchased or drip subscribed).
     */
    private function hasAccess(int $userId, Course $course): bool
    {
        $hasPurchased = $course->purchases()
            ->where('user_id', $userId)
            ->where('status', 'paid')
            ->exists();

        if ($hasPurchased) {
            return true;
        }

        if ($course->course_type === 'drip') {
            return DripSubscription::where('user_id', $userId)
                ->where('course_id', $course->id)
                ->exists();
        }

        return false;
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

        if (!$this->hasAccess($user->id, $course)) {
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

        if (!$this->hasAccess($user->id, $course)) {
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
    private function formatLesson(Lesson $lesson, array $completedLessonIds, array $lessonUnlockMap = []): array
    {
        $data = [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'duration_formatted' => $lesson->duration_formatted,
            'has_video' => $lesson->has_video,
            'is_completed' => in_array($lesson->id, $completedLessonIds),
        ];

        if (isset($lessonUnlockMap[$lesson->id])) {
            $data['is_unlocked'] = $lessonUnlockMap[$lesson->id]['is_unlocked'];
            $data['unlock_in_days'] = $lessonUnlockMap[$lesson->id]['unlock_in_days'];
        }

        return $data;
    }

    /**
     * Format lesson with full content for display.
     */
    private function formatLessonFull(Lesson $lesson, array $completedLessonIds, array $lessonUnlockMap = []): array
    {
        $isLocked = isset($lessonUnlockMap[$lesson->id]) && !$lessonUnlockMap[$lesson->id]['is_unlocked'];

        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'duration_formatted' => $lesson->duration_formatted,
            'has_video' => $isLocked ? false : $lesson->has_video,
            'video_platform' => $isLocked ? null : $lesson->video_platform,
            'video_id' => $isLocked ? null : $lesson->video_id,
            'embed_url' => $isLocked ? null : $lesson->embed_url,
            'html_content' => $isLocked ? null : $lesson->html_content,
            'is_completed' => in_array($lesson->id, $completedLessonIds),
            'is_unlocked' => $isLocked ? false : true,
            'unlock_in_days' => $lessonUnlockMap[$lesson->id]['unlock_in_days'] ?? 0,
            'promo_delay_seconds' => $isLocked ? null : $lesson->promo_delay_seconds,
            'promo_html' => $isLocked ? null : $lesson->promo_html,
        ];
    }
}
