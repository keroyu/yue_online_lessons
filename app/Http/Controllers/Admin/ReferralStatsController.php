<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReferralStatsController extends Controller
{
    /**
     * Referral performance by referrer (US5).
     * range ∈ 7|30|60|90|all（預設 30）。
     */
    public function index(Request $request): Response
    {
        $range = (string) $request->input('range', '30');
        $days = $range === 'all' ? null : (int) $range;

        $query = Order::query()
            ->whereNotNull('referrer_user_id')
            ->where('status', 'paid');

        if ($days) {
            $query->where('orders.webhook_received_at', '>=', now()->subDays($days));
        }

        $rows = $query
            ->join('users', 'users.id', '=', 'orders.referrer_user_id')
            ->groupBy('orders.referrer_user_id', 'users.nickname', 'users.email', 'users.referral_code')
            ->select([
                'orders.referrer_user_id',
                'users.nickname as referrer_name',
                'users.email as referrer_email',
                'users.referral_code',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(orders.total_amount) as revenue'),
                DB::raw('SUM(orders.referral_reward_points) as reward_points'),
            ])
            ->orderByDesc('reward_points')
            ->get()
            ->map(fn ($r) => [
                'referrer_name'  => $r->referrer_name ?: '（未命名）',
                'referrer_email' => $r->referrer_email,
                'referral_code'  => $r->referral_code,
                'order_count'    => (int) $r->order_count,
                'revenue'        => (int) round($r->revenue),
                'reward_points'  => (int) $r->reward_points,
            ]);

        return Inertia::render('Admin/Referrals/Index', [
            'rows'  => $rows,
            'range' => $range,
        ]);
    }
}
