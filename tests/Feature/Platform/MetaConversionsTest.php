<?php

namespace Tests\Feature\Platform;

use App\Jobs\SendMetaConversionJob;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\MetaConversionsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MetaConversionsTest extends TestCase
{
    use RefreshDatabase;

    private function enableCapi(): void
    {
        SiteSetting::set('meta_pixel_id', '123456789');
        SiteSetting::set('meta_capi_access_token', 'test-token');
    }

    private function makeCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name'             => 'CAPI Course',
            'slug'             => 'capi-course-' . uniqid(),
            'tagline'          => 'tag',
            'description'      => 'desc',
            'price'            => 2000,
            'instructor_name'  => 'Tester',
            'type'             => 'lecture',
            'status'           => 'selling',
            'course_type'      => 'standard',
            'is_published'     => true,
            'is_visible'       => true,
            'payment_gateway'  => 'payuni',
        ], $overrides));
    }

    private function makePendingOrder(Course $course): Order
    {
        $order = Order::create([
            'buyer_name'        => '王小明',
            'buyer_email'       => 'capi@example.com',
            'buyer_phone'       => '0912345678',
            'total_amount'      => 2000,
            'currency'          => 'TWD',
            'payment_gateway'   => 'payuni',
            'status'            => 'pending',
            'merchant_order_no' => 'ord_test_' . uniqid(),
        ]);
        OrderItem::create([
            'order_id'    => $order->id,
            'course_id'   => $course->id,
            'course_name' => $course->name,
            'unit_price'  => 2000,
        ]);

        return $order->fresh();
    }

    // --- hash normalization ---

    public function test_hash_email_normalizes_case_and_whitespace(): void
    {
        $service = app(MetaConversionsService::class);

        $this->assertSame(
            hash('sha256', 'user@example.com'),
            $service->hashEmail('  User@Example.COM ')
        );
    }

    public function test_hash_phone_normalizes_taiwan_format(): void
    {
        $service = app(MetaConversionsService::class);

        // 0912-345-678 → 886912345678 → sha256
        $this->assertSame(
            hash('sha256', '886912345678'),
            $service->hashPhone('0912-345-678')
        );
        // already has country code
        $this->assertSame(
            hash('sha256', '886912345678'),
            $service->hashPhone('+886 912 345 678')
        );
    }

    // --- send() gating ---

    public function test_send_noops_when_credentials_missing(): void
    {
        Queue::fake();

        // pixel id set but no token
        SiteSetting::set('meta_pixel_id', '123456789');
        app(MetaConversionsService::class)->send('Purchase', [], ['value' => 100]);

        Queue::assertNothingPushed();
    }

    public function test_send_dispatches_job_when_configured(): void
    {
        Queue::fake();
        $this->enableCapi();

        app(MetaConversionsService::class)->send(
            'Purchase',
            ['em' => hash('sha256', 'a@b.c')],
            ['value' => 100, 'currency' => 'TWD'],
            'purchase_ord_1'
        );

        Queue::assertPushed(SendMetaConversionJob::class, function (SendMetaConversionJob $job) {
            return $job->payload['event_name'] === 'Purchase'
                && $job->payload['event_id'] === 'purchase_ord_1'
                && $job->payload['custom_data']['value'] === 100;
        });
    }

    // --- Purchase on fulfillment ---

    public function test_fulfill_order_sends_capi_purchase_with_event_id(): void
    {
        Queue::fake();
        $this->enableCapi();

        $course = $this->makeCourse();
        $order = $this->makePendingOrder($course);

        app(CheckoutService::class)->fulfillOrder($order, 'TRADE123', 'payuni');

        Queue::assertPushed(SendMetaConversionJob::class, function (SendMetaConversionJob $job) use ($order, $course) {
            return $job->payload['event_name'] === 'Purchase'
                && $job->payload['event_id'] === 'purchase_' . $order->merchant_order_no
                && in_array($course->id, $job->payload['custom_data']['content_ids']);
        });
    }

    public function test_fulfill_order_skips_capi_when_not_configured(): void
    {
        Queue::fake();

        $course = $this->makeCourse();
        $order = $this->makePendingOrder($course);

        app(CheckoutService::class)->fulfillOrder($order, 'TRADE123', 'payuni');

        Queue::assertNotPushed(SendMetaConversionJob::class);
        $this->assertSame('paid', $order->fresh()->status);
    }

    // --- funnel events ---

    public function test_free_enroll_sends_custom_free_enroll_event(): void
    {
        Queue::fake();
        $this->enableCapi();

        $course = $this->makeCourse(['price' => 0]);

        $this->postJson("/api/purchase/free/{$course->id}", [
            'email' => 'free@example.com',
            'name'  => '免費仔',
            'phone' => '0911222333',
        ])->assertOk();

        Queue::assertPushed(SendMetaConversionJob::class, function (SendMetaConversionJob $job) use ($course) {
            return $job->payload['event_name'] === 'FreeEnroll'
                && $job->payload['custom_data']['content_ids'] === [$course->id];
        });
    }

    public function test_high_ticket_booking_sends_lead_event(): void
    {
        Queue::fake();
        $this->enableCapi();

        \App\Models\EmailTemplate::create([
            'event_type' => 'high_ticket_booking_confirmation',
            'name'       => '預約確認',
            'subject'    => '已收到您的預約 {{course_name}}',
            'body_md'    => '哈囉 {{user_name}}',
        ]);
        // sqlite's CHECK on the type enum predates high_ticket (MySQL-only ALTER);
        // persist with a legal type, then flip attributes in memory for the guard.
        $course = $this->makeCourse(['high_ticket_hide_price' => true]);
        $course->type = 'high_ticket';

        $result = app(\App\Services\HighTicketBookingService::class)->book($course, [
            'name'  => '高價仔',
            'email' => 'ht@example.com',
        ]);

        $this->assertTrue($result['success']);
        Queue::assertPushed(SendMetaConversionJob::class, fn (SendMetaConversionJob $job) =>
            $job->payload['event_name'] === 'Lead'
            && $job->payload['custom_data']['content_ids'] === [$course->id]);
    }

    public function test_newsletter_signup_sends_complete_registration_only_for_new_user(): void
    {
        Queue::fake();
        $this->enableCapi();

        // new user → event
        app(\App\Services\NewsletterService::class)->subscribeVerified('brandnew@example.com');
        Queue::assertPushed(SendMetaConversionJob::class, fn (SendMetaConversionJob $job) =>
            $job->payload['event_name'] === 'CompleteRegistration'
            && $job->payload['custom_data']['content_name'] === 'newsletter');

        // existing user re-subscribes → no second event
        Queue::fake();
        app(\App\Services\NewsletterService::class)->subscribeVerified('brandnew@example.com');
        Queue::assertNotPushed(SendMetaConversionJob::class);
    }

    // --- settings fields ---

    public function test_admin_can_save_capi_token_and_empty_keeps_old_value(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/settings/payment', [
            'meta_pixel_id'              => '123456789',
            'meta_capi_access_token'     => 'secret-token-abc',
            'meta_capi_test_event_code'  => 'TEST123',
        ])->assertRedirect();

        $this->assertSame('secret-token-abc', SiteSetting::get('meta_capi_access_token'));
        $this->assertSame('TEST123', SiteSetting::get('meta_capi_test_event_code'));

        // empty token on resubmit must NOT overwrite the stored secret
        $this->actingAs($admin)->post('/admin/settings/payment', [
            'meta_pixel_id'             => '123456789',
            'meta_capi_access_token'    => '',
            'meta_capi_test_event_code' => '',
        ])->assertRedirect();

        $this->assertSame('secret-token-abc', SiteSetting::get('meta_capi_access_token'));
        $this->assertSame('', SiteSetting::get('meta_capi_test_event_code') ?? '');
    }
}
