<?php

namespace Tests\Feature\Drip;

use App\Mail\DripLessonMail;
use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 010 US15 — claiming the free ebook no longer sends a verification code and no
 * longer logs anyone in.
 *
 * The code was never really verification: it gated `Auth::login()`. Once the
 * claim stops creating a session there is nothing left for it to protect, so it
 * goes — and the writes it used to guard have to be narrowed instead. An
 * unverified form may ADD a subscription; it may not TOUCH an existing account
 * (010 FR-023 / FR-025 / D20).
 */
class GuestClaimTest extends TestCase
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

    private function claimPayload(Course $course, array $overrides = []): array
    {
        return array_merge([
            'course_id' => $course->id,
            'email'     => 'newreader@example.com',
            'nickname'  => '小明',
        ], $overrides);
    }

    public function test_claiming_completes_in_one_post_and_sends_the_first_lesson(): void
    {
        Mail::fake();
        $course = $this->makeDripCourse();

        $this->post('/drip/subscribe', $this->claimPayload($course))
            ->assertSessionHas('drip_subscribed');

        $user = User::where('email', 'newreader@example.com')->firstOrFail();
        $this->assertSame(1, DripSubscription::where('user_id', $user->id)->count());
        Mail::assertSent(DripLessonMail::class);
    }

    public function test_claiming_does_not_log_anybody_in(): void
    {
        Mail::fake();
        $course = $this->makeDripCourse();

        $this->post('/drip/subscribe', $this->claimPayload($course));

        $this->assertFalse(Auth::check(), '領取不該建立 session');

        $user = User::where('email', 'newreader@example.com')->firstOrFail();
        $this->assertNull($user->last_login_at, '沒有人登入，就不該有登入時間');
    }

    public function test_a_claim_created_account_is_not_marked_verified(): void
    {
        Mail::fake();
        $course = $this->makeDripCourse();

        $this->post('/drip/subscribe', $this->claimPayload($course));

        $this->assertNull(
            User::where('email', 'newreader@example.com')->value('email_verified_at'),
            '沒驗證就不能宣稱已驗證',
        );
    }

    /**
     * The break that only appears once the login is gone: an unauthenticated
     * form could rename an existing member.
     */
    public function test_an_existing_members_nickname_survives_a_stranger_claiming(): void
    {
        Mail::fake();
        $course = $this->makeDripCourse();
        User::factory()->create(['email' => 'member@example.com', 'nickname' => '原本的暱稱']);

        $this->post('/drip/subscribe', $this->claimPayload($course, [
            'email'    => 'member@example.com',
            'nickname' => '陌生人亂填',
        ]));

        $this->assertSame('原本的暱稱', User::where('email', 'member@example.com')->value('nickname'));
    }

    public function test_an_empty_nickname_is_filled_in(): void
    {
        Mail::fake();
        $course = $this->makeDripCourse();
        User::factory()->create(['email' => 'blank@example.com', 'nickname' => null]);

        $this->post('/drip/subscribe', $this->claimPayload($course, ['email' => 'blank@example.com']));

        $this->assertSame('小明', User::where('email', 'blank@example.com')->value('nickname'));
    }

    public function test_the_honeypot_blocks_the_submission(): void
    {
        Mail::fake();
        $course = $this->makeDripCourse();

        $this->post('/drip/subscribe', $this->claimPayload($course, ['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, DripSubscription::count());
        Mail::assertNothingSent();
    }

    public function test_the_verify_route_is_gone(): void
    {
        $this->post('/drip/verify', [])->assertNotFound();
    }
}
