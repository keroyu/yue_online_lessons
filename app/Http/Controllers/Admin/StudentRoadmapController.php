<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Services\RoadmapProgressService;
use Illuminate\Http\JsonResponse;

/**
 * Read-only view of one learner's roadmap, opened from the homework grading
 * list (003 US11 / FR-032 / D35). No write path exists on purpose.
 */
class StudentRoadmapController extends Controller
{
    public function __construct(protected RoadmapProgressService $roadmapProgressService) {}

    public function show(Course $course, User $user): JsonResponse
    {
        abort_unless($course->hasAccessForUser($user, includeAdmin: false), 404);

        return response()->json([
            'board' => $this->roadmapProgressService->boardFor($course, $user),
            'user'  => [
                'id'       => $user->id,
                'nickname' => $user->nickname,
            ],
        ]);
    }
}
