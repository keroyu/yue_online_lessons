# Routes Contract: 上課頁面與管理員後臺

**Branch**: `002-classroom-admin` | **Date**: 2026-01-17
**Updated**: 2026-01-30 - 新增 is_visible 欄位支援課程顯示/隱藏設定

## Overview

All routes use Inertia.js for page rendering. API-style routes return JSON for AJAX operations (sorting, progress tracking).

## Route Groups

### Public Routes (No Auth)

| Method | URI | Name | Controller@Action | Description |
|--------|-----|------|-------------------|-------------|
| GET | `/` | home | HomeController@index | 首頁（現有） |
| GET | `/course/{course}` | course.show | CourseController@show | 課程販售頁（現有，需擴充顯示 description_html） |

---

### Member Routes (Auth Required)

**Middleware**: `auth`

| Method | URI | Name | Controller@Action | Description |
|--------|-----|------|-------------------|-------------|
| GET | `/member/learning` | member.learning | Member\LearningController@index | 我的課程（現有） |
| GET | `/member/classroom/{course}` | member.classroom | Member\ClassroomController@show | 上課頁面 |
| POST | `/member/classroom/{course}/progress/{lesson}` | member.progress.store | Member\ClassroomController@markComplete | 標記小節完成 |
| DELETE | `/member/classroom/{course}/progress/{lesson}` | member.progress.destroy | Member\ClassroomController@markIncomplete | 標記小節未完成 |

#### Classroom Page Data

```php
// GET /member/classroom/{course}
// Returns Inertia page with:
[
    'course' => [
        'id' => 1,
        'name' => '課程名稱',
        'chapters' => [
            [
                'id' => 1,
                'title' => '第一章',
                'lessons' => [
                    [
                        'id' => 1,
                        'title' => '1.1 小節標題',
                        'duration_formatted' => '3:50',
                        'has_video' => true,
                        'is_completed' => false
                    ]
                ]
            ]
        ],
        'standalone_lessons' => [ /* lessons without chapter */ ]
    ],
    'current_lesson' => [ /* first uncompleted or first lesson */ ]
]
```

---

### Admin Routes (Admin Auth Required)

**Middleware**: `auth`, `admin`
**Prefix**: `/admin`

#### Dashboard

| Method | URI | Name | Controller@Action | Description |
|--------|-----|------|-------------------|-------------|
| GET | `/admin` | admin.dashboard | Admin\DashboardController@index | 後臺首頁 |

#### Course Management

| Method | URI | Name | Controller@Action | Description |
|--------|-----|------|-------------------|-------------|
| GET | `/admin/courses` | admin.courses.index | Admin\CourseController@index | 課程列表 |
| GET | `/admin/courses/create` | admin.courses.create | Admin\CourseController@create | 新增課程表單 |
| POST | `/admin/courses` | admin.courses.store | Admin\CourseController@store | 儲存新課程 |
| GET | `/admin/courses/{course}/edit` | admin.courses.edit | Admin\CourseController@edit | 編輯課程表單 |
| PUT | `/admin/courses/{course}` | admin.courses.update | Admin\CourseController@update | 更新課程 |
| DELETE | `/admin/courses/{course}` | admin.courses.destroy | Admin\CourseController@destroy | 刪除課程 |
| POST | `/admin/courses/{course}/publish` | admin.courses.publish | Admin\CourseController@publish | 發佈課程 |
| POST | `/admin/courses/{course}/unpublish` | admin.courses.unpublish | Admin\CourseController@unpublish | 下架為草稿 |

#### Chapter Management

| Method | URI | Name | Controller@Action | Description |
|--------|-----|------|-------------------|-------------|
| GET | `/admin/courses/{course}/chapters` | admin.chapters.index | Admin\ChapterController@index | 章節編輯頁 |
| POST | `/admin/courses/{course}/chapters` | admin.chapters.store | Admin\ChapterController@store | 新增章 |
| PUT | `/admin/chapters/{chapter}` | admin.chapters.update | Admin\ChapterController@update | 更新章 |
| DELETE | `/admin/chapters/{chapter}` | admin.chapters.destroy | Admin\ChapterController@destroy | 刪除章 |
| POST | `/admin/courses/{course}/chapters/reorder` | admin.chapters.reorder | Admin\ChapterController@reorder | 章排序 |

#### Lesson Management

| Method | URI | Name | Controller@Action | Description |
|--------|-----|------|-------------------|-------------|
| POST | `/admin/courses/{course}/lessons` | admin.lessons.store | Admin\LessonController@store | 新增小節 |
| PUT | `/admin/lessons/{lesson}` | admin.lessons.update | Admin\LessonController@update | 更新小節 |
| DELETE | `/admin/lessons/{lesson}` | admin.lessons.destroy | Admin\LessonController@destroy | 刪除小節 |
| POST | `/admin/courses/{course}/lessons/reorder` | admin.lessons.reorder | Admin\LessonController@reorder | 小節排序 |

#### Course Images (Gallery)

| Method | URI | Name | Controller@Action | Description |
|--------|-----|------|-------------------|-------------|
| GET | `/admin/courses/{course}/images` | admin.images.index | Admin\CourseImageController@index | 相簿頁面 |
| POST | `/admin/courses/{course}/images` | admin.images.store | Admin\CourseImageController@store | 上傳圖片 |
| DELETE | `/admin/images/{image}` | admin.images.destroy | Admin\CourseImageController@destroy | 刪除圖片 |

---

## Request/Response Formats

### Store Course Request

```php
// POST /admin/courses
[
    'name' => 'required|string|max:255',
    'tagline' => 'required|string|max:255',
    'description' => 'required|string',
    'description_html' => 'nullable|string',
    'price' => 'required|numeric|min:0',                    // 優惠價（實際售價）
    'original_price' => 'nullable|numeric|min:0',           // 原價（2026-01-17 新增）
    'promo_ends_at' => 'nullable|date|after:now',           // 優惠到期時間（2026-01-17 新增）
    'thumbnail' => 'nullable|image|max:10240',
    'instructor_name' => 'required|string|max:100',
    'type' => 'required|in:lecture,mini,full',
    'duration_minutes' => 'nullable|integer|min:0',         // 時間總長（分鐘）
    'sale_at' => 'nullable|date|after:now',
    'portaly_product_id' => 'nullable|string|max:100',      // 前端產生完整 URL
    'is_visible' => 'nullable|boolean',                     // 是否顯示在首頁（2026-01-30 新增）
]
```

**課程顯示/隱藏說明（2026-01-30 新增）**：
- `is_visible` = true（預設）：課程顯示在首頁課程列表
- `is_visible` = false：課程不顯示在首頁，但仍可透過直接 URL 存取和購買
- 適用於私人課程、限定會員優惠、測試課程

**定價模式說明（2026-01-17 新增）**：
- `price` = 優惠價（必填，實際售價）
- `original_price` = 原價（選填）
- `promo_ends_at` = 優惠到期時間（選填，預設建立後 30 天）
- 驗證：`original_price` 應大於 `price`，否則顯示警告

**Portaly 整合說明**：
- 只儲存 `portaly_product_id`（如 `LaHt56zWV8VlHbMnXbvQ`）
- 前端動態產生購買連結：`https://portaly.cc/kyontw/product/{portaly_product_id}`
- `portaly_url` 欄位已移除（2026-01-17 更新）
- **注意**：Portaly 實際售價需管理員手動同步

### Store Chapter Request

```php
// POST /admin/courses/{course}/chapters
[
    'title' => 'required|string|max:255',
]
```

### Store Lesson Request

```php
// POST /admin/courses/{course}/lessons
[
    'chapter_id' => 'nullable|exists:chapters,id',
    'title' => 'required|string|max:255',
    'video_url' => 'nullable|url|max:500',
    'html_content' => 'nullable|string',
    'duration_seconds' => 'nullable|integer|min:0',
]
```

### Reorder Request

```php
// POST /admin/courses/{course}/chapters/reorder
// POST /admin/courses/{course}/lessons/reorder
[
    'items' => 'required|array',
    'items.*.id' => 'required|integer',
    'items.*.sort_order' => 'required|integer|min:0',
]
```

### Upload Image Request

```php
// POST /admin/courses/{course}/images
[
    'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
]

// Response includes auto-detected dimensions (2026-01-17 新增)
{
    'id' => 1,
    'course_id' => 1,
    'path' => 'course-images/1/abc123.jpg',
    'filename' => 'original-name.jpg',
    'url' => '/storage/course-images/1/abc123.jpg',
    'width' => 1200,   // Auto-detected
    'height' => 800,   // Auto-detected
    'created_at' => '2026-01-17T10:00:00Z'
}
```

**圖片尺寸用途（2026-01-17 新增）**：
- 上傳時自動偵測並儲存原始寬高
- 相簿 Modal 選擇圖片時，可根據原始比例計算自適應尺寸

---

## Error Responses

### 403 Forbidden (Classroom Access)
```json
{
    "message": "您尚未購買此課程",
    "redirect": "/course/{course_id}"
}
```

### 403 Forbidden (Admin Access)
```json
{
    "message": "您沒有權限存取此頁面",
    "redirect": "/"
}
```

### 404 Not Found
```json
{
    "message": "找不到該資源"
}
```

### 422 Validation Error
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

### 409 Conflict (Delete Course with Purchases)
```json
{
    "message": "此課程已有學員購買，無法刪除"
}
```

---

## Middleware Registration

```php
// bootstrap/app.php or app/Http/Kernel.php
'admin' => \App\Http\Middleware\AdminMiddleware::class,
```

---

## Route File Structure

```php
// routes/web.php

// Public
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/course/{course}', [CourseController::class, 'show'])->name('course.show');

// Member (requires auth)
Route::middleware('auth')->prefix('member')->name('member.')->group(function () {
    Route::get('/learning', [LearningController::class, 'index'])->name('learning');
    Route::get('/classroom/{course}', [ClassroomController::class, 'show'])->name('classroom');
    Route::post('/classroom/{course}/progress/{lesson}', [ClassroomController::class, 'markComplete'])->name('progress.store');
    Route::delete('/classroom/{course}/progress/{lesson}', [ClassroomController::class, 'markIncomplete'])->name('progress.destroy');
});

// Admin (requires auth + admin)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Courses
    Route::resource('courses', CourseController::class);
    Route::post('/courses/{course}/publish', [CourseController::class, 'publish'])->name('courses.publish');
    Route::post('/courses/{course}/unpublish', [CourseController::class, 'unpublish'])->name('courses.unpublish');

    // Chapters
    Route::get('/courses/{course}/chapters', [ChapterController::class, 'index'])->name('chapters.index');
    Route::post('/courses/{course}/chapters', [ChapterController::class, 'store'])->name('chapters.store');
    Route::put('/chapters/{chapter}', [ChapterController::class, 'update'])->name('chapters.update');
    Route::delete('/chapters/{chapter}', [ChapterController::class, 'destroy'])->name('chapters.destroy');
    Route::post('/courses/{course}/chapters/reorder', [ChapterController::class, 'reorder'])->name('chapters.reorder');

    // Lessons
    Route::post('/courses/{course}/lessons', [LessonController::class, 'store'])->name('lessons.store');
    Route::put('/lessons/{lesson}', [LessonController::class, 'update'])->name('lessons.update');
    Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');
    Route::post('/courses/{course}/lessons/reorder', [LessonController::class, 'reorder'])->name('lessons.reorder');

    // Course Images
    Route::get('/courses/{course}/images', [CourseImageController::class, 'index'])->name('images.index');
    Route::post('/courses/{course}/images', [CourseImageController::class, 'store'])->name('images.store');
    Route::delete('/images/{image}', [CourseImageController::class, 'destroy'])->name('images.destroy');
});
```
