<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTransactionRequest;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Models\Purchase;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService)
    {
    }

    /**
     * Display a paginated, filterable list of transactions.
     */
    public function index(Request $request): InertiaResponse
    {
        $search   = $request->input('search');
        $status   = $request->input('status');
        $type     = $request->input('type');
        $courseId = $request->input('course_id');
        $perPage  = min($request->input('per_page', 20), 100);

        $query = Purchase::with(['user:id,real_name,nickname,email', 'course:id,name', 'course.lessons:id,course_id'])
            ->when($search, fn ($q) => $q->where(fn ($q2) =>
                $q2->where('buyer_email', 'like', "%{$search}%")
                   ->orWhere('portaly_order_id', 'like', "%{$search}%")
            ))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($type,   fn ($q) => $q->where('type', $type))
            ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
            ->orderBy('created_at', 'desc');

        $matchingCount = $query->count();

        $transactions = $query->paginate($perPage)->withQueryString();

        // Batch compute lesson progress for all transactions on this page
        $items = $transactions->items();

        $allLessonIds = [];
        $allUserIds   = [];
        foreach ($items as $t) {
            if ($t->user_id) {
                $allUserIds[] = $t->user_id;
            }
            if ($t->course) {
                foreach ($t->course->lessons as $lesson) {
                    $allLessonIds[] = $lesson->id;
                }
            }
        }
        $allLessonIds = array_unique($allLessonIds);
        $allUserIds   = array_unique($allUserIds);

        // Build a lookup: [user_id => Set of completed lesson_ids]
        $progressMap = [];
        if (!empty($allUserIds) && !empty($allLessonIds)) {
            LessonProgress::whereIn('user_id', $allUserIds)
                ->whereIn('lesson_id', $allLessonIds)
                ->get(['user_id', 'lesson_id'])
                ->each(function ($lp) use (&$progressMap) {
                    $progressMap[$lp->user_id][$lp->lesson_id] = true;
                });
        }

        // Attach progress fields to each paginator item
        $transactions->through(function ($t) use ($progressMap) {
            $lessonIds = $t->course ? $t->course->lessons->pluck('id')->all() : [];
            $total     = count($lessonIds);
            $completed = 0;
            if ($t->user_id && $total > 0) {
                foreach ($lessonIds as $lid) {
                    if (isset($progressMap[$t->user_id][$lid])) {
                        $completed++;
                    }
                }
            }
            $t->progress_completed = $completed;
            $t->progress_total     = $total;
            return $t;
        });

        $courses = Course::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Transactions/Index', [
            'transactions' => $transactions,
            'filters'      => [
                'search'    => $search,
                'status'    => $status,
                'type'      => $type,
                'course_id' => $courseId,
            ],
            'courses'      => $courses,
            'matchingCount' => $matchingCount,
        ]);
    }

    /**
     * Display a single transaction's detail.
     */
    public function show(Purchase $transaction): InertiaResponse
    {
        $transaction->load(['user:id,real_name,nickname,email', 'course:id,name']);

        return Inertia::render('Admin/Transactions/Show', [
            'transaction' => [
                'id'                  => $transaction->id,
                'portaly_order_id'    => $transaction->portaly_order_id,
                'buyer_email'         => $transaction->buyer_email,
                'user'                => $transaction->user ? [
                    'id'        => $transaction->user->id,
                    'real_name' => $transaction->user->real_name,
                    'nickname'  => $transaction->user->nickname,
                    'email'     => $transaction->user->email,
                ] : null,
                'course'              => $transaction->course ? [
                    'id'   => $transaction->course->id,
                    'name' => $transaction->course->name,
                ] : null,
                'amount'              => $transaction->amount,
                'discount_amount'     => $transaction->discount_amount,
                'coupon_code'         => $transaction->coupon_code,
                'currency'            => $transaction->currency,
                'status'              => $transaction->status,
                'source'              => $transaction->source,
                'type'                => $transaction->type,
                'type_label'          => $transaction->type_label,
                'webhook_received_at' => $transaction->webhook_received_at?->toIso8601String(),
                'created_at'          => $transaction->created_at->toIso8601String(),
                'updated_at'          => $transaction->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Manually create a transaction (system_assigned or gift).
     */
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $user   = User::findOrFail($request->validated('user_id'));
        $course = Course::findOrFail($request->validated('course_id'));
        $type   = $request->validated('type');

        $result = $this->transactionService->createManual($user, $course, $type);

        if (! $result['success']) {
            return back()->withErrors(['user_id' => $result['error']])->withInput();
        }

        return redirect()->route('admin.transactions.index')
            ->with('success', '交易新增成功');
    }

    /**
     * Mark a transaction as refunded.
     */
    public function refund(Purchase $transaction): RedirectResponse
    {
        $result = $this->transactionService->refund($transaction);

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['error']]);
        }

        return back()->with('success', '已標記退款，課程存取已撤銷');
    }

    /**
     * Export selected (or all matching) transactions as a UTF-8 CSV file.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $ids       = $request->input('ids', []);
        $selectAll = $request->boolean('select_all');

        // Validate: one of the two modes must be provided
        if (! $selectAll && empty($ids)) {
            abort(422, '請選擇要匯出的交易，或勾選「全選符合條件」');
        }

        $query = Purchase::with(['user:id,real_name,nickname,email', 'course:id,name'])
            ->orderBy('created_at', 'desc');

        if ($selectAll) {
            // Apply same filters as list view
            $search   = $request->input('search');
            $status   = $request->input('status');
            $type     = $request->input('type');
            $courseId = $request->input('course_id');

            $query
                ->when($search, fn ($q) => $q->where(fn ($q2) =>
                    $q2->where('buyer_email', 'like', "%{$search}%")
                       ->orWhere('portaly_order_id', 'like', "%{$search}%")
                ))
                ->when($status,   fn ($q) => $q->where('status', $status))
                ->when($type,     fn ($q) => $q->where('type', $type))
                ->when($courseId, fn ($q) => $q->where('course_id', $courseId));
        } else {
            $query->whereIn('id', $ids);
        }

        $filename = 'transactions-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for proper Excel display of Chinese characters
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                '訂單 ID',
                'Portaly 訂單編號',
                '購買者姓名',
                '購買者 Email',
                '課程名稱',
                '金額',
                '折扣金額',
                '優惠碼',
                '幣別',
                '狀態',
                '來源',
                '類型',
                '購買時間',
            ]);

            $query->chunk(200, function ($purchases) use ($handle) {
                foreach ($purchases as $purchase) {
                    fputcsv($handle, [
                        $purchase->id,
                        $purchase->portaly_order_id ?? '',
                        $purchase->user?->real_name ?? $purchase->user?->nickname ?? '',
                        $purchase->buyer_email ?? $purchase->user?->email ?? '',
                        $purchase->course?->name ?? '',
                        $purchase->amount,
                        $purchase->discount_amount,
                        $purchase->coupon_code ?? '',
                        $purchase->currency,
                        $purchase->status,
                        $purchase->source ?? '',
                        $purchase->type,
                        $purchase->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
