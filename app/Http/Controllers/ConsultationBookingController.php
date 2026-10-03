<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsultationBookingRequest;
use App\Models\Course;
use App\Services\ConsultationCreditBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * Self-booked consultations from the sales page (011 US38).
 */
class ConsultationBookingController extends Controller
{
    public function __construct(private ConsultationCreditBookingService $bookings) {}

    /** Hour-long starts the picker can offer, refetched after a 409 (FR-226). */
    public function slots(Course $course): JsonResponse
    {
        return response()->json(['slots' => $this->bookings->slotsFor()]);
    }

    public function store(StoreConsultationBookingRequest $request, Course $course): JsonResponse
    {
        $result = $this->bookings->book(
            $request->user(),
            $course,
            Carbon::parse($request->validated('starts_at')),
        );

        if (! $result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json([
            'booking' => $this->bookings->stateFor($request->user(), $course),
        ]);
    }
}
