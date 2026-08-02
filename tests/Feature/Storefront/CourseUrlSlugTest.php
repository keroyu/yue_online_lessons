<?php

namespace Tests\Feature\Storefront;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Course overrides resolveRouteBinding() to accept either a slug or an id, but
 * URL *generation* used to fall back to the primary key — so every link built
 * from the model (OG meta, the admin tracking-link builder, post-claim
 * redirects) pointed at /course/{id}. getRouteKey() now prefers the slug and
 * keeps the id for courses that have not been given one yet.
 */
class CourseUrlSlugTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name'            => 'Course',
            'slug'            => 'my-course',
            'tagline'         => 'tag',
            'description'     => 'desc',
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

    public function test_generated_url_uses_the_slug(): void
    {
        $course = $this->makeCourse();

        $this->assertStringEndsWith('/course/my-course', route('course.show', $course));
    }

    public function test_course_without_a_slug_falls_back_to_its_id(): void
    {
        $course = $this->makeCourse(['slug' => null]);

        $this->assertStringEndsWith("/course/{$course->id}", route('course.show', $course));
    }

    public function test_admin_traffic_page_hands_the_slug_url_to_the_link_builder(): void
    {
        $course = $this->makeCourse();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get("/admin/courses/{$course->id}/traffic")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('course.url', route('course.show', $course))
                ->where('course.url', fn ($url) => str_ends_with($url, '/course/my-course')));
    }
}
