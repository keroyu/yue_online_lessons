# API Contracts: 009-cart-checkout

**Feature Branch**: `009-cart-checkout`
**Created**: 2026-05-06

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
    "phone": "0912345678"
  },
  "course_ids": [12],
  "agree_terms": true
}
```

Validation:
- `buyer.name`: required, string, max:100
- `buyer.email`: required, email, max:255
- `buyer.phone`: required, string, max:20
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
Response `409`: a course in `course_ids` has already been purchased by this email (detected after `getOrCreateUser`). Body: `{ "message": "已購買的課程無法重複購買", "courses": [12] }`.

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

Behavior: Look up `Order` with `order_items` by `merchant_order_no`. If not found or `status != 'paid'`, abort 404.

Response props:
```json
{
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

---

## Admin: Course Payment Gateway

### `GET /admin/courses/{course}/edit`

Existing route. Response props extended to include:

```json
{
  "course": {
    "payment_gateway": "payuni"
  }
}
```

The Vue component (`CourseForm.vue`) renders a `<select>` for `payment_gateway` (options: `payuni`, `newebpay`) that is hidden/cleared when `portaly_product_id` is non-empty.

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
    "hash_iv": ""
  },
  "newebpay": {
    "merchant_id": "MS1234567890",
    "hash_key": "",
    "hash_iv": "",
    "env": "sandbox"
  },
  "meta_pixel_id": "1287511383482442"
}
```

**Note**: `hash_key` and `hash_iv` are returned as empty strings (never exposed in response per FR-022). The UI shows `<input type="password">` with placeholder "已儲存，輸入新值以更新". `meta_pixel_id` is returned as-is (not sensitive).

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
  "meta_pixel_id": "1287511383482442"
}
```

Validation: all fields optional (blank = keep existing). `newebpay_env` in `['sandbox', 'production']` if provided. `meta_pixel_id`: string, max:30, numeric characters only if provided (Facebook Pixel IDs are numeric strings).

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
