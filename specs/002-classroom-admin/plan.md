# Implementation Plan: 上課頁面與管理員後臺

**Branch**: `002-classroom-admin` | **Date**: 2026-01-17 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/002-classroom-admin/spec.md`
**Updated**: 2026-01-18 - 新增課程完成狀態節流機制
**Updated**: 2026-01-26 - 新增課程擁有權自動指派 (US7) 與管理員前端預覽 (US8)
**Updated**: 2026-01-30 - 將課程完成狀態節流門檻從 5 分鐘調整為 2 分鐘
**Updated**: 2026-01-30 - 優化優惠倒數計時 UI（卡片式設計 + 數字滾動動畫）
**Updated**: 2026-01-30 - 新增課程顯示/隱藏設定功能 (US9)
**Updated**: 2026-03-01 - Markdown 內嵌影片 iframe 響應式樣式
**Updated**: 2026-03-02 - 教室切換 lesson 時影片自動播放
**Updated**: 2026-03-08 - Bug Fix：獨立小節 md_content 欄位 key 錯誤
**Updated**: 2026-03-08 - Vimeo 影片自動顯示 zh-TW CC 字幕
**Updated**: 2026-03-09 - 新增 US10 小節新增 Email 通知 (Phase 23)
**Updated**: 2026-03-09 - 管理員課程表單新增 SEO 欄位（Phase 25）
**Updated**: 2026-03-09 - 修正：通知觸發點從 ChapterController 改為 LessonController，排除 drip 課程
**Updated**: 2026-03-09 - 精簡 lesson-added Email 模板，移除裝飾性 HTML
**Updated**: 2026-03-09 - 小節通知 Email 改為純文字 MIME（text:），主旨精簡
**Updated**: 2026-03-09 - 修正 Email 模板檔名（.text.blade.php → .blade.php）；主旨與內文加入課程類型標籤
**Updated**: 2026-03-09 - 免費試閱功能（US11）：is_preview 欄位、後台勾選、公開試閱教室路由、鎖定 UI

## Summary

上課頁面採用 Teachable 風格（左欄章節列表 + 右欄影片播放），管理員後臺提供課程管理、章節編輯和相簿管理功能。採用 Laravel 12 + Inertia.js + Vue 3 + Tailwind CSS 技術棧，符合現有專案架構。

**2026-01-18 更新**：新增課程完成狀態節流機制，避免會員頻繁點選章節時產生過多伺服器請求。前端採用樂觀更新 + ~~5 分鐘~~ **2 分鐘**延遲寫入機制（2026-01-30 調整）。

**2026-01-26 更新**：新增 US7（課程擁有權自動指派）和 US8（管理員前端預覽）。管理員建立課程時自動獲得存取權，並可在首頁和課程販售頁看到草稿課程進行預覽檢查。

**2026-01-30 更新**：新增 US9（課程顯示/隱藏設定）。管理員可設定課程是否顯示在首頁，隱藏課程仍可透過直接 URL 存取和購買。

## Technical Context

**Language/Version**: PHP 8.2+ / Laravel 12.x
**Primary Dependencies**: Inertia.js, Vue 3, Tailwind CSS, vuedraggable@next
**Storage**: MySQL (existing database), Local filesystem for images (storage/app/public)
**Testing**: PHPUnit (`php artisan test`)
**Target Platform**: Web (responsive design, mobile-first)
**Project Type**: Web application (Inertia.js monolith)
**Performance Goals**: 3s page load, 2s video switch (per SC-001, SC-002)
**Constraints**: 10MB max image upload, admin users < 10
**Scale/Scope**: Single-tenant, moderate traffic

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Laravel Conventions | ✅ Pass | RESTful controllers, Form Requests, Policies |
| II. Vue & Frontend Standards | ✅ Pass | Composition API, `<script setup>`, Tailwind |
| III. Responsive Design First | ✅ Pass | Mobile-first, tested on 320px+ |
| IV. Simplicity Over Complexity | ✅ Pass | No over-engineering, YAGNI applied |
| V. Security & Sensitive Data | ✅ Pass | No credentials in code, Vimeo embed only |

**Technology Stack Compliance**:
- ✅ Laravel 12.x
- ✅ MySQL
- ✅ Vue 3.x with Composition API
- ✅ Inertia.js
- ✅ Tailwind CSS
- ✅ Vimeo embed integration

## Project Structure

### Documentation (this feature)

```text
specs/002-classroom-admin/
├── plan.md              # This file
├── research.md          # Phase 0 output - video embed, drag-drop, storage decisions
├── data-model.md        # Phase 1 output - Course, Chapter, Lesson, LessonProgress, CourseImage
├── quickstart.md        # Phase 1 output - dev setup, verification checklist
├── contracts/           # Phase 1 output - routes.md
├── checklists/          # Requirements validation
│   └── requirements.md
└── tasks.md             # Phase 2 output - 131 tasks across 13 phases
```

### Source Code (repository root)

```text
# Laravel + Inertia.js monolith structure

app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── CourseController.php
│   │   │   ├── ChapterController.php
│   │   │   ├── LessonController.php
│   │   │   └── CourseImageController.php
│   │   └── Member/
│   │       └── ClassroomController.php
│   ├── Middleware/
│   │   └── AdminMiddleware.php
│   └── Requests/Admin/
│       ├── StoreCourseRequest.php
│       ├── UpdateCourseRequest.php
│       ├── StoreChapterRequest.php
│       └── StoreLessonRequest.php
├── Models/
│   ├── Course.php (extended)
│   ├── Chapter.php
│   ├── Lesson.php
│   ├── LessonProgress.php
│   └── CourseImage.php
├── Services/
│   └── VideoEmbedService.php
└── Console/Commands/
    └── UpdateCourseStatus.php

resources/js/
├── Layouts/
│   └── AdminLayout.vue
├── Pages/
│   ├── Admin/
│   │   ├── Dashboard.vue
│   │   └── Courses/
│   │       ├── Index.vue
│   │       ├── Create.vue
│   │       ├── Edit.vue
│   │       ├── Chapters.vue
│   │       └── Gallery.vue
│   └── Member/
│       └── Classroom.vue
├── Components/
│   ├── Admin/
│   │   ├── CourseForm.vue
│   │   ├── ChapterList.vue
│   │   ├── LessonForm.vue
│   │   └── ImageGalleryModal.vue
│   ├── Classroom/
│   │   ├── ChapterSidebar.vue
│   │   ├── LessonItem.vue
│   │   ├── VideoPlayer.vue
│   │   └── HtmlContent.vue
│   ├── Course/
│   │   └── PriceDisplay.vue
│   ├── Legal/
│   │   ├── LegalPolicyModal.vue
│   │   ├── TermsContent.vue
│   │   ├── PurchaseContent.vue
│   │   └── PrivacyContent.vue
│   └── Layout/
│       └── Footer.vue

database/migrations/
├── xxxx_add_status_to_courses_table.php
├── xxxx_create_chapters_table.php
├── xxxx_create_lessons_table.php
├── xxxx_create_lesson_progress_table.php
├── xxxx_create_course_images_table.php
├── xxxx_remove_portaly_url_from_courses_table.php
├── xxxx_add_pricing_fields_to_courses_table.php
└── xxxx_add_dimensions_to_course_images_table.php
```

**Structure Decision**: Using Laravel + Inertia.js monolith structure with separate Admin and Member controller namespaces. Vue components organized by feature (Admin, Classroom, Course, Legal).

## Complexity Tracking

> **No violations to justify** - All design decisions follow Constitution principles.

| Principle | Adherence | Notes |
|-----------|-----------|-------|
| Simplicity | ✅ | No over-engineering, direct implementations |
| Technology Stack | ✅ | All techs within approved stack |
| Security | ✅ | Admin middleware, no exposed credentials |

## Implementation Summary

### Completed Phases (1-15)

| Phase | Focus | Status |
|-------|-------|--------|
| Phase 1 | Setup (vuedraggable, storage:link) | ✅ Complete |
| Phase 2 | Foundational (migrations, models, middleware) | ✅ Complete |
| Phase 3 | US5 - Admin Auth | ✅ Complete |
| Phase 4 | US2 - Course CRUD | ✅ Complete |
| Phase 5 | US3 - Chapters | ✅ Complete |
| Phase 6 | US4 - Gallery | ✅ Complete |
| Phase 7 | US1 - Classroom | ✅ Complete |
| Phase 8 | Polish & Edge Cases | ✅ Complete |
| Phase 9 | Portaly 簡化 | ✅ Complete |
| Phase 10 | 優惠價/原價定價模式 | ✅ Complete |
| Phase 11 | 同頁插入圖片功能 | ✅ Complete |
| Phase 12 | 法律政策頁面 Modal | ✅ Complete |
| Phase 13 | 課程完成狀態節流機制 | ✅ Complete |
| Phase 14 | US7 - 課程擁有權自動指派 | ✅ Complete |
| Phase 15 | US8 - 管理員前端預覽 | ✅ Complete |
| Phase 16 | 節流門檻調整 | ✅ Complete |
| Phase 17 | US2b - 倒數計時 UI 優化 | ✅ Complete |
| Phase 18 | US9 - 課程顯示/隱藏設定 | ✅ Complete |
| Phase 19 | Bug Fixes & UI Polish | ✅ Complete |
| Phase 20 | US8 擴充 - 後臺課程管理頁預覽按鈕 | ✅ Complete |
| Phase 21 | Markdown 內嵌影片 iframe 響應式樣式 | ✅ Complete |
| Phase 22 | 教室切換 lesson 時影片自動播放 | ✅ Complete |
| Phase 23 | US10 - 小節新增 Email 通知會員 | ✅ Complete |

**Phase 13 Details** (2026-01-18 完成, 2026-01-30 調整門檻):
- 前端樂觀更新：點擊小節後立即顯示綠色勾勾
- **2 分鐘門檻**：停留滿 2 分鐘後才寫入伺服器（原 5 分鐘，2026-01-30 調整）
- 切換取消：2 分鐘內切換至其他小節則取消計時器
- 取消完成立即發送：不受 2 分鐘限制
- 純前端實作：使用 JavaScript setTimeout，伺服器不參與計時
- 視覺區分：待儲存狀態顯示較淺的綠色 (text-green-400)

**Phase 14 Details** (US7 - 課程擁有權自動指派, 2026-01-27 完成):
- 新增 Purchase.type 欄位（paid, system_assigned, gift）
- 管理員建立課程時自動建立購買紀錄
- 購買紀錄標記為 system_assigned，金額 $0
- 系統指派紀錄不計入銷售統計
- 課程刪除時一併移除系統指派紀錄
- 訂單紀錄頁面顯示「系統指派」類型

**Phase 15 Details** (US8 - 管理員前端預覽, 2026-01-27 完成, 2026-02-24 擴充):
- 首頁根據用戶角色返回不同課程列表（scopeVisibleToUser）
- 管理員可看到所有課程（含草稿）
- 草稿課程顯示「草稿」標籤（灰色）
- 草稿課程販售頁顯示「預覽模式」橫幅（藍色）
- 草稿課程購買按鈕點擊時顯示提示 Modal
- 一般會員無法存取草稿課程（404）
- **2026-02-24 新增**：後臺課程管理頁每筆課程操作欄加入橘色「預覽」按鈕，直接連結至上課頁面
- **2026-02-24 新增**：ClassroomController 管理員身份繞過購買驗證（isAdmin 短路邏輯）

See [tasks.md](./tasks.md) for detailed task breakdown.

---

### 2026-03-01: Markdown 內嵌影片 iframe 響應式樣式

**背景**：管理員在課程介紹（`description_md`）或小節 Markdown（`md_content`）中貼入 YouTube/Vimeo `<iframe>` 代碼時，iframe 預設帶有固定 `width="560"`，在手機螢幕上會溢出容器。需要確保嵌入影片響應式顯示。

**修改檔案**：
- `resources/css/app.css` - 新增 `.course-content iframe` 響應式樣式（`width: 100%`、`aspect-ratio: 16/9`、`border-radius: 0.5rem`）
- `resources/js/Components/Classroom/HtmlContent.vue` - 新增注釋，說明 marked.js 允許 iframe 直通，禁止加 sanitizer
- `resources/js/Pages/Course/Show.vue` - 新增注釋，同上

**設計決策**：
- **不需修改 marked.js 設定**：marked.js v17 預設就允許 `<iframe>` 等原始 HTML 直通（不過濾），無需額外設定
- **純 CSS 解法**：用 `width: 100%` + `aspect-ratio: 16/9` 覆蓋 iframe 上的固定寬高屬性，不需改動 JS 邏輯
- **禁止加 sanitizer**：admin 內容為可信任來源，加入 DOMPurify 會過濾 iframe，故明確以注釋標記禁止

**使用方式**：在 Markdown 編輯器中，`<iframe>` 前後需各保留一個空行，marked.js 才能將其識別為 HTML block 正確通過。

---

### 2026-03-02: 教室切換 lesson 時影片自動播放

**背景**：會員在上課頁面切換章節時，影片播放器需要自動開始播放，提升學習流暢度，無需手動點擊播放按鈕。

**修改檔案**：
- `resources/js/Components/Classroom/VideoPlayer.vue` - 將 Vimeo `autoplay` 從 `'0'` 改為 `'1'`，並為 YouTube 新增 `autoplay=1` 參數

**設計決策**：
- **URL 參數解法**：直接在 embed URL 加上 `autoplay=1`，iframe 重新載入時自動播放，無需額外 JS 控制
- **瀏覽器相容性**：切換 lesson 需要用戶點擊（使用者互動），符合瀏覽器 autoplay policy，不會被封鎖
- **初次載入**：頁面首次載入若未有使用者互動，瀏覽器可能攔截 autoplay，但屬預期行為

---

### 2026-03-08: Bug Fix - 獨立小節編輯時 md_content 欄位空白

**背景**：管理員在章節編輯頁面點擊「獨立小節（無章節分類）」的編輯按鈕後，Markdown 內容欄位顯示空白，但存檔後前台顯示正常。

**根本原因**：`ChapterController::index()` 在組建獨立小節資料時，錯誤地使用了不存在的 key `html_content`（`$lesson->html_content`），而有章節的小節則正確使用 `md_content`。前端 `LessonForm.vue` 讀取 `props.lesson.md_content`，因 key 不符導致拿到 `undefined`，呈現空白。

**修改檔案**：
- `app/Http/Controllers/Admin/ChapterController.php` - 將 standalone lessons mapping 中的 `'html_content' => $lesson->html_content` 改為 `'md_content' => $lesson->md_content`

**設計決策**：
- 此為 typo 修正，無架構變更

---

### 2026-03-08: Vimeo 影片自動顯示 CC 字幕

**背景**：Vimeo 影片在上課頁面播放時，即使影片已上傳字幕，播放器預設不自動顯示 CC 字幕，需會員手動開啟，影響學習體驗。

**修改檔案**：
- `resources/js/Components/Classroom/VideoPlayer.vue` - 新增 `texttrack=zh-TW` 參數至 Vimeo embed URL

**設計決策**：
- **URL 參數解法**：Vimeo embed 支援 `texttrack` 參數指定語言代碼，設定後播放器自動啟用對應字幕軌
- **語言代碼選擇 zh-TW**：本平台以繁體中文為主，配合 Vimeo 上傳的字幕語言代碼
- **僅影響 Vimeo**：YouTube 字幕由 YouTube 播放器原生管理，不在此處處理
- **前提條件**：需影片在 Vimeo 後台已上傳 zh-TW 字幕軌，否則此參數無作用

---

### Phase 23 Plan — US10: 小節新增 Email 通知會員

**背景**：管理員在已發布課程（preorder / selling）新增**小節（Lesson）**時，可勾選「發送 Email 通知學員」。系統同步發送 Email 給所有符合條件的購買者，說明課程名稱與新小節名稱，並邀請回來觀看。章（Chapter/ep）只是容器結構，真正代表新內容的是小節，因此通知觸發點在 LessonController，而非 ChapterController。

**新增檔案**：

```text
app/
└── Mail/
    └── LessonAddedNotification.php       # Mailable 類別（取代原錯誤的 ChapterAddedNotification）

resources/
└── views/emails/
    └── lesson-added.blade.php            # Email Blade 模板（取代原錯誤的 chapter-added.blade.php）
```

**修改檔案**：
- `app/Http/Controllers/Admin/LessonController.php` — `store()` 新增 `notify_members` 欄位讀取、排除於 create data、發信邏輯
- `app/Http/Requests/Admin/StoreLessonRequest.php` — 新增 `notify_members: nullable|boolean` 驗證
- `resources/js/Components/Admin/LessonForm.vue` — 新增「發送 Email 通知學員」勾選框（已發布且非 drip 課程才顯示，僅新增小節時）；新增 `courseStatus` prop
- `resources/js/Components/Admin/ChapterList.vue` — 傳遞 `:course-status` 給 `<LessonForm>`

**Email 內容設計**：
- Subject: `【{課程名稱}】新小節「{小節名稱}」上線囉！`
- Body: 說明「您購買的課程新增了新內容：小節名稱」，附上「立即前往上課」按鈕，連結至 `/member/classroom/{courseId}`

**核心實作邏輯**：

```php
// LessonController@store
$notifyMembers = $request->boolean('notify_members');
$data = $request->safe()->except(['notify_members']); // 排除 notify_members，避免傳入 create()

$lesson = $course->lessons()->create($data);

// ... drip reactivation logic ...

if ($notifyMembers && $course->status !== 'draft' && $course->course_type !== 'drip') {
    $recipients = Purchase::where('course_id', $course->id)
        ->where('status', '!=', 'refunded')
        ->where('type', '!=', 'system_assigned')
        ->with('user')
        ->get();

    foreach ($recipients as $purchase) {
        if ($purchase->user && $purchase->user->email) {
            try {
                Mail::to($purchase->user->email)
                    ->send(new LessonAddedNotification($course, $lesson));
            } catch (\Exception $e) {
                Log::error('Failed to send lesson notification', ['error' => $e->getMessage()]);
            }
        }
    }
}
```

**設計決策**：
- **觸發點在 LessonController，不在 ChapterController**：章只是容器，小節才是實際新內容；最初錯誤地放在 ChapterController，已修正
- **`notify_members` 必須用 `safe()->except()` 排除**：`notify_members` 不是 DB 欄位，若包含在 `create($data)` 中會導致資料庫欄位錯誤
- **同步發送（Mail::send）**：學員數量少，同步發送夠用，無需 Queue Worker；與現有登入驗證碼發送方式一致
- **Opt-in 勾選**：預設未勾選，管理員逐次自行決定是否通知
- **通知對象篩選**：排除 refunded（退款）與 system_assigned（管理員自身），確保只通知真實學員
- **草稿課程不顯示選項**：前端條件 `courseStatus !== 'draft'`，後端 `$course->status !== 'draft'` 雙重保護
- **drip 課程排除**：drip 課程有自己的訂閱排程發信，手動通知會造成訂閱者混亂；前端條件 `courseType !== 'drip'`，後端 `$course->course_type !== 'drip'` 雙重保護
- **發信失敗不影響小節儲存**：使用 try/catch 捕獲例外，錯誤記錄至 Log，小節仍視為儲存成功
- **未來擴展**：若學員數增長到同步發送感覺卡頓，只需將 `send()` 改為 `queue()` 即可，Mailable 不需修改

**技術考量**：
- 使用現有 Resend 服務（已在 003-member-management 設定），無需新增服務依賴
- Mailable 使用 Blade 模板（繁體中文），樣式與現有系統 Email 一致
- 不需要新增 Job 類別，減少程式複雜度

---

### 2026-03-09: 精簡 Email 模板 HTML

**背景**：`lesson-added.blade.php` 使用大量裝飾性 HTML（色塊、emoji、按鈕樣式、陰影），容易被 Gmail 等信件服務歸類為「促銷」分類，降低開信率。

**修改檔案**：
- `resources/views/emails/lesson-added.blade.php` - 移除所有裝飾性樣式，改為純文字風格 HTML；連結改用純文字 URL 而非樣式化按鈕

**設計決策**：
- **純文字風格**：僅保留基本 font-family、color、line-height，外觀接近一般事務信件
- **連結不用按鈕**：CTA 按鈕（大色塊 + 白字）是促銷信的典型特徵，改為純文字連結降低促銷判定風險
- **移除 emoji**：📢 等 emoji 也是促銷信常見特徵

---

### 2026-03-09: 小節通知 Email 改為純文字 MIME

**背景**：上一步雖移除了裝飾性樣式，但仍以 HTML MIME 傳送，部分信件服務仍可能歸類為促銷。改用 `text/plain` MIME 徹底避免此問題，並將主旨精簡以降低促銷感。

**修改檔案**：
- `app/Mail/LessonAddedNotification.php` - `content()` 改為 `text:`；主旨從 `【課程名】新小節「...」上線囉！` 改為 `您擁有的課程更新了：新小節「...」上線囉`
- `resources/views/emails/lesson-added.blade.php` → 重新命名為 `lesson-added.text.blade.php`，內容改為純文字

**設計決策**：
- **`Content(text: ...)` 而非 `view:`**：Laravel 以 `text/plain` Content-Type 傳送，收件端無法渲染任何 HTML，完全排除促銷判定風險
- **`.text.blade.php` 命名慣例**：Laravel 的純文字模板慣例，與 `view:` 的 `.blade.php` 區別
- **主旨去掉課程名**：主旨過長也是促銷信特徵之一；改為通用格式 `您擁有的課程更新了：新小節「{title}」上線囉`

---

### 2026-03-09: 修正 Email 模板檔名與加入課程類型標籤

**背景**：上一版將模板檔名改為 `.text.blade.php`，但 Laravel 的 `Content(text: 'view.name')` 實際上查找的是 `.blade.php`（不帶 `.text`）；`.text.blade.php` 只在使用 `Content(view:)` 時才會被自動發現為 text 副本。導致模板找不到，Email 發送失效。同時收到用戶反映主旨與內文未區分課程類型。

**修改檔案**：
- `resources/views/emails/lesson-added.blade.php` - 從 `lesson-added.text.blade.php` 改回正確檔名；重新排版為多行格式，並依 `$course->type` 顯示類型標籤（課程/迷你課/講座）
- `app/Mail/LessonAddedNotification.php` - 主旨加入課程類型標籤，使用 `match($this->course->type)` 動態生成

**設計決策**：
- **檔名規則澄清**：`Content(text: 'view')` 找 `view.blade.php`；`.text.blade.php` 是 `Content(view:)` 的自動偵測副本命名；兩者不可混用
- **類型標籤**：`full` → 課程、`mini` → 迷你課、`lecture` → 講座，兩處（主旨 + 內文）使用相同 `match` 邏輯

---

### 2026-03-09: 免費試閱功能（US11）

**背景**：讓未購買訪客體驗課程介面，降低購買決策阻力。管理員在後台標記特定小節為「試閱」，訪客可免登入進入試閱教室，但僅能觀看試閱小節；其他小節顯示鎖頭。試閱教室下方顯示購買 CTA 引導轉換。

**修改檔案**：
- `database/migrations/2026_03_09_000001_add_is_preview_to_lessons_table.php` - 新增 `is_preview` boolean default false
- `app/Models/Lesson.php` - `is_preview` 加入 `$fillable` 與 `casts`
- `app/Models/Course.php` - 新增 `hasPreviewLessons(): bool` 方法
- `app/Http/Requests/Admin/StoreLessonRequest.php` - 新增 `is_preview` 驗證規則
- `app/Http/Controllers/Member/ClassroomController.php` - 新增 `preview()` action、`formatLessonForPreview()`；`formatLesson()` 加入 `is_preview` 欄位
- `routes/web.php` - 新增公開路由 `GET /course/{course}/preview`（`course.preview`）
- `resources/js/Components/Admin/LessonForm.vue` - 非 drip 課程顯示「免費試閱」checkbox
- `resources/js/Pages/Member/Classroom.vue` - 新增 `isFreePreview` prop，試閱導航邏輯、返回連結、頂部橫幅、底部 CTA
- `resources/js/Components/Classroom/ChapterSidebar.vue` - 新增 `isFreePreview` prop，傳入 LessonItem
- `resources/js/Components/Classroom/LessonItem.vue` - 新增 `isFreePreview` prop，`isLocked` computed，鎖頭圖示，隱藏完成勾勾

**設計決策**：
- **複用 Classroom.vue**：試閱模式與正式教室同頁面，`isFreePreview` prop 切換行為差異，不新建頁面
- **不記錄進度**：`handleToggleComplete` 開頭加 `if (isFreePreview) return`；計時器完全不啟動
- **鎖定顯示**：鎖定小節顯示 padlock SVG，`cursor-default`，`@click="!isLocked && handleClick()"`
- **CTA 位置**：每個試閱小節內容最下方固定顯示購買區塊，確保每次觀看後都能看到轉換入口
- **返回連結**：試閱教室返回 `/course/{id}`（販售頁），正式教室返回 `/member/learning`

---

### 2026-03-09: 管理員課程表單新增 SEO 欄位

**背景**：前台 SEO 基礎建設（slug URL + meta_description）需要管理員能在後台填入這兩個欄位。

**修改檔案**：
- `app/Http/Requests/Admin/StoreCourseRequest.php` - 新增 `slug`（nullable, unique, regex）與 `meta_description`（nullable, max:160）驗證
- `app/Http/Requests/Admin/UpdateCourseRequest.php` - 同上，slug unique 排除自身
- `app/Http/Controllers/Admin/CourseController.php` - `edit()` 輸出加入 `slug`、`meta_description` 欄位
- `resources/js/Components/Admin/CourseForm.vue` - 在「副標題」下方新增 SEO 欄位區塊（兩欄並排：slug + meta_description）

**設計決策**：
- **放置位置**：SEO 欄位放在「副標題」後方（同一「基本資訊」卡片），視覺上與行銷欄位相鄰但分開
- **即時字數計算**：meta_description 顯示 `{{ form.meta_description.length }}/160` 提醒管理員長度
- **slug 格式說明**：input 下方提示「英文、數字、連字號，留空則用 ID」，降低輸入錯誤

---

## Key Design Decisions

Documented in [research.md](./research.md):

1. **Video Embedding**: URL parsing + iframe (no SDK dependencies)
2. **Drag & Drop**: vuedraggable@next (SortableJS wrapper)
3. **Image Storage**: Laravel Storage (local, expandable to S3)
4. **Status Scheduler**: Laravel Task Scheduling (every minute)
5. **Admin Auth**: Custom middleware checking `role === 'admin'`
6. **Markdown Content**: Rendered via marked.js (frontend), stored as Markdown in `description_html` / `html_content` columns (admin trusted, no sanitization). marked.js v17 passes raw HTML (including `<iframe>`) through by default — **do NOT add DOMPurify**. `<iframe>` 需前後保留空行才能被視為 HTML block。
7. **Countdown Timer**: Frontend Vue computed (every second)
8. **Image Gallery Modal**: Vue 3 Teleport
9. **Legal Policy Modal**: Static Vue components
10. **Lesson Completion Throttle**: Frontend setTimeout (2 minutes, 原 5 分鐘，2026-01-30 調整)
11. **Auto-Assign Ownership**: Purchase.type extension (system_assigned)
12. **Admin Frontend Preview**: Conditional query + UI badges
13. **Countdown Timer UI**: Card-based design with flip/scroll animation
14. **Course Visibility Toggle**: is_visible field in Course model
15. **Chapter Email Notification**: Resend Mailable 同步發送（Mail::send），admin opt-in，學員數少不需 Queue ← **New**
