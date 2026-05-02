<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BatchEmailMail;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Services\HighTicketLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class HighTicketLeadController extends Controller
{
    public function __construct(private HighTicketLeadService $leadService) {}

    public function index(Request $request): Response
    {
        $status   = $request->query('status');
        $search   = $request->query('search');
        $courseId = $request->query('course_id');

        $leads = HighTicketLead::with('course:id,name')
            ->when($status, fn ($q) => $q->byStatus($status))
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
            ->orderBy('booked_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $highTicketCourses = Course::where('type', 'high_ticket')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $dripCourses = Course::where('course_type', 'drip')
            ->select('id', 'name')
            ->ordered()
            ->get();

        $notifyTemplate = EmailTemplate::forEvent('high_ticket_slot_available')
            ->first()
            ?->only(['id', 'subject', 'body_md']);

        return Inertia::render('Admin/HighTicketLeads/Index', [
            'leads'             => $leads,
            'filters'           => ['status' => $status, 'search' => $search, 'course_id' => $courseId],
            'highTicketCourses' => $highTicketCourses,
            'dripCourses'       => $dripCourses,
            'notifyTemplate'    => $notifyTemplate,
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

    public function batchEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1'],
            'lead_ids.*' => ['integer'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $leads = HighTicketLead::whereIn('id', $validated['lead_ids'])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($leads->isEmpty()) {
            return response()->json(['message' => '沒有可發送郵件的 Lead'], 422);
        }

        $sentCount = 0;
        foreach ($leads as $lead) {
            try {
                Mail::to($lead->email)->send(new BatchEmailMail($validated['subject'], $validated['body']));
                $sentCount++;
            } catch (\Exception $e) {
                Log::error('Failed to send lead batch email', [
                    'lead_id' => $lead->id,
                    'email' => $lead->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "已發送 {$sentCount} 封郵件",
            'sent_count' => $sentCount,
        ]);
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
