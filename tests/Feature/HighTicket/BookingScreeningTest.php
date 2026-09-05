<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\DripConversionTarget;
use App\Models\DripSubscription;
use App\Models\HighTicketLead;
use App\Models\User;
use App\Services\ConsultationSlotService;
use App\Services\HighTicketBookingService;
use App\Support\BookingScreening;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 011 US24 — the five-question gate in front of the wizard.
 *
 * The gate is deliberately soft (D97): what these tests protect is that it is
 * consistent, that a refusal costs the applicant nothing but the answer, and
 * that being turned away never touches the nurture sequence they came from.
 */
class BookingScreeningTest extends TestCase
{
    use RefreshDatabase;

    private function makeHighTicketCourse(): Course
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

    /** A full-marks set of answers (10/10). */
    private function answers(array $overrides = []): array
    {
        return array_merge([
            'screen_timeline'  => 'immediate',
            'screen_budget'    => '10k_50k',
            'screen_authority' => 'self',
            'screen_pain'      => 'severe',
            'screen_next_step' => 'start_now',
        ], $overrides);
    }

    /** Identity, answers and the scope acknowledgement — what step 1 posts. */
    private function screeningPayload(array $overrides = []): array
    {
        return array_merge([
            'name'       => 'Applicant',
            'email'      => 'applicant@example.com',
            'screen_ack' => true,
        ], $this->answers(), $overrides);
    }

    private function service(): HighTicketBookingService
    {
        return app(HighTicketBookingService::class);
    }

    // ── 計分表（FR-123） ───────────────────────────────────────────────────

    public function test_every_option_scores_what_the_table_says(): void
    {
        $expected = [
            'screen_timeline'  => ['immediate' => 2, '1_3m' => 2, '3_6m' => 1, '6m_plus' => 0, 'exploring' => 0],
            'screen_budget'    => ['over_100k' => 2, '50k_100k' => 2, '10k_50k' => 2, '6k_10k' => 1, 'under_6k' => 0, 'none' => 0, 'unsure' => 1],
            'screen_authority' => ['self' => 2, 'discuss' => 2, 'approval' => 1, 'none' => 0, 'not_considered' => 0],
            'screen_pain'      => ['severe' => 2, 'high' => 2, 'moderate' => 1, 'low' => 0, 'curious' => 0],
            'screen_next_step' => ['start_now' => 2, 'evaluate' => 2, 'compare' => 1, 'diy' => 0, 'advice_only' => 0],
        ];

        foreach ($expected as $field => $options) {
            foreach ($options as $value => $score) {
                $this->assertSame(
                    $score,
                    BookingScreening::QUESTIONS[$field]['options'][$value]['score'],
                    "{$field}.{$value} 的分數與 FR-123 的表不符"
                );
            }

            $this->assertCount(count($options), BookingScreening::QUESTIONS[$field]['options'], "{$field} 的選項數量與 FR-123 不符");
        }

        $this->assertSame(10, BookingScreening::score($this->answers()), '五題滿分應為 10');
    }

    public function test_the_pass_mark_is_five(): void
    {
        // 1 + 1 + 1 + 1 + 0 = 4
        $four = $this->answers([
            'screen_timeline'  => '3_6m',
            'screen_budget'    => '6k_10k',
            'screen_authority' => 'approval',
            'screen_pain'      => 'moderate',
            'screen_next_step' => 'diy',
        ]);

        $this->assertSame(4, BookingScreening::score($four));
        $this->assertFalse(BookingScreening::passes($four));

        $five = array_merge($four, ['screen_next_step' => 'compare']);

        $this->assertSame(5, BookingScreening::score($five));
        $this->assertTrue(BookingScreening::passes($five), '5 分是通過門檻，不是未達');
    }

    /** 「目前沒有預算」 stops an otherwise perfect application (使用者決策). */
    public function test_no_budget_vetoes_whatever_the_total(): void
    {
        $vetoed = $this->answers(['screen_budget' => 'none']);

        $this->assertSame(8, BookingScreening::score($vetoed), '其餘四題仍是滿分');
        $this->assertTrue(BookingScreening::vetoed($vetoed));
        $this->assertFalse(BookingScreening::passes($vetoed));
    }

    // ── 「不確定」的分數上限（FR-172） ────────────────────────────────────

    /** An unnamed budget scores, but it cannot reach 高購買意願 (使用者決策). */
    public function test_an_unsure_budget_caps_the_total_at_six(): void
    {
        // 2 + 1 + 2 + 2 + 2 = 9 before the ceiling.
        $unsure = $this->answers(['screen_budget' => 'unsure']);

        $this->assertSame(6, BookingScreening::score($unsure));
        $this->assertSame('warm', BookingScreening::tier(BookingScreening::score($unsure)));
    }

    /** The cap is a ceiling, not a score — a weak application keeps its own total. */
    public function test_the_cap_never_raises_a_lower_total(): void
    {
        $weak = $this->answers([
            'screen_budget'    => 'unsure',
            'screen_timeline'  => '3_6m',
            'screen_authority' => 'approval',
            'screen_pain'      => 'moderate',
            'screen_next_step' => 'compare',
        ]);

        $this->assertSame(5, BookingScreening::score($weak));
        $this->assertTrue(BookingScreening::passes($weak), '上限不是否決 —— 及格照樣及格');
    }

    /** Naming a budget is what the ceiling is asking for; the 10 is still reachable. */
    public function test_a_named_budget_is_not_capped(): void
    {
        $this->assertSame(10, BookingScreening::score($this->answers(['screen_budget' => '10k_50k'])));
        $this->assertSame('hot', BookingScreening::tier(10));
    }

    /** The rubric must not travel to the browser (FR-124 / D101). */
    public function test_the_questions_sent_to_the_front_carry_no_scores(): void
    {
        $encoded = json_encode(BookingScreening::questionsForFront(), JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsString('score', $encoded);
        $this->assertStringNotContainsString('veto', $encoded);
        $this->assertStringNotContainsString('cap', $encoded);
        $this->assertCount(5, BookingScreening::questionsForFront());
        $this->assertStringContainsString('目前沒有預算', $encoded, '選項文字仍要送出去讓前台渲染');
    }

    // ── 審核端點（FR-125 / FR-126） ─────────────────────────────────────────

    public function test_passing_records_the_answers_and_lets_the_applicant_through(): void
    {
        Mail::fake();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload())
            ->assertOk()
            ->assertJson(['passed' => true])
            ->assertJsonMissing(['score' => 10]);

        $lead = HighTicketLead::first();
        $this->assertSame('pending', $lead->status);
        $this->assertSame(10, $lead->screening_score);
        $this->assertSame('10k_50k', $lead->screen_budget);
        $this->assertNotNull($lead->screened_at);
        $this->assertNull($lead->declined_at);
        $this->assertNull($lead->confirm_token, '審核通過還不是預約');

        Mail::assertNothingSent();
    }

    public function test_the_response_never_carries_the_score(): void
    {
        Mail::fake();
        $course = $this->makeHighTicketCourse();

        $json = $this->postJson("/course/{$course->id}/screen", $this->screeningPayload())->json();

        $this->assertSame(['passed' => true], $json, '回應只有通過與否，分數是給顧問看的');
    }

    public function test_failing_declines_without_sending_anything_or_taking_a_slot(): void
    {
        Mail::fake();
        $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload([
            'screen_timeline'  => 'exploring',
            'screen_budget'    => 'none',
            'screen_authority' => 'not_considered',
            'screen_pain'      => 'curious',
            'screen_next_step' => 'advice_only',
        ]))->assertOk()->assertJson(['passed' => false]);

        $lead = HighTicketLead::first();
        $this->assertSame('declined', $lead->status);
        $this->assertNotNull($lead->declined_at);
        $this->assertSame(0, $lead->screening_score);
        $this->assertNull($lead->confirm_token, '沒有信要寄，token 只會是死連結');
        $this->assertSame(0, $lead->slots()->count());
        $this->assertSame(0, ConsultationSlot::whereNotNull('lead_id')->count());

        Mail::assertNothingSent();
    }

    /**
     * FR-126 — these people came from the ebook sequence and are staying in it.
     * Declining means "not a 1v1 right now", not "stop talking to them".
     */
    public function test_declining_does_not_stop_the_drip_sequence(): void
    {
        Mail::fake();
        $target = $this->makeHighTicketCourse();

        $drip = Course::create([
            'name' => 'Drip', 'slug' => 'drip-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 0, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'drip', 'drip_interval_days' => 3,
            'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);
        DripConversionTarget::create(['drip_course_id' => $drip->id, 'target_course_id' => $target->id]);

        $user = User::create(['email' => 'applicant@example.com', 'role' => 'member']);
        $sub = DripSubscription::create([
            'user_id'       => $user->id,
            'course_id'     => $drip->id,
            'subscribed_at' => now()->subDays(6),
            'emails_sent'   => 2,
            'status'        => 'active',
        ]);

        $this->postJson("/course/{$target->id}/screen", $this->screeningPayload([
            'screen_budget' => 'none',
        ]))->assertOk()->assertJson(['passed' => false]);

        $this->assertSame('active', $sub->fresh()->status, '被婉拒的人要留在加溫名單裡');
    }

    public function test_all_five_answers_are_required(): void
    {
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload(['screen_pain' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['screen_pain']);

        $this->assertSame(0, HighTicketLead::count());
    }

    public function test_an_unknown_option_is_rejected(): void
    {
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload(['screen_budget' => 'made_up']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['screen_budget']);
    }

    // ── 翻案與既有預約（FR-125 / D97） ──────────────────────────────────────

    public function test_a_declined_applicant_can_screen_again_with_different_answers(): void
    {
        Mail::fake();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload(['screen_budget' => 'none']))
            ->assertJson(['passed' => false]);

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload())
            ->assertJson(['passed' => true]);

        $this->assertSame(1, HighTicketLead::count(), '同 email 同課程仍是一筆 lead');

        $lead = HighTicketLead::first();
        $this->assertSame('pending', $lead->status);
        $this->assertNull($lead->declined_at);
        $this->assertSame(10, $lead->screening_score);
    }

    /** A customer who already booked must not screen themselves out of it. */
    public function test_screening_never_downgrades_a_confirmed_booking(): void
    {
        Mail::fake();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->service()->apply($course, [
            'name'           => 'Applicant',
            'email'          => 'applicant@example.com',
            'phone'          => '0912345678',
            'occupation'     => '設計師',
            'bottleneck'     => '收入不穩',
            'expertise'      => '設計',
            'commitments'    => [true, true, true],
            'slot_starts_at' => $start->toIso8601String(),
        ]);
        $this->service()->confirm(HighTicketLead::first()->confirm_token);

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload(['screen_budget' => 'none']))
            ->assertOk();

        $lead = HighTicketLead::first()->fresh();
        $this->assertNotNull($lead->confirmed_at);
        $this->assertNotSame('declined', $lead->status, '已確認的預約不得被自己的重測洗掉');
        $this->assertNull($lead->declined_at);
        $this->assertSame('none', $lead->screen_budget, '答案還是要記下來');
    }

    /** An admin-managed lead is somebody's work in progress. */
    public function test_screening_does_not_overwrite_an_admin_set_status(): void
    {
        Mail::fake();
        $course = $this->makeHighTicketCourse();

        HighTicketLead::create([
            'name' => 'Applicant', 'email' => 'applicant@example.com', 'course_id' => $course->id,
            'status' => 'contacted', 'booked_at' => now(),
        ]);

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload(['screen_budget' => 'none']))
            ->assertOk();

        $this->assertSame('contacted', HighTicketLead::first()->status);
    }

    // ── 送出時重新計分（FR-129） ────────────────────────────────────────────

    // ── 服務範圍告知（FR-162…FR-165） ──────────────────────────────────────

    public function test_the_scope_acknowledgement_is_required(): void
    {
        $course = $this->makeHighTicketCourse();

        $payload = $this->screeningPayload();
        unset($payload['screen_ack']);

        $this->postJson("/course/{$course->id}/screen", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('screen_ack');

        // Nothing is recorded for somebody who never got past the gate.
        $this->assertSame(0, HighTicketLead::count());
    }

    public function test_an_unticked_acknowledgement_is_refused(): void
    {
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload(['screen_ack' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('screen_ack');
    }

    /** FR-163: a gate, not a consent record — it must not reach the database. */
    public function test_the_acknowledgement_is_not_stored(): void
    {
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload())->assertOk();

        $this->assertFalse(
            \Illuminate\Support\Facades\Schema::hasColumn('high_ticket_leads', 'screen_ack'),
            '服務範圍告知是閘門不是同意書，不落庫（FR-163）',
        );
    }

    /**
     * FR-164: the acknowledgement belongs to step 1 alone. A resumed draft and
     * every pre-feature lead reaches the submit without ever having seen it, so
     * requiring it there would lock them out at the last step.
     */
    public function test_submitting_does_not_require_the_acknowledgement(): void
    {
        Mail::fake();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/book", array_merge([
            'name'           => 'Applicant',
            'email'          => 'applicant@example.com',
            'phone'          => '0912345678',
            'occupation'     => '設計師',
            'bottleneck'     => '收入不穩',
            'expertise'      => '設計',
            'commitments'    => [true, true, true],
            'slot_starts_at' => $start->toIso8601String(),
        ], $this->answers()))->assertOk();
    }

    /**
     * Screening with one address and submitting with another would otherwise
     * walk straight past the gate, since the lead being checked is a different row.
     */
    public function test_submitting_with_failing_answers_is_refused(): void
    {
        Mail::fake();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/book", array_merge([
            'name'           => 'Applicant',
            'email'          => 'other@example.com',
            'phone'          => '0912345678',
            'occupation'     => '設計師',
            'bottleneck'     => '收入不穩',
            'expertise'      => '設計',
            'commitments'    => [true, true, true],
            'slot_starts_at' => $start->toIso8601String(),
        ], $this->answers(['screen_budget' => 'none'])))->assertStatus(422);

        $this->assertSame(0, ConsultationSlot::whereNotNull('lead_id')->count());
        Mail::assertNothingSent();
    }

    /** A resumed draft and every pre-feature lead carries no answers at all. */
    public function test_submitting_without_answers_is_allowed(): void
    {
        Mail::fake();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/book", [
            'name'           => 'Applicant',
            'email'          => 'applicant@example.com',
            'phone'          => '0912345678',
            'occupation'     => '設計師',
            'bottleneck'     => '收入不穩',
            'expertise'      => '設計',
            'commitments'    => [true, true, true],
            'slot_starts_at' => $start->toIso8601String(),
        ])->assertOk();
    }

    public function test_a_passing_application_keeps_its_answers_through_the_booking(): void
    {
        Mail::fake();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload())->assertOk();

        $this->postJson("/course/{$course->id}/book", array_merge([
            'name'           => 'Applicant',
            'email'          => 'applicant@example.com',
            'phone'          => '0912345678',
            'occupation'     => '設計師',
            'bottleneck'     => '收入不穩',
            'expertise'      => '設計',
            'commitments'    => [true, true, true],
            'slot_starts_at' => $start->toIso8601String(),
        ], $this->answers()))->assertOk();

        $lead = HighTicketLead::first();
        $this->assertSame(1, HighTicketLead::count());
        $this->assertSame(10, $lead->screening_score);
        $this->assertNotNull($lead->confirm_token);
        $this->assertSame(2, $lead->slots()->count());
    }

    // ── 後台（FR-127 / FR-131） ────────────────────────────────────────────

    /**
     * The status list is written out in four places (FR-127); this is the one
     * that fails silently — the admin clicks a status the validator does not
     * know and gets a 422 that mentions nothing useful.
     */
    public function test_an_admin_can_move_a_lead_in_and_out_of_declined(): void
    {
        Mail::fake();
        $course = $this->makeHighTicketCourse();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload(['screen_budget' => 'none']))->assertOk();
        $lead = HighTicketLead::first();
        $this->assertSame('declined', $lead->status);

        $this->actingAs($admin)
            ->patchJson("/admin/high-ticket-leads/{$lead->id}/status", ['status' => 'pending'])
            ->assertOk();
        $this->assertSame('pending', $lead->fresh()->status);

        $this->actingAs($admin)
            ->patchJson("/admin/high-ticket-leads/{$lead->id}/status", ['status' => 'declined'])
            ->assertOk();
        $this->assertSame('declined', $lead->fresh()->status);
    }

    /** The admin panel reads labels, not keys — resolved on the model (FR-131). */
    public function test_the_lead_payload_carries_readable_answers_and_a_tier(): void
    {
        Mail::fake();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload())->assertOk();

        $lead = HighTicketLead::first()->toArray();

        $this->assertSame('hot', $lead['screening_tier']);
        $this->assertCount(5, $lead['screening_answers']);
        $this->assertSame('NT$10,000–49,999', $lead['screening_answers'][1]['answer']);
    }

    public function test_a_lead_with_no_screening_carries_no_answers(): void
    {
        $course = $this->makeHighTicketCourse();

        $lead = HighTicketLead::create([
            'name' => 'Old', 'email' => 'old@example.com', 'course_id' => $course->id,
            'status' => 'pending', 'booked_at' => now(),
        ])->toArray();

        $this->assertSame([], $lead['screening_answers']);
        $this->assertSame('unknown', $lead['screening_tier']);
    }

    // ── 清掃（FR-126） ─────────────────────────────────────────────────────

    public function test_the_purge_leaves_screened_and_declined_leads_alone(): void
    {
        Mail::fake();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload())->assertOk();
        $this->postJson("/course/{$course->id}/screen", $this->screeningPayload([
            'email'         => 'broke@example.com',
            'screen_budget' => 'none',
        ]))->assertOk();

        $this->travel(2)->hours();

        $this->service()->purgeExpiredApplications();

        $this->assertSame(2, HighTicketLead::count(), '只完成審核的人沒有待確認的信，不該被清掉');
    }
}
