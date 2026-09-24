<?php

namespace Tests\Feature\Platform;

use App\Models\SiteSetting;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\NewsletterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 000 US13 — the first person to register on a fresh install becomes the admin.
 *
 * The rule has two halves and both matter: no administrator may already exist
 * (otherwise a deleted admin hands the panel to the next stranger who signs up),
 * and the account must either be the only one in the table or match
 * FIRST_ADMIN_EMAIL (otherwise a newsletter subscriber gets there first).
 */
class FirstAdminTest extends TestCase
{
    use RefreshDatabase;

    /** Register through the real OTP flow — that path is the only trigger. */
    private function registerViaOtp(string $email): void
    {
        Mail::fake();

        $this->post('/login/send-code', ['email' => $email])->assertRedirect();

        $code = VerificationCode::where('email', $email)->latest('id')->value('code');

        $this->post('/login/verify', [
            'email'       => $email,
            'code'        => $code,
            'agree_terms' => true,
        ])->assertRedirect();
    }

    public function test_the_first_otp_registration_becomes_the_administrator(): void
    {
        $this->registerViaOtp('owner@example.com');

        $user = User::where('email', 'owner@example.com')->firstOrFail();

        $this->assertSame('admin', $user->role);
        $this->assertSame('owner@example.com', SiteSetting::get(SiteSetting::SUPPORT_EMAIL_KEY));
    }

    public function test_the_second_registration_stays_a_member(): void
    {
        $this->registerViaOtp('owner@example.com');
        $this->post('/logout');
        $this->registerViaOtp('someone@example.com');

        $this->assertSame('member', User::where('email', 'someone@example.com')->value('role'));
        $this->assertSame('owner@example.com', SiteSetting::get(SiteSetting::SUPPORT_EMAIL_KEY));
    }

    public function test_nobody_is_promoted_while_an_administrator_exists(): void
    {
        User::create(['email' => 'boss@example.com', 'role' => 'admin']);

        $this->registerViaOtp('stranger@example.com');

        $this->assertSame('member', User::where('email', 'stranger@example.com')->value('role'));
    }

    public function test_only_the_configured_email_is_promoted(): void
    {
        config(['auth.first_admin_email' => 'owner@example.com']);

        // First account in the table, but not the configured one: stays a member.
        $this->registerViaOtp('stranger@example.com');
        $this->assertSame('member', User::where('email', 'stranger@example.com')->value('role'));

        $this->post('/logout');
        $this->registerViaOtp('owner@example.com');
        $this->assertSame('admin', User::where('email', 'owner@example.com')->value('role'));
    }

    public function test_the_configured_email_matches_regardless_of_case_and_spacing(): void
    {
        config(['auth.first_admin_email' => '  Owner@Example.com ']);

        $this->registerViaOtp('owner@example.com');

        $this->assertSame('admin', User::where('email', 'owner@example.com')->value('role'));
    }

    public function test_accounts_created_by_other_paths_are_never_promoted(): void
    {
        // A newsletter subscriber is the first row in the table; the panel is
        // not something an email address alone should buy.
        app(NewsletterService::class)->subscribeVerified('reader@example.com');

        $this->assertSame('member', User::where('email', 'reader@example.com')->value('role'));
        $this->assertNull(SiteSetting::get(SiteSetting::SUPPORT_EMAIL_KEY));
    }

    public function test_an_existing_support_email_is_not_overwritten(): void
    {
        SiteSetting::set(SiteSetting::SUPPORT_EMAIL_KEY, 'help@example.com');

        $this->registerViaOtp('owner@example.com');

        $this->assertSame('admin', User::where('email', 'owner@example.com')->value('role'));
        $this->assertSame('help@example.com', SiteSetting::get(SiteSetting::SUPPORT_EMAIL_KEY));
    }
}
