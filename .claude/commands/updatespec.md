---
description: 根據本次對話中已完成的代碼改動，將變更內容更新到相關的 spec 文件（spec.md、plan.md、tasks.md）。觸發詞：/updatespec
---

## User Input

```text
$ARGUMENTS
```

若有補充說明，請納入分析。

---

## 目的

將本次對話中已實作的改動，同步記錄到對應的 spec 文件，保持代碼與文件一致。

---

## Step 1：識別改動

從對話脈絡取得本次改動的資訊：
- 修改了哪些檔案
- 改動的功能描述
- 涉及哪個 feature（對應 `specs/` 下的哪個目錄）

如果無法從對話脈絡確定，執行：

```bash
git diff HEAD --name-only
git diff HEAD --stat
```

---

## Step 2：找到 spec 目錄

根據 Step 1 識別的 feature，找到對應的 spec 目錄（格式：`specs/NNN-feature-name/`）。

確認以下三個檔案存在：
- `spec.md`
- `plan.md`
- `tasks.md`

---

## Step 3：更新 spec.md

**目的**：記錄本次改動對用戶行為/需求的影響。

**更新規則**：

1. 在檔案最上方的 `**Updated**` 區域新增一行：
   ```
   **Updated**: YYYY-MM-DD - [一句話說明本次改動]
   ```

2. 找到最相關的 **User Story**，在其 Acceptance Scenarios 末尾新增場景（若改動涉及新的用戶可見行為）：
   ```
   N. **Given** [前提條件], **When** [觸發動作], **Then** [預期結果]
   ```

3. 若改動涉及邊界行為，在 **Edge Cases** 區域末尾新增說明。

4. 若改動引入新的功能性需求，在 **Functional Requirements** 末尾新增：
   ```
   - **FR-NNN**: 系統 MUST/SHOULD [需求描述]
   ```
   FR 編號接續最後一個。

**判斷是否需要更新**：
- 有新的用戶可見行為 → 更新 Acceptance Scenarios
- 有新的邊界條件 → 更新 Edge Cases
- 有新的系統限制或規則 → 更新 Functional Requirements
- 純粹重構無用戶行為變化 → 只更新 `**Updated**` 標頭即可

---

## Step 4：更新 plan.md

**目的**：記錄技術決策與實作細節。

**更新規則**：

1. 在最上方 `**Updated**` 區域新增一行：
   ```
   **Updated**: YYYY-MM-DD - [一句話說明]
   ```

2. 在 `## Incremental Update Summary` 區段（若無則在檔案末尾）新增以下格式的段落：

   ```markdown
   ---

   ### YYYY-MM-DD: [改動標題]

   **背景**：[為什麼做這個改動，解決什麼問題]

   **修改檔案**：
   - `path/to/file.php` - [說明]
   - `path/to/file.vue` - [說明]

   **設計決策**（若有）：
   - [決策 1]：[理由]
   - [決策 2]：[理由]

   **影響元素**（若為 UI 改動）：
   1. [元素 1] — [如何控制]
   2. [元素 2] — [如何控制]
   ```

---

## Step 5：更新 tasks.md

**目的**：記錄已完成的任務。

**更新規則**：

1. 在最上方 `**Updated**` 區域新增一行：
   ```
   **Updated**: YYYY-MM-DD - [改動標題] (Phase N)
   ```

2. 找到 `## Summary` 段落**之前**，新增一個新 Phase（編號接續最後一個）：

   ```markdown
   ## Phase N: [改動標題] (YYYY-MM-DD 新增)

   **Purpose**: [一句話說明目的]

   **背景**：[背景說明]

   - [x] TNNN [USX] [任務描述] in `path/to/file`
     - [具體做了什麼]
   - [x] TNNN [P] [USX] [任務描述] in `path/to/file`
     - [具體做了什麼]

   **Checkpoint**: [驗收標準] ✅

   ---
   ```

   Task ID 格式：`TNNN`（接續最後一個 Task ID）
   Story label 格式：`[USN]`（對應 spec.md 的 User Story）
   Parallel marker：`[P]`（若任務涉及不同檔案且無相依性）

3. 更新 `## Summary` 表格，新增一行並更新 Total：
   ```markdown
   | Phase N: [標題] | N | N |
   | **Total** | **NNN** | **NN** |
   ```

---

## Step 6：回報

輸出簡短摘要，說明在哪些檔案做了哪些更新。格式：

```
✅ 已更新 specs/NNN-feature-name/
- spec.md: [說明更新了什麼]
- plan.md: [說明更新了什麼]
- tasks.md: [說明更新了什麼，Task ID 範圍]
```

---

## 注意事項

- 今天日期請用 `date +%Y-%m-%d` 取得，或從 currentDate context 讀取
- 只更新有實際改動對應的段落，不新增無關內容
- Task checklist 格式必須嚴格遵循：`- [x] TNNN [P?] [USN?] 描述 in \`path\``
- 若本次改動跨越多個 User Story，在對應的多個 Story 下分別新增場景
