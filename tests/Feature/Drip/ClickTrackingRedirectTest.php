<?php

namespace Tests\Feature\Drip;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DripTrackingController::click() must redirect to the lesson's own stored
 * promo_url, never to a client-supplied `url` query param — trusting that
 * param turns the endpoint into an open redirect through our own domain.
 */
class ClickTrackingRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourseWithLesson(?string $promoUrl): Lesson
    {
        $course = Course::create([
            'name' => 'Drip C', 'slug' => 'drip-c', 'tagline' => 't', 'description' => 'd',
            'price' => 0, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'drip', 'drip_interval_days' => 3,
            'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);

        return Lesson::create([
            'course_id' => $course->id, 'title' => 'L1', 'sort_order' => 1,
            'duration_seconds' => 0, 'promo_url' => $promoUrl,
        ]);
    }

    public function test_redirect_ignores_a_spoofed_url_param_and_uses_the_lessons_own_promo_url(): void
    {
        $lesson = $this->makeCourseWithLesson('https://legit-target.example/offer');
        $user = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($user)->get('/drip/track/click?' . http_build_query([
            'les' => $lesson->id,
            'url' => 'https://evil.example/phish',
        ]));

        $response->assertRedirect('https://legit-target.example/offer');
    }

    public function test_redirect_falls_back_to_home_when_lesson_has_no_promo_url(): void
    {
        $lesson = $this->makeCourseWithLesson(null);
        $user = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($user)->get('/drip/track/click?' . http_build_query([
            'les' => $lesson->id,
            'url' => 'https://evil.example/phish',
        ]));

        $response->assertRedirect('/');
    }
}
