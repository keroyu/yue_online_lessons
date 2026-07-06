---
id: 001-auth-account
status: done
owner_files:
  - app/Http/Controllers/Auth/LoginController.php
  - app/Http/Requests/Auth/SendVerificationCodeRequest.php
  - app/Http/Requests/Auth/VerifyCodeRequest.php
  - app/Http/Controllers/Member/SettingsController.php
  - app/Http/Requests/Member/UpdateProfileRequest.php
  - app/Models/User.php
  - app/Models/VerificationCode.php
  - app/Services/VerificationCodeService.php
  - app/Mail/VerificationCodeMail.php
  - resources/views/emails/verification-code.blade.php
  - resources/js/Pages/Auth/Login.vue
  - resources/js/Pages/Member/Settings.vue
  - resources/js/Components/VerificationCodeInput.vue
  - database/migrations/0001_01_01_000000_create_users_table.php
  - database/migrations/2026_01_16_000004_create_verification_codes_table.php
touchpoints:
  - file: app/Models/User.php
    owner: 001-auth-account
    why: 本模組擁有；007-points-referral 使用 points / referral_code / referral_activated_at 欄位與 generateReferralCode()，008-members-admin 使用 role scope 與會員管理相關方法
  - file: database/migrations/2026_05_10_000001_add_points_to_users_table.php
    owner: 007-points-referral
    why: users 表的 points 欄位由積分模組 alter 新增，本模組 Settings 頁面顯示該值
  - file: database/migrations/2026_06_30_000002_add_referral_fields_to_users_table.php
    owner: 007-points-referral
    why: users 表的 referral_code / referral_activated_at 由推薦模組 alter 新增，User model 建立時自動產生 referral_code
  - file: resources/js/composables/useCart.js
    owner: cart-checkout 模組（原 009-cart-checkout，新編號待定）
    why: Login.vue 於登入成功後呼叫 mergeGuestCartOnLogin() 合併訪客購物車
---

# Auth & Account（Email 驗證碼登入 + 會員帳號設定）

## 目標

讓用戶以 Email + 6 碼驗證碼完成無密碼登入/註冊（首次驗證成功即自動建立帳號），
並提供會員帳號設定頁：編輯個人資料、檢視訂單紀錄與積分/作業完成歷程。

## User Stories

### User Story 1 - Email 驗證碼登入/註冊 (Priority: P1)

用戶在 `/login` 輸入 email，系統寄出 6 碼數字驗證碼；輸入正確驗證碼即登入。
該 email 未註冊時，勾選同意條款後自動建立帳號，全程不需設定密碼。

**驗收**：
- [x] 輸入有效 email 並提交後，系統寄出 6 碼驗證碼（寄件者「經營者時間銀行」，主旨「您的登入驗證碼」）
- [x] 輸入正確驗證碼後登入成功，更新 last_login_at / last_login_ip，跳轉「我的課程」（`/member/learning`）
- [x] email 未註冊且未勾選同意條款時，後端拒絕（`agree_terms` 驗證錯誤），前端登入按鈕亦為禁用
- [x] email 未註冊且勾選同意條款並驗證成功時，自動建立帳號（`email_verified_at = now()`，role 預設 member）並登入
- [x] 驗證碼錯誤時顯示錯誤訊息與剩餘嘗試次數，可重新輸入
- [x] 驗證碼過期（10 分鐘）時顯示「驗證碼已過期，請重新發送」，可重發
- [x] 60 秒內重複請求發送時回傳「請等待 N 秒後再發送驗證碼」（驗證碼已過期則不受此限）
- [x] 驗證失敗達 5 次後鎖定該 email 15 分鐘，鎖定期間發送與驗證均被拒絕
- [x] 驗證碼輸入採 6 格自動跳格組件（VerificationCodeInput），支援貼上完整驗證碼、Backspace 回退
- [x] 登入成功後前端呼叫 `mergeGuestCartOnLogin()` 合併訪客購物車
- [x] `/login?hint=payuni` 或 `?hint=purchase` 時顯示「付款已完成！請用購買時填寫的 Email 登入」提示
- [x] Email 寄送失敗時記錄 error log 並回傳「驗證碼發送失敗，請稍後重試」
- [x] 已登入用戶可 `POST /logout` 登出（invalidate session + regenerate token），導回首頁

### User Story 2 - 會員帳號設定 (Priority: P2)

會員在 `/member/settings` 檢視與修改個人資料（暱稱、真實姓名、電話、出生年月日），
並檢視訂單紀錄、積分餘額與作業完成歷程。

**驗收**：
- [x] 頁面顯示 email（唯讀，標示「Email 無法修改」）與目前的暱稱/真實姓名/電話/出生年月日
- [x] 修改資料並儲存後顯示「資料已更新」；四個欄位皆為選填（nullable）
- [x] 出生日期必須早於今天，否則顯示驗證錯誤並保留輸入內容
- [x] 訂單紀錄區塊顯示所有購買紀錄（日期、課程名稱、金額、狀態標籤）；狀態標籤依 type 區分：系統指派/贈送/顧問轉換（lead_conversion），一般購買則依 status 顯示已付款/已退款
- [x] 無任何訂單時顯示「尚無訂單紀錄」空狀態
- [x] 「積分與作業完成記錄」區塊顯示積分餘額（`user.points`）與作業完成列表（完成時間、課程、小節、獲得積分）
- [x] 作業獲得積分以 `point_transactions` 帳本（type=earn_homework, reference_type=assignment）為準，而非當前設定值
- [x] 未登入訪問 `/member/settings` 時跳轉登入頁（auth middleware）

## Requirements

- **FR-001**: 驗證碼為 6 碼數字（`random_int` 補零），有效期 10 分鐘
- **FR-002**: 同一 email 60 秒內最多發送 1 次驗證碼；但最後一組驗證碼已過期時允許立即重發
- **FR-003**: 驗證失敗達 5 次即鎖定該 email 15 分鐘；鎖定檢查優先於頻率檢查（防止用重發繞過鎖定）
- **FR-004**: `POST /login/send-code` 另有 HTTP 層 `throttle:6,1`（每分鐘 6 次，跨 email 防濫用）
- **FR-005**: 驗證一律比對該 email「最新一筆」驗證碼；驗證成功後刪除該 email 全部驗證碼（一次性使用）
- **FR-006**: 首次驗證成功即自動建立帳號，不需預先註冊；新用戶必須 `agree_terms` accepted（後端僅在 user 不存在時強制）
- **FR-007**: 登入使用 `Auth::login($user, remember: true)`；session lifetime 43200 分鐘（30 天，`.env SESSION_LIFETIME`）
- **FR-008**: 登入頁路由掛 `guest` middleware，已登入者不可再訪問 `/login`
- **FR-009**: 帳號設定四欄位皆選填：nickname/real_name ≤ 100 字元、phone ≤ 20 字元、birth_date 須早於今天；email 不可修改
- **FR-010**: 前端於發送驗證碼成功後一律顯示同意條款勾選框（`isNewUser` 簡化為恆 true），實際是否必勾由後端判定 — 避免透過 UI 洩漏「此 email 是否已註冊」

## 設計決策

- **D1**: 無密碼 OTP 登入（`users.password` nullable）— 降低註冊門檻；購買流程（webhook/金流回調）自動建立的帳號也能直接用 email 登入
- **D2**: 驗證碼存 DB（`verification_codes` 表）而非 Cache — 需要 attempts / locked_until 的原子遞增與跨請求狀態，且方便排程清理與稽核
- **D3**: 驗證碼 email 同步寄送（非 queue）— 用戶在頁面上等待收信，同步失敗可立即回饋錯誤；量小無需 queue
- **D4**: Settings 頁除個人資料外，還聚合訂單紀錄與積分/作業歷程 — 會員自助查詢單一入口；積分數字以 point_transactions 帳本回查，避免設定變動造成歷史顯示錯誤
- **D5**: User model `creating` hook 自動產生 8 碼推薦碼（排除 0/O/1/I/L 易混字元、碰撞重試）— 欄位由 007-points-referral 引入，但產生邏輯放在 User model（本模組擁有），確保任何來源建立的帳號都有推薦碼

## Schema

- `users` — 平台用戶；email unique 為唯一登入識別，password nullable（OTP 登入不使用）；role enum(admin/editor/member) 預設 member；nickname/real_name/phone/birth_date 個人資料皆 nullable；last_login_at/last_login_ip 於每次驗證成功時更新；points / referral_code / referral_activated_at 由 007-points-referral alter 新增（referral_code 於建立帳號時自動產生、永久不變）
- `verification_codes` — OTP 驗證碼；同一 email 可有多筆但只有最新一筆有效；attempts 只增不減，達 5 即寫入 locked_until（now+15min）；expires_at = created_at + 10min；驗證成功即刪除該 email 全部紀錄；無 updated_at（timestamps 關閉）

## 進度日誌

- 2026-07-06: 領域重組 — 自 001-course-platform-mvp (US2/US4) 重寫，依實際 codebase 校正
