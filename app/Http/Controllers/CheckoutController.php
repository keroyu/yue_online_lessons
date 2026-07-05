<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Purchase;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\NewebpayService;
use App\Services\PayuniService;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function show(Request $request): Response
    {
        $user  = auth()->user();
        $items = [];
        $total = 0;

        if ($user) {
            $cartItems = app(CartService::class)->getItems($user->id);
            $items = $cartItems->map(fn ($item) => [
                'id'     => $item->id,
                'course' => [
                    'id'              => $item->course->id,
                    'name'            => $item->course->name,
                    'price'           => (float) $item->course->display_price,
                    'thumbnail'       => $item->course->thumbnail_url ?? null,
                    'payment_gateway' => $item->course->payment_gateway,
                ],
            ])->values()->all();
            $total = collect($items)->sum(fn ($i) => $i['course']['price']);
        }

        // Coupon code from ?coupon= query, fall back to session (US5). The CouponInput
        // component re-validates and applies it client-side (guest + auth alike).
        $couponCode = $request->query('coupon') ?: $request->session()->get('checkout_coupon');
        $couponCode = $couponCode ? strtoupper(trim($couponCode)) : null;

        return Inertia::render('Checkout/Index', [
            'items'      => $items,
            'total'      => $total,
            'prefill'    => [
                'name'  => $user?->real_name,
                'email' => $user?->email,
                'phone' => $user?->phone,
            ],
            'couponCode' => $couponCode,
        ]);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'        => 'required|email',
            'course_ids'   => 'required|array',
            'course_ids.*' => 'integer',
        ]);

        $email     = $data['email'];
        $courseIds = $data['course_ids'];

        $byEmail = Purchase::where('buyer_email', $email)
            ->whereIn('course_id', $courseIds)
            ->where('status', 'paid')
            ->pluck('course_id');

        $byUserId = collect();
        $user = User::where('email', $email)->first();
        if ($user) {
            $byUserId = Purchase::where('user_id', $user->id)
                ->whereIn('course_id', $courseIds)
                ->where('status', 'paid')
                ->pluck('course_id');
        }

        $purchasedIds = $byEmail->merge($byUserId)->unique()->values()->all();

        return response()->json(['purchased_course_ids' => $purchasedIds]);
    }

    public function initiate(CheckoutRequest $request): JsonResponse
    {
        $validated    = $request->validated();
        $buyer        = $validated['buyer'];
        $courseIds    = $validated['course_ids'];
        $couponCode   = $validated['coupon_code'] ?? null;
        $referralCode = $validated['referral_code'] ?? null;
        $userId       = auth()->id();

        // Validate referral code before the order is created (FR-018); block on failure.
        $referral = null;
        if ($referralCode) {
            $result = app(ReferralService::class)->validateAtCheckout($referralCode, [
                'user_id' => $userId,
                'email'   => $buyer['email'],
            ]);
            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => $result['error']], 422);
            }
            $referral = ['referrer_id' => $result['referrer']->id, 'rate' => $result['rate']];
        }

        $checkoutService = app(CheckoutService::class);
        $trafficSource = session('traffic_source', []);

        try {
            $order = $checkoutService->createOrder($userId, $courseIds, $buyer, $trafficSource, $couponCode, $referral);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        // Coupon consumed into the order — clear session attribution so it won't leak
        // into the next order (US5). Cleared in the controller, not the service.
        $request->session()->forget('checkout_coupon');

        $gateway = $checkoutService->routeGateway($order);

        if ($gateway === 'newebpay') {
            $formData = app(NewebpayService::class)->buildPaymentForm($order, $buyer);
        } else {
            $formData = app(PayuniService::class)->buildOrderPaymentForm($order, $buyer);
        }

        return response()->json(array_merge(['gateway' => $gateway], $formData));
    }
}
