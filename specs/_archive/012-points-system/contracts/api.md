# API Contracts: 積分系統擴充 (012-points-system)

**Branch**: `012-points-system` | **Date**: 2026-06-30
**Updated**: 2026-07-05 - 實作完成。推薦碼驗證實作於既有結帳 api 群組：`POST /api/checkout/validate-referral`（name `api.checkout.validate-referral`），與下方簡化列示的 `/checkout/validate-referral` 為同一端點。派發積分 `POST /admin/members/{member}/grant-points` 對 AJAX 回 JSON、對 Inertia 回 redirect。
**Updated**: 2026-07-05 - US1 兌換改兩段式確認（詳見 §3）；修 `Admin/CourseController::edit` 漏傳 `redeem_points`。路由清單不變。
**Updated**: 2026-07-05 - 兌換成功導向由 `member.classroom` 改為 `member.learning`（我的課程）；確認觸發按鈕文字改為方位中性的「請確認兌換…」。
**Updated**: 2026-07-05 - 會員積分中心（§4）新增 `rewardRate` prop（目前回饋比例 %），推薦碼區塊文案告知使用者回饋比例。

慣例：Inertia 頁面回 `Inertia::render`；表單／AJAX 動作回 `JsonResponse` 或 `redirect()->with()`（比照既有 `CouponController`、`MemberController`）。錯誤訊息一律中文。Controller 不含商業邏輯，委派 Service。

---

## 1. 結帳推薦碼即時驗證（US2）

比照 `POST /cart/apply-coupon`（`CouponController::apply`）——**JSON 鍵與節流一律對齊既有 CouponController**。

```
POST /checkout/validate-referral        name: checkout.validate-referral
Middleware: web（guest + auth 皆可，結帳本身支援 guest）
Request (ValidateReferralRequest):
  { "referral_code": "AB3D7K9P", "buyer_email": "user@example.com" }
Response 200 (有效):
  { "success": true, "rate": 10 }
Response 422 (無效，前端據此提示，不建單):
  { "success": false, "message": "推薦碼不存在，請再次確認" }
  // 其他文案： "不可使用自己的推薦碼"、"此推薦碼目前無法使用"
Response 429 (失敗次數過多):
  { "message": "嘗試次數過多，請於 60 秒後再試" }
```

**對齊既有 `CouponController` 的兩個約定**：
- JSON 錯誤鍵用 **`message`**（非 `error`）；service 內部回 `['success'=>false,'error'=>...]`，controller 轉成 `['success'=>false,'message'=>$result['error']]`、HTTP 422。
- **失敗節流**：比照 011 折扣碼「IP 失敗節流」與 `CouponController` 的 429，對 `validate-referral` 加 `RateLimiter`（同一 IP 連續失敗達上限回 429）。目的：防止枚舉 `referral_code` 來 harvest「已啟用的推薦人」。

驗證規則（`ReferralService::validateAtCheckout`）：
1. 正規化（去空白、轉大寫）。
2. 查無對應 `users.referral_code` → 「推薦碼不存在，請再次確認」。
3. referrer 即買家本人（`buyer_email` 比對 referrer.email，或登入者 id 相同）→ 「不可使用自己的推薦碼」。
4. `referrer.referral_activated_at` 為 null → 「此推薦碼目前無法使用」。

---

## 2. 結帳建單（既有 `POST /checkout/initiate` 擴充，US2）

`CheckoutRequest` 新增欄位：

```
'referral_code' => ['nullable', 'string', 'max:12'],
```

`CheckoutController::initiate` 流程擴充：
- 取 `referral_code`，呼叫 `ReferralService::validateAtCheckout`；失敗 → `response()->json(['success'=>false,'error'=>...], 422)`（建單前擋下，FR-018）。
- 通過 → 傳入 `CheckoutService::createOrder(..., $referral)`，於 `orders` 快照 `referrer_user_id`、`referral_rate`、`referral_reward_points`（以建單 subtotal 預估）。

`CheckoutService::fulfillOrder`（付款確認後，FR-020）新增：
- 若 `order.referrer_user_id` 存在 → `ReferralService::reward($order)`（以實付 `order.total_amount` 重算並發放 `earn_referral`，`available_at = now + maturity_days`）。
- 對買家會員呼叫 `ReferralService::evaluateActivation($buyerUser)`（FR-016）。

---

## 3. 課程兌換（US1）

```
POST /courses/{course}/redeem           name: courses.redeem
Middleware: auth
Request (RedeemCourseRequest): {}（course 由路由綁定，user 由 auth）
Response (成功): redirect()->route('member.learning')->with('success','已使用積分兌換課程')  // 兌換後導向「我的課程」（不直接進教室，較不突兀）
Response (失敗): back()->withErrors(['redeem' => '可用積分不足'])
  // 其他： '此課程無法以積分兌換'、'您已擁有此課程'
```

`RedemptionController::store` → `RedemptionService::redeem($user, $course)`：
- `DB::transaction`：課程可兌換（`redeem_points > 0`）→ 未擁有 → `PointService::redeemDeduct($user, $course->redeem_points, 'course', $course->id)`（條件式 UPDATE，不足則 throw rollback）→ 建 `source='points'` Purchase。
- 僅能使用已成熟可用積分（FR-012，扣的是 `users.points` 快取）。

**銷售頁 props（`Course/Show.vue`）新增**：
- `redeemPoints`：課程兌換所需點數（null 表不可兌換）。
- `userAvailablePoints`：登入者可用積分（未登入為 null）。
- 前端：可兌換且 `userAvailablePoints >= redeemPoints` → 按鈕可點；不足 → disabled + 「還差 N 點」。
- **兩段式確認（2026-07-05）**：`RedeemButton.vue` 綠色按鈕點擊後 emit `request`（不直接 POST）；`Course/Show.vue` 於銷售頁顯示確認面板（目前可用／本次扣除／兌換後餘額 = `userAvailablePoints - redeemPoints`），按「確定兌換」才 `router.post(courses.redeem)`，失敗以 `errors.redeem` 於面板顯示中文訊息。路由與 payload 不變。

**後台編輯（bug fix 2026-07-05）**：`Admin/CourseController::edit` 的 course props 須包含 `redeem_points`，否則編輯表單載入為空（`Admin/CourseForm.vue` 以 `props.course?.redeem_points` 初始化）。

---

## 4. 會員積分中心（US3）

```
GET /member/points                       name: member.points
Middleware: auth
Inertia::render('Member/Points', [
  'available'   => int,                  // users.points
  'pending'     => int,                  // SUM 未成熟
  'referralCode'=> string,
  'referralActive' => bool,
  'thresholdAmount' => int,              // 用於未啟用提示文案
  'rewardRate'  => int,                  // 目前回饋比例（%），前端文案顯示「實付金額 N%」
  'transactions'=> paginate([            // 逐筆明細
    { created_at, type, amount, note, available_at, is_matured }
  ]),
])
```

---

## 5. 後台派發積分 + 帳本明細（US6）

**派發**（FR-030，只增不減）：
```
POST /admin/members/{member}/grant-points    name: admin.members.grant-points
Middleware: auth, admin
Request (Admin\GrantPointsRequest):
  { "amount": 300, "note": "活動獎勵" }
Rules: 'amount' => ['required','integer','min:1']   // 僅正整數，無扣除入口
       'note'   => ['nullable','string','max:255']
Response: back()->with('success', '已派發 300 積分')
```
→ `PointService::award($member, $amount, 'admin_grant', 'admin', null, $note)`（即時成熟）。

**帳本明細**（FR-031）：`MemberController::show`（既有 `GET /admin/members/{member}`）的 JSON 回應新增：
```
'points' => int,                         // 目前可用
'pointTransactions' => [                  // 該會員帳本（分頁或近 N 筆）
   { created_at, type, amount, note, available_at }
]
```
`MemberDetailModal.vue` 的「積分」區塊：顯示可用積分 + 派發表單 + 帳本明細列表。

---

## 6. 後台推薦成效統計（US5）

比照折扣碼統計頁（011 `Admin/Coupons/Show`）。
```
GET /admin/referrals                     name: admin.referrals.index
Middleware: auth, admin
Query: ?days=7|30|60|90|all（預設 30）
Inertia::render('Admin/Referrals/Index', [
  'rows' => [{ referrer_name, referrer_email, referral_code,
               order_count, revenue, reward_points }],
  'range'=> string,
])
```
→ `ReferralStatsController` 聚合 `orders WHERE referrer_user_id IS NOT NULL AND status='paid'`，依 referrer 分組、可依時間區間篩選。

---

## 7. 退款互動（US 對應 FR-023~025；搭配 006）

`TransactionService::refund(Purchase $purchase)` 擴充：
- 取 `$order = $purchase->order`；若 `$order?->referrer_user_id`：
  - 檢查 `now() <= $order->webhook_received_at->addDays(referral_maturity_days)`；逾期 → 回 `['success'=>false,'error'=>'此訂單已超過退款期限']`（FR-023）。
  - 期限內 → 標記退款後呼叫 `PointService::voidReferral($order)`（作廢未成熟回饋，冪等，FR-024）。
- `evaluateActivation` 不因退款回退（旗標永久，FR-025）。

---

## 8. 排程：回饋成熟結算 + 對帳

比照既有 `routes/console.php`（`drip:process-emails->dailyAt('09:00')`、`courses:update-status->everyMinute()`）的排程慣例。

```
Console command: points:mature        （每日排程，後備批次）
行為: PointService::matureDue() — 批次將所有到期未同步的 earn_referral 計入 users.points。
      注意：即時正確性由 availableBalance/redeemDeduct 內的 syncMatured(User) 保證，
      本指令僅處理長期未登入者與確保最終一致，非「成熟可用」的必要條件。

Console command: points:reconcile      （每日排程，守雙真相來源）
行為: PointService::reconcile() — 斷言每位會員 users.points == SUM(已成熟帳本)，
      列出/記錄漂移者。亦作為 CI Feature test 的斷言依據（SC-002）。
```

---

## 路由彙總（routes/web.php 新增）

| Method | URI | Name | Middleware |
|--------|-----|------|------------|
| POST | `/checkout/validate-referral` | `checkout.validate-referral` | web |
| POST | `/courses/{course}/redeem` | `courses.redeem` | auth |
| GET | `/member/points` | `member.points` | auth |
| POST | `/admin/members/{member}/grant-points` | `admin.members.grant-points` | auth, admin |
| GET | `/admin/referrals` | `admin.referrals.index` | auth, admin |

（`/checkout/initiate`、`/admin/members/{member}` 為既有路由擴充，不新增。）
