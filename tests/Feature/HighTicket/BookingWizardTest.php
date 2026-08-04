<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\DripConversionTarget;
use App\Models\DripSubscription;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\User;
use App\Services\ConsultationSlotService;
use App\Services\HighTicketBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 011 US9/US11 — the four-step application and the two-stage booking.
 *
 * The point of this story is that submitting the form is no longer the same
 * thing as having a booking: the questionnaire has to be complete, all five
 * commitments ticked, and the emailed link clicked before the slot is really
 * the applicant's.
 */
class BookingWizardTest extends TestCase
{
    use RefreshDatabase;

    private function makeHighTicketCourse(): Course
    {
        $course = Course::create([
            'name'                   => 'HT Course',
            'slug'                   => 'ht-' . uniqid(),
            'tagline'                => 'tag',
            'description'            => 'desc',
            'price'                  => 50000,
            'instructor_name'        => 'Tester',
            'type'                   => 'high_ticket',
            'status'                 => 'selling',
            'course_type'            => 'standard',
            'is_published'           => true,
            'is_visible'             => true,
            'payment_gateway'        => 'payuni',
            'high_ticket_hide_price' => true,
        ]);

        return $course->fresh();
    }

    private function templates(bool $verify = true, bool $confirmation = true): void
    {
        if ($verify) {
            EmailTemplate::create([
                'name'       => '預約待確認',
                'event_type' => 'high_ticket_booking_verify',
                'subject'    => '請確認你的 {{course_name}} 預約',
                'body_md'    => 'Hi {{user_name}}，請點 {{confirm_url}}，時段 {{slot_time}}，期限 {{expires_at}}',
            ]);
        }

        if ($confirmation) {
            EmailTemplate::create([
                'name'       => '預約確認',
                'event_type' => 'high_ticket_booking_confirmation',
                'subject'    => '{{course_name}} 預約確認',
                'body_md'    => 'Hi {{user_name}}，時段 {{slot_time}}，連結 {{zoom_join_url}}',
            ]);
        }
    }

    /** Four consecutive units tomorrow morning. */
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'        => 'Applicant',
            'email'       => 'applicant@example.com',
            'phone'       => '0912345678',
            'occupation'  => '設計師，做了 6 年',
            'bottleneck'  => '接案收入不穩定',
            'expertise'   => '品牌識別設計',
            'social_url'  => 'https://instagram.com/someone',
            'commitments' => [true, true, true, true, true],
        ], $overrides);
    }

    private function service(): HighTicketBookingService
    {
        return app(HighTicketBookingService::class);
    }

    // ---- US9: the application itself -------------------------------------

    public function test_application_stores_the_questionnaire_and_holds_the_slot(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();

        $result = $this->service()->apply(
            $this->makeHighTicketCourse(),
            $this->payload(['slot_starts_at' => $start->toIso8601String()])
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($result['mail_sent']);

        $lead = HighTicketLead::first();
        $this->assertSame('0912345678', $lead->phone);
        $this->assertSame('接案收入不穩定', $lead->bottleneck);
        $this->assertSame('品牌識別設計', $lead->expertise);
        $this->assertNotNull($lead->commitments_accepted_at);
        $this->assertNotNull($lead->confirm_token);
        $this->assertNull($lead->confirmed_at, '送出申請不等於完成預約');

        // 30 minutes = 2 units, held for an hour.
        $this->assertSame(2, $lead->slots()->count());
        $this->assertNotNull($lead->slots()->first()->held_until);
    }

    public function test_http_endpoint_rejects_incomplete_questionnaire(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $response = $this->postJson("/course/{$course->id}/book", $this->payload([
            'slot_starts_at' => $start->toIso8601String(),
            'bottleneck'     => '',
            'phone'          => '',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors(['phone', 'bottleneck']);
        $this->assertSame(0, HighTicketLead::count());
    }

    public function test_http_endpoint_rejects_unticked_commitments(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $response = $this->postJson("/course/{$course->id}/book", $this->payload([
            'slot_starts_at' => $start->toIso8601String(),
            'commitments'    => [true, true, false, true, true],
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors(['commitments']);
        $this->assertSame(0, HighTicketLead::count());
    }

    public function test_social_url_is_optional_but_must_be_a_url(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/book", $this->payload([
            'slot_starts_at' => $start->toIso8601String(),
            'social_url'     => 'not a url',
        ]))->assertStatus(422)->assertJsonValidationErrors(['social_url']);

        $this->postJson("/course/{$course->id}/book", $this->payload([
            'slot_starts_at' => $start->toIso8601String(),
            'social_url'     => null,
        ]))->assertOk();
    }

    public function test_missing_verify_template_blocks_the_whole_application(): void
    {
        Mail::fake();
        $this->templates(verify: false);
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/book", $this->payload([
            'slot_starts_at' => $start->toIso8601String(),
        ]))->assertStatus(422);

        $this->assertSame(0, HighTicketLead::count());
        // Nothing may be held when the application never happened.
        $this->assertSame(0, ConsultationSlot::whereNotNull('lead_id')->count());
    }

    public function test_taken_slot_returns_409(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots(2);
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/book", $this->payload([
            'slot_starts_at' => $start->toIso8601String(),
        ]))->assertOk();

        $this->postJson("/course/{$course->id}/book", $this->payload([
            'email'          => 'second@example.com',
            'slot_starts_at' => $start->toIso8601String(),
        ]))->assertStatus(409);
    }

    public function test_verify_mail_failure_keeps_the_lead_but_frees_the_slot(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp is down'));
        $this->templates();
        $start = $this->makeSlots();

        $result = $this->service()->apply(
            $this->makeHighTicketCourse(),
            $this->payload(['slot_starts_at' => $start->toIso8601String()])
        );

        $this->assertTrue($result['success']);
        $this->assertFalse($result['mail_sent']);
        $this->assertSame(1, HighTicketLead::count());
        // Nobody can ever confirm a mail that never arrived — don't hold the slot (D34).
        $this->assertSame(0, ConsultationSlot::whereNotNull('lead_id')->count());
    }

    public function test_bonus_code_takes_three_units(): void
    {
        Mail::fake();
        $this->templates();
        \App\Models\SiteSetting::set(ConsultationSlotService::BONUS_CODES_KEY, 'VIP2026');
        $start = $this->makeSlots();

        $this->service()->apply(
            $this->makeHighTicketCourse(),
            $this->payload(['slot_starts_at' => $start->toIso8601String(), 'code' => 'vip2026'])
        );

        $lead = HighTicketLead::first();
        $this->assertSame(3, $lead->slots()->count());
        $this->assertSame('vip2026', $lead->booking_code);
    }

    public function test_slots_endpoint_reflects_the_bonus_code(): void
    {
        \App\Models\SiteSetting::set(ConsultationSlotService::BONUS_CODES_KEY, 'VIP2026');
        $this->makeSlots(3);
        $course = $this->makeHighTicketCourse();

        // Slots come back grouped by day: one day here, so one group.
        $plain = $this->getJson("/course/{$course->id}/booking-slots");
        $plain->assertOk()->assertJsonPath('minutes', 30);
        $this->assertCount(2, $plain->json('slots.0.times'), '3 個單位可排出 2 個 30 分鐘的起始時間');

        $bonus = $this->getJson("/course/{$course->id}/booking-slots?code=VIP2026");
        $bonus->assertOk()->assertJsonPath('minutes', 45)->assertJsonPath('code_applied', true);
        $this->assertCount(1, $bonus->json('slots.0.times'), '45 分鐘要 3 個連續單位，只排得出 1 個');

        $bad = $this->getJson("/course/{$course->id}/booking-slots?code=nope");
        $bad->assertOk()->assertJsonPath('minutes', 30)->assertJsonPath('code_applied', false);
    }

    // ---- US11: confirmation ----------------------------------------------

    public function test_confirmation_finalises_the_slot_and_sends_the_confirmation_mail(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();

        $this->service()->apply(
            $this->makeHighTicketCourse(),
            $this->payload(['slot_starts_at' => $start->toIso8601String()])
        );
        $lead = HighTicketLead::first();

        $this->get("/booking/confirm/{$lead->confirm_token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Booking/Confirm')->where('state', 'confirmed'));

        $lead->refresh();
        $this->assertNotNull($lead->confirmed_at);
        $this->assertNull($lead->slots()->first()->held_until, '確認後時段不再是暫留');

        Mail::assertSent(\App\Mail\TemplatedMail::class, fn ($mail) => str_contains($mail->emailSubject, '預約確認'));
    }

    public function test_confirmation_mail_is_not_sent_before_confirming(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();

        $this->service()->apply(
            $this->makeHighTicketCourse(),
            $this->payload(['slot_starts_at' => $start->toIso8601String()])
        );

        // Only the verify mail so far (FR-033).
        Mail::assertSent(\App\Mail\TemplatedMail::class, 1);
        Mail::assertSent(\App\Mail\TemplatedMail::class, fn ($mail) => str_contains($mail->emailSubject, '請確認'));
    }

    public function test_confirming_twice_is_idempotent(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();

        $this->service()->apply(
            $this->makeHighTicketCourse(),
            $this->payload(['slot_starts_at' => $start->toIso8601String()])
        );
        $token = HighTicketLead::first()->confirm_token;

        $this->get("/booking/confirm/{$token}")->assertOk();
        $mailsAfterFirst = count(Mail::sent(\App\Mail\TemplatedMail::class));

        $this->get("/booking/confirm/{$token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('state', 'already'));

        $this->assertCount($mailsAfterFirst, Mail::sent(\App\Mail\TemplatedMail::class), '重複確認不得再寄一封');
    }

    public function test_expired_token_reports_expired_and_the_slot_is_free(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();

        $this->service()->apply(
            $this->makeHighTicketCourse(),
            $this->payload(['slot_starts_at' => $start->toIso8601String()])
        );
        $token = HighTicketLead::first()->confirm_token;

        $this->travel(61)->minutes();

        $this->get("/booking/confirm/{$token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('state', 'expired'));

        $this->assertNull(HighTicketLead::first()->confirmed_at);
        // Lazy release: available again without any command running.
        $this->assertCount(3, app(ConsultationSlotService::class)->availableStarts(30), '4 個單位全數釋出 = 3 個起始時間');
    }

    public function test_unknown_token_reports_invalid(): void
    {
        $this->get('/booking/confirm/' . str_repeat('x', 64))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('state', 'invalid'));
    }

    public function test_drip_stop_happens_on_confirmation_not_on_submit(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();
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

        $this->service()->apply($target, $this->payload(['slot_starts_at' => $start->toIso8601String()]));

        $this->assertSame('active', $sub->fresh()->status, '尚未確認前不得停掉序列信');

        $this->get('/booking/confirm/' . HighTicketLead::first()->confirm_token);

        $this->assertSame('booked', $sub->fresh()->status);
    }

    public function test_reapplying_moves_the_hold_and_keeps_one_lead(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->service()->apply($course, $this->payload(['slot_starts_at' => $start->toIso8601String()]));
        $firstToken = HighTicketLead::first()->confirm_token;

        $later = $start->copy()->addMinutes(30);
        $this->service()->apply($course, $this->payload(['slot_starts_at' => $later->toIso8601String()]));

        $this->assertSame(1, HighTicketLead::count(), '同 email 同課程仍是一筆 lead');
        $lead = HighTicketLead::first();
        $this->assertSame(2, $lead->slots()->count(), '不得同時卡住兩組時段');
        $this->assertNotSame($firstToken, $lead->confirm_token, '重新申請要換一組 token');
    }

    public function test_waitlist_keeps_the_application_when_no_slots_exist(): void
    {
        Mail::fake();
        $this->templates();
        $course = $this->makeHighTicketCourse();
        // Deliberately no slots created.

        $this->postJson("/course/{$course->id}/waitlist", $this->payload())
            ->assertOk()
            ->assertJsonPath('waitlisted', true);

        $lead = HighTicketLead::first();
        $this->assertSame('pending', $lead->status, '等管理員用「通知新時段」跟進');
        $this->assertSame('0912345678', $lead->phone);
        $this->assertNull($lead->confirm_token, '沒有時段可確認，token 只會是死連結');
        $this->assertSame(0, $lead->slots()->count());
    }

    public function test_waitlist_is_refused_when_slots_are_actually_available(): void
    {
        Mail::fake();
        $this->templates();
        $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        // Otherwise it becomes a way to skip the picker entirely.
        $this->postJson("/course/{$course->id}/waitlist", $this->payload())
            ->assertStatus(422);

        $this->assertSame(0, HighTicketLead::count());
    }

    public function test_sales_page_prefills_a_previous_application_for_its_owner(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->service()->apply($course, $this->payload([
            'email'          => 'member@example.com',
            'slot_starts_at' => $start->toIso8601String(),
        ]));

        $user = User::create(['email' => 'member@example.com', 'role' => 'member']);

        $this->actingAs($user)
            ->get("/course/{$course->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('bookingDraft.phone', '0912345678'));
    }

    public function test_sales_page_does_not_leak_an_application_to_a_guest(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->service()->apply($course, $this->payload([
            'email'          => 'member@example.com',
            'slot_starts_at' => $start->toIso8601String(),
        ]));

        $this->get("/course/{$course->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('bookingDraft', null));
    }

    /**
     * 011 US9 — the questionnaire is the only place we ask for a phone number,
     * so the member account created from a lead inherits it.
     */
    public function test_converting_a_lead_carries_the_phone_onto_a_new_member(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $this->service()->apply($course, $this->payload(['slot_starts_at' => $start->toIso8601String()]));
        $lead = HighTicketLead::first();

        app(\App\Services\HighTicketLeadService::class)->convertLead($lead, $course->id, 30000);

        $this->assertSame('0912345678', User::where('email', 'applicant@example.com')->first()->phone);
    }

    /** An existing member's own data must not be rewritten by a conversion. */
    public function test_converting_a_lead_does_not_overwrite_an_existing_member_phone(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        User::create(['email' => 'applicant@example.com', 'role' => 'member', 'phone' => '0900000000']);

        $this->service()->apply($course, $this->payload(['slot_starts_at' => $start->toIso8601String()]));
        app(\App\Services\HighTicketLeadService::class)->convertLead(HighTicketLead::first(), $course->id, 30000);

        $this->assertSame('0900000000', User::where('email', 'applicant@example.com')->first()->phone);
    }

    /** A 30-character number has to survive the trip to the member record. */
    public function test_a_long_phone_number_is_not_truncated(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();
        $long = '+886 912-345-678 ext.1234';

        $this->service()->apply($course, $this->payload([
            'phone'          => $long,
            'slot_starts_at' => $start->toIso8601String(),
        ]));
        app(\App\Services\HighTicketLeadService::class)->convertLead(HighTicketLead::first(), $course->id, 0);

        $this->assertSame($long, HighTicketLead::first()->phone);
        $this->assertSame($long, User::where('email', 'applicant@example.com')->first()->phone);
    }

    /** The other lead→member path (加入序列信) carries the phone too. */
    public function test_subscribing_a_lead_to_drip_carries_the_phone(): void
    {
        Mail::fake();
        $this->templates();
        $start = $this->makeSlots();
        $course = $this->makeHighTicketCourse();

        $drip = Course::create([
            'name' => 'Drip', 'slug' => 'drip-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 0, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'drip', 'drip_interval_days' => 3,
            'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);

        $this->service()->apply($course, $this->payload(['slot_starts_at' => $start->toIso8601String()]));
        $lead = HighTicketLead::first();

        (new \App\Jobs\SubscribeDripLeadJob($lead->id, $drip->id))
            ->handle(app(\App\Services\DripService::class));

        $this->assertSame('0912345678', User::where('email', 'applicant@example.com')->first()->phone);
    }

    // ── 候補回訪連結（FR-042） ─────────────────────────────────────────────

    public function test_a_waitlisted_application_gets_a_resume_token(): void
    {
        Mail::fake();
        $this->templates();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/waitlist", $this->payload())->assertOk();

        $lead = HighTicketLead::first();
        $this->assertNotNull($lead->resume_token, '沒有 token 就沒有回訪的路');
        $this->assertSame(64, strlen($lead->resume_token));
    }

    /** Links already mailed out must keep working after a second waitlist submit. */
    public function test_reapplying_from_the_waitlist_keeps_the_same_resume_token(): void
    {
        Mail::fake();
        $this->templates();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/waitlist", $this->payload())->assertOk();
        $first = HighTicketLead::first()->resume_token;

        $this->postJson("/course/{$course->id}/waitlist", $this->payload(['bottleneck' => '改了說法']))->assertOk();

        $this->assertSame($first, HighTicketLead::first()->resume_token);
    }

    public function test_the_slot_notification_links_back_to_the_picker(): void
    {
        Mail::fake();
        $this->templates();
        $course = $this->makeHighTicketCourse();

        EmailTemplate::create([
            'name'       => '新時段通知',
            'event_type' => 'high_ticket_slot_available',
            'subject'    => '新時段',
            'body_md'    => 'Hi {{user_name}}，請選時段：{{booking_url}}',
        ]);

        $this->postJson("/course/{$course->id}/waitlist", $this->payload())->assertOk();
        $lead = HighTicketLead::first();

        app(\App\Services\HighTicketLeadService::class)->notifySlot([$lead->id]);

        Mail::assertSent(\App\Mail\TemplatedMail::class, function ($mail) use ($course, $lead) {
            return str_contains($mail->htmlBody, "/course/{$course->slug}?resume={$lead->resume_token}");
        });
    }

    /** Leads that predate FR-042 get a token minted at notify time, not left out. */
    public function test_a_lead_without_a_resume_token_is_given_one_when_notified(): void
    {
        Mail::fake();
        $course = $this->makeHighTicketCourse();

        EmailTemplate::create([
            'name'       => '新時段通知',
            'event_type' => 'high_ticket_slot_available',
            'subject'    => '新時段',
            'body_md'    => '請選時段：{{booking_url}}',
        ]);

        $lead = HighTicketLead::create([
            'name' => 'Old', 'email' => 'old@example.com', 'course_id' => $course->id,
            'status' => 'pending', 'booked_at' => now(),
        ]);

        app(\App\Services\HighTicketLeadService::class)->notifySlot([$lead->id]);

        $token = $lead->fresh()->resume_token;
        $this->assertNotNull($token);

        Mail::assertSent(\App\Mail\TemplatedMail::class, function ($mail) use ($course, $token) {
            return str_contains($mail->htmlBody, "/course/{$course->slug}?resume={$token}");
        });
    }

    /**
     * An old lead has a token but no questionnaire — dropping them on step 3
     * would leave required fields behind them empty.
     */
    public function test_a_lead_without_questionnaire_answers_starts_at_step_one(): void
    {
        $course = $this->makeHighTicketCourse();

        $lead = HighTicketLead::create([
            'name' => 'Old', 'email' => 'old@example.com', 'course_id' => $course->id,
            'status' => 'pending', 'booked_at' => now(), 'resume_token' => str_repeat('b', 64),
        ]);

        $this->get("/course/{$course->slug}?resume={$lead->resume_token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('bookingDraft.resume', false)
                ->where('bookingDraft.email', 'old@example.com'));
    }

    public function test_a_resume_token_prefills_the_wizard_for_a_guest(): void
    {
        Mail::fake();
        $this->templates();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/waitlist", $this->payload())->assertOk();
        $lead = HighTicketLead::first();

        // No login: holding the token IS the proof of identity.
        $this->get("/course/{$course->slug}?resume={$lead->resume_token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('bookingDraft.resume', true)
                ->where('bookingDraft.email', 'applicant@example.com')
                ->where('bookingDraft.name', 'Applicant')
                ->where('bookingDraft.phone', '0912345678'));
    }

    public function test_a_wrong_resume_token_prefills_nothing(): void
    {
        Mail::fake();
        $this->templates();
        $course = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/waitlist", $this->payload())->assertOk();

        $this->get("/course/{$course->slug}?resume=" . str_repeat('a', 64))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('bookingDraft', null));
    }

    /** A token is scoped to the course it was issued for. */
    public function test_a_resume_token_from_another_course_is_ignored(): void
    {
        Mail::fake();
        $this->templates();
        $course = $this->makeHighTicketCourse();
        $other = $this->makeHighTicketCourse();

        $this->postJson("/course/{$course->id}/waitlist", $this->payload())->assertOk();
        $lead = HighTicketLead::first();

        $this->get("/course/{$other->slug}?resume={$lead->resume_token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('bookingDraft', null));
    }
}
