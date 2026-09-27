<?php

namespace Tests\Feature\HighTicket;

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Purchase;
use App\Models\User;
use App\Services\BundleCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 011 US37 — the admin roster modal and its batch deduction (FR-208–FR-211).
 *
 * The roster is built server side because the consume endpoint cannot trust the
 * list the browser is holding (D144); these tests pin both halves of that.
 */
class CourseRosterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin-roster@example.com', 'role' => 'admin']);
    }

    private function member(string $email, string $nickname = '學員'): User
    {
        return User::create(['email' => $email, 'role' => 'member', 'nickname' => $nickname]);
    }

    private function course(): Course
    {
        return Course::create([
            'name' => 'C', 'slug' => 'c-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 1000, 'instructor_name' => 'I', 'type' => 'high_ticket', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni', 'bundle_name' => '團體諮詢', 'bundle_redeem_points' => 300,
        ]);
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

    private function enrol(User $user, Course $course, ?CoursePlan $plan = null, string $status = 'paid'): Purchase
    {
        $purchase = Purchase::create([
            'user_id' => $user->id, 'course_id' => $course->id, 'course_plan_id' => $plan?->id,
            'buyer_email' => $user->email, 'amount' => 30000, 'currency' => 'TWD',
            'status' => $status, 'type' => 'lead_conversion',
        ]);

        app(BundleCreditService::class)->syncPlanGrant($purchase);

        return $purchase;
    }

    public function test_roster_lists_every_paid_holder_by_default(): void
    {
        $course = $this->course();
        $entry = $this->plan($course, '入門方案', 2);
        $this->enrol($this->member('a@example.com', '甲'), $course, $entry);
        $this->enrol($this->member('b@example.com', '乙'), $course);
        // Refunded records are not students any more.
        $this->enrol($this->member('c@example.com', '丙'), $course, $entry, 'refunded');

        $response = $this->actingAs($this->admin())
            ->getJson("/admin/courses/{$course->id}/roster")
            ->assertOk();

        $emails = array_column($response->json('students'), 'email');
        $this->assertSame(['a@example.com', 'b@example.com'], $emails);
        $this->assertTrue($response->json('has_bundle'));
    }

    public function test_roster_can_be_narrowed_to_one_plan(): void
    {
        $course = $this->course();
        $entry = $this->plan($course, '入門方案', 2);
        $full = $this->plan($course, '完整方案', 5, 1);
        $this->enrol($this->member('entry@example.com'), $course, $entry);
        $this->enrol($this->member('full@example.com'), $course, $full);

        $students = $this->actingAs($this->admin())
            ->getJson("/admin/courses/{$course->id}/roster?plan_id={$full->id}")
            ->assertOk()
            ->json('students');

        $this->assertSame(['full@example.com'], array_column($students, 'email'));
        $this->assertSame('完整方案', $students[0]['plan_name']);
        $this->assertSame(5, $students[0]['bundle_balance']);
    }

    public function test_roster_can_be_narrowed_to_members_with_no_plan(): void
    {
        $course = $this->course();
        $entry = $this->plan($course, '入門方案', 2);
        $this->enrol($this->member('entry@example.com'), $course, $entry);
        $this->enrol($this->member('legacy@example.com'), $course);

        $students = $this->actingAs($this->admin())
            ->getJson("/admin/courses/{$course->id}/roster?plan_id=none")
            ->assertOk()
            ->json('students');

        $this->assertSame(['legacy@example.com'], array_column($students, 'email'));
        $this->assertNull($students[0]['plan_name']);
    }

    public function test_with_credit_filter_drops_members_who_have_none_left(): void
    {
        $course = $this->course();
        $plan = $this->plan($course, '完整方案', 1);
        $spent = $this->member('spent@example.com');
        $this->enrol($spent, $course, $plan);
        $this->enrol($this->member('holding@example.com'), $course, $plan);
        app(BundleCreditService::class)->consume($course, [$spent->id]);

        $students = $this->actingAs($this->admin())
            ->getJson("/admin/courses/{$course->id}/roster?with_credit=1")
            ->assertOk()
            ->json('students');

        $this->assertSame(['holding@example.com'], array_column($students, 'email'));
    }

    public function test_joined_at_is_rendered_in_taipei_time(): void
    {
        $course = $this->course();
        $purchase = $this->enrol($this->member('tz@example.com'), $course);
        // 2026-03-01 18:30 UTC is 2026-03-02 02:30 in Taipei — the date differs,
        // which is exactly what a UTC-rendered column would get wrong.
        $purchase->forceFill(['created_at' => Carbon::parse('2026-03-01 18:30:00', 'UTC')])->save();

        $students = $this->actingAs($this->admin())
            ->getJson("/admin/courses/{$course->id}/roster")
            ->assertOk()
            ->json('students');

        $this->assertSame('2026-03-02 02:30', $students[0]['joined_at']);
    }

    public function test_batch_consume_deducts_one_each_and_reports_the_skipped(): void
    {
        $course = $this->course();
        $plan = $this->plan($course, '完整方案', 1);
        $holding = $this->member('holding@example.com');
        $spent = $this->member('spent@example.com');
        $this->enrol($holding, $course, $plan);
        $this->enrol($spent, $course, $plan);
        app(BundleCreditService::class)->consume($course, [$spent->id]);

        $this->actingAs($this->admin())
            ->postJson("/admin/courses/{$course->id}/bundle/consume", [
                'user_ids' => [$holding->id, $spent->id],
            ])
            ->assertOk()
            ->assertJson(['consumed' => 1, 'skipped' => ['spent@example.com']]);

        $this->assertSame(0, Purchase::where('user_id', $holding->id)->value('bundle_balance'));
    }

    public function test_batch_consume_ignores_ids_that_do_not_own_the_course(): void
    {
        $course = $this->course();
        $other = $this->course();
        $plan = $this->plan($other, '完整方案', 5);
        $stranger = $this->member('stranger@example.com');
        $this->enrol($stranger, $other, $plan);

        $this->actingAs($this->admin())
            ->postJson("/admin/courses/{$course->id}/bundle/consume", ['user_ids' => [$stranger->id]])
            ->assertOk()
            ->assertJson(['consumed' => 0]);

        // The other course's credits are untouched — course scoping holds.
        $this->assertSame(5, Purchase::where('user_id', $stranger->id)->value('bundle_balance'));
    }

    public function test_consume_is_rejected_on_a_course_with_no_bundle(): void
    {
        $course = $this->course();
        $course->update(['bundle_name' => null]);
        $member = $this->member('m@example.com');
        $this->enrol($member, $course);

        $this->actingAs($this->admin())
            ->postJson("/admin/courses/{$course->id}/bundle/consume", ['user_ids' => [$member->id]])
            ->assertStatus(422);
    }

    // ── unlimited members (FR-215 / FR-216) ───────────────────────────────

    public function test_with_credit_filter_keeps_unlimited_members_despite_a_zero_balance(): void
    {
        $course = $this->course();
        $unlimitedPlan = $this->plan($course, '完整方案', 0, 0, unlimited: true);
        $spent = $this->plan($course, '入門方案', 1, 1);
        $spender = $this->member('spent@example.com');
        $this->enrol($this->member('unlimited@example.com'), $course, $unlimitedPlan);
        $this->enrol($spender, $course, $spent);
        app(BundleCreditService::class)->consume($course, [$spender->id]);

        $students = $this->actingAs($this->admin())
            ->getJson("/admin/courses/{$course->id}/roster?with_credit=1")
            ->assertOk()
            ->json('students');

        // Balance is 0 for both, but only the unlimited one still qualifies.
        $this->assertSame(['unlimited@example.com'], array_column($students, 'email'));
        $this->assertTrue($students[0]['unlimited']);
    }

    public function test_batch_consume_reports_unlimited_members_separately(): void
    {
        $course = $this->course();
        $limited = $this->plan($course, '入門方案', 2);
        $unlimitedPlan = $this->plan($course, '完整方案', 0, 1, unlimited: true);
        $paying = $this->member('paying@example.com');
        $forever = $this->member('forever@example.com');
        $this->enrol($paying, $course, $limited);
        $this->enrol($forever, $course, $unlimitedPlan);

        $this->actingAs($this->admin())
            ->postJson("/admin/courses/{$course->id}/bundle/consume", [
                'user_ids' => [$paying->id, $forever->id],
            ])
            ->assertOk()
            ->assertJson([
                'consumed' => 1,
                'skipped' => [],
                'unlimited' => ['forever@example.com'],
            ]);

        $this->assertSame(1, Purchase::where('user_id', $paying->id)->value('bundle_balance'));
        // Untouched: an unlimited member has nothing to spend.
        $this->assertSame(0, Purchase::where('user_id', $forever->id)->value('bundle_balance'));
    }

    // ── batch backfill (FR-219) ───────────────────────────────────────────

    public function test_batch_grant_backfills_students_who_predate_the_perk(): void
    {
        $course = $this->course();
        $plan = $this->plan($course, '完整方案', 5);
        $fresh = $this->member('fresh@example.com');
        $alreadyHas = $this->member('already@example.com');
        // enrol() grants; simulate a pre-perk student by zeroing the columns.
        $this->enrol($fresh, $course, $plan)->update(['bundle_balance' => 0, 'bundle_granted' => 0]);
        $this->enrol($alreadyHas, $course, $plan);

        $this->actingAs($this->admin())
            ->postJson("/admin/courses/{$course->id}/bundle/grant", [
                'user_ids' => [$fresh->id, $alreadyHas->id],
            ])
            ->assertOk()
            ->assertJson(['granted' => 1, 'unchanged' => 1]);

        $this->assertSame(5, Purchase::where('user_id', $fresh->id)->value('bundle_balance'));
        $this->assertSame(5, Purchase::where('user_id', $alreadyHas->id)->value('bundle_balance'));
    }

    public function test_pressing_batch_grant_twice_hands_out_nothing_extra(): void
    {
        $course = $this->course();
        $plan = $this->plan($course, '完整方案', 5);
        $member = $this->member('twice@example.com');
        $this->enrol($member, $course, $plan)->update(['bundle_balance' => 0, 'bundle_granted' => 0]);

        $admin = $this->admin();

        foreach ([1, 2] as $attempt) {
            $this->actingAs($admin)
                ->postJson("/admin/courses/{$course->id}/bundle/grant", ['user_ids' => [$member->id]])
                ->assertOk();
        }

        $this->assertSame(5, Purchase::where('user_id', $member->id)->value('bundle_balance'));
    }

    public function test_batch_grant_leaves_unlimited_members_and_strangers_alone(): void
    {
        $course = $this->course();
        $unlimitedPlan = $this->plan($course, '完整方案', 0, 0, unlimited: true);
        $forever = $this->member('forever@example.com');
        $stranger = $this->member('stranger@example.com');
        $this->enrol($forever, $course, $unlimitedPlan);

        $this->actingAs($this->admin())
            ->postJson("/admin/courses/{$course->id}/bundle/grant", [
                'user_ids' => [$forever->id, $stranger->id],
            ])
            ->assertOk()
            ->assertJson(['granted' => 0, 'unchanged' => 1]); // the stranger is not on the roster

        $this->assertSame(0, Purchase::where('user_id', $forever->id)->value('bundle_balance'));
    }

    public function test_batch_grant_is_rejected_on_a_course_with_no_bundle(): void
    {
        $course = $this->course();
        $course->update(['bundle_name' => null]);
        $member = $this->member('m@example.com');
        $this->enrol($member, $course);

        $this->actingAs($this->admin())
            ->postJson("/admin/courses/{$course->id}/bundle/grant", ['user_ids' => [$member->id]])
            ->assertStatus(422);
    }

    public function test_members_cannot_read_the_roster_or_spend_credits(): void
    {
        $course = $this->course();
        $member = $this->member('nosy@example.com');

        // AdminMiddleware redirects rather than 403s (000 US6), so both ends of
        // the modal bounce non-admins before the controller runs.
        $this->actingAs($member)->get("/admin/courses/{$course->id}/roster")->assertRedirect();
        $this->actingAs($member)
            ->post("/admin/courses/{$course->id}/bundle/consume", ['user_ids' => [$member->id]])
            ->assertRedirect();

        $this->assertSame(0, \App\Models\Purchase::query()->count());
    }
}
