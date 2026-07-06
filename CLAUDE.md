# Online Lesson Platform

數位內容販售平台

## Tech Stack

- **Backend:** Laravel 12 + MySQL (本機從官網 .pkg 安裝，port 3306)
- **Frontend:** Inertia.js + Vue 3 + Tailwind CSS
- **Email:** Resend.com
- **Video:** Vimeo Embed
- **Deploy:** Laravel Forge

## Commands

```bash
# Dev
php artisan serve
npm run dev

# Database
php artisan migrate:fresh --seed

# Test
php artisan test

# Remote Deploy (SSH)
ssh forge@129.212.217.5
```

## Coding Conventions

**PHP (Laravel):**
- PSR-12 標準
- Controller 使用 RESTful 命名
- Form Request 處理驗證
- Policy 處理授權
- Eager Loading 避免 N+1

**Vue:**
- Composition API (`<script setup>`)
- 頁面放 `Pages/`，組件放 `Components/`

**CSS:**
- Tailwind utility classes
- Mobile-first RWD

**語言:**
- UI 文案：中文
- Code / Comments：英文

## Agent Navigation Protocol

When working in this repository, follow this order **strictly**:

1. Read `repo_map.md` to identify the relevant module and its main files.
2. Search `specs/spec_index.json` by feature name, user story summary, or keywords.
3. Read only the matched spec section (use `file` + `anchor` from the index entry).
4. Read only the `code_files` listed in that spec entry.
5. Expand search outward only if those files are insufficient.
6. Do **not** scan the entire repository unless explicitly requested.

Before making any change, output:
```
Target feature: [feature id, e.g. 003.us-5]
Spec section: [file#anchor]
Target files: [list]
Need more search: yes/no
```

## Spec System

- `specs/` 為 **12 個領域模組**（000-platform-core ~ 011-high-ticket），每模組單一 `spec.md`，格式定義在 `specs/_template.md`。
- **Ownership 規則**：每個程式檔有且只有一個 owner 模組（spec frontmatter `owner_files`）；跨模組修改以 `touchpoints` 聲明。
- 索引：`spec_index.json`（US 級）、`code_index.json`（反查）、`repo_map.md` 由 `tools/build_spec_index.py` 生成（post-commit hook 自動觸發）。
- 舊交付批次 spec 在 `specs/_archive/`（唯讀，僅供考古）；外部參考文件在 `specs/_reference/`。

**Workflow（取代 speckit）**：
1. `/spec <描述>` — 規劃：自動分級（小改/加故事/新模組），產出含設計決策的方案（status: draft），列關鍵決策供審核。
2. 使用者審核通過 → status: building。
3. `/dev [模組id]` — 依 Tasks 實作、勾選、寫進度日誌；只吃 status: building。
4. `/sync` — 任何 code 變更（含沒走 /spec 的小修）對帳回 spec 與索引。

## Development Rules

- 所有頁面需 RWD
- 不過度設計，先完成再優化
- 敏感資料不進 git

## Key Dependencies

PHP 8.2 / Laravel 12、Inertia.js v2、Vue 3（`<script setup>`）、Tailwind CSS v4、`league/commonmark` + `marked`（Markdown）、`chart.js` + `vue-chartjs`（後台圖表）、vuedraggable（拖曳排序）、Resend（email）。各模組的資料表與 schema 細節見所屬 `specs/NNN-*/spec.md` 的 Schema 段。
