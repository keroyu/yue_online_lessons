<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreConsultationSlotsRequest;
use App\Models\ConsultationSlot;
use App\Services\ConsultationSlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationSlotController extends Controller
{
    public function __construct(protected ConsultationSlotService $slots) {}

    /**
     * The consultant's calendar, grouped by day (011 US10).
     */
    public function index(): Response
    {
        $rows = ConsultationSlot::with('lead:id,name,email')
            ->where('starts_at', '>=', now()->subDay())
            ->orderBy('starts_at')
            ->get();

        $groups = [];

        foreach ($rows as $row) {
            $date = $this->slots->dateLabel($row->starts_at);

            $groups[$date] ??= ['date' => $date, 'slots' => []];
            $groups[$date]['slots'][] = [
                'id'       => $row->id,
                'time'     => $row->starts_at->timezone(ConsultationSlotService::DISPLAY_TZ)->format('H:i'),
                'state'    => $this->state($row),
                'lead'     => $row->lead ? [
                    'name'  => $row->lead->name,
                    'email' => $row->lead->email,
                ] : null,
            ];
        }

        return Inertia::render('Admin/ConsultationSlots/Index', [
            'groups' => array_values($groups),
        ]);
    }

    public function store(StoreConsultationSlotsRequest $request): RedirectResponse
    {
        $tz = ConsultationSlotService::DISPLAY_TZ;
        $date = $request->input('date');

        $from = Carbon::parse("{$date} {$request->input('start_time')}", $tz);
        $to = Carbon::parse("{$date} {$request->input('end_time')}", $tz);

        $result = $this->slots->generate($from, $to);

        return back()->with('success', "已新增 {$result['created']} 個時段、略過 {$result['skipped']} 個已存在的時段");
    }

    /**
     * Only unclaimed units may be deleted — pulling the rug from under a
     * confirmed booking would leave the visitor with a calendar invite for a
     * slot the system no longer knows about.
     */
    public function destroy(ConsultationSlot $consultationSlot): RedirectResponse
    {
        if (!$consultationSlot->isAvailable()) {
            return back()->withErrors([
                'slot' => '此時段已被預約，請先到 Leads 名單處理該筆預約再刪除',
            ]);
        }

        $consultationSlot->delete();

        return back()->with('success', '時段已刪除');
    }

    /** available / held / booked — mirrors the derivation in FR-029. */
    private function state(ConsultationSlot $slot): string
    {
        if ($slot->isAvailable()) {
            return 'available';
        }

        return $slot->held_until === null ? 'booked' : 'held';
    }
}
