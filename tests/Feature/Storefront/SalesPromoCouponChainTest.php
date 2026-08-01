<?php

namespace Tests\Feature\Storefront;

use App\Models\CouponChain;
use App\Models\CouponCode;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The sales page promo block stores {alias} placeholders rather than literal
 * coupon codes, so a chain can rotate to a fresh code without anyone editing
 * the copy. This covers the substitution on the way out to the page, plus the
 * admin form getting the chain list it needs to insert placeholders.
 */
class SalesPromoCouponChainTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name'            => 'Promo Course',
            'slug'            => 'promo-course',
            'tagline'         => 'tag',
            'description'     => 'desc',
            'description_md'  => '## Intro',
            'price'           => 1000,
            'instructor_name' => 'Tester',
            'type'            => 'lecture',
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ], $overrides));
    }

    private function makeChain(string $alias, ?string $code = 'SAVE10'): CouponChain
    {
        $chain = CouponChain::create([
            'alias'         => $alias,
            'type'          => 'ratio',
            'value'         => 10,
            'code_max_uses' => 5,
            'is_active'     => true,
        ]);

        if ($code) {
            CouponCode::create([
                'code'      => $code,
                'type'      => $chain->type,
                'value'     => $chain->value,
                'max_uses'  => $chain->code_max_uses,
                'is_active' => true,
                'chain_id'  => $chain->id,
            ]);
        }

        return $chain;
    }

    public function test_placeholder_is_replaced_with_the_current_code(): void
    {
        $this->makeChain('earlybird', 'SAVE10');
        $course = $this->makeCourse([
            'promo_html'          => '<p>輸入折扣碼 {earlybird} 現折</p>',
            'promo_delay_seconds' => 0,
        ]);

        $this->get("/course/{$course->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('course.promo_html', '<p>輸入折扣碼 SAVE10 現折</p>')
            );
    }

    public function test_placeholder_follows_the_chain_when_the_code_rotates(): void
    {
        $chain = $this->makeChain('earlybird', 'SAVE10');
        $course = $this->makeCourse([
            'promo_html' => '<p>{earlybird}</p>',
        ]);

        // First code is used up and the chain issues the next one.
        $chain->codes()->first()->update(['used_count' => 5]);
        app(\App\Services\CouponChainService::class)->generateNextCode($chain);
        $fresh = $chain->currentCode();

        $this->get("/course/{$course->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('course.promo_html', "<p>{$fresh->code}</p>")
            );

        $this->assertNotSame('SAVE10', $fresh->code);
    }

    public function test_unknown_alias_is_left_untouched(): void
    {
        $course = $this->makeCourse([
            'promo_html' => '<p>{nosuchchain} 與 {{email}}</p>',
        ]);

        $this->get("/course/{$course->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('course.promo_html', '<p>{nosuchchain} 與 {{email}}</p>')
            );
    }

    public function test_chain_with_no_usable_code_leaves_the_placeholder(): void
    {
        $this->makeChain('empty', null);
        $course = $this->makeCourse([
            'promo_html' => '<p>{empty}</p>',
        ]);

        $this->get("/course/{$course->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('course.promo_html', '<p>{empty}</p>')
            );
    }

    public function test_admin_course_form_receives_the_chain_options(): void
    {
        $this->makeChain('earlybird');
        $course = $this->makeCourse();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get("/admin/courses/{$course->id}/edit")
            ->assertInertia(fn ($page) => $page
                ->where('couponChains.0.alias', 'earlybird')
                ->where('couponChains.0.label', 'earlybird（全站通用）')
            );

        $this->actingAs($admin)
            ->get('/admin/courses/create')
            ->assertInertia(fn ($page) => $page
                ->where('couponChains.0.alias', 'earlybird')
            );
    }
}
