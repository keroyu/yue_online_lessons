# Implementation Plan: 交易紀錄管理

**Branch**: `006-transactions-management` | **Date**: 2026-03-11 | **Spec**: [spec.md](./spec.md)
**Input**: 增量更新 — 在交易列表「查看」按鈕左側新增「標記退款」快捷按鈕

## Summary

交易列表每列操作欄目前只有「查看」連結。本次增量更新在其左側新增「標記退款」按鈕，使管理員可直接從列表操作退款，無需進入詳情頁。後端 `POST /admin/transactions/{transaction}/refund` 路由與 `TransactionService::refund()` 均已實作，本次變更集中在前端 `Index.vue`。

## Technical Context

**Language/Version**: PHP 8.2 / Laravel 12, Vue 3 (Composition API `<script setup>`)
**Primary Dependencies**: Inertia.js v2, Tailwind CSS v4
**Storage**: MySQL — 現有 `purchases` 表（`status` 欄位）
**Testing**: `php artisan test` (PHPUnit)
**Target Platform**: Web (admin backend, desktop-first with RWD)
**Performance Goals**: 確認對話框出現 < 100ms；退款請求完成後列表即時反映狀態
**Constraints**: 只有 `status = 'paid'` 的交易才顯示退款按鈕；`system_assigned` / `gift` 類型同樣可退款（條件僅看 status）
**Scale/Scope**: 單一 Vue 頁面改動 (`Index.vue`)

## Constitution Check

| Principle | Status | Note |
|-----------|--------|------|
| I. Controller Layering | ✅ Pass | `refund()` 已委派給 `TransactionService::refund()`，不在此次改動範圍 |
| II. Service Business Logic | ✅ Pass | Service 已存在，本次不新增後端邏輯 |
| III. Frontend Component Architecture | ✅ Pass | 使用 `router.post()` (Inertia) + 本地 `ref` 管理確認狀態 |
| VIII. Authorization | ✅ Pass | 路由已套用 `auth` + `admin` middleware |
| X. Simplicity & YAGNI | ✅ Pass | 最小改動：僅在 Index.vue 列操作欄新增按鈕與確認邏輯 |

## Project Structure

### Documentation (this feature)

```text
specs/006-transactions-management/
├── plan.md              # This file
├── research.md          # Phase 0 — existing
├── data-model.md        # existing
├── quickstart.md        # existing
├── contracts/
│   └── transactions.md  # existing (no change needed)
└── tasks.md             # Phase 2 output
```

### Source Code (changed files only)

```text
resources/js/Pages/Admin/Transactions/
└── Index.vue            # [CHANGE] 操作欄新增「標記退款」按鈕
```

**No backend changes needed** — `TransactionController::refund()` 和路由已實作完畢。

## Design Decisions

### D-001: 確認方式

使用原生 `window.confirm()` 彈窗（與現有 `Admin/Members/Index.vue` 中退款/撤銷操作保持一致）。不引入新的 Modal 元件。

### D-002: 按鈕顯示條件

`v-if="transaction.status === 'paid'"` — 只有付款中（paid）才顯示，退款後按鈕消失。

### D-003: Inertia 提交方式

使用 `router.post(\`/admin/transactions/\${transaction.id}/refund\`)` 搭配 `preserveScroll: true`，提交後 Inertia 重新載入列表，列表狀態自動反映。

### D-004: 按鈕樣式

紅色文字（`text-red-600 hover:text-red-900`），與「查看」（indigo）視覺上明確區分，搭配 `mr-3` 間距。

### D-005: 按鈕游標

加上 `cursor-pointer`，確保 hover 時游標與 `<Link>` 元件（`查看`）一致，皆顯示指針游標。`<button>` 元素在瀏覽器預設下游標為 `default`，需明確指定。

### D-006: 金額格式化

使用 `formatAmount(currency, amount)` helper（`Number(amount).toFixed(2)`），確保金額固定顯示兩位小數（如 `TWD 1200.00`）。Laravel `decimal:2` cast 回傳字串，但若 DB 存整數舊資料仍可能缺小數位，統一在前端格式化最為穩定。`Index.vue` 與 `Show.vue` 各自定義同名 helper。

## Complexity Tracking

無憲法違規，不需要此區塊。
