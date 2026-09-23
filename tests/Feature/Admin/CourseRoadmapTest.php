<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\CourseRoadmapCheckpoint;
use App\Models\CourseRoadmapStage;
use App\Models\RoadmapCheckpointCompletion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 004 US7 / FR-020–FR-022 — whole-document roadmap save.
 *
 * The one thing these tests exist to prevent: a "delete everything, insert
 * everything" save. Checkpoint ids are what learner completions point at, so
 * rebuilding them silently wipes every student's roadmap progress.
 */
class CourseRoadmapTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function course(string $slug = 'roadmap-course'): Course
    {
        return Course::create([
            'name'            => 'Roadmap Course',
            'slug'            => $slug,
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 1000,
            'instructor_name' => 'Tester',
            'type'            => 'full',
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ]);
    }

    /** Seed one stage with two checkpoints, the second already ticked by $user. */
    private function seedRoadmap(Course $course, User $user): array
    {
        $stage = CourseRoadmapStage::create([
            'course_id'      => $course->id,
            'title'          => '01｜找到可變現的知識方向',
            'description_md' => '選出一個有能力解決的問題。',
            'sort_order'     => 0,
        ]);

        $first = CourseRoadmapCheckpoint::create([
            'course_roadmap_stage_id' => $stage->id,
            'label'                   => '列出至少 10 項能力',
            'sort_order'              => 0,
        ]);

        $second = CourseRoadmapCheckpoint::create([
            'course_roadmap_stage_id' => $stage->id,
            'label'                   => '選出 3 個可能變現的方向',
            'sort_order'              => 1,
        ]);

        RoadmapCheckpointCompletion::create([
            'user_id'                      => $user->id,
            'course_roadmap_checkpoint_id' => $second->id,
        ]);

        return [$stage, $first, $second];
    }

    public function test_editing_an_existing_roadmap_keeps_ids_and_learner_progress(): void
    {
        $student = User::factory()->create();
        $course = $this->course();
        [$stage, $first, $second] = $this->seedRoadmap($course, $student);

        $this->actingAs($this->admin())
            ->put("/admin/courses/{$course->id}/roadmap", [
                'roadmap_title' => '知識變現 Roadmap',
                'stages' => [
                    [
                        'id'             => $stage->id,
                        'title'          => '01｜找到可變現的知識方向（改過）',
                        'description_md' => '改過的說明',
                        'checkpoints'    => [
                            ['id' => $first->id, 'label' => '列出至少 10 項能力'],
                            ['id' => $second->id, 'label' => '選出 3 個可能變現的方向'],
                            ['label' => '新增的第三項'],
                        ],
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertSame('知識變現 Roadmap', $course->fresh()->roadmap_title);
        $this->assertSame('01｜找到可變現的知識方向（改過）', $stage->fresh()->title);

        // The whole point: the ticked checkpoint kept its id, so the completion survived.
        $this->assertDatabaseHas('roadmap_checkpoint_completions', [
            'user_id'                      => $student->id,
            'course_roadmap_checkpoint_id' => $second->id,
        ]);
        $this->assertSame(3, $stage->fresh()->checkpoints()->count());
    }

    public function test_removing_a_checkpoint_deletes_it_with_its_completions(): void
    {
        $student = User::factory()->create();
        $course = $this->course();
        [$stage, $first, $second] = $this->seedRoadmap($course, $student);

        $this->actingAs($this->admin())
            ->put("/admin/courses/{$course->id}/roadmap", [
                'roadmap_title' => null,
                'stages' => [
                    [
                        'id'          => $stage->id,
                        'title'       => $stage->title,
                        'checkpoints' => [
                            ['id' => $first->id, 'label' => $first->label],
                        ],
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('course_roadmap_checkpoints', ['id' => $second->id]);
        $this->assertDatabaseMissing('roadmap_checkpoint_completions', [
            'course_roadmap_checkpoint_id' => $second->id,
        ]);
        $this->assertDatabaseHas('course_roadmap_checkpoints', ['id' => $first->id]);
    }

    public function test_a_stage_id_from_another_course_is_rejected(): void
    {
        $student = User::factory()->create();
        $course = $this->course();
        $other  = $this->course('other-course');
        [$otherStage] = $this->seedRoadmap($other, $student);

        $this->actingAs($this->admin())
            ->put("/admin/courses/{$course->id}/roadmap", [
                'stages' => [
                    [
                        'id'          => $otherStage->id,
                        'title'       => '偷改別門課',
                        'checkpoints' => [],
                    ],
                ],
            ])
            ->assertSessionHasErrors('stages');

        $this->assertSame('01｜找到可變現的知識方向', $otherStage->fresh()->title);
    }

    public function test_sort_order_comes_from_array_position_not_the_payload(): void
    {
        $course = $this->course();

        $this->actingAs($this->admin())
            ->put("/admin/courses/{$course->id}/roadmap", [
                'stages' => [
                    ['title' => '第一關', 'sort_order' => 99, 'checkpoints' => [
                        ['label' => 'A', 'sort_order' => 77],
                        ['label' => 'B'],
                    ]],
                    ['title' => '第二關', 'sort_order' => 5, 'checkpoints' => []],
                ],
            ])
            ->assertRedirect();

        $stages = $course->fresh()->roadmapStages;
        $this->assertSame([0, 1], $stages->pluck('sort_order')->all());
        $this->assertSame(['第一關', '第二關'], $stages->pluck('title')->all());
        $this->assertSame([0, 1], $stages->first()->checkpoints->pluck('sort_order')->all());
    }
}
