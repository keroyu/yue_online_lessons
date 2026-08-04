<?php

namespace App\Http\Controllers;

use App\Services\HighTicketBookingService;
use Inertia\Inertia;
use Inertia\Response;

class BookingConfirmController extends Controller
{
    public function __construct(protected HighTicketBookingService $bookingService) {}

    /**
     * The link from the verify email (011 US11 / FR-034).
     *
     * Public on purpose — the token is the credential. All four outcomes render
     * the same page with a different state so a stale link never looks like a
     * server error, and an unknown token never reveals whether it once existed.
     */
    public function show(string $token): Response
    {
        $result = $this->bookingService->confirm($token);

        return Inertia::render('Booking/Confirm', [
            'state'      => $result['state'],
            'courseName' => $result['course']->name ?? null,
            'courseSlug' => $result['course']->slug ?? null,
            'slotLabel'  => $result['slot_label'] ?? null,
        ]);
    }
}
