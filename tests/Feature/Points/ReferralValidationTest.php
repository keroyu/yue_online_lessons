<?php

namespace Tests\Feature\Points;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ReferralValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('referral-validate:127.0.0.1');
    }

    public function test_nonexistent_code_returns_chinese_message(): void
    {
        $this->postJson('/api/checkout/validate-referral', ['referral_code' => 'NOTACODE'])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => '推薦碼不存在，請再次確認']);
    }

    public function test_self_referral_is_blocked(): void
    {
        $user = User::factory()->create(['referral_activated_at' => now()]);

        $this->actingAs($user)
            ->postJson('/api/checkout/validate-referral', ['referral_code' => $user->referral_code])
            ->assertStatus(422)
            ->assertJson(['message' => '不可使用自己的推薦碼']);
    }

    public function test_inactive_referrer_is_blocked(): void
    {
        $referrer = User::factory()->create(['referral_activated_at' => null]);

        $this->postJson('/api/checkout/validate-referral', ['referral_code' => $referrer->referral_code])
            ->assertStatus(422)
            ->assertJson(['message' => '此推薦碼目前無法使用']);
    }

    public function test_valid_active_code_passes(): void
    {
        $referrer = User::factory()->create(['referral_activated_at' => now()]);

        $this->postJson('/api/checkout/validate-referral', [
            'referral_code' => $referrer->referral_code,
            'buyer_email'   => 'someone-else@example.com',
        ])
            ->assertOk()
            ->assertJson(['success' => true, 'rate' => 10]);
    }

    public function test_repeated_failures_are_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/checkout/validate-referral', ['referral_code' => 'BADCODE' . $i])
                ->assertStatus(422);
        }

        $this->postJson('/api/checkout/validate-referral', ['referral_code' => 'BADCODEX'])
            ->assertStatus(429);
    }
}
