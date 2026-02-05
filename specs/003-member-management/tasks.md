# Tasks: Member Management (會員管理)

**Input**: Design documents from `/specs/003-member-management/`
**Prerequisites**: plan.md, spec.md, data-model.md, contracts/api.md, research.md
**Updated**: 2026-01-18 (Added User Story 7 - Gift Course)

**Tests**: Not explicitly requested - test tasks omitted.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2)
- Include exact file paths in descriptions

## Path Conventions

Based on plan.md - Laravel monolith structure:
- Backend: `app/`, `routes/`, `resources/views/`
- Frontend: `resources/js/`

---

## Phase 1: Setup

**Purpose**: Project initialization and route configuration

- [X] T001 Add member management routes to `routes/web.php` under admin group
- [X] T002 [P] Create `UpdateMemberRequest` form request in `app/Http/Requests/Admin/UpdateMemberRequest.php`
- [X] T003 [P] Create `SendBatchEmailRequest` form request in `app/Http/Requests/Admin/SendBatchEmailRequest.php`
- [X] T003a [P] Create `GiftCourseRequest` form request in `app/Http/Requests/Admin/GiftCourseRequest.php`
- [X] T003b Add gift course route to `routes/web.php` under admin.members group

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure for all user stories

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T004 Create `MemberController` skeleton with all method stubs in `app/Http/Controllers/Admin/MemberController.php`
- [X] T005 [P] Add `lessonProgress()` relationship to User model in `app/Models/User.php`
- [X] T006 [P] Add `getCourseProgress(Course $course)` method to User model in `app/Models/User.php`
- [X] T007 Create `Members/Index.vue` page skeleton in `resources/js/Pages/Admin/Members/Index.vue`

**Checkpoint**: Foundation ready - user story implementation can begin

---

## Phase 3: User Story 1 - View and Search Members (Priority: P1) 🎯 MVP

**Goal**: Display paginated member list with search and sort functionality

**Independent Test**: Login as admin, navigate to `/admin/members`, verify member list displays with all fields, search works, sorting works

### Implementation for User Story 1

- [X] T008 [US1] Implement `index()` method in `MemberController` with pagination, search, sort
- [X] T009 [US1] Build member list table UI in `resources/js/Pages/Admin/Members/Index.vue`
- [X] T010 [US1] Add search input with debounced filtering in `Index.vue`
- [X] T011 [US1] Add sortable column headers (email, real_name, created_at, last_login_at) in `Index.vue`
- [X] T012 [US1] Add pagination controls in `Index.vue`
- [X] T013 [US1] Style table for mobile responsiveness with Tailwind in `Index.vue`

**Checkpoint**: Member list with search and sort is fully functional

---

## Phase 4: User Story 2 - Edit Member Information (Priority: P1)

**Goal**: Enable inline editing of email/name/phone and modal editing of nickname/birthday

**Independent Test**: Click to edit email/name/phone inline, verify save works; open modal to edit nickname/birthday, verify save works; test email uniqueness validation

### Implementation for User Story 2

- [X] T014 [US2] Implement `update()` method in `MemberController` with validation
- [X] T015 [US2] Add inline edit functionality for email, real_name, phone columns in `Index.vue`
- [X] T016 [US2] Add copy-to-clipboard button next to email field in `Index.vue`
- [X] T017 [P] [US2] Create `MemberDetailModal.vue` component in `resources/js/Components/MemberDetailModal.vue`
- [X] T018 [US2] Implement nickname and birthday editing in `MemberDetailModal.vue`
- [X] T019 [US2] Add validation error display for inline edits in `Index.vue`
- [X] T020 [US2] Add validation error display for modal edits in `MemberDetailModal.vue`
- [X] T021 [US2] Connect modal open trigger from member row action in `Index.vue`

**Checkpoint**: All member editing (inline and modal) is functional with validation

---

## Phase 5: User Story 3 - View Member Course Ownership and Progress (Priority: P2)

**Goal**: Display member's courses and completion progress in detail modal

**Independent Test**: Open member detail modal, verify courses list shows with progress percentage; test empty state for members without courses

### Implementation for User Story 3

- [X] T022 [US3] Implement `show()` method in `MemberController` returning member + courses with progress
- [X] T023 [US3] Add courses section with progress bars to `MemberDetailModal.vue`
- [X] T024 [US3] Add empty state message when member has no courses in `MemberDetailModal.vue`

**Checkpoint**: Course progress viewing is functional in member modal

---

## Phase 6: User Story 4 - Filter Members by Course Ownership (Priority: P2)

**Goal**: Allow filtering member list by course ownership

**Independent Test**: Select course from dropdown, verify only members who own that course are shown; clear filter, verify all members return

### Implementation for User Story 4

- [X] T025 [US4] Add course filter query logic to `index()` method in `MemberController`
- [X] T026 [US4] Add course dropdown filter UI in `Index.vue`
- [X] T027 [US4] Add clear filter button in `Index.vue`
- [X] T028 [US4] Add empty state message when filter returns no results in `Index.vue`

**Checkpoint**: Course ownership filtering is functional

---

## Phase 7: User Story 5 - Select Members for Batch Operations (Priority: P2)

**Goal**: Enable checkbox selection of members with cross-page persistence and "select all matching" functionality

**Independent Test**: Check individual checkboxes, verify count updates; use select-all, verify page selection; use "Select all X matching", verify all filtered members selected; navigate pages, verify selection persists

### Implementation for User Story 5

- [X] T029 [US5] Implement `count()` method in `MemberController` for matching member count
- [X] T030 [US5] Add checkbox column to member table in `Index.vue`
- [X] T031 [US5] Add "select all on page" checkbox in table header in `Index.vue`
- [X] T032 [US5] Add selected count display in `Index.vue`
- [X] T033 [US5] Add "Select all X matching members" banner when filter applied in `Index.vue`
- [X] T034 [US5] Implement selection state persistence across page navigation in `Index.vue`

**Checkpoint**: Member selection system is fully functional

---

## Phase 8: User Story 6 - Send Batch Email to Selected Members (Priority: P3)

**Goal**: Allow composing and sending batch emails to selected members via Resend.com

**Independent Test**: Select members, click send email, compose in modal, send; verify emails queued; verify success message with count

### Implementation for User Story 6

- [X] T035 [P] [US6] Create `BatchEmailMail` mailable in `app/Mail/BatchEmailMail.php`
- [X] T036 [P] [US6] Create batch email blade template in `resources/views/emails/batch-email.blade.php`
- [X] T037 [P] [US6] Create `SendBatchEmailJob` job in `app/Jobs/SendBatchEmailJob.php`
- [X] T038 [US6] Implement `sendBatchEmail()` method in `MemberController` with chunked job dispatch
- [X] T039 [P] [US6] Create `BatchEmailModal.vue` component in `resources/js/Components/BatchEmailModal.vue`
- [X] T040 [US6] Add subject and body fields with validation in `BatchEmailModal.vue`
- [X] T041 [US6] Add "Send Email" button (disabled when no selection) in `Index.vue`
- [X] T042 [US6] Connect modal to selection state and send action in `Index.vue`
- [X] T043 [US6] Add success/error feedback after sending in `Index.vue`

**Checkpoint**: Batch email system is fully functional

---

## Phase 9: User Story 7 - Gift Course to Selected Members (Priority: P3)

**Goal**: Allow admins to gift courses to selected members with automatic notification email

**Independent Test**: Select members, click "贈送課程" button, select course, confirm; verify purchases created, emails sent, result summary shows counts

### Implementation for User Story 7

- [X] T050 [P] [US7] Create `CourseGiftedMail` mailable in `app/Mail/CourseGiftedMail.php`
- [X] T051 [P] [US7] Create gift notification email template in `resources/views/emails/course-gifted.blade.php`
- [X] T052 [P] [US7] Create `GiftCourseJob` job in `app/Jobs/GiftCourseJob.php`
- [X] T053 [US7] Implement `giftCourse()` method in `MemberController` with chunked job dispatch
- [X] T054 [P] [US7] Create `GiftCourseModal.vue` component in `resources/js/Components/GiftCourseModal.vue`
- [X] T055 [US7] Add course selection dropdown with name and description preview in `GiftCourseModal.vue`
- [X] T056 [US7] Add "贈送課程" button (disabled when no selection) in `Index.vue`
- [X] T057 [US7] Connect modal to selection state and gift action in `Index.vue`
- [X] T058 [US7] Add success/error feedback showing gifted count, already owned count, emails sent in `Index.vue`
- [X] T059 [US7] Handle edge case: all members already own course - show "所有選取的會員都已擁有此課程" message
- [X] T060 [US7] Handle edge case: members without email get course but skip notification - show warning in summary
- [X] T061 [US7] Handle edge case: course has no description - use "（無課程簡介）" placeholder in email

**Checkpoint**: Gift course system is fully functional

---

## Phase 10: Polish & Cross-Cutting Concerns

**Purpose**: Final refinements and edge case handling

- [X] T044 Add admin sidebar link to member management page
- [X] T045 Handle edge case: member deleted while viewing modal (redirect with notification)
- [X] T046 Handle edge case: members without email excluded from batch email with warning
- [X] T047 Add rate limiting to batch email endpoint (10/min)
- [X] T062 Add rate limiting to gift course endpoint (10/min)
- [X] T048 Verify mobile responsiveness on all screens
- [X] T049 Run quickstart.md validation checklist
- [X] T063 Update quickstart.md validation checklist for US7 items

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies - can start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 - BLOCKS all user stories
- **Phases 3-8 (User Stories 1-6)**: All depend on Phase 2 completion
- **Phase 9 (User Story 7)**: Depends on Phase 7 (US5 Selection) - uses same selection mechanism
- **Phase 10 (Polish)**: Depends on all user stories being complete

### User Story Dependencies

```
Phase 2 (Foundational)
    │
    ├── US1 (View/Search) ──────────────────┐
    │                                       │
    ├── US2 (Edit) ── requires US1 UI ──────┤
    │                                       │
    ├── US3 (Course Progress) ── uses US2 modal
    │                                       │
    ├── US4 (Course Filter) ── extends US1 ─┤
    │                                       │
    ├── US5 (Selection) ── extends US1 ─────┼──┐
    │                                       │  │
    ├── US6 (Batch Email) ── requires US5 ──┘  │
    │                                          │
    └── US7 (Gift Course) ── requires US5 ─────┘
```

**Note**: US1 must be complete before US2-US7 can begin (they extend the Index.vue page)
**Note**: US7 (Gift Course) follows same pattern as US6 (Batch Email) - both require US5 (Selection)

### Parallel Opportunities

**Within Phase 1:**
```
T002 [P] UpdateMemberRequest
T003 [P] SendBatchEmailRequest
T003a [P] GiftCourseRequest
```

**Within Phase 2:**
```
T005 [P] lessonProgress relationship
T006 [P] getCourseProgress method
```

**Within US2:**
```
T017 [P] MemberDetailModal.vue (separate file)
```

**Within US6:**
```
T035 [P] BatchEmailMail
T036 [P] batch-email.blade.php
T037 [P] SendBatchEmailJob
T039 [P] BatchEmailModal.vue
```

**Within US7:**
```
T050 [P] CourseGiftedMail
T051 [P] course-gifted.blade.php
T052 [P] GiftCourseJob
T054 [P] GiftCourseModal.vue
```

---

## Parallel Example: User Story 6

```bash
# Launch all independent backend components together:
Task: "Create BatchEmailMail mailable in app/Mail/BatchEmailMail.php"
Task: "Create batch email blade template in resources/views/emails/batch-email.blade.php"
Task: "Create SendBatchEmailJob job in app/Jobs/SendBatchEmailJob.php"

# Launch modal component in parallel with backend:
Task: "Create BatchEmailModal.vue component in resources/js/Components/BatchEmailModal.vue"

# Then integrate (sequential):
Task: "Implement sendBatchEmail() method in MemberController"
Task: "Add subject and body fields with validation in BatchEmailModal.vue"
Task: "Connect modal to selection state and send action in Index.vue"
```

---

## Parallel Example: User Story 7

```bash
# Launch all independent backend components together:
Task: "Create CourseGiftedMail mailable in app/Mail/CourseGiftedMail.php"
Task: "Create gift notification email template in resources/views/emails/course-gifted.blade.php"
Task: "Create GiftCourseJob job in app/Jobs/GiftCourseJob.php"

# Launch modal component in parallel with backend:
Task: "Create GiftCourseModal.vue component in resources/js/Components/GiftCourseModal.vue"

# Then integrate (sequential):
Task: "Implement giftCourse() method in MemberController"
Task: "Add course selection dropdown with name and description preview in GiftCourseModal.vue"
Task: "Add 贈送課程 button and connect modal in Index.vue"
Task: "Add success/error feedback with counts in Index.vue"
```

---

## Implementation Strategy

### MVP First (User Stories 1 + 2 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1 (View/Search)
4. Complete Phase 4: User Story 2 (Edit)
5. **STOP and VALIDATE**: Admin can view and edit members
6. Deploy/demo MVP

### Incremental Delivery

| Increment | Stories | Value Delivered |
|-----------|---------|-----------------|
| MVP | US1 + US2 | View, search, edit members |
| +1 | US3 + US4 | View course progress, filter by course |
| +2 | US5 + US6 | Selection and batch email |
| +3 | US7 | Gift course to selected members |
| Final | Polish | Edge cases, mobile, rate limiting |

---

## Notes

- [P] tasks = different files, no dependencies on incomplete tasks
- [Story] label maps task to specific user story
- UI text must be in Chinese (中文)
- All pages must be mobile-responsive (Tailwind mobile-first)
- Batch email uses existing Resend.com integration via `resend/resend-laravel`

## Deployment Checklist

### Queue Worker (Required for Batch Email and Gift Course)

| Environment | QUEUE_CONNECTION | Action |
|-------------|------------------|--------|
| Local Dev | `sync` | No setup needed - emails send immediately |
| Production | `database` | Must configure Supervisor for queue worker |

**Production**: Add Supervisor config to run `php artisan queue:work --sleep=3 --tries=3`

### Bug Fixes Applied

- **Duplicate Toast Fix**: Admin pages now use `defineOptions({ layout: AdminLayout })` instead of `<AdminLayout>` wrapper to prevent both AppLayout and AdminLayout rendering flash toasts (commit `40d9bfb`)

---

## Task Summary

| Phase | Tasks | Status |
|-------|-------|--------|
| Phase 1: Setup | 5 tasks (T001-T003b) | Complete |
| Phase 2: Foundational | 4 tasks (T004-T007) | Complete |
| Phase 3: US1 View/Search | 6 tasks (T008-T013) | Complete |
| Phase 4: US2 Edit | 8 tasks (T014-T021) | Complete |
| Phase 5: US3 Course Progress | 3 tasks (T022-T024) | Complete |
| Phase 6: US4 Course Filter | 4 tasks (T025-T028) | Complete |
| Phase 7: US5 Selection | 6 tasks (T029-T034) | Complete |
| Phase 8: US6 Batch Email | 9 tasks (T035-T043) | Complete |
| Phase 9: US7 Gift Course | 12 tasks (T050-T061) | Complete |
| Phase 10: Polish | 8 tasks (T044-T063) | Complete |

**Total**: 67 tasks (67 complete)
