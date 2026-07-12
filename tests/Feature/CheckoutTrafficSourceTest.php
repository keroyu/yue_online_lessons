<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Services\TrafficSourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Source capture is cookie-based since 002 US10: the TrackTrafficSource
 * middleware writes tf_first / tf_last on any GET entry point, and checkout
 * reads the cookies (last touch → orders columns, first touch → JSON column).
 */
class CheckoutTrafficSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Plain-text cookies so we can round-trip them between test requests.
        $this->disableCookieEncryption();
    }

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

    private function cookieValue(TestResponse $response, string $name): ?string
    {
        foreach ($response->baseResponse->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie->getValue();
            }
        }

        return null;
    }

    public function test_full_utm_trail_persists_through_checkout(): void
    {
        $course = $this->makeCourse();

        // 1. Visit course page with UTM params + click ID + external referrer
        $term = urlencode('財商');
        $response = $this->withHeaders(['Referer' => 'https://www.threads.net/some/post'])
            ->get("/course/{$course->id}?utm_source=instagram&utm_medium=social&utm_campaign=launch2026&utm_content=post-001&utm_term={$term}&fbclid=ABC123");
        $response->assertOk();

        // 2. Middleware wrote both touch cookies
        $lastJson = $this->cookieValue($response, TrafficSourceService::COOKIE_LAST);
        $firstJson = $this->cookieValue($response, TrafficSourceService::COOKIE_FIRST);
        $this->assertNotNull($lastJson);
        $this->assertNotNull($firstJson);

        $last = json_decode($lastJson, true);
        $this->assertSame('instagram', $last['utm_source']);
        $this->assertSame('social', $last['utm_medium']);
        $this->assertSame('launch2026', $last['utm_campaign']);
        $this->assertSame('post-001', $last['utm_content']);
        $this->assertSame('財商', $last['utm_term']);
        $this->assertSame('ABC123', $last['fbclid']);
        $this->assertSame('threads.net', $last['referrer_domain']);

        // 3. Submit checkout carrying the cookies — must reach Order intact
        $checkout = $this->withCookie(TrafficSourceService::COOKIE_LAST, $lastJson)
            ->withCookie(TrafficSourceService::COOKIE_FIRST, $firstJson)
            ->post('/api/checkout/initiate', [
                'buyer' => [
                    'name'  => '王小明',
                    'email' => 'test+utm@example.com',
                    'phone' => '0912345678',
                ],
                'agree_terms' => true,
                'course_ids'  => [$course->id],
            ]);

        $checkout->assertOk()->assertJsonStructure(['gateway', 'endpoint', 'fields']);

        // 4. Order row: last touch on the flat columns + first touch snapshot
        $order = Order::where('buyer_email', 'test+utm@example.com')->firstOrFail();
        $this->assertSame('instagram',  $order->utm_source);
        $this->assertSame('social',     $order->utm_medium);
        $this->assertSame('launch2026', $order->utm_campaign);
        $this->assertSame('post-001',   $order->utm_content);
        $this->assertSame('財商',        $order->utm_term);
        $this->assertSame('ABC123',     $order->fbclid);
        $this->assertSame('threads.net', $order->referrer_domain);
        $this->assertSame('instagram', $order->first_touch['utm_source'] ?? null);
    }

    public function test_first_and_last_touch_can_differ_on_order(): void
    {
        $course = $this->makeCourse();

        $first = json_encode(['utm_source' => 'instagram', 'utm_medium' => 'social'], JSON_UNESCAPED_UNICODE);
        $last  = json_encode(['utm_source' => 'newsletter', 'utm_medium' => 'email'], JSON_UNESCAPED_UNICODE);

        $this->withCookie(TrafficSourceService::COOKIE_FIRST, $first)
            ->withCookie(TrafficSourceService::COOKIE_LAST, $last)
            ->post('/api/checkout/initiate', [
                'buyer' => [
                    'name'  => '雙觸點',
                    'email' => 'multitouch@example.com',
                    'phone' => '0911111111',
                ],
                'agree_terms' => true,
                'course_ids'  => [$course->id],
            ])->assertOk();

        $order = Order::where('buyer_email', 'multitouch@example.com')->firstOrFail();
        $this->assertSame('newsletter', $order->utm_source);           // last touch wins the columns
        $this->assertSame('instagram', $order->first_touch['utm_source']); // first touch preserved
    }

    public function test_direct_visit_results_in_null_utm(): void
    {
        $course = $this->makeCourse();

        // Visit without UTM, no referrer → no capture cookies queued
        $response = $this->get("/course/{$course->id}");
        $response->assertOk();
        $this->assertNull($this->cookieValue($response, TrafficSourceService::COOKIE_LAST));

        // Checkout without cookies — Order created with NULL UTM (直接造訪)
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
        $this->assertNull($order->first_touch);
    }

    public function test_internal_referrer_is_filtered_out(): void
    {
        $course = $this->makeCourse();

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $response = $this->withHeaders(['Referer' => 'https://' . $appHost . '/some-internal-page'])
            ->get("/course/{$course->id}");
        $response->assertOk();

        // Internal referrer must NOT be captured
        $this->assertNull($this->cookieValue($response, TrafficSourceService::COOKIE_LAST));
    }

    public function test_traffic_source_survives_login(): void
    {
        $course = $this->makeCourse();

        // Capture UTM as guest
        $response = $this->get("/course/{$course->id}?utm_source=email&utm_medium=newsletter");
        $lastJson = $this->cookieValue($response, TrafficSourceService::COOKIE_LAST);
        $this->assertNotNull($lastJson);

        // Cookies survive session regeneration on login (the original session
        // approach lost attribution here) — checkout as the logged-in user.
        $user = User::factory()->create(['role' => 'member']);

        $this->actingAs($user)
            ->withCookie(TrafficSourceService::COOKIE_LAST, $lastJson)
            ->post('/api/checkout/initiate', [
                'buyer' => [
                    'name'  => '小華',
                    'email' => $user->email,
                    'phone' => '0987654321',
                ],
                'agree_terms' => true,
                'course_ids'  => [$course->id],
            ])->assertOk();

        $order = Order::where('buyer_email', $user->email)->firstOrFail();
        $this->assertSame('email', $order->utm_source);
    }
}
