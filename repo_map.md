# Repository Map

Online lesson platform built with Laravel 12 + Inertia.js + Vue 3.
Read this file first to identify the relevant module, then search `specs/spec_index.json` to find the target spec section.

---

## Course Platform (001)
purpose: homepage, course listing, purchase flow (Portaly/PayUni/free), webhook processing, email login
specs: specs/001-course-platform-mvp/

main_files:
- app/Http/Controllers/Auth/LoginController.php
- app/Http/Controllers/CourseController.php
- app/Http/Controllers/HomeController.php
- app/Http/Controllers/Member/ClassroomController.php
- app/Http/Controllers/Member/SettingsController.php
- app/Http/Controllers/Payment/PayuniController.php
- app/Http/Controllers/Purchase/FreePurchaseController.php
- app/Http/Controllers/Webhook/PortalyController.php
- app/Http/Middleware/HandleInertiaRequests.php
- app/Models/Course.php
- app/Models/Purchase.php
- app/Models/User.php
- app/Models/VerificationCode.php
- app/Services/PayuniService.php
- config/services.php
- resources/js/Pages/Auth/
- resources/js/Pages/Auth/Login.vue
- resources/js/Pages/Course/
- resources/js/Pages/Course/Show.vue
- resources/js/Pages/Home.vue
- resources/js/Pages/Member/
- routes/api.php
- routes/web.php

related_specs:
- specs/001-course-platform-mvp/spec.md
- specs/001-course-platform-mvp/data-model.md
- specs/001-course-platform-mvp/contracts/routes.md

---

## Classroom & Admin (002)
purpose: member classroom with video playback, admin CRUD for courses/chapters/lessons, email notifications, preview
specs: specs/002-classroom-admin/

main_files:
- app/Http/Controllers/Admin/ChapterController.php
- app/Http/Controllers/Admin/CourseController.php
- app/Http/Controllers/Admin/CourseImageController.php
- app/Http/Controllers/Admin/DashboardController.php
- app/Http/Controllers/Admin/LessonController.php
- app/Http/Controllers/CheckoutController.php
- app/Http/Controllers/CourseController.php
- app/Http/Controllers/HomeController.php
- app/Http/Controllers/Member/ClassroomController.php
- app/Http/Controllers/Member/LearningController.php
- app/Models/Chapter.php
- app/Models/Course.php
- app/Models/CourseImage.php
- app/Models/Lesson.php
- app/Models/LessonProgress.php
- app/Models/Order.php
- app/Models/Purchase.php
- app/Services/CheckoutService.php
- database/migrations/2026_05_08_000001_add_utm_to_orders_table.php
- resources/js/Components/Admin/ImageGalleryModal.vue
- resources/js/Components/Classroom/VideoPlayer.vue
- resources/js/Pages/Admin/
- resources/js/Pages/Admin/Courses/Gallery.vue
- resources/js/Pages/Admin/Courses/Index.vue
- resources/js/Pages/Admin/Courses/Traffic.vue
- resources/js/Pages/Course/
- resources/js/Pages/Member/Classroom.vue
- routes/web.php

related_specs:
- specs/002-classroom-admin/spec.md
- specs/002-classroom-admin/data-model.md
- specs/002-classroom-admin/contracts/routes.md

---

## Member Management (003)
purpose: admin list/search/edit members, view course ownership and progress, batch email, gift course
specs: specs/003-member-management/

main_files:
- app/Http/Controllers/Admin/MemberController.php
- app/Models/AssignmentCompletion.php
- app/Models/LessonProgress.php
- app/Models/Purchase.php
- app/Models/User.php
- resources/js/Components/ImportMembersModal.vue
- resources/js/Pages/Admin/Member/
- resources/js/Pages/Admin/Members/Index.vue

related_specs:
- specs/003-member-management/spec.md
- specs/003-member-management/data-model.md
- specs/003-member-management/contracts/api.md

---

## Homepage Enhancement (004)
purpose: blog article display via RSS feed, social media links section, RWD
specs: specs/004-homepage-enhancement/

main_files:
- app/Http/Controllers/HomeController.php
- resources/js/Pages/Home.vue

related_specs:
- specs/004-homepage-enhancement/spec.md
- specs/004-homepage-enhancement/contracts/inertia-props.md

---

## Homepage Admin Settings (007)
purpose: admin page to manage hero unit (banner image, title, description, CTA button), SNS social links CRUD, blog RSS URL; DB-backed via site_settings + social_links tables
specs: specs/007-homepage-admin-settings/

main_files:
- app/Http/Controllers/Admin/HomepageSettingController.php
- app/Http/Controllers/Admin/SocialLinkController.php
- app/Http/Controllers/HomeController.php
- app/Http/Requests/Admin/StoreSocialLinkRequest.php
- app/Http/Requests/Admin/UpdateHomepageSettingRequest.php
- app/Http/Requests/Admin/UpdateSocialLinkRequest.php
- app/Models/SiteSetting.php
- app/Models/SocialLink.php
- app/Services/BlogRssService.php
- resources/js/Components/BlogArticles.vue
- resources/js/Components/SocialLinks.vue
- resources/js/Layouts/AdminLayout.vue
- resources/js/Pages/Admin/HomepageSettings/Edit.vue
- resources/js/Pages/Home.vue

related_specs:
- specs/007-homepage-admin-settings/spec.md
- specs/007-homepage-admin-settings/data-model.md
- specs/007-homepage-admin-settings/contracts/inertia-props.md

---

## High Ticket Booking (008)
purpose: 客製服務課程類別（隱藏價格模式）、銷售頁預約表單（非同步送出 + inline 成功提示）、確認 Email（DB 模板驅動）、後台 Email 模板 CRUD（Markdown 編輯器 + 變數插入）
specs: specs/008-high-ticket-booking/

main_files:
- app/Http/Controllers/Admin/CourseController.php
- app/Http/Controllers/Admin/EmailTemplateController.php
- app/Http/Controllers/Admin/HighTicketLeadController.php
- app/Http/Controllers/CourseController.php
- app/Http/Controllers/HighTicketBookingController.php
- app/Http/Requests/Admin/EmailTemplateRequest.php
- app/Http/Requests/Admin/StoreCourseRequest.php
- app/Http/Requests/Admin/UpdateCourseRequest.php
- app/Jobs/NotifyHighTicketSlotJob.php
- app/Jobs/SubscribeDripLeadJob.php
- app/Mail/HighTicketBookingMail.php
- app/Models/Course.php
- app/Models/EmailTemplate.php
- app/Models/HighTicketLead.php
- app/Services/HighTicketBookingService.php
- app/Services/HighTicketLeadService.php
- database/migrations/2026_04_10_000001_create_high_ticket_leads_table.php
- database/seeders/EmailTemplateSeeder.php
- resources/js/Components/Admin/CourseForm.vue
- resources/js/Pages/Admin/EmailTemplates/Edit.vue
- resources/js/Pages/Admin/EmailTemplates/Index.vue
- resources/js/Pages/Admin/HighTicketLeads/Index.vue
- resources/js/Pages/Course/Show.vue
- resources/views/emails/high-ticket-booking.blade.php
- routes/web.php

related_specs:
- specs/008-high-ticket-booking/spec.md
- specs/008-high-ticket-booking/data-model.md
- specs/008-high-ticket-booking/contracts/api.md

---

## Drip Email System (005)
purpose: free/paid subscription to drip courses, auto email sequences after purchase, admin configuration, tracking
specs: specs/005-drip-email/

main_files:
- app/Http/Controllers/Admin/CourseController.php
- app/Http/Controllers/Admin/LessonController.php
- app/Http/Controllers/Admin/MemberController.php
- app/Http/Controllers/DripSubscriptionController.php
- app/Http/Controllers/DripTrackingController.php
- app/Http/Controllers/Member/ClassroomController.php
- app/Http/Controllers/Webhook/PortalyController.php
- app/Models/Course.php
- app/Models/DripConversionTarget.php
- app/Models/DripEmailEvent.php
- app/Models/DripSubscription.php
- app/Models/Lesson.php
- app/Models/Purchase.php
- resources/js/Pages/Drip/
- resources/views/emails/drip-lesson.blade.php

related_specs:
- specs/005-drip-email/spec.md
- specs/005-drip-email/data-model.md
- specs/005-drip-email/contracts/api.md

## Cart & Checkout (009)
purpose: 購物車結帳系統（guest + auth）、PayUni UPP + NewebPay MPG、訂單快照（orders/order_items/purchases）、金流憑證後台管理
specs: specs/009-cart-checkout/

main_files:
- app/Http/Controllers/CartController.php
- app/Http/Controllers/CheckoutController.php
- app/Http/Controllers/Payment/NewebpayController.php
- app/Http/Controllers/Payment/PayuniController.php
- app/Http/Controllers/Payment/SuccessController.php
- app/Http/Controllers/Admin/SettingsController.php
- app/Http/Requests/AddToCartRequest.php
- app/Http/Requests/CheckoutRequest.php
- app/Models/CartItem.php
- app/Models/Order.php
- app/Models/OrderItem.php
- app/Models/Course.php
- app/Models/Purchase.php
- app/Services/CartService.php
- app/Services/CheckoutService.php
- app/Services/NewebpayService.php
- app/Services/PayuniService.php
- resources/js/composables/useCart.js
- resources/js/Pages/Cart/Index.vue
- resources/js/Pages/Checkout/Index.vue
- resources/js/Pages/Payment/Success.vue
- resources/js/Pages/Admin/Settings/Payment.vue
- resources/js/Components/Layout/Navigation.vue
- resources/js/Pages/Course/Show.vue

related_specs:
- specs/009-cart-checkout/spec.md
- specs/009-cart-checkout/data-model.md
- specs/009-cart-checkout/contracts/api.md

---

## Transaction Management (006)
purpose: admin transaction list with search/filter/pagination, transaction detail, manual create (gift/system_assigned), refund marking, batch CSV export
specs: specs/006-transactions-management/

main_files:
- app/Http/Controllers/Admin/TransactionController.php
- app/Http/Requests/Admin/StoreTransactionRequest.php
- app/Services/TransactionService.php
- resources/js/Components/Admin/TransactionRefundModal.vue
- resources/js/Pages/Admin/Transactions/Index.vue
- resources/js/Pages/Admin/Transactions/Show.vue

related_specs:
- specs/006-transactions-management/spec.md
- specs/006-transactions-management/data-model.md
- specs/006-transactions-management/contracts/transactions.md

---

## Lesson Homework & Grading (010)
purpose: student homework submission (2-level thread), admin grading dashboard, mark-complete with +100 points, notification bell
specs: specs/010-lesson-homework/

main_files:
- app/Http/Controllers/Admin/HomeworkController.php
- app/Http/Controllers/Member/AssignmentCommentController.php
- app/Http/Requests/Member/StoreCommentRequest.php
- app/Services/AssignmentService.php
- resources/js/Components/Classroom/AssignmentSection.vue
- resources/js/Pages/Admin/Homework/Index.vue

related_specs:
- specs/010-lesson-homework/spec.md
- specs/010-lesson-homework/data-model.md
- specs/010-lesson-homework/contracts/routes.md

---

## Discount Coupon (011)
purpose: 折扣碼系統（fixed 固定折抵 / ratio 折數），後台 CRUD + 啟用停用 + 軟刪除 + 成效統計（7/30/60/90/全部）；前台購物車與結帳頁套用（含「直接購買」流程）、IP 失敗節流、銷售頁 ?coupon= 自動帶入；結帳折後金額建單、付款確認後才累計使用次數；輪換折扣碼（CouponChain）自動補碼與 {alias} 佔位符展開
specs: specs/011-discount-coupon/

main_files:
- app/Http/Controllers/CouponController.php
- app/Http/Controllers/Admin/CouponController.php
- app/Http/Controllers/Admin/CouponChainController.php
- app/Http/Requests/Admin/StoreCouponRequest.php
- app/Http/Requests/Admin/UpdateCouponRequest.php
- app/Http/Requests/Admin/StoreCouponChainRequest.php
- app/Http/Requests/Admin/UpdateCouponChainRequest.php
- app/Models/CouponCode.php
- app/Models/CouponChain.php
- app/Services/CouponService.php
- app/Services/CouponChainService.php
- app/Services/CheckoutService.php
- app/Http/Controllers/CheckoutController.php
- app/Http/Controllers/CartController.php
- app/Http/Controllers/CourseController.php
- resources/js/Components/Cart/CouponInput.vue
- resources/js/Components/Admin/CouponForm.vue
- resources/js/Pages/Admin/Coupons/Index.vue
- resources/js/Pages/Admin/Coupons/Create.vue
- resources/js/Pages/Admin/Coupons/Edit.vue
- resources/js/Pages/Admin/Coupons/Show.vue
- resources/js/Pages/Admin/CouponChains/Index.vue
- resources/js/Pages/Admin/CouponChains/Create.vue
- resources/js/Pages/Admin/CouponChains/Edit.vue
- resources/js/Pages/Admin/CouponChains/Show.vue
- resources/js/Pages/Cart/Index.vue
- resources/js/Pages/Checkout/Index.vue

related_specs:
- specs/011-discount-coupon/spec.md
- specs/011-discount-coupon/plan.md
- specs/011-discount-coupon/data-model.md
- specs/011-discount-coupon/contracts/api.md

## Points System (012)
purpose: 積分帳本（point_transactions 為單一真相來源，users.points 為已成熟可用餘額快取，PointService 唯一寫入點）；用積分兌換課程（條件式扣點防超扣）；推薦碼回饋（結帳驗證推薦碼、付款確認後發放實付比例回饋、14 天成熟、跨門檻自動啟用推薦資格）；會員積分中心；後台積分參數設定、推薦成效統計、派發積分與帳本檢視；退款作廢未成熟回饋（14 天窗口）、每日成熟批次與對帳排程（points:mature / points:reconcile）
specs: specs/012-points-system/

main_files:
- app/Console/Commands/MaturePoints.php
- app/Http/Controllers/Admin/CourseController.php
- app/Http/Controllers/Admin/MemberController.php
- app/Http/Controllers/Admin/ReferralStatsController.php
- app/Http/Controllers/Admin/SettingsController.php
- app/Http/Controllers/CheckoutController.php
- app/Http/Controllers/Member/PointController.php
- app/Http/Controllers/RedemptionController.php
- app/Http/Controllers/ReferralController.php
- app/Http/Requests/Admin/GrantPointsRequest.php
- app/Http/Requests/CheckoutRequest.php
- app/Http/Requests/RedeemCourseRequest.php
- app/Http/Requests/ValidateReferralRequest.php
- app/Services/CheckoutService.php
- app/Services/PointService.php
- app/Services/RedemptionService.php
- app/Services/ReferralService.php
- resources/js/Components/Admin/CourseForm.vue
- resources/js/Components/Cart/ReferralInput.vue
- resources/js/Components/Course/RedeemButton.vue
- resources/js/Components/MemberDetailModal.vue
- resources/js/Pages/Admin/Referrals/Index.vue
- resources/js/Pages/Admin/Settings/Points.vue
- resources/js/Pages/Checkout/Index.vue
- resources/js/Pages/Course/Show.vue
- resources/js/Pages/Member/Points.vue

related_specs:
- specs/012-points-system/spec.md
- specs/012-points-system/plan.md
- specs/012-points-system/data-model.md
- specs/012-points-system/research.md
- specs/012-points-system/contracts/api.md
