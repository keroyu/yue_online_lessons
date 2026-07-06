# Phase 0 Research: 積分系統擴充

**Feature**: 012-points-system | **Date**: 2026-06-30

本功能的設計決策已於規格撰寫前的討論中拍板，故無 NEEDS CLARIFICATION 待解。本文件記錄關鍵技術選擇的決策、理由與被否決的替代方案，作為 data-model 與實作的依據。

---

## R1. 帳本（ledger）vs 沿用整數欄位

- **Decision**: 新增 `point_transactions` 帳本表作為所有積分異動的單一真相來源；`users.points` 退化為「已成熟可用餘額」的快取。
- **Rationale**: 積分一旦可消耗且能自金流交易（推薦回饋）賺取，即帶負債性質，必須能 (a) 稽核每筆增減來源（客訴查核、後台帳本檢視 FR-031）、(b) 防止並發超扣（FR-004）、(c) 退款可逆（FR-024）。整數欄位三者皆無法滿足。
- **Alternatives considered**:
  - *純整數 + 不可逆*：最簡單，但無法稽核、退款無法收回、推薦回饋無賺取紀錄 → 否決。
  - *帳本但不留快取*（每次 `SUM`）：每次顯示餘額都全表掃描該會員帳本 → 隨筆數成長變慢；以 `users.points` 快取 + 未成熟另算規避。

## R2. 「可用餘額」的計算與快取一致性

- **Decision**: `users.points` 儲存「**已成熟**淨額」快取。每次寫帳本時，若該筆 `available_at <= now`，同步 `increment/decrement` 快取；未成熟（推薦回饋）的筆**不**計入快取，待成熟由排程結算或讀取時即時納入。可用餘額 = `users.points`（已成熟快取）。未成熟餘額 = `SUM(amount) WHERE user_id=? AND available_at > now`。
- **Rationale**: 作業獎勵、兌換、派發皆即時成熟 → 直接動快取，符合既有 `$student->increment('points', ...)` 寫法慣例（原則 X，最小改動）。只有推薦回饋有成熟期，量相對少，未成熟另以查詢處理。
- **成熟結算（on-read/on-spend 為主、cron 為後備）**: 「成熟即可用」的正確性由 `PointService::syncMatured(User)` 保證——在查餘額（`availableBalance`）與扣點（`redeemDeduct`）前，先把該會員已到期未同步的 `earn_referral` 計入 `users.points`（單會員、冪等）。排程 `points:mature`（每日）僅為**後備批次**，處理長期未登入者與統計，正確性不依賴其頻率。
- **⚠ 唯一新增複雜度 — 雙真相來源（帳本 + `users.points` 快取）的漂移防護**（既有程式碼無此模式，需明確守門）：
  1. **單一寫入點**：`PointService` 為 `users.points` 的唯一可寫者；每次寫帳本與動快取都在同一 `DB::transaction` 內，禁止他處直接改 `points`（FR-003）。
  2. **冪等成熟**：`points:mature` 以 `matured_synced` 旗標 + transaction 保證同一筆只計入快取一次（重跑安全）。
  3. **對帳保護**：提供一支對帳指令／測試 `php artisan points:reconcile`（或 Feature test），斷言每位會員 `users.points == SUM(amount WHERE available_at<=now)`，CI 與上線後排程定期執行，及早抓出漂移。
- **Alternatives considered**:
  - *完全即時 SUM 不快取*：簡單但效能差，且與既有 `increment('points')` 慣例衝突 → 否決。
  - *回饋發放當下就計入快取、僅以旗標標記未成熟*：兌換時要排除未成熟，會讓「可用餘額」與快取不一致、易出錯 → 否決。

## R3. 原子扣點防超扣

- **Decision**: 兌換扣點以條件式更新 `UPDATE users SET points = points - :cost WHERE id = :id AND points >= :cost`，於 `DB::transaction` 內執行；受影響筆數為 0 即視為餘額不足、`throw` 並 rollback（含已建立的 Purchase 與帳本）。
- **Rationale**: 單一條件式 UPDATE 是 MySQL 下最簡單且正確的防超扣手段，無需 `lockForUpdate` 顯式鎖；天然防並發雙花與重複點擊（FR-004、SC-001）。
- **Alternatives considered**:
  - *先 SELECT 餘額再 UPDATE*：read-modify-write 有 TOCTOU 競態 → 否決。
  - *`lockForUpdate` 悲觀鎖*：可行但較重，條件式 UPDATE 已足夠 → 否決。

## R4. 兌換取得的 Purchase 如何標記、是否影響擁有權與推薦門檻

- **Decision**: 兌換建立的 Purchase 設 `source='points'`、`type='paid'`、`amount=0`、`status='paid'`，沿用既有 `(user_id, course_id)` 唯一約束與「建立 Purchase 即授課」路線（比照 `FreePurchaseController`）。
- **Rationale**:
  - 教室存取依「Purchase 存在且 `status='paid'`」判定，與 `type` 無關，故 `type='paid'` 可正常授課。
  - 推薦啟用門檻 = `SUM(amount) WHERE type='paid'`；兌換 `amount=0` 對門檻貢獻 0，天然不灌水（符合 FR-015「積分兌換不計入累計」）。
  - `source='points'` 讓報表／交易列表能與付費取得區分。
- **Alternatives considered**:
  - *新增 `type='redeem'`*：語意更清楚，但需逐一檢查所有 `type='paid'` 的既有查詢是否漏算授權，改動面大、風險高 → 否決（以 `source` 區分即足夠，符合原則 X）。

## R5. 推薦碼產生、唯一性與既有會員 backfill

- **Decision**: `users.referral_code` 為 unique、不可變；於 Model `booted()` 的 `creating` 事件產生（比照 `DripSubscription::unsubscribe_token` 的自動產生慣例）。格式：8 碼大寫英數，排除易混字元（0/O、1/I/L）。既有會員以一次性 migration/seeder backfill 補發。
- **Rationale**: 沿用既有「`booted()` 自動產生欄位」慣例（原則 IV）；排除易混字元降低輸入錯誤；與折扣碼（6 碼）長度不同，視覺可區分（spec Assumptions）。
- **碰撞處理**: 產生時迴圈重試直到 `referral_code` 不存在（碼空間極大，碰撞機率微乎其微）。
- **Alternatives considered**:
  - *用 user id 直接當碼*：可預測、可枚舉他人碼 → 否決。
  - *UUID*：太長不利分享 → 否決。

## R6. 推薦啟用判定時機與 backfill

- **Decision**: `users.referral_activated_at`（nullable timestamp）。在 `CheckoutService::fulfillOrder` 付款履行成功後，對「該訂單的買家會員」重算累計 `SUM(amount) WHERE type='paid'`，若 ≥ 門檻且 `referral_activated_at` 為 null 則點亮（寫入 now）。一旦點亮永不清除。上線時以 seeder 對既有已達門檻者補點亮。
- **Rationale**: 累計只在「自己付款」時改變，故只需在付款履行時評估（最省、無需排程）。永久旗標符合「不會更動」語意，亦避免每次結帳重算全歷史。
- **Alternatives considered**:
  - *結帳驗證當下即時 SUM 推薦人累計*：每次別人用碼都要算推薦人全歷史 → 較重；改以讀旗標 O(1) → 採旗標。

## R7. 推薦回饋發放時機、計算與成熟期

- **Decision**:
  - 發放於 `CheckoutService::fulfillOrder`（付款確認後），絕不在 `createOrder`（FR-020）。
  - 金額 = `round(order.total_amount * rate / 100 / 10) * 10`（折後實付、比例取自 order 快照、四捨五入到十位、half-up）。
  - 寫一筆 `point_transactions`：`type='earn_referral'`、`amount=回饋點數`、`reference=order_id`、`available_at = now + maturityDays`。
  - 結帳建單時於 `orders` 快照 `referrer_user_id`、`referral_rate`、`referral_reward_points`（以建單當下 subtotal 預估，付款時以實付重算並更新）。
- **Rationale**: 對齊 011 折扣碼「付款確認才 redeem」紀律；快照比例確保日後改設定不影響歷史（FR-027）。
- **Rounding**: PHP `round($x / 10, 0, PHP_ROUND_HALF_UP) * 10`。
- **Alternatives considered**:
  - *以 subtotal（折扣前）計*：與用戶決策（折後實付）不符 → 否決。

## R8. 退款互動與「不會產生負餘額」的保證

- **Decision**:
  - 含推薦回饋的訂單，退款期限 = 成熟天數（14 天）。`TransactionService::refund` 在標記退款前，若該 purchase 所屬 order 有推薦回饋，檢查 `now <= order.webhook_received_at + maturityDays`；逾期則拒絕退款並回結構化錯誤。
  - 期限內退款：呼叫 `PointService::voidReferral(order)` 作廢對應的**尚未成熟** `earn_referral` 帳本筆（刪除或寫一筆對銷 `refund_reversal`，且因未成熟、未計入快取，無需動 `users.points`）。
- **Rationale**: 回饋 14 天內鎖住不可用、退款也限 14 天內 → 回饋被退款收回時「必定尚未成熟、必定尚未被花用」，因此永不出現負餘額（SC-006）。這把上一版需要的 clawback 負餘額政策整個消除。
- **實作細節**: order 與 purchase 為一對多；退款以 purchase 為單位觸發，但回饋作廢以 order 為單位且需冪等（多 item 訂單只作廢一次）。以 order 上 `referral_reward_points` 是否已作廢的狀態判定（或檢查帳本是否仍存在該筆）。
- **Alternatives considered**:
  - *允許負餘額 + 對銷*：帳務完整但 UX 差、需處理負餘額顯示與兌換阻擋 → 被用戶否決，改用成熟期方案。

## R9. 設定儲存

- **Decision**: 沿用 `SiteSetting::get/set`（007 基礎建設）存 4 組鍵：
  - `referral_threshold_amount`（預設 `3000`）
  - `referral_reward_rate`（預設 `10`，單位 %）
  - `homework_reward_points`（預設 `100`）
  - `referral_maturity_days`（預設 `14`）
- **Rationale**: 全站單一值、現成 KV 設定機制、行銷可調不需改 code（FR-026）。以 seeder 寫入預設值。
- **Alternatives considered**: 新增專屬設定表 → 過度設計，否決（原則 X）。

## R10. 作業發點接點與「硬編碼 100」清查

- **Decision**: 將 `AssignmentService:27` 的 `$student->increment('points', 100)` 改為 `app(PointService::class)->award($student, $settingPoints, 'earn_homework', 'assignment', $assignment->id)`，點數讀 `homework_reward_points` 設定。`PointService::award` 在同一 `DB::transaction` 內寫帳本並更新快取，維持與既有冪等檢查（`AssignmentCompletion` 唯一）一致。
- **⚠ 硬編碼 100 有兩處，皆需處理**（程式碼勘查結果）：
  1. `app/Services/AssignmentService.php:27` — 發放邏輯，改走 `PointService` + 設定值。
  2. `app/Http/Controllers/Member/SettingsController.php:43` — 會員端「作業完成歷程」顯示 `'points_awarded' => 100` 為**寫死**。改為讀取**帳本該筆 `earn_homework` 的實際 `amount`**，而非固定 100——因為設定可調整，歷史完成可能以不同點數發放，寫死會顯示錯誤。
- **Rationale**: 收斂所有積分增減到單一寫入點（FR-003）；歷程顯示以帳本為準確保歷史正確性（FR-027「設定變更不影響既有紀錄」的呈現一致性）。

---

## 研究結論

所有技術選擇皆落在既有慣例內（Service 封裝、條件式 UPDATE、`booted()` 自動產生、`SiteSetting` KV、`DB::transaction`），無需新套件，Constitution Check 全數 PASS。可進入 Phase 1 設計。
