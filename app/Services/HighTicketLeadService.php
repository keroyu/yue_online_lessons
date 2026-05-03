<?php

namespace App\Services;

use App\Jobs\NotifyHighTicketSlotJob;
use App\Jobs\SubscribeDripLeadJob;
use App\Models\DripSubscription;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\Purchase;
use App\Models\User;
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
            ->whereIn('status', ['pending', 'closed'])
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
     * Register (or confirm) the lead as a member and grant them a course.
     * Lead status is updated to converted.
     */
    public function convertLead(HighTicketLead $lead, int $courseId): array
    {
        $user = User::firstOrCreate(
            ['email' => $lead->email],
            ['nickname' => $lead->name, 'password' => Str::password(16)]
        );

        Purchase::updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $courseId],
            [
                'buyer_email' => $lead->email ?? '',
                'amount'      => 0,
                'currency'    => 'TWD',
                'status'      => 'paid',
                'type'        => 'gift',
            ]
        );

        $lead->update(['status' => 'converted']);

        return [
            'success'      => true,
            'user_created' => $user->wasRecentlyCreated,
        ];
    }
}
