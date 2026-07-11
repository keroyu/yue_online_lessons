---
id: 006-coupons
status: done
owner_files:
  - app/Http/Controllers/CouponController.php
  - app/Http/Controllers/Admin/CouponController.php
  - app/Http/Controllers/Admin/CouponChainController.php
  - app/Http/Requests/Admin/StoreCouponRequest.php
  - app/Http/Requests/Admin/UpdateCouponRequest.php
  - app/Http/Requests/Admin/StoreCouponChainRequest.php
  - app/Http/Requests/Admin/UpdateCouponChainRequest.php
  - app/Models/CouponCode.php
  - app/Models/CouponChain.php
  - app/Services/CouponService.php
  - app/Services/CouponChainService.php
  - database/migrations/2026_06_09_000001_create_coupon_codes_table.php
  - database/migrations/2026_06_09_000002_add_discount_columns_to_orders_table.php
  - database/migrations/2026_06_26_000001_create_coupon_chains_table.php
  - database/migrations/2026_06_26_000002_add_chain_id_to_coupon_codes_table.php
  - resources/js/Components/Cart/CouponInput.vue
  - resources/js/Components/Admin/CouponForm.vue
  - resources/js/Pages/Admin/Coupons/Index.vue
  - resources/js/Pages/Admin/Coupons/Create.vue
  - resources/js/Pages/Admin/Coupons/Edit.vue
  - resources/js/Pages/Admin/Coupons/Show.vue
  - resources/js/Pages/Admin/CouponChains/Index.vue
  - resources/js/Pages/Admin/CouponChains/Create.vue
  - resources/js/Pages/Admin/CouponChains/Edit.vue
  - resources/js/Pages/Admin/CouponChains/Show.vue
touchpoints:
  - file: app/Services/CheckoutService.php
    owner: 005-checkout
    why: createOrder() 結帳前重驗折扣碼並寫入訂單快照欄；fulfillOrder() 付款確認後呼叫 CouponService::redeem()
  - file: app/Http/Controllers/CheckoutController.php
    owner: 005-checkout
    why: show() 以 ?coupon= query 優先、session('checkout_coupon') 為備援傳 couponCode prop；initiate() 建單後 forget session
  - file: app/Http/Controllers/CartController.php
    owner: 005-checkout
    why: index()（登入/訪客兩分支）傳 prefillCouponCode = session('checkout_coupon') 給購物車頁
  - file: resources/js/Pages/Cart/Index.vue
    owner: 005-checkout
    why: 掛載 CouponInput 元件（套用狀態存頁面層）
  - file: resources/js/Pages/Checkout/Index.vue
    owner: 005-checkout
    why: 掛載 CouponInput 元件；送單僅帶 UI 上「已套用」的代碼
  - file: app/Models/Order.php
    owner: 005-checkout
    why: coupon_code / original_amount / discount_amount 快照欄位（fillable + casts）
  - file: app/Http/Controllers/CourseController.php
    owner: 002-storefront
    why: 銷售頁捕捉 ?coupon=CODE（消毒後）存入 session('checkout_coupon')
  - file: app/Http/Controllers/Member/ClassroomController.php
    owner: TBD-classroom
    why: formatLessonFull() 以 CouponChainService::substitutePlaceholders() 展開 promo_html 的 {alias}
  - file: app/Http/Controllers/Admin/ChapterController.php
    owner: TBD-course-admin
    why: 傳啟用中 couponChains 給小節編輯表單（插入佔位符下拉）
  - file: resources/js/Components/Admin/LessonForm.vue
    owner: TBD-course-admin
    why: 促銷內容區的輪換折扣碼下拉 + 「插入折扣碼」按鈕（游標處插入 {alias}）
---

# Coupons（折扣碼）

## 目標

讓管理員建立與管理折扣碼（固定折抵 / 折數）並追蹤成效；讓前台用戶（登入或訪客）在購物車與結帳頁套用折扣、或經分享連結自動帶入。另提供「輪換折扣碼」（CouponChain）：每支代碼達名額後自動補下一支，促銷內容以 `{alias}` 佔位符永遠顯示當前有效代碼。

## User Stories

### User Story 1 - 購物車與結帳頁套用折扣碼 (Priority: P1)

用戶（登入或訪客）在購物車頁或結帳頁輸入折扣碼即時套用，看到折扣標籤、折抵金額與更新後合計，可隨時移除恢復原價。「直接購買」略過購物車者在結帳頁同樣可用。

**驗收**：
- [x] 輸入有效碼套用後顯示標籤（fixed「折抵 NT$XXX」/ ratio「X折優惠」）、折扣行與折後合計；輸入區變為「已套用 + 移除」
- [x] 錯誤分類回應：不存在/停用→「折扣碼無效」；過期→「折扣碼已過期」；達上限→「折扣碼已達使用上限」；課程不符→「此折扣碼不適用於購物車中的課程」；折後 < NT$1→「折扣金額超過訂單上限，無法套用」
- [x] 移除後恢復原價、可重新輸入；結帳送出只帶 UI 上實際「已套用」的代碼
- [x] 大小寫不敏感（伺服器統一 trim + 轉大寫比對）
- [x] 同一 IP 連續驗證失敗 5 次 → 60 秒節流（429「嘗試次數過多」）；成功即重置計數
- [x] 小計由伺服器依 course_ids 重算（display_price），不信任前端金額

### User Story 2 - 後台折扣碼管理 (Priority: P1)

管理員在後台新增/編輯/啟停/刪除折扣碼，列表一覽代碼、類型、適用範圍、到期日、剩餘名額、已用次數與啟用狀態。

**驗收**：
- [x] 建立欄位：代碼（1–6 位英數、全域唯一、含軟刪列不可重複）、類型 fixed（值 ≥ NT$10）/ ratio（0.50–0.95）、適用課程（null = 全站）、到期日（可空，建立時須晚於現在）、名額 max_uses（可空 = 無限）、備註
- [x] 編輯不可修改 code（永久唯一）；其餘欄位可改，僅影響之後的新訂單
- [x] toggle 啟用/停用立即生效（前台驗證即時反映）
- [x] 刪除為軟刪除：列表消失、代碼字串永久佔用不可重建、歷史訂單與統計不受影響
- [x] 列表顯示 type_label / scope_label / expires_label（「永不過期」）/ remaining_label（「無限制」或剩餘數）
- [x] 列表頁提供「輪換折扣碼」tab 連往 /admin/coupon-chains

### User Story 3 - 結帳折扣生效與付款後計數 (Priority: P2)

套用折扣碼的訂單以折後金額建立並保存金額快照；付款確認（webhook）後才將 used_count +1，未付款、失敗或取消一律不計。

**驗收**：
- [x] createOrder() 送單前重新 server-side 驗證；失效則 throw RuntimeException 阻擋付款並回報原因
- [x] 訂單快照：coupon_code、original_amount（折前小計）、discount_amount；total_amount = original − discount
- [x] fulfillOrder() 為 used_count 唯一寫入點（原子 increment），以訂單 status='paid' 冪等保護，重複金流通知不重複計數
- [x] redeem 為軟限制：不再檢查上限，高峰併發超發不回滾已完成訂單；查無代碼記 warning 後略過
- [x] purchases 每筆寫入 coupon_code；discount_amount 僅記於首筆（sum = 訂單折抵額，統計以 orders 為準）

### User Story 4 - 折扣碼成效統計 (Priority: P2)

管理員進入單一折扣碼統計頁，切換時間範圍查看完成交易筆數、總營收（折後實付）、總折抵金額與交易明細。

**驗收**：
- [x] 時間範圍 7 / 30 / 60 / 90 天 / 全部，預設 30；「全部」不套用時間條件
- [x] 僅計 orders.status='paid'；時間依 webhook_received_at（付款確認時間）篩選與排序
- [x] 明細每筆：購買者 Email、付款確認時間、折後結帳金額、原始金額
- [x] 期間無交易時數字為 0、明細為空

### User Story 5 - 輪換折扣碼 CouponChain (Priority: P2)

管理員建立輪換折扣碼模板（alias、折扣、每碼名額），系統自動生成首支代碼並在每支達名額後自動補下一支；課程小節促銷內容以 `{alias}` 佔位符引用，學員永遠看到當前有效代碼。

**驗收**：
- [x] 後台以「折扣碼管理」第二個 tab 呈現 CRUD；列表含佔位符、折扣、每碼名額、當前有效代碼、歷史代碼數、啟用狀態
- [x] alias 限英數與底線（max 50）、現存列唯一、儲存前統一轉小寫
- [x] 建立時立即自動生成首支 CouponCode（chain_id 關聯；type/value/course_id 繼承；max_uses = code_max_uses，0 則為 null 無限）
- [x] redeem 後若 used_count ≥ code_max_uses（且 > 0）自動生成下一支；code_max_uses = 0 不補碼
- [x] 自動生成碼為 6 位大寫英數，迴圈檢查唯一（含軟刪列）
- [x] 教室 promo_html 的 {alias} 由伺服器展開為 currentCode()（啟用、未達上限、最新建立）；unknown alias、chain 停用或無可用碼時「保留佔位符原樣」（不展開為空字串）
- [x] 小節編輯 modal 下拉列出啟用中 chain（標籤含課程名/全站通用），點「插入折扣碼」於游標處插入 {alias}；未選擇時按鈕 disabled
- [x] Show 頁列出鏈上所有歷史代碼（is_current 標記）；刪除 chain 為硬刪除，codes 的 chain_id 經 nullOnDelete 設 null，歷史代碼保留

### User Story 6 - 分享連結自動帶入折扣碼 (Priority: P3)

行銷分享連結 `/courses/xxx?coupon=CODE`，用戶進站後折扣碼經 session 跨頁保留，購物車/結帳頁自動套用；無效則靜默忽略。

**驗收**：
- [x] 銷售頁捕捉 ?coupon=（strip 非英數、轉大寫、截 6 碼）存 session('checkout_coupon')
- [x] 購物車頁以 prefillCouponCode prop 帶入；CouponInput 以 watch(immediate) 等 courseIds 就緒後 silent 自動套用（訪客 localStorage 購物車延遲載入也涵蓋）
- [x] 結帳頁 ?coupon= query 優先、session 備援；同樣走前端驗證套用
- [x] 無效碼靜默忽略不顯示錯誤；手動輸入優先於自動帶入
- [x] 用戶移除自動帶入碼時呼叫 DELETE /cart/coupon 清 session，重整不再自動重套；訂單建立成功後亦清除

## Requirements

- **FR-001**: 代碼 1–6 位英數、全域唯一（含軟刪除列）、統一以大寫儲存與比對；每筆訂單僅能套用一個折扣碼，不支援疊加。
- **FR-002**: 兩種類型 — fixed：折抵金額 ≥ NT$10，折抵 = min(value, subtotal)；ratio：0.50–0.95（實付比例），先算折後實付四捨五入至整數元再回推折抵。
- **FR-003**: 折扣以整筆訂單小計計算一次（不逐課程分攤）；折後實付下限 NT$1，低於則拒絕套用。
- **FR-004**: 適用範圍限「全站通用」（course_id = null）或「指定單一課程」；限定課程須存在於購物車 course_ids 中。
- **FR-005**: used_count 唯一寫入點為付款確認後的 fulfillOrder()；「套用」按鈕（純驗證）與建立 pending 訂單皆不計數。名額為軟限制，併發超發不回滾。
- **FR-006**: 刪除採軟刪除；UNIQUE(code) 為一般約束（非 partial），軟刪列仍佔用代碼字串，等同「代碼永久保留」。
- **FR-007**: 驗證端點公開（支援訪客），以 RateLimiter（key = coupon-apply:{ip}）節流：5 次失敗 / 60 秒冷卻 / 成功清零。
- **FR-008**: 訂單的 coupon_code 為字串快照、無 FK；折扣碼事後編輯/刪除不回溯影響歷史訂單與統計。
- **FR-009**: session key `checkout_coupon` 與 traffic_source 同機制跨頁保留；僅課程銷售頁捕捉 ?coupon=；訂單建立後或用戶移除時清除。
- **FR-010**: CouponChain 的 {alias} 展開為伺服器端操作（教室 API 回傳前）；後台編輯者看到的 promo_html 永遠是原始佔位符。
- **FR-011**: 補碼時機在 redeem() 內（付款確認後），新舊碼交替瞬間的競態不加原子鎖：用戶撞到已滿舊碼時收到「已達使用上限」提示即可。
- **FR-012**: chain alias 與 coupon code 為兩個獨立命名空間；alias 唯一性僅限 coupon_chains 現存列（chain 為硬刪除，刪後可重建同名）。

## 設計決策

- **D1**: 軟刪除 + 一般 UNIQUE(code) 實現「代碼永久佔用」 — 免去 partial index，也保證歷史訂單快照可追溯。
- **D2**: used_count 只在 fulfillOrder() 累計，靠訂單 paid 狀態冪等 — 拒絕「套用即計數」方案，避免棄單灌水；接受併發下軟性超發。
- **D3**: {alias} 無可用碼時保留佔位符原樣（非展開為空字串）— 讓設定錯誤（打錯 alias、chain 停用）在頁面上可見可查，靜默清空反而難 debug。
- **D4**: CouponChain 硬刪除 + codes.chain_id nullOnDelete — 歷史代碼（與其統計）自動脫鏈保留；chain 本身無訂單快照需求，不必軟刪。
- **D5**: `2026_06_09_000002_add_discount_columns_to_orders_table` 歸本模組 — 三欄皆為折扣語意、由本功能建立；orders 表本體仍歸 005-checkout。
- **D6**: 節流用 Laravel RateLimiter（cache-based）而非 DB 記錄 — 短代碼防枚舉只需暫時性計數，無稽核需求。
- **D7**: apply 端點由伺服器依 course_ids 重算小計 — 不信任前端傳入金額，防改價。
- **D8**: 錯誤分類查詢不套 active() scope — 先撈 code 再逐項判斷，才能區分「無效 / 過期 / 達上限」給出精準訊息。

## Schema

- `coupon_codes` — 折扣碼本體；`UNIQUE(code)`（6 位大寫英數、軟刪列仍佔用）；`used_count` 只在付款確認後 increment；`course_id` null = 全站；`max_uses` null = 無限；`chain_id` null = 一般碼、非 null = chain 自動生成（nullOnDelete）；SoftDeletes。
- `coupon_chains` — 輪換模板；`UNIQUE(alias)`（小寫英數底線）；`code_max_uses` 0 = 無限且不自動補碼；無軟刪除。
- `orders`（005-checkout 擁有表、本模組擁有此 alter migration）— 新增 `coupon_code`（字串快照無 FK）、`original_amount`、`discount_amount`；不變量：套用折扣時 `total_amount = original_amount - discount_amount`；未套用時 coupon_code/original_amount 為 null、discount_amount 為 0。
- `purchases`（沿用既有欄位，無 migration）— 每筆寫 `coupon_code`；`discount_amount` 僅首筆記總折抵額，其餘 0。

## 進度日誌

- 2026-07-11: 折扣碼列表代碼旁新增快速複製按鈕（clipboard，複製後綠勾回饋）。
- 2026-07-06: 領域重組 — 自 011-discount-coupon 重寫，依實際 codebase 校正（{alias} 無碼時保留原樣非空字串、chain 為硬刪除、alias 轉小寫儲存、結帳頁 ?coupon= query 優先於 session）
- 2026-06-26: 新增 CouponChain 輪換折扣碼：後台 tab CRUD、自動補碼、{alias} 佔位符展開、小節編輯插入 UI
- 2026-06-10: 011 實作完成；折扣碼輸入欄同置購物車與結帳頁；修正計數完整性（僅 UI 已套用且付款成功才 +1）
- 2026-06-09: 011-discount-coupon 起案（fixed/ratio、軟刪除、IP 節流、統計 7/30/60/90、?coupon= 自動帶入）
