<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTrafficSourceTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(): Course
    {
        return Course::create([
            'name'             => 'Test Course',
            'slug'             => 'test-course-' . uniqid(),
            'tagline'          => 'tag',
            'description'      => 'desc',
            'price'            => 1000,
            'instructor_name'  => 'Tester',
            'type'             => 'lecture',
            'status'           => 'selling',
            'course_type'      => 'standard',
            'is_published'     => true,
            'is_visible'       => true,
            'payment_gateway'  => 'payuni',
        ]);
    }

    public function test_full_utm_trail_persists_through_checkout(): void
    {
        $course = $this->makeCourse();

        // 1. Visit course page with UTM params + click ID + external referrer
        $term = urlencode('財商');
        $this->withSession([])
            ->withHeaders(['Referer' => 'https://www.threads.net/some/post'])
            ->get("/course/{$course->id}?utm_source=instagram&utm_medium=social&utm_campaign=launch2026&utm_content=post-001&utm_term={$term}&fbclid=ABC123")
            ->assertOk();

        // 2. Verify session captured traffic_source correctly
        $session = session('traffic_source');
        $this->assertIsArray($session);
        $this->assertSame('instagram', $session['utm_source']);
        $this->assertSame('social', $session['utm_medium']);
        $this->assertSame('launch2026', $session['utm_campaign']);
        $this->assertSame('post-001', $session['utm_content']);
        $this->assertSame('財商', $session['utm_term']);
        $this->assertSame('ABC123', $session['fbclid']);
        $this->assertSame('threads.net', $session['referrer_domain']);

        // 3. Submit checkout (same session) — must reach Order with UTM intact
        $response = $this->post('/api/checkout/initiate', [
            'buyer' => [
                'name'  => '王小明',
                'email' => 'test+utm@example.com',
                'phone' => '0912345678',
            ],
            'agree_terms' => true,
            'course_ids'  => [$course->id],
        ]);

        $response->assertOk()->assertJsonStructure(['gateway', 'endpoint', 'fields']);

        // 4. Verify Order row has all UTM data persisted
        $order = Order::where('buyer_email', 'test+utm@example.com')->firstOrFail();
        $this->assertSame('instagram',  $order->utm_source);
        $this->assertSame('social',     $order->utm_medium);
        $this->assertSame('launch2026', $order->utm_campaign);
        $this->assertSame('post-001',   $order->utm_content);
        $this->assertSame('財商',        $order->utm_term);
        $this->assertSame('ABC123',     $order->fbclid);
        $this->assertSame('threads.net', $order->referrer_domain);
        $this->assertNull($order->utm_source === null ? 'should_not_be_null' : null);
    }

    public function test_direct_visit_results_in_null_utm(): void
    {
        $course = $this->makeCourse();

        // Visit without UTM, no referrer
        $this->get("/course/{$course->id}")->assertOk();

        // No session traffic_source written
        $this->assertNull(session('traffic_source'));

        // Submit checkout — Order created with NULL UTM (still appears on Traffic page as 直接造訪)
        $this->post('/api/checkout/initiate', [
            'buyer' => [
                'name'  => '王小明',
                'email' => 'direct@example.com',
                'phone' => '0912345678',
            ],
            'agree_terms' => true,
            'course_ids'  => [$course->id],
        ])->assertOk();

        $order = Order::where('buyer_email', 'direct@example.com')->firstOrFail();
        $this->assertNull($order->utm_source);
        $this->assertNull($order->referrer_domain);
        $this->assertNull($order->fbclid);
    }

    public function test_internal_referrer_is_filtered_out(): void
    {
        $course = $this->makeCourse();

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $this->withHeaders(['Referer' => 'https://' . $appHost . '/some-internal-page'])
            ->get("/course/{$course->id}")
            ->assertOk();

        // Internal referrer must NOT be captured
        $this->assertNull(session('traffic_source'));
    }

    public function test_traffic_source_persists_across_login(): void
    {
        $course = $this->makeCourse();

        // Capture UTM as guest
        $this->get("/course/{$course->id}?utm_source=email&utm_medium=newsletter")->assertOk();
        $this->assertSame('email', session('traffic_source.utm_source'));

        // Simulate session preservation across login — checkout still has UTM
        $this->post('/api/checkout/initiate', [
            'buyer' => [
                'name'  => '小華',
                'email' => 'newuser@example.com',
                'phone' => '0987654321',
            ],
            'agree_terms' => true,
            'course_ids'  => [$course->id],
        ])->assertOk();

        $order = Order::where('buyer_email', 'newuser@example.com')->firstOrFail();
        $this->assertSame('email', $order->utm_source);
        $this->assertSame('newsletter', $order->utm_medium);
    }
}
