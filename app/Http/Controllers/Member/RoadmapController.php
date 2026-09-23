<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseRoadmapCheckpoint;
use App\Models\RoadmapCheckpointCompletion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Learner self-check on a course roadmap (003 US11).
 *
 * Note the deliberate difference from lesson progress (003 FR-026): members may
 * untick here. A roadmap item is a self-assessment, not an achievement — it
 * grants no points and feeds no completion rate, so there is nothing to protect.
 */
class RoadmapController extends Controller
{
    public function complete(Request $request, Course $course, CourseRoadmapCheckpoint $checkpoint): JsonResponse
    {
        $this->assertBelongsToCourse($course, $checkpoint);

        RoadmapCheckpointCompletion::firstOrCreate([
            'user_id'                      => $request->user()->id,
            'course_roadmap_checkpoint_id' => $checkpoint->id,
        ]);

        return response()->json(['completed' => true]);
    }

    public function uncomplete(Request $request, Course $course, CourseRoadmapCheckpoint $checkpoint): JsonResponse
    {
        $this->assertBelongsToCourse($course, $checkpoint);

        RoadmapCheckpointCompletion::where('user_id', $request->user()->id)
            ->where('course_roadmap_checkpoint_id', $checkpoint->id)
            ->delete();

        return response()->json(['completed' => false]);
    }

    /**
     * Walk checkpoint → stage → course (FR-029). Without this, a checkpoint id
     * from any other course would be writable through an accessible course.
     */
    private function assertBelongsToCourse(Course $course, CourseRoadmapCheckpoint $checkpoint): void
    {
        abort_unless($checkpoint->stage?->course_id === $course->id, 404);
    }
}
