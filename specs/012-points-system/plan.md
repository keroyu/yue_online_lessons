# Implementation Plan: 積分系統擴充（積分帳本 + 兌換課程 + 推薦回饋）

**Branch**: `012-points-system` | **Date**: 2026-06-30 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/012-points-system/spec.md`

## Summary

把現有「只進不出」的 `users.points` 整數，升級為以**積分帳本（`point_transactions`）為單一真相來源**的積分系統，並新增兩個用途：

1. **兌換課程**：課程設定 `redeem_points`，學員以已成熟積分整筆兌換、走「建立 Purchase 授課」路線（不經金流）。
2. **推薦回饋**：每位會員有永久 `referral_code`，累計付費跨門檻後啟用；他人結帳填碼、付款確認後回饋實付金額一定比例的積分，帶 14 天成熟期。

同時：後台會員詳情可派發積分（只增不減）並檢視該會員帳本；門檻金額、回饋比例、作業獎勵點數、成熟天數皆存 `site_settings` 可調。

**技術路線**：新增 `PointService`（帳本與餘額的唯一寫入點）與 `ReferralService`（推薦碼驗證、啟用判定、回饋計算），分別掛載到既有 `AssignmentService`（作業發點改走帳本）與 `CheckoutService`（建單時驗證推薦碼、`fulfillOrder` 時發放回饋並重算推薦資格）。兌換走新的 `RedemptionService`，比照 `FreePurchaseController` 建立 Purchase。退款於 `TransactionService::refund` 補上推薦回饋作廢與 14 天窗口守門。

## Technical Context

**Language/Version**: PHP 8.2 / Laravel 12.x
**Primary Dependencies**: Inertia.js v2、Vue 3（`<script setup>`）、Tailwind CSS v4（皆現有，無新增套件）
**Storage**: MySQL — 1 張新表 `point_transactions`；`users`、`courses`、`orders` 各 alter 新增欄位；`purchases` 沿用既有欄位（新增 `source='points'` 值）；`site_settings` 新增 4 組設定鍵
**Testing**: `php artisan test`（PHPUnit / Feature tests）
**Target Platform**: Laravel Forge 部署的 Web 伺服器
**Project Type**: Web（Laravel backend + Inertia/Vue 前端，單一 repo）
**Performance Goals**: 一般 Web 互動延遲；可用餘額查詢需避免每次全表掃帳本（以 `users.points` 快取 + 未成熟另計）
**Constraints**: 扣點必須原子化、餘額永不為負；回饋只在付款確認後發放；所有積分異動必經帳本
**Scale/Scope**: 既有會員規模（千級）；帳本筆數隨作業／兌換／回饋成長，需索引支撐

**既有模組與將改動檔案（來自 repo_map + 程式碼勘查）**：

| 既有元件 | 角色 | 本功能如何介入 |
|---|---|---|
| `app/Services/AssignmentService.php:27` | 作業標記完成 `$student->increment('points', 100)` | 改為 `PointService::award(...)`，點數讀 `site_settings` |
| `app/Http/Controllers/Member/SettingsController.php:43` | 作業歷程顯示 `'points_awarded' => 100`（寫死） | 改讀帳本該筆 `earn_homework` 實際 `amount`（第二處硬編碼 100） |
| `app/Services/CheckoutService.php` (`createOrder`/`fulfillOrder`) | 建單與付款履行 | `createOrder` 驗證並快照推薦碼；`fulfillOrder` 發放回饋 + 重算推薦資格 |
| `app/Http/Controllers/CheckoutController.php` (`initiate`/`show`) | 結帳入口 | 接收 `referral_code` 欄位、傳入 service |
| `app/Http/Requests/CheckoutRequest.php` | 結帳驗證 | 新增 `referral_code` rule |
| `app/Services/CouponService.php` (`validateForCart`) | 折扣碼驗證範式 | 比照其 `['success'=>..., 'error'=>...]` 回傳風格做推薦碼驗證 |
| `app/Services/TransactionService.php` (`refund`) | 退款標記 | 加 14 天窗口守門 + 作廢未成熟回饋 |
| `app/Http/Controllers/Admin/MemberController.php` (`show`/`update`) | 會員詳情 | 新增派發積分 action + 帳本明細 prop |
| `app/Models/User.php` | 會員 | 新增 `referral_code`、`referral_activated_at`、`pointTransactions()` 關聯 |
| `app/Models/Course.php` | 課程 | 新增 `redeem_points` fillable + `isRedeemable` accessor |
| `app/Models/Order.php` / `Purchase.php` | 訂單/擁有權 | Order 新增推薦欄位；Purchase 新增 `source='points'` 用法 |
| `app/Models/SiteSetting.php` (`get`/`set`) | 站台設定 | 新增 4 組鍵的讀寫 |
| `resources/js/Pages/Checkout/Index.vue` + `Components/Cart/CouponInput.vue` | 結帳 UI | 新增獨立 `ReferralInput.vue` |
| `resources/js/Pages/Course/Show.vue` + `Components/Course/PriceDisplay.vue` | 銷售頁 | 新增「用 N 積分兌換」入口 |
| `resources/js/Components/MemberDetailModal.vue` | 會員詳情 modal | 積分區塊：派發表單 + 帳本明細 |

**潛在衝突 / 並存點**：
- 推薦碼系統與 **011 折扣碼** 完全獨立（不同欄位、不同表、不同 service）；結帳可同時套用，回饋以折後 `order.total_amount` 計。
- 與 **009 結帳**：推薦欄位掛在既有 order 生命週期，不改動金流路由。
- 與 **006 退款**：退款以 Purchase 為單位，但推薦回饋為 Order 級——退款需由 purchase 找回 order 處理。

**NEEDS CLARIFICATION**: 無（spec 撰寫前討論已全數拍板，未決處以 spec Assumptions 記錄）。

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| 原則 | 本計畫遵循方式 | 判定 |
|---|---|---|
| **I. Controller Layering** | 積分／推薦邏輯一律落在 Service；`MemberController` 派發只呼叫 `PointService`；`CheckoutController` 維持薄。 | ✅ PASS |
| **II. Service Encapsulation** | 新增 `PointService`（帳本唯一寫入點，含原子扣點、冪等）、`ReferralService`、`RedemptionService`；皆收 domain 物件、回傳結構化陣列、內含冪等檢查、不碰 `auth()`／`Request`。 | ✅ PASS |
| **III. Frontend Architecture** | 沿用 Inertia + 本地 state；新增 `ReferralInput.vue` 比照 `CouponInput.vue`；無 Pinia／axios／自訂 CSS。 | ✅ PASS |
| **IV. Model Conventions** | `PointTransaction` 用 `$fillable`、`casts()`、scope、type-hinted relations；只需 `created_at` → `$timestamps=false` + `boot()`（比照 `LessonProgress`/`AssignmentCompletion`）；`referral_code` 於 `booted()` 自動產生。 | ✅ PASS |
| **V. Job & Queue** | 本功能皆同步流程，無新 Job；發點在既有 transaction 內完成（與 `AssignmentService` 既有寫法一致）。 | ✅ PASS（無新增） |
| **VI. Email** | 不涉及新郵件。 | ✅ N/A |
| **VII. Error Handling** | Service 回 `['success'=>false,'error'=>'中文訊息']`；推薦碼驗證錯誤經 controller 轉 `withErrors`；扣點不足回結構化錯誤不丟例外。 | ✅ PASS |
| **VIII. Authorization** | 派發／帳本檢視在 `admin` middleware + `isAdmin()` 後；兌換在 `auth` middleware + 擁有權檢查；Service 收 `User` 參數不碰 `auth()`。 | ✅ PASS |
| **IX. Security** | 無敏感憑證；推薦碼為公開分享碼但唯一；防自薦在 service 層比對。 | ✅ PASS |
| **X. Simplicity & YAGNI** | 不引入新套件、不做全站帳本總覽（US 僅 per-member）、餘額用 `users.points` 快取避免過度設計；不加 Repository/DTO/Event。 | ✅ PASS |

**整體判定**：PASS，無違規，Complexity Tracking 免填。

唯一需論證的設計選擇：**為何新增 `point_transactions` 帳本而非沿用整數欄位**——因積分一旦可消耗且自金流交易賺取，必須具備稽核、防超扣、退款可逆三項，整數欄位無法滿足（見 research.md R1）。此非過度設計，而是需求本質要求，符合原則 X 的「完成需求所需」。

## Project Structure

### Documentation (this feature)

```text
specs/012-points-system/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── api.md           # Phase 1 output（routes + request/response 形狀）
└── tasks.md             # Phase 2 output（/speckit.tasks，本指令不產生）
```

### Source Code (repository root)

```text
app/
├── Models/
│   ├── PointTransaction.php          # NEW — 帳本
│   ├── User.php                      # ALTER — referral_code, referral_activated_at, pointTransactions()
│   ├── Course.php                    # ALTER — redeem_points, isRedeemable
│   ├── Order.php                     # ALTER — referrer_user_id, referral_rate, referral_reward_points
│   ├── Purchase.php                  # ALTER — 容納 source='points'
│   └── SiteSetting.php               # （沿用 get/set，無需改）
├── Services/
│   ├── PointService.php              # NEW — 帳本唯一寫入點：award / redeemDeduct / availableBalance / void
│   ├── ReferralService.php           # NEW — 推薦碼驗證 / 啟用判定 / 回饋計算與發放
│   ├── RedemptionService.php         # NEW — 課程兌換（原子扣點 + 建 Purchase）
│   ├── AssignmentService.php         # ALTER — 作業發點改走 PointService
│   ├── CheckoutService.php           # ALTER — 推薦碼快照 + fulfillOrder 發放回饋 + 重算資格
│   └── TransactionService.php        # ALTER — 退款窗口守門 + 作廢未成熟回饋
├── Console/Commands/
│   ├── MaturePoints.php              # NEW — points:mature（每日；成熟結算未成熟回饋）
│   └── ReconcilePoints.php           # NEW — points:reconcile（對帳快取 vs 帳本）
├── Http/Controllers/Member/
│   └── SettingsController.php        # ALTER — 作業歷程改讀帳本實際發點值
├── Http/
│   ├── Controllers/
│   │   ├── CheckoutController.php     # ALTER — referral_code 進出
│   │   ├── ReferralController.php     # NEW — 結帳前推薦碼即時驗證端點（比照 CouponController::apply）
│   │   ├── RedemptionController.php   # NEW — 課程兌換 action
│   │   ├── Member/PointController.php # NEW — 會員積分中心（餘額/明細/推薦碼）
│   │   └── Admin/
│   │       ├── MemberController.php   # ALTER — 派發積分 + 帳本明細
│   │       └── ReferralStatsController.php # NEW — 後台推薦成效統計
│   └── Requests/
│       ├── CheckoutRequest.php        # ALTER — referral_code rule
│       ├── ValidateReferralRequest.php# NEW
│       ├── RedeemCourseRequest.php    # NEW
│       └── Admin/GrantPointsRequest.php # NEW
database/
├── migrations/
│   ├── 2026_06_30_000001_create_point_transactions_table.php       # NEW
│   ├── 2026_06_30_000002_add_referral_fields_to_users_table.php    # NEW
│   ├── 2026_06_30_000003_add_redeem_points_to_courses_table.php    # NEW
│   └── 2026_06_30_000004_add_referral_fields_to_orders_table.php   # NEW
└── seeders/
    └── SiteSettingSeeder.php          # ALTER/NEW — 4 組積分設定預設值
resources/js/
├── Pages/
│   ├── Checkout/Index.vue             # ALTER — 嵌入 ReferralInput
│   ├── Course/Show.vue                # ALTER — 兌換入口
│   ├── Member/Points.vue             # NEW — 會員積分中心
│   └── Admin/Referrals/Index.vue     # NEW — 後台推薦統計
└── Components/
    ├── Cart/ReferralInput.vue         # NEW（比照 CouponInput.vue）
    ├── Course/RedeemButton.vue        # NEW
    └── MemberDetailModal.vue          # ALTER — 派發表單 + 帳本明細
routes/
└── web.php                            # ALTER — 新增 referral 驗證、兌換、積分中心、推薦統計、派發路由
```

**Structure Decision**: 沿用既有 Web（Laravel + Inertia/Vue）單一 repo 結構與命名慣例（`Admin\`/`Member\` 控制器命名空間、`Services/{Domain}Service`、`Pages/{Domain}`、`Components/{Context}`）。不新增頂層目錄。

## Complexity Tracking

> Constitution Check 無違規，本段免填。
