<?php

namespace Tests\Feature\HighTicket;

use App\Mail\BookingVerifyMail;
use App\Models\EmailTemplate;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 011 FR-057 — one configurable customer-service address, reachable from system
 * mail and from any email template via {{support_email}}.
 */
class SupportEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_falls_back_to_the_built_in_default(): void
    {
        $this->assertSame(SiteSetting::DEFAULT_SUPPORT_EMAIL, SiteSetting::supportEmail());
    }

    public function test_a_blank_setting_still_falls_back(): void
    {
        SiteSetting::set(SiteSetting::SUPPORT_EMAIL_KEY, '   ');

        $this->assertSame(SiteSetting::DEFAULT_SUPPORT_EMAIL, SiteSetting::supportEmail());
    }

    public function test_a_configured_address_wins(): void
    {
        SiteSetting::set(SiteSetting::SUPPORT_EMAIL_KEY, 'help@example.com');

        $this->assertSame('help@example.com', SiteSetting::supportEmail());
    }

    /** Available to every template without the caller passing it (FR-057). */
    public function test_templates_can_use_the_variable_without_the_caller_supplying_it(): void
    {
        SiteSetting::set(SiteSetting::SUPPORT_EMAIL_KEY, 'help@example.com');

        $template = EmailTemplate::create([
            'name'       => 'T',
            'event_type' => 'course_gifted',
            'subject'    => '有問題寄 {{support_email}}',
            'body_md'    => "課程 {{course_name}}\n\n聯絡：{{support_email}}",
        ]);

        $vars = ['{{course_name}}' => '寫作課'];

        $this->assertSame('有問題寄 help@example.com', $template->renderSubject($vars));
        $this->assertStringContainsString('help@example.com', $template->renderBody($vars));
        $this->assertStringContainsString('help@example.com', $template->renderText($vars));
    }

    /** A caller that passes the variable explicitly must not be overridden. */
    public function test_an_explicit_value_beats_the_global(): void
    {
        SiteSetting::set(SiteSetting::SUPPORT_EMAIL_KEY, 'help@example.com');

        $template = EmailTemplate::create([
            'name'       => 'T',
            'event_type' => 'course_gifted',
            'subject'    => '{{support_email}}',
            'body_md'    => 'x',
        ]);

        $this->assertSame('override@example.com', $template->renderSubject(['{{support_email}}' => 'override@example.com']));
    }

    public function test_the_verify_mail_prints_the_configured_address(): void
    {
        SiteSetting::set(SiteSetting::SUPPORT_EMAIL_KEY, 'help@example.com');
        Mail::fake();

        Mail::to('booker@example.com')->send(new BookingVerifyMail(
            '小明',
            '寫作課',
            '8/6（週四）13:00',
            30,
            'https://example.com/booking/confirm/tok',
            '8/6 14:25',
        ));

        Mail::assertSent(BookingVerifyMail::class, function (BookingVerifyMail $mail) {
            $html = $mail->render();

            return str_contains($html, 'help@example.com')
                && str_contains($html, '8/6（週四）13:00')
                && str_contains($html, '8/6 14:25')
                && str_contains($html, 'https://example.com/booking/confirm/tok');
        });
    }

    public function test_the_verify_mail_subject_carries_the_one_hour_deadline(): void
    {
        $mail = new BookingVerifyMail('小明', '寫作課', '8/6 13:00', 30, 'https://x/y', '8/6 14:25');

        $this->assertSame('【請於 1 小時內確認】寫作課 預約時段保留中', $mail->envelope()->subject);
    }

    public function test_admin_can_save_the_support_email(): void
    {
        $admin = User::create(['email' => 'admin@example.com', 'role' => 'admin']);

        $this->actingAs($admin)
            ->put('/admin/email-templates/support-email', ['support_email' => 'help@example.com'])
            ->assertRedirect();

        $this->assertSame('help@example.com', SiteSetting::supportEmail());
    }

    public function test_a_malformed_address_is_rejected(): void
    {
        $admin = User::create(['email' => 'admin@example.com', 'role' => 'admin']);

        $this->actingAs($admin)
            ->put('/admin/email-templates/support-email', ['support_email' => 'not-an-email'])
            ->assertSessionHasErrors('support_email');
    }

    public function test_the_endpoint_requires_staff(): void
    {
        $this->put('/admin/email-templates/support-email', ['support_email' => 'help@example.com'])
            ->assertRedirect('/login');
    }

    /**
     * The legal modal lives in the footer, so every page needs the address —
     * there is no controller to prop it in from (FR-057).
     */
    public function test_it_is_shared_with_every_inertia_page(): void
    {
        SiteSetting::set(SiteSetting::SUPPORT_EMAIL_KEY, 'help@example.com');

        $this->get('/')->assertInertia(fn ($page) => $page->where('supportEmail', 'help@example.com'));
    }

    public function test_the_shared_value_falls_back_when_unset(): void
    {
        $this->get('/')->assertInertia(
            fn ($page) => $page->where('supportEmail', SiteSetting::DEFAULT_SUPPORT_EMAIL)
        );
    }

    /**
     * Guards the sweep: the address used to be hardcoded in six places, so a
     * setting that only half the app respects would be worse than none.
     */
    public function test_no_source_file_hardcodes_the_address(): void
    {
        $roots = [base_path('app'), base_path('resources')];
        $offenders = [];

        foreach ($roots as $root) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

            foreach ($files as $file) {
                if (!$file->isFile() || !in_array($file->getExtension(), ['php', 'vue', 'blade'], true)) {
                    continue;
                }

                $path = $file->getPathname();

                // The fallback constant is the one legitimate copy.
                if (str_ends_with($path, 'app/Models/SiteSetting.php')) {
                    continue;
                }

                if (str_contains((string) file_get_contents($path), SiteSetting::DEFAULT_SUPPORT_EMAIL)) {
                    $offenders[] = str_replace(base_path() . '/', '', $path);
                }
            }
        }

        $this->assertSame([], $offenders, '客服信箱應改用 SiteSetting::supportEmail() 或 {{support_email}}');
    }
}
