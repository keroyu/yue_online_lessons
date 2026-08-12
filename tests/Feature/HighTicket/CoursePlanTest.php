<?php

namespace Tests\Feature\HighTicket;

use App\Mail\LessonAddedNotification;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Lesson;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 011 US21 — plan CRUD and lesson assignment (D82 / FR-093).
 *
 * The delete guard is the one that matters: a plan somebody still holds must
 * not vanish, because the alternative (nulling the column) silently promotes
 * that member to full access.
 */
class CoursePlanTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin-plans@example.com', 'role' => 'admin']);
    }

    private function makeCourse(string $type = 'high_ticket'): Course
    {
        return Course::create([
            'name' => 'C', 'slug' => 'c-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 1000, 'instructor_name' => 'I', 'type' => $type, 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ]);
    }

    private function makeLesson(Course $course, string $title = 'EP'): Lesson
    {
        return Lesson::create([
            'course_id' => $course->id, 'title' => $title, 'video_platform' => 'youtube',
            'video_id' => 'v' . uniqid(), 'duration_seconds' => 60, 'sort_order' => 1,
        ]);
    }

    // ── create ──────────────────────────────────────────────────────────────

    public function test_admin_can_create_a_plan_on_a_high_ticket_course(): void
    {
        $course = $this->makeCourse();

        $this->actingAs($this->admin())
            ->post("/admin/courses/{$course->id}/plans", ['name' => '方案A', 'price' => 30000])
            ->assertRedirect();

        $this->assertDatabaseHas('course_plans', [
            'course_id' => $course->id,
            'name' => '方案A',
            'price' => 30000,
        ]);
    }

    public function test_plans_are_rejected_on_non_high_ticket_courses(): void
    {
        // D82: normal courses sell through the storefront, which has no plan
        // picker — allowing plans there would be a half-built feature.
        $course = $this->makeCourse('lecture');

        $this->actingAs($this->admin())
            ->post("/admin/courses/{$course->id}/plans", ['name' => '方案A'])
            ->assertForbidden();

        $this->assertDatabaseCount('course_plans', 0);
    }

    public function test_price_is_optional(): void
    {
        $course = $this->makeCourse();

        $this->actingAs($this->admin())
            ->post("/admin/courses/{$course->id}/plans", ['name' => '只有名字'])
            ->assertRedirect();

        $this->assertDatabaseHas('course_plans', ['name' => '只有名字', 'price' => null]);
    }

    public function test_guest_cannot_create_plans(): void
    {
        $course = $this->makeCourse();

        $this->post("/admin/courses/{$course->id}/plans", ['name' => '方案A'])
            ->assertRedirect('/login');
    }

    // ── update / delete ─────────────────────────────────────────────────────

    public function test_admin_can_rename_a_plan(): void
    {
        $course = $this->makeCourse();
        $plan = CoursePlan::create(['course_id' => $course->id, 'name' => '舊名', 'sort_order' => 0]);

        $this->actingAs($this->admin())
            ->put("/admin/plans/{$plan->id}", ['name' => '新名', 'price' => 88000])
            ->assertRedirect();

        $this->assertDatabaseHas('course_plans', ['id' => $plan->id, 'name' => '新名', 'price' => 88000]);
    }

    public function test_plan_nobody_holds_can_be_deleted(): void
    {
        $course = $this->makeCourse();
        $plan = CoursePlan::create(['course_id' => $course->id, 'name' => '方案A', 'sort_order' => 0]);
        $plan->lessons()->sync([$this->makeLesson($course)->id]);

        $this->actingAs($this->admin())
            ->delete("/admin/plans/{$plan->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('course_plans', ['id' => $plan->id]);
        $this->assertDatabaseCount('course_plan_lesson', 0);
    }

    public function test_plan_still_held_by_a_member_cannot_be_deleted(): void
    {
        $course = $this->makeCourse();
        $plan = CoursePlan::create(['course_id' => $course->id, 'name' => '方案A', 'sort_order' => 0]);
        $member = User::create(['email' => 'holder@example.com', 'role' => 'member']);

        Purchase::create([
            'user_id' => $member->id, 'course_id' => $course->id, 'course_plan_id' => $plan->id,
            'buyer_email' => $member->email, 'amount' => 30000, 'currency' => 'TWD',
            'status' => 'paid', 'type' => 'lead_conversion',
        ]);

        $this->actingAs($this->admin())
            ->delete("/admin/plans/{$plan->id}")
            ->assertSessionHasErrors('plan');

        $this->assertDatabaseHas('course_plans', ['id' => $plan->id]);
    }

    // ── lesson assignment ───────────────────────────────────────────────────

    public function test_admin_can_sync_a_lesson_to_multiple_plans(): void
    {
        $course = $this->makeCourse();
        $lesson = $this->makeLesson($course);
        $a = CoursePlan::create(['course_id' => $course->id, 'name' => 'A', 'sort_order' => 0]);
        $b = CoursePlan::create(['course_id' => $course->id, 'name' => 'B', 'sort_order' => 1]);

        $this->actingAs($this->admin())
            ->put("/admin/lessons/{$lesson->id}/plans", ['plan_ids' => [$a->id, $b->id]])
            ->assertRedirect();

        $this->assertSame([$a->id, $b->id], $lesson->fresh()->plans->pluck('id')->sort()->values()->all());
    }

    public function test_syncing_an_empty_list_removes_all_assignments(): void
    {
        $course = $this->makeCourse();
        $lesson = $this->makeLesson($course);
        $a = CoursePlan::create(['course_id' => $course->id, 'name' => 'A', 'sort_order' => 0]);
        $lesson->plans()->sync([$a->id]);

        $this->actingAs($this->admin())
            ->put("/admin/lessons/{$lesson->id}/plans", ['plan_ids' => []])
            ->assertRedirect();

        $this->assertCount(0, $lesson->fresh()->plans);
    }

    // ── new-lesson notification (FR-095) ────────────────────────────────────

    /**
     * A newly created lesson has no plan assignments yet (they are made
     * afterwards, from the chapter list), so on a tiered course the create-time
     * notification reaches only full-access members. Plan holders are excluded
     * because at that instant the lesson is invisible to them — telling them
     * would send them looking for something that is not in their sidebar.
     *
     * Pinned deliberately: it is the correct read of FR-095, and it means
     * "assign plans, then notify" needs a path of its own if it is ever wanted.
     */
    public function test_new_lesson_notification_skips_plan_holders_who_cannot_see_it(): void
    {
        Mail::fake();

        $course = $this->makeCourse();
        $planA = CoursePlan::create(['course_id' => $course->id, 'name' => '方案A', 'sort_order' => 0]);

        $holderA = User::create(['email' => 'a@example.com', 'role' => 'member']);
        $fullAccess = User::create(['email' => 'full@example.com', 'role' => 'member']);

        foreach ([[$holderA, $planA->id], [$fullAccess, null]] as [$user, $planId]) {
            Purchase::create([
                'user_id' => $user->id, 'course_id' => $course->id, 'course_plan_id' => $planId,
                'buyer_email' => $user->email, 'amount' => 1000, 'currency' => 'TWD',
                'status' => 'paid', 'type' => 'lead_conversion',
            ]);
        }

        $this->actingAs($this->admin())
            ->post("/admin/courses/{$course->id}/lessons", [
                'title' => '新小節',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'duration_seconds' => 300,
                'notify_members' => true,
            ])
            ->assertRedirect();

        Mail::assertSent(LessonAddedNotification::class, 1);
        Mail::assertSent(
            LessonAddedNotification::class,
            fn ($mail) => $mail->hasTo('full@example.com'),
        );
    }

    // ── plan-side bulk assignment (chapter shortcuts) ───────────────────────

    public function test_plan_lessons_can_be_synced_in_bulk(): void
    {
        $course = $this->makeCourse();
        $plan = CoursePlan::create(['course_id' => $course->id, 'name' => 'A', 'sort_order' => 0]);
        $one = $this->makeLesson($course, 'EP1');
        $two = $this->makeLesson($course, 'EP2');

        $this->actingAs($this->admin())
            ->put("/admin/plans/{$plan->id}/lessons", ['lesson_ids' => [$one->id, $two->id]])
            ->assertRedirect();

        $this->assertSame([$one->id, $two->id], $plan->fresh()->lessons->pluck('id')->sort()->values()->all());
    }

    public function test_bulk_sync_replaces_rather_than_appends(): void
    {
        $course = $this->makeCourse();
        $plan = CoursePlan::create(['course_id' => $course->id, 'name' => 'A', 'sort_order' => 0]);
        $one = $this->makeLesson($course, 'EP1');
        $two = $this->makeLesson($course, 'EP2');
        $plan->lessons()->sync([$one->id, $two->id]);

        $this->actingAs($this->admin())
            ->put("/admin/plans/{$plan->id}/lessons", ['lesson_ids' => [$two->id]])
            ->assertRedirect();

        $this->assertSame([$two->id], $plan->fresh()->lessons->pluck('id')->all());
    }

    public function test_bulk_sync_rejects_a_lesson_from_another_course(): void
    {
        $course = $this->makeCourse();
        $other = $this->makeCourse();
        $plan = CoursePlan::create(['course_id' => $course->id, 'name' => 'A', 'sort_order' => 0]);
        $foreign = $this->makeLesson($other, '別課的小節');

        $this->actingAs($this->admin())
            ->put("/admin/plans/{$plan->id}/lessons", ['lesson_ids' => [$foreign->id]])
            ->assertSessionHasErrors('lesson_ids');

        $this->assertCount(0, $plan->fresh()->lessons);
    }

    public function test_plan_from_another_course_is_rejected(): void
    {
        $course = $this->makeCourse();
        $other = $this->makeCourse();
        $lesson = $this->makeLesson($course);
        $foreign = CoursePlan::create(['course_id' => $other->id, 'name' => '別課的方案', 'sort_order' => 0]);

        $this->actingAs($this->admin())
            ->put("/admin/lessons/{$lesson->id}/plans", ['plan_ids' => [$foreign->id]])
            ->assertSessionHasErrors('plan_ids');

        $this->assertCount(0, $lesson->fresh()->plans);
    }
}
