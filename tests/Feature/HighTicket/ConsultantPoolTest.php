<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\User;
use App\Services\ConsultationSlotService;
use App\Support\BookingScreening;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 011 US45 / FR-264 — the slot picker routes applicants by their screening
 * answers: strong and funded to the chief (admin), weak and under NT$6,000 to
 * the other consultants, everybody else sees both. Only with more than one
 * consultant on the roster.
 */
class ConsultantPoolTest extends TestCase
{
    use RefreshDatabase;

    private User $chief;

    private User $consultant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chief = User::factory()->create(['role' => 'admin']);
        $this->consultant = User::factory()->create(['is_sales_consultant' => true]);
    }

    private function makeCourse(): Course
    {
        return Course::create([
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
        ])->fresh();
    }

    /** Two units (one 30-minute start) at $time Taipei tomorrow. */
    private function makeSlots(string $time, ?int $consultantId): Carbon
    {
        $at = Carbon::parse("+1 day {$time}", ConsultationSlotService::DISPLAY_TZ)->utc();

        for ($i = 0; $i < 2; $i++) {
            ConsultationSlot::create([
                'starts_at'     => $at->copy()->addMinutes($i * ConsultationSlot::UNIT_MINUTES),
                'consultant_id' => $consultantId,
            ]);
        }

        return $at;
    }

    /** 10/10 with no veto; override single answers from here. */
    private function answers(array $overrides = []): array
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

    /** Exactly 8, budget NT$6,000–9,999. */
    private function chiefAnswers(): array
    {
        return $this->answers(['screen_timeline' => '3_6m', 'screen_budget' => '6k_10k']);
    }

    /** Exactly 7, budget under NT$6,000. */
    private function othersAnswers(): array
    {
        return $this->answers(['screen_timeline' => '3_6m', 'screen_budget' => 'under_6k']);
    }

    private function labels(Course $course, array $answers): array
    {
        $response = $this->getJson("/course/{$course->id}/booking-slots?" . http_build_query($answers))->assertOk();

        return collect($response->json('slots'))->flatMap(fn ($g) => array_column($g['times'], 'label'))->all();
    }

    private function bookingPayload(Carbon $start, array $answers): array
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
        ], $answers);
    }

    // ---- the rule --------------------------------------------------------

    public function test_pool_boundaries(): void
    {
        $this->assertSame(8, BookingScreening::score($this->chiefAnswers()));
        $this->assertSame('chief', BookingScreening::consultantPool($this->chiefAnswers()));

        $this->assertSame('chief', BookingScreening::consultantPool($this->answers(['screen_budget' => 'over_100k'])));

        $sevenFunded = $this->answers(['screen_timeline' => '3_6m', 'screen_budget' => 'over_100k', 'screen_pain' => 'moderate', 'screen_authority' => 'approval']);
        $this->assertSame(7, BookingScreening::score($sevenFunded));
        $this->assertSame('all', BookingScreening::consultantPool($sevenFunded));

        $eightUnfunded = $this->answers(['screen_budget' => 'under_6k']);
        $this->assertSame(8, BookingScreening::score($eightUnfunded));
        $this->assertSame('all', BookingScreening::consultantPool($eightUnfunded));

        $this->assertSame(7, BookingScreening::score($this->othersAnswers()));
        $this->assertSame('others', BookingScreening::consultantPool($this->othersAnswers()));

        $partial = $this->chiefAnswers();
        unset($partial['screen_learning']);
        $this->assertSame('all', BookingScreening::consultantPool($partial));
    }

    // ---- the picker ------------------------------------------------------

    public function test_strong_funded_applicant_sees_only_chief_and_unassigned_slots(): void
    {
        $course = $this->makeCourse();
        $this->makeSlots('10:00', $this->chief->id);
        $this->makeSlots('14:00', $this->consultant->id);
        $this->makeSlots('16:00', null);

        $this->assertSame(['10:00', '16:00'], $this->labels($course, $this->chiefAnswers()));
    }

    public function test_weak_unfunded_applicant_sees_only_other_consultants(): void
    {
        $course = $this->makeCourse();
        $this->makeSlots('10:00', $this->chief->id);
        $this->makeSlots('14:00', $this->consultant->id);
        $this->makeSlots('16:00', null);

        $this->assertSame(['14:00'], $this->labels($course, $this->othersAnswers()));
    }

    public function test_mixed_or_missing_answers_see_everything(): void
    {
        $course = $this->makeCourse();
        $this->makeSlots('10:00', $this->chief->id);
        $this->makeSlots('14:00', $this->consultant->id);

        $this->assertSame(['10:00', '14:00'], $this->labels($course, $this->answers(['screen_budget' => 'under_6k'])));
        $this->assertSame(['10:00', '14:00'], $this->labels($course, []));
    }

    public function test_single_consultant_roster_never_routes(): void
    {
        $this->consultant->update(['is_sales_consultant' => false]);

        $course = $this->makeCourse();
        $this->makeSlots('10:00', $this->chief->id);
        $this->makeSlots('14:00', $this->consultant->id);

        $this->assertSame(['10:00', '14:00'], $this->labels($course, $this->chiefAnswers()));
        $this->assertSame(['10:00', '14:00'], $this->labels($course, $this->othersAnswers()));
    }

    // ---- empty pool → waitlist ------------------------------------------

    public function test_empty_pool_shows_no_slots_and_allows_the_waitlist(): void
    {
        $course = $this->makeCourse();
        $this->makeSlots('14:00', $this->consultant->id);

        $this->assertSame([], $this->labels($course, $this->chiefAnswers()));

        $payload = $this->bookingPayload(now(), $this->chiefAnswers());
        unset($payload['slot_starts_at']);

        $this->postJson("/course/{$course->id}/waitlist", $payload)
            ->assertOk()
            ->assertJsonPath('waitlisted', true);
    }

    public function test_waitlist_is_still_refused_when_the_pool_has_slots(): void
    {
        $course = $this->makeCourse();
        $this->makeSlots('10:00', $this->chief->id);

        $payload = $this->bookingPayload(now(), $this->chiefAnswers());
        unset($payload['slot_starts_at']);

        $this->postJson("/course/{$course->id}/waitlist", $payload)->assertStatus(422);
    }

    // ---- server-side re-check -------------------------------------------

    public function test_booking_a_slot_outside_the_pool_is_refused(): void
    {
        Mail::fake();

        $course = $this->makeCourse();
        $this->makeSlots('10:00', $this->chief->id);
        $other = $this->makeSlots('14:00', $this->consultant->id);

        $this->postJson("/course/{$course->id}/book", $this->bookingPayload($other, $this->chiefAnswers()))
            ->assertStatus(409);

        $this->assertSame(0, ConsultationSlot::whereNotNull('lead_id')->count());
    }

    public function test_booking_a_slot_inside_the_pool_holds_it(): void
    {
        Mail::fake();

        $course = $this->makeCourse();
        $chief = $this->makeSlots('10:00', $this->chief->id);
        $this->makeSlots('14:00', $this->consultant->id);

        $this->postJson("/course/{$course->id}/book", $this->bookingPayload($chief, $this->chiefAnswers()))
            ->assertOk();

        $this->assertSame(2, ConsultationSlot::where('consultant_id', $this->chief->id)->whereNotNull('lead_id')->count());
    }

    // ---- other callers untouched ----------------------------------------

    public function test_default_available_starts_ignores_pools(): void
    {
        $this->makeSlots('10:00', $this->chief->id);
        $this->makeSlots('14:00', $this->consultant->id);

        $this->assertCount(2, app(ConsultationSlotService::class)->availableStarts(30));
    }
}
