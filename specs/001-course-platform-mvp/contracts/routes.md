# API Routes Contract: 數位課程販售平台 MVP

**Branch**: `001-course-platform-mvp` | **Date**: 2026-01-16

## Overview

本專案使用 Inertia.js，所有路由返回 Inertia 頁面而非純 JSON API。
以下定義所有 HTTP 路由及其對應的 Controller 方法和頁面組件。

---

## Public Routes (Guest)

### Home Page
```
GET /
Controller: HomeController@index
Page: Pages/Home.vue
Props: { courses: Course[] }
Description: 首頁，顯示所有販售中課程
```

### Course Detail Page
```
GET /course/{course}
Controller: CourseController@show
Page: Pages/Course/Show.vue
Props: { course: Course }
Description: 課程獨立販售頁
404: 課程不存在或未上架
```

---

## Authentication Routes (Guest Only)

### Login Page
```
GET /login
Controller: Auth\LoginController@showLoginForm
Page: Pages/Auth/Login.vue
Props: {}
Middleware: guest
Description: 登入頁面（輸入 email）
```

### Send Verification Code
```
POST /login/send-code
Controller: Auth\LoginController@sendCode
Request: SendVerificationCodeRequest
  - email: required|email|max:255
Response (success): redirect back with flash message
Response (error): validation errors or rate limit error
Middleware: guest, throttle:6,1
Description: 發送驗證碼到 email
Rate Limit: 每分鐘最多 6 次請求
```

### Verify Code
```
POST /login/verify
Controller: Auth\LoginController@verify
Request: VerifyCodeRequest
  - email: required|email
  - code: required|string|size:6
Response (success): redirect to /member/learning
Response (error): invalid code, expired, or locked
Middleware: guest
Description: 驗證 OTP 並登入（或自動註冊）
```

### Logout
```
POST /logout
Controller: Auth\LoginController@logout
Response: redirect to /
Middleware: auth
Description: 登出
```

---

## Member Routes (Authenticated)

### My Learning Page
```
GET /member/learning
Controller: Member\LearningController@index
Page: Pages/Member/Learning.vue
Props: {
  courses: Array<{
    course: Course,
    purchase: Purchase,
    progress: CourseProgress
  }>
}
Middleware: auth
Description: 我的課程頁面
```

### Settings Page
```
GET /member/settings
Controller: Member\SettingsController@index
Page: Pages/Member/Settings.vue
Props: {
  user: User,
  orders: Purchase[]
}
Middleware: auth
Description: 帳號設定頁面
```

### Update Profile
```
PUT /member/settings
Controller: Member\SettingsController@update
Request: UpdateProfileRequest
  - nickname: nullable|string|max:100
  - real_name: nullable|string|max:100
  - phone: nullable|string|max:20
  - birth_date: nullable|date|before:today
Response (success): redirect back with flash message
Response (error): validation errors
Middleware: auth
Description: 更新會員資料
```

---

## Route Definitions (Laravel)

```php
// routes/web.php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Member\LearningController;
use App\Http\Controllers\Member\SettingsController;

// Public
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/course/{course}', [CourseController::class, 'show'])->name('course.show');

// Auth (Guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login/send-code', [LoginController::class, 'sendCode'])
        ->middleware('throttle:6,1')
        ->name('login.send-code');
    Route::post('/login/verify', [LoginController::class, 'verify'])->name('login.verify');
});

// Logout
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Member (Authenticated)
Route::middleware('auth')->prefix('member')->name('member.')->group(function () {
    Route::get('/learning', [LearningController::class, 'index'])->name('learning');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});
```

---

## Data Transfer Objects

### Course (for listing)
```typescript
interface CourseListItem {
  id: number;
  name: string;
  tagline: string;
  price: number;
  thumbnail: string | null;
  instructor_name: string;
  type: 'lecture' | 'mini' | 'full';
}
```

### Course (for detail page)
```typescript
interface CourseDetail extends CourseListItem {
  description: string;
  description_html: string | null;
  portaly_url: string | null;
  portaly_product_id: string | null;
  original_price: number | null;
  promo_ends_at: string | null;  // ISO 8601 format
  duration_formatted: string | null;
  status: 'draft' | 'preorder' | 'selling';
}
```

### User (for settings)
```typescript
interface UserProfile {
  id: number;
  email: string;
  nickname: string | null;
  real_name: string | null;
  phone: string | null;
  birth_date: string | null; // YYYY-MM-DD format
}
```

### Purchase (for order history)
```typescript
interface PurchaseRecord {
  id: number;
  course_name: string;
  amount: number;
  currency: string;
  status: 'paid' | 'refunded';
  created_at: string; // ISO 8601
}
```

### My Course (for learning page)
```typescript
interface MyCourse {
  id: number;
  name: string;
  thumbnail: string | null;
  instructor_name: string;
  progress_percent: number;
  purchased_at: string; // ISO 8601
}
```

---

## Flash Messages

使用 Inertia.js shared data 傳遞 flash messages：

```php
// HandleInertiaRequests middleware
'flash' => [
    'success' => fn () => $request->session()->get('success'),
    'error' => fn () => $request->session()->get('error'),
],
```

### Message Examples
- 驗證碼已發送: `驗證碼已發送至您的信箱`
- 登入成功: `登入成功`
- 登出成功: `您已登出`
- 資料更新成功: `資料已更新`
- 驗證碼錯誤: `驗證碼錯誤，請重新輸入`
- 驗證碼過期: `驗證碼已過期，請重新發送`
- 帳號鎖定: `嘗試次數過多，請 15 分鐘後再試`
- Email 發送失敗: `驗證碼發送失敗，請稍後重試`
