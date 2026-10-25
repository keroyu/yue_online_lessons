<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\User;
use App\Services\SimpanyInvoiceExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 009 US8 — Simpany B2C e-invoice batch import export (FR-013 … FR-016).
 *
 * Simpany rejects the file outright on a header mismatch, and the money columns
 * must add up to what the customer actually paid, so both are pinned here.
 */
class SimpanyInvoiceExportTest extends TestCase
{
    use RefreshDatabase;

    private const TEMPLATE_HEADER = '自訂編號,買受人 Email *,買受人名稱,發票備註,特殊需求 *,捐贈碼 *,載具類型 *,載具號碼 *,課稅別 *,零稅率原因 *,含稅發票總金額 *,品名 1 *,數量 1 *,含稅單價 1 *,品名 2,數量 2,含稅單價 2,品名 3,數量 3,含稅單價 3,品名 4,數量 4,含稅單價 4,品名 5,數量 5,含稅單價 5';

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin-simpany@example.com'], ['role' => 'admin']);
    }

    private function course(string $name, int $price = 1000): Course
    {
        return Course::create([
            'name' => $name, 'slug' => 'c-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => $price, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
        ]);
    }

    private function order(int $total, array $attributes = []): Order
    {
        $no = 'ORD' . uniqid();

        return Order::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'buyer_name' => 'B', 'buyer_email' => "{$no}@example.com", 'buyer_phone' => '0900000000',
            'total_amount' => $total, 'currency' => 'TWD', 'payment_gateway' => 'payuni',
            'merchant_order_no' => $no, 'status' => 'paid', 'webhook_received_at' => now(),
        ], $attributes));
    }

    private function purchase(Course $course, array $attributes = []): Purchase
    {
        return Purchase::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'course_id' => $course->id,
            'buyer_email' => 'buyer' . uniqid() . '@example.com',
            'amount' => $course->price, 'currency' => 'TWD', 'discount_amount' => 0,
            'status' => 'paid', 'type' => 'paid', 'source' => 'payuni',
        ], $attributes));
    }

    /** @return array<int, array<int, string>> */
    private function export(array $ids): array
    {
        $response = $this->actingAs($this->admin())
            ->get('/admin/transactions/export-simpany?' . http_build_query(['ids' => $ids]));
        $response->assertOk();

        $lines = explode("\r\n", rtrim($response->getContent(), "\r\n"));

        return array_map('str_getcsv', $lines);
    }

    public function test_header_matches_template_byte_for_byte_without_bom_and_with_crlf(): void
    {
        $p = $this->purchase($this->course('課A'));

        $response = $this->actingAs($this->admin())
            ->get('/admin/transactions/export-simpany?ids[]=' . $p->id);

        $response->assertOk();
        $body = $response->getContent();
        $this->assertStringStartsWith(self::TEMPLATE_HEADER . "\r\n", $body);
        $this->assertStringNotContainsString("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('simpany-b2c-', $response->headers->get('Content-Disposition'));
    }

    public function test_cart_order_becomes_one_invoice_with_total_amount_allocated_by_price(): void
    {
        $a = $this->course('課A', 3000);
        $b = $this->course('課B', 1000);
        // 4000 list price, 3001 paid — allocation must round and still sum to 3001
        $order = $this->order(3001);
        $pa = $this->purchase($a, ['order_id' => $order->id, 'buyer_email' => 'x@example.com', 'discount_amount' => 999]);
        $pb = $this->purchase($b, ['order_id' => $order->id, 'buyer_email' => 'x@example.com']);

        $rows = $this->export([$pa->id, $pb->id]);

        $this->assertCount(2, $rows); // header + 1 invoice
        $row = $rows[1];
        $this->assertSame($order->merchant_order_no, $row[0]);
        $this->assertSame($order->buyer_email, $row[1]);
        $this->assertSame('1 - 無特殊需求', $row[4]);
        $this->assertSame('1 - 應稅', $row[8]);
        $this->assertSame('3001', $row[10]);
        $this->assertSame(['課A', '1', '2251'], array_slice($row, 11, 3));
        $this->assertSame(['課B', '1', '750'], array_slice($row, 14, 3));
        $this->assertSame('', $row[17]);
        $this->assertCount(26, $row);
    }

    public function test_partial_selection_keeps_the_same_unit_price(): void
    {
        $a = $this->course('課A', 3000);
        $b = $this->course('課B', 1000);
        $order = $this->order(2000);
        $this->purchase($a, ['order_id' => $order->id]);
        $pb = $this->purchase($b, ['order_id' => $order->id]);

        $rows = $this->export([$pb->id]);

        $this->assertSame('500', $rows[1][10]);
        $this->assertSame(['課B', '1', '500'], array_slice($rows[1], 11, 3));
    }

    public function test_refunded_item_inside_an_order_is_left_out(): void
    {
        $a = $this->course('課A', 1000);
        $b = $this->course('課B', 1000);
        $order = $this->order(2000);
        $pa = $this->purchase($a, ['order_id' => $order->id]);
        $pb = $this->purchase($b, ['order_id' => $order->id, 'status' => 'refunded']);

        $rows = $this->export([$pa->id, $pb->id]);

        $this->assertSame('1000', $rows[1][10]);
        $this->assertSame('', $rows[1][14]);
    }

    public function test_orderless_purchase_uses_amount_minus_discount_and_plan_name(): void
    {
        $course = $this->course('智識價值工程', 50000);
        $plan = CoursePlan::create(['course_id' => $course->id, 'name' => '入門方案', 'price' => 9050]);
        $p = $this->purchase($course, [
            'amount' => 10000, 'discount_amount' => 950, 'course_plan_id' => $plan->id,
            'portaly_order_id' => 'PT-123', 'source' => 'portaly', 'buyer_email' => 'p@example.com',
        ]);

        $rows = $this->export([$p->id]);

        $this->assertSame('PT-123', $rows[1][0]);
        $this->assertSame('p@example.com', $rows[1][1]);
        $this->assertSame('9050', $rows[1][10]);
        $this->assertSame(['【智識價值工程】入門方案', '1', '9050'], array_slice($rows[1], 11, 3));
    }

    public function test_purchase_without_any_order_number_falls_back_to_p_id(): void
    {
        $p = $this->purchase($this->course('課A'), ['source' => 'manual']);

        $rows = $this->export([$p->id]);

        $this->assertSame('P' . $p->id, $rows[1][0]);
    }

    public function test_excluded_purchases_are_counted_by_reason(): void
    {
        $course = $this->course('課A');
        $ok = $this->purchase($course);
        $refunded = $this->purchase($course, ['status' => 'refunded']);
        $gift = $this->purchase($course, ['amount' => 0, 'type' => 'gift', 'source' => 'manual']);
        $b2b = $this->purchase($course, ['order_id' => $this->order(1000, ['tax_id' => '12345678'])->id]);

        $response = $this->actingAs($this->admin())->get('/admin/transactions/export-simpany?' . http_build_query([
            'ids' => [$ok->id, $refunded->id, $gift->id, $b2b->id],
        ]));

        $response->assertOk();
        $this->assertSame('1', $response->headers->get('X-Simpany-Invoices'));
        $this->assertSame(
            ['refunded' => 1, 'b2b' => 1, 'zero' => 1],
            json_decode($response->headers->get('X-Simpany-Skipped'), true)
        );
        $this->assertCount(2, explode("\r\n", rtrim($response->getContent(), "\r\n")));
    }

    public function test_all_excluded_returns_422(): void
    {
        $p = $this->purchase($this->course('課A'), ['status' => 'refunded']);

        $this->actingAs($this->admin())
            ->getJson('/admin/transactions/export-simpany?ids[]=' . $p->id)
            ->assertStatus(422)
            ->assertJson(['message' => '選取的交易都不需開 B2C 發票']);
    }

    public function test_no_selection_returns_422(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/admin/transactions/export-simpany')
            ->assertStatus(422);
    }

    public function test_more_than_five_items_collapse_into_the_fifth_column(): void
    {
        $order = $this->order(7000);
        $ids = [];
        foreach (range(1, 7) as $i) {
            $ids[] = $this->purchase($this->course("課{$i}"), ['order_id' => $order->id])->id;
        }

        $row = $this->export($ids)[1];

        $this->assertSame('7000', $row[10]);
        $this->assertSame(['課4', '1', '1000'], array_slice($row, 20, 3));
        $this->assertSame(['其他課程（3 堂）', '1', '3000'], array_slice($row, 23, 3));
    }

    public function test_select_all_mode_reuses_list_filters(): void
    {
        $a = $this->course('課A');
        $b = $this->course('課B');
        $this->purchase($a);
        $this->purchase($b);

        $response = $this->actingAs($this->admin())
            ->get('/admin/transactions/export-simpany?select_all=true&course_id=' . $a->id);

        $response->assertOk();
        $this->assertSame('1', $response->headers->get('X-Simpany-Invoices'));
    }

    public function test_data_row_is_written_like_the_template(): void
    {
        $course = $this->course('智識價值工程', 42000);
        $plan = CoursePlan::create(['course_id' => $course->id, 'name' => '完整方案', 'price' => 42000]);
        $p = $this->purchase($course, [
            'course_plan_id' => $plan->id, 'portaly_order_id' => 'PT-9', 'source' => 'portaly',
            'buyer_email' => 'judy@example.com',
        ]);

        $body = $this->actingAs($this->admin())
            ->get('/admin/transactions/export-simpany?ids[]=' . $p->id)
            ->getContent();

        $this->assertSame(
            "PT-9,judy@example.com,,,1 - 無特殊需求,,,,1 - 應稅,,42000,【智識價值工程】完整方案,1,42000,,,,,,,,,,,,\r\n",
            explode("\r\n", $body, 2)[1]
        );
    }

    public function test_service_headers_constant_matches_template(): void
    {
        $this->assertSame(self::TEMPLATE_HEADER, implode(',', SimpanyInvoiceExportService::HEADERS));
    }
}
