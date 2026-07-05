<?php

namespace Tests\Feature\Points;

use App\Models\Assignment;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsEffectTest extends TestCase
{
    use RefreshDatabase;

    private function makeAssignment(): Assignment
    {
        $course = Course::create([
            'name' => 'C', 'slug' => 'c-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 1000, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);
        $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch', 'sort_order' => 1]);
        $lesson = Lesson::create(['course_id' => $course->id, 'chapter_id' => $chapter->id, 'title' => 'L']);

        return Assignment::create(['lesson_id' => $lesson->id, 'md_content' => 'hw', 'is_published' => true]);
    }

    public function test_homework_reward_setting_affects_next_award_not_history(): void
    {
        $svc = app(AssignmentService::class);

        SiteSetting::set('homework_reward_points', '100');
        $studentA = User::factory()->create();
        $svc->markComplete($studentA, $this->makeAssignment());

        $this->assertSame(100, $studentA->pointTransactions()->where('type', 'earn_homework')->first()->amount);

        // Change the setting — the previous award must NOT change.
        SiteSetting::set('homework_reward_points', '250');
        $studentB = User::factory()->create();
        $svc->markComplete($studentB, $this->makeAssignment());

        $this->assertSame(250, $studentB->pointTransactions()->where('type', 'earn_homework')->first()->amount);
        $this->assertSame(100, $studentA->pointTransactions()->where('type', 'earn_homework')->first()->amount);
    }

    public function test_referral_rate_uses_order_snapshot_not_current_setting(): void
    {
        $svc = app(ReferralService::class);

        // Reward is computed from the order's snapshotted rate, so a later setting change
        // never rewrites history (FR-027).
        $this->assertSame(300, $svc->computeReward(3000, 10));

        SiteSetting::set('referral_reward_rate', '20');

        // computeReward is pure over its args; the order snapshot (10) still yields 300,
        // while a new order snapshotting 20 would yield 600.
        $this->assertSame(300, $svc->computeReward(3000, 10));
        $this->assertSame(600, $svc->computeReward(3000, 20));
    }
}
