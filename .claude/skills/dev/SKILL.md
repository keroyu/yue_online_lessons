---
name: dev
description: 依已審核的 spec Tasks 執行實作，勾選進度並寫進度日誌（取代 speckit implement）。觸發詞：/dev 或 /dev <模組id>。跨 session 可續跑，只吃 status:building 的模組。
---

# /dev — 依 spec 實作

讀取領域模組 spec 的未完成 Tasks，實作、勾選、記日誌。進度存在 spec 本身，換 session 也能接續。

## 步驟

### 1. 找目標

- 有參數（如 `/dev 007` 或 `/dev 007-points-referral`）：直接開該模組 spec。
- 無參數：掃 `specs/*/spec.md` frontmatter，找 `status: building` 的模組；多個就列出請使用者選；一個都沒有就回報「沒有進行中的方案」並建議先跑 `/spec`。

### 2. 檢查放行狀態

- `status: draft` → **停**。提醒使用者方案尚未確認，請先回 `/spec` 流程審核。不可自行翻成 building。
- `status: building` → 繼續。
- `status: done` 且無未勾選 tasks → 回報已完成，無事可做。

### 3. 實作循環

1. 讀 spec 的 Tasks 段，找未勾選項；讀「設計決策」與「Schema」段作為實作依據。
2. 依 phase 順序實作；改 code 前先讀該檔案現況；遵循 CLAUDE.md 與 `.specify/memory/constitution.md` 慣例（PSR-12、thin controller、Form Request、Policy、eager loading、Composition API、Tailwind、UI 中文/註解英文）。
3. **改到其他模組 owner 的檔案時**：確認該接點已在 touchpoints 聲明；沒有就補上（file/owner/why）。
4. 每完成一個 task 立即把 `- [ ]` 改 `- [x]`（不要攢到最後一起勾）。
5. 有測試要求的 task 跑 `php artisan test`（相關測試），失敗要修到過或如實回報。

### 4. 收尾（每個 session 結束時做，不論是否全部完成）

1. 在 spec「進度日誌」段最上方加一行：`- YYYY-MM-DD: 本次完成了什麼（一行）`。
2. 全部 tasks 完成 → frontmatter `status: done`；把 `spec_index.json` 對應 US 的 status 由 `planned` 改 `implemented`（部分完成用 `partial`）。
3. 對照實際新增/修改的檔案，補齊 frontmatter `owner_files`（新檔案）與 index entry 的 `code_files`（若與規劃時不同）。
4. 跑 `python3 tools/build_spec_index.py`。
5. 回報：完成了哪些 task、剩哪些、測試結果。**不 commit**（除非使用者要求）。
