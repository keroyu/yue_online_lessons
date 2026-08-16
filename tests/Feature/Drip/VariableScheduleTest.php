<?php

namespace Tests\Feature\Drip;

use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\Lesson;
use App\Models\User;
use App\Services\DripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 010 US18 — each email carries its own send day, so a course can run
 * Day 0 -> 3 -> 7 -> 14 -> 30 instead of one fixed interval.
 *
 * The old evenly-spaced formula stays as the fallback for lessons with no
 * drip_day (D31), which is what every other drip test exercises.
 */
class VariableScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function makeDripCourse(?int $interval = 3): Course
    {
        return Course::create([
            'name'               => 'Drip ' . uniqid(),
            'slug'               => 'drip-' . uniqid(),
            'tagline'            => 'tag',
            'description'        => 'desc',
            'price'              => 0,
            'instructor_name'    => 'Tester',
            'type'               => 'lecture',
            'status'             => 'selling',
            'course_type'        => 'drip',
            'drip_interval_days' => $interval,
            'is_published'       => true,
            'is_visible'         => true,
            'payment_gateway'    => 'payuni',
        ]);
    }

    /**
     * @param  array<int, int|null>  $dripDays
     * @return array<int, Lesson>
     */
    private function makeLessons(Course $course, array $dripDays): array
    {
        $lessons = [];
        foreach ($dripDays as $i => $day) {
            $lessons[] = Lesson::create([
                'course_id'      => $course->id,
                'title'          => "L{$i}",
                'video_platform' => 'vimeo',
                'video_id'       => '1032766965',
                'sort_order'     => $i + 1, // as the admin numbers them (max + 1)
                'drip_day'       => $day,
            ]);
        }

        return $lessons;
    }

    private function subscribe(Course $course, int $daysAgo): DripSubscription
    {
        return DripSubscription::create([
            'user_id'           => User::factory()->create()->id,
            'course_id'         => $course->id,
            'subscribed_at'     => now()->subDays($daysAgo),
            'emails_sent'       => 1,
            'status'            => 'active',
            'status_changed_at' => now(),
            'unsubscribe_token' => uniqid(),
        ]);
    }

    public function test_variable_schedule_drives_how_many_emails_are_due(): void
    {
        $course = $this->makeDripCourse();
        $this->makeLessons($course, [0, 3, 7, 14, 30]);
        $service = app(DripService::class);

        $expected = [
            0  => 1,
            3  => 2,
            6  => 2,
            7  => 3,
            13 => 3,
            14 => 4,
            29 => 4,
            30 => 5,
            90 => 5, // capped at the lesson count
        ];

        foreach ($expected as $daysAgo => $due) {
            $this->assertSame(
                $due,
                $service->getUnlockedLessonCount($this->subscribe($course, $daysAgo)),
                "訂閱第 {$daysAgo} 天的應寄數不對",
            );
        }
    }

    public function test_lessons_without_a_drip_day_keep_the_even_interval(): void
    {
        $course = $this->makeDripCourse(3);
        $this->makeLessons($course, [null, null, null, null]);
        $service = app(DripService::class);

        $this->assertSame(1, $service->getUnlockedLessonCount($this->subscribe($course, 0)));
        $this->assertSame(2, $service->getUnlockedLessonCount($this->subscribe($course, 3)));
        $this->assertSame(3, $service->getUnlockedLessonCount($this->subscribe($course, 6)));
        $this->assertSame(4, $service->getUnlockedLessonCount($this->subscribe($course, 60)));
    }

    /** FR-002 — no usable schedule at all means everything is open (防呆). */
    public function test_no_schedule_at_all_unlocks_everything(): void
    {
        $course = $this->makeDripCourse(null);
        $this->makeLessons($course, [null, null, null]);

        $this->assertSame(
            3,
            app(DripService::class)->getUnlockedLessonCount($this->subscribe($course, 0)),
        );
    }

    public function test_days_until_unlock_follows_the_variable_schedule(): void
    {
        $course  = $this->makeDripCourse();
        $lessons = $this->makeLessons($course, [0, 3, 7, 14, 30]);
        $service = app(DripService::class);

        $sub = $this->subscribe($course, 5); // subscribed 5 days ago

        $this->assertSame(2, $service->daysUntilUnlock($sub, $lessons[2]), 'Day 7 應該還有 2 天');
        $this->assertSame(9, $service->daysUntilUnlock($sub, $lessons[3]), 'Day 14 應該還有 9 天');
        $this->assertSame(25, $service->daysUntilUnlock($sub, $lessons[4]), 'Day 30 應該還有 25 天');
    }

    public function test_video_access_fallback_anchor_uses_the_variable_schedule(): void
    {
        $course  = $this->makeDripCourse();
        $lessons = $this->makeLessons($course, [0, 3, 7]);
        $lessons[2]->update(['video_access_hours' => 48]);

        $sub = $this->subscribe($course, 0);

        // No 'sent' event yet, so the theoretical anchor applies: day 7 + 48h.
        $this->assertSame(
            $sub->subscribed_at->copy()->addDays(7)->addHours(48)->toDateTimeString(),
            app(DripService::class)->getVideoAccessExpiresAt($sub, $lessons[2])->toDateTimeString(),
        );
    }

    // --- Admin write path (FR-037) ---------------------------------------

    private function courseFormPayload(Course $course, array $overrides = []): array
    {
        return array_merge([
            'name'               => $course->name,
            'slug'               => $course->slug,
            'tagline'            => $course->tagline,
            'description'        => $course->description,
            'price'              => 0,
            'instructor_name'    => $course->instructor_name,
            'type'               => 'lecture',
            'content_category'   => 'monetization',
            'status'             => 'selling',
            'course_type'        => 'drip',
            'drip_interval_days' => 3,
            'payment_gateway'    => 'payuni',
        ], $overrides);
    }

    public function test_admin_can_save_a_variable_schedule(): void
    {
        $course  = $this->makeDripCourse();
        $lessons = $this->makeLessons($course, [null, null, null]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/courses/{$course->id}", $this->courseFormPayload($course, [
                'drip_days' => [
                    $lessons[0]->id => 0,
                    $lessons[1]->id => 3,
                    $lessons[2]->id => 7,
                ],
            ]))
            ->assertRedirect();

        $this->assertSame(0, $lessons[0]->fresh()->drip_day);
        $this->assertSame(3, $lessons[1]->fresh()->drip_day);
        $this->assertSame(7, $lessons[2]->fresh()->drip_day);
    }

    public function test_non_increasing_days_are_rejected(): void
    {
        $course  = $this->makeDripCourse();
        $lessons = $this->makeLessons($course, [null, null, null]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/courses/{$course->id}", $this->courseFormPayload($course, [
                'drip_days' => [
                    $lessons[0]->id => 0,
                    $lessons[1]->id => 7,
                    $lessons[2]->id => 3, // goes backwards
                ],
            ]))
            ->assertSessionHasErrors('drip_days');

        $this->assertNull($lessons[2]->fresh()->drip_day);
    }

    public function test_first_email_must_be_day_zero(): void
    {
        $course  = $this->makeDripCourse();
        $lessons = $this->makeLessons($course, [null, null]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/courses/{$course->id}", $this->courseFormPayload($course, [
                'drip_days' => [
                    $lessons[0]->id => 2,
                    $lessons[1]->id => 5,
                ],
            ]))
            ->assertSessionHasErrors('drip_days');
    }

    public function test_switching_back_to_standard_clears_the_schedule(): void
    {
        $course  = $this->makeDripCourse();
        $lessons = $this->makeLessons($course, [0, 3, 7]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/courses/{$course->id}", $this->courseFormPayload($course, [
                'course_type'        => 'standard',
                'drip_interval_days' => null,
                'price'              => 1000,
            ]))
            ->assertRedirect();

        foreach ($lessons as $lesson) {
            $this->assertNull($lesson->fresh()->drip_day);
        }
        $this->assertNull($course->fresh()->drip_interval_days);
    }

    /** FR-038 — a new lesson must not land as a null hole in an explicit schedule. */
    public function test_new_lesson_in_a_scheduled_course_gets_a_default_day(): void
    {
        $course = $this->makeDripCourse();
        $this->makeLessons($course, [0, 3, 7]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post("/admin/courses/{$course->id}/lessons", [
                'title'          => '第四課',
                'video_platform' => 'vimeo',
                'video_url'      => 'https://vimeo.com/1032766965',
            ])
            ->assertRedirect();

        $this->assertSame(10, $course->lessons()->orderBy('sort_order')->get()->last()->drip_day);
    }

    public function test_new_lesson_in_a_fallback_course_stays_null(): void
    {
        $course = $this->makeDripCourse();
        $this->makeLessons($course, [null, null]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post("/admin/courses/{$course->id}/lessons", [
                'title'          => '第三課',
                'video_platform' => 'vimeo',
                'video_url'      => 'https://vimeo.com/1032766965',
            ])
            ->assertRedirect();

        $this->assertNull($course->lessons()->orderBy('sort_order')->get()->last()->drip_day);
    }
}
