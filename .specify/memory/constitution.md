<!--
  Sync Impact Report
  ==================
  Version change: 1.1.0 → 2.0.0 (MAJOR - complete rewrite based on codebase audit)

  Modified principles:
  - I. Laravel Conventions → I. Controller Layering (expanded from generic to evidence-based)
  - II. Vue & Frontend Standards → III. Frontend Component Architecture (expanded)
  - III. Responsive Design First → absorbed into III
  - IV. Simplicity Over Complexity → X. Simplicity & YAGNI (retained, repositioned)
  - V. Security & Sensitive Data → IX. Security (retained, repositioned)

  Added sections:
  - II. Service & Business Logic Encapsulation (NEW)
  - IV. Model Conventions (NEW)
  - V. Job & Queue Discipline (NEW)
  - VI. Email Delivery Patterns (NEW)
  - VII. Error Handling & Exception Flow (NEW)
  - VIII. Authorization & Permission Boundaries (NEW)
  - Full 10-section codebase analysis appendix

  Removed sections:
  - Technology Stack table (moved to CLAUDE.md reference)
  - Development Workflow commands (already in CLAUDE.md)

  Templates requiring updates:
  - .specify/templates/plan-template.md ⚠ pending (Constitution Check section
    should reference new principle IDs)
  - .specify/templates/spec-template.md ✅ (no changes needed - generic structure)
  - .specify/templates/tasks-template.md ✅ (no changes needed - generic structure)

  Follow-up TODOs:
  - plan-template.md: update Constitution Check gates to reference
    principles I–X by ID
-->

# Online Lesson Platform — Architecture Constitution

## Core Principles

### I. Controller Layering & Responsibility

Controllers MUST be thin orchestrators. Their allowed scope is:

1. Accept the HTTP request
2. Delegate validation to a **Form Request** class (Admin controllers)
   or call `$request->validate()` inline (public/lightweight controllers)
3. Call a **Service** for any logic that spans more than one model or
   involves side-effects (email, job dispatch, status transitions)
4. Shape data into an Inertia prop array (manually — no API Resource
   classes are used in this project)
5. Return `Inertia::render()` for pages or `redirect()->route()->with()`
   for form submissions

Controllers MUST NOT:
- Contain business logic that belongs in a Service
  (e.g. subscription state machine, idempotency checks)
- Access `auth()` in a Service's behalf — pass the `User` object down
- Dispatch Jobs directly when a Service already owns that workflow

**Evidence-based exception (actual codebase):**
Simple single-model CRUD (e.g. `Admin\CourseController::store`) MAY
perform direct Eloquent operations without a Service when no
cross-model side-effects are involved. This is the "simple path" rule —
add a Service only when complexity warrants it.

**Namespace conventions (observed):**
- `App\Http\Controllers\{domain}` — domain = `Admin`, `Member`, `Auth`
- Root namespace for public-facing: `HomeController`, `CourseController`,
  `DripSubscriptionController`

**Rationale**: Thin controllers keep HTTP concerns separate from domain
logic, making both independently testable and reusable (Console Commands
call the same Services).

---

### II. Service & Business Logic Encapsulation

A **Service class** (`App\Services\*`) MUST be created when the operation:
- Spans two or more Models, OR
- Involves external I/O (API calls, email, cache), OR
- Contains a state machine or multi-step workflow

Services MUST:
- Accept domain objects (`User`, `Course`) as parameters — never
  `Request` objects
- Return structured result arrays: `['success' => bool, 'error' => string]`
  (or `['success' => true, 'subscription' => $model]`)
- Handle idempotency checks internally
  (`PortalyWebhookService::createPurchase` checks `portaly_order_id`;
  `DripService::subscribe` checks existing subscription)
- Log significant operations via `Log::info()` with structured context
- Dispatch Jobs for async work

Services MUST NOT:
- Access `auth()` or `Request` — they receive identity via parameters
- Return HTTP responses (no `Response`, `RedirectResponse`, `JsonResponse`)
- Render Inertia views

**Naming**: `{Domain}Service` — e.g. `DripService`,
`VerificationCodeService`, `PortalyWebhookService`,
`SubstackRssService`, `VideoEmbedService`.

**Rationale**: Services are the single source of business rules. Console
Commands (`ProcessDripEmails`), Controllers, and webhooks all call the
same Service, guaranteeing consistent behavior.

---

### III. Frontend Component Architecture

All frontend code MUST use Vue 3 Composition API with `<script setup>`.

**Layer responsibilities (observed):**

| Layer | Location | Responsibility |
|-------|----------|----------------|
| Page | `Pages/{Domain}/` | Receive Inertia props, compose Components, set `<Head>` |
| Component | `Components/{Context}/` | Encapsulate UI logic, emit events, manage local state |
| Layout | `Components/Layout/` | `AppLayout`, `Navigation`, `Footer` — wrap all pages |

**Data flow rules:**
- **Server → Frontend**: Controller shapes props manually (no API
  Resource classes). Shared props via `HandleInertiaRequests` middleware
  (`auth`, `flash`).
- **Frontend → Server**: `router.post/put/delete` from `@inertiajs/vue3`.
  Error handling via `onError` callback.
- **State management**: Local `ref`/`computed` only. No Vuex, no Pinia.
  If state needs to survive page navigation, it goes through Inertia
  shared props or `localStorage`.
- **localStorage**: Used ONLY for ephemeral client-side features (promo
  block unlock timers). Never for business state.
- **Optimistic UI**: Allowed for non-critical UX (lesson completion
  checkbox) with server reconciliation on next page load.

**Practices that MUST be followed:**
- `defineProps()` with explicit types on every component
- Tailwind utility classes for all styling — no custom CSS files
- Mobile-first responsive design (`sm:`, `md:`, `lg:` breakpoints)

**Practices NOT used in this project (do not introduce):**
- Global state stores (Vuex/Pinia)
- API Resource classes on backend
- Axios for data fetching (use Inertia router)
- Custom CSS classes or SCSS

**Rationale**: The Inertia + local-state pattern keeps the frontend
simple. Data ownership stays on the backend; the frontend is a
rendering layer.

---

### IV. Model Conventions

All Eloquent models MUST follow these patterns:

1. **`$fillable` array** — never `$guarded = []`
2. **`casts()` method** — for type declarations (not `$casts` property)
3. **`Attribute` class** — for computed accessors
   (`protected function fooBar(): Attribute`)
4. **`scope*` methods** — for reusable query scopes
   (`scopePublished`, `scopeActive`, `scopeOrdered`)
5. **Explicit relationship methods** with type hints
   (`public function lessons(): HasMany`)

**Naming (observed, MUST follow):**

| Pattern | Convention | Example |
|---------|-----------|---------|
| Model class | Singular PascalCase | `DripSubscription` |
| Table | Plural snake_case (Laravel default) | `drip_subscriptions` |
| Foreign key | `{model}_id` | `course_id`, `user_id` |
| Accessor | camelCase method → snake_case access | `thumbnailUrl()` → `$course->thumbnail_url` |
| Scope | `scope{Name}` | `scopePublished` |
| Boolean check | `is{Condition}()` on model | `isAdmin()`, `isExpired()` |
| Relationship | Descriptive noun | `purchases()`, `dripSubscriptions()` |

**Default ordering in relationships**: When a collection has a natural
sort order, define it in the relationship method:
`return $this->hasMany(Lesson::class)->orderBy('sort_order');`

**SoftDeletes**: Used ONLY on `Course`. Do not add to other models
unless explicitly required.

**Timestamps**: When only `created_at` is needed, set
`$timestamps = false` and manually assign `created_at` in `boot()`
(see `LessonProgress`, `CourseImage`).

**Auto-generated fields**: Use `booted()` or `boot()` for fields that
must be set on creation (e.g. `DripSubscription::unsubscribe_token`).

**Rationale**: Consistent model conventions enable any developer to
predict structure without reading every file.

---

### V. Job & Queue Discipline

All async work MUST be encapsulated in a Job class implementing
`ShouldQueue`.

**Job constructor rules:**
- Accept **primitive types** (`int`, `string`, `array`) — NOT model
  instances. This avoids serialization issues and race conditions.
  (Evidence: `SendDripEmailJob(int $userId, int $lessonId, int $subscriptionId)`)
- Load models in `handle()` via `::find()`

**Retry configuration (MUST set on every Job):**
- `$tries = 3` (minimum)
- `$backoff`: `int` for uniform delay, `array` for progressive
  (e.g. `[60, 300, 900]`)

**Error handling in Jobs:**
- **Single-item Jobs** (e.g. `SendDripEmailJob`): If the model is
  missing, log a warning and `return` (do not throw). If the operation
  fails (email send), `throw` to trigger retry.
- **Batch Jobs** (e.g. `GiftCourseJob`, `SendBatchEmailJob`): Wrap
  each iteration in try/catch. Log failures per-item. Never let one
  item's failure stop the entire batch.

**Guard clause**: Before performing work, check if the operation is
still valid (e.g. `SendDripEmailJob` checks `$subscription->status !== 'active'`).

**Rationale**: Jobs run outside the request cycle. Defensive coding and
primitive constructors prevent silent data corruption.

---

### VI. Email Delivery Patterns

**Mailable classes** (`App\Mail\*`) MUST use:
- `Queueable`, `SerializesModels` traits
- `envelope()` for subject line
- `content()` pointing to a Blade view in `resources/views/emails/`
- Constructor with named parameters for all template data

**Sync vs Async decision rule (observed):**

| Scenario | Delivery | Rationale |
|----------|----------|-----------|
| User is waiting for the result (verification code) | **Sync** `Mail::to()->send()` | Immediate feedback required |
| Bulk or background operations (batch email, drip, gift notification) | **Async** via Job dispatch | Don't block the request |

**Email subjects**: MUST be in 中文 for user-facing emails.
Format: `"{$courseName}：{$lessonTitle}"` or similar context-rich pattern.

**Rationale**: Sync-only when the user needs immediate confirmation;
async everywhere else to keep response times fast.

---

### VII. Error Handling & Exception Flow

**Layer-by-layer rules (observed):**

| Layer | Behavior |
|-------|----------|
| **Service** | Return `['success' => false, 'error' => '中文訊息']`. Do NOT throw for business rule violations. |
| **Controller** | Translate service errors to `withErrors()` or `with('error')`. Catch email send failures, log, return user-friendly message. |
| **Job** | Log + return for missing data. Throw for retriable failures (email). Per-item try/catch in batch loops. |
| **Webhook** | Catch known errors (RuntimeException) and return HTTP 200 to prevent webhook retry loops. Re-throw truly unexpected exceptions. |

**Logging conventions:**
- `Log::info()` — successful operations with context array
- `Log::warning()` — non-critical issues (missing data, rate limits)
- `Log::error()` — failures requiring investigation

**User-facing errors**: Always in 中文. Use Laravel validation message
customization (`messages()` in Form Request or inline).

**Rationale**: Never expose stack traces or English error messages to
users. Services communicate failure through return values, not
exceptions, keeping control flow predictable.

---

### VIII. Authorization & Permission Boundaries

**Route-level** (first gate):
- `auth` middleware for authenticated routes
- `admin` middleware (`AdminMiddleware`) for admin routes
- `guest` middleware for login pages
- `throttle` for rate-sensitive endpoints

**Controller-level** (second gate):
- Admin check: `$user->isAdmin()`
- Ownership check: `$user->purchases()->where('course_id', ...)->exists()`
- Role guard: `$member->role !== 'member'` → abort

**Policy classes exist** (`CoursePolicy`, `PurchasePolicy`) but are
NOT currently invoked via `$this->authorize()` in controllers. Actual
authorization is done inline. This is an **acknowledged deviation**.

**Rule: Services MUST NOT access `auth()`.**
Services receive the `User` as a parameter. This ensures Services are
testable without HTTP context.

**Frontend**: Receives `auth.user` (id, email, nickname, role) via
Inertia shared props. Admin-only UI elements are conditionally rendered
using `isAdmin` prop passed from the controller.

**Rationale**: Two-layer authorization (middleware + controller) with
explicit inline checks keeps the permission model simple and auditable.

---

### IX. Security & Sensitive Data

- Environment files (`.env`) MUST be in `.gitignore`
- API keys, secrets, credentials MUST NOT appear in code
- Database credentials MUST be managed through environment variables
- Webhook signatures MUST be verified (`PortalyWebhookService::verifySignature`)
- Verification codes MUST have expiration, attempt limits, and lockout
- Unsubscribe tokens MUST be UUID-based and unique per subscription
- Video delivery uses Vimeo/YouTube embed (no direct file hosting)
- Admin-authored HTML (promo blocks) is rendered via `v-html` — treated
  as trusted content since only admins can edit

**Rationale**: Defense in depth at every boundary — env vars, HMAC
verification, rate limiting, token-based unsubscribe.

---

### X. Simplicity & YAGNI

- Features MUST be completed before optimization
- Over-engineering MUST be avoided — implement what is needed now
- Do NOT introduce patterns not already present in the codebase
  (no Repository pattern, no DTO classes, no Event/Listener for
  simple flows, no API Resources, no global state stores)
- If a pattern exists in the codebase (e.g. manual prop shaping in
  controllers), follow it — do not "upgrade" to a different approach
  without amending this constitution
- Three similar lines of code is better than a premature abstraction

**Rationale**: This is a small-team project. Consistency with existing
patterns is more valuable than theoretical "best practices".

---

## Appendix: Codebase Analysis (Evidence Base)

### A1. Controller / Service / Job Responsibility Map

**Controllers (observed responsibilities):**

| Controller | Layer | Does validation via | Has Service DI | Direct DB ops |
|-----------|-------|-------------------|---------------|--------------|
| `HomeController` | Public | — | SubstackRssService | Read-only queries |
| `CourseController` (public) | Public | — | — | Read-only + inline auth |
| `DripSubscriptionController` | Public | Inline `$request->validate()` | DripService, VerificationCodeService | — |
| `LoginController` | Auth | Form Request | VerificationCodeService | User create/update |
| `Admin\CourseController` | Admin | Form Request | — | Full CRUD |
| `Admin\MemberController` | Admin | Form Request | — | Read + Job dispatch |
| `Member\ClassroomController` | Member | — | — | Read + LessonProgress CRUD |
| `Member\LearningController` | Member | — | — | Read-only |
| `Member\SettingsController` | Member | Form Request | — | Update profile |

**Boundary rule**: A Service is introduced when the controller would
need to coordinate multiple models with side-effects. Simple
single-model CRUD stays in the controller.

**Actual evidence of this boundary:**
- `Admin\CourseController::store()` does `Course::create()` +
  `Purchase::create()` in a `DB::transaction()` — no Service,
  because it's a straightforward admin action.
- `DripSubscriptionController::verify()` delegates to both
  `VerificationCodeService` and `DripService` — complex multi-step
  flow warrants Services.

---

### A2. Model Relationship Map

```
User
├── hasMany → Purchase
├── hasMany → LessonProgress
├── hasMany → DripSubscription
└── hasMany → DripSubscription (scoped: activeDripSubscriptions)

Course (SoftDeletes)
├── hasMany → Purchase
├── hasMany → Chapter (ordered by sort_order)
├── hasMany → Lesson (ordered by sort_order)
├── hasMany → CourseImage
├── hasMany → DripConversionTarget (FK: drip_course_id)
└── hasMany → DripSubscription

Chapter
├── belongsTo → Course
└── hasMany → Lesson (ordered by sort_order)

Lesson
├── belongsTo → Course
├── belongsTo → Chapter
└── hasMany → LessonProgress

Purchase
├── belongsTo → User
└── belongsTo → Course

DripSubscription
├── belongsTo → User
└── belongsTo → Course

DripConversionTarget
├── belongsTo → Course (as dripCourse, FK: drip_course_id)
└── belongsTo → Course (as targetCourse, FK: target_course_id)

CourseImage
└── belongsTo → Course

LessonProgress
├── belongsTo → User
└── belongsTo → Lesson

VerificationCode (standalone, no relations)
```

---

### A3. Frontend Component Inventory

**Pages** (15 total):
`Home`, `Error`, `Auth/Login`, `Course/Show`,
`Member/Classroom`, `Member/ClassroomUnauthorized`, `Member/Learning`,
`Member/Settings`, `Admin/Dashboard`, `Admin/Courses/Index`,
`Admin/Courses/Create`, `Admin/Courses/Edit`, `Admin/Courses/Chapters`,
`Admin/Courses/Gallery`, `Admin/Members/Index`

**Components** (25 total):
- Layout: `AppLayout`, `Navigation`, `Footer`
- Classroom: `ChapterSidebar`, `VideoPlayer`, `HtmlContent`, `LessonItem`
- Admin: `ChapterList`, `CourseForm`, `LessonForm`, `ImageGalleryModal`
- Course: `PriceDisplay`, `DripSubscribeForm`
- Legal: `LegalPolicyModal`, `PrivacyContent`, `PurchaseContent`, `TermsContent`
- Shared: `CourseCard`, `MyCourseCard`, `SocialLinks`,
  `SubstackArticles`, `VerificationCodeInput`, `BatchEmailModal`,
  `GiftCourseModal`, `MemberDetailModal`

**No component uses Vuex, Pinia, or axios.**

---

### A4. Email / Queue Inventory

| Job | Trigger | Constructor params | $tries | $backoff |
|-----|---------|-------------------|--------|----------|
| `SendDripEmailJob` | `DripService::subscribe`, `DripService::processSubscription` | `int, int, int` | 3 | `[60, 300, 900]` |
| `SendBatchEmailJob` | `Admin\MemberController::sendBatchEmail` | `array, string, string` | 3 | `60` |
| `GiftCourseJob` | `Admin\MemberController::giftCourse` | `array, int` | 3 | `60` |

| Mailable | Delivery | Template |
|----------|----------|----------|
| `VerificationCodeMail` | Sync | (not inspected) |
| `DripLessonMail` | Async (via Job) | `emails.drip-lesson` |
| `BatchEmailMail` | Async (via Job) | (not inspected) |
| `CourseGiftedMail` | Async (via Job) | (not inspected) |

---

### A5. Error Handling Observed Patterns

| Context | Pattern | Example |
|---------|---------|---------|
| Service business rule | Return `['success' => false, 'error' => '...']` | `DripService::subscribe` — already subscribed |
| Controller email failure | try/catch, Log::error, return withErrors | `DripSubscriptionController::subscribe` |
| Job missing data | null check, Log::warning, return (no throw) | `SendDripEmailJob::handle` |
| Job email failure | throw to trigger retry | `SendDripEmailJob::handle` |
| Job batch item failure | per-item try/catch, Log::error, continue | `GiftCourseJob::handle` |
| Webhook known error | catch RuntimeException, return HTTP 200 | `PortalyWebhookService::handlePaidEvent` |
| Webhook unknown error | re-throw | `PortalyWebhookService::handlePaidEvent` |

---

### A6. Authorization Matrix

| Route group | Middleware | Controller check |
|------------|-----------|-----------------|
| Public (`/`, `/course/{id}`) | none | Inline `isAdmin()` for draft visibility |
| Auth (`/login`) | `guest` | — |
| Drip (`/drip/*`) | none (public) | Service checks course type |
| Member (`/member/*`) | `auth` | Purchase ownership check |
| Admin (`/admin/*`) | `auth`, `admin` | Role check on member ops |

---

### A7. Data Flow: State Ownership

| State | Owner | Mechanism |
|-------|-------|-----------|
| Business data (courses, purchases, subscriptions) | Backend DB | Eloquent models |
| Auth identity | Backend session | Inertia shared props |
| Flash messages | Backend session | `with('success')` → Inertia flash |
| UI state (modals, sidebar) | Frontend `ref` | Component-local |
| Promo block timers | Frontend `localStorage` | Per-lesson keys |
| Lesson completion optimistic | Frontend `ref` | Reconciled on page load |

---

### A8. Testing Status

- **Current coverage**: Default example tests only
  (`tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`)
- **Factories**: `HasFactory` trait on Course, Lesson, Chapter, Purchase,
  User, LessonProgress, CourseImage (factories likely exist or can be generated)
- **Test runner**: `php artisan test` (PHPUnit)
- **Confidence source**: Manual testing + Inertia SSR rendering

---

### A9. Dependency Direction

```
Console Command → Service → Model
                ↘ Job → Model + Mail
Controller → Service → Model
           ↘ Form Request (validation)
           → Inertia::render (response)
Middleware → Model (auth check)
Frontend Page → Component → (local state only)
```

**No reverse dependencies observed.** Services do not call Controllers.
Jobs do not call Services. Models do not call Services.

---

### A10. Performance Conventions

**MUST NOT occur:**
- N+1 queries — use `->with()` for relationship loading
- Unbounded `->get()` on user-facing tables — use `->paginate()` or
  `->select()` with limits
- Blocking external calls in request cycle without timeout

**Observed safeguards:**
- `->with(['course.lessons'])` on subscription queries
- `->select([...])` in HomeController
- `Cache::remember()` for RSS with 1-hour TTL
- `Http::timeout(5)` on external HTTP
- `->chunk(50)` for Job dispatch
- `->paginate($perPage)` with `min($input, 100)` ceiling

---

## Governance

This constitution establishes binding development standards for the
Online Lesson Platform.

**Amendment Process**:
1. Proposed changes MUST be documented with rationale
2. Changes MUST be reviewed for impact on existing code
3. Migration plan MUST be provided for breaking changes
4. Version MUST be incremented according to semantic versioning

**Versioning Policy**:
- MAJOR: Backward-incompatible changes (principle removals, pattern changes)
- MINOR: New principles or expanded guidance
- PATCH: Clarifications and non-semantic refinements

**Compliance**:
- All new code MUST verify compliance with these principles
- Deviations MUST be justified and documented as exceptions
- If a pattern is not covered here, follow the nearest existing pattern
  in the codebase

**Runtime Guidance**: See `CLAUDE.md` for development commands, tech
stack table, and quick reference.

**Version**: 2.0.0 | **Ratified**: 2026-01-16 | **Last Amended**: 2026-02-16
