---
description: "Task list for 折扣碼管理系統 implementation"
---

# Tasks: 折扣碼管理系統

**Input**: Design documents from `/specs/011-discount-coupon/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/api.md

**Tests**: 本功能未要求 TDD/自動化測試任務；驗收以 `quickstart.md` 手動路徑為準（Polish 階段執行）。

**Organization**: 依 5 個 user story 分階段，每階段可獨立實作與驗收。

**Updated**: 2026-06-26 - CouponChain 子功能實作完成（Phase 10）：migrations × 2、CouponChain model、CouponChainService、CouponService::redeem() 補碼邏輯、CouponChainController + Form Requests、Vue Pages（Index/Create/Edit/Show）、AdminLayout tab 整合、ClassroomController placeholder 展開、LessonForm 插入 UI。
**Updated**: 2026-06-10 - 全 39 任務完成；另補實作後修正（Phase 9）：結帳頁掛載 `CouponInput`（FR-021 buy-now）、計數完整性修正（FR-022，結帳僅送實際套用碼）、後台重複 toast 修正（`defineOptions({ layout: AdminLayout })`）。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 可平行（不同檔案、無未完成相依）
- **[Story]**: US1–US5，對應 spec.md
- 所有路徑為 repo 根目錄相對路徑

## Path Conventions

Laravel monolith + Inertia/Vue：後端 `app/`、`routes/`、`database/migrations/`；前端 `resources/js/`。

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: 確認環境，無新增套件

- [x] T001 確認分支為 `011-discount-coupon`、無需新增 composer/npm 依賴（皆沿用既有：Inertia v2、Vue 3、Tailwind v4）；建立前端目錄 `resources/js/Pages/Admin/Coupons/` 與 `resources/js/Components/Cart/`（若不存在）

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: 所有 user story 共用的資料層與 Service 骨架

**⚠️ CRITICAL**: 本階段未完成前，任何 user story 不可開始

- [x] T002 建立 migration `database/migrations/2026_06_09_000001_create_coupon_codes_table.php`：依 data-model.md，含 `code`(UNIQUE)、`type` enum、`value`、`course_id` FK(CASCADE)、`expires_at`、`max_uses`、`used_count`、`is_active`、`note`、timestamps、`deleted_at`(SoftDeletes)
- [x] T003 建立 migration `database/migrations/2026_06_09_000002_add_discount_columns_to_orders_table.php`：`orders` 新增 `coupon_code` varchar(6) null、`original_amount` decimal(10,2) null、`discount_amount` decimal(10,2) default 0
- [x] T004 [P] 建立 `app/Models/CouponCode.php`：SoftDeletes、`$fillable`、`casts()`、`booted()` saving 轉大寫、`course()` 關聯、`scopeActive`、`isExpired()`/`isExhausted()`/`isSiteWide()`（見 data-model.md）
- [x] T005 [P] 修改 `app/Models/Order.php`：`$fillable` 加 `coupon_code`/`original_amount`/`discount_amount`，`casts()` 加 `original_amount`/`discount_amount` 為 `decimal:2`；並確認 `app/Models/Purchase.php` 的 `$fillable` 含 `coupon_code`/`discount_amount`/`order_id`
- [x] T006 建立 `app/Services/CouponService.php` 類別骨架：定義 `validateForCart()`、`calculateDiscount()`、`label()`、`redeem()`、`stats()` 方法簽名與 PHPDoc（見 data-model.md「Service Interfaces」），方法主體先留待各 story 實作

**Checkpoint**: 資料表、Model、Service 骨架就緒，user story 可開始

---

## Phase 3: User Story 1 — 購物車套用折扣碼 (Priority: P1) 🎯 MVP

**Goal**: 用戶於購物車輸入折扣碼，伺服器即時驗證並回傳折扣，前端顯示折扣行與更新合計，可移除。

**Independent Test**: 先以 tinker 或 seeder 建一個 fixed 折扣碼（或先完成 US2），於購物車輸入該碼 → 折扣行出現、合計正確；移除 → 恢復原價；輸入無效碼 → 顯示分類錯誤。

### Implementation for User Story 1

- [x] T007 [US1] 在 `app/Services/CouponService.php` 實作 `calculateDiscount(CouponCode, int $subtotal): int`（fixed：`min(value,subtotal)`；ratio：`subtotal - (int) round(subtotal*value)`）與 `label(CouponCode): string`（fixed→「折抵 NT$XXX」、ratio→「X折優惠」），subtotal 進入計算前正規化為整數元（research D1）
- [x] T008 [US1] 在 `app/Services/CouponService.php` 實作 `validateForCart(string $code, array $courseIds, int $subtotal): array`：`CouponCode::active()` 查詢、課程適用性、折後 ≥ NT$1 判定，回傳結構化 `['success'=>bool,'error'=>中文]` 或成功含 `discount/payable/original/label/coupon`（分類錯誤訊息見 data-model.md）
- [x] T009 [US1] 建立 `app/Http/Controllers/CouponController.php` 的 `apply()`：以 `RateLimiter`（key `coupon-apply:{ip}`）實作 FR-019 失敗節流（`tooManyAttempts`→429、失敗 `hit(60)`、成功 `clear`），驗證 body（code/course_ids），伺服器依 course_ids 重算 subtotal，呼叫 `validateForCart`，回 JSON（contracts/api.md）
- [x] T010 [US1] 在 `routes/web.php` 既有 `api` 群組（非 auth 子群組，支援 guest）新增 `POST /api/cart/apply-coupon` → `CouponController@apply`，name `api.cart.apply-coupon`
- [x] T011 [P] [US1] 建立 `resources/js/Components/Cart/CouponInput.vue`：`<script setup>`，輸入欄 + 套用按鈕 + 錯誤訊息 + 已套用狀態（標籤/折扣額/移除鈕），呼叫 apply 端點，emit `applied`/`removed`，Tailwind mobile-first
- [x] T012 [US1] 修改 `resources/js/Pages/Cart/Index.vue`：嵌入 `CouponInput`，以 `ref` 保存已套用折扣（不持久化），顯示折扣行（-NT$ XXX）與折後合計；限定課程被移除時自動清除折扣並提示（US1-9）

**Checkpoint**: 購物車折扣碼套用/移除/錯誤處理完整可用（依賴外部已存在的折扣碼）

---

## Phase 4: User Story 2 — 後台建立與管理折扣碼 (Priority: P1)

**Goal**: 管理員後台 CRUD 折扣碼，含啟用/停用、軟刪除、列表一覽。

**Independent Test**: `/admin/coupons` 新增 fixed `TEST6`（NT$100、全站）→ 列表出現；試 value 5 / ratio 0.3 / 重複碼 → 各自擋下對應錯誤；切換啟用/停用即時生效。

### Implementation for User Story 2

- [x] T013 [P] [US2] 建立 `app/Http/Requests/Admin/StoreCouponRequest.php`：`code`(required/max:6/regex 英數/unique:coupon_codes,code)、`type`(in:fixed,ratio)、`value`(條件式 fixed→min:10、ratio→between:0.50,0.95)、`course_id`(nullable/exists)、`expires_at`(nullable/after:now)、`max_uses`(nullable/min:1)、`is_active`、`note`；`messages()` 中文（見 contracts/api.md）
- [x] T014 [P] [US2] 建立 `app/Http/Requests/Admin/UpdateCouponRequest.php`：同上，`code` 的 unique 加 `ignore($coupon)`（code 設唯讀/不開放修改）
- [x] T015 [US2] 建立 `app/Http/Controllers/Admin/CouponController.php`：`index`（未刪除列表，塑形 type_label/scope_label/remaining_label）、`create`、`store`、`edit`、`update`、`destroy`（軟刪除 `$coupon->delete()`）、`toggle`（切 `is_active`）；courses 下拉資料
- [x] T016 [US2] 在 `routes/web.php` 的 `['auth','admin']` 群組新增 `Route::resource('coupons', ...)->except(['show'])` + `PATCH /coupons/{coupon}/toggle`（name `admin.coupons.*`，見 contracts/api.md）
- [x] T017 [P] [US2] 建立 `resources/js/Components/Admin/CouponForm.vue`：新增/編輯共用表單（type 切換時動態顯示 fixed 金額/ratio 折數說明與即時驗證提示），參照既有 `CourseForm.vue` pattern
- [x] T018 [US2] 建立 `resources/js/Pages/Admin/Coupons/Index.vue`：列表（代碼/類型值/範圍/到期/名額/已用/狀態），含 toggle 開關、刪除確認、新增與編輯連結
- [x] T019 [P] [US2] 建立 `resources/js/Pages/Admin/Coupons/Create.vue` 與 `Edit.vue`（皆用 `CouponForm`，`router.post`/`router.put`，`onError` 顯示驗證錯誤）
- [x] T020 [US2] 在 `resources/js/Layouts/AdminLayout.vue` 後台側欄新增「折扣碼」連結指向 `admin.coupons.index`

**Checkpoint**: 後台折扣碼 CRUD 完整；搭配 US1 即可端到端建立並套用折扣碼

---

## Phase 5: User Story 3 — 結帳時折扣生效並記錄 (Priority: P2)

**Goal**: 結帳以折後金額建單，付款 webhook 確認後兌現使用次數並寫入訂單/購買記錄。

**Independent Test**: 套用名額=1 的碼 → 完成 sandbox 付款 → `orders` 三金額正確、`used_count=1`；同碼再結帳 → 「已達使用上限」；付款失敗 → `used_count` 不變。

### Implementation for User Story 3

- [x] T021 [US3] 在 `app/Services/CouponService.php` 實作 `redeem(string $code): void`：原子 `increment('used_count')`（軟限制不強制檢查上限），查無代碼記 `Log::warning` 後略過（research D4）
- [x] T022 [US3] 修改 `app/Services/CheckoutService.php` 的 `createOrder()`：新增 `?string $couponCode = null` 參數；計算 subtotal 後若有碼則呼叫 `CouponService::validateForCart`，失敗 `throw RuntimeException`；成功時 `Order::create` 寫 `coupon_code`/`original_amount=subtotal`/`discount_amount`，並以 `payable` **覆蓋** `total_amount`（research D-金額流，勿保留原價）
- [x] T023 [US3] 修改 `app/Services/CheckoutService.php` 的 `fulfillOrder()`：標記 paid 後，若 `order->coupon_code` 非空則於交易內 `CouponService::redeem()`；建立每筆 `Purchase` 時帶入 `coupon_code`（稽核）；`discount_amount` 僅記於**首筆**（= 訂單總折抵額），其餘為 0，使 `sum == order.discount_amount` 而免比例分攤運算（統計一律以 orders 為準，避免 YAGNI）
- [x] T024 [US3] 修改 `app/Http/Requests/CheckoutRequest.php`：新增 `coupon_code` => `nullable|string|max:6`
- [x] T025 [US3] 修改 `app/Http/Controllers/CheckoutController.php`：`initiate()` 取 `coupon_code` 傳入 `createOrder(..., couponCode:)`，捕捉 `RuntimeException` 回 409；`show()` 讀 `?coupon=` query → `validateForCart` 重驗 → 塑形 `coupon` prop（失敗則 null）
- [x] T026 [US3] 修改 `resources/js/Pages/Checkout/Index.vue`：顯示折扣摘要（代碼/折扣額/實付），`initiate` 的 POST body 帶 `coupon_code`
- [x] T027 [US3] 修改 `resources/js/Pages/Cart/Index.vue`：「前往結帳」連結於已套用折扣時帶 `?coupon=CODE`（將 US1 ref 狀態傳遞至結帳，research D2）

**Checkpoint**: 折扣端到端生效；金額正確送金流、使用次數於付款確認後計入

---

## Phase 6: User Story 4 — 後台查看折扣碼統計 (Priority: P2)

**Goal**: 統計指定折扣碼於 7/30/60/90 天及「全部」的成效與交易明細。

**Independent Test**: 某碼完成 2 筆訂單 → 統計頁筆數 2、營收=折後合計、總折抵正確、明細齊全；切換各時間範圍（含「全部」）數據即時刷新。

### Implementation for User Story 4

- [x] T028 [US4] 在 `app/Services/CouponService.php` 實作 `stats(CouponCode, ?int $days): array`：來源 `orders` 表 `status='paid'` 且 `coupon_code=code`；`$days=null` 時略過 `webhook_received_at` 範圍（「全部」）；回傳 count/revenue(sum total_amount)/discountTotal(sum discount_amount) 與明細（email/paid_at/total/original）
- [x] T029 [US4] 在 `app/Http/Controllers/Admin/CouponController.php` 新增 `show()`：解析 `?range`（7/30/60/90/all，預設 30），呼叫 `stats`，回 Inertia `Admin/Coupons/Show`；於 `routes/web.php` 新增 `GET /admin/coupons/{coupon}` name `admin.coupons.show`
- [x] T030 [P] [US4] 建立 `resources/js/Pages/Admin/Coupons/Show.vue`：時間範圍切換鈕（7/30/60/90/全部），摘要卡（筆數/營收/折抵）與明細表；空期間顯示「此期間無交易記錄」
- [x] T031 [US4] 修改 `resources/js/Pages/Admin/Coupons/Index.vue`：每列新增「統計」連結指向 `admin.coupons.show`

**Checkpoint**: 統計頁完整，含「全部」期間

---

## Phase 7: User Story 5 — 分享連結自動帶入折扣碼 (Priority: P3)

**Goal**: `/courses/xxx?coupon=CODE` 落地後，折扣碼於購物車/結帳自動套用（手動優先、無效靜默忽略）。

**Independent Test**: 以 `?coupon=TEST6` 開銷售頁 → 加入購物車 → 自動顯示已套用；手動另套碼則覆蓋；無效碼靜默忽略；移除後重整不再自動套用。

### Implementation for User Story 5

- [x] T032 [US5] 修改 `app/Http/Controllers/CourseController.php` 的 `show()`：於既有 `traffic_source` 捕捉處（約 line 120）並排讀 `?coupon=`，正規化（英數、取前 6、大寫）後 `session()->put('checkout_coupon', $code)`（contracts/api.md）
- [x] T033 [US5] 修改 `app/Http/Controllers/CartController.php` 的 `index()`：於**已登入與訪客兩個分支**皆讀 `session('checkout_coupon')`，以 `prefillCouponCode`(string|null) prop 回傳原始代碼字串（不在伺服器驗證，因訪客購物車於 localStorage、伺服器無 course_ids）；實際驗證由前端套用時呼叫 apply-coupon 完成
- [x] T034 [US5] 修改 `app/Http/Controllers/CheckoutController.php`：`show()` 以 `session('checkout_coupon')` 作 `?coupon=` 後備；`initiate()` 於 `createOrder()` 回傳成功後呼叫 `session()->forget('checkout_coupon')`（清除邏輯置於 **Controller**，`CheckoutService` 不得存取 session — constitution §II）
- [x] T035 [US5] 在 `app/Http/Controllers/CouponController.php` 新增 `clear()`（`session()->forget('checkout_coupon')`）並於 `routes/web.php` api 群組新增 `DELETE /api/cart/coupon` name `api.cart.clear-coupon`（用**獨立路徑**避免與既有 `DELETE /api/cart/{courseId}` 萬用參數衝突；另為既有 cart.remove 路由加 `->whereNumber('courseId')`），供移除自動帶入碼時呼叫
- [x] T036 [US5] 修改 `resources/js/Components/Cart/CouponInput.vue` 與 `resources/js/Pages/Cart/Index.vue`：接收 `prefillCouponCode` prop，`onMounted` 時若有值且無手動套用，**以當前購物車 course_ids（登入取 props.items、訪客取 localStorage）呼叫 apply-coupon 端點自動套用**（失敗靜默忽略，US5-2）；手動輸入覆蓋之；移除自動碼時呼叫 `api.cart.clear-coupon`（US5-3/US5-4，登入/訪客一致）

**Checkpoint**: 全部 5 個 user story 獨立可用

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: 跨 story 收尾與導航同步

- [x] T037 執行 `php artisan migrate` 套用 T002/T003 migration，並 `npm run build`（或 dev）確認前端編譯通過
- [x] T038 [P] 執行 `python plugins/spec_index_plugin.py` 同步 `specs/spec_index.json`、`specs/code_index.json`、`repo_map.md`（將 011 模組與檔案納入導航）
- [x] T039 依 `specs/011-discount-coupon/quickstart.md` 的 20 步驗收路徑與邊界清單逐項手動驗證

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (P1)**：無相依，可立即開始
- **Foundational (P2)**：依賴 Setup；**阻擋所有 user story**
- **User Stories (P3–P7)**：皆依賴 Foundational
  - US1、US2 同為 P1：可平行；**端到端驗證 US1 需 US2 已建碼**（或先以 seeder/tinker 建碼）
  - US3 依賴 US1（折扣計算/驗證）+ US2（有碼可用）
  - US4 依賴 US3（需有已完成訂單資料才有統計）
  - US5 依賴 US1（套用狀態/驗證）+ US3（結帳延續）
- **Polish (P8)**：依賴所有欲交付的 story 完成

### User Story Dependencies

- **US1 (P1)**：Foundational 後即可；計算/驗證為其他 story 共用核心
- **US2 (P1)**：Foundational 後即可；獨立於 US1
- **US3 (P2)**：依賴 US1 的 `CouponService::validateForCart/calculateDiscount`
- **US4 (P2)**：邏輯獨立，但有意義的驗收需 US3 產生的 paid 訂單
- **US5 (P3)**：依賴 US1 + US3

### Within Each User Story

- Service 方法 → Controller/Route → 前端元件 → 頁面整合
- 同檔案任務不可平行（如 `CouponService.php` 跨 T007/T008/T021/T028 需序列）

### Parallel Opportunities

- T004、T005 可平行（不同 Model 檔）
- T013、T014、T017、T019 可平行（不同檔案）
- T011 可與 T009/T010 平行（元件 vs 後端）
- T030、T038 標 [P]

---

## Parallel Example: Foundational

```bash
# T004 與 T005 不同檔案，可同時進行：
Task: "建立 app/Models/CouponCode.php"
Task: "修改 app/Models/Order.php + Purchase.php $fillable"
```

## Parallel Example: User Story 2

```bash
# 表單請求與前端元件可平行：
Task: "建立 StoreCouponRequest.php"
Task: "建立 UpdateCouponRequest.php"
Task: "建立 Components/Admin/CouponForm.vue"
```

---

## Implementation Strategy

### MVP First

1. Phase 1 Setup → Phase 2 Foundational
2. Phase 4 US2（先建碼能力）→ Phase 3 US1（套用）→ 端到端最小可用：可建碼並於購物車套用
3. **STOP & VALIDATE**：quickstart US1/US2 步驟

> 註：spec 將 US1 列首，但兩者同為 P1；實務上先做 US2 可讓 US1 立即有真實折扣碼可測（亦可用 seeder 解耦）。

### Incremental Delivery

1. Foundational → US2 + US1（建碼 + 套用，MVP）
2. + US3（結帳生效、金流帶折後金額、兌現次數）
3. + US4（統計，含「全部」）
4. + US5（分享連結自動帶入）
5. Polish（migrate/build/index 同步/驗收）

---

## Notes

- [P] = 不同檔案、無相依
- `CouponService.php` 為多 story 共用核心，跨 story 觸碰同檔需序列實作
- 金額全程整數元；`createOrder` 必須以 `payable` 覆蓋 `total_amount`（金流讀 `order.total_amount`）
- `used_count` 僅於 `fulfillOrder` webhook 確認後 `increment`
- 軟刪除靠 `UNIQUE(code)` + SoftDeletes 自然達成「代碼永久佔用」
- 每完成一任務或一邏輯群組即 commit
