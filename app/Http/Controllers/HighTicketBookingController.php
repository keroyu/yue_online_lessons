<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\HighTicketBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HighTicketBookingController extends Controller
{
    public function __construct(protected HighTicketBookingService $bookingService) {}

    public function store(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $result = $this->bookingService->book($course, $validated);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json(['success' => true]);
    }
}
