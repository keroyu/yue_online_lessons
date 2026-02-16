# Quickstart: Email 連鎖加溫系統

**Feature**: 005-drip-email
**Date**: 2026-02-05
**Updated**: 2026-02-05 (新增 Lesson 促銷區塊)

## Prerequisites

- PHP 8.2+
- MySQL running on port 3306
- Node.js (for frontend build)
- Resend API key configured in `.env`

## Setup

### 1. Pull the branch

```bash
git checkout 005-drip-email
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Run migrations

```bash
php artisan migrate
```

This will create:
- Add `course_type` and `drip_interval_days` columns to `courses` table
- Create `drip_subscriptions` table
- Create `drip_conversion_targets` table

### 4. Start development servers

```bash
# Terminal 1: Backend
php artisan serve

# Terminal 2: Frontend with hot reload
npm run dev

# Terminal 3: Queue worker (for email sending)
php artisan queue:work
```

---

## Testing the Feature

### A. Create a Drip Course (Admin)

1. Login as admin
2. Go to Admin → Courses → Create/Edit Course
3. Set "Course Type" to "連鎖課程"
4. Set "發信間隔天數" (e.g., 3 days)
5. Add Lessons with content
6. Optionally set target courses for conversion

### B. Subscribe as Guest

1. Visit the course detail page
2. Click "免費訂閱"
3. Enter email address
4. Enter verification code
5. Verify:
   - New User created (if email was new)
   - DripSubscription created with status "active"
   - Welcome email received

### C. Subscribe as Logged-in Member

1. Login as a member
2. Visit a drip course detail page
3. Click "訂閱" button
4. Verify:
   - DripSubscription created
   - Redirect to classroom
   - Welcome email received

### D. Test Classroom Unlock Logic

1. Enter classroom for a subscribed drip course
2. Verify:
   - First lesson is unlocked (Day 0)
   - Other lessons show "X 天後解鎖"

To test time-based unlock:
```bash
# In tinker, modify subscribed_at to simulate time passing
php artisan tinker

>>> $sub = App\Models\DripSubscription::first();
>>> $sub->subscribed_at = now()->subDays(5);
>>> $sub->save();
```

### E. Test Daily Email Scheduler

```bash
# Run the scheduler command manually
php artisan drip:process-emails

# Or test specific subscription
php artisan tinker
>>> app(App\Services\DripService::class)->processSubscription($subscription);
```

### F. Test Unsubscribe

1. Check welcome email for unsubscribe link
2. Click link → shows warning page
3. Confirm unsubscribe
4. Verify:
   - Status changed to "unsubscribed"
   - Can still access unlocked lessons
   - Cannot re-subscribe

### G. Test Conversion

1. Set Course B as target of Drip Course A
2. Subscribe to Drip Course A
3. Purchase Course B via Portaly
4. Verify:
   - DripSubscription status changed to "converted"
   - All lessons in Course A are now unlocked
   - No more drip emails sent

### H. Test Lesson Promo Block

1. Admin: 進入課程 → 章節編輯 → 編輯某個 Lesson
2. 在 LessonForm 中設定 `promo_delay_seconds` = 60（測試用 60 秒）
3. 設定 `promo_html` = `<div class="bg-yellow-100 p-4"><a href="/course/123">立即購買</a></div>`
4. 儲存後以會員身份進入教室（Classroom），選擇該 Lesson
5. 驗證：
   - 初始顯示「請先觀看課程」+ 0:59 倒數
   - 1 分鐘後促銷區塊出現
   - 重新整理頁面 → 促銷區塊直接顯示（無需倒數）
   - 檢查 localStorage: `promo_unlocked_lesson_{id}` = true

重置促銷狀態（測試用）：
```javascript
// 在瀏覽器 Console 執行
localStorage.removeItem('promo_unlocked_lesson_123'); // 替換 123 為 lesson ID
```

---

## Key Files

### Backend

| File | Purpose |
|------|---------|
| `app/Models/DripSubscription.php` | Subscription model |
| `app/Models/DripConversionTarget.php` | Conversion target model |
| `app/Services/DripService.php` | Core business logic |
| `app/Console/Commands/ProcessDripEmails.php` | Daily scheduler command |
| `app/Jobs/SendDripEmailJob.php` | Email sending job |
| `app/Mail/DripLessonMail.php` | Email template |
| `app/Http/Controllers/Admin/ChapterController.php` | MODIFY: Lesson map 加入 promo 欄位 |
| `app/Http/Controllers/DripSubscriptionController.php` | Subscribe/unsubscribe |

### Frontend

| File | Purpose |
|------|---------|
| `resources/js/Components/Course/DripSubscribeForm.vue` | Subscribe form (email + verification code) |
| `resources/js/Components/Classroom/LessonPromoBlock.vue` | Promo block with countdown |
| `resources/js/Components/Admin/LessonForm.vue` | MODIFY: Add promo fields |
| `resources/js/Pages/Admin/Courses/Edit.vue` | MODIFY: Add drip settings section |
| `resources/js/Pages/Member/Classroom.vue` | MODIFY: Show promo block |
| `resources/js/Pages/Drip/Unsubscribe.vue` | Unsubscribe confirmation |

### Database

| Migration | Purpose |
|-----------|---------|
| `add_drip_fields_to_courses` | course_type, drip_interval_days |
| `create_drip_subscriptions` | Subscription tracking |
| `create_drip_conversion_targets` | Conversion goal mapping |
| `add_promo_fields_to_lessons` | promo_delay_seconds, promo_html |

---

## Testing

```bash
# Run all tests
php artisan test

# Run only drip-related tests
php artisan test --filter=Drip

# Run specific test file
php artisan test tests/Feature/DripSubscriptionTest.php
```

---

## Scheduler (Production)

The drip email processor runs daily at 9:00 AM via Laravel Scheduler.

**routes/console.php**:
```php
Schedule::command('drip:process-emails')->dailyAt('09:00');
```

**Verify scheduler is registered**:
```bash
php artisan schedule:list
```

**Laravel Forge**: Scheduler is already configured via cron.

---

## Troubleshooting

### Emails not sending

1. Check queue worker is running: `php artisan queue:work`
2. Check failed jobs: `php artisan queue:failed`
3. Verify Resend API key in `.env`

### Unlock calculation wrong

1. Check `subscribed_at` timestamp
2. Check `drip_interval_days` on course
3. Check `sort_order` on lessons (starts from 0)

### Conversion not triggering

1. Verify target course is set in `drip_conversion_targets`
2. Check Portaly webhook is calling the updated handler
3. Check user has active subscription to the drip course

### Promo block settings blank after save (admin)

1. Check `ChapterController@index` lesson map includes `promo_delay_seconds` and `promo_html`
2. Both chapter lessons AND standalone lessons maps must include these fields
3. This data feeds `ChapterList.vue` → `LessonForm.vue` via `editingLesson` prop

### Promo block not showing

1. Check `promo_delay_seconds` is not null
2. Check `promo_html` is not empty
3. Check localStorage for `promo_unlocked_lesson_{id}`
4. Clear localStorage and refresh to reset

### Promo countdown not working

1. Check video player events are triggering (if using video time)
2. Check page timer is running (for non-video lessons)
3. Verify localStorage is accessible (not in incognito with cookies blocked)

---

## Environment Variables

```env
# Required (existing)
RESEND_API_KEY=re_xxxx

# Optional: Override default send time (for testing)
DRIP_SEND_HOUR=9
```
