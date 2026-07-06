# Quickstart: 積分系統擴充 (012-points-system)

**Branch**: `012-points-system` | **Date**: 2026-06-30

實作與驗證的建議順序。每個切片可獨立測試（對應 spec 的 User Stories）。

## 前置

```bash
git checkout 012-points-system
# 開發
php artisan serve
npm run dev
```

## 實作順序（依相依性）

### 切片 0：帳本基礎（US1/US3 的前置）
1. Migration：`point_transactions`、`users` 推薦欄位、`courses.redeem_points`、`orders` 推薦欄位。
2. `PointTransaction` model（`$timestamps=false` + `boot()`）。
3. `PointService`：`award` / `redeemDeduct` / `availableBalance` / `voidReferral` / `matureDue`。
4. 改 `AssignmentService`：`increment('points', 100)` → `PointService::award(..., 'earn_homework')`，點數讀 `homework_reward_points`。
5. `SiteSettingSeeder`：寫入 4 組設定預設值。
6. 既有會員 backfill：`referral_code` 補發 + 達門檻者 `referral_activated_at` 點亮。

```bash
php artisan migrate
php artisan db:seed --class=SiteSettingSeeder
```

### 切片 1：兌換課程（US1，P1）
1. `RedemptionService::redeem` + `RedemptionController` + 路由 `POST /courses/{course}/redeem`。
2. `Course/Show.vue` 加 `RedeemButton.vue`，props：`redeemPoints`、`userAvailablePoints`。
3. 後台課程表單（`CourseForm.vue`）加 `redeem_points` 欄位。

**驗證**：
- 餘額足夠帳號兌換 → 取得擁有權、`users.points` 正確扣除、帳本新增 `redeem_course` 負筆。
- 餘額不足 → 按鈕 disabled 顯示「還差 N 點」。
- 已擁有 → 無兌換入口。
- 並發/重複點擊 → 至多一次成功，餘額不為負（可用兩個請求或測試模擬）。

### 切片 2：推薦碼 + 結帳驗證 + 回饋（US2，P1）
1. `ReferralService`：`validateAtCheckout` / `reward` / `evaluateActivation`。
2. `ReferralController` + 路由 `POST /checkout/validate-referral`；`ReferralInput.vue` 比照 `CouponInput.vue` 嵌入 `Checkout/Index.vue`。
3. `CheckoutRequest` 加 `referral_code`；`CheckoutService::createOrder` 快照推薦欄位；`fulfillOrder` 發放回饋 + `evaluateActivation`。
4. 排程 `points:mature`。

**驗證**：
- 不存在／自薦／未啟用推薦碼 → 結帳前即被擋下並顯示對應中文訊息。
- 已啟用推薦人 + 買家完成付款 → 推薦人帳本新增 `earn_referral`，金額 = 折後實付 × 比例 四捨五入到十位，`available_at` = 14 天後。
- 未付款（放棄）→ 無回饋。
- 回饋 14 天內 → 兌換時不可用（不計入 `users.points`）。
- 買家累計付費跨門檻 → `referral_activated_at` 點亮。

### 切片 3：會員積分中心（US3，P2）
- `Member/PointController` + `Member/Points.vue`：可用/未成熟餘額、明細分頁、推薦碼。

### 切片 4：後台派發 + 帳本明細（US6，P3）
- `Admin\GrantPointsRequest` + `MemberController::grantPoints` + 路由。
- `MemberController::show` 回傳 `pointTransactions`；`MemberDetailModal.vue` 積分區塊加派發表單 + 明細。
- **驗證**：派發只有「增加」、無扣除入口；非正整數被擋；派發即時可用並出現在明細。

### 切片 5：後台推薦統計（US5，P3）
- `ReferralStatsController` + `Admin/Referrals/Index.vue`，時間區間篩選。

### 切片 6：退款互動（FR-023~025，搭配 006）
- `TransactionService::refund`：含回饋訂單 14 天窗口守門 + `voidReferral`。
- **驗證**：14 天內退款 → 作廢未成熟回饋、無負餘額；逾 14 天 → 退款被拒。

## 測試

```bash
php artisan test
# 重點 Feature 測試：
# - 並發兌換不超扣（SC-001）
# - 推薦回饋計算與四捨五入（SC-004）
# - 未付款不發回饋（SC-004）
# - 成熟期內回饋不可用（SC-005）
# - 退款不產生負餘額（SC-006）
# - 派發只增不減（US6）
```

## 完成後

執行 `/updatespec` 同步 `repo_map.md`、`spec_index.json`、`code_index.json`。
