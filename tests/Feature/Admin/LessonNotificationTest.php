<?php

namespace Tests\Feature\Admin;

use App\Mail\LessonAddedNotification;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Lesson;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 004 US6 — lesson notification recipients, back-fill path and honest reporting
 * (FR-024 ~ FR-028).
 *
 * The case that matters: on a multi-plan course the pivot is empty at creation
 * time, so every plan holder is filtered out and nobody gets the mail. That is
 * a timing problem, not a broken filter — so the test locks in both halves,
 * the silent zero at creation and the back-fill from the edit form.
 */
class LessonNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['email' => 'admin-notify@example.com', 'role' => 'admin']);
        $this->course = $this->makeCourse();
    }

    private function makeCourse(string $type = 'high_ticket'): Course
    {
        return Course::create([
            'name' => '高價陪跑', 'slug' => 'c-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 30000, 'instructor_name' => 'I', 'type' => $type, 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ]);
    }

    private function member(?CoursePlan $plan, string $status = 'paid', string $type = 'lead_conversion'): User
    {
        $user = User::create(['email' => 'm' . uniqid() . '@example.com', 'role' => 'member']);

        Purchase::create([
            'user_id' => $user->id,
            'course_id' => $this->course->id,
            'course_plan_id' => $plan?->id,
            'buyer_email' => $user->email,
            'amount' => 30000,
            'currency' => 'TWD',
            'status' => $status,
            'type' => $type,
        ]);

        return $user;
    }

    private function plan(string $name): CoursePlan
    {
        return CoursePlan::create([
            'course_id' => $this->course->id,
            'name' => $name,
            'price' => 30000,
            'sort_order' => 1,
        ]);
    }

    private function storeLesson(bool $notify = true): Lesson
    {
        $this->actingAs($this->admin)
            ->post("/admin/courses/{$this->course->id}/lessons", [
                'title' => '新小節',
                'duration_seconds' => 60,
                'notify_members' => $notify,
            ])
            ->assertRedirect();

        return Lesson::where('course_id', $this->course->id)->latest('id')->firstOrFail();
    }

    private function recipients(): array
    {
        return collect(Mail::sent(LessonAddedNotification::class))
            ->flatMap(fn ($mail) => collect($mail->to)->pluck('address'))
            ->sort()->values()->all();
    }

    // ── (a) the silent zero at creation, now visible ─────────────────────────

    public function test_creating_a_lesson_on_a_multi_plan_course_reaches_nobody_and_says_so(): void
    {
        Mail::fake();

        $planA = $this->plan('方案A');
        $this->member($planA);
        $this->member($planA);

        $this->storeLesson();

        // Plan holders cannot see a lesson no plan includes (011 FR-095), so
        // mailing them would send them looking for something hidden.
        Mail::assertNothingSent();

        $flash = session('success');
        $this->assertStringContainsString('0 位', $flash);
        $this->assertStringContainsString('尚未歸屬任何方案', $flash);
    }

    // ── (b) the back-fill path from the edit form ───────────────────────────

    public function test_notifying_from_the_edit_form_reaches_only_the_plans_that_include_the_lesson(): void
    {
        Mail::fake();

        $planA = $this->plan('方案A');
        $planB = $this->plan('方案B');
        $inA = $this->member($planA);
        $inB = $this->member($planB);
        $full = $this->member(null);

        $lesson = $this->storeLesson(notify: false);
        $lesson->plans()->sync([$planA->id]);

        $this->actingAs($this->admin)
            ->put("/admin/lessons/{$lesson->id}", [
                'title' => '新小節',
                'duration_seconds' => 60,
                'notify_members' => true,
            ])
            ->assertRedirect();

        // Plan A holder plus the unrestricted holder; plan B holder excluded.
        $this->assertSame(
            collect([$inA->email, $full->email])->sort()->values()->all(),
            $this->recipients()
        );

        $this->assertNotNull($lesson->fresh()->notified_at);
        $this->assertStringContainsString('2 位', session('success'));
        $this->assertFalse(in_array($inB->email, $this->recipients(), true));
    }

    public function test_notified_at_is_not_mass_assignable(): void
    {
        $lesson = $this->storeLesson(notify: false);

        $lesson->update(['notified_at' => now()]);

        $this->assertNull($lesson->fresh()->notified_at);
    }

    // ── (c) courses with no plans keep the old behaviour ────────────────────

    public function test_a_course_without_plans_still_notifies_every_holder_on_create(): void
    {
        Mail::fake();

        $one = $this->member(null);
        $two = $this->member(null);

        $this->storeLesson();

        $this->assertSame(
            collect([$one->email, $two->email])->sort()->values()->all(),
            $this->recipients()
        );
        $this->assertStringContainsString('2 位', session('success'));
    }

    // ── (d) refunded / system_assigned never receive ────────────────────────

    public function test_refunded_and_system_assigned_holders_are_never_notified(): void
    {
        Mail::fake();

        $this->member(null, status: 'refunded');
        $this->member(null, type: 'system_assigned');
        $good = $this->member(null);

        $this->storeLesson();

        $this->assertSame([$good->email], $this->recipients());
    }

    // ── (e) one failing send must not stop the rest, nor the save ───────────

    public function test_a_failing_send_does_not_stop_the_others_or_the_save(): void
    {
        $first = $this->member(null);
        $second = $this->member(null);

        $sent = [];
        Mail::shouldReceive('to')->andReturnUsing(function ($address) use (&$sent) {
            $sent[] = $address;

            if (count($sent) === 1) {
                throw new \RuntimeException('smtp down');
            }

            return new class {
                public function send($mailable): void {}
            };
        });

        $lesson = $this->storeLesson();

        $this->assertCount(2, $sent, 'the second recipient must still be attempted');
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'title' => '新小節']);
        $this->assertStringContainsString('1 位', session('success'));
    }

    // ── the preview counts the form adds up (FR-027) ────────────────────────

    public function test_notifiable_counts_bucket_holders_by_plan(): void
    {
        $planA = $this->plan('方案A');
        $planB = $this->plan('方案B');
        $this->member($planA);
        $this->member($planA);
        $this->member($planB);
        $this->member(null);
        $this->member(null, status: 'refunded');
        $this->member(null, type: 'system_assigned');

        $counts = app(\App\Services\LessonNotificationService::class)
            ->notifiableCounts($this->course);

        $this->assertSame(1, $counts['no_plan'], 'unrestricted holders, refunded/system_assigned excluded');
        $this->assertSame([$planA->id => 2, $planB->id => 1], $counts['plans']);
    }

    public function test_the_chapters_page_ships_the_counts_and_last_send_time(): void
    {
        $planA = $this->plan('方案A');
        $this->member($planA);
        $this->member(null);

        $lesson = $this->storeLesson();
        $lesson->plans()->sync([$planA->id]);

        $props = $this->actingAs($this->admin)
            ->get("/admin/courses/{$this->course->id}/chapters")
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame(1, $props['notifiableCounts']['no_plan']);
        $this->assertSame([$planA->id => 1], $props['notifiableCounts']['plans']);

        // Sent at creation to the unrestricted holder, so the stamp is there and
        // rendered in Taipei time, not UTC.
        $shipped = collect($props['standaloneLessons'])->firstWhere('id', $lesson->id);
        $this->assertSame(
            $lesson->fresh()->notified_at->timezone('Asia/Taipei')->format('Y/m/d H:i'),
            $shipped['notified_at']
        );
    }

    // ── draft / drip courses never notify ──────────────────────────────────

    public function test_draft_and_drip_courses_never_notify_even_if_the_flag_is_posted(): void
    {
        Mail::fake();

        $this->course->update(['status' => 'draft']);
        $this->member(null);

        $this->storeLesson();

        Mail::assertNothingSent();
    }
}
