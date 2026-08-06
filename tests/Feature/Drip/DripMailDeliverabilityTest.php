<?php

namespace Tests\Feature\Drip;

use App\Mail\DripLessonMail;
use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 010 US6 — the sequence mail must carry a machine-readable unsubscribe.
 * Without it the only signal is the wording in the body, and Gmail started
 * dropping the mail into the Promotions tab once that wording stopped
 * saying「退訂」.
 */
class DripMailDeliverabilityTest extends TestCase
{
    use RefreshDatabase;

    private function mail(): DripLessonMail
    {
        return new DripLessonMail(
            lessonTitle: '第一課',
            htmlContent: '<p>內容</p>',
            hasVideo: false,
            classroomUrl: 'https://example.test/member/classroom/1',
            unsubscribeUrl: 'https://example.test/drip/unsubscribe/abc-123',
            courseName: '免費電子書',
            greetingName: '小明',
        );
    }

    public function test_mail_declares_one_click_unsubscribe(): void
    {
        $headers = $this->mail()->headers();

        $this->assertSame(
            '<https://example.test/drip/unsubscribe/abc-123>',
            $headers->text['List-Unsubscribe']
        );
        $this->assertSame('List-Unsubscribe=One-Click', $headers->text['List-Unsubscribe-Post']);
    }

    public function test_body_keeps_a_recognisable_unsubscribe_link(): void
    {
        $html = $this->mail()->render();

        $this->assertStringContainsString('https://example.test/drip/unsubscribe/abc-123', $html);
        $this->assertStringContainsString('Unsubscribe', $html);
        $this->assertStringContainsString('停止接收', $html);
    }

    public function test_one_click_unsubscribe_urls_bypass_csrf(): void
    {
        // RFC 8058 clients POST with no session; CSRF verification would 419 them.
        // bootstrap/app.php's middleware closure only runs once the HTTP kernel
        // is resolved, so resolve it before reading the excluded paths.
        app(\Illuminate\Contracts\Http\Kernel::class);

        $except = app(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)->getExcludedPaths();

        $this->assertContains('drip/unsubscribe/*', $except);
        $this->assertContains('newsletter/unsubscribe/*', $except);
    }

    /**
     * 010 US16 / FR-028 — the confirm page now asks twice with a 5s wait, but
     * that guard lives in Vue only. A mail client posting straight here has no
     * page to press twice, so a single POST must still take effect. Guards
     * against anyone later "completing" the two-stage flow server-side.
     */
    public function test_a_single_post_still_stops_the_emails(): void
    {
        $course = Course::create([
            'name'               => '免費電子書',
            'slug'               => 'free-ebook',
            'tagline'            => 'tag',
            'description'        => 'desc',
            'price'              => 0,
            'instructor_name'    => 'Tester',
            'type'               => 'ebook',
            'status'             => 'selling',
            'course_type'        => 'drip',
            'drip_interval_days' => 3,
            'is_published'       => true,
            'is_visible'         => true,
            'payment_gateway'    => 'payuni',
        ]);

        $subscription = DripSubscription::create([
            'user_id'       => User::factory()->create()->id,
            'course_id'     => $course->id,
            'subscribed_at' => now(),
            'emails_sent'   => 1,
            'status'        => 'active',
        ]);

        $this->post("/drip/unsubscribe/{$subscription->unsubscribe_token}")
            ->assertRedirect();

        $this->assertSame('unsubscribed', $subscription->fresh()->status);

        // Repeating it (mail clients retry) must stay harmless.
        $this->post("/drip/unsubscribe/{$subscription->unsubscribe_token}")
            ->assertRedirect();

        $this->assertSame('unsubscribed', $subscription->fresh()->status);
    }
}
