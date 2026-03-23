# Tasks: 數位課程販售平台 MVP

**Input**: Design documents from `/specs/001-course-platform-mvp/`
**Prerequisites**: plan.md, spec.md, data-model.md, contracts/routes.md
**Updated**: 2026-01-30 - 全站配色優化 (Phase 11)、倒數計時器簡化設計
**Updated**: 2026-03-01 - 隱藏課程自動精簡 UI (Phase 12)
**Updated**: 2026-03-01 - 販售頁版面重設計 (Phase 13)
**Updated**: 2026-03-01 - 課程資訊欄、價格標示、按鈕樣式優化 (Phase 14)
**Updated**: 2026-03-08 - 課程縮圖統一 16:9 比例 (Phase 15)
**Updated**: 2026-03-09 - 課程 SEO 欄位 slug + meta_description (Phase 16)
**Updated**: 2026-03-09 - 販售頁「免費試閱」按鈕 (Phase 17)
**Updated**: 2026-03-09 - 我的課程頁面 card 增大 (Phase 18)
**Updated**: 2026-03-11 - 我的課程頁面未登入防護 (Phase 19)
**Updated**: 2026-03-19 - 販售頁 h3 標題左側色塊裝飾樣式 (Phase 20)

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

## Phase 12: 隱藏課程自動精簡 UI (2026-03-01 新增)

**Purpose**: 課程設為隱藏（`is_visible = false`）時，販售頁自動隱藏導覽列與返回連結，作為獨立 landing page 使用

**背景**：與既有的 `?lp=1` landing page 模式共用同一隱藏機制，統一由 `hideUiElements` computed 控制。

- [x] T100 [US5] Pass `isHidden` prop from CourseController to Course/Show page in `app/Http/Controllers/CourseController.php`
  - Add `'isHidden' => !$course->is_visible` to Inertia render props
- [x] T101 [US5] Add `hideUiElements` computed to Course/Show.vue in `resources/js/Pages/Course/Show.vue`
  - Add `isHidden` prop (Boolean, default false)
  - Add `hideUiElements = computed(() => isLandingMode.value || props.isHidden)`
  - Replace `:hide-nav="isLandingMode"` and `:hide-breadcrumb="isLandingMode"` with `hideUiElements`
  - Replace `v-if="!isLandingMode"` on back link with `v-if="!hideUiElements"`

**Checkpoint**: 課程設為隱藏後，販售頁不顯示導覽列和「返回課程列表」連結 ✅

---

## Phase 13: 販售頁版面重設計 (2026-03-01 新增)

**Purpose**: 提升課程販售頁視覺吸引力，改為分區段佈局，H1 標題置於影片上方

**背景**：原白色卡片設計視覺層次不足。重設計為無邊界分段佈局，課程標題突出，影片適中顯示，課程介紹中的 h2 標題以全寬深色方塊強調。

- [x] T102 [US5] 重構 `resources/js/Pages/Course/Show.vue` 版面結構
  - 移除白色卡片包裝（`bg-white rounded-lg shadow-sm`）
  - H1 標題 + 講師移至影片上方（米白背景，置中大字）
  - 影片/縮圖改為限寬（`max-w-3xl`）並加藍色 tagline 條
  - 移除影片下方促銷 CTA block 與頁底促銷橫幅
  - 新增 `purchaseSectionRef` + `handleBuyClick()`：未同意條款時 scroll 至購買區
- [x] T103 [P] [US5] 更新 `resources/css/app.css` `.course-content h2` 為 full-bleed 深色標題
  - 黑底白字（`#1a1a1a`），`margin: calc(-50vw + 50%)` 突破容器寬度
  - 父容器加 `overflow-x: hidden` 防止橫向捲動

**Checkpoint**: 課程販售頁 H1 在影片上方，影片 max-w-3xl，介紹內 h2 為全寬深色方塊 ✅

---

## Phase 14: 課程資訊欄、價格標示、按鈕樣式優化 (2026-03-01 新增)

**Purpose**: 填補影片下方空白，補充課程資訊，統一按鈕樣式，優化價格標示

**背景**：版面重設計後影片下方出現空白；按鈕形狀和顏色不一致；價格標示缺少「優惠價」說明和標準幣別格式。

- [x] T104 [US5] 新增課程資訊欄至 `resources/js/Pages/Course/Show.vue`（影片正下方）
  - 左欄：課程類型、預計時長、授課講師、觀看限制（靜態）、預購狀態
  - 右欄：PriceDisplay + scroll-to-purchase 快速按鈕（不重複購買邏輯）
- [x] T105 [P] [US5] 新增靜態「觀看限制：不限時間、次數」欄位 in `resources/js/Pages/Course/Show.vue`
  - 不讀取 DB，硬編碼靜態文字
- [x] T106 [P] [US5] 統一按鈕樣式 in `resources/js/Pages/Course/Show.vue`
  - `rounded-full` → `rounded-lg`（全部按鈕）
  - Drip 訂閱按鈕由 `bg-indigo-600` 改為金色統一樣式
  - 補齊 `hover:shadow-md active:scale-[0.98] cursor-pointer`
- [x] T107 [P] [US5] 更新 PriceDisplay in `resources/js/Components/Course/PriceDisplay.vue`
  - 兩種狀態皆加「優惠價」灰色小標籤
  - 幣別格式 `NT$` → `NTD$`（自訂格式）
  - 購買區移除白色卡片包裝

**Checkpoint**: 影片下方無空白，課程資訊清晰呈現，所有按鈕圓角矩形金色樣式一致，價格顯示「優惠價 NTD$XXX」✅

---

## Phase 15: 課程縮圖統一 16:9 比例 (2026-03-08 新增)

**Purpose**: 全站課程縮圖顯示區域統一為 16:9 比例，與課程販售頁一致

**背景**：首頁課程卡使用 3:2，我的課程卡使用固定高度，與販售頁 16:9 不一致。

- [x] T108 [P] [US1] 更新 CourseCard 縮圖比例 in `resources/js/Components/CourseCard.vue`
  - `aspect-[3/2]` → `aspect-video`
- [x] T109 [P] [US3] 更新 MyCourseCard 縮圖比例 in `resources/js/Components/MyCourseCard.vue`
  - 固定高度容器改為 `aspect-video`，圖片改為 `h-full object-cover`

**Checkpoint**: 首頁、我的課程、販售頁縮圖皆以 16:9 比例顯示 ✅

---

## Phase 16: 課程 SEO 欄位 slug + meta_description (2026-03-09 新增)

**Purpose**: 新增 SEO 友善 URL（slug）與搜尋描述（meta_description）欄位，提升課程頁面在 Google 的可見度

**背景**：URL `/course/1` 對 Google 無語意；tagline 行銷用途與搜尋描述目的不同，分開管理更有效。

- [x] T110 [US5] 新增 Migration 加入 slug 與 meta_description 欄位 in `database/migrations/2026_03_08_180036_add_seo_fields_to_courses_table.php`
  - `slug` string nullable unique after name
  - `meta_description` string(160) nullable after tagline
- [x] T111 [P] [US5] 更新 Course Model 支援 slug 路由解析 in `app/Models/Course.php`
  - 加入 `slug`、`meta_description` 至 `$fillable`
  - 新增 `resolveRouteBinding()`：先查 slug，找不到再查 id（向下相容）
- [x] T112 [P] [US5] 更新 CourseController OG description fallback in `app/Http/Controllers/CourseController.php`
  - `description` 改為 `meta_description ?: tagline ?: name`
- [x] T113 [P] [US1] 更新 SitemapController + sitemap.blade 輸出 slug URL in `app/Http/Controllers/SitemapController.php` + `resources/views/sitemap.blade.php`
  - 查詢加入 `slug` 欄位
  - `<loc>` 改為 `slug ?: id`

**Checkpoint**: 設定 slug 後可用 `/course/my-slug` 訪問；舊 `/course/{id}` 仍可用；sitemap 輸出 slug URL；OG description 優先使用 meta_description ✅

---

## Phase 17: 販售頁「免費試閱」按鈕 (2026-03-09 新增)

**Purpose**: 讓未購買訪客可在販售頁直接點擊試閱，提升轉換率

**背景**：新增試閱教室（Phase 31 of 002）後，販售頁需對應顯示入口按鈕。僅非 drip、非草稿、有 `is_preview` 小節的課程才顯示按鈕。

- [x] T114 [US5] 計算 `$hasPreviewLessons` 並傳入 Inertia prop in `app/Http/Controllers/CourseController.php`
  - 條件：`!$isDraft && !$isDrip && $course->hasPreviewLessons()`
- [x] T115 [P] [US5] 新增 `hasPreviewLessons` prop 並在課程資訊欄右側加入「免費試閱」按鈕 in `resources/js/Pages/Course/Show.vue`
  - `<a target="_blank">` 含播放 icon，`v-if="hasPreviewLessons && !isDrip && !isPreviewMode"`
  - 與「立即購買」按鈕並排（`flex-row gap-2`）
- [x] T116 [P] [US5] 底部購買區加入「免費試閱」按鈕（同條件）in `resources/js/Pages/Course/Show.vue`
  - 「免費試閱」在左、「立即購買」在右

**Checkpoint**: 有 preview 小節的非 drip 課程販售頁顯示「免費試閱」按鈕；drip 課程、草稿課程、無 preview 小節的課程不顯示 ✅

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
| Phase 12: 隱藏課程精簡 UI | 2 | 0 |
| Phase 13: 販售頁版面重設計 | 2 | 1 |
| Phase 14: 課程資訊欄、價格標示、按鈕樣式 | 4 | 3 |
| Phase 15: 課程縮圖統一 16:9 比例 | 2 | 2 |
| Phase 16: 課程 SEO 欄位 slug + meta_description | 3 | 2 |
| Phase 17: 販售頁「免費試閱」按鈕 | 3 | 2 |
| Phase 18: 我的課程 card 增大 | 1 | 1 |
| Phase 19: 我的課程未登入防護 | 1 | 0 |
| Phase 20: 販售頁 h3 色塊樣式 | 1 | 0 |
| Phase 21: 販售頁懸浮購買面板 | 2 | 1 |
| Phase 22: PayUni + 免費報名 | 13 | 8 |
| **Total** | **139** | **64** |

## Phase 20: 販售頁 h3 標題左側色塊裝飾樣式 (2026-03-19 新增)

**Purpose**: 課程介紹中的 h3 標題加入左側深色色塊裝飾，強化視覺層次

**背景**：課程販售頁 Markdown 介紹中的 h3 標題原為純文字，缺乏視覺層次；改為左側附有 10px 深色長方形色塊（與 h3 文字垂直置中，間距 15px）的設計，與設計稿一致。

- [x] T119 [US5] 更新 `.course-content h3` 樣式：flexbox 左對齊 + `::before` 偽元素色塊 in `resources/css/app.css`
  - `.course-content h3` 改為 `display: flex; align-items: center; gap: 15px`
  - 新增 `::before`：`width: 10px; height: 1.2em; background-color: #1f2937; flex-shrink: 0; border-radius: 1px`

**Checkpoint**: 課程販售頁 h3 標題左側顯示 10px 深色長方形色塊，與文字垂直置中，間距 15px ✅

---

## Phase 19: 我的課程頁面未登入防護 (2026-03-11 新增)

**Purpose**: 未登入者因 Inertia SPA 快取看到頁面時，顯示「請先登入」提示而非「尚無課程」

**背景**：Inertia.js SPA history cache 可能讓未登入者看到他人 auth 狀態的舊 cached 頁面，誤以為被指派的課程不見了。Server-side auth middleware 已有 redirect，此為 client-side 第二層防護。

- [x] T118 [US3] 我的課程 client-side 未登入防護 in `resources/js/Pages/Member/Learning.vue`
  - 引入 `usePage`、新增 `isLoggedIn` computed（`!!page.props.auth?.user`）
  - 新增 `v-if="!isLoggedIn"` 區塊：人形 icon + 「請先登入」+ 「前往登入」按鈕
  - 原課程列表改為 `v-else-if courses.length > 0`，空白狀態改為 `v-else`
  - `courses` prop 改為 `default: () => []` 防止 server 未傳值時報錯

**Checkpoint**: 未登入狀態下訪問 /member/learning 顯示「請先登入」提示，而非「尚無課程」 ✅

---

## Phase 18: 我的課程頁面 Card 增大 (2026-03-09 新增)

**Purpose**: 將我的課程頁面的課程 card 由最多 4 欄調整為最多 2 欄，使每張 card 約 500px 寬

**背景**：原本 4 欄 grid 導致 card 過小（約 250px），縮圖難以辨識；增大後提升視覺清晰度。

- [x] T117 [US3] 我的課程頁面 card 增大：容器改 `max-w-[1100px]`、grid 改 `grid-cols-1 sm:grid-cols-2` in `resources/js/Pages/Member/Learning.vue`
  - `max-w-7xl` → `max-w-[1100px]` 讓容器寬度配合 2 欄 500px card
  - grid 從 `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4` 縮減為 `grid-cols-1 sm:grid-cols-2`

**Checkpoint**: 桌機上每張 card 約 506px 寬，手機維持全寬單欄 ✅

---

---

## Phase 21: 販售頁懸浮購買面板 (2026-03-22 新增)

**Purpose**: scroll 過頂部資訊區後從右側滑入懸浮面板，顯示價格、優惠倒數計時與購買按鈕，底部購買區可見時自動收回

**背景**：提升販售頁的轉換率，讓用戶在滾動頁面時隨時可見購買入口；僅限非 drip、非草稿、有付款管道的課程顯示。

- [x] T120 [P] [US5] 更新 FR-031 懸浮面板顯示條件 in `app/Http/Controllers/CourseController.php`
  - `hasBuyAction` 由 `use_payuni`/`is_free`/`portaly_url` 組合決定
- [x] T121 [US5] 實作懸浮購買面板 in `resources/js/Pages/Course/Show.vue`
  - 使用 `IntersectionObserver` 監測頂部資訊欄與底部購買區是否可見
  - 面板包含：`PriceDisplay`（含優惠倒數計時）、免費試閱按鈕（若有）、購買按鈕
  - 顯示條件：`showFloatingPanel && !isDrip && !isPreviewMode && hasBuyAction`
  - 動畫：從右側滑入（`translate-x-full` → `translate-x-0`，`transition-transform`）

**Checkpoint**: 非 drip 課程販售頁 scroll 過資訊區後右下角顯示懸浮面板；scroll 回購買區或無付款管道時收起 ✅

---

## Phase 22: PayUni 統一金流 + 免費課程直接報名 (2026-03-23 新增)

**Purpose**: 新增 PayUni 付費路徑（portaly_product_id 空且 price > 0）與免費 inline 表單報名路徑（portaly_product_id 空且 price = 0）

**背景**：US7 / US8 — 課程不使用 Portaly 時需要獨立金流處理。PayUni 使用 AES-256-GCM 加密（不使用 SDK echo/exit），免費課程直接建立購買紀錄。

- [x] T122 [P] 新增 `payuni_trade_no` 欄位 migration in `database/migrations/2026_03_23_060722_add_payuni_trade_no_to_purchases_table.php`
  - `varchar(64) nullable unique`，置於 `portaly_order_id` 之後；`php artisan migrate` 已執行
- [x] T123 [P] 新增 PayUni config 區塊 in `config/services.php`
  - `merchant_id`, `hash_key`, `hash_iv`, `sandbox` 四個 env 對應值
- [x] T124 [P] 更新 Purchase model `$fillable` 加入 `payuni_trade_no` in `app/Models/Purchase.php`
- [x] T125 [US7] 建立 `PayuniService` in `app/Services/PayuniService.php`
  - `generateMerTradeNo(courseId)` → `YUE-C{courseId:06d}-{YmdHis}-{rand4}`
  - `parseCourseId(merTradeNo)` → regex `/^YUE-C(\d+)-/`
  - `buildPaymentForm(course, email, merTradeNo)` → `{ endpoint, fields }` (AES-256-GCM encrypt)
  - `verifyAndDecrypt(encryptInfo, hashInfo)` → 驗證 hash 後解密
  - `processNotify(request)` → 建立 Purchase，回傳 `'1|OK'`
  - `getOrCreateUser(email, name, phone)` → 建立或更新用戶（姓名電話以最新為準）
- [x] T126 [US7] 建立 `PayuniController` in `app/Http/Controllers/Payment/PayuniController.php`
  - `initiate`: 驗證課程（無 portaly_id, price>0, 非草稿），組裝 email，回傳 `{ endpoint, fields }` JSON
  - `notify`: 轉交 `processNotify`，固定回傳 200 plain text `1|OK`
  - `return`: 解密結果，成功 redirect `/member/learning`，失敗 redirect `/course/{id}?payment_failed=1`
- [x] T127 [P] [US8] 建立 `FreePurchaseController` in `app/Http/Controllers/Purchase/FreePurchaseController.php`
  - 驗證課程（無 portaly_id, price=0, 非草稿），驗證 email/name/phone
  - 冪等檢查 `Purchase::where(user_id, course_id)->exists()`
  - 建立 Purchase（`source=free, amount=0, status=paid`）；drip 課程自動 subscribe
- [x] T128 [P] 新增 4 條 API 路由 in `routes/api.php`
  - `POST /payment/payuni/initiate`, `POST /webhooks/payuni`, `POST /payment/payuni/return`
  - `POST /purchase/free/{course}`
- [x] T129 [P] [US7] 更新 CourseController 傳入 PayUni/免費相關 props in `app/Http/Controllers/CourseController.php`
  - `use_payuni`, `is_free`, `display_price`
- [x] T130 [US7] Show.vue 新增 PayUni 付費流程 in `resources/js/Pages/Course/Show.vue`
  - computed: `usePayuni`, `isFree`, `hasBuyAction`
  - `payuniEmail`（已登入預填，未登入顯示輸入欄）、`payuniSubmitting`、`payuniError`
  - `initiatePayuni()`: axios POST → 動態建 form 並 submit
  - 更新 `handleBuyClick()`: free → `openFreeForm()`; payuni → `initiatePayuni()`
- [x] T131 [P] [US8] Show.vue 新增免費報名 inline 表單 in `resources/js/Pages/Course/Show.vue`
  - `showFreeForm`, `freeFormEmail/Name/Phone`, `freeSubmitting`, `freeSuccess`, `freeError`, `showFreeConfirm`
  - `submitFreeEnrollment()`: axios POST → 成功顯示綠色訊息 + 前往我的課程連結
  - 未登入送出前顯示確認提示；表單預填已登入用戶資料
- [x] T132 [P] [US5] 更新浮動面板與 Section 6b 按鈕條件為 `hasBuyAction` in `resources/js/Pages/Course/Show.vue`
  - 浮動面板條件：`showFloatingPanel && !isDrip && !isPreviewMode && hasBuyAction`
  - 按鈕 disabled: `!isPreviewMode && (!agreed || !hasBuyAction)`；免費課程不顯示同意條款
- [ ] T133 [US7] 後端傳入 `hasPurchased` prop + 前端「前往學習」按鈕 in `app/Http/Controllers/CourseController.php` + `resources/js/Pages/Course/Show.vue`
  - `'hasPurchased' => $user ? Purchase::where('user_id', $user->id)->where('course_id', $course->id)->where('status', 'paid')->exists() : false`
  - 前端：`hasPurchased` 為 true 時，主購買按鈕與浮動面板按鈕改為「前往學習」，導向 `/course/{id}/classroom`
- [ ] T134 [P] [US7] 登入頁顯示 PayUni 購買完成提示 in `resources/js/Pages/Auth/Login.vue`
  - `PayuniController::return()` redirect 到 `/member/learning`，未登入時 Laravel auth middleware 導向 `/login?hint=payuni`
  - 登入頁讀取 `?hint=payuni` query param，顯示提示橫幅「請用購買時的 email 登入查看課程」

**Checkpoint**:
- PayUni 課程（無 portaly_id, price > 0）：點擊購買 → 導到 PayUni 付款頁；完成付款 → purchases 有新紀錄（source=payuni）；導回 /member/learning ✅
- 免費課程（無 portaly_id, price = 0）：點擊「免費報名」→ 展開表單；送出 → purchases 有新紀錄（source=free, amount=0）；顯示成功訊息 ✅
- Portaly 課程（有 portaly_product_id）：完全不受影響 ✅
- 已購買：T133 完成後按鈕改為「前往學習」（待完成）
- 未登入 PayUni 引導：T134 完成後登入頁顯示提示（待完成）

---

## Notes

- [P] tasks = different files, no dependencies on incomplete tasks
- [Story] label maps task to specific user story
- Each user story independently completable after Phase 2
- Commit after each task or logical group
- Test on mobile viewport (320px+) after each page task
- Phase 9 tasks are incremental fixes and can be done independently
- Phase 10 implements US6 (Webhook) and updates US5 (Guest email input)
