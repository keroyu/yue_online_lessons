<?php

namespace Tests\Feature\HighTicket;

use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 011 US5 — lead conversion with an admin-entered deal price (FR-008 / FR-011).
 * The deal amount is written to Purchase.amount so consultant-closed sales
 * show up in the transactions list and revenue chart.
 */
class LeadConvertTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name'            => 'High Ticket Course',
            'slug'            => 'ht-course-' . uniqid(),
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 50000,
            'instructor_name' => 'Tester',
            // sqlite's CHECK on the type enum predates high_ticket (MySQL-only
            // ALTER); convert accepts any course type, so use a legal one.
            'type'            => 'lecture',
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ], $overrides));
    }

    private function makeLead(Course $course): HighTicketLead
    {
        return HighTicketLead::create([
            'name'      => 'Lead Person',
            'email'     => 'lead@example.com',
            'course_id' => $course->id,
            'status'    => 'contacted',
            'booked_at' => now(),
        ]);
    }

    public function test_convert_writes_deal_amount_to_purchase(): void
    {
        $course = $this->makeCourse();
        $lead   = $this->makeLead($course);

        $this->actingAs($this->admin())
            ->postJson("/admin/high-ticket-leads/{$lead->id}/convert", [
                'course_id' => $course->id,
                'amount'    => 38000,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('purchases', [
            'course_id' => $course->id,
            'amount'    => 38000,
            'status'    => 'paid',
            'type'      => 'lead_conversion',
        ]);
        $this->assertSame('converted', $lead->fresh()->status);
    }

    public function test_convert_requires_amount(): void
    {
        $course = $this->makeCourse();
        $lead   = $this->makeLead($course);

        $this->actingAs($this->admin())
            ->postJson("/admin/high-ticket-leads/{$lead->id}/convert", [
                'course_id' => $course->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');
    }

    public function test_convert_allows_zero_but_rejects_negative_amount(): void
    {
        $course = $this->makeCourse();
        $lead   = $this->makeLead($course);
        $admin  = $this->admin();

        $this->actingAs($admin)
            ->postJson("/admin/high-ticket-leads/{$lead->id}/convert", [
                'course_id' => $course->id,
                'amount'    => -1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');

        $this->actingAs($admin)
            ->postJson("/admin/high-ticket-leads/{$lead->id}/convert", [
                'course_id' => $course->id,
                'amount'    => 0,
            ])
            ->assertOk();

        $this->assertDatabaseHas('purchases', [
            'course_id' => $course->id,
            'amount'    => 0,
            'type'      => 'lead_conversion',
        ]);
    }

    public function test_repeat_convert_overwrites_amount_without_duplicate_purchase(): void
    {
        $course = $this->makeCourse();
        $lead   = $this->makeLead($course);
        $admin  = $this->admin();

        foreach ([30000, 42000] as $amount) {
            $this->actingAs($admin)
                ->postJson("/admin/high-ticket-leads/{$lead->id}/convert", [
                    'course_id' => $course->id,
                    'amount'    => $amount,
                ])
                ->assertOk();
        }

        $user = User::where('email', 'lead@example.com')->firstOrFail();
        $purchases = Purchase::where('user_id', $user->id)->where('course_id', $course->id)->get();

        $this->assertCount(1, $purchases);
        $this->assertEquals(42000, $purchases->first()->amount);
    }

    public function test_index_grantable_courses_include_display_price(): void
    {
        $course = $this->makeCourse([
            'price'          => 30000,
            'original_price' => 50000,
            'promo_ends_at'  => now()->addDay(),
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads')
            ->assertInertia(fn ($page) => $page
                ->where('grantableCourses.0.id', $course->id)
                // Promo active → display price is the promo price.
                ->where('grantableCourses.0.display_price', 30000)
            );
    }
}
