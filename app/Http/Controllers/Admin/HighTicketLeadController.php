<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BatchEmailMail;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\User;
use App\Services\DripService;
use App\Services\HighTicketLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class HighTicketLeadController extends Controller
{
    private const PURCHASE_TYPE_LABELS = [
        'paid'            => '線上付款',
        'gift'            => '贈送',
        'system_assigned' => '系統開通',
        'lead_conversion' => '顧問轉換',
    ];

    /** Tabs of the leads admin page (US8). Anything else falls back to booking. */
    private const TABS = ['booking', 'subscribers'];

    public function __construct(
        private HighTicketLeadService $leadService,
        private DripService $dripService,
    ) {}

    /**
     * Hosts both lists behind ?tab=: the booking leads (default) and the drip
     * subscribers of one selected drip course. Only the active tab's data is
     * assembled — the subscriber side runs per-lesson aggregates that should
     * not be paid for on every visit to the leads page (FR-022 / D24).
     */
    public function index(Request $request): Response
    {
        $tab = $request->query('tab');
        $tab = in_array($tab, self::TABS, true) ? $tab : 'booking';

        $status    = $request->query('status');
        $search    = $request->query('search');
        $courseId  = $request->query('course_id');
        $subCourse = $request->query('sub_course');
        $subStatus = $request->query('sub_status');

        $filters = [
            'status'     => $status,
            'search'     => $search,
            'course_id'  => $courseId,
            'sub_course' => $subCourse ? (int) $subCourse : null,
            'sub_status' => $subStatus,
        ];

        $dripCourses = Course::drip()
            ->select('id', 'name')
            ->ordered()
            ->get();

        if ($tab === 'subscribers') {
            // Only ever read subscribers of a course that is actually drip —
            // an arbitrary sub_course must not expose other courses' data (FR-023).
            $selected = $dripCourses->firstWhere('id', $filters['sub_course']) ?? $dripCourses->first();

            $subscriberData = $selected
                ? $this->dripService->subscriberPageData(
                    Course::findOrFail($selected->id),
                    $subStatus,
                )
                : null;

            $filters['sub_course'] = $selected?->id;

            return Inertia::render('Admin/HighTicketLeads/Index', [
                'tab'               => $tab,
                'filters'           => $filters,
                'dripCourseOptions' => $dripCourses,
                'subscriberData'    => $subscriberData,
                'leads'             => null,
            ]);
        }

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

        $notifyTemplate = EmailTemplate::forEvent('high_ticket_slot_available')
            ->first(['id', 'subject', 'body_md'])
            ?->toArray();

        $emails = $leads->pluck('email')->filter()->unique()->values();
        $dripByEmail = User::whereIn('email', $emails)
            ->with(['dripSubscriptions' => fn ($q) => $q->with('course:id,name')])
            ->get(['id', 'email'])
            ->keyBy('email')
            ->map(fn ($u) => $u->dripSubscriptions->map(fn ($s) => [
                'course_name' => $s->course->name ?? '?',
                'status'      => $s->status,
            ])->values());

        // Feeds the convert modal's "already owns this" warning (D16) — same
        // single-query, compare-in-the-browser shape as dripByEmail above.
        $purchasesByEmail = User::whereIn('email', $emails)
            ->with(['purchases:id,user_id,course_id,type,amount,status'])
            ->get(['id', 'email'])
            ->keyBy('email')
            ->map(fn ($u) => $u->purchases->map(fn ($p) => [
                'course_id' => $p->course_id,
                'type'      => $p->type,
                'amount'    => (int) $p->amount,
                'status'    => $p->status,
            ])->values());

        // display_price feeds the convert modal's default deal amount (FR-011).
        $grantableCourses = Course::select('id', 'name', 'price', 'original_price', 'promo_ends_at')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'            => $c->id,
                'name'          => $c->name,
                'display_price' => (int) $c->display_price,
            ])
            ->values();

        return Inertia::render('Admin/HighTicketLeads/Index', [
            'tab'               => $tab,
            'leads'             => $leads,
            'filters'           => $filters,
            'highTicketCourses' => $highTicketCourses,
            'dripCourses'       => $dripCourses,
            'notifyTemplate'    => $notifyTemplate,
            'dripByEmail'       => $dripByEmail,
            'purchasesByEmail'  => $purchasesByEmail,
            'grantableCourses'  => $grantableCourses,
            'dripCourseOptions' => $dripCourses,
            'subscriberData'    => null,
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

    public function convert(Request $request, HighTicketLead $lead): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'amount'    => ['required', 'integer', 'min:0'],
            'force'     => ['sometimes', 'boolean'],
        ]);

        $result = $this->leadService->convertLead(
            $lead,
            $validated['course_id'],
            $validated['amount'],
            (bool) ($validated['force'] ?? false),
        );

        // 409: the buyer already owns this course through another channel, and
        // overwriting would rewrite both the transaction type and its amount
        // with no way back (FR-015).
        if (isset($result['conflict'])) {
            $conflict = $result['conflict'];
            $label = self::PURCHASE_TYPE_LABELS[$conflict['type']] ?? $conflict['type'];

            return response()->json([
                'error'    => "此會員已有本課程的購買紀錄（{$label}，NT$ " . number_format($conflict['amount']) . '），開通會覆寫該筆紀錄的類型與金額。確認要覆寫請勾選下方確認框。',
                'conflict' => $conflict,
            ], 409);
        }

        return response()->json($result);
    }
}
