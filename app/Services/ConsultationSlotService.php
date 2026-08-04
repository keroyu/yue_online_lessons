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
    public function generate(CarbonInterface $from, CarbonInterface $to): array
    {
        $cursor = Carbon::instance($from)->utc();
        $end = Carbon::instance($to)->utc();

        $created = 0;
        $skipped = 0;

        while ($cursor->lt($end)) {
            $exists = ConsultationSlot::where('starts_at', $cursor)->exists();

            if ($exists) {
                $skipped++;
            } else {
                ConsultationSlot::create(['starts_at' => $cursor->copy()]);
                $created++;
            }

            $cursor->addMinutes(ConsultationSlot::UNIT_MINUTES);
        }

        return ['created' => $created, 'skipped' => $skipped];
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
     * @throws SlotUnavailableException
     */
    public function reserve(HighTicketLead $lead, CarbonInterface $startsAt, int $minutes, CarbonInterface $holdUntil): void
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
                'held_until' => Carbon::instance($holdUntil)->utc(),
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
