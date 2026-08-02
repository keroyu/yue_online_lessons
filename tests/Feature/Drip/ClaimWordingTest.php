<?php

namespace Tests\Feature\Drip;

use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 010 US1 — free products are "claimed", not "subscribed to", and they are
 * 商品 rather than 課程 (the catalogue includes ebooks). A visitor who already
 * claimed and then logged out used to hit a dead end: 「此 Email 已訂閱此課程」
 * told them nothing about what to do next, so the message now points at login.
 */
class ClaimWordingTest extends TestCase
{
    use RefreshDatabase;

    private function makeDripCourse(): Course
    {
        return Course::create([
            'name'                => '猴子也能懂的 AI Road Map',
            'slug'                => 'ai-roadmap',
            'tagline'             => 'tag',
            'description'         => 'desc',
            'price'               => 0,
            'instructor_name'     => 'Tester',
            'type'                => 'ebook',
            'status'              => 'selling',
            'course_type'         => 'drip',
            'drip_interval_days'  => 3,
            'is_published'        => true,
            'is_visible'          => true,
            'payment_gateway'     => 'payuni',
        ]);
    }

    private function claim(Course $course, User $user, string $status = 'active'): DripSubscription
    {
        return DripSubscription::create([
            'user_id'       => $user->id,
            'course_id'     => $course->id,
            'subscribed_at' => now(),
            'emails_sent'   => 0,
            'status'        => $status,
        ]);
    }

    public function test_claiming_again_points_the_visitor_at_their_inbox(): void
    {
        Mail::fake();

        $course = $this->makeDripCourse();
        $user = User::factory()->create(['email' => 'reader@example.com']);
        $this->claim($course, $user);

        $response = $this->post('/drip/subscribe', [
            'course_id' => $course->id,
            'email'     => 'reader@example.com',
            'nickname'  => '小明',
        ]);

        // The flash carries the address so the form can name the inbox
        $response->assertSessionHas('drip_already_claimed', 'reader@example.com');

        $error = session('errors')->first('email');
        $this->assertStringContainsString('領取過', $error);
        $this->assertStringContainsString('信箱', $error);
        // The content is delivered by mail — never promise on-site viewing
        $this->assertStringNotContainsString('登入', $error);
        $this->assertStringNotContainsString('觀看', $error);
        $this->assertStringNotContainsString('訂閱', $error);
        $this->assertStringNotContainsString('課程', $error);

        // No second code is sent and no duplicate subscription is created
        Mail::assertNothingSent();
        $this->assertSame(1, DripSubscription::where('course_id', $course->id)->count());
    }

    public function test_stopped_receiver_is_told_they_cannot_claim_again(): void
    {
        Mail::fake();

        $course = $this->makeDripCourse();
        $user = User::factory()->create(['email' => 'gone@example.com']);
        $this->claim($course, $user, 'unsubscribed');

        $this->post('/drip/subscribe', [
            'course_id' => $course->id,
            'email'     => 'gone@example.com',
            'nickname'  => '小明',
        ]);

        $error = session('errors')->first('email');
        $this->assertStringContainsString('商品', $error);
        $this->assertStringNotContainsString('訂閱', $error);
        Mail::assertNothingSent();
    }

    public function test_member_one_click_claim_reports_in_claim_wording(): void
    {
        $course = $this->makeDripCourse();
        $user = User::factory()->create();
        $this->claim($course, $user);

        $error = app(\App\Services\DripService::class)->subscribe($user, $course)['error'];

        $this->assertStringContainsString('領取', $error);
        $this->assertStringNotContainsString('訂閱', $error);
    }
}
