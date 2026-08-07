<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use App\Services\ConsultationSlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 011 US10/US11 — the slot grid and the one-hour hold.
 *
 * The rules under test are the ones that decide whether two people can end up
 * on the consultant's calendar at the same time, so they are exercised at the
 * service layer where the locking lives.
 */
class SlotHoldTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ConsultationSlotService
    {
        return app(ConsultationSlotService::class);
    }

    /** Create n consecutive 15-minute units starting at $start (Taipei wall clock). */
    private function makeSlots(string $start, int $count): array
    {
        $at = Carbon::parse($start, ConsultationSlotService::DISPLAY_TZ)->utc();
        $made = [];

        for ($i = 0; $i < $count; $i++) {
            $made[] = ConsultationSlot::create([
                'starts_at' => $at->copy()->addMinutes($i * ConsultationSlot::UNIT_MINUTES),
            ]);
        }

        return $made;
    }

    private function makeLead(string $email = 'a@example.com'): HighTicketLead
    {
        return HighTicketLead::create([
            'name'      => 'Applicant',
            'email'     => $email,
            'course_id' => 1,
            'status'    => 'pending',
            'booked_at' => now(),
        ]);
    }

    public function test_default_consultation_is_thirty_minutes(): void
    {
        $this->assertSame(30, $this->service()->minutesFor(null));
        $this->assertSame(30, $this->service()->minutesFor(''));
        $this->assertSame(30, $this->service()->minutesFor('nope'));
    }

    public function test_bonus_code_extends_to_forty_five_minutes(): void
    {
        SiteSetting::set(ConsultationSlotService::BONUS_CODES_KEY, 'VIP2026, GOLD');

        $this->assertSame(45, $this->service()->minutesFor('VIP2026'));
        // Case and surrounding whitespace must not matter (FR-031).
        $this->assertSame(45, $this->service()->minutesFor('  vip2026 '));
        $this->assertSame(45, $this->service()->minutesFor('gold'));
        $this->assertSame(30, $this->service()->minutesFor('VIP2025'));
    }

    public function test_available_starts_requires_consecutive_units(): void
    {
        // 10:00, 10:15, 10:30 then a gap, then 11:00.
        $this->makeSlots('+1 day 10:00', 3);
        $this->makeSlots('+1 day 11:00', 1);

        $starts = $this->service()->availableStarts(30);

        // 10:00 and 10:15 each have a following unit; 10:30 and 11:00 do not.
        $this->assertCount(2, $starts);
        $this->assertSame('10:00', $starts[0]->timezone(ConsultationSlotService::DISPLAY_TZ)->format('H:i'));
        $this->assertSame('10:15', $starts[1]->timezone(ConsultationSlotService::DISPLAY_TZ)->format('H:i'));
    }

    public function test_forty_five_minutes_needs_three_consecutive_units(): void
    {
        $this->makeSlots('+1 day 10:00', 3);

        $this->assertCount(1, $this->service()->availableStarts(45));
        $this->assertCount(2, $this->service()->availableStarts(30));
    }

    /**
     * FR-069: every quarter-hour from 10:00 to 11:00 has three consecutive
     * units behind it (10:15's would be 10:15/10:30/10:45), so the old logic
     * would offer :15 and :45 too. Only :00 and :30 may surface.
     */
    public function test_forty_five_minute_starts_are_limited_to_the_hour_or_half_hour(): void
    {
        $this->makeSlots('+1 day 10:00', 5);

        $starts = collect($this->service()->availableStarts(45))
            ->map(fn ($at) => $at->timezone(ConsultationSlotService::DISPLAY_TZ)->format('H:i'))
            ->all();

        $this->assertSame(['10:00', '10:30'], $starts);
    }

    public function test_thirty_minute_starts_are_not_restricted_to_the_hour_or_half_hour(): void
    {
        $this->makeSlots('+1 day 10:00', 3);

        $starts = collect($this->service()->availableStarts(30))
            ->map(fn ($at) => $at->timezone(ConsultationSlotService::DISPLAY_TZ)->format('H:i'))
            ->all();

        $this->assertSame(['10:00', '10:15'], $starts);
    }

    public function test_past_slots_are_never_offered(): void
    {
        $this->makeSlots('-1 day 10:00', 4);

        $this->assertCount(0, $this->service()->availableStarts(30));
    }

    public function test_reserve_holds_the_units_and_removes_them_from_availability(): void
    {
        $this->makeSlots('+1 day 10:00', 4);
        $lead = $this->makeLead();
        $start = $this->service()->availableStarts(30)[0];

        $this->service()->reserve($lead, $start, 30, now()->addHour());

        $this->assertSame(2, $lead->slots()->count());
        // 10:00 and 10:15 are gone; only 10:30 still has a partner (10:45).
        $this->assertCount(1, $this->service()->availableStarts(30));
    }

    public function test_second_booker_of_the_same_slot_is_rejected(): void
    {
        $this->makeSlots('+1 day 10:00', 2);
        $start = $this->service()->availableStarts(30)[0];

        $this->service()->reserve($this->makeLead('first@example.com'), $start, 30, now()->addHour());

        $this->expectException(\App\Exceptions\SlotUnavailableException::class);
        $this->service()->reserve($this->makeLead('second@example.com'), $start, 30, now()->addHour());
    }

    public function test_expired_hold_frees_the_slot_for_someone_else(): void
    {
        $this->makeSlots('+1 day 10:00', 2);
        $start = $this->service()->availableStarts(30)[0];
        $first = $this->makeLead('first@example.com');

        $this->service()->reserve($first, $start, 30, now()->subMinute());

        // Lazy release (FR-035): no command has run, the slot is simply free again.
        $this->assertCount(1, $this->service()->availableStarts(30));

        $second = $this->makeLead('second@example.com');
        $this->service()->reserve($second, $start, 30, now()->addHour());

        $this->assertSame(2, $second->slots()->count());
        $this->assertSame(0, $first->slots()->count());
    }

    public function test_confirmed_slots_are_never_released(): void
    {
        $this->makeSlots('+1 day 10:00', 2);
        $start = $this->service()->availableStarts(30)[0];
        $lead = $this->makeLead();

        $this->service()->reserve($lead, $start, 30, now()->addHour());
        $this->service()->confirm($lead);

        $this->assertNull($lead->slots()->first()->held_until);
        $this->assertCount(0, $this->service()->availableStarts(30));

        // Even long after the original hold would have expired.
        $this->travel(2)->hours();
        $this->assertCount(0, $this->service()->availableStarts(30));
    }

    public function test_rebooking_releases_the_previous_hold(): void
    {
        $this->makeSlots('+1 day 10:00', 4);
        $lead = $this->makeLead();
        $starts = $this->service()->availableStarts(30);

        $this->service()->reserve($lead, $starts[0], 30, now()->addHour());
        $this->service()->reserve($lead, $starts[2], 30, now()->addHour());

        // One person never holds two ranges at once.
        $this->assertSame(2, $lead->slots()->count());
        $this->assertSame(
            '10:30',
            $lead->slots()->first()->starts_at->timezone(ConsultationSlotService::DISPLAY_TZ)->format('H:i')
        );
    }

    public function test_generate_creates_units_and_skips_existing(): void
    {
        $day = Carbon::parse('+2 days 09:00', ConsultationSlotService::DISPLAY_TZ);
        $to = $day->copy()->addHour();

        $first = $this->service()->generate($day, $to);
        $this->assertSame(['created' => 4, 'skipped' => 0], $first);

        $second = $this->service()->generate($day, $to);
        $this->assertSame(['created' => 0, 'skipped' => 4], $second);
        $this->assertSame(4, ConsultationSlot::count());
    }

    public function test_release_expired_command_clears_stale_holds_only(): void
    {
        $this->makeSlots('+1 day 10:00', 4);
        $starts = $this->service()->availableStarts(30);

        $stale = $this->makeLead('stale@example.com');
        $this->service()->reserve($stale, $starts[0], 30, now()->subMinute());

        $confirmed = $this->makeLead('ok@example.com');
        $this->service()->reserve($confirmed, $starts[2], 30, now()->addHour());
        $this->service()->confirm($confirmed);

        $this->artisan('booking:release-holds')->assertExitCode(0);

        $this->assertSame(0, $stale->slots()->count());
        $this->assertSame(2, $confirmed->slots()->count());
    }
}
