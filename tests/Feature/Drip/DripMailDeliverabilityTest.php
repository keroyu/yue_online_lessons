<?php

namespace Tests\Feature\Drip;

use App\Mail\DripLessonMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 010 US6 — the sequence mail must carry a machine-readable unsubscribe.
 * Without it the only signal is the wording in the body, and Gmail started
 * spam-foldering the mail once that wording stopped saying「退訂」.
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
}
