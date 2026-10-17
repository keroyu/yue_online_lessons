<?php

namespace Tests\Feature\Admin;

use App\Models\CouponCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponExpiryClearTest extends TestCase
{
    use RefreshDatabase;

    public function test_clearing_expires_at_on_update_sets_it_to_null(): void
    {
        $coupon = CouponCode::create([
            'code' => 'CLR1', 'type' => 'fixed', 'value' => 100,
            'expires_at' => now()->addDays(3), 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/coupons/{$coupon->id}", [
                'type' => 'fixed', 'value' => 100, 'course_id' => null,
                'expires_at' => '', 'max_uses' => '', 'is_active' => true, 'note' => '',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertNull($coupon->fresh()->expires_at);
    }
}
