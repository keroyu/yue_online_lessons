# Implementation Plan: 上課頁面與管理員後臺

**Branch**: `002-classroom-admin` | **Date**: 2026-01-17 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/002-classroom-admin/spec.md`
**Updated**: 2026-01-18 - 新增課程完成狀態節流機制
**Updated**: 2026-01-26 - 新增課程擁有權自動指派 (US7) 與管理員前端預覽 (US8)
**Updated**: 2026-01-30 - 將課程完成狀態節流門檻從 5 分鐘調整為 2 分鐘
**Updated**: 2026-01-30 - 優化優惠倒數計時 UI（卡片式設計 + 數字滾動動畫）
**Updated**: 2026-01-30 - 新增課程顯示/隱藏設定功能 (US9)

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
| Phase 18 | US9 - 課程顯示/隱藏設定 | ⏳ Pending |

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

**Phase 15 Details** (US8 - 管理員前端預覽, 2026-01-27 完成):
- 首頁根據用戶角色返回不同課程列表（scopeVisibleToUser）
- 管理員可看到所有課程（含草稿）
- 草稿課程顯示「草稿」標籤（灰色）
- 草稿課程販售頁顯示「預覽模式」橫幅（藍色）
- 草稿課程購買按鈕點擊時顯示提示 Modal
- 一般會員無法存取草稿課程（404）

See [tasks.md](./tasks.md) for detailed task breakdown.

## Key Design Decisions

Documented in [research.md](./research.md):

1. **Video Embedding**: URL parsing + iframe (no SDK dependencies)
2. **Drag & Drop**: vuedraggable@next (SortableJS wrapper)
3. **Image Storage**: Laravel Storage (local, expandable to S3)
4. **Status Scheduler**: Laravel Task Scheduling (every minute)
5. **Admin Auth**: Custom middleware checking `role === 'admin'`
6. **HTML Content**: No sanitization (admin trusted)
7. **Countdown Timer**: Frontend Vue computed (every second)
8. **Image Gallery Modal**: Vue 3 Teleport
9. **Legal Policy Modal**: Static Vue components
10. **Lesson Completion Throttle**: Frontend setTimeout (2 minutes, 原 5 分鐘，2026-01-30 調整)
11. **Auto-Assign Ownership**: Purchase.type extension (system_assigned)
12. **Admin Frontend Preview**: Conditional query + UI badges
13. **Countdown Timer UI**: Card-based design with flip/scroll animation
14. **Course Visibility Toggle**: is_visible field in Course model ← **New**
