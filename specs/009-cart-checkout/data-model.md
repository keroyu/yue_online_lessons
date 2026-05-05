
# Data Model: 購物車結帳系統 (009-cart-checkout)

**Branch**: `009-cart-checkout` | **Date**: 2026-05-05

## New Migrations

### 1. Add `payment_gateway` to `courses`

```php
// XXXX_add_payment_gateway_to_courses_table.php
$table->string('payment_gateway', 20)->nullable()->default('payuni')->after('portaly_product_id');
// Values: 'payuni' | 'newebpay' — NULL treated as 'payuni' fallback
```

**Rule**: Only applies when `portaly_product_id IS NULL`. Portaly courses ignore this field.

---

### 2. Create `cart_items`

```php
Schema::create('cart_items', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('course_id');
    $table->timestamp('created_at')->useCurrent();

    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
    $table->unique(['user_id', 'course_id']); // prevent duplicates
    $table->index('user_id');
});
```

**Notes**:
- No `updated_at` — follows project pattern (`LessonProgress`, `CourseImage`)
- `$timestamps = false` on Model, set `created_at` in `boot()`
- `cascadeOnDelete` on both FKs — deleting a user or course cleans up cart

---

### 3. Create `orders`

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->unsignedInteger('total_amount');         // TWD, sum of order_items.price
    $table->string('payment_gateway', 20);            // 'payuni' | 'newebpay'
    $table->string('gateway_trade_no', 100)->nullable()->unique(); // MerTradeNo / MerchantOrderNo
    $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
    $table->timestamps();

    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->index('status');
});
```

**Status transitions**:
- `pending` → created when checkout initiated, before redirect to gateway
- `paid` → set by NotifyURL/webhook handler after successful payment
- `failed` → set if ReturnURL confirms failure (optional; kept for audit)

**gateway_trade_no**: PayUni `MerTradeNo` or NewebPay `MerchantOrderNo`. Set at initiation time. Used by webhook handler to look up the Order.

---

### 4a. Add `order_id` to `purchases` (反查欄位 — Plan v2 增量)

```php
// XXXX_add_order_id_to_purchases_table.php
Schema::table('purchases', function (Blueprint $table) {
    $table->unsignedBigInteger('order_id')->nullable()->after('course_id');
    $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
    $table->index('order_id');
});
```

**Rule**:
- 新 cart 流程建立的 Purchase: `order_id` = 對應 Order
- 舊流程（YC PayUni、Portaly webhook、system_assigned、gift）: `order_id` = NULL
- 純為反查/客服用途，不參與業務邏輯判斷

### 4. Create `order_items`

```php
Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('order_id');
    $table->unsignedBigInteger('course_id');
    $table->unsignedInteger('price');               // display_price at checkout moment (TWD)
    $table->timestamp('created_at')->useCurrent();

    $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
    $table->foreign('course_id')->references('id')->on('courses')->restrictOnDelete();
    // No unique constraint — same course could theoretically appear in different orders
});
```

**Notes**:
- Price is locked at checkout time (not add-to-cart time) — spec clarification D-003
- `course_id` uses `restrictOnDelete` (not cascade) to preserve order history if course is deleted

---

## Model Definitions

### CartItem

```php
// app/Models/CartItem.php
protected $fillable = ['user_id', 'course_id'];
public $timestamps = false;

protected static function boot() {
    parent::boot();
    static::creating(fn($m) => $m->created_at = now());
}

// Relationships
public function user(): BelongsTo
public function course(): BelongsTo
```

### Order

```php
// app/Models/Order.php
protected $fillable = ['user_id', 'total_amount', 'payment_gateway', 'gateway_trade_no', 'status'];

protected function casts(): array {
    return ['status' => 'string'];
}

// Relationships
public function user(): BelongsTo
public function items(): HasMany  // → OrderItem
public function purchases(): HasMany  // → Purchase (after payment success)

// Scopes
public function scopePending($query)
public function scopePaid($query)
```

### OrderItem

```php
// app/Models/OrderItem.php
protected $fillable = ['order_id', 'course_id', 'price'];
public $timestamps = false;

protected static function boot() {
    parent::boot();
    static::creating(fn($m) => $m->created_at = now());
}

// Relationships
public function order(): BelongsTo
public function course(): BelongsTo
```

---

## Updated Model: Course

Add to `$fillable`: `'payment_gateway'`

Add accessor:
```php
protected function effectiveGateway(): Attribute
{
    return Attribute::make(
        get: fn () => $this->portaly_product_id ? null : ($this->payment_gateway ?? 'payuni')
    );
}
```

Add boolean check (Plan v2 — 統一 4 處判斷依據):
```php
public function isCartEligible(): bool
{
    return is_null($this->portaly_product_id)
        && $this->price > 0
        && $this->type !== 'high_ticket'
        && $this->status === 'published';
}
```

Add scope:
```php
public function scopeCartEligible($query): void
{
    $query->whereNull('portaly_product_id')
          ->where('price', '>', 0)
          ->where('type', '!=', 'high_ticket')
          ->where('status', 'published');
}
```

## Updated Model: Purchase (Plan v2)

Add to `$fillable`: `'order_id'`

Add relationship:
```php
public function order(): BelongsTo
{
    return $this->belongsTo(Order::class);
}
```

舊資料 NULL（Portaly、YC PayUni、system_assigned、gift），新 cart 流程設值。

---

## Updated Model Relationship Map

```
User
├── hasMany → CartItem (new)
├── hasMany → Order (new)
├── hasMany → Purchase (existing)
...

Order (new)
├── belongsTo → User
└── hasMany → OrderItem

OrderItem (new)
├── belongsTo → Order
└── belongsTo → Course

CartItem (new)
├── belongsTo → User
└── belongsTo → Course

Course (updated)
├── hasMany → CartItem (new)
...
```

---

## Index Strategy

| Table | Index | Reason |
|-------|-------|--------|
| `cart_items` | `user_id` | Load user's cart |
| `cart_items` | `(user_id, course_id)` UNIQUE | Prevent duplicates |
| `orders` | `gateway_trade_no` UNIQUE | Webhook lookup |
| `orders` | `status` | Admin queries |
| `order_items` | `order_id` | Load order contents |
| `courses` | `payment_gateway` | (not needed — low cardinality) |

---

## Incremental Update: 2026-05-05 — NewebPay Service Design

### NewebPayService (`app/Services/NewebPayService.php`)

**Constructor dependencies**:
```php
private string $merchantId;  // NEWEBPAY_MERCHANT_ID
private string $hashKey;     // NEWEBPAY_HASH_KEY
private string $hashIv;      // NEWEBPAY_HASH_IV
private string $apiUrl;      // ccore (sandbox) or core (production)
```

**Core methods**:

```php
// Build MPG form data for frontend auto-submit
public function buildCheckoutForm(Order $order): array
// Returns: ['action_url' => string, 'form_fields' => [...]]
// form_fields: MerchantID, TradeInfo (AES encrypted), TradeSha (SHA256), Version=2.3

// Generate unique MerchantOrderNo
public function generateOrderNo(int $orderId): string
// Format: YO{orderId:06d}{YmdHis}{rand4} — max 26 chars, alphanumeric+underscore

// Process NotifyURL callback (server-to-server)
public function processNotify(string $tradeInfo, string $tradeSha): array
// Returns: ['success' => bool, 'error' => string]

// Verify TradeSha signature
private function verifyTradeSha(string $tradeInfo, string $tradeSha): bool
// SHA256(HashKey={key}&{tradeInfo}&HashIV={iv}) uppercase

// AES-256-CBC encrypt (returns hex)
private function encrypt(string $data): string
// openssl_encrypt($data, 'AES-256-CBC', $hashKey, OPENSSL_ZERO_PADDING, $hashIv) → bin2hex

// AES-256-CBC decrypt (from hex)
private function decrypt(string $hex): string
// openssl_decrypt(hex2bin($hex), 'AES-256-CBC', $hashKey, OPENSSL_ZERO_PADDING, $hashIv)
```

**TradeInfo required parameters**:
```
MerchantID, RespondType=JSON, TimeStamp (Unix ±120s), Version=2.3,
MerchantOrderNo, Amt (integer TWD), ItemDesc (UTF-8 ≤50 chars),
Email, ReturnURL, NotifyURL, CREDIT=1
```

**Notification flow** (`processNotify`):
1. Verify TradeSha — if fail: log warning, return success=true (prevent retry)
2. Decrypt TradeInfo
3. Check outer Status == "SUCCESS"
4. Look up Order by `gateway_trade_no = MerchantOrderNo`
5. Idempotency: if `order->status === 'paid'` → skip, return success=true
6. For each OrderItem: `getOrCreateUser()` + `Purchase::create()`
7. Update `order->status = 'paid'`
8. Clear CartItems for paid courses
9. Trigger DripService for drip courses (same as PayuniService)

### Updated `orders` table — `gateway_trade_no` source by gateway

| Gateway | Field stored in `gateway_trade_no` | Format |
|---------|-------------------------------------|--------|
| payuni | MerTradeNo | `YO{orderId:06d}{YmdHis}{rand4}` |
| newebpay | MerchantOrderNo | `YO{orderId:06d}{YmdHis}{rand4}` |

Same format, same prefix — webhook handlers can look up Order by this field regardless of gateway.

---

## Incremental Update: 2026-05-05 — OrderFulfillmentService (Plan v2)

### `OrderFulfillmentService` (`app/Services/OrderFulfillmentService.php`)

**單一職責**：把已通過簽章驗證的 paid Order 轉換為 Purchase 記錄，並執行所有相關副作用。為兩個金流 service 共用，避免重複。

**公開介面**：
```php
namespace App\Services;

class OrderFulfillmentService
{
    public function fulfill(Order $order): array;
    // Returns: ['success' => true, 'error' => '', 'idempotent' => bool]
}
```

**完整流程（`fulfill()` 內部）**:
1. **冪等檢查 1**: `$order->status === 'paid'` → 直接 return `['idempotent' => true]`
2. **DB transaction 包圍以下動作**:
   1. eager load `items.course` 與 `user`
   2. 對每個 OrderItem：
      - **冪等檢查 2**: `Purchase::where(user_id, course_id, status='paid')->exists()` → skip
      - 否則建立 Purchase（含 `order_id`、`source = $order->payment_gateway`、`payuni_trade_no` 僅 PayUni 設值）
   3. `$order->update(['status' => 'paid'])`
   4. 清空 cart：`CartItem::whereIn('course_id', $order->items->pluck('course_id'))->delete()`
   5. Drip 觸發（與既有 PayuniService::processNotify 邏輯一致）：
      - `$item->course->course_type === 'drip'` → `DripService::subscribe`
      - 一律 `DripService::checkAndConvert($user, $course)`
   6. `Log::info('OrderFulfillment: completed', [...])`
3. Return `['success' => true, 'error' => '']`

**呼叫者**:
- `PayuniService::processNotify` 在 `MerTradeNo` 前綴為 `YO` 時呼叫
- `NewebPayService::processNotify` 在簽章與 Status 驗證通過後呼叫

**為何不用 Trait**: Trait 無法乾淨地 inject `DripService` 與 `Log`；Service + DI 是 Constitution II 的標準。

**為何不用 Event/Listener**: Constitution X 明確禁止「為簡單流程引入 Event」。本邏輯為同步、結果驅動、需要 return 值給 webhook handler 用，Event 反而增加複雜度。
