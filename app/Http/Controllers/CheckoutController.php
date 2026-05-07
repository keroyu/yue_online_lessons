<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Purchase;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\NewebpayService;
use App\Services\PayuniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function show(): Response
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

        return Inertia::render('Checkout/Index', [
            'items'   => $items,
            'total'   => $total,
            'prefill' => [
                'name'  => $user?->real_name,
                'email' => $user?->email,
                'phone' => $user?->phone,
            ],
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
        $validated = $request->validated();
        $buyer     = $validated['buyer'];
        $courseIds = $validated['course_ids'];
        $userId    = auth()->id();

        $checkoutService = app(CheckoutService::class);

        try {
            $order = $checkoutService->createOrder($userId, $courseIds, $buyer);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        $gateway = $checkoutService->routeGateway($order);

        if ($gateway === 'newebpay') {
            $formData = app(NewebpayService::class)->buildPaymentForm($order, $buyer);
        } else {
            $formData = app(PayuniService::class)->buildOrderPaymentForm($order, $buyer);
        }

        return response()->json(array_merge(['gateway' => $gateway], $formData));
    }
}
