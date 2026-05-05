# Implementation Plan: 購物車結帳系統

**Branch**: `009-cart-checkout` | **Date**: 2026-05-05 | **Spec**: [spec.md](spec.md)

## Summary

重構購買流程：PayUni 與藍新金流（NewebPay）課程統一改為「加入購物車 → 購物車頁 → 結帳頁 → 對應金流」。支援 guest cart（localStorage）合併至 server-side cart（DB）。新增 `CartItem`、`Order`、`OrderItem` 三張資料表 + `purchases.order_id` 反查欄位；`CheckoutService` 統一處理結帳邏輯並依 `payment_gateway` 欄位路由至 `PayuniService` 或 `NewebPayService`，`OrderFulfillmentService` 單一處理「Order → Purchases」轉換邏輯，兩個金流服務共用。Portaly、免費、high-ticket 課程完全不動。

**最佳化重點（v2 增量）**：
1. 抽出 `OrderFulfillmentService` → 兩個金流 service 共用「Order 轉 Purchase」邏輯（去除重複），Constitution II 多模型 + 工作流符合條件。
2. 兩個金流 service 採用**對稱公開介面**（`buildCheckoutForm(Order)` / `processOrderNotify(...)`）→ 未來新增金流 drop-in。
3. `PayuniService::processNotify` 用 `MerTradeNo` 前綴顯式分流（`YC` 舊流程零改動 / `YO` 新流程委派 `OrderFulfillmentService`）。
4. `Course::isCartEligible()` 模型布林方法 → 統一前端按鈕邏輯與後端購物車驗證的判斷依據。
5. `purchases.order_id` 可空 FK → 新流程訂單可反查 Purchase；舊流程不受影響。
6. 前端 `useCart()` composable → 集中 guest/server 切換 + Meta Pixel 觸發；3+ 使用點（Navigation、Course/Show、Cart/Index、Checkout/Index）一致性。

## Technical Context

**Language/Version**: PHP 8.2 / Laravel 12
**Primary Dependencies**: Inertia.js v2, Vue 3 (`<script setup>`), Tailwind CSS v4
**Storage**: MySQL — 3 new tables (`cart_items`, `orders`, `order_items`), 2 altered tables (`courses` 新增 `payment_gateway`、`purchases` 新增 `order_id`)
**Testing**: PHPUnit (`php artisan test`)
**Target Platform**: Web application (existing Laravel + Inertia stack)
**Project Type**: Web application
**Performance Goals**: 購物車 badge 即時更新（無頁面重整）；checkout initiate 回應 < 2 秒
**Constraints**: 不動 Portaly / 免費 / high-ticket 課程流程；PayUni 與藍新金流均在本版本實作；舊 PayUni 單一課程流程（`YC` 前綴）100% 保留
**Scale/Scope**: 單一租戶平台，預期同時購物車用戶數 < 1000

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Controller Layering | ✅ | `CartController` 簡單 CRUD（單模型）；`CheckoutController` → `CheckoutService`；`NewebPayController` 薄殼 → `NewebPayService` + `OrderFulfillmentService`；inline validate 配合 public/lightweight 慣例（仿 `DripSubscriptionController`） |
| II. Service Encapsulation | ✅ | `CheckoutService`（跨 CartItem/Order/OrderItem + 外部 API + 多步驟）；`NewebPayService`（外部 API + 加解密）；`OrderFulfillmentService`（Order→Purchases 多模型 + Drip 副作用）— 三者皆達門檻 |
| III. Frontend Architecture | ✅ | Vue 3 `<script setup>`、Tailwind utility、localStorage 僅用於 guest cart（ephemeral，符合附錄 A7）。新增 `composables/useCart.js`（純 Composition API，無外部依賴，無全局 store） |
| IV. Model Conventions | ✅ | `$fillable`、`casts()`、`Attribute` accessor、`scope*` 方法、`is*()` 布林、`boot()` 設 `created_at`（仿 LessonProgress） |
| V. Job Discipline | ✅ | 無新 Job；Order 建立與 webhook 在 request cycle 內完成（同 PayUni 既有模式） |
| VI. Email Patterns | ✅ | 無新 email |
| VII. Error Handling | ✅ | Service 回傳 `['success' => false, 'error' => '中文']`；webhook 永遠回 200/SUCCESS（吞已知例外、re-throw 未知）；前台錯誤 redirect + flash |
| VIII. Authorization | ✅ | cart 路由加 `auth` middleware；`cartItem.user_id` server 端強制驗證；guest cart client-only；Service 不取 `auth()`（接 `User` 參數） |
| IX. Security | ✅ | `payment_gateway` 不從 client 傳入；PayUni HashInfo 驗證；藍新 TradeSha 驗證；`.env` 管理金鑰；webhook idempotency 雙層（Order.status + Purchase 唯一性） |
| X. YAGNI | ✅ | 無 Repository、無 DTO、無 Event/Listener、無全局 store；`OrderFulfillmentService` 抽出**因為**有兩個 caller（不是預先抽象）；`useCart()` composable 同理（4 個使用點） |

**Violations**: 無

## Project Structure

### Documentation (this feature)

```text
specs/009-cart-checkout/
├── plan.md              ← this file ✅
├── research.md          ← Phase 0 ✅
├── data-model.md        ← Phase 1 ✅
├── quickstart.md        ← Phase 1 ✅
├── contracts/
│   └── api.md           ← Phase 1 ✅
├── checklists/
│   └── requirements.md
└── tasks.md             ← /speckit.tasks (not yet)
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── CartController.php                    (new — index/store/destroy/merge/show)
│   │   ├── CheckoutController.php                (new — show + initiate)
│   │   └── Payment/
│   │       ├── PayuniController.php              (update: notify 不變，return 接 YO 走 OrderFulfillmentService)
│   │       └── NewebPayController.php            (new — notify + return，薄殼)
│   └── Requests/                                 (inline validation for cart ops，仿 DripSubscriptionController)
├── Models/
│   ├── CartItem.php                              (new)
│   ├── Order.php                                 (new)
│   ├── OrderItem.php                             (new)
│   ├── Purchase.php                              (update: $fillable += 'order_id', BelongsTo Order)
│   └── Course.php                                (update: payment_gateway fillable + effectiveGateway accessor + isCartEligible() + scopeCartEligible)
└── Services/
    ├── CheckoutService.php                       (new — cart validation, Order creation, gateway dispatch)
    ├── OrderFulfillmentService.php               (new — Order → Purchases，兩金流共用，唯一 Drip 觸發點)
    ├── NewebPayService.php                       (new — AES-256-CBC, TradeSha, form build, notify)
    └── PayuniService.php                         (update: processNotify 前綴分流；buildCheckoutForm(Order) 新增；舊 buildPaymentForm 保留)

database/migrations/
├── XXXX_add_payment_gateway_to_courses_table.php (new)
├── XXXX_create_cart_items_table.php              (new)
├── XXXX_create_orders_table.php                  (new)
├── XXXX_create_order_items_table.php             (new)
└── XXXX_add_order_id_to_purchases_table.php      (new — nullable FK，反查用)

config/services.php                                (update: 新增 newebpay 區段)

resources/js/
├── composables/
│   └── useCart.js                                (new — guest/server cart + Meta Pixel 統一觸發點)
├── Pages/
│   ├── Cart/
│   │   └── Index.vue                             (new — 使用 useCart())
│   └── Checkout/
│       └── Index.vue                             (new — useCart() + 自動送出隱藏表單)
└── Components/
    └── Layout/
        └── Navigation.vue                        (update: cart icon + badge，使用 useCart())

resources/js/Pages/Course/Show.vue                (update: 加入購物車 / 直接購買 buttons，使用 useCart())
resources/js/Components/Admin/CourseForm.vue      (update: payment_gateway selector — PayUni + 藍新；偵測 portaly_product_id)

routes/
├── web.php   (update: /cart, /checkout, /payment/newebpay/return — CSRF 略過)
└── api.php   (update: cart CRUD, cart/merge, checkout/initiate, webhooks/newebpay)
```

**Structure Decision**: Single Laravel web application. 完全沿用既有 layout，新增的 `app/Services/OrderFulfillmentService.php` 與 `resources/js/composables/useCart.js` 兩個檔案不違反現有 pattern：Service 是既有模式（已有 7 個）；composables 雖為新目錄但屬 Vue 3 慣例位置，且僅在 4 個使用點之前才建立（YAGNI 對齊）。

## Gateway Routing Architecture

```
[結帳發起]
POST /api/checkout/initiate
  └─ CheckoutService::initiate($user)
       ├─ validate cart (isCartEligible, sameGateway, atLeast1Item)
       ├─ create Order (status=pending) + OrderItems (price = course.display_price@now)
       ├─ generate gateway_trade_no = "YO" + str_pad($order->id, 6) + YmdHis + rand4
       ├─ Order::update(['gateway_trade_no' => $tradeNo])
       ├─ if 'payuni'   → PayuniService::buildCheckoutForm($order)
       └─ if 'newebpay' → NewebPayService::buildCheckoutForm($order)

[Webhook / Return]
POST /api/webhooks/payuni  (existing route)
  └─ PayuniController@notify → PayuniService::processNotify($enc, $hash)
       ├─ verifyAndDecrypt
       ├─ inspect $data['MerTradeNo'] 前綴
       │     ├─ 'YC' → 既有單一課程流程（完全不動，私有方法 processLegacySingleCourseNotify()）
       │     └─ 'YO' → OrderFulfillmentService::fulfill(Order::firstWhere('gateway_trade_no', $tradeNo))
       └─ 回應 '1|OK'

POST /api/webhooks/newebpay  (new route)
  └─ NewebPayController@notify → NewebPayService::processNotify($tradeInfo, $tradeSha)
       ├─ verifyTradeSha + decrypt
       ├─ Status == 'SUCCESS' guard
       └─ OrderFulfillmentService::fulfill(Order::firstWhere('gateway_trade_no', $merOrderNo))
       └─ 回應 'SUCCESS'

POST /payment/{payuni|newebpay}/return  (CSRF excluded)
  └─ {Service}::processNotify(...)   ← idempotent，與 NotifyURL 競態安全
       └─ if success → redirect /member/learning
       └─ if failure → redirect /cart with flash
```

**MerTradeNo 前綴設計（風險控管的核心）：**
- `YC{courseId:04d}{YmdHis}{rand4}` → 現有 PayUni 單一課程流程（完全保留，零改動）
- `YO{orderId:06d}{YmdHis}{rand4}`  → 新購物車 Order 流程（PayUni 與藍新共用同格式，最長 26 字元，符合藍新 30 字元上限）

## Service Layer Architecture (核心優化)

### `CheckoutService` — 入口協調器

**責任**：購物車快照 → Order 建立 → 金流路由

```php
public function initiate(User $user): array
{
    $items = CartItem::with('course')->where('user_id', $user->id)->get();

    if ($items->isEmpty()) {
        return ['success' => false, 'error' => '購物車是空的'];
    }

    $eligible = $items->filter(fn ($i) => $i->course->isCartEligible());
    if ($eligible->count() !== $items->count()) {
        return ['success' => false, 'error' => '購物車中有無法結帳的課程，請移除後重試'];
    }

    $gateways = $eligible->pluck('course.payment_gateway')->unique();
    if ($gateways->count() > 1) {
        return ['success' => false, 'error' => '購物車中有不同金流的課程，請分開結帳'];
    }
    $gateway = $gateways->first();

    return DB::transaction(function () use ($user, $eligible, $gateway) {
        $order = Order::create([
            'user_id'         => $user->id,
            'total_amount'    => $eligible->sum(fn ($i) => $i->course->display_price),
            'payment_gateway' => $gateway,
            'status'          => 'pending',
        ]);

        foreach ($eligible as $item) {
            $order->items()->create([
                'course_id' => $item->course_id,
                'price'     => $item->course->display_price,
            ]);
        }

        $order->update([
            'gateway_trade_no' => $this->generateTradeNo($order->id),
        ]);

        $form = match ($gateway) {
            'payuni'   => app(PayuniService::class)->buildCheckoutForm($order->fresh('items.course')),
            'newebpay' => app(NewebPayService::class)->buildCheckoutForm($order->fresh('items.course')),
        };

        return ['success' => true, 'gateway' => $gateway, ...$form];
    });
}

private function generateTradeNo(int $orderId): string
{
    return sprintf('YO%06d%s%04d', $orderId, date('YmdHis'), rand(1000, 9999));
}
```

### `OrderFulfillmentService` — 統一履約器（**最佳化重點**）

**責任**：Order paid → 建立 Purchase rows → 清空 cart → 觸發 Drip。兩個金流共用此邏輯，避免重複。

```php
public function fulfill(Order $order): array
{
    if ($order->status === 'paid') {
        return ['success' => true, 'error' => '', 'idempotent' => true];
    }

    return DB::transaction(function () use ($order) {
        $order->loadMissing('items.course', 'user');
        $user = $order->user;

        foreach ($order->items as $item) {
            // 雙層冪等：已存在則略過
            if (Purchase::where('user_id', $user->id)
                       ->where('course_id', $item->course_id)
                       ->where('status', 'paid')
                       ->exists()) {
                continue;
            }

            Purchase::create([
                'user_id'             => $user->id,
                'course_id'           => $item->course_id,
                'order_id'            => $order->id,
                'payuni_trade_no'     => $order->payment_gateway === 'payuni' ? $order->gateway_trade_no : null,
                'buyer_email'         => $user->email,
                'amount'              => $item->price,
                'currency'            => 'TWD',
                'status'              => 'paid',
                'type'                => 'paid',
                'source'              => $order->payment_gateway, // 'payuni' | 'newebpay'
                'webhook_received_at' => now(),
            ]);
        }

        $order->update(['status' => 'paid']);

        // 清空購物車中此次已付款的課程
        CartItem::where('user_id', $user->id)
                ->whereIn('course_id', $order->items->pluck('course_id'))
                ->delete();

        // Drip 觸發（與既有 PayuniService::processNotify 邏輯相同）
        $dripService = app(DripService::class);
        foreach ($order->items as $item) {
            if ($item->course->course_type === 'drip') {
                $dripService->subscribe($user, $item->course);
            }
            $dripService->checkAndConvert($user, $item->course);
        }

        Log::info('OrderFulfillment: completed', [
            'order_id' => $order->id,
            'user_id'  => $user->id,
            'gateway'  => $order->payment_gateway,
            'items'    => $order->items->count(),
        ]);

        return ['success' => true, 'error' => ''];
    });
}
```

**為何抽出**：
- 兩個 caller（PayuniService::processNotify 的 YO 分支 + NewebPayService::processNotify）會做完全相同的 7 件事（建 Purchase / 清 cart / Drip subscribe / Drip convert / Order 狀態 / 雙層冪等 / log）
- Constitution X (YAGNI)：兩個 caller 才抽，不是預先抽象
- Constitution II：跨 Order/OrderItem/Purchase/CartItem/User + DripService 副作用，符合 Service 門檻

### `PayuniService::processNotify` — 前綴分流（**風險控管核心**）

```php
public function processNotify(string $encryptInfo, string $hashInfo): array
{
    $data = $this->verifyAndDecrypt($encryptInfo, $hashInfo);
    if (!$data) return ['success' => false, 'error' => 'signature_mismatch'];

    if (($data['Status'] ?? '') !== 'SUCCESS' || ($data['TradeStatus'] ?? '') != '1') {
        return ['success' => true, 'error' => '']; // 非成功直接吞
    }

    $merTradeNo = $data['MerTradeNo'] ?? '';

    // ─── 前綴分流（保留舊流程零改動）─────────────────────────
    if (str_starts_with($merTradeNo, 'YO')) {
        $order = Order::where('gateway_trade_no', $merTradeNo)->first();
        if (!$order) {
            Log::error('PayUni: YO order not found', ['MerTradeNo' => $merTradeNo]);
            return ['success' => false, 'error' => 'order_not_found'];
        }
        return app(OrderFulfillmentService::class)->fulfill($order);
    }

    // YC 前綴 → 走原有單一課程邏輯（程式碼完全不動，內縮為私有方法）
    return $this->processLegacySingleCourseNotify($data, $merTradeNo);
}
```

**為何如此設計**：
- 原有 `YC` 流程的所有 30+ 行邏輯內縮為私有方法 `processLegacySingleCourseNotify($data, $merTradeNo)`，**邏輯完全不動**，僅縮排與命名
- 新流程進入點只有 4 行，極小攻擊面
- `Order::firstWhere('gateway_trade_no', ...)` 利用 unique index，O(log n) 查詢
- Constitution VII：仍回傳結構化 array，仍吞已知例外

### `NewebPayService` — 對稱介面

```php
public function buildCheckoutForm(Order $order): array;       // 對應 PayuniService::buildCheckoutForm
public function processNotify(string $tradeInfo, string $tradeSha): array;  // 對應 PayuniService::processNotify
private function encrypt(string $data): string;               // AES-256-CBC OPENSSL_ZERO_PADDING → bin2hex
private function decrypt(string $hex): string;                // hex2bin → AES decrypt
private function verifyTradeSha(string $tradeInfo, string $sha): bool;  // SHA256 uppercase
```

兩個服務**公開介面對稱**，未來若新增第三家金流，CheckoutService 的 match 表只需增加一行。

## Frontend Optimization: `useCart()` Composable

**位置**：`resources/js/composables/useCart.js`

**為何引入**（YAGNI 對齊檢查）：
- 使用點：`Navigation.vue`（badge）、`Course/Show.vue`（加入購物車 + 直接購買）、`Cart/Index.vue`（清單操作）、`Checkout/Index.vue`（顯示）—— 4 個使用點
- 集中責任：guest（localStorage）vs server（router）切換、Meta Pixel `AddToCart` 觸發、reactive 狀態 sync
- 替代方案（不採用）：每個元件各自實作 → 4 處重複；放 Vuex/Pinia → 違反 Constitution III「不引入新狀態庫」
- 最終結論：composable 是 Vue 3 慣例位置，無新依賴，3 行 import 即可使用 → 接受

**輸出**：
```js
// resources/js/composables/useCart.js
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const items = ref([])  // 模組層級 = 全頁面共享單例

export function useCart() {
  const page = usePage()
  const isAuthed = computed(() => !!page.props.auth?.user)

  async function init() { /* 已登入：從 props 載入；未登入：localStorage 載入 */ }
  async function add(course) { /* server | localStorage + fbq AddToCart */ }
  async function remove(item) { /* server | localStorage + reactive 更新 */ }
  async function mergeOnLogin() { /* POST /api/cart/merge，由登入 redirect 後觸發 */ }

  return {
    items: computed(() => items.value),
    count: computed(() => items.value.length),
    total: computed(() => items.value.reduce((s, i) => s + i.course.display_price, 0)),
    add, remove, init, mergeOnLogin,
  }
}
```

**Meta Pixel 觸發點集中於 composable**：避免任一元件忘記 `fbq` → 行銷數據漏失。

## Risk Mitigation Matrix

| 風險 | 影響面 | 緩解措施 | 驗收 |
|------|-------|---------|------|
| 舊 YC 流程被誤改 | 既有 PayUni 課程付款失敗 | `processNotify` 前綴分流；YC 邏輯整段內縮為私有方法、行為不動；既有測試卡可驗證 | 以測試卡完成既有 YC 課程付款 → Purchase 正常建立 |
| Notify 與 Return 競態 | 重複建立 Purchase / Order 雙重狀態 | `OrderFulfillmentService::fulfill` 雙層冪等：(1) `$order->status === 'paid'` early return；(2) Purchase exists 略過 | 模擬 Notify 與 Return 同時抵達 |
| 藍新 TradeSha 驗證失敗 | webhook 拒絕導致重試風暴 | 簽章不符仍回 `SUCCESS`（log warning）防重試；只有有效簽章才呼 fulfill | 製造錯誤 sha → log 有 warning，藍新不重試 |
| 混合金流購物車 | 結帳失敗、用戶困惑 | `CheckoutService::initiate` early-fail with 中文訊息；前端 `Cart/Index.vue` 顯眼提示 | E2E：放入 PayUni + 藍新課程 → 結帳按鈕禁用 + 提示 |
| Course 下架後仍在 cart | Order 建立卡住 | `Course::scopeCartEligible` + `isCartEligible()` 雙閘：cart 載入時過濾、checkout 再驗一次 | 手動下架後重整 cart 頁 → 課程消失 |
| Order 留 pending 永不關閉 | 髒資料累積 | `gateway_trade_no` 唯一 index 防重複；非阻塞，可選未來加 cron 清理（YAGNI 暫不實作） | 僅 log 觀察；> 100 筆再決定是否加清理 |
| 藍新 TimeStamp ±120s | 加密無效 | 採用伺服器 `time()`；NTP 同步在 Forge 預設啟用 | 部署時驗證 `date` 與 `ntpdate` 一致 |
| guest cart 與 server cart merge 衝突 | 重複加入 | merge endpoint 內逐筆檢查 unique `(user_id, course_id)` + 已購買 → 略過 | unit test：guest 有 [A, B]、server 有 [B, C]、user 已購 D，merge 後 = [A, B, C] |
| Pixel `AddToCart` 漏觸發 | 行銷數據失準 | 統一在 `useCart().add()` 內 `window.fbq?.('track', ...)`；元件層不可繞過 | grep 確認 fbq AddToCart 只出現在 useCart.js |

## Migration Sequence (依賴順序)

1. `add_payment_gateway_to_courses_table` — `payuni` 預設值
2. `create_cart_items_table`
3. `create_orders_table` — `gateway_trade_no` UNIQUE
4. `create_order_items_table`
5. `add_order_id_to_purchases_table` — 可空 FK，舊資料 NULL

每個 migration 可獨立 rollback。

## Validation & Field Rules (Constitution VIII)

| 端點 | 授權閘 | 驗證 |
|------|-------|------|
| `POST /api/cart` | `auth` middleware | inline: `course_id` exists + `Course::isCartEligible()` |
| `DELETE /api/cart/{cartItem}` | `auth` middleware | implicit binding + `cartItem.user_id === auth()->id()` 否則 abort 403 |
| `POST /api/cart/merge` | `auth` middleware | inline: `course_ids` array of int |
| `POST /api/checkout/initiate` | `auth` middleware | 無 body；server 從 session 取 user 與 cart |
| `POST /api/webhooks/payuni` | 無 middleware | HashInfo 驗證內部處理；總是回 200 |
| `POST /api/webhooks/newebpay` | 無 middleware | TradeSha 驗證內部處理；總是回 SUCCESS |
| `POST /payment/{gw}/return` | 無 middleware（CSRF 略過） | 同 notify 簽章驗證；最終 redirect |

**關鍵**：`payment_gateway` 永遠由 server 從 `Course` 讀取，**不接受 client 傳入**。

## Phase 0 Output: research.md ✅

存在於 `specs/009-cart-checkout/research.md`，記錄 D-001 ~ D-015 全部決策。本次更新增加 **D-016：OrderFulfillmentService 抽出決策** 與 **D-017：useCart composable 引入決策**（待寫入 research.md）。

## Phase 1 Output: data-model.md / contracts/api.md / quickstart.md ✅

存在於 `specs/009-cart-checkout/`。本次更新增量：
- `data-model.md`：新增 `purchases.order_id` migration，新增 `OrderFulfillmentService` 公開方法簽章
- `contracts/api.md`：endpoints 不變
- `quickstart.md`：新增 `useCart()` 使用範例

## Post-Design Constitution Re-Check

| Principle | Re-Check | 變動 |
|-----------|---------|------|
| I–X | All ✅ | 抽出 `OrderFulfillmentService` 與 `useCart()` 後仍在原則範圍內：Service 是既有 pattern；composable 是 Vue 3 idiomatic 位置（非新狀態庫）；無 Repository / DTO / Event 引入 |

**Final Gate**: PASS — 可進入 `/speckit.tasks` 階段。

## What's Next

執行 `/speckit.tasks` 生成 `tasks.md`：
- 預估 ~28–32 tasks，分組為：DB migrations (5)、Models (4)、Services (3)、Controllers (3)、Routes/Web (1)、Frontend composable (1)、Pages (2)、Components (3)、Risk-mitigation tests (5)
- 標記 `[parallel]` 的可同時進行：例如 4 個 migration、3 個 Service 主體（互不依賴）、2 個 Vue Page
