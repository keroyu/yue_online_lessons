<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 004 US1 / FR-018 — the two front-end entry points on the admin course list.
 * The classroom route lives under the `member` prefix, so the link has to be
 * /member/classroom/{id}; a bare /classroom/{id} 404s (regression 2026-09-23).
 */
class AdminCourseListLinksTest extends TestCase
{
    use RefreshDatabase;

    private function course(): Course
    {
        return Course::create([
            'name'            => 'Linked Course',
            'slug'            => 'linked-course',
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 1000,
            'instructor_name' => 'Tester',
            'type'            => 'full',
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ]);
    }

    public function test_course_name_links_to_a_reachable_sales_page(): void
    {
        $course = $this->course();

        $this->get("/course/{$course->id}")->assertOk();
    }

    public function test_classroom_preview_link_is_reachable_for_an_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->course();

        $this->actingAs($admin)
            ->get("/member/classroom/{$course->id}")
            ->assertOk();
    }

    public function test_bare_classroom_path_does_not_exist(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->course();

        $this->actingAs($admin)
            ->get("/classroom/{$course->id}")
            ->assertNotFound();
    }
}
