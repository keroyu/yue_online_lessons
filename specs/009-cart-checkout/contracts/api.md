# API Contracts: 009-cart-checkout

**Feature Branch**: `009-cart-checkout`
**Created**: 2026-05-06
**Updated**: 2026-05-06 - 新增 /api/checkout/check-email、/api/checkout/order-status；/payment/success 加入 waiting prop（pending 緩衝）
**Updated**: 2026-05-06 - 確認 GET /admin/settings/payment 回傳 merchant_id 明文；POST 接受 payuni_merchant_id / newebpay_merchant_id；HashKey/HashIV 以空字串代替（FR-022）
**Updated**: 2026-05-07 - GET /admin/courses/{course}/edit 加入 gatewayConfigured prop；CourseForm 改為 pill button；GET /admin/courses/create 亦注入 gatewayConfigured
**Updated**: 2026-05-07 - GET /admin/settings/payment 回應新增 hash_key_preview / hash_iv_preview（前 5 碼 + 星號遮蔽）；空字串表示尚未設定（FR-033）；後台標題格式統一為「中文名稱（英文）」
**Updated**: 2026-05-07 - 增量 US8：GET /admin/settings/payment 回應新增 `portaly` 區塊（含 `webhook_key_preview`）；POST 新增 `portaly_webhook_key` 欄位（FR-034）
**Updated**: 2026-05-07 - POST /api/checkout/initiate 的 `buyer` 物件新增 optional `tax_id` 欄位（恰好 8 位數字 regex 驗證；留空允許）；存入 `orders.tax_id`（FR-036）

All routes follow Laravel conventions. Inertia routes return an Inertia response; API routes return JSON. Authenticated routes use `auth:web` middleware. Admin routes add `role:admin`.

---

## Cart

### `GET /cart`

**Type**: Inertia  
**Auth**: none (public)  
**Component**: `Cart/Index.vue`

Response props:
```json
{
  "items": [
    {
      "id": 3,
      "course": {
        "id": 12,
        "name": "Vue 3 實戰",
        "price": 2980,
        "thumbnail": "/storage/courses/12.jpg",
        "payment_gateway": "payuni"
      }
    }
  ],
  "total": 2980
}
```

Note: For guests, the page renders with `items: []`; the frontend reads `localStorage.guest_cart` and displays items client-side. The server does not attempt to decode guest cart state.

---

### `POST /api/cart/add`

**Type**: JSON API  
**Auth**: `auth:web` (required — guest cart is client-only; this endpoint is for logged-in users)  
**Controller**: `CartController@add`

Request:
```json
{ "course_id": 12 }
```

Validation:
- `course_id`: required, integer, exists in `courses`, `portaly_product_id` must be null, `price > 0`, not already purchased by `auth()->id()`, `status = published`

Response `200`:
```json
{ "cartCount": 2 }
```

Response `409` (already in cart — idempotent):
```json
{ "cartCount": 2, "message": "already_in_cart" }
```

Response `422` (validation error):
```json
{ "message": "課程不可加入購物車", "errors": { "course_id": ["..."] } }
```

---

### `DELETE /api/cart/{courseId}`

**Type**: JSON API  
**Auth**: `auth:web`  
**Controller**: `CartController@remove`

Response `200`:
```json
{ "cartCount": 1 }
```

Response `404`: item not found in user's cart.

---

### `POST /api/cart/merge`

**Type**: JSON API  
**Auth**: `auth:web`  
**Controller**: `CartController@merge`

Called on login to merge guest cart (`localStorage.guest_cart`) into server-side cart.

Request:
```json
{ "course_ids": [12, 15, 20] }
```

Validation: `course_ids` — array of integers, each must exist in `courses`.

Behavior: For each course_id, skip if already in user's cart or already purchased by user. No error for skipped items.

Response `200`:
```json
{ "cartCount": 3 }
```

---

## Checkout

### `GET /checkout`

**Type**: Inertia  
**Auth**: none (public)  
**Component**: `Checkout/Index.vue`

Query: none required. Reads cart server-side if authenticated; for guests, relies on frontend passing cart state (no server query).

Response props:
```json
{
  "items": [
    {
      "id": 3,
      "course": {
        "id": 12,
        "name": "Vue 3 實戰",
        "price": 2980,
        "thumbnail": "/storage/courses/12.jpg",
        "payment_gateway": "payuni"
      }
    }
  ],
  "total": 2980,
  "prefill": {
    "name": "Wang Xiaoming",
    "email": "wx@example.com",
    "phone": "0912345678"
  }
}
```

`prefill` is populated from `auth()->user()` if logged in; otherwise all `null`.

---

### `POST /api/checkout/initiate`

**Type**: JSON API  
**Auth**: none (public)  
**Controller**: `CheckoutController@initiate`

Creates an Order snapshot and returns gateway-specific form fields for frontend auto-submit.

Request:
```json
{
  "buyer": {
    "name": "Wang Xiaoming",
    "email": "wx@example.com",
    "phone": "0912345678",
    "tax_id": "12345678"
  },
  "course_ids": [12],
  "agree_terms": true
}
```

Validation:
- `buyer.name`: required, string, max:100
- `buyer.email`: required, email, max:255
- `buyer.phone`: required, string, max:20
- `buyer.tax_id`: nullable, string, regex `/^\d{8}$/`（恰好 8 位數字；可留空）
- `agree_terms`: required, `true`
- `course_ids`: required, array, min:1; each must exist and be publishable/purchasable

Behavior:
1. Calls `CheckoutService::createOrder()` → creates `orders` + `order_items`, sets `merchant_order_no`.
2. Calls `CheckoutService::routeGateway()` → returns `'payuni'` or `'newebpay'`.
3. For `payuni`: calls `PayuniService::buildPaymentForm(order, buyer)` → `{endpoint, fields}`.
4. For `newebpay`: calls `NewebpayService::buildPaymentForm(order, buyer)` → `{endpoint, fields}`.

Response `200`:
```json
{
  "gateway": "payuni",
  "endpoint": "https://api.payuni.com.tw/api/upp",
  "fields": {
    "MerID": "...",
    "Version": "1.0",
    "EncryptInfo": "...",
    "HashInfo": "..."
  }
}
```

Or for newebpay:
```json
{
  "gateway": "newebpay",
  "endpoint": "https://ccore.newebpay.com/MPG/mpg_gateway",
  "fields": {
    "MerchantID": "...",
    "TradeInfo": "...",
    "TradeSha": "...",
    "Version": "2.3"
  }
}
```

Response `422`: validation errors.  
Response `409`: a course in `course_ids` has already been purchased by this email. Body: `{ "message": "此 Email 已購買過部分課程，無需重複購買。若需存取課程請登入帳號，或聯絡客服。" }`.

---

### `POST /api/checkout/check-email`

**Type**: JSON API  
**Auth**: none (public)  
**Controller**: `CheckoutController@checkEmail`

Pre-submission duplicate purchase check. Called on email field blur in `Checkout/Index.vue`.

Request:
```json
{
  "email": "wx@example.com",
  "course_ids": [12, 15]
}
```

Behavior: Queries `purchases` where `buyer_email = email` OR (`user_id` matches a User found by email) AND `course_id IN course_ids` AND `status = 'paid'`.

Response `200`:
```json
{ "purchased_course_ids": [12] }
```
Empty array means no duplicates found.

---

### `GET /api/checkout/order-status`

**Type**: JSON API  
**Auth**: none (public)  
**Route**: closure in `routes/api.php`

Polling endpoint used by `Payment/Success.vue` when `waiting = true`.

Query param: `order` = `merchant_order_no`

Response `200`:
```json
{ "status": "pending" }
```
Possible values: `"pending"`, `"paid"`, `"not_found"`.

---

## Payment Webhooks

### `POST /api/webhooks/payuni`

**Type**: Webhook (JSON API)  
**Auth**: none (CSRF exempt, verified via `HashInfo`)  
**Controller**: `PayuniController@notify`

Receives PayUni background notification. Controller delegates to `PayuniService::processNotify()`.

New Order-based path (MerTradeNo starts with `ord_`):
1. Verify `HashInfo` — if invalid, log warning, return `1|OK`.
2. Decrypt `EncryptInfo`.
3. Check `Status = 'SUCCESS'` and `TradeStatus = 1` — otherwise return `1|OK`.
4. Look up `Order` by `merchant_order_no = MerTradeNo`.
5. If `Order.status = 'paid'` (idempotency Layer 1) → return `1|OK`.
6. Call `CheckoutService::fulfillOrder(order, tradeNo, 'payuni')`.
7. Return `1|OK`.

Legacy path (MerTradeNo starts with `YC`): preserved as-is.

Response: always `200 1|OK`.

---

### `POST /payment/payuni/return`

**Type**: Browser POST (Inertia redirect)  
**Auth**: none  
**Controller**: `PayuniController@return`

Receives PayUni ReturnURL form POST (after user returns from payment page).

Behavior:
- Decrypt and check `TradeStatus`.
- If success: redirect to `/payment/success?order={merchant_order_no}`.
- If failure/cancel: redirect to `/cart` with flash `payment_failed`.

---

### `POST /api/webhooks/newebpay`

**Type**: Webhook (JSON API)  
**Auth**: none (CSRF exempt, verified via `TradeSha`)  
**Controller**: `NewebpayController@notify`

Receives NewebPay background notification.

1. Verify `TradeSha` (see R-001 formula) — if invalid, log error, return `SUCCESS` (NewebPay requires this to stop retries).
2. Decrypt `TradeInfo` (AES-256-CBC).
3. Check `Status = 'SUCCESS'` — otherwise return `SUCCESS`.
4. Look up `Order` by `merchant_order_no = MerchantOrderNo`.
5. If `Order.status = 'paid'` (idempotency Layer 1) → return `SUCCESS`.
6. Call `CheckoutService::fulfillOrder(order, tradeNo, 'newebpay')`.
7. Return `SUCCESS`.

Response: always `200 SUCCESS` (plain text, no Content-Type override).

---

### `POST /payment/newebpay/return`

**Type**: Browser POST (Inertia redirect)  
**Auth**: none  
**Controller**: `NewebpayController@return`

Receives NewebPay ReturnURL form POST.

Behavior:
- Decrypt `TradeInfo`.
- If `Status = 'SUCCESS'`: redirect to `/payment/success?order={merchant_order_no}`.
- If failure/cancel: redirect to `/cart` with flash `payment_failed`.

---

## Payment Success

### `GET /payment/success`

**Type**: Inertia  
**Auth**: none (public)  
**Component**: `Payment/Success.vue`

Query param: `order` = `merchant_order_no` (e.g., `ord_42_250506`)

Behavior: Look up `Order` with `order_items` by `merchant_order_no`. If not found, abort 404. If `status = 'pending'` (webhook not yet received), return `waiting: true` for frontend polling. If `status = 'paid'`, return full order summary.

Response props (paid):
```json
{
  "waiting": false,
  "order": {
    "merchant_order_no": "ord_42_250506",
    "buyer_name": "Wang Xiaoming",
    "buyer_email": "wx@example.com",
    "buyer_phone": "0912345678",
    "total_amount": "2980.00",
    "payment_gateway": "payuni",
    "items": [
      { "course_name": "Vue 3 實戰", "unit_price": "2980.00" }
    ]
  },
  "isLoggedIn": true
}
```

Response props (pending — webhook not yet arrived):
```json
{
  "waiting": true,
  "order": null,
  "isLoggedIn": true
}
```

---

## Admin: Course Payment Gateway

### `GET /admin/courses/{course}/edit` and `GET /admin/courses/create`

Existing routes. Response props extended to include:

```json
{
  "course": {
    "payment_gateway": "payuni"
  },
  "gatewayConfigured": {
    "payuni": true,
    "newebpay": false
  }
}
```

`gatewayConfigured` is computed by `CourseController::gatewayConfigured()` which checks `SiteSetting` for non-empty `merchant_id` + `hash_key` + `hash_iv` per gateway. `CourseForm.vue` renders `payment_gateway` as pill buttons (not `<select>`); when the selected gateway's `gatewayConfigured[gateway] === false`, a red warning with link to `/admin/settings/payment` is shown inline next to the label. The selector is hidden/cleared when `portaly_product_id` is non-empty.

### `PUT /admin/courses/{course}`

Existing route. `payment_gateway` added to validated fields:
- `payment_gateway`: in `['payuni', 'newebpay']`; required unless `portaly_product_id` is present.

---

## Admin: Payment Credentials Settings

### `GET /admin/settings/payment`

**Type**: Inertia  
**Auth**: `auth:web` + `role:admin`  
**Component**: `Admin/Settings/Payment.vue`

Response props:
```json
{
  "payuni": {
    "merchant_id": "M00001",
    "hash_key": "",
    "hash_iv": "",
    "hash_key_preview": "3oJT3***************************",
    "hash_iv_preview": "NGbzP***********"
  },
  "newebpay": {
    "merchant_id": "MS1234567890",
    "hash_key": "",
    "hash_iv": "",
    "hash_key_preview": "abcde***************************",
    "hash_iv_preview": "",
    "env": "sandbox"
  },
  "portaly": {
    "webhook_key": "",
    "webhook_key_preview": "sk_li***************************"
  },
  "meta_pixel_id": "1287511383482442"
}
```

**Note**: `hash_key` and `hash_iv` are always returned as empty strings (never exposed per FR-022). `hash_key_preview` / `hash_iv_preview` return the first 5 chars of the stored value followed by asterisks (e.g. `"3oJT3***...***"`); empty string means not yet configured. `portaly.webhook_key` is always returned as empty string; `portaly.webhook_key_preview` follows the same masking rule. The UI shows `<input type="password">` with placeholder bound to `preview || '尚未設定'` (FR-033、FR-034). `meta_pixel_id` is returned as-is (not sensitive).

---

### `POST /admin/settings/payment`

**Type**: JSON / Inertia form  
**Auth**: `auth:web` + `role:admin`  
**Controller**: `Admin\SettingsController@updatePayment`

Request:
```json
{
  "payuni_merchant_id": "M00001",
  "payuni_hash_key": "...",
  "payuni_hash_iv": "...",
  "newebpay_merchant_id": "MS1234567890",
  "newebpay_hash_key": "...",
  "newebpay_hash_iv": "...",
  "newebpay_env": "sandbox",
  "portaly_webhook_key": "...",
  "meta_pixel_id": "1287511383482442"
}
```

Validation: all fields optional (blank = keep existing). `newebpay_env` in `['sandbox', 'production']` if provided. `portaly_webhook_key`: string; blank = keep existing (same rule as hash_key/hash_iv). `meta_pixel_id`: string, max:30, numeric characters only if provided (Facebook Pixel IDs are numeric strings).

Behavior: For each non-empty field, call `SiteSetting::set(key, value)`. Blank hash_key/hash_iv skipped. `meta_pixel_id` may be explicitly cleared by saving an empty string (admin wants to disable Pixel).

Response: redirect back with flash success.

---

## Global: Meta Pixel Script Injection

**File**: `resources/views/app.blade.php`

The hardcoded `fbq('init', '1287511383482442')` block is replaced with a conditional Blade snippet:

```blade
@php $pixelId = \App\Models\SiteSetting::get('meta_pixel_id', env('META_PIXEL_ID', '')); @endphp
@if($pixelId)
<!-- Meta Pixel -->
<script>
!function(f,b,e,v,n,t,s){...}(window,...);
fbq('init','{{ $pixelId }}');
fbq('track','PageView');
</script>
<noscript>...</noscript>
@endif
```

If `meta_pixel_id` is empty, the entire block is omitted — no `fbq` global is created, so all `if (window.fbq)` guards in Vue components are safely skipped.
