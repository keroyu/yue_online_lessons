<?php

namespace Tests\Feature\HighTicket;

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Purchase;
use App\Models\User;
use App\Services\BundleCreditService;
use App\Services\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 011 US37 — bundle credits (FR-200–FR-212).
 *
 * The grant rule is one line (`max(0, planDefault - bundle_granted)`) and it has
 * to satisfy two things at once: re-granting must not hand out a second batch,
 * and an upgrade must top up the difference without returning credits the member
 * already spent. Those two, plus "updateOrCreate must not reset the balance"
 * (FR-204), are what this file exists for.
 */
class BundleCreditTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin-bundle@example.com', 'role' => 'admin']);
    }

    private function member(string $email = 'bundle-member@example.com'): User
    {
        return User::create(['email' => $email, 'role' => 'member', 'nickname' => '小明']);
    }

    private function makeCourse(string $type = 'high_ticket', array $extra = []): Course
    {
        return Course::create(array_merge([
            'name' => 'C', 'slug' => 'c-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 1000, 'instructor_name' => 'I', 'type' => $type, 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ], $extra));
    }

    private function bundleCourse(array $extra = []): Course
    {
        return $this->makeCourse('high_ticket', array_merge([
            'bundle_name' => '團體諮詢',
            'bundle_redeem_points' => 300,
        ], $extra));
    }

    private function plan(
        Course $course,
        string $name,
        int $quantity,
        int $sort = 0,
        bool $unlimited = false,
    ): CoursePlan {
        return $course->plans()->create([
            'name' => $name, 'price' => 30000, 'bundle_quantity' => $quantity,
            'bundle_unlimited' => $unlimited, 'sort_order' => $sort,
        ]);
    }

    private function purchase(User $user, Course $course, ?CoursePlan $plan = null): Purchase
    {
        return Purchase::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_plan_id' => $plan?->id,
            'buyer_email' => $user->email,
            'amount' => 30000,
            'currency' => 'TWD',
            'status' => 'paid',
            'type' => 'lead_conversion',
        ]);
    }

    private function service(): BundleCreditService
    {
        return app(BundleCreditService::class);
    }

    // ── grant rule (FR-203) ────────────────────────────────────────────────

    public function test_grant_uses_the_plan_quantity(): void
    {
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 5);
        $purchase = $this->purchase($this->member(), $course, $plan);

        $this->assertSame(5, $this->service()->syncPlanGrant($purchase));

        $purchase->refresh();
        $this->assertSame(5, $purchase->bundle_balance);
        $this->assertSame(5, $purchase->bundle_granted);
    }

    public function test_grant_falls_back_to_the_course_default_when_there_are_no_plans(): void
    {
        $course = $this->bundleCourse(['bundle_default_quantity' => 3]);
        $purchase = $this->purchase($this->member(), $course);

        $this->assertSame(3, $this->service()->syncPlanGrant($purchase));
        $this->assertSame(3, $purchase->fresh()->bundle_balance);
    }

    public function test_grant_is_idempotent_so_regranting_hands_out_nothing_extra(): void
    {
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '入門方案', 2);
        $purchase = $this->purchase($this->member(), $course, $plan);

        $this->service()->syncPlanGrant($purchase);
        $this->assertSame(0, $this->service()->syncPlanGrant($purchase->fresh()));

        $this->assertSame(2, $purchase->fresh()->bundle_balance);
    }

    public function test_upgrading_tops_up_only_the_difference_and_keeps_spent_credits_spent(): void
    {
        $course = $this->bundleCourse();
        $entry = $this->plan($course, '入門方案', 2);
        $full = $this->plan($course, '完整方案', 5, 1);
        $member = $this->member();
        $purchase = $this->purchase($member, $course, $entry);

        $this->service()->syncPlanGrant($purchase);
        // One credit already used up before the upgrade.
        $this->service()->consume($course, [$member->id]);

        $purchase->refresh()->update(['course_plan_id' => $full->id]);
        $this->assertSame(3, $this->service()->syncPlanGrant($purchase->fresh()));

        $purchase->refresh();
        // 2 granted - 1 spent + 3 topped up = 4. NOT 5: the spent one stays spent.
        $this->assertSame(4, $purchase->bundle_balance);
        $this->assertSame(5, $purchase->bundle_granted);
    }

    public function test_downgrading_never_claws_credits_back(): void
    {
        $course = $this->bundleCourse();
        $entry = $this->plan($course, '入門方案', 2);
        $full = $this->plan($course, '完整方案', 5, 1);
        $purchase = $this->purchase($this->member(), $course, $full);

        $this->service()->syncPlanGrant($purchase);
        $purchase->refresh()->update(['course_plan_id' => $entry->id]);

        $this->assertSame(0, $this->service()->syncPlanGrant($purchase->fresh()));

        $purchase->refresh();
        $this->assertSame(5, $purchase->bundle_balance);
        $this->assertSame(5, $purchase->bundle_granted);
    }

    public function test_a_course_without_a_bundle_grants_nothing(): void
    {
        $course = $this->makeCourse('high_ticket', ['bundle_default_quantity' => 4]);
        $purchase = $this->purchase($this->member(), $course);

        $this->assertSame(0, $this->service()->syncPlanGrant($purchase));
        $this->assertSame(0, $purchase->fresh()->bundle_balance);
    }

    // ── consumption (FR-205 / FR-211) ──────────────────────────────────────

    public function test_consume_deducts_one_per_member_and_skips_those_without_credit(): void
    {
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 1);
        $withCredit = $this->member('has@example.com');
        $withoutCredit = $this->member('none@example.com');

        $this->service()->syncPlanGrant($this->purchase($withCredit, $course, $plan));
        $this->purchase($withoutCredit, $course, $plan); // never granted

        $result = $this->service()->consume($course, [$withCredit->id, $withoutCredit->id]);

        $this->assertSame(1, $result['consumed']);
        $this->assertSame(['none@example.com'], $result['skipped']);
        $this->assertSame(0, Purchase::where('user_id', $withCredit->id)->value('bundle_balance'));
    }

    public function test_consume_never_drives_the_balance_negative(): void
    {
        $course = $this->bundleCourse();
        $member = $this->member();
        $purchase = $this->purchase($member, $course);
        $purchase->update(['bundle_balance' => 1, 'bundle_granted' => 1]);

        $this->service()->consume($course, [$member->id]);
        $second = $this->service()->consume($course, [$member->id]);

        $this->assertSame(0, $second['consumed']);
        $this->assertSame(0, $purchase->fresh()->bundle_balance);
    }

    public function test_consume_ignores_members_who_do_not_own_the_course(): void
    {
        $course = $this->bundleCourse();
        $stranger = $this->member('stranger@example.com');

        $result = $this->service()->consume($course, [$stranger->id]);

        $this->assertSame(0, $result['consumed']);
        $this->assertSame([], $result['skipped']); // not on the roster at all
    }

    // ── point top-up (FR-206 / FR-207) ─────────────────────────────────────

    public function test_member_can_top_up_one_credit_with_points(): void
    {
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 1);
        $member = $this->member();
        app(PointService::class)->award($member, 500, 'admin_grant');
        $purchase = $this->purchase($member, $course, $plan);
        $this->service()->syncPlanGrant($purchase);

        $this->actingAs($member)
            ->post("/member/purchases/{$purchase->id}/bundle-redeem")
            ->assertRedirect();

        $this->assertSame(2, $purchase->fresh()->bundle_balance);
        // bundle_granted answers "what the plan owed", so a top-up leaves it alone.
        $this->assertSame(1, $purchase->fresh()->bundle_granted);
        $this->assertSame(200, $member->fresh()->points);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $member->id,
            'type' => 'redeem_bundle',
            'amount' => -300,
            'reference_type' => 'purchase',
            'reference_id' => $purchase->id,
        ]);
    }

    public function test_top_up_is_rejected_when_points_are_short_and_nothing_is_written(): void
    {
        $course = $this->bundleCourse();
        $member = $this->member();
        app(PointService::class)->award($member, 100, 'admin_grant');
        $purchase = $this->purchase($member, $course);

        $this->actingAs($member)
            ->post("/member/purchases/{$purchase->id}/bundle-redeem")
            ->assertSessionHasErrors('bundle');

        $this->assertSame(0, $purchase->fresh()->bundle_balance);
        $this->assertSame(100, $member->fresh()->points);
    }

    public function test_top_up_returns_422_when_the_course_sets_no_redeem_points(): void
    {
        $course = $this->bundleCourse(['bundle_redeem_points' => null]);
        $member = $this->member();
        app(PointService::class)->award($member, 500, 'admin_grant');
        $purchase = $this->purchase($member, $course);

        $this->actingAs($member)
            ->post("/member/purchases/{$purchase->id}/bundle-redeem")
            ->assertStatus(422);
    }

    public function test_top_up_on_somebody_elses_purchase_is_forbidden(): void
    {
        $course = $this->bundleCourse();
        $owner = $this->member('owner@example.com');
        $intruder = $this->member('intruder@example.com');
        app(PointService::class)->award($intruder, 500, 'admin_grant');
        $purchase = $this->purchase($owner, $course);

        $this->actingAs($intruder)
            ->post("/member/purchases/{$purchase->id}/bundle-redeem")
            ->assertForbidden();

        $this->assertSame(0, $purchase->fresh()->bundle_balance);
    }

    // ── never granted vs used up (FR-218) ─────────────────────────────────

    public function test_my_courses_payload_carries_granted_so_the_card_can_tell_the_two_apart(): void
    {
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 1);
        $member = $this->member();
        $legacy = $this->member('legacy@example.com');
        $this->service()->syncPlanGrant($this->purchase($member, $course, $plan));
        $this->service()->consume($course, [$member->id]);
        // Held the course before the perk existed: never granted anything.
        $this->purchase($legacy, $course, $plan);

        $usedUp = $this->actingAs($member)->get('/member/learning')
            ->assertOk()->inertiaProps('courses')[0]['bundle'];
        $neverHad = $this->actingAs($legacy)->get('/member/learning')
            ->assertOk()->inertiaProps('courses')[0]['bundle'];

        $this->assertSame(0, $usedUp['balance']);
        $this->assertSame(1, $usedUp['granted']);   // card shows 剩 0 次
        $this->assertSame(0, $neverHad['balance']);
        $this->assertSame(0, $neverHad['granted']); // card hides the block
    }

    // ── the three grant entrances (FR-204) ────────────────────────────────

    public function test_converting_a_lead_grants_the_plans_credits(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 5);
        $lead = \App\Models\HighTicketLead::create([
            'name' => 'Lead', 'email' => 'lead-bundle@example.com',
            'course_id' => $course->id, 'status' => 'contacted', 'booked_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->postJson("/admin/high-ticket-leads/{$lead->id}/convert", [
                'course_id' => $course->id,
                'amount' => 38000,
                'course_plan_id' => $plan->id,
            ])
            ->assertOk();

        $purchase = Purchase::where('course_id', $course->id)->firstOrFail();
        $this->assertSame(5, $purchase->bundle_balance);
        $this->assertSame(5, $purchase->bundle_granted);
    }

    public function test_switching_a_members_plan_tops_the_credits_up(): void
    {
        $course = $this->bundleCourse();
        $entry = $this->plan($course, '入門方案', 2);
        $full = $this->plan($course, '完整方案', 5, 1);
        $member = $this->member();
        $purchase = $this->purchase($member, $course, $entry);
        $this->service()->syncPlanGrant($purchase);

        $this->actingAs($this->admin())
            ->patchJson("/admin/members/{$member->id}/purchases/{$purchase->id}/plan", [
                'course_plan_id' => $full->id,
            ])
            ->assertOk();

        $this->assertSame(5, $purchase->fresh()->bundle_balance);
    }

    public function test_gifting_a_course_grants_the_plans_credits(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 3);
        $member = $this->member();

        $this->actingAs($this->admin())
            ->postJson('/admin/members/gift-course', [
                'member_ids' => [$member->id],
                'course_id' => $course->id,
                'course_plan_id' => $plan->id,
            ])
            ->assertOk();

        $purchase = Purchase::where('user_id', $member->id)->firstOrFail();
        $this->assertSame(3, $purchase->bundle_balance);
    }

    public function test_a_front_end_checkout_style_purchase_grants_nothing(): void
    {
        // No grant path runs for storefront checkout / free claims / course
        // redemption, so the columns stay at zero (FR-204).
        $course = $this->bundleCourse(['bundle_default_quantity' => 4]);
        $purchase = Purchase::create([
            'user_id' => $this->member()->id, 'course_id' => $course->id,
            'buyer_email' => 'x@example.com', 'amount' => 1000, 'currency' => 'TWD',
            'status' => 'paid', 'type' => 'paid', 'source' => 'checkout',
        ]);

        $this->assertSame(0, $purchase->fresh()->bundle_balance);
    }

    // ── the updateOrCreate trap (FR-204) ──────────────────────────────────

    public function test_gifting_the_same_course_again_does_not_reset_the_balance(): void
    {
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 5);
        $member = $this->member();
        $purchase = $this->purchase($member, $course, $plan);
        $this->service()->syncPlanGrant($purchase);
        $this->service()->consume($course, [$member->id]);

        // The shape every grant path uses — the attribute array must not carry
        // bundle_* or this wipes the 4 remaining credits.
        Purchase::updateOrCreate(
            ['user_id' => $member->id, 'course_id' => $course->id],
            ['course_plan_id' => $plan->id, 'buyer_email' => $member->email, 'amount' => 0,
                'currency' => 'TWD', 'status' => 'paid', 'type' => 'lead_conversion'],
        );

        $this->assertSame(4, $purchase->fresh()->bundle_balance);
    }

    // ── unlimited perk (FR-213–FR-217) ────────────────────────────────────

    public function test_an_unlimited_plan_grants_no_credits_at_all(): void
    {
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 5, 0, unlimited: true);
        $purchase = $this->purchase($this->member(), $course, $plan);

        $this->assertSame(0, $this->service()->syncPlanGrant($purchase));

        $purchase->refresh();
        // Both columns are meaningless while unlimited holds, so they stay at 0
        // rather than carrying a number nobody should read (FR-214).
        $this->assertSame(0, $purchase->bundle_balance);
        $this->assertSame(0, $purchase->bundle_granted);
        $this->assertTrue($this->service()->isUnlimitedFor($course, $plan));
    }

    public function test_switching_to_an_unlimited_plan_takes_effect_with_no_regrant(): void
    {
        $course = $this->bundleCourse();
        $entry = $this->plan($course, '入門方案', 2);
        $unlimited = $this->plan($course, '完整方案', 0, 1, unlimited: true);
        $member = $this->member();
        $purchase = $this->purchase($member, $course, $entry);
        $this->service()->syncPlanGrant($purchase);

        $this->actingAs($this->admin())
            ->patchJson("/admin/members/{$member->id}/purchases/{$purchase->id}/plan", [
                'course_plan_id' => $unlimited->id,
            ])
            ->assertOk();

        $this->assertTrue($this->service()->isUnlimitedFor($course, $purchase->fresh()->plan));
    }

    public function test_flipping_a_plan_to_unlimited_covers_its_existing_holders(): void
    {
        // The whole point of reading the flag off the plan (D148): no reconversion.
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 5);
        $purchase = $this->purchase($this->member(), $course, $plan);
        $this->service()->syncPlanGrant($purchase);

        $plan->update(['bundle_unlimited' => true]);

        $this->assertTrue($this->service()->isUnlimitedFor($course, $purchase->fresh()->plan));
    }

    public function test_switching_back_to_a_limited_plan_grants_that_plans_full_quantity(): void
    {
        $course = $this->bundleCourse();
        $unlimited = $this->plan($course, '完整方案', 0, 0, unlimited: true);
        $limited = $this->plan($course, '入門方案', 2, 1);
        $purchase = $this->purchase($this->member(), $course, $unlimited);
        $this->service()->syncPlanGrant($purchase);

        $purchase->refresh()->update(['course_plan_id' => $limited->id]);

        // bundle_granted was never moved while unlimited, so the full 2 arrive.
        $this->assertSame(2, $this->service()->syncPlanGrant($purchase->fresh()));
        $this->assertSame(2, $purchase->fresh()->bundle_balance);
    }

    public function test_unlimited_members_are_never_deducted(): void
    {
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 0, 0, unlimited: true);
        $member = $this->member();
        $this->service()->syncPlanGrant($this->purchase($member, $course, $plan));

        $result = $this->service()->consume($course, [$member->id]);

        $this->assertSame(0, $result['consumed']);
        $this->assertSame([], $result['skipped']);
        $this->assertSame(['bundle-member@example.com'], $result['unlimited']);
    }

    public function test_top_up_is_rejected_for_unlimited_members(): void
    {
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 0, 0, unlimited: true);
        $member = $this->member();
        app(PointService::class)->award($member, 500, 'admin_grant');
        $purchase = $this->purchase($member, $course, $plan);

        $this->actingAs($member)
            ->post("/member/purchases/{$purchase->id}/bundle-redeem")
            ->assertStatus(422);

        $this->assertSame(500, $member->fresh()->points);
    }

    public function test_the_course_level_flag_only_applies_when_there_are_no_plans(): void
    {
        $withoutPlans = $this->bundleCourse(['bundle_unlimited' => true, 'bundle_default_quantity' => 3]);
        $purchaseA = $this->purchase($this->member('nop@example.com'), $withoutPlans);
        $this->assertTrue($this->service()->isUnlimitedFor($withoutPlans, null));
        $this->assertSame(0, $this->service()->syncPlanGrant($purchaseA));

        // Same flag on a course that does have plans: the tier decides, and a
        // planless purchase there is a legacy full-access record (FR-213).
        $withPlans = $this->bundleCourse(['bundle_unlimited' => true]);
        $limited = $this->plan($withPlans, '入門方案', 2);
        $this->assertFalse($this->service()->isUnlimitedFor($withPlans, $limited));
        $this->assertFalse($this->service()->isUnlimitedFor($withPlans, null));
    }

    // ── course settings (FR-200 / FR-201 / FR-212) ─────────────────────────

    public function test_admin_can_save_bundle_fields_on_a_high_ticket_course(): void
    {
        $course = $this->bundleCourse();

        $this->actingAs($this->admin())
            ->put("/admin/courses/{$course->id}", $this->coursePayload($course, [
                'bundle_name' => '團體諮詢',
                'bundle_redeem_points' => 250,
                'bundle_default_quantity' => 2,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $course->refresh();
        $this->assertSame('團體諮詢', $course->bundle_name);
        $this->assertSame(250, $course->bundle_redeem_points);
        $this->assertSame(2, $course->bundle_default_quantity);
    }

    public function test_bundle_fields_are_rejected_on_a_non_high_ticket_course(): void
    {
        $course = $this->makeCourse('lecture');

        $this->actingAs($this->admin())
            ->put("/admin/courses/{$course->id}", $this->coursePayload($course, [
                'type' => 'lecture',
                'bundle_name' => '團體諮詢',
            ]))
            ->assertSessionHasErrors('bundle_name');

        $this->assertNull($course->fresh()->bundle_name);
    }

    public function test_clearing_the_bundle_name_is_blocked_while_members_still_hold_credits(): void
    {
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 5);
        $this->service()->syncPlanGrant($this->purchase($this->member(), $course, $plan));

        $this->actingAs($this->admin())
            ->put("/admin/courses/{$course->id}", $this->coursePayload($course, ['bundle_name' => '']))
            ->assertSessionHasErrors('bundle_name');

        $this->assertSame('團體諮詢', $course->fresh()->bundle_name);
    }

    public function test_renaming_the_bundle_is_allowed_even_when_members_hold_credits(): void
    {
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 5);
        $this->service()->syncPlanGrant($this->purchase($this->member(), $course, $plan));

        $this->actingAs($this->admin())
            ->put("/admin/courses/{$course->id}", $this->coursePayload($course, ['bundle_name' => '小組諮詢']))
            ->assertRedirect();

        $this->assertSame('小組諮詢', $course->fresh()->bundle_name);
    }

    public function test_plan_quantity_is_saved_through_the_existing_plan_endpoint(): void
    {
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 0);

        $this->actingAs($this->admin())
            ->put("/admin/plans/{$plan->id}", ['name' => '完整方案', 'price' => 30000, 'bundle_quantity' => 5])
            ->assertRedirect();

        $this->assertSame(5, $plan->fresh()->bundle_quantity);
    }

    public function test_unlimited_flag_is_saved_through_the_existing_plan_endpoint(): void
    {
        $course = $this->bundleCourse();
        $plan = $this->plan($course, '完整方案', 0);

        $this->actingAs($this->admin())
            ->put("/admin/plans/{$plan->id}", [
                'name' => '完整方案', 'price' => 30000, 'bundle_quantity' => 0, 'bundle_unlimited' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($plan->fresh()->bundle_unlimited);
    }

    /** The course form posts every field it holds; only the overrides differ. */
    private function coursePayload(Course $course, array $overrides = []): array
    {
        return array_merge([
            'name' => $course->name,
            'tagline' => $course->tagline,
            'description' => $course->description,
            'price' => 1000,
            'instructor_name' => $course->instructor_name,
            'type' => $course->type,
            'content_category' => 'mindset',
            'course_type' => 'standard',
        ], $overrides);
    }
}
