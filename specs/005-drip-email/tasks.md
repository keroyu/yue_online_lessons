# Tasks: Email 連鎖加溫系統 (Drip Email System)

**Input**: Design documents from `/specs/005-drip-email/`
**Prerequisites**: plan.md, spec.md, data-model.md, contracts/, research.md, quickstart.md
**Tests**: Not explicitly requested. Tests NOT included.

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US6)
- Exact file paths included in descriptions

---

## Phase 1: Setup (Database Migrations)

**Purpose**: Create all database schema changes needed for the drip email system

- [x] T001 Create migration to add `course_type` (enum: standard/drip, default standard) and `drip_interval_days` (unsigned int, nullable) columns to courses table in `database/migrations/`
- [x] T002 [P] Create migration for `drip_subscriptions` table (user_id FK, course_id FK, subscribed_at, emails_sent, status enum, status_changed_at, unsubscribe_token UUID unique, unique user_id+course_id, index course_id+status) in `database/migrations/`
- [x] T003 [P] Create migration for `drip_conversion_targets` table (drip_course_id FK, target_course_id FK, unique drip_course_id+target_course_id, index target_course_id) in `database/migrations/`
- [x] T004 [P] Create migration to add `promo_delay_seconds` (unsigned int, nullable) and `promo_html` (text, nullable) columns to lessons table after `html_content` in `database/migrations/`

---

## Phase 2: Foundational (Models + Shared Infrastructure)

**Purpose**: Core models, relationships, mail, job, and route registration that ALL user stories depend on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T005 [P] Create DripSubscription model with $fillable, casts (subscribed_at datetime, status_changed_at datetime, emails_sent integer), booted() for auto UUID, user()/course() relationships, scopeActive(), isActive accessor in `app/Models/DripSubscription.php`
- [x] T006 [P] Create DripConversionTarget model with $fillable (drip_course_id, target_course_id), dripCourse()/targetCourse() BelongsTo relationships in `app/Models/DripConversionTarget.php`
- [x] T007 [P] Modify Course model: add course_type and drip_interval_days to $fillable, add drip_interval_days integer cast, add isDrip accessor, add scopeDrip, add dripConversionTargets() and dripSubscriptions() HasMany relationships in `app/Models/Course.php`
- [x] T008 [P] Modify Lesson model: add promo_delay_seconds and promo_html to $fillable, add promo_delay_seconds integer cast, add hasPromoBlock and isPromoImmediate accessors in `app/Models/Lesson.php`
- [x] T009 [P] Modify User model: add dripSubscriptions() HasMany and activeDripSubscriptions() (filtered by status=active) relationships in `app/Models/User.php`
- [x] T010 [P] Create DripLessonMail mailable (implements ShouldQueue, uses Queueable+SerializesModels) with envelope() (subject: lesson title) and content() referencing drip-lesson blade template in `app/Mail/DripLessonMail.php`
- [x] T011 [P] Create drip lesson email Blade template with lesson title, full html_content (when present), video notice (if has_video), fallback text for pure-video lessons without html_content ("本課程包含教學影片，請至網站觀看完整內容" with classroom link), classroom link (`/member/classroom/{course_id}`), and unsubscribe link (`/drip/unsubscribe/{token}`) in `resources/views/emails/drip-lesson.blade.php`
- [x] T012 [P] Create SendDripEmailJob (implements ShouldQueue, $tries=3, $backoff=[60,300,900]) that accepts userId (int), lessonId (int), subscriptionId (int) as primitive constructor params, loads models in handle(), and dispatches DripLessonMail in `app/Jobs/SendDripEmailJob.php`
- [x] T013 Register all drip routes in `routes/web.php`: public drip group (POST subscribe, POST verify, GET/POST unsubscribe/{token}), member auth group (POST drip/subscribe/{course}), admin group (GET courses/{course}/subscribers)

**Checkpoint**: Foundation ready - all models exist, email infrastructure ready, routes registered

---

## Phase 3: US1 + US1.5 - 訂閱連鎖課程 (Priority: P1) 🎯 MVP

**Goal**: 訪客可透過 Email+驗證碼訂閱免費連鎖課程，已登入會員可一鍵訂閱。訂閱後立即發送歡迎信，後續每天 9 點排程發信。

**Independent Test**: 建立測試連鎖課程 → 訪客輸入 Email 訂閱 → 驗證歡迎信發送。已登入會員點擊訂閱 → 驗證訂閱記錄建立。

### Implementation

- [x] T014 [US1] Create DripService with subscribe() method: check re-subscription prevention (unsubscribed users blocked), create DripSubscription record, **dispatchSync** welcome email (first lesson) via SendDripEmailJob (immediate send, not queued), increment emails_sent to 1 in `app/Services/DripService.php`
- [x] T015 [P] [US1] Create StoreDripSubscriptionRequest Form Request with validation rules: course_id (required, exists:courses,id), email (required_without:user_id, email) for guest flow; code (required) for verify flow. Add Chinese error messages in `app/Http/Requests/StoreDripSubscriptionRequest.php`
- [x] T016 [US1] Create DripSubscriptionController with: subscribe() sends verification code via VerificationCodeService, verify() completes subscription via DripService and auto-creates/logs-in user, memberSubscribe() for logged-in one-click subscribe. Use StoreDripSubscriptionRequest for validation in `app/Http/Controllers/DripSubscriptionController.php`
- [x] T017 [P] [US1] Create DripSubscribeForm.vue component: email input field, verification code input, two-step form (enter email → enter code), uses Inertia router.post for /drip/subscribe and /drip/verify in `resources/js/Components/Course/DripSubscribeForm.vue`
- [x] T018 [US1] Modify course detail page controller: pass additional props for drip courses (is_drip, user_subscription status, can_subscribe boolean) to Course/Show.vue in `app/Http/Controllers/CourseController.php`
- [x] T019 [US1] Modify Course/Show.vue: for drip courses show DripSubscribeForm (guests), one-click subscribe button (logged-in members), or "已訂閱" status badge (existing subscribers), hide Portaly purchase section for free drip courses in `resources/js/Pages/Course/Show.vue`
- [x] T020 [US1] Add processDailyEmails() to DripService: query active subscriptions (only published drip courses), for each calculate unlocked lesson count via `floor(daysSince / interval) + 1`, compare with emails_sent, dispatch SendDripEmailJob for each unsent lesson, mark status=completed when all sent in `app/Services/DripService.php`
- [x] T021 [US1] Create ProcessDripEmails artisan command (signature: `drip:process-emails`) that calls DripService->processDailyEmails() and outputs sent count in `app/Console/Commands/ProcessDripEmails.php`
- [x] T022 [US1] Register daily schedule `Schedule::command('drip:process-emails')->dailyAt('09:00')` in `routes/console.php`

**Checkpoint**: 訪客和會員都可以訂閱連鎖課程，歡迎信立即發送，每日排程發信正常運作

---

## Phase 4: US6 - 在教室中觀看連鎖課程 (Priority: P1)

**Goal**: 訂閱者進入教室頁面，已解鎖 Lesson 可觀看，未解鎖顯示「X 天後解鎖」倒數

**Independent Test**: 以不同訂閱天數的帳號進入教室，驗證各自看到正確的解鎖狀態和倒數天數

**Depends on**: Phase 2 (models), benefits from Phase 3 (subscription data for testing)

### Implementation

- [x] T023 [US6] Add isLessonUnlocked(subscription, lesson) and daysUntilUnlock(subscription, lesson) helper methods to DripService: use formula `unlockDay = sort_order × drip_interval_days` (sort_order 0-based), compare with `sort_order < unlockedCount` in `app/Services/DripService.php`
- [x] T024 [US6] Modify ClassroomController.show(): for drip courses, load user's DripSubscription, calculate per-lesson is_unlocked and unlock_in_days, pass subscription data as Inertia prop, block access to locked lesson content in formatLessonFull() in `app/Http/Controllers/Member/ClassroomController.php`
- [x] T025 [US6] Modify Classroom.vue: for drip courses, show lock overlay with "X 天後解鎖" text on locked lessons in sidebar, hide locked lesson titles as "******", prevent selecting locked lessons, display subscription status in `resources/js/Pages/Member/Classroom.vue`

**Checkpoint**: 教室頁面正確顯示解鎖狀態，已解鎖可觀看，未解鎖顯示倒數

---

## Phase 5: US3 - 管理員設定連鎖課程 (Priority: P2)

**Goal**: 管理員可在後台設定課程類型為連鎖課程、設定發信間隔天數、指定目標課程

**Independent Test**: 管理員建立完整連鎖課程設定（類型、間隔、目標課程），儲存後前台顯示正確

### Implementation

- [x] T026 [US3] Modify UpdateCourseRequest: add validation rules for course_type (required, in:standard,drip), drip_interval_days (required_if:course_type,drip, integer, min:1, max:30), target_course_ids (nullable array, each exists:courses,id) in `app/Http/Requests/Admin/UpdateCourseRequest.php`
- [x] T027 [US3] Modify Admin CourseController: in update() method save course_type and drip_interval_days, sync DripConversionTargets from target_course_ids array; in edit() method pass available courses (excluding self) and current target_course_ids as Inertia props in `app/Http/Controllers/Admin/CourseController.php`
- [x] T028 [US3] Modify Admin Courses/Edit.vue: add drip settings section (visible when course_type=drip) with interval days input, target course multi-select, and lesson schedule preview table showing Day 0/3/6/9... per lesson in `resources/js/Pages/Admin/Courses/Edit.vue`

**Checkpoint**: 管理員可完整設定連鎖課程參數，儲存後正確保存

---

## Phase 6: US2 - 付費購買連鎖課程 (Priority: P2)

**Goal**: 使用者透過 Portaly 付款購買連鎖課程後，系統自動建立訂閱並開始連鎖流程

**Independent Test**: 模擬 Portaly webhook 付款成功，驗證 DripSubscription 建立和歡迎信發送

**Depends on**: US1 (subscribe infrastructure)

### Implementation

- [x] T029 [US2] Modify PortalyWebhookService: after purchase creation in handlePaidEvent(), check if purchased course is drip type, if so call DripService->subscribe() to auto-create subscription and send welcome email in `app/Services/PortalyWebhookService.php`

**Checkpoint**: Portaly 付款成功後，自動建立 drip 訂閱並發送歡迎信

---

## Phase 7: US4 - 購買目標課程後自動轉換 (Priority: P2)

**Goal**: 訂閱者購買任一目標課程後，系統自動標記為 converted、停止發信、解鎖全部 Lesson

**Independent Test**: 設定目標課程 → 訂閱者購買目標課程 → 驗證狀態變 converted、所有 Lesson 解鎖

**Depends on**: US3 (conversion targets setup), US2 (purchase webhook)

### Implementation

- [x] T030 [US4] Add checkAndConvert(user, purchasedCourse) method to DripService: query DripConversionTarget for purchased course_id, find user's active subscriptions to matching drip courses, update status to converted and set status_changed_at in `app/Services/DripService.php`
- [x] T031 [US4] Modify PortalyWebhookService.handlePaidEvent(): after purchase creation, call DripService->checkAndConvert(user, course) to detect and process conversions in `app/Services/PortalyWebhookService.php`
- [x] T032 [US4] Modify ClassroomController: for converted/completed subscriptions, unlock all lessons regardless of time-based calculation in `app/Http/Controllers/Member/ClassroomController.php`

**Checkpoint**: 購買目標課程後，訂閱狀態自動變為 converted，所有 Lesson 解鎖

---

## Phase 8: US8 + US9 - Lesson 促銷區塊 (Priority: P2)

**Goal**: 管理員可設定 Lesson 的延遲顯示促銷區塊（自訂 HTML），使用者觀看指定時間後才顯示。適用所有課程類型。

**Independent Test**: 管理員設定 Lesson 促銷延遲 1 分鐘 → 會員觀看 1 分鐘後促銷區塊出現 → 重整後直接顯示

### Implementation

- [x] T033 [P] [US8] Modify StoreLessonRequest: add validation rules for promo_delay_seconds (nullable, integer, min:0, max:7200) and promo_html (nullable, string, max:10000) with Chinese error messages in `app/Http/Requests/Admin/StoreLessonRequest.php`
- [x] T034 [P] [US8] Modify LessonForm.vue: add "促銷區塊設定" section with promo_delay_seconds number input (placeholder: 留空則不顯示) and promo_html textarea, add fields to form data in `resources/js/Components/Admin/LessonForm.vue`
- [x] T034b [P] [US9] Modify ChapterController@index: add promo_delay_seconds and promo_html to lesson map arrays (both chapter lessons and standalone lessons) so LessonForm receives promo data when editing in `app/Http/Controllers/Admin/ChapterController.php`
- [x] T035 [US8] Create LessonPromoBlock.vue component: props (lessonId, delaySeconds, promoHtml), localStorage persistence for both unlock status (`promo_unlocked_lesson_{id}`) AND elapsed seconds (`promo_elapsed_lesson_{id}`) to support mid-session interruption resume, countdown timer (restore elapsed on mount, persist every 5s + on unmount, formatted MM:SS display), v-html render when unlocked, "解鎖進階資訊，請先完成學習" placeholder when locked, clean up elapsed key on unlock in `resources/js/Components/Classroom/LessonPromoBlock.vue`
- [x] T036 [US8] Modify ClassroomController.formatLessonFull(): add promo_delay_seconds and promo_html to returned array in `app/Http/Controllers/Member/ClassroomController.php`
- [x] T037 [US8] Modify Classroom.vue: import and render LessonPromoBlock below lesson content when currentLesson has promo settings (promo_delay_seconds !== null && promo_html not empty) in `resources/js/Pages/Member/Classroom.vue`

**Checkpoint**: 促銷區塊在倒數完成後顯示，重整後永久顯示，admin 可設定

---

## Phase 9: US5 - 使用者退訂連鎖課程 (Priority: P3)

**Goal**: 使用者點擊 Email 退訂連結，確認後停止接收後續 Email，已解鎖 Lesson 仍可觀看

**Independent Test**: 點擊退訂連結 → 顯示警告頁 → 確認退訂 → 驗證狀態更新、不再收到 Email、已解鎖內容仍可觀看

### Implementation

- [x] T038 [US5] Add showUnsubscribe(token) and unsubscribe(token) methods to DripSubscriptionController: validate token, render Drip/Unsubscribe page with subscription/course info, process unsubscribe (update status to unsubscribed, set status_changed_at), redirect to homepage with flash in `app/Http/Controllers/DripSubscriptionController.php`
- [x] T039 [P] [US5] Create Unsubscribe.vue page: display course name, warning message "這是限期商品，一旦退訂將無法再次訂閱此課程", confirm button, Inertia form POST to /drip/unsubscribe/{token} in `resources/js/Pages/Drip/Unsubscribe.vue`
- [x] T040 [US5] Modify ClassroomController: for unsubscribed users, only show lessons unlocked up to the point of unsubscription (based on emails_sent count, not current time), prevent new unlocks in `app/Http/Controllers/Member/ClassroomController.php`

**Checkpoint**: 退訂流程完成，不再收到 Email，已解鎖 Lesson 仍可觀看

---

## Phase 10: US7 - 管理員查看訂閱者清單 (Priority: P3)

**Goal**: 管理員可查看連鎖課程的訂閱者清單、狀態篩選、統計資訊

**Independent Test**: 在有訂閱者的連鎖課程後台查看清單、篩選狀態、確認統計數字正確

### Implementation

- [x] T041 [US7] Add subscribers() method to Admin CourseController: paginated DripSubscription list with user eager loading, status filter query param, stats aggregation (total/active/converted/completed/unsubscribed counts), render Admin/Courses/Subscribers page in `app/Http/Controllers/Admin/CourseController.php`
- [x] T042 [US7] Create Subscribers.vue page: stats summary cards, status filter dropdown, subscriber table (email, nickname, subscribed_at, emails_sent, status, status_changed_at), pagination in `resources/js/Pages/Admin/Courses/Subscribers.vue`

**Checkpoint**: 管理員可查看和篩選訂閱者清單

---

## Phase 11: US10 - Drip 影片免費觀看期限提醒 (Priority: P2)

**Goal**: Drip 課程 Lesson 解鎖後 48 小時內為免費觀看期，過期後影片仍可觀看但顯示加強版促銷區塊（方案 A：軟性提醒，不鎖定影片）

**Independent Test**: 訂閱 drip 課程 → 修改 subscribed_at 使 Lesson 超過 48 小時 → 驗證影片仍可播放、過期促銷區塊出現、converted 使用者不顯示

**Depends on**: Phase 4 (US6 - ClassroomController drip 支援), Phase 7 (US4 - conversion targets 用於促銷區塊)

### Implementation

- [x] T047 [P] [US10] Create config file with `video_access_hours` setting (default 48, env override `DRIP_VIDEO_ACCESS_HOURS`) in `config/drip.php`
- [x] T048 [US10] Add getVideoAccessExpiresAt(subscription, lesson), isVideoAccessExpired(subscription, lesson), getVideoAccessRemainingSeconds(subscription, lesson) methods to DripService: calculate expiry as `subscribed_at + (sort_order × interval) days + config hours`, return null if config is null (feature disabled) in `app/Services/DripService.php`
- [x] T049 [US10] Modify ClassroomController.formatLessonFull(): for drip courses add video_access_expired (bool) and video_access_remaining_seconds (int|null) per lesson (skip for converted users and lessons without video); in show() add videoAccessTargetCourses prop with target course id/name/url from DripConversionTarget in `app/Http/Controllers/Member/ClassroomController.php`
- [x] T050 [P] [US10] Create VideoAccessNotice.vue component: props (expired bool, remainingSeconds number|null, targetCourses array), countdown timer with HH:MM:SS format (reload page when reaches 0), green "免費公開中" notice when within window, amber urgency promo block when expired with target course purchase buttons or generic "探索更多課程" fallback in `resources/js/Components/Classroom/VideoAccessNotice.vue`
- [x] T051 [US10] Modify Classroom.vue: import VideoAccessNotice, render below video player (above LessonPromoBlock) when course is drip + lesson has video + subscription not converted + (expired or remaining > 0), pass videoAccessTargetCourses prop in `resources/js/Pages/Member/Classroom.vue`
- [x] T052 [P] [US10] Modify drip-lesson email template: for lessons with video_id, add "⏰ 影片 {hours} 小時內免費觀看，把握時間！" notice below the video prompt, only show when config('drip.video_access_hours') is not null in `resources/views/emails/drip-lesson.blade.php`

**Checkpoint**: 免費觀看期內顯示倒數、過期後顯示促銷區塊、converted 使用者不顯示、Email 包含免費觀看提示

---

## Phase 12: Polish & Cross-Cutting Concerns

**Purpose**: Final validation and refinements across all stories

- [x] T043 Verify re-subscription prevention: unsubscribed users see "此課程已無法再次訂閱" when attempting to subscribe again
- [x] T044 Verify email retry mechanism: SendDripEmailJob retries up to 3 times with backoff [60, 300, 900] seconds
- [ ] T045 Run quickstart.md validation scenarios end-to-end (sections A through I)
- [x] T046 Verify all new/modified pages are responsive (mobile-first RWD per project conventions)
- [x] T053 Verify VideoAccessNotice responsive design: countdown and urgency promo block display correctly on mobile

---

## Phase 13: US11 - 準時到課獎勵區塊 (Priority: P2)

**Goal**: 在免費觀看期倒數旁加入獎勵欄。會員進入頁面計時，停留滿設定時間後顯示管理員自訂獎勵 HTML。免費期逾期後保留已達標的獎勵，未達標則顯示「下次早點來喔，錯過了獎勵 :(」提示。

**Independent Test**: 管理員設定某 Lesson 的 reward_html → 以訂閱者身份進入教室 → 驗證左側倒數右側顯示「你準時來上課了！真棒」→ 調低 `config(drip.reward_delay_minutes)` 為 0 後進入 → 驗證右側立即切換為 reward_html → 重整後仍直接顯示獎勵。

**Depends on**: Phase 11 (US10 VideoAccessNotice.vue 已建立)

### Implementation

- [x] T054 [P] [US11] Create migration to add `reward_html` (text, nullable) column to lessons table after `promo_html` in `database/migrations/`
- [x] T055 [P] [US11] Modify `config/drip.php`: add `reward_delay_minutes` key (default 10, env override `DRIP_REWARD_DELAY_MINUTES`) alongside existing `video_access_hours` in `config/drip.php`
- [x] T056 [P] [US11] Modify Lesson model: add `reward_html` to `$fillable` in `app/Models/Lesson.php`
- [x] T057 [P] [US11] Modify Admin ChapterController@index: add `reward_html` to lesson map arrays (both chapter lessons and standalone lessons); also ensure `course_type` is accessible to the chapter view (e.g. pass it as page prop or include in course data) so LessonForm.vue can conditionally render the reward_html field in `app/Http/Controllers/Admin/ChapterController.php`
- [x] T058 [P] [US11] Modify StoreLessonRequest: add `reward_html` validation rule (nullable, string, max:10000) and Chinese error message `'reward_html.max' => '獎勵內容太長'` in `app/Http/Requests/Admin/StoreLessonRequest.php`
- [x] T059 [P] [US11] Modify LessonForm.vue: add `reward_html` textarea field in "促銷區塊設定" section wrapped in `v-if="courseType === 'drip'"` (conditionally visible, add `courseType` prop to component); add `reward_html` to form data object and submit payload in `resources/js/Components/Admin/LessonForm.vue`
- [x] T060 [US11] Modify ClassroomController.show(): (a) add `reward_delay_minutes` as page-level Inertia prop from `config('drip.reward_delay_minutes')`; (b) in formatLessonFull(), pass `reward_html` per lesson for drip courses (set to null for converted users or lessons without video_id) in `app/Http/Controllers/Member/ClassroomController.php`
- [x] T061 [US11] Modify VideoAccessNotice.vue: add new props `rewardHtml` (String, default null), `rewardDelayMinutes` (Number, default 10), `lessonId` (Number, required); when rewardHtml is not empty, convert layout to two-column flex (flex-col on mobile, flex-row on md+): left column = existing countdown/expired notice, right column = reward panel; right column uses per-session timer (starts at 0 on each mount — NOT restored from localStorage, per spec:離開後計時歸零); localStorage key `reward_achieved_lesson_{lessonId}` tracks achievement across page loads; before achievement (elapsed < rewardDelayMinutes × 60s): show "你準時來上課了！真棒"; on achievement: switch right panel to v-html rewardHtml and save `reward_achieved_lesson_{lessonId}` to localStorage; when `expired === true` and localStorage key exists: show rewardHtml in right panel; when `expired === true` and localStorage key absent: append "下次早點來喔，錯過了獎勵 :(" below existing expired promo block in `resources/js/Components/Classroom/VideoAccessNotice.vue`
- [x] T062 [US11] Modify Classroom.vue: pass `rewardHtml` (currentLesson.reward_html || null), `rewardDelayMinutes` (page prop reward_delay_minutes), `lessonId` (currentLesson.id) to VideoAccessNotice component; ensure these props are only passed when course is drip + lesson has video_id + subscription.status !== 'converted'; add `reward_delay_minutes` to destructured Inertia page props in `resources/js/Pages/Member/Classroom.vue`

**Checkpoint**: 獎勵欄在倒數旁正確顯示、達標後切換為 reward_html、重整後直接顯示、逾期未達標顯示提示、RWD 正常

---

## Phase 14: US12 + US13 + US14 - Email 追蹤分析 (Priority: P2)

**Goal**: Drip 信件嵌入 tracking pixel 記錄開信事件；promo_url 包裝為追蹤 redirect 記錄點擊事件。管理員訂閱者清單新增 Lesson 統計表（已發送/開信率/點擊率/整體轉換率）及每位訂閱者的開信數和促銷點擊狀態。

**Independent Test**: 建立 drip 課程並設定 Lesson promo_url → 訂閱測試帳號 → 收到 drip 信件後開啟信件（觸發 pixel 端點）並點擊商品連結（觸發 redirect 端點）→ 進入後台 /admin/courses/{id}/subscribers 驗證 Lesson 統計列顯示正確開信數和點擊數。

**Depends on**: Phase 10 (US7 Subscribers.vue 已建立), Phase 3 (SendDripEmailJob 已建立), Phase 2 (DripLessonMail 已建立)

### Implementation

- [x] T063 [P] [US12] Create migration for `drip_email_events` table: id (PK), subscription_id (FK → drip_subscriptions.id, cascade), lesson_id (FK → lessons.id, cascade), event_type ENUM('opened','clicked') NOT NULL, target_url varchar(500) nullable, ip varchar(45) nullable, user_agent text nullable, created_at timestamp (NO updated_at — events are immutable), UNIQUE KEY (subscription_id, lesson_id, event_type), INDEX subscription_id, INDEX lesson_id in `database/migrations/`
- [x] T064 [P] [US14] Create migration to add `promo_url` (varchar 500, nullable) column to lessons table after `promo_html` in `database/migrations/`
- [x] T065 [P] [US12] Create DripEmailEvent model: `const UPDATED_AT = null`, $fillable (subscription_id, lesson_id, event_type, target_url, ip, user_agent), subscription() BelongsTo DripSubscription (foreign key: subscription_id), lesson() BelongsTo Lesson in `app/Models/DripEmailEvent.php`
- [x] T066 [P] [US12] Modify DripSubscription model: add `emailEvents()` HasMany DripEmailEvent relationship (foreign key: subscription_id) in `app/Models/DripSubscription.php`
- [x] T067 [P] [US14] Modify Lesson model: add `promo_url` to `$fillable` in `app/Models/Lesson.php`
- [x] T068 [P] [US12] Create DripTrackingController: `open(Request $request)` — if `$request->hasValidSignature()` then `DripEmailEvent::firstOrCreate(['subscription_id' => $request->integer('sub'), 'lesson_id' => $request->integer('les'), 'event_type' => 'opened'], ['ip' => $request->ip(), 'user_agent' => $request->userAgent()])` wrapped in try/catch with `Log::warning` on failure — always return `response(PIXEL_BINARY, 200, ['Content-Type' => 'image/gif', 'Cache-Control' => 'no-store'])` where PIXEL_BINARY is a private const with the 1x1 transparent GIF hex sequence; `click(Request $request)` — same firstOrCreate pattern for 'clicked' event (include target_url in attributes), always `return redirect()->away($request->query('url', '/'))` in `app/Http/Controllers/DripTrackingController.php`
- [x] T069 [P] [US12] Add two public tracking routes to `routes/web.php` outside any auth middleware group: `Route::get('/drip/track/open', [DripTrackingController::class, 'open'])->name('drip.track.open')` and `Route::get('/drip/track/click', [DripTrackingController::class, 'click'])->name('drip.track.click')` in `routes/web.php`
- [x] T070 [US12] Modify SendDripEmailJob.handle(): before calling Mail::to()->send(), generate signed URLs: `$openPixelUrl = URL::signedRoute('drip.track.open', ['sub' => $subscription->id, 'les' => $lesson->id], now()->addDays(180))`; `$promoTrackUrl = !empty($lesson->promo_url) ? URL::signedRoute('drip.track.click', ['sub' => $subscription->id, 'les' => $lesson->id, 'url' => $lesson->promo_url], now()->addDays(180)) : null`; pass both as named constructor args to DripLessonMail in `app/Jobs/SendDripEmailJob.php`
- [x] T071 [P] [US12] Modify DripLessonMail: add constructor params `public string $openPixelUrl` and `public ?string $promoTrackUrl = null`; verify content() method exposes these as template variables (as public properties they are auto-accessible in Blade) in `app/Mail/DripLessonMail.php`
- [x] T072 [US12] Modify drip-lesson.blade.php: (1) after html_content section, add promo button block — `@if($promoTrackUrl)` render `<p style="text-align:center;margin:24px 0"><a href="{{ $promoTrackUrl }}" style="display:inline-block;background:#F0C14B;color:#373557;padding:12px 40px;border-radius:9999px;border:1px solid rgba(199,163,59,0.5);text-decoration:none;font-weight:600;font-size:15px;box-shadow:0 1px 3px rgba(0,0,0,0.1)">立即瞭解</a></p>` `@endif`; (2) add tracking pixel as last element before closing body: `<img src="{{ $openPixelUrl }}" width="1" height="1" alt="" style="display:none">` in `resources/views/emails/drip-lesson.blade.php`
- [x] T073 [P] [US14] Modify StoreLessonRequest: add `promo_url` validation rule (`nullable|url|max:500`) and Chinese error message `'promo_url.url' => '商品連結必須是有效的 URL'` in `app/Http/Requests/Admin/StoreLessonRequest.php`
- [x] T074 [P] [US14] Modify LessonForm.vue: add `promo_url` URL input field in "促銷區塊設定" section below promo_html textarea (label: "商品連結 URL（Email 追蹤）", type="url", placeholder: "https://example.com/product/...", help text: "設定後，drip 信件中顯示可追蹤點擊的商品連結按鈕。留空則不顯示。"); add `promo_url` to form data object and submit payload in `resources/js/Components/Admin/LessonForm.vue`
- [x] T075 [P] [US14] Modify Admin ChapterController@index: add `promo_url` to lesson map arrays (both chapter lessons and standalone lessons) so LessonForm receives promo_url data when editing existing lessons in `app/Http/Controllers/Admin/ChapterController.php`
- [x] T076 [US12] Add `getSubscriberStats(Course $course): array` method to DripService (Constitution §II: spans DripSubscription + DripEmailEvent + Lesson = 3 models, MUST go in Service): method returns `['lesson_stats' => [...], 'conversion_rate' => ?float]` where lesson_stats is a Collection — for each lesson ordered by sort_order compute: `sent_count` (DripSubscription where course_id and emails_sent > sort_order), `open_count`/`click_count` (DripEmailEvent aggregated by lesson_id), `open_rate` (null when sent_count=0), `click_rate` (null when sent_count=0 or no promo_url), `has_promo_url` bool; conversion_rate = converted_count / total (null when total=0); also add `getSubscriberEventCounts(Collection $subscriptionIds): Collection` returning per-subscription opened_count and has_clicked (bool) via DripEmailEvent withCount in `app/Services/DripService.php`
- [x] T076b [US12] Modify Admin CourseController::subscribers(): inject DripService via constructor (add `protected DripService $dripService` param); replace inline aggregation with `$stats = $this->dripService->getSubscriberStats($course)`; add `withCount` for per-subscriber event metrics by calling `$this->dripService->getSubscriberEventCounts($subIds)` and merging into subscriber collection; pass `lessonStats`, `conversionRate` as new Inertia props — controller remains thin orchestrator in `app/Http/Controllers/Admin/CourseController.php`
- [x] T077 [US12] Modify Subscribers.vue: (a) add Lesson stats table above subscriber list (columns: 課程/已發送/開信/開信率/點擊/點擊率); render open_rate and click_rate as percentage string (e.g. "40.0%"); show "—" when null (sent_count=0 or no promo_url); show conversionRate summary line below table ("整體轉換率：XX.X%"), hide when null; (b) add "已開 N/M 封" column (opened_count / emails_sent) to each subscriber row; (c) add has_clicked indicator column (✓ green text when true, — gray when false) to each subscriber row in `resources/js/Pages/Admin/Courses/Subscribers.vue`

**Checkpoint**: Tracking pixel 觸發開信記錄、redirect 追蹤點擊並導向目標 URL、後台統計表顯示正確開信率/點擊率/轉換率、每位訂閱者行顯示開信數和點擊狀態

---

## Phase 12 (continued): Polish Additions

- [x] T078 Verify US11 reward column RWD: on mobile (< md breakpoint) reward section stacks below countdown, on desktop both columns display side by side
- [x] T079 Verify tracking end-to-end: open tracking pixel URL directly in browser returns 1x1 GIF (Content-Type: image/gif); click redirect URL records event and redirects to target within 1 second; duplicate requests (same sub+les+event_type) do not create duplicate records
- [x] T080 Verify Subscribers.vue lesson stats accuracy: open_count and click_count in table match actual drip_email_events records; division-by-zero cases (sent_count=0) show "—" without PHP errors
- [x] T081 [US14] Modify LessonForm.vue CTA quick-insert function: update generated button HTML template from old orange (`background:#ff5a36`, `border-radius:6px`) to brand-gold style (`background:#F0C14B`, `color:#373557`, `border-radius:9999px`, `border:1px solid rgba(199,163,59,0.5)`, `font-weight:600`, `box-shadow:0 1px 3px rgba(0,0,0,0.1)`); update default button text from any prior default to `'立即瞭解'` per spec FR-034b in `resources/js/Components/Admin/LessonForm.vue`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 - BLOCKS all user stories
- **US1+US1.5 (Phase 3)**: Depends on Phase 2 → 🎯 MVP
- **US6 (Phase 4)**: Depends on Phase 2, benefits from Phase 3 for test data
- **US3 (Phase 5)**: Depends on Phase 2 (can parallel with Phase 3/4)
- **US2 (Phase 6)**: Depends on Phase 3 (reuses subscribe infrastructure)
- **US4 (Phase 7)**: Depends on Phase 5 (conversion targets) + Phase 6 (webhook)
- **US8+US9 (Phase 8)**: Depends on Phase 2 only (independent of drip subscription flow)
- **US5 (Phase 9)**: Depends on Phase 3 (subscription + email infrastructure)
- **US7 (Phase 10)**: Depends on Phase 2 (can parallel with any story)
- **US10 (Phase 11)**: Depends on Phase 4 (US6 classroom) + Phase 7 (US4 conversion targets for urgency promo)
- **US11 (Phase 13)**: Depends on Phase 11 (VideoAccessNotice.vue exists to extend)
- **US12~US14 (Phase 14)**: Depends on Phase 3 (SendDripEmailJob) + Phase 10 (Subscribers.vue) + Phase 2 (DripLessonMail)
- **Polish (Phase 12)**: Depends on all phases complete

### User Story Dependencies

- **US1+US1.5 (P1)**: After Phase 2 → no story dependencies
- **US6 (P1)**: After Phase 2 → benefits from US1 for test data
- **US3 (P2)**: After Phase 2 → independent of US1/US6
- **US2 (P2)**: After US1 → reuses subscribe()
- **US4 (P2)**: After US3 + US2 → needs conversion targets + webhook
- **US8+US9 (P2)**: After Phase 2 → fully independent (all course types)
- **US10 (P2)**: After US6 + US4 → needs classroom drip support + conversion targets
- **US11 (P2)**: After US10 → extends VideoAccessNotice
- **US12~US14 (P2)**: After US1 + US7 → needs email job + subscriber list
- **US5 (P3)**: After US1 → uses subscription + email
- **US7 (P3)**: After Phase 2 → independent (read-only)

### Parallel Opportunities

**After Phase 2 completes**, these can start simultaneously:
- US1+US1.5 (subscribe flow)
- US3 (admin settings)
- US8+US9 (promo blocks)
- US7 (subscriber list)

**After US1 completes**:
- US6 (classroom) and US5 (unsubscribe) can run in parallel

**After US4 completes**:
- US10 (video access window) can start — needs classroom + conversion targets

**After US10 completes**:
- US11 (reward block) can start — extends VideoAccessNotice

**After US1 + US7 complete**:
- US12~US14 (tracking) can start in parallel with US11

---

## Parallel Example: Phase 2 (Foundational)

```bash
# All model tasks can run in parallel (different files):
T005: DripSubscription model    → app/Models/DripSubscription.php
T006: DripConversionTarget model → app/Models/DripConversionTarget.php
T007: Course model modifications → app/Models/Course.php
T008: Lesson model modifications → app/Models/Lesson.php
T009: User model modifications   → app/Models/User.php
T010: DripLessonMail             → app/Mail/DripLessonMail.php
T011: Email template             → resources/views/emails/drip-lesson.blade.php
T012: SendDripEmailJob           → app/Jobs/SendDripEmailJob.php
```

## Parallel Example: After Phase 2

```bash
# These user stories can start simultaneously (different controllers/pages):
Phase 3:  US1+US1.5 → DripSubscriptionController + DripSubscribeForm.vue
Phase 5:  US3       → Admin CourseController + Edit.vue
Phase 8:  US8+US9   → LessonForm.vue + LessonPromoBlock.vue
Phase 10: US7       → Admin CourseController subscribers + Subscribers.vue
```

## Parallel Example: Phase 11 (US10)

```bash
# These tasks can run in parallel (different files):
T047: config/drip.php           → NEW config file
T050: VideoAccessNotice.vue     → NEW Vue component
T052: drip-lesson.blade.php     → MODIFY email template

# Then sequential:
T048: DripService methods       → depends on config
T049: ClassroomController       → depends on T048
T051: Classroom.vue             → depends on T049 + T050
```

## Parallel Example: Phase 13 (US11)

```bash
# These tasks can run in parallel (different files):
T054: migration reward_html     → database/migrations/
T055: config/drip.php           → add reward_delay_minutes
T056: Lesson model              → app/Models/Lesson.php
T057: ChapterController         → app/Http/Controllers/Admin/ChapterController.php
T058: StoreLessonRequest        → app/Http/Requests/Admin/StoreLessonRequest.php
T059: LessonForm.vue            → resources/js/Components/Admin/LessonForm.vue

# Then sequential:
T060: ClassroomController       → depends on T054 + T055 + T056
T061: VideoAccessNotice.vue     → depends on T055 (reward_delay_minutes config)
T062: Classroom.vue             → depends on T060 + T061
```

## Parallel Example: Phase 14 (US12~US14)

```bash
# These tasks can run in parallel (different files):
T063: migration drip_email_events → database/migrations/
T064: migration promo_url         → database/migrations/
T065: DripEmailEvent model        → app/Models/DripEmailEvent.php
T066: DripSubscription model      → app/Models/DripSubscription.php
T067: Lesson model                → app/Models/Lesson.php
T068: DripTrackingController      → app/Http/Controllers/DripTrackingController.php
T069: routes/web.php              → routes/web.php
T071: DripLessonMail              → app/Mail/DripLessonMail.php
T073: StoreLessonRequest          → app/Http/Requests/Admin/StoreLessonRequest.php
T074: LessonForm.vue              → resources/js/Components/Admin/LessonForm.vue
T075: ChapterController           → app/Http/Controllers/Admin/ChapterController.php

# Then sequential (depend on models + routes + mail being ready):
T070: SendDripEmailJob            → depends on T065 + T066 + T069 + T071
T072: drip-lesson.blade.php       → depends on T071
T076: CourseController            → depends on T065 + T066
T077: Subscribers.vue             → depends on T076
```

---

## Implementation Strategy

### MVP First (US1 + US1.5 Only)

1. Complete Phase 1: Setup (4 migrations)
2. Complete Phase 2: Foundational (9 tasks - models, mail, job, routes)
3. Complete Phase 3: US1+US1.5 (9 tasks - subscribe + Form Request + email + scheduler)
4. **STOP and VALIDATE**: Test subscription flow end-to-end
5. Deploy/demo if ready

### Incremental Delivery

1. Setup + Foundational → Foundation ready
2. US1+US1.5 → 訂閱功能 → **MVP Deploy**
3. US6 → 教室解鎖 → Deploy
4. US3 → 管理員設定 → Deploy
5. US2 → 付費訂閱 → Deploy
6. US4 → 自動轉換 → Deploy
7. US8+US9 → 促銷區塊 → Deploy
8. US5 → 退訂 → Deploy
9. US7 → 訂閱者清單 → Deploy
10. US10 → 影片免費觀看期限 → Deploy
11. US11 → 準時到課獎勵區塊 → Deploy
12. US12~US14 → Email 追蹤分析 → Deploy
13. Polish → Final validation

---

## Notes

- [P] tasks = different files, no dependencies on incomplete tasks
- [Story] label maps task to specific user story for traceability
- US1 and US1.5 combined — they share subscribe infrastructure
- US8 and US9 combined — admin setup and frontend display are tightly coupled
- Promo blocks (US8/US9) apply to ALL courses, not just drip
- Course model already has a `type` field (lecture/mini/full) — the new `course_type` field is separate
- Commit after each task or logical group
- Stop at any checkpoint to validate independently
- US10 has no DB migrations — config-based setting only (`config/drip.php`)
- US10 urgency promo content is system-generated (not custom HTML like promo blocks)
- US11 extends VideoAccessNotice.vue from US10 — reward timer is per-session (no localStorage accumulation), achievement state IS localStorage-persisted
- US12~US14 combined — tracking infrastructure (pixel + redirect + events table) and analytics UI are tightly coupled
- DripEmailEvent uses `const UPDATED_AT = null` — events are immutable records
- Tracking signed URLs have 180-day expiry to cover typical email engagement window
- promo_url (US14) is email-only; promo_html (US8) is classroom-only — they are independent
