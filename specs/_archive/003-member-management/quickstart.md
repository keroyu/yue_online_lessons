# Quickstart: Member Management

**Feature**: 003-member-management
**Date**: 2026-01-17
**Updated**: 2026-01-18

## Prerequisites

- PHP 8.2+
- Node.js 18+
- MySQL running on port 3306
- Composer dependencies installed
- NPM dependencies installed

## Quick Setup

```bash
# 1. Switch to feature branch
git checkout 003-member-management

# 2. Install dependencies (if not already)
composer install
npm install

# 3. Run migrations (if new migrations added)
php artisan migrate

# 4. Start development servers
php artisan serve &
npm run dev
```

## Development Workflow

### Backend Development

```bash
# Create new controller
php artisan make:controller Admin/MemberController

# Create form requests
php artisan make:request Admin/UpdateMemberRequest
php artisan make:request Admin/SendBatchEmailRequest
php artisan make:request Admin/GiftCourseRequest

# Create jobs for batch operations
php artisan make:job SendBatchEmailJob
php artisan make:job GiftCourseJob

# Create mailables
php artisan make:mail BatchEmailMail
php artisan make:mail CourseGiftedMail

# Run tests
php artisan test --filter=MemberManagement
```

### Frontend Development

```bash
# Start Vite dev server with HMR
npm run dev

# Type check (if TypeScript configured)
npm run type-check
```

### Queue Processing

**Local Development** (recommended):
```bash
# In .env, set sync for immediate email sending:
QUEUE_CONNECTION=sync
```

**Production** (requires worker):
```bash
# In .env:
QUEUE_CONNECTION=database

# Run queue worker (use Supervisor in production)
php artisan queue:work

# Or for development with auto-restart
php artisan queue:listen
```

## Key Files to Create

### Backend

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Admin/MemberController.php` | Main controller |
| `app/Http/Requests/Admin/UpdateMemberRequest.php` | Inline edit validation |
| `app/Http/Requests/Admin/SendBatchEmailRequest.php` | Batch email validation |
| `app/Http/Requests/Admin/GiftCourseRequest.php` | Gift course validation |
| `app/Jobs/SendBatchEmailJob.php` | Queued email sender |
| `app/Jobs/GiftCourseJob.php` | Queued gift processing |
| `app/Mail/BatchEmailMail.php` | Email template class |
| `app/Mail/CourseGiftedMail.php` | Gift notification template |
| `resources/views/emails/batch-email.blade.php` | Email blade template |
| `resources/views/emails/course-gifted.blade.php` | Gift notification template |

### Frontend

| File | Purpose |
|------|---------|
| `resources/js/Pages/Admin/Members/Index.vue` | Main member list page |
| `resources/js/Components/MemberDetailModal.vue` | Edit/view modal |
| `resources/js/Components/BatchEmailModal.vue` | Email composition modal |
| `resources/js/Components/GiftCourseModal.vue` | Gift course modal |

### Routes

Add to `routes/web.php` inside admin group:

```php
// Members
Route::get('/members', [MemberController::class, 'index'])->name('members.index');
Route::patch('/members/{member}', [MemberController::class, 'update'])->name('members.update');
Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');
Route::post('/members/batch-email', [MemberController::class, 'sendBatchEmail'])->name('members.batch-email');
Route::post('/members/gift-course', [MemberController::class, 'giftCourse'])->name('members.gift-course');
Route::get('/members/count', [MemberController::class, 'count'])->name('members.count');
```

## Testing Checklist

### Core Member Management
- [ ] Member list displays with pagination
- [ ] Search filters members by email/name
- [ ] Course filter shows only course owners
- [ ] Inline edit saves email/name/phone
- [ ] Copy button copies email to clipboard
- [ ] Modal shows member details + courses
- [ ] Modal edits nickname/birthday
- [ ] Progress percentage shows correctly
- [ ] Checkbox selection works across pages
- [ ] "Select all X" selects filtered members
- [ ] Admin-only access enforced

### Batch Email (User Story 6)
- [ ] Batch email modal opens with selection
- [ ] Batch email validates subject/body
- [ ] Emails are queued (check `jobs` table)
- [ ] Success message shows after send

### Gift Course (User Story 7)
- [ ] Gift course button appears when members selected
- [ ] Gift course modal shows course dropdown
- [ ] Selected course shows name and description
- [ ] Gift creates purchase records with type='gift'
- [ ] Members who already own course are skipped
- [ ] Result shows gifted count and already-owned count
- [ ] Gift notification email sent to recipients
- [ ] Email includes course name, description, welcome message
- [ ] Members without email get course but no notification

## Common Issues

### Emails not sending (local dev)

```bash
# Use sync queue for immediate sending in .env:
QUEUE_CONNECTION=sync
```

### Emails delayed (production)

```bash
# Check if queue worker is running
php artisan queue:work

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Duplicate success toasts on admin pages

Admin pages must use `defineOptions({ layout: AdminLayout })` in script, NOT `<AdminLayout>` wrapper in template. The default AppLayout is applied by `app.js`, so wrapping with AdminLayout causes both layouts to render.

### Inertia page not updating

```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Restart Vite
npm run dev
```

### Database connection issues

```bash
# Verify MySQL is running
mysql -u root -p

# Check .env database config
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yue_lessons
DB_USERNAME=root
DB_PASSWORD=
```

## Reference Documentation

- [spec.md](./spec.md) - Feature specification
- [data-model.md](./data-model.md) - Data model details
- [contracts/api.md](./contracts/api.md) - API contracts
- [research.md](./research.md) - Technical decisions
