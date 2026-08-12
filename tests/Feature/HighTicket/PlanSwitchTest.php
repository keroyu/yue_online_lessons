<?php

namespace Tests\Feature\HighTicket;

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 011 US21 — upgrading a member between tiers after an offline transfer
 * (FR-094). The top-up adds to the purchase rather than replacing it, so the
 * revenue chart's sum(amount) reflects what was actually collected.
 */
class PlanSwitchTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;
    private CoursePlan $planA;
    private CoursePlan $planB;
    private User $member;
    private Purchase $purchase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::create([
            'name' => 'C', 'slug' => 'c-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 30000, 'instructor_name' => 'I', 'type' => 'high_ticket', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ]);

        $this->planA = CoursePlan::create(['course_id' => $this->course->id, 'name' => '方案A', 'price' => 30000, 'sort_order' => 0]);
        $this->planB = CoursePlan::create(['course_id' => $this->course->id, 'name' => '方案B', 'price' => 80000, 'sort_order' => 1]);

        $this->member = User::create(['email' => 'holder@example.com', 'role' => 'member']);

        $this->purchase = Purchase::create([
            'user_id' => $this->member->id, 'course_id' => $this->course->id,
            'course_plan_id' => $this->planA->id, 'buyer_email' => $this->member->email,
            'amount' => 30000, 'currency' => 'TWD', 'status' => 'paid', 'type' => 'lead_conversion',
        ]);
    }

    private function admin(): User
    {
        return User::create(['email' => 'admin-switch@example.com', 'role' => 'admin']);
    }

    private function url(?Purchase $purchase = null, ?User $member = null): string
    {
        $member ??= $this->member;
        $purchase ??= $this->purchase;

        return "/admin/members/{$member->id}/purchases/{$purchase->id}/plan";
    }

    public function test_admin_can_switch_the_plan(): void
    {
        $this->actingAs($this->admin())
            ->patchJson($this->url(), ['course_plan_id' => $this->planB->id])
            ->assertOk();

        $this->assertSame($this->planB->id, $this->purchase->fresh()->course_plan_id);
    }

    public function test_top_up_is_added_to_the_existing_amount(): void
    {
        $this->actingAs($this->admin())
            ->patchJson($this->url(), [
                'course_plan_id' => $this->planB->id,
                'additional_amount' => 50000,
            ])
            ->assertOk();

        // 30000 collected originally + 50000 wired for the upgrade.
        $this->assertSame(80000, (int) $this->purchase->fresh()->amount);
    }

    public function test_switching_without_a_top_up_leaves_the_amount_alone(): void
    {
        $this->actingAs($this->admin())
            ->patchJson($this->url(), ['course_plan_id' => $this->planB->id])
            ->assertOk();

        $this->assertSame(30000, (int) $this->purchase->fresh()->amount);
    }

    public function test_plan_can_be_cleared_back_to_full_access(): void
    {
        $this->actingAs($this->admin())
            ->patchJson($this->url(), ['course_plan_id' => null])
            ->assertOk();

        $this->assertNull($this->purchase->fresh()->course_plan_id);
    }

    public function test_negative_top_up_is_rejected(): void
    {
        // Refunds have their own path in the transactions admin; a second
        // entrance here would only make the books harder to reconcile.
        $this->actingAs($this->admin())
            ->patchJson($this->url(), [
                'course_plan_id' => $this->planB->id,
                'additional_amount' => -5000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('additional_amount');

        $this->assertSame(30000, (int) $this->purchase->fresh()->amount);
    }

    public function test_plan_from_another_course_is_rejected(): void
    {
        $other = Course::create([
            'name' => 'Other', 'slug' => 'o-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 1000, 'instructor_name' => 'I', 'type' => 'high_ticket', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ]);
        $foreign = CoursePlan::create(['course_id' => $other->id, 'name' => '別課方案', 'sort_order' => 0]);

        $this->actingAs($this->admin())
            ->patchJson($this->url(), ['course_plan_id' => $foreign->id])
            ->assertStatus(422);

        $this->assertSame($this->planA->id, $this->purchase->fresh()->course_plan_id);
    }

    public function test_purchase_belonging_to_another_member_is_rejected(): void
    {
        // Route model binding validates each parameter alone, never the
        // relationship between them.
        $stranger = User::create(['email' => 'stranger@example.com', 'role' => 'member']);

        $this->actingAs($this->admin())
            ->patchJson($this->url(member: $stranger), ['course_plan_id' => $this->planB->id])
            ->assertForbidden();

        $this->assertSame($this->planA->id, $this->purchase->fresh()->course_plan_id);
    }

    public function test_non_admin_cannot_switch_plans(): void
    {
        // AdminMiddleware bounces non-admins to the homepage rather than 403.
        $this->actingAs($this->member)
            ->patchJson($this->url(), ['course_plan_id' => $this->planB->id])
            ->assertRedirect('/');

        $this->assertSame($this->planA->id, $this->purchase->fresh()->course_plan_id);
    }

    public function test_member_detail_exposes_the_plan_and_the_available_options(): void
    {
        $courses = $this->actingAs($this->admin())
            ->getJson("/admin/members/{$this->member->id}")
            ->assertOk()
            ->json('courses');

        $row = collect($courses)->firstWhere('id', $this->course->id);

        $this->assertSame($this->purchase->id, $row['purchase_id']);
        $this->assertSame($this->planA->id, $row['plan_id']);
        $this->assertSame('方案A', $row['plan_name']);
        $this->assertSame(['方案A', '方案B'], collect($row['available_plans'])->pluck('name')->all());
    }
}
