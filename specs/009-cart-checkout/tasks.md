# Tasks: 購物車結帳系統

**Branch**: `009-cart-checkout` | **Date**: 2026-05-05
**Input**: `specs/009-cart-checkout/` (plan.md · spec.md · data-model.md · contracts/api.md · research.md)
**Tests**: 不包含（spec 未要求 TDD）；驗收方式為手動 sandbox 測試（見 quickstart.md）

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 可與同階段其他 [P] 任務並行（不同檔案，無依賴）
- **[US#]**: 對應 spec.md 中的 User Story 編號
- 每個任務說明含完整檔案路徑

---

## Phase 1: Setup

**Purpose**: 環境變數、config、路由骨架——任何功能開始前必須完成

- [ ] T001 新增 NEWEBPAY 環境變數至 `.env.example`（`NEWEBPAY_MERCHANT_ID`, `NEWEBPAY_HASH_KEY`, `NEWEBPAY_HASH_IV`, `NEWEBPAY_ENV=sandbox`）
- [ ] T002 `config/services.php` 新增 `newebpay` 區段（merchant_id / hash_key / hash_iv / sandbox bool，對應環境變數）
- [ ] T003 `routes/api.php` 與 `routes/web.php` 新增所有新路由（cart CRUD、checkout/initiate、webhooks/newebpay、/cart、/checkout、/payment/newebpay/return — 先指向空 Controller stub，確保路由解析正確）
- [ ] T003a `app/Http/Middleware/VerifyCsrfToken.php`：`$except` 陣列加入 `'payment/newebpay/return'`（與既有 `'api/webhooks/*'` 同處理方式；ReturnURL 為 POST 但由藍新外部跳轉，無 CSRF token，不排除必定 419 失敗）

**Checkpoint**: `php artisan route:list` 可看到所有新路由，無報錯；`POST /payment/newebpay/return` 不回 419

---

## Phase 2: Foundational（阻塞所有 User Story）

**Purpose**: 資料庫 schema + 核心 Model 變更。所有 User Story 均依賴此階段完成。

**⚠️ CRITICAL**: 此階段必須在任何 User Story 實作前全部完成

### Migrations（互相獨立，可並行）

- [ ] T004 [P] `database/migrations/XXXX_add_payment_gateway_to_courses_table.php`：新增 `payment_gateway` string(20) nullable default 'payuni'，after `portaly_product_id`
- [ ] T005 [P] `database/migrations/XXXX_create_cart_items_table.php`：`id, user_id, course_id, created_at(timestamp useCurrent)`；unique `(user_id, course_id)`；FK cascadeOnDelete；index `user_id`
- [ ] T006 [P] `database/migrations/XXXX_create_orders_table.php`：`id, user_id, total_amount(unsignedInt), payment_gateway(string 20), gateway_trade_no(string 100 nullable unique), status(enum pending/paid/failed default pending), timestamps`；FK `user_id cascadeOnDelete`；index `status`
- [ ] T007 [P] `database/migrations/XXXX_create_order_items_table.php`：`id, order_id, course_id, price(unsignedInt), created_at(timestamp useCurrent)`；FK `order_id cascadeOnDelete`；FK `course_id restrictOnDelete`；index `order_id`
- [ ] T008 [P] `database/migrations/XXXX_add_order_id_to_purchases_table.php`：新增 `order_id` unsignedBigInteger nullable after `course_id`；FK references `orders(id) nullOnDelete`；index `order_id`

### Model 變更（互相獨立，可並行）

- [ ] T009 [P] `app/Models/Course.php`：`$fillable` 加入 `'payment_gateway'`；新增 `effectiveGateway()` Attribute accessor（portaly → null，否則 payment_gateway ?? 'payuni'）；新增 `isCartEligible(): bool`（portaly_product_id IS NULL && price > 0 && type != 'high_ticket' && status == 'published'）；新增 `scopeCartEligible($query)`（對應 4 個條件的 where 鏈）
- [ ] T010 [P] `app/Models/CartItem.php`（新建）：`$fillable = ['user_id', 'course_id']`；`$timestamps = false`；`boot()` 設 `created_at = now()`；`user(): BelongsTo`；`course(): BelongsTo`
- [ ] T011 [P] `app/Models/Order.php`（新建）：`$fillable = ['user_id', 'total_amount', 'payment_gateway', 'gateway_trade_no', 'status']`；`casts(): array`（status string）；`user(): BelongsTo`；`items(): HasMany → OrderItem`；`scopePending()` / `scopePaid()`
- [ ] T012 [P] `app/Models/OrderItem.php`（新建）：`$fillable = ['order_id', 'course_id', 'price']`；`$timestamps = false`；`boot()` 設 `created_at = now()`；`order(): BelongsTo`；`course(): BelongsTo`
- [ ] T013 [P] `app/Models/Purchase.php`：`$fillable` 加入 `'order_id'`；新增 `order(): BelongsTo`

**Checkpoint**: `php artisan migrate` 成功，`php artisan tinker` 可 `Course::first()->isCartEligible()`

---

## Phase 3: US1 — 加入購物車 / 直接購買（Priority: P1）🎯 MVP 入口

**Goal**: 在 PayUni 課程頁顯示「加入購物車」與「直接購買」按鈕；guest cart 暫存 localStorage；已登入者寫 server-side CartItem；Navigation 顯示 badge 數量

**Independent Test**: 未登入點「加入購物車」→ badge +1（localStorage）；登入後再看同課程頁 → badge 仍顯示；Portaly 課程只顯示「立即購買」外部連結（不顯示購物車按鈕）

### Implementation

- [ ] T014 [P] [US1] `resources/js/composables/useCart.js`（新建）：module-level `ref(items)`；`useCart()` export `{items, count, total, add, remove, init, mergeOnLogin}`；`add()` 已登入 → `router.post('/api/cart', {course_id})` + 更新本地 ref；未登入 → push to localStorage；兩者均呼 `window.fbq?.('track', 'AddToCart', {content_ids:[id], value, currency:'TWD'})`；`remove()` 已登入 → `router.delete('/api/cart/{id}')`；未登入 → splice localStorage；`init()` 已登入 → 從 Inertia props 或 /api/cart 載入；未登入 → 從 localStorage 載入
- [ ] T015 [US1] `app/Http/Controllers/CartController.php`（新建）— `store(Request $request)` POST /api/cart：inline validate `course_id`；guard `Course::isCartEligible()` + 未已購買；`CartItem::firstOrCreate(['user_id'=>auth()->id(), 'course_id'=>$id])`；回傳 `{status: 'added'|'already_in_cart', count}`
- [ ] T016 [US1] `app/Http/Controllers/CartController.php` — `destroy(CartItem $cartItem)` DELETE /api/cart/{cartItem}：確認 `$cartItem->user_id === auth()->id()` 否則 abort 403；delete；回傳 `{count}`
- [ ] T017 [US1] `app/Http/Controllers/CartController.php` — `index()` GET /api/cart：`CartItem::with('course')->where('user_id', auth()->id())->get()`；計算 total；回傳 items + total + count JSON
- [ ] T018 [US1] `resources/js/Pages/Course/Show.vue`：引入 `useCart()`；依 `course.portaly_product_id` / `course.price` / `course.type` / 已購買狀態決定顯示哪個按鈕（Portaly→立即購買外部連結；免費→免費領取；high_ticket→預約諮詢；已購買→進入課程；其餘→加入購物車 + 直接購買）；「加入購物車」呼 `useCart().add(course)`；「直接購買」呼 add 後 `router.visit('/checkout')`
- [ ] T019 [US1] `resources/js/Components/Layout/Navigation.vue`：引入 `useCart()`；購物車 icon 連結 `/cart`；badge 顯示 `useCart().count`（count > 0 才顯示）；`onMounted(() => useCart().init())`
- [ ] T020 [US1] `app/Http/Controllers/CartController.php` — `show()` GET /cart：`Inertia::render('Cart/Index', ['items' => ..., 'total' => ...])`；已登入回傳 server cart with eager-loaded courses；未登入回傳空陣列（前端讀 localStorage）。勿手動傳 flash — flash 由 `HandleInertiaRequests` 自動共享

**Checkpoint**: 已登入點「加入購物車」→ badge +1，重整頁面 badge 仍在；未登入點「加入購物車」→ badge +1（純 client）；Portaly 課程頁無購物車按鈕

---

## Phase 4: US2 — Portaly 課程保留直購流程（Priority: P1）

**Goal**: 明確確認 Portaly 課程的直購行為完全不受影響

**Independent Test**: 有 portaly_product_id 的課程頁只顯示「立即購買」外部連結；POST /api/cart 拒絕此課程（422）

- [ ] T021 [US2] `app/Http/Controllers/CartController.php` store() — 驗收 guard：確認 `!$course->isCartEligible()` 時回傳 `{message: '此課程不支援加入購物車', status: 422}`；並手動驗證 Portaly 課程頁面無購物車按鈕（T018 已實作，此處為 acceptance checkpoint）

**Checkpoint**: Portaly 課程購買流程行為 100% 與重構前相同

---

## Phase 5: US3 — 購物車頁結帳（PayUni）（Priority: P1）

**Goal**: 購物車頁完整功能（顯示、移除、前往結帳）；PayUni 一鍵結帳建立 Order + 跳轉付款頁；NotifyURL 處理 YO prefix 並建立 Purchase records

**Independent Test**: PayUni sandbox 完整流程：加入 2 門課 → 購物車頁顯示總金額（**購物車頁 onMounted 觸發 InitiateCheckout**）→ 前往結帳頁 → 跳轉 PayUni sandbox → 付款成功 → 重導 /member/learning → 兩筆 Purchase 建立、CartItems 清空

### Implementation

- [ ] T022 [P] [US3] `app/Services/CheckoutService.php`（新建）：`initiate(User $user): array`（load CartItems → validate eligible + sameGateway → DB::transaction { create Order + OrderItems + generateTradeNo → route to PayuniService or NewebPayService }）；`generateTradeNo(int $orderId): string`（format `YO{orderId:06d}{YmdHis}{rand4}`）
- [ ] T023 [P] [US3] `app/Services/OrderFulfillmentService.php`（新建）：`fulfill(Order $order): array`（冪等1: order->status==='paid' early return；DB::transaction { loadMissing items.course user → foreach item: 冪等2 Purchase exists → create Purchase(含order_id, source, payuni_trade_no) → order->update(paid) → CartItem delete → DripService subscribe+checkAndConvert → Log::info } → return ['success'=>true]）
- [ ] T024 [US3] `app/Services/PayuniService.php`：新增 `buildCheckoutForm(Order $order): array` method（從 `$order->items` 取課程名稱；`$order->total_amount` 作為 TradeAmt；`$order->user->email` 作為 UsrMail；`$order->gateway_trade_no` 作為 MerTradeNo）；回傳鍵名用 `action_url` + `form_fields`（而非既有 `buildPaymentForm` 的 `endpoint`+`fields`），與 contracts/api.md 一致：`['action_url' => $this->apiUrl, 'form_fields' => ['MerID'=>..., 'Version'=>'1.0', 'EncryptInfo'=>..., 'HashInfo'=>...]]`
- [ ] T025 [US3] `app/Services/PayuniService.php`：重構 `processNotify()`（加入 `str_starts_with($merTradeNo, 'YO')` 分支 → `OrderFulfillmentService::fulfill(Order::firstWhere('gateway_trade_no', $merTradeNo))`；原有 YC 邏輯移入私有 `processLegacySingleCourseNotify(array $data, string $merTradeNo): array`；**確保 YC 邏輯一字不差**）
- [ ] T026 [US3] `app/Http/Controllers/CheckoutController.php`（新建）：`show()` GET /checkout → `Inertia::render('Checkout/Index', ['items'=>..., 'total'=>...])`（auth middleware）；`initiate(Request $request)` POST /api/checkout/initiate → `CheckoutService::initiate(auth()->user())` → return JSON `{gateway, action_url, form_fields}`
- [ ] T027 [US3] `resources/js/Pages/Cart/Index.vue`（新建）：顯示 server-side items（已登入）或 localStorage items（guest）；每項顯示課程封面圖、名稱、定價；移除按鈕 → `useCart().remove()`；計算總額；「前往結帳」按鈕（已登入 → `/checkout`；未登入 → `/login`）；混合金流警告 banner（PayUni + 藍新同在購物車時顯示）；空購物車 empty state；`onMounted` 觸發 `fbq('track', 'InitiateCheckout', {num_items: items.length, value: total, currency:'TWD'})`（spec US3-AS1 確認在購物車頁觸發）
- [ ] T028 [US3] `resources/js/Pages/Checkout/Index.vue`（新建）：顯示 order summary（items list + 總金額）；「前往付款」按鈕 → `router.post('/api/checkout/initiate')` → 成功時解構 response `{action_url, form_fields}` → 動態建立隱藏 form auto-submit 至 `action_url`（`form_fields` 每個 key 為一個 `<input type="hidden">`）；處理 422 錯誤（購物車空或混合金流顯示 error 訊息）；**不**重複觸發 InitiateCheckout（已在 Cart/Index.vue 觸發）
- [ ] T029 [US3] `app/Http/Controllers/Payment/PayuniController.php` `return()` method 更新：解密取得 `$merTradeNo = $data['MerTradeNo']`；呼叫 `processNotify`（idempotent）；success 時：若 `str_starts_with($merTradeNo, 'YO')` 則 `$amount = Order::where('gateway_trade_no', $merTradeNo)->value('total_amount') ?? 0`，否則（YC）`$amount = $data['TradeAmt'] ?? 0`；`redirect('/member/learning')->with('success', '付款成功！您的課程已開通。')->with('purchase_amount', $amount)`；failure → `redirect('/cart')->with('error', '付款未完成，請再試一次；若仍遇到問題請聯絡客服 themustbig+learn@gmail.com')`

**Checkpoint**: PayUni sandbox 完整購物車付款流程成功；Order status = paid；Purchase records 建立；CartItems 清空

---

## Phase 6: US4 — 付款成功觸發 Purchase Pixel（Priority: P2）

**Goal**: 任一金流付款成功返回後，Meta Pixel 觸發 `Purchase`（含金額）

**Independent Test**: PayUni sandbox 付款成功後，Facebook Events Manager 可見 Purchase 事件含 value 與 currency: TWD

- [ ] T030 [US4] `resources/js/Pages/Member/Learning.vue`（或 AppLayout.vue）：偵測 Inertia flash 中的 `purchase_amount`；若存在則執行 `window.fbq?.('track', 'Purchase', {value: flash.purchase_amount, currency: 'TWD'})`；flash 只觸發一次（用 `onMounted`，且 flash 由 Laravel session 自動消費）

**Checkpoint**: PayUni + 藍新付款成功後 Events Manager 均可見 Purchase 事件

---

## Phase 7: US5 — 購物車狀態持久化（Priority: P2）

**Goal**: 登出後重新登入，購物車內容保留（server-side）；guest cart 在登入後自動合併

**Independent Test**: 加入課程至 server cart → 登出 → 重新登入 → 購物車仍有原課程；登入前 localStorage 中的 guest 課程合併至 server cart

- [ ] T031 [US5] `app/Http/Controllers/CartController.php` — `merge(Request $request)` POST /api/cart/merge：inline validate `course_ids array of int`；foreach course_id: skip if already in cart or already purchased or not isCartEligible；batch insert CartItems；回傳 `{merged, skipped, count}`
- [ ] T032 [US5] `resources/js/composables/useCart.js` + `resources/js/app.js`：`useCart.js` 中的 `mergeOnLogin()` 讀取 localStorage guest cart course_ids，若有則 `router.post('/api/cart/merge', {course_ids})` → 成功後清空 localStorage 並更新 ref items；觸發點在 `app.js` 的 `router.on('navigate', ...)` 回呼中偵測 `page.props.auth?.user`（已登入）且 localStorage 有 guest 項目則呼叫 `useCart().mergeOnLogin()`（與現有 `router.on('navigate', () => fbq PageView)` 同一位置追加即可）

**Checkpoint**: Guest 加入 2 門課 → 登入 → /api/cart 回傳合併後的課程清單，LocalStorage 清空

---

## Phase 8: US6 — 後台設定課程金流方式（Priority: P2）

**Goal**: 管理員可在課程表單選擇金流；Portaly 課程時選擇器自動隱藏

**Independent Test**: 後台編輯非 Portaly 課程 → 儲存「藍新金流」→ 前台購物車結帳跳轉藍新 MPG；填入 portaly_product_id → 選擇器消失

- [ ] T033 [US6] `resources/js/Components/Admin/CourseForm.vue`：在「價格」欄位附近新增金流選擇器（select）；選項：PayUni / 藍新金流（values: `payuni` / `newebpay`）；v-model 綁定 `form.payment_gateway`（預設 `'payuni'`）；computed 或 watch：`portaly_product_id` 有值時 `v-show="false"` 且 `form.payment_gateway = null`（null 符合後端 `nullable` 驗證；空字串會被 `in:payuni,newebpay` 拒絕）；清空時恢復顯示並 reset 為 `'payuni'`
- [ ] T034 [US6] `app/Http/Controllers/Admin/CourseController.php` `store()` / `update()`：在 Form Request 或 inline validate 中加入 `payment_gateway` 規則（`nullable|in:payuni,newebpay`）；確認已存入 DB（`Course::$fillable` 在 T009 已更新）

**Checkpoint**: 後台課程金流選擇可儲存；藍新課程前台結帳路由正確

---

## Phase 9: US7 — 藍新金流結帳流程（Priority: P2）

**Goal**: 藍新金流課程完整走購物車 → MPG 付款頁 → NotifyURL webhook → Purchase 建立 → ReturnURL 跳轉

**Independent Test**: 後台將一門課程設為藍新金流 → 購物車結帳 → 跳轉 `ccore.newebpay.com` sandbox → 測試卡付款成功 → Purchase 建立、Order paid、CartItem 清空、導回 /member/learning

### Implementation

- [ ] T035 [P] [US7] `app/Services/NewebPayService.php`（新建）：constructor 讀 `config('services.newebpay.*')`；`buildCheckoutForm(Order $order): array`（buildTradeInfo array → encrypt to TradeInfo → TradeSha → return {action_url, form_fields{MerchantID, TradeInfo, TradeSha, Version:'2.3'}}）；`processNotify(string $tradeInfo, string $tradeSha): array`（verifyTradeSha → decrypt → Status check → Order lookup → OrderFulfillmentService::fulfill）；`private encrypt(string): string`（AES-256-CBC OPENSSL_ZERO_PADDING → bin2hex）；`private decrypt(string): string`（hex2bin → AES decrypt → json_decode）；`private verifyTradeSha(string $tradeInfo, string $sha): bool`（`strtoupper(hash('sha256', "HashKey={key}&{tradeInfo}&HashIV={iv}"))` — 注意 `$tradeInfo` 為 hex 字串）。**勿新增 `generateOrderNo`**：trade number 由 `CheckoutService::generateTradeNo()` 統一生成並存入 `orders.gateway_trade_no`，NewebPayService 從 Order 物件讀取即可
- [ ] T036 [P] [US7] `app/Http/Controllers/Payment/NewebPayController.php`（新建）：`notify(Request $request)`：擷取 `TradeInfo`, `TradeSha`；呼叫 `NewebPayService::processNotify($tradeInfo, $tradeSha)`（內部執行 verifyTradeSha + decrypt，PayuniService 的 `verifyAndDecrypt` 是不同方法，勿混用）；回傳 `response('SUCCESS', 200)->header('Content-Type', 'text/plain')`；try/catch 吞例外（catch 也回 SUCCESS 防重試）。`return(Request $request)`：`NewebPayService::verifyTradeSha()` + `decrypt()` 取得 `$decoded`；若 `$decoded['Status'] === 'SUCCESS'` 則從 `$decoded['MerchantOrderNo']` 查 `$amount = Order::where('gateway_trade_no', $merOrderNo)->value('total_amount') ?? 0`；`redirect('/member/learning')->with('success', '付款成功！您的課程已開通。')->with('purchase_amount', $amount)`；否則 `redirect('/cart')->with('error', '付款未完成，請再試一次；若仍遇到問題請聯絡客服 themustbig+learn@gmail.com')`
- [ ] T037 [US7] 確認 `routes/api.php` (`POST /api/webhooks/newebpay`) 與 `routes/web.php` (`POST /payment/newebpay/return`，CSRF 排除) 已正確指向 `NewebPayController@notify` 與 `@return`（T003 已建立骨架，此處為 controller binding 確認）
- [ ] T038 [US7] 確認 `CheckoutService::initiate()` 的 match 表已含 `'newebpay' => app(NewebPayService::class)->buildCheckoutForm(...)` case（T022 已預留，此為 wiring 驗收）

**Checkpoint**: 藍新 sandbox 完整付款流程成功；`php artisan log:watch`（或 storage/logs/laravel.log）可見 `OrderFulfillment: completed` log

---

## Phase 10: Polish & 驗收

**Purpose**: 端對端驗收、回歸測試、Pixel 驗收

- [ ] T039 `php artisan migrate`：執行全部 5 個新 migration，確認 `php artisan migrate:status` 全部 Ran
- [ ] T040 手動驗收 PayUni sandbox：加入 2 門課 → 購物車頁 → 結帳頁 → PayUni sandbox 付款 → `/member/learning` 顯示成功；確認 2 筆 Purchase 建立（`source='payuni'`）、Order status = paid、CartItems 清空
- [ ] T041 手動驗收 NewebPay sandbox：後台設 1 門課為藍新 → 加入購物車 → 結帳 → ccore.newebpay.com → 測試卡（`4000-2211-1111-1111`）付款 → `/member/learning`；確認 Purchase 建立（`source='newebpay'`）
- [ ] T042 回歸測試：既有 YC 前綴單一課程 PayUni 流程（`/api/payment/payuni/initiate` → sandbox 付款）仍正常建立 Purchase；`parseCourseId()` 與 `processLegacySingleCourseNotify()` 行為不變
- [ ] T043 回歸測試：Portaly webhook 課程購買流程（POST `/api/webhooks/portaly`）仍正常；購物車 API 拒絕加入 Portaly 課程（422）
- [ ] T044 Meta Pixel 驗收：Facebook Events Manager「測試事件」工具確認 AddToCart / InitiateCheckout / Purchase 三事件均可觸發；`grep -r "AddToCart"` 確認只出現在 `useCart.js`（無其他元件散落觸發）

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: 無依賴，立即開始
- **Phase 2 (Foundational)**: 依賴 Phase 1 完成 → **阻塞所有 User Story**
- **Phase 3 (US1)**: 依賴 Phase 2
- **Phase 4 (US2)**: 依賴 Phase 3（US2 是 US1 的 acceptance checkpoint）
- **Phase 5 (US3)**: 依賴 Phase 3（需要 CartItem / CartController 存在）
- **Phase 6 (US4)**: 依賴 Phase 5（Purchase pixel 觸發在 return URL 之後）
- **Phase 7 (US5)**: 依賴 Phase 3（merge 需要 CartController）
- **Phase 8 (US6)**: 依賴 Phase 2（需要 payment_gateway 欄位）
- **Phase 9 (US7)**: 依賴 Phase 5（OrderFulfillmentService T023 必須先完成）
- **Phase 10 (Polish)**: 依賴所有 User Story 完成

### User Story Dependencies

| Story | 依賴 | 可否在 US1 後並行 |
|-------|------|-------------|
| US1 (P1) | Phase 2 | 開始點 |
| US2 (P1) | US1 (T018) | 接 US1 後立即驗收 |
| US3 (P1) | US1 (CartController/useCart) | 接 US1 checkpoint |
| US4 (P2) | US3 (return URL) | 接 US3 checkpoint |
| US5 (P2) | US1 (CartController) | 可與 US3 並行 |
| US6 (P2) | Phase 2 (payment_gateway 欄位) | 可與 US1/US3 並行 |
| US7 (P2) | US3 (OrderFulfillmentService T023) | 接 US3 checkpoint |

### Parallel Opportunities

**Phase 2 並行**（所有 [P] 任務同時開始）:
```
T004 migration courses | T005 migration cart_items | T006 migration orders |
T007 migration order_items | T008 migration purchases
T009 Course model | T010 CartItem model | T011 Order model |
T012 OrderItem model | T013 Purchase model
```

**Phase 3 並行**（T014 獨立並行；T015–T017 同一檔案，依序實作）:
```
T014 useCart.js（獨立，可並行）
→ T015 CartController@store → T016 CartController@destroy → T017 CartController@index（同一檔案，循序）
```
接著 T018 Course/Show.vue、T019 Navigation.vue（可再並行）

**Phase 5 並行**（T022 + T023 同時進行）:
```
T022 CheckoutService | T023 OrderFulfillmentService
```

**Phase 9 並行**（T035 + T036 同時進行）:
```
T035 NewebPayService | T036 NewebPayController
```

**US5 + US6 可與 US3 並行**（不同檔案，不同資料表）:
```
Dev A: US3 (CheckoutService, PayuniService update, Checkout/Index.vue)
Dev B: US5 (CartController@merge) + US6 (CourseForm.vue, CourseController)
```

---

## Implementation Strategy

### MVP First（US1 + US2 + US3 = PayUni 完整購物車）

1. Phase 1: Setup（T001–T003）
2. Phase 2: Foundational（T004–T013）— **run `php artisan migrate`**
3. Phase 3: US1（T014–T020）— 購物車 CRUD 完成
4. Phase 4: US2（T021）— Portaly 確認
5. Phase 5: US3（T022–T029）— PayUni 結帳完整流程
6. **STOP & VALIDATE**: PayUni sandbox 端對端驗收（T040 + T042）
7. 若 PayUni 驗收通過：部署至 staging

### Incremental Delivery

1. Foundation → US1 → 🎯 **Cart CRUD + badge 可用**
2. US1 + US3 → 🎯 **PayUni 購物車結帳可用**（對外 MVP）
3. US4 + US5 → 🎯 **Pixel Purchase + 跨 session 持久**
4. US6 + US7 → 🎯 **藍新金流完整支援**
5. Polish → 🎯 **全功能上線**

---

## Notes

- 每個任務完成後執行 `php artisan serve` + `npm run dev` 確認無報錯
- T025（PayuniService processNotify 重構）是最高風險任務：完成後立即執行 T042 回歸測試
- `useCart.js` 的 module-level `ref(items)` 是全頁共享狀態的關鍵（非 Vuex/Pinia，符合 Constitution III）
- NewebPay TradeInfo 的 `TimeStamp` 需用 `time()`（Unix timestamp），允許 ±120 秒誤差
- CSRF 排除：已在 T003a 作為明確 task 處理（`VerifyCsrfToken::$except`）
