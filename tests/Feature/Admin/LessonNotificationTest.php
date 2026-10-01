<?php

namespace Tests\Feature\Admin;

use App\Mail\LessonAddedNotification;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\EmailTemplate;
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

    // ── (e) direct lesson link and subject (FR-029 ~ FR-031) ───────────────

    private const OLD_SUBJECT = '您擁有的課程「{{course_name}}」新增了小節：{{lesson_title}}';
    private const OLD_BODY = "您好，\n\n您擁有的課程「{{course_name}}」新增了小節：\n「{{lesson_title}}」\n\n歡迎回來繼續學習：\n{{classroom_url}}\n\n經營者時間銀行";

    // The install-missing-templates migration (011 FR-052) seeds lesson_added,
    // so both paths have to clear it first to know which one they exercise.
    private function withoutTemplate(): void
    {
        EmailTemplate::forEvent('lesson_added')->delete();
    }

    private function lessonTemplate(string $subject = self::OLD_SUBJECT, string $body = self::OLD_BODY): EmailTemplate
    {
        $this->withoutTemplate();

        return EmailTemplate::create([
            'name' => '課程新增小節通知', 'event_type' => 'lesson_added',
            'subject' => $subject, 'body_type' => 'markdown', 'body_md' => $body,
        ]);
    }

    public function test_fallback_mail_links_straight_to_the_lesson_by_slug(): void
    {
        $this->withoutTemplate();
        $this->course->update(['slug' => 'growth-camp']);
        $lesson = $this->storeLesson(false);

        $mail = new LessonAddedNotification($this->course, $lesson);

        $mail->assertSeeInText("/member/classroom/growth-camp?lesson_id={$lesson->id}");
        $mail->assertDontSeeInText("/member/classroom/{$this->course->id}");
        $mail->assertSeeInText('立即觀看新小節');
    }

    public function test_a_course_without_slug_falls_back_to_its_id_and_keeps_the_lesson(): void
    {
        $this->withoutTemplate();
        $lesson = $this->storeLesson(false);
        $this->course->forceFill(['slug' => null])->save();

        $mail = new LessonAddedNotification($this->course->fresh(), $lesson);

        $mail->assertSeeInText("/member/classroom/{$this->course->id}?lesson_id={$lesson->id}");
    }

    public function test_template_classroom_url_now_points_at_the_lesson(): void
    {
        $this->course->update(['slug' => 'growth-camp']);
        $lesson = $this->storeLesson(false);
        $this->lessonTemplate();

        $mail = new LessonAddedNotification($this->course, $lesson);
        $url = "/member/classroom/growth-camp?lesson_id={$lesson->id}";

        $this->assertStringContainsString($url, html_entity_decode($mail->htmlBody));
        $this->assertStringContainsString($url, $mail->textBody);
        $this->assertSame($mail->lessonUrl, route('member.classroom', ['course' => $this->course, 'lesson_id' => $lesson->id]));
    }

    public function test_subject_names_the_lesson_but_not_the_course_on_both_paths(): void
    {
        $this->withoutTemplate();
        $lesson = $this->storeLesson(false);
        $expected = "您擁有的課程新增了小節：「{$lesson->title}」";

        foreach (['lecture', 'ebook', 'mini', 'full'] as $type) {
            $this->course->update(['type' => $type]);
            $mail = new LessonAddedNotification($this->course->fresh(), $lesson);
            $mail->assertHasSubject($expected);
        }

        $this->lessonTemplate(self::NEW_SUBJECT_FOR_TEST);
        $mail = new LessonAddedNotification($this->course->fresh(), $lesson);
        $mail->assertHasSubject($expected);
        $this->assertStringNotContainsString($this->course->name, $mail->envelope()->subject);
    }

    private const NEW_SUBJECT_FOR_TEST = '您擁有的課程新增了小節：「{{lesson_title}}」';

    private function runCopyMigration(string $direction = 'up'): void
    {
        $migration = require database_path('migrations/2026_09_28_000001_update_lesson_added_email_template_copy.php');
        $migration->{$direction}();
    }

    public function test_copy_migration_updates_an_untouched_template_and_only_the_one_sentence(): void
    {
        $template = $this->lessonTemplate();

        $this->runCopyMigration();
        $template->refresh();

        $this->assertSame(self::NEW_SUBJECT_FOR_TEST, $template->subject);
        $this->assertStringContainsString("立即觀看新小節：\n{{classroom_url}}", $template->body_md);
        $this->assertStringNotContainsString('歡迎回來繼續學習', $template->body_md);
        $this->assertStringContainsString('經營者時間銀行', $template->body_md);

        $this->runCopyMigration('down');
        $template->refresh();

        $this->assertSame(self::OLD_SUBJECT, $template->subject);
        $this->assertSame(self::OLD_BODY, $template->body_md);
    }

    public function test_copy_migration_leaves_a_customised_subject_alone(): void
    {
        $template = $this->lessonTemplate('【新課】{{lesson_title}}');

        $this->runCopyMigration();

        $this->assertSame('【新課】{{lesson_title}}', $template->fresh()->subject);
    }
}
