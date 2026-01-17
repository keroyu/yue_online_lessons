<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Member\LearningController;
use App\Http\Controllers\Member\SettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\ChapterController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\CourseImageController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/course/{course}', [CourseController::class, 'show'])->name('course.show');

// Auth routes (Guest only)
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

// Member routes (Authenticated)
Route::middleware('auth')->prefix('member')->name('member.')->group(function () {
    Route::get('/learning', [LearningController::class, 'index'])->name('learning');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

// Admin routes (Admin only)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Courses
    Route::resource('courses', AdminCourseController::class);
    Route::post('/courses/{course}/publish', [AdminCourseController::class, 'publish'])->name('courses.publish');
    Route::post('/courses/{course}/unpublish', [AdminCourseController::class, 'unpublish'])->name('courses.unpublish');

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
