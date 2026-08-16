<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\SlotUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RescheduleBookingRequest;
use App\Mail\BatchEmailMail;
use App\Models\Course;
use App\Models\EmailSuppression;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\User;
use App\Services\DripService;
use App\Services\HighTicketBookingService;
use App\Services\HighTicketLeadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        private HighTicketBookingService $bookingService,
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

        $status     = $request->query('status');
        $search     = $request->query('search');
        $courseId   = $request->query('course_id');
        $consultant = $request->query('consultant');
        $subCourse  = $request->query('sub_course');
        $subStatus  = $request->query('sub_status');

        $filters = [
            'status'     => $status,
            'search'     => $search,
            'course_id'  => $courseId,
            'consultant' => $consultant,
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

        $leads = $this->bookingLeadsQuery($search, $courseId, $consultant)
            ->with([
                'course:id,name',
                'consultant:id,nickname,email',
                'slots:id,lead_id,starts_at',
                // Joined on email, so this pulls every session this person has
                // ever had — including ones booked under a different lead
                // (011 FR-116).
                //
                // The transcript body is deliberately absent: it can run to
                // 200k characters, twenty leads to a page each carry every
                // session that person ever had, and the panel now offers it as
                // a download rather than on screen (FR-120). `LENGTH` (not
                // MySQL's `CHAR_LENGTH`) because it has to work on the sqlite
                // the tests run against, and bytes are what the download's size
                // hint wants anyway.
                'consultationNotes' => fn ($q) => $q->with('consultant:id,nickname')
                    ->select([
                        'id', 'email', 'source', 'lead_id', 'consultant_id', 'course_id',
                        'met_at', 'summary', 'summary_generated_at', 'summary_edited_at',
                        'transcript_fetched_at',
                        DB::raw('LENGTH(transcript) AS transcript_bytes'),
                    ]),
                'consultationNotes.course:id,name',
            ])
            ->when($status, fn ($q) => $q->byStatus($status))
            ->orderBy('booked_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Funnel share behind each status pill (FR-067). Counted over the whole
        // filtered set rather than the current page, and deliberately without
        // the status filter — the shares have to keep adding up to 100% after
        // the admin clicks into one status. The consultant filter *does* belong
        // in here (FR-074): sharing one builder is what keeps the pills and the
        // list from telling two different stories.
        $statusCounts = $this->bookingLeadsQuery($search, $courseId, $consultant)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();

        // Deals closed, on the same scope as the pills above and deliberately
        // without the status filter — clicking into a status must not move the
        // money (FR-097, mirrors the funnel-share rule).
        $conversionStats = $this->leadService->conversionStats(
            $this->bookingLeadsQuery($search, $courseId, $consultant)
        );

        $highTicketCourses = Course::where('type', 'high_ticket')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Same definition of "who is a consultant" as the week grid's owner
        // picker, minus its self-only restriction — this filter is a viewing
        // tool, not a permission gate (D70).
        $consultantOptions = User::where(fn ($q) => $q->where('role', 'admin')->orWhere('is_sales_consultant', true))
            ->orderBy('nickname')
            ->get(['id', 'nickname', 'email']);

        $notifyTemplate = EmailTemplate::forEvent('high_ticket_slot_available')
            ->first(['id', 'subject', 'body_md'])
            ?->toArray();

        $emails = $leads->pluck('email')->filter()->unique()->values();
        $suppressionsByEmail = EmailSuppression::reasonsFor($emails);
        $dripByEmail = User::whereIn('email', $emails)
            ->with(['dripSubscriptions' => fn ($q) => $q->with('course:id,name')])
            ->get(['id', 'email'])
            ->keyBy('email')
            ->map(fn ($u) => $u->dripSubscriptions->map(fn ($s) => [
                'course_name'   => $s->course->name ?? '?',
                'status'        => $s->status,
                'subscribed_at' => $s->subscribed_at,
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
            ->with('plans:id,course_id,name,price,sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'            => $c->id,
                'name'          => $c->name,
                'display_price' => (int) $c->display_price,
                // Empty for every course without tiers, which is what keeps the
                // plan picker out of the convert modal (011 US21).
                'plans'         => $c->plans->map(fn ($plan) => [
                    'id'    => $plan->id,
                    'name'  => $plan->name,
                    'price' => $plan->price,
                ])->values(),
            ])
            ->values();

        return Inertia::render('Admin/HighTicketLeads/Index', [
            'tab'               => $tab,
            'leads'             => $leads,
            'filters'           => $filters,
            'highTicketCourses' => $highTicketCourses,
            'consultantOptions' => $consultantOptions,
            'dripCourses'       => $dripCourses,
            'notifyTemplate'    => $notifyTemplate,
            'dripByEmail'       => $dripByEmail,
            'purchasesByEmail'  => $purchasesByEmail,
            'suppressionsByEmail' => $suppressionsByEmail,
            'grantableCourses'  => $grantableCourses,
            'dripCourseOptions' => $dripCourses,
            'subscriberData'    => null,
            'conversionStats'   => $conversionStats,
            // Cast so an empty result still reaches Vue as {} rather than [].
            'statusCounts'      => (object) $statusCounts,
        ]);
    }

    /**
     * The booking tab's search / course / consultant filter, shared by the
     * paginated list and the per-status counts so the two can never disagree
     * about which leads are in scope (FR-074).
     *
     * `$consultant` is a view parameter, not an identity: empty means every
     * consultant, `none` means the leads nobody owns (everything from before
     * US15 lives there), anything else is an id. An id that matches nobody just
     * yields an empty list — not worth a validation layer (FR-075).
     */
    private function bookingLeadsQuery(?string $search, $courseId, ?string $consultant = null): Builder
    {
        return HighTicketLead::query()
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
            ->when($consultant === 'none', fn ($q) => $q->whereNull('consultant_id'))
            ->when(
                $consultant !== null && $consultant !== '' && $consultant !== 'none',
                fn ($q) => $q->where('consultant_id', (int) $consultant),
            );
    }

    /**
     * `cancelled` is accepted here only when there is no live booking to undo
     * (FR-054, narrowed 2026-08-05).
     *
     * The original rule refused it outright, on the grounds that labelling a
     * lead cancelled without releasing the slot, deleting the Zoom meeting and
     * telling the applicant makes the status a lie. That holds for a lead that
     * actually holds a booking — but not for one that never did, and refusing
     * both left a dead end: leads carried over from the old 未回應 status have
     * no slot at all, so once nudged out of `cancelled` nothing could put them
     * back.
     */
    public function updateStatus(Request $request, HighTicketLead $lead): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,contacted,no_response,converted,closed,cancelled'],
        ]);

        if ($validated['status'] === 'cancelled' && $lead->isActiveBooking()) {
            return response()->json([
                'error' => '這筆有生效中的預約，請到「諮詢時段」點該區塊取消 —— 那裡才會釋出時段、刪除 Zoom 會議並通知對方',
            ], 422);
        }

        $lead->update($validated);

        return response()->json($lead->fresh());
    }

    /**
     * Move a confirmed booking to another slot (011 US14 / FR-048).
     *
     * Admin-only: there is no self-service path, so reaching here always means
     * somebody asked for the change in a message (D50).
     */
    public function reschedule(RescheduleBookingRequest $request, HighTicketLead $lead): RedirectResponse
    {
        try {
            $result = $this->bookingService->reschedule($lead, $request->startsAt());
        } catch (SlotUnavailableException $e) {
            // 409 rather than a flash: the grid the admin was looking at is out
            // of date and needs reloading, not a message on top of stale data.
            abort(409, $e->getMessage());
        }

        if (!$result['success']) {
            return back()->withErrors(['booking' => $result['message']]);
        }

        // The week that now holds it, not the one the admin was looking at
        // (FR-084): a cross-week move otherwise just makes the booking vanish
        // from the grid while a flash message claims success.
        return redirect()
            ->route('admin.consultation-slots.index', ['week' => $result['slot_date']])
            ->with('success', "已改期至 {$result['slot_label']}，並已寄出更新通知與行事曆邀請"
                . $this->zoomNote($result, '會議時間'));
    }

    /** Call a confirmed booking off and hand the slot back (FR-049). */
    public function cancelBooking(HighTicketLead $lead): RedirectResponse
    {
        $result = $this->bookingService->cancel($lead);

        if (!$result['success']) {
            return back()->withErrors(['booking' => $result['message']]);
        }

        return back()->with('success', '已取消預約，時段已釋出，並已寄出取消通知與行事曆更新'
            . $this->zoomNote($result, '會議刪除'));
    }

    /**
     * Zoom now runs inline (D55), so a failure is known here and there is no
     * reason to tell the admin by email — say it while they are looking.
     * Silence when the lead never had a meeting: nothing failed.
     */
    private function zoomNote(array $result, string $what): string
    {
        // Only false means a call was made and lost; null means there was
        // nothing to sync, which is not worth a warning.
        if (($result['zoom_synced'] ?? null) !== false) {
            return '';
        }

        return "。⚠️ Zoom {$what}同步失敗，請到 Zoom 後台手動處理";
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
            'course_id'      => ['required', 'integer', 'exists:courses,id'],
            'course_plan_id' => ['nullable', 'integer'],
            'amount'         => ['required', 'integer', 'min:0'],
            'force'          => ['sometimes', 'boolean'],
        ]);

        $result = $this->leadService->convertLead(
            $lead,
            $validated['course_id'],
            $validated['amount'],
            (bool) ($validated['force'] ?? false),
            $validated['course_plan_id'] ?? null,
        );

        // The plan does not match the course (or is missing on a tiered
        // course): the admin picked an impossible combination (FR-092).
        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 422);
        }

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
