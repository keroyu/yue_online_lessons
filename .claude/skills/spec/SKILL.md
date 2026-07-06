---
name: spec
description: 規劃功能並產出可審核的技術方案（取代 speckit specify+clarify+plan+tasks）。觸發詞：/spec <功能描述> 或 /spec <模組id> <功能描述>。產出 status:draft 的 spec 供使用者審核，審核通過才可 /dev 實作。
---

# /spec — 功能規劃

把一個功能描述變成「一份可審核的技術方案」，寫進所屬領域模組的 `spec.md`。
不建 git branch、不 commit、不寫任何程式碼。

## 步驟

### 1. 定位模組（永遠先做）

1. 讀 `repo_map.md` 找相關領域模組。
2. 查 `specs/spec_index.json`（keywords/summary 比對）確認既有功能與可能重疊。
3. 讀目標模組的 `specs/NNN-slug/spec.md`（frontmatter + 相關段落即可，不用全讀）。

### 2. 分級（決定動多少東西）

- **S 小改**（bugfix、微調、單一 FR）：不加 US。在既有模組補一條 FR 或修改既有驗收條款，Tasks 加 1~3 個項目。
- **M 加故事**（既有領域的新功能）：在既有模組追加 `### User Story N - 標題 (Priority: PX)`（編號接續），補 FR、設計決策、Schema delta、Tasks 新 phase。**這是最常見的情況** — 優先歸入既有模組，不要輕易開新資料夾。
- **L 新模組**（真正的新領域，現有 12 模組都放不下）：開 `specs/NNN-slug/`（編號接續最大值），依 `specs/_template.md` 寫完整單檔，並在 `repo_map.md` 加模組區段（`## Name (NNN)` + purpose + specs + `main_files:` 空列表 + related_specs）。

### 3. 澄清（合併原 clarify，不是獨立步驟）

只問**真正影響設計方向**的問題，最多 3 個，用 AskUserQuestion。
顯而易見或有慣例預設的事不要問。

### 4. 寫方案（格式嚴格依 `specs/_template.md`）

必守規則：
- US 標題必須是 `### User Story N - 標題 (Priority: PX)`（索引 regex 依賴）。
- **設計決策段寫到可審核的粒度**：資料表欄位與索引、Service 方法簽名與關鍵邏輯流程、與既有程式碼的接點。這是使用者要逐條確認的部分，不可含糊。
- Schema 段：migration 清單 + 關鍵不變量，細節留給程式碼。
- Tasks：`- [ ] T00N [P?] 描述 in path/to/file.php`，依相依序分 phase，可平行標 [P]。
- **owner_files 唯一原則**：新檔案登記到本模組 frontmatter `owner_files`；要改到其他模組擁有的檔案，寫進 `touchpoints`（file/owner/why），owner 用 `specs/` 下的實際資料夾名。
- 設計遵循 `.specify/memory/constitution.md` 的架構原則（thin controller、Service 封裝、Form Request 等）。
- frontmatter `status: draft`。

### 5. 同步索引

在 `specs/spec_index.json` 為每個新 US 追加 entry：
`{id, module, title, summary, heading, file, anchor, keywords(中英混列), code_files(預計涉及檔案), status: "planned"}`。
anchor 規則：heading 去 `### ` 後轉小寫、空格轉 `-`、刪 `():/,"'!?`（中文保留）。
然後跑 `python3 tools/build_spec_index.py` 驗證解析一致。

### 6. 請使用者審核（結束前必做）

在對話中列出「本次關鍵技術決策」清單（資料表、核心邏輯、接點），請使用者確認或修改。
使用者要求調整 → 改 spec 再列一次，直到點頭。
**使用者明確同意後**：frontmatter 改 `status: building`，回覆「可以開始 `/dev` 了」。
使用者未同意前，spec 停在 draft，不進入實作。
