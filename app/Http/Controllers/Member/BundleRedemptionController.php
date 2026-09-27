<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\BundleCreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Buy one more bundle credit with points (011 US37 / FR-206–FR-207).
 *
 * The purchase is the subject rather than the course: the balance lives on that
 * row, and "does this member own the course" is answered by the row existing.
 */
class BundleRedemptionController extends Controller
{
    public function store(Request $request, Purchase $purchase, BundleCreditService $credits): RedirectResponse
    {
        // Route model binding proves the purchase exists, nothing more — this is
        // a public endpoint that spends somebody's points (FR-207).
        if ($purchase->user_id !== $request->user()->id || $purchase->status !== 'paid') {
            abort(403, '此購買紀錄不屬於您');
        }

        $result = $credits->redeemWithPoints($purchase);

        if (! $result['success']) {
            // 422 is the misconfigured-course case (no perk / no price); a short
            // balance is an ordinary form error the card can show inline.
            if (($result['status'] ?? null) === 422) {
                abort(422, $result['error']);
            }

            return back()->withErrors(['bundle' => $result['error']]);
        }

        return back()->with('success', "已加購 1 次 {$purchase->course->bundle_name}");
    }
}
