<?php

namespace Tests\Feature\HighTicket;

use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 011 FR-263 — the booking list shows what a converted lead actually paid.
 *
 * Same number as the CSV's 成交金額 (FR-158), read live from purchases, so a
 * plan upgrade's top-up shows up on the next load without anything to sync.
 */
class LeadDealAmountTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::create([
            'name' => '主課程', 'slug' => 'c-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 50000, 'instructor_name' => 'I', 'type' => 'high_ticket', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ]);
    }

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin-deal@example.com'], ['role' => 'admin']);
    }

    private function lead(array $attributes = []): HighTicketLead
    {
        return HighTicketLead::create(array_merge([
            'name'      => '王小明',
            'email'     => 'lead@example.com',
            'phone'     => '0912345678',
            'course_id' => $this->course->id,
            'status'    => 'converted',
            'booked_at' => now(),
        ], $attributes));
    }

    private function conversion(string $email, int $amount, string $status = 'paid', ?Course $course = null): Purchase
    {
        $user = User::firstOrCreate(['email' => $email], ['role' => 'member']);

        return Purchase::create([
            'user_id'     => $user->id,
            'course_id'   => ($course ?? $this->course)->id,
            'buyer_email' => $email,
            'amount'      => $amount,
            'currency'    => 'TWD',
            'status'      => $status,
            'type'        => 'lead_conversion',
        ]);
    }

    /** @return array<int, array<string, mixed>> leads on the list page, keyed by id */
    private function listed(): array
    {
        $leads = $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads')
            ->assertOk()
            ->viewData('page')['props']['leads']['data'];

        return collect($leads)->keyBy('id')->all();
    }

    public function test_a_converted_lead_carries_its_deal_amount(): void
    {
        $lead = $this->lead();
        $this->conversion('lead@example.com', 36000);

        $this->assertSame(36000, $this->listed()[$lead->id]['deal_amount']);
    }

    public function test_an_upgrade_top_up_raises_the_amount(): void
    {
        $lead = $this->lead();
        $purchase = $this->conversion('lead@example.com', 36000);

        $this->actingAs($this->admin())
            ->patchJson("/admin/members/{$purchase->user_id}/purchases/{$purchase->id}/plan", [
                'course_plan_id'    => null,
                'additional_amount' => 20000,
            ])->assertOk();

        $this->assertSame(56000, $this->listed()[$lead->id]['deal_amount']);
    }

    public function test_a_refunded_sale_does_not_count(): void
    {
        $lead = $this->lead();
        $this->conversion('lead@example.com', 36000, 'refunded');

        $this->assertNull($this->listed()[$lead->id]['deal_amount']);
    }

    public function test_two_sales_under_one_email_add_up_and_list_each_line(): void
    {
        $other = Course::create([
            'name' => '迷你課', 'slug' => 'c-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 420, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ]);
        $lead = $this->lead();
        $this->conversion('lead@example.com', 36000);
        $this->conversion('lead@example.com', 8800, 'paid', $other);

        $row = $this->listed()[$lead->id];

        $this->assertSame(44800, $row['deal_amount']);
        $this->assertSame([
            ['amount' => 36000, 'course' => '主課程'],
            ['amount' => 8800, 'course' => '迷你課'],
        ], $row['deal_lines']);
    }

    public function test_a_lead_without_a_sale_has_no_amount(): void
    {
        $pending = $this->lead(['email' => 'pending@example.com', 'status' => 'pending']);
        $handFlipped = $this->lead(['email' => 'flipped@example.com']);

        $rows = $this->listed();

        $this->assertNull($rows[$pending->id]['deal_amount']);
        $this->assertNull($rows[$handFlipped->id]['deal_amount'], '只點了成交色塊、沒有購買紀錄');
        $this->assertSame([], $rows[$handFlipped->id]['deal_lines']);
    }

    public function test_the_list_and_the_csv_agree(): void
    {
        $lead = $this->lead();
        $this->conversion('lead@example.com', 36888);

        $body = $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads/export?' . http_build_query(['ids' => [$lead->id]]))
            ->assertOk()
            ->streamedContent();
        $lines = array_values(array_filter(explode("\n", str_replace("\xEF\xBB\xBF", '', $body)), fn ($l) => trim($l) !== ''));
        $header = str_getcsv(rtrim($lines[0], "\r"));
        $row = str_getcsv(rtrim($lines[1], "\r"));
        $csvAmount = $row[array_search('成交金額', $header, true)];

        $this->assertSame((int) $csvAmount, $this->listed()[$lead->id]['deal_amount']);
    }
}
