<?php

namespace Tests\Feature\HighTicket;

use App\Models\Course;
use App\Models\User;
use App\Support\BookingScreening;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 011 US42 / FR-259 — the refusal shown after a failed screen is set per course
 * (HTML, rendered as-is), falls back to the FR-128 copy when blank, and only
 * ever travels on the failed /screen response.
 */
class BookingScreeningDeclineCopyTest extends TestCase
{
    use RefreshDatabase;

    private function makeHighTicketCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name'                   => 'HT Course',
            'slug'                   => 'ht-' . uniqid(),
            'tagline'                => 'tag',
            'description'            => 'desc',
            'price'                  => 58000,
            'instructor_name'        => 'Tester',
            'type'                   => 'high_ticket',
            'status'                 => 'selling',
            'course_type'            => 'standard',
            'is_published'           => true,
            'is_visible'             => true,
            'payment_gateway'        => 'payuni',
            'high_ticket_hide_price' => true,
        ], $overrides))->fresh();
    }

    /** Full marks unless overridden. */
    private function screeningPayload(array $overrides = []): array
    {
        return array_merge([
            'name'              => 'Applicant',
            'email'             => 'applicant@example.com',
            'screen_ack'        => true,
            'screen_timeline'   => 'immediate',
            'screen_budget'     => '10k_50k',
            'screen_authority'  => 'self',
            'screen_pain'       => 'severe',
            'screen_next_step'  => 'start_now',
            'screen_experience' => '1_3y',
            'screen_learning'   => 'paid_course',
        ], $overrides);
    }

    private function adminUpdate(Course $course, array $overrides = [])
    {
        return $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/courses/{$course->id}", array_merge([
                'name'                   => $course->name,
                'slug'                   => $course->slug,
                'tagline'                => $course->tagline,
                'description'            => $course->description,
                'price'                  => 58000,
                'instructor_name'        => $course->instructor_name,
                'type'                   => 'high_ticket',
                'content_category'       => 'monetization',
                'status'                 => 'selling',
                'course_type'            => 'standard',
                'payment_gateway'        => 'payuni',
                'high_ticket_hide_price' => true,
                'high_ticket_screening'  => true,
            ], $overrides));
    }

    public function test_a_failed_screen_returns_the_default_copy_when_none_is_set(): void
    {
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload(['screen_budget' => 'none']))
            ->assertOk()
            ->assertExactJson([
                'passed'       => false,
                'decline_html' => BookingScreening::DEFAULT_DECLINE_HTML,
            ]);
    }

    public function test_a_failed_screen_returns_the_course_copy_verbatim(): void
    {
        $html = '先看看<a href="https://example.com/ebook">這本電子書</a><br><strong>之後再見</strong>';
        $course = $this->makeHighTicketCourse(['high_ticket_decline_html' => $html]);

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload(['screen_budget' => 'none']))
            ->assertOk()
            ->assertJsonPath('passed', false)
            ->assertJsonPath('decline_html', $html);
    }

    public function test_a_passed_screen_carries_no_copy(): void
    {
        $course = $this->makeHighTicketCourse(['high_ticket_decline_html' => '<p>no</p>']);

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload())
            ->assertOk()
            ->assertExactJson(['passed' => true]);
    }

    public function test_the_sales_page_never_ships_the_copy(): void
    {
        $course = $this->makeHighTicketCourse(['high_ticket_decline_html' => '<p>SECRET-DECLINE</p>']);

        $response = $this->get("/course/{$course->slug}")->assertOk();

        $this->assertStringNotContainsString('SECRET-DECLINE', $response->getContent());
    }

    public function test_admin_saves_the_copy_and_blank_becomes_null(): void
    {
        $course = $this->makeHighTicketCourse();

        $this->adminUpdate($course, ['high_ticket_decline_html' => '<p>改去看電子書</p>'])
            ->assertSessionHasNoErrors();
        $this->assertSame('<p>改去看電子書</p>', $course->fresh()->high_ticket_decline_html);

        $this->adminUpdate($course, ['high_ticket_decline_html' => "  \n "])
            ->assertSessionHasNoErrors();
        $this->assertNull($course->fresh()->high_ticket_decline_html);
    }

    public function test_admin_copy_over_ten_thousand_characters_is_refused(): void
    {
        $course = $this->makeHighTicketCourse();

        $this->adminUpdate($course, ['high_ticket_decline_html' => str_repeat('a', 10001)])
            ->assertSessionHasErrors('high_ticket_decline_html');
    }

    public function test_the_edit_page_ships_the_copy_and_the_default(): void
    {
        $course = $this->makeHighTicketCourse(['high_ticket_decline_html' => '<p>x</p>']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get("/admin/courses/{$course->id}/edit")
            ->assertInertia(fn ($page) => $page
                ->where('course.high_ticket_decline_html', '<p>x</p>')
                ->where('defaultDeclineHtml', BookingScreening::DEFAULT_DECLINE_HTML));
    }
}
