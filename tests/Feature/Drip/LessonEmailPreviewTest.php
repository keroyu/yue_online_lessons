<?php

namespace Tests\Feature\Drip;

use App\Models\Course;
use App\Models\DripEmailEvent;
use App\Models\DripSubscription;
use App\Models\Lesson;
use App\Models\User;
use App\Services\DripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 010 US17 — the admin previews a drip letter as it actually goes out.
 *
 * The point of the endpoint is fidelity: it renders the real DripLessonMail
 * (FR-030/FR-031), so the assertions here are about the parts a front-end
 * re-render would silently get wrong — UTM stamping, the {{classroom_url}}
 * substitution, the footer, the assembled subject.
 *
 * The other half is that previewing must cost nothing: no pixel, no real
 * unsubscribe token, no events written (FR-032).
 */
class LessonEmailPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function makeDripCourse(): Course
    {
        return Course::create([
            'name'               => 'Drip Course',
            'slug'               => 'drip-course',
            'tagline'            => 'tag',
            'description'        => 'desc',
            'price'              => 0,
            'instructor_name'    => 'Tester',
            'type'               => 'lecture',
            'status'             => 'selling',
            'course_type'        => 'drip',
            'drip_interval_days' => 3,
            'is_published'       => true,
            'is_visible'         => true,
            'payment_gateway'    => 'payuni',
        ]);
    }

    private function makeLesson(Course $course, ?string $md = null, int $sortOrder = 1): Lesson
    {
        return Lesson::create([
            'course_id'   => $course->id,
            'title'       => '第一課：為什麼你一直卡住',
            'content_md'  => $md,
            'sort_order'  => $sortOrder,
        ]);
    }

    private function preview(Lesson $lesson)
    {
        return $this->get("/admin/drip/lessons/{$lesson->id}/email-preview");
    }

    public function test_admin_gets_the_rendered_letter(): void
    {
        $course = $this->makeDripCourse();
        $lesson = $this->makeLesson($course, "這是**內文**\n\n[進教室]({{classroom_url}})");

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->preview($lesson)
            ->assertOk()
            ->assertJsonStructure(['subject', 'html']);

        $html = $response->json('html');

        // Rendered through the real mail pipeline, not the raw markdown.
        $this->assertStringContainsString('<strong>內文</strong>', $html);
        // Footer comes from the blade, so its presence proves the blade ran.
        $this->assertStringContainsString('停止接收', $html);
        // Placeholder greeting, and the subject assembled the same way as a send.
        $this->assertSame(
            DripService::PREVIEW_GREETING_NAME . '，' . $lesson->title,
            $response->json('subject')
        );
        $this->assertStringContainsString('Hi ' . DripService::PREVIEW_GREETING_NAME, $html);
        // The type scale lives on the blade's wrapper, so the preview only shows
        // the real letter for as long as it keeps coming from the blade. A
        // front-end re-render would lose it without failing anything else here.
        $this->assertStringContainsString('font-size:16px;line-height:1.75', $html);
    }

    public function test_links_carry_the_same_utm_stamp_as_a_real_send(): void
    {
        $course = $this->makeDripCourse();
        $lesson = $this->makeLesson($course, '[進教室]({{classroom_url}})');

        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->preview($lesson)
            ->assertOk()
            ->json('html');

        // {{classroom_url}} resolved, and stamped as the first letter of the sequence.
        $this->assertStringContainsString('/member/classroom/' . $course->id, $html);
        $this->assertStringContainsString('utm_source=drip', $html);
        $this->assertStringContainsString('utm_content=lesson-1', $html);
    }

    public function test_preview_emits_no_tracking_pixel_and_no_live_unsubscribe_token(): void
    {
        $course = $this->makeDripCourse();
        $lesson = $this->makeLesson($course, 'body');

        $subscriber = User::factory()->create();
        $subscription = DripSubscription::create([
            'user_id'           => $subscriber->id,
            'course_id'         => $course->id,
            'subscribed_at'     => now(),
            'emails_sent'       => 1,
            'status'            => 'active',
            'status_changed_at' => now(),
            'unsubscribe_token' => 'live-token-must-not-leak',
        ]);

        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->preview($lesson)
            ->assertOk()
            ->json('html');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('drip/track/open', $html);
        $this->assertStringNotContainsString($subscription->unsubscribe_token, $html);
        // Read-only: previewing must not look like an open, or a send.
        $this->assertSame(0, DripEmailEvent::count());
    }

    public function test_lesson_without_content_falls_back_to_the_blade_notice(): void
    {
        $lesson = $this->makeLesson($this->makeDripCourse(), null);

        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->preview($lesson)
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('新的內容已經解鎖了', $html);
    }

    public function test_non_drip_lesson_is_not_previewable(): void
    {
        $course = $this->makeDripCourse();
        $course->update(['course_type' => 'standard']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->preview($this->makeLesson($course, 'body'))
            ->assertNotFound();
    }

    public function test_endpoint_requires_staff(): void
    {
        $lesson = $this->makeLesson($this->makeDripCourse(), 'body');

        $this->preview($lesson)->assertRedirect('/login');

        // StaffMiddleware bounces to the homepage rather than 403-ing.
        $this->actingAs(User::factory()->create(['role' => 'member']))
            ->preview($lesson)
            ->assertRedirect('/');
    }

    public function test_sales_consultant_can_preview(): void
    {
        // The preview opens from the subscriber tab, which consultants can see —
        // an admin-only endpoint would break the link for them.
        $lesson = $this->makeLesson($this->makeDripCourse(), 'body');

        $this->actingAs(User::factory()->create(['role' => 'member', 'is_sales_consultant' => true]))
            ->preview($lesson)
            ->assertOk();
    }
}
