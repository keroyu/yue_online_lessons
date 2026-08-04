<?php

namespace Tests\Feature\HighTicket;

use App\Jobs\SubscribeDripLeadJob;
use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\HighTicketLead;
use App\Models\User;
use App\Services\HighTicketLeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * 011 US5 — 「加入序列信」recipient selection (FR-007).
 *
 * The status whitelist was dropped on 2026-08-05: the admin's checkbox is the
 * decision, matching notifySlot (FR-006). What still guards the operation is
 * the duplicate-subscription check, so that is what these tests pin down.
 */
class LeadSubscribeDripTest extends TestCase
{
    use RefreshDatabase;

    private function makeDripCourse(): Course
    {
        return Course::create([
            'name'            => 'Drip Course',
            'slug'            => 'drip-' . uniqid(),
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 0,
            'instructor_name' => 'Tester',
            'type'            => 'lecture',
            'status'          => 'selling',
            'course_type'     => 'drip',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ])->fresh();
    }

    private function lead(string $status, string $email): HighTicketLead
    {
        return HighTicketLead::create([
            'name'      => 'Lead ' . $status,
            'email'     => $email,
            'course_id' => 1,
            'status'    => $status,
            'booked_at' => now(),
        ]);
    }

    private function subscribe(array $leads, Course $drip): array
    {
        return app(HighTicketLeadService::class)
            ->subscribeDrip(collect($leads)->pluck('id')->all(), $drip->id);
    }

    /**
     * FR-007 as amended: every status is eligible. Previously 已面談 / 已成交 /
     * 已取消 were silently dropped, so the admin saw "已加入 3 位" after ticking 6.
     */
    public function test_every_status_can_be_subscribed(): void
    {
        Queue::fake();
        $drip = $this->makeDripCourse();

        $leads = [
            $this->lead('pending', 'a@example.com'),
            $this->lead('contacted', 'b@example.com'),
            $this->lead('no_response', 'c@example.com'),
            $this->lead('converted', 'd@example.com'),
            $this->lead('closed', 'e@example.com'),
            $this->lead('cancelled', 'f@example.com'),
        ];

        $result = $this->subscribe($leads, $drip);

        $this->assertSame(6, $result['dispatched']);
        $this->assertSame(0, $result['skipped']);
        Queue::assertPushed(SubscribeDripLeadJob::class, 6);
    }

    /** The guard that actually matters: nobody gets two live sequences. */
    public function test_a_lead_with_an_active_subscription_is_skipped(): void
    {
        Queue::fake();
        $drip = $this->makeDripCourse();

        $subscribed = $this->lead('pending', 'taken@example.com');
        $fresh = $this->lead('pending', 'free@example.com');

        $user = User::factory()->create(['email' => 'taken@example.com']);
        DripSubscription::create([
            'user_id'       => $user->id,
            'course_id'     => $drip->id,
            'status'        => 'active',
            'subscribed_at' => now(),
        ]);

        $result = $this->subscribe([$subscribed, $fresh], $drip);

        $this->assertSame(1, $result['dispatched']);
        $this->assertSame(1, $result['skipped']);
    }

    /** A finished or cancelled subscription is not a live one. */
    public function test_an_inactive_subscription_does_not_block(): void
    {
        Queue::fake();
        $drip = $this->makeDripCourse();

        $lead = $this->lead('closed', 'done@example.com');
        $user = User::factory()->create(['email' => 'done@example.com']);

        DripSubscription::create([
            'user_id'       => $user->id,
            'course_id'     => $drip->id,
            'status'        => 'unsubscribed',
            'subscribed_at' => now(),
        ]);

        $result = $this->subscribe([$lead], $drip);

        $this->assertSame(1, $result['dispatched']);
        $this->assertSame(0, $result['skipped']);
    }

    public function test_endpoint_requires_staff(): void
    {
        $drip = $this->makeDripCourse();
        $lead = $this->lead('pending', 'x@example.com');

        $this->postJson('/admin/high-ticket-leads/subscribe-drip', [
            'lead_ids'       => [$lead->id],
            'drip_course_id' => $drip->id,
        ])->assertStatus(401);
    }

    public function test_staff_can_subscribe_a_converted_lead_through_the_endpoint(): void
    {
        Queue::fake();
        $drip = $this->makeDripCourse();
        $lead = $this->lead('converted', 'buyer@example.com');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->postJson('/admin/high-ticket-leads/subscribe-drip', [
                'lead_ids'       => [$lead->id],
                'drip_course_id' => $drip->id,
            ])
            ->assertOk()
            ->assertJson(['dispatched' => 1, 'skipped' => 0]);
    }
}
