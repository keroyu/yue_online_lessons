# Research: 購物車結帳系統 (009-cart-checkout)

**Date**: 2026-05-05
**Branch**: `009-cart-checkout`

## Decision Log

### D-001: Guest Cart Storage
- **Decision**: localStorage (client-side JSON array of course_ids)
- **Rationale**: Constitution III explicitly permits localStorage for ephemeral client-side features. Guest cart is ephemeral by nature — it only exists to reduce friction before login. No server resource needed.
- **Alternatives considered**: Server-side session cart (would require guest session tracking); cookie-based cart (less ergonomic, size limits)
- **Merge on login**: `POST /api/cart/merge { course_ids: [] }` — server validates each course (published, not already owned, not duplicate), then bulk-inserts CartItems.

### D-002: Multi-Course PayUni Order Model
- **Decision**: One PayUni transaction per checkout (total amount of all cart items). Order entity created server-side before redirect; webhook looks up Order to create Purchase records.
- **Rationale**: User confirmed: all non-Portaly courses route through a checkout page that sums the total and sends one PayUni request. Matches existing `display_price` → `Amt` pattern.
- **Alternatives considered**: One PayUni transaction per course (rejected — requires multiple redirects, poor UX); no Order entity (rejected — webhook would have no way to know which courses to unlock).

### D-003: Price Snapshot Policy
- **Decision**: CartItem does NOT store price. OrderItem stores price at checkout moment (not add-to-cart moment).
- **Rationale**: User confirmed — the platform has no obligation to hold a price just because a user added to cart. The price locked at checkout time is the price sent to PayUni, which is sufficient.
- **Implication**: OrderItem.price = course.display_price at the moment `POST /api/checkout/initiate` is called.

### D-004: Payment Failure Redirect
- **Decision**: Both PayUni and NewebPay failure/cancel → redirect to `/cart` with flash message "付款未完成，請再試一次；若仍遇到問題請聯絡客服 themustbig+learn@gmail.com".
- **Rationale**: User confirmed. Cart content is preserved (Order is not deleted — stays in `failed` status; CartItems remain untouched).

### D-005: Cart Authorization
- **Decision**: All server-side cart operations use `auth()->id()` from session. No `user_id` accepted from request body. Guest operations are client-side only.
- **Rationale**: User confirmed. Prevents IDOR attacks.

### D-006: Payment Gateway Routing
- **Decision**: `CheckoutService` reads `course.payment_gateway` and dispatches to `PayuniService` or `NewebPayService`. Frontend only calls `POST /api/checkout/initiate`.
- **Rationale**: Frontend should be gateway-agnostic. All cart items for a single checkout must share the same gateway (validated server-side: if cart has mixed gateways, reject — edge case, unlikely in practice since each course has one gateway).
- **Constraint for MVP**: Only PayUni implemented. NewebPay service stub created but not wired.

### D-007: NewebPay Architecture Compatibility
- **Decision**: NewebPay uses same redirect model as PayUni. No architecture changes needed — add `NewebPayService`, `NewebPayController`, two new routes.
- **Key differences from PayUni**:
  - Encryption: AES-256-CBC (OPENSSL_ZERO_PADDING, hex output) + SHA256 TradeSha
  - Form POST target: `https://ccore.newebpay.com/MPG/mpg_gateway` (test) / `https://core.newebpay.com/MPG/mpg_gateway` (prod)
  - NotifyURL must respond string `"SUCCESS"` (not `1|OK` like PayUni)
  - ReturnURL receives POST (same as PayUni)
  - Required env vars: `NEWEBPAY_MERCHANT_ID`, `NEWEBPAY_HASH_KEY`, `NEWEBPAY_HASH_IV`
  - Order number: alphanumeric + underscore, max 30 chars, must be unique

### D-008: Route Design
- **Decision**: Follow existing `/api/payment/{gateway}/` + `/api/webhooks/{gateway}` + `/payment/{gateway}/return` pattern.
  - Checkout initiation: single gateway-agnostic `POST /api/checkout/initiate`
  - Webhooks and return URLs remain gateway-specific (different encryption, different response format)
- **Rationale**: Matches existing codebase conventions exactly. No dynamic dispatch needed.

### D-009: Service Boundaries
- **Decision**: `CheckoutService` is required (spans CartItem, Order, OrderItem, external gateway call). `CartController` handles simple CRUD directly without a Service.
- **Rationale**: Constitution II mandates a Service when operation spans 2+ models or involves external I/O. Cart add/remove is single-model CRUD. Merge is borderline but simple enough for controller.

### D-010: Post-Purchase Redirect
- **Decision**: Payment success → redirect to `/member/learning` (existing "我的課程" page).
- **Rationale**: User confirmed. This page is already auth-gated. User is always logged in at this point (login is required before checkout).

---

## Open Questions (Deferred to Implementation)

- **Mixed-gateway cart**: What if a user somehow has courses from different gateways in the cart? Server should validate all cart items use the same `payment_gateway` before checkout; show user-friendly error if mixed. Implementation detail.
- **NewebPay order number format**: Must be ≤ 30 chars alphanumeric+underscore. Use format `ORDER_{order_id}_{timestamp_last6}` — ensure uniqueness.
- **PayUni existing `initiate` endpoint**: Deprecated in favor of new checkout flow. Keep route for now (may be used by Portaly-adjacent code). Can be removed in a future cleanup.

---

## Incremental Update: 2026-05-05 — NewebPay Promoted to Full Implementation

### D-011: NewebPay Scope Change
- **Decision**: NewebPay (藍新金流) is now fully implemented in this version (not deferred).
- **Rationale**: User requested full implementation alongside PayUni.
- **Impact on prior decisions**:
  - D-007 updated: NewebPayService is fully implemented, not a skeleton
  - D-006 updated: CheckoutService routes to both PayuniService AND NewebPayService in this version

### D-012: Mixed-Gateway Cart Handling
- **Decision**: If cart contains courses with different payment_gateway values (payuni + newebpay), system blocks checkout and prompts user to resolve the conflict. No automatic cart splitting.
- **Rationale**: Simplest correct behavior. Cart splitting would require two separate Order records and two payment flows — over-engineered for current scale.
- **Alternatives considered**: Auto-split cart (rejected — complex UX, double checkout redirects); allow mixed gateway with one gateway taking priority (rejected — confusing for merchant reconciliation).

### D-013: NewebPay MerchantOrderNo Format
- **Decision**: Use `YO{orderId:06d}{YmdHis}{rand4}` format (same prefix as PayUni Order-based trades).
- **Rationale**: Consistent format across gateways. `YO` prefix distinguishes Order-based flow from legacy `YC` single-course PayUni flow. Max 30 chars — `YO` + 6 + 14 + 4 = 26 chars ✓.
- **Idempotency**: Orders table `gateway_trade_no` unique constraint prevents duplicate processing.

### D-014: NewebPayService — Supported Payment Methods (MVP)
- **Decision**: Enable credit card (CREDIT=1) only for MVP. LINE Pay, ATM, convenience store are supported by NewebPay but not enabled in initial integration.
- **Rationale**: Simplest viable integration. Other methods can be enabled via config without code changes.
- **Future**: Add `LINEPAY`, `VACC`, `CVS` flags to NewebPayService config when merchant account supports them.

### D-015: NewebPay Buyer Info Source
- **Decision**: NewebPay NotifyURL payload includes buyer email in TradeInfo after decryption (unlike PayUni which omits it). No Cache needed for buyer lookup — use `Order → user → email`.
- **Rationale**: NewebPay MPG includes `Email` in the encrypted TradeInfo. The Order record also has `user_id` linking to the user. Either source is reliable; use Order's user as primary, TradeInfo email as fallback.

---

## Incremental Update: 2026-05-05 — Maintainability Optimizations (Plan v2)

### D-016: 抽出 `OrderFulfillmentService` 統一兩金流的履約邏輯
- **Decision**: 新增 `App\Services\OrderFulfillmentService::fulfill(Order $order)`，由 `PayuniService::processNotify`（YO 分支）與 `NewebPayService::processNotify` 共同呼叫。
- **Rationale**:
  1. 兩個 caller 會做完全相同的 7 件事（建 Purchase / 清 cart / Drip subscribe / Drip convert / Order 狀態更新 / 雙層冪等檢查 / log）— 若不抽出將產生大量重複。
  2. Constitution II 明確規定「跨 2+ models 或多步驟 workflow MUST 抽 Service」，本邏輯涵蓋 Order/OrderItem/Purchase/CartItem/User + DripService 副作用，符合門檻。
  3. Constitution X (YAGNI)：抽出時機是「已有兩個 caller」，並非預先抽象，符合 YAGNI 原則。
- **Alternatives considered**:
  - Trait（被否決：trait 無法正確管理依賴注入 DripService）
  - 各自重複（被否決：30+ 行邏輯複製，違反 DRY）
  - 在 PayuniService 內呼叫共用，NewebPayService 透過 PayuniService 呼叫（被否決：服務間反向依賴破壞 Constitution A9 依賴方向）

### D-017: 前端引入 `useCart()` composable
- **Decision**: 新增 `resources/js/composables/useCart.js`，作為 4 個元件（Navigation、Course/Show、Cart/Index、Checkout/Index）的唯一購物車狀態與操作入口；統一觸發 Meta Pixel `AddToCart`。
- **Rationale**:
  1. 4 個使用點若分別實作 → guest/server 切換邏輯與 fbq 觸發都會在 4 處重複，維護災難。
  2. composable 是 Vue 3 idiomatic pattern（非新依賴），不違反 Constitution III「不引入 Vuex/Pinia」。
  3. 集中 Meta Pixel 觸發點 → 避免漏觸發導致行銷數據失準（grep 可直接驗證 `fbq.*AddToCart` 只出現在此檔）。
- **Alternatives considered**:
  - 4 處各自實作（被否決：重複 + 漏觸發風險）
  - 引入 Pinia（被否決：違反 Constitution III + YAGNI，無需 store 級狀態）
  - 放在 mixin（被否決：Vue 3 已不推薦 mixin，Composition API 是慣例）

### D-018: `purchases.order_id` 可空 FK（反查欄位）
- **Decision**: 新增 migration `add_order_id_to_purchases_table`，欄位為 nullable `unsignedBigInteger` + FK references `orders(id) onDelete restrict`。
- **Rationale**:
  1. 新流程（YO Order）建立的 Purchase 可反查 Order；舊流程（YC、Portaly、system_assigned、gift）保持 NULL，零影響。
  2. 退費/客服查詢場景需要從 Purchase 一鍵跳到 Order 看完整快照，否則只能用 `payuni_trade_no` 字串比對。
  3. 成本：1 個 migration、1 個 fillable 欄位、1 個 BelongsTo 關係 — 極低；維護價值高。
- **Alternatives considered**:
  - 不加（被否決：未來退費功能會被迫追加，現在加成本最低）
  - 用 `payuni_trade_no` LIKE 字串比對（被否決：藍新課程不會寫入此欄位，無法統一查詢）

### D-019: `Course::isCartEligible()` 模型布林方法
- **Decision**: 在 `Course` model 新增 `isCartEligible(): bool` 方法，回傳是否可加入購物車的單一判斷依據。
- **Rationale**:
  1. 條件「非 Portaly + price > 0 + type !== high_ticket + status === published」會在 4 處用到：前端 Course/Show.vue 按鈕邏輯、`POST /api/cart` 驗證、`CheckoutService::initiate` 驗證、`scopeCartEligible` query scope。
  2. 集中於 model 方法 → 條件變更時僅一處修改。
  3. Constitution IV 明示「`is{Condition}()` 為布林檢查的命名慣例」，與既有 `isAdmin()`、`isExpired()` 一致。
- **Alternatives considered**:
  - 各處 inline 條件（被否決：4 處重複，條件變更易遺漏）
  - 只用 scope（被否決：scope 是 query，前端 prop 用不到）
