<?php

namespace Tests\Feature\Storefront;

use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\Lesson;
use App\Models\Purchase;
use App\Models\User;
use App\Services\TrafficSourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * 002 US17 — free claims carry their traffic source, and the per-course traffic
 * report counts them (FR-037 … FR-040).
 *
 * The report used to read `orders` only, so a free course showed 0 forever no
 * matter how much traffic the link brought in — and the source was not even
 * being stored, so there was nothing to count.
 */
class FreeClaimTrafficTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // tf_first / tf_last are declared unencrypted in bootstrap/app.php, so
        // the plain JSON written by the middleware round-trips as-is.
        $this->disableCookieEncryption();

        Mail::fake();
    }

    private function makeCourse(string $courseType = 'drip'): Course
    {
        $course = Course::create([
            'name' => 'Free Course', 'slug' => 'free-' . uniqid(), 'tagline' => 't',
            'description' => 'd', 'price' => 0, 'instructor_name' => 'I',
            'type' => 'lecture', 'status' => 'selling', 'course_type' => $courseType,
            'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);

        Lesson::create([
            'course_id' => $course->id, 'title' => 'EP1', 'video_platform' => 'youtube',
            'video_id' => 'v1', 'duration_seconds' => 60, 'sort_order' => 1,
        ]);

        return $course;
    }

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin-claim@example.com'], ['role' => 'admin']);
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

    /** Land on the sales page with UTM so the middleware writes the touch cookies. */
    private function arriveWithSource(Course $course, string $utmSource = 'instagram'): array
    {
        $response = $this->withHeaders(['Referer' => 'https://www.threads.net/p/1'])
            ->get("/course/{$course->id}?utm_source={$utmSource}&utm_medium=social&utm_campaign=launch");

        $response->assertOk();
        return [
            TrafficSourceService::COOKIE_LAST => $this->cookieValue($response, TrafficSourceService::COOKIE_LAST),
            TrafficSourceService::COOKIE_FIRST => $this->cookieValue($response, TrafficSourceService::COOKIE_FIRST),
        ];
    }

    private function trafficRows(Course $course): array
    {
        return $this->actingAs($this->admin())
            ->get("/admin/courses/{$course->id}/traffic")
            ->assertOk()
            ->viewData('page')['props']['traffic']['sources'];
    }

    // ── FR-037/038: the claim stores where it came from ─────────────────────

    public function test_drip_claim_stores_the_traffic_source(): void
    {
        $course = $this->makeCourse('drip');
        $cookies = $this->arriveWithSource($course);

        $this->withCookies($cookies)->post('/drip/subscribe', [
            'email' => 'claimer@example.com',
            'nickname' => '領取者',
            'course_id' => $course->id,
        ]);

        $sub = DripSubscription::firstOrFail();
        $this->assertSame('instagram', $sub->utm_source);
        $this->assertSame('social', $sub->utm_medium);
        $this->assertSame('launch', $sub->utm_campaign);
        $this->assertSame('threads.net', $sub->referrer_domain);
        $this->assertSame('instagram', $sub->first_touch['utm_source'] ?? null);
    }

    public function test_non_drip_free_claim_stores_the_traffic_source(): void
    {
        $course = $this->makeCourse('standard');
        $cookies = $this->arriveWithSource($course, 'facebook');

        $this->withCredentials()->withCookies($cookies)->postJson("/api/purchase/free/{$course->id}", [
            'email' => 'freebie@example.com',
            'name' => '免費仔',
            'phone' => '0912345678',
        ])->assertOk();

        $purchase = Purchase::where('buyer_email', 'freebie@example.com')->firstOrFail();
        $this->assertSame('free', $purchase->source);
        $this->assertSame('facebook', $purchase->utm_source);
        $this->assertSame('threads.net', $purchase->referrer_domain);
    }

    public function test_background_subscription_without_a_request_stores_no_source(): void
    {
        // SubscribeDripLeadJob and friends have no Request; they must still work.
        $course = $this->makeCourse('drip');
        $user = User::create(['email' => 'bg@example.com', 'role' => 'member']);

        app(\App\Services\DripService::class)->subscribe($user, $course);

        $this->assertNull(DripSubscription::firstOrFail()->utm_source);
    }

    // ── FR-039: the report counts claims, exactly once ─────────────────────

    public function test_traffic_report_counts_drip_claims(): void
    {
        $course = $this->makeCourse('drip');
        $cookies = $this->arriveWithSource($course);

        $this->withCookies($cookies)->post('/drip/subscribe', [
            'email' => 'claimer@example.com', 'nickname' => 'A', 'course_id' => $course->id,
        ]);

        $rows = $this->trafficRows($course);

        $this->assertCount(1, $rows);
        $this->assertSame('instagram', $rows[0]['utm_source']);
        $this->assertSame(1, $rows[0]['order_count']);
        $this->assertSame(0, (int) $rows[0]['revenue'], '免費領取不得產生營收');
    }

    public function test_drip_claim_through_the_purchase_endpoint_is_not_double_counted(): void
    {
        // FreePurchaseController writes BOTH a Purchase and a subscription for
        // a drip course; counting both tables would report one person twice.
        $course = $this->makeCourse('drip');
        $cookies = $this->arriveWithSource($course);

        $this->withCredentials()->withCookies($cookies)->postJson("/api/purchase/free/{$course->id}", [
            'email' => 'both@example.com', 'name' => 'B', 'phone' => '0912345678',
        ])->assertOk();

        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseCount('drip_subscriptions', 1);

        $rows = $this->trafficRows($course);

        $this->assertSame(1, array_sum(array_column($rows, 'order_count')), '同一次領取只能算一筆');
    }

    public function test_unsubscribed_claimers_are_still_counted(): void
    {
        $course = $this->makeCourse('drip');
        $cookies = $this->arriveWithSource($course);

        $this->withCookies($cookies)->post('/drip/subscribe', [
            'email' => 'gone@example.com', 'nickname' => 'C', 'course_id' => $course->id,
        ]);

        DripSubscription::firstOrFail()->update(['status' => 'unsubscribed']);

        // Claiming happened, and that link brought them in (FR-040).
        $this->assertSame(1, array_sum(array_column($this->trafficRows($course), 'order_count')));
    }

    public function test_claims_without_a_source_fall_into_direct(): void
    {
        $course = $this->makeCourse('drip');

        $this->post('/drip/subscribe', [
            'email' => 'direct@example.com', 'nickname' => 'D', 'course_id' => $course->id,
        ]);

        $rows = $this->trafficRows($course);

        $this->assertCount(1, $rows);
        $this->assertSame('(直接造訪)', $rows[0]['display_source']);
        $this->assertSame(1, $rows[0]['order_count']);
    }

    public function test_csv_export_includes_free_claims(): void
    {
        $course = $this->makeCourse('drip');
        $cookies = $this->arriveWithSource($course);

        $this->withCookies($cookies)->post('/drip/subscribe', [
            'email' => 'csv@example.com', 'nickname' => 'E', 'course_id' => $course->id,
        ]);

        $csv = $this->actingAs($this->admin())
            ->get("/admin/courses/{$course->id}/traffic/export")
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('csv@example.com', $csv);
        $this->assertStringContainsString('instagram', $csv);
    }

    public function test_paid_course_report_is_unchanged(): void
    {
        // A course with a price has no free claims; the report must behave
        // exactly as before this story.
        $course = Course::create([
            'name' => 'Paid', 'slug' => 'paid-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 1000, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ]);

        $this->assertSame([], $this->trafficRows($course));
    }
}
