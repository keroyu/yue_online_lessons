# Tasks: 購物車結帳系統 (009-cart-checkout)

**Input**: Design documents from `/specs/009-cart-checkout/`
**Prerequisites**: plan.md ✅ spec.md ✅ data-model.md ✅ contracts/api.md ✅ research.md ✅ quickstart.md ✅

**Organization**: Tasks grouped by user story — each phase is independently deployable and testable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no shared dependency)
- **[Story]**: Which user story this task belongs to (US1–US7)
- Exact file paths included in all descriptions

---

## Phase 1: Setup

**Purpose**: Confirm baseline before any new work.

- [X] T001 Confirm branch `009-cart-checkout` is active and `php artisan test` passes with no regressions

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: DB schema + models + config shared by ALL user stories.

⚠️ CRITICAL: No user story work can begin until this phase is complete.

- [X] T002 [P] Create migration `create_cart_items_table` in `database/migrations/` — BIGINT id, user_id FK CASCADE, course_id FK CASCADE, created_at TIMESTAMP (no updated_at), UNIQUE(user_id, course_id)
- [X] T003 [P] Create migration `create_orders_table` in `database/migrations/` — columns per data-model.md; merchant_order_no NULLABLE VARCHAR(30) UNIQUE (nullable to support two-step INSERT→UPDATE pattern; NOT NULL enforced at app layer)
- [X] T004 [P] Create migration `create_order_items_table` in `database/migrations/` — order_id FK→orders CASCADE, course_id FK→courses RESTRICT, course_name/unit_price snapshots, created_at only (no updated_at)
- [X] T005 [P] Create migration `add_payment_gateway_to_courses_table` in `database/migrations/` — `VARCHAR(20) NOT NULL DEFAULT 'payuni'` AFTER portaly_product_id
- [X] T006 [P] Create migration `add_order_id_to_purchases_table` in `database/migrations/` — BIGINT UNSIGNED NULL, FK→orders ON DELETE SET NULL, placed AFTER payuni_trade_no
- [X] T007 Run `php artisan migrate` — confirm all 5 migrations succeed and schema matches data-model.md
- [X] T008 [P] Create `app/Models/CartItem.php` — `$timestamps = false`, `$fillable = ['user_id','course_id']`, `booted()` sets created_at, `user()` BelongsTo, `course()` BelongsTo, `scopeForUser(Builder, int): Builder`
- [X] T009 [P] Create `app/Models/Order.php` — $fillable, casts (total_amount decimal:2, webhook_received_at datetime), `user()` BelongsTo, `items()` HasMany→OrderItem, `scopePending`, `scopePaid`, `isPaid` Attribute
- [X] T010 [P] Create `app/Models/OrderItem.php` — `$timestamps = false`, $fillable, `unit_price` cast decimal:2, `booted()` sets created_at, `order()` BelongsTo, `course()` BelongsTo
- [X] T011 Update `app/Models/Course.php` — add `payment_gateway` to `$fillable`; add `cartItems(): HasMany` relationship
- [X] T012 Update `app/Models/Purchase.php` — add `order_id` to `$fillable`; add `order(): BelongsTo` relationship
- [X] T013 Add `newebpay` block to `config/services.php` — keys: `merchant_id`, `hash_key`, `hash_iv`, `env`; all read from `NEWEBPAY_*` env vars; `env` defaults to `'sandbox'`

**Checkpoint**: `php artisan tinker` can instantiate CartItem, Order, OrderItem without errors.

---

## Phase 3: User Stories 1 & 2 — 加入購物車 / 直接購買 + Portaly 直購 (Priority: P1) 🎯 MVP Entry Point

**Goal (US1)**: PayUni 課程銷售頁顯示「加入購物車」+「直接購買」；已登入用戶加入 server-side cart；未登入用戶加入 localStorage guest cart；觸發 Meta Pixel AddToCart。
**Goal (US2)**: Portaly 課程銷售頁顯示「立即購買」外部連結，不顯示購物車按鈕，行為與重構前相同。

**Independent Test**: 未登入加入 PayUni 課程 → `localStorage.guest_cart` 更新、Navigation badge +1；已登入加入 → `cart_items` 新增一筆、cartCount +1；Portaly 課程頁只顯示「立即購買」外部連結。

- [X] T014 Create `app/Http/Requests/AddToCartRequest.php` — validate `course_id`: exists in courses, `portaly_product_id` IS NULL, `price > 0`, `status = published`, not already purchased by `auth()->id()`
- [X] T015 Create `app/Services/CartService.php` — implement `add(int $userId, int $courseId): ?CartItem` (idempotent), `remove(int $userId, int $courseId): bool`, `getItems(int $userId): Collection` (with course eager-loaded), `count(int $userId): int`
- [X] T016 Create `app/Http/Controllers/CartController.php` — `add(AddToCartRequest)`: 200 `{cartCount}` or 409 `{cartCount, message}`; `remove(int $courseId)`: 200 `{cartCount}` or 404; `index()`: Inertia response with `items` + `total` for authenticated users (guests get `items:[]`)
- [X] T017 Register cart API routes in `routes/api.php` — `auth:web` middleware group: `POST /cart/add` → CartController@add, `DELETE /cart/{courseId}` → CartController@remove
- [X] T018 Register `GET /cart` in `routes/web.php` → CartController@index (public, no auth)
- [X] T019 Add `cartCount` to `app/Http/Middleware/HandleInertiaRequests.php` `share()` — `'cartCount' => fn () => auth()->check() ? app(CartService::class)->count(auth()->id()) : 0`
- [X] T020 Create `resources/js/composables/useCart.js` — `addToCart(courseId)`: auth → `POST /api/cart/add`; guest → push to `localStorage.guest_cart` array; on success fire `window.fbq && fbq('track','AddToCart',{content_ids:[courseId], value, currency:'TWD', content_type:'product'})`; `buyNow(courseId)`: addToCart then `router.visit('/checkout')`
- [X] T021 [US1] Update `resources/js/Pages/Course/Show.vue` — **remove all existing buyer form fields (email, phone, agree_terms checkbox) per FR-019** (these move exclusively to `/checkout`); replace buy-button section with full conditional logic: `portaly_product_id` → 「立即購買」external link (US2); `price==0` → 「免費領取」unchanged; `type=='high_ticket'` → 「預約諮詢」unchanged; otherwise: `isOwned` → 「進入課程」; `isInCart` → 「前往購物車」; else → 「加入購物車」+ 「直接購買」using useCart composable
- [X] T022 [US1] Update `app/Http/Controllers/CourseController.php` `show()` — add `isInCart` (bool, CartService check for auth users, false for guests) and `isOwned` (bool, purchases check) to Inertia response props
- [X] T023 [US1] Update `resources/js/Components/Layout/Navigation.vue` — add cart icon with badge: auth → `$page.props.cartCount`; guest → `computed(() => JSON.parse(localStorage.getItem('guest_cart') || '[]').length)`

**Checkpoint**: PayUni 課程加入購物車流程完整。Portaly 課程按鈕行為與重構前相同（US2 satisfied）。Badge 即時更新。

---

## Phase 4: User Story 3 — 查看購物車並結帳 (Priority: P1)

**Goal**: 購物車頁顯示課程清單並可移除；結帳頁收集購買者資料後建立 Order 快照並跳轉 PayUni 付款頁；InitiateCheckout Pixel 觸發。

**Independent Test**: 購物車放入一門 PayUni 課程 → `/cart` 顯示正確 → 點「前往結帳」填完表單 → `POST /api/checkout/initiate` 回傳 PayUni fields → 前端自動 form submit 跳轉至 PayUni sandbox URL；`orders` + `order_items` 表各新增一筆。

- [ ] T024 [US3] Create `resources/js/Pages/Cart/Index.vue` — auth: 渲染 Inertia props `items`; guest: `onMounted` 讀 `localStorage.guest_cart` 顯示; 每項顯示 thumbnail/name/price; 移除按鈕 (auth→DELETE /api/cart/{id}; guest→splice localStorage); 「前往結帳」→ `/checkout`; empty state 含課程列表連結; `onMounted` 觸發 `window.fbq && fbq('track','InitiateCheckout',{num_items, value, currency:'TWD'})`
- [ ] T025 [US3] Create `app/Http/Requests/CheckoutRequest.php` — validate `buyer.name` (required, max:100), `buyer.email` (required, email, max:255), `buyer.phone` (required, max:20), `agree_terms` (required, accepted), `course_ids` (required array min:1, each exists in courses and is purchasable)
- [ ] T026 [US3] Create `app/Services/CheckoutService.php` — `createOrder(?int $userId, array $courseIds, array $buyer): Order`: if any courseId is unpublished/Portaly/already-purchased-by-buyer-email throw `\RuntimeException` (controller catches → HTTP 409 `{message:'已購買的課程無法重複購買', courses:[...]}`); DB::transaction: INSERT order (`user_id=null` for guests) with placeholder `merchant_order_no=''`, UPDATE with `ord_{id}_{YYMMdd}` (note: DB column `merchant_order_no` maps to PayUni field `MerTradeNo` and NewebPay field `MerchantOrderNo` — same value, different gateway names); INSERT order_items (snapshot course_name + unit_price); `routeGateway(Order $order): string`: single item + course.payment_gateway='newebpay' → 'newebpay'; else → 'payuni'
- [ ] T027 [US3] Add `buildOrderPaymentForm(Order $order, array $buyer): array` to `app/Services/PayuniService.php` — use `$order->merchant_order_no` as MerTradeNo, `$order->total_amount` as TradeAmt, `$order->buyer_email` as UsrMail, ProdDesc from first item name (+ " 等 N 門課程" if multi); preserve existing `buildPaymentForm(Course, ...)` signature untouched
- [ ] T028 [US3] Create `app/Http/Controllers/CheckoutController.php` — `show()`: Inertia response with `items`+`total` (auth from CartService; guest `items:[]`) + `prefill` from auth user or nulls; `initiate(CheckoutRequest)`: createOrder → routeGateway → buildOrderPaymentForm or NewebpayService::buildPaymentForm → return JSON `{gateway, endpoint, fields}`
- [ ] T029 [US3] Register routes — `routes/api.php`: `POST /checkout/initiate` (public); `routes/web.php`: `GET /checkout` → CheckoutController@show (public)
- [ ] T030 [US3] Create `resources/js/Pages/Checkout/Index.vue` — show order summary (items + total); buyer form (姓名/Email/電話 + 同意服務條款 checkbox); 「前往付款」disabled until all fields valid + checkbox checked; on submit: `POST /api/checkout/initiate` → on 200 build hidden form with `endpoint`+`fields` and `.submit()`; guest: reads full `{id, name, price, thumbnail}` objects from `localStorage.guest_cart` for display, extracts `.map(i => i.id)` as `course_ids` in request body; logged-in: uses server props `items`; pre-fill from `$props.prefill`; handle 422 inline errors, 409 error banner

**Checkpoint**: `/cart` → `/checkout` → PayUni sandbox 跳轉完整。`orders` + `order_items` 記錄正確建立。

---

## Phase 5: User Story 4 — 購買成功觸發 Purchase 事件 (Priority: P2)

**Goal**: PayUni webhook 驗證後建立 Purchase records（含冪等保護）；ReturnURL 導回 `/payment/success`；頁面顯示訂單摘要並觸發 Meta Pixel Purchase；購物車清空已購課程。

**Independent Test**: 完成 PayUni sandbox 付款 → `orders.status = paid`、`purchases` 建立 N 筆（N = order_items 數）、`/payment/success?order=ord_xx_yymmdd` 顯示正確摘要、`fbq Purchase` 觸發、cart_items 已購課程移除。

- [ ] T031 [US4] Add `fulfillOrder(Order $order, string $gatewayTradeNo, string $gateway): array` to `app/Services/CheckoutService.php` — Layer 1 idempotency: `if ($order->status === 'paid') return []`; DB::transaction: find-or-create User by `$order->buyer_email`, then `$order->update(['user_id' => $user->id])`; update Order status='paid', gateway_trade_no, webhook_received_at; per OrderItem: wrap `Purchase::firstOrCreate(['user_id'=>$user->id,'course_id'=>$item->course_id], [buyer_email, amount=$item->unit_price, currency='TWD', status='paid', type='paid', source=$gateway, webhook_received_at, order_id=$order->id])` in `try { ... } catch (\Illuminate\Database\QueryException $e) { Log::warning('duplicate purchase skipped', ['order'=>$order->id,'course'=>$item->course_id]); continue; }` (Layer 2 idempotency — UNIQUE race guard per Constitution §VII); for each new Purchase: `DripService::checkAndConvert($user, $course)`; return created purchases array
- [ ] T032 [US4] Update `app/Services/PayuniService.php` `processNotify()` — add branch at top: `if (str_starts_with($merTradeNo, 'ord_'))` → look up `Order::where('merchant_order_no', $merTradeNo)->firstOrFail()`, check idempotency, call `CheckoutService::fulfillOrder($order, $tradeNo, 'payuni')`, return `1|OK`; else fall through to existing YC path unchanged
- [ ] T033 [US4] Update `app/Http/Controllers/Payment/PayuniController.php` `return()` — add dual-path: `ord_` prefix + success → `redirect('/payment/success?order='.$merTradeNo)`; `ord_` prefix + failure → `redirect('/cart')->with('payment_failed', '付款未完成，請再試一次；若仍遇到問題請聯絡客服 themustbig+learn@gmail.com')`; YC prefix → preserve existing redirect behavior
- [ ] T034 [US4] Add `clearPurchased(int $userId, array $courseIds): void` to `app/Services/CartService.php` — `CartItem::where('user_id', $userId)->whereIn('course_id', $courseIds)->delete()`
- [ ] T035 [US4] Create `app/Http/Controllers/Payment/SuccessController.php` — `show(Request)`: read `order` query param; `Order::where('merchant_order_no', $orderNo)->with('items')->firstOrFail()`; abort 404 if `status != 'paid'`; if `auth()->check()` call `CartService::clearPurchased()`; Inertia response with `order` summary shape + `isLoggedIn`
- [ ] T036 [US4] Create `resources/js/Pages/Payment/Success.vue` — display 訂單編號/Email/姓名/電話/總金額/課程清單(name+price); `onMounted`: `window.fbq && fbq('track','Purchase',{value:order.total_amount, currency:'TWD', content_ids:[...]})` + `localStorage.removeItem('guest_cart')`; auth → `<Link href="/member/learning">前往我的課程</Link>`; guest → `<Link href="/login?hint=purchase">登入查看課程</Link>`
- [ ] T037 [US4] Register `GET /payment/success` in `routes/web.php` → SuccessController@show (public)

**Checkpoint**: PayUni 完整購買閉環可驗證：付款 → webhook → purchases 建立 → `/payment/success` 正確顯示。

---

## Phase 6: User Story 5 — 購物車狀態持久化（跨 session）(Priority: P2)

**Goal**: 登入時 guest cart 自動合併至 server-side 購物車；已在購物車或已購買者略過；登出後重新登入 server-side cart 持久保留。

**Independent Test**: 未登入加入 2 門課程 → 登入 → `/api/cart/merge` 呼叫 → server cart 有 2 筆 → `localStorage.guest_cart` 清除；重複呼叫 merge 不產生重複 cart_items。

- [ ] T038 [US5] Add `mergeGuestCart(int $userId, array $courseIds): void` to `app/Services/CartService.php` — for each courseId: skip if already in cart (`CartItem::where(user+course)->exists()`) or already purchased (`Purchase::where(user+course)->exists()`); else `CartItem::create()`
- [ ] T039 [US5] Add `merge(Request $request)` to `app/Http/Controllers/CartController.php` — validate `course_ids` array of integers each exists in courses; call `CartService::mergeGuestCart()`; return `{cartCount}`
- [ ] T040 [US5] Register `POST /api/cart/merge` in `routes/api.php` under `auth:web` middleware → CartController@merge
- [ ] T041 [US5] Update `resources/js/composables/useCart.js` — add `mergeGuestCartOnLogin()`: read `localStorage.guest_cart`, if non-empty call `POST /api/cart/merge` with `{course_ids:[...ids]}`, on 200 `localStorage.removeItem('guest_cart')`; export function for Login.vue
- [ ] T041b [US5] Update `resources/js/Pages/Auth/Login.vue` — after successful auth response (before Inertia navigation), call `useCart.mergeGuestCartOnLogin()` to trigger `POST /api/cart/merge` and clear `localStorage.guest_cart`; without this task US5 guest cart merge never fires

**Checkpoint**: 登入合併流程完整。`localStorage.guest_cart` 在登入後清除。server cart 跨登出/登入持久。

---

## Phase 7: User Story 6 — 後台設定課程金流方式 (Priority: P2)

**Goal**: 管理員可設定 PayUni + NewebPay 憑證與 Meta Pixel ID（存入 site_settings）；課程表單可選金流（非 Portaly 課程）；`app.blade.php` 條件輸出 Meta Pixel 腳本。

**Independent Test**: 後台設定 PayUni HashKey → `site_settings` 有對應 key；PayuniService 讀取 DB 值而非 .env；Portaly 課程表單金流選擇器隱藏；非 Portaly 課程選 newebpay 儲存後 `courses.payment_gateway = 'newebpay'`；`meta_pixel_id` 空時 `app.blade.php` 不輸出任何 fbq 代碼。

- [ ] T042 [US6] Create `app/Http/Controllers/Admin/SettingsController.php` — `showPayment()`: load via `SiteSetting::get(key, default)` for all 8 keys (data-model.md SiteSetting Keys table); return hash_key/hash_iv as empty string in Inertia props (FR-022); `updatePayment()`: for each submitted non-empty field call `SiteSetting::set(key, value)`; meta_pixel_id allows explicit empty string to disable Pixel
- [ ] T043 [US6] Register admin routes in `routes/web.php` under admin auth middleware — `GET /admin/settings/payment` → SettingsController@showPayment; `POST /admin/settings/payment` → SettingsController@updatePayment
- [ ] T055 [US6] Add nav link to `/admin/settings/payment` in `resources/js/Layouts/AdminLayout.vue` sidebar — required for FR-021 to be discoverable; moved here from Polish phase so US6 is complete without extra steps
- [ ] T044 [US6] Create `resources/js/Pages/Admin/Settings/Payment.vue` — PayUni section: merchant_id (text), hash_key/hash_iv (`<input type="password">` placeholder "已儲存，輸入新值以更新"); NewebPay section: same + env `<select>` (sandbox/production); Meta Pixel ID section: text input; submit via Inertia `useForm().post('/admin/settings/payment')`
- [ ] T045 [US6] Update `app/Services/PayuniService.php` `__construct()` — replace direct config reads with `SiteSetting::get('payuni_merchant_id', config('services.payuni.merchant_id'))` pattern for all three credentials (merchant_id, hash_key, hash_iv)
- [ ] T046 [US6] Update `resources/js/Components/Admin/CourseForm.vue` — add `payment_gateway` `<select>` with options `payuni`(PayUni) / `newebpay`(藍新金流); `watch(form.portaly_product_id, val => { if (val) { show=false; form.payment_gateway='' } else { show=true; form.payment_gateway ||= 'payuni' } })`
- [ ] T047 [US6] Update `app/Http/Requests/Admin/StoreCourseRequest.php` and `app/Http/Requests/Admin/UpdateCourseRequest.php` — add `payment_gateway`: `Rule::in(['payuni','newebpay'])`, `required_without:portaly_product_id`; update `app/Http/Controllers/Admin/CourseController.php` store/update to include `payment_gateway` in validated fields saved to model
- [ ] T048 [US6] Update `resources/views/app.blade.php` — replace hardcoded `fbq('init','1287511383482442')` block with: `@php $pixelId = \App\Models\SiteSetting::get('meta_pixel_id', env('META_PIXEL_ID', '')) @endphp` → `@if($pixelId)` output full Meta Pixel `<script>` block with `fbq('init','{{ $pixelId }}')` `@endif`; omit entire block (including `<noscript>`) when empty

**Checkpoint**: 後台 `/admin/settings/payment` 可存取。憑證儲存至 site_settings。PayuniService 優先讀 DB 值。Portaly 課程隱藏金流選擇器。`meta_pixel_id` 空時頁面原始碼無任何 fbq 代碼。

---

## Phase 8: User Story 7 — 藍新金流結帳流程 (Priority: P2)

**Goal**: 藍新金流課程可完成完整購買流程：購物車 → 跳轉藍新 MPG → 付款成功 → NotifyURL 建立 Purchase → ReturnURL 導回 `/payment/success`；失敗導回購物車。

**Independent Test**: 後台將一門課程設為 newebpay → 加入購物車 → 結帳 → 跳轉 `ccore.newebpay.com` sandbox MPG → 付款成功 → `orders.status = paid`、`purchases` 建立、`/payment/success` 顯示正確。

- [ ] T049 [US7] Create `app/Services/NewebpayService.php` — `__construct()`: read credentials via `SiteSetting::get('newebpay_hash_key', config('services.newebpay.hash_key'))` pattern; read `newebpay_env` to set endpoint (sandbox: `ccore.newebpay.com`, production: `core.newebpay.com`); `buildPaymentForm(Order, array $buyer): array`: build $tradeParams (MerchantID, RespondType=JSON, TimeStamp, Version=2.3, MerchantOrderNo, Amt, ItemDesc, Email, LoginType=0); `TradeInfo = bin2hex(openssl_encrypt(http_build_query($tradeParams), 'AES-256-CBC', $hashKey, OPENSSL_RAW_DATA, $hashIV))`; `TradeSha = strtoupper(hash('sha256', "HashKey={$hashKey}&{$tradeInfo}&HashIV={$hashIV}"))` return `{endpoint, fields:{MerchantID, TradeInfo, TradeSha, Version:'2.3'}}`; `verifyTradeSha(string, string): bool`; `decryptTradeInfo(string): array`: `openssl_decrypt(hex2bin($tradeInfo), 'AES-256-CBC', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv)` + manual PKCS7 strip + `parse_str()`
- [ ] T050 [US7] Create `app/Http/Controllers/Payment/NewebpayController.php` — `notify(Request)`: verify TradeSha (fail → log + `return response('SUCCESS')`); decrypt TradeInfo; check `Status='SUCCESS'`; look up Order by MerchantOrderNo; idempotency Layer 1 check; `CheckoutService::fulfillOrder($order, $tradeNo, 'newebpay')`; `return response('SUCCESS')`; `return(Request)`: decrypt TradeInfo; success → `redirect('/payment/success?order='.$merchantOrderNo)`; failure → `redirect('/cart')->with('payment_failed', '付款未完成...')`
- [ ] T051 [US7] Register NewebPay routes in `routes/web.php` — `Route::post('/api/webhooks/newebpay', ...)->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])`; `Route::post('/payment/newebpay/return', ...)->withoutMiddleware([VerifyCsrfToken::class])`
- [ ] T052 [US7] Update `app/Http/Controllers/CheckoutController.php` `initiate()` — wire NewebPay branch: when `CheckoutService::routeGateway()` returns `'newebpay'`, call `app(NewebpayService::class)->buildPaymentForm($order, $buyer)` and return `{gateway:'newebpay', endpoint, fields}`

**Checkpoint**: 藍新完整購買閉環可驗證。PayUni + NewebPay 兩條路徑均正常。E2E 驗證步驟見 `specs/009-cart-checkout/quickstart.md` Phase I。

---

## Phase 9: Polish & Cross-Cutting Concerns

- [ ] T056 Verify `resources/js/Pages/Cart/Index.vue` renders `$page.props.flash.payment_failed` error banner when present (失敗訊息含客服 email `themustbig+learn@gmail.com`)
- [ ] T057 Run full verification checklist per `specs/009-cart-checkout/quickstart.md` — confirm all 14 checklist items pass
- [ ] T058 Run `php artisan test` — confirm no regressions across all existing features
- [ ] T059 Add `NEWEBPAY_MERCHANT_ID=`, `NEWEBPAY_HASH_KEY=`, `NEWEBPAY_HASH_IV=`, `NEWEBPAY_ENV=sandbox` to `.env.example` with placeholder values alongside existing `PAYUNI_*` entries

---

## Dependencies & Execution Order

### Phase Dependencies

```
Phase 1 (Setup)
  └── Phase 2 (Foundational) ← BLOCKS all phases below
        ├── Phase 3 (US1+US2) ← requires CartItem schema + Course model update
        │     └── Phase 4 (US3) ← requires CartController@index + useCart
        │           └── Phase 5 (US4) ← requires CheckoutService::createOrder
        │                 └── Phase 6 (US5) ← requires CartService (can also parallel with US4)
        ├── Phase 7 (US6) ← requires only SiteSetting pattern (can start after Phase 2)
        └── Phase 8 (US7) ← requires Phase 4 (CheckoutService::routeGateway) + Phase 7 (SiteSetting credentials)
Phase 9 (Polish) ← depends on all phases complete
```

### User Story Dependencies

| Story | Depends On | Can Parallel With |
|-------|-----------|-------------------|
| US1+US2 (Phase 3) | Phase 2 complete | US6 Phase 7 |
| US3 (Phase 4) | Phase 3 complete | US5 Phase 6 (partially) |
| US4 (Phase 5) | Phase 4 complete | — |
| US5 (Phase 6) | Phase 3 complete | US4 Phase 5 |
| US6 (Phase 7) | Phase 2 complete | US1+US2, US3, US4, US5 |
| US7 (Phase 8) | Phase 4 + Phase 7 complete | — |

### Within Each User Story

- Models before services → services before controllers → controllers before frontend
- New files ([P] marked) within a story can be created in parallel
- Existing file updates within a story must be sequential (same file)

---

## Parallel Opportunities

### Phase 2 (Foundational) — all [P] tasks can run simultaneously
```
T002 create_cart_items migration
T003 create_orders migration              ← all 5 in parallel
T004 create_order_items migration
T005 add_payment_gateway migration
T006 add_order_id migration
```
Then T007 (migrate), then:
```
T008 CartItem model
T009 Order model                          ← all 3 models in parallel
T010 OrderItem model
```

### Phase 3 (US1+US2) — service/request files in parallel
```
T014 AddToCartRequest
T015 CartService                          ← T014+T015 in parallel
```
Then T016 CartController (depends on both), then T017/T018/T019/T020 in parallel:
```
T017 API routes
T018 Web route                            ← all 4 in parallel
T019 HandleInertiaRequests
T020 useCart.js
```
Then T021/T022/T023 (all touch different files — can parallel):
```
T021 Course/Show.vue
T022 CourseController                     ← T021+T022+T023 in parallel
T023 Navigation.vue
```

### Phase 7 (US6) — mostly independent files
```
T042 SettingsController
T043 Routes                               ← T042+T043 parallel
T044 Payment.vue                          ← T042+T044 parallel
T045 PayuniService credentials
T046 CourseForm.vue                       ← T045+T046+T047 parallel (different files)
T047 StoreCourseRequest + UpdateCourseRequest
```

---

## Implementation Strategy

### MVP (US1+US2+US3+US4 — PayUni 完整閉環)

1. Complete Phase 2 (Foundational)
2. Complete Phase 3 (US1+US2 — Sales page + cart add)
3. Complete Phase 4 (US3 — Cart page + checkout)
4. Complete Phase 5 (US4 — PayUni webhook + success page)
5. **STOP and VALIDATE**: Full PayUni sandbox purchase cycle working
6. Deploy/demo

### Incremental Delivery After MVP

- Add Phase 6 (US5 — Cart persistence/merge) → Test guest cart merge
- Add Phase 7 (US6 — Admin credentials + Meta Pixel) → Test admin UI
- Add Phase 8 (US7 — NewebPay flow) → Test NewebPay sandbox
- Phase 9 (Polish) → Final verification + regression test

### Single-Developer Execution Order (Recommended)

```
T001 → T002-T006 (parallel) → T007 → T008-T010 (parallel) → T011-T013
→ T014-T015 (parallel) → T016 → T017-T020 (parallel) → T021-T023 (parallel)
→ T024 → T025-T026 (parallel) → T027-T030
→ T031 → T032-T033 (parallel) → T034 → T035-T037 (parallel)
→ T038 → T039-T040 (parallel) → T041 → T041b
→ T042-T048 + T055 (mostly parallel per file, T055 after T043)
→ T049 → T050-T051 (parallel) → T052
→ T056-T059
```
