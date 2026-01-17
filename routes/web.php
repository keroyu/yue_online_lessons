<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Member\LearningController;
use App\Http\Controllers\Member\SettingsController;
use App\Http\Controllers\Admin\DashboardController;
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
});
