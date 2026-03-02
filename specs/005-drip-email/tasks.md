# Tasks: Email 連鎖加溫系統 (Drip Email System)

**Input**: Design documents from `/specs/005-drip-email/`
**Prerequisites**: plan.md, spec.md, data-model.md, contracts/, research.md, quickstart.md
**Tests**: Not explicitly requested. Tests NOT included.
**Updated**: 2026-03-02 - 訂閱時強制填寫暱稱 (Phase 20)
**Updated**: 2026-03-02 - 暱稱欄位行為調整：永遠顯示+預填+regex 驗證 (Phase 21)

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
- [x] T004 [P] Create migration to add `promo_delay_seconds` (unsigned int, nullable) and `promo_html` (text, nullable) columns to lessons table after `md_content` in `database/migrations/`

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
- [x] T011 [P] Create drip lesson email Blade template with lesson title, full md_content converted to HTML via league/commonmark (when present), video notice (if has_video), fallback text for pure-video lessons without md_content ("本課程包含教學影片，請至網站觀看完整內容" with classroom link), classroom link (`/member/classroom/{course_id}`), and unsubscribe link (`/drip/unsubscribe/{token}`) in `resources/views/emails/drip-lesson.blade.php`
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
- [x] T019b [US1] Fix Course/Show.vue: after successful subscription Inertia full-page redirect resets scroll to top, hiding the success notice at the bottom. On `onMounted`, detect `page.props.flash.drip_subscribed` and call `scrollIntoView({ behavior: 'smooth', block: 'center' })` on the drip section ref. Covers both guest verify flow and logged-in one-click flow in `resources/js/Pages/Course/Show.vue`
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

**Goal**: Drip 課程 Lesson 若設定了 `video_access_hours`，則從 Lesson 解鎖起算至指定時數內為免費觀看期，過期後影片仍可觀看但顯示加強版促銷區塊（方案 A：軟性提醒，不鎖定影片）。未設定 `video_access_hours` 的 Lesson 無限期觀看，不顯示任何相關 UI。

> ⚠️ **此 Phase 實作為舊 config-based 設計（全站統一時數）**，已於 Phase 16 (T089~T098) 改為 per-lesson `video_access_hours` 欄位。Phase 16 為本 Phase 的設計修正。

**Independent Test**: 訂閱 drip 課程 → 為 Lesson 設定 `video_access_hours`，修改 subscribed_at 使其超過設定時數 → 驗證影片仍可播放、過期促銷區塊出現、converted 使用者不顯示；另驗證未設定 `video_access_hours` 的 Lesson 完全不顯示倒數 UI

**Depends on**: Phase 4 (US6 - ClassroomController drip 支援), Phase 7 (US4 - conversion targets 用於促銷區塊)

### Implementation

- [x] T047 [P] [US10] Create config file with `video_access_hours` setting (default 48, env override `DRIP_VIDEO_ACCESS_HOURS`) in `config/drip.php`
  > ⚠️ **SUPERSEDED**: `video_access_hours` config key 已移除，改為 per-lesson 欄位。**由 T094 移除此 key。**
- [x] T048 [US10] Add getVideoAccessExpiresAt(subscription, lesson), isVideoAccessExpired(subscription, lesson), getVideoAccessRemainingSeconds(subscription, lesson) methods to DripService: calculate expiry as `subscribed_at + (sort_order × interval) days + config hours`, return null if config is null (feature disabled) in `app/Services/DripService.php`
  > ⚠️ **SUPERSEDED**: 計算邏輯使用 `config('drip.video_access_hours')`，已改為讀取 `$lesson->video_access_hours`。**由 T095 修正。**
- [x] T049 [US10] Modify ClassroomController.formatLessonFull(): for drip courses add video_access_expired (bool) and video_access_remaining_seconds (int|null) per lesson (skip for converted users and lessons without video); in show() add videoAccessTargetCourses prop with target course id/name/url from DripConversionTarget in `app/Http/Controllers/Member/ClassroomController.php`
- [x] T050 [P] [US10] Create VideoAccessNotice.vue component: props (expired bool, remainingSeconds number|null, targetCourses array), countdown timer with HH:MM:SS format (reload page when reaches 0), green "免費公開中" notice when within window, amber urgency promo block when expired with target course purchase buttons or generic "探索更多課程" fallback in `resources/js/Components/Classroom/VideoAccessNotice.vue`
- [x] T051 [US10] Modify Classroom.vue: import VideoAccessNotice, render below video player (above LessonPromoBlock) when course is drip + lesson has video + subscription not converted + (expired or remaining > 0), pass videoAccessTargetCourses prop in `resources/js/Pages/Member/Classroom.vue`
  > ⚠️ **BUG (C1)**: 此任務的 render condition 缺少 `video_access_hours !== null` 檢查，導致 `video_access_hours` 未設定的 Lesson 仍會顯示倒數元件。**必須由 T097（Phase 16）修正後才算完整實作。**
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
  > ⚠️ **條件不完整 (U1)**: prop 傳遞條件缺少 `video_access_hours !== null`（FR-046 要求）。功能上由 T097 在 VideoAccessNotice 渲染層補上此判斷來補償，但條件本身不完整。T097 執行後功能正確，此處為文件標記。

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
  > ⚠️ **SUPERSEDED (E1)**: `click()` 方法使用 `$request->integer('sub')` 讀取訂閱 ID，但 T087 生成的點擊 URL 不含 `sub` 參數（改用 auth session 識別訂閱者）。此版本上線會造成 FK 約束錯誤。**由 T088a 修正。**
- [x] T069 [P] [US12] Add two public tracking routes to `routes/web.php` outside any auth middleware group: `Route::get('/drip/track/open', [DripTrackingController::class, 'open'])->name('drip.track.open')` and `Route::get('/drip/track/click', [DripTrackingController::class, 'click'])->name('drip.track.click')` in `routes/web.php`
  > ⚠️ **SUPERSEDED (C2)**: `drip.track.click` 路由設計錯誤（click 應在 auth middleware 內）。**由 T082 修正。**
- [x] T070 [US12] Modify SendDripEmailJob.handle(): before calling Mail::to()->send(), generate signed URLs: `$openPixelUrl = URL::signedRoute('drip.track.open', ['sub' => $subscription->id, 'les' => $lesson->id], now()->addDays(180))`; `$promoTrackUrl = !empty($lesson->promo_url) ? URL::signedRoute('drip.track.click', ['sub' => $subscription->id, 'les' => $lesson->id, 'url' => $lesson->promo_url], now()->addDays(180)) : null`; pass both as named constructor args to DripLessonMail in `app/Jobs/SendDripEmailJob.php`
  > ⚠️ **SUPERSEDED (C2)**: Email 不應生成 `$promoTrackUrl`（Email 不應有促銷按鈕）。**由 T083 修正。**
- [x] T071 [P] [US12] Modify DripLessonMail: add constructor params `public string $openPixelUrl` and `public ?string $promoTrackUrl = null`; verify content() method exposes these as template variables (as public properties they are auto-accessible in Blade) in `app/Mail/DripLessonMail.php`
  > ⚠️ **SUPERSEDED (C2)**: `$promoTrackUrl` 不應存在於 Mailable（Email 不追蹤點擊）。**由 T084 移除。**
- [x] T072 [US12] Modify drip-lesson.blade.php: (1) after md_content section, add promo button block — `@if($promoTrackUrl)` render `<p style="text-align:center;margin:24px 0"><a href="{{ $promoTrackUrl }}" style="display:inline-block;background:#F0C14B;color:#373557;padding:12px 40px;border-radius:9999px;border:1px solid rgba(199,163,59,0.5);text-decoration:none;font-weight:600;font-size:15px;box-shadow:0 1px 3px rgba(0,0,0,0.1)">立即瞭解</a></p>` `@endif`; (2) add tracking pixel as last element before closing body: `<img src="{{ $openPixelUrl }}" width="1" height="1" alt="" style="display:none">` in `resources/views/emails/drip-lesson.blade.php`
  > ⚠️ **SUPERSEDED (C2)**: Email 模板不應包含 `$promoTrackUrl` 促銷按鈕區塊（違反 FR-065）。**由 T085 移除促銷按鈕並加入 video_access_hours 提示。**
- [x] T073 [P] [US14] Modify StoreLessonRequest: add `promo_url` validation rule (`nullable|url|max:500`) and Chinese error message `'promo_url.url' => '商品連結必須是有效的 URL'` in `app/Http/Requests/Admin/StoreLessonRequest.php`
- [x] T074 [P] [US14] Modify LessonForm.vue: add `promo_url` URL input field in "促銷區塊設定" section below promo_html textarea (label: "商品連結 URL（Email 追蹤）", type="url", placeholder: "https://example.com/product/...", help text: "設定後，drip 信件中顯示可追蹤點擊的商品連結按鈕。留空則不顯示。"); add `promo_url` to form data object and submit payload in `resources/js/Components/Admin/LessonForm.vue`
  > ⚠️ **SUPERSEDED (C2)**: 欄位 label「商品連結 URL（Email 追蹤）」和 help text 說明 drip 信件顯示按鈕，均與新設計衝突。**由 T086 修正 label 和說明文字。**
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

## Phase 15: promo_url 追蹤方式修正 (US12~US14 設計修正)

**Goal**: 修正 T069-T074 實作的舊設計錯誤：Email 不應有促銷按鈕；promo_url 追蹤改為「教室促銷點擊追蹤」，使用 auth session 識別訂閱者。

**Background**: 原始任務 T069（click 路由為 public）、T070（SendDripEmailJob 生成 promoTrackUrl）、T071（DripLessonMail 有 promoTrackUrl 參數）、T072（Email 模板含促銷按鈕）、T074（欄位 label 為「Email 追蹤」）均需修正以符合 2026-03-01 spec 更新。

**Independent Test**: 訂閱 drip 課程 → 收到信件 → 確認信件中無任何促銷按鈕（只有 pixel 和課程內容）→ 進入教室 → 確認教室頁面有 promo_url 追蹤按鈕 → 點擊後記錄事件並 redirect → 確認 `/drip/track/click` 路由需登入才能訪問。

**Depends on**: Phase 14 (T069-T074 已完成的錯誤版本)

### Implementation

- [x] T082 [P] [US14] Fix `routes/web.php`: move `Route::get('/drip/track/click', ...)` from public group into `Route::middleware('auth')->group(...)` so classroom click tracking requires auth session; keep `/drip/track/open` in public group (email pixel has no session) in `routes/web.php`
- [x] T083 [P] [US12] Fix `SendDripEmailJob.handle()`: remove `$promoTrackUrl` generation (the signed URL block for `drip.track.click`); only generate `$openPixelUrl = URL::signedRoute('drip.track.open', ...)` and pass it alone to DripLessonMail constructor in `app/Jobs/SendDripEmailJob.php`
- [x] T084 [P] [US12] Fix `DripLessonMail`: remove `public ?string $promoTrackUrl = null` constructor parameter entirely; keep only `public string $openPixelUrl` in `app/Mail/DripLessonMail.php`
- [x] T085 [US12] Fix `drip-lesson.blade.php`: remove the `@if($promoTrackUrl)` promo button block entirely; keep only the tracking pixel `<img src="{{ $openPixelUrl }}" ...>` at bottom; add `@if($videoAccessHours)⏰ 影片 {{ $videoAccessHours }} 小時內免費觀看，把握時間！@endif` below the video prompt block (only shows when lesson has video_access_hours set) in `resources/views/emails/drip-lesson.blade.php`
- [x] T086 [P] [US14] Fix `LessonForm.vue`: update `promo_url` field label from "商品連結 URL（Email 追蹤）" to "促銷連結 URL（教室追蹤）"; update help text from "設定後，drip 信件中顯示可追蹤點擊的商品連結按鈕" to "設定後，教室頁面的促銷區塊旁顯示可追蹤點擊的按鈕，追蹤訂閱者在教室的促銷互動" in `resources/js/Components/Admin/LessonForm.vue`
- [x] T087 [US14] Fix `ClassroomController.formatLessonFull()`: change `'promo_url' => $lesson->promo_url` to wrap as tracking route `'promo_url' => $lesson->promo_url ? route('drip.track.click', ['les' => $lesson->id, 'url' => $lesson->promo_url]) : null` so frontend receives ready-to-use tracking URL (no signed URL needed — auth middleware handles identity) in `app/Http/Controllers/Member/ClassroomController.php`
- [x] T088a [US12] Fix `DripTrackingController.click()`: remove `$request->integer('sub')` lookup; replace with auth-based subscription lookup — `$sub = Auth::user()->dripSubscriptions()->whereHas('course.lessons', fn($q) => $q->where('id', $request->integer('les')))->first()`; if `$sub` is null (not a subscriber), skip `firstOrCreate` and still `return redirect()->away($request->query('url', '/'))` (silent skip, don't fail); use `$sub->id` as `subscription_id` in `DripEmailEvent::firstOrCreate` in `app/Http/Controllers/DripTrackingController.php`
- [x] T088 [US14] Fix `Classroom.vue`: add promo_url tracking button rendered after LessonPromoBlock — `<a v-if="currentLesson?.promo_url" :href="currentLesson.promo_url" style="display:inline-block;background:#F0C14B;color:#373557;padding:12px 40px;border-radius:9999px;font-weight:600">立即瞭解</a>` — only visible when currentLesson has promo_url (i.e. lesson has promo_url set AND user is subscribed drip member) in `resources/js/Pages/Member/Classroom.vue`

**Checkpoint**: Email 中無促銷按鈕；教室有 promo_url 追蹤按鈕；click 路由需登入；點擊事件正確記錄到 drip_email_events

---

## Phase 16: per-lesson 影片觀看期限 (US10 設計修正)

**Goal**: 將影片免費觀看時數從全站 config 改為 per-lesson `video_access_hours` 欄位（nullable 整數）。null=無限期觀看不顯示任何倒數計時 UI；有填寫則啟用限時觀看、倒數計時和準時到課獎勵欄。

**Independent Test**: 管理員為 Lesson A 設定 `video_access_hours=2`、Lesson B 不設定 → 訂閱後修改 subscribed_at 使 Lesson A 超過 2 小時 → 驗證 Lesson A 顯示過期促銷區塊、Lesson B 完全不顯示倒數計時 UI → 驗證 Email 中 Lesson A 含「2 小時內免費觀看」提示、Lesson B 無此提示。

**Depends on**: Phase 11 (US10 DripService + VideoAccessNotice.vue), Phase 15 (Email 模板已修正)

### Implementation

- [x] T089 Create migration to add `video_access_hours` (unsigned integer, nullable) column to lessons table after `reward_html` in `database/migrations/`
- [x] T090 [P] [US10] Modify Lesson model: add `video_access_hours` to `$fillable`; add `'video_access_hours' => 'integer'` to `casts()` in `app/Models/Lesson.php`
- [x] T091 [P] [US10] Modify `StoreLessonRequest`: add `video_access_hours` validation rule (`nullable|integer|min:1|max:8760`) and Chinese error message `'video_access_hours.min' => '觀看期限至少 1 小時'` in `app/Http/Requests/Admin/StoreLessonRequest.php`
- [x] T092 [P] [US10] Modify Admin `ChapterController@index`: add `video_access_hours` to lesson map arrays (both chapter lessons and standalone lessons) so LessonForm receives the value when editing in `app/Http/Controllers/Admin/ChapterController.php`
- [x] T093 [P] [US10] Modify `LessonForm.vue`: add `video_access_hours` number input in "促銷區塊設定" section (label: "影片觀看期限（小時）", type="number", min="1", placeholder="留空表示無限期觀看", help text: "drip 課程有影片的 Lesson 專用。設定後啟用倒數計時與準時到課獎勵欄。"); add `video_access_hours` to form data and submit payload; **wrap the field with `v-if="courseType === 'drip'"` so it only appears for drip courses** in `resources/js/Components/Admin/LessonForm.vue`
- [x] T094 [P] Modify `config/drip.php`: remove `video_access_hours` key entirely (and its `env('DRIP_VIDEO_ACCESS_HOURS', 48)` line); retain only `reward_delay_minutes`; add comment `// video_access_hours moved to Lesson.video_access_hours field (per-lesson)` in `config/drip.php`
- [x] T095 [US10] Modify `DripService.getVideoAccessExpiresAt()`: change `$hours = config('drip.video_access_hours')` to `$hours = $lesson->video_access_hours`; update comment from `// feature disabled` to `// null = unlimited access, no countdown UI`; also update `isVideoAccessExpired()` and `getVideoAccessRemainingSeconds()` comments accordingly in `app/Services/DripService.php`
- [x] T096 [US10] Modify `ClassroomController.formatLessonFull()`: update condition for `video_access_expired` and `video_access_remaining_seconds` — skip calculation (return false/null) when `$lesson->video_access_hours === null` (in addition to existing converted/no-video checks); pass `'video_access_hours' => $lesson->video_access_hours` in returned array so frontend can check it directly in `app/Http/Controllers/Member/ClassroomController.php`
- [x] T097 [US10] Modify `Classroom.vue`: update VideoAccessNotice render condition — add `&& currentLesson.video_access_hours !== null` check (i.e. only render VideoAccessNotice when course is drip + lesson has video + lesson has video_access_hours set + subscription not converted); update VideoAccessNotice prop binding to include `video_access_hours` if needed in `resources/js/Pages/Member/Classroom.vue`
- [x] T098 [US10] Modify `drip-lesson.blade.php`: replace any hardcoded hours reference with `$lesson->video_access_hours` — ensure `@if($lesson->video_access_hours)` guard wraps the "X 小時內免費觀看" line (this should already be done in T085; verify and ensure no leftover hardcoded config reference remains) in `resources/views/emails/drip-lesson.blade.php`

**Checkpoint**: Lesson 有設定 video_access_hours → 倒數計時和獎勵欄正常顯示；Lesson 未設定 → 完全不顯示任何觀看期 UI；Email 動態帶入時數或不顯示；config/drip.php 不再有 video_access_hours

---

## Phase 12 (continued): Polish Additions (Phase 15+16)

- [ ] T099 Verify Phase 15 tracking end-to-end: subscribe to drip course → receive email → confirm NO promo button in email, only tracking pixel → enter classroom → confirm promo_url button appears → click button → verify event recorded in drip_email_events AND redirect completes within 1 second → verify `/drip/track/click` returns 302 for authenticated users and 403/redirect for unauthenticated
- [ ] T100 Verify Phase 16 video_access_hours: set Lesson A with `video_access_hours=1`, Lesson B with null → subscribe → confirm Lesson A shows countdown, Lesson B shows nothing → force expire Lesson A (modify subscribed_at) → confirm expired promo block appears for A but not B → confirm reward column only appears on Lesson A when `reward_html` is set → confirm Email for A has "1 小時內免費觀看" and Email for B has no such line

---

## Phase 17: US15 (Drip 課程教室側邊欄過濾)

**Story Goal**: Drip 課程教室側邊欄只顯示「有影片且已解鎖」的 Lesson。純文字 Lesson 永遠不出現（包含 converted 全解鎖後）；未解鎖 Lesson 完全不露出（無倒數、無鎖頭）。

**Independent Test**: 建立 drip 課程含 2 個純文字 Lesson（已解鎖）+ 2 個有影片 Lesson（1 已解鎖、1 未解鎖）→ 進教室 → 側邊欄只顯示 1 個有影片已解鎖的 Lesson → 模擬 converted → 側邊欄顯示 2 個有影片 Lesson，純文字仍不出現。

- [x] T101 [US15] Modify `ClassroomController@show`: (1) in chapters filter add `&& (!$isDrip || !empty($lesson->video_id))` to the existing unlocked filter condition; (2) apply same condition to standaloneLessons filter; (3) update drip `currentLesson` default selection to add `&& !empty($lesson->video_id)` to both first-uncompleted and fallback queries; (4) **admin exemption**: wrap the video filter condition with `&& (!$isDrip || $isAdmin || !empty($lesson->video_id))` so admin preview shows all lessons including text-only; (5) direct `?lesson_id=X` access unchanged — pure text lesson still displayable when accessed via email link in `app/Http/Controllers/Member/ClassroomController.php`
- [x] T102 [P] [US15] Modify `Classroom.vue`: (1) add sidebar empty-state message for drip courses when no visible lessons — `v-if="course.is_drip && chapters.length === 0 && standaloneLessons.length === 0"` displays `<p class="text-sm text-gray-500 p-4">你的課程正在準備中，請留意 Email 通知。</p>` in the sidebar lesson list area; (2) add main content area null guard — when `currentLesson` is null AND `course.is_drip`, show a welcome/empty state in the main panel (e.g. `<p>訂閱成功！第一堂課程即將發送至您的信箱，請留意 Email 通知。</p>`) instead of a blank or broken layout in `resources/js/Pages/Member/Classroom.vue`
- [x] T103 Verify US15 sidebar filtering: (1) drip course with mixed lessons (text-only + video) → subscribe → confirm sidebar shows only unlocked video lessons; (2) force converted status (update DB) → confirm all video lessons visible, text-only still absent; (3) navigate via `?lesson_id=X` to a text-only lesson → confirm content renders normally despite not being in sidebar; (4) standard course → confirm sidebar behaviour unchanged (all lessons including text-only shown)

---

## Phase 18: Email 個人化問候語 (2026-03-01 新增)

**Purpose**: drip 信件主旨與正文開頭加入收件者姓名，提升親切感與開信率。

**背景**：所有訂閱者收到完全相同格式的信件。加入個人化稱呼讓信件更像真人手寫，提高開信動機。

- [x] T104 [US1] Add `resolveGreetingName(User $user): string` private method to `SendDripEmailJob`: priority `nickname → real_name → ''`; if exactly 3 Chinese chars (`preg_match('/^[\x{4e00}-\x{9fff}]+$/u')`) return `mb_substr($name, 1)`, else return full name; pass result as `greetingName:` to `DripLessonMail` constructor in `app/Jobs/SendDripEmailJob.php`
  - nickname 優先，fallback real_name，兩者空回傳 ''
  - 3 個中文字取後 2 字（王小明 → 小明），其餘全名
- [x] T105 [P] [US1] Modify `DripLessonMail`: add `public string $greetingName = ''` constructor parameter; update `envelope()` to build subject as `"{$this->greetingName}，{$this->lessonTitle}"` when greetingName non-empty, else keep `$this->lessonTitle` in `app/Mail/DripLessonMail.php`
- [x] T106 [P] [US1] Modify `drip-lesson.blade.php`: add `@if($greetingName)<p>Hi {{ $greetingName }}，</p>@endif` as first element inside `<body>` in `resources/views/emails/drip-lesson.blade.php`
  > ⚠️ **Phase 19 後續修正**：課程/Lesson 標題行已於 T107 移除，問候語後直接接 Lesson HTML 正文

**Checkpoint**: 有暱稱訂閱者收到的信件主旨含名字前綴，信件正文開頭顯示個人化問候（獨立段落），無名字訂閱者行為不變 ✅

---

## Phase 19: 精簡 drip 信件模板 (2026-03-01 新增)

**Purpose**: 移除 Email 中系統自動產生的固定區塊，讓信件更像日常對話式 Email。

**背景**：原模板自動填入課程名、Lesson 標題、影片提醒、教室連結、退訂連結，視覺上像系統通知。改為純內容輸出，所有連結由管理員手動在 Lesson 內容中維護。

- [x] T107 [US1] Simplify `drip-lesson.blade.php`: remove course/lesson title line (`$courseName — $lessonTitle`), video reminder block (`@if($hasVideo)…@endif`), video access hours hint (`$videoAccessHours`), classroom URL line (`$classroomUrl`), unsubscribe URL line (`$unsubscribeUrl`) in `resources/views/emails/drip-lesson.blade.php`
  - 最終結構：問候語（@if greetingName）→ htmlContent → tracking pixel
  - `$classroomUrl`、`$unsubscribeUrl` 等變數仍由 Mail class 傳入（`DripLessonMail`），但模板不再渲染

**Checkpoint**: 發出的 drip 信件不含任何系統自動產生的標題行或功能連結，僅顯示問候語（若設定）與 Lesson 正文 ✅

---

## Phase 20: 訂閱時強制填寫暱稱 (2026-03-02 新增)

**Purpose**: 確保每位 drip 訂閱者都有暱稱，讓 Email 個人化問候語（FR-075/FR-076）能正常運作。

**背景**：訂閱流程原本不收集暱稱，導致新訂閱者無 nickname，drip 信件略過問候語。透過在訂閱入口強制填寫暱稱解決此問題。

- [x] T108 [US1] Add `drip_nickname` to flash in `app/Http/Middleware/HandleInertiaRequests.php`
  - 在 flash 陣列新增 `'drip_nickname' => fn () => $request->session()->get('drip_nickname')`
- [x] T109 [US1] Update `DripSubscriptionController::subscribe()` in `app/Http/Controllers/DripSubscriptionController.php`
  - 新增 nickname 必填驗證（required, string, max:50）；flash 回傳帶入 `drip_nickname`
- [x] T110 [P] [US1] Update `DripSubscriptionController::verify()` in `app/Http/Controllers/DripSubscriptionController.php`
  - 新增 nickname 必填驗證；新用戶建立時帶入 nickname；已存在但無 nickname 的用戶補填
- [x] T111 [P] [US1.5] Update `DripSubscriptionController::memberSubscribe()` in `app/Http/Controllers/DripSubscriptionController.php`
  - 使用 `Rule::requiredIf(empty($user->nickname))` 條件驗證；成功後更新 user.nickname；引入 `Illuminate\Validation\Rule`
- [x] T112 [P] [US1] Update `DripSubscribeForm.vue` in `resources/js/Components/Course/DripSubscribeForm.vue`
  - Step 1 加入暱稱輸入欄位（含錯誤顯示）；nickname ref 初始值從 `flash.drip_nickname` 讀取；Step 1 + Step 2 POST 帶入 nickname；按鈕 disabled 條件加入 `!nickname`
- [x] T113 [P] [US1.5] Update `Course/Show.vue` in `resources/js/Pages/Course/Show.vue`
  - 新增 `needsNickname` computed（`!!auth.user && !auth.user.nickname`）和 `memberNickname` ref；已登入無暱稱時在訂閱按鈕上方顯示暱稱輸入欄；按鈕 disabled 條件加入 `needsNickname && !memberNickname`；memberSubscribe 帶入 nickname

**Checkpoint**: 訪客訂閱時必須填寫暱稱才能送出；已登入無暱稱會員需填寫後才能訂閱；有暱稱的會員流程完全不受影響；新訂閱者的 drip 歡迎信包含個人化問候語 ✅

---

## Phase 21: 暱稱欄位行為調整（永遠顯示+預填+regex 驗證）(2026-03-02 新增)

**Purpose**: 修正 Phase 20 的初版設計，讓所有訂閱者在訂閱前均可確認/修改暱稱，並加強輸入安全性。

**背景**：Phase 20 僅對無暱稱會員顯示欄位，有暱稱的會員可繞過；verify() 亦只在無暱稱時補填。本 Phase 調整為永遠顯示（預填舊值）、一律覆蓋、加入 regex 驗證。

- [x] T114 [US1] [US1.5] Add `regex:/\p{L}/u` to all three nickname validation rules + add Chinese error message in `app/Http/Controllers/DripSubscriptionController.php`
  - subscribe() / verify() / memberSubscribe() 統一加入 `'regex:/\p{L}/u'` 和 `'nickname.regex' => '暱稱需包含至少一個文字（不可為純空格或符號）'`
- [x] T115 [P] [US1] Fix `verify()` to always update nickname in `app/Http/Controllers/DripSubscriptionController.php`
  - 移除 `elseif (!$user->nickname)` 條件 → 改為 `else { $user->update(['nickname' => $nickname]); }`；trim() 統一處理
- [x] T116 [P] [US1.5] Fix `memberSubscribe()` to always require and update nickname in `app/Http/Controllers/DripSubscriptionController.php`
  - 移除 `Rule::requiredIf` → 改為 `'required'`；移除 `nullable`；移除條件判斷直接 update；移除 `use Illuminate\Validation\Rule` import
- [x] T117 [P] [US1.5] Update `Course/Show.vue` nickname field to always show with pre-fill in `resources/js/Pages/Course/Show.vue`
  - `memberNickname` 初始值改為 `page.props.auth?.user?.nickname || ''`；移除 `needsNickname` computed；移除 `v-if="needsNickname"`（欄位永遠顯示）；按鈕 disabled 改為 `!memberNickname.trim()`；memberSubscribe 移除 `|| undefined`

**Checkpoint**: 所有已登入會員（含有暱稱）訂閱時見到暱稱欄並可確認/修改；純空格/符號/數字暱稱後端拒絕；前端空白時按鈕 disabled；trim 後儲存；verify() 和 memberSubscribe() 均一律更新 nickname ✅

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
- **Phase 15 (promo_url 修正)**: Depends on Phase 14 (T069-T074 舊版實作需修正)
- **Phase 16 (video_access_hours)**: Depends on Phase 11 (DripService + VideoAccessNotice) + Phase 15 (Email 模板已修正)
- **Phase 17 (US15 sidebar filter)**: Depends on Phase 4 (ClassroomController drip unlock logic exists)

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
- **US15 (P1)**: After Phase 4 (US6 ClassroomController) → no other story dependencies

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
14. Phase 15 → promo_url 追蹤方式修正（Email 移除促銷按鈕，改為教室追蹤）→ Deploy
15. Phase 16 → per-lesson video_access_hours → Deploy
16. Polish (Phase 15+16) → Final validation

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
- US10 has no DB migrations originally (was config-based) — **Phase 16 adds `video_access_hours` migration to lessons table**
- US10 urgency promo content is system-generated (not custom HTML like promo blocks)
- US11 extends VideoAccessNotice.vue from US10 — reward timer is per-session (no localStorage accumulation), achievement state IS localStorage-persisted
- US11 reward column only appears when lesson has `video_access_hours` set (same prerequisite as video countdown UI)
- US12~US14 combined — tracking infrastructure (pixel + redirect + events table) and analytics UI are tightly coupled
- DripEmailEvent uses `const UPDATED_AT = null` — events are immutable records
- Tracking signed URLs have 180-day expiry — applies to open pixel only; click tracking uses auth session (no signed URL)
- **Phase 15 修正**：T069-T074 的舊設計將 click 追蹤放在 Email 中，已於 Phase 15 (T082-T088) 修正為教室追蹤
- promo_url (US14) is classroom-only (tracked via auth session); promo_html (US8) is also classroom-only — both independent of Email
- Email 中不含任何促銷按鈕；drip 信件只有課程內容 + tracking pixel + （若設定）觀看期限提示
- **Phase 16 修正**：`config/drip.php` 的 `video_access_hours` 已移至 `lessons.video_access_hours` 欄位（per-lesson）
