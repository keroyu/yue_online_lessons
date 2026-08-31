<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\DripSubscription;
use App\Models\HighTicketLead;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 011 US31 — the booking list's CSV export (FR-156 … FR-161).
 *
 * The two things that go wrong invisibly here: the `select_all` scope drifting
 * away from the list it claims to mirror, and a money column that stops being
 * numeric (Excel's SUM skips text cells silently, so the total just comes out
 * short with nothing on screen to say so).
 */
class LeadExportTest extends TestCase
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

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin-export@example.com'], ['role' => 'admin']);
    }

    private function lead(array $attributes = []): HighTicketLead
    {
        return HighTicketLead::create(array_merge([
            'name'      => '王小明',
            'email'     => 'lead@example.com',
            'phone'     => '0912345678',
            'course_id' => $this->course->id,
            'status'    => 'pending',
            'booked_at' => now(),
        ], $attributes));
    }

    /** The purchase a conversion writes (011 D13). */
    private function conversion(
        string $email,
        int $amount,
        ?Course $course = null,
        string $status = 'paid',
        string $type = 'lead_conversion',
        ?CoursePlan $plan = null,
        ?Carbon $at = null,
        ?string $memberEmail = null,
    ): Purchase {
        $course ??= $this->course;
        $user = User::firstOrCreate(['email' => $memberEmail ?? $email], ['role' => 'member']);

        $purchase = Purchase::create([
            'user_id'        => $user->id,
            'course_id'      => $course->id,
            'course_plan_id' => $plan?->id,
            'buyer_email'    => $email,
            'amount'         => $amount,
            'currency'       => 'TWD',
            'status'         => $status,
            'type'           => $type,
        ]);

        if ($at) {
            $purchase->forceFill(['created_at' => $at])->save();
        }

        return $purchase;
    }

    /**
     * Runs the export and returns the parsed CSV, header row included.
     *
     * @return array<int, array<int, string>>
     */
    private function csv(array $query): array
    {
        $body = $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads/export?' . http_build_query($query))
            ->assertOk()
            ->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $body, 'CSV 必須有 UTF-8 BOM');

        $lines = array_filter(explode("\n", str_replace("\xEF\xBB\xBF", '', $body)), fn ($l) => trim($l) !== '');

        return array_values(array_map(fn ($line) => str_getcsv(rtrim($line, "\r")), $lines));
    }

    /** Data rows keyed by email, so a test never depends on row order. */
    private function rowsByEmail(array $query): array
    {
        $rows = $this->csv($query);
        array_shift($rows);

        return collect($rows)->keyBy(1)->all();
    }

    // ── FR-157: shape of the file ───────────────────────────────────────────

    public function test_header_lists_the_nine_columns_in_order(): void
    {
        $lead = $this->lead();

        $this->assertSame([
            '姓名', 'Email', '手機電話', '諮詢時段', '狀態',
            '成交金額', '課程', '序列信起始時間', '已經過天數',
        ], $this->csv(['ids' => [$lead->id]])[0]);
    }

    public function test_only_the_ticked_leads_are_exported(): void
    {
        $wanted = $this->lead(['email' => 'wanted@example.com']);
        $this->lead(['email' => 'skipped@example.com']);

        $rows = $this->csv(['ids' => [$wanted->id]]);

        $this->assertCount(2, $rows, '表頭 + 一列');
        $this->assertSame('wanted@example.com', $rows[1][1]);
    }

    public function test_missing_values_are_empty_strings_not_dashes(): void
    {
        // No phone, no slot, no conversion, no drip subscription.
        $lead = $this->lead(['phone' => null]);

        $row = $this->csv(['ids' => [$lead->id]])[1];

        $this->assertSame('', $row[2], '手機電話');
        $this->assertSame('', $row[3], '諮詢時段');
        $this->assertSame('', $row[5], '成交金額');
        $this->assertSame('', $row[7], '序列信起始時間');
        $this->assertSame('', $row[8], '已經過天數');
    }

    // ── FR-156: scope modes ─────────────────────────────────────────────────

    public function test_export_without_a_scope_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads/export')
            ->assertStatus(422);
    }

    public function test_select_all_exports_every_lead_matching_the_filters(): void
    {
        $alice = User::create(['email' => 'alice@example.com', 'role' => 'admin', 'is_sales_consultant' => true]);

        $this->lead(['email' => 'mine@example.com', 'consultant_id' => $alice->id]);
        $this->lead(['email' => 'theirs@example.com']);

        $rows = $this->csv(['select_all' => 1, 'consultant' => $alice->id]);

        $this->assertCount(2, $rows);
        $this->assertSame('mine@example.com', $rows[1][1]);
    }

    public function test_select_all_honours_the_status_tab_and_search(): void
    {
        $this->lead(['email' => 'converted@example.com', 'name' => '成交者', 'status' => 'converted']);
        $this->lead(['email' => 'pending@example.com', 'name' => '待談者', 'status' => 'pending']);

        $byStatus = $this->csv(['select_all' => 1, 'status' => 'converted']);
        $this->assertCount(2, $byStatus);
        $this->assertSame('converted@example.com', $byStatus[1][1]);

        $bySearch = $this->csv(['select_all' => 1, 'search' => '待談者']);
        $this->assertCount(2, $bySearch);
        $this->assertSame('pending@example.com', $bySearch[1][1]);
    }

    public function test_select_all_honours_the_meeting_time_quick_filter(): void
    {
        $today = $this->lead(['email' => 'today@example.com']);
        ConsultationSlot::create([
            'starts_at' => now(config('app.timezone'))->setTimezone('Asia/Taipei')->startOfDay()->addHours(14)->utc(),
            'lead_id'   => $today->id,
        ]);

        $old = $this->lead(['email' => 'old@example.com']);
        ConsultationSlot::create([
            'starts_at' => now()->subDays(30),
            'lead_id'   => $old->id,
        ]);

        $rows = $this->csv(['select_all' => 1, 'met' => 'today']);

        $this->assertCount(2, $rows);
        $this->assertSame('today@example.com', $rows[1][1]);
    }

    // ── FR-158: the money column ────────────────────────────────────────────

    public function test_deal_amount_sums_paid_conversions_only(): void
    {
        // `purchases` is unique on (user_id, course_id), so each of these
        // needs its own course.
        $lead = $this->lead(['email' => 'buyer@example.com', 'status' => 'converted']);

        $this->conversion('buyer@example.com', 68000);
        $this->conversion('buyer@example.com', 12000, $this->makeCourse('第二課程'));
        $this->conversion('buyer@example.com', 90000, $this->makeCourse('退掉的課'), status: 'refunded');
        $this->conversion('buyer@example.com', 99999, $this->makeCourse('贈品課'), type: 'gift');

        $row = $this->csv(['ids' => [$lead->id]])[1];

        $this->assertSame('80000', $row[5], '只計 paid 的 lead_conversion，且為總和');
    }

    public function test_deal_amount_is_blank_rather_than_zero_when_nothing_closed(): void
    {
        $lead = $this->lead(['email' => 'nodeal@example.com']);

        $this->assertSame('', $this->csv(['ids' => [$lead->id]])[1][5]);
    }

    public function test_conversion_is_found_through_buyer_email_after_a_member_changes_address(): void
    {
        $lead = $this->lead(['email' => 'old-address@example.com', 'status' => 'converted']);

        // The member later changed their login email; buyer_email is the
        // snapshot of the lead's address at conversion time (FR-158).
        $this->conversion('old-address@example.com', 45000, memberEmail: 'new-address@example.com');

        $this->assertSame('45000', $this->csv(['ids' => [$lead->id]])[1][5]);
    }

    // ── FR-159: the course column ───────────────────────────────────────────

    public function test_course_column_names_the_purchased_course_with_its_plan(): void
    {
        $plan = CoursePlan::create(['course_id' => $this->course->id, 'name' => '方案B', 'price' => 68000]);
        $lead = $this->lead(['email' => 'buyer@example.com', 'status' => 'converted']);

        $this->conversion('buyer@example.com', 68000, plan: $plan);

        $this->assertSame('主課程（方案B）', $this->csv(['ids' => [$lead->id]])[1][6]);
    }

    public function test_course_column_falls_back_to_the_applied_course_when_nothing_closed(): void
    {
        $lead = $this->lead(['email' => 'nodeal@example.com']);

        $this->assertSame('主課程', $this->csv(['ids' => [$lead->id]])[1][6]);
    }

    public function test_multiple_conversions_are_listed_while_the_amount_stays_a_single_number(): void
    {
        $second = $this->makeCourse('第二課程');
        $lead = $this->lead(['email' => 'buyer@example.com', 'status' => 'converted']);

        $this->conversion('buyer@example.com', 68000, at: now()->subDays(3));
        $this->conversion('buyer@example.com', 12000, $second, at: now());

        $row = $this->csv(['ids' => [$lead->id]])[1];

        $this->assertSame('主課程／第二課程', $row[6], '課程依購買時間由舊到新');
        $this->assertSame('80000', $row[5], '金額欄必須維持可 SUM 的純數字');
    }

    // ── FR-160: the drip columns ────────────────────────────────────────────

    public function test_drip_columns_align_one_entry_per_subscription(): void
    {
        $lead = $this->lead([
            'email'        => 'warmed@example.com',
            'confirmed_at' => Carbon::parse('2026-08-31 02:00:00'), // Taipei 8/31 10:00
        ]);
        $user = User::create(['email' => 'warmed@example.com', 'role' => 'member']);

        DripSubscription::create([
            'user_id' => $user->id, 'course_id' => $this->course->id, 'status' => 'active',
            'subscribed_at' => Carbon::parse('2026-08-01 01:30:00'), // Taipei 8/1 09:30
        ]);
        DripSubscription::create([
            'user_id' => $user->id, 'course_id' => $this->makeCourse('第二序列')->id, 'status' => 'active',
            'subscribed_at' => Carbon::parse('2026-08-20 01:30:00'), // Taipei 8/20 09:30
        ]);

        $row = $this->csv(['ids' => [$lead->id]])[1];

        $this->assertSame('2026/8/1 09:30／2026/8/20 09:30', $row[7], '依 subscribed_at 由舊到新');
        $this->assertSame('30／11', $row[8], '天數項數與順序必須對齊上一欄');
    }

    public function test_days_count_up_to_today_while_the_booking_is_unconfirmed(): void
    {
        $lead = $this->lead(['email' => 'unconfirmed@example.com', 'confirmed_at' => null]);
        $user = User::create(['email' => 'unconfirmed@example.com', 'role' => 'member']);

        DripSubscription::create([
            'user_id' => $user->id, 'course_id' => $this->course->id, 'status' => 'active',
            'subscribed_at' => now()->subDays(5),
        ]);

        $this->assertSame('5', $this->csv(['ids' => [$lead->id]])[1][8]);
    }

    // ── FR-157 / D122: Taipei time and Chinese status labels ────────────────

    public function test_slot_range_is_rendered_in_taipei_time(): void
    {
        $lead = $this->lead();

        // 16:00 UTC on 8/31 is midnight on 9/1 in Taipei — a browser-local or
        // UTC rendering would file this row on the wrong day.
        ConsultationSlot::create(['starts_at' => Carbon::parse('2026-08-31 16:00:00'), 'lead_id' => $lead->id]);
        ConsultationSlot::create(['starts_at' => Carbon::parse('2026-08-31 16:15:00'), 'lead_id' => $lead->id]);

        $this->assertSame('2026/9/1 00:00-00:30', $this->csv(['ids' => [$lead->id]])[1][3]);
    }

    public function test_status_is_exported_in_chinese(): void
    {
        $lead = $this->lead(['status' => 'no_response']);

        $this->assertSame('未出席', $this->csv(['ids' => [$lead->id]])[1][4]);
    }

    public function test_status_labels_cover_every_legal_status(): void
    {
        $this->assertSame(
            ['pending', 'contacted', 'no_response', 'converted', 'closed', 'cancelled', 'declined'],
            array_keys(HighTicketLead::STATUS_LABELS),
        );
    }

    // ── FR-161 / permissions ────────────────────────────────────────────────

    public function test_export_writes_nothing(): void
    {
        $lead = $this->lead(['notified_count' => 2, 'status' => 'pending']);

        $this->csv(['ids' => [$lead->id]]);

        $lead->refresh();
        $this->assertSame(2, $lead->notified_count);
        $this->assertSame('pending', $lead->status);
    }

    public function test_members_cannot_export(): void
    {
        $member = User::create(['email' => 'member@example.com', 'role' => 'member']);
        $lead = $this->lead();

        $this->actingAs($member)
            ->get('/admin/high-ticket-leads/export?' . http_build_query(['ids' => [$lead->id]]))
            ->assertRedirect('/');
    }
}
