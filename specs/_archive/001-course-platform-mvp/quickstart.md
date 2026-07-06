# Quickstart Guide: 數位課程販售平台 MVP

**Branch**: `001-course-platform-mvp` | **Date**: 2026-01-16

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ & npm
- MySQL 8.0+

## Initial Setup

### 1. Clone & Install Dependencies

```bash
# Clone the repository
git clone git@github.com:keroyu/yue_online_lessons.git
cd yue_online_lessons

# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Edit `.env` with your local settings:

```env
APP_NAME="YUE Lessons"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yue_lessons
DB_USERNAME=root
DB_PASSWORD=

# Email (Resend.com)
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxx_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
MAIL_FROM_ADDRESS=noreply@yueyuknows.com
MAIL_FROM_NAME="經營者時間銀行"

SESSION_LIFETIME=43200  # 30 days in minutes
```

### 3. Database Setup

```bash
# Create database (MySQL CLI)
mysql -u root -p -e "CREATE DATABASE yue_lessons CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations and seed data
php artisan migrate:fresh --seed
```

### 4. Build Frontend Assets

```bash
# Development build with hot reload
npm run dev

# OR production build
npm run build
```

### 5. Start Development Server

```bash
# In terminal 1: Start Laravel server
php artisan serve

# In terminal 2: Start Vite dev server (if using npm run dev)
npm run dev
```

Visit: http://localhost:8000

---

## Test Accounts (After Seeding)

| Role | Email | Notes |
|------|-------|-------|
| Admin | admin@example.com | 管理員帳號 |
| Member | member1@example.com | 有購買課程 |
| Member | member2@example.com | 有購買課程 |
| Member | member3@example.com | 無購買紀錄 |

**Note**: 使用 Email OTP 登入，驗證碼會發送到該 email。開發時可使用 `MAIL_MAILER=log` 將驗證碼輸出到 `laravel.log`，或使用真實的 Resend API Key 測試。

---

## Development Commands

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter=AuthTest

# Clear all caches
php artisan optimize:clear

# Create new migration
php artisan make:migration create_xxx_table

# Create new model with migration
php artisan make:model ModelName -m

# Create new controller
php artisan make:controller ControllerName

# Create form request
php artisan make:request RequestName
```

---

## Project Structure Overview

```
├── app/
│   ├── Http/Controllers/     # Request handlers
│   ├── Models/               # Eloquent models
│   ├── Services/             # Business logic
│   └── Mail/                 # Mailable classes
├── database/
│   ├── migrations/           # Database schema
│   └── seeders/              # Test data
├── resources/js/
│   ├── Pages/                # Inertia page components
│   └── Components/           # Reusable Vue components
├── routes/web.php            # Route definitions
└── tests/                    # PHPUnit tests
```

---

## Key URLs (Development)

| URL | Description |
|-----|-------------|
| / | 首頁（課程列表） |
| /course/{id} | 課程販售頁 |
| /login | 登入頁面 |
| /member/learning | 我的課程 |
| /member/settings | 帳號設定 |

---

## Debugging Tips

### Check Email Sending
For local development, you have two options:

**Option 1: Log driver (no actual email sent)**
```env
MAIL_MAILER=log
```
Check `storage/logs/laravel.log` for the OTP code.

**Option 2: Resend.com (real email)**
```env
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxx_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
MAIL_FROM_ADDRESS=noreply@yueyuknows.com
MAIL_FROM_NAME="經營者時間銀行"
```
Get your API key at: https://resend.com/api-keys (已設定域名: yueyuknows.com)

### View Verification Codes
If using log driver, check `storage/logs/laravel.log` for the OTP code.
If using Resend, check your email inbox (or Resend dashboard for delivery status).

### Database Queries
Enable query logging in development:

```php
// AppServiceProvider boot()
if (app()->environment('local')) {
    \DB::listen(function ($query) {
        \Log::info($query->sql, $query->bindings);
    });
}
```

### Inertia Debug
Install Vue DevTools browser extension for debugging Inertia props and state.

---

## Deployment Notes

For Laravel Forge deployment:

1. Set environment variables in Forge
2. Configure queue worker (optional for future)
3. Set up SSL certificate
4. Configure scheduled tasks (for cleanup, optional)

```bash
# Production build
npm run build

# Optimize Laravel
php artisan optimize
php artisan view:cache
php artisan route:cache
```
