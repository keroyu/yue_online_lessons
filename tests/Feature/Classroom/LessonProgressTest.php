<?php

namespace Tests\Feature\Classroom;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Completion is one-way for members (003 FR-026): they can mark a lesson done but
 * never roll it back. Only an admin may delete a lesson_progress row, and the guard
 * lives on the endpoint — hiding the green check in the sidebar is not a guard.
 */
class LessonProgressTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;
    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::create([
            'name' => 'C', 'slug' => 'c-1', 'tagline' => 't', 'description' => 'd',
            'price' => 1000, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);

        $chapter = Chapter::create(['course_id' => $this->course->id, 'title' => 'Ch', 'sort_order' => 1]);
        $this->lesson = Lesson::create([
            'course_id' => $this->course->id, 'chapter_id' => $chapter->id, 'title' => 'L',
        ]);
    }

    private function member(): User
    {
        $user = User::create(['email' => 'member@example.com', 'role' => 'member']);

        Purchase::create([
            'user_id' => $user->id, 'course_id' => $this->course->id, 'buyer_email' => $user->email,
            'amount' => 1000, 'status' => 'paid', 'type' => 'paid',
        ]);

        return $user;
    }

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    private function progressFor(User $user): void
    {
        LessonProgress::create(['user_id' => $user->id, 'lesson_id' => $this->lesson->id]);
    }

    private function progressUrl(): string
    {
        return "/member/classroom/{$this->course->id}/progress/{$this->lesson->id}";
    }

    public function test_member_cannot_mark_a_lesson_incomplete(): void
    {
        $member = $this->member();
        $this->progressFor($member);

        $response = $this->actingAs($member)->deleteJson($this->progressUrl());

        $response->assertStatus(403);
        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $member->id, 'lesson_id' => $this->lesson->id,
        ]);
    }

    public function test_admin_can_mark_a_lesson_incomplete(): void
    {
        $admin = $this->admin();
        $this->progressFor($admin);

        $response = $this->actingAs($admin)->deleteJson($this->progressUrl());

        $response->assertOk()->assertJson(['success' => true, 'is_completed' => false]);
        $this->assertDatabaseMissing('lesson_progress', [
            'user_id' => $admin->id, 'lesson_id' => $this->lesson->id,
        ]);
    }

    public function test_member_can_still_mark_a_lesson_complete(): void
    {
        $member = $this->member();

        $response = $this->actingAs($member)->postJson($this->progressUrl());

        $response->assertOk()->assertJson(['success' => true, 'is_completed' => true]);
        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $member->id, 'lesson_id' => $this->lesson->id,
        ]);
    }
}
