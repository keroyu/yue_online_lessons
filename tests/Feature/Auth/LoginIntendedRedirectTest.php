<?php

namespace Tests\Feature\Auth;

use App\Models\Course;
use App\Models\User;
use App\Services\VerificationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 004 FR-030 — a guest bounced to /login by the `auth` middleware lands back on
 * the page they asked for. The lesson link in the notification email depends on
 * this: most recipients click it while logged out.
 */
class LoginIntendedRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function loginWithCode(string $email): \Illuminate\Testing\TestResponse
    {
        $code = app(VerificationCodeService::class)->generate($email)['code'];

        return $this->post('/login/verify', ['email' => $email, 'code' => $code]);
    }

    public function test_guest_returns_to_the_lesson_after_logging_in(): void
    {
        User::create(['email' => 'member@example.com', 'role' => 'member']);
        $course = Course::create([
            'name' => '課', 'slug' => 'growth-camp', 'tagline' => 't', 'description' => 'd',
            'price' => 100, 'instructor_name' => 'I', 'type' => 'full', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ]);
        $target = route('member.classroom', ['course' => $course, 'lesson_id' => 42]);

        $this->get($target)->assertRedirect(route('login'));

        $this->loginWithCode('member@example.com')->assertRedirect($target);
    }

    public function test_without_an_intended_url_login_still_goes_to_my_learning(): void
    {
        User::create(['email' => 'member@example.com', 'role' => 'member']);

        $this->loginWithCode('member@example.com')->assertRedirect(route('member.learning'));
    }
}
