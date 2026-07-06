# Research & Design Decisions: 折扣碼管理系統

**Feature Branch**: `011-discount-coupon`
**Created**: 2026-06-09

本文件記錄 Phase 0 的設計決策。spec 的三項 clarification（IP 節流、NT$1 下限、軟刪除）已定案，以下聚焦於「如何以最一致、可維護的方式落地」。

---

## D1. 折扣計算邏輯與單一真實來源

**Decision**: 所有折扣計算集中於 `CouponService::calculateDiscount(CouponCode $coupon, int $subtotal): int`，回傳「折抵金額（整數元）」。三個呼叫點共用：(1) 前台 apply-coupon 驗證、(2) `CheckoutService::createOrder()` 建單、(3) 結帳前最終重驗。

計算規則：
- **fixed**：`discount = min(coupon.value, subtotal)`；折抵金額即設定值，但不超過小計。
- **ratio**：`payable = round(subtotal * coupon.value)`（四捨五入至整數元，`(int) round()`）；`discount = subtotal - payable`。
- **下限保護**：若 `subtotal - discount < 1`（NT$1），驗證回傳失敗（不在 `calculateDiscount` 內拋例外，由 `validateForCart` 統一判定）。

**Rationale**: 折扣只扣一次整筆訂單（FR-010），故計算對象是 subtotal 而非逐課程。集中於單一方法避免前後端、建單、兌現各自實作導致金額不一致（SC-002 要求 100% 準確）。整數元計算避免金流送出小數。

**Alternatives considered**:
- 前端計算折扣再送後端 → 駁回：金額必須伺服器權威，前端僅顯示。
- 逐 OrderItem 分攤折扣後相加 → 駁回：與「只扣一次」語義不符，且四捨五入累積誤差。

---

## D2. 折扣碼狀態如何從購物車傳遞到結帳

**問題**: spec 要求購物車折扣碼狀態存 `ref`（不持久化），但 Inertia `<Link>` 導頁至 `/checkout` 會遺失 ref；而結帳頁需顯示折扣摘要（FR-011）並於建單帶入。

**Decision**: 採「伺服器權威 + 透過導頁參數傳遞代碼」：
1. 購物車套用成功後，「前往結帳」連結帶上已套用代碼：`/checkout?coupon=ABC123`。
2. `CheckoutController::show()` 若收到 `coupon` query，呼叫 `CouponService::validateForCart()` 重新驗證並塑形折扣摘要 props（`couponCode`、`discountAmount`、`originalTotal`、`payableTotal`）。驗證失敗則忽略（不帶折扣，結帳頁顯示原價），避免半套狀態。
3. 結帳送出 `POST /api/checkout/initiate` 的 body 帶 `coupon_code`，`CheckoutService::createOrder()` 最終再驗一次（FR-012）。

**Rationale**: 折扣碼字串本身非敏感資料，放 query string 可接受，且符合「狀態不持久化、每次伺服器重驗」的安全模型——任何一關失效（過期/達上限/停用）都會被攔下。避免引入 session/DB 暫存購物車折扣的複雜度（YAGNI）。

**Alternatives considered**:
- sessionStorage 暫存 → 駁回：仍需伺服器重驗，且多一份客戶端狀態來源。
- 後端 session 存購物車折扣 → 駁回：對所有訪客增加每請求寫入成本（與 constitution III 對 guest_cart 的權衡一致，傾向無狀態）。

---

## D3. 軟刪除與代碼唯一性

**Decision**: `coupon_codes` 使用 Laravel `SoftDeletes`（`deleted_at`），`code` 欄位加 **一般 UNIQUE 約束（非 partial）**。

**Rationale**: 軟刪除後資料列仍存在於表中（僅 `deleted_at` 有值），故 `UNIQUE(code)` 天然保證「相同代碼字串永久佔用、不可重建」（FR-016）。無需 MySQL 不支援的 partial unique index，也不需在應用層額外檢查已刪除代碼。歷史訂單透過 `orders.coupon_code` 字串快照保存，與折扣碼軟刪除完全解耦（SC-007）。

**驗證查詢**: 前台/結帳驗證用 `CouponCode::active()`（scope 已隱含排除 soft-deleted，因 SoftDeletes 全域 scope 自動過濾）。後台列表預設僅顯示未刪除；不提供「還原」功能（YAGNI，spec 未要求）。

**Alternatives considered**:
- 硬刪除 + 黑名單表 → 駁回：多一張表，複雜。
- partial unique index `WHERE deleted_at IS NULL` → 駁回：允許重建相同代碼，違反 FR-016。

---

## D4. used_count 累計時機與並發

**Decision**: `used_count` 僅在付款 webhook 確認（`CheckoutService::fulfillOrder()`）時，由 `CouponService::redeem(string $code)` 以 `increment('used_count')` 原子遞增。建單時不增加。

並發超發（FR/Edge：名額為軟限制）：以原子 `increment` 避免 lost update，但**不**在兌現時強制檢查上限——付款已完成，依 spec 高峰並發允許超發、不退款。上限檢查僅在「套用/結帳前」進行（盡力而為的軟限制）。

**Rationale**: 付款前的計數不可信（用戶可能棄單），故以 webhook 為準（FR-005、FR-014、SC-003）。原子遞增防止兩筆 webhook 同時到達造成計數遺失。軟限制符合 spec 並發決策，避免分散式鎖的過度設計。

**Alternatives considered**:
- 建單即 +1，付款失敗再 -1 → 駁回：失敗/棄單回滾不可靠，計數易漂移。
- 兌現時 `SELECT ... FOR UPDATE` 強制不超發 → 駁回：spec 明確接受軟限制，不需重量級鎖。

---

## D5. 統計資料來源（orders 為準）

**Decision**: 後台統計（US4）一律以 `orders` 表為資料來源，條件 `status = 'paid'` 且 `coupon_code = {code}`：
- 完成交易筆數 = `count(*)`
- 總營收（折後實付）= `sum(total_amount)`
- 總折抵金額 = `sum(discount_amount)`
- 明細 = `buyer_email`、`webhook_received_at`（付款確認時間）、`total_amount`（折後）、`original_amount`（原價）

時間範圍以 `webhook_received_at` 落在最近 N 天（7/30/60/90）篩選；**「全部」**則不套用時間條件，統計該折扣碼自建立以來所有 `status='paid'` 的訂單（`stats()` 的 `$days` 參數為 `null` 時略過 `whereBetween`）。

**Rationale**: 一筆訂單 = 一筆交易（正確 grain）。`orders` 已記錄 original/discount/total 三金額快照，欄位齊備且不受折扣碼軟刪除影響。避免從 `purchases`（逐課程 grain）聚合造成多課程訂單重複計數。

**purchases 表角色**: `fulfillOrder()` 將 `coupon_code` 寫入每筆 purchase（稽核軌跡，供 006 交易詳情顯示）；`discount_amount` 僅記於**首筆** purchase（= 訂單總折抵額），其餘為 0，使 `sum(purchase.discount_amount) == order.discount_amount`。**不採比例分攤**——統計一律以 `orders` 為準，逐課程分攤屬非必要複雜度（constitution §X YAGNI），單課程訂單（常見情況）下首筆即等於全額。

---

## D6. 失敗節流（FR-019）實作方式

**Decision**: 於 `CouponController`（前台 apply-coupon）內使用 `Illuminate\Support\Facades\RateLimiter`，key = `coupon-apply:{request->ip()}`：
- 進入時 `RateLimiter::tooManyAttempts($key, 5)` → 回 429 + 中文訊息「嘗試次數過多，請於 60 秒後再試」。
- 驗證**失敗**時 `RateLimiter::hit($key, 60)`（衰減 60 秒）。
- 驗證**成功**時 `RateLimiter::clear($key)`（重置失敗計數，符合 FR-019）。

**Rationale**: spec 要求「失敗計數」而非「請求計數」，故不能用 route-level `throttle` middleware（它計所有請求）。`RateLimiter` 手動 hit/clear 精準符合「連續失敗 5 次、成功重置」語義。鍵以 IP 為準，對應 spec 的「同一 IP」決策。

**Alternatives considered**:
- `throttle:5,1` middleware → 駁回：成功請求也計數，無法「成功重置」。

---

## D7. fixed 最低 NT$10 與 ratio 0.50–0.95 的驗證落點

**Decision**: 後台儲存驗證放 Form Request（`StoreCouponRequest` / `UpdateCouponRequest`）：
- `type=fixed` → `value` 為整數、`min:10`。
- `type=ratio` → `value` 為小數、`between:0.50,0.95`。
採用條件式規則（`Rule::when` 或 `required_if` + 自訂訊息），中文錯誤訊息於 `messages()`。

前台套用驗證（折後 < NT$1）放 `CouponService::validateForCart()`，回傳分類錯誤訊息。

**Rationale**: 後台輸入正確性屬表單驗證職責（constitution I：Admin controller 用 Form Request）；前台套用屬商業規則（與購物車內容相關）屬 Service 職責。職責分離清晰。

---

## D8. 網址參數自動帶入折扣碼（FR-020 / US5）

**Decision**: 沿用既有 `traffic_source`（UTM）的 session 捕捉機制——**不新增 middleware、不新增儲存層**。

實作落點：
1. **捕捉**：`CourseController::show()` 內（既有已 `session()->put('traffic_source', ...)` 之處，`CourseController.php:120`）並排加入：讀 `$request->query('coupon')`，正規化（`strtoupper`、取前 6 碼、英數）後 `session()->put('checkout_coupon', $code)`。
2. **顯示為已套用（登入/訪客一致）**：`CartController::index()` 於兩個分支皆讀 `session('checkout_coupon')`，以 `prefillCouponCode`(string) prop 回傳原始代碼。**不在伺服器驗證**——因訪客購物車存於 localStorage，伺服器無 course_ids。`CouponInput.vue` 於 `onMounted` 以當前購物車 course_ids（登入取 `props.items`、訪客取 localStorage）呼叫 apply-coupon 端點完成驗證與套用，失敗靜默忽略（US5-2）。如此登入與訪客走同一條套用流程，避免伺服器/客戶端兩套驗證分歧。
3. **結帳延續**：`CheckoutController::show()` 同樣以 `session('checkout_coupon')` 作為 `?coupon=` query 的後備來源（D2 已處理 query；此處補 session fallback）。
4. **優先序**：前端 `ref` 一旦有手動套用值，覆蓋自動帶入（`prefillCouponCode`）的結果（手動 > 自動，US5-3）。移除時清除前端狀態並呼叫清除（見下），避免重整後再自動套用（US5-4）。
5. **清除時機（清除邏輯置於 Controller，遵守 constitution §II「Service 不存取 Request/session」）**：訂單成功建立後，由 **`CheckoutController::initiate()`**（非 `CheckoutService`）在 `createOrder()` 回傳成功後 `session()->forget('checkout_coupon')`，避免污染下一筆訂單。手動移除自動碼時，前端呼叫輕量 `DELETE /api/cart/coupon`（`CouponController@clear`）清 session。
   - **路由衝突防範（I1）**：清除端點用獨立路徑 `/api/cart/coupon`，避免與既有 `DELETE /api/cart/{courseId}`（cart.remove）萬用參數相撞；並建議為 cart.remove 補 `->whereNumber('courseId')`。

**Rationale**: 折扣碼與 UTM 同屬「行銷歸因參數」，生命週期一致（落地 → 結帳）。複用同一 session 機制是維護性最高、最一致的選擇，避免引入第二套狀態來源（呼應 constitution X：不新增既有未用的 pattern）。session 跨 Inertia 導頁自然保留，無需在每個 `<Link>` 串接 query。

**Alternatives considered**:
- localStorage 暫存 → 駁回：折扣碼需伺服器權威驗證，且 session 已是既有歸因載體，重複造輪。
- 每頁 query string 串接 → 駁回：易遺漏、易被使用者改動，維護成本高。
- 全域 middleware 捕捉所有頁面的 `?coupon=` → 駁回：spec 限定僅銷售頁攜帶（Assumptions），且 `CourseController` 已有捕捉點，YAGNI。

---

## 摘要：無 NEEDS CLARIFICATION 殘留

spec 的所有 [NEEDS CLARIFICATION] 已於 `/speckit.clarify`（Session 2026-06-09）解決。本研究階段未發現新的阻斷性未知項。可進入 Phase 1。
