# Research: 交易紀錄管理

**Phase**: 0 — Unknowns Resolution
**Date**: 2026-03-10

---

## R-001: CSV 匯出策略（大量資料）

**Decision**: 使用 Laravel `StreamedResponse` + `fputcsv()` 逐行寫出，不一次 `->get()` 整個結果集。

**Rationale**:
- `StreamedResponse` 讓 PHP 邊查邊輸出，記憶體用量固定（`->chunk(200)` 逐批讀取）
- 不需要暫存檔，不需要 Job/Queue（同步，適合管理後台低頻操作）
- Laravel 內建 `response()->streamDownload()` 可直接指定 Content-Disposition filename

**Pattern**:
```php
return response()->streamDownload(function () use ($query) {
    $handle = fopen('php://output', 'w');
    fputcsv($handle, ['訂單 ID', 'Portaly 訂單編號', ...]);  // header
    $query->chunk(200, function ($purchases) use ($handle) {
        foreach ($purchases as $p) {
            fputcsv($handle, [$p->id, $p->portaly_order_id, ...]);
        }
    });
    fclose($handle);
}, "transactions-{$date}.csv");
```

**Alternatives considered**:
- `->get()` 整批取出後 implode：記憶體不可控，5k 筆以上有 OOM 風險 → 棄用
- Queue + 下載連結：過度設計，管理後台低頻 → 棄用
- Laravel Excel / Maatwebsite：外部套件，違反 X.Simplicity → 棄用

---

## R-002: 跨頁全選實作方式

**Decision**: 沿用 `MemberController` 的 `selectedIds` + `matchingCount` 模式；CSV 匯出時若為「全選符合條件」則以 filter params 重新查詢，不傳所有 ID。

**Rationale**:
- `MemberController::index()` 已有 `selectedIds` 和 `matchingCount` prop，前端 `Admin/Members/Index.vue` 已實作跨頁全選 UI
- 傳遞幾千個 ID 至 POST body 可行但不優雅；改為傳 filter params（`status`, `type`, `course_id`, `search`）讓後端重新查詢更乾淨
- CSV 匯出使用 GET 請求：`?export=csv&ids[]=1&ids[]=2` 或 `?export=csv&select_all=true&status=paid&course_id=3`

**Alternatives considered**:
- 傳遞所有 ID 陣列：URL 長度限制（GET 約 2000 chars），若改 POST 則需要 CSRF 處理 → filter params 更優雅
- Session 儲存選取狀態：Server-side 狀態管理複雜化 → 棄用

---

## R-003: 手動新增交易的課程存取控制

**Decision**: 手動新增（system_assigned / gift）的交易 status 為 `paid`，amount 為 `0`。存取控制沿用現有 Purchase 存在即有效的判斷邏輯（`purchases` 表有 paid 紀錄即可進入教室），無需額外欄位。

**Rationale**:
- 現有 `Member\ClassroomController` 檢查 `$user->purchases()->where('course_id', ...)->where('status', 'paid')->exists()`
- 新建一筆 status=paid、amount=0 的 Purchase 即自動授予存取，不需改動已有存取邏輯
- `source` 欄位記錄 `'manual'`，`type` 記錄 `'system_assigned'` 或 `'gift'`

**Alternatives considered**:
- 獨立的「課程授權」表：過度設計 → 棄用
- Boolean `is_manual` 欄位：`type` 欄位已足夠區分 → 棄用

---

## R-004: 退款操作撤銷存取

**Decision**: 退款 = 將該筆 Purchase 的 `status` 更新為 `refunded`。現有存取控制查詢 `where('status', 'paid')`，改為 `refunded` 後自動失效，無需刪除紀錄。

**Rationale**:
- 保留紀錄供對帳與審計追蹤（刪除會丟失歷史）
- `status` 的語義已明確（`paid` = 有效，`refunded` = 已退款）
- 不需要新增欄位或 SoftDelete

**Alternatives considered**:
- 刪除 Purchase 紀錄：失去歷史紀錄 → 棄用
- 新增 `revoked_at` 欄位：`status` 已足夠 → 棄用

---

## R-005: TransactionService 邊界判斷

**Decision**: 建立 `App\Services\TransactionService`，包含：
- `createManual(User $user, Course $course, string $type, ?string $note): array` — 跨 Purchase 唯一性檢查 + create
- `refund(Purchase $purchase): array` — 更新 status

CSV 匯出為 query-only，直接在 `TransactionController::export()` 中實作 StreamedResponse，不抽出 Service。

**Rationale**:
- `createManual()` 涉及唯一性檢查（`Purchase::where([...])->exists()`）+ 建立，符合 Constitution II「跨兩個以上 Model 或有 side effect」判斷
- `refund()` 只更新單一 Purchase，理論上可在 controller 直接做；但作為狀態轉換操作（含日誌），放在 Service 更一致
- CSV export 不涉及 side effects，controller 可直接處理

---

## R-006: Purchase.$fillable 缺漏 `source` 欄位

**Finding**: 現有 `Purchase` 模型的 `$fillable` 陣列缺少 `source` 欄位（資料庫有此欄位，migration 已建立）。

**Impact**: 任何 `Purchase::create(['source' => ...])` 或 `$purchase->update(['source' => ...])` 呼叫會被 mass-assignment 保護靜默忽略，導致 `source` 永遠寫不進去。

**Fix**: 在 `Purchase.php` 的 `$fillable` 中補上 `'source'`。此為現有 bug，應在 Task 清單中優先修正。

---

## R-007: 營收圖表 — 前端圖表套件選擇

**Decision**: 使用 `chart.js` + `vue-chartjs`（Vue 3 官方包裝）實作雙軸混合圖表（柱狀 + 折線）。

**Rationale**:
- 專案目前無任何圖表套件；需從零引入
- Chart.js 支援 mixed chart type（bar + line 同一圖）及雙 Y 軸，原生功能完整
- `vue-chartjs` 提供 Vue 3 Composition API 包裝，使用 `<Bar>` component + `data`/`options` props，符合 Constitution III 的 Vue 3 `<script setup>` 規範
- 套件體積小（Chart.js ~60KB gzip），不影響管理後台載入速度

**Installation**:
```bash
npm install chart.js vue-chartjs
```

**Alternatives considered**:
- Apache ECharts / vue-echarts：功能更強大但體積較大（~300KB），超出需求 → 棄用
- Recharts：React 生態，無 Vue 版本 → 棄用
- 純 SVG 手寫：工程量過大，維護困難 → 棄用
- uPlot：API 較底層，需自行處理雙軸 → 棄用

---

## R-008: 營收圖表 — 資料取得策略（後端 vs 前端狀態）

**Decision**: 將圖表資料（`chartData`）與圖表篩選條件（`chartFilters`）作為 prop 加入現有 Index 頁面，透過 Inertia `router.visit()` 的 `only: ['chartData', 'chartFilters']` 做局部更新（partial reload），切換時間區間時不重新載入整個頁面。

**Rationale**:
- Constitution III 明確「No Axios for data fetching (use Inertia router)」；Inertia v2 partial reload 是符合規範的動態資料更新方式
- 圖表資料與列表使用相同 URL（`/admin/transactions?chart_range=30d&...`），方便書籤與分享
- `only: ['chartData', 'chartFilters']` 讓後端只計算圖表 props，不重新查詢 transactions 分頁列表，效能可接受

**Chart data URL params**:

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `chart_range` | `7d\|30d\|90d\|custom` | `30d` | 預設區間 |
| `chart_start` | `YYYY-MM-DD` | 30天前 | 自訂起始日（chart_range=custom 時有效） |
| `chart_end` | `YYYY-MM-DD` | 今天 | 自訂結束日（chart_range=custom 時有效） |

**Alternatives considered**:
- 獨立 JSON API 端點 + fetch：違反 Constitution III，需引入 axios 或 native fetch → 棄用
- Livewire/Alpine：專案未使用此技術 → 棄用
- 在頁面初始載入時一次傳入所有區間資料：payload 過大 → 棄用
