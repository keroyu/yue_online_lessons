<?php

namespace Tests\Feature\Storefront;

use App\Mail\NewsletterWelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 002 US21 — the hero form subscribes to the newsletter without an OTP.
 *
 * What the tests have to hold down is the premise that makes skipping the code
 * acceptable at all (FR-067 / D61): this path adds a list row, never an
 * identity. If `email_verified_at` or a login session ever leaks in here, the
 * argument for single opt-in collapses with it.
 */
class HeroSubscribeTest extends TestCase
{
    use RefreshDatabase;

    private function subscribe(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/newsletter/quick-subscribe', array_merge([
            'email'    => 'visitor@example.com',
            'nickname' => '訪客',
        ], $overrides));
    }

    public function test_a_new_visitor_is_subscribed_in_one_step(): void
    {
        Mail::fake();

        $this->subscribe()->assertRedirect();

        $user = User::where('email', 'visitor@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('subscribed', $user->newsletter_status);
        $this->assertSame('訪客', $user->nickname);
        $this->assertNotNull($user->newsletter_unsubscribe_token);

        Mail::assertSent(NewsletterWelcomeMail::class, 1);
    }

    public function test_the_path_grants_no_identity(): void
    {
        // The whole single-opt-in argument rests on this (FR-067 / D61).
        Mail::fake();

        $this->subscribe();

        $this->assertNull(User::where('email', 'visitor@example.com')->first()->email_verified_at);
        $this->assertGuest();
    }

    public function test_subscribing_twice_sends_one_welcome_mail(): void
    {
        Mail::fake();

        $this->subscribe();
        $first = User::where('email', 'visitor@example.com')->first();
        $subscribedAt = $first->newsletter_subscribed_at;

        $this->subscribe()->assertRedirect();

        Mail::assertSent(NewsletterWelcomeMail::class, 1);
        $this->assertEquals($subscribedAt, $first->fresh()->newsletter_subscribed_at);
    }

    public function test_an_existing_nickname_is_never_overwritten(): void
    {
        Mail::fake();
        $user = User::create(['email' => 'visitor@example.com', 'nickname' => '我自己取的', 'role' => 'member']);

        $this->subscribe(['nickname' => '表單填的']);

        $this->assertSame('我自己取的', $user->fresh()->nickname);
    }

    public function test_a_blank_nickname_on_an_existing_account_is_filled_in(): void
    {
        Mail::fake();
        $user = User::create(['email' => 'visitor@example.com', 'role' => 'member']);

        $this->subscribe(['nickname' => '表單填的']);

        $this->assertSame('表單填的', $user->fresh()->nickname);
    }

    public function test_the_honeypot_blocks_the_request(): void
    {
        Mail::fake();

        $this->subscribe(['website' => 'http://spam.example'])
            ->assertSessionHasErrors('website');

        $this->assertNull(User::where('email', 'visitor@example.com')->first());
    }

    public function test_a_malformed_email_is_rejected(): void
    {
        Mail::fake();

        $this->subscribe(['email' => 'not-an-email'])->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_a_nickname_is_required(): void
    {
        Mail::fake();

        $this->subscribe(['nickname' => '   '])->assertSessionHasErrors('nickname');
    }

    public function test_the_existing_otp_route_still_requires_a_code(): void
    {
        // D60: the new path is a separate endpoint precisely so nothing a caller
        // sends can turn the OTP off on the one that has it.
        Mail::fake();

        $this->post('/newsletter/subscribe', ['email' => 'visitor@example.com'])->assertRedirect();

        $user = User::where('email', 'visitor@example.com')->first();
        $this->assertTrue($user === null || $user->newsletter_status !== 'subscribed');
    }
}
