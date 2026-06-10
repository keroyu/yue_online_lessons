<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Models\CouponCode;
use App\Models\Course;
use App\Services\CouponService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function __construct(private CouponService $couponService) {}

    public function index(): Response
    {
        $coupons = CouponCode::with('course:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (CouponCode $c) => $this->row($c));

        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => $coupons,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Coupons/Create', [
            'courses' => $this->courseOptions(),
        ]);
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        CouponCode::create($request->validated());

        return redirect()->route('admin.coupons.index')->with('success', '折扣碼已建立');
    }

    public function edit(CouponCode $coupon): Response
    {
        return Inertia::render('Admin/Coupons/Edit', [
            'coupon'  => [
                'id'         => $coupon->id,
                'code'       => $coupon->code,
                'type'       => $coupon->type,
                'value'      => (float) $coupon->value,
                'course_id'  => $coupon->course_id,
                'expires_at' => $coupon->expires_at?->format('Y-m-d\TH:i'),
                'max_uses'   => $coupon->max_uses,
                'is_active'  => $coupon->is_active,
                'note'       => $coupon->note,
            ],
            'courses' => $this->courseOptions(),
        ]);
    }

    public function update(UpdateCouponRequest $request, CouponCode $coupon): RedirectResponse
    {
        // code 不可修改
        $coupon->update($request->validated());

        return redirect()->route('admin.coupons.index')->with('success', '折扣碼已更新');
    }

    public function destroy(CouponCode $coupon): RedirectResponse
    {
        $coupon->delete(); // 軟刪除：代碼永久保留，不可重建

        return redirect()->route('admin.coupons.index')->with('success', '折扣碼已刪除');
    }

    public function toggle(CouponCode $coupon): RedirectResponse
    {
        $coupon->update(['is_active' => !$coupon->is_active]);

        return back()->with('success', $coupon->is_active ? '折扣碼已啟用' : '折扣碼已停用');
    }

    /**
     * 統計頁（US4）：range ∈ 7|30|60|90|all，預設 30。
     */
    public function show(Request $request, CouponCode $coupon): Response
    {
        $range = $request->input('range', '30');
        $days  = $range === 'all' ? null : (int) $range;

        $stats = $this->couponService->stats($coupon, $days);

        return Inertia::render('Admin/Coupons/Show', [
            'coupon'  => $this->row($coupon->loadMissing('course:id,name')),
            'range'   => $range,
            'summary' => [
                'count'          => $stats['count'],
                'revenue'        => $stats['revenue'],
                'discount_total' => $stats['discountTotal'],
            ],
            'details' => $stats['details'],
        ]);
    }

    /**
     * Shape a coupon row for list / header display.
     */
    private function row(CouponCode $c): array
    {
        return [
            'id'              => $c->id,
            'code'            => $c->code,
            'type'            => $c->type,
            'value'           => (float) $c->value,
            'type_label'      => $c->type === 'fixed'
                ? '固定折抵 NT$' . number_format((int) round($c->value))
                : $this->couponService->label($c),
            'scope_label'     => $c->course_id === null ? '全站通用' : ($c->course->name ?? '（課程已刪除）'),
            'expires_at'      => $c->expires_at?->toIso8601String(),
            'expires_label'   => $c->expires_at ? $c->expires_at->format('Y-m-d') : '永不過期',
            'max_uses'        => $c->max_uses,
            'used_count'      => $c->used_count,
            'remaining_label' => $c->max_uses === null ? '無限制' : (string) max(0, $c->max_uses - $c->used_count),
            'is_active'       => $c->is_active,
            'note'            => $c->note,
        ];
    }

    private function courseOptions(): array
    {
        return Course::select('id', 'name')->orderBy('name')->get()->all();
    }
}
