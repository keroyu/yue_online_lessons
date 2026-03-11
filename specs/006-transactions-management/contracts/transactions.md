# API Contracts: 交易紀錄管理

**Phase**: 1 — Design
**Date**: 2026-03-10
**Transport**: Inertia.js (server-side rendered props) + direct HTTP (CSV export)

---

## Routes

```php
// In routes/web.php, within admin middleware group
Route::get('/transactions', [TransactionController::class, 'index'])
    ->name('transactions.index');

Route::get('/transactions/export', [TransactionController::class, 'export'])
    ->name('transactions.export');

Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])
    ->name('transactions.show');

Route::post('/transactions', [TransactionController::class, 'store'])
    ->name('transactions.store');

Route::patch('/transactions/{transaction}/refund', [TransactionController::class, 'refund'])
    ->name('transactions.refund');
```

---

## Endpoint Contracts

### GET /admin/transactions

**Purpose**: 交易列表（含篩選、搜尋、分頁）

**Query Parameters**:

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `search` | string | null | 搜尋 buyer_email 或 portaly_order_id |
| `status` | `paid\|refunded` | null | 狀態篩選 |
| `type` | `paid\|system_assigned\|gift` | null | 類型篩選 |
| `course_id` | int | null | 課程篩選 |
| `per_page` | int | 20 | 每頁筆數（上限 100） |
| `chart_range` | `7d\|30d\|90d\|custom` | `30d` | 圖表時間區間 |
| `chart_start` | `YYYY-MM-DD` | — | 自訂起始日（chart_range=custom 時有效） |
| `chart_end` | `YYYY-MM-DD` | — | 自訂結束日（chart_range=custom 時有效） |

**Response**: Inertia render `Admin/Transactions/Index`

**Props**:
```json
{
  "transactions": { /* LengthAwarePaginator */ },
  "filters": { "search": null, "status": null, "type": null, "course_id": null },
  "courses": [{ "id": 1, "name": "課程名稱" }],
  "matchingCount": 150,
  "chartData": {
    "days": [
      { "date": "2026-02-09", "amount": 600.0, "count": 3 },
      { "date": "2026-02-10", "amount": 0, "count": 0 }
    ],
    "total_amount": 20330.0,
    "total_count": 492
  },
  "chartFilters": {
    "range": "30d",
    "start": "2026-02-09",
    "end": "2026-03-10"
  }
}
```

**Partial reload** (when user changes chart filter only):
```js
// Frontend: only re-fetch chartData + chartFilters, skip transactions reload
router.visit(url, {
  only: ['chartData', 'chartFilters'],
  preserveState: true,
  preserveScroll: true,
})
```

---

### GET /admin/transactions/export

**Purpose**: 匯出選取交易為 CSV

**Query Parameters** (兩種模式擇一):

**模式 A — 指定 ID**:

| Param | Type | Description |
|-------|------|-------------|
| `ids[]` | int[] | 要匯出的 Purchase IDs |

**模式 B — 全選符合條件**:

| Param | Type | Description |
|-------|------|-------------|
| `select_all` | `true` | 觸發全選模式 |
| `search` | string | 同列表篩選條件 |
| `status` | string | 同列表篩選條件 |
| `type` | string | 同列表篩選條件 |
| `course_id` | int | 同列表篩選條件 |

**Response**: `StreamedResponse`
- Content-Type: `text/csv; charset=UTF-8`
- Content-Disposition: `attachment; filename="transactions-YYYYMMDD.csv"`
- BOM: `\xEF\xBB\xBF`（UTF-8 BOM，確保 Excel 中文正常顯示）

**CSV Columns**:
```
訂單 ID, Portaly 訂單編號, 購買者姓名, 購買者 Email, 課程名稱,
金額, 折扣金額, 優惠碼, 幣別, 狀態, 來源, 類型, 購買時間
```

**Validation**:
- 模式 A：`ids` 必須非空陣列
- 模式 B：`select_all=true` 時，filter params 重新查詢
- 兩種模式皆未提供時：返回 422

---

### GET /admin/transactions/{transaction}

**Purpose**: 單筆交易詳情

**Response**: Inertia render `Admin/Transactions/Show`

**UI Note**: 購買者連結應導向 `admin.members.index?highlight={user.id}`（會員管理列表，透過 query param 觸發展開該會員的詳情 modal）。**勿使用 `admin.members.show`** — 該路由回傳 JsonResponse，不是 Inertia 頁面。

**Props**:
```json
{
  "transaction": {
    "id": 42,
    "portaly_order_id": "PO-12345",
    "buyer_email": "user@example.com",
    "user": { "id": 7, "real_name": "王小明", "nickname": "ming", "email": "user@example.com" },
    "course": { "id": 3, "name": "Python 入門" },
    "amount": "1200.00",
    "discount_amount": "0.00",
    "coupon_code": null,
    "currency": "TWD",
    "status": "paid",
    "source": "purchase",
    "type": "paid",
    "type_label": "已付款",
    "webhook_received_at": "2026-01-15T10:30:00+08:00",
    "created_at": "2026-01-15T10:30:05+08:00",
    "updated_at": "2026-01-15T10:30:05+08:00"
  }
}
```

---

### POST /admin/transactions

**Purpose**: 手動新增交易（system_assigned / gift）

**Request Body** (Form Request: `StoreTransactionRequest`):
```json
{
  "user_id": 7,
  "course_id": 3,
  "type": "system_assigned"
}
```

**Validation Rules**:
```php
'user_id'   => 'required|exists:users,id',
'course_id' => 'required|exists:courses,id',
'type'      => 'required|in:system_assigned,gift',
```

**Business Rule** (in TransactionService::createManual):
- 若該 user+course 已有 status=paid 的紀錄 → 返回錯誤「該會員已擁有此課程」
- 若有 status=refunded 的舊紀錄 → 更新為 paid（不 insert 新紀錄，避免 unique constraint 衝突）
- 否則 → 建立新 Purchase（amount=0, source='manual', status='paid'）

**Response (success)**: `redirect()->route('admin.transactions.index')->with('success', '交易新增成功')`

**Response (error)**:
```json
{ "errors": { "user_id": ["該會員已擁有此課程"] } }
```

---

### PATCH /admin/transactions/{transaction}/refund

**Purpose**: 將交易標記為退款（撤銷課程存取）

**Request Body**: 無（確認已在前端 modal 完成）

**Business Rules** (in TransactionService::refund):
- 若 status 已為 `refunded` → 返回錯誤「此交易已退款」
- 否則 → 更新 status 為 `refunded`，記錄 Log::info

**Response (success)**: `redirect()->back()->with('success', '已標記退款，課程存取已撤銷')`

**Response (error)**: `redirect()->back()->withErrors(['error' => '此交易已退款'])`

---

## Navigation Entry

後台導覽列新增「交易紀錄」連結，位置於「會員管理」之後：

```vue
<!-- In Components/Layout/Navigation.vue or Admin sidebar -->
<Link :href="route('admin.transactions.index')">交易紀錄</Link>
```
