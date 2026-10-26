<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationNote;
use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 011 US50 — admins delete checked leads from the booking list.
 *
 * Deletion is hard and irreversible, so what these tests protect is the edge:
 * live bookings and closed deals survive, and nothing is left pointing at a
 * lead that no longer exists.
 */
class LeadBatchDeleteTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::create([
            'name' => 'HT', 'slug' => 'ht-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 50000, 'instructor_name' => 'I', 'type' => 'high_ticket', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function lead(array $attributes = []): HighTicketLead
    {
        return HighTicketLead::create(array_merge([
            'name'      => 'Fake',
            'email'     => uniqid() . '@example.com',
            'course_id' => $this->course->id,
            'status'    => 'pending',
            'booked_at' => now(),
        ], $attributes));
    }

    private function destroy(array $ids, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->admin())
            ->deleteJson('/admin/high-ticket-leads', ['lead_ids' => $ids]);
    }

    public function test_admin_deletes_the_checked_leads_only(): void
    {
        $a = $this->lead();
        $b = $this->lead();
        $untouched = $this->lead();

        $this->destroy([$a->id, $b->id])
            ->assertOk()
            ->assertJson(['deleted' => 2, 'skipped' => 0, 'message' => '已刪除 2 筆']);

        $this->assertSame([$untouched->id], HighTicketLead::pluck('id')->all());
    }

    public function test_live_bookings_and_closed_deals_are_skipped(): void
    {
        $junk = $this->lead();
        $booked = $this->lead(['confirmed_at' => now()]);
        $converted = $this->lead(['status' => 'converted']);
        // A cancelled booking is no longer live, so it may go
        $cancelled = $this->lead(['status' => 'cancelled', 'confirmed_at' => now()->subDay(), 'cancelled_at' => now()]);

        $this->destroy([$junk->id, $booked->id, $converted->id, $cancelled->id])
            ->assertOk()
            ->assertJson([
                'deleted' => 2,
                'skipped' => 2,
                'message' => '已刪除 2 筆；另有 2 筆為有效預約或已成交，未刪除',
            ]);

        $this->assertEqualsCanonicalizing([$booked->id, $converted->id], HighTicketLead::pluck('id')->all());
    }

    public function test_a_held_slot_goes_back_to_the_pool(): void
    {
        $lead = $this->lead(['confirm_expires_at' => now()->addHour()]);
        $slot = ConsultationSlot::create([
            'starts_at'  => now()->addDay(),
            'lead_id'    => $lead->id,
            'held_until' => now()->addHour(),
        ]);

        $this->destroy([$lead->id])->assertOk();

        $slot->refresh();
        $this->assertNull($slot->lead_id);
        $this->assertNull($slot->held_until);
    }

    public function test_testimonials_and_consultation_notes_survive_without_the_link(): void
    {
        $lead = $this->lead(['status' => 'contacted']);
        $testimonial = Testimonial::create([
            'lead_id' => $lead->id, 'course_id' => $this->course->id, 'nickname' => 'N',
            'content' => 'c', 'consulted_at' => now(),
        ]);
        $note = ConsultationNote::create([
            'email' => $lead->email, 'source' => ConsultationNote::SOURCE_HIGH_TICKET,
            'lead_id' => $lead->id, 'met_at' => now()->subHour(),
        ]);

        $this->destroy([$lead->id])->assertOk()->assertJson(['deleted' => 1]);

        $this->assertNull($testimonial->fresh()->lead_id);
        $this->assertNull($note->fresh()->lead_id);
    }

    public function test_sales_consultants_cannot_delete(): void
    {
        $lead = $this->lead();
        $consultant = User::factory()->create(['role' => 'member', 'is_sales_consultant' => true]);

        // The admin middleware turns non-admins away with a redirect, site-wide
        $this->destroy([$lead->id], $consultant)->assertRedirect('/');

        $this->assertTrue(HighTicketLead::whereKey($lead->id)->exists());
    }

    public function test_an_empty_selection_is_rejected(): void
    {
        $this->destroy([])->assertStatus(422);
    }
}
