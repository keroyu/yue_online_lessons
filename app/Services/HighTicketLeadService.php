<?php

namespace App\Services;

use App\Jobs\NotifyHighTicketSlotJob;
use App\Jobs\SubscribeDripLeadJob;
use App\Mail\TemplatedMail;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\DripSubscription;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\Purchase;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class HighTicketLeadService
{
    /**
     * Dispatch slot-available notification emails to the given leads.
     *
     * Any status is allowed: a new slot is worth telling a contacted, silent
     * or even closed lead about, and the admin picking the rows is a better
     * judge of that than a whitelist.
     *
     * @param array $leadIds
     * @return array{dispatched: int}|array{success: false, error: string}
     */
    public function notifySlot(array $leadIds): array
    {
        $template = EmailTemplate::forEvent('high_ticket_slot_available')->first();

        if (!$template) {
            return ['success' => false, 'error' => '新時段通知 Email 模板不存在，請先建立 high_ticket_slot_available 模板'];
        }

        $leads = HighTicketLead::whereIn('id', $leadIds)->get();

        foreach ($leads as $lead) {
            NotifyHighTicketSlotJob::dispatch($lead->id, $template->id);
        }

        return ['dispatched' => $leads->count()];
    }

    /**
     * Dispatch drip subscription jobs for the given leads.
     * Leads with an existing active drip subscription for any course are skipped.
     *
     * @param array $leadIds
     * @param int $dripCourseId
     * @return array{dispatched: int, skipped: int}
     */
    public function subscribeDrip(array $leadIds, int $dripCourseId): array
    {
        // No status whitelist (2026-08-05, FR-007 amended): the recipients are
        // whoever the admin ticked, exactly as for notifySlot (FR-006). The
        // guard that matters is the duplicate-subscription check below —
        // status told us nothing about whether the sequence would be a mistake,
        // it just quietly dropped 已面談 and 已取消 leads on the floor.
        $leads = HighTicketLead::whereIn('id', $leadIds)->get();

        $dispatched = 0;
        $skipped = 0;

        foreach ($leads as $lead) {
            $existingUser = User::where('email', $lead->email)->first();

            if ($existingUser) {
                $hasActiveSub = DripSubscription::where('user_id', $existingUser->id)
                    ->where('status', 'active')
                    ->exists();

                if ($hasActiveSub) {
                    $skipped++;
                    continue;
                }
            }

            SubscribeDripLeadJob::dispatch($lead->id, $dripCourseId);
            $dispatched++;
        }

        return ['dispatched' => $dispatched, 'skipped' => $skipped];
    }

    /**
     * Deals closed this month and this year, for the leads currently in scope
     * (011 US22).
     *
     * The caller passes the *same* builder that feeds the list and the status
     * pills, which is what keeps all three from telling different stories
     * (FR-097). `purchases` has no consultant_id and no back-reference to the
     * lead it came from — email is the only join available.
     *
     * @param Builder $leadsQuery the filtered lead scope, WITHOUT the status filter
     * @return array{month: array{people: int, amount: int}, year: array{people: int, amount: int}}
     */
    public function conversionStats(Builder $leadsQuery): array
    {
        $emails = (clone $leadsQuery)->pluck('email')
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return [
                'month' => ['people' => 0, 'amount' => 0],
                'year'  => ['people' => 0, 'amount' => 0],
            ];
        }

        // Boundaries are Taipei's, not the server's: a deal closed at 07:00
        // Taipei on the 1st is still the previous month in UTC, and a naive
        // whereMonth() on the stored column would file it there (FR-098).
        $now = now(ConsultationSlotService::DISPLAY_TZ);

        return [
            'month' => $this->conversionTotals($emails, $now->copy()->startOfMonth(), $now->copy()->startOfMonth()->addMonth()),
            'year'  => $this->conversionTotals($emails, $now->copy()->startOfYear(), $now->copy()->startOfYear()->addYear()),
        ];
    }

    /**
     * Resolve a meeting-time quick filter key into a half-open [from, until)
     * pair of UTC instants (011 US28 / FR-144).
     *
     * Boundaries are Taipei calendar days, for the same reason the deal
     * summary's are (FR-098): the column holds UTC, and a session at 00:30
     * Taipei is 16:30 UTC the previous day.
     *
     * The upper bound is tomorrow 00:00 rather than `now()` on purpose. A
     * rolling "minus N hours" window would leave the 15:00 consultation out of
     * this morning's list and in this afternoon's — the same button giving two
     * different working lists on the same day. It also makes `today` a strict
     * subset of `7d`, which is what lets the two buttons be read as one scale.
     *
     * An unknown key yields null (= do not filter). This is an interface key,
     * not user data: turning a typo into an empty list would read as "nobody
     * was met this week".
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}|null
     */
    public function metRange(?string $preset): ?array
    {
        $today = now(ConsultationSlotService::DISPLAY_TZ)->startOfDay();
        $until = $today->copy()->addDay();

        $from = match ($preset) {
            'today' => $today,
            '7d'    => $today->copy()->subDays(6),
            default => null,
        };

        return $from === null ? null : [$from->utc(), $until->utc()];
    }

    /**
     * People and money between two Taipei instants, half-open [from, until).
     *
     * People are counted per email rather than per purchase (D86): one buyer
     * taking two courses is one customer. The two figures therefore rest on
     * different bases and MUST NOT be divided into an "average deal size".
     *
     * @return array{people: int, amount: int}
     */
    private function conversionTotals($emails, CarbonInterface $from, CarbonInterface $until): array
    {
        $row = Purchase::query()
            ->join('users', 'users.id', '=', 'purchases.user_id')
            ->whereIn('users.email', $emails)
            ->where('purchases.type', 'lead_conversion')
            // A refunded sale is a voided one — it is not revenue (FR-099).
            ->where('purchases.status', 'paid')
            ->where('purchases.created_at', '>=', $from->copy()->utc())
            ->where('purchases.created_at', '<', $until->copy()->utc())
            ->selectRaw('count(distinct users.email) as people, coalesce(sum(purchases.amount), 0) as amount')
            ->first();

        return [
            'people' => (int) ($row->people ?? 0),
            'amount' => (int) ($row->amount ?? 0),
        ];
    }

    /**
     * Purchase types this conversion may overwrite without asking. A whitelist
     * rather than a blacklist because `purchases.type` keeps growing, and a
     * forgotten entry there is silent data loss (D14).
     */
    private const OVERWRITABLE_TYPES = ['lead_conversion'];

    /**
     * Register (or confirm) the lead as a member and grant them a course.
     * Lead status is updated to converted.
     *
     * $amount is the actual deal price entered by the admin (may differ from
     * the listed price for offline deals); it counts toward revenue stats.
     *
     * Since D13 this is the only way a high-ticket sale enters the system, so
     * it carries the same guarantees as the payment gateway path: no silent
     * overwrite of someone else's purchase (FR-015), all-or-nothing writes
     * (FR-016), and a confirmation mail to the buyer (FR-017).
     *
     * $coursePlanId picks the tier being sold on a multi-plan course; null
     * means the whole course, which is also the only legal value for a course
     * with no plans (011 FR-087).
     *
     * @return array{success: true, user_created: bool, mail_sent: bool}
     *   |array{success: false, conflict: array{type: string, amount: int, status: string}}
     *   |array{success: false, error: string}
     */
    public function convertLead(
        HighTicketLead $lead,
        int $courseId,
        int $amount,
        bool $force = false,
        ?int $coursePlanId = null,
    ): array {
        $course = Course::find($courseId);
        $plan = $this->resolvePlan($course, $coursePlanId);

        // A string error rather than a conflict: this is the admin picking an
        // impossible combination, not a collision with someone else's record.
        if (is_string($plan)) {
            return ['success' => false, 'error' => $plan];
        }

        $existingUser = User::where('email', $lead->email)->first();

        // Read-only gate, deliberately before the transaction: a blocked
        // conversion must not leave a user row behind either.
        if ($existingUser && !$force) {
            $existing = Purchase::where('user_id', $existingUser->id)
                ->where('course_id', $courseId)
                ->first();

            if ($existing && !$this->isOverwritable($existing)) {
                return [
                    'success'  => false,
                    'conflict' => [
                        'type'   => $existing->type,
                        'amount' => (int) $existing->amount,
                        'status' => $existing->status,
                    ],
                ];
            }
        }

        $user = DB::transaction(function () use ($lead, $courseId, $amount, $coursePlanId) {
            // The application questionnaire is the only place we ever asked for
            // a phone number, so a brand-new member account inherits it (011
            // US9). firstOrCreate means an existing member keeps whatever they
            // already have — converting a lead must not rewrite member data.
            $user = User::firstOrCreate(
                ['email' => $lead->email],
                [
                    'nickname' => $lead->name,
                    'phone'    => $lead->phone,
                    'password' => Str::password(16),
                ]
            );

            Purchase::updateOrCreate(
                ['user_id' => $user->id, 'course_id' => $courseId],
                [
                    'course_plan_id' => $coursePlanId,
                    'buyer_email' => $lead->email ?? '',
                    'amount'      => $amount,
                    'currency'    => 'TWD',
                    'status'      => 'paid',
                    'type'        => 'lead_conversion',
                ]
            );

            $lead->update(['status' => 'converted']);

            return $user;
        });

        // Everything below is an external side effect: it must not roll the
        // sale back, and it must not be rolled back with it (FR-016).

        // A consultant-closed deal is a real sale: end any drip funnel pointing
        // at this course (010 US13). Best-effort, never blocks the conversion.
        try {
            if ($course) {
                app(DripService::class)->checkAndConvert($user, $course);
            }
        } catch (\Exception $e) {
            Log::error('Lead conversion: drip conversion failed', [
                'lead_id'   => $lead->id,
                'course_id' => $courseId,
                'error'     => $e->getMessage(),
            ]);
        }

        return [
            'success'      => true,
            'user_created' => $user->wasRecentlyCreated,
            'mail_sent'    => $this->sendConversionMail($lead, $course, $amount, $plan),
        ];
    }

    /**
     * Validate the chosen tier against the course (FR-092).
     *
     * The guard lives here rather than in the Form Request because "this course
     * has plans, so one must be chosen" is a rule about the sale, and the front
     * end's required-field is only a hint.
     *
     * @return CoursePlan|null|string  the plan, null for a whole-course sale,
     *                                 or an error message
     */
    private function resolvePlan(?Course $course, ?int $coursePlanId): CoursePlan|null|string
    {
        if (!$course) {
            return null;
        }

        $hasPlans = $course->plans()->exists();

        if ($coursePlanId === null) {
            return $hasPlans ? '此課程已設定方案，請選擇要開通的方案' : null;
        }

        $plan = CoursePlan::where('id', $coursePlanId)
            ->where('course_id', $course->id)
            ->first();

        return $plan ?: '所選方案不屬於此課程';
    }

    /**
     * A refunded purchase is a voided entitlement — re-selling it is the normal
     * case, not an overwrite worth warning about (D14, mirrors 008's gifting).
     */
    private function isOverwritable(Purchase $purchase): bool
    {
        return in_array($purchase->type, self::OVERWRITABLE_TYPES, true)
            || $purchase->status === 'refunded';
    }

    /**
     * Tell the buyer their access is live and how to log in (FR-017). No CC:
     * the admin triggered this and already knows, unlike a new booking.
     *
     * A missing template does not block the sale and gets no hardcoded
     * fallback — money already changed hands, but a stranger who just wired a
     * five-figure sum should not receive a mail nobody wrote (D15).
     */
    private function sendConversionMail(HighTicketLead $lead, ?Course $course, int $amount, ?CoursePlan $plan = null): bool
    {
        $template = EmailTemplate::forEvent('lead_converted')->first();

        if (!$template || !$lead->email) {
            Log::warning('Lead conversion: notification not sent', [
                'lead_id' => $lead->id,
                'reason'  => $template ? 'lead has no email' : 'lead_converted template missing',
            ]);

            return false;
        }

        // The plan rides along inside {{course_name}} rather than as a sixth
        // variable: someone who just wired a five-figure sum has to be able to
        // read what they bought without the template being edited first.
        $courseName = $course?->name ?? '';

        if ($plan) {
            $courseName .= "（{$plan->name}）";
        }

        $vars = [
            '{{user_name}}'     => $lead->name,
            '{{course_name}}'   => $courseName,
            '{{amount}}'        => number_format($amount),
            '{{classroom_url}}' => config('app.url') . '/member/classroom/' . ($course?->id ?? ''),
            '{{app_url}}'       => config('app.url'),
        ];

        try {
            Mail::to($lead->email)->send(new TemplatedMail(
                $template->renderSubject($vars),
                $template->renderBody($vars),
                $template->renderText($vars),
            ));

            return true;
        } catch (\Exception $e) {
            Log::error('Lead conversion: notification email failed', [
                'lead_id' => $lead->id,
                'email'   => $lead->email,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }
}
