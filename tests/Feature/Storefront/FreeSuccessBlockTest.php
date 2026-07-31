<?php

namespace Tests\Feature\Storefront;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 002 US11/US12 — the free-claim retention block and the delayed sales promo
 * are admin-authored course fields surfaced on the sales page. The gating and
 * rendering live in Vue; this covers persistence, validation and props.
 */
class FreeSuccessBlockTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name'            => 'Free Course',
            'slug'            => 'free-course',
            'tagline'         => 'tag',
            'description'     => 'desc',
            'description_md'  => '## Intro',
            'price'           => 0,
            'instructor_name' => 'Tester',
            'type'            => 'lecture',
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ], $overrides));
    }

    public function test_sales_page_exposes_retention_and_promo_props(): void
    {
        $course = $this->makeCourse([
            'free_success_md'     => '電子書已送到 {{email}}！',
            'promo_html'          => '<h3>限時優惠</h3>',
            'promo_delay_seconds' => 45,
        ]);

        $this->get("/course/{$course->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('course.free_success_md', '電子書已送到 {{email}}！')
                ->where('course.promo_html', '<h3>限時優惠</h3>')
                ->where('course.promo_delay_seconds', 45)
            );
    }

    public function test_promo_delay_is_null_by_default(): void
    {
        $course = $this->makeCourse();

        $this->get("/course/{$course->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('course.free_success_md', null)
                ->where('course.promo_delay_seconds', null)
            );
    }

    public function test_admin_can_save_the_new_fields(): void
    {
        $course = $this->makeCourse();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/courses/{$course->id}", [
                'name'                => $course->name,
                'slug'                => $course->slug,
                'tagline'             => $course->tagline,
                'description'         => $course->description,
                'price'               => 0,
                'instructor_name'     => $course->instructor_name,
                'type'                => 'lecture',
                'content_category'    => 'monetization',
                'status'              => 'selling',
                'course_type'         => 'standard',
                'payment_gateway'     => 'payuni',
                'free_success_md'     => '留客內容',
                'promo_html'          => '<p>促銷</p>',
                'promo_delay_seconds' => 60,
            ])
            ->assertRedirect();

        $course->refresh();
        $this->assertSame('留客內容', $course->free_success_md);
        $this->assertSame('<p>促銷</p>', $course->promo_html);
        $this->assertSame(60, $course->promo_delay_seconds);
    }

    public function test_negative_promo_delay_is_rejected(): void
    {
        $course = $this->makeCourse();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/courses/{$course->id}", [
                'name'                => $course->name,
                'slug'                => $course->slug,
                'tagline'             => $course->tagline,
                'description'         => $course->description,
                'price'               => 0,
                'instructor_name'     => $course->instructor_name,
                'type'                => 'lecture',
                'content_category'    => 'monetization',
                'status'              => 'selling',
                'course_type'         => 'standard',
                'payment_gateway'     => 'payuni',
                'promo_delay_seconds' => -5,
            ])
            ->assertSessionHasErrors('promo_delay_seconds');
    }
}
