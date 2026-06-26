<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponChainRequest;
use App\Http\Requests\Admin\UpdateCouponChainRequest;
use App\Models\Course;
use App\Models\CouponChain;
use App\Services\CouponChainService;
use Inertia\Inertia;
use Inertia\Response;

class CouponChainController extends Controller
{
    public function __construct(private CouponChainService $chainService) {}

    public function index(): Response
    {
        $chains = CouponChain::with(['course', 'codes' => fn ($q) => $q->orderByDesc('created_at')])
            ->latest()
            ->paginate(20);

        $chains->getCollection()->transform(fn (CouponChain $chain) => $this->formatChain($chain));

        return Inertia::render('Admin/CouponChains/Index', [
            'chains' => $chains,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/CouponChains/Create', [
            'courses' => Course::select('id', 'name')->where('status', 'selling')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCouponChainRequest $request)
    {
        $data = $request->validated();
        $data['alias'] = strtolower($data['alias']);

        $chain = CouponChain::create($data);

        // Auto-generate the first code immediately.
        $this->chainService->generateNextCode($chain);

        return redirect('/admin/coupon-chains')->with('success', "輪換折扣碼「{$chain->alias}」已建立，首支代碼已自動生成。");
    }

    public function show(CouponChain $couponChain): Response
    {
        $codes = $couponChain->codes()->latest()->get()->map(fn ($c) => [
            'id'          => $c->id,
            'code'        => $c->code,
            'used_count'  => $c->used_count,
            'max_uses'    => $c->max_uses,
            'is_active'   => $c->is_active,
            'is_current'  => $c->is_active && ($c->max_uses === null || $c->used_count < $c->max_uses),
            'created_at'  => $c->created_at?->toIso8601String(),
        ]);

        return Inertia::render('Admin/CouponChains/Show', [
            'chain'       => $this->formatChain($couponChain),
            'codes'       => $codes,
        ]);
    }

    public function edit(CouponChain $couponChain): Response
    {
        return Inertia::render('Admin/CouponChains/Edit', [
            'chain'   => $couponChain->load('course'),
            'courses' => Course::select('id', 'name')->where('status', 'selling')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCouponChainRequest $request, CouponChain $couponChain)
    {
        $data = $request->validated();
        $data['alias'] = strtolower($data['alias']);
        $couponChain->update($data);

        return redirect('/admin/coupon-chains')->with('success', '輪換折扣碼已更新。');
    }

    public function toggle(CouponChain $couponChain)
    {
        $couponChain->update(['is_active' => !$couponChain->is_active]);

        return back()->with('success', $couponChain->is_active ? '已啟用' : '已停用');
    }

    public function destroy(CouponChain $couponChain)
    {
        // Deleting the chain sets chain_id = null on its codes via nullOnDelete FK.
        $couponChain->delete();

        return redirect('/admin/coupon-chains')->with('success', '輪換折扣碼已刪除，歷史代碼保留不受影響。');
    }

    private function formatChain(CouponChain $chain): array
    {
        $currentCode = $chain->currentCode();

        return [
            'id'            => $chain->id,
            'alias'         => $chain->alias,
            'placeholder'   => '{' . $chain->alias . '}',
            'type'          => $chain->type,
            'type_label'    => $this->typeLabel($chain->type, (float) $chain->value),
            'value'         => $chain->value,
            'course_id'     => $chain->course_id,
            'scope_label'   => $chain->course_id ? ($chain->course?->name ?? '指定課程') : '全站通用',
            'code_max_uses' => $chain->code_max_uses,
            'is_active'     => $chain->is_active,
            'note'          => $chain->note,
            'current_code'  => $currentCode?->code,
            'total_codes'   => $chain->codes()->count(),
            'created_at'    => $chain->created_at?->toIso8601String(),
        ];
    }

    private function typeLabel(string $type, float $value): string
    {
        if ($type === 'fixed') {
            return '折抵 NT$' . number_format((int) round($value));
        }

        $digits = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
        $n      = (int) round($value * 100);
        $tens   = intdiv($n, 10);
        $units  = $n % 10;

        return $digits[$tens] . ($units > 0 ? $digits[$units] : '') . '折優惠';
    }
}
