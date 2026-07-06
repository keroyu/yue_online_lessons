# Quickstart: 交易紀錄管理

**For**: Developers implementing feature `006-transactions-management`
**Date**: 2026-03-10

---

## Prerequisites

- Branch: `006-transactions-management` (already checked out)
- DB: `php artisan migrate:fresh --seed` (no new migrations needed)
- Dev server: `php artisan serve` + `npm run dev`

---

## Implementation Order

Follow this order — each step is independently testable.

### Step 1 — Bug fix: Purchase.$fillable (5 min)

```php
// app/Models/Purchase.php — add 'source' to $fillable
protected $fillable = [
    // ... existing fields ...
    'source',   // ← ADD THIS
];
```

**Test**: `php artisan tinker` → `Purchase::create(['source' => 'manual', ...])` should persist the field.

---

### Step 2 — Routes (5 min)

Add to `routes/web.php` inside the admin middleware group:

```php
// Transactions
Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
Route::get('/transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
Route::patch('/transactions/{transaction}/refund', [TransactionController::class, 'refund'])->name('transactions.refund');
```

Add import at top of `web.php`:
```php
use App\Http\Controllers\Admin\TransactionController;
```

---

### Step 3 — TransactionService (20 min)

Create `app/Services/TransactionService.php`:

Key logic:
- `createManual()`: Check existing paid record → error. Check existing refunded record → update to paid. Otherwise → create new.
- `refund()`: Check already refunded → error. Update status to refunded.

See `contracts/transactions.md` for full interface.

---

### Step 4 — StoreTransactionRequest (10 min)

Create `app/Http/Requests/Admin/StoreTransactionRequest.php` with validation rules from `contracts/transactions.md`.

---

### Step 5 — TransactionController (30 min)

Create `app/Http/Controllers/Admin/TransactionController.php`:

- `index()`: Build filtered query, paginate(20), return Inertia props
- `show()`: Eager load user + course, return Inertia props
- `store()`: Delegate to TransactionService::createManual(), redirect
- `refund()`: Delegate to TransactionService::refund(), redirect back
- `export()`: Build query (ids[] or select_all + filters), StreamedResponse with UTF-8 BOM + fputcsv chunk(200)

Refer to `MemberController::index()` for filter/pagination pattern.

---

### Step 6 — Frontend: Index.vue (60 min)

Create `resources/js/Pages/Admin/Transactions/Index.vue`:

- Table with columns: checkbox, 訂單ID, 購買者, 課程, 金額, 狀態, 類型, 購買時間, 操作
- Search input + filter dropdowns (status, type, course)
- Checkbox logic: individual, select-all page, select-all matching (follow Members/Index.vue pattern)
- Selected count banner + export CSV button (disabled when none selected)
- CSV export: build URL with `?ids[]=...` or `?select_all=true&...filters` → `window.location.href`
- 「手動新增」button → modal or navigate to form (inline modal preferred)

---

### Step 7 — Frontend: Show.vue (30 min)

Create `resources/js/Pages/Admin/Transactions/Show.vue`:

- Detail display of all transaction fields
- Member link → `route('admin.members.index', { highlight: transaction.user?.id })` (NOT `admin.members.show` — that route returns JsonResponse, not an Inertia page)
- Course link → `route('admin.courses.edit', transaction.course.id)`
- If status === 'paid': show 「標記退款」button → `TransactionRefundModal`

---

### Step 8 — TransactionRefundModal.vue (15 min)

Create `resources/js/Components/Admin/TransactionRefundModal.vue`:

- Simple confirm dialog: "確定要將此交易標記為退款？此操作將撤銷該會員對課程的存取權。"
- On confirm: `router.patch(route('admin.transactions.refund', transaction.id))`

---

### Step 9 — Navigation (5 min)

Add 「交易紀錄」link to admin navigation component, after 「會員管理」.

---

## Key Files Reference

| File | Purpose |
|------|---------|
| `app/Models/Purchase.php` | Patch: add `source` to `$fillable` |
| `app/Services/TransactionService.php` | createManual + refund business logic |
| `app/Http/Controllers/Admin/TransactionController.php` | 5 actions: index, show, store, refund, export |
| `app/Http/Requests/Admin/StoreTransactionRequest.php` | Validation for manual create |
| `resources/js/Pages/Admin/Transactions/Index.vue` | List page with checkboxes + CSV export |
| `resources/js/Pages/Admin/Transactions/Show.vue` | Detail page + refund button |
| `resources/js/Components/Admin/TransactionRefundModal.vue` | Confirm dialog |
| `routes/web.php` | 5 new routes in admin group |

---

### Step 10 — Install chart.js + vue-chartjs (2 min)

```bash
npm install chart.js vue-chartjs
```

---

### Step 11 — RevenueChart.vue (40 min)

Create `resources/js/Components/Admin/RevenueChart.vue`:

```vue
<script setup>
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale,
  BarElement, LineElement, PointElement, Tooltip, Legend
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Tooltip, Legend)

const props = defineProps({
  chartData: Object,   // { days: [], total_amount, total_count }
  chartFilters: Object // { range, start, end }
})

const emit = defineEmits(['change-range'])

const data = computed(() => ({
  labels: props.chartData.days.map(d => d.date),
  datasets: [
    {
      type: 'bar',
      label: '當日銷售額',
      data: props.chartData.days.map(d => d.amount),
      backgroundColor: '#2dd4bf',  // teal-400
      yAxisID: 'yAmount',
    },
    {
      type: 'line',
      label: '當日銷售量',
      data: props.chartData.days.map(d => d.count),
      borderColor: '#93c5fd',  // blue-300
      backgroundColor: 'transparent',
      yAxisID: 'yCount',
      tension: 0.4,
    },
  ],
}))

const options = {
  responsive: true,
  interaction: { mode: 'index', intersect: false },
  scales: {
    yAmount: { type: 'linear', position: 'left',  ticks: { callback: v => `$${v.toLocaleString()}` } },
    yCount:  { type: 'linear', position: 'right', grid: { drawOnChartArea: false } },
  },
}
</script>
```

Key design points:
- `type: 'bar'` dataset uses left Y axis (`yAmount`), `type: 'line'` uses right Y axis (`yCount`)
- Register only the Chart.js modules you use (tree-shake friendly)
- Emit `change-range` when user selects a preset; parent handles partial reload

---

### Step 12 — Wire chart filter to Inertia partial reload (20 min)

In `Index.vue`, add:

```js
import { router } from '@inertiajs/vue3'

function changeChartRange(range) {
  const params = new URLSearchParams(window.location.search)
  params.set('chart_range', range)
  if (range !== 'custom') {
    params.delete('chart_start')
    params.delete('chart_end')
  }
  router.visit(`/admin/transactions?${params}`, {
    only: ['chartData', 'chartFilters'],
    preserveState: true,
    preserveScroll: true,
  })
}

function changeCustomRange(start, end) {
  const params = new URLSearchParams(window.location.search)
  params.set('chart_range', 'custom')
  params.set('chart_start', start)
  params.set('chart_end', end)
  router.visit(`/admin/transactions?${params}`, {
    only: ['chartData', 'chartFilters'],
    preserveState: true,
    preserveScroll: true,
  })
}
```

Mount `<RevenueChart>` above the transaction table, passing `chartData` and `chartFilters` props.

---

### Step 13 — Update TransactionController::index() (20 min)

Add chart data computation to the existing `index()` method:

```php
// Resolve chart date range
$chartRange = $request->input('chart_range', '30d');
[$chartStart, $chartEnd] = match($chartRange) {
    '7d'    => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
    '90d'   => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
    'custom'=> [Carbon::parse($request->chart_start)->startOfDay(),
                Carbon::parse($request->chart_end)->endOfDay()],
    default => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
};

// See data-model.md for full query pattern (GROUP BY date with zero-fill)
// Append chartData + chartFilters to existing Inertia::render() props
```

See `data-model.md` "Chart Query Pattern" section for the full GROUP BY + CarbonPeriod zero-fill implementation.

---

## Key Files Reference

| File | Purpose |
|------|---------|
| `app/Models/Purchase.php` | Patch: add `source` to `$fillable` |
| `app/Services/TransactionService.php` | createManual + refund business logic |
| `app/Http/Controllers/Admin/TransactionController.php` | 5 actions: index, show, store, refund, export + chart data |
| `app/Http/Requests/Admin/StoreTransactionRequest.php` | Validation for manual create |
| `resources/js/Pages/Admin/Transactions/Index.vue` | List page with checkboxes + CSV export + revenue chart |
| `resources/js/Pages/Admin/Transactions/Show.vue` | Detail page + refund button |
| `resources/js/Components/Admin/TransactionRefundModal.vue` | Confirm dialog |
| `resources/js/Components/Admin/RevenueChart.vue` | Dual-axis bar+line chart |
| `routes/web.php` | 5 new routes in admin group |

## Patterns to Follow

| Pattern | Where to Look |
|---------|--------------|
| Filtered list + pagination | `Admin\MemberController::index()` |
| Checkbox + cross-page select | `Pages/Admin/Members/Index.vue` |
| Confirmation modal | `Components/Admin/GiftCourseModal.vue` |
| Inertia redirect with flash | `Admin\MemberController::update()` |
| StreamedResponse CSV | New pattern (see research.md R-001) |
| Inertia partial reload | research.md R-008, contracts/transactions.md |
| Dual-axis Chart.js | research.md R-007, Components/Admin/RevenueChart.vue |
