<?php

namespace App\Http\Controllers;

use App\Exceptions\SlotUnavailableException;
use App\Http\Requests\HighTicketBookingRequest;
use App\Models\Course;
use App\Services\ConsultationSlotService;
use App\Services\HighTicketBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HighTicketBookingController extends Controller
{
    public function __construct(
        protected HighTicketBookingService $bookingService,
        protected ConsultationSlotService $slots,
    ) {}

    /**
     * Start times the wizard can offer, grouped by day (011 US10).
     *
     * Re-requested whenever the bonus code changes, because a longer session
     * needs more consecutive units and therefore has fewer valid starts.
     */
    public function slots(Request $request, Course $course): JsonResponse
    {
        $code = $request->query('code');
        $minutes = $this->slots->minutesFor($code);

        $groups = [];

        foreach ($this->slots->availableStarts($minutes) as $start) {
            $date = $this->slots->dateLabel($start);

            $groups[$date] ??= ['date' => $date, 'times' => []];
            $groups[$date]['times'][] = [
                'value' => $start->toIso8601String(),
                'label' => $start->timezone(ConsultationSlotService::DISPLAY_TZ)->format('H:i'),
            ];
        }

        return response()->json([
            'minutes'      => $minutes,
            'code_applied' => $this->slots->codeIsValid($code),
            'slots'        => array_values($groups),
        ]);
    }

    public function store(HighTicketBookingRequest $request, Course $course): JsonResponse
    {
        try {
            $result = $this->bookingService->apply($course, $request->validated());
        } catch (SlotUnavailableException $e) {
            // 409 so the wizard knows to refetch the list rather than showing a
            // generic error the visitor can do nothing about (FR-032).
            return response()->json(['message' => $e->getMessage()], 409);
        }

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json([
            'success'         => true,
            'mail_sent'       => $result['mail_sent'],
            'hold_expires_at' => $result['hold_expires_at'],
            'slot_label'      => $result['slot_label'],
            'minutes'         => $result['minutes'],
        ]);
    }

    /**
     * Application with no slot to pick — the consultant has not opened any yet
     * (011 US10). Keeps the lead so 「通知新時段」has somebody to notify.
     */
    public function waitlist(HighTicketBookingRequest $request, Course $course): JsonResponse
    {
        $result = $this->bookingService->waitlist($course, $request->validated());

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json(['success' => true, 'waitlisted' => true]);
    }
}
