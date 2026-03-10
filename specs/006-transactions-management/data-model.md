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
