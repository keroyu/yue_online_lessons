---
id: 009-transactions-admin
status: done
owner_files:
  - app/Http/Controllers/Admin/TransactionController.php
  - app/Http/Controllers/Admin/DashboardController.php
  - app/Http/Requests/Admin/StoreTransactionRequest.php
  - app/Services/TransactionService.php
  - resources/js/Components/Admin/TransactionRefundModal.vue
  - resources/js/Components/Admin/RevenueChart.vue
  - resources/js/Pages/Admin/Transactions/Index.vue
  - resources/js/Pages/Admin/Transactions/Show.vue
  - resources/js/Pages/Admin/Dashboard.vue
touchpoints:
  - file: app/Models/Purchase.php
    owner: 005-checkout
    why: 列表/詳情/匯出讀取 Purchase；退款更新 status；手動新增建立紀錄（source=manual, amount=0）
  - file: app/Models/Order.php
    owner: 005-checkout
    why: 讀取 merchant_order_no / gateway_trade_no / payment_gateway / tax_id / referrer_user_id（badge 判斷、詳情、CSV、退款期限）
  - file: app/Services/PointService.php
    owner: 007-points-referral
    why: 退款時呼叫 voidReferral(order) 作廢未成熟推薦回饋（訂單層級冪等；activation flag 不回復）
  - file: app/Models/SiteSetting.php
    owner: 000-platform-core
    why: 退款期限檢查讀取 referral_maturity_days 設定（預設 14）
  - file: app/Models/User.php
    owner: 001-auth-account
    why: 手動新增交易查詢會員（findOrFail）；Dashboard 以 members() scope 統計會員數
  - file: app/Models/Course.php
    owner: 004-course-admin
    why: 課程篩選下拉與手動新增查詢課程；Dashboard 統計課程總數/已發佈/草稿與最近新增課程
  - file: app/Http/Controllers/Admin/MemberController.php
    owner: 008-members-admin
    why: 手動新增交易的會員選擇透過 GET /admin/members（X-Inertia: false，JSON 回應）搜尋 Email
---

# Transactions Admin（後台交易管理與營運儀表板）

## 目標

讓管理員在後台掌握全站交易：檢視/搜尋/篩選所有購買紀錄、查看單筆詳情、
手動補單（指派/贈送）、標記退款、勾選匯出 CSV 對帳，並以營收圖表與
Dashboard 統計卡片快速掌握營運狀況。

## User Stories

### User Story 1 - 交易列表檢視與搜尋篩選 (Priority: P1)

管理員在 `/admin/transactions` 看到所有交易的分頁列表，可即時搜尋與多條件篩選，
並直接在列表掌握金流來源、訂單編號與會員學習進度。

**驗收**：
- [x] 依 created_at 倒序分頁（預設每頁 20，`per_page` 參數上限 100），顯示購買者、課程、金額（格式 `幣別 金額.00`）、狀態、類型、購買時間
- [x] 搜尋（300ms debounce）同時比對 buyer_email、portaly_order_id、關聯 Order 的 merchant_order_no（orWhereHas）
- [x] 篩選：狀態（paid/refunded）、類型（paid/system_assigned/gift）、課程下拉
- [x] 訂單欄位以 data-driven badge 標示金流來源：`[Portaly]` / `[PayUni]` / `[NewebPay]`；手動指派無編號顯示「—」（判斷順序見 FR-003）
- [x] 點擊 badge 將完整訂單編號複製到剪貼簿，顯示「已複製」1.5 秒；瀏覽器不支援 Clipboard API 時靜默失敗
- [x] 課程欄位下方顯示學習進度條與「已完成/總課數」（LessonProgress 批次查詢，避免 N+1）
- [x] 狀態標籤文案依 type 調整：type=paid → 已付款/已退款；system_assigned/gift → 有效/已撤銷
- [x] 僅 auth + admin middleware 可存取（全部 /admin 路由）

### User Story 2 - 交易詳情檢視 (Priority: P2)

管理員點擊列表「查看」進入 `/admin/transactions/{id}`，取得單筆交易完整資訊，
供客服與對帳使用。

**驗收**：
- [x] 顯示：訂單 ID、Portaly 訂單編號、PayUni 交易序號（legacy 直購，v-if 有值）、購買者、購買者 Email（記錄值）、課程、金額、折扣金額、優惠碼、狀態、來源、類型、Webhook 接收時間、建立/更新時間
- [x] 關聯 Order 存在時顯示「購物車訂單資訊」區塊：商店訂單編號、金流交易序號、金流管道（顯示中文名稱）、公司統編（v-if 有值）
- [x] 購買者連結跳轉 `/admin/members?highlight={id}`；課程連結跳轉 `/admin/courses/{id}/edit`
- [x] 會員已刪除時顯示「（會員已刪除）」並以 buyer_email 備援顯示
- [x] status=paid 時顯示「標記退款」按鈕，開啟 TransactionRefundModal 確認

### User Story 3 - 手動新增交易 (Priority: P3)

管理員可為指定會員手動指派（system_assigned）或贈送（gift）課程存取權，
處理補單、贈課等例外情況，無需付款流程。

**驗收**：
- [x] 列表頁「手動新增」開啟 modal：Email 搜尋會員（呼叫 /admin/members JSON、300ms debounce、需從結果點選）、課程下拉、類型（gift 預設 / system_assigned）
- [x] 建立成功：amount=0、currency=TWD、status=paid、source=manual、buyer_email=會員 email；flash「交易新增成功」
- [x] 該會員已有此課程的 paid 紀錄 → 錯誤「該會員已擁有此課程」，不建立重複紀錄
- [x] 存在 refunded 舊紀錄 → 原地更新該筆為 paid/manual（因 UNIQUE(user_id, course_id) 約束，不另 insert）
- [x] StoreTransactionRequest 驗證 user_id/course_id 存在、type 限 system_assigned/gift，錯誤訊息為中文

### User Story 4 - 標記退款 (Priority: P3)

管理員可將 paid 交易標記為 refunded，同步使會員失去課程存取權；
含推薦回饋的訂單受成熟期限制。

**驗收**：
- [x] 列表每列 status=paid 顯示「標記退款」快捷按鈕（「查看」左側），以 window.confirm 確認；refunded 列不顯示
- [x] 詳情頁以 TransactionRefundModal 確認（ESC 可關閉、body scroll lock、防重複送出）
- [x] `PATCH /admin/transactions/{id}/refund` 將 status 更新為 refunded；flash「已標記退款，課程存取已撤銷」
- [x] 已 refunded 的交易重複操作回傳錯誤「此交易已退款」（不可逆，refunded 無法改回 paid）
- [x] 關聯 Order 有 referrer_user_id 且已超過回饋成熟期（referral_maturity_days，預設 14 天，自 webhook_received_at ?? created_at 起算）→ 拒絕退款並顯示期限訊息
- [x] 退款時呼叫 PointService::voidReferral 作廢未成熟推薦回饋（細節歸 007-points-referral）
- [x] 課程存取判斷以 status=paid 為準，標記後即失效，無需額外刪除資料

### User Story 5 - 勾選匯出 CSV (Priority: P2)

管理員勾選一筆/多筆交易，或跨頁全選符合篩選條件的交易，匯出 CSV 供對帳分析。

**驗收**：
- [x] 每列 checkbox + 表頭當頁全選；套用篩選後可「選取全部 N 筆符合條件」（跨頁，select_all 模式）；列表頂端顯示已選取筆數
- [x] 匯出走 `GET /admin/transactions/export`：勾選模式傳 `ids[]`；select_all 模式改傳列表篩選參數，後端重建同一查詢
- [x] 未選取時匯出按鈕不可用；後端無 ids 且非 select_all 時回 422
- [x] 檔名 `transactions-YYYYMMDD.csv`、UTF-8 BOM（Excel 中文相容）、streamDownload + chunk(200) 串流輸出
- [x] 欄位順序：訂單 ID、商店訂單編號、金流交易序號、金流管道、PayUni 交易序號、Portaly 訂單編號、購買者姓名、購買者 Email、購買者電話、公司統編、課程名稱、金額、折扣金額、優惠碼、幣別、狀態、來源、類型、購買時間（共 19 欄；無值輸出空字串）

### User Story 6 - 營收圖表與區間統計 (Priority: P2)

交易列表上方顯示營收圖表區塊：可切換時間區間，以雙軸圖呈現每日銷售額與銷售量，
搭配區間統計卡片。

**驗收**：
- [x] 預設「過去 30 天」；篩選器提供 7d / 30d / 90d / 自訂（MM/DD/YYYY 起訖輸入 + 套用）
- [x] 統計卡片：區間銷售額（$ 千分位）與區間銷售量（筆）；僅計 status=paid 交易
- [x] chart.js 雙軸：綠色柱狀=當日銷售額（左 Y 軸）、折線=當日銷售量（右 Y 軸，整數刻度）；圖例置底；容器高度固定 360px
- [x] 右 Y 軸為整數刻度（precision: 0、stepSize: 1、beginAtZero），資料稀少時不出現小數刻度
- [x] 日期以台灣時區切分（SQL `CONVERT_TZ(created_at, "+00:00", "+08:00")`）；無交易日以 CarbonPeriod 補 0，X 軸連續不中斷
- [x] 切換區間以 Inertia partial reload（`only: ['chartData', 'chartFilters']`）更新，不重載交易列表

### User Story 7 - 營運儀表板 (Priority: P2)

管理員登入後台首頁 `/admin` 看到平台整體統計與最近新增課程，作為後台入口總覽。

**驗收**：
- [x] 統計卡片：課程總數、已發佈（is_published=true）、草稿（status=draft）、會員數（User::members() scope，排除管理員）
- [x] Controller 另回傳 total_purchases（目前前端未顯示，保留欄位）
- [x] 「最近新增課程」列表：最新 5 筆，狀態標籤（草稿/預購中/熱賣中）+ preorder/selling 加註「已上架」，並有「查看全部」連結至 /admin/courses
- [x] 無課程時顯示「目前沒有課程」空狀態

## Requirements

- **FR-001**: `status`（paid/refunded）與 `type`（paid/system_assigned/gift）為兩個獨立維度：「有效交易」= `status=paid`；權限與營收統計依 status，取得方式與 UI 標籤依 type。
- **FR-002**: 關鍵字搜尋 MUST 同時比對 buyer_email、portaly_order_id、關聯 Order 的 merchant_order_no，任一符合即納入；CSV select_all 模式沿用同一組篩選。
- **FR-003**: 訂單 badge 判斷 MUST 為 data-driven、不依賴 `purchases.source`（legacy 資料 source 不一致）：依序 `portaly_order_id`（→Portaly）→ `order.payment_gateway === 'payuni'/'newebpay'`（→PayUni/NewebPay，複製 merchant_order_no）→ `payuni_trade_no`（legacy 單堂直購 →PayUni）→ 顯示「—」。
- **FR-004**: 手動新增 MUST 先擋既有 paid 紀錄；若存在 refunded 舊紀錄則原地更新為 paid（避免 UNIQUE(user_id, course_id) 衝突），否則新建。
- **FR-005**: 退款為單向 paid → refunded，僅為後台標記；實際金流退款由外部（Portaly / 金流商）處理，系統不呼叫金流退款 API。
- **FR-006**: 含推薦回饋的訂單（order.referrer_user_id 有值）MUST 僅能於回饋成熟期內退款（`site_settings.referral_maturity_days`，預設 14 天，自 order 的 webhook_received_at ?? created_at 起算），逾期拒絕並提示天數。
- **FR-007**: 退款 MUST 同步作廢該訂單未成熟的推薦回饋（PointService::voidReferral，訂單層級冪等；推薦人 activation flag 不回復）— 行為細節歸 007。
- **FR-008**: CSV 匯出 MUST 以 streamDownload + chunk(200) 串流，開頭寫入 UTF-8 BOM；大量匯出（5,000+ 筆）不逾時、不截斷。
- **FR-009**: 營收統計僅計 `status=paid`；日期切分以台灣時區（UTC+8）為準；區間內無交易的日期補 0。
- **FR-010**: 圖表區間切換 MUST 用 Inertia partial reload（only: chartData/chartFilters），保留列表狀態與捲動位置。
- **FR-011**: 會員帳號刪除後交易紀錄仍保留，顯示原始 buyer_email 備援。
- **FR-012**: Dashboard 會員數 MUST 使用 members scope 統計（排除 admin 角色）。

## 設計決策

- **D1**: badge 判斷 data-driven（依編號欄位而非 source）— legacy webhook 紀錄的 source 可能為 null/不一致；以實際存在的訂單編號決定來源最可靠。
- **D2**: 退款後重新指派 = 原地更新 refunded 紀錄 — `UNIQUE(user_id, course_id)` 使 insert 必然衝突；否決「刪除重建」以保留原始紀錄 id。
- **D3**: 營收圖表用 chart.js + vue-chartjs（bar + line 雙軸混合圖），資料由 index 同一 action 計算並隨 partial reload 回傳 — 免去獨立 API 端點。
- **D4**: 列表快捷退款用 `window.confirm`、詳情頁用 TransactionRefundModal — 列表求快，詳情頁有完整上下文值得正式 modal；兩者同打 `PATCH .../refund`（曾因誤用 POST 出現 405，已修正）。
- **D5**: `/admin/transactions/export` 路由 MUST 註冊在 `/admin/transactions/{transaction}` 之前，否則 "export" 會被當成 route model binding 參數。
- **D6**: 手動新增的會員搜尋復用 `/admin/members` 端點（帶 `X-Inertia: false` 取 JSON）— 不另建 API；課程進度批次計算在 controller 以兩段查詢 + in-memory map 完成，避免每列 N+1。
- **D7**: CSV 匯出用瀏覽器導向（`window.location.href` + query string）而非 Inertia 請求 — 讓瀏覽器原生處理檔案下載。

## Schema

本模組不擁有資料表。核心讀寫對象 `purchases` / `orders` 歸 005-checkout；
本模組依賴的關鍵語意：

- `purchases.status`（paid/refunded）— 有效性；`purchases.type`（paid/system_assigned/gift）— 取得方式。兩者獨立（FR-001）。
- `purchases` 有 `UNIQUE(user_id, course_id)` — 手動新增的原地更新策略源於此（D2）。
- `orders.referrer_user_id` — 有值代表該訂單綁定推薦回饋，退款受成熟期限制（FR-006）。

## 進度日誌

- 2026-07-06: 領域重組 — 自 006-transactions-management 重寫（含 Admin Dashboard），依實際 codebase 校正
