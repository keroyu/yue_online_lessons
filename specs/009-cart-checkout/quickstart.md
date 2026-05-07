# Quickstart: 009-cart-checkout

**Feature Branch**: `009-cart-checkout`
**Created**: 2026-05-06

Step-by-step guide for implementing the cart + checkout feature. Follow phases A–K in order. Each phase is independently deployable (no half-finished states).

---

## Prerequisites

- Branch `009-cart-checkout` checked out.
- `php artisan migrate:fresh --seed` passes on current schema.
- `npm run dev` running.
- PayUni sandbox credentials in `.env`: `PAYUNI_MERCHANT_ID`, `PAYUNI_HASH_KEY`, `PAYUNI_HASH_IV`.
- NewebPay sandbox credentials in `.env`: `NEWEBPAY_MERCHANT_ID`, `NEWEBPAY_HASH_KEY`, `NEWEBPAY_HASH_IV`.

---

## Phase A — Database Migrations & Models

**Goal**: Schema in place; Eloquent models usable.

1. Create migration: `php artisan make:migration create_cart_items_table`
   - Table: `cart_items` — see `data-model.md` for DDL.
   - No `updated_at`. UNIQUE(`user_id`, `course_id`).

2. Create migration: `php artisan make:migration create_orders_table`
   - Table: `orders` — see `data-model.md` for DDL.
   - `merchant_order_no` UNIQUE, nullable until two-step INSERT.

3. Create migration: `php artisan make:migration create_order_items_table`
   - Table: `order_items` — see `data-model.md` for DDL.
   - No `updated_at`.

4. Create migration: `php artisan make:migration add_payment_gateway_to_courses_table`
   - `ALTER TABLE courses ADD COLUMN payment_gateway VARCHAR(20) NOT NULL DEFAULT 'payuni'`.

5. Create migration: `php artisan make:migration add_order_id_to_purchases_table`
   - `ALTER TABLE purchases ADD COLUMN order_id BIGINT UNSIGNED NULL`.
   - Add FK: `ON DELETE SET NULL`.

6. Create models (interfaces documented in `data-model.md`):
   - `app/Models/CartItem.php`
   - `app/Models/Order.php`
   - `app/Models/OrderItem.php`

7. Update `app/Models/Course.php`:
   - Add `payment_gateway` to `$fillable`.
   - Add `cartItems()` HasMany relationship.

8. Update `app/Models/Purchase.php`:
   - Add `order_id` to `$fillable`.
   - Add `order()` BelongsTo relationship.

9. Run `php artisan migrate` — confirm all tables created.

---

## Phase B — CartService + CartController

**Goal**: Server-side cart CRUD working for authenticated users.

1. Create `app/Services/CartService.php`.
   - Methods: `add()`, `remove()`, `getItems()`, `count()`, `mergeGuestCart()`, `clearPurchased()`.
   - All throw on invalid courseId; idempotent add returns existing item.

2. Create `app/Http/Controllers/CartController.php`.
   - `add`: `POST /api/cart/add` — validates, calls `CartService::add()`, returns `{ cartCount }`.
   - `remove`: `DELETE /api/cart/{courseId}` — calls `CartService::remove()`, returns `{ cartCount }`.
   - `merge`: `POST /api/cart/merge` — calls `CartService::mergeGuestCart()`, returns `{ cartCount }`.

3. Create `app/Http/Requests/AddToCartRequest.php`:
   - Validates `course_id`; ensures course is published, not Portaly, not free, not already purchased.

4. Register routes in `routes/api.php`:
   ```php
   Route::middleware('auth:web')->group(function () {
       Route::post('/cart/add', [CartController::class, 'add']);
       Route::delete('/cart/{courseId}', [CartController::class, 'remove']);
       Route::post('/cart/merge', [CartController::class, 'merge']);
   });
   ```

5. Add `cartCount` to `HandleInertiaRequests::share()`:
   ```php
   'cartCount' => fn () => auth()->check()
       ? app(CartService::class)->count(auth()->id())
       : 0,
   ```

6. Create `GET /cart` Inertia route → `CartController@index` → renders `Cart/Index.vue`.

7. Manual test: add a course via curl or Postman, check `cart_items` row and `cartCount`.

---

## Phase C — Payment Gateway Credentials Admin

**Goal**: Admin can set/update credentials stored in `site_settings`.

1. Create `app/Http/Controllers/Admin/SettingsController.php` (or add to existing one):
   - `showPayment()`: loads credential keys via `SiteSetting::getMany([...])`, masks hash_key/hash_iv as empty string in props.
   - `updatePayment()`: for each non-empty submitted field, calls `SiteSetting::set(key, value)`.

2. Create `resources/js/Pages/Admin/Settings/Payment.vue`:
   - Two sections: PayUni fields, NewebPay fields.
   - `<input type="password">` for HashKey and HashIV with placeholder "已儲存，輸入新值以更新".
   - `<input type="text">` for MerchantID.
   - `<select>` for NewebPay env (sandbox / production).
   - Submit via Inertia `useForm().post()`.

3. Register routes in `routes/web.php` under admin middleware:
   ```php
   Route::get('/admin/settings/payment', [SettingsController::class, 'showPayment']);
   Route::post('/admin/settings/payment', [SettingsController::class, 'updatePayment']);
   ```

4. Add nav link to admin sidebar.

5. Update `PayuniService::__construct()` to read from `SiteSetting` with `.env` fallback:
   ```php
   $this->merKey = SiteSetting::get('payuni_hash_key', config('services.payuni.hash_key', ''));
   $this->merIV  = SiteSetting::get('payuni_hash_iv',  config('services.payuni.hash_iv',  ''));
   ```

6. Manual test: set credentials via UI, confirm `site_settings` rows written.

---

## Phase D — Sales Page Update (`Course/Show.vue`)

**Goal**: Course page shows correct buttons per course type; guest cart triggers work.

1. Read existing `Course/Show.vue` structure.

2. Add conditional button logic:
   - `portaly_product_id` set → "立即購買" (external link to Portaly).
   - `price = 0` → "免費領取" (existing flow).
   - `type = high_ticket` → "預約諮詢" (existing flow).
   - Otherwise (PayUni/NewebPay course):
     - If user already owns course → "進入課程".
     - If course in cart (server or guest) → "前往購物車".
     - Otherwise → "加入購物車" + "直接購買" buttons.

3. Create/update composable `resources/js/composables/useCart.js`:
   - `addToCart(courseId)`: if logged in → `POST /api/cart/add`; if guest → append to `localStorage.guest_cart`.
   - `buyNow(courseId)`: same as `addToCart` then redirect to `/checkout`.
   - Fires `fbq('track', 'AddToCart', {...})` on success.

4. Pass required props from `CourseController@show`:
   - `isInCart` (bool): server-side check for authenticated users; `false` for guests (client-side handles).
   - `isOwned` (bool): check `purchases` table.

5. Test: verify all 4 course types show correct buttons; guest add-to-cart updates `localStorage`.

---

## Phase E — Cart Frontend (`Cart/Index.vue`)

**Goal**: Cart page renders items, allows removal, shows total, triggers `InitiateCheckout`.

1. Create `resources/js/Pages/Cart/Index.vue`.
   - For authenticated users: render `$props.items` from Inertia props.
   - For guests: read `localStorage.guest_cart` on `onMounted`, display items.
   - Show course thumbnail, name, price per item.
   - "移除" button → `DELETE /api/cart/{courseId}` (logged-in) or splice from `localStorage` (guest).
   - "前往結帳" button → navigate to `/checkout`.
   - Total computed from items.
   - Empty state message with link to course list.

2. Fire `fbq('track', 'InitiateCheckout', {...})` on page mount.

3. Add navigation cart badge to `Navigation.vue` (or `app.blade.php` nav):
   - Logged-in: `$page.props.cartCount`.
   - Guest: `computed(() => JSON.parse(localStorage.guest_cart || '[]').length)`.

4. Test: add two courses, visit `/cart`, confirm display and `InitiateCheckout` fires.

---

## Phase F — CheckoutService + CheckoutController

**Goal**: `POST /api/checkout/initiate` creates Order and returns gateway form fields.

1. Create `app/Services/CheckoutService.php`:
   - `createOrder(?int $userId, array $courseIds, array $buyer): Order`
     - `DB::transaction`: INSERT order with placeholder `merchant_order_no`, then UPDATE with `ord_{id}_{YYMMdd}`.
     - Insert `order_items` for each courseId (snapshot `course_name`, `unit_price`).
   - `routeGateway(Order $order): string`
     - Single item with `newebpay` → `'newebpay'`; else → `'payuni'`.
   - `fulfillOrder(Order $order, string $gatewayTradeNo, string $gateway): array`
     - Check `$order->status === 'paid'` → return early (idempotency Layer 1).
     - `DB::transaction`:
       - Update `Order` status to `paid`, set `gateway_trade_no`, `webhook_received_at`.
       - For each `OrderItem`: find-or-create user, call `Purchase::firstOrCreate([user_id, course_id], [...])`.
       - For each new Purchase: call `DripService::checkAndConvert($user, $course)`.
     - Return array of created purchases.

2. Create `app/Http/Controllers/CheckoutController.php`:
   - `initiate(CheckoutRequest $request)`:
     - Validate buyer fields, `agree_terms`, `course_ids`.
     - Call `CheckoutService::createOrder()`.
     - Route gateway, call `PayuniService::buildPaymentForm()` or `NewebpayService::buildPaymentForm()`.
     - Return JSON `{ gateway, endpoint, fields }`.

3. Create `app/Http/Requests/CheckoutRequest.php`.

4. Create `GET /checkout` route → `CheckoutController@show` → renders `Checkout/Index.vue`.

5. Register `POST /api/checkout/initiate` in `routes/api.php`.

6. Test: call initiate with valid payload, confirm Order + OrderItems rows, confirm response has correct fields for the routing case.

---

## Phase G — Checkout Frontend (`Checkout/Index.vue`)

**Goal**: Checkout page shows cart summary, collects buyer details, auto-submits to gateway.

1. Create `resources/js/Pages/Checkout/Index.vue`:
   - Show order summary (same items as cart page).
   - Form: 姓名 (required), Email (required), 電話 (required), 同意服務條款 checkbox.
   - "前往付款" disabled until all fields valid and checkbox checked (use `useForm().processing` guard).
   - On submit: `POST /api/checkout/initiate` → on `200`, build a hidden HTML form with returned `endpoint` + `fields`, auto-submit it (triggers browser redirect to gateway).
   - Guest: reads `localStorage.guest_cart` for course_ids; logged-in: uses server props `items`.
   - Pre-fill name/email/phone from `$props.prefill` if available.

2. Handle `422` (validation error): show Inertia form errors inline.

3. Handle `409` (already purchased): show error banner.

4. After receiving gateway response, set `submitting = true` and auto-submit form to prevent double-click.

5. Test: fill form, submit, confirm redirect to PayUni / NewebPay sandbox URL.

---

## Phase H — PayUni Order Flow (Refactor `PayuniService`)

**Goal**: PayuniService handles new `ord_` MerTradeNo format; legacy `YC` path preserved.

1. Update `PayuniService::buildPaymentForm()` to accept `Order` instead of `Course`:
   - Use `$order->merchant_order_no` as `MerTradeNo`.
   - Use `$order->total_amount` as `TradeAmt`.
   - Use `$order->buyer_email` as `UsrMail`.
   - `ProdDesc`: first `order_items[0].course_name` + (if multiple items) " 等 N 門課程".

2. Update `PayuniService::processNotify()`:
   - Check MerTradeNo prefix: `str_starts_with($merTradeNo, 'ord_')` → new path.
   - New path: look up `Order::where('merchant_order_no', $merTradeNo)->firstOrFail()`.
   - Call `CheckoutService::fulfillOrder($order, $tradeNo, 'payuni')`.
   - Legacy `YC` path: unchanged.

3. Update `PayuniController::return()`:
   - On success: redirect to `/payment/success?order={merchant_order_no}`.
   - On failure: redirect to `/cart` with flash `payment_failed`.

4. Update `PayuniService::__construct()` to read credentials from `SiteSetting` (Phase C).

5. Test: complete PayUni sandbox payment, verify Order + Purchases created.

---

## Phase I — NewebpayService + Controllers

**Goal**: Full NewebPay MPG flow implemented.

1. Create `app/Services/NewebpayService.php`:
   - `__construct()`: reads credentials from `SiteSetting` with `.env` fallback.
   - `buildPaymentForm(Order $order, array $buyer): array`:
     - Build `$tradeParams` array with: `MerchantID`, `RespondType=JSON`, `TimeStamp`, `Version=2.3`, `MerchantOrderNo`, `Amt` (int), `ItemDesc`, `Email`, `LoginType=0`.
     - `TradeInfo = bin2hex(openssl_encrypt(http_build_query($tradeParams), 'AES-256-CBC', $hashKey, OPENSSL_RAW_DATA, $hashIV))`.
     - `TradeSha = strtoupper(hash('sha256', "HashKey={$hashKey}&{$tradeInfo}&HashIV={$hashIV}"))`.
     - Return `{ endpoint, fields: { MerchantID, TradeInfo, TradeSha, Version: "2.3" } }`.
   - `verifyTradeSha(string $tradeSha, string $tradeInfo): bool`.
   - `decryptTradeInfo(string $tradeInfo): array`:
     - `openssl_decrypt(hex2bin($tradeInfo), 'AES-256-CBC', $hashKey, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $hashIV)`.
     - Strip PKCS7 padding manually.
     - `parse_str()` the result.

2. Create `app/Http/Controllers/Payment/NewebpayController.php`:
   - `notify(Request $request)`:
     - Verify `TradeSha` — if fail, log and return `response('SUCCESS')`.
     - Decrypt `TradeInfo`.
     - Check `Status = 'SUCCESS'`.
     - Look up Order by `merchant_order_no = Result.MerchantOrderNo`.
     - Call `CheckoutService::fulfillOrder(order, tradeNo, 'newebpay')`.
     - Return `response('SUCCESS')`.
   - `return(Request $request)`:
     - Decrypt `TradeInfo`.
     - If success: redirect to `/payment/success?order=...`.
     - Else: redirect to `/cart` with flash `payment_failed`.

3. Register routes in `routes/web.php`:
   ```php
   Route::post('/api/webhooks/newebpay', [NewebpayController::class, 'notify'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
   Route::post('/payment/newebpay/return', [NewebpayController::class, 'return'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
   ```

4. Add `newebpay` to CSRF exceptions in `VerifyCsrfToken` (or use `withoutMiddleware` above).

5. Test: NewebPay sandbox MPG payment, verify Order status, Purchase records.

---

## Phase J — Payment Success Page

**Goal**: `/payment/success?order=xxx` page with order summary and Pixel event.

1. Create `app/Http/Controllers/Payment/SuccessController.php`:
   - `show(Request $request)`:
     - Read `order` query param.
     - Look up `Order::where('merchant_order_no', $orderNo)->with('items')->firstOrFail()`.
     - If `status != 'paid'` → abort 404.
     - Clear cart: if `auth()->check()`, call `CartService::clearPurchased(userId, courseIds)`.
     - Pass props: `order` summary, `isLoggedIn`.

2. Create `resources/js/Pages/Payment/Success.vue`:
   - Display: 訂單編號, Email, 姓名, 電話, 總金額, 課程清單 (course name + price each).
   - On mount: `fbq('track', 'Purchase', { value: total, currency: 'TWD' })`.
   - If `isLoggedIn` → "前往我的課程" button (link to `/member/learning`).
   - If guest → "登入查看課程" button (link to `/login?hint=purchase`).
   - If `localStorage.guest_cart` exists, clear it on mount.

3. Register route: `Route::get('/payment/success', [SuccessController::class, 'show'])`.

4. Test: verify page shows correct data, Pixel fires, cart cleared.

---

## Phase K — Admin CourseForm (`payment_gateway` field)

**Goal**: Admin can select PayUni or NewebPay when editing a non-Portaly course.

1. Open `resources/js/Pages/Admin/Courses/Form.vue` (or `Create.vue` / `Edit.vue`).

2. Add `<select>` for `payment_gateway` with options: `payuni` (PayUni), `newebpay` (藍新金流).

3. Conditional visibility: hide and clear `payment_gateway` when `form.portaly_product_id` is truthy. Restore default `'payuni'` when `portaly_product_id` is cleared. Use `watch` on `portaly_product_id`.

4. Update `app/Http/Requests/Admin/StoreCourseRequest.php` and `UpdateCourseRequest.php`:
   - Add `payment_gateway`: `Rule::in(['payuni', 'newebpay'])`, required unless `portaly_product_id` present.

5. Update `CoursesController@store` and `@update` to save `payment_gateway`.

6. Seed existing courses with `payment_gateway = 'payuni'` via migration default (already handled by `DEFAULT 'payuni'`).

7. Test: edit a course, change to `newebpay`, save, confirm DB value. Set Portaly ID, confirm selector hidden.

---

## Phase L — Portaly Webhook Key 後台設定（US8）

**Goal**: 管理員可在後台金流設定頁設定 Portaly Webhook Key，取代 `.env` 的 `PORTALY_WEBHOOK_KEY`。`PortalyWebhookService` 優先讀取 DB 值，不中斷現有 webhook 流程。

**Scope**: 僅修改憑證讀取層，不動 `PortalyController`、webhook 路由、或 `PortalyWebhookService` 的簽章驗證邏輯本身。

**Module 001 files touched** (per repo_map.md):
- `app/Services/PortalyWebhookService.php`
- `config/services.php`
- `.env.example`

**Module 009 files touched**:
- `app/Http/Controllers/Admin/SettingsController.php`
- `resources/js/Pages/Admin/Settings/Payment.vue`

---

1. **`config/services.php`** — portaly block:

   ```php
   // Before:
   'portaly' => [
       'webhook_secret' => env('PORTALY_WEBHOOK_SECRET'),
   ],

   // After:
   'portaly' => [
       'webhook_key' => env('PORTALY_WEBHOOK_KEY'),
   ],
   ```

2. **`.env.example`** — rename env var:

   ```
   # Before:
   PORTALY_WEBHOOK_SECRET=

   # After:
   PORTALY_WEBHOOK_KEY=
   ```

   > ⚠️ 同步更新伺服器 `.env`：將既有 `PORTALY_WEBHOOK_SECRET` 值複製到 `PORTALY_WEBHOOK_KEY`，再刪除舊 key。

3. **`app/Services/PortalyWebhookService.php`** — `verifySignature()` 改讀 SiteSetting：

   ```php
   // Before:
   $secret = config('services.portaly.webhook_secret');

   // After:
   $secret = \App\Models\SiteSetting::get('portaly_webhook_key', config('services.portaly.webhook_key'));
   ```

   其餘 HMAC 驗證邏輯完全不動。

4. **`app/Http/Controllers/Admin/SettingsController.php`** — `showPayment()` 加入 portaly preview：

   ```php
   $portalyKey = SiteSetting::get('portaly_webhook_key', '');
   // 在回傳 props 中加入：
   'portaly' => [
       'webhook_key'         => '',            // never expose plaintext
       'webhook_key_preview' => $portalyKey
           ? substr($portalyKey, 0, 5) . str_repeat('*', max(0, strlen($portalyKey) - 5))
           : '',
   ],
   ```

   `updatePayment()` 加入：

   ```php
   if (!empty($validated['portaly_webhook_key'])) {
       SiteSetting::set('portaly_webhook_key', $validated['portaly_webhook_key']);
   }
   ```

5. **`resources/js/Pages/Admin/Settings/Payment.vue`** — 新增「Portaly（Webhook）」區塊：

   ```vue
   <!-- Portaly Webhook Key 區塊，放在 NewebPay 區塊之後、Meta Pixel 之前 -->
   <section>
     <h3 class="...">Portaly（Webhook）</h3>
     <div>
       <label>Webhook Key</label>
       <input
         type="password"
         v-model="form.portaly_webhook_key"
         :placeholder="portaly.webhook_key_preview || '尚未設定'"
       />
     </div>
   </section>
   ```

   `form` 初始值：`portaly_webhook_key: ''`（空白 = 保留既有）。

6. **驗證**：
   - 後台填入新 Portaly Webhook Key → 送出 → 確認 `site_settings` 有 `portaly_webhook_key` 記錄。
   - 用正確金鑰打一次模擬 Portaly webhook → `POST /api/webhooks/portaly` 回應 200、`success: true`。
   - 清空 `site_settings` 中的 key → 再打 webhook → 系統 fallback 使用 `.env` 值，仍驗證通過。

---

## Verification Checklist (Post-implementation)

- [ ] `php artisan test` passes (no regressions).
- [ ] Guest add-to-cart stores to `localStorage.guest_cart`, badge updates without reload.
- [ ] Login merges guest cart, `localStorage.guest_cart` cleared.
- [ ] Cart persists after logout + login (SC-005).
- [ ] PayUni sandbox purchase creates Order + Purchases, opens `/payment/success`.
- [ ] NewebPay sandbox purchase creates Order + Purchases, opens `/payment/success`.
- [ ] Duplicate webhook → no duplicate Purchase (SC-009).
- [ ] Portaly course shows "立即購買" only (SC-003).
- [ ] Free course shows "免費領取" only (SC-004).
- [ ] Admin sets NewebPay credentials, saves, credentials persist in `site_settings`.
- [ ] Admin sets course `payment_gateway = newebpay`, frontend routes to NewebPay.
- [ ] Meta Pixel `AddToCart`, `InitiateCheckout`, `Purchase` visible in FB Events Manager.
- [ ] Guest checkout with existing email silently binds to existing account.
- [ ] Payment failure redirects to `/cart` with error message.
- [ ] Transaction admin (`/admin/transactions`) correctly shows `source = 'payuni'` / `'newebpay'`.
- [ ] Admin sets Portaly Webhook Key via `/admin/settings/payment`, value persists in `site_settings` as `portaly_webhook_key` (SC-010).
- [ ] `PortalyWebhookService` reads key from `site_settings` when set; falls back to `.env` `PORTALY_WEBHOOK_KEY` when not set.
