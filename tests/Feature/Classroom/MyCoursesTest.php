<?php

namespace Tests\Feature\Classroom;

use App\Models\Course;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "My courses" lists purchased courses only; drip courses are reached through
 * a subscription and never appear here, whatever Purchase they carry (003 FR-044).
 */
class MyCoursesTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(string $courseType): Course
    {
        return Course::create([
            'name' => ucfirst($courseType) . ' Course', 'slug' => $courseType . '-' . uniqid(),
            'tagline' => 't', 'description' => 'd', 'price' => 0, 'instructor_name' => 'I', 'type' => 'mini',
            'status' => 'selling', 'course_type' => $courseType,
            'is_published' => true, 'is_visible' => true,
        ]);
    }

    private function own(User $user, Course $course, string $type): void
    {
        Purchase::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'buyer_email' => $user->email,
            'amount' => 0,
            'currency' => 'TWD',
            'status' => 'paid',
            'type' => $type,
        ]);
    }

    private function listedCourseIds(User $user): array
    {
        $courses = $this->actingAs($user)
            ->get('/member/learning')
            ->assertOk()
            ->viewData('page')['props']['courses'];

        return collect($courses)->pluck('id')->all();
    }

    public function test_admin_does_not_see_the_drip_course_auto_assigned_on_creation(): void
    {
        $admin = User::create(['email' => 'admin-mc@example.com', 'role' => 'admin']);
        $drip = $this->makeCourse('drip');
        $standard = $this->makeCourse('standard');
        $this->own($admin, $drip, 'system_assigned');
        $this->own($admin, $standard, 'system_assigned');

        $ids = $this->listedCourseIds($admin);

        $this->assertNotContains($drip->id, $ids);
        $this->assertContains($standard->id, $ids);
    }

    public function test_member_still_sees_standard_courses(): void
    {
        $member = User::create(['email' => 'member-mc@example.com', 'role' => 'member']);
        $standard = $this->makeCourse('standard');
        $this->own($member, $standard, 'paid');

        $this->assertSame([$standard->id], $this->listedCourseIds($member));
    }

    public function test_member_does_not_see_a_gifted_drip_course(): void
    {
        $member = User::create(['email' => 'gift-mc@example.com', 'role' => 'member']);
        $drip = $this->makeCourse('drip');
        $this->own($member, $drip, 'gift');

        $this->assertSame([], $this->listedCourseIds($member));
    }
}
