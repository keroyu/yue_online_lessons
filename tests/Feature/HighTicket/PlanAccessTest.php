<?php

namespace Tests\Feature\HighTicket;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 011 US21 — lesson-level entitlement (FR-088 / FR-089 / FR-091).
 *
 * The classroom used to answer one question: does this user own the course?
 * With plans it has to answer a narrower one per lesson, in four places at
 * once — the sidebar, the standalone list, current-lesson resolution, and the
 * completion counts. These tests pin all four, plus the two write endpoints
 * that no amount of hiding in the UI can protect.
 */
class PlanAccessTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    /** @var array<int, Lesson> 1-indexed so $this->lesson[1] is "EP1" */
    private array $lesson = [];

    private CoursePlan $planA;
    private CoursePlan $planB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::create([
            'name' => 'HT Course', 'slug' => 'ht-plan-' . uniqid(), 'tagline' => 't',
            'description' => 'd', 'price' => 50000, 'instructor_name' => 'I',
            'type' => 'high_ticket', 'status' => 'selling', 'course_type' => 'standard',
            'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);

        $chapter = Chapter::create([
            'course_id' => $this->course->id, 'title' => 'Ch1', 'sort_order' => 0,
        ]);

        // 6 lessons, all with video so nothing is filtered for unrelated reasons.
        for ($i = 1; $i <= 6; $i++) {
            $this->lesson[$i] = Lesson::create([
                'course_id' => $this->course->id,
                'chapter_id' => $i <= 4 ? $chapter->id : null,
                'title' => "EP{$i}",
                'video_platform' => 'youtube',
                'video_id' => "vid{$i}",
                'duration_seconds' => 600,
                'sort_order' => $i,
            ]);
        }

        // Deliberately overlapping (the requirement that rules out a column on
        // `lessons`), and EP6 belongs to neither — the unclassified case.
        $this->planA = CoursePlan::create(['course_id' => $this->course->id, 'name' => '方案A', 'price' => 30000, 'sort_order' => 0]);
        $this->planB = CoursePlan::create(['course_id' => $this->course->id, 'name' => '方案B', 'price' => 80000, 'sort_order' => 1]);

        $this->planA->lessons()->sync([$this->lesson[1]->id, $this->lesson[2]->id, $this->lesson[3]->id]);
        $this->planB->lessons()->sync([
            $this->lesson[2]->id, $this->lesson[4]->id, $this->lesson[5]->id,
        ]);
    }

    private function member(?CoursePlan $plan): User
    {
        $user = User::create(['email' => 'm' . uniqid() . '@example.com', 'role' => 'member']);

        Purchase::create([
            'user_id' => $user->id,
            'course_id' => $this->course->id,
            'course_plan_id' => $plan?->id,
            'buyer_email' => $user->email,
            'amount' => 30000,
            'currency' => 'TWD',
            'status' => 'paid',
            'type' => 'lead_conversion',
        ]);

        return $user;
    }

    /** Every lesson title the classroom page would render in its lists. */
    private function visibleTitles(User $user): array
    {
        $props = $this->actingAs($user)
            ->get("/member/classroom/{$this->course->id}")
            ->assertOk()
            ->viewData('page')['props'];

        $titles = [];

        foreach ($props['chapters'] as $chapter) {
            foreach ($chapter['lessons'] as $lesson) {
                $titles[] = $lesson['title'];
            }
        }

        foreach ($props['standaloneLessons'] as $lesson) {
            $titles[] = $lesson['title'];
        }

        sort($titles);

        return $titles;
    }

    // ── Sidebar + standalone filtering (FR-089) ──────────────────────────────

    public function test_plan_member_sees_only_their_own_lessons(): void
    {
        $this->assertSame(['EP1', 'EP2', 'EP3'], $this->visibleTitles($this->member($this->planA)));
    }

    public function test_overlapping_lesson_is_visible_to_both_plans(): void
    {
        $this->assertContains('EP2', $this->visibleTitles($this->member($this->planA)));
        $this->assertContains('EP2', $this->visibleTitles($this->member($this->planB)));
    }

    public function test_standalone_lessons_are_filtered_too(): void
    {
        // EP5 has no chapter and belongs to B only.
        $this->assertContains('EP5', $this->visibleTitles($this->member($this->planB)));
        $this->assertNotContains('EP5', $this->visibleTitles($this->member($this->planA)));
    }

    public function test_lesson_in_no_plan_is_hidden_from_every_plan_holder(): void
    {
        // EP6 belongs to neither plan: unclassified means unauthorised (D81).
        $this->assertNotContains('EP6', $this->visibleTitles($this->member($this->planA)));
        $this->assertNotContains('EP6', $this->visibleTitles($this->member($this->planB)));
    }

    public function test_null_plan_purchase_still_sees_everything(): void
    {
        // The regression guard for every pre-existing purchase (FR-087).
        $titles = $this->visibleTitles($this->member(null));

        $this->assertSame(['EP1', 'EP2', 'EP3', 'EP4', 'EP5', 'EP6'], $titles);
    }

    public function test_admin_sees_every_lesson(): void
    {
        $admin = User::create(['email' => 'admin-plan@example.com', 'role' => 'admin']);

        $this->assertSame(['EP1', 'EP2', 'EP3', 'EP4', 'EP5', 'EP6'], $this->visibleTitles($admin));
    }

    // ── current-lesson resolution (FR-089, third of the four points) ─────────

    public function test_direct_lesson_id_outside_the_plan_serves_no_content(): void
    {
        $user = $this->member($this->planA);

        $props = $this->actingAs($user)
            ->get("/member/classroom/{$this->course->id}?lesson_id={$this->lesson[5]->id}")
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertNotSame('EP5', $props['currentLesson']['title'] ?? null);
        $this->assertNotSame($this->lesson[5]->id, $props['currentLesson']['id'] ?? null);
    }

    public function test_default_current_lesson_lands_inside_the_plan(): void
    {
        $user = $this->member($this->planA);

        $props = $this->actingAs($user)
            ->get("/member/classroom/{$this->course->id}")
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertContains($props['currentLesson']['id'], [
            $this->lesson[1]->id, $this->lesson[2]->id, $this->lesson[3]->id,
        ]);
    }

    // ── write endpoints (FR-089) ─────────────────────────────────────────────

    public function test_mark_complete_outside_the_plan_is_forbidden(): void
    {
        $user = $this->member($this->planA);

        $this->actingAs($user)
            ->postJson("/member/classroom/{$this->course->id}/progress/{$this->lesson[5]->id}")
            ->assertForbidden();

        $this->assertDatabaseMissing('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $this->lesson[5]->id,
        ]);
    }

    public function test_mark_complete_inside_the_plan_still_works(): void
    {
        $user = $this->member($this->planA);

        $this->actingAs($user)
            ->postJson("/member/classroom/{$this->course->id}/progress/{$this->lesson[1]->id}")
            ->assertOk();

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $this->lesson[1]->id,
        ]);
    }

    public function test_mark_incomplete_outside_the_plan_is_forbidden(): void
    {
        $user = $this->member($this->planA);

        $this->actingAs($user)
            ->deleteJson("/member/classroom/{$this->course->id}/progress/{$this->lesson[5]->id}")
            ->assertForbidden();
    }

    // ── progress denominator (FR-091) ────────────────────────────────────────

    public function test_my_courses_progress_is_complete_when_the_whole_plan_is_watched(): void
    {
        $user = $this->member($this->planA);

        foreach ([1, 2, 3] as $i) {
            LessonProgress::create(['user_id' => $user->id, 'lesson_id' => $this->lesson[$i]->id]);
        }

        $data = $this->actingAs($user)
            ->get('/member/learning')
            ->assertOk()
            ->viewData('page')['props']['courses'];

        $row = collect($data)->firstWhere('id', $this->course->id);

        // 3 of 3, not 3 of 6: the denominator is the plan (FR-091).
        $this->assertSame(100, $row['progress_percent']);
    }

    public function test_member_detail_progress_counts_only_the_plan(): void
    {
        $user = $this->member($this->planA);
        $admin = User::create(['email' => 'admin-progress@example.com', 'role' => 'admin']);

        // The EP5 record is left over from before a downgrade: it must inflate
        // neither the numerator nor the denominator.
        LessonProgress::create(['user_id' => $user->id, 'lesson_id' => $this->lesson[1]->id]);
        LessonProgress::create(['user_id' => $user->id, 'lesson_id' => $this->lesson[5]->id]);

        $courses = $this->actingAs($admin)
            ->getJson("/admin/members/{$user->id}")
            ->assertOk()
            ->json('courses');

        $row = collect($courses)->firstWhere('id', $this->course->id);

        $this->assertSame(3, $row['total_lessons']);
        $this->assertSame(1, $row['completed_lessons']);
        $this->assertSame(33, $row['progress_percent']);
    }

    public function test_course_without_plans_is_unaffected(): void
    {
        $this->planA->lessons()->detach();
        $this->planB->lessons()->detach();
        $this->planA->delete();
        $this->planB->delete();

        $this->assertSame(
            ['EP1', 'EP2', 'EP3', 'EP4', 'EP5', 'EP6'],
            $this->visibleTitles($this->member(null)),
        );
    }
}
