---
name: sync
description: 把實際程式碼變更對帳回 spec 與索引（取代 updatespec）。觸發詞：/sync。適用於沒走 /spec 的小修改、hotfix，或 /dev 之後的最終對帳。
---

# /sync — Code → Spec 對帳

以實際 diff 為準，把變更記回所屬領域模組的 spec 與索引。適用任何情況：走過 /spec /dev 的收尾對帳，或完全沒寫 spec 的臨時修改。

## 步驟

### 1. 取得變更清單

- 預設：`git status --short` + `git diff --stat`（工作區 + staged，對 HEAD）。
- 使用者指定範圍（如 `HEAD~3..`）則用該範圍。
- 沒有任何變更 → 回報後結束。

### 2. 找 owner 模組

對每個變更檔案：
1. 掃 `specs/*/spec.md` frontmatter 的 `owner_files` 找 owner（一個檔案只有一個 owner）。
2. 找不到 owner 的**新檔案** → 依領域語意判斷歸屬模組（參考 `repo_map.md` 的模組 purpose），加進該模組 `owner_files`。無法判斷就問使用者（AskUserQuestion，給 2~3 個候選模組）。
3. 已刪除的檔案 → 從 owner 的 `owner_files` 移除。

### 3. 更新受影響模組的 spec

對每個受影響模組（讀 diff 內容理解「行為改了什麼」，不是只看檔名）：
- 行為變更 → 修正對應 US 的驗收條款 / FR / 設計決策，使 spec 與 code 一致。過時的描述就地改掉，不要新舊並陳。
- 純小修（bugfix、文案、樣式）→ 不動主文，只記日誌。
- 一律在「進度日誌」加一行：`- YYYY-MM-DD: 變更摘要（一行）`。
- A 模組的變更動到 B 模組 owner 的檔案 → 在 A 的 `touchpoints` 補聲明（若缺）。
- 有新 US 級別的功能但沒走過 /spec → 依 `specs/_template.md` 補一個 US（標題格式 `### User Story N - 標題 (Priority: PX)`）並在 `spec_index.json` 補 entry（status 依實況 implemented）。

### 4. 同步索引

1. 更新 `spec_index.json` 受影響 entry 的 `code_files`（新增/移除檔案）與 `status`。
2. 跑 `python3 tools/build_spec_index.py`（同時更新 code_index 與 repo_map main_files）。

### 5. 回報

列出：哪些模組的 spec 被更新、各更新了什麼段落、索引重建結果。**不 commit**（post-commit hook 會在使用者 commit 時再保險重建一次索引）。
