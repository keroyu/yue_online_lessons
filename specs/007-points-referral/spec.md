---
id: 007-points-referral
status: draft
owner_files:
  - app/Http/Controllers/Member/PointController.php
  - app/Http/Controllers/RedemptionController.php
  - app/Http/Controllers/ReferralController.php
  - app/Http/Controllers/Admin/ReferralController.php
  - app/Http/Requests/Admin/GrantPointsRequest.php
  - app/Http/Requests/RedeemCourseRequest.php
  - app/Http/Requests/ValidateReferralRequest.php
  - app/Models/PointTransaction.php
  - app/Services/PointService.php
  - app/Services/RedemptionService.php
  - app/Services/ReferralService.php
  - app/Console/Commands/MaturePoints.php
  - app/Console/Commands/ReconcilePoints.php
  - resources/js/Components/Cart/ReferralInput.vue
  - resources/js/Components/Course/RedeemButton.vue
  - resources/js/Components/Admin/ReferrerDetailModal.vue
  - resources/js/Pages/Member/Points.vue
  - resources/js/Pages/Admin/Settings/Points.vue
  - database/migrations/2026_05_10_000001_add_points_to_users_table.php
  - database/migrations/2026_06_30_000001_create_point_transactions_table.php
  - database/migrations/2026_06_30_000002_add_referral_fields_to_users_table.php
  - database/migrations/2026_06_30_000003_add_redeem_points_to_courses_table.php
  - database/migrations/2026_06_30_000004_add_referral_fields_to_orders_table.php
  - database/migrations/2026_06_30_000005_backfill_points_and_referral_data.php
  - database/migrations/2026_07_11_000002_add_referral_discount_to_orders_table.php
touchpoints:
  - file: app/Services/CheckoutService.php
    owner: 005-checkout
    why: createOrder 快照推薦欄位與買家折抵（referral_discount_amount，coupon 後扣、保底 1 元），fulfillOrder 付款確認後呼叫 ReferralService::reward + evaluateActivation
  - file: app/Http/Controllers/CheckoutController.php
    owner: 005-checkout
    why: 建單前呼叫 validateAtCheckout 並把 referrer_id / rate / discount 組成 referral 快照傳入 createOrder
  - file: app/Http/Requests/CheckoutRequest.php
    owner: 005-checkout
    why: 結帳表單的 referral_code 欄位驗證（nullable, max:12）
  - file: app/Models/Order.php
    owner: 005-checkout
    why: referrer_user_id / referral_rate / referral_reward_points 快照欄位與 referrer() 關聯由本模組 alter 引入，model 本體歸 005；US8 唯讀讀取 referred_orders（Order where referrer_user_id）
  - file: app/Models/Purchase.php
    owner: 005-checkout
    why: evaluateActivation 累計實付；US8 唯讀讀取推薦人本人交易紀錄（Purchase where user_id，含 course/order 關聯）
  - file: resources/js/Pages/Checkout/Index.vue
    owner: 005-checkout
    why: 結帳頁掛載 ReferralInput 元件（獨立於折扣碼欄位）
  - file: app/Models/User.php
    owner: 001-auth-account
    why: points / referral_code / referral_activated_at 欄位、generateReferralCode()（creating hook 自動產生）、pointTransactions() / pendingPoints() / isReferralActive()——欄位與方法由本模組引入，model 本體歸 001
  - file: app/Services/AssignmentService.php
    owner: 003-classroom
    why: 作業標記完成時經 PointService::award 發放 earn_homework 積分（點數讀 homework_reward_points 設定，不再硬編碼 100）
  - file: resources/js/Pages/Course/Show.vue
    owner: 002-storefront
    why: 課程銷售頁掛載 RedeemButton 並實作兩段式確認面板（顯示兌換後餘額，POST courses.redeem）
  - file: app/Models/Course.php
    owner: 004-course-admin
    why: redeem_points 欄位與 is_redeemable accessor 由本模組 alter 引入，model 本體歸 004
  - file: app/Http/Controllers/Admin/CourseController.php
    owner: 004-course-admin
    why: 課程表單編輯 redeem_points；edit 需正確帶回已儲存值（曾漏傳導致顯示為空的 bug）
  - file: app/Http/Controllers/Admin/SettingsController.php
    owner: 000-platform-core
    why: showPoints / updatePoints 讀寫本模組 5 組 site_settings 鍵；showPoints 另帶入 ReferralService::performanceRows 的推薦成效（US5 併入本頁）
  - file: routes/web.php
    owner: 000-platform-core
    why: /admin/settings/points（積分與推薦）路由；舊 /admin/referrals 改為 redirect 至此；US8 新增 GET /admin/referrals/{user}/detail（admin-only）
  - file: app/Models/SiteSetting.php
    owner: 000-platform-core
    why: 5 組設定鍵（referral_threshold_amount / referral_reward_rate / homework_reward_points / referral_maturity_days / referral_discount_amount）沿用 KV 基礎建設
  - file: app/Services/TransactionService.php
    owner: 009-transactions-admin
    why: 退款前檢查含回饋訂單的 14 天期限；退款時呼叫 PointService::voidReferral 作廢未成熟回饋
  - file: app/Http/Controllers/Admin/MemberController.php
    owner: 008-members-admin
    why: grantPoints action 經 PointService::award 派發 admin_grant；會員詳情載入帳本明細
  - file: resources/js/Components/MemberDetailModal.vue
    owner: 008-members-admin
    why: 會員詳情 modal 的積分區塊——派發表單（只增不減）+ 帳本明細列表
  - file: routes/console.php
    owner: 000-platform-core
    why: 註冊 points:mature（每日 00:30）與 points:reconcile（每日 01:00）排程
---

# Points & Referral（積分帳本、兌換課程、推薦回饋）

## 目標

讓積分從「只進不出的整數」升級為可消耗、可從交易賺取的價值單位：學員可用積分整筆兌換課程、
會員可分享推薦碼賺取好友實付金額比例的回饋。以 `point_transactions` 帳本作為所有積分異動的
單一真相來源，保證可稽核、防超扣、退款不產生負餘額。

## User Stories

### User Story 1 - 用積分兌換課程 (Priority: P1)

學員在課程銷售頁以積分整筆兌換課程（全有或全無，不折抵價格、不經金流）。採兩段式確認：
點「用 N 積分兌換」先顯示確認面板（目前可用 / 本次扣除 / 兌換後餘額），按「確定兌換」才實際扣點。

**驗收**：
- [x] 可用積分足夠且未擁有 → 點兌換顯示確認面板，確認後扣點、建立 `source='points'` Purchase、導向「我的課程」（`member.learning`）
- [x] 確認面板可取消，取消不動任何積分；伺服器端扣點失敗（餘額於他處被花用）於面板顯示中文錯誤
- [x] 可用積分不足 → 按鈕不可點並顯示「還差 N 點」；未登入 → 顯示「登入以 N 積分兌換」導向登入
- [x] 課程未設定 `redeem_points`（null/0）或已擁有 → 不顯示兌換入口
- [x] 並發兌換同一筆餘額至多一筆成功，餘額永不為負（條件式 UPDATE）
- [x] drip 課程兌換後同步訂閱（`DripService::subscribe` + `checkAndConvert`），與免費領取路徑履行一致

### User Story 2 - 推薦碼結帳回饋 (Priority: P1)

每位會員有永久唯一推薦碼。買家（含 guest）結帳填入推薦碼且推薦人已啟用資格時，
付款完成後推薦人獲得買家實付金額 × 回饋比例（四捨五入到十位）的積分，14 天成熟期內不可使用。

**驗收**：
- [x] 結帳頁提供獨立於折扣碼的推薦碼欄位，即時驗證（`POST /api/checkout/validate-referral`，公開路由支援 guest）
- [x] 推薦碼不存在 / 自薦（登入 id 或 buyer_email 比對）/ 推薦人未啟用 → 建單前即擋下並顯示對應中文訊息
- [x] 同一 IP 連續驗證失敗 5 次 → 60 秒節流（429，防枚舉推薦碼）
- [x] 買家改 Email 後前端自動清除已套用推薦碼（自薦判定可能改變）
- [x] 付款確認（fulfillOrder）才發放回饋；金額 = round(實付 × rate% / 10) × 10（half-up），`available_at = now + maturity_days`；每張訂單冪等只發一次
- [x] 買家自己付款後重算累計實付（`SUM(purchases.amount WHERE type='paid')`），跨門檻自動點亮 `referral_activated_at`，永不取消
- [x] 訂單快照 `referrer_user_id` / `referral_rate` / `referral_reward_points`，日後改設定不影響歷史

### User Story 3 - 會員積分中心 (Priority: P2)

會員在 `/member/points` 查看可用/未成熟餘額、帳本明細（分頁）、自己的推薦碼與回饋比例。

**驗收**：
- [x] 可用（已成熟快取）與未成熟（pending 回饋）分開顯示；讀取前先 `availableBalance` 折入剛成熟的回饋
- [x] 明細每頁 20 筆，每筆顯示時間、類型中文標籤（作業獎勵/兌換扣點/推薦回饋/退款作廢/後台派發）、增減點數、是否成熟
- [x] 推薦碼區塊：已啟用顯示「好友實付金額 N% 回饋」文案 + 複製；未啟用顯示「累計消費滿 NT$ 門檻後自動啟用」並同樣告知 N% 比例

### User Story 4 - 後台積分參數設定 (Priority: P2)

管理員於 `/admin/settings/points` 調整 4 組參數：啟用門檻、回饋比例、作業獎勵點數、成熟天數。

**驗收**：
- [x] 4 組鍵存 `site_settings`（預設 3000 / 10% / 100 / 14 天），表單驗證（rate 0–100 等）
- [x] 修改僅影響之後產生的積分與判定；既有帳本紀錄與訂單快照不變

### User Story 5 - 後台推薦成效統計 (Priority: P3)

管理員於「積分與推薦」頁（`/admin/settings/points` 的「推薦成效」分頁）查看各推薦人的推薦訂單數、帶來營收、回饋積分總額與目前積分餘額。統計查詢封裝於 `ReferralService::performanceRows()`，由 `SettingsController@showPoints` 帶入頁面；舊 `/admin/referrals` 302 redirect 至此。

**驗收**：
- [x] 以 `orders.referrer_user_id`（status=paid）彙總，依回饋積分排序；range ∈ 7/30/60/90/all（預設 30），以 `webhook_received_at` 篩選付款時間
- [x] 每列顯示該推薦人目前積分餘額（`users.points`，含入 GROUP BY 以符 ONLY_FULL_GROUP_BY）
- [x] 與「積分設定」合併為單一頁面的兩個分頁（`Settings/Points.vue`）；`/admin/referrals` redirect 保留舊連結

### User Story 6 - 後台派發積分與帳本檢視 (Priority: P3)

管理員在會員詳情 modal 的積分區塊派發積分（只增不減、可附原因）並檢視該會員帳本明細。

**驗收**：
- [x] `POST /admin/members/{member}/grant-points` 寫入 `admin_grant` 帳本筆（即時成熟、即時可用）；amount 須為正整數否則驗證擋下（GrantPointsRequest）
- [x] 介面僅提供派發，無任何扣除入口
- [x] 會員詳情可逐筆檢視帳本（時間、類型、增減、來源/原因），支撐客訴查核

### User Story 7 - 推薦碼買家折抵 (Priority: P1)

買家（含 guest）結帳輸入有效推薦碼時，訂單直接折抵固定金額（後台「積分與推薦 → 推薦回饋」
可設，預設 NT$150）；與折扣碼可疊加（先折扣碼、後推薦折抵），實付保底 1 元。
推薦人回饋改以折抵後的最終實付計算。

**驗收**：
- [x] 後台「推薦回饋」區塊新增「買家折抵金額」欄位（integer ≥0，預設 150）；設 0 = 停用折抵（推薦碼仍可用、回饋照發）
- [x] `validate-referral` API 回傳 `discount` 金額；結帳頁套用推薦碼後訂單摘要顯示「推薦碼折抵 −NT$N」與折後總額，移除推薦碼即還原
- [x] `createOrder` 計價順序 subtotal − 折扣碼 − 推薦折抵；實際折抵 = min(設定值, 折扣碼後 payable − 1)，快照至 `orders.referral_discount_amount`；`total_amount` 為最終實付
- [x] 推薦回饋（預估與 fulfillOrder 重算）皆以折抵後實付為基準
- [x] 買家改 Email 清除推薦碼時，折抵同步自前端摘要移除；guest 買家同樣享有折抵
- [x] 設定值修改僅影響之後建立的訂單，既有訂單快照不變

### User Story 8 - 推薦人明細檢視 (Priority: P3)

管理員在「積分與推薦 → 推薦成效」的推薦人列表點任一列，開啟該推薦人明細 modal，唯讀檢視三區：
(1) 積分帳本（增減/兌換/回饋/派發），(2) 本人交易紀錄（他自己買了什麼），(3) 他帶進來的推薦訂單明細。

**驗收**：
- [ ] 推薦成效每列可點（或列末「查看」鈕）→ 開 `ReferrerDetailModal`，帶入 `referrer_user_id`
- [ ] `GET /admin/referrals/{user}/detail`（admin-only）一次回 JSON：`referrer` 基本（name/email/code/current_points）、`point_transactions`、`own_transactions`、`referred_orders`（三組各取最新 50 筆）
- [ ] 積分帳本每筆：時間、類型中文標籤、增減點數、是否成熟（沿用 008 `MemberDetailModal` 的 `POINT_TYPE_LABELS` 與 `is_matured = available_at <= now` 判定，shape 與 `MemberController@show` 一致）
- [ ] 本人交易紀錄每筆：課程名、金額、狀態、類型標籤、商店訂單編號、時間（`Purchase where user_id = referrer`，最新優先）
- [ ] 推薦訂單明細每筆：商店訂單編號、買家 email、實付金額、回饋積分、狀態、時間（`Order where referrer_user_id = referrer`，最新優先）
- [ ] 三區各有載入中／空狀態；modal 支援 ESC／點背景關閉／body scroll lock／Teleport（比照 `MemberDetailModal`）
- [ ] 純唯讀：無任何編輯／派發／退款入口（那些留在會員管理與交易管理）

## Requirements

- **FR-022**: 推薦人明細為唯讀彙整；三組查詢各自 `limit(50)` 取最新，全部封裝於 `ReferralService::referrerDetail(User): array`，controller 僅轉呼叫（thin controller）。上限 50 為刻意截斷（與會員帳本 modal 一致），非全量。
- **FR-023**: 明細端點 admin-only（隨 `/admin/settings/points` 同權限群組，銷售顧問／editor 不得存取）；`{user}` 路由綁定任意會員，但入口僅在推薦成效列（實際推薦人）提供。

- **FR-001**: 每一筆積分異動 MUST 寫入 `point_transactions` 帳本（單一真相來源）；`users.points` 僅為「已成熟可用餘額」快取，只能由 `PointService` 在 `DB::transaction` 內寫入，禁止他處直接 increment。
- **FR-002**: 可用積分 = 已成熟（`available_at <= now`）帳本淨額；未成熟回饋不計入可用、不可兌換。
- **FR-003**: 扣點 MUST 為原子操作：條件式 `UPDATE users SET points = points - cost WHERE points >= cost`，0 筆受影響即 throw 回滾整個兌換交易；並發下餘額永不為負。
- **FR-004**: 兌換 MUST 於單一交易內完成：檢查可兌換 → 檢查未擁有 → 扣點 → 建 Purchase（`source='points'`, `type='paid'`, `amount=0`, `status='paid'`）→ 寫負值帳本；任一步失敗全部回復。
- **FR-005**: 兌換取得的 Purchase `amount=0` → 天然不計入推薦啟用門檻累計；教室存取依 `status='paid'` 判定不受影響。
- **FR-006**: 推薦碼 8 碼大寫英數（排除易混字元 0/O/1/I/L）、unique、永久不變；`User::creating` 自動產生，既有會員由 backfill migration 補發。
- **FR-007**: 推薦回饋 MUST 僅在付款確認後發放（`fulfillOrder`，絕不在 `createOrder`）；每張訂單冪等（帳本已存在該 order 的 `earn_referral` 即跳過）。
- **FR-008**: 回饋金額 = 折扣後實付金額 × 訂單快照比例，四捨五入到十位（half-up）；結果 ≤ 0 視為無回饋。
- **FR-009**: 回饋成熟時間 = 發放當下 + `referral_maturity_days`（預設 14 天）；「成熟即可用」由 on-read/on-spend 的 `syncMatured` 保證，`points:mature` 排程僅為長期未登入者的後備批次。
- **FR-010**: 含推薦回饋的訂單退款期限 = 成熟天數；期限內退款 MUST 作廢未成熟回饋（對銷 `refund_reversal` 筆），逾期拒絕退款——因此被作廢的回饋必定未成熟、未入快取，永不產生負餘額。
- **FR-011**: 推薦啟用為單向永久旗標：買家自己的訂單付款履行時重算累計實付 ≥ 門檻即點亮；退款不取消資格。
- **FR-012**: 防自薦：以登入 user_id 與 buyer_email（雙重比對推薦人）擋下；推薦碼驗證 API 具 IP 失敗節流（5 次 / 60 秒冷卻，成功即重置）。
- **FR-013**: 推薦碼比對前正規化（trim + 大寫）；guest 買家可使用推薦碼（回饋對象是推薦人）。
- **FR-014**: 作業獎勵 MUST 經 `PointService::award` 發放（`earn_homework`、即時成熟），點數讀 `homework_reward_points` 設定；冪等由 `AssignmentCompletion` 唯一約束保證。
- **FR-015**: 後台派發只能增加（正整數），不得提供扣除入口——避免快取被扣成負數、與帳本計算衝突。
- **FR-016**: 折扣碼與推薦碼兩系統獨立並存；回饋以折後實付金額為基準。
- **FR-017**: 快取與帳本 MUST 可對帳：`points:reconcile` 斷言每位會員 `users.points == SUM(已成熟帳本)`，漂移記 log 並回傳失敗。
- **FR-018**: 會員端「作業完成歷程」顯示的獲得點數 MUST 讀帳本該筆 `earn_homework` 的實際 `amount`（非寫死 100）——設定可調，歷史完成可能以不同點數發放。
- **FR-019**: 上線 backfill MUST：為既有會員補發推薦碼、對已達門檻者點亮啟用、為既有作業完成補寫 `earn_homework` 帳本筆（點數已在 `users.points`，只補帳本不重複加點，確保 reconcile 通過）。

- **FR-020**: 推薦折抵 MUST 在 `createOrder` 單點計算（不信任前端金額）：順序為折扣碼先、推薦折抵後；`applied = min(referral_discount_amount, payable - 1)`，結果 ≤ 0 則不折抵但推薦碼仍有效（回饋照發）。
- **FR-021**: `orders.referral_discount_amount` 為建單時快照；`total_amount` 已含所有折抵，故回饋計算（FR-008 的實付基準）無需另行扣減。

### 邊界行為

- 同一買家以同一推薦碼多次下單：每筆符合條件的已付款訂單各自回饋一次（啟用門檻為主要防濫用閘門）。
- 實付金額極小：10% 四捨五入到十位可能為 0 → 視為無回饋、不寫帳本，可接受。
- 訂單金額 ≤ 折抵額（如 100 元課、折 150）：自動封頂折至實付 1 元（與 CouponService::MIN_PAYABLE 一致），不拒單。
- 訂單建立後買家放棄付款：`createOrder` 只留快照，未進 `fulfillOrder` 即不發任何回饋。
- 多 item 訂單退款：退款以 purchase 為單位觸發，但回饋作廢以 order 為單位且冪等（只作廢一次）。
- 兌換全額以積分取得：不經金流，無最低金額 / 0 元送單問題。
- 累計門檻不計入：積分兌換（amount=0）、贈課、系統指派（type != 'paid'）皆不灌門檻。

## 設計決策

- **D1**: 帳本 + `users.points` 快取雙軌 — 積分可消耗且來自金流即帶負債性質，需稽核、防超扣、退款可逆；純整數欄位三者皆不滿足。每次 SUM 不快取則效能差（否決）。
- **D2**: 快取只存「已成熟」淨額 — 即時成熟筆（作業/兌換/派發）寫帳本時同步動快取；延遲成熟筆（回饋）不動快取，成熟後才折入。避免「快取含未成熟、兌換時再排除」的不一致風險（否決）。
- **D3**: 成熟結算 on-read/on-spend 為主、cron 後備 — `availableBalance` / `redeemDeduct` 前先 `syncMatured`（單會員、`matured_synced` 旗標冪等 + `lockForUpdate`），正確性不依賴排程頻率；`points:mature` 每日 00:30 只處理長期未登入者。
- **D4**: 條件式 UPDATE 防超扣 — 單句 `WHERE points >= cost` 天然防 TOCTOU 與並發雙花；先 SELECT 再 UPDATE（競態）與悲觀鎖（過重）皆否決。
- **D5**: 兌換 Purchase 用 `source='points'` 而非新 `type` — 沿用「建 Purchase 即授課」路線（比照免費領取）；新增 type 需清查所有 `type='paid'` 查詢，改動面大（否決）。`amount=0` 順帶不灌推薦門檻。
- **D6**: 兩段式兌換確認在銷售頁內嵌面板（非 modal、非獨立頁）— 點綠色按鈕只切前端狀態顯示「兌換後餘額」，確定才 POST；成功導向「我的課程」而非直接進教室（方位中性，多課程情境不預設進哪一門）。
- **D7**: 啟用資格用永久旗標 `referral_activated_at`，於買家自己付款履行時評估 — 別人用碼時只讀旗標 O(1)，免每次重算推薦人全歷史消費（否決即時 SUM）。
- **D8**: 訂單快照 `referral_rate` / `referral_reward_points`（建單時以 subtotal 預估、付款時以實付重算覆寫）— 對齊折扣碼「付款確認才結算」紀律；日後改設定不影響歷史。
- **D9**: 退款作廢採對銷筆而非刪除 — `refund_reversal` 保留稽核軌跡；reversal 共用原筆 `available_at` 且雙方標 `matured_synced=true`，正負對永遠同時跨成熟線，`reconcile()`（SUM 已成熟筆）恆平衡。
- **D10**: 「成熟期 = 退款期限」對齊（14 天）— 從源頭消除負餘額：被退款收回的回饋必定尚未成熟、必定尚未被花用。允許負餘額 + clawback 的替代方案被否決。
- **D11**: 4 組參數沿用 `SiteSetting` KV — 全站單一值，行銷可調免改 code；專屬設定表為過度設計（否決）。
- **D12**: 買家折抵為固定金額 site_settings KV（`referral_discount_amount`，預設 150）＋訂單快照欄位 — 沿 D11 KV 紀律與 D8 快照紀律；折抵只在 `createOrder` 一處入帳（否決：前端計算後傳入 — 不可信任；否決：折抵做成負值 order_item — 汙染品項統計）。
- **D13**: 疊加順序固定「折扣碼 → 推薦折抵」，保底實付 1 元 — 與折扣碼 MIN_PAYABLE 同一底線，兩系統維持獨立驗證、互不知曉，只在 createOrder 依序扣減（否決：擇一互斥 — 前端需互鎖、行銷彈性差）。
- **D14**: 推薦人明細做成推薦頁專屬唯讀 modal + 單一 JSON 端點，而非複用 008 `MemberDetailModal` — 後者含編輯/派發/贈課、綁 `/admin/members` 且範圍限 `isManageableMember`；推薦頁只需唯讀彙整，且多出「本人交易」與「推薦訂單」兩塊 008 沒有的資料，另建輕量 modal 較內聚、避免跨模組耦合。
- **D15**: 三組資料合併於 `ReferralService::referrerDetail` 一次回傳（各 `limit(50)`），而非三個端點 — 一次開 modal 一次載入、減少往返；帳本沿用 `MemberController@show` 既有 shape，前端可共用同一組類型標籤。
- **D16**: `performanceRows` map 輸出加 `referrer_user_id` 供列點擊帶入 — 既有查詢已 `select` 該欄，只需一併 map 出，零額外查詢。

## Schema

- `point_transactions` —（本模組唯一擁有的表）所有積分異動的帳本。`amount` 帶號整數（正=賺取/派發、負=兌換扣點/對銷）；`type` ∈ earn_homework / redeem_course / earn_referral / refund_reversal / admin_grant；`reference_type`/`reference_id` 為無 FK 快照式關聯（order / assignment / course / admin）；`available_at` 成熟時間（即時筆 = created_at）；`matured_synced` 標記是否已折入快取（冪等守門）；write-once、無 updated_at。不變量：`users.points == SUM(amount WHERE available_at <= now)`。
- `users`（alter，表歸 001-auth-account）— `points`：已成熟可用餘額快取，僅 PointService 可寫；`referral_code`：unique 永久推薦碼；`referral_activated_at`：單向啟用旗標，點亮永不清除。
- `courses`（alter，表歸 004-course-admin）— `redeem_points`：null/0 = 不可兌換，>0 = 兌換所需點數；不綁課程類型。
- `orders`（alter，表歸 005-checkout）— `referrer_user_id`（nullable FK, nullOnDelete）+ `referral_rate`（% 快照）+ `referral_reward_points`（結算快照）+ `referral_discount_amount`（unsignedInteger default 0，US7 買家折抵快照）；與折扣碼欄位並存互不干擾。
- `site_settings`（seed，表歸 000-platform-core）— `referral_threshold_amount`(3000) / `referral_reward_rate`(10) / `homework_reward_points`(100) / `referral_maturity_days`(14，兼含回饋訂單退款期限) / `referral_discount_amount`(150，US7 買家折抵，0=停用)。

US8 不新增資料表：讀 `point_transactions`（本模組表）＋ `purchases`／`orders`（005 表，唯讀彙整，各取最新 50）。

## Tasks

### US7 - 推薦碼買家折抵

Phase A — 後端
- [x] T001 migration：orders 加 `referral_discount_amount` unsignedInteger default 0 in database/migrations/2026_07_11_000002_add_referral_discount_to_orders_table.php
- [x] T002 [P] `validateAtCheckout` 回傳值加 `discount`（讀 `referral_discount_amount` 設定）in app/Services/ReferralService.php, app/Http/Controllers/ReferralController.php
- [x] T003 [P] `showPoints`/`updatePoints` 增第 5 鍵與驗證（integer, min:0）in app/Http/Controllers/Admin/SettingsController.php〔touchpoint 000〕
- [x] T004 `createOrder` 折抵計價（coupon 後 min(設定, payable-1)、快照欄位、reward 估算基準改折抵後）＋ TDD 測試 in app/Services/CheckoutService.php〔touchpoint 005〕, tests/Feature/Points/ReferralDiscountTest.php

Phase B — 前端
- [x] T005 [P] 「推薦回饋」區塊新增「買家折抵金額」欄位 in resources/js/Pages/Admin/Settings/Points.vue
- [x] T006 [P] 結帳頁套用推薦碼顯示「推薦碼折抵 −NT$N」與折後總額、移除即還原 in resources/js/Components/Cart/ReferralInput.vue, resources/js/Pages/Checkout/Index.vue〔touchpoint 005〕

Phase C — 驗證
- [x] T007 php artisan test 全綠 + npm run build exit 0；逐條對 US7 驗收

### US8 - 推薦人明細檢視

Phase A — 後端
- [ ] T001 `ReferralService::referrerDetail(User): array` — 組 `point_transactions`(50)＋`own_transactions`(50)＋`referred_orders`(50)＋`referrer` 基本 in app/Services/ReferralService.php
- [ ] T002 [P] `performanceRows` map 輸出加 `referrer_user_id` in app/Services/ReferralService.php
- [ ] T003 `Admin/ReferralController::detail(User): JsonResponse` — thin，delegate to `referrerDetail` in app/Http/Controllers/Admin/ReferralController.php
- [ ] T004 route `GET /admin/referrals/{user}/detail` → `admin.referrals.detail`（admin 群組，置於既有 `/referrals` redirect 之後）in routes/web.php〔touchpoint 000〕

Phase B — 前端
- [ ] T005 [P] `ReferrerDetailModal.vue` — axios 載入、三區、loading/空/ESC/backdrop/scroll-lock/Teleport in resources/js/Components/Admin/ReferrerDetailModal.vue
- [ ] T006 Points.vue 推薦成效列可點開 modal，帶 `referrer_user_id` in resources/js/Pages/Admin/Settings/Points.vue

Phase C — 驗證
- [ ] T007 php artisan test 全綠 + npm run build exit 0；逐條對 US8 驗收

## 進度日誌

- 2026-07-12: /spec 規劃 US8 推薦人明細檢視（推薦成效列點開唯讀 modal：積分帳本＋本人交易＋帶進來的推薦訂單，單一 detail JSON 端點，各取最新 50），status: draft 待審
- 2026-07-11: /dev 完成 US7 推薦碼買家折抵 — orders 加 referral_discount_amount 快照、createOrder 折抵計價（coupon 後扣、保底 1 元）、validate-referral 回傳 discount、後台第 5 鍵設定欄位、結帳摘要折抵列；TDD ReferralDiscountTest 7 tests，全套 100 passed

- 2026-07-11: /spec 規劃 US7 推薦碼買家折抵（預設 150、KV 可調、可疊加折扣碼、保底 1 元），status: draft 待審
- 2026-07-11: US5 推薦成效併入「積分與推薦」頁（`Settings/Points.vue` 兩分頁）；查詢移至 `ReferralService::performanceRows`、由 `SettingsController@showPoints` 帶入；每列加顯目前積分（GROUP BY 含 users.points 防 ONLY_FULL_GROUP_BY）；刪除 `ReferralStatsController` 與 `Referrals/Index.vue`，`/admin/referrals` 302 redirect。補回歸測試 `tests/Feature/Points/ReferralPerformanceTest.php`。
- 2026-07-06: 領域重組 — 自 012-points-system 重寫，依實際 codebase 校正
- 2026-07-05: 積分中心推薦碼區塊顯示回饋比例；兌換成功改導向「我的課程」；確認按鈕文案方位中性
- 2026-07-05: US1 兌換改兩段式確認；修正後台編輯課程 redeem_points 未帶回表單的 bug
- 2026-07-05: US2–US6 + 退款/對帳全數完成；validate-referral 路由落在 /api 前綴對齊既有結帳 api 群組
- 2026-06-30: 012 規格/研究/資料模型定稿（帳本 + 成熟期方案拍板）
