# Data Model: 009-cart-checkout

**Feature Branch**: `009-cart-checkout`
**Created**: 2026-05-06

---

## Existing Tables (Reference)

### `purchases` (existing, altered)
| Column | Type | Nullable | Notes |
|--------|------|----------|-------|
| id | bigint PK | no | |
| user_id | FK → users | no | |
| course_id | FK → courses | no | |
| portaly_order_id | varchar | yes | |
| payuni_trade_no | varchar | yes | UNIQUE |
| buyer_email | varchar(255) | yes | |
| amount | decimal | no | |
| currency | varchar(10) | no | |
| coupon_code | varchar | yes | |
| discount_amount | decimal | yes | |
| status | enum | no | paid \| refunded |
| source | varchar | yes | |
| type | enum | no | paid \| system_assigned \| gift |
| webhook_received_at | timestamp | yes | |
| **order_id** | bigint unsigned FK → orders | **yes** | **new column; SET NULL on delete** |
| created_at | timestamp | no | |
| updated_at | timestamp | no | |

UNIQUE constraint: `(user_id, course_id)`

### `site_settings` (existing, read-only)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| key | varchar | UNIQUE |
| value | text | |

Used to store payment gateway credentials (see [SiteSetting Keys](#sitesetting-keys-for-payment-credentials)).

---

## New Tables

### `cart_items`

| Column | Type | Nullable | Notes |
|--------|------|----------|-------|
| id | bigint PK | no | |
| user_id | FK → users | no | ON DELETE CASCADE |
| course_id | FK → courses | no | ON DELETE CASCADE |
| created_at | timestamp | no | set manually; no `updated_at` |

**Constraints:** UNIQUE(`user_id`, `course_id`)

**Migration note:** No `updated_at` column. Model uses `$timestamps = false` and sets `created_at` in `booted()`.

```sql
CREATE TABLE cart_items (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    course_id   BIGINT UNSIGNED NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_items_user    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_course  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY uq_cart_user_course (user_id, course_id)
);
```

---

### `orders`

| Column | Type | Nullable | Notes |
|--------|------|----------|-------|
| id | bigint PK | no | |
| user_id | FK → users | yes | null = guest order |
| buyer_name | varchar(100) | no | snapshot from checkout form |
| buyer_email | varchar(255) | no | snapshot |
| buyer_phone | varchar(20) | no | snapshot |
| total_amount | decimal(10,2) | no | |
| currency | varchar(10) | no | default `'TWD'` |
| payment_gateway | varchar(20) | no | `'payuni'` or `'newebpay'` |
| merchant_order_no | varchar(30) | no | UNIQUE; format: `ord_{id}_{YYMMdd}` e.g. `ord_42_250506` |
| status | enum | no | `pending` \| `paid` \| `failed`; default `pending` |
| gateway_trade_no | varchar(100) | yes | PayUni MerTradeNo or NewebPay TradeNo from gateway |
| webhook_received_at | timestamp | yes | |
| created_at | timestamp | no | |
| updated_at | timestamp | no | |

**Important:** `merchant_order_no` requires the auto-increment `id`, so it is generated in a two-step process inside a transaction: INSERT the row first (with a placeholder or NULL), then UPDATE `merchant_order_no`.

**State transitions:**

```
[created] → pending ──→ paid    (webhook success)
                    └──→ failed  (gateway error / timeout)
```

- `pending → paid`: via webhook (PayUni notify / NewebPay NotifyURL)
- `pending → failed`: explicit mark on ReturnURL failure (optional)
- `paid` is terminal — no reversal. Refunds are handled at the `purchases` level.

```sql
CREATE TABLE orders (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NULL,
    buyer_name          VARCHAR(100) NOT NULL,
    buyer_email         VARCHAR(255) NOT NULL,
    buyer_phone         VARCHAR(20) NOT NULL,
    total_amount        DECIMAL(10,2) NOT NULL,
    currency            VARCHAR(10) NOT NULL DEFAULT 'TWD',
    payment_gateway     VARCHAR(20) NOT NULL,
    merchant_order_no   VARCHAR(30) NOT NULL,
    status              ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    gateway_trade_no    VARCHAR(100) NULL,
    webhook_received_at TIMESTAMP NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_orders_merchant_order_no (merchant_order_no)
);
```

---

### `order_items`

| Column | Type | Nullable | Notes |
|--------|------|----------|-------|
| id | bigint PK | no | |
| order_id | FK → orders | no | ON DELETE CASCADE |
| course_id | FK → courses | no | ON DELETE RESTRICT |
| course_name | varchar(255) | no | snapshot at order time |
| unit_price | decimal(10,2) | no | snapshot at order time |
| created_at | timestamp | no | no `updated_at` |

**Migration note:** No `updated_at`. Model uses `$timestamps = false` and sets `created_at` in `booted()`.

```sql
CREATE TABLE order_items (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id    BIGINT UNSIGNED NOT NULL,
    course_id   BIGINT UNSIGNED NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    unit_price  DECIMAL(10,2) NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_items_order  FOREIGN KEY (order_id)  REFERENCES orders(id)  ON DELETE CASCADE,
    CONSTRAINT fk_order_items_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT
);
```

---

## Altered Tables

### `courses` — add `payment_gateway`

```sql
ALTER TABLE courses
    ADD COLUMN payment_gateway VARCHAR(20) NOT NULL DEFAULT 'payuni'
    AFTER portaly_product_id;
```

Values: `'payuni'` (default) or `'newebpay'`. Portaly courses set this to empty/ignored; the admin UI hides the selector when `portaly_product_id` is set.

### `purchases` — add `order_id`

```sql
ALTER TABLE purchases
    ADD COLUMN order_id BIGINT UNSIGNED NULL AFTER payuni_trade_no;

ALTER TABLE purchases
    ADD CONSTRAINT fk_purchases_order_id
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL;
```

---

## Eloquent Models

### `CartItem` (`app/Models/CartItem.php`)

```php
class CartItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'course_id'];

    protected static function booted(): void
    {
        static::creating(function (CartItem $item) {
            $item->created_at = now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }
}
```

### `Order` (`app/Models/Order.php`)

```php
class Order extends Model
{
    protected $fillable = [
        'user_id', 'buyer_name', 'buyer_email', 'buyer_phone',
        'total_amount', 'currency', 'payment_gateway',
        'merchant_order_no', 'status', 'gateway_trade_no',
        'webhook_received_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount'        => 'decimal:2',
            'webhook_received_at' => 'datetime',
            'status'              => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'pending');
    }

    public function scopePaid(Builder $q): Builder
    {
        return $q->where('status', 'paid');
    }

    public function isPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'paid',
        );
    }
}
```

### `OrderItem` (`app/Models/OrderItem.php`)

```php
class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'course_id', 'course_name', 'unit_price'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OrderItem $item) {
            $item->created_at = now();
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
```

---

## Service Interfaces

### `CartService` (`app/Services/CartService.php`)

```php
class CartService
{
    /**
     * Add a course to a user's cart.
     * Idempotent: returns existing CartItem if already present.
     */
    public function add(int $userId, int $courseId): ?CartItem;

    /**
     * Remove a course from a user's cart.
     *
     * @return bool true if removed, false if not found
     */
    public function remove(int $userId, int $courseId): bool;

    /**
     * Get all cart items for a user with course eager-loaded.
     *
     * @return Collection<CartItem>
     */
    public function getItems(int $userId): Collection;

    /**
     * Count items in a user's cart.
     */
    public function count(int $userId): int;

    /**
     * Merge a guest cart (array of course_ids) into a user's cart.
     * Skips courses already in cart or already purchased.
     */
    public function mergeGuestCart(int $userId, array $courseIds): void;

    /**
     * Remove specific course_ids from cart after successful payment.
     */
    public function clearPurchased(int $userId, array $courseIds): void;
}
```

### `CheckoutService` (`app/Services/CheckoutService.php`)

```php
class CheckoutService
{
    /**
     * Create an Order + OrderItems from a cart or single-course purchase.
     * buyer = ['name' => string, 'email' => string, 'phone' => string]
     * Returns Order with merchant_order_no set.
     */
    public function createOrder(?int $userId, array $courseIds, array $buyer): Order;

    /**
     * Determine which payment gateway to use.
     * Single item with newebpay course → 'newebpay'; otherwise → 'payuni'.
     */
    public function routeGateway(Order $order): string;

    /**
     * Called by webhook handlers on payment success.
     * Creates Purchase records from OrderItems, sets Order.status = 'paid'.
     *
     * @return array<Purchase>
     */
    public function fulfillOrder(Order $order, string $gatewayTradeNo, string $gateway): array;
}
```

### `NewebpayService` (`app/Services/NewebpayService.php`)

```php
class NewebpayService
{
    /**
     * Reads credentials from SiteSetting first, falls back to config().
     */
    public function __construct();

    /**
     * Build MPG form fields for frontend POST submission.
     *
     * @return array{endpoint: string, fields: array{MerchantID: string, TradeInfo: string, TradeSha: string, Version: string}}
     */
    public function buildPaymentForm(Order $order, array $buyer): array;

    /**
     * Verify TradeSha from NotifyURL / ReturnURL.
     */
    public function verifyTradeSha(string $tradeSha, string $tradeInfo): bool;

    /**
     * Decrypt AES-256-CBC TradeInfo → decoded params array.
     */
    public function decryptTradeInfo(string $tradeInfo): array;
}
```

---

## SiteSetting Keys

| Key | Fallback `config()` / `env()` path |
|-----|-------------------------------------|
| `payuni_merchant_id` | `config('services.payuni.merchant_id')` |
| `payuni_hash_key` | `config('services.payuni.hash_key')` |
| `payuni_hash_iv` | `config('services.payuni.hash_iv')` |
| `newebpay_merchant_id` | `config('services.newebpay.merchant_id')` |
| `newebpay_hash_key` | `config('services.newebpay.hash_key')` |
| `newebpay_hash_iv` | `config('services.newebpay.hash_iv')` |
| `newebpay_env` | `config('services.newebpay.env', 'sandbox')` |
| `meta_pixel_id` | `env('META_PIXEL_ID', '')` |

SiteSetting value takes priority over `.env`. `.env` is initial default / fallback only. If `meta_pixel_id` resolves to empty string, the entire Pixel `<script>` block in `app.blade.php` is omitted.

---

## Frontend Props Schema

### `Cart/Index.vue`

```typescript
interface CartProps {
  items: CartItem[]
  total: number
}

interface CartItem {
  id: number            // cart_item.id
  course: {
    id: number
    name: string
    price: number
    thumbnail: string | null
    payment_gateway: string
  }
}
```

### `Checkout/Index.vue`

```typescript
interface CheckoutProps {
  items: CartItem[]     // same CartItem shape as above
  total: number
  prefill: {
    name: string | null
    email: string | null
    phone: string | null
  }                     // populated from authenticated user if logged in
}
```

### `Payment/Success.vue`

```typescript
interface PaymentSuccessProps {
  order: {
    merchant_order_no: string
    buyer_name: string
    buyer_email: string
    buyer_phone: string
    total_amount: string
    payment_gateway: string
    items: {
      course_name: string
      unit_price: string
    }[]
  }
  isLoggedIn: boolean
}
```

### Shared Props Addition (`HandleInertiaRequests`)

```typescript
// Added to existing shared props object
cartCount: number   // authenticated users: server-side count; guests: 0 (badge driven by localStorage on client)
```
