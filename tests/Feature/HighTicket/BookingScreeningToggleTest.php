<?php

namespace Tests\Feature\HighTicket;

use App\Jobs\SendScreeningDeclineMailJob;
use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\User;
use App\Services\ConsultationSlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * 011 US44 / FR-261 (replacing US41's "off skips step 1") — the switch picks
 * strict or lenient screening. Both run the same wizard; lenient only refuses
 * no budget or a total of 4 and under.
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
            'screen_learning'   => 'passive',
        ];
    }

    /** 10/10 and clear of every veto; override single answers from here. */
    private function strongAnswers(array $overrides = []): array
    {
        return array_merge([
            'screen_timeline'   => 'immediate',
            'screen_budget'     => '10k_50k',
            'screen_authority'  => 'self',
            'screen_pain'       => 'severe',
            'screen_next_step'  => 'start_now',
            'screen_experience' => '1_3y',
            'screen_learning'   => 'paid_course',
        ], $overrides);
    }

    /** 1 + 1 + 1 + $pain + 1, no veto: `moderate` totals 5, `low` totals 4. */
    private function borderlineAnswers(string $pain): array
    {
        return $this->strongAnswers([
            'screen_timeline'  => '3_6m',
            'screen_budget'    => '6k_10k',
            'screen_authority' => 'approval',
            'screen_pain'      => $pain,
            'screen_next_step' => 'compare',
        ]);
    }

    private function screen(Course $course, array $answers)
    {
        return $this->postJson("/course/{$course->id}/screen", array_merge([
            'name'       => 'Applicant',
            'email'      => 'applicant@example.com',
            'screen_ack' => true,
        ], $answers));
    }

    private function lenientCourse(): Course
    {
        return $this->makeHighTicketCourse(['high_ticket_screening' => false]);
    }

    public function test_existing_and_new_courses_default_to_strict(): void
    {
        $this->assertTrue($this->makeHighTicketCourse()->high_ticket_screening);
    }

    public function test_the_sales_page_ships_the_questions_either_way(): void
    {
        foreach ([true, false] as $strict) {
            $course = $this->makeHighTicketCourse(['high_ticket_screening' => $strict]);

            $this->get("/course/{$course->slug}")
                ->assertOk()
                ->assertInertia(fn ($page) => $page->has('screeningQuestions', 7));
        }
    }

    // ── lenient (switch off): only no budget or a total of 4 and under ──

    public function test_lenient_declines_no_budget(): void
    {
        Queue::fake();
        $course = $this->lenientCourse();

        $this->screen($course, $this->strongAnswers(['screen_budget' => 'none']))
            ->assertOk()->assertJson(['passed' => false]);

        $this->assertSame('declined', HighTicketLead::sole()->status);
    }

    public function test_lenient_declines_a_total_of_four(): void
    {
        Queue::fake();
        $course = $this->lenientCourse();

        $this->screen($course, $this->borderlineAnswers('low'))
            ->assertOk()->assertJson(['passed' => false]);

        $this->assertSame(4, HighTicketLead::sole()->screening_score);
    }

    public function test_lenient_passes_a_total_of_five(): void
    {
        $course = $this->lenientCourse();

        $this->screen($course, $this->borderlineAnswers('moderate'))
            ->assertOk()->assertJson(['passed' => true]);

        $lead = HighTicketLead::sole();
        $this->assertSame('pending', $lead->status);
        $this->assertSame(5, $lead->screening_score);
        $this->assertNotNull($lead->screened_at, '寬鬆審核照樣留下答案與分數');
    }

    /** The answer vetoes and the Q6×Q7 combo are strict-only. */
    public function test_lenient_ignores_the_other_vetoes_that_strict_applies(): void
    {
        $cases = [
            'diy'         => $this->strongAnswers(['screen_next_step' => 'diy']),
            'advice_only' => $this->strongAnswers(['screen_next_step' => 'advice_only']),
            'combo'       => $this->strongAnswers([
                'screen_budget'     => 'under_6k',
                'screen_experience' => 'not_started',
                'screen_learning'   => 'passive',
            ]),
        ];

        foreach ($cases as $label => $answers) {
            HighTicketLead::query()->delete();
            $this->screen($this->makeHighTicketCourse(), $answers)
                ->assertOk()->assertJson(['passed' => false], "嚴格：{$label} 應婉拒");

            HighTicketLead::query()->delete();
            $this->screen($this->lenientCourse(), $answers)
                ->assertOk()->assertJson(['passed' => true], "寬鬆：{$label} 應通過");
        }
    }

    public function test_a_lenient_refusal_schedules_the_decline_email(): void
    {
        Queue::fake();
        $course = $this->lenientCourse();

        $this->screen($course, $this->failingAnswers())->assertOk();

        Queue::assertPushed(SendScreeningDeclineMailJob::class);
    }

    public function test_lenient_booking_refuses_no_budget(): void
    {
        Mail::fake();
        $start = $this->makeSlots();
        $course = $this->lenientCourse();

        $this->postJson("/course/{$course->id}/book", $this->bookingPayload($start, $this->strongAnswers(['screen_budget' => 'none'])))
            ->assertStatus(422);
    }

    public function test_lenient_booking_accepts_and_keeps_answers_strict_would_veto(): void
    {
        Mail::fake();
        $start = $this->makeSlots();
        $course = $this->lenientCourse();

        $this->postJson("/course/{$course->id}/book", $this->bookingPayload($start, $this->strongAnswers(['screen_next_step' => 'diy'])))
            ->assertOk();

        $lead = HighTicketLead::sole();
        $this->assertSame('diy', $lead->screen_next_step);
        $this->assertSame(8, $lead->screening_score);
        $this->assertNotNull($lead->screened_at);
    }

    public function test_lenient_waitlist_refuses_no_budget(): void
    {
        Mail::fake();
        $course = $this->lenientCourse();

        $payload = $this->bookingPayload(Carbon::now(), $this->strongAnswers(['screen_budget' => 'none']));
        unset($payload['slot_starts_at']);

        $this->postJson("/course/{$course->id}/waitlist", $payload)->assertStatus(422);
    }

    // ── strict (switch on): unchanged ──

    public function test_strict_booking_with_failing_answers_is_still_refused(): void
    {
        Mail::fake();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/book", $this->bookingPayload($start, $this->strongAnswers(['screen_next_step' => 'diy'])))
            ->assertStatus(422);
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
