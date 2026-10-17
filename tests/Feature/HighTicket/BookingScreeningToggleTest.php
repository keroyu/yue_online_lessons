<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\User;
use App\Services\ConsultationSlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 011 US41 / FR-255 — screening can be switched off per course. Off means no
 * step 1 at all: no questions shipped, no gate on the submit, no answers kept.
 */
class BookingScreeningToggleTest extends TestCase
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

    private function makeSlots(int $count = 4): Carbon
    {
        $at = Carbon::parse('+1 day 10:00', ConsultationSlotService::DISPLAY_TZ)->utc();

        for ($i = 0; $i < $count; $i++) {
            ConsultationSlot::create([
                'starts_at' => $at->copy()->addMinutes($i * ConsultationSlot::UNIT_MINUTES),
            ]);
        }

        return $at;
    }

    private function bookingPayload(Carbon $start, array $overrides = []): array
    {
        return array_merge([
            'name'           => 'Applicant',
            'email'          => 'applicant@example.com',
            'phone'          => '0912345678',
            'occupation'     => '設計師',
            'bottleneck'     => '收入不穩',
            'expertise'      => '設計',
            'commitments'    => [true, true, true],
            'slot_starts_at' => $start->toIso8601String(),
        ], $overrides);
    }

    /** Answers that every gate would refuse: no budget, and only advice wanted. */
    private function failingAnswers(): array
    {
        return [
            'screen_timeline'   => 'exploring',
            'screen_budget'     => 'none',
            'screen_authority'  => 'none',
            'screen_pain'       => 'curious',
            'screen_next_step'  => 'advice_only',
            'screen_experience' => 'not_started',
            'screen_learning'   => 'self_only',
        ];
    }

    public function test_existing_and_new_courses_default_to_screening_on(): void
    {
        $this->assertTrue($this->makeHighTicketCourse()->high_ticket_screening);
    }

    public function test_the_sales_page_ships_no_questions_when_off(): void
    {
        $course = $this->makeHighTicketCourse(['high_ticket_screening' => false]);

        $this->get("/course/{$course->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('screeningQuestions', [])
                ->where('course.high_ticket_screening', false));
    }

    public function test_the_sales_page_ships_the_questions_when_on(): void
    {
        $course = $this->makeHighTicketCourse();

        $this->get("/course/{$course->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('screeningQuestions', 7)
                ->where('course.high_ticket_screening', true));
    }

    public function test_the_screen_endpoint_refuses_when_off(): void
    {
        $course = $this->makeHighTicketCourse(['high_ticket_screening' => false]);

        $this->postJson("/course/{$course->id}/screen", array_merge([
            'name'       => 'Applicant',
            'email'      => 'applicant@example.com',
            'screen_ack' => true,
        ], $this->failingAnswers()))->assertStatus(422);

        $this->assertSame(0, HighTicketLead::count());
    }

    public function test_booking_without_answers_succeeds_and_keeps_no_screening_when_off(): void
    {
        Mail::fake();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse(['high_ticket_screening' => false]);

        $this->postJson("/course/{$course->id}/book", $this->bookingPayload($start))->assertOk();

        $lead = HighTicketLead::sole();
        $this->assertNull($lead->screened_at);
        $this->assertNull($lead->screening_score);
        $this->assertNotNull($lead->confirm_token);
    }

    /** Off means off: answers that would be vetoed are neither judged nor stored. */
    public function test_booking_ignores_answers_when_off(): void
    {
        Mail::fake();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse(['high_ticket_screening' => false]);

        $this->postJson("/course/{$course->id}/book", $this->bookingPayload($start, $this->failingAnswers()))
            ->assertOk();

        $lead = HighTicketLead::sole();
        $this->assertNull($lead->screen_budget);
        $this->assertNull($lead->screen_next_step);
        $this->assertNull($lead->screening_score);
        $this->assertNull($lead->screened_at);
    }

    public function test_booking_with_failing_answers_is_still_refused_when_on(): void
    {
        Mail::fake();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/book", $this->bookingPayload($start, $this->failingAnswers()))
            ->assertStatus(422);
    }

    public function test_the_waitlist_ignores_answers_when_off(): void
    {
        Mail::fake();
        $course = $this->makeHighTicketCourse(['high_ticket_screening' => false]);

        $payload = $this->bookingPayload(Carbon::now(), $this->failingAnswers());
        unset($payload['slot_starts_at']);

        $this->postJson("/course/{$course->id}/waitlist", $payload)->assertOk();

        $this->assertNull(HighTicketLead::sole()->screened_at);
    }

    public function test_admin_can_switch_screening_off(): void
    {
        $course = $this->makeHighTicketCourse();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/courses/{$course->id}", [
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
                'high_ticket_screening'  => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($course->fresh()->high_ticket_screening);
    }
}
