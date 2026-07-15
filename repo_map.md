# Repository Map

Online lesson platform built with Laravel 12 + Inertia.js + Vue 3.
Read this file first to identify the relevant module, then search `specs/spec_index.json` to find the target spec section.

Modules are **domain-based**: every code file has exactly one owner module (declared in that module's `spec.md` frontmatter `owner_files`). Cross-module interactions are declared as `touchpoints`. Old delivery-batch specs live in `specs/_archive/`.

---

## Platform Core (000)
purpose: 全站基礎設施 — 前台/後台 Layout 與導航、admin 權限 middleware、法務條款彈窗、SEO/Sitemap/Meta Pixel、site_settings 全站設定機制
specs: specs/000-platform-core/

main_files:
- app/Http/Controllers/Admin/SettingsController.php
- app/Http/Controllers/Auth/LoginController.php
- app/Http/Controllers/CheckoutController.php
- app/Http/Controllers/Purchase/FreePurchaseController.php
- app/Http/Controllers/SitemapController.php
- app/Http/Middleware/AdminMiddleware.php
- app/Http/Middleware/HandleInertiaRequests.php
- app/Http/Middleware/StaffMiddleware.php
- app/Jobs/SendMetaConversionJob.php
- app/Models/Order.php
- app/Models/SiteSetting.php
- app/Providers/AppServiceProvider.php
- app/Services/CheckoutService.php
- app/Services/HighTicketBookingService.php
- app/Services/MetaConversionsService.php
- app/Services/NewsletterService.php
- app/Services/PortalyWebhookService.php
- bootstrap/app.php
- config/services.php
- database/migrations/2026_03_25_000001_create_site_settings_table.php
- database/migrations/2026_07_11_000003_add_is_sales_consultant_to_users.php
- database/migrations/2026_07_12_000001_add_meta_click_ids_to_orders_table.php
- resources/js/Components/Layout/AppLayout.vue
- resources/js/Components/Layout/Footer.vue
- resources/js/Components/Layout/Navigation.vue
- resources/js/Components/Legal/LegalPolicyModal.vue
- resources/js/Components/Legal/PrivacyContent.vue
- resources/js/Components/Legal/PurchaseContent.vue
- resources/js/Components/Legal/TermsContent.vue
- resources/js/Layouts/AdminLayout.vue
- resources/js/Pages/Admin/Settings/Payment.vue
- resources/js/app.js
- resources/views/app.blade.php
- resources/views/sitemap.blade.php
- routes/web.php

related_specs:
- specs/000-platform-core/spec.md

---

## Auth & Account (001)
purpose: Email 驗證碼（OTP）登入/註冊、會員帳號設定（個人資料、訂單紀錄、積分與作業歷程檢視）
specs: specs/001-auth-account/

main_files:
- app/Http/Controllers/Admin/HomeworkController.php
- app/Http/Controllers/Admin/MemberController.php
- app/Http/Controllers/Auth/LoginController.php
- app/Http/Controllers/Member/SettingsController.php
- app/Http/Controllers/Member/SocialLinkController.php
- app/Http/Requests/Auth/SendVerificationCodeRequest.php
- app/Http/Requests/Auth/VerifyCodeRequest.php
- app/Http/Requests/Member/StoreUserSocialLinkRequest.php
- app/Http/Requests/Member/UpdateProfileRequest.php
- app/Mail/VerificationCodeMail.php
- app/Models/User.php
- app/Models/UserSocialLink.php
- app/Models/VerificationCode.php
- app/Services/VerificationCodeService.php
- database/migrations/0001_01_01_000000_create_users_table.php
- database/migrations/2026_01_16_000004_create_verification_codes_table.php
- database/migrations/2026_07_15_000001_create_user_social_links_table.php
- resources/js/Components/MemberDetailModal.vue
- resources/js/Components/SocialLinks.vue
- resources/js/Components/UserSocialIcons.vue
- resources/js/Components/VerificationCodeInput.vue
- resources/js/Pages/Admin/Homework/Index.vue
- resources/js/Pages/Auth/Login.vue
- resources/js/Pages/Member/Settings.vue
- resources/js/lib/socialPlatforms.js
- resources/views/emails/verification-code.blade.php
- routes/web.php
- tests/Feature/Member/UserSocialLinkTest.php

related_specs:
- specs/001-auth-account/spec.md

---

## Storefront (002)
purpose: 門市前台 — 首頁（hero/精選課程/內容分類/部落格 RSS/社群連結）、課程銷售頁、首頁後台設定、課程連結 UTM 流量追蹤
specs: specs/002-storefront/

main_files:
- app/Http/Controllers/Admin/AnalyticsController.php
- app/Http/Controllers/Admin/CourseController.php
- app/Http/Controllers/Admin/HomepageFeaturedCourseController.php
- app/Http/Controllers/Admin/HomepageSettingController.php
- app/Http/Controllers/Admin/SocialLinkController.php
- app/Http/Controllers/BlogController.php
- app/Http/Controllers/CheckoutController.php
- app/Http/Controllers/CourseController.php
- app/Http/Controllers/HomeController.php
- app/Http/Controllers/TrackController.php
- app/Http/Middleware/TrackTrafficSource.php
- app/Http/Requests/Admin/StoreFeaturedCourseRequest.php
- app/Http/Requests/Admin/StoreSocialLinkRequest.php
- app/Http/Requests/Admin/UpdateFeaturedCourseRequest.php
- app/Http/Requests/Admin/UpdateHomepageSettingRequest.php
- app/Http/Requests/Admin/UpdateSocialLinkRequest.php
- app/Models/Course.php
- app/Models/CourseDailyStat.php
- app/Models/HomepageFeaturedCourse.php
- app/Models/Order.php
- app/Models/PostCtaClick.php
- app/Models/SocialLink.php
- app/Services/BlogRssService.php
- app/Services/CheckoutService.php
- app/Services/SiteAnalyticsService.php
- app/Services/TrafficSourceService.php
- bootstrap/app.php
- database/migrations/2026_07_05_000001_create_homepage_featured_courses_table.php
- database/migrations/2026_07_11_000001_rename_monetization_label_to_business_strategy.php
- database/migrations/2026_07_12_000002_create_course_daily_stats_table.php
- database/migrations/2026_07_12_000003_create_post_cta_clicks_table.php
- database/migrations/2026_07_12_000004_add_first_touch_to_orders_table.php
- resources/js/Components/BlogArticles.vue
- resources/js/Components/Course/PriceDisplay.vue
- resources/js/Components/CourseCard.vue
- resources/js/Components/FeaturedCourses.vue
- resources/js/Components/SectionHeader.vue
- resources/js/Components/SocialLinks.vue
- resources/js/Layouts/AdminLayout.vue
- resources/js/Pages/Admin/Analytics/Index.vue
- resources/js/Pages/Admin/Courses/Traffic.vue
- resources/js/Pages/Admin/HomepageSettings/Edit.vue
- resources/js/Pages/Course/Show.vue
- resources/js/Pages/Home.vue
- resources/js/composables/useCart.js
- routes/web.php

related_specs:
- specs/002-storefront/spec.md

---

## Classroom (003)
purpose: 會員教室 — 我的課程、影片播放與進度、免費試閱、作業提交/批改/通知鈴鐺
specs: specs/003-classroom/

main_files:
- app/Http/Controllers/Admin/HomeworkController.php
- app/Http/Controllers/Member/AssignmentCommentController.php
- app/Http/Controllers/Member/ClassroomController.php
- app/Http/Controllers/Member/LearningController.php
- app/Http/Controllers/Member/NotificationController.php
- app/Http/Requests/Admin/AssignmentRequest.php
- app/Http/Requests/Member/StoreCommentRequest.php
- app/Models/Assignment.php
- app/Models/AssignmentCompletion.php
- app/Models/Comment.php
- app/Models/HomeworkNotification.php
- app/Models/LessonProgress.php
- app/Services/AssignmentService.php
- app/Services/VideoEmbedService.php
- database/migrations/2026_01_17_000004_create_lesson_progress_table.php
- database/migrations/2026_05_10_000002_create_assignments_table.php
- database/migrations/2026_05_10_000003_create_comments_table.php
- database/migrations/2026_05_10_000004_create_assignment_completions_table.php
- database/migrations/2026_05_10_000005_create_homework_notifications_table.php
- resources/js/Components/Classroom/AssignmentSection.vue
- resources/js/Components/Classroom/ChapterSidebar.vue
- resources/js/Components/Classroom/CommentThread.vue
- resources/js/Components/Classroom/HtmlContent.vue
- resources/js/Components/Classroom/LessonItem.vue
- resources/js/Components/Classroom/VideoPlayer.vue
- resources/js/Components/MyCourseCard.vue
- resources/js/Pages/Admin/Homework/Index.vue
- resources/js/Pages/Member/Classroom.vue
- resources/js/Pages/Member/ClassroomUnauthorized.vue
- resources/js/Pages/Member/Learning.vue
- resources/js/composables/useNotifications.js

related_specs:
- specs/003-classroom/spec.md

---

## Course Admin (004)
purpose: 後台課程管理 — 課程/章節/小節 CRUD、圖片庫、課程類別與上架排程、新小節通知信
specs: specs/004-course-admin/

main_files:
- app/Console/Commands/UpdateCourseStatus.php
- app/Http/Controllers/Admin/ChapterController.php
- app/Http/Controllers/Admin/CourseController.php
- app/Http/Controllers/Admin/CourseImageController.php
- app/Http/Controllers/Admin/LessonController.php
- app/Http/Requests/Admin/StoreChapterRequest.php
- app/Http/Requests/Admin/StoreCourseRequest.php
- app/Http/Requests/Admin/StoreLessonRequest.php
- app/Http/Requests/Admin/UpdateCourseRequest.php
- app/Mail/LessonAddedNotification.php
- app/Models/Chapter.php
- app/Models/Course.php
- app/Models/CourseImage.php
- app/Models/Lesson.php
- app/Policies/CoursePolicy.php
- database/migrations/2026_03_08_180036_add_seo_fields_to_courses_table.php
- database/migrations/2026_04_09_000001_add_high_ticket_fields_to_courses_table.php
- database/migrations/2026_06_30_000003_add_redeem_points_to_courses_table.php
- database/migrations/2026_07_06_000002_change_content_category_to_string_on_courses.php
- resources/js/Components/Admin/ChapterList.vue
- resources/js/Components/Admin/CourseForm.vue
- resources/js/Components/Admin/ImageGalleryModal.vue
- resources/js/Components/Admin/LessonForm.vue
- resources/js/Pages/Admin/Courses/Chapters.vue
- resources/js/Pages/Admin/Courses/Create.vue
- resources/js/Pages/Admin/Courses/Edit.vue
- resources/js/Pages/Admin/Courses/Gallery.vue
- resources/js/Pages/Admin/Courses/Index.vue
- resources/views/emails/lesson-added.blade.php
- routes/console.php

related_specs:
- specs/004-course-admin/spec.md

---

## Checkout & Payments (005)
purpose: 購物車、結帳（guest/auth）、訂單快照、PayUni UPP / NewebPay MPG / Portaly webhook、免費領取、金流憑證後台
specs: specs/005-checkout/

main_files:
- app/Http/Controllers/CartController.php
- app/Http/Controllers/CheckoutController.php
- app/Http/Controllers/Payment/NewebpayController.php
- app/Http/Controllers/Payment/PayuniController.php
- app/Http/Controllers/Payment/SuccessController.php
- app/Http/Controllers/Purchase/FreePurchaseController.php
- app/Http/Controllers/Webhook/PortalyController.php
- app/Http/Requests/AddToCartRequest.php
- app/Http/Requests/CheckoutRequest.php
- app/Models/CartItem.php
- app/Models/Order.php
- app/Models/OrderItem.php
- app/Models/Purchase.php
- app/Services/CartService.php
- app/Services/CheckoutService.php
- app/Services/NewebpayService.php
- app/Services/PayuniService.php
- app/Services/PortalyWebhookService.php
- database/migrations/2026_01_16_000002_create_purchases_table.php
- database/migrations/2026_05_06_000001_create_cart_items_table.php
- database/migrations/2026_05_06_000002_create_orders_table.php
- database/migrations/2026_05_06_000003_create_order_items_table.php
- database/migrations/2026_05_06_000005_add_order_id_to_purchases_table.php
- resources/js/Pages/Cart/Index.vue
- resources/js/Pages/Checkout/Index.vue
- resources/js/Pages/Payment/Success.vue
- resources/js/composables/useCart.js
- routes/api.php

related_specs:
- specs/005-checkout/spec.md

---

## Coupons (006)
purpose: 折扣碼系統 — 後台 CRUD/啟停/軟刪除/成效統計、輪換折扣碼鏈、前台套用與 IP 節流、?coupon= 自動帶入
specs: specs/006-coupons/

main_files:
- app/Http/Controllers/Admin/ChapterController.php
- app/Http/Controllers/Admin/CouponChainController.php
- app/Http/Controllers/Admin/CouponController.php
- app/Http/Controllers/CartController.php
- app/Http/Controllers/CheckoutController.php
- app/Http/Controllers/CouponController.php
- app/Http/Controllers/CourseController.php
- app/Http/Controllers/Member/ClassroomController.php
- app/Http/Requests/Admin/StoreCouponChainRequest.php
- app/Http/Requests/Admin/StoreCouponRequest.php
- app/Http/Requests/Admin/UpdateCouponChainRequest.php
- app/Http/Requests/Admin/UpdateCouponRequest.php
- app/Models/CouponChain.php
- app/Models/CouponCode.php
- app/Models/Order.php
- app/Services/CheckoutService.php
- app/Services/CouponChainService.php
- app/Services/CouponService.php
- database/migrations/2026_06_09_000001_create_coupon_codes_table.php
- database/migrations/2026_06_09_000002_add_discount_columns_to_orders_table.php
- database/migrations/2026_06_26_000001_create_coupon_chains_table.php
- database/migrations/2026_06_26_000002_add_chain_id_to_coupon_codes_table.php
- resources/js/Components/Admin/CouponForm.vue
- resources/js/Components/Admin/LessonForm.vue
- resources/js/Components/Cart/CouponInput.vue
- resources/js/Pages/Admin/CouponChains/Create.vue
- resources/js/Pages/Admin/CouponChains/Edit.vue
- resources/js/Pages/Admin/CouponChains/Index.vue
- resources/js/Pages/Admin/CouponChains/Show.vue
- resources/js/Pages/Admin/Coupons/Create.vue
- resources/js/Pages/Admin/Coupons/Edit.vue
- resources/js/Pages/Admin/Coupons/Index.vue
- resources/js/Pages/Admin/Coupons/Show.vue
- resources/js/Pages/Cart/Index.vue
- resources/js/Pages/Checkout/Index.vue
- routes/web.php

related_specs:
- specs/006-coupons/spec.md

---

## Points & Referral (007)
purpose: 積分帳本（point_transactions 單一真相）、積分兌換課程、推薦碼回饋（14 天成熟）、會員積分中心、後台參數/統計/派發
specs: specs/007-points-referral/

main_files:
- app/Console/Commands/MaturePoints.php
- app/Http/Controllers/Admin/MemberController.php
- app/Http/Controllers/Admin/ReferralController.php
- app/Http/Controllers/Admin/SettingsController.php
- app/Http/Controllers/CheckoutController.php
- app/Http/Controllers/Member/PointController.php
- app/Http/Controllers/RedemptionController.php
- app/Http/Controllers/ReferralController.php
- app/Http/Requests/Admin/GrantPointsRequest.php
- app/Http/Requests/RedeemCourseRequest.php
- app/Http/Requests/ValidateReferralRequest.php
- app/Models/Order.php
- app/Models/PointTransaction.php
- app/Models/SiteSetting.php
- app/Services/CheckoutService.php
- app/Services/PointService.php
- app/Services/RedemptionService.php
- app/Services/ReferralService.php
- app/Services/TransactionService.php
- database/migrations/2026_06_30_000002_add_referral_fields_to_users_table.php
- database/migrations/2026_06_30_000003_add_redeem_points_to_courses_table.php
- database/migrations/2026_06_30_000004_add_referral_fields_to_orders_table.php
- database/migrations/2026_07_11_000002_add_referral_discount_to_orders_table.php
- resources/js/Components/Admin/ReferrerDetailModal.vue
- resources/js/Components/Cart/ReferralInput.vue
- resources/js/Components/Course/RedeemButton.vue
- resources/js/Components/MemberDetailModal.vue
- resources/js/Pages/Admin/Settings/Points.vue
- resources/js/Pages/Checkout/Index.vue
- resources/js/Pages/Course/Show.vue
- resources/js/Pages/Member/Points.vue

related_specs:
- specs/007-points-referral/spec.md

---

## Members Admin (008)
purpose: 後台會員管理 — 列表/搜尋/編輯、課程持有與進度檢視、匯入匯出 CSV、批次寄信、贈課
specs: specs/008-members-admin/

main_files:
- app/Http/Controllers/Admin/MemberController.php
- app/Http/Requests/Admin/GiftCourseRequest.php
- app/Http/Requests/Admin/SendBatchEmailRequest.php
- app/Http/Requests/Admin/ToggleSalesConsultantRequest.php
- app/Http/Requests/Admin/UpdateMemberRequest.php
- app/Mail/BatchEmailMail.php
- app/Mail/CourseGiftedMail.php
- resources/js/Components/BatchEmailModal.vue
- resources/js/Components/GiftCourseModal.vue
- resources/js/Components/ImportMembersModal.vue
- resources/js/Components/MemberDetailModal.vue
- resources/js/Components/SalesConsultantModal.vue
- resources/js/Pages/Admin/Members/Index.vue
- resources/views/emails/batch-email.blade.php
- resources/views/emails/course-gifted.blade.php

related_specs:
- specs/008-members-admin/spec.md

---

## Transactions Admin (009)
purpose: 後台交易管理 — 列表/篩選、詳情、手動建立（gift/system_assigned）、退款標記、CSV 匯出、營收儀表板
specs: specs/009-transactions-admin/

main_files:
- app/Http/Controllers/Admin/DashboardController.php
- app/Http/Controllers/Admin/TransactionController.php
- app/Http/Requests/Admin/StoreTransactionRequest.php
- app/Services/TransactionService.php
- resources/js/Components/Admin/RevenueChart.vue
- resources/js/Components/Admin/TransactionRefundModal.vue
- resources/js/Pages/Admin/Dashboard.vue
- resources/js/Pages/Admin/Transactions/Index.vue
- resources/js/Pages/Admin/Transactions/Show.vue

related_specs:
- specs/009-transactions-admin/spec.md

---

## Drip Email (010)
purpose: Drip 訂閱（免費/付費）、自動序列信排程、開信/點擊追蹤、退訂、訂閱者後台
specs: specs/010-drip-email/

main_files:
- app/Console/Commands/ProcessDripEmails.php
- app/Http/Controllers/Admin/CourseController.php
- app/Http/Controllers/Admin/LessonController.php
- app/Http/Controllers/DripSubscriptionController.php
- app/Http/Controllers/DripTrackingController.php
- app/Http/Controllers/Member/ClassroomController.php
- app/Http/Controllers/Purchase/FreePurchaseController.php
- app/Http/Requests/StoreDripSubscriptionRequest.php
- app/Jobs/SendDripEmailJob.php
- app/Jobs/SubscribeDripLeadJob.php
- app/Mail/DripLessonMail.php
- app/Models/DripConversionTarget.php
- app/Models/DripEmailEvent.php
- app/Models/DripSubscription.php
- app/Services/CheckoutService.php
- app/Services/DripService.php
- app/Services/PortalyWebhookService.php
- app/Services/RedemptionService.php
- config/drip.php
- database/migrations/2026_02_16_000001_add_drip_fields_to_courses_table.php
- database/migrations/2026_02_16_000004_add_promo_fields_to_lessons_table.php
- database/migrations/2026_02_21_000001_add_reward_html_to_lessons_table.php
- database/migrations/2026_02_28_000001_create_drip_email_events_table.php
- database/migrations/2026_02_28_000002_add_promo_url_to_lessons_table.php
- database/migrations/2026_03_01_084230_add_video_access_hours_to_lessons_table.php
- resources/js/Components/Admin/CourseForm.vue
- resources/js/Components/Admin/LessonForm.vue
- resources/js/Components/Classroom/LessonPromoBlock.vue
- resources/js/Components/Classroom/VideoAccessNotice.vue
- resources/js/Components/Course/DripSubscribeForm.vue
- resources/js/Pages/Admin/Courses/Subscribers.vue
- resources/js/Pages/Course/Show.vue
- resources/js/Pages/Drip/Unsubscribe.vue
- resources/js/Pages/Member/Classroom.vue
- resources/views/emails/drip-lesson.blade.php
- routes/console.php

related_specs:
- specs/010-drip-email/spec.md

---

## High Ticket (011)
purpose: 高價課預約 — 隱藏價格銷售頁預約表單、leads 後台、Email 模板系統（Markdown + 變數）、名額通知
specs: specs/011-high-ticket/

main_files:
- app/Http/Controllers/Admin/EmailTemplateController.php
- app/Http/Controllers/Admin/HighTicketLeadController.php
- app/Http/Controllers/CourseController.php
- app/Http/Controllers/HighTicketBookingController.php
- app/Http/Requests/Admin/EmailTemplateRequest.php
- app/Jobs/NotifyHighTicketSlotJob.php
- app/Jobs/SubscribeDripLeadJob.php
- app/Mail/BatchEmailMail.php
- app/Mail/HighTicketBookingMail.php
- app/Models/EmailTemplate.php
- app/Models/HighTicketLead.php
- app/Models/Purchase.php
- app/Services/DripService.php
- app/Services/HighTicketBookingService.php
- app/Services/HighTicketLeadService.php
- database/migrations/2026_04_09_000002_create_email_templates_table.php
- database/migrations/2026_04_10_000001_create_high_ticket_leads_table.php
- database/seeders/EmailTemplateSeeder.php
- resources/js/Pages/Admin/EmailTemplates/Edit.vue
- resources/js/Pages/Admin/EmailTemplates/Index.vue
- resources/js/Pages/Admin/HighTicketLeads/Index.vue
- resources/js/Pages/Course/Show.vue
- resources/views/emails/high-ticket-booking.blade.php
- routes/web.php
- tests/Feature/HighTicket/LeadConvertTest.php

related_specs:
- specs/011-high-ticket/spec.md

---

## Newsletter (012)
purpose: 極簡電子報 / mini-blog — Markdown 文章 CRUD（YouTube embed）、SEO 部落格前台（/blog、tag 頁、RSS、sitemap、JSON-LD）、首頁精選文章與原生分享、email 一鍵訂閱即成會員、把文章寄成極簡電子報（Broadcast）、開信追蹤與月排程清理沉睡訂閱者
specs: specs/012-newsletter/

main_files:
- app/Console/Commands/CleanDormantSubscribers.php
- app/Console/Commands/PublishScheduledPosts.php
- app/Http/Controllers/Admin/BroadcastController.php
- app/Http/Controllers/Admin/PostController.php
- app/Http/Controllers/Admin/PostImageController.php
- app/Http/Controllers/BlogController.php
- app/Http/Controllers/BlogFeedController.php
- app/Http/Controllers/HomeController.php
- app/Http/Controllers/NewsletterSubscriptionController.php
- app/Http/Controllers/NewsletterTrackingController.php
- app/Http/Controllers/SitemapController.php
- app/Http/Requests/Admin/SendBroadcastRequest.php
- app/Http/Requests/Admin/StorePostRequest.php
- app/Http/Requests/Admin/UpdatePostRequest.php
- app/Http/Requests/StoreNewsletterSubscriptionRequest.php
- app/Jobs/SendBroadcastEmailJob.php
- app/Mail/NewsletterBroadcastMail.php
- app/Mail/NewsletterWelcomeMail.php
- app/Mail/VerificationCodeMail.php
- app/Models/Broadcast.php
- app/Models/NewsletterEmailEvent.php
- app/Models/Post.php
- app/Models/PostImage.php
- app/Models/Tag.php
- app/Models/User.php
- app/Services/BroadcastService.php
- app/Services/NewsletterService.php
- app/Services/PostService.php
- app/Services/VerificationCodeService.php
- database/migrations/2026_07_10_000001_create_posts_table.php
- database/migrations/2026_07_10_000002_create_tags_table.php
- database/migrations/2026_07_10_000003_create_post_tag_table.php
- database/migrations/2026_07_10_000004_create_post_images_table.php
- database/migrations/2026_07_10_000005_create_broadcasts_table.php
- database/migrations/2026_07_10_000006_create_newsletter_email_events_table.php
- database/migrations/2026_07_10_000007_add_newsletter_fields_to_users_table.php
- resources/js/Components/Admin/PostForm.vue
- resources/js/Components/BlogArticles.vue
- resources/js/Components/Newsletter/PostCard.vue
- resources/js/Components/Newsletter/ShareButtons.vue
- resources/js/Components/Newsletter/SubscribeForm.vue
- resources/js/Components/VerificationCodeInput.vue
- resources/js/Pages/Admin/Broadcasts/Index.vue
- resources/js/Pages/Admin/Broadcasts/Show.vue
- resources/js/Pages/Admin/Posts/Create.vue
- resources/js/Pages/Admin/Posts/Edit.vue
- resources/js/Pages/Admin/Posts/Index.vue
- resources/js/Pages/Blog/Index.vue
- resources/js/Pages/Blog/Show.vue
- resources/js/Pages/Blog/Tag.vue
- resources/js/Pages/Home.vue
- resources/js/Pages/Newsletter/Unsubscribe.vue
- resources/views/app.blade.php
- resources/views/emails/newsletter-broadcast.blade.php
- resources/views/emails/newsletter-welcome.blade.php
- resources/views/sitemap.blade.php
- routes/console.php

related_specs:
- specs/012-newsletter/spec.md
