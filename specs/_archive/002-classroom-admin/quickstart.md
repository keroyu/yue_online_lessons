# Quickstart: 上課頁面與管理員後臺

**Branch**: `002-classroom-admin` | **Date**: 2026-01-17

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+
- Git

## Setup Steps

### 1. Clone and Switch Branch

```bash
git checkout 002-classroom-admin
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Install New Package

```bash
npm install vuedraggable@next
```

### 4. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your database credentials:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yue_lessons
DB_USERNAME=forge
DB_PASSWORD=your_password
```

### 5. Storage Link

```bash
php artisan storage:link
```

### 6. Database Migration

```bash
php artisan migrate:fresh --seed
```

### 7. Start Development Servers

Terminal 1:
```bash
php artisan serve
```

Terminal 2:
```bash
npm run dev
```

## Verification Checklist

### Admin Features

1. **Login as Admin**
   - URL: `http://localhost:8000/login`
   - Email: `themustbig@gmail.com`
   - Use OTP login flow

2. **Access Admin Dashboard**
   - URL: `http://localhost:8000/admin`
   - Should see dashboard with course overview

3. **Course Management**
   - Create new course with all required fields
   - Edit course details
   - Upload course thumbnail
   - Delete a course (without purchases)

4. **Chapter/Lesson Management**
   - Add chapters to a course
   - Add lessons with Vimeo URL
   - Add lessons with HTML content
   - Drag to reorder chapters and lessons

5. **Course Publishing**
   - Publish course as "熱賣中"
   - Publish course as "預購中" with future date
   - Unpublish course back to draft

6. **Image Gallery**
   - Upload images to course gallery
   - Delete images from gallery
   - Copy image URL for HTML insertion

### Member Features

1. **Login as Member**
   - Email: `member1@example.com`
   - Use OTP login flow

2. **My Courses Page**
   - URL: `http://localhost:8000/member/learning`
   - Should see purchased courses

3. **Classroom Page**
   - Click a course to enter classroom
   - Verify chapter sidebar displays correctly
   - Click lesson to switch content
   - Verify green checkmark appears after clicking
   - Click checkmark to mark as incomplete
   - Verify mobile responsive layout

### Access Control

1. **Unauthorized Admin Access**
   - Login as member1@example.com
   - Try to access `/admin`
   - Should redirect to home with error message

2. **Unpurchased Course Access**
   - Try to access classroom for unpurchased course
   - Should see "您尚未購買此課程" message

## Test Commands

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter=ClassroomTest
php artisan test --filter=CourseManagementTest
```

## Scheduled Task (for preorder auto-switch)

Add to crontab on server:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## Troubleshooting

### Images not displaying
```bash
php artisan storage:link
```

### Permission denied on storage
```bash
chmod -R 775 storage bootstrap/cache
```

### Vite not loading
```bash
npm run dev
# Make sure Vite is running before testing
```

### Database errors after migration changes
```bash
php artisan migrate:fresh --seed
# Warning: This will delete all data
```

## Key URLs

| Feature | URL |
|---------|-----|
| Homepage | http://localhost:8000 |
| Login | http://localhost:8000/login |
| Admin Dashboard | http://localhost:8000/admin |
| Admin Courses | http://localhost:8000/admin/courses |
| My Courses | http://localhost:8000/member/learning |
| Classroom | http://localhost:8000/member/classroom/{id} |
