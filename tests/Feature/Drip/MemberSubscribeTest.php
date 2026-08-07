<?php

namespace Tests\Feature\Drip;

use App\Mail\DripLessonMail;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 010 US2 / FR-029 — logged-in one-click subscribe used to 500 on every
 * request: `memberSubscribe(Request $request, ...)` sits in the bare
 * `App\Http\Controllers` namespace without a `use Illuminate\Http\Request;`
 * import, so the type hint resolved to the nonexistent
 * `App\Http\Controllers\Request` and route model binding blew up before the
 * method body ever ran.
 */
class MemberSubscribeTest extends TestCase
{
    use RefreshDatabase;

    private function makeDripCourse(): Course
    {
        $course = Course::create([
            'name' => '免費電子書', 'slug' => 'free-ebook-' . uniqid(),
            'tagline' => 't', 'description' => 'd', 'price' => 0,
            'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'drip', 'drip_interval_days' => 2,
            'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);

        Lesson::create([
            'course_id' => $course->id, 'title' => '第一課',
            'video_platform' => 'vimeo', 'video_id' => '1032766965',
            'sort_order' => 1, 'md_content' => '內容',
        ]);

        return $course;
    }

    public function test_logged_in_member_can_one_click_subscribe(): void
    {
        Mail::fake();
        $course = $this->makeDripCourse();
        $user = User::create(['email' => 'member@example.com', 'nickname' => '', 'role' => 'member']);

        $response = $this->actingAs($user)->post("/member/drip/subscribe/{$course->id}", [
            'nickname' => '小明',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('drip_subscriptions', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
        Mail::assertSent(DripLessonMail::class);
    }
}
