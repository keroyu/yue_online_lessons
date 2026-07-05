<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\PointService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PointController extends Controller
{
    public function __construct(private PointService $pointService) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        // Fold any just-matured referral rewards before reading the cache (matured == available).
        $available = $this->pointService->availableBalance($user);

        $transactions = $user->pointTransactions()
            ->paginate(20)
            ->through(fn ($tx) => [
                'created_at'   => $tx->created_at->toIso8601String(),
                'type'         => $tx->type,
                'amount'       => $tx->amount,
                'note'         => $tx->note,
                'available_at' => $tx->available_at->toIso8601String(),
                'is_matured'   => $tx->available_at->lessThanOrEqualTo(now()),
            ]);

        return Inertia::render('Member/Points', [
            'available'       => $available,
            'pending'         => $user->pendingPoints(),
            'referralCode'    => $user->referral_code,
            'referralActive'  => $user->isReferralActive(),
            'thresholdAmount' => (int) SiteSetting::get('referral_threshold_amount', 3000),
            'rewardRate'      => (int) SiteSetting::get('referral_reward_rate', 10),
            'transactions'    => $transactions,
        ]);
    }
}
