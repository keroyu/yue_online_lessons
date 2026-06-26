<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Member\LearningController;
use App\Http\Controllers\Member\SettingsController;
use App\Http\Controllers\Member\ClassroomController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\ChapterController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\CourseImageController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\HighTicketLeadController;
use App\Http\Controllers\Admin\HomepageSettingController;
use App\Http\Controllers\Admin\HomeworkController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\SocialLinkController;
use App\Http\Controllers\Member\AssignmentCommentController;
use App\Http\Controllers\Member\NotificationController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\CouponChainController;
use App\Http\Controllers\DripSubscriptionController;
use App\Http\Controllers\DripTrackingController;
use App\Http\Controllers\HighTicketBookingController;
use App\Http\Controllers\Payment\NewebpayController;
use App\Http\Controllers\Payment\PayuniController;
use App\Http\Controllers\Payment\SuccessController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::get('/payment/success', [SuccessController::class, 'show'])->name('payment.success');
Route::get('/course/{course}', [CourseController::class, 'show'])->name('course.show');
Route::post('/course/{course}/book', [HighTicketBookingController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('course.book');
Route::get('/course/{course}/preview', [ClassroomController::class, 'preview'])->name('course.preview');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Cart & Checkout API — must live in web.php (api middleware group has no StartSession;
// checkout/initiate reads session('traffic_source') for UTM tracking)
Route::prefix('api')->name('api.')->group(function () {
    // Cart (auth required)
    Route::middleware('auth')->group(function () {
        Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
        // whereNumber prevents this wildcard from capturing literal segments like /cart/coupon
        Route::delete('/cart/{courseId}', [CartController::class, 'remove'])->whereNumber('courseId')->name('cart.remove');
        Route::post('/cart/merge', [CartController::class, 'merge'])->name('cart.merge');
    });

    // Coupon (public — guest checkout supported)
    Route::post('/cart/apply-coupon', [CouponController::class, 'apply'])->name('cart.apply-coupon');
    Route::delete('/cart/coupon', [CouponController::class, 'clear'])->name('cart.clear-coupon');

    // Checkout (public — guest checkout supported)
    Route::post('/checkout/check-email', [CheckoutController::class, 'checkEmail'])->name('checkout.check-email');
    Route::post('/checkout/initiate', [CheckoutController::class, 'initiate'])->name('checkout.initiate');
});

// PayUni ReturnURL — browser redirect after payment (needs web middleware for auth/session)
Route::post('/payment/payuni/return', [PayuniController::class, 'return'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
    ->name('payuni.return');

// NewebPay ReturnURL — browser redirect after payment (CSRF excluded; NotifyURL is in api.php)
Route::post('/payment/newebpay/return', [NewebpayController::class, 'return'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
    ->name('newebpay.return');

// Drip subscription routes (public)
Route::prefix('drip')->name('drip.')->group(function () {
    Route::post('/subscribe', [DripSubscriptionController::class, 'subscribe'])->name('subscribe');
    Route::post('/verify', [DripSubscriptionController::class, 'verify'])->name('verify');
    Route::get('/unsubscribe/{token}', [DripSubscriptionController::class, 'showUnsubscribe'])->name('unsubscribe.show');
    Route::post('/unsubscribe/{token}', [DripSubscriptionController::class, 'unsubscribe'])->name('unsubscribe');
});

// Drip email tracking routes
// open: public (email pixel has no session)
Route::get('/drip/track/open', [DripTrackingController::class, 'open'])->name('drip.track.open');
// click: requires auth (classroom tracking uses auth session to identify subscriber)
Route::middleware('auth')->group(function () {
    Route::get('/drip/track/click', [DripTrackingController::class, 'click'])->name('drip.track.click');
});

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

    // Classroom
    Route::get('/classroom/{course}', [ClassroomController::class, 'show'])->name('classroom');
    Route::post('/classroom/{course}/progress/{lesson}', [ClassroomController::class, 'markComplete'])->name('progress.store');
    Route::delete('/classroom/{course}/progress/{lesson}', [ClassroomController::class, 'markIncomplete'])->name('progress.destroy');

    // Drip subscription (logged-in member one-click subscribe)
    Route::post('/drip/subscribe/{course}', [DripSubscriptionController::class, 'memberSubscribe'])->name('drip.subscribe');

    // Assignment comments
    Route::post('/classroom/{course}/assignment/{assignment}/comments', [AssignmentCommentController::class, 'store'])->name('comments.store');
    Route::put('/classroom/{course}/assignment/{assignment}/comments/{comment}', [AssignmentCommentController::class, 'update'])->name('comments.update');
    Route::delete('/classroom/{course}/assignment/{assignment}/comments/{comment}', [AssignmentCommentController::class, 'destroy'])->name('comments.destroy');

    // Notifications
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
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
    Route::post('/courses/{course}/images/batch', [CourseImageController::class, 'batchStore'])->name('images.batch-store');
    Route::delete('/images/batch', [CourseImageController::class, 'batchDestroy'])->name('images.batch-destroy');
    Route::delete('/images/{image}', [CourseImageController::class, 'destroy'])->name('images.destroy');

    // Drip subscribers
    Route::get('/courses/{course}/subscribers', [AdminCourseController::class, 'subscribers'])->name('courses.subscribers');

    // Traffic source analytics
    Route::get('/courses/{course}/traffic', [AdminCourseController::class, 'traffic'])->name('courses.traffic');
    Route::get('/courses/{course}/traffic/export', [AdminCourseController::class, 'trafficExport'])->name('courses.traffic.export');

    // Members
    Route::get('/members/count', [MemberController::class, 'count'])->name('members.count');
    Route::get('/members/export', [MemberController::class, 'exportCsv'])->name('members.export');
    Route::post('/members/import', [MemberController::class, 'importEmails'])->name('members.import');
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::patch('/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');
    Route::post('/members/batch-email', [MemberController::class, 'sendBatchEmail'])
        ->middleware('throttle:10,1')
        ->name('members.batch-email');
    Route::post('/members/gift-course', [MemberController::class, 'giftCourse'])
        ->middleware('throttle:10,1')
        ->name('members.gift-course');

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::patch('/transactions/{transaction}/refund', [TransactionController::class, 'refund'])->name('transactions.refund');

    // Discount Coupons
    Route::resource('coupons', AdminCouponController::class)->except(['show']);
    Route::get('/coupons/{coupon}', [AdminCouponController::class, 'show'])->name('coupons.show');
    Route::patch('/coupons/{coupon}/toggle', [AdminCouponController::class, 'toggle'])->name('coupons.toggle');

    // Coupon Chains (rotating auto-generated coupons)
    Route::resource('coupon-chains', CouponChainController::class)->except(['show']);
    Route::get('/coupon-chains/{coupon_chain}', [CouponChainController::class, 'show'])->name('coupon-chains.show');
    Route::patch('/coupon-chains/{coupon_chain}/toggle', [CouponChainController::class, 'toggle'])->name('coupon-chains.toggle');

    // High-ticket leads
    Route::get('/high-ticket-leads', [HighTicketLeadController::class, 'index'])->name('high-ticket-leads.index');
    Route::patch('/high-ticket-leads/{lead}/status', [HighTicketLeadController::class, 'updateStatus'])->name('high-ticket-leads.update-status');
    Route::post('/high-ticket-leads/notify-slot', [HighTicketLeadController::class, 'notifySlot'])->name('high-ticket-leads.notify-slot');
    Route::post('/high-ticket-leads/subscribe-drip', [HighTicketLeadController::class, 'subscribeDrip'])->name('high-ticket-leads.subscribe-drip');
    Route::post('/high-ticket-leads/batch-email', [HighTicketLeadController::class, 'batchEmail'])->name('high-ticket-leads.batch-email');
    Route::post('/high-ticket-leads/{lead}/convert', [HighTicketLeadController::class, 'convert'])->name('high-ticket-leads.convert');

    // Email templates
    Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
    Route::get('/email-templates/{template}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
    Route::put('/email-templates/{template}', [EmailTemplateController::class, 'update'])->name('email-templates.update');

    // Homepage settings
    Route::get('/homepage', [HomepageSettingController::class, 'edit'])->name('homepage.edit');
    Route::post('/homepage', [HomepageSettingController::class, 'update'])->name('homepage.update');
    Route::delete('/homepage/banner', [HomepageSettingController::class, 'deleteBanner'])->name('homepage.banner.destroy');

    // Social links CRUD
    Route::post('/homepage/social-links', [SocialLinkController::class, 'store'])->name('social-links.store');
    Route::put('/homepage/social-links/{socialLink}', [SocialLinkController::class, 'update'])->name('social-links.update');
    Route::delete('/homepage/social-links/{socialLink}', [SocialLinkController::class, 'destroy'])->name('social-links.destroy');

    // Payment settings
    Route::get('/settings/payment', [AdminSettingsController::class, 'showPayment'])->name('settings.payment');
    Route::post('/settings/payment', [AdminSettingsController::class, 'updatePayment'])->name('settings.payment.update');

    // Homework / grading
    Route::get('/homework', [HomeworkController::class, 'index'])->name('homework.index');
    Route::post('/lessons/{lesson}/assignment', [HomeworkController::class, 'store'])->name('homework.store');
    Route::put('/homework/{assignment}', [HomeworkController::class, 'update'])->name('homework.update');
    Route::post('/homework/{assignment}/publish', [HomeworkController::class, 'publish'])->name('homework.publish');
    Route::post('/homework/{assignment}/unpublish', [HomeworkController::class, 'unpublish'])->name('homework.unpublish');
    Route::post('/homework/{assignment}/comments', [HomeworkController::class, 'storeComment'])->name('homework.comments.store');
    Route::put('/homework/{assignment}/comments/{comment}', [HomeworkController::class, 'updateComment'])->name('homework.comments.update');
    Route::delete('/homework/{assignment}/comments/{comment}', [HomeworkController::class, 'destroyComment'])->name('homework.comments.destroy');
    Route::post('/homework/{assignment}/completions/{user}', [HomeworkController::class, 'markComplete'])->name('homework.completions.store');
});
