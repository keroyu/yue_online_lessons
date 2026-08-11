# 部署指南：全新安裝給新客戶

把這套系統交付給新客戶（全新 server、清空資料庫）時，照這份清單走。

## 0. 前置：客戶網域的寄信設定（Resend）

在填 `.env` 之前，客戶的寄信網域要先設定好，不然後面 OTP、通知信、drip email 全部寄不出去或進垃圾信。

### 0.1 決定用哪個網域/子網域寄信

**強烈建議用子網域**，例如 `mail.客戶網域.com` 或 `notify.客戶網域.com`，不要直接用根網域 `客戶網域.com`。原因：

- 如果客戶根網域已經在用 Google Workspace / Outlook 收發真人信件，SPF 只能有一筆 TXT 記錄，兩邊要合併寫（見下面地雷）；子網域完全獨立、互不干擾
- 萬一系統寄信被檢舉或信譽分數變差，只影響子網域，不會拖累客戶的正式商業信箱

### 0.2 Resend 後台加網域

**新客戶要開自己的 Resend 帳號**，不要沿用你自己的帳號幫他寄信 —— 帳單、寄送額度、寄信紀錄都該歸屬客戶自己。

1. 客戶（或你代操作但用客戶帳號）登入 Resend → Domains → Add Domain
2. 填入 `mail.客戶網域.com`
3. Resend 產生一組 DNS 記錄，通常包含：
   - **DKIM**：一筆 TXT（或 CNAME，視版本）用來簽章驗證寄件人身分
   - **SPF**：一筆 TXT，內容類似 `v=spf1 include:amazonses.com ~all`（Resend 底層走 AWS SES）
   - **MX**（bounce 退信追蹤用）：通常是 `feedback-smtp.resend.com`，priority 10

### 0.3 把記錄貼到網域的 DNS 後台

不管網域註冊在 Cloudflare、GoDaddy、中華電信、蝦皮商店網域哪一家，操作都一樣：進 DNS 管理頁 → 依 Resend 給的內容逐筆新增。

**地雷**：如果加在根網域（不是子網域）且該網域已有 SPF 記錄（例如已用 Google Workspace），**不能新增第二筆 SPF TXT**，DNS 只認第一筆、其餘會被忽略甚至驗證失敗。必須手動合併成一筆：

```
v=spf1 include:_spf.google.com include:amazonses.com ~all
```

（這也是為什麼子網域比較省事 —— 全新的子網域不會有既有記錄要合併）

### 0.4（建議）加 DMARC

Resend 不強制，但建議在 `_dmarc.客戶網域.com` 加一筆：

```
v=DMARC1; p=none; rua=mailto:客戶的信箱@example.com
```

`p=none` 先觀察不擋信，之後信譽穩了可以拉高到 `quarantine`。

### 0.5 等驗證

DNS 生效通常幾分鐘到最長 48 小時。Resend 後台 Domain 狀態變綠色「Verified」才算成功，沒過先不要上線寄信。

---

## 1. `.env` 必填項

- `APP_KEY` — 每個環境都要重新 `php artisan key:generate`，不可沿用
- `APP_ENV=production`、`APP_DEBUG=false`、`APP_URL=https://新網域`
- `DB_*` — 新資料庫帳密
- `SESSION_DOMAIN`（改成新網域）、正式站建議加 `SESSION_SECURE_COOKIE=true`
- `RESEND_API_KEY=`（客戶自己帳號產生的 key）
- `MAIL_FROM_ADDRESS=noreply@mail.客戶網域.com`
- `MAIL_FROM_NAME="客戶的品牌名稱"`

`MAIL_FROM_ADDRESS` 的信箱位址本身不需要真的存在（no-reply 通常不會有人收信），只要網域驗證通過、SPF/DKIM 對得上就能寄。

## 2. 其他第三方服務金鑰

全部要換成新客戶自己的帳號，不能沿用你的：

| 服務 | 變數 | 用途 |
|---|---|---|
| PayUni | `PAYUNI_MERCHANT_ID/HASH_KEY/HASH_IV`、`PAYUNI_SANDBOX` | 上線前記得關 sandbox |
| 藍新 NewebPay | `NEWEBPAY_MERCHANT_ID/HASH_KEY/HASH_IV`、`NEWEBPAY_ENV` | 同上，`sandbox`→正式 |
| Meta Pixel | `META_PIXEL_ID` | 新客戶自己的像素，不是你現有那個 |
| Cloudflare Stream | `CLOUDFLARE_STREAM_CUSTOMER_CODE/KEY_ID/PRIVATE_KEY` | 只有走簽名播放的影片才需要；純 Vimeo embed 不用填任何 key |
| Portaly | `PORTALY_WEBHOOK_KEY` | 如果有用 Portaly 收單 webhook |
| AWS S3 | `AWS_*`、`FILESYSTEM_DISK` | 選填，不設就用本機 `local` disk |

`SLACK_BOT_USER_OAUTH_TOKEN`（`config/services.php` 有留欄位）目前程式碼沒有實際呼叫用到，可以不填。

## 3. 資料庫 seed 要客製，不能整包直接跑

`database/seeders/UserSeeder.php` 目前寫死了 admin 帳號 `themustbig@gmail.com` 和三個 `member@example.com` 示範帳號 —— 這是原開發者自己的帳號，交付給別人前一定要改成對方的 email，或整段拿掉改用互動式建立 admin 的指令/流程。

## 4. 程式碼裡寫死的品牌文案（不是 `.env` 能改的）

`resources/js/Components/Legal/{Privacy,Purchase,Terms}Content.vue` 裡直接寫死：

- 平台名稱「經營者時間銀行」
- 經營者「投好壯壯有限公司」+ 台北地址

換客戶要直接改這三個 Vue 檔案的內容（公司名、統編、地址、平台名），不會因為換資料庫或 `.env` 而自動變。

## 5. 部署環境層（Server 設定，不是程式碼）

- **`php artisan storage:link`** —— 課程封面、OG 圖、部落格圖都存 `Storage::disk('public')`，沒建 symlink 上傳的圖會 404
- **Queue worker** —— `QUEUE_CONNECTION=database`，需要 Supervisor 常駐跑 `php artisan queue:work`（Email、Meta CAPI 上報等都靠 queue）
- **Cron scheduler** —— 系統 crontab 要有 `* * * * * php artisan schedule:run`。漏掉這條的話以下全部不會動：課程預售自動上架、排程文章發布、電子報排程寄送、drip email、referral 積分結算、高單價預約 17:00 提醒信等（見 `routes/console.php`）
- SSL 憑證 + DNS 指到新網域
- MySQL 版本/連線設定（官網 `.pkg` 安裝、3306 port）

## 6. 上線前實測

用 OTP 登入流程（`VerificationCodeMail`）寄一封真的信到 Gmail/Outlook 各測一次，確認：

- 沒有進垃圾信
- 寄件人顯示名稱、地址正確
- 可用 [mail-tester.com](https://www.mail-tester.com) 之類外部工具抓 spam score（是否使用自行判斷）
