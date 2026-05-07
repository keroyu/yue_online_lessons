<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuccessController extends Controller
{
    public function show(Request $request): Response
    {
        $orderNo = $request->query('order', '');

        if (!$orderNo) {
            abort(404);
        }

        $order = Order::where('merchant_order_no', $orderNo)
            ->with('items')
            ->first();

        if (!$order) {
            abort(404);
        }

        if ($order->status !== 'paid') {
            return Inertia::render('Payment/Success', ['waiting' => true, 'order' => null, 'isLoggedIn' => auth()->check()]);
        }

        if (auth()->check()) {
            $courseIds = $order->items->pluck('course_id')->toArray();
            app(CartService::class)->clearPurchased(auth()->id(), $courseIds);
        }

        return Inertia::render('Payment/Success', [
            'order' => [
                'merchant_order_no' => $order->merchant_order_no,
                'buyer_name'        => $order->buyer_name,
                'buyer_email'       => $order->buyer_email,
                'buyer_phone'       => $order->buyer_phone,
                'tax_id'            => $order->tax_id,
                'total_amount'      => $order->total_amount,
                'payment_gateway'   => $order->payment_gateway,
                'items'             => $order->items->map(fn ($item) => [
                    'course_name' => $item->course_name,
                    'unit_price'  => $item->unit_price,
                ])->toArray(),
            ],
            'isLoggedIn' => auth()->check(),
        ]);
    }
}
