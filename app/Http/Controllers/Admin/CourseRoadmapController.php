<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseRoadmapRequest;
use App\Models\Course;
use App\Services\CourseRoadmapService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Roadmap authoring for one course (004 US7).
 */
class CourseRoadmapController extends Controller
{
    public function __construct(protected CourseRoadmapService $roadmapService) {}

    public function edit(Course $course): Response
    {
        $stages = $course->roadmapStages()
            ->with(['checkpoints' => fn ($q) => $q->withCount('completions')])
            ->get()
            ->map(fn ($stage) => [
                'id'             => $stage->id,
                'title'          => $stage->title,
                'description_md' => $stage->description_md,
                'checkpoints'    => $stage->checkpoints->map(fn ($checkpoint) => [
                    'id'    => $checkpoint->id,
                    'label' => $checkpoint->label,
                    // Drives the "N learner records will be deleted" warning (D23);
                    // the page already has it, so deleting needs no extra round trip.
                    'completed_count' => $checkpoint->completions_count,
                ]),
            ]);

        return Inertia::render('Admin/Courses/Roadmap', [
            'course' => [
                'id'            => $course->id,
                'name'          => $course->name,
                'roadmap_title' => $course->roadmap_title,
            ],
            'stages' => $stages,
        ]);
    }

    public function update(CourseRoadmapRequest $request, Course $course): RedirectResponse
    {
        $this->roadmapService->sync($course, $request->validated());

        return redirect()
            ->route('admin.courses.roadmap.edit', $course->id)
            ->with('success', 'Roadmap 已儲存');
    }
}
