# API Contracts: 購物車結帳系統 (009-cart-checkout)

**Branch**: `009-cart-checkout` | **Date**: 2026-05-05

## Route Summary

### New API Routes (`routes/api.php`)

| Method | Path | Controller | Auth | Description |
|--------|------|------------|------|-------------|
| GET | `/api/cart` | CartController@index | auth | 取得登入用戶購物車 |
| POST | `/api/cart` | CartController@store | auth | 加入課程至購物車 |
| DELETE | `/api/cart/{cartItem}` | CartController@destroy | auth | 移除購物車項目 |
| POST | `/api/cart/merge` | CartController@merge | auth | 登入後合併 guest cart |
| POST | `/api/checkout/initiate` | CheckoutController@initiate | auth | 建立 Order，回傳金流表單資料 |
| POST | `/api/webhooks/newebpay` | NewebPayController@notify | public | 藍新背景通知（回應 "SUCCESS"） |

### New Web Routes (`routes/web.php`)

| Method | Path | Controller | Auth | Description |
|--------|------|------------|------|-------------|
| GET | `/cart` | CartController@show | — | 購物車 Inertia 頁（guest 可看） |
| GET | `/checkout` | CheckoutController@show | auth | 結帳 Inertia 頁 |
| POST | `/payment/newebpay/return` | NewebPayController@return | — | 藍新前台跳轉（CSRF 略過） |

### Modified Existing Routes

| Route | Change |
|-------|--------|
| `POST /payment/payuni/return` | Success redirect 改為 `/member/learning`（原為 `/member/learning`，邏輯不變但確認統一） |
| `POST /api/payment/payuni/initiate` | 保留但廢棄（舊單一課程流程）；購物車流程改用 `/api/checkout/initiate` |

---

## Endpoint Specifications

### GET /api/cart

**Auth**: session (logged-in user only)

**Response 200**:
```json
{
  "items": [
    {
      "id": 1,
      "course_id": 42,
      "course": {
        "id": 42,
        "title": "課程名稱",
        "display_price": 1980,
        "cover_image_url": "https://...",
        "payment_gateway": "payuni"
      },
      "created_at": "2026-05-05T10:00:00Z"
    }
  ],
  "total": 1980,
  "count": 1
}
```

---

### POST /api/cart

**Auth**: session

**Request body**:
```json
{ "course_id": 42 }
```

**Validation**:
- `course_id`: required, exists in courses, course must be published, not free, not high_ticket, not Portaly

**Response 200** (added or already in cart):
```json
{
  "status": "added",   // or "already_in_cart"
  "count": 2
}
```

**Response 422** (already purchased):
```json
{ "message": "您已購買此課程" }
```

---

### DELETE /api/cart/{cartItem}

**Auth**: session (ownership enforced: cartItem.user_id must equal auth user)

**Response 200**:
```json
{ "count": 1 }
```

**Response 403**: cartItem belongs to another user

---

### POST /api/cart/merge

**Auth**: session

**Request body**:
```json
{ "course_ids": [42, 43, 44] }
```

**Behaviour**: For each course_id — skip if already in cart, skip if already purchased, skip if course ineligible. Silently insert the rest.

**Response 200**:
```json
{ "merged": 2, "skipped": 1, "count": 3 }
```

---

### POST /api/checkout/initiate

**Auth**: session

**Request body**: empty (uses current server-side cart)

**Behaviour**:
1. Load user's CartItems with courses (eager load)
2. Validate: at least 1 item, all courses published, all use same payment_gateway
3. Create `Order` (status: pending) with `OrderItems` (price = course.display_price at this moment)
4. Call `PayuniService::buildCheckoutForm($order)` or `NewebPayService::buildCheckoutForm($order)`
5. Return form data

**Response 200 (PayUni)**:
```json
{
  "gateway": "payuni",
  "action_url": "https://sandbox-api.payuni.com.tw/api/upp",
  "form_fields": {
    "MerID": "...",
    "EncryptInfo": "...",
    "HashInfo": "..."
  }
}
```

**Response 200 (NewebPay)**:
```json
{
  "gateway": "newebpay",
  "action_url": "https://ccore.newebpay.com/MPG/mpg_gateway",
  "form_fields": {
    "MerchantID": "...",
    "TradeInfo": "...",
    "TradeSha": "...",
    "Version": "2.3"
  }
}
```

**Response 422**:
```json
{ "message": "購物車是空的" }
// or
{ "message": "購物車中有無法結帳的課程，已自動移除" }
```

---

### POST /api/webhooks/newebpay (NotifyURL)

**Auth**: none (public, signature verified internally)

**Request**: `application/x-www-form-urlencoded`
```
Status=SUCCESS&MerchantID=...&TradeInfo={AES encrypted}&TradeSha={SHA256}&Version=2.0
```

**Behaviour**:
1. Verify TradeSha (SHA256 of `HashKey={key}&{TradeInfo}&HashIV={iv}`)
2. Decrypt TradeInfo (AES-256-CBC)
3. If Status == "SUCCESS": look up Order by `MerchantOrderNo`, mark `paid`, create Purchase records per OrderItem
4. Remove paid CourseIds from user's CartItems

**Response**: Plain text `"SUCCESS"` with 200 (藍新要求)

---

### POST /payment/newebpay/return (ReturnURL)

**Web route**, CSRF excluded

**Behaviour**: Same pattern as `PayuniController@return`:
- Verify & decrypt payload
- If success: redirect `/member/learning` with success flash
- If failure: redirect `/cart` with error flash ("付款未完成，請再試一次；若仍遇到問題請聯絡客服 themustbig+learn@gmail.com")

---

## Inertia Pages

### GET /cart → `Pages/Cart/Index.vue`

**Props (from CartController@show)**:
- `items`: array of cart items (server-side for logged-in; empty array for guest — guest reads localStorage client-side)
- `total`: integer (server-side total; 0 for guest)

Note: flash is injected automatically via `HandleInertiaRequests` shared props — do NOT manually pass `session('flash')` in this controller.

**Guest behaviour**: Vue component reads localStorage and displays guest cart; shows "登入後結帳" button.

**Page behaviour**: On mount, fire `fbq('track', 'InitiateCheckout', { value: total, currency: 'TWD', num_items: items.length })` — this is the canonical trigger point per spec US3-AS1 ("用戶進入購物車頁").

---

### GET /checkout → `Pages/Checkout/Index.vue`

**Middleware**: auth

**Props**:
- `items`: cart items with course data
- `total`: integer

**Page behaviour**:
1. Display order summary
2. "前往付款" button: POST `/api/checkout/initiate` → receive `{ gateway, action_url, form_fields }` → auto-submit hidden form to `action_url` (each key in `form_fields` becomes a hidden input)

---

## Meta Pixel Events

| Event | Trigger | Data |
|-------|---------|------|
| `AddToCart` | `POST /api/cart` succeeds | `{ content_ids: [course_id], value: display_price, currency: 'TWD' }` |
| `InitiateCheckout` | `/cart` page mounted (spec US3-AS1) | `{ num_items, value: total, currency: 'TWD' }` |
| `Purchase` | PayUni 或藍新付款成功返回頁 | `{ value: order.total_amount, currency: 'TWD' }` |
