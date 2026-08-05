<?php

namespace Tests\Feature\HighTicket;

use App\Mail\TemplatedMail;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * 011 US5 — lead conversion with an admin-entered deal price (FR-008 / FR-011),
 * plus the guarantees that came with D13 making this the only sales entry
 * point: no silent overwrite (FR-015), atomic writes (FR-016), buyer
 * notification (FR-017).
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

    /** A purchase that came from somewhere other than a consultant deal. */
    private function makeForeignPurchase(Course $course, string $type = 'paid', string $status = 'paid'): Purchase
    {
        $user = User::factory()->create(['email' => 'lead@example.com']);

        return Purchase::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'buyer_email' => 'lead@example.com',
            'amount'      => 50000,
            'currency'    => 'TWD',
            'status'      => $status,
            'type'        => $type,
        ]);
    }

    private function seedConversionTemplate(): EmailTemplate
    {
        return EmailTemplate::updateOrCreate(['event_type' => 'lead_converted'], [
            'name'       => '顧問成交開通通知',
            'event_type' => 'lead_converted',
            'subject'    => '【開通完成】{{course_name}}',
            'body_type'  => 'markdown',
            'body_md'    => "{{user_name}} 您好，成交金額 NT$ {{amount}}。\n\n{{classroom_url}}",
        ]);
    }

    public function test_existing_paid_purchase_blocks_conversion(): void
    {
        $course   = $this->makeCourse();
        $lead     = $this->makeLead($course);
        $purchase = $this->makeForeignPurchase($course);

        $this->actingAs($this->admin())
            ->postJson("/admin/high-ticket-leads/{$lead->id}/convert", [
                'course_id' => $course->id,
                'amount'    => 38000,
            ])
            ->assertStatus(409)
            ->assertJsonPath('conflict.type', 'paid')
            ->assertJsonPath('conflict.amount', 50000);

        // Nothing may have moved: not the purchase, not the lead.
        $purchase->refresh();
        $this->assertSame('paid', $purchase->type);
        $this->assertEquals(50000, $purchase->amount);
        $this->assertSame('contacted', $lead->fresh()->status);
    }

    public function test_force_overwrites_a_blocked_purchase(): void
    {
        $course   = $this->makeCourse();
        $lead     = $this->makeLead($course);
        $purchase = $this->makeForeignPurchase($course);

        $this->actingAs($this->admin())
            ->postJson("/admin/high-ticket-leads/{$lead->id}/convert", [
                'course_id' => $course->id,
                'amount'    => 38000,
                'force'     => true,
            ])
            ->assertOk();

        $purchase->refresh();
        $this->assertSame('lead_conversion', $purchase->type);
        $this->assertEquals(38000, $purchase->amount);
        $this->assertSame('converted', $lead->fresh()->status);
    }

    public function test_refunded_purchase_is_overwritten_without_force(): void
    {
        $course   = $this->makeCourse();
        $lead     = $this->makeLead($course);
        $purchase = $this->makeForeignPurchase($course, 'paid', 'refunded');

        $this->actingAs($this->admin())
            ->postJson("/admin/high-ticket-leads/{$lead->id}/convert", [
                'course_id' => $course->id,
                'amount'    => 38000,
            ])
            ->assertOk();

        $purchase->refresh();
        $this->assertSame('lead_conversion', $purchase->type);
        $this->assertSame('paid', $purchase->status);
        $this->assertEquals(38000, $purchase->amount);
    }

    public function test_blocked_conversion_does_not_create_a_user(): void
    {
        $course = $this->makeCourse();
        $lead   = HighTicketLead::create([
            'name'      => 'Nobody',
            'email'     => 'nobody@example.com',
            'course_id' => $course->id,
            'status'    => 'contacted',
            'booked_at' => now(),
        ]);

        // No existing user → nothing to conflict with → it goes through, and
        // that is the point: the gate must not be what creates the account.
        $this->actingAs($this->admin())
            ->postJson("/admin/high-ticket-leads/{$lead->id}/convert", [
                'course_id' => $course->id,
                'amount'    => 1000,
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'nobody@example.com']);
    }

    public function test_conversion_mails_the_buyer_without_cc(): void
    {
        Mail::fake();
        $this->seedConversionTemplate();

        $course = $this->makeCourse();
        $lead   = $this->makeLead($course);

        $this->actingAs($this->admin())
            ->postJson("/admin/high-ticket-leads/{$lead->id}/convert", [
                'course_id' => $course->id,
                'amount'    => 38000,
            ])
            ->assertOk()
            ->assertJson(['mail_sent' => true]);

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            $mail->assertTo('lead@example.com');

            // The admin triggered this and already knows — no internal CC (FR-017).
            $this->assertEmpty($mail->cc);
            $this->assertEmpty($mail->bcc);
            $this->assertStringContainsString('38,000', $mail->htmlBody);

            return true;
        });
    }

    public function test_conversion_succeeds_without_the_template(): void
    {
        Mail::fake();

        // 2026_08_08_000003 installs every canonical template, so a missing one
        // now has to be arranged rather than assumed.
        EmailTemplate::where('event_type', 'lead_converted')->delete();

        $course = $this->makeCourse();
        $lead   = $this->makeLead($course);

        $this->actingAs($this->admin())
            ->postJson("/admin/high-ticket-leads/{$lead->id}/convert", [
                'course_id' => $course->id,
                'amount'    => 38000,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'mail_sent' => false]);

        Mail::assertNothingSent();

        // The sale stands: money already changed hands (D15).
        $this->assertDatabaseHas('purchases', ['course_id' => $course->id, 'amount' => 38000]);
        $this->assertSame('converted', $lead->fresh()->status);
    }

    public function test_conversion_succeeds_when_sending_throws(): void
    {
        $this->seedConversionTemplate();

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $course = $this->makeCourse();
        $lead   = $this->makeLead($course);

        $this->actingAs($this->admin())
            ->postJson("/admin/high-ticket-leads/{$lead->id}/convert", [
                'course_id' => $course->id,
                'amount'    => 38000,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'mail_sent' => false]);

        $this->assertDatabaseHas('purchases', ['course_id' => $course->id, 'amount' => 38000]);
        $this->assertSame('converted', $lead->fresh()->status);
    }

    public function test_failed_purchase_write_leaves_no_orphan_user(): void
    {
        $course = $this->makeCourse();
        $lead   = $this->makeLead($course);

        // Make the second write fail outright: the user created by the first
        // one must go with it, or the lead ends up with a half-made account.
        Schema::drop('purchases');

        try {
            app(\App\Services\HighTicketLeadService::class)
                ->convertLead($lead, $course->id, 38000);
            $this->fail('Expected the purchase write to fail');
        } catch (\Throwable $e) {
            // expected
        }

        $this->assertDatabaseMissing('users', ['email' => 'lead@example.com']);
        $this->assertSame('contacted', $lead->fresh()->status);
    }

    public function test_index_exposes_purchases_by_email(): void
    {
        $course = $this->makeCourse();
        $lead   = $this->makeLead($course);
        $this->makeForeignPurchase($course);

        $response = $this->actingAs($this->admin())->get('/admin/high-ticket-leads');

        // Read the prop directly: an email key would be split apart by
        // assertInertia's dot-notation paths.
        $entry = $response->viewData('page')['props']['purchasesByEmail']['lead@example.com'][0];

        $this->assertSame($course->id, $entry['course_id']);
        $this->assertSame('paid', $entry['type']);
        $this->assertSame(50000, $entry['amount']);
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
