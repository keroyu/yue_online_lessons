<?php

namespace Tests\Feature\Admin;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 004 US1 / D18 — deleting a course from the admin list is permanent, and
 * rows soft-deleted before that change can still be purged (they used to 404).
 */
class CourseDestroyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function course(): Course
    {
        return Course::create([
            'name'            => 'Doomed Course',
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 1000,
            'instructor_name' => 'Tester',
            'type'            => 'full',
            'status'          => 'draft',
            'course_type'     => 'standard',
            'is_published'    => false,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ]);
    }

    private function purchase(Course $course, string $type, string $status = 'paid'): void
    {
        $user = User::factory()->create();

        Purchase::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'buyer_email' => $user->email,
            'amount'      => 0,
            'currency'    => 'TWD',
            'status'      => $status,
            'type'        => $type,
        ]);
    }

    public function test_delete_removes_the_course_and_its_children_permanently(): void
    {
        $course = $this->course();
        $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'C1', 'sort_order' => 1]);
        $this->purchase($course, 'system_assigned');

        $this->actingAs($this->admin)
            ->delete("/admin/courses/{$course->id}")
            ->assertRedirect('/admin/courses')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('chapters', ['id' => $chapter->id]);
        $this->assertDatabaseMissing('purchases', ['course_id' => $course->id]);
    }

    public function test_a_legacy_soft_deleted_course_can_be_purged(): void
    {
        $course = $this->course();
        $course->delete();

        $this->actingAs($this->admin)
            ->delete("/admin/courses/{$course->id}")
            ->assertRedirect('/admin/courses');

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_a_course_with_paid_or_gifted_students_is_kept(): void
    {
        foreach (['paid', 'gift'] as $type) {
            $course = $this->course();
            $this->purchase($course, $type);

            $this->actingAs($this->admin)
                ->delete("/admin/courses/{$course->id}")
                ->assertSessionHas('error');

            $this->assertDatabaseHas('courses', ['id' => $course->id, 'deleted_at' => null]);
        }
    }

    public function test_a_course_with_order_items_is_kept_instead_of_500(): void
    {
        $course = $this->course();
        $user = User::factory()->create();
        $order = Order::create([
            'user_id'         => $user->id,
            'buyer_name'      => 'B',
            'buyer_email'     => $user->email,
            'buyer_phone'     => '0912345678',
            'total_amount'    => 1000,
            'status'          => 'pending',
            'payment_gateway' => 'payuni',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'course_id' => $course->id,
            'course_name' => $course->name, 'unit_price' => 1000,
        ]);

        $this->actingAs($this->admin)
            ->delete("/admin/courses/{$course->id}")
            ->assertRedirect('/admin/courses')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }
}
