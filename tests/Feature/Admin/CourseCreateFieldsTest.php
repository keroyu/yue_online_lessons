<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 004 US2 / FR-015~016 — the create flow must accept the same field set as the
 * edit flow. Fields without a rule in StoreCourseRequest are silently dropped by
 * validated(), so a drip course created from the admin form used to be saved as
 * a plain standard course with no conversion targets.
 */
class CourseCreateFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Base payload mirroring what CourseForm.vue posts for a standard course.
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'             => '新課程',
            'slug'             => 'new-course',
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

    private function publishedTarget(string $slug): Course
    {
        return Course::create([
            'name'            => 'Target ' . $slug,
            'slug'            => $slug,
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 2000,
            'instructor_name' => 'Tester',
            'type'            => 'full',
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ]);
    }

    public function test_drip_course_can_be_created_without_a_price(): void
    {
        $targetA = $this->publishedTarget('target-a');
        $targetB = $this->publishedTarget('target-b');

        $this->actingAs($this->admin())
            ->post('/admin/courses', $this->payload([
                'slug'               => 'drip-course',
                'price'              => null,
                'course_type'        => 'drip',
                'drip_interval_days' => 3,
                'target_course_ids'  => [$targetA->id, $targetB->id],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $course = Course::where('slug', 'drip-course')->firstOrFail();

        $this->assertSame('drip', $course->course_type);
        $this->assertSame(0.0, (float) $course->price);
        $this->assertSame(3, $course->drip_interval_days);
        $this->assertEqualsCanonicalizing(
            [$targetA->id, $targetB->id],
            $course->dripConversionTargets()->pluck('target_course_id')->all()
        );
    }

    public function test_high_ticket_hide_price_is_persisted_on_create(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/courses', $this->payload([
                'slug'                    => 'ht-course',
                'type'                    => 'high_ticket',
                'high_ticket_hide_price'  => true,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertTrue(Course::where('slug', 'ht-course')->firstOrFail()->high_ticket_hide_price);
    }

    public function test_standard_course_still_requires_a_price(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/courses', $this->payload(['price' => null]))
            ->assertSessionHasErrors('price');

        $this->assertDatabaseMissing('courses', ['slug' => 'new-course']);
    }

    public function test_create_page_receives_available_target_courses(): void
    {
        $target = $this->publishedTarget('target-a');

        $this->actingAs($this->admin())
            ->get('/admin/courses/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('availableCourses', 1)
                ->where('availableCourses.0.id', $target->id));
    }
}
