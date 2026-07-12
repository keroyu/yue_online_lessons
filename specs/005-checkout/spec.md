---
id: 005-checkout
status: done
owner_files:
  - app/Http/Controllers/CartController.php
  - app/Http/Controllers/CheckoutController.php
  - app/Http/Controllers/Payment/NewebpayController.php
  - app/Http/Controllers/Payment/PayuniController.php
  - app/Http/Controllers/Payment/SuccessController.php
  - app/Http/Controllers/Purchase/FreePurchaseController.php
  - app/Http/Controllers/Webhook/PortalyController.php
  - app/Http/Requests/AddToCartRequest.php
  - app/Http/Requests/CheckoutRequest.php
  - app/Models/CartItem.php
  - app/Models/Order.php
  - app/Models/OrderItem.php
  - app/Models/Purchase.php
  - app/Policies/PurchasePolicy.php
  - app/Services/CartService.php
  - app/Services/CheckoutService.php
  - app/Services/NewebpayService.php
  - app/Services/PayuniService.php
  - app/Services/PortalyWebhookService.php
  - resources/js/composables/useCart.js
  - resources/js/Pages/Cart/Index.vue
  - resources/js/Pages/Checkout/Index.vue
  - resources/js/Pages/Payment/Success.vue
  - resources/js/Pages/Admin/Settings/Payment.vue
  - database/migrations/2026_01_16_000002_create_purchases_table.php
  - database/migrations/2026_01_17_141405_add_webhook_fields_to_purchases_table.php
  - database/migrations/2026_01_17_184859_add_source_to_purchases_table.php
  - database/migrations/2026_01_27_081409_add_type_to_purchases_table.php
  - database/migrations/2026_03_07_174930_make_buyer_email_nullable_in_purchases_table.php
  - database/migrations/2026_03_23_060722_add_payuni_trade_no_to_purchases_table.php
  - database/migrations/2026_05_06_000001_create_cart_items_table.php
  - database/migrations/2026_05_06_000002_create_orders_table.php
  - database/migrations/2026_05_06_000003_create_order_items_table.php
  - database/migrations/2026_05_06_000004_add_payment_gateway_to_courses_table.php
  - database/migrations/2026_05_06_000005_add_order_id_to_purchases_table.php
  - database/migrations/2026_05_07_000001_add_tax_id_to_orders_table.php
  - database/migrations/2026_05_08_000001_add_utm_to_orders_table.php
touchpoints:
  - file: app/Models/SiteSetting.php
    owner: 000-platform-core
    why: 金流憑證（payuni_* / newebpay_*）、portaly_webhook_key、meta_pixel_id 儲存於 site_settings；SiteSetting::get 優先於 .env fallback
  - file: app/Http/Controllers/Admin/AdminSettingsController.php
    owner: 000-platform-core
    why: showPayment / updatePayment 端點承載本模組的金流設定頁（Pages/Admin/Settings/Payment.vue）
  - file: app/Services/CouponService.php
    owner: 006-coupons
    why: createOrder 呼叫 validateForCart 計算折扣；fulfillOrder 付款確認時呼叫 redeem 消耗折扣碼
  - file: app/Services/ReferralService.php
    owner: 007-points-referral
    why: initiate 時 validateAtCheckout 產生推薦快照；fulfillOrder 呼叫 reward / evaluateActivation
  - file: app/Services/PointService.php
    owner: 007-points-referral
    why: 推薦獎勵點數經 ReferralService 間接發放；本模組不直接操作點數
  - file: app/Services/TransactionService.php
    owner: 009-transactions-admin
    why: 後台退款操作更新 purchases.status = refunded；本模組只定義 Purchase 資料語意
  - file: app/Services/DripService.php
    owner: 010-drip-email
    why: 付款履行、免費領取、Portaly 開通後呼叫 subscribe / checkAndConvert 觸發 drip 流程
---

# Checkout（購物車與結帳金流）

## 目標

讓訪客與會員能把付費課程加入購物車、免登入完成結帳付款（PayUni / 藍新），
並涵蓋 Portaly 外部金流 webhook 開通與免費課程直接領取。
付款確認（webhook）後以訂單快照建立 Purchase 記錄，為全站「擁有課程」的唯一事實來源。

## User Stories

### User Story 1 - 加入購物車與直接購買 (Priority: P1)

任何用戶（含未登入）可在平台金流課程銷售頁「加入購物車」或「直接購買」；
guest cart 存 localStorage，登入後自動合併至 server-side 購物車。

**驗收**：
- [x] 已登入加入購物車 → 寫入 cart_items（UNIQUE user+course，重複回 409「課程已在購物車中」），navbar badge 即時 +1
- [x] 未登入加入購物車 → 存 localStorage `guest_cart`，badge 由 client 計算；重整頁面按鈕狀態從 localStorage 還原
- [x] 登入後 `POST /api/cart/merge` 合併 guest cart；已在購物車或已購買（paid）的課程略過不報錯
- [x] AddToCartRequest 擋下：Portaly 課程、免費課程（price<=0）、非 selling / 未發布課程、已購買課程
- [x] 加入成功才觸發 Meta Pixel `AddToCart`（value / currency TWD / content_ids）；失敗或已在購物車（409 / guest 重複）不觸發；`window.fbq` 不存在時靜默略過
- [x] 已購買用戶在銷售頁看到「進入課程」，不顯示加購按鈕；已在購物車則顯示「前往購物車」+「直接購買」並排

### User Story 2 - 購物車頁與結帳頁 (Priority: P1)

購物車頁（`/cart`）與結帳頁（`/checkout`）全程不需登入；結帳頁填寫購買者資料
（姓名 / Email / 電話 / 選填統編）並勾選同意條款後才能送出付款。

**驗收**：
- [x] `/cart` 顯示課程名稱、封面、價格、總金額；移除課程即時更新；空車顯示提示與課程列表連結；進頁觸發 `InitiateCheckout`
- [x] guest 進 `/cart`、`/checkout` 不強制登入；guest 的 items 由前端從 localStorage 補齊（server 回空陣列）
- [x] 已登入用戶結帳頁 prefill 姓名 / Email / 電話（real_name / email / phone）
- [x] 統編選填，填寫時必為 8 位數字（前後端 `regex:/^\d{8}$/` 雙重驗證），寫入 orders.tax_id
- [x] Email blur 時呼叫 `POST /api/checkout/check-email`，同時比對 purchases.buyer_email 與 users.email→purchases.user_id 雙路徑；已購買任一車內課程則紅字提示並 disable 送出
- [x] 資料不完整或未勾選同意時「前往付款」保持 disabled
- [x] 付款失敗返回 `/cart` 時讀取 flash `payment_failed` 顯示紅色橫幅（含客服 Email themustbig+learn@gmail.com）

### User Story 3 - 建立訂單與金流付款 (Priority: P1)

送出結帳（`POST /api/checkout/initiate`）建立 Order + OrderItems 快照，
依路由規則選擇 PayUni UPP 或藍新 MPG，回傳表單欄位由前端 POST 至金流頁。

**驗收**：
- [x] CheckoutRequest 逐課驗證：非 Portaly、price>0、selling 且已發布；違反回 422
- [x] createOrder 再次檢查重複購買（buyer_email + 既有帳號 user_id 雙查），命中丟 RuntimeException → controller 回 409
- [x] 金流路由：單一課程且該課 payment_gateway=newebpay → 藍新；其餘（單課 payuni 或多課）→ 一律 PayUni
- [x] Order 以 transaction 兩段式建立：先 INSERT 再 UPDATE `merchant_order_no = ord_{id}_{ymd}`；OrderItems 快照 course_name + unit_price（display_price）
- [x] guest 下單時 orders.user_id 為 NULL，帳號延後到 webhook 履行時 find-or-create
- [x] 訂單記錄 session `traffic_source` 的 9 個歸因欄位（utm_* / referrer_domain / gclid / fbclid / ttclid）
- [x] 折扣碼與推薦碼在 initiate 時驗證並快照進 orders（邏輯屬 006 / 007 模組，此處僅接點）
- [x] PayUni：AES-256-GCM 加密 + SHA256 HashInfo，送 UPP endpoint（sandbox 由 config 切換）
- [x] 藍新：AES-256-CBC TradeInfo + TradeSha、Version 2.3、RespondType=JSON；endpoint 依 site_settings `newebpay_env` 切換 ccore（測試）/ core（正式）

### User Story 4 - Webhook 履行訂單 (Priority: P1)

金流付款成功後由 NotifyURL（server-to-server，api.php）觸發 `CheckoutService::fulfillOrder()`；
ReturnURL（瀏覽器跳轉，web.php、CSRF 排除）作為 Notify 失敗時的 fallback 安全網。

**驗收**：
- [x] PayUni Notify（`POST /api/webhooks/payuni`）驗 HashInfo 後解密處理，恆回 `1|OK` 防重試迴圈
- [x] 藍新 Notify（`POST /api/webhooks/newebpay`）驗 TradeSha 後解密，恆回純字串 `SUCCESS`（其他任何回應會觸發藍新重試）
- [x] 藍新 RespondType=JSON：解密後 `json_decode` 為主、`parse_str` fallback；業務欄位取 `Result` 子物件，Status 在頂層
- [x] fulfillOrder 雙層冪等：Layer 1 檢查 `order.status === 'paid'` 直接 return；Layer 2 purchases UNIQUE(user_id, course_id) 擋 race condition
- [x] 履行時 find-or-create user（Email 已存在則靜默綁定並更新姓名電話）、orders.user_id 回填、建立各課程 Purchase（source=payuni/newebpay、order_id 關聯）
- [x] PayUni 與藍新的 ReturnURL 在付款成功且訂單仍 pending 時，皆呼叫 fulfillOrder 作為 fallback，之後 redirect `/payment/success?order={merchant_order_no}`
- [x] 付款失敗 / 驗簽失敗 → redirect `/cart` 帶 flash `payment_failed`
- [x] 履行時觸發接點：coupon redeem（006）、referral reward + activation（007）、drip subscribe / checkAndConvert（010）
- [x] 訂單層折扣只記在第一筆 purchase row，使 sum(purchases.discount_amount) == orders.discount_amount

### User Story 5 - 付款成功頁 (Priority: P2)

`/payment/success?order=xxx` 顯示訂單摘要並觸發 Meta Pixel `Purchase`；
webhook 尚未到達時顯示等待 overlay 輪詢訂單狀態。

**驗收**：
- [x] 訂單 paid：顯示摘要（訂單編號、姓名、Email、電話、統編、金額、課程清單）；已登入顯示「前往我的課程」、guest 顯示「登入查看課程」
- [x] 訂單 pending：回傳 `waiting: true`，前端全畫面 overlay 每 2 秒 poll `GET /api/checkout/order-status`，上限 30 次（60 秒）
- [x] 輪詢超時切換 amber 警告 UI（重新確認 / 前往課程 / 回首頁 + 客服 Email），不無限轉圈
- [x] 已登入用戶到達成功頁時，該訂單課程自動從 server cart 移除（clearPurchased）
- [x] order query 缺失或查無訂單 → 404
- [x] 登入頁接受 `?hint=purchase`（與 `?hint=payuni`）顯示「請用購買時填寫的 Email 登入」提示

### User Story 6 - Portaly 課程購買 (Priority: P1)

有 `portaly_product_id` 的課程不走購物車，銷售頁「立即購買」外連 Portaly 結帳；
付款後 Portaly webhook（`POST /api/webhooks/portaly`）開通課程，退款事件同步標記。

**驗收**：
- [x] HMAC-SHA256 驗證 `X-Portaly-Signature`；金鑰讀 site_settings `portaly_webhook_key`，fallback config；驗簽失敗回 401
- [x] 簽章計算用 raw body 的 `data` 重新 json_encode（繞過 ConvertEmptyStringsToNull 破壞簽章的問題）
- [x] paid 事件：先以 productId 對應課程，無關產品靜默忽略且**不建立用戶**；find-or-create user 後建 Purchase（source=portaly）
- [x] 冪等：相同 portaly_order_id 已存在則跳過；處理中例外記 log 仍回 200 防 Portaly 重試
- [x] refund 事件：對應 purchase 標記 refunded；查無訂單記 warning 仍回 200
- [x] drip 課程開通後自動 subscribe + checkAndConvert
- [x] Portaly 課程在前台不顯示「加入購物車」（入口層排除，AddToCartRequest / CheckoutRequest 雙重防守）

### User Story 7 - 免費課程直接領取 (Priority: P2)

price = 0 且無 portaly_product_id 的課程，銷售頁展開 inline 表單（Email / 姓名 / 電話），
`POST /api/purchase/free/{course}` 免金流直接建立購買記錄。

**驗收**：
- [x] guard：非免費（有 portaly_product_id 或 price>0）回 422；draft / 未發布回 422
- [x] 已登入用戶強制使用帳號 Email 報名（忽略表單 email），姓名電話則以表單值更新
- [x] find-or-create user 後建 Purchase（amount=0、source=free、type=paid）；已領取回 `already_enrolled: true` 冪等
- [x] drip 課程自動 subscribe + checkAndConvert

## Requirements

- **FR-001**: 課程購買入口分流以 `portaly_product_id` 為第一層判斷：有值 → Portaly 直購；無值且 price>0 → 購物車金流；無值且 price=0 → 免費領取；type=high_ticket → 預約流程（008 模組）。
- **FR-002**: 整個購買流程（加車、結帳、付款）全程不需登入；帳號在 webhook 履行時才 find-or-create，guest 訂單期間 orders.user_id 為 NULL。
- **FR-003**: server cart 以 session 登入用戶 ID 為唯一識別，不接受 client 傳入 user_id；guest cart 只存 course_id 陣列於 localStorage。
- **FR-004**: 購物車不存價格快照，結帳永遠以課程當下 `display_price` 計價（不承諾保留特價）。
- **FR-005**: 多課程結帳合併為單一 Order 一次金流請求；金流商快照在 `orders.payment_gateway`，`routeGateway()` 只讀此欄位。
- **FR-006**: `merchant_order_no` 格式 `ord_{Order.id}_{ymd}`（依賴自增 id，故 transaction 內兩段式 INSERT→UPDATE）；PayUni MerTradeNo 與藍新 MerchantOrderNo 共用此值，webhook 以此欄位反查訂單。
- **FR-007**: 重複購買防線三層：結帳頁 email blur 預查（UX）、createOrder 內 email+user 雙查（丟 409）、purchases UNIQUE(user_id, course_id)（最終防線）。
- **FR-008**: fulfillOrder 必須冪等；Notify 與 Return 都可能觸發履行，重複呼叫不得產生重複 Purchase。
- **FR-009**: 金流 Notify 端點不論處理結果恆回固定成功字串（PayUni `1|OK`、藍新 `SUCCESS`），錯誤只記 log，防止金流端重試迴圈。
- **FR-010**: 金流憑證（MerchantID / HashKey / HashIV）、portaly_webhook_key、meta_pixel_id 儲存於 site_settings，資料庫值優先於 .env；後台表單空白提交＝保留既有值；API 回應不得回傳 HashKey/IV 明文（只回前 5 碼 + 星號 preview）。
- **FR-011**: Meta Pixel 事件（AddToCart / InitiateCheckout / Purchase）僅在 meta_pixel_id 已設定且 `window.fbq` 存在時觸發；未設定時全站不輸出 Pixel script。
- **FR-012**: Cart / Checkout API 路由必須放 web.php（api middleware group 無 StartSession，而 initiate 需讀 session 的 traffic_source 與 checkout_coupon）；金流 Notify 放 api.php；兩個 ReturnURL 放 web.php 並 withoutMiddleware CSRF。
- **FR-013**: Purchase 授權：本人或 admin 可 view；create / update / delete 僅 admin（PurchasePolicy）。

## 設計決策

- **D1**: 訂單快照（Order + OrderItems）先落庫再送金流 — webhook 回傳時只憑 merchant_order_no 反查快照建 Purchase，不信任金流回傳的商品資訊。替代方案（webhook 帶課程參數）遭否決：不可竄改性差。
- **D2**: guest 帳號延後至付款確認才建立 — 避免未付款訂單汙染 users 表；Email 撞既有帳號時靜默綁定，不在結帳中途打斷。
- **D3**: 雙層冪等（status 檢查 + DB UNIQUE）而非鎖 — Notify / Return 幾乎同時到達的 race 由 UNIQUE constraint 兜底，QueryException 捕捉後跳過。
- **D4**: ReturnURL 也做 fallback fulfill — 生產環境曾發生 Notify 未達導致付款後無課程；fulfillOrder 冪等性讓安全網零成本。（初版設計「Return 只 redirect」已被推翻，以 code 為準。）
- **D5**: 藍新解密以 json_decode 為主、parse_str fallback — RespondType=JSON 時 TradeInfo 是 JSON 字串，parse_str 會靜默失敗導致 Status 恆 null（歷史 bug）。
- **D6**: 訂單層折扣只記在第一筆 purchase — 保持 sum 一致性，統計以 orders 為準，purchases 不做按比例攤提。
- **D7**: 多課程一律走 PayUni — 避免混合金流拆單複雜度；藍新只服務單課結帳。
- **D8**: 金流憑證進 site_settings 而非 .env — 管理員可自行維護、即時生效免重啟；.env 僅作初始 fallback。

## Schema

- `cart_items` — server-side 購物車項目；UNIQUE(user_id, course_id)；無 updated_at（`$timestamps=false`，booted 補 created_at）；不存價格。
- `orders` — 結帳快照 + 付款狀態機 `pending → paid｜failed`（paid 為終態，退款在 purchases 層處理）；user_id NULL＝guest 訂單；merchant_order_no UNIQUE、建立初期短暫為 NULL；含 tax_id、9 個流量歸因欄位；折扣欄（006）與推薦欄（007）由各自模組 alter。
- `order_items` — 訂單明細快照（course_name、unit_price 於下單當下凍結）；course FK ON DELETE RESTRICT 防止刪課毀帳；無 updated_at。
- `purchases` — 「用戶擁有課程」唯一事實來源；UNIQUE(user_id, course_id)；status paid｜refunded；source 標記來源管道（portaly / payuni / newebpay / free / points…）；order_id nullable FK（SET NULL）串回訂單；portaly_order_id / payuni_trade_no 供各管道冪等查重。

## 進度日誌

- 2026-07-12: AddToCart Pixel 事件時機修正（useCart.js）— 原本進函式就送（失敗/重複也計數），改為 API 成功或 guest cart 實際新增後才送。
- 2026-07-11: 金流設定頁（Payment.vue）標題改名「API 設定」（含 PayUni/NewebPay/Portaly/Meta Pixel 憑證，頁面已不只金流）。憑證讀取為 SiteSetting 優先、config/.env fallback（PayuniService/NewebpayService/PortalyWebhookService 建構子）。
- 2026-07-06: 領域重組 — 合併 009+001(購買流程) 重寫，依實際 codebase 校正
