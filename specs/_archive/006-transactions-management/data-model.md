# Data Model: 交易紀錄管理

**Phase**: 1 — Design
**Date**: 2026-03-10

---

## Existing Models (No Migration Needed)

### Purchase (現有，需修補)

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | bigint PK | — | auto | |
| `user_id` | FK → users | no | — | onDelete cascade |
| `course_id` | FK → courses | no | — | onDelete cascade |
| `portaly_order_id` | varchar(100) | yes | null | unique |
| `buyer_email` | varchar(255) | yes | null | 保留供 user 刪除後顯示 |
| `amount` | decimal(10,2) | no | — | 手動建立時填 0 |
| `currency` | varchar(10) | no | `'TWD'` | |
| `coupon_code` | varchar(50) | yes | null | |
| `discount_amount` | decimal(10,2) | no | `0` | |
| `status` | enum(paid,refunded) | no | — | paid = 有效；refunded = 已退款 |
| `source` | varchar(20) | no | `'purchase'` | ⚠️ 缺漏於 $fillable，需修補 |
| `type` | varchar(20) | no | `'paid'` | paid / system_assigned / gift |
| `webhook_received_at` | timestamp | yes | null | |
| `created_at` | timestamp | no | — | |
| `updated_at` | timestamp | no | — | |

**Required patch** (`Purchase.php` `$fillable`):
```php
// Add 'source' to existing $fillable array
'source',
```

**Indexes (existing)**:
- `user_id` index
- `course_id` index
- `type` index
- `UNIQUE(user_id, course_id)` — 防止重複購買，手動新增時亦受此約束（退款後可重新指派）

---

## State Transitions

```
Purchase.status:

  [created] → paid ──────────────────────→ refunded
                ↑                              │
                │  (new system_assigned/gift    │
                │   after refund is OK,         │
                │   creates a NEW Purchase)     │
                └──────────────────────────────┘
```

**Rules**:
- `paid → refunded`: 只能透過管理員操作，不可逆（refunded 無法改回 paid）
- 同一 `(user_id, course_id)` 組合的 UNIQUE constraint：退款後狀態為 `refunded`，DB 仍保留該筆，但 unique constraint 已被佔用。若要重新指派，需先考慮此約束。

> **Implementation note**: 因為 UNIQUE(`user_id`, `course_id`) 的存在，退款後若要重新手動指派同一會員+課程，必須處理 constraint 衝突。兩種方式：(a) 更新原本的 refunded 紀錄、(b) 刪除後重建。建議方案：`TransactionService::createManual()` 先查詢是否有 `refunded` 狀態的舊紀錄，若有則更新為 paid（而非 insert）。

---

## Service Interface

### TransactionService

```php
namespace App\Services;

class TransactionService
{
    /**
     * 手動建立交易（system_assigned / gift）
     * @return array{success: bool, purchase?: Purchase, error?: string}
     */
    public function createManual(
        User $user,
        Course $course,
        string $type,  // 'system_assigned' | 'gift'
        ?string $note = null
    ): array

    /**
     * 將交易標記為退款，撤銷課程存取
     * @return array{success: bool, error?: string}
     */
    public function refund(Purchase $purchase): array
}
```

---

## Query Patterns

### 列表查詢（TransactionController::index）

```
Purchase::with(['user:id,real_name,nickname,email', 'course:id,name'])
    ->when($search, fn($q) => $q->where('buyer_email', 'like', "%$search%")
        ->orWhere('portaly_order_id', 'like', "%$search%"))
    ->when($status, fn($q) => $q->where('status', $status))
    ->when($type, fn($q) => $q->where('type', $type))
    ->when($courseId, fn($q) => $q->where('course_id', $courseId))
    ->orderBy('created_at', 'desc')
    ->paginate(20)
```

### CSV 匯出查詢

同列表查詢，但改用 `->chunk(200, ...)` 代替 `->paginate()`，搭配 `response()->streamDownload()` 輸出。

---

## Frontend Props Schema

### Index page props

```typescript
interface TransactionsIndexProps {
  transactions: {
    data: TransactionRow[]
    current_page: number
    last_page: number
    total: number
    per_page: number
    links: PaginationLink[]
  }
  filters: {
    search: string | null
    status: 'paid' | 'refunded' | null
    type: 'paid' | 'system_assigned' | 'gift' | null
    course_id: number | null
  }
  courses: { id: number; name: string }[]
  matchingCount: number
}

interface TransactionRow {
  id: number
  portaly_order_id: string | null
  buyer_email: string | null
  user: { id: number; real_name: string | null; nickname: string | null; email: string } | null
  course: { id: number; name: string } | null
  amount: string       // decimal as string
  discount_amount: string
  currency: string
  coupon_code: string | null
  status: 'paid' | 'refunded'
  source: string
  type: 'paid' | 'system_assigned' | 'gift'
  type_label: string   // accessor
  created_at: string   // ISO8601
}
```

### Show page props

```typescript
interface TransactionShowProps {
  transaction: TransactionRow & {
    webhook_received_at: string | null
    updated_at: string
  }
}
```

### Chart props (added to Index page — partial reload via `only: ['chartData', 'chartFilters']`)

```typescript
interface ChartFilters {
  range: '7d' | '30d' | '90d' | 'custom'
  start: string   // YYYY-MM-DD
  end: string     // YYYY-MM-DD
}

interface ChartDayPoint {
  date: string        // 'YYYY-MM-DD'
  amount: number      // 當日 paid 銷售額（float）
  count: number       // 當日 paid 銷售量（int）
}

interface ChartData {
  days: ChartDayPoint[]
  total_amount: number  // 區間總銷售額
  total_count: number   // 區間總銷售量
}
```

### Chart Query Pattern (TransactionController::index)

```php
// Resolve date range from chart_range / chart_start / chart_end params
$range = $request->input('chart_range', '30d');
[$chartStart, $chartEnd] = match($range) {
    '7d'     => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
    '90d'    => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
    'custom' => [Carbon::parse($request->chart_start)->startOfDay(),
                 Carbon::parse($request->chart_end)->endOfDay()],
    default  => [now()->subDays(29)->startOfDay(), now()->endOfDay()], // '30d'
};

$days = Purchase::query()
    ->paidStatus()
    ->whereBetween('created_at', [$chartStart, $chartEnd])
    ->selectRaw('DATE(CONVERT_TZ(created_at, "+00:00", "+08:00")) as date')
    ->selectRaw('SUM(amount) as amount')
    ->selectRaw('COUNT(*) as count')
    ->groupBy('date')
    ->orderBy('date')
    ->get()
    ->keyBy('date');

// Fill missing dates with zeros to ensure continuous x-axis
$period = CarbonPeriod::create($chartStart, $chartEnd);
$chartDays = collect($period)->map(fn($day) => [
    'date'   => $day->format('Y-m-d'),
    'amount' => (float) ($days[$day->format('Y-m-d')]->amount ?? 0),
    'count'  => (int)   ($days[$day->format('Y-m-d')]->count  ?? 0),
]);
```

**Notes**:
- 時區轉換使用 `CONVERT_TZ(created_at, "+00:00", "+08:00")` 確保日期以台灣時區（UTC+8）切分
- 使用 `CarbonPeriod` 補齊無資料的日期，確保 X 軸連續（spec FR-026 edge case）
- 只計算 `status = 'paid'` 的交易（Assumption: 退款不計入）
