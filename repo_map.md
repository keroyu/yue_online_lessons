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
- app/Http/Controllers/CourseController.php
- app/Http/Controllers/HomeController.php
- app/Http/Controllers/Member/ClassroomController.php
- app/Http/Controllers/Member/LearningController.php
- app/Models/Chapter.php
- app/Models/Course.php
- app/Models/CourseImage.php
- app/Models/Lesson.php
- app/Models/LessonProgress.php
- app/Models/Purchase.php
- resources/js/Components/Admin/ImageGalleryModal.vue
- resources/js/Pages/Admin/
- resources/js/Pages/Admin/Courses/Gallery.vue
- resources/js/Pages/Course/

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
- app/Models/LessonProgress.php
- app/Models/Purchase.php
- app/Models/User.php
- resources/js/Pages/Admin/Member/

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
