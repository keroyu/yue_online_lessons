<?php

namespace App\Services;

use App\Jobs\NotifyHighTicketSlotJob;
use App\Jobs\SubscribeDripLeadJob;
use App\Mail\TemplatedMail;
use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class HighTicketLeadService
{
    /**
     * Dispatch slot-available notification emails to pending leads.
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

        $leads = HighTicketLead::whereIn('id', $leadIds)
            ->where('status', 'pending')
            ->get();

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
        $leads = HighTicketLead::whereIn('id', $leadIds)
            ->whereIn('status', ['pending', 'no_response', 'closed'])
            ->get();

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
     * @return array{success: true, user_created: bool, mail_sent: bool}
     *   |array{success: false, conflict: array{type: string, amount: int, status: string}}
     */
    public function convertLead(HighTicketLead $lead, int $courseId, int $amount, bool $force = false): array
    {
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

        $user = DB::transaction(function () use ($lead, $courseId, $amount) {
            $user = User::firstOrCreate(
                ['email' => $lead->email],
                ['nickname' => $lead->name, 'password' => Str::password(16)]
            );

            Purchase::updateOrCreate(
                ['user_id' => $user->id, 'course_id' => $courseId],
                [
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

        $course = Course::find($courseId);

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
            'mail_sent'    => $this->sendConversionMail($lead, $course, $amount),
        ];
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
    private function sendConversionMail(HighTicketLead $lead, ?Course $course, int $amount): bool
    {
        $template = EmailTemplate::forEvent('lead_converted')->first();

        if (!$template || !$lead->email) {
            Log::warning('Lead conversion: notification not sent', [
                'lead_id' => $lead->id,
                'reason'  => $template ? 'lead has no email' : 'lead_converted template missing',
            ]);

            return false;
        }

        $vars = [
            '{{user_name}}'     => $lead->name,
            '{{course_name}}'   => $course?->name ?? '',
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
