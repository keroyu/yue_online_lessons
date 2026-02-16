# Tasks: 數位課程販售平台 MVP

**Input**: Design documents from `/specs/001-course-platform-mvp/`
**Prerequisites**: plan.md, spec.md, data-model.md, contracts/routes.md
**Updated**: 2026-01-30 - 全站配色優化 (Phase 11)、倒數計時器簡化設計

**Tests**: Not explicitly requested - tests excluded from task list.

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story?] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story (US1-US5 maps to spec.md user stories)
- Exact file paths included in descriptions

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Laravel project initialization with Inertia.js + Vue 3

- [x] T001 Initialize Laravel 12 project with `laravel new` or verify existing structure
- [x] T002 Install and configure Inertia.js with Vue 3 adapter in `composer.json` and `package.json`
- [x] T003 [P] Install and configure Tailwind CSS in `tailwind.config.js` and `resources/css/app.css`
- [x] T004 [P] Configure Vite for Vue 3 SPA in `vite.config.js`
- [x] T005 Create base Blade template in `resources/views/app.blade.php`
- [x] T006 Setup Inertia middleware in `app/Http/Middleware/HandleInertiaRequests.php`
- [x] T007 [P] Configure session for 30-day lifetime in `config/session.php`
- [x] T008 [P] Configure Resend.com mail driver: install `resend/resend-laravel`, update `config/mail.php`, `config/services.php`, and `.env.example`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Database schema, models, and shared components that ALL user stories depend on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Database Migrations

- [x] T009 Create users table migration in `database/migrations/xxxx_create_users_table.php` with role, nickname, birth_date, last_login_at, last_login_ip fields
- [x] T010 [P] Create courses table migration in `database/migrations/xxxx_create_courses_table.php`
- [x] T011 [P] Create purchases table migration in `database/migrations/xxxx_create_purchases_table.php`
- [x] T013 [P] Create verification_codes table migration in `database/migrations/xxxx_create_verification_codes_table.php`

### Eloquent Models

- [x] T014 [P] Create User model with relationships and role enum in `app/Models/User.php`
- [x] T015 [P] Create Course model with scopes (published, ordered) in `app/Models/Course.php`
- [x] T016 [P] Create Purchase model with relationships in `app/Models/Purchase.php`
- [x] T018 [P] Create VerificationCode model in `app/Models/VerificationCode.php`

### Policies (Authorization)

- [x] T018a [P] Create CoursePolicy for course access authorization in `app/Policies/CoursePolicy.php`
- [x] T018b [P] Create PurchasePolicy for purchase access authorization in `app/Policies/PurchasePolicy.php`
- [x] T018c Register policies in `app/Providers/AuthServiceProvider.php`

### Seeders

- [x] T019 Create UserSeeder with admin and test members in `database/seeders/UserSeeder.php`
- [x] T020 [P] Create CourseSeeder with 5 sample courses in `database/seeders/CourseSeeder.php`
- [x] T021 [P] Create PurchaseSeeder with sample purchases in `database/seeders/PurchaseSeeder.php`
- [x] T022 Update DatabaseSeeder to call all seeders in `database/seeders/DatabaseSeeder.php`

### Shared Vue Components

- [x] T023 Create AppLayout component in `resources/js/Components/Layout/AppLayout.vue`
- [x] T024 [P] Create Navigation component with auth state in `resources/js/Components/Layout/Navigation.vue`
- [x] T025 [P] Create Footer component in `resources/js/Components/Layout/Footer.vue`
- [x] T026 Configure app.js with Inertia and layout in `resources/js/app.js`

### Routes Setup

- [x] T027 Define all routes per contracts/routes.md in `routes/web.php`

**Checkpoint**: Foundation ready - run `php artisan migrate:fresh --seed` to verify

---

## Phase 3: User Story 1 - 瀏覽課程首頁 (Priority: P1) 🎯 MVP

**Goal**: 訪客可瀏覽首頁看到所有販售中課程，點擊進入課程販售頁

**Independent Test**: 無需登入即可瀏覽首頁，課程卡片顯示縮圖、名稱、簡介

### Implementation for User Story 1

- [x] T028 [US1] Create HomeController with index method in `app/Http/Controllers/HomeController.php`
- [x] T029 [US1] Create CourseCard component (300x200 thumbnail, name, tagline) in `resources/js/Components/CourseCard.vue`
- [x] T030 [US1] Create Home page with responsive grid layout in `resources/js/Pages/Home.vue`

**Checkpoint**: Visit `/` to see course listing with RWD support

---

## Phase 4: User Story 5 - 課程獨立販售頁 (Priority: P2)

**Goal**: 訪客可查看課程詳情，點擊購買按鈕外連 Portaly

**Independent Test**: 訪問 `/course/{id}` 顯示完整課程資訊和購買按鈕

**Note**: Grouped with US1 as both are public pages for course browsing flow

### Implementation for User Story 5

- [x] T031 [US5] Create CourseController with show method in `app/Http/Controllers/CourseController.php`
- [x] T032 [US5] Handle 404 for unpublished/missing courses in CourseController
- [x] T033 [US5] Create Course/Show page with full description and Portaly button in `resources/js/Pages/Course/Show.vue`
- [x] T033a [US5] Add consent checkbox (同意購買條款) before purchase button can be clicked in Course/Show.vue

**Checkpoint**: Visit `/course/1` to see course detail with "購買" button (disabled until checkbox checked)

---

## Phase 5: User Story 2 - Email 驗證碼登入/註冊 (Priority: P1) 🎯 MVP

**Goal**: 用戶透過 email OTP 登入，新用戶自動註冊

**Independent Test**: 輸入 email → 收驗證碼 → 驗證成功登入

### Implementation for User Story 2

- [x] T034 [US2] Create VerificationCodeService with generate, validate, rate-limit logic in `app/Services/VerificationCodeService.php`
- [x] T035 [US2] Create VerificationCodeMail mailable in `app/Mail/VerificationCodeMail.php`
- [x] T036 [P] [US2] Create SendVerificationCodeRequest in `app/Http/Requests/Auth/SendVerificationCodeRequest.php`
- [x] T037 [P] [US2] Create VerifyCodeRequest in `app/Http/Requests/Auth/VerifyCodeRequest.php`
- [x] T038 [US2] Create LoginController with showLoginForm, sendCode, verify, logout methods in `app/Http/Controllers/Auth/LoginController.php`
- [x] T039 [US2] Implement sendCode with rate limiting (60s) and email sending in LoginController
- [x] T040 [US2] Implement verify with auto-registration, attempt tracking, lockout (5 attempts/15 min) in LoginController
- [x] T041 [US2] Update last_login_at and last_login_ip on successful login
- [x] T042 [US2] Create VerificationCodeInput component for 6-digit input in `resources/js/Components/VerificationCodeInput.vue`
- [x] T043 [US2] Create Login page with email form and code verification in `resources/js/Pages/Auth/Login.vue`
- [x] T043a [US2] Add consent checkbox (同意服務條款和隱私政策) for new user registration in Login.vue
- [x] T044 [US2] Add flash message handling for success/error states in Login page

**Checkpoint**: Complete login flow from email input to authenticated redirect (new users must check consent)

---

## Phase 6: User Story 3 - 我的課程頁面 (Priority: P2)

**Goal**: 會員查看已購買課程列表

**Independent Test**: 登入會員訪問 `/member/learning` 看到購買的課程

### Implementation for User Story 3

- [x] T045 [US3] Create LearningController with index method in `app/Http/Controllers/Member/LearningController.php`
- [x] T046 [US3] Query user's purchases with course data in LearningController
- [x] T047 [US3] Create MyCourseCard component with thumbnail, name, instructor in `resources/js/Components/MyCourseCard.vue`
- [x] T048 [US3] Create Learning page with course grid and empty state in `resources/js/Pages/Member/Learning.vue`

**Checkpoint**: Login and visit `/member/learning` to see purchased courses

---

## Phase 7: User Story 4 - 帳號設定頁面 (Priority: P3)

**Goal**: 會員修改個人資料並檢視訂單紀錄

**Independent Test**: 登入會員訪問 `/member/settings` 修改暱稱/生日，查看訂單

### Implementation for User Story 4

- [x] T049 [US4] Create UpdateProfileRequest with validation rules in `app/Http/Requests/Member/UpdateProfileRequest.php`
- [x] T050 [US4] Create SettingsController with index and update methods in `app/Http/Controllers/Member/SettingsController.php`
- [x] T051 [US4] Create Settings page with profile form and order history in `resources/js/Pages/Member/Settings.vue`
- [x] T052 [US4] Implement order history display with empty state

**Checkpoint**: Login and visit `/member/settings` to update profile and view orders

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Final integration, error handling, and RWD verification

- [x] T053 [P] Add flash message display to AppLayout component
- [x] T054 [P] Create 404 error page in `resources/js/Pages/Error.vue`
- [x] T055 Verify all pages RWD on 320px, 768px, 1024px viewports
- [x] T056 [P] Add loading states to forms (send code, verify, update profile)
- [x] T057 Review and update HandleInertiaRequests middleware for shared data
- [x] T058 Run `php artisan test` to verify no errors
- [x] T059 Run quickstart.md validation - complete setup on fresh environment

---

## Dependencies & Execution Order

### Phase Dependencies

```
Phase 1 (Setup) → Phase 2 (Foundational) → User Stories (Phase 3-7) → Phase 8 (Polish)
```

- **Setup (Phase 1)**: No dependencies - start immediately
- **Foundational (Phase 2)**: Depends on Setup - BLOCKS all user stories
- **User Stories (Phase 3-7)**: All depend on Foundational completion
- **Polish (Phase 8)**: Depends on all user stories complete

### User Story Dependencies

| Story | Depends On | Can Parallel With |
|-------|------------|-------------------|
| US1 (首頁) | Foundational | US5 |
| US5 (販售頁) | Foundational, Course model | US1 |
| US2 (登入) | Foundational | - |
| US3 (我的課程) | US2 (needs auth) | US4 |
| US4 (帳號設定) | US2 (needs auth) | US3 |

### Within Each User Story

1. Controller/Service → Form Request → Vue Page → Integration
2. Backend first, then frontend
3. Complete story before moving to next priority

### Parallel Opportunities by Phase

**Phase 2 (Foundational)**:
```
Parallel: T010, T011, T013 (all migrations)
Parallel: T014, T015, T016, T018 (all models)
Parallel: T019, T020, T021 (seeders, after models)
Parallel: T023, T024, T025 (Vue layout components)
```

**Phase 5 (US2 - 登入)**:
```
Parallel: T036, T037 (Form Requests)
```

---

## Parallel Example: Foundational Phase

```bash
# Launch all migrations in parallel:
Task: T010 "Create courses table migration"
Task: T011 "Create purchases table migration"
Task: T013 "Create verification_codes table migration"

# Then all models in parallel:
Task: T014 "Create User model"
Task: T015 "Create Course model"
Task: T016 "Create Purchase model"
Task: T018 "Create VerificationCode model"
```

---

## Implementation Strategy

### MVP First (US1 + US5 + US2)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1 (首頁)
4. Complete Phase 4: User Story 5 (販售頁)
5. Complete Phase 5: User Story 2 (登入)
6. **STOP and VALIDATE**: Test public browsing + login flow
7. Deploy MVP if ready

### Incremental Delivery

1. Setup + Foundational → Foundation ready
2. Add US1 + US5 → Public browsing works → Demo
3. Add US2 → Login works → Demo
4. Add US3 → My courses works → Demo
5. Add US4 → Settings works → Demo
6. Polish → Production ready

---

## Phase 9: 縮圖 URL 處理 (2026-01-17 新增)

**Purpose**: 統一縮圖 URL 處理，後端輸出完整 URL，前端直接使用

**背景**：課程縮圖資料庫儲存相對路徑（如 `thumbnails/abc.jpg`），但前端需要完整 URL（如 `/storage/thumbnails/abc.jpg`）。目前各處理方式不一致，需統一。

### Model Accessor

- [x] T060 Add `thumbnailUrl` Accessor to Course model in `app/Models/Course.php`
  - Return `/storage/{$this->thumbnail}` or null if no thumbnail

### Controller Updates

- [x] T061 [P] Update HomeController to output `thumbnail_url` in `app/Http/Controllers/HomeController.php`
  - Change course mapping to use `$course->thumbnail_url`
- [x] T062 [P] Update CourseController to output `thumbnail_url` in `app/Http/Controllers/CourseController.php`
  - Change `'thumbnail' => $course->thumbnail` to `'thumbnail' => $course->thumbnail_url`

### Frontend Cleanup (if applicable)

- [x] T063 [P] Verify CourseCard.vue uses thumbnail directly in `resources/js/Components/CourseCard.vue`
  - No changes needed - frontend already uses thumbnail directly without /storage/ prefix
- [x] T064 [P] Verify Course/Show.vue uses thumbnail directly in `resources/js/Pages/Course/Show.vue`
  - No changes needed - frontend already uses thumbnail directly without /storage/ prefix

### Verification

- [x] T065 Test thumbnail displays correctly on Home page
- [x] T066 Test thumbnail displays correctly on Course detail page
- [x] T067 Test thumbnail displays correctly on My Learning page (refactored to use accessor)

**Checkpoint**: All course thumbnails display correctly across all pages

---

## Phase 10: Webhook 購買處理 (2026-01-17 新增)

**Purpose**: 實作 Portaly webhook 接收購買通知，自動建立會員與購買紀錄

**背景**：用戶在 Portaly 完成付款後，系統透過 webhook 自動建立購買紀錄。未登入用戶需先輸入 email 再購買，若 email 未註冊則自動建立會員。

### Database Migration

- [x] T068 Create migration to add webhook fields to purchases table
  - Add `buyer_email` varchar(255) not null
  - Add `webhook_received_at` timestamp nullable
  - Modify `portaly_order_id` to not null (if possible, or handle in code)

### Service Layer

- [x] T069 Create PortalyWebhookService in `app/Services/PortalyWebhookService.php`
  - Implement `verifySignature()` using HMAC-SHA256 with `X-Portaly-Signature` header
  - Parse webhook payload (data, event, timestamp)
  - Return structured data or throw exception

- [x] T070 Add method to create or get user by email in PortalyWebhookService
  - Check if user exists by email
  - If not, create new user with role 'member', real_name, phone from customerData
  - Return User model

- [x] T071 Add method to create purchase record in PortalyWebhookService
  - Check if portaly_order_id already exists (idempotency)
  - Find Course by `portaly_product_id` matching `data.productId`
  - Create purchase record with all webhook data
  - Return Purchase model or null if already exists

- [x] T071a Add method to handle refund event in PortalyWebhookService
  - Find Purchase by portaly_order_id
  - Update status to 'refunded'
  - Log if order not found

### Controller

- [x] T072 Create PortalyController in `app/Http/Controllers/Webhook/PortalyController.php`
  - Handle POST /api/webhook/portaly
  - Verify signature first, return 401 if invalid
  - Route to appropriate handler based on `event` field (paid/refund)
  - Return 200 even on processing errors (to prevent Portaly retry loops)
  - Log errors for debugging

- [x] T073 Add webhook route to `routes/api.php` or `routes/web.php`
  - POST /api/webhook/portaly → PortalyController@handle
  - Exclude from CSRF middleware (add to $except in VerifyCsrfToken)

### Frontend Updates

- [x] T074 [US5] Update Course/Show.vue to add email reminder for purchase
  - Guest: 顯示提醒「購買課程之後，務必確認使用和 Portaly 下訂時相同的 Email 登入。如果您已註冊過本站，建議先登入。」
  - Logged-in: 顯示「請確認 Portaly 結帳時使用此 Email：{user.email}」
  - Position above consent checkbox

- [x] T075 [US5] Simplify purchase button (no email input needed)
  - Purchase button simply opens Portaly product page
  - Portaly form handles email, name, phone collection

### Configuration

- [x] T076 Add Portaly configuration to environment
  - Add `PORTALY_WEBHOOK_SECRET=` to `.env.example`
  - Add portaly config to `config/services.php`

- [x] T076a Document Portaly setup in README or deployment guide
  - How to get webhook secret from Portaly
  - How to set webhook URL in Portaly for each product
  - Mapping between Course.portaly_product_id and Portaly product ID

### Verification

- [x] T077 Test webhook signature verification (valid and invalid signatures)
- [x] T078 Test paid event: new user auto-registration with name/phone
- [x] T079 Test paid event: existing user purchase
- [x] T080 Verify idempotency (same webhook received twice)
- [x] T081 Test refund event: purchase status updated to 'refunded'
- [x] T082 Test error handling: productId not found, log but return 200
- [x] T083 Test Course/Show.vue email reminder displays correctly (guest vs logged-in)

**Checkpoint**: Complete purchase flow from course page to webhook processing (paid and refund)

---

## Phase 11: 全站配色優化 (2026-01-30 新增) 🎨

**Purpose**: 統一全站配色，提升視覺一致性和品牌識別度

**Color Scheme**:
- `#F6F1E9` - 米白色（頁面背景）
- `#FAA45E` - 橘色（強調元素、倒數計時數字）
- `#FF4438` - 紅色（促銷價格、警示）
- `#373557` - 深紫藍色（深色背景、主要文字）
- `#3F83A3` - 藍綠色（連結、按鈕、次要強調）

### Tailwind Configuration

- [x] T084 Extend Tailwind theme with custom colors in `tailwind.config.js`
  - Add `brand` color palette: cream (#F6F1E9), orange (#FAA45E), red (#FF4438), navy (#373557), teal (#3F83A3)

### Global Styles

- [x] T085 [P] Update body background to cream (#F6F1E9) in `resources/css/app.css` or base layout

### Navigation & Layout

- [x] T086 [P] Update Navigation.vue with new color scheme
  - Header background: navy (#373557)
  - Navigation links: white, hover teal (#3F83A3)
  - Login button: teal (#3F83A3)

- [x] T087 [P] Update Footer.vue with new color scheme
  - Background: navy (#373557)
  - Text: cream (#F6F1E9)
  - Links: teal (#3F83A3) hover

### Buttons & Interactive Elements

- [x] T088 Update primary buttons site-wide
  - Primary action (Buy, Login): teal (#3F83A3) background
  - Hover state: darker teal
  - Danger/Sale: red (#FF4438)

### Course Components

- [x] T089 [P] Update CourseCard.vue colors
  - Card background: white
  - Price text: red (#FF4438) for promo, navy (#373557) for regular
  - Status badges with appropriate colors

- [x] T090 [P] Update Course/Show.vue colors and layout
  - Promo price: red (#FF4438)
  - Original price strikethrough: gray
  - CTA button: teal (#3F83A3)
  - Price/timer displayed twice: header area + purchase section
  - Purchase section: two-column layout (price/timer left, consent/button right)

### Countdown Timer (PriceDisplay.vue)

- [x] T091 Update PriceDisplay countdown timer (simplified design)
  - Removed dark background container for cleaner look
  - Removed individual digit card backgrounds (reduced visual complexity)
  - Digit text: orange (#FAA45E) - prominent and easy to read
  - Label text: navy/60 (#373557 at 60% opacity)
  - Title "優惠倒數": navy/70 (#373557 at 70% opacity)
  - Simplified animation: subtle pulse on digit change

### Member Pages

- [x] T092 [P] Update Member/Learning.vue colors
  - Page heading: navy (#373557)
  - Empty state: appropriate styling

- [x] T093 [P] Update Member/Settings.vue colors
  - Section headings: navy (#373557)
  - Form inputs focus state: teal (#3F83A3) border

### Auth Pages

- [x] T094 [P] Update Auth/Login.vue colors
  - Form card: white on cream background
  - Submit button: teal (#3F83A3)
  - Links: teal (#3F83A3)

### Verification

- [x] T095 [P] Visual verification - Homepage colors consistent
- [x] T096 [P] Visual verification - Course detail page colors consistent
- [x] T097 [P] Visual verification - Countdown timer uses orange digits
- [x] T098 [P] Visual verification - Navigation and Footer match navy scheme
- [x] T099 Build verification - `npm run build` passes

**Checkpoint**: All pages use consistent color scheme with brand identity ✅

---

## Summary

| Phase | Tasks | Parallel Tasks |
|-------|-------|----------------|
| Phase 1: Setup | 8 | 4 |
| Phase 2: Foundational | 22 | 16 |
| Phase 3: US1 首頁 | 3 | 0 |
| Phase 4: US5 販售頁 | 4 | 0 |
| Phase 5: US2 登入 | 12 | 2 |
| Phase 6: US3 我的課程 | 4 | 0 |
| Phase 7: US4 帳號設定 | 4 | 0 |
| Phase 8: Polish | 7 | 4 |
| Phase 9: 縮圖 URL | 8 | 4 |
| Phase 10: Webhook 購買 | 17 | 2 |
| Phase 11: 全站配色優化 | 16 | 12 |
| **Total** | **105** | **44** |

---

## Notes

- [P] tasks = different files, no dependencies on incomplete tasks
- [Story] label maps task to specific user story
- Each user story independently completable after Phase 2
- Commit after each task or logical group
- Test on mobile viewport (320px+) after each page task
- Phase 9 tasks are incremental fixes and can be done independently
- Phase 10 implements US6 (Webhook) and updates US5 (Guest email input)
