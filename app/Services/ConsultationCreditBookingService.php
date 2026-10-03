<?php

namespace App\Services;

use App\Exceptions\SlotUnavailableException;
use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\Purchase;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Self-booked consultations on ordinary courses (011 US38).
 *
 * The customer has already paid and is logged in, so none of the application
 * funnel applies — no screening, no emailed confirmation, no hold. Picking a
 * slot books it, and the credit is spent in the same transaction (D152).
 *
 * The booking itself is a `high_ticket_leads` row of kind `credit` (D150), so
 * the week grid, reschedule, cancel, Zoom and invites all work unchanged.
 */
class ConsultationCreditBookingService
{
    public function __construct(
        private ConsultationSlotService $slots,
        private BundleCreditService $credits,
        private HighTicketBookingService $bookings,
    ) {}

    /** The earliest start a self-booking may take right now (FR-226). */
    public function notBefore(): Carbon
    {
        return now()->addHours($this->slots->minNoticeHours());
    }

    /**
     * Bookable hour-long starts, shaped for the picker (FR-226).
     *
     * @return array<int, array{date: string, value: string, times: array<int, array{value: string, label: string}>}>
     */
    public function slotsFor(): array
    {
        return $this->slots->groupStarts(
            $this->slots->availableStarts(ConsultationSlotService::CREDIT_MINUTES, null, $this->notBefore())
        );
    }

    public function holdingFor(User $user, Course $course): ?Purchase
    {
        return Purchase::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->paidStatus()
            ->first();
    }

    /**
     * The booking on this holding that has not ended yet, if any (FR-229).
     *
     * "Ended" is the clock, not the status: once the last unit is over the
     * customer may book again without anyone marking the session as held.
     */
    public function activeBookingFor(Purchase $purchase): ?HighTicketLead
    {
        return $this->unfinished([$purchase->id])->latest('id')->first();
    }

    /**
     * Start labels of the unfinished bookings on these holdings, in one query —
     * for the 我的課程 cards (011 T530).
     *
     * @param  array<int, int>  $purchaseIds
     * @return array<int, string> purchase_id => label
     */
    public function activeSlotLabels(array $purchaseIds): array
    {
        if ($purchaseIds === []) {
            return [];
        }

        return $this->unfinished($purchaseIds)
            ->get()
            ->filter(fn (HighTicketLead $lead) => $lead->slots->isNotEmpty())
            ->mapWithKeys(fn (HighTicketLead $lead) => [
                $lead->purchase_id => $this->slots->label($lead->slots->first()->starts_at),
            ])
            ->all();
    }

    /** @param array<int, int> $purchaseIds */
    private function unfinished(array $purchaseIds)
    {
        return HighTicketLead::whereIn('purchase_id', $purchaseIds)
            ->where('kind', 'credit')
            ->whereNotNull('confirmed_at')
            ->whereNull('cancelled_at')
            // The last unit is the one that has to still be running.
            ->whereHas('slots', fn ($query) => $query
                ->where('starts_at', '>', now()->subMinutes(ConsultationSlot::UNIT_MINUTES)))
            ->with('slots');
    }

    /**
     * What the sales page needs to show a holder (FR-233), or null when this
     * visitor has nothing to book with.
     *
     * @return array{balance: int, unlimited: bool, redeem_points: ?int, active: ?array{slot_label: string, zoom_join_url: ?string}}|null
     */
    public function stateFor(?User $user, Course $course): ?array
    {
        if (! $user || ! $this->credits->isSelfBooking($course)) {
            return null;
        }

        $purchase = $this->holdingFor($user, $course);

        if (! $purchase) {
            return null;
        }

        $active = $this->activeBookingFor($purchase);
        $start = $active?->slots->first()?->starts_at;

        return [
            'balance'       => (int) $purchase->bundle_balance,
            'unlimited'     => $this->credits->isUnlimitedFor($course, $purchase->plan),
            'redeem_points' => $course->bundle_redeem_points,
            'active'        => $active && $start ? [
                'slot_label'    => $this->slots->label($start),
                'zoom_join_url' => $active->zoom_join_url,
            ] : null,
        ];
    }

    /**
     * Book an hour and spend one credit, atomically (FR-227).
     *
     * The holding is locked first so two tabs pressing at once queue up: the
     * second one then sees the booking the first just made (D153). A slot lost
     * to someone else throws inside the transaction, which takes the spent
     * credit back with it.
     *
     * @return array{success: bool, status: int, message?: string, lead?: HighTicketLead}
     */
    public function book(User $user, Course $course, CarbonInterface $startsAt): array
    {
        if (! $this->credits->isSelfBooking($course)) {
            return $this->refuse(422, '此課程未提供諮詢預約');
        }

        $start = Carbon::instance($startsAt)->utc();

        try {
            $result = DB::transaction(function () use ($user, $course, $start) {
                $purchase = Purchase::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->paidStatus()
                    ->lockForUpdate()
                    ->first();

                if (! $purchase) {
                    return $this->refuse(403, '你尚未擁有此課程');
                }

                if ($this->activeBookingFor($purchase)) {
                    return $this->refuse(422, '你已有一場尚未結束的諮詢預約，結束後才能再預約');
                }

                if (! $this->isOfferable($start)) {
                    return $this->refuse(422, '此時段無法預約，請重新選擇');
                }

                $unlimited = $this->credits->isUnlimitedFor($course, $purchase->plan);

                if (! $unlimited) {
                    // Guarded, never read-modify-write (FR-205): there is no
                    // ledger to reconcile a wrong balance against.
                    $spent = Purchase::whereKey($purchase->id)
                        ->where('bundle_balance', '>=', 1)
                        ->decrement('bundle_balance');

                    if ($spent === 0) {
                        return $this->refuse(422, '諮詢次數不足');
                    }
                }

                $lead = HighTicketLead::create([
                    'kind'          => 'credit',
                    'purchase_id'   => $purchase->id,
                    'course_id'     => $course->id,
                    'name'          => $user->real_name ?: ($user->nickname ?: $user->email),
                    'email'         => $user->email,
                    'phone'         => $user->phone,
                    'status'        => 'pending',
                    'booked_at'     => now(),
                    'confirmed_at'  => now(),
                    'credits_spent' => $unlimited ? 0 : 1,
                ]);

                // Throws SlotUnavailableException, rolling back the credit too.
                $this->slots->reserve($lead, $start, ConsultationSlotService::CREDIT_MINUTES, null);
                $this->slots->confirm($lead);

                return ['success' => true, 'status' => 200, 'lead' => $lead->fresh(), 'unlimited' => $unlimited];
            });
        } catch (SlotUnavailableException) {
            return $this->refuse(409, '該時段剛被預約，請重新選擇');
        }

        if (! $result['success']) {
            return $result;
        }

        $remaining = $result['unlimited']
            ? '不限'
            : (string) Purchase::whereKey($result['lead']->purchase_id)->value('bundle_balance');

        $this->bookings->confirmCreditBooking($result['lead'], $course, $remaining);

        return ['success' => true, 'status' => 200, 'lead' => $result['lead']->fresh()];
    }

    /**
     * Mirrors what slotsFor() offers: on the hour or half hour (FR-069) and no
     * sooner than the notice. Whether the units are free is reserve()'s job.
     */
    private function isOfferable(Carbon $start): bool
    {
        $minute = $start->copy()->timezone(ConsultationSlotService::DISPLAY_TZ)->minute;

        return in_array($minute, [0, 30], true) && $start->gte($this->notBefore());
    }

    /** @return array{success: false, status: int, message: string} */
    private function refuse(int $status, string $message): array
    {
        return ['success' => false, 'status' => $status, 'message' => $message];
    }
}
