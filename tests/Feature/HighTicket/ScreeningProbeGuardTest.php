<?php

namespace Tests\Feature\HighTicket;

use App\Jobs\SendScreeningDeclineMailJob;
use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\User;
use App\Support\ScreeningProbeGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * 011 US49 — an IP that keeps submitting the screening gets the ordinary
 * refusal for a day, so trial and error stops teaching it the rubric.
 *
 * The point of the ban is that it cannot be told apart from a real refusal,
 * so the blocked response is compared key for key with one.
 */
class ScreeningProbeGuardTest extends TestCase
{
    use RefreshDatabase;

    private function course(): Course
    {
        return Course::create([
            'name' => 'HT Course', 'slug' => 'ht-' . uniqid(), 'tagline' => 'tag', 'description' => 'desc',
            'price' => 58000, 'instructor_name' => 'Tester', 'type' => 'high_ticket', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni', 'high_ticket_hide_price' => true,
        ])->fresh();
    }

    private function payload(string $email, bool $pass = true): array
    {
        return [
            'name'              => 'Applicant',
            'email'             => $email,
            'screen_ack'        => true,
            'screen_timeline'   => $pass ? 'immediate' : 'exploring',
            'screen_budget'     => $pass ? '10k_50k' : 'none',
            'screen_authority'  => 'self',
            'screen_pain'       => $pass ? 'severe' : 'curious',
            'screen_next_step'  => $pass ? 'start_now' : 'advice_only',
            'screen_experience' => '1_3y',
            'screen_learning'   => 'paid_course',
        ];
    }

    private function screen(Course $course, string $email, string $ip = '203.0.113.7', bool $pass = true): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson("/course/{$course->id}/screen", $this->payload($email, $pass));
    }

    public function test_third_submission_from_one_ip_gets_a_silent_refusal(): void
    {
        $course = $this->course();

        $this->screen($course, 'a@example.com')->assertOk()->assertJson(['passed' => true]);
        $this->screen($course, 'b@example.com')->assertOk()->assertJson(['passed' => true]);

        $blocked = $this->screen($course, 'c@example.com')->assertOk();

        $this->assertSame(['passed' => false, 'decline_html' => $course->declineHtml()], $blocked->json());
        $this->assertSame(2, HighTicketLead::count());
    }

    public function test_blocked_response_has_the_same_keys_as_a_real_refusal(): void
    {
        $course = $this->course();

        $real = $this->screen($course, 'real@example.com', '198.51.100.1', pass: false)->json();

        $this->screen($course, 'a@example.com');
        $this->screen($course, 'b@example.com');
        $blocked = $this->screen($course, 'c@example.com')->json();

        $this->assertSame(array_keys($real), array_keys($blocked));
        $this->assertSame($real, $blocked);
    }

    public function test_blocked_submission_leaves_no_trace(): void
    {
        Queue::fake();
        $course = $this->course();

        $this->screen($course, 'a@example.com', pass: false);
        $this->screen($course, 'b@example.com', pass: false);
        Queue::assertPushed(SendScreeningDeclineMailJob::class, 2);

        $this->screen($course, 'c@example.com', pass: false);
        // Same email as an existing lead: a blocked re-answer must not rewrite it either
        $this->screen($course, 'a@example.com');

        Queue::assertPushed(SendScreeningDeclineMailJob::class, 2);
        $this->assertFalse(HighTicketLead::where('email', 'c@example.com')->exists());
        $this->assertSame('declined', HighTicketLead::where('email', 'a@example.com')->value('status'));
    }

    public function test_every_submission_counts_even_with_the_same_email(): void
    {
        $course = $this->course();

        $this->screen($course, 'same@example.com', pass: false);
        $this->screen($course, 'same@example.com', pass: false);

        $this->screen($course, 'same@example.com')->assertJson(['passed' => false]);
    }

    public function test_ban_lasts_a_day_then_the_count_starts_over(): void
    {
        $course = $this->course();

        $this->screen($course, 'a@example.com');
        $this->screen($course, 'b@example.com');
        $this->screen($course, 'c@example.com')->assertJson(['passed' => false]);

        $this->travel(ScreeningProbeGuard::BAN_SECONDS - 60)->seconds();
        $this->screen($course, 'd@example.com')->assertJson(['passed' => false]);

        $this->travel(61)->seconds();
        $this->screen($course, 'e@example.com')->assertJson(['passed' => true]);
        $this->screen($course, 'f@example.com')->assertJson(['passed' => true]);
        $this->screen($course, 'g@example.com')->assertJson(['passed' => false]);
    }

    public function test_validation_errors_do_not_count(): void
    {
        $course = $this->course();

        foreach (range(1, 3) as $i) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
                ->postJson("/course/{$course->id}/screen", ['email' => 'x@example.com'])
                ->assertStatus(422);
        }

        $this->screen($course, 'a@example.com')->assertJson(['passed' => true]);
    }

    public function test_other_ips_are_unaffected(): void
    {
        $course = $this->course();

        foreach (['a', 'b', 'c'] as $name) {
            $this->screen($course, "{$name}@example.com");
        }

        $this->screen($course, 'other@example.com', '198.51.100.9')->assertJson(['passed' => true]);
    }

    public function test_staff_are_not_counted(): void
    {
        $course = $this->course();
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['a', 'b', 'c', 'd'] as $name) {
            $this->actingAs($admin)->screen($course, "{$name}@example.com")->assertJson(['passed' => true]);
        }

        $this->assertSame(4, HighTicketLead::count());
    }

    public function test_triggering_the_ban_is_logged(): void
    {
        Log::spy();
        $course = $this->course();

        foreach (['a', 'b', 'c'] as $name) {
            $this->screen($course, "{$name}@example.com");
        }

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn ($message, $context) => $message === 'Screening probe: IP banned'
                && $context['ip'] === '203.0.113.7'
                && $context['email'] === 'c@example.com'
                && $context['course_id'] === $course->id
        );
    }
}
