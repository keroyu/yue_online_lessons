<?php

namespace App\Services;

use App\Exceptions\SlotUnavailableException;
use App\Models\ConsultationSlot;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The consultant's calendar (011 US10/US11).
 *
 * Everything time-related is funnelled through here so the UTC↔Taipei
 * conversion (D32) exists in exactly one place — the server runs UTC and a
 * booking that lands 8 hours off is worse than no booking at all.
 */
class ConsultationSlotService
{
    /** Slots are stored in UTC and always shown in the consultant's timezone. */
    public const DISPLAY_TZ = 'Asia/Taipei';

    /** A consultation is 30 minutes unless a bonus code extends it (FR-030). */
    public const DEFAULT_MINUTES = 30;

    /** What a valid booking code adds. */
    public const BONUS_MINUTES = 15;

    public const BONUS_CODES_KEY = 'high_ticket_booking_bonus_codes';

    /**
     * Consultation length for a submitted code. An unknown code is not an
     * error — it silently falls back to the default and the UI says so, rather
     * than blocking someone who already filled in the whole questionnaire (D31).
     */
    public function minutesFor(?string $code): int
    {
        return $this->codeIsValid($code)
            ? self::DEFAULT_MINUTES + self::BONUS_MINUTES
            : self::DEFAULT_MINUTES;
    }

    public function codeIsValid(?string $code): bool
    {
        $code = strtolower(trim((string) $code));

        if ($code === '') {
            return false;
        }

        $configured = HighTicketBookingService::parseRecipients(
            (string) SiteSetting::get(self::BONUS_CODES_KEY, '')
        );

        foreach ($configured as $valid) {
            if (strtolower(trim($valid)) === $code) {
                return true;
            }
        }

        return false;
    }

    /** How many 15-minute units a consultation of this length occupies. */
    public function unitsFor(int $minutes): int
    {
        return (int) ceil($minutes / ConsultationSlot::UNIT_MINUTES);
    }

    /**
     * Create one row per 15-minute unit between two instants.
     *
     * Re-running over an overlapping range is expected (the admin adds Tuesday
     * mornings every week), so existing units are skipped rather than erroring.
     *
     * @return array{created: int, skipped: int}
     */
    public function generate(CarbonInterface $from, CarbonInterface $to, ?int $consultantId = null): array
    {
        $cursor = Carbon::instance($from)->utc();
        $end = Carbon::instance($to)->utc();

        $created = 0;
        $skipped = 0;

        while ($cursor->lt($end)) {
            $exists = ConsultationSlot::where('starts_at', $cursor)->exists();

            if ($exists) {
                // Skipped rather than reassigned: the unit already belongs to
                // somebody, and dragging over it must not quietly take it (D57 —
                // one shared calendar means one owner per unit).
                $skipped++;
            } else {
                ConsultationSlot::create([
                    'starts_at'     => $cursor->copy(),
                    'consultant_id' => $consultantId,
                ]);
                $created++;
            }

            $cursor->addMinutes(ConsultationSlot::UNIT_MINUTES);
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /** Default first and last row of the week grid, in minutes from midnight (D47). */
    private const GRID_START_MINUTE = 8 * 60;

    private const GRID_END_MINUTE = 22 * 60;

    /**
     * Everything the admin week grid needs to draw itself (011 US13 / FR-043).
     *
     * Assembled here rather than in Vue because a 30-minute consultation is two
     * rows in the database and one block on screen: re-deriving "consecutive"
     * in the frontend would be a second copy of the rule availableStarts()
     * already owns, and two copies drift (D46).
     *
     * @param  string|null  $week  Any date in the wanted week (Y-m-d). Anything
     *                             unparseable falls back to the current week —
     *                             a hand-edited URL should show a calendar, not
     *                             an error page.
     */
    public function weekView(?string $week = null): array
    {
        $tz = self::DISPLAY_TZ;
        $monday = $this->parseWeek($week);

        $rows = ConsultationSlot::with(['lead:id,name,email,zoom_join_url', 'consultant:id,nickname,email'])
            ->where('starts_at', '>=', $monday->copy()->utc())
            ->where('starts_at', '<', $monday->copy()->addDays(7)->utc())
            ->orderBy('starts_at')
            ->get();

        // Bucket on the Taipei date, never the stored UTC one: 23:30 Taipei is
        // 15:30 UTC the same day, but 00:30 Taipei is the *previous* UTC day,
        // which would file it under the wrong column — and near midnight on a
        // Monday, the wrong week (D32).
        $free = [];
        $busy = [];
        $owners = [];
        $earliest = self::GRID_START_MINUTE;
        $latest = self::GRID_END_MINUTE;

        foreach ($rows as $row) {
            $local = $row->starts_at->copy()->timezone($tz);
            $date = $local->format('Y-m-d');
            $minute = $local->hour * 60 + $local->minute;

            $earliest = min($earliest, $minute);
            $latest = max($latest, $minute + ConsultationSlot::UNIT_MINUTES);

            if ($row->isAvailable()) {
                $free[$date][] = $local->format('H:i');
                // Ownership of an open slot is tooltip-only in v1 (D57): with
                // one consultant a visual lane is noise, with several it would
                // need lanes rather than a colour.
                $owners[$date][$local->format('H:i')] = $row->consultant?->nickname ?: $row->consultant?->email;
            } else {
                $busy[$date][] = ['row' => $row, 'local' => $local];
            }
        }

        $today = Carbon::now($tz)->startOfDay();
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $monday->copy()->addDays($i);
            $date = $day->format('Y-m-d');

            $days[] = [
                'date'     => $date,
                'label'    => $day->format('n/j'),
                'weekday'  => $this->weekdayName($day),
                'is_today' => $day->isSameDay($today),
                'is_past'  => $day->lt($today),
                'free'     => $free[$date] ?? [],
                'owners'   => $owners[$date] ?? [],
                'bookings' => $this->mergeBookings($busy[$date] ?? []),
            ];
        }

        return [
            'week'  => [
                'start'      => $monday->format('Y-m-d'),
                'end'        => $monday->copy()->addDays(6)->format('Y-m-d'),
                'label'      => $monday->format('Y/m/d') . ' – ' . $monday->copy()->addDays(6)->format('m/d'),
                'prev'       => $monday->copy()->subWeek()->format('Y-m-d'),
                'next'       => $monday->copy()->addWeek()->format('Y-m-d'),
                'current'    => Carbon::now($tz)->startOfWeek()->format('Y-m-d'),
                'is_current' => $monday->isSameDay(Carbon::now($tz)->startOfWeek()),
            ],
            'range' => [
                'start' => $this->minuteLabel($earliest),
                'end'   => $this->minuteLabel($latest),
                'rows'  => $this->rowLabels($earliest, $latest),
            ],
            'days'  => $days,
        ];
    }

    /**
     * Collapse one day's occupied units into the blocks a human sees.
     *
     * A run breaks on a different lead or a gap — two consultations for the
     * same person on the same day are two blocks, not one long one.
     *
     * @param  array<int, array{row: ConsultationSlot, local: Carbon}>  $items
     */
    /**
     * Site-wide totals for the legend (011 US13 / D65), not scoped to the
     * displayed week. Each real state uses a different time window:
     * available/held only count units that have not started yet, booked
     * counts everything (it means "consultations actually held"). "Not yet
     * released" has no count — see D65 for why.
     */
    public function statusCounts(): array
    {
        return [
            'available' => ConsultationSlot::available()->upcoming()->count(),
            'held'      => ConsultationSlot::whereNotNull('held_until')
                ->where('held_until', '>', now())
                ->upcoming()
                ->count(),
            'booked'    => ConsultationSlot::whereNotNull('lead_id')
                ->whereNull('held_until')
                ->count(),
        ];
    }

    private function mergeBookings(array $items): array
    {
        $blocks = [];
        $current = null;

        foreach ($items as $item) {
            $row = $item['row'];
            $local = $item['local'];

            $continues = $current !== null
                && $current['lead_id'] === $row->lead_id
                && $current['next']->equalTo($local);

            if (!$continues) {
                if ($current !== null) {
                    $blocks[] = $this->finishBlock($current);
                }

                $current = [
                    'lead_id'    => $row->lead_id,
                    'lead'       => $row->lead,
                    'consultant' => $row->consultant,
                    'start'      => $local->copy(),
                    'units'      => 0,
                    'held_until' => $row->held_until,
                    'next'       => $local->copy(),
                ];
            }

            $current['units']++;
            $current['next'] = $local->copy()->addMinutes(ConsultationSlot::UNIT_MINUTES);
        }

        if ($current !== null) {
            $blocks[] = $this->finishBlock($current);
        }

        return $blocks;
    }

    private function finishBlock(array $b): array
    {
        // A hold that is still running can still evaporate; one with no
        // held_until is a confirmed booking (FR-029).
        $held = $b['held_until'];

        return [
            'lead_id'       => $b['lead_id'],
            'start'         => $b['start']->format('H:i'),
            'end'           => $b['next']->format('H:i'),
            'start_minute'  => $b['start']->hour * 60 + $b['start']->minute,
            'units'         => $b['units'],
            'state'         => $held === null ? 'booked' : 'held',
            'name'          => $b['lead']?->name,
            'email'         => $b['lead']?->email,
            'zoom_join_url' => $b['lead']?->zoom_join_url,
            'consultant_id' => $b['consultant']?->id,
            'consultant'    => $b['consultant']?->nickname ?: $b['consultant']?->email,
            'held_until'    => $held?->copy()->timezone(self::DISPLAY_TZ)->format('H:i'),
        ];
    }

    /**
     * Delete every unoccupied unit in a dragged range (011 FR-044/FR-045).
     *
     * Occupied units are skipped rather than refused: dragging over a booked
     * slot on the way past should not abort the whole gesture, and the count
     * comes back so the flash message can say what was left behind.
     *
     * @return array{released: int, skipped: int}
     */
    public function releaseRange(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = ConsultationSlot::where('starts_at', '>=', Carbon::instance($from)->utc())
            ->where('starts_at', '<', Carbon::instance($to)->utc())
            ->get();

        $removable = $rows->filter(fn (ConsultationSlot $row) => $row->isAvailable());

        if ($removable->isNotEmpty()) {
            ConsultationSlot::whereIn('id', $removable->pluck('id'))->delete();
        }

        return [
            'released' => $removable->count(),
            'skipped'  => $rows->count() - $removable->count(),
        ];
    }

    private function parseWeek(?string $week): Carbon
    {
        $tz = self::DISPLAY_TZ;

        if (is_string($week) && $week !== '') {
            try {
                return Carbon::createFromFormat('Y-m-d', $week, $tz)->startOfWeek()->startOfDay();
            } catch (\Throwable) {
                // A typo in the URL should not be an error page.
            }
        }

        return Carbon::now($tz)->startOfWeek()->startOfDay();
    }

    /** @return array<int, string> Every row's start time, e.g. 08:00 … 21:45. */
    private function rowLabels(int $from, int $to): array
    {
        $labels = [];

        for ($m = $from; $m < $to; $m += ConsultationSlot::UNIT_MINUTES) {
            $labels[] = $this->minuteLabel($m);
        }

        return $labels;
    }

    /** Minutes from midnight as HH:MM — 1440 stays 24:00 rather than rolling over. */
    private function minuteLabel(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function weekdayName(CarbonInterface $at): string
    {
        return ['日', '一', '二', '三', '四', '五', '六'][$at->dayOfWeek];
    }

    /**
     * Start times a consultation of $minutes can actually begin at.
     *
     * A start only qualifies when every one of the N units it needs exists and
     * is free — a gap in the middle means the consultant is not available for
     * the whole session, so the start must not be offered (FR-028).
     *
     * @return array<int, Carbon> UTC instants, ascending
     */
    public function availableStarts(int $minutes): array
    {
        $units = $this->unitsFor($minutes);

        $free = ConsultationSlot::query()
            ->available()
            ->upcoming()
            ->orderBy('starts_at')
            ->pluck('starts_at');

        // Keyed by instant so "is the next unit also free" is a lookup, not a scan.
        $byKey = [];
        foreach ($free as $at) {
            $byKey[$this->key($at)] = $at;
        }

        $starts = [];

        foreach ($free as $at) {
            $ok = true;

            for ($i = 1; $i < $units; $i++) {
                $next = $at->copy()->addMinutes($i * ConsultationSlot::UNIT_MINUTES);

                if (!isset($byKey[$this->key($next)])) {
                    $ok = false;
                    break;
                }
            }

            if ($ok) {
                $starts[] = $at;
            }
        }

        return $starts;
    }

    /**
     * Hold the N units starting at $startsAt for this lead.
     *
     * Locked and re-checked inside the transaction because the visitor picked
     * from a list that may be seconds out of date (FR-032). Any previous hold
     * by the same lead is dropped first, so re-applying never leaves one person
     * sitting on two ranges.
     *
     * A null `$holdUntil` books the units outright (US14 / FR-048): rescheduling
     * an already-confirmed booking has nothing left to verify, so going through
     * a hold and an immediate confirm() would just be two writes for one fact.
     *
     * @throws SlotUnavailableException
     */
    public function reserve(HighTicketLead $lead, CarbonInterface $startsAt, int $minutes, ?CarbonInterface $holdUntil): void
    {
        $units = $this->unitsFor($minutes);
        $start = Carbon::instance($startsAt)->utc();

        $wanted = [];
        for ($i = 0; $i < $units; $i++) {
            $wanted[] = $start->copy()->addMinutes($i * ConsultationSlot::UNIT_MINUTES);
        }

        DB::transaction(function () use ($lead, $wanted, $units, $holdUntil) {
            $this->release($lead);

            $rows = ConsultationSlot::whereIn('starts_at', $wanted)
                ->lockForUpdate()
                ->get();

            if ($rows->count() !== $units) {
                throw new SlotUnavailableException();
            }

            foreach ($rows as $row) {
                if (!$row->isAvailable()) {
                    throw new SlotUnavailableException();
                }
            }

            ConsultationSlot::whereIn('id', $rows->pluck('id'))->update([
                'lead_id'    => $lead->id,
                'held_until' => $holdUntil ? Carbon::instance($holdUntil)->utc() : null,
                'updated_at' => now(),
            ]);
        });
    }

    /** Give back every unit this lead holds. */
    public function release(HighTicketLead $lead): void
    {
        ConsultationSlot::where('lead_id', $lead->id)->update([
            'lead_id'    => null,
            'held_until' => null,
            'updated_at' => now(),
        ]);
    }

    /**
     * Turn a hold into a permanent booking: clearing held_until is what makes
     * the units stop being reclaimable (FR-029).
     */
    public function confirm(HighTicketLead $lead): void
    {
        ConsultationSlot::where('lead_id', $lead->id)->update([
            'held_until' => null,
            'updated_at' => now(),
        ]);

        // Snapshot, not a live lookup (FR-061 / D58): the slot can be reassigned
        // later, and cancelling hands it back to the pool where somebody else
        // may claim it — either would rewrite who handled a settled booking.
        $consultantId = ConsultationSlot::where('lead_id', $lead->id)
            ->orderBy('starts_at')
            ->value('consultant_id');

        if ($consultantId !== null) {
            $lead->update(['consultant_id' => $consultantId]);
        }
    }

    /**
     * Tidy up holds nobody completed. Purely cosmetic — availability already
     * ignores expired holds, so the schedule falling over changes nothing that
     * a visitor can see (FR-035).
     */
    public function releaseExpired(): int
    {
        return ConsultationSlot::whereNotNull('lead_id')
            ->whereNotNull('held_until')
            ->where('held_until', '<=', now())
            ->update(['lead_id' => null, 'held_until' => null, 'updated_at' => now()]);
    }

    /** Human-facing label for an instant, e.g. 8/6（週三）14:30. */
    public function label(CarbonInterface $at): string
    {
        $local = Carbon::instance($at)->timezone(self::DISPLAY_TZ);
        $weekday = ['日', '一', '二', '三', '四', '五', '六'][$local->dayOfWeek];

        return $local->format('n/j') . "（週{$weekday}）" . $local->format('H:i');
    }

    /** Date bucket used to group the picker, e.g. 8/6（週三）. */
    public function dateLabel(CarbonInterface $at): string
    {
        $local = Carbon::instance($at)->timezone(self::DISPLAY_TZ);
        $weekday = ['日', '一', '二', '三', '四', '五', '六'][$local->dayOfWeek];

        return $local->format('n/j') . "（週{$weekday}）";
    }

    private function key(CarbonInterface $at): string
    {
        return Carbon::instance($at)->utc()->format('Y-m-d H:i');
    }
}
