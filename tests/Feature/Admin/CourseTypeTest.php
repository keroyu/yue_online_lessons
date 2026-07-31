<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 004 US2 / FR-013 — the product category value set. 'ebook' is new; 'high_ticket'
 * is covered too because its original migration only widened the enum on MySQL,
 * leaving the sqlite test database unable to store it (004 D10).
 */
class CourseTypeTest extends TestCase
{
    use RefreshDatabase;

    private function courseAttributes(string $type): array
    {
        return [
            'name'            => 'Course ' . $type,
            'slug'            => 'course-' . $type,
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 1000,
            'instructor_name' => 'Tester',
            'type'            => $type,
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ];
    }

    public function test_every_product_category_can_be_stored(): void
    {
        foreach (['lecture', 'mini', 'full', 'high_ticket', 'ebook'] as $type) {
            $course = Course::create($this->courseAttributes($type));

            $this->assertSame($type, $course->fresh()->type);
        }
    }

    public function test_high_ticket_flag_works_on_a_persisted_course(): void
    {
        $course = Course::create($this->courseAttributes('high_ticket'));

        $this->assertTrue($course->fresh()->is_high_ticket);
    }

    public function test_admin_can_save_a_course_as_ebook(): void
    {
        $course = Course::create($this->courseAttributes('lecture'));

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/courses/{$course->id}", [
                'name'             => $course->name,
                'slug'             => $course->slug,
                'tagline'          => $course->tagline,
                'description'      => $course->description,
                'price'            => 1000,
                'instructor_name'  => $course->instructor_name,
                'type'             => 'ebook',
                'content_category' => 'monetization',
                'status'           => 'selling',
                'course_type'      => 'standard',
                'payment_gateway'  => 'payuni',
            ])
            ->assertRedirect();

        $this->assertSame('ebook', $course->fresh()->type);
    }

    public function test_unknown_product_category_is_rejected(): void
    {
        $course = Course::create($this->courseAttributes('lecture'));

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/courses/{$course->id}", [
                'name'             => $course->name,
                'slug'             => $course->slug,
                'tagline'          => $course->tagline,
                'description'      => $course->description,
                'price'            => 1000,
                'instructor_name'  => $course->instructor_name,
                'type'             => 'audiobook',
                'content_category' => 'monetization',
                'status'           => 'selling',
                'course_type'      => 'standard',
                'payment_gateway'  => 'payuni',
            ])
            ->assertSessionHasErrors('type');
    }
}
