<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseRoadmapCheckpoint;
use App\Models\CourseRoadmapStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Whole-document roadmap save (004 US7 / FR-020–FR-022).
 *
 * The rule this class exists to enforce: items that come back with an id are
 * UPDATED, never recreated. Learner completions hang off checkpoint ids, so a
 * delete-all/insert-all save would silently wipe every student's progress.
 */
class CourseRoadmapService
{
    /**
     * @param  array{roadmap_title?: ?string, stages: array<int, array>}  $data
     */
    public function sync(Course $course, array $data): void
    {
        DB::transaction(function () use ($course, $data) {
            $course->update(['roadmap_title' => $data['roadmap_title'] ?? null]);

            $stages = $data['stages'] ?? [];
            $keptStageIds = [];

            foreach ($stages as $position => $stageData) {
                $stage = $this->upsertStage($course, $stageData, $position);
                $keptStageIds[] = $stage->id;

                $this->syncCheckpoints($stage, $stageData['checkpoints'] ?? []);
            }

            // Stages absent from the payload are gone; the FK cascade takes their
            // checkpoints and every learner completion with them.
            $course->roadmapStages()
                ->whereNotIn('id', $keptStageIds ?: [0])
                ->delete();
        });
    }

    private function upsertStage(Course $course, array $data, int $position): CourseRoadmapStage
    {
        $attributes = [
            'title'          => $data['title'],
            'description_md' => $data['description_md'] ?? null,
            // sort_order always comes from the array position, never from the
            // client-supplied value (FR-022).
            'sort_order'     => $position,
        ];

        if (empty($data['id'])) {
            return $course->roadmapStages()->create($attributes);
        }

        $stage = $course->roadmapStages()->find($data['id']);

        if (!$stage) {
            throw ValidationException::withMessages([
                'stages' => '階段資料有誤：找不到屬於本課程的階段（id ' . $data['id'] . '）',
            ]);
        }

        $stage->update($attributes);

        return $stage;
    }

    /**
     * @param  array<int, array>  $checkpoints
     */
    private function syncCheckpoints(CourseRoadmapStage $stage, array $checkpoints): void
    {
        $keptIds = [];

        foreach ($checkpoints as $position => $data) {
            $attributes = [
                'label'      => $data['label'],
                'sort_order' => $position,
            ];

            if (empty($data['id'])) {
                $keptIds[] = $stage->checkpoints()->create($attributes)->id;
                continue;
            }

            $checkpoint = $stage->checkpoints()->find($data['id']);

            if (!$checkpoint) {
                throw ValidationException::withMessages([
                    'stages' => '檢核項目資料有誤：找不到屬於此階段的項目（id ' . $data['id'] . '）',
                ]);
            }

            $checkpoint->update($attributes);
            $keptIds[] = $checkpoint->id;
        }

        CourseRoadmapCheckpoint::where('course_roadmap_stage_id', $stage->id)
            ->whereNotIn('id', $keptIds ?: [0])
            ->delete();
    }
}
