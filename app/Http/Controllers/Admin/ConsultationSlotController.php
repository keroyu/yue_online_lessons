<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyConsultationSlotsRequest;
use App\Http\Requests\Admin\StoreConsultationSlotsRequest;
use App\Http\Requests\Admin\UpdateConsultationSettingsRequest;
use App\Models\ConsultationSlot;
use App\Models\SiteSetting;
use App\Services\ConsultationSlotService;
use App\Services\HighTicketBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationSlotController extends Controller
{
    public function __construct(protected ConsultationSlotService $slots) {}

    /**
     * The consultant's calendar as a week grid (011 US13).
     *
     * All the shaping lives in the service — this only decides which week.
     */
    public function index(Request $request): Response
    {
        return Inertia::render(
            'Admin/ConsultationSlots/Index',
            array_merge($this->slots->weekView($request->query('week')), [
                'bonusCodes' => (string) SiteSetting::get(ConsultationSlotService::BONUS_CODES_KEY, ''),
            ])
        );
    }

    /**
     * The codes that buy an extra 15 minutes (FR-031). They live on this page
     * because consultation length is what they change, not because they are
     * site config in general.
     */
    public function updateSettings(UpdateConsultationSettingsRequest $request): RedirectResponse
    {
        // Normalised on the way in so the field reads back the way the next
        // admin would have typed it, whatever separator they used.
        $codes = HighTicketBookingService::parseRecipients((string) $request->input('bonus_codes', ''));

        SiteSetting::set(ConsultationSlotService::BONUS_CODES_KEY, implode(', ', $codes));

        return back()->with('success', $codes === []
            ? '已清空預約優惠碼，所有諮詢一律 30 分鐘'
            : '預約優惠碼已更新（共 ' . count($codes) . ' 組）');
    }

    public function store(StoreConsultationSlotsRequest $request): RedirectResponse
    {
        [$from, $to] = $this->range($request);

        $result = $this->slots->generate($from, $to);

        return back()->with('success', "已釋出 {$result['created']} 個時段、略過 {$result['skipped']} 個已存在的時段");
    }

    /**
     * Reclaim a dragged range (011 FR-044). Occupied units survive — see
     * destroy() for why pulling one out from under a booking is not allowed.
     */
    public function destroyRange(DestroyConsultationSlotsRequest $request): RedirectResponse
    {
        [$from, $to] = $this->range($request);

        $result = $this->slots->releaseRange($from, $to);

        $message = "已收回 {$result['released']} 個時段";

        if ($result['skipped'] > 0) {
            $message .= "、略過 {$result['skipped']} 個已被預約的時段";
        }

        return back()->with('success', $message);
    }

    /** The dragged range as two instants; end is exclusive. */
    private function range(Request $request): array
    {
        $tz = ConsultationSlotService::DISPLAY_TZ;
        $date = $request->input('date');

        return [
            Carbon::parse("{$date} {$request->input('start_time')}", $tz),
            Carbon::parse("{$date} {$request->input('end_time')}", $tz),
        ];
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
}
