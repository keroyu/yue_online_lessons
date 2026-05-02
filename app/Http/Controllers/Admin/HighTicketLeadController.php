<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Services\HighTicketLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HighTicketLeadController extends Controller
{
    public function __construct(private HighTicketLeadService $leadService) {}

    public function index(Request $request): Response
    {
        $status = $request->query('status');

        $leads = HighTicketLead::with('course:id,name')
            ->when($status, fn ($q) => $q->byStatus($status))
            ->orderBy('booked_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $dripCourses = Course::where('course_type', 'drip')
            ->select('id', 'name')
            ->ordered()
            ->get();

        $notifyTemplate = EmailTemplate::forEvent('high_ticket_slot_available')
            ->first()
            ?->only(['id', 'subject', 'body_md']);

        return Inertia::render('Admin/HighTicketLeads/Index', [
            'leads' => $leads,
            'filters' => ['status' => $status],
            'dripCourses' => $dripCourses,
            'notifyTemplate' => $notifyTemplate,
        ]);
    }

    public function updateStatus(Request $request, HighTicketLead $lead): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,contacted,converted,closed'],
        ]);

        $lead->update($validated);

        return response()->json($lead->fresh());
    }

    public function notifySlot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_ids' => ['required', 'array'],
            'lead_ids.*' => ['integer'],
        ]);

        $result = $this->leadService->notifySlot($validated['lead_ids']);

        if (isset($result['success']) && !$result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json($result);
    }

    public function subscribeDrip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_ids' => ['required', 'array'],
            'lead_ids.*' => ['integer'],
            'drip_course_id' => ['required', 'integer'],
        ]);

        $result = $this->leadService->subscribeDrip(
            $validated['lead_ids'],
            $validated['drip_course_id']
        );

        return response()->json($result);
    }
}
