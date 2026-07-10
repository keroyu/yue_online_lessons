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
4. **有明確驗收條款或核心邏輯的 task → 測試先行（TDD）**：先照 spec 驗收條款寫一個會失敗的測試 → 跑 `php artisan test --filter=xxx` 確認它**因為功能不存在而紅**（不是因為 typo 出錯）→ 再寫最小實作讓它綠。純樣式／文案／設定檔可略過。
5. 每完成一個 task 立即把 `- [ ]` 改 `- [x]`（不要攢到最後一起勾）。
6. **卡關時**（測試一直紅、行為不如預期）：先找根因再改——讀完整錯誤訊息、確認能穩定重現、看最近 diff 改了什麼；一次只試一個假設。同一個 bug 改超過 3 次還不好 → 停，別再試第 4 次，回報使用者可能是設計/架構問題。

### 4. 完工前驗證（宣稱完成前必做，鐵律）

**沒有當下這次跑出來的驗證輸出，就不准說「完成／修好／通過」。** 上一次的結果、「應該會過」、「看起來對」都不算。

1. **跑一次完整測試**：`php artisan test`（或本次相關範圍 `--filter=`），讀完整輸出、確認 exit code、數失敗數。
2. **改到前端行為的 task**：跑 `npm run build` 確認 exit 0（**純 CLI，不開瀏覽器**；要不要用瀏覽器實跑前端由使用者決定，`/dev` 不自動開）。
3. **逐條對驗收條款**：回頭讀 spec 該 US 的驗收條款，一條一條確認有沒有真的做到，缺的如實列出。
4. 測試紅 → 修到綠或如實回報紅在哪，**不可跳過或宣稱綠**。回報時把實際輸出（如 `34 passed`）貼出來，不要只寫「測試通過」。

### 5. 收尾（每個 session 結束時做，不論是否全部完成）

1. 在 spec「進度日誌」段最上方加一行：`- YYYY-MM-DD: 本次完成了什麼（一行）`。
2. 全部 tasks 完成 → frontmatter `status: done`；把 `spec_index.json` 對應 US 的 status 由 `planned` 改 `implemented`（部分完成用 `partial`）。
3. 對照實際新增/修改的檔案，補齊 frontmatter `owner_files`（新檔案）與 index entry 的 `code_files`（若與規劃時不同）。
4. 跑 `python3 tools/build_spec_index.py`。
5. 回報：完成了哪些 task、剩哪些、測試結果。**不 commit**（除非使用者要求）。
