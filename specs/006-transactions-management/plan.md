# Implementation Plan: 交易紀錄管理（含營收圖表）

**Branch**: `006-transactions-management` | **Date**: 2026-03-11 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/006-transactions-management/spec.md`

## Summary

在管理後台建立交易紀錄的完整檢視與管理功能（列表、詳情、手動新增、退款），並在列表頁上方新增「營收圖表」區塊（每日銷售額柱狀 + 每日銷售量折線雙軸圖），支援時間區間篩選（過去 7 / 30 / 90 天、自訂）。圖表透過 Inertia partial reload 動態更新，不重新載入交易列表。

## Technical Context

**Language/Version**: PHP 8.2 / Laravel 12
**Primary Dependencies**: Inertia.js v2, Vue 3 + `<script setup>`, Tailwind CSS v4, `chart.js` + `vue-chartjs`（新增）
**Storage**: MySQL — 現有 `purchases` 表 + 009 新增的 `orders` 表（透過 `purchases.order_id` 關聯，無新增 migration）
**Testing**: `php artisan test`（PHPUnit）
**Target Platform**: Web browser（管理後台，桌面優先，支援 RWD）
**Performance Goals**: 圖表資料在切換區間後 1 秒內更新（SC-009）；列表 10,000 筆 2 秒內載入（SC-002）
**Constraints**: 不引入 Axios / Pinia / API Resource；Inertia partial reload 替代 AJAX；chart.js 樹搖（只 import 用到的模組）
**Scale/Scope**: 單一管理後台頁面；預計 transactions 數萬筆規模

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Controller Layering | ✅ PASS | `TransactionController` 為薄控制器；複雜業務邏輯（createManual, refund）交給 `TransactionService` |
| II. Service Encapsulation | ✅ PASS | `TransactionService` 包含跨模型操作（唯一性檢查 + status 轉換）；chart 資料為 query-only，留在 controller |
| III. Frontend Architecture | ✅ PASS | Vue 3 `<script setup>`；Inertia props 手動 shape；local `ref` 狀態；partial reload 替代 axios；`RevenueChart.vue` 為獨立元件 |
| IV. Model Conventions | ✅ PASS | `Purchase` 使用 `$fillable`；沿用現有 casts；不新增 SoftDeletes |
| V. Job & Queue | ✅ PASS | 無非同步操作需要 Job |
| VI. Email Delivery | ✅ PASS | 本功能無 email 傳送 |
| VII. Error Handling | ✅ PASS | Service 回傳 `['success' => bool, 'error' => string]`；Controller 轉換為 `withErrors` / `with('error')` |
| VIII. Authorization | ✅ PASS | `admin` middleware 守護所有路由；controller 不另加 Policy（沿用現有 inline 模式） |
| IX. Security | ✅ PASS | 所有操作需管理員身份；無敏感資料暴露；CSV 匯出不含密碼等欄位 |
| X. Simplicity & YAGNI | ✅ PASS | 引入 chart.js 為最輕量選擇（R-007）；無 Repository / DTO / Event；chart 資料計算在 controller 完成，不再抽 Service |

**No violations. No Complexity Tracking required.**

## Project Structure

### Documentation (this feature)

```text
specs/006-transactions-management/
├── plan.md              ← 本文件
├── spec.md
├── research.md          ← R-001~R-008（含圖表研究）
├── data-model.md        ← Purchase schema + chart query + frontend props
├── quickstart.md        ← Step 1-13（含圖表安裝與實作步驟）
├── contracts/
│   └── transactions.md  ← 含 chartData/chartFilters props 合約
└── tasks.md             ← 由 /speckit.tasks 產生
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── Admin/
│   │       └── TransactionController.php  ← index/show/store/refund/export + chart data
│   └── Requests/
│       └── Admin/
│           └── StoreTransactionRequest.php
└── Services/
    └── TransactionService.php             ← createManual + refund

resources/js/
├── Pages/
│   └── Admin/
│       └── Transactions/
│           ├── Index.vue                  ← 列表 + 圖表 + CSV 匯出
│           └── Show.vue                   ← 詳情 + 退款按鈕
└── Components/
    └── Admin/
        ├── TransactionRefundModal.vue
        └── RevenueChart.vue               ← 新增：雙軸柱狀+折線圖

routes/web.php                             ← 5 個 admin 路由
package.json                               ← 新增 chart.js + vue-chartjs
```

**Structure Decision**: Web application（Laravel backend + Inertia/Vue frontend），沿用現有 `Admin\` namespace 慣例。

## Implementation Phases

### Phase A — Core Transaction Management（已完成大部分）

已實作：
- `TransactionController` index / show / store / refund / export
- `TransactionService` createManual / refund
- `StoreTransactionRequest`
- `Index.vue`（列表、篩選、checkbox、CSV 匯出、課程進度、快捷退款按鈕）
- `Show.vue`（詳情頁）
- `TransactionRefundModal.vue`

### Phase B — Revenue Chart ✅ 已完成（2026-03-11）

1. **套件安裝** ✅ — `npm install chart.js vue-chartjs`
2. **後端：TransactionController::index() 補充 chart props** ✅
   - 解析 `chart_range / chart_start / chart_end` 參數
   - 執行 GROUP BY date 查詢（UTC+8 時區轉換）
   - CarbonPeriod 補齊無資料日期
   - 計算 `total_amount / total_count`，加入 `chartData` / `chartFilters` 至 Inertia props
3. **前端：RevenueChart.vue** ✅
   - 雙軸 mixed chart（bar: 銷售額左軸 teal，line: 銷售量右軸 blue）
   - 統計卡片（區間總銷售量、區間總銷售額千分位）
   - 時間區間 dropdown（7d / 30d / 90d / 自訂），預設 30d
   - 自訂模式起訖日期輸入欄（MM/DD/YYYY）
   - **高度固定 360px**（`maintainAspectRatio: false` + 外層 `style="height:360px"`）
   - **右 Y 軸整數刻度**：`beginAtZero: true` + `stepSize: 1` + `precision: 0`
4. **前端：Index.vue 整合** ✅
   - 掛載 `<RevenueChart>` + 監聽 events → Inertia partial reload（`only: ['chartData', 'chartFilters']`）

### Phase C — 009 整合：訂單資料顯示 + 搜尋 + 匯出 + Badge UI

**背景**: 009 購物車結帳功能上線後，透過 PayUni / NewebPay 完成的交易會在 `orders` 表建立紀錄（merchant_order_no、gateway_trade_no、payment_gateway），並以 `purchases.order_id` 關聯。現有 006 後台看不到這些欄位。

**Affected Files**:

```text
app/Http/Controllers/Admin/TransactionController.php
resources/js/Pages/Admin/Transactions/Index.vue
resources/js/Pages/Admin/Transactions/Show.vue
```

**Tasks**:

1. **後端 — TransactionController::show()** (FR-030)
   - 加入 `->with('order')` eager load
   - Inertia props 新增 `order_info` 物件：
     - `merchant_order_no`: `$purchase->order?->merchant_order_no ?? null`
     - `gateway_trade_no`: `$purchase->order?->gateway_trade_no ?? null`
     - `payment_gateway`: `$purchase->order?->payment_gateway ?? null`

2. **後端 — TransactionController::index() 搜尋擴展** (FR-031)
   - 現有 `->where(fn => $q->where('buyer_email', 'like', ...)->orWhere('portaly_order_id', 'like', ...))`
   - 追加 `->orWhereHas('order', fn($q) => $q->where('merchant_order_no', 'like', "%{$search}%"))`
   - index() 的 `->with(...)` 補上 `order`（only 取 merchant_order_no 用於列表 badge）

3. **後端 — TransactionController::export()** (FR-032)
   - eager load 補上 `order`
   - CSV header 新增 `merchant_order_no`（置於 `portaly_order_id` 前）
   - 每列填入 `$purchase->order?->merchant_order_no ?? ''`

4. **前端 — Index.vue 訂單 Badge** (FR-033, FR-034)
   - 訂單欄位由純文字 ID 改為 badge 元件
   - Badge 顯示規則：
     - `source = 'portaly'` → 灰藍色 badge `[Portaly]`，複製 `portaly_order_id`
     - `source = 'payuni'` → 品牌色 badge `[PayUni]`，複製 `merchant_order_no`（來自 `order.merchant_order_no`）
     - `source = 'newebpay'` → badge `[NewebPay]`，複製 `merchant_order_no`
     - `source = 'manual'` 或無訂單編號 → 顯示「—」，無 badge
   - 點擊 badge 觸發 `navigator.clipboard.writeText()`，成功後 200ms 顯示「已複製 ✓」tooltip/提示，然後恢復原樣
   - 完整訂單編號以 `title` 屬性（tooltip）顯示，badge 內只顯示標籤文字

5. **前端 — Show.vue 購物車訂單資訊區塊** (FR-030)
   - 判斷 `order_info.merchant_order_no` 存在時，顯示「購物車訂單資訊」卡片區塊
   - 區塊包含：商店訂單編號（merchant_order_no）、金流交易序號（gateway_trade_no）、金流渠道（payment_gateway 以對應中文顯示）
   - Portaly 來源：不顯示此區塊（或 merchant_order_no 為 null 時隱藏）

**Purchase Model**（確認）:
- `order()` belongsTo 關聯應已存在（009 建立）；若尚未，需在 `Purchase` model 新增 `public function order(): BelongsTo`

## Dependencies & Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| `CONVERT_TZ` 在 MySQL strict mode 下對 NULL 值行為 | 低 | `created_at` 不允許 null，可忽略 |
| 自訂日期區間過長（如數年）導致查詢慢 | 低 | 無上限限制（per Assumption），但 GROUP BY + index on `created_at` 足夠快 |
| chart.js bundle size | 低 | 只 import 使用的模組（tree-shaking） |
| Inertia partial reload 與現有列表篩選 params 衝突 | 中 | 使用 `URLSearchParams` 保留現有 params，只覆寫 chart_* params |
| `Purchase::order()` 關聯尚未建立（009 可能未加） | 低 | 實作前先確認；若缺少，在 Phase C Task 1 補上 `belongsTo(Order::class)` |
| index() N+1：列表每列 eager load order | 低 | `with('order:id,merchant_order_no')` 限制欄位；列表每頁 20 筆，影響可忽略 |
| Clipboard API 在 http:// 本機開發環境可能受限 | 低 | 本機用 localhost（Chrome 允許），staging/prod 為 https；若不支援則靜默失敗 |

---

## Incremental Update Summary

### 2026-05-07: Phase C — 009 整合規劃

**背景**: 009 購物車結帳上線，新增 `orders` 表；006 需更新以顯示購物車訂單資料、擴展搜尋、更新 CSV，並重新設計列表訂單欄位為 badge + 點擊複製。

**規劃範圍**:
- 詳情頁（Show.vue + TransactionController::show）：顯示 merchant_order_no / gateway_trade_no / payment_gateway
- 列表搜尋（index()）：擴展 merchant_order_no 搜尋
- CSV 匯出（export()）：新增 merchant_order_no 欄
- 列表 UI（Index.vue）：訂單 ID 欄位改為金流來源 badge（Portaly / PayUni / NewebPay）+ 點擊複製

**新增 FR**: FR-030（詳情擴展）、FR-031（搜尋擴展）、FR-032（CSV）、FR-033（badge UI）、FR-034（複製功能）

---

### 2026-03-11: Phase 12 — 營收圖表實作完成（含 UI 修正）

**背景**：Phase 12（T029–T032）本次全部完成，並在初版實作後依視覺回饋做了兩輪修正：高度過高 → 限制 360px；右 Y 軸出現小數刻度（資料稀少時 Chart.js 預設行為）→ 強制整數。

**修改檔案**：
- `app/Http/Controllers/Admin/TransactionController.php` — `index()` 新增 chart 查詢邏輯（chart_range 解析、GROUP BY date、CarbonPeriod 零值補齊、chartData/chartFilters props）
- `resources/js/Components/Admin/RevenueChart.vue` — 全新元件；統計卡片 + mixed bar/line chart + 時間篩選器
- `resources/js/Pages/Admin/Transactions/Index.vue` — 新增 chartData/chartFilters props，掛載 RevenueChart，處理 partial reload events
- `package.json` — 新增 `chart.js`、`vue-chartjs` 依賴

**設計決策**：
- **圖表高度固定 360px**：外層容器設 `height: 360px`，Chart.js 設 `maintainAspectRatio: false` 讓畫布填滿容器，避免預設 aspect ratio 導致高度隨寬度無限增長。
- **右 Y 軸整數刻度**：`beginAtZero: true` 確保從 0 開始；`stepSize: 1` + `precision: 0` 確保即使 max 為 0 或 1 時也只顯示整數刻度，不出現 0.1 等小數。
- **圖表呈現方式**：柱狀圖（銷售額 teal `#2dd4bf`）搭配折線圖（銷售量 blue `#93c5fd`），左右雙 Y 軸；圖例置於底部。
