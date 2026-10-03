<?php

namespace Tests\Feature\HighTicket;

use App\Mail\TemplatedMail;
use App\Models\ConsultationNote;
use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\Purchase;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\ConsultationSlotService;
use App\Services\HighTicketBookingService;
use App\Services\HighTicketLeadService;
use App\Services\PortalyWebhookService;
use App\Services\RedemptionService;
use App\Services\ZoomMeetingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Support\BooksHighTicket;
use Tests\TestCase;

/**
 * 011 US38 — consultation credits on ordinary courses, spent by self-booking.
 *
 * Two halves: the credits have to arrive with a storefront payment (which US37
 * deliberately never did), and spending one has to be atomic with taking the
 * slot — a stolen slot must not cost a credit, a missing credit must not hold a
 * slot (D152).
 */
class ConsultationCreditBookingTest extends TestCase
{
    use BooksHighTicket, RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin-credit@example.com', 'role' => 'admin']);
    }

    private function member(string $email = 'credit-member@example.com'): User
    {
        return User::create(['email' => $email, 'role' => 'member', 'nickname' => '小華', 'phone' => '0912345678']);
    }

    private function makeCourse(array $extra = []): Course
    {
        return Course::create(array_merge([
            'name' => '一般課', 'slug' => 'c-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 3000, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ], $extra));
    }

    private function creditCourse(array $extra = []): Course
    {
        return $this->makeCourse(array_merge([
            'bundle_name' => '1 對 1 諮詢',
            'bundle_default_quantity' => 3,
            'bundle_redeem_points' => 500,
        ], $extra));
    }

    private function coursePayload(Course $course, array $overrides = []): array
    {
        return array_merge([
            'name' => $course->name,
            'tagline' => $course->tagline,
            'description' => $course->description,
            'price' => (int) $course->price,
            'instructor_name' => $course->instructor_name,
            'type' => $course->type,
            'content_category' => 'mindset',
            'course_type' => $course->course_type,
        ], $overrides);
    }

    private function buyer(string $email = 'buyer-credit@example.com'): array
    {
        return ['name' => 'Buyer', 'email' => $email, 'phone' => '0900000000'];
    }

    private function checkout(Course $course, string $email = 'buyer-credit@example.com'): Purchase
    {
        $checkout = app(CheckoutService::class);
        $order = $checkout->createOrder(null, [$course->id], $this->buyer($email));
        $checkout->fulfillOrder($order->fresh(), 'trade-' . uniqid(), 'payuni');

        return Purchase::where('course_id', $course->id)->where('buyer_email', $email)->firstOrFail();
    }

    // ── settings guard (FR-222) ───────────────────────────────────────────

    public function test_an_ordinary_paid_course_can_carry_consultation_credits(): void
    {
        $course = $this->makeCourse();

        $this->actingAs($this->admin())
            ->put("/admin/courses/{$course->id}", $this->coursePayload($course, [
                'bundle_name' => '1 對 1 諮詢',
                'bundle_default_quantity' => 3,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $course->refresh();
        $this->assertSame('1 對 1 諮詢', $course->bundle_name);
        $this->assertSame(3, $course->bundle_default_quantity);
    }

    public function test_a_drip_course_cannot_carry_consultation_credits(): void
    {
        $course = $this->makeCourse(['course_type' => 'drip']);

        $this->actingAs($this->admin())
            ->put("/admin/courses/{$course->id}", $this->coursePayload($course, ['bundle_name' => '1 對 1 諮詢']))
            ->assertSessionHasErrors('bundle_name');

        $this->assertNull($course->fresh()->bundle_name);
    }

    public function test_a_free_course_cannot_carry_consultation_credits(): void
    {
        $course = $this->makeCourse(['price' => 0]);

        $this->actingAs($this->admin())
            ->put("/admin/courses/{$course->id}", $this->coursePayload($course, ['bundle_name' => '1 對 1 諮詢']))
            ->assertSessionHasErrors('bundle_name');

        $this->assertNull($course->fresh()->bundle_name);
    }

    public function test_saving_an_ineligible_course_with_the_forms_untouched_defaults_succeeds(): void
    {
        // The course form always posts every bundle field, and FormData turns
        // the defaults into "0". Those set nothing and must not block the save
        // (the error would land on a field that is not even rendered).
        $course = $this->makeCourse(['price' => 0]);

        $this->actingAs($this->admin())
            ->post("/admin/courses/{$course->id}", $this->coursePayload($course, [
                '_method' => 'put',
                'price' => '0',
                'bundle_name' => '',
                'bundle_redeem_points' => '',
                'bundle_default_quantity' => '0',
                'bundle_unlimited' => '0',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    }

    public function test_creating_a_drip_course_with_credits_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/courses', [
                'name' => '新課', 'tagline' => 't', 'description' => 'd', 'price' => 0,
                'instructor_name' => 'I', 'type' => 'lecture', 'content_category' => 'mindset',
                'course_type' => 'drip', 'bundle_name' => '1 對 1 諮詢',
            ])
            ->assertSessionHasErrors('bundle_name');
    }

    // ── grant on storefront payment (FR-224) ──────────────────────────────

    public function test_storefront_checkout_grants_the_courses_credits(): void
    {
        $purchase = $this->checkout($this->creditCourse());

        $this->assertSame(3, $purchase->bundle_balance);
        $this->assertSame(3, $purchase->bundle_granted);
    }

    public function test_a_repeated_payment_notification_does_not_grant_twice(): void
    {
        $course = $this->creditCourse();
        $checkout = app(CheckoutService::class);
        $order = $checkout->createOrder(null, [$course->id], $this->buyer());
        $checkout->fulfillOrder($order->fresh(), 'trade-x', 'payuni');
        $checkout->fulfillOrder($order->fresh(), 'trade-x', 'payuni');

        $this->assertSame(3, Purchase::where('course_id', $course->id)->sole()->bundle_balance);
    }

    public function test_storefront_checkout_of_a_high_ticket_course_still_grants_nothing(): void
    {
        $purchase = $this->checkout($this->creditCourse(['type' => 'high_ticket']));

        $this->assertSame(0, $purchase->bundle_balance);
    }

    public function test_portaly_payment_grants_the_courses_credits(): void
    {
        $course = $this->creditCourse(['portaly_product_id' => 'prod-credit']);

        $purchase = app(PortalyWebhookService::class)->createPurchase($this->member(), [
            'id' => 'portaly-1', 'productId' => 'prod-credit', 'amount' => 3000,
            'customerData' => ['email' => 'credit-member@example.com'],
        ]);

        $this->assertSame(3, $purchase->fresh()->bundle_balance);
    }

    public function test_redeeming_the_whole_course_with_points_grants_the_credits(): void
    {
        $course = $this->creditCourse(['redeem_points' => 100]);
        $user = $this->member();
        $user->forceFill(['points' => 1000])->save();

        $result = app(RedemptionService::class)->redeem($user, $course);

        $this->assertTrue($result['success'], $result['error'] ?? '');
        $this->assertSame(3, $result['purchase']->fresh()->bundle_balance);
    }

    // ── booking (FR-226–FR-231) ───────────────────────────────────────────

    private function holder(?Course $course = null, int $balance = 3, string $email = 'credit-member@example.com'): array
    {
        $course ??= $this->creditCourse();
        $user = User::where('email', $email)->first() ?? $this->member($email);
        $purchase = Purchase::create([
            'user_id' => $user->id, 'course_id' => $course->id, 'buyer_email' => $user->email,
            'amount' => 3000, 'currency' => 'TWD', 'status' => 'paid', 'type' => 'paid', 'source' => 'checkout',
        ]);
        Purchase::whereKey($purchase->id)->update(['bundle_balance' => $balance, 'bundle_granted' => $balance]);

        return [$user, $course, $purchase->fresh()];
    }

    /** Four free 15-minute units — one hour — starting $daysAhead days out at $time Taipei. */
    private function openHour(int $daysAhead = 3, string $time = '14:00', ?int $consultantId = null): Carbon
    {
        $at = Carbon::parse("+{$daysAhead} day {$time}", ConsultationSlotService::DISPLAY_TZ)->utc();

        for ($i = 0; $i < 4; $i++) {
            ConsultationSlot::firstOrCreate(
                ['starts_at' => $at->copy()->addMinutes($i * ConsultationSlot::UNIT_MINUTES)],
                ['consultant_id' => $consultantId],
            );
        }

        return $at;
    }

    private function seedCreditTemplate(): void
    {
        EmailTemplate::updateOrCreate(['event_type' => 'consultation_credit_booking_confirmation'], [
            'name' => '諮詢預約成立',
            'subject' => '諮詢預約成立 {{slot_time}}',
            'body_md' => "{{course_name}} {{consult_minutes}} 分鐘\n\n{{zoom_join_url}}\n\n剩 {{remaining_credits}}",
        ]);
    }

    private function book(User $user, Course $course, Carbon $at)
    {
        return $this->actingAs($user)->postJson("/course/{$course->id}/consultation-bookings", [
            'starts_at' => $at->toIso8601String(),
        ]);
    }

    public function test_booking_spends_one_credit_and_confirms_the_hour_immediately(): void
    {
        Mail::fake();
        $this->seedCreditTemplate();
        [$user, $course, $purchase] = $this->holder();
        $at = $this->openHour();

        $this->book($user, $course, $at)->assertOk();

        $this->assertSame(2, $purchase->fresh()->bundle_balance);
        $this->assertSame(3, $purchase->fresh()->bundle_granted);

        $lead = HighTicketLead::sole();
        $this->assertSame('credit', $lead->kind);
        $this->assertSame($purchase->id, $lead->purchase_id);
        $this->assertSame(1, $lead->credits_spent);
        $this->assertNotNull($lead->confirmed_at);
        $this->assertNull($lead->confirm_token);
        $this->assertSame(4, ConsultationSlot::where('lead_id', $lead->id)->whereNull('held_until')->count());
    }

    public function test_the_confirmation_mail_goes_to_the_member_cc_the_consultant_with_an_invite(): void
    {
        Mail::fake();
        $this->seedCreditTemplate();
        $consultant = User::create(['email' => 'consultant@example.com', 'role' => 'member', 'is_sales_consultant' => true]);
        [$user, $course] = $this->holder();
        $at = $this->openHour(consultantId: $consultant->id);

        $this->book($user, $course, $at)->assertOk();

        $this->assertSame($consultant->id, HighTicketLead::sole()->consultant_id);
        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo('credit-member@example.com')
            && $mail->hasCc('consultant@example.com')
            && str_contains($mail->emailSubject, '諮詢預約成立')
            && str_contains($mail->htmlBody, '60 分鐘')
            && str_contains($mail->htmlBody, '剩 2')
            && count($mail->fileAttachments) === 1);
    }

    public function test_zoom_is_created_for_sixty_minutes_when_configured(): void
    {
        Mail::fake();
        $this->seedCreditTemplate();
        SiteSetting::set(ZoomMeetingService::ACCOUNT_ID_KEY, 'a');
        SiteSetting::set(ZoomMeetingService::CLIENT_ID_KEY, 'b');
        SiteSetting::set(ZoomMeetingService::CLIENT_SECRET_KEY, 'c');
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 't']),
            'api.zoom.us/v2/users/*' => Http::response(['id' => 77, 'join_url' => 'https://zoom.us/j/77']),
        ]);
        [$user, $course] = $this->holder();

        $this->book($user, $course, $this->openHour())->assertOk();

        $this->assertSame('https://zoom.us/j/77', HighTicketLead::sole()->zoom_join_url);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/meetings') && $request['duration'] === 60);
    }

    public function test_an_unlimited_holder_books_without_spending(): void
    {
        Mail::fake();
        [$user, $course, $purchase] = $this->holder($this->creditCourse(['bundle_unlimited' => true]), 0);

        $this->book($user, $course, $this->openHour())->assertOk();

        $this->assertSame(0, $purchase->fresh()->bundle_balance);
        $this->assertSame(0, HighTicketLead::sole()->credits_spent);
    }

    public function test_no_credit_left_is_refused_and_the_slot_stays_free(): void
    {
        [$user, $course] = $this->holder(balance: 0);
        $at = $this->openHour();

        $this->book($user, $course, $at)->assertStatus(422);

        $this->assertSame(0, HighTicketLead::count());
        $this->assertSame(0, ConsultationSlot::whereNotNull('lead_id')->count());
    }

    public function test_a_slot_taken_in_the_meantime_is_409_and_costs_nothing(): void
    {
        [$user, $course, $purchase] = $this->holder();
        $at = $this->openHour();
        // Somebody else already sits on the third quarter of the hour.
        $other = HighTicketLead::create(['name' => 'X', 'email' => 'x@example.com', 'course_id' => $course->id, 'status' => 'pending', 'booked_at' => now()]);
        ConsultationSlot::where('starts_at', $at->copy()->addMinutes(30))->update(['lead_id' => $other->id]);

        $this->book($user, $course, $at)->assertStatus(409);

        $this->assertSame(3, $purchase->fresh()->bundle_balance);
        $this->assertSame(1, HighTicketLead::count());
    }

    public function test_starts_inside_the_minimum_notice_are_refused_and_not_offered(): void
    {
        [$user, $course] = $this->holder();
        $soon = Carbon::now()->addHours(5)->startOfHour();
        for ($i = 0; $i < 4; $i++) {
            ConsultationSlot::create(['starts_at' => $soon->copy()->addMinutes($i * 15)]);
        }
        $later = $this->openHour();

        $slots = $this->actingAs($user)->getJson("/course/{$course->id}/consultation-slots")->assertOk()->json('slots');
        $offered = collect($slots)->flatMap(fn ($day) => array_column($day['times'], 'value'))->all();

        $this->assertSame([$later->toIso8601String()], $offered);
        $this->book($user, $course, $soon)->assertStatus(422);
    }

    public function test_the_minimum_notice_is_an_admin_setting(): void
    {
        SiteSetting::set(ConsultationSlotService::MIN_NOTICE_KEY, '0');
        Mail::fake();
        [$user, $course] = $this->holder();
        $soon = Carbon::now()->addHours(2)->startOfHour();
        for ($i = 0; $i < 4; $i++) {
            ConsultationSlot::create(['starts_at' => $soon->copy()->addMinutes($i * 15)]);
        }

        $this->book($user, $course, $soon)->assertOk();
    }

    public function test_staff_can_change_the_minimum_notice(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/consultation-slots/settings', ['bonus_codes' => '', 'min_notice_hours' => 6])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(6, app(ConsultationSlotService::class)->minNoticeHours());
    }

    public function test_a_quarter_past_start_is_refused(): void
    {
        [$user, $course] = $this->holder();
        $at = $this->openHour(time: '14:00');
        $this->openHour(time: '15:00');

        $this->book($user, $course, $at->copy()->addMinutes(15))->assertStatus(422);
    }

    public function test_only_one_unfinished_booking_at_a_time(): void
    {
        Mail::fake();
        [$user, $course, $purchase] = $this->holder();
        $first = $this->openHour(3);
        $second = $this->openHour(4);

        $this->book($user, $course, $first)->assertOk();
        $this->book($user, $course, $second)->assertStatus(422);

        $this->assertSame(2, $purchase->fresh()->bundle_balance);
    }

    public function test_the_next_booking_opens_once_the_last_one_has_ended(): void
    {
        Mail::fake();
        [$user, $course] = $this->holder();
        $first = $this->openHour(3);
        $this->book($user, $course, $first)->assertOk();

        $this->travelTo($first->copy()->addMinutes(61));
        $this->book($user, $course, $this->openHour(3))->assertOk();

        $this->assertSame(2, HighTicketLead::count());
    }

    public function test_someone_without_the_course_is_forbidden(): void
    {
        $course = $this->creditCourse();
        $stranger = $this->member('stranger@example.com');

        $this->book($stranger, $course, $this->openHour())->assertForbidden();
    }

    public function test_a_refunded_holding_is_forbidden(): void
    {
        [$user, $course, $purchase] = $this->holder();
        $purchase->update(['status' => 'refunded']);

        $this->book($user, $course, $this->openHour())->assertForbidden();
    }

    public function test_a_high_ticket_course_offers_no_self_booking(): void
    {
        [$user, $course] = $this->holder($this->creditCourse(['type' => 'high_ticket']));

        $this->book($user, $course, $this->openHour())->assertStatus(422);
    }

    public function test_booking_requires_login(): void
    {
        $course = $this->creditCourse();

        $this->postJson("/course/{$course->id}/consultation-bookings", ['starts_at' => now()->toIso8601String()])
            ->assertUnauthorized();
    }

    // ── cancel / reschedule (FR-231, FR-225) ──────────────────────────────

    public function test_cancelling_gives_the_credit_back_exactly_once(): void
    {
        Mail::fake();
        Queue::fake();
        [$user, $course, $purchase] = $this->holder();
        $this->book($user, $course, $this->openHour())->assertOk();
        $lead = HighTicketLead::sole();

        $bookings = app(HighTicketBookingService::class);
        $this->assertTrue($bookings->cancel($lead)['success']);
        $this->assertFalse($bookings->cancel($lead->fresh())['success']);

        $this->assertSame(3, $purchase->fresh()->bundle_balance);
        $this->assertSame(0, $lead->fresh()->credits_spent);
        $this->assertSame(0, ConsultationSlot::whereNotNull('lead_id')->count());
    }

    public function test_cancelling_an_unlimited_booking_refunds_nothing(): void
    {
        Mail::fake();
        Queue::fake();
        [$user, $course, $purchase] = $this->holder($this->creditCourse(['bundle_unlimited' => true]), 0);
        $this->book($user, $course, $this->openHour())->assertOk();

        app(HighTicketBookingService::class)->cancel(HighTicketLead::sole());

        $this->assertSame(0, $purchase->fresh()->bundle_balance);
    }

    public function test_a_credit_booking_cannot_be_declined(): void
    {
        Mail::fake();
        [$user, $course] = $this->holder();
        $this->book($user, $course, $this->openHour())->assertOk();

        $this->assertFalse(app(HighTicketBookingService::class)->decline(HighTicketLead::sole())['success']);
    }

    public function test_rescheduling_keeps_the_hour_long(): void
    {
        Mail::fake();
        Queue::fake();
        [$user, $course, $purchase] = $this->holder();
        $this->book($user, $course, $this->openHour(3))->assertOk();
        $target = $this->openHour(5);

        $result = app(HighTicketBookingService::class)->reschedule(HighTicketLead::sole(), $target);

        $this->assertTrue($result['success']);
        $this->assertSame(4, ConsultationSlot::where('lead_id', HighTicketLead::sole()->id)->count());
        $this->assertEquals($target, ConsultationSlot::where('lead_id', HighTicketLead::sole()->id)->min('starts_at') ? Carbon::parse(ConsultationSlot::where('lead_id', HighTicketLead::sole()->id)->min('starts_at'), 'UTC') : null);
        $this->assertSame(2, $purchase->fresh()->bundle_balance);
    }

    // ── kept out of the sales funnel (FR-232) ─────────────────────────────

    public function test_credit_bookings_stay_out_of_the_leads_list_and_stats(): void
    {
        Mail::fake();
        [$user, $course] = $this->holder();
        $this->book($user, $course, $this->openHour())->assertOk();

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('leads.total', 0));
    }

    public function test_credit_bookings_are_not_exported(): void
    {
        Mail::fake();
        [$user, $course] = $this->holder();
        $this->book($user, $course, $this->openHour())->assertOk();

        $admin = $this->admin();
        $byQuery = $this->actingAs($admin)->get('/admin/high-ticket-leads/export?select_all=1')->streamedContent();
        $byIds = $this->actingAs($admin)->get('/admin/high-ticket-leads/export?ids[]=' . HighTicketLead::sole()->id)->streamedContent();

        $this->assertStringNotContainsString('credit-member@example.com', $byQuery);
        $this->assertStringNotContainsString('credit-member@example.com', $byIds);
    }

    public function test_slot_notifications_skip_credit_bookings(): void
    {
        Mail::fake();
        Queue::fake();
        [$user, $course] = $this->holder();
        $this->book($user, $course, $this->openHour())->assertOk();

        EmailTemplate::updateOrCreate(['event_type' => 'high_ticket_slot_available'], [
            'name' => '新時段', 'subject' => 's', 'body_md' => 'b',
        ]);

        $result = app(HighTicketLeadService::class)->notifySlot([HighTicketLead::sole()->id]);

        $this->assertSame(['dispatched' => 0], $result);
    }

    public function test_the_funnel_batches_leave_credit_bookings_alone(): void
    {
        Mail::fake();
        [$user, $course] = $this->holder();
        $this->book($user, $course, $this->openHour())->assertOk();
        $this->travel(10)->days();

        $bookings = app(HighTicketBookingService::class);
        $bookings->purgeExpiredApplications();
        $bookings->cancelStaleApplications();
        $bookings->sendApplicationResumeReminders();

        $lead = HighTicketLead::sole();
        $this->assertSame('pending', $lead->status);
        $this->assertNull($lead->cancelled_at);
    }

    public function test_no_drip_capi_or_consultation_note_for_a_credit_booking(): void
    {
        Mail::fake();
        [$user, $course] = $this->holder();

        $this->book($user, $course, $this->openHour())->assertOk();

        $this->assertSame(0, ConsultationNote::count());
    }

    // ── pages (FR-233, T530, T531) ────────────────────────────────────────

    public function test_the_sales_page_tells_every_visitor_what_comes_with_the_course(): void
    {
        $course = $this->creditCourse();

        $this->get("/course/{$course->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('consultationOffer', ['name' => '1 對 1 諮詢', 'quantity' => 3, 'unlimited' => false])
                ->where('consultationBooking', null));
    }

    public function test_the_sales_page_gives_a_holder_their_booking_state(): void
    {
        Mail::fake();
        [$user, $course] = $this->holder();

        $this->actingAs($user)->get("/course/{$course->id}")
            ->assertInertia(fn ($page) => $page
                ->where('consultationBooking.balance', 3)
                ->where('consultationBooking.active', null));

        $at = $this->openHour();
        $this->book($user, $course, $at)->assertOk()->assertJsonPath('booking.balance', 2);

        $this->actingAs($user)->get("/course/{$course->id}")
            ->assertInertia(fn ($page) => $page
                ->where('consultationBooking.balance', 2)
                ->where('consultationBooking.active.slot_label', app(ConsultationSlotService::class)->label($at)));
    }

    public function test_a_high_ticket_sales_page_carries_no_consultation_offer(): void
    {
        $course = $this->creditCourse(['type' => 'high_ticket']);

        $this->get("/course/{$course->id}")
            ->assertInertia(fn ($page) => $page->where('consultationOffer', null));
    }

    public function test_my_courses_card_links_to_booking_and_shows_the_upcoming_session(): void
    {
        Mail::fake();
        [$user, $course] = $this->holder();

        $this->actingAs($user)->get('/member/learning')
            ->assertInertia(fn ($page) => $page
                ->where('courses.0.bundle.self_booking', true)
                ->where('courses.0.bundle.active_slot_label', null)
                ->where('courses.0.bundle.booking_url', route('course.show', $course) . '#consultation-booking'));

        $at = $this->openHour();
        $this->book($user, $course, $at)->assertOk();

        $this->actingAs($user)->get('/member/learning')
            ->assertInertia(fn ($page) => $page
                ->where('courses.0.bundle.active_slot_label', app(ConsultationSlotService::class)->label($at)));
    }

    public function test_the_week_grid_marks_a_credit_booking(): void
    {
        Mail::fake();
        [$user, $course] = $this->holder();
        $at = $this->openHour();
        $this->book($user, $course, $at)->assertOk();

        $view = app(ConsultationSlotService::class)->weekView($at->copy()->timezone(ConsultationSlotService::DISPLAY_TZ)->format('Y-m-d'));
        $booking = collect($view['days'])->flatMap(fn ($day) => $day['bookings'])->sole();

        $this->assertSame('credit', $booking['kind']);
        $this->assertSame('一般課', $booking['course_name']);
        $this->assertSame(4, $booking['units']);
    }

    public function test_the_roster_cannot_deduct_credits_on_a_self_booking_course(): void
    {
        [$user, $course, $purchase] = $this->holder();
        $admin = $this->admin();

        $this->actingAs($admin)->getJson("/admin/courses/{$course->id}/roster")
            ->assertOk()
            ->assertJsonPath('self_booking', true);

        $this->actingAs($admin)
            ->postJson("/admin/courses/{$course->id}/bundle/consume", ['user_ids' => [$user->id]])
            ->assertStatus(422);

        $this->assertSame(3, $purchase->fresh()->bundle_balance);
    }

    public function test_the_day_before_reminder_covers_a_credit_booking_at_sixty_minutes(): void
    {
        Mail::fake();
        SiteSetting::set(ConsultationSlotService::MIN_NOTICE_KEY, '0');
        EmailTemplate::updateOrCreate(['event_type' => 'high_ticket_consultation_reminder'], [
            'name' => '明日提醒', 'subject' => '明日提醒', 'body_md' => '{{consult_minutes}} 分鐘',
        ]);
        [$user, $course] = $this->holder();
        $this->book($user, $course, $this->openHour(1))->assertOk();

        $this->assertSame(1, app(HighTicketBookingService::class)->sendDayBeforeReminders());
        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->emailSubject === '明日提醒'
            && str_contains($mail->htmlBody, '60 分鐘'));
    }
}
