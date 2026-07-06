# Data Model: 011-discount-coupon

**Feature Branch**: `011-discount-coupon`
**Created**: 2026-06-09
**Updated**: 2026-06-26 - 新增 `coupon_chains` 表；`coupon_codes` 新增 `chain_id` nullable FK（nullOnDelete）。
**Updated**: 2026-06-10 - 已建立 migration 並 migrate：`coupon_codes`（含 SoftDeletes、`UNIQUE(code)`）、`orders` 新增 `coupon_code`/`original_amount`/`discount_amount`；`purchases` 沿用既有欄位（`coupon_code` 寫每筆、`discount_amount` 僅記首筆）。schema 與本文件一致。

---

## New Tables

### `coupon_chains`

| Column | Type | Nullable | Notes |
|--------|------|----------|-------|
| id | bigint PK | no | |
| alias | varchar(50) | no | **UNIQUE**；英數字與底線；輸入佔位符識別 |
| course_id | bigint unsigned FK → courses | yes | null = 全站通用；ON DELETE SET NULL |
| type | enum('fixed','ratio') | no | |
| value | decimal(10,2) | no | 同 coupon_codes 規則 |
| code_max_uses | int unsigned | no | default 1；0 = 無限制，不自動補碼 |
| is_active | boolean | no | default true |
| note | varchar(255) | yes | |
| created_at | timestamp | no | |
| updated_at | timestamp | no | |

**Constraints**: `UNIQUE(alias)`

```sql
CREATE TABLE coupon_chains (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    alias         VARCHAR(50) NOT NULL,
    course_id     BIGINT UNSIGNED NULL,
    type          ENUM('fixed','ratio') NOT NULL,
    value         DECIMAL(10,2) NOT NULL,
    code_max_uses INT UNSIGNED NOT NULL DEFAULT 1,
    is_active     BOOLEAN NOT NULL DEFAULT 1,
    note          VARCHAR(255) NULL,
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL,
    CONSTRAINT fk_coupon_chains_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    UNIQUE KEY uq_coupon_chains_alias (alias)
);
```

---

### `coupon_codes`

| Column | Type | Nullable | Notes |
|--------|------|----------|-------|
| id | bigint PK | no | |
| code | varchar(6) | no | **UNIQUE**；儲存為大寫；軟刪除後仍佔用（永久保留） |
| type | enum('fixed','ratio') | no | fixed=固定折抵金額；ratio=折數 |
| value | decimal(10,2) | no | fixed：折抵金額（≥10）；ratio：0.50–0.95 |
| course_id | bigint unsigned FK → courses | yes | null = 全站通用；ON DELETE CASCADE |
| expires_at | timestamp | yes | null = 永不過期 |
| max_uses | int unsigned | yes | null = 無限制 |
| used_count | int unsigned | no | default 0；付款確認時 `increment` |
| chain_id | bigint unsigned FK → coupon_chains | yes | null = 一般折扣碼；非 null = CouponChain 自動生成；ON DELETE SET NULL |
| is_active | boolean | no | default true |
| note | varchar(255) | yes | 後台備註說明 |
| created_at | timestamp | no | |
| updated_at | timestamp | no | |
| deleted_at | timestamp | yes | SoftDeletes |

**Constraints**: `UNIQUE(code)`

> **唯一性與軟刪除**：一般 UNIQUE（非 partial）即足夠——soft-deleted 列仍留在表中，`code` 不可被重建（FR-016）。

```sql
CREATE TABLE coupon_codes (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(6) NOT NULL,
    type        ENUM('fixed','ratio') NOT NULL,
    value       DECIMAL(10,2) NOT NULL,
    course_id   BIGINT UNSIGNED NULL,
    expires_at  TIMESTAMP NULL,
    max_uses    INT UNSIGNED NULL,
    used_count  INT UNSIGNED NOT NULL DEFAULT 0,
    is_active   BOOLEAN NOT NULL DEFAULT 1,
    note        VARCHAR(255) NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL,
    CONSTRAINT fk_coupon_codes_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY uq_coupon_codes_code (code)
);
```

---

## Altered Table

### `orders` — add discount columns

```sql
ALTER TABLE orders
    ADD COLUMN coupon_code     VARCHAR(6)     NULL AFTER total_amount,
    ADD COLUMN original_amount DECIMAL(10,2)  NULL AFTER coupon_code,
    ADD COLUMN discount_amount DECIMAL(10,2)  NOT NULL DEFAULT 0 AFTER original_amount;
```

| Column | Type | Nullable | Notes |
|--------|------|----------|-------|
| coupon_code | varchar(6) | yes | 套用的折扣碼快照；null = 未套用 |
| original_amount | decimal(10,2) | yes | 折扣前原始小計；null = 未套用折扣 |
| discount_amount | decimal(10,2) | no | 折抵金額；default 0 |

- 不變式：`total_amount = original_amount - discount_amount`（套用折扣時）。未套用折扣時 `coupon_code=null`、`original_amount=null`、`discount_amount=0`、`total_amount` 維持原小計。
- `coupon_code` 為**字串快照**，與 `coupon_codes` 表無 FK——折扣碼軟刪除/編輯後，歷史訂單金額不回溯（SC-007、Edge：管理員改折扣值不影響歷史）。

### `purchases` — 既有欄位，無需 migration

`coupon_code varchar`、`discount_amount decimal` 已存在（009 建立，未使用）。本功能於 `fulfillOrder()` 填入：
- `coupon_code` = order 的折扣碼（每筆 purchase 皆寫入，稽核用）。
- `discount_amount` = 僅記於**首筆** purchase（= 訂單總折抵額），其餘為 0，使 `sum == order.discount_amount`（不採比例分攤；統計以 orders 為準）。

> 確認 `Purchase::$fillable` 含 `coupon_code`、`discount_amount`、`order_id`（若缺則補上）。

---

## Eloquent Models

### `CouponCode` (`app/Models/CouponCode.php`) — 新增

```php
class CouponCode extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'type', 'value', 'course_id',
        'expires_at', 'max_uses', 'used_count', 'is_active', 'note',
    ];

    protected function casts(): array
    {
        return [
            'value'      => 'decimal:2',
            'expires_at' => 'datetime',
            'max_uses'   => 'integer',
            'used_count' => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    // 寫入時統一大寫
    protected static function booted(): void
    {
        static::saving(function (CouponCode $c) {
            $c->code = strtoupper($c->code);
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    // 可用狀態（啟用 + 未過期 + 未達上限）；soft-deleted 由全域 scope 自動排除
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->where(fn ($q2) => $q2->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->max_uses !== null && $this->used_count >= $this->max_uses;
    }

    public function isSiteWide(): bool
    {
        return $this->course_id === null;
    }
}
```

### `CouponChain` (`app/Models/CouponChain.php`) — 新增

`$fillable`: `alias`, `course_id`, `type`, `value`, `code_max_uses`, `is_active`, `note`

Relationships: `course(): BelongsTo`, `codes(): HasMany(CouponCode, chain_id)`

Key method: `currentCode(): ?CouponCode` — 撈最新一支啟用中、未達上限、未軟刪除的 CouponCode。

### `CouponCode` (`app/Models/CouponCode.php`) — 擴充

`$fillable` 加入 `chain_id`；新增 `chain(): BelongsTo(CouponChain)` relationship。

### `CouponChainService` (`app/Services/CouponChainService.php`) — 新增

```
substitutePlaceholders(?string $html): ?string
  — preg_replace_callback /\{([a-zA-Z0-9_]+)\}/ → 查 alias → 展開為 currentCode()->code 或 ''

generateNextCode(CouponChain $chain): CouponCode
  — 新增一支 CouponCode（type/value/course_id/max_uses 繼承 chain），chain_id 關聯

generateUniqueCode(): string
  — 6 位大寫英數字隨機，迴圈檢查唯一性（含軟刪除）
```

`CouponService::redeem()` 修改：increment 後若 `chain_id && code_max_uses > 0 && used_count >= code_max_uses`，呼叫 `CouponChainService::generateNextCode()`。

`ClassroomController` 修改：注入 `CouponChainService`，`formatLessonFull()` 的 `promo_html` 欄位改為 `substitutePlaceholders($lesson->promo_html)`。

### `Order` (`app/Models/Order.php`) — 擴充

`$fillable` 加入 `coupon_code`、`original_amount`、`discount_amount`；`casts()` 加入：

```php
'original_amount' => 'decimal:2',
'discount_amount' => 'decimal:2',
```

### `Purchase` (`app/Models/Purchase.php`) — 確認 `$fillable`

確保 `coupon_code`、`discount_amount` 在 `$fillable` 中（如缺補上）。

---

## Service Interfaces

### `CouponService` (`app/Services/CouponService.php`) — 新增（核心）

```php
class CouponService
{
    /**
     * 套用前驗證折扣碼（前台 apply-coupon / 結帳前重驗共用）。
     * 涵蓋：存在 + 啟用、未過期、未達上限、課程適用性、折後 ≥ NT$1。
     *
     * @param string $code       使用者輸入的代碼（不分大小寫）
     * @param int[]  $courseIds  購物車中的 course_id 陣列
     * @param int    $subtotal   折扣前小計（整數元）
     * @return array{success: bool, error?: string, coupon?: CouponCode,
     *               discount?: int, payable?: int, original?: int, label?: string}
     */
    public function validateForCart(string $code, array $courseIds, int $subtotal): array;

    /**
     * 計算折抵金額（整數元）。不做下限判定（由 validateForCart 統一處理）。
     * fixed: min(value, subtotal)；ratio: subtotal - (int) round(subtotal * value)
     */
    public function calculateDiscount(CouponCode $coupon, int $subtotal): int;

    /**
     * 顯示標籤：fixed → "折抵 NT$XXX"；ratio → "X折優惠"（0.6 → "六折優惠"）。
     */
    public function label(CouponCode $coupon): string;

    /**
     * 付款確認後兌現：原子 increment used_count（軟限制，不強制檢查上限）。
     * 由 CheckoutService::fulfillOrder() 呼叫。查無代碼則記錄 warning 後略過。
     */
    public function redeem(string $code): void;

    /**
     * 後台統計：指定折扣碼於最近 $days 天（依 webhook_received_at）的成效。
     * $days = null → 「全部」期間，不套用時間範圍條件。
     * 來源 = orders 表 status='paid'。
     *
     * @param int|null $days 7/30/60/90，或 null = 全部
     * @return array{count: int, revenue: float, discountTotal: float,
     *               details: array<array{email: string, paid_at: string,
     *               total: float, original: float}>}
     */
    public function stats(CouponCode $coupon, ?int $days): array;
}
```

**回傳契約說明**：
- `validateForCart` 失敗回 `['success' => false, 'error' => '中文訊息']`；成功回完整折扣資訊供前端顯示與後續建單。
- 錯誤訊息分類（對應 US1 場景）：
  - 不存在/已停用 → 「折扣碼無效」
  - 已過期 → 「折扣碼已過期」
  - 達上限 → 「折扣碼已達使用上限」
  - 課程不符 → 「此折扣碼不適用於購物車中的課程」
  - 折後 < NT$1 → 「折扣金額超過訂單上限，無法套用」

### `CheckoutService`（既有，修改）

```php
// createOrder() 簽名擴充（新增最後一個可選參數，不破壞既有呼叫）
public function createOrder(
    ?int $userId,
    array $courseIds,
    array $buyer,
    array $trafficSource = [],
    ?string $couponCode = null   // ← 新增
): Order;
```

`createOrder()` 內整合（在計算 `$totalAmount` 之後）：
1. 若 `$couponCode` 非空 → 呼叫 `CouponService::validateForCart($couponCode, $courseIds, $subtotal)`。
2. 驗證失敗 → `throw new \RuntimeException($result['error'])`（由 `CheckoutController::initiate()` catch 回 409，符合 FR-012 阻擋付款）。
3. 驗證成功 → 在 `Order::create()` 寫入 `coupon_code`、`original_amount = $subtotal`、`discount_amount = $result['discount']`、`total_amount = $result['payable']`。

`fulfillOrder()` 內整合（標記 paid 後、建立 purchases 時）：
1. 若 `$order->coupon_code` 非空 → `CouponService::redeem($order->coupon_code)`（在交易內，原子 increment）。
2. 建立每筆 `Purchase` 時帶入 `coupon_code` 與分攤後的 `discount_amount`。

---

## Frontend Props Schema

### `Cart/Index.vue`（既有 props 擴充）

```typescript
// 既有 props（items, total）。折扣碼套用狀態存元件內 ref，不持久化。
// 新增 prop：prefillCouponCode — 來自 session('checkout_coupon') 的原始代碼字串（US5）。
//   伺服器不驗證（訪客購物車在 localStorage）；CouponInput 於 onMounted 以當前
//   購物車 course_ids 呼叫 apply-coupon 端點完成驗證與套用，失敗靜默忽略。
interface CartProps {
  items: CartItem[]
  total: number
  prefillCouponCode: string | null   // ← 新增（網址 ?coupon= 自動帶入，登入/訪客一致）
}

// CouponInput 元件 emit / prefill 共用結構：
interface AppliedCoupon {
  code: string
  type: 'fixed' | 'ratio'
  label: string        // "六折優惠" / "折抵 NT$200"
  discount: number     // 折抵金額
  original: number     // 原始小計
  payable: number      // 折後實付
}
```

> **自動帶入流程（US5 / research D8）**：`CourseController::show()` 將 `?coupon=` 存入 `session('checkout_coupon')`（與 `traffic_source` 並排）。`CartController::index()`（登入/訪客兩分支）讀此 session → 以 `prefillCouponCode` 字串傳前端 → `CouponInput` 於 `onMounted` 帶當前 course_ids 呼叫 apply-coupon 驗證並套用。手動輸入優先覆蓋；訂單建立後由 **`CheckoutController::initiate()`**（非 Service）`session()->forget('checkout_coupon')`。

### `Checkout/Index.vue`（props 擴充）

```typescript
interface CheckoutProps {
  items: CartItem[]
  total: number              // 原始小計（既有）
  prefill: { name, email, phone }
  // ↓ 新增：當 ?coupon=XXX 通過伺服器重驗時才帶入
  coupon: {
    code: string
    label: string
    discount: number
    payable: number
  } | null
}
```

### `Admin/Coupons/Index.vue`

```typescript
interface CouponRow {
  id: number
  code: string
  type: 'fixed' | 'ratio'
  value: string
  type_label: string         // "固定折抵 NT$100" / "六折"
  scope_label: string        // "全站通用" / 課程名稱
  expires_at: string | null
  max_uses: number | null
  used_count: number
  remaining_label: string    // "無限制" / 剩餘數
  is_active: boolean
}
```

### `Admin/Coupons/Show.vue`（統計頁）

```typescript
interface CouponStatsProps {
  coupon: CouponRow
  range: 7 | 30 | 60 | 90 | 'all'    // 預設 30；'all' = 全部期間
  summary: {
    count: number
    revenue: number          // 折後實付合計
    discount_total: number   // 總折抵
  }
  details: {
    email: string
    paid_at: string          // 付款確認時間
    total: number            // 折後結帳金額
    original: number         // 原始金額
  }[]
}
```
