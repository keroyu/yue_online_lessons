<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\User;
use App\Services\ConsultationSlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 011 US13 — the consultant's calendar as a week grid.
 *
 * The week payload is assembled server-side (D46): a 30-minute consultation is
 * two rows in the database but must be one block on screen, and re-deriving
 * "consecutive" in Vue would be a second copy of FR-028's rule.
 */
class ConsultationSlotAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function slots(): ConsultationSlotService
    {
        return app(ConsultationSlotService::class);
    }

    /** A Taipei wall-clock time, stored the way the picker stores it. */
    private function at(string $taipei): Carbon
    {
        return Carbon::parse($taipei, ConsultationSlotService::DISPLAY_TZ)->utc();
    }

    private function makeSlot(string $taipei, ?HighTicketLead $lead = null, ?Carbon $heldUntil = null): ConsultationSlot
    {
        return ConsultationSlot::create([
            'starts_at'  => $this->at($taipei),
            'lead_id'    => $lead?->id,
            'held_until' => $heldUntil,
        ]);
    }

    private function makeLead(string $name = 'Booker'): HighTicketLead
    {
        $course = Course::create([
            'name' => 'HT', 'slug' => 'ht-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 50000, 'instructor_name' => 'I', 'type' => 'high_ticket', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni', 'high_ticket_hide_price' => true,
        ]);

        return HighTicketLead::create([
            'name' => $name, 'email' => strtolower($name) . '@example.com',
            'course_id' => $course->id, 'status' => 'pending', 'booked_at' => now(),
        ]);
    }

    private function day(array $view, string $date): array
    {
        foreach ($view['days'] as $day) {
            if ($day['date'] === $date) {
                return $day;
            }
        }

        $this->fail("週檢視沒有 {$date} 這一天");
    }

    // ── 週資料組裝（T099） ──────────────────────────────────────────────

    public function test_the_week_runs_monday_to_sunday_in_taipei_time(): void
    {
        // 2026-08-05 is a Wednesday; its week is Mon 08-03 … Sun 08-09.
        $view = $this->slots()->weekView('2026-08-05');

        $this->assertSame('2026-08-03', $view['week']['start']);
        $this->assertSame('2026-08-09', $view['week']['end']);
        $this->assertCount(7, $view['days']);
        $this->assertSame('2026-08-03', $view['days'][0]['date']);
        $this->assertSame('一', $view['days'][0]['weekday']);
        $this->assertSame('2026-07-27', $view['week']['prev']);
        $this->assertSame('2026-08-10', $view['week']['next']);
    }

    /**
     * A slot at 23:30 Taipei is 15:30 UTC the same day — bucketing on the raw
     * UTC value would file it under the wrong date, and near midnight under the
     * wrong week entirely.
     */
    public function test_a_late_night_slot_lands_on_the_taipei_date(): void
    {
        $this->makeSlot('2026-08-05 23:30');

        $view = $this->slots()->weekView('2026-08-05');

        $this->assertContains('23:30', $this->day($view, '2026-08-05')['free']);
    }

    public function test_the_grid_expands_to_cover_a_slot_outside_office_hours(): void
    {
        $this->makeSlot('2026-08-05 06:30');

        $view = $this->slots()->weekView('2026-08-05');

        // The 08:00–22:00 default is a starting point, not a filter (D47).
        $this->assertSame('06:30', $view['range']['start']);
        $this->assertContains('06:30', $view['range']['rows']);
        $this->assertContains('21:45', $view['range']['rows']);
    }

    public function test_the_default_range_is_office_hours(): void
    {
        $this->makeSlot('2026-08-05 10:00');

        $view = $this->slots()->weekView('2026-08-05');

        $this->assertSame('08:00', $view['range']['start']);
        $this->assertSame('22:00', $view['range']['end']);
        $this->assertCount(56, $view['range']['rows']);
    }

    public function test_consecutive_units_of_one_lead_merge_into_one_booking(): void
    {
        $lead = $this->makeLead('Alice');
        $this->makeSlot('2026-08-05 10:00', $lead);
        $this->makeSlot('2026-08-05 10:15', $lead);

        $bookings = $this->day($this->slots()->weekView('2026-08-05'), '2026-08-05')['bookings'];

        $this->assertCount(1, $bookings, '30 分鐘是一塊，不是兩格');
        $this->assertSame('10:00', $bookings[0]['start']);
        $this->assertSame('10:30', $bookings[0]['end']);
        $this->assertSame(2, $bookings[0]['units']);
        $this->assertSame('Alice', $bookings[0]['name']);
        $this->assertSame('booked', $bookings[0]['state']);
    }

    public function test_a_gap_between_units_starts_a_new_booking(): void
    {
        $lead = $this->makeLead('Alice');
        $this->makeSlot('2026-08-05 10:00', $lead);
        $this->makeSlot('2026-08-05 14:00', $lead);

        $bookings = $this->day($this->slots()->weekView('2026-08-05'), '2026-08-05')['bookings'];

        $this->assertCount(2, $bookings, '同一人的兩場諮詢不得被併成一塊');
    }

    public function test_two_leads_back_to_back_stay_separate(): void
    {
        $a = $this->makeLead('Alice');
        $b = $this->makeLead('Bob');
        $this->makeSlot('2026-08-05 10:00', $a);
        $this->makeSlot('2026-08-05 10:15', $b);

        $bookings = $this->day($this->slots()->weekView('2026-08-05'), '2026-08-05')['bookings'];

        $this->assertCount(2, $bookings);
    }

    public function test_a_live_hold_is_a_block_and_an_expired_one_is_free(): void
    {
        $live = $this->makeLead('Live');
        $dead = $this->makeLead('Dead');
        $this->makeSlot('2026-08-05 10:00', $live, now()->addHour());
        $this->makeSlot('2026-08-05 11:00', $dead, now()->subHour());

        $day = $this->day($this->slots()->weekView('2026-08-05'), '2026-08-05');

        $this->assertCount(1, $day['bookings']);
        $this->assertSame('held', $day['bookings'][0]['state']);
        $this->assertNotNull($day['bookings'][0]['held_until']);
        // An expired hold is already available to the next visitor (D33), so
        // the admin must see it as available too.
        $this->assertContains('11:00', $day['free']);
    }

    public function test_an_invalid_week_param_falls_back_to_this_week(): void
    {
        $expected = Carbon::now(ConsultationSlotService::DISPLAY_TZ)->startOfWeek()->format('Y-m-d');

        foreach (['not-a-date', '', null] as $bad) {
            $this->assertSame($expected, $this->slots()->weekView($bad)['week']['start']);
        }
    }

    public function test_the_zoom_link_rides_along_with_a_confirmed_booking(): void
    {
        $lead = $this->makeLead('Alice');
        $lead->update(['zoom_join_url' => 'https://zoom.us/j/123']);
        $this->makeSlot('2026-08-05 10:00', $lead);

        $bookings = $this->day($this->slots()->weekView('2026-08-05'), '2026-08-05')['bookings'];

        $this->assertSame('https://zoom.us/j/123', $bookings[0]['zoom_join_url']);
    }

    // ── 批次收回（T100） ────────────────────────────────────────────────

    public function test_release_range_deletes_only_unoccupied_units(): void
    {
        $lead = $this->makeLead();
        $this->makeSlot('2026-08-05 10:00');
        $this->makeSlot('2026-08-05 10:15', $lead);
        $this->makeSlot('2026-08-05 10:30');

        $result = $this->slots()->releaseRange($this->at('2026-08-05 10:00'), $this->at('2026-08-05 10:45'));

        $this->assertSame(2, $result['released']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, ConsultationSlot::count());
        $this->assertNotNull(ConsultationSlot::first()->lead_id);
    }

    public function test_release_range_reclaims_an_expired_hold(): void
    {
        $lead = $this->makeLead();
        $this->makeSlot('2026-08-05 10:00', $lead, now()->subHour());

        $result = $this->slots()->releaseRange($this->at('2026-08-05 10:00'), $this->at('2026-08-05 10:15'));

        $this->assertSame(1, $result['released']);
        $this->assertSame(0, ConsultationSlot::count());
    }

    public function test_release_range_stops_at_the_end_of_the_range(): void
    {
        $this->makeSlot('2026-08-05 10:00');
        $this->makeSlot('2026-08-05 10:15');

        $this->slots()->releaseRange($this->at('2026-08-05 10:00'), $this->at('2026-08-05 10:15'));

        // The end is exclusive: 10:15 was not in the dragged range.
        $this->assertSame(1, ConsultationSlot::count());
    }

    // ── 端點（T100） ────────────────────────────────────────────────────

    public function test_the_batch_endpoint_releases_a_dragged_range(): void
    {
        $this->makeSlot('2026-08-05 10:00');
        $this->makeSlot('2026-08-05 10:15');

        $this->actingAs($this->admin())
            ->delete('/admin/consultation-slots', [
                'date'       => '2026-08-05',
                'start_time' => '10:00',
                'end_time'   => '10:30',
            ])
            ->assertRedirect();

        $this->assertSame(0, ConsultationSlot::count());
    }

    public function test_the_batch_endpoint_rejects_a_guest(): void
    {
        $this->makeSlot('2026-08-05 10:00');

        $this->delete('/admin/consultation-slots', [
            'date' => '2026-08-05', 'start_time' => '10:00', 'end_time' => '10:15',
        ])->assertRedirect('/login');

        $this->assertSame(1, ConsultationSlot::count());
    }

    /** Reclaiming yesterday's leftovers is legitimate, unlike creating them. */
    public function test_the_batch_endpoint_allows_a_past_date(): void
    {
        $past = Carbon::now(ConsultationSlotService::DISPLAY_TZ)->subWeek();
        $this->makeSlot($past->format('Y-m-d') . ' 10:00');

        $this->actingAs($this->admin())
            ->delete('/admin/consultation-slots', [
                'date'       => $past->format('Y-m-d'),
                'start_time' => '10:00',
                'end_time'   => '10:15',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, ConsultationSlot::count());
    }

    // ── 預約優惠碼設定 ──────────────────────────────────────────────────

    public function test_the_bonus_codes_setting_is_saved_and_normalised(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/consultation-slots/settings', ['bonus_codes' => ' vip2026 ;GOLD,  早鳥 '])
            ->assertRedirect();

        // Stored as one canonical comma-separated string so the field reads back
        // the way the next admin would have typed it.
        $this->assertSame(
            'vip2026, GOLD, 早鳥',
            \App\Models\SiteSetting::get(ConsultationSlotService::BONUS_CODES_KEY)
        );
    }

    public function test_a_saved_code_actually_extends_the_consultation(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/consultation-slots/settings', ['bonus_codes' => 'VIP2026']);

        $this->assertSame(45, $this->slots()->minutesFor('  vip2026 '));
        $this->assertSame(30, $this->slots()->minutesFor('NOPE'));
    }

    public function test_clearing_the_setting_disables_every_code(): void
    {
        \App\Models\SiteSetting::set(ConsultationSlotService::BONUS_CODES_KEY, 'VIP2026');

        $this->actingAs($this->admin())
            ->put('/admin/consultation-slots/settings', ['bonus_codes' => '']);

        $this->assertSame(30, $this->slots()->minutesFor('VIP2026'));
    }

    public function test_the_settings_endpoint_rejects_a_guest(): void
    {
        $this->put('/admin/consultation-slots/settings', ['bonus_codes' => 'HACK'])
            ->assertRedirect('/login');

        $this->assertSame('', (string) \App\Models\SiteSetting::get(ConsultationSlotService::BONUS_CODES_KEY, ''));
    }

    public function test_the_page_exposes_the_current_codes(): void
    {
        \App\Models\SiteSetting::set(ConsultationSlotService::BONUS_CODES_KEY, 'VIP2026, GOLD');

        $this->actingAs($this->admin())
            ->get('/admin/consultation-slots')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('bonusCodes', 'VIP2026, GOLD'));
    }

    public function test_the_week_endpoint_renders_the_grid(): void
    {
        $this->makeSlot('2026-08-05 10:00');

        $this->actingAs($this->admin())
            ->get('/admin/consultation-slots?week=2026-08-05')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/ConsultationSlots/Index')
                ->where('week.start', '2026-08-03')
                ->has('days', 7)
                ->has('range.rows'));
    }
}
