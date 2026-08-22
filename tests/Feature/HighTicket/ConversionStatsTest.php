<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 011 US22 — the deal summary beside the status pills (FR-097 … FR-100).
 *
 * Four things here are easy to get subtly wrong and invisible when they are:
 * the consultant/course filters must reach it, the status tab must not, people
 * are counted per email rather than per purchase, and the month boundary is
 * Taipei's rather than the server's UTC.
 */
class ConversionStatsTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = $this->makeCourse('主課程');
    }

    private function makeCourse(string $name): Course
    {
        return Course::create([
            'name' => $name, 'slug' => 'c-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 50000, 'instructor_name' => 'I', 'type' => 'high_ticket', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ]);
    }

    /** firstOrCreate: several tests hit the page more than once. */
    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin-stats@example.com'], ['role' => 'admin']);
    }

    /** A converted lead plus the purchase that conversion would have written. */
    private function converted(
        string $email,
        int $amount,
        ?Course $course = null,
        ?Carbon $at = null,
        ?User $consultant = null,
        string $status = 'paid',
    ): HighTicketLead {
        $course ??= $this->course;
        $at ??= now();

        $lead = HighTicketLead::create([
            'name' => $email, 'email' => $email, 'course_id' => $course->id,
            'status' => 'converted', 'booked_at' => now(),
            'consultant_id' => $consultant?->id,
        ]);

        $user = User::firstOrCreate(['email' => $email], ['role' => 'member']);

        $purchase = Purchase::create([
            'user_id' => $user->id, 'course_id' => $course->id, 'buyer_email' => $email,
            'amount' => $amount, 'currency' => 'TWD', 'status' => $status,
            'type' => 'lead_conversion',
        ]);

        // created_at is what the period buckets read (FR-100).
        $purchase->forceFill(['created_at' => $at])->save();

        return $lead;
    }

    /** @return array{month: array, year: array} */
    private function stats(array $query = []): array
    {
        return $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads?' . http_build_query($query))
            ->assertOk()
            ->viewData('page')['props']['conversionStats'];
    }

    // ── the basics ──────────────────────────────────────────────────────────

    public function test_summary_counts_people_and_sums_amounts(): void
    {
        $this->converted('a@example.com', 30000);
        $this->converted('b@example.com', 50000);

        $stats = $this->stats();

        $this->assertSame(2, $stats['month']['people']);
        $this->assertSame(80000, $stats['month']['amount']);
    }

    public function test_zero_state_is_reported_rather_than_omitted(): void
    {
        $stats = $this->stats();

        $this->assertSame(0, $stats['month']['people']);
        $this->assertSame(0, $stats['month']['amount']);
        $this->assertSame(0, $stats['year']['people']);
        $this->assertSame(0, $stats['year']['amount']);
    }

    // ── FR-099: dedupe by person, ignore refunds ────────────────────────────

    public function test_one_person_buying_two_courses_counts_as_one_person(): void
    {
        $second = $this->makeCourse('第二課程');
        $this->converted('same@example.com', 30000);
        $this->converted('same@example.com', 20000, $second);

        $stats = $this->stats();

        $this->assertSame(1, $stats['month']['people'], '同一 email 應只算 1 人');
        $this->assertSame(50000, $stats['month']['amount'], '但兩筆金額都要計入');
    }

    public function test_refunded_conversions_are_excluded(): void
    {
        $this->converted('paid@example.com', 30000);
        $this->converted('refunded@example.com', 90000, status: 'refunded');

        $stats = $this->stats();

        $this->assertSame(1, $stats['month']['people']);
        $this->assertSame(30000, $stats['month']['amount']);
    }

    public function test_non_lead_conversion_purchases_are_excluded(): void
    {
        $lead = $this->converted('a@example.com', 30000);

        // A gift to the same person must not inflate the consultant's numbers.
        $user = User::where('email', 'a@example.com')->firstOrFail();
        Purchase::create([
            'user_id' => $user->id, 'course_id' => $this->makeCourse('贈品課')->id,
            'buyer_email' => 'a@example.com', 'amount' => 99999, 'currency' => 'TWD',
            'status' => 'paid', 'type' => 'gift',
        ]);

        $this->assertSame(30000, $this->stats()['month']['amount']);
    }

    // ── FR-097: which filters reach it ──────────────────────────────────────

    public function test_consultant_filter_narrows_the_summary(): void
    {
        $alice = User::create(['email' => 'alice@example.com', 'role' => 'admin', 'is_sales_consultant' => true]);
        $bob = User::create(['email' => 'bob@example.com', 'role' => 'admin', 'is_sales_consultant' => true]);

        $this->converted('x@example.com', 30000, consultant: $alice);
        $this->converted('y@example.com', 70000, consultant: $bob);

        $this->assertSame(30000, $this->stats(['consultant' => $alice->id])['month']['amount']);
        $this->assertSame(70000, $this->stats(['consultant' => $bob->id])['month']['amount']);
        $this->assertSame(100000, $this->stats()['month']['amount']);
    }

    public function test_course_filter_narrows_the_summary(): void
    {
        $other = $this->makeCourse('另一門課');
        $this->converted('x@example.com', 30000);
        $this->converted('y@example.com', 70000, $other);

        $this->assertSame(30000, $this->stats(['course_id' => $this->course->id])['month']['amount']);
        $this->assertSame(70000, $this->stats(['course_id' => $other->id])['month']['amount']);
    }

    public function test_status_tab_does_not_change_the_summary(): void
    {
        // FR-067's rule: clicking into a status must not move the denominator.
        $this->converted('x@example.com', 30000);
        HighTicketLead::create([
            'name' => 'pending person', 'email' => 'p@example.com',
            'course_id' => $this->course->id, 'status' => 'pending', 'booked_at' => now(),
        ]);

        $unfiltered = $this->stats()['month'];

        foreach (['pending', 'converted', 'closed'] as $status) {
            $this->assertSame($unfiltered, $this->stats(['status' => $status])['month'],
                "狀態 {$status} 不得改變摘要");
        }
    }

    // ── FR-098: Taipei boundaries ───────────────────────────────────────────

    public function test_month_boundary_follows_taipei_not_utc(): void
    {
        // 2026-09-01 07:00 Taipei is still 2026-08-31 23:00 UTC. A naive
        // whereMonth() on the UTC column would file this under August.
        Carbon::setTestNow(Carbon::parse('2026-09-05 12:00', 'Asia/Taipei')->utc());

        $this->converted('sept@example.com', 60000,
            at: Carbon::parse('2026-09-01 07:00', 'Asia/Taipei')->utc());

        $stats = $this->stats();

        $this->assertSame(1, $stats['month']['people'], '台北 9/1 早上的成交必須算在 9 月');
        $this->assertSame(60000, $stats['month']['amount']);

        Carbon::setTestNow();
    }

    public function test_previous_month_is_excluded_but_still_counts_for_the_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-05 12:00', 'Asia/Taipei')->utc());

        $this->converted('aug@example.com', 40000,
            at: Carbon::parse('2026-08-20 15:00', 'Asia/Taipei')->utc());
        $this->converted('sep@example.com', 60000,
            at: Carbon::parse('2026-09-03 15:00', 'Asia/Taipei')->utc());

        $stats = $this->stats();

        $this->assertSame(60000, $stats['month']['amount'], '本月只有 9 月那筆');
        $this->assertSame(100000, $stats['year']['amount'], '年度兩筆都算');
        $this->assertSame(2, $stats['year']['people']);

        Carbon::setTestNow();
    }

    public function test_last_year_is_excluded_from_the_year_total(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-05 12:00', 'Asia/Taipei')->utc());

        $this->converted('old@example.com', 40000,
            at: Carbon::parse('2025-12-31 23:00', 'Asia/Taipei')->utc());

        $this->assertSame(0, $this->stats()['year']['amount']);

        Carbon::setTestNow();
    }

    // ── 011 US28: the meeting-time filter reaches the summary too ───────────

    /**
     * FR-146 — `met` lives in the same builder as the other filters, so the
     * money follows it. What the summary then reads is "what these people, the
     * ones I just met, have bought" — narrower than the site total on purpose.
     */
    public function test_meeting_time_filter_narrows_the_summary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-05 12:00', 'Asia/Taipei'));

        $metToday = $this->converted('today@example.com', 30000);
        $metLong  = $this->converted('long-ago@example.com', 70000);

        foreach ([[$metToday, '2026-09-05 14:00'], [$metLong, '2026-07-01 14:00']] as [$lead, $taipei]) {
            $start = Carbon::parse($taipei, 'Asia/Taipei')->utc();
            ConsultationSlot::create(['starts_at' => $start, 'lead_id' => $lead->id]);
            ConsultationSlot::create(['starts_at' => $start->copy()->addMinutes(15), 'lead_id' => $lead->id]);
        }

        $this->assertSame(100000, $this->stats()['month']['amount'], '不篩選時兩筆都算');
        $this->assertSame(30000, $this->stats(['met' => 'today'])['month']['amount']);
        $this->assertSame(30000, $this->stats(['met' => '7d'])['month']['amount']);

        Carbon::setTestNow();
    }
}
