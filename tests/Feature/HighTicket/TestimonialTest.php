<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\PointTransaction;
use App\Models\Purchase;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * 011 US40 — testimonials collected from a link the consultant pastes into the
 * follow-up email, rewarded with points on submission, shown on the sales page
 * only after review (FR-238 – FR-245).
 */
class TestimonialTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name'                   => 'HT Course',
            'slug'                   => 'ht-' . uniqid(),
            'tagline'                => 'tag',
            'description'            => 'desc',
            'price'                  => 50000,
            'instructor_name'        => 'Tester',
            'type'                   => 'high_ticket',
            'status'                 => 'selling',
            'course_type'            => 'standard',
            'is_published'           => true,
            'is_visible'             => true,
            'payment_gateway'        => 'payuni',
            'high_ticket_hide_price' => true,
        ], $overrides))->fresh();
    }

    /** A confirmed lead whose 30-minute consultation started at $startsAt. */
    private function makeLead(Course $course, ?Carbon $startsAt = null, array $overrides = []): HighTicketLead
    {
        // Slot start times are unique, so each default lead gets its own hour.
        $startsAt ??= now()->subDay()->startOfHour()->subHours(HighTicketLead::count());

        $lead = HighTicketLead::create(array_merge([
            'name'         => 'Lead Person',
            'email'        => 'lead@example.com',
            'phone'        => '0912345678',
            'course_id'    => $course->id,
            'status'       => 'pending',
            'booked_at'    => $startsAt->copy()->subDays(3),
            'confirmed_at' => $startsAt->copy()->subDays(3),
        ], $overrides));

        foreach ([0, 15] as $offset) {
            ConsultationSlot::create([
                'starts_at' => $startsAt->copy()->addMinutes($offset),
                'lead_id'   => $lead->id,
            ]);
        }

        return $lead;
    }

    private function issueLink(HighTicketLead $lead): string
    {
        return $this->actingAs($this->admin())
            ->postJson("/admin/high-ticket-leads/{$lead->id}/testimonial-link")
            ->assertOk()
            ->json('url');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nickname' => '小明',
            'title'    => '自由接案設計師',
            'content'  => '面談非常有收穫，幫我把定價想清楚了。',
        ], $overrides);
    }

    // ── Link issuing (FR-238 / FR-239) ──────────────────────────

    public function test_link_is_issued_for_a_lead_whose_consultation_has_happened(): void
    {
        $lead = $this->makeLead($this->makeCourse());

        $url = $this->issueLink($lead);

        $lead->refresh();
        $this->assertNotNull($lead->testimonial_token);
        $this->assertSame(route('testimonials.create', $lead->testimonial_token), $url);
    }

    public function test_the_same_link_is_returned_on_every_copy(): void
    {
        $lead = $this->makeLead($this->makeCourse());

        $this->assertSame($this->issueLink($lead), $this->issueLink($lead));
    }

    public function test_link_is_refused_before_the_consultation_starts(): void
    {
        $lead = $this->makeLead($this->makeCourse(), now()->addDay()->startOfHour());

        $this->actingAs($this->admin())
            ->postJson("/admin/high-ticket-leads/{$lead->id}/testimonial-link")
            ->assertStatus(422);

        $this->assertNull($lead->fresh()->testimonial_token);
    }

    public function test_link_is_refused_for_a_cancelled_or_unconfirmed_booking(): void
    {
        $course = $this->makeCourse();
        $cancelled = $this->makeLead($course, null, ['email' => 'c@example.com', 'cancelled_at' => now()]);
        $unconfirmed = $this->makeLead($course, null, ['email' => 'u@example.com', 'confirmed_at' => null]);

        foreach ([$cancelled, $unconfirmed] as $lead) {
            $this->actingAs($this->admin())
                ->postJson("/admin/high-ticket-leads/{$lead->id}/testimonial-link")
                ->assertStatus(422);
        }
    }

    public function test_sales_consultant_can_issue_a_link(): void
    {
        $lead = $this->makeLead($this->makeCourse());
        $consultant = User::factory()->create(['role' => 'member', 'is_sales_consultant' => true]);

        $this->actingAs($consultant)
            ->postJson("/admin/high-ticket-leads/{$lead->id}/testimonial-link")
            ->assertOk();
    }

    public function test_leads_list_flags_eligibility_per_row(): void
    {
        $course = $this->makeCourse();
        $past = $this->makeLead($course, null, ['email' => 'past@example.com']);
        $future = $this->makeLead($course, now()->addDay()->startOfHour(), ['email' => 'future@example.com']);

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads')
            ->assertInertia(fn (Assert $page) => $page
                ->where('leads.data', fn ($rows) => collect($rows)->firstWhere('id', $past->id)['testimonial_eligible'] === true
                    && collect($rows)->firstWhere('id', $future->id)['testimonial_eligible'] === false));
    }

    // ── Public form (FR-240) ────────────────────────────────────

    public function test_form_shows_course_and_identity_not_consultation_time(): void
    {
        $course = $this->makeCourse(['name' => '品牌顧問']);
        $startsAt = Carbon::parse('2026-10-01 12:00', 'UTC');
        $lead = $this->makeLead($course, $startsAt);
        $this->issueLink($lead);

        $this->get('/testimonials/' . $lead->fresh()->testimonial_token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Testimonial/Create')
                ->where('course_name', '品牌顧問')
                ->where('identity_label', '諮詢客戶')
                ->missing('consulted_at')
                ->where('default_nickname', 'Lead Person')
                ->where('submitted', false)
                ->where('reward_points', 100));
    }

    public function test_unknown_token_is_404(): void
    {
        $this->get('/testimonials/' . str_repeat('x', 40))->assertNotFound();
        $this->post('/testimonials/' . str_repeat('x', 40), $this->payload())->assertNotFound();
    }

    // ── Submission (FR-241 / FR-242) ────────────────────────────

    public function test_submission_creates_member_testimonial_and_points(): void
    {
        $course = $this->makeCourse();
        $lead = $this->makeLead($course);
        $this->issueLink($lead);
        $token = $lead->fresh()->testimonial_token;

        $this->post("/testimonials/{$token}", $this->payload())
            ->assertRedirect("/testimonials/{$token}");

        $user = User::where('email', 'lead@example.com')->firstOrFail();
        $this->assertSame('Lead Person', $user->nickname);
        $this->assertSame(100, (int) $user->points);

        $testimonial = Testimonial::firstOrFail();
        $this->assertSame('pending', $testimonial->status);
        $this->assertSame($course->id, $testimonial->course_id);
        $this->assertSame($user->id, $testimonial->user_id);
        $this->assertSame(100, $testimonial->reward_points);
        $this->assertTrue($testimonial->consulted_at->equalTo($lead->slots()->first()->starts_at));

        $this->assertDatabaseHas('point_transactions', [
            'user_id'        => $user->id,
            'amount'         => 100,
            'type'           => 'earn_testimonial',
            'reference_type' => 'testimonial',
            'reference_id'   => $testimonial->id,
        ]);

        $this->get("/testimonials/{$token}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('submitted', true)
                ->where('awarded_points', 100));
    }

    public function test_existing_member_is_reused_and_not_overwritten(): void
    {
        $member = User::factory()->create(['email' => 'lead@example.com', 'nickname' => '原暱稱', 'points' => 50]);
        $lead = $this->makeLead($this->makeCourse());
        $this->issueLink($lead);

        $this->post('/testimonials/' . $lead->fresh()->testimonial_token, $this->payload());

        $member->refresh();
        $this->assertSame('原暱稱', $member->nickname);
        $this->assertSame(150, (int) $member->points);
        $this->assertSame(1, User::where('email', 'lead@example.com')->count());
    }

    public function test_reward_uses_the_configured_amount(): void
    {
        SiteSetting::set('testimonial_reward_points', '250');
        $lead = $this->makeLead($this->makeCourse());
        $this->issueLink($lead);

        $this->post('/testimonials/' . $lead->fresh()->testimonial_token, $this->payload());

        $this->assertSame(250, (int) User::where('email', 'lead@example.com')->value('points'));
    }

    public function test_zero_reward_writes_no_ledger_entry(): void
    {
        SiteSetting::set('testimonial_reward_points', '0');
        $lead = $this->makeLead($this->makeCourse());
        $this->issueLink($lead);

        $this->post('/testimonials/' . $lead->fresh()->testimonial_token, $this->payload());

        $this->assertSame(1, Testimonial::count());
        $this->assertSame(0, Testimonial::first()->reward_points);
        $this->assertSame(0, PointTransaction::count());
    }

    public function test_second_submission_is_refused_without_extra_points(): void
    {
        $lead = $this->makeLead($this->makeCourse());
        $this->issueLink($lead);
        $token = $lead->fresh()->testimonial_token;

        $this->post("/testimonials/{$token}", $this->payload());
        $this->post("/testimonials/{$token}", $this->payload(['content' => '第二次送出的內容，應該被擋下來']))
            ->assertSessionHasErrors('content');

        $this->assertSame(1, Testimonial::count());
        $this->assertSame(1, PointTransaction::count());
        $this->assertSame(100, (int) User::where('email', 'lead@example.com')->value('points'));
    }

    public function test_validation_rules(): void
    {
        $lead = $this->makeLead($this->makeCourse());
        $this->issueLink($lead);
        $token = $lead->fresh()->testimonial_token;

        $this->post("/testimonials/{$token}", $this->payload([
            'nickname' => '',
            'title'    => str_repeat('長', 41),
            'content'  => '太短了',
        ]))->assertSessionHasErrors(['nickname', 'title', 'content']);

        $this->post("/testimonials/{$token}", $this->payload(['content' => str_repeat('字', 501)]))
            ->assertSessionHasErrors('content');

        // Limits count characters, not bytes: 20 CJK characters is fine.
        $this->post("/testimonials/{$token}", $this->payload(['nickname' => str_repeat('名', 20), 'title' => null]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Testimonial::count());
    }

    // ── Moderation (FR-244) ─────────────────────────────────────

    private function submitted(Course $course, string $email = 'lead@example.com', ?Carbon $startsAt = null): Testimonial
    {
        $lead = $this->makeLead($course, $startsAt, ['email' => $email]);
        $this->issueLink($lead);
        $this->post('/testimonials/' . $lead->fresh()->testimonial_token, $this->payload());

        return Testimonial::where('lead_id', $lead->id)->firstOrFail();
    }

    public function test_publish_and_hide(): void
    {
        $testimonial = $this->submitted($this->makeCourse());

        $this->actingAs($this->admin())
            ->patch("/admin/testimonials/{$testimonial->id}", ['status' => 'published'])
            ->assertRedirect();

        $testimonial->refresh();
        $this->assertSame('published', $testimonial->status);
        $this->assertNotNull($testimonial->published_at);

        $this->actingAs($this->admin())
            ->patch("/admin/testimonials/{$testimonial->id}", ['status' => 'hidden']);
        $this->assertSame('hidden', $testimonial->fresh()->status);

        $this->actingAs($this->admin())
            ->patch("/admin/testimonials/{$testimonial->id}", ['status' => 'bogus'])
            ->assertSessionHasErrors('status');
    }

    public function test_delete_keeps_the_points(): void
    {
        $testimonial = $this->submitted($this->makeCourse());

        $this->actingAs($this->admin())
            ->delete("/admin/testimonials/{$testimonial->id}")
            ->assertRedirect();

        $this->assertSame(0, Testimonial::count());
        $this->assertSame(1, PointTransaction::count());
        $this->assertSame(100, (int) User::where('email', 'lead@example.com')->value('points'));
    }

    public function test_members_cannot_moderate(): void
    {
        $testimonial = $this->submitted($this->makeCourse());
        $member = User::factory()->create();

        $this->actingAs($member)
            ->patch("/admin/testimonials/{$testimonial->id}", ['status' => 'published'])
            ->assertRedirect('/');

        $this->assertSame('pending', $testimonial->fresh()->status);
    }

    // ── Sales page (FR-245) ─────────────────────────────────────

    public function test_sales_page_lists_only_this_courses_published_testimonials(): void
    {
        $course = $this->makeCourse();
        $other = $this->makeCourse();

        $older = $this->submitted($course, 'a@example.com', now()->subDays(10)->startOfHour());
        $newer = $this->submitted($course, 'b@example.com', now()->subDays(2)->startOfHour());
        $pending = $this->submitted($course, 'c@example.com');
        $elsewhere = $this->submitted($other, 'd@example.com');

        foreach ([$older, $newer, $elsewhere] as $t) {
            $t->update(['status' => 'published', 'published_at' => now()]);
        }

        $this->get("/course/{$course->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('testimonials', 2)
                ->where('testimonials.0.id', $newer->id)
                ->where('testimonials.1.id', $older->id)
                ->missing('testimonials.0.lead_id')
                ->missing('testimonials.0.consulted_at')
                ->where('testimonials.0.identity_label', '諮詢客戶'));
    }

    public function test_sales_page_of_ordinary_course_has_no_testimonials(): void
    {
        $course = $this->makeCourse(['type' => 'lecture', 'high_ticket_hide_price' => false]);

        $this->get("/course/{$course->slug}")
            ->assertInertia(fn (Assert $page) => $page->where('testimonials', []));
    }

    // ── Identity (FR-246 – FR-249) ──────────────────────────────

    private function buy(Course $course, string $email, string $status = 'paid'): void
    {
        $user = User::firstOrCreate(['email' => $email], ['nickname' => 'Buyer', 'password' => 'x']);

        Purchase::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'buyer_email' => $email,
            'amount'      => 30000,
            'currency'    => 'TWD',
            'status'      => $status,
            'type'        => 'lead_conversion',
        ]);
    }

    public function test_client_without_a_purchase_is_a_consultation_client(): void
    {
        $testimonial = $this->submitted($this->makeCourse());

        $this->assertSame('consultation', $testimonial->identity);
    }

    public function test_client_holding_the_course_is_a_student(): void
    {
        $course = $this->makeCourse();
        $this->buy($course, 'lead@example.com');

        $lead = $this->makeLead($course);
        $this->issueLink($lead);
        $token = $lead->fresh()->testimonial_token;

        // The form already says so before anything is submitted.
        $this->get("/testimonials/{$token}")
            ->assertInertia(fn (Assert $page) => $page->where('identity_label', '課程學員'));

        $this->post("/testimonials/{$token}", $this->payload());

        $this->assertSame('student', Testimonial::firstOrFail()->identity);
    }

    public function test_refunded_purchase_does_not_make_a_student(): void
    {
        $course = $this->makeCourse();
        $this->buy($course, 'lead@example.com', 'refunded');

        $this->assertSame('consultation', $this->submitted($course)->identity);
    }

    public function test_a_purchase_of_another_course_does_not_count(): void
    {
        $course = $this->makeCourse();
        $this->buy($this->makeCourse(), 'lead@example.com');

        $this->assertSame('consultation', $this->submitted($course)->identity);
    }

    public function test_admin_can_switch_identity_without_touching_status_or_points(): void
    {
        $testimonial = $this->submitted($this->makeCourse());

        $this->actingAs($this->admin())
            ->patch("/admin/testimonials/{$testimonial->id}", ['identity' => 'student'])
            ->assertSessionHasNoErrors();

        $testimonial->refresh();
        $this->assertSame('student', $testimonial->identity);
        $this->assertSame('pending', $testimonial->status);
        $this->assertNull($testimonial->published_at);
        $this->assertSame(1, PointTransaction::count());
        $this->assertSame(100, (int) User::where('email', 'lead@example.com')->value('points'));
    }

    public function test_update_rejects_bad_identity_and_empty_body(): void
    {
        $testimonial = $this->submitted($this->makeCourse());

        $this->actingAs($this->admin())
            ->patch("/admin/testimonials/{$testimonial->id}", ['identity' => 'graduate'])
            ->assertSessionHasErrors('identity');

        $this->actingAs($this->admin())
            ->patch("/admin/testimonials/{$testimonial->id}", [])
            ->assertSessionHasErrors();

        $this->assertSame('consultation', $testimonial->fresh()->identity);
    }

    public function test_leads_list_carries_the_identity(): void
    {
        $testimonial = $this->submitted($this->makeCourse());

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads')
            ->assertInertia(fn (Assert $page) => $page
                ->where('leads.data', fn ($rows) => collect($rows)
                    ->firstWhere('id', $testimonial->lead_id)['testimonial']['identity'] === 'consultation'));
    }

    // ── Others' testimonials on the form page (FR-251) ──────────

    public function test_consultation_client_sees_published_testimonials_and_course_link(): void
    {
        $course = $this->makeCourse();
        $other = $this->makeCourse();
        $shown = $this->submitted($course, 'a@example.com');
        $shown->update(['status' => 'published', 'published_at' => now()]);
        $this->submitted($course, 'b@example.com'); // pending
        $elsewhere = $this->submitted($other, 'c@example.com');
        $elsewhere->update(['status' => 'published', 'published_at' => now()]);

        $lead = $this->makeLead($course, null, ['email' => 'client@example.com']);
        $this->issueLink($lead);

        $this->get('/testimonials/' . $lead->fresh()->testimonial_token)
            ->assertInertia(fn (Assert $page) => $page
                ->has('testimonials', 1)
                ->where('testimonials.0.id', $shown->id)
                ->where('testimonials.0.identity_label', '諮詢客戶')
                ->missing('testimonials.0.consulted_at')
                ->where('course_url', route('course.show', $course)));
    }

    public function test_own_testimonial_is_not_listed_on_the_thank_you_page(): void
    {
        $course = $this->makeCourse();
        $mine = $this->submitted($course);
        $mine->update(['status' => 'published', 'published_at' => now()]);
        $theirs = $this->submitted($course, 'other@example.com');
        $theirs->update(['status' => 'published', 'published_at' => now()]);

        $token = HighTicketLead::find($mine->lead_id)->testimonial_token;

        $this->get("/testimonials/{$token}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('submitted', true)
                ->has('testimonials', 1)
                ->where('testimonials.0.id', $theirs->id));
    }

    public function test_students_are_not_shown_sales_testimonials(): void
    {
        $course = $this->makeCourse();
        $this->submitted($course, 'a@example.com')->update(['status' => 'published', 'published_at' => now()]);
        $this->buy($course, 'lead@example.com');

        $lead = $this->makeLead($course);
        $this->issueLink($lead);

        $this->get('/testimonials/' . $lead->fresh()->testimonial_token)
            ->assertInertia(fn (Assert $page) => $page
                ->where('identity_label', '課程學員')
                ->where('testimonials', [])
                ->where('course_url', null));
    }

    // ── Settings (FR-243) ───────────────────────────────────────

    public function test_points_settings_page_reads_and_saves_the_reward(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin/settings/points')
            ->assertInertia(fn (Assert $page) => $page->where('points.testimonial_reward_points', 100));

        $this->actingAs($admin)->post('/admin/settings/points', [
            'referral_threshold_amount' => 3000,
            'referral_reward_rate'      => 10,
            'homework_reward_points'    => 100,
            'referral_maturity_days'    => 14,
            'referral_discount_amount'  => 150,
            'testimonial_reward_points' => 300,
        ])->assertSessionHasNoErrors();

        $this->assertSame('300', SiteSetting::get('testimonial_reward_points'));
    }
}
