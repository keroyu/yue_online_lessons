# Implementation Plan: 折扣碼管理系統

**Branch**: `011-discount-coupon` | **Date**: 2026-06-09 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/011-discount-coupon/spec.md`

## Summary

新增一套折扣碼（優惠碼）系統，整合進現有 009-cart-checkout 結帳流程。後台提供折扣碼 CRUD、啟用/停用、軟刪除與成效統計（時間範圍含 7/30/60/90 天及「全部」）；前台在購物車頁套用單一折扣碼（fixed 固定折抵 / ratio 折數），結帳時以折後金額建立訂單，並於付款 webhook 確認後才累計使用次數。另支援分享連結（`/courses/xxx?coupon=CODE`）自動帶入折扣碼，沿用既有 traffic_source 的 session 歸因機制（US5）。

技術取向：完全沿用現有架構 pattern——薄 Controller + `App\Services\CouponService` 封裝商業規則、Eloquent Model（`$fillable` + `casts()` + scope + SoftDeletes）、Form Request 驗證、Inertia 手動塑形 props、Vue 3 `<script setup>` + 元件化。折扣計算與驗證單一真實來源集中於 `CouponService`，供購物車驗證端點、結帳建單、webhook 兌現三處共用，確保行為一致。

## Technical Context

**Language/Version**: PHP 8.2 / Laravel 12
**Primary Dependencies**: Inertia.js v2, Vue 3 (`<script setup>`), Tailwind CSS v4（皆為現有依賴，無新增套件）
**Storage**: MySQL — 1 張新表 `coupon_codes`（含 `deleted_at` 軟刪除）；`orders` 表 alter 新增 3 欄；`purchases` 表沿用既有 `coupon_code` / `discount_amount` 欄（無需 migration）
**Testing**: `php artisan test`（PHPUnit / Pest，依現有專案設定）
**Target Platform**: Laravel Forge（Linux server）
**Project Type**: Web（Laravel monolith + Inertia/Vue SPA）
**Performance Goals**: 套用折扣碼驗證 < 3 秒回應（SC-001）；後台統計切換 < 3 秒（SC-005）——皆為單表查詢，無效能風險
**Constraints**: 折後實付金額下限 NT$1（金流限制）；ratio 四捨五入至整數元；used_count 僅於付款確認後 +1；同一 IP 驗證失敗 5 次後節流 60 秒
**Scale/Scope**: 小型營運規模；折扣碼數量數十至數百筆；統計查詢以 `orders` 表（已 indexed）為準

**現有整合點（來自 repo_map → 009-cart-checkout）**：
- `app/Services/CheckoutService.php` — `createOrder()` 增加 `?string $couponCode` 參數；`fulfillOrder()` 兌現折扣碼
- `app/Http/Controllers/CheckoutController.php` — `initiate()` 透傳 coupon_code；`show()` 顯示折扣摘要
- `app/Http/Controllers/CartController.php` — 同 `api` 群組新增 apply-coupon 端點（或獨立 controller）；`index()` 讀 session 折扣碼塑形 `prefillCouponCode`
- `app/Http/Controllers/CourseController.php` — `show()` 既有 `traffic_source` 捕捉處並排捕捉 `?coupon=` 至 session（FR-020 / US5）
- `resources/js/Pages/Cart/Index.vue`、`resources/js/Pages/Checkout/Index.vue` — 折扣 UI（含自動帶入）
- `app/Models/Order.php`、`app/Models/Purchase.php` — `$fillable` 擴充

## Constitution Check

*GATE: 對照 `.specify/memory/constitution.md`（v2.0.0）逐條檢核。*

| 原則 | 檢核 | 結果 |
|------|------|------|
| **I. Controller Layering** | Admin `CouponController` 走 Form Request；CRUD 本身單表，但驗證/兌現/統計屬跨模型 + 副作用 → 委派 `CouponService`。前台 apply-coupon controller 薄殼呼叫 service。 | ✅ Pass |
| **II. Service Encapsulation** | `CouponService` 封裝驗證（多規則）、`redeem`（used_count 副作用 + purchases 寫入）、統計聚合。回傳結構化陣列 `['success'=>bool,'error'=>string]`；不碰 `auth()`/`Request`，以 `User`/primitive 參數傳入。 | ✅ Pass |
| **III. Frontend Architecture** | Vue 3 `<script setup>`；新增 `Components/Cart/CouponInput.vue`、`Components/Admin/CouponForm.vue`；折扣碼套用狀態存 `ref`（spec 要求不持久化）；Tailwind only；mobile-first。 | ✅ Pass |
| **IV. Model Conventions** | `CouponCode` 使用 `$fillable`、`casts()`、`scopeActive`、`isExpired()`/`isExhausted()`、`course()` 關聯。SoftDeletes 屬「explicitly required」（FR-016）允許新增。 | ✅ Pass |
| **V. Job & Queue** | 無非同步工作；不需 Job。 | ✅ N/A |
| **VI. Email** | 本功能不寄信。 | ✅ N/A |
| **VII. Error Handling** | Service 以結構化陣列回傳業務錯誤（中文）；Controller 轉 `withErrors()` / JSON。結帳最終驗證失效 → `RuntimeException` 由 `CheckoutController` catch 回 409（沿用既有 pattern）。 | ✅ Pass |
| **VIII. Authorization** | Admin 路由 `['auth','admin']` middleware；apply-coupon 公開（支援 guest 結帳，與 checkout 一致）。 | ✅ Pass |
| **IX. Security** | apply-coupon 端點對同一 IP 失敗節流（`RateLimiter`，FR-019），防短碼暴力枚舉。 | ✅ Pass |
| **X. Simplicity & YAGNI** | 不引入新 pattern（無 Repository / DTO / Event）；沿用 manual prop shaping、resource route、Service 結構化回傳。 | ✅ Pass |

**Gate 結果：通過，無違規，無需 Complexity Tracking。**

## Project Structure

### Documentation (this feature)

```text
specs/011-discount-coupon/
├── plan.md              # 本檔
├── research.md          # Phase 0：設計決策
├── data-model.md        # Phase 1：資料表 + Model + Service 介面
├── quickstart.md        # Phase 1：開發者驗收路徑
├── contracts/
│   └── api.md           # Phase 1：路由與請求/回應契約
├── checklists/
│   └── requirements.md  # （已存在）spec 品質檢核
└── tasks.md             # Phase 2：由 /speckit.tasks 產生（本指令不建立）
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── CouponController.php                # 前台 apply-coupon（公開，guest 可用）
│   │   ├── CourseController.php                # show() 捕捉 ?coupon= 至 session（US5）
│   │   ├── CartController.php                  # index() 塑形 prefillCouponCode（US5）
│   │   └── Admin/
│   │       └── CouponController.php            # 後台 CRUD + toggle + stats（含「全部」期間）
│   └── Requests/
│       └── Admin/
│           ├── StoreCouponRequest.php          # 新增驗證（code/type/value/...）
│           └── UpdateCouponRequest.php         # 編輯驗證
├── Models/
│   ├── CouponCode.php                          # 新 Model（SoftDeletes）
│   ├── Order.php                               # $fillable + casts 擴充
│   └── Purchase.php                            # （既有欄位，確認 $fillable 含 coupon_code/discount_amount）
└── Services/
    ├── CouponService.php                       # 驗證 / 計算 / 兌現 / 統計（核心）
    └── CheckoutService.php                     # createOrder()/fulfillOrder() 整合折扣

database/migrations/
├── 2026_06_09_000001_create_coupon_codes_table.php
└── 2026_06_09_000002_add_discount_columns_to_orders_table.php

resources/js/
├── Components/
│   ├── Cart/
│   │   └── CouponInput.vue                     # 折扣碼輸入/套用/移除（前台共用）
│   └── Admin/
│       └── CouponForm.vue                      # 後台新增/編輯共用表單
└── Pages/
    ├── Cart/Index.vue                          # 整合 CouponInput + 折扣行
    ├── Checkout/Index.vue                      # 折扣摘要 + initiate 帶 coupon_code
    └── Admin/Coupons/
        ├── Index.vue                           # 列表（toggle/刪除/連結）
        ├── Create.vue                          # 新增（用 CouponForm）
        ├── Edit.vue                            # 編輯（用 CouponForm）
        └── Show.vue                            # 統計頁（7/30/60/90 天 + 明細）

routes/web.php                                  # 新增 api.cart.apply-coupon + admin.coupons.*
```

**Structure Decision**: 沿用現有 Laravel monolith + Inertia/Vue 結構。後台採 `Route::resource('coupons', ...)` + 兩條補充路由（toggle、stats），與既有 `Admin\TransactionController`、`Admin\CourseController` 一致。前台折扣碼套用端點放入既有 `api` 群組（公開，與 checkout 同樣支援 guest）。`CouponService` 為單一真實來源，三個呼叫點（前台驗證、結帳建單、webhook 兌現）共用同一計算邏輯。

## Complexity Tracking

> 無 Constitution 違規，本節不適用。
