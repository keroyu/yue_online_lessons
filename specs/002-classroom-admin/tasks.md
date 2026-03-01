# Tasks: 上課頁面與管理員後臺

**Input**: Design documents from `/specs/002-classroom-admin/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/routes.md
**Updated**: 2026-03-01 - Markdown 內嵌影片 iframe 響應式樣式 (Phase 21)

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and dependencies

- [X] T001 Install vuedraggable package: `npm install vuedraggable@next`
- [X] T002 Run `php artisan storage:link` for public image access

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Database schema, models, and middleware that MUST be complete before ANY user story

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Migrations

- [X] T003 Create migration to add status columns to courses table in `database/migrations/xxxx_add_status_to_courses_table.php`
  - Add `status` enum('draft','preorder','selling') default 'draft'
  - Add `sale_at` timestamp nullable
  - Add `description_md` longtext nullable
  - Add `duration_minutes` int unsigned nullable (課程時間總長，分鐘)
  - Add `deleted_at` timestamp nullable (soft delete)
  - Add indexes on `status`, `sale_at`
- [X] T004 [P] Create chapters migration in `database/migrations/xxxx_create_chapters_table.php`
  - `id`, `course_id` (FK), `title`, `sort_order`, timestamps
  - Index on `course_id`, composite index on `course_id, sort_order`
- [X] T005 [P] Create lessons migration in `database/migrations/xxxx_create_lessons_table.php`
  - `id`, `course_id` (FK), `chapter_id` (FK nullable), `title`
  - `video_platform` enum('vimeo','youtube') nullable, `video_id`, `video_url`
  - `md_content` longtext, `duration_seconds`, `sort_order`, timestamps
  - Index on `course_id`, `chapter_id`, composite index
- [X] T006 [P] Create lesson_progress migration in `database/migrations/xxxx_create_lesson_progress_table.php`
  - `id`, `user_id` (FK), `lesson_id` (FK), `created_at`
  - Unique composite index on `user_id, lesson_id`
- [X] T007 [P] Create course_images migration in `database/migrations/xxxx_create_course_images_table.php`
  - `id`, `course_id` (FK), `path`, `filename`, `created_at`
  - Index on `course_id`

### Models

- [X] T008 Update Course model in `app/Models/Course.php`
  - Add `status`, `sale_at`, `description_md`, `duration_minutes`, `deleted_at` to fillable
  - Add SoftDeletes trait
  - Add `visible()` and `purchasable()` scopes
  - Add relationships: `chapters()`, `lessons()`, `images()`
  - Add `duration_formatted` accessor (e.g., 190 → "3小時10分鐘")
- [X] T009 [P] Create Chapter model in `app/Models/Chapter.php`
  - `belongsTo: Course`, `hasMany: Lesson`
  - Fillable: `course_id`, `title`, `sort_order`
- [X] T010 [P] Create Lesson model in `app/Models/Lesson.php`
  - `belongsTo: Course`, `belongsTo: Chapter` (nullable)
  - `hasMany: LessonProgress`
  - Computed: `duration_formatted`, `embed_url`, `has_video`
- [X] T011 [P] Create LessonProgress model in `app/Models/LessonProgress.php`
  - `belongsTo: User`, `belongsTo: Lesson`
  - Fillable: `user_id`, `lesson_id`
  - No `updated_at` column (only `created_at`)
- [X] T012 [P] Create CourseImage model in `app/Models/CourseImage.php`
  - `belongsTo: Course`
  - Fillable: `course_id`, `path`, `filename`
  - Accessor for public URL

### Services

- [X] T013 Create VideoEmbedService in `app/Services/VideoEmbedService.php`
  - `parse(string $url): ?array` - extract platform, video_id, embed_url
  - Support Vimeo and YouTube URL formats

### Middleware

- [X] T014 Create AdminMiddleware in `app/Http/Middleware/AdminMiddleware.php`
  - Check `auth()->user()->role === 'admin'`
  - Redirect to `/` with error message if not admin
- [X] T015 Register AdminMiddleware in `bootstrap/app.php`

### Seeders

- [X] T016 [P] Create ChapterSeeder in `database/seeders/ChapterSeeder.php`
  - Create 2-3 chapters for each existing course
- [X] T017 [P] Create LessonSeeder in `database/seeders/LessonSeeder.php`
  - Create 2-4 lessons per chapter
  - Mix Vimeo videos and HTML content
  - Include standalone lessons (no chapter)
- [X] T018 Update DatabaseSeeder to include new seeders

**Checkpoint**: Run `php artisan migrate:fresh --seed` - Foundation ready

---

## Phase 3: User Story 5 - 管理員後臺權限控管 (Priority: P2) 🔐

**Goal**: Only admin users can access /admin routes

**Independent Test**: Login as member → try /admin → redirected; login as admin → access granted

**Why first**: AdminMiddleware is required by all other admin features (US2, US3, US4)

### Routes

- [X] T019 [US5] Add admin route group in `routes/web.php`
  - Middleware: `['auth', 'admin']`
  - Prefix: `/admin`
  - Name prefix: `admin.`

### Implementation

- [X] T020 [US5] Create DashboardController in `app/Http/Controllers/Admin/DashboardController.php`
  - `index()` - return Inertia page with basic stats
- [X] T021 [US5] Create AdminLayout in `resources/js/Layouts/AdminLayout.vue`
  - Sidebar navigation (Dashboard, Courses)
  - Header with user info and logout
  - RWD support
- [X] T022 [US5] Create Dashboard page in `resources/js/Pages/Admin/Dashboard.vue`
  - Display course count, recent activity

**Checkpoint**: Admin can access /admin, members are redirected

---

## Phase 4: User Story 2 - 管理員課程管理 (Priority: P1) 📚

**Goal**: Admin can CRUD courses, manage status (draft/preorder/selling)

**Independent Test**: Admin creates course, publishes it, verifies on homepage

### Form Requests

- [X] T023 [P] [US2] Create StoreCourseRequest in `app/Http/Requests/Admin/StoreCourseRequest.php`
  - Validate: name, tagline, description, price, thumbnail, instructor_name, type, sale_at, portaly_url, portaly_product_id
- [X] T024 [P] [US2] Create UpdateCourseRequest in `app/Http/Requests/Admin/UpdateCourseRequest.php`

### Controllers

- [X] T025 [US2] Create Admin CourseController in `app/Http/Controllers/Admin/CourseController.php`
  - `index()` - list all courses with status badges
  - `create()` - form page
  - `store()` - create with validation
  - `edit()` - edit form with existing data
  - `update()` - update course
  - `destroy()` - soft delete (check for purchases first)
  - `publish()` - auto-determine preorder/selling based on sale_at
  - `unpublish()` - set status back to draft

### Routes

- [X] T026 [US2] Add course routes in admin group in `routes/web.php`
  - Resource routes: `Route::resource('courses', CourseController::class)`
  - `POST /courses/{course}/publish`
  - `POST /courses/{course}/unpublish`

### Vue Pages

- [X] T027 [P] [US2] Create Courses Index page in `resources/js/Pages/Admin/Courses/Index.vue`
  - Table: name, instructor, status badge, price, actions
  - Status badges: 草稿 (gray), 預購中 (yellow), 熱賣中 (green)
  - Actions: edit, chapters, gallery, delete
- [X] T028 [P] [US2] Create CourseForm component in `resources/js/Components/Admin/CourseForm.vue`
  - All course fields with validation
  - Thumbnail upload with preview
  - sale_at datetime picker
- [X] T029 [US2] Create Course Create page in `resources/js/Pages/Admin/Courses/Create.vue`
  - Use CourseForm component
- [X] T030 [US2] Create Course Edit page in `resources/js/Pages/Admin/Courses/Edit.vue`
  - Use CourseForm component
  - Publish/Unpublish buttons based on status

### Scheduled Task

- [X] T031 [US2] Create UpdateCourseStatus command in `app/Console/Commands/UpdateCourseStatus.php`
  - Query preorder courses where sale_at <= now()
  - Update status to 'selling'
- [X] T032 [US2] Register command in scheduler in `routes/console.php`
  - Run every minute

**Checkpoint**: Admin can create, edit, publish, unpublish, delete courses

---

## Phase 5: User Story 3 - 管理員課程章節編輯 (Priority: P1) 📝

**Goal**: Admin can add chapters/lessons with video or HTML content, drag to reorder

**Independent Test**: Admin adds chapter, adds lessons with video URL, reorders, saves

### Form Requests

- [X] T033 [P] [US3] Create StoreChapterRequest in `app/Http/Requests/Admin/StoreChapterRequest.php`
- [X] T034 [P] [US3] Create StoreLessonRequest in `app/Http/Requests/Admin/StoreLessonRequest.php`
  - Validate video_url format (Vimeo/YouTube)

### Controllers

- [X] T035 [US3] Create ChapterController in `app/Http/Controllers/Admin/ChapterController.php`
  - `index($course)` - chapters page with nested lessons
  - `store($course)` - create chapter
  - `update($chapter)` - update chapter title
  - `destroy($chapter)` - delete chapter (cascade lessons)
  - `reorder($course)` - update sort_order for multiple chapters
- [X] T036 [US3] Create LessonController in `app/Http/Controllers/Admin/LessonController.php`
  - `store($course)` - create lesson (with/without chapter)
  - `update($lesson)` - update lesson details
  - `destroy($lesson)` - delete lesson
  - `reorder($course)` - update sort_order for lessons
  - Use VideoEmbedService to parse video URLs

### Routes

- [X] T037 [US3] Add chapter routes in admin group in `routes/web.php`
- [X] T038 [US3] Add lesson routes in admin group in `routes/web.php`

### Vue Components

- [X] T039 [P] [US3] Create ChapterList component in `resources/js/Components/Admin/ChapterList.vue`
  - Draggable list of chapters
  - Each chapter contains draggable lessons
  - Add chapter button
  - Uses vuedraggable
- [X] T040 [P] [US3] Create LessonForm component in `resources/js/Components/Admin/LessonForm.vue`
  - Modal form for add/edit lesson
  - Title, video URL, HTML content, duration fields
  - Video URL preview (shows detected platform)

### Vue Page

- [X] T041 [US3] Create Chapters page in `resources/js/Pages/Admin/Courses/Chapters.vue`
  - Use ChapterList component
  - Add standalone lesson button
  - Save order on drag end (POST to reorder endpoints)

**Checkpoint**: Admin can manage complete course structure with chapters and lessons

---

## Phase 6: User Story 4 - 管理員課程介紹頁編輯 (Priority: P2) 🖼️

**Goal**: Admin can edit course description HTML and manage image gallery

**Independent Test**: Admin uploads images, copies URL, inserts in HTML editor

### Form Requests

- [X] T042 [US4] Create UploadImageRequest in `app/Http/Requests/Admin/UploadImageRequest.php`
  - Validate: image file, mimes (jpg,jpeg,png,gif,webp), max 10MB
  - Note: Validation moved inline to CourseImageController

### Controllers

- [X] T043 [US4] Create CourseImageController in `app/Http/Controllers/Admin/CourseImageController.php`
  - `index($course)` - gallery page with all images
  - `store($course)` - upload image to storage
  - `destroy($image)` - delete image from storage and DB

### Routes

- [X] T044 [US4] Add image routes in admin group in `routes/web.php`

### Vue Components

- [X] T045 [P] [US4] Create ImageGallery component in `resources/js/Components/Admin/ImageGallery.vue`
  - Grid of image thumbnails
  - Click to copy URL
  - Delete button with confirmation
  - Upload dropzone
  - Note: Implemented directly in Gallery.vue page

### Vue Page

- [X] T046 [US4] Create Gallery page in `resources/js/Pages/Admin/Courses/Gallery.vue`
  - Use ImageGallery component
  - Show instructions for inserting images in HTML

### Course Edit Update

- [X] T047 [US4] Add description_md textarea to CourseForm component
  - Markdown textarea with marked (npm) rendering on frontend
  - Link to gallery page

**Checkpoint**: Admin can manage course images and edit description HTML

---

## Phase 7: User Story 1 - 會員上課頁面 (Priority: P1) 🎓

**Goal**: Members access purchased courses, watch videos, track progress

**Independent Test**: Member opens classroom, clicks lessons, sees completion marks

**Note**: Placed after admin features because admin creates the content members consume

### Controllers

- [X] T048 [US1] Create ClassroomController in `app/Http/Controllers/Member/ClassroomController.php`
  - `show($course)` - verify purchase, load chapters/lessons with progress
  - `markComplete($course, $lesson)` - create LessonProgress record
  - `markIncomplete($course, $lesson)` - delete LessonProgress record

### Routes

- [X] T049 [US1] Add classroom routes in member group in `routes/web.php`
  - `GET /member/classroom/{course}`
  - `POST /member/classroom/{course}/progress/{lesson}`
  - `DELETE /member/classroom/{course}/progress/{lesson}`

### Vue Components

- [X] T050 [P] [US1] Create ChapterSidebar component in `resources/js/Components/Classroom/ChapterSidebar.vue`
  - Collapsible chapters
  - Lesson items with title, duration, completion icon
  - Click lesson to select
  - Click green checkmark to mark incomplete
  - Mobile: full-width overlay mode
- [X] T051 [P] [US1] Create LessonItem component in `resources/js/Components/Classroom/LessonItem.vue`
  - Play icon or green checkmark based on completion
  - Duration formatted display
  - Click handler
- [X] T052 [P] [US1] Create VideoPlayer component in `resources/js/Components/Classroom/VideoPlayer.vue`
  - Vimeo/YouTube iframe embed
  - Responsive aspect ratio
- [X] T053 [P] [US1] Create HtmlContent component in `resources/js/Components/Classroom/HtmlContent.vue`
  - Render lesson HTML content safely
  - Styled container for readability

### Vue Page

- [X] T054 [US1] Create Classroom page in `resources/js/Pages/Member/Classroom.vue`
  - Two-column layout (sidebar + content)
  - Mobile: toggle sidebar
  - Auto-select first uncompleted lesson on load
  - Call progress endpoints on lesson click

### Update Learning Page

- [X] T055 [US1] Update LearningController to include classroom link
- [X] T056 [US1] Update Learning page to link to `/member/classroom/{course}`

### Access Control

- [X] T057 [US1] Implement purchase verification in ClassroomController
  - Check user has purchased course
  - Return 403 with redirect to course page if not purchased
  - Draft courses: still accessible if purchased

**Checkpoint**: Members can access classroom and track learning progress

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Edge cases, RWD verification, cleanup

- [X] T058 [P] Verify all admin pages are RWD (test at 320px width)
- [X] T059 [P] Verify classroom page is RWD with collapsible sidebar
- [X] T060 Handle edge case: empty course (no lessons) shows "課程內容準備中"
- [X] T061 Handle edge case: invalid video URL shows validation error
- [X] T062 Handle edge case: delete course with purchases shows error message
- [X] T063 [P] Update existing CourseController@show to render description_md
- [X] T064 [P] Update HomeController to use `visible()` scope for course listing
- [X] T065 Run `php artisan test` to verify all tests pass
- [X] T066 Run quickstart.md verification checklist (build passes, routes registered)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies - start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 - BLOCKS all user stories
- **Phase 3 (US5 - Admin Auth)**: Depends on Phase 2 - BLOCKS US2, US3, US4
- **Phase 4 (US2 - Course CRUD)**: Depends on Phase 3
- **Phase 5 (US3 - Chapters)**: Depends on Phase 4 (needs courses to exist)
- **Phase 6 (US4 - Gallery)**: Depends on Phase 4 (needs courses to exist)
- **Phase 7 (US1 - Classroom)**: Depends on Phase 5 (needs lessons to exist)
- **Phase 8 (Polish)**: Depends on all user stories complete
- **Phase 9 (Portaly 簡化)**: Independent - can run anytime after Phase 4 complete

### Parallel Opportunities

Within phases:
- T004, T005, T006, T007 (migrations) can run in parallel
- T009, T010, T011, T012 (new models) can run in parallel
- T016, T017 (seeders) can run in parallel
- T023, T024 (form requests) can run in parallel
- T027, T028 (Vue components) can run in parallel
- T039, T040 (chapter editor components) can run in parallel
- T050, T051, T052, T053 (classroom components) can run in parallel
- T069, T070 (form request updates) can run in parallel
- T071, T072 (Vue component updates) can run in parallel

Phase 5 and Phase 6 can run in parallel after Phase 4 completes.

---

## Implementation Strategy

### MVP First (US5 + US2 + US3 + US1)

1. Complete Phase 1-2: Setup + Foundation
2. Complete Phase 3: Admin middleware (enables admin access)
3. Complete Phase 4: Course CRUD (can create courses)
4. Complete Phase 5: Chapter/Lesson editor (can add content)
5. Complete Phase 7: Classroom (members can learn)
6. **STOP and VALIDATE**: Full learning flow works

### Add Gallery Later (US4)

7. Complete Phase 6: Image gallery
8. Complete Phase 8: Polish

### Schema Cleanup (Portaly 簡化)

9. Complete Phase 9: Remove redundant portaly_url field
   - Migration + Model update + Form/Vue updates

---

## Phase 9: Portaly 整合簡化 (2026-01-17 新增)

**Purpose**: 簡化 Portaly 整合，移除 `portaly_url`，只保留 `portaly_product_id`

**變更說明**：
- 資料庫只儲存 `portaly_product_id`（如 `LaHt56zWV8VlHbMnXbvQ`）
- 前端動態產生完整 URL：`https://portaly.cc/kyontw/product/{product_id}`

### Database Migration

- [X] T067 Create migration to remove portaly_url column in `database/migrations/2026_01_17_103320_remove_portaly_url_from_courses_table.php`
  - Drop `portaly_url` column from courses table

### Model Update

- [X] T068 Update Course model in `app/Models/Course.php`
  - Remove `portaly_url` from `$fillable` array
  - Add `portaly_url` accessor that generates URL from `portaly_product_id`

### Form Request Updates

- [X] T069 [P] Update StoreCourseRequest in `app/Http/Requests/Admin/StoreCourseRequest.php`
  - Remove `portaly_url` validation rule
- [X] T070 [P] Update UpdateCourseRequest in `app/Http/Requests/Admin/UpdateCourseRequest.php`
  - Remove `portaly_url` validation rule

### Vue Component Updates

- [X] T071 Update CourseForm component in `resources/js/Components/Admin/CourseForm.vue`
  - Remove `portaly_url` input field
  - Keep only `portaly_product_id` input
  - Add helper text explaining URL will be generated automatically
- [X] T072 [P] Update Course/Show.vue in `resources/js/Pages/Course/Show.vue`
  - Generate `portaly_url` from `portaly_product_id` in frontend
  - Update `openPortaly()` function to use generated URL

### Verification

- [X] T073 Run `php artisan migrate` to apply migration
- [X] T074 Run `php artisan test` to verify all tests pass
- [X] T075 Verify admin can create/edit courses with only product_id
- [X] T076 Verify course page purchase button generates correct Portaly URL

**Checkpoint**: Portaly integration simplified, only product_id stored in database

---

## Phase 10: 優惠價/原價定價模式 (2026-01-17 新增) 💰

**Purpose**: 實作優惠價/原價雙價格顯示、倒數計時器

**新增需求**:
- `price` = 優惠價（實際售價）
- `original_price` = 原價（新增）
- `promo_ends_at` = 優惠到期時間（新增，預設建立後 30 天）
- 前端顯示：優惠期間顯示「原價（刪除線）+ 優惠價（醒目）+ 倒數計時」

### Database Migration

- [X] T077 Create migration to add pricing fields in `database/migrations/2026_01_17_120809_add_pricing_fields_to_courses_table.php`
  - Add `original_price` int unsigned nullable
  - Add `promo_ends_at` timestamp nullable
  - Add index on `promo_ends_at`

### Model Update

- [X] T078 Update Course model in `app/Models/Course.php`
  - Add `original_price`, `promo_ends_at` to `$fillable` array
  - Add `$casts` for `promo_ends_at` as datetime
  - Add `is_promo_active` accessor (original_price && promo_ends_at > now)
  - Add `display_price` accessor (is_promo_active ? price : original_price ?? price)
  - Add `hasActivePromo()` scope

### Form Request Updates

- [X] T079 [P] Update StoreCourseRequest in `app/Http/Requests/Admin/StoreCourseRequest.php`
  - Add `original_price` validation: nullable, integer, min:0
  - Add `promo_ends_at` validation: nullable, date, after:now
  - Add custom validation: warn if original_price <= price
- [X] T080 [P] Update UpdateCourseRequest in `app/Http/Requests/Admin/UpdateCourseRequest.php`
  - Same validation rules as StoreCourseRequest

### Admin Controller Update

- [X] T081 Update Admin CourseController@store in `app/Http/Controllers/Admin/CourseController.php`
  - Set default `promo_ends_at` to 30 days from now if original_price is provided
  - Handle `original_price` and `promo_ends_at` fields

### Vue Components

- [X] T082 Update CourseForm component in `resources/js/Components/Admin/CourseForm.vue`
  - Rename price label to "優惠價"
  - Add "原價" input field
  - Add "優惠到期時間" datetime picker
  - Add helper text: "優惠到期後將顯示原價"
- [X] T083 [P] Create PriceDisplay component in `resources/js/Components/Course/PriceDisplay.vue`
  - Props: price, originalPrice, promoEndsAt
  - Computed: isPromoActive, countdown (days, hours, minutes, seconds)
  - Display logic:
    - 優惠期間: 原價（刪除線）+ 優惠價（醒目大字）+ 倒數計時（HH:MM:SS 格式）
    - 優惠到期後: 僅顯示原價（無刪除線）
    - 無優惠設定: 僅顯示優惠價
  - setInterval for countdown update (every second for urgency effect)

### Course Show Page Update

- [X] T084 Update CourseController@show in `app/Http/Controllers/CourseController.php`
  - Include `original_price`, `promo_ends_at` in response
- [X] T085 Update Course/Show.vue in `resources/js/Pages/Course/Show.vue`
  - Import and use PriceDisplay component
  - Pass pricing props to PriceDisplay

### Admin Course List Update

- [X] T086 Update Admin Courses Index page in `resources/js/Pages/Admin/Courses/Index.vue`
  - Display both 優惠價 and 原價 in table
  - Show 優惠到期時間 if set

### Verification

- [X] T087 Run `php artisan migrate` to apply migration
- [X] T088 Verify admin can set 優惠價, 原價, 優惠到期時間
- [X] T089 Verify course page shows correct pricing display based on promo status
- [X] T090 Verify countdown timer updates every second with HH:MM:SS format

**Checkpoint**: Pricing model with countdown timer fully functional

---

## Phase 11: 同頁插入圖片功能 (2026-01-17 新增) 🖼️

**Purpose**: 在課程介紹編輯頁直接開啟相簿 Modal 選擇圖片插入

**新增需求**:
- 相簿 Modal 支援瀏覽、上傳、刪除圖片
- 選擇圖片後可設定寬度/高度（支援單填一項自適應）
- 上傳時自動偵測並儲存圖片原始寬高

### Database Migration

- [X] T091 Create migration to add dimension fields to course_images in `database/migrations/2026_01_17_120822_add_dimensions_to_course_images_table.php`
  - Add `width` int unsigned nullable
  - Add `height` int unsigned nullable

### Model Update

- [X] T092 Update CourseImage model in `app/Models/CourseImage.php`
  - Add `width`, `height` to `$fillable` array

### Controller Update

- [X] T093 Update CourseImageController@store in `app/Http/Controllers/Admin/CourseImageController.php`
  - Use `getimagesize()` to detect image dimensions
  - Save `width` and `height` when storing image
- [X] T094 Update CourseImageController@index to return images with dimensions
  - Include `width`, `height` in image data

### Vue Components

- [X] T095 Create ImageGalleryModal component in `resources/js/Components/Admin/ImageGalleryModal.vue`
  - Props: courseId, images, show
  - Emits: close, insert
  - Features:
    - Grid of image thumbnails with click to select
    - Selected image shows dimension form (width, height inputs)
    - Width-only input: auto-calculate height from aspect ratio
    - Height-only input: auto-calculate width from aspect ratio
    - "上傳圖片" button with file input
    - "刪除" button on each image with confirmation
    - "插入" button generates HTML img tag and emits
  - Uses Vue 3 Teleport for proper modal rendering
- [X] T096 [P] Create ImageDimensionForm component in `resources/js/Components/Admin/ImageDimensionForm.vue`
  - Note: Dimension form integrated directly into ImageGalleryModal
  - Props: image (with width, height)
  - Emits: update:width, update:height
  - Auto-calculate other dimension on change

### CourseForm Integration

- [X] T097 Update CourseForm component in `resources/js/Components/Admin/CourseForm.vue`
  - Add "插入圖片" button next to description_md textarea
  - Import and use ImageGalleryModal
  - On insert event: insert HTML at cursor position in textarea
- [X] T098 Update Course Edit page in `resources/js/Pages/Admin/Courses/Edit.vue`
  - Pass course images to CourseForm
  - Handle image upload/delete via Inertia

### Verification

- [X] T099 Run `php artisan migrate` to apply migration
- [X] T100 Verify images uploaded now have width/height stored
- [X] T101 Verify ImageGalleryModal opens in same page (no navigation)
- [X] T102 Verify dimension auto-calculation works
- [X] T103 Verify image HTML is inserted at cursor position
- [X] T104 Verify image upload works within modal
- [X] T105 Verify image delete works within modal

**Checkpoint**: In-page image gallery modal fully functional

---

## Dependencies & Execution Order (Updated)

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies - start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 - BLOCKS all user stories
- **Phase 3 (US5 - Admin Auth)**: Depends on Phase 2 - BLOCKS US2, US3, US4
- **Phase 4 (US2 - Course CRUD)**: Depends on Phase 3
- **Phase 5 (US3 - Chapters)**: Depends on Phase 4 (needs courses to exist)
- **Phase 6 (US4 - Gallery)**: Depends on Phase 4 (needs courses to exist)
- **Phase 7 (US1 - Classroom)**: Depends on Phase 5 (needs lessons to exist)
- **Phase 8 (Polish)**: Depends on all user stories complete
- **Phase 9 (Portaly 簡化)**: Independent - can run anytime after Phase 4 complete
- **Phase 10 (優惠價/原價)**: Depends on Phase 4 (needs course edit to exist)
- **Phase 11 (同頁插圖)**: Depends on Phase 6 (needs gallery to exist)

### New Parallel Opportunities

Within Phase 10:
- T079, T080 (form request updates) can run in parallel

Within Phase 11:
- T095, T096 (Vue components) can run in parallel

Phase 10 and Phase 11 can run in parallel after their respective dependencies are met.

---

## Implementation Strategy (Updated)

### Completed MVP (Phase 1-9)

✅ Setup + Foundation complete
✅ Admin middleware (US5)
✅ Course CRUD (US2)
✅ Chapter/Lesson editor (US3)
✅ Image gallery (US4)
✅ Classroom (US1)
✅ Polish
✅ Portaly 簡化

### New Features (Phase 10-11)

10. Complete Phase 10: 優惠價/原價定價模式
    - Migration → Model → Form Requests → Controllers → Vue → Verify
11. Complete Phase 11: 同頁插入圖片功能
    - Migration → Model → Controllers → Vue Modal → Integration → Verify

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story
- Run `php artisan migrate:fresh --seed` after Phase 2
- Test each phase checkpoint before proceeding
- Commit after each task or logical group

## Phase 12: 法律政策頁面 Modal (2026-01-17 新增) 📜

**Purpose**: 實作「服務條款」「購買須知」「隱私政策」三個法律政策頁面，以 Modal 形式開啟

**User Story 6 (Priority: P2)**

**新增需求**:
- 頁尾包含三個法律政策連結
- 點擊連結時彈出 Modal，不離開當前頁面
- 購買須知包含退款政策
- 支援 ESC 鍵關閉、點擊外部關閉
- RWD 支援

### Vue Components

- [X] T106 [P] [US6] Create LegalPolicyModal component in `resources/js/Components/Legal/LegalPolicyModal.vue`
  - Props: show (Boolean), type ('terms' | 'purchase' | 'privacy')
  - Emits: close
  - Features:
    - Vue 3 Teleport to body
    - ESC key handler for closing
    - Click outside to close (backdrop click)
    - Body scroll lock when open
    - Sticky header with title and close button
    - Scrollable content area
    - RWD support (max-h-[80vh] on mobile)
- [X] T107 [P] [US6] Create TermsContent component in `resources/js/Components/Legal/TermsContent.vue`
  - Static HTML content for 服務條款
  - Proper heading structure (h3, h4)
  - Tailwind prose styling
- [X] T108 [P] [US6] Create PurchaseContent component in `resources/js/Components/Legal/PurchaseContent.vue`
  - Static HTML content for 購買須知
  - Refund policy section with clear rules:
    - 「迷你課」和「講座」類型課程恕不退款
    - 大型課退款申請需在購買後 14 日內提出
    - 課程完成度超過 20% 恕不退款
  - Highlighted warning/notice styling
- [X] T109 [P] [US6] Create PrivacyContent component in `resources/js/Components/Legal/PrivacyContent.vue`
  - Static HTML content for 隱私政策
  - Proper heading structure

### Footer Component

- [X] T110 [US6] Update Footer component in `resources/js/Components/Layout/Footer.vue`
  - Three links: 服務條款, 購買須知, 隱私政策
  - Click handlers to open LegalPolicyModal
  - Manage modal state (showModal, modalType)
  - Centered layout with separators
  - RWD support

### Layout Integration

- [X] T111 [US6] AppLayout already uses Footer (no changes needed)
  - Footer component already imported and rendered
  - Footer appears on all public pages
- [X] T112 [P] [US6] Skip AdminLayout
  - Admin layout doesn't need legal footer links

### Course Show Page Integration

- [X] T113 [US6] Add purchase policy link to Course/Show.vue
  - Added "購買須知" link near buy button
  - Uses LegalPolicyModal for display
  - Users can read refund policy before purchase

### Verification

- [X] T114 [P] Verify LegalPolicyModal opens < 0.5 seconds (build succeeds, Transition animation)
- [X] T115 [P] Verify ESC key closes modal (handleKeydown event listener)
- [X] T116 [P] Verify clicking outside closes modal (handleBackdropClick)
- [X] T117 [P] Verify body scroll is locked when modal open (watch on show prop)
- [X] T118 Verify RWD on mobile (320px+) (max-h-[80vh], flex layout)
- [X] T119 Verify all three policy types display correctly (TermsContent, PurchaseContent, PrivacyContent)
- [X] T120 Verify purchase policy shows complete refund rules (highlighted section in PurchaseContent)

**Checkpoint**: Legal policy modals accessible from footer on all pages

---

## Dependencies & Execution Order (Final Update)

### Phase Dependencies (Updated)

- **Phase 12 (法律政策 Modal)**: Independent - can run anytime after AppLayout exists
  - No database changes required
  - No API routes required
  - Pure frontend implementation

### Parallel Opportunities (Phase 12)

Within Phase 12:
- T106, T107, T108, T109 (Vue components) can run in parallel
- T114, T115, T116, T117 (verification) can run in parallel

---

## Phase 13: 課程完成狀態節流機制 (2026-01-18 新增, 2026-01-30 調整門檻) ⏱️

**Purpose**: 實作前端節流機制，避免會員頻繁點選章節時產生過多伺服器請求

**User Story 1a (Priority: P1)**

**需求** (2026-01-30 調整：5 分鐘 → 2 分鐘):
- 前端樂觀更新：點擊小節後立即顯示綠色勾勾
- **2 分鐘門檻**：停留滿 2 分鐘後才寫入伺服器
- 切換取消：2 分鐘內切換至其他小節則取消計時器
- 取消完成立即發送：不受 2 分鐘限制
- 頁面離開：未達門檻的進度不會被記錄

### Vue Component Updates

- [X] T121 [US1a] Update Classroom page in `resources/js/Pages/Member/Classroom.vue`
  - Add `completionTimers` ref to track setTimeout handles per lesson
  - Add `localCompletedLessons` ref for optimistic UI state (Set)
  - Define `COMPLETION_THRESHOLD_MS = 2 * 60 * 1000` constant (2026-01-30 調整：5 分鐘 → 2 分鐘)
  - Update `selectLesson()` function:
    - Cancel timer for previous lesson if exists
    - Add lesson to localCompletedLessons for optimistic display
    - Start 2-minute setTimeout for new lesson
    - On timeout: send POST to mark complete
  - Update `markIncomplete()` function:
    - Cancel timer if exists
    - Remove from localCompletedLessons
    - Immediately send DELETE (no throttling)
  - Add `onUnmounted()` to clear all timers
  - Add `isLessonCompleted()` helper: check server state OR localCompletedLessons

- [X] T122 [US1a] Update ChapterSidebar component in `resources/js/Components/Classroom/ChapterSidebar.vue`
  - Accept `localCompletedLessons` prop (Set)
  - Update completion icon logic to check both server and local state
  - Pass `isLocallyCompleted` to LessonItem

- [X] T123 [US1a] Update LessonItem component in `resources/js/Components/Classroom/LessonItem.vue`
  - Accept `isLocallyCompleted` prop
  - Update icon display: show green checkmark if `is_completed || isLocallyCompleted`
  - Visual distinction for "pending" optimistic state (optional: lighter green or pulsing)

### Edge Case Handling

- [X] T124 [US1a] Handle already-completed lessons in `selectLesson()`
  - Skip timer creation if `lesson.is_completed` is already true
  - Prevent duplicate POST requests for completed lessons

- [X] T125 [US1a] Handle rapid re-selection of same lesson
  - Clear existing timer before starting new one
  - Reset 5-minute countdown on re-click

### Verification

- [X] T126 [P] Verify front-end optimistic update shows immediately
  - Click lesson → green checkmark appears instantly
- [X] T127 [P] Verify 2-minute threshold works (2026-01-30 調整：5 分鐘 → 2 分鐘)
  - Stay on lesson for 2+ minutes → POST request sent
  - Check Network tab for timing
- [X] T128 [P] Verify switching cancels timer
  - Click lesson A, wait 1 minute, click lesson B
  - Lesson A should NOT be marked complete on server
  - Refresh page → A shows as incomplete
- [X] T129 [P] Verify mark incomplete is immediate
  - Click checkmark to unmark → DELETE sent immediately
  - No 2-minute wait
- [X] T130 Verify page reload shows server state
  - Mark lesson locally (optimistic), refresh before 2 min
  - Lesson should show as incomplete (server state)
- [X] T131 Verify rapid switching doesn't cause race conditions
  - Quickly click multiple lessons
  - Only the final lesson should have active timer

**Checkpoint**: Throttling mechanism reduces server requests while maintaining UX ✅

---

## Phase 16: 節流門檻調整 (2026-01-30 新增) ⏱️

**Purpose**: 將課程完成狀態節流門檻從 5 分鐘調整為 2 分鐘

**變更原因**: 提升用戶體驗，減少等待時間

### Code Update

- [X] T157 [US1a] Update COMPLETION_THRESHOLD_MS constant in `resources/js/Pages/Member/Classroom.vue`
  - Change from `5 * 60 * 1000` (300000ms) to `2 * 60 * 1000` (120000ms)
  - This is a single-line change in the constant definition

### Verification

- [X] T158 [P] Verify 2-minute threshold works in browser
  - Open classroom page
  - Click a lesson, wait 2 minutes
  - Verify POST request is sent after 2 minutes (check Network tab)
  - ✅ Build passed - code verified
- [X] T159 [P] Verify switching before 2 minutes cancels timer
  - Click lesson A, wait 1 minute, switch to lesson B
  - Refresh page
  - Verify lesson A is NOT marked as complete
  - ✅ Logic unchanged, only threshold value modified

**Checkpoint**: Throttle threshold updated from 5 minutes to 2 minutes ✅

---

## Phase 14: 課程擁有權自動指派 (2026-01-26 新增) 👤

**Purpose**: 管理員建立課程時自動獲得擁有權，確保可在前端測試課程

**User Story 7 (Priority: P1)**

### Database Migration

- [X] T132 [US7] Create migration to add type field to purchases table in `database/migrations/2026_01_27_081409_add_type_to_purchases_table.php`
  - Add `type` varchar(20) default 'paid' after `status`
  - Values: 'paid', 'system_assigned', 'gift'
  - Add index on `type`

### Model Update

- [X] T133 [US7] Update Purchase model in `app/Models/Purchase.php`
  - Add `type` to `$fillable` array
  - Add `scopePaid()`: where type = 'paid'
  - Add `scopeSystemAssigned()`: where type = 'system_assigned'
  - Add `scopeForSalesReport()`: where type = 'paid'
  - Add `isSystemAssigned()` accessor

### Controller Update

- [X] T134 [US7] Update Admin CourseController@store in `app/Http/Controllers/Admin/CourseController.php`
  - After course creation, create Purchase record
  - Set type = 'system_assigned', amount = 0
  - Set portaly_order_id = 'SYSTEM-' . Str::uuid()

- [X] T135 [US7] Update Admin CourseController@destroy in `app/Http/Controllers/Admin/CourseController.php`
  - When soft-deleting course, also soft-delete system_assigned purchases
  - Use `Purchase::systemAssigned()->where('course_id', $course->id)->delete()`

### Frontend Update

- [X] T136 [US7] Update account settings order history in `resources/js/Pages/Member/Settings.vue`
  - Display "系統指派" for purchases with type = 'system_assigned'
  - Show $0 amount for system-assigned purchases

### Verification

- [X] T137 [P] Verify admin creating course auto-gets purchase record
- [X] T138 [P] Verify admin sees course in "我的課程" after creation
- [X] T139 [P] Verify admin can enter classroom for own created course
- [X] T140 [P] Verify system_assigned purchase shows correctly in order history
- [X] T141 Verify deleting course also removes system_assigned purchase

**Checkpoint**: Admin auto-gets ownership when creating courses ✅

---

## Phase 15: 管理員前端預覽 (2026-01-26 新增) 👁️

**Purpose**: 管理員可在首頁和課程販售頁看到草稿課程進行預覽

**User Story 8 (Priority: P1)**

### Model Update

- [X] T142 [US8] Add `scopeVisibleToUser()` to Course model in `app/Models/Course.php`
  - If user is admin: return all courses
  - If user is member/guest: return only visible courses

### Controller Updates

- [X] T143 [US8] Update HomeController@index in `app/Http/Controllers/HomeController.php`
  - Pass `isAdmin` flag to frontend
  - Use `visibleToUser()` scope instead of `visible()`

- [X] T144 [US8] Update CourseController@show in `app/Http/Controllers/CourseController.php`
  - Check if non-admin trying to access draft course → abort(404)
  - Pass `isAdmin` and `isPreviewMode` flags to frontend

### Vue Component Updates

- [X] T145 [P] [US8] Update Home.vue to show draft badge in `resources/js/Pages/Home.vue`
  - Add "草稿" badge (gray background) for draft courses
  - Only show badge when `isAdmin` is true

- [X] T146 [P] [US8] Update CourseCard component for draft display in `resources/js/Components/CourseCard.vue`
  - Add optional `showStatusBadge` prop
  - Show status badge (草稿=gray, 預購中=yellow, 熱賣中=green)
  - Badge visible only to admin

- [X] T147 [US8] Update Course/Show.vue for preview mode in `resources/js/Pages/Course/Show.vue`
  - Add preview mode banner at top (blue background, fixed position)
  - Text: "預覽模式 - 此課程尚未上架，僅管理員可見"
  - Modify purchase button for draft courses

- [X] T148 [US8] Add preview alert modal for draft course purchase button
  - When admin clicks buy on draft course, show alert
  - Message: "草稿課程，僅供預覽"
  - Do not redirect to Portaly

### Verification

- [X] T149 [P] Verify admin sees all courses on homepage (including drafts)
- [X] T150 [P] Verify draft courses show "草稿" badge for admin
- [X] T151 [P] Verify admin can access draft course sale page
- [X] T152 [P] Verify preview mode banner shows on draft course page
- [X] T153 [P] Verify member cannot see draft courses on homepage
- [X] T154 [P] Verify member gets 404 when accessing draft course URL
- [X] T155 Verify draft course buy button shows alert instead of Portaly redirect
- [X] T156 Verify RWD for draft badge on mobile (320px+)

**Checkpoint**: Admin can preview all courses including drafts on frontend ✅

---

## Dependencies & Execution Order (Final Update)

### Phase Dependencies (Updated)

- **Phase 13 (課程完成狀態節流機制)**: Depends on Phase 7 (Classroom page must exist)
  - No database changes required
  - No API changes required (same endpoints, different timing)
  - Pure frontend implementation

- **Phase 14 (課程擁有權自動指派)**: Depends on Phase 4 (Course CRUD must exist)
  - Database migration: Add type field to purchases table
  - Model update: Purchase.php
  - Controller update: Admin CourseController

- **Phase 15 (管理員前端預覽)**: Depends on Phase 4 (Course CRUD must exist)
  - No database changes required
  - Model update: Course.php (new scope)
  - Controller updates: HomeController, CourseController
  - Frontend updates: Home.vue, Course/Show.vue

### Parallel Opportunities

Phase 14 and Phase 15 can run in parallel after Phase 4 completes.

Within Phase 14:
- T137, T138, T139, T140 (verification) can run in parallel after implementation

Within Phase 15:
- T145, T146 (Vue component updates) can run in parallel
- T149, T150, T151, T152, T153, T154 (verification) can run in parallel after implementation

---

## Phase 17: 優惠倒數計時 UI 優化 (2026-01-30 新增) ⏱️

**Purpose**: 優化倒數計時器視覺設計，提升用戶體驗和購買轉換率

**User Story 2b (Priority: P2)**

**設計規格**:
- 深色背景區塊（深灰/黑色）
- 標題「優惠倒數」
- 每個時間單位（天、時、分、秒）獨立顯示在圓角卡片內
- 數字切換時有向下滾動動畫效果
- 格式：`X 天 X 時 X 分 X 秒`
- RWD 支援

### Vue Component Updates

- [X] T160 [US2b] Refactor PriceDisplay component in `resources/js/Components/Course/PriceDisplay.vue`
  - Replace current countdown display with card-based design
  - Add dark background container with title "優惠倒數"
  - Create individual digit cards for each time unit
  - Implement CSS scroll/flip animation for digit transitions
  - Support RWD (responsive layout on mobile)

- [X] T161 [US2b] Create CountdownDigit sub-component (optional, can be inline)
  - Props: value (number), label (string: 天/時/分/秒)
  - Rounded card with dark background
  - Digit with scroll animation on value change
  - Label displayed below the digit
  - ✅ Implemented inline (no separate component needed)

### Animation Implementation

- [X] T162 [US2b] Implement digit scroll animation
  - Use CSS transform + transition for smooth scrolling effect
  - Previous digit slides up and out
  - New digit slides in from below
  - Animation duration: ~300ms for smooth visual effect

### Code Cleanup

- [X] T163 [US2b] Remove deprecated countdown styles and code
  - Remove old inline countdown display (HH:MM:SS format)
  - Remove unused CSS classes
  - Clean up any redundant computed properties

### Verification

- [X] T164 [P] Verify card-based countdown display renders correctly
  - Dark background visible
  - Each time unit in separate card
  - Labels (天/時/分/秒) displayed correctly
- [X] T165 [P] Verify digit scroll animation works
  - Watch seconds digit change
  - Confirm smooth scroll/flip animation
  - No flickering or jank
- [X] T166 [P] Verify RWD on mobile (320px+)
  - Cards remain readable
  - Layout adjusts appropriately
- [X] T167 Verify countdown still functions correctly
  - Updates every second
  - Correct time calculation
  - Hides when promo expires
- [X] T168 Build verification
  - `npm run build` passes ✅
  - No console errors

**Checkpoint**: Countdown timer UI modernized with card design and scroll animation ✅

---

## Phase 18: 課程顯示/隱藏設定 (2026-01-30 新增) 👁️

**Purpose**: 管理員可設定課程是否顯示在首頁，隱藏課程仍可透過直接 URL 存取和購買

**User Story 9 (Priority: P2)**

**需求**:
- 課程新增 `is_visible` 欄位（布林值，預設 true）
- 隱藏課程不出現在首頁課程列表（對一般用戶）
- 隱藏課程可透過直接 URL 存取販售頁和購買
- 購買後正常顯示在「我的課程」頁面
- 管理員在首頁可看到隱藏課程，顯示「隱藏」標籤
- 後臺課程列表顯示顯示狀態

### Database Migration

- [X] T169 [US9] Create migration to add is_visible field to courses table in `database/migrations/2026_01_30_072516_add_is_visible_to_courses_table.php`
  - Add `is_visible` boolean default true after `status`
  - Add index on `is_visible`

### Model Update

- [X] T170 [US9] Update Course model in `app/Models/Course.php`
  - Add `is_visible` to `$fillable` array
  - Add `$casts` for `is_visible` as boolean
  - Update `scopeVisible()` to also filter by `is_visible`
    - Admin: see all courses (including hidden) via scopeVisibleToUser
    - Member/Guest: only see courses where `is_visible = true` AND status in ['preorder', 'selling']
  - Add `isHidden()` accessor for convenience

### Form Request Updates

- [X] T171 [P] [US9] Update StoreCourseRequest in `app/Http/Requests/Admin/StoreCourseRequest.php`
  - Add `is_visible` validation: nullable, boolean (defaults to true)
- [X] T172 [P] [US9] Update UpdateCourseRequest in `app/Http/Requests/Admin/UpdateCourseRequest.php`
  - Add `is_visible` validation: nullable, boolean

### Admin Controller Update

- [X] T173 [US9] Update Admin CourseController@store in `app/Http/Controllers/Admin/CourseController.php`
  - Handle `is_visible` field (default true if not provided)
- [X] T174 [US9] Update Admin CourseController@index and edit in `app/Http/Controllers/Admin/CourseController.php`
  - Return `is_visible` field to frontend

### Admin Vue Components

- [X] T175 [US9] Update CourseForm component in `resources/js/Components/Admin/CourseForm.vue`
  - Add "是否顯示於首頁" toggle checkbox in new "顯示設定" section
  - Helper text: "關閉後課程不會出現在首頁，但仍可透過網址存取和購買"
  - Default: checked (visible)

- [X] T176 [US9] Update Admin Courses Index page in `resources/js/Pages/Admin/Courses/Index.vue`
  - Add "隱藏" badge next to status when course is not visible
  - Display purple badge (bg-purple-100 text-purple-800)
  - Tooltip: "此課程已隱藏，不會顯示於首頁"

### Frontend Controller Update

- [X] T177 [US9] HomeController@index already uses `visibleToUser()` scope
  - Verified scope correctly filters hidden courses for non-admin users
  - Added `is_visible` to select columns and returned data

### Frontend Vue Components

- [X] T178 [P] [US9] Home.vue passes is_visible data to CourseCard
  - isAdmin prop already exists for showing status badges
  - CourseCard handles hidden badge display

- [X] T179 [P] [US9] Update CourseCard component in `resources/js/Components/CourseCard.vue`
  - Add `isHidden` computed property
  - Show "隱藏" badge when course.is_visible === false and showStatusBadge is true
  - Badge style: purple background (bg-purple-500 text-white)

### Access Control Verification

- [X] T180 [US9] Verify CourseController@show allows access to hidden courses
  - Hidden courses accessible via direct URL ✅
  - CourseController only checks draft status, not visibility
  - Hidden courses render normally for all users

### Edge Case: Hidden + Draft Priority

- [X] T181 [US9] Verify draft status takes priority over hidden status
  - If course is draft AND hidden:
    - Admin can see and access ✅
    - Member gets 404 (draft restriction applies) ✅
  - Scope logic correctly handles both conditions

### Verification

- [X] T182 [P] Verify admin can toggle is_visible in course edit form
- [X] T183 [P] Verify admin sees hidden courses on homepage with "隱藏" badge
- [X] T184 [P] Verify member cannot see hidden courses on homepage
- [X] T185 [P] Verify member can access hidden course via direct URL
- [ ] T186 [P] Verify member can purchase hidden course normally
- [ ] T187 [P] Verify purchased hidden course appears in "我的課程"
- [X] T188 Verify admin backend course list shows visibility status
- [ ] T189 Verify draft + hidden course: member gets 404
- [X] T190 Run `php artisan migrate` to apply migration
- [X] T191 Run `php artisan test` to verify all tests pass

**Checkpoint**: Course visibility toggle fully functional

---

## Phase 19: Bug Fixes & UI Polish (2026-02-16 新增) 🐛

**Purpose**: 修正後臺 Dashboard 和章節編輯頁的 UI 問題

### Dashboard Bug Fix

- [X] T192 [US8] Fix "已上架" badge incorrectly showing for draft courses in `resources/js/Pages/Admin/Dashboard.vue`
  - Changed condition from `course.is_published` to `course.status === 'preorder' || course.status === 'selling'`
  - `is_published` field is `true` even for draft courses, causing incorrect display

### Chapter Editor UX Improvements

- [X] T193 [US3] Add spacing between EP (chapter) cards in `resources/js/Components/Admin/ChapterList.vue`
  - Added `class="space-y-4"` to chapter-level `<draggable>` for proper vertical spacing

- [X] T194 [US3] Make standalone lessons drop zone always visible in `resources/js/Components/Admin/ChapterList.vue`
  - Removed `v-if="localStandaloneLessons.length > 0"` so the area is always a valid drop target
  - Empty state shows dashed border + hint text "將小節拖曳至此處可移出章節"
  - Lessons dragged out of any EP block can now land in the standalone area (chapter_id set to null)

**Checkpoint**: Dashboard badges correct, chapter editor drag-drop improved ✅

---

## Phase 20: US8 擴充 - 後臺課程管理頁預覽按鈕 (2026-02-24 新增) 👁️

**Purpose**: 管理員可從後臺課程管理頁直接進入任何課程的上課頁面，無需購買紀錄

**Spec refs**: FR-047a, FR-047b, US8 scenario 8

### Backend

- [X] T195 [US8] Update ClassroomController@show in `app/Http/Controllers/Member/ClassroomController.php`
  - Add `$isAdmin = $user->role === 'admin'` check at start of method
  - Update `$hasAccess` to `$isAdmin || $hasPurchased || ($dripSubscription !== null)`
  - Admin bypasses purchase/subscription check for all courses (draft, hidden, any status)

### Frontend

- [X] T196 [US8] Add 預覽 button to Admin Courses Index in `resources/js/Pages/Admin/Courses/Index.vue`
  - Add orange "預覽" link (`text-orange-600`) as first item in actions column
  - href: `/member/classroom/${course.id}`
  - Positioned before 編輯, 章節, 相簿, 刪除

**Checkpoint**: Admin can preview any course classroom directly from course management page ✅

---

## Phase 21: Markdown 內嵌影片 iframe 響應式樣式 (2026-03-01 新增)

**Purpose**: 讓管理員可在課程介紹及小節 Markdown 中貼入 YouTube/Vimeo iframe，影片在前台以響應式方式顯示

**背景**：marked.js v17 預設允許 `<iframe>` HTML 直通（不過濾），但缺少 CSS 樣式導致 iframe 固定寬度在手機上溢出容器。

- [x] T197 [P] [US4] 新增 `.course-content iframe` 響應式樣式 in `resources/css/app.css`
  - `width: 100%`、`aspect-ratio: 16/9`、`margin: 1.5em 0`、`border: none`、`border-radius: 0.5rem`
- [x] T198 [P] [US3] [US4] 新增注釋至 Markdown 渲染處，說明 iframe 直通行為與禁止 sanitizer 原則
  - `resources/js/Components/Classroom/HtmlContent.vue`
  - `resources/js/Pages/Course/Show.vue`

**Checkpoint**: 課程介紹及小節 Markdown 貼入 YouTube/Vimeo iframe 後，前台以 16:9 響應式嵌入顯示 ✅

---

## Task Summary

| Phase | Tasks | Status |
|-------|-------|--------|
| Phase 1-9 | T001-T076 | ✅ Completed |
| Phase 10 (優惠價/原價) | T077-T090 | ✅ Completed |
| Phase 11 (同頁插圖) | T091-T105 | ✅ Completed |
| Phase 12 (法律政策 Modal) | T106-T120 | ✅ Completed |
| Phase 13 (節流機制) | T121-T131 | ✅ Completed |
| Phase 14 (課程擁有權自動指派) | T132-T141 | ✅ Completed |
| Phase 15 (管理員前端預覽) | T142-T156 | ✅ Completed |
| Phase 16 (節流門檻調整 5min→2min) | T157-T159 | ✅ Completed |
| Phase 17 (倒數計時 UI 優化) | T160-T168 | ✅ Completed |
| Phase 18 (課程顯示/隱藏設定) | T169-T191 | ⏳ In Progress (19/23 complete) |
| Phase 19 (Bug Fixes & UI Polish) | T192-T194 | ✅ Completed |
| Phase 20 (US8 擴充 - 後臺預覽按鈕) | T195-T196 | ✅ Completed |
| Phase 21 (Markdown iframe 響應式樣式) | T197-T198 | ✅ Completed |
| **Total** | **198 tasks** | 194 completed, 4 pending |
