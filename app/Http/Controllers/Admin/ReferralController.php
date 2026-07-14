<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;

class ReferralController extends Controller
{
    public function __construct(private ReferralService $referralService)
    {
    }

    /**
     * Read-only referral detail for the 推薦成效 drill-down modal (US8):
     * the referrer's point ledger + own transactions + orders they referred.
     */
    public function detail(User $user): JsonResponse
    {
        return response()->json($this->referralService->referrerDetail($user));
    }
}
