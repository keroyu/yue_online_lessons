<?php

namespace Tests\Feature\Classroom;

use App\Models\Course;
use App\Models\CourseRoadmapCheckpoint;
use App\Models\CourseRoadmapStage;
use App\Models\Purchase;
use App\Models\User;
use App\Services\RoadmapProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 003 US11 / FR-028–FR-032 — learner self-check on the course roadmap.
 */
class RoadmapProgressTest extends TestCase
{
    use RefreshDatabase;

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

    private function buyer(Course $course): User
    {
        $user = User::factory()->create();

        Purchase::create([
            'user_id'   => $user->id,
            'course_id' => $course->id,
            'amount'    => $course->price,
            'status'    => 'paid',
            'type'      => 'purchase',
        ]);

        return $user;
    }

    private function checkpoint(Course $course, string $label = '列出至少 10 項能力'): CourseRoadmapCheckpoint
    {
        $stage = CourseRoadmapStage::create([
            'course_id'  => $course->id,
            'title'      => '01｜找到可變現的知識方向',
            'sort_order' => 0,
        ]);

        return CourseRoadmapCheckpoint::create([
            'course_roadmap_stage_id' => $stage->id,
            'label'                   => $label,
            'sort_order'              => 0,
        ]);
    }

    /**
     * The deliberate opposite of LessonProgressTest: lesson progress is
     * admin-only to roll back (FR-026), a roadmap tick is the learner's own.
     */
    public function test_a_member_can_tick_and_untick_their_own_checkpoint(): void
    {
        $course = $this->course();
        $checkpoint = $this->checkpoint($course);
        $user = $this->buyer($course);

        $this->actingAs($user)
            ->postJson("/member/classroom/{$course->id}/roadmap/{$checkpoint->id}")
            ->assertOk()
            ->assertJson(['completed' => true]);

        $this->assertDatabaseHas('roadmap_checkpoint_completions', [
            'user_id'                      => $user->id,
            'course_roadmap_checkpoint_id' => $checkpoint->id,
        ]);

        $this->actingAs($user)
            ->deleteJson("/member/classroom/{$course->id}/roadmap/{$checkpoint->id}")
            ->assertOk()
            ->assertJson(['completed' => false]);

        $this->assertDatabaseMissing('roadmap_checkpoint_completions', [
            'user_id'                      => $user->id,
            'course_roadmap_checkpoint_id' => $checkpoint->id,
        ]);
    }

    public function test_a_checkpoint_from_another_course_is_not_writable(): void
    {
        $course = $this->course();
        $other  = $this->course('other-course');
        $foreign = $this->checkpoint($other);
        $user = $this->buyer($course);

        $this->actingAs($user)
            ->postJson("/member/classroom/{$course->id}/roadmap/{$foreign->id}")
            ->assertNotFound();

        $this->assertDatabaseCount('roadmap_checkpoint_completions', 0);
    }

    public function test_ticking_twice_creates_only_one_row(): void
    {
        $course = $this->course();
        $checkpoint = $this->checkpoint($course);
        $user = $this->buyer($course);

        $this->actingAs($user)->postJson("/member/classroom/{$course->id}/roadmap/{$checkpoint->id}")->assertOk();
        $this->actingAs($user)->postJson("/member/classroom/{$course->id}/roadmap/{$checkpoint->id}")->assertOk();

        $this->assertDatabaseCount('roadmap_checkpoint_completions', 1);
    }

    public function test_a_course_without_a_roadmap_has_a_null_board(): void
    {
        $course = $this->course();
        $user = $this->buyer($course);

        $this->assertNull(app(RoadmapProgressService::class)->boardFor($course, $user));

        $this->actingAs($user)
            ->get("/member/classroom/{$course->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('roadmap', null));
    }

    public function test_the_board_reports_per_stage_and_total_progress(): void
    {
        $course = $this->course();
        $checkpoint = $this->checkpoint($course);
        CourseRoadmapCheckpoint::create([
            'course_roadmap_stage_id' => $checkpoint->course_roadmap_stage_id,
            'label'                   => '選出 3 個可能變現的方向',
            'sort_order'              => 1,
        ]);
        $user = $this->buyer($course);

        $this->actingAs($user)->postJson("/member/classroom/{$course->id}/roadmap/{$checkpoint->id}");

        $board = app(RoadmapProgressService::class)->boardFor($course->fresh(), $user);

        $this->assertSame(1, $board['completed_count']);
        $this->assertSame(2, $board['total']);
        $this->assertSame(1, $board['stages'][0]['completed_count']);
        $this->assertTrue($board['stages'][0]['checkpoints'][0]['completed']);
        $this->assertFalse($board['stages'][0]['checkpoints'][1]['completed']);
    }

    public function test_the_admin_endpoint_is_read_only_and_needs_learner_access(): void
    {
        $course = $this->course();
        $this->checkpoint($course);
        $admin = User::factory()->create(['role' => 'admin']);
        $stranger = User::factory()->create();
        $buyer = $this->buyer($course);

        // A user with no purchase is not a learner of this course.
        $this->actingAs($admin)
            ->getJson("/admin/homework/roadmap/{$course->id}/{$stranger->id}")
            ->assertNotFound();

        $this->actingAs($admin)
            ->getJson("/admin/homework/roadmap/{$course->id}/{$buyer->id}")
            ->assertOk()
            ->assertJsonPath('board.total', 1);

        // No write verb is routed here.
        $this->actingAs($admin)
            ->postJson("/admin/homework/roadmap/{$course->id}/{$buyer->id}")
            ->assertStatus(405);
    }
}
