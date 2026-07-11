<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    /**
     * Create an Order + OrderItems snapshot.
     * Determines payment gateway and sets merchant_order_no in a two-step INSERT→UPDATE.
     *
     * @param array<string, ?string> $trafficSource 來源資料；keys: utm_source, utm_medium, utm_campaign, utm_term, utm_content, referrer_domain, gclid, fbclid, ttclid
     */
    /**
     * @param  array{referrer_id: int, rate: int}|null  $referral  validated referral snapshot (US2)
     */
    public function createOrder(?int $userId, array $courseIds, array $buyer, array $trafficSource = [], ?string $couponCode = null, ?array $referral = null): Order
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

        $subtotal = (int) round($courses->sum(fn ($c) => $c->display_price));

        // Apply discount coupon (if any). Discount is computed once on the order subtotal.
        // Final re-validation here blocks payment if the coupon went invalid (FR-012).
        $couponFields = [
            'coupon_code'     => null,
            'original_amount' => null,
            'discount_amount' => 0,
        ];
        $payable = $subtotal;

        $couponCode = $couponCode ? strtoupper(trim($couponCode)) : null;
        if ($couponCode) {
            $result = app(CouponService::class)->validateForCart($couponCode, $courseIds, $subtotal);
            if (!$result['success']) {
                throw new \RuntimeException($result['error']);
            }
            $couponFields = [
                'coupon_code'     => $result['code'],
                'original_amount' => $subtotal,
                'discount_amount' => $result['discount'],
            ];
            $payable = $result['payable'];
        }

        // Referral snapshot (US2) + buyer discount (US7). Discount applies after the
        // coupon, clamped so at least NT$1 remains payable (mirrors MIN_PAYABLE);
        // reward is estimated on the discounted payable, fulfillOrder recomputes.
        $referralFields = [
            'referrer_user_id'         => null,
            'referral_rate'            => null,
            'referral_reward_points'   => 0,
            'referral_discount_amount' => 0,
        ];
        if ($referral) {
            $rate = (int) $referral['rate'];
            $referralDiscount = max(0, min((int) ($referral['discount'] ?? 0), $payable - 1));
            $payable -= $referralDiscount;
            $referralFields = [
                'referrer_user_id'         => (int) $referral['referrer_id'],
                'referral_rate'            => $rate,
                'referral_reward_points'   => app(ReferralService::class)->computeReward($payable, $rate),
                'referral_discount_amount' => $referralDiscount,
            ];
        }

        $sourceKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'referrer_domain', 'gclid', 'fbclid', 'ttclid'];

        return DB::transaction(function () use ($userId, $courseIds, $buyer, $courses, $gateway, $payable, $couponFields, $referralFields, $trafficSource, $sourceKeys) {
            $sourceData = [];
            foreach ($sourceKeys as $key) {
                $sourceData[$key] = $trafficSource[$key] ?? null;
            }

            $order = Order::create(array_merge([
                'user_id'           => $userId,
                'buyer_name'        => $buyer['name'],
                'buyer_email'       => $buyer['email'],
                'buyer_phone'       => $buyer['phone'],
                'tax_id'            => !empty($buyer['tax_id']) ? $buyer['tax_id'] : null,
                'total_amount'      => $payable,
                'currency'          => 'TWD',
                'payment_gateway'   => $gateway,
                'merchant_order_no' => null,
                'status'            => 'pending',
            ], $couponFields, $referralFields, $sourceData));

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

            // Discount is order-level; record it on the first purchase row only so that
            // sum(purchase.discount_amount) == order.discount_amount (stats use orders).
            $discountRemaining = (int) round($order->discount_amount);

            foreach ($order->items as $item) {
                try {
                    $discountForItem = $discountRemaining;
                    $discountRemaining = 0;

                    [$purchase, $created] = $this->firstOrCreatePurchase($user, $item, $order, $gateway, $discountForItem);

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

            // Redeem coupon once on payment confirmation (FR-014). Layer-1 status guard
            // above ensures this runs only on the first successful fulfillment.
            if ($order->coupon_code) {
                app(CouponService::class)->redeem($order->coupon_code);
            }

            // Referral payout on payment confirmation (FR-020) + buyer activation (FR-016).
            $referralService = app(ReferralService::class);
            if ($order->referrer_user_id) {
                $order->load('referrer');
                $referralService->reward($order);
            }
            $referralService->evaluateActivation($user);
        });

        return $purchases;
    }

    private function firstOrCreatePurchase(User $user, OrderItem $item, Order $order, string $gateway, int $discountAmount = 0): array
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
            'coupon_code'        => $order->coupon_code,
            'discount_amount'    => $discountAmount,
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
