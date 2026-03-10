# Implementation Plan: 交易紀錄管理

**Branch**: `006-transactions-management` | **Date**: 2026-03-10 | **Spec**: [spec.md](./spec.md)
**Updated**: 2026-03-11 - 交易列表新增課程進度顯示（批次查詢 LessonProgress，透過 through() 注入）
**Input**: Feature specification from `/specs/006-transactions-management/spec.md`

## Summary

在管理後台新增交易紀錄的完整管理功能：分頁列表（含搜尋/篩選）、單筆詳情、手動新增（system_assigned/gift）、退款標記（撤銷存取）、批次勾選並匯出 CSV。後端以薄 Controller + TransactionService 實作，前端沿用 Inertia + Vue 3 local state 模式，完全利用現有 `purchases` 表。

## Technical Context

**Language/Version**: PHP 8.2+ / Laravel 12.x
**Primary Dependencies**: Inertia.js v2, Vue 3, Tailwind CSS v4
**Storage**: MySQL — 現有 `purchases` 表（無需新增 migration）
**Testing**: PHPUnit via `php artisan test`
**Target Platform**: Web (Laravel + Inertia SPA)
**Project Type**: Web application
**Performance Goals**: 列表 2s（10k 筆）、CSV 匯出 5s（1k 筆）
**Constraints**: 無 N+1、所有列表使用 `->paginate()`，CSV 使用 StreamedResponse 不快取整個結果集
**Scale/Scope**: 小型後台管理，預估交易筆數數千至數萬

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Controller Layering | ✅ PASS | TransactionController 薄層；手動新增與退款有跨模型副作用 → 委派 TransactionService |
| II. Service Layer | ✅ PASS | TransactionService 處理 createManual() 和 refund()；CSV 為 query-only，在 controller 中直接 StreamedResponse |
| III. Frontend Architecture | ✅ PASS | Vue 3 `<script setup>`，local ref/computed，無 Pinia，Inertia router |
| IV. Model Conventions | ✅ PASS | Purchase 模型已存在且符合規範；**需補 `source` 至 `$fillable`**（目前缺漏） |
| V. Job & Queue | ✅ PASS | 無非同步操作需求（CSV 同步 stream，退款為即時操作） |
| VIII. Authorization | ✅ PASS | 沿用 `auth` + `admin` middleware，無需新增 Policy |
| X. Simplicity | ✅ PASS | 無 Repository、DTO、Event/Listener；直接 Eloquent |

**No violations. Proceeding.**

## Project Structure

### Documentation (this feature)

```text
specs/006-transactions-management/
├── plan.md              ← this file
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── transactions.md
└── tasks.md             ← /speckit.tasks output (not yet created)
```

### Source Code

```text
app/
├── Http/
│   ├── Controllers/Admin/
│   │   └── TransactionController.php    (new)
│   └── Requests/Admin/
│       └── StoreTransactionRequest.php  (new)
├── Services/
│   └── TransactionService.php           (new)
└── Models/
    └── Purchase.php                     (patch: add 'source' to $fillable)

resources/js/
├── Pages/Admin/Transactions/
│   ├── Index.vue                        (new)
│   └── Show.vue                         (new)
└── Components/Admin/                    (existing dir)
    └── TransactionRefundModal.vue       (new — confirm dialog)

routes/
└── web.php                              (patch: add transactions routes)
```

**Structure Decision**: Web application pattern。後端 `Admin/` namespace，前端 `Pages/Admin/Transactions/`，與現有 Members、Courses 保持一致。

## Complexity Tracking

> No violations to justify.

## Incremental Update Summary

---

### 2026-03-11: 交易列表新增課程進度顯示

**背景**：管理員在交易列表查看時無法快速得知會員的學習狀況，需要進入詳情頁才能判斷；加入進度顯示讓列表頁即可掌握關鍵資訊。

**修改檔案**：
- `app/Http/Controllers/Admin/TransactionController.php` - index() 新增 `course.lessons:id,course_id` eager load；分頁後批次查詢 `lesson_progress`，用 `through()` 將 `progress_completed` / `progress_total` 注入各 paginator item
- `resources/js/Pages/Admin/Transactions/Index.vue` - 課程欄位 td 新增進度條（indigo，`h-1.5`）與「X/Y 課」文字；若課程無課程內容則顯示「（無課程內容）」

**設計決策**：
- 批次查詢策略：收集當頁所有 user_id + lesson_id，發出**單一**額外查詢 `LessonProgress whereIn user_id whereIn lesson_id`，避免 N+1
- 使用 `paginator->through()` 在 Inertia render 前注入進度欄位，保持 paginator 結構不變（分頁 meta 完整保留）
- 前端只接收 `progress_completed`、`progress_total` 兩個整數，百分比在 template 中計算，不在後端預先計算

**影響元素**：
1. 課程欄位進度條 — `progress_completed / progress_total * 100`，用 inline style 控制寬度
2. 課程欄位進度文字 — `{{ progress_completed }}/{{ progress_total }} 課`
3. 若 `progress_total === 0`（課程尚無內容）— 顯示灰色「（無課程內容）」提示
