# Spec 模板（領域模組單檔格式）

> 每個領域模組一個資料夾、一個 `spec.md`。本檔案是格式定義，也是 `/spec` skill 的寫作依據。
> 目標長度 150–400 行。寫「非顯而易見」的事；程式碼能自我說明的細節不要抄進來。

---

```markdown
---
id: NNN-slug
status: done            # draft(方案待審) | building(實作中) | done(無未完成工作)
owner_files:            # 本模組「擁有」的程式檔 — 全 repo 每個檔案有且只有一個 owner
  - app/...
touchpoints:            # 引用他模組檔案的接點（不擁有，只說明為何會改到）
  - file: app/Services/XxxService.php
    owner: NNN-other-module
    why: 一行說明
---

# Module Name（中文名）

## 目標

3 行以內：這個模組為誰解決什麼問題。

## User Stories

### User Story 1 - 標題 (Priority: P1)

一兩行敘述（As a / I want / So that 可省略，講清楚就好）。

**驗收**：
- [x] 已實作並驗證的行為（Given/When/Then 濃縮成一行）
- [ ] 未實作的行為

## Requirements

只列非顯而易見的規則（業務不變量、邊界行為、防濫用限制）：

- **FR-001**: ...

## 設計決策

取代 research.md + plan.md。只留「決策 + 理由」，每條 1–3 行：

- **D1**: 決策內容 — 為什麼（被否決的替代方案一句話帶過）

## Schema

本模組擁有的資料表 + 關鍵欄位語意與不變量。細節看 migrations，這裡只寫語意：

- `table_name` — 用途一句話；關鍵不變量（如「amount 恆為正，方向由 type 決定」）

## Tasks

未完成的工作才放這裡（已完成的 feature 重建時本段省略）。格式：

- [ ] T001 [P] 描述 in `path/to/file.php`

## 進度日誌

每個工作 session 一行，最新在上：

- YYYY-MM-DD: 做了什麼（一行）
```

---

## 索引相容性規則（不可違反）

1. User Story 標題必須嚴格符合：`### User Story N - 標題 (Priority: PX)`
   （`tools/build_spec_index.py` 靠 regex 解析此格式）
2. 資料夾命名 `NNN-slug`，spec 檔名固定 `spec.md`。
3. `owner_files` 全 repo 唯一：一個檔案只能出現在一個模組的 owner_files。
   跨模組修改用 `touchpoints` 聲明。
4. 索引的 `summary` / `keywords` / `code_files` / `status` 維護在
   `specs/spec_index.json`（per-US），rebuild 不會覆蓋。

## 寫作語言

- 內容：中文；code 路徑、識別字：英文。
- keywords：中英混列（搜尋用）。
