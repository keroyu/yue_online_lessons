<?php

namespace Tests\Feature\Newsletter;

use App\Mail\NewsletterWelcomeMail;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function codeFor(string $email): string
    {
        return VerificationCode::where('email', $email)->latest('id')->first()->code;
    }

    public function test_subscribe_sends_verification_code(): void
    {
        Mail::fake();

        $this->post('/newsletter/subscribe', ['email' => 'new@example.com'])
            ->assertSessionHas('newsletter_code_sent', true);

        Mail::assertSent(VerificationCodeMail::class);
        $this->assertDatabaseHas('verification_codes', ['email' => 'new@example.com']);
        // No account created until verified (anti subscribe-bombing).
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_verify_creates_member_subscribes_and_sends_welcome(): void
    {
        Mail::fake();

        $this->post('/newsletter/subscribe', ['email' => 'jane@example.com']);
        $code = $this->codeFor('jane@example.com');

        $this->post('/newsletter/verify', ['email' => 'jane@example.com', 'code' => $code])
            ->assertSessionHas('newsletter_subscribed', true);

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('member', $user->role);
        $this->assertSame('subscribed', $user->newsletter_status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->newsletter_unsubscribe_token);
        $this->assertAuthenticatedAs($user);
        Mail::assertSent(NewsletterWelcomeMail::class);
    }

    public function test_wrong_code_does_not_subscribe(): void
    {
        Mail::fake();
        $this->post('/newsletter/subscribe', ['email' => 'x@example.com']);

        $this->post('/newsletter/verify', ['email' => 'x@example.com', 'code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    public function test_existing_member_defaults_to_none_and_can_subscribe(): void
    {
        Mail::fake();
        $member = User::create(['email' => 'member@example.com', 'role' => 'member']);
        $this->assertSame('none', $member->fresh()->newsletter_status);

        $this->post('/newsletter/subscribe', ['email' => 'member@example.com']);
        $code = $this->codeFor('member@example.com');
        $this->post('/newsletter/verify', ['email' => 'member@example.com', 'code' => $code]);

        $this->assertSame('subscribed', $member->fresh()->newsletter_status);
    }

    public function test_logged_in_member_subscribes_own_email_without_otp(): void
    {
        Mail::fake();
        $member = User::create(['email' => 'loggedin@example.com', 'role' => 'member']);

        $this->actingAs($member)
            ->post('/newsletter/subscribe', ['email' => 'loggedin@example.com'])
            ->assertSessionHas('newsletter_subscribed', true);

        $this->assertSame('subscribed', $member->fresh()->newsletter_status);
        // No OTP code was generated for the logged-in fast path.
        $this->assertDatabaseMissing('verification_codes', ['email' => 'loggedin@example.com']);
        Mail::assertSent(NewsletterWelcomeMail::class);
        Mail::assertNotSent(VerificationCodeMail::class);
    }

    public function test_already_subscribed_member_gets_info_not_duplicate(): void
    {
        Mail::fake();
        $member = User::create([
            'email' => 'sub@example.com',
            'role' => 'member',
            'newsletter_status' => 'subscribed',
            'newsletter_unsubscribe_token' => 'tok-x',
        ]);

        $this->actingAs($member)
            ->post('/newsletter/subscribe', ['email' => 'sub@example.com'])
            ->assertSessionHas('newsletter_info', '你已在訂閱清單中');

        Mail::assertNothingSent();
    }

    public function test_honeypot_blocks_bots(): void
    {
        Mail::fake();

        $this->post('/newsletter/subscribe', ['email' => 'bot@example.com', 'website' => 'http://spam'])
            ->assertSessionHasErrors('website');

        Mail::assertNothingSent();
    }

    public function test_unsubscribe_keeps_member_status(): void
    {
        $user = User::create([
            'email' => 'unsub@example.com',
            'role' => 'member',
            'newsletter_status' => 'subscribed',
            'newsletter_unsubscribe_token' => 'tok-123',
        ]);

        $this->post('/newsletter/unsubscribe/tok-123')->assertRedirect(route('home'));

        $user->refresh();
        $this->assertSame('unsubscribed', $user->newsletter_status);
        $this->assertSame('member', $user->role); // account preserved
    }
}
