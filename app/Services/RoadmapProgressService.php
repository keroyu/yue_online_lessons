<?php

namespace App\Services;

use App\Models\Course;
use App\Models\RoadmapCheckpointCompletion;
use App\Models\User;

/**
 * Read-side assembly of a course roadmap for one learner (003 US11 / D34).
 *
 * Three callers need the same shape — the classroom page, the admin modal and
 * anything added later — so the assembly lives here. Writes (firstOrCreate /
 * delete) stay in the controller; there is no logic there to wrap.
 */
class RoadmapProgressService
{
    /**
     * Null when the course has no roadmap at all (004 FR-019).
     */
    public function boardFor(Course $course, User $user): ?array
    {
        $stages = $course->roadmapStages()->with('checkpoints')->get();

        if ($stages->isEmpty()) {
            return null;
        }

        // Queried directly rather than via a User relation: User.php is owned by
        // 001-auth-account and this needs no touchpoint there.
        $completedIds = RoadmapCheckpointCompletion::where('user_id', $user->id)
            ->whereIn(
                'course_roadmap_checkpoint_id',
                $stages->flatMap(fn ($stage) => $stage->checkpoints->pluck('id'))
            )
            ->pluck('course_roadmap_checkpoint_id')
            ->all();

        $completedLookup = array_flip($completedIds);

        $mapped = $stages->map(function ($stage) use ($completedLookup) {
            $checkpoints = $stage->checkpoints->map(fn ($checkpoint) => [
                'id'        => $checkpoint->id,
                'label'     => $checkpoint->label,
                'completed' => isset($completedLookup[$checkpoint->id]),
            ]);

            return [
                'id'              => $stage->id,
                'title'           => $stage->title,
                'description_md'  => $stage->description_md,
                'checkpoints'     => $checkpoints->values()->all(),
                'completed_count' => $checkpoints->where('completed', true)->count(),
                'total'           => $checkpoints->count(),
            ];
        });

        return [
            'title'           => $course->roadmap_title ?: 'Roadmap',
            'stages'          => $mapped->values()->all(),
            'completed_count' => $mapped->sum('completed_count'),
            'total'           => $mapped->sum('total'),
        ];
    }
}
