<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 008 FR-010 / FR-011 — the two admin paths that hand out a course for free
 * (gifting and member import) must name the plan they are handing out.
 *
 * A null course_plan_id on a paid purchase means "the whole course" to
 * Purchase::accessibleLessonIds(), so letting "the admin picked nothing" fall
 * through as null gives away the most expensive tier with no trace on screen.
 */
class MemberCoursePlanAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name'            => 'Planned Course',
            'slug'            => 'planned-' . uniqid(),
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 50000,
            'instructor_name' => 'Tester',
            'type'            => 'lecture',
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ], $overrides));
    }

    /** @return array{0: CoursePlan, 1: CoursePlan} */
    private function planned(Course $course): array
    {
        return [
            CoursePlan::create(['course_id' => $course->id, 'name' => '方案A', 'price' => 30000, 'sort_order' => 0]),
            CoursePlan::create(['course_id' => $course->id, 'name' => '方案B', 'price' => 80000, 'sort_order' => 1]),
        ];
    }

    // ── Import: pasted email list ──────────────────────────────────────────

    public function test_import_writes_the_selected_plan(): void
    {
        $course = $this->makeCourse();
        [$planA] = $this->planned($course);

        $this->actingAs($this->admin())
            ->postJson('/admin/members/import', [
                'emails'         => 'buyer@example.com',
                'course_id'      => $course->id,
                'course_plan_id' => $planA->id,
            ])
            ->assertOk()
            ->assertJson(['assigned_count' => 1]);

        $this->assertDatabaseHas('purchases', [
            'course_id'      => $course->id,
            'course_plan_id' => $planA->id,
            'type'           => 'lead_conversion',
        ]);
    }

    public function test_import_is_refused_when_a_planned_course_has_no_plan_selected(): void
    {
        $course = $this->makeCourse();
        $this->planned($course);

        $this->actingAs($this->admin())
            ->postJson('/admin/members/import', [
                'emails'    => 'buyer@example.com',
                'course_id' => $course->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('course_plan_id');

        // The guard runs before any write: no member, no purchase.
        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseMissing('users', ['email' => 'buyer@example.com']);
    }

    public function test_import_rejects_a_plan_from_another_course(): void
    {
        $course = $this->makeCourse();
        $other = $this->makeCourse();
        $foreign = CoursePlan::create(['course_id' => $other->id, 'name' => '別課方案', 'sort_order' => 0]);
        $this->planned($course);

        $this->actingAs($this->admin())
            ->postJson('/admin/members/import', [
                'emails'         => 'buyer@example.com',
                'course_id'      => $course->id,
                'course_plan_id' => $foreign->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('course_plan_id');

        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_import_rejects_a_plan_on_a_course_that_has_none(): void
    {
        $course = $this->makeCourse();
        $other = $this->makeCourse();
        $foreign = CoursePlan::create(['course_id' => $other->id, 'name' => '別課方案', 'sort_order' => 0]);

        $this->actingAs($this->admin())
            ->postJson('/admin/members/import', [
                'emails'         => 'buyer@example.com',
                'course_id'      => $course->id,
                'course_plan_id' => $foreign->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('course_plan_id');

        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_import_on_a_course_without_plans_keeps_a_null_plan(): void
    {
        $course = $this->makeCourse();

        $this->actingAs($this->admin())
            ->postJson('/admin/members/import', [
                'emails'    => 'buyer@example.com',
                'course_id' => $course->id,
            ])
            ->assertOk();

        $purchase = Purchase::where('course_id', $course->id)->firstOrFail();
        $this->assertNull($purchase->course_plan_id);
    }

    // ── Import: parsed CSV rows share the same guard ───────────────────────

    public function test_csv_rows_import_writes_the_selected_plan(): void
    {
        $course = $this->makeCourse();
        [, $planB] = $this->planned($course);

        $this->actingAs($this->admin())
            ->postJson('/admin/members/import', [
                'rows'           => [['email' => 'row@example.com', 'real_name' => '王小明', 'phone' => '0912345678']],
                'course_id'      => $course->id,
                'course_plan_id' => $planB->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('purchases', [
            'course_id'      => $course->id,
            'course_plan_id' => $planB->id,
        ]);
    }

    public function test_csv_rows_import_is_refused_when_no_plan_selected(): void
    {
        $course = $this->makeCourse();
        $this->planned($course);

        $this->actingAs($this->admin())
            ->postJson('/admin/members/import', [
                'rows'      => [['email' => 'row@example.com', 'real_name' => '王小明', 'phone' => '0912345678']],
                'course_id' => $course->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('course_plan_id');

        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseMissing('users', ['email' => 'row@example.com']);
    }

    // ── Gifting ────────────────────────────────────────────────────────────

    public function test_gift_writes_the_selected_plan(): void
    {
        Mail::fake();
        $course = $this->makeCourse();
        [$planA] = $this->planned($course);
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($this->admin())
            ->postJson('/admin/members/gift-course', [
                'member_ids'     => [$member->id],
                'course_id'      => $course->id,
                'course_plan_id' => $planA->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('purchases', [
            'user_id'        => $member->id,
            'course_id'      => $course->id,
            'course_plan_id' => $planA->id,
            'type'           => 'gift',
        ]);
    }

    public function test_gift_is_refused_when_a_planned_course_has_no_plan_selected(): void
    {
        Mail::fake();
        $course = $this->makeCourse();
        $this->planned($course);
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($this->admin())
            ->postJson('/admin/members/gift-course', [
                'member_ids' => [$member->id],
                'course_id'  => $course->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('course_plan_id');

        $this->assertDatabaseCount('purchases', 0);
        Mail::assertNothingSent();
    }

    public function test_gift_rejects_a_plan_from_another_course(): void
    {
        Mail::fake();
        $course = $this->makeCourse();
        $other = $this->makeCourse();
        $foreign = CoursePlan::create(['course_id' => $other->id, 'name' => '別課方案', 'sort_order' => 0]);
        $this->planned($course);
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($this->admin())
            ->postJson('/admin/members/gift-course', [
                'member_ids'     => [$member->id],
                'course_id'      => $course->id,
                'course_plan_id' => $foreign->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('course_plan_id');

        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_gift_on_a_course_without_plans_keeps_a_null_plan(): void
    {
        Mail::fake();
        $course = $this->makeCourse();
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($this->admin())
            ->postJson('/admin/members/gift-course', [
                'member_ids' => [$member->id],
                'course_id'  => $course->id,
            ])
            ->assertOk();

        $purchase = Purchase::where('user_id', $member->id)->firstOrFail();
        $this->assertNull($purchase->course_plan_id);
    }

    // ── The member list ships the plans the pickers need (D10) ─────────────

    public function test_member_index_ships_course_plans(): void
    {
        $course = $this->makeCourse();
        [$planA] = $this->planned($course);

        $this->actingAs($this->admin())
            ->get('/admin/members')
            ->assertInertia(fn ($page) => $page
                ->where('courses.0.plans.0.id', $planA->id)
                ->where('courses.0.plans.0.name', '方案A')
            );
    }
}
