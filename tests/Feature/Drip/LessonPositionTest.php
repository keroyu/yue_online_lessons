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
 * 010 — the drip cursor counts emails, so it must be compared against a
 * lesson's POSITION, not its sort_order value.
 *
 * Every other drip test builds lessons with sort_order 0, 1, 2 — a convention
 * the admin never produces: `LessonController` numbers new lessons `max + 1`,
 * so a real course runs 1, 2, 3, and reordering can leave arbitrary gaps.
 * Reading sort_order as the cursor put the whole module one lesson behind on
 * live data: subscribers could not open the lesson they had just been emailed,
 * and the subscriber list reported 0 sent (so no open rate) for it.
 *
 * The lessons here are therefore deliberately NOT 0-based.
 */
class LessonPositionTest extends TestCase
{
    use RefreshDatabase;

    private function makeDripCourse(): Course
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
            'drip_interval_days' => 3,
            'is_published'       => true,
            'is_visible'         => true,
            'payment_gateway'    => 'payuni',
        ]);
    }

    /** @return array<int, Lesson> lessons in running order, numbered like the admin does */
    private function makeLessons(Course $course, array $sortOrders): array
    {
        $lessons = [];
        foreach ($sortOrders as $i => $sortOrder) {
            $lessons[] = Lesson::create([
                'course_id'      => $course->id,
                'title'          => "L{$i}",
                'video_platform' => 'vimeo',
                'video_id'       => '1032766965',
                'sort_order'     => $sortOrder,
            ]);
        }

        return $lessons;
    }

    private function subscribe(Course $course, int $emailsSent): DripSubscription
    {
        return DripSubscription::create([
            'user_id'           => User::factory()->create()->id,
            'course_id'         => $course->id,
            'subscribed_at'     => now()->subDays(4),
            'emails_sent'       => $emailsSent,
            'status'            => 'active',
            'status_changed_at' => now(),
            'unsubscribe_token' => uniqid(),
        ]);
    }

    public function test_lessons_sent_are_unlocked_when_sort_order_starts_at_one(): void
    {
        $course  = $this->makeDripCourse();
        $lessons = $this->makeLessons($course, [1, 2, 3]);
        $service = app(DripService::class);

        // Got the first email only.
        $first = $this->subscribe($course, 1);
        $this->assertTrue($service->isLessonUnlocked($first, $lessons[0]), '收到第一封信卻打不開第一課');
        $this->assertFalse($service->isLessonUnlocked($first, $lessons[1]));

        // Got the second.
        $second = $this->subscribe($course, 2);
        $this->assertTrue($service->isLessonUnlocked($second, $lessons[0]));
        $this->assertTrue($service->isLessonUnlocked($second, $lessons[1]), '收到第二封信卻打不開第二課');
        $this->assertFalse($service->isLessonUnlocked($second, $lessons[2]));
    }

    public function test_unlock_follows_running_order_even_with_gaps(): void
    {
        $course  = $this->makeDripCourse();
        $lessons = $this->makeLessons($course, [10, 20, 30]);
        $service = app(DripService::class);

        $sub = $this->subscribe($course, 2);

        $this->assertTrue($service->isLessonUnlocked($sub, $lessons[0]));
        $this->assertTrue($service->isLessonUnlocked($sub, $lessons[1]));
        $this->assertFalse($service->isLessonUnlocked($sub, $lessons[2]));
    }

    public function test_days_until_unlock_counts_from_the_first_lesson(): void
    {
        $course  = $this->makeDripCourse(); // 3-day interval
        $lessons = $this->makeLessons($course, [1, 2, 3]);
        $service = app(DripService::class);

        $sub = $this->subscribe($course, 1); // subscribed 4 days ago

        // Position 1 unlocks on day 3 — already past.
        $this->assertSame(0, $service->daysUntilUnlock($sub, $lessons[1]));
        // Position 2 unlocks on day 6 — two days out.
        $this->assertSame(2, $service->daysUntilUnlock($sub, $lessons[2]));
    }

    public function test_subscriber_stats_count_sends_and_open_rate_for_every_lesson(): void
    {
        $course  = $this->makeDripCourse();
        $lessons = $this->makeLessons($course, [1, 2, 3]);

        $onFirst  = $this->subscribe($course, 1);
        $onSecond = $this->subscribe($course, 2);
        $this->subscribe($course, 2);

        // One of the two who received the second email opened it.
        DripEmailEvent::create([
            'subscription_id' => $onSecond->id,
            'lesson_id'       => $lessons[1]->id,
            'event_type'      => 'opened',
        ]);
        DripEmailEvent::create([
            'subscription_id' => $onFirst->id,
            'lesson_id'       => $lessons[0]->id,
            'event_type'      => 'opened',
        ]);

        $stats = app(DripService::class)->getSubscriberStats($course)['lesson_stats'];

        $this->assertSame(3, $stats[0]['sent_count']);
        $this->assertSame(1, $stats[0]['open_count']);
        $this->assertEqualsWithDelta(1 / 3, $stats[0]['open_rate'], 0.0001);

        $this->assertSame(2, $stats[1]['sent_count'], '第二封信的已發送數被算成 0');
        $this->assertSame(1, $stats[1]['open_count']);
        $this->assertEqualsWithDelta(0.5, $stats[1]['open_rate'], 0.0001);

        // Nobody has reached the third email yet — no rate to show.
        $this->assertSame(0, $stats[2]['sent_count']);
        $this->assertNull($stats[2]['open_rate']);
    }
}
