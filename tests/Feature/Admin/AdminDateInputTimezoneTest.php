<?php

namespace Tests\Feature\Admin;

use App\Models\CouponCode;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin's `datetime-local` inputs post a bare wall-clock string with no
 * offset, and the admin means Taipei. The columns store UTC. Without the
 * conversion at the request boundary a course set to open at 09:00 opens at
 * 17:00 Taipei, and `after:now` compares against the wrong moment — it will
 * accept a time that is already eight hours in the past.
 */
class AdminDateInputTimezoneTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Asia/Taipei';

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function coursePayload(array $overrides = []): array
    {
        return array_merge([
            'name'             => '新課程',
            'slug'             => 'tz-course',
            'tagline'          => '副標題',
            'description'      => '描述',
            'price'            => 1000,
            'instructor_name'  => 'Tester',
            'type'             => 'lecture',
            'content_category' => 'monetization',
            'course_type'      => 'standard',
            'payment_gateway'  => 'payuni',
        ], $overrides);
    }

    public function test_course_sale_at_is_read_as_taipei_and_stored_as_utc(): void
    {
        $wallClock = now(self::TZ)->addDays(3)->setTime(9, 0);

        $this->actingAs($this->admin())
            ->post('/admin/courses', $this->coursePayload([
                'sale_at' => $wallClock->format('Y-m-d\TH:i'),
            ]))
            ->assertRedirect();

        $this->assertSame(
            $wallClock->copy()->utc()->format('Y-m-d H:i'),
            Course::where('slug', 'tz-course')->sole()->sale_at->utc()->format('Y-m-d H:i'),
        );
    }

    /**
     * The edit form must hand the value back in the same wall-clock it took, or
     * every save would walk the time eight hours forward.
     */
    public function test_course_edit_form_renders_sale_at_back_in_taipei(): void
    {
        $wallClock = now(self::TZ)->addDays(3)->setTime(9, 0);

        $course = Course::create($this->coursePayload([
            'slug'    => 'tz-roundtrip',
            'status'  => 'draft',
            'sale_at' => $wallClock->copy()->utc(),
        ]));

        $this->actingAs($this->admin())
            ->get("/admin/courses/{$course->id}/edit")
            ->assertInertia(fn ($page) => $page->where(
                'course.sale_at',
                $wallClock->format('Y-m-d\TH:i'),
            ));
    }

    /**
     * 08:00 Taipei is midnight UTC: a time just past it is in the past for the
     * admin but still "tomorrow" to a naive UTC read of the string.
     */
    public function test_a_past_taipei_time_is_rejected_even_when_it_reads_as_future_in_utc(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/courses', $this->coursePayload([
                'slug'    => 'tz-past',
                'sale_at' => now(self::TZ)->subHours(2)->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('sale_at');
    }

    public function test_coupon_expires_at_is_read_as_taipei_and_stored_as_utc(): void
    {
        $wallClock = now(self::TZ)->addDays(3)->setTime(23, 30);

        $this->actingAs($this->admin())
            ->post('/admin/coupons', [
                'code'       => 'TZ01',
                'type'       => 'fixed',
                'value'      => 100,
                'expires_at' => $wallClock->format('Y-m-d\TH:i'),
                'is_active'  => true,
            ])
            ->assertRedirect();

        $this->assertSame(
            $wallClock->copy()->utc()->format('Y-m-d H:i'),
            CouponCode::where('code', 'TZ01')->sole()->expires_at->utc()->format('Y-m-d H:i'),
        );
    }
}
