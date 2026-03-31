<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearningController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get user's purchases with course data
        $purchases = $user->purchases()
            ->with(['course.lessons'])
            ->paidStatus()
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all user's progress records
        $progressMap = LessonProgress::where('user_id', $user->id)
            ->pluck('lesson_id')
            ->flip()
            ->toArray();

        // Map to MyCourse format for frontend
        $courses = $purchases->map(function ($purchase) use ($progressMap, $user) {
            $course = $purchase->course;
            $progress = $user->getCourseProgressSummary($course, $progressMap);

            return [
                'id' => $course->id,
                'name' => $course->name,
                'thumbnail' => $course->thumbnail_url,
                'instructor_name' => $course->instructor_name,
                'progress_percent' => $progress['progress_percent'],
                'purchased_at' => $purchase->created_at->toIso8601String(),
            ];
        });

        return Inertia::render('Member/Learning', [
            'courses' => $courses,
        ]);
    }
}
