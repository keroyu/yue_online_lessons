<?php

namespace Database\Seeders;

use App\Models\CouponCode;
use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointTransaction;
use App\Models\Purchase;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ReferralService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cross-module demo data for local showcase (idempotent).
 *
 * Everything created here is tagged so a re-run wipes the previous batch first:
 *   - users            email LIKE '%@demo.test'
 *   - orders           merchant_order_no LIKE 'DEMO-%'
 *   - purchases        portaly_order_id  LIKE 'DEMO-%'
 *   - point ledger     note LIKE '[demo]%'
 *   - coupons          note LIKE '[demo]%'
 *   - high-ticket lead email LIKE '%@demo.test'
 *
 * The data is intentionally self-consistent across modules: every sale is a real
 * Order + OrderItem + Purchase trio (mirroring CheckoutService::fulfillOrder), coupon
 * usage increments used_count and is recorded on both order & first purchase row,
 * referral orders pay out a real point-ledger entry to an activated referrer, and
 * "converted" leads reuse the emails of actual flagship buyers.
 */
class DemoDataSeeder extends Seeder
{
    /** Demo course price map: real course id => showcase price. Only ids present in DB are used. */
    private const PRICE_MAP = [1 => 750, 3 => 12990, 5 => 2490, 6 => 990];

    /** Weighted picking of which course a customer buys (id => weight). */
    private const COURSE_WEIGHT = [1 => 25, 3 => 30, 5 => 35, 6 => 10];

    /** Monthly revenue target (paid Purchase.amount) per ~30-day bucket. All within 10–30万. */
    private const TARGET_A = 120000; // ~90-60 days ago
    private const TARGET_B = 180000; // ~60-30 days ago
    private const TARGET_C = 150000; // last 30 days (holds the referral orders)

    private int $seq = 0;

    public function run(): void
    {
        $this->cleanup();

        // Resolve the priced, real courses we will actually sell.
        $courses = Course::whereIn('id', array_keys(self::PRICE_MAP))->get()->keyBy('id');
        if ($courses->isEmpty()) {
            $this->command?->error('DemoDataSeeder: none of the expected courses exist; aborting.');
            return;
        }

        [$members, $customers] = $this->createUsers();
        $this->subscribeNewsletter(array_slice($members, 0, 5));
        $coupons = $this->createCoupons($courses);

        $buyerPool = array_merge($members, $customers);
        $owned = [];                 // "uid-cid" guard for the unique(user_id, course_id) constraint
        $flagshipBuyerEmails = [];   // course #3 buyers → reused as "converted" leads

        // --- 5 referral orders in the last 30 days (US5 referral stats) ---
        $this->createReferralOrders($members, $buyerPool, $courses, $owned, $flagshipBuyerEmails);

        // --- Fill each month bucket to its revenue target ---
        $now = now();
        $this->fillBucket($buyerPool, $courses, $coupons, $owned, $flagshipBuyerEmails,
            $now->copy()->subDays(90), $now->copy()->subDays(61), self::TARGET_A);
        $this->fillBucket($buyerPool, $courses, $coupons, $owned, $flagshipBuyerEmails,
            $now->copy()->subDays(60), $now->copy()->subDays(31), self::TARGET_B);
        $this->fillBucket($buyerPool, $courses, $coupons, $owned, $flagshipBuyerEmails,
            $now->copy()->subDays(30), $now->copy()->subDays(1), self::TARGET_C);

        // --- A few refunded purchases (excluded from revenue, shown in transaction list) ---
        $this->createRefunds($buyerPool, $courses, $owned);

        // --- 30 high-ticket leads across all statuses ---
        $this->createLeads($courses, $flagshipBuyerEmails);

        $this->report();
    }

    // ---------------------------------------------------------------------
    // Users
    // ---------------------------------------------------------------------

    /** @return array{0: int[], 1: int[]} [showcaseMemberIds, customerIds] */
    private function createUsers(): array
    {
        $memberNames = ['林雅婷', '陳冠宇', '黃詩涵', '張家豪', '李佩珊',
                        '王柏翰', '吳孟璇', '劉承恩', '蔡宜庭', '鄭光宏'];

        $members = [];
        foreach ($memberNames as $i => $name) {
            $n = $i + 1;
            $u = User::create([
                'email'             => sprintf('demo-%02d@demo.test', $n),
                'nickname'          => $name,
                'real_name'         => $name,
                'phone'             => '09' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'role'              => 'member',
                'email_verified_at' => now(),
            ]);
            // Spread signup over the last ~120 days; some logged in recently.
            $created = now()->copy()->subDays(random_int(30, 120))->subHours(random_int(0, 23));
            $this->stampCreated('users', $u->id, $created);
            $u->update([
                'last_login_at' => (random_int(0, 100) < 70)
                    ? now()->copy()->subDays(random_int(0, 20))
                    : null,
            ]);
            $members[] = $u->id;
        }

        // Broader customer base so monthly revenue is actually attainable under the
        // unique(user_id, course_id) constraint. These are ordinary members (a paid
        // checkout always creates one), just unnamed walk-up buyers.
        $customers = [];
        for ($i = 1; $i <= 55; $i++) {
            $u = User::create([
                'email'             => sprintf('cust-%02d@demo.test', $i),
                'nickname'          => '顧客' . $i,
                'phone'             => '09' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'role'              => 'member',
                'email_verified_at' => now(),
            ]);
            $created = now()->copy()->subDays(random_int(1, 120))->subHours(random_int(0, 23));
            $this->stampCreated('users', $u->id, $created);
            $customers[] = $u->id;
        }

        return [$members, $customers];
    }

    private function subscribeNewsletter(array $memberIds): void
    {
        foreach ($memberIds as $i => $id) {
            $subscribedAt = now()->copy()->subDays(random_int(20, 90));
            User::whereKey($id)->update([
                'newsletter_status'            => 'subscribed',
                'newsletter_subscribed_at'     => $subscribedAt,
                'newsletter_status_changed_at' => $subscribedAt,
                'newsletter_unsubscribe_token' => (string) Str::uuid(),
                // Most subscribers have opened at least one issue (open-rate realism).
                'newsletter_last_opened_at'    => $i < 3 ? now()->copy()->subDays(random_int(1, 14)) : null,
            ]);
        }
    }

    // ---------------------------------------------------------------------
    // Coupons
    // ---------------------------------------------------------------------

    /** @return array<string, CouponCode> */
    private function createCoupons($courses): array
    {
        $out = [];

        $out['SPRING'] = CouponCode::create([
            'code' => 'SPRING', 'type' => 'ratio', 'value' => 0.80,
            'course_id' => null, 'max_uses' => null, 'is_active' => true,
            'note' => '[demo] 春季全站八折',
        ]);
        $out['SAVE5H'] = CouponCode::create([
            'code' => 'SAVE5H', 'type' => 'fixed', 'value' => 500,
            'course_id' => null, 'max_uses' => 100, 'is_active' => true,
            'note' => '[demo] 全站折 500',
        ]);
        if ($courses->has(3)) {
            $out['VIP2K'] = CouponCode::create([
                'code' => 'VIP2K', 'type' => 'fixed', 'value' => 2000,
                'course_id' => 3, 'max_uses' => 50, 'is_active' => true,
                'note' => '[demo] 旗艦課折 2000',
            ]);
        }

        return $out;
    }

    // ---------------------------------------------------------------------
    // Referral orders (last 30 days)
    // ---------------------------------------------------------------------

    private function createReferralOrders(array $members, array $buyerPool, $courses, array &$owned, array &$flagshipBuyerEmails): void
    {
        // Activate 3 showcase members as referrers.
        $referrers = array_slice($members, 0, 3);
        foreach ($referrers as $rid) {
            User::whereKey($rid)->update(['referral_activated_at' => now()->copy()->subDays(random_int(40, 100))]);
        }

        // 5 referred orders across those 3 referrers.
        $plan = [$referrers[0], $referrers[0], $referrers[1], $referrers[1], $referrers[2]];
        $courseIds = array_keys(self::PRICE_MAP);

        foreach ($plan as $refId) {
            $date = now()->copy()->subDays(random_int(2, 28))->subHours(random_int(0, 23));

            // Prefer flagship for a meatier reward; fall back if buyer already owns it.
            $buyerId = $this->pickBuyer($buyerPool, $refId, $owned, $courseIds);
            if ($buyerId === null) {
                continue;
            }
            $cid = $this->pickCourseFor($buyerId, $owned, [3, 5, 1, 6]);
            if ($cid === null || ! $courses->has($cid)) {
                continue;
            }

            $this->createSale($buyerId, $cid, $courses[$cid], $date, $owned, null, $refId, $flagshipBuyerEmails);
        }
    }

    // ---------------------------------------------------------------------
    // Revenue buckets
    // ---------------------------------------------------------------------

    private function fillBucket(array $buyerPool, $courses, array $coupons, array &$owned, array &$flagshipBuyerEmails, Carbon $start, Carbon $end, int $target): void
    {
        $sum = 0;
        $attempts = 0;
        $couponCodes = array_keys($coupons);

        while ($sum < $target && $attempts < 2000) {
            $attempts++;

            $buyerId = $this->pickBuyer($buyerPool, null, $owned, array_keys(self::PRICE_MAP));
            if ($buyerId === null) {
                break; // pool exhausted
            }
            $cid = $this->weightedCourse($buyerId, $owned);
            if ($cid === null || ! $courses->has($cid)) {
                continue;
            }

            $date = $this->randomDate($start, $end);

            // ~18% of orders use a coupon (scope-aware).
            $couponCode = null;
            if (random_int(0, 100) < 18) {
                $couponCode = $this->pickCouponFor($coupons, $cid);
            }

            $amount = $this->createSale($buyerId, $cid, $courses[$cid], $date, $owned, $couponCode, null, $flagshipBuyerEmails);
            $sum += $amount;
        }
    }

    // ---------------------------------------------------------------------
    // Core sale builder — Order + OrderItem + Purchase (+ referral ledger)
    // ---------------------------------------------------------------------

    /** @return int the paid Purchase.amount (full course price) added to revenue */
    private function createSale(int $buyerId, int $courseId, Course $course, Carbon $date, array &$owned, ?CouponCode $coupon, ?int $referrerId, array &$flagshipBuyerEmails): int
    {
        $this->seq++;
        $buyer = User::find($buyerId);
        $price = self::PRICE_MAP[$courseId];

        // Coupon math (mirrors CheckoutService: discount computed on subtotal).
        $original = null;
        $discount = 0;
        if ($coupon) {
            $original = $price;
            $discount = $coupon->type === 'fixed'
                ? min((int) round($coupon->value), $price - 1)
                : $price - (int) round($price * (float) $coupon->value);
        }
        $payable = $price - $discount;

        // Referral snapshot.
        $referralFields = ['referrer_user_id' => null, 'referral_rate' => null, 'referral_reward_points' => 0];
        if ($referrerId) {
            $rate = (int) SiteSetting::get('referral_reward_rate', 10);
            $reward = app(ReferralService::class)->computeReward($payable, $rate);
            $referralFields = [
                'referrer_user_id'       => $referrerId,
                'referral_rate'          => $rate,
                'referral_reward_points' => $reward,
            ];
        }

        $order = Order::create(array_merge([
            'user_id'           => $buyerId,
            'buyer_name'        => $buyer->real_name ?? $buyer->nickname,
            'buyer_email'       => $buyer->email,
            'buyer_phone'       => $buyer->phone,
            'total_amount'      => $payable,
            'coupon_code'       => $coupon?->code,
            'original_amount'   => $original,
            'discount_amount'   => $discount,
            'currency'          => 'TWD',
            'payment_gateway'   => random_int(0, 1) ? 'payuni' : 'newebpay',
            'merchant_order_no' => sprintf('DEMO-%04d', $this->seq),
            'status'            => 'paid',
            'gateway_trade_no'  => 'TXN' . str_pad((string) $this->seq, 8, '0', STR_PAD_LEFT),
            'webhook_received_at' => $date,
            'utm_source'        => ['google', 'facebook', 'newsletter', 'direct', 'instagram'][random_int(0, 4)],
            'utm_medium'        => ['cpc', 'social', 'email', 'organic'][random_int(0, 3)],
        ], $referralFields));
        $this->stampCreated('orders', $order->id, $date);

        $item = OrderItem::create([
            'order_id'    => $order->id,
            'course_id'   => $courseId,
            'course_name' => $course->name,
            'unit_price'  => $price,
        ]);
        DB::table('order_items')->where('id', $item->id)->update(['created_at' => $date]);

        $purchase = Purchase::create([
            'user_id'            => $buyerId,
            'course_id'          => $courseId,
            'portaly_order_id'   => sprintf('DEMO-%04d', $this->seq),
            'buyer_email'        => $buyer->email,
            'amount'             => $price,
            'currency'           => 'TWD',
            'coupon_code'        => $coupon?->code,
            'discount_amount'    => $discount,
            'status'             => 'paid',
            'type'               => 'paid',
            'source'             => $order->payment_gateway,
            'webhook_received_at' => $date,
            'order_id'           => $order->id,
        ]);
        $this->stampCreated('purchases', $purchase->id, $date);
        $owned[$buyerId . '-' . $courseId] = true;

        if ($coupon) {
            $coupon->increment('used_count');
        }

        if ($courseId === 3) {
            $flagshipBuyerEmails[] = $buyer->email;
        }

        // Referral payout to the ledger (matures 14 days after the order).
        if ($referrerId) {
            $this->awardReferral($referrerId, $referralFields['referral_reward_points'], $order->id, $date);
        }

        return $price;
    }

    private function awardReferral(int $referrerId, int $reward, int $orderId, Carbon $orderDate): void
    {
        if ($reward <= 0) {
            return;
        }
        $maturityDays = (int) SiteSetting::get('referral_maturity_days', 14);
        $availableAt = $orderDate->copy()->addDays($maturityDays);
        $matured = $availableAt->lessThanOrEqualTo(now());

        PointTransaction::create([
            'user_id'        => $referrerId,
            'amount'         => $reward,
            'type'           => 'earn_referral',
            'reference_type' => 'order',
            'reference_id'   => $orderId,
            'note'           => '[demo] 推薦回饋',
            'available_at'   => $availableAt,
            'matured_synced' => $matured,
            'created_at'     => $orderDate,
        ]);

        if ($matured) {
            User::whereKey($referrerId)->increment('points', $reward);
        }
    }

    // ---------------------------------------------------------------------
    // Refunds
    // ---------------------------------------------------------------------

    private function createRefunds(array $buyerPool, $courses, array &$owned): void
    {
        $made = 0;
        $attempts = 0;
        while ($made < 3 && $attempts < 200) {
            $attempts++;
            $buyerId = $this->pickBuyer($buyerPool, null, $owned, array_keys(self::PRICE_MAP));
            if ($buyerId === null) {
                break;
            }
            $cid = $this->weightedCourse($buyerId, $owned);
            if ($cid === null || ! $courses->has($cid)) {
                continue;
            }
            $date = now()->copy()->subDays(random_int(5, 80));
            $flagship = [];
            $this->createSale($buyerId, $cid, $courses[$cid], $date, $owned, null, null, $flagship);
            // Flip the just-created purchase to refunded.
            Purchase::where('portaly_order_id', sprintf('DEMO-%04d', $this->seq))
                ->update(['status' => 'refunded']);
            $made++;
        }
    }

    // ---------------------------------------------------------------------
    // High-ticket leads
    // ---------------------------------------------------------------------

    private function createLeads($courses, array $flagshipBuyerEmails): void
    {
        // Prefer the pricier "high-ticket-looking" courses for booking demand.
        $leadCourseIds = array_values(array_filter([3, 5, 1], fn ($id) => $courses->has($id)));
        if (empty($leadCourseIds)) {
            $leadCourseIds = [$courses->keys()->first()];
        }

        $names = ['周妍希', '許志明', '賴思妤', '楊博丞', '謝欣怡', '簡志偉', '羅心妍',
                  '江俊傑', '范曉萱', '洪明德', '曾雅琪', '邱建華', '孫佳蓉', '馬冠廷', '高詩婷'];

        // Distribution across the four statuses (total 30).
        $statuses = array_merge(
            array_fill(0, 11, 'pending'),
            array_fill(0, 8, 'contacted'),
            array_fill(0, 6, 'converted'),
            array_fill(0, 5, 'closed'),
        );
        shuffle($statuses);

        $convertedEmails = array_values(array_unique($flagshipBuyerEmails));
        $ci = 0;

        foreach ($statuses as $i => $status) {
            $booked = now()->copy()->subDays(random_int(1, 60))->subHours(random_int(0, 23));
            $name = $names[$i % count($names)] . (intdiv($i, count($names)) > 0 ? (string) (intdiv($i, count($names)) + 1) : '');

            // Tie "converted" leads to real flagship buyers when available (cross-module consistency).
            if ($status === 'converted' && isset($convertedEmails[$ci])) {
                $email = $convertedEmails[$ci++];
            } else {
                $email = sprintf('lead-%02d@demo.test', $i + 1);
            }

            $contacted = in_array($status, ['contacted', 'converted', 'closed'], true);
            $notified = $contacted ? random_int(1, 3) : 0;

            $lead = HighTicketLead::create([
                'name'             => $name,
                'email'            => $email,
                'course_id'        => $leadCourseIds[array_rand($leadCourseIds)],
                'status'           => $status,
                'notified_count'   => $notified,
                'last_notified_at' => $notified ? $booked->copy()->addDays(random_int(1, 5)) : null,
                'booked_at'        => $booked,
            ]);
            $this->stampCreated('high_ticket_leads', $lead->id, $booked);
        }
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function pickBuyer(array $pool, ?int $excludeId, array $owned, array $courseIds): ?int
    {
        $shuffled = $pool;
        shuffle($shuffled);
        foreach ($shuffled as $uid) {
            if ($excludeId !== null && $uid === $excludeId) {
                continue;
            }
            // Buyer must still be able to own at least one demo course.
            foreach ($courseIds as $cid) {
                if (! isset($owned[$uid . '-' . $cid])) {
                    return $uid;
                }
            }
        }
        return null;
    }

    private function pickCourseFor(int $buyerId, array $owned, array $preference): ?int
    {
        foreach ($preference as $cid) {
            if (! isset($owned[$buyerId . '-' . $cid])) {
                return $cid;
            }
        }
        return null;
    }

    private function weightedCourse(int $buyerId, array $owned): ?int
    {
        $bag = [];
        foreach (self::COURSE_WEIGHT as $cid => $w) {
            if (! isset($owned[$buyerId . '-' . $cid])) {
                for ($i = 0; $i < $w; $i++) {
                    $bag[] = $cid;
                }
            }
        }
        return empty($bag) ? null : $bag[array_rand($bag)];
    }

    private function pickCouponFor(array $coupons, int $courseId): ?CouponCode
    {
        $candidates = [];
        foreach ($coupons as $c) {
            if ($c->course_id === null || $c->course_id === $courseId) {
                $candidates[] = $c;
            }
        }
        return empty($candidates) ? null : $candidates[array_rand($candidates)];
    }

    private function randomDate(Carbon $start, Carbon $end): Carbon
    {
        $span = max(1, $start->diffInSeconds($end));
        return $start->copy()->addSeconds(random_int(0, $span));
    }

    private function stampCreated(string $table, int $id, Carbon $when): void
    {
        DB::table($table)->where('id', $id)->update([
            'created_at' => $when,
            'updated_at' => $when,
        ]);
    }

    private function cleanup(): void
    {
        $demoUserIds = User::where('email', 'like', '%@demo.test')->pluck('id');

        PointTransaction::where('note', 'like', '[demo]%')
            ->orWhereIn('user_id', $demoUserIds)->delete();

        Purchase::where('portaly_order_id', 'like', 'DEMO-%')->delete();

        $demoOrderIds = Order::where('merchant_order_no', 'like', 'DEMO-%')->pluck('id');
        OrderItem::whereIn('order_id', $demoOrderIds)->delete();
        Order::whereIn('id', $demoOrderIds)->delete();

        HighTicketLead::where('email', 'like', '%@demo.test')->delete();
        CouponCode::where('note', 'like', '[demo]%')->forceDelete();

        // Deleting users cascades any leftover purchases (FK onDelete cascade).
        User::whereIn('id', $demoUserIds)->delete();
    }

    private function report(): void
    {
        $revenue = Purchase::where('portaly_order_id', 'like', 'DEMO-%')->where('status', 'paid')->sum('amount');
        $this->command?->info('DemoDataSeeder done:');
        $this->command?->info('  demo members/customers : ' . User::where('email', 'like', '%@demo.test')->count());
        $this->command?->info('  newsletter subscribed  : ' . User::where('email', 'like', '%@demo.test')->where('newsletter_status', 'subscribed')->count());
        $this->command?->info('  paid demo purchases    : ' . Purchase::where('portaly_order_id', 'like', 'DEMO-%')->where('status', 'paid')->count());
        $this->command?->info('  total demo revenue     : NT$' . number_format((int) $revenue));
        $this->command?->info('  referral orders (30d)  : ' . Order::where('merchant_order_no', 'like', 'DEMO-%')->whereNotNull('referrer_user_id')->count());
        $this->command?->info('  coupon orders          : ' . Order::where('merchant_order_no', 'like', 'DEMO-%')->whereNotNull('coupon_code')->count());
        $this->command?->info('  high-ticket leads      : ' . HighTicketLead::where('email', 'like', '%@demo.test')->count()
            . ' + converted tied to buyers');
    }
}
