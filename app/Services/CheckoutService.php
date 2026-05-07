<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    /**
     * Create an Order + OrderItems snapshot.
     * Determines payment gateway and sets merchant_order_no in a two-step INSERT→UPDATE.
     */
    public function createOrder(?int $userId, array $courseIds, array $buyer): Order
    {
        $courses = Course::whereIn('id', $courseIds)->get()->keyBy('id');

        foreach ($courseIds as $courseId) {
            $course = $courses->get($courseId);
            if (!$course || $course->portaly_product_id || $course->price <= 0
                || $course->status !== 'selling' || !$course->is_published) {
                throw new \RuntimeException('一或多門課程目前無法購買。');
            }
        }

        // Check for duplicate purchase by buyer_email or by user account
        $byEmail = Purchase::where('buyer_email', $buyer['email'])
            ->whereIn('course_id', $courseIds)
            ->where('status', 'paid')
            ->pluck('course_id');

        $byUserId = collect();
        $existingUser = User::where('email', $buyer['email'])->first();
        if ($existingUser) {
            $byUserId = Purchase::where('user_id', $existingUser->id)
                ->whereIn('course_id', $courseIds)
                ->where('status', 'paid')
                ->pluck('course_id');
        }

        $alreadyPurchased = $byEmail->merge($byUserId)->unique()->values()->toArray();

        if (!empty($alreadyPurchased)) {
            throw new \RuntimeException('此 Email 已購買過部分課程，無需重複購買。若需存取課程請登入帳號，或聯絡客服。');
        }

        // Determine gateway before creating the order
        $gateway = count($courseIds) === 1 && $courses->first()?->payment_gateway === 'newebpay'
            ? 'newebpay'
            : 'payuni';

        $totalAmount = $courses->sum(fn ($c) => $c->display_price);

        return DB::transaction(function () use ($userId, $courseIds, $buyer, $courses, $gateway, $totalAmount) {
            $order = Order::create([
                'user_id'           => $userId,
                'buyer_name'        => $buyer['name'],
                'buyer_email'       => $buyer['email'],
                'buyer_phone'       => $buyer['phone'],
                'total_amount'      => $totalAmount,
                'currency'          => 'TWD',
                'payment_gateway'   => $gateway,
                'merchant_order_no' => null,
                'status'            => 'pending',
            ]);

            $order->update([
                'merchant_order_no' => 'ord_' . $order->id . '_' . date('ymd'),
            ]);

            foreach ($courseIds as $courseId) {
                $course = $courses->get($courseId);
                OrderItem::create([
                    'order_id'    => $order->id,
                    'course_id'   => $courseId,
                    'course_name' => $course->name,
                    'unit_price'  => $course->display_price,
                ]);
            }

            $order->refresh();
            return $order;
        });
    }

    /**
     * Determine payment gateway from the order record.
     */
    public function routeGateway(Order $order): string
    {
        return $order->payment_gateway;
    }

    /**
     * Fulfill a paid order: find-or-create user, create Purchase records.
     * Two-layer idempotency: Layer 1 = Order.status check; Layer 2 = DB UNIQUE constraint.
     *
     * @return array<Purchase>
     */
    public function fulfillOrder(Order $order, string $gatewayTradeNo, string $gateway): array
    {
        if ($order->status === 'paid') {
            Log::info('CheckoutService: order already fulfilled', ['order_id' => $order->id]);
            return [];
        }

        $purchases = [];

        DB::transaction(function () use ($order, $gatewayTradeNo, $gateway, &$purchases) {
            $user = $this->findOrCreateUser(
                $order->buyer_email,
                $order->buyer_name,
                $order->buyer_phone
            );

            $order->update([
                'user_id'            => $user->id,
                'status'             => 'paid',
                'gateway_trade_no'   => $gatewayTradeNo,
                'webhook_received_at' => now(),
            ]);

            $order->load('items.course');

            $dripService = app(\App\Services\DripService::class);

            foreach ($order->items as $item) {
                try {
                    [$purchase, $created] = $this->firstOrCreatePurchase($user, $item, $order, $gateway);

                    if ($created) {
                        $purchases[] = $purchase;

                        if ($item->course && $item->course->course_type === 'drip') {
                            $dripService->subscribe($user, $item->course);
                        }
                        $dripService->checkAndConvert($user, $item->course);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::warning('CheckoutService: duplicate purchase skipped', [
                        'order'   => $order->id,
                        'course'  => $item->course_id,
                        'error'   => $e->getMessage(),
                    ]);
                    continue;
                }
            }
        });

        return $purchases;
    }

    private function firstOrCreatePurchase(User $user, OrderItem $item, Order $order, string $gateway): array
    {
        $existing = Purchase::where('user_id', $user->id)
            ->where('course_id', $item->course_id)
            ->first();

        if ($existing) {
            return [$existing, false];
        }

        $purchase = Purchase::create([
            'user_id'            => $user->id,
            'course_id'          => $item->course_id,
            'buyer_email'        => $order->buyer_email,
            'amount'             => $item->unit_price,
            'currency'           => 'TWD',
            'status'             => 'paid',
            'type'               => 'paid',
            'source'             => $gateway,
            'webhook_received_at' => now(),
            'order_id'           => $order->id,
        ]);

        return [$purchase, true];
    }

    private function findOrCreateUser(string $email, ?string $name, ?string $phone): User
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            $updates = [];
            if (!empty($name)) $updates['real_name'] = $name;
            if (!empty($phone)) $updates['phone'] = $phone;
            if ($updates) $user->update($updates);
            return $user;
        }

        return User::create([
            'email'     => $email,
            'real_name' => $name,
            'phone'     => $phone,
            'role'      => 'member',
        ]);
    }
}
