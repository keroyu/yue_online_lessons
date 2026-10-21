<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 011 US48 / FR-277 — resume the sequences that stopped for somebody who has
 * no confirmed booking today: refused once and later passed a re-screen, or
 * booked and then cancelled (including August's submit-time stops that were
 * never confirmed). Same rule the live code now follows, applied once.
 *
 * Plain queries rather than models so later changes to either cannot alter
 * what this one-off did.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $subscriptions = DB::table('drip_subscriptions')
            ->join('users', 'users.id', '=', 'drip_subscriptions.user_id')
            ->whereIn('drip_subscriptions.status', ['declined', 'booked'])
            ->get(['drip_subscriptions.id', 'drip_subscriptions.course_id', 'drip_subscriptions.status', 'users.email']);

        foreach ($subscriptions as $subscription) {
            $targetIds = DB::table('drip_conversion_targets')
                ->where('drip_course_id', $subscription->course_id)
                ->pluck('target_course_id');

            $leads = DB::table('high_ticket_leads')
                ->where('kind', 'application')
                ->where('email', $subscription->email)
                ->whereIn('course_id', $targetIds)
                ->orderByDesc('id')
                ->get(['status', 'confirmed_at', 'cancelled_at']);

            $latest = $leads->first();

            if ($latest === null) {
                continue;
            }

            $hasLiveBooking = $leads->contains(fn ($lead) => $lead->confirmed_at !== null && $lead->cancelled_at === null);

            $resumable = $subscription->status === 'declined'
                ? $latest->status === 'pending' && $latest->confirmed_at === null
                : $latest->status === 'cancelled';

            if ($hasLiveBooking || !$resumable) {
                continue;
            }

            DB::table('drip_subscriptions')->where('id', $subscription->id)->update([
                'status'            => 'active',
                'status_changed_at' => $now,
                'resumed_at'        => $now,
                'updated_at'        => $now,
            ]);
        }
    }

    public function down(): void
    {
        // One-off data fix; nothing to undo.
    }
};
