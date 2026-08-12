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

## 1. 客戶網域指向我方 Server（Public IP: `129.212.217.5`）

網站跑在我方 Laravel server（Laravel Forge，固定 IP `129.212.217.5`），客戶自己去買網域。這段是**給客戶的 DNS 設定說明**，寄信的 DNS（第 0 節）跟這段互不衝突，兩邊都要設。

### 1.1 兩種做法，先選一種

| 做法 | 客戶要做的事 | 適合誰 |
|---|---|---|
| **A. 客戶保留 DNS 管理權**（建議） | 在原註冊商的 DNS 後台加兩筆 A 記錄指到我方 IP | 一般情況，客戶想自己保有控制權 |
| **B. 把 NS 轉到我方 Cloudflare** | 在註冊商把 Nameserver 改成我方 Cloudflare 給的兩組 NS | 客戶完全不想碰 DNS，全部委託我方代管 |

做法 B 之後所有 DNS（含第 0 節的寄信記錄）都由我方代設，客戶只要動一次 Nameserver。但要提醒客戶：**NS 一改，該網域現有的其他服務（企業信箱、舊官網、子網域）都要一起搬過來設**，不然會斷線。

### 1.2 做法 A：客戶要加的 DNS 記錄

進註冊商 DNS 管理頁（GoDaddy / Cloudflare / Gandi / 中華電信 / Namecheap 操作都一樣），新增：

| Type | Name（主機名稱） | Value | TTL |
|---|---|---|---|
| A | `@`（代表根網域本身，有些後台要填空白或網域全名） | `129.212.217.5` | 自動 / 3600 |
| A | `www` | `129.212.217.5` | 自動 / 3600 |

`www` 那筆也可以改成 CNAME 指向根網域（`客戶網域.com`），效果一樣，但**根網域 `@` 一定要用 A 記錄**，不能用 CNAME（DNS 規範不允許根網域 CNAME，多數後台會直接報錯）。

如果只想用子網域上線（例如 `learn.客戶網域.com`，根網域留給現有官網），那就只加一筆：

| Type | Name | Value |
|---|---|---|
| A | `learn` | `129.212.217.5` |

### 1.3 常見地雷

- **不要用「網址轉址 / Domain Forwarding / 301 轉址」功能**。很多客戶在 GoDaddy 看到「轉址」就設下去，結果瀏覽器網址列會變成我方的 IP 或臨時網域、SSL 也發不出來。要的是 **A 記錄**，不是轉址。
- **既有的 A 記錄要刪掉或改掉**。網域如果原本有停放頁（parking page）或舊主機，後台常已存在一筆 `@` 的 A 記錄指到別的 IP —— 直接修改那筆的值，不要新增第二筆，否則流量會在兩個 IP 之間亂跳。
- **Cloudflare 用戶注意橘色雲朵（Proxy）**：
  - 申請 SSL 憑證（Let's Encrypt）當下，先把該筆記錄切成灰色雲朵 `DNS only`，憑證發完再開回橘色，不然 HTTP-01 驗證可能失敗。
  - 開橘色雲朵時，Cloudflare 的 SSL/TLS 模式必須設 **Full (strict)**。設成 `Flexible` 會跟我方 server 的 HTTPS 轉址打架，造成無限重新導向（`ERR_TOO_MANY_REDIRECTS`）。
- **寄信記錄不受影響**：第 0 節加在 `mail.客戶網域.com` 的 SPF/DKIM/MX，跟這裡的 `@`、`www` A 記錄是不同主機名稱，各自獨立、不會互相覆蓋。
- **生效時間**：一般幾分鐘到 1 小時，最長 48 小時。改之前先把該筆記錄 TTL 調低（300 秒）可以縮短切換陣痛期。

### 1.4 客戶設完後，我方要做的事

1. 確認 DNS 已指過來（在自己電腦跑）：
   ```bash
   dig +short 客戶網域.com
   dig +short www.客戶網域.com
   # 兩個都要回 129.212.217.5
   ```
2. Forge → 該 Server → New Site，Domain 填 `客戶網域.com`，Aliases 加 `www.客戶網域.com`
3. Forge → 該 Site → SSL → Let's Encrypt，把根網域跟 `www` **一起勾**簽發，開啟自動續期
4. `.env` 對齊新網域（詳見第 2 節）：`APP_URL=https://客戶網域.com`、`SESSION_DOMAIN=.客戶網域.com`、`SESSION_SECURE_COOKIE=true`
5. 改完 `.env` 記得 `php artisan config:clear`（正式站若有跑 `config:cache` 就重跑一次）
6. 瀏覽器實測：`http://` 會自動跳 `https://`、`www` 跟非 `www` 都進得去、鎖頭是綠的

> 同一台 server 可以掛多個客戶網域（Forge 一個 Site 一個網域），共用這個 IP 沒問題。但要注意共用資源：CPU / 記憶體 / queue worker 數量，以及**所有客戶會共用同一個對外 IP 信譽**。

---

## 2. `.env` 必填項

- `APP_KEY` — 每個環境都要重新 `php artisan key:generate`，不可沿用
- `APP_ENV=production`、`APP_DEBUG=false`、`APP_URL=https://新網域`
- `DB_*` — 新資料庫帳密
- `SESSION_DOMAIN`（改成新網域）、正式站建議加 `SESSION_SECURE_COOKIE=true`
- `RESEND_API_KEY=`（客戶自己帳號產生的 key）
- `MAIL_FROM_ADDRESS=noreply@mail.客戶網域.com`
- `MAIL_FROM_NAME="客戶的品牌名稱"`

`MAIL_FROM_ADDRESS` 的信箱位址本身不需要真的存在（no-reply 通常不會有人收信），只要網域驗證通過、SPF/DKIM 對得上就能寄。

## 3. 其他第三方服務金鑰

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

## 4. 資料庫 seed 要客製，不能整包直接跑

`database/seeders/UserSeeder.php` 目前寫死了 admin 帳號 `themustbig@gmail.com` 和三個 `member@example.com` 示範帳號 —— 這是原開發者自己的帳號，交付給別人前一定要改成對方的 email，或整段拿掉改用互動式建立 admin 的指令/流程。

## 5. 程式碼裡寫死的品牌文案（不是 `.env` 能改的）

`resources/js/Components/Legal/{Privacy,Purchase,Terms}Content.vue` 裡直接寫死：

- 平台名稱「經營者時間銀行」
- 經營者「投好壯壯有限公司」+ 台北地址

換客戶要直接改這三個 Vue 檔案的內容（公司名、統編、地址、平台名），不會因為換資料庫或 `.env` 而自動變。

## 6. 部署環境層（Server 設定，不是程式碼）

- **`php artisan storage:link`** —— 課程封面、OG 圖、部落格圖都存 `Storage::disk('public')`，沒建 symlink 上傳的圖會 404
- **Queue worker** —— `QUEUE_CONNECTION=database`，需要 Supervisor 常駐跑 `php artisan queue:work`（Email、Meta CAPI 上報等都靠 queue）
- **Cron scheduler** —— 系統 crontab 要有 `* * * * * php artisan schedule:run`。漏掉這條的話以下全部不會動：課程預售自動上架、排程文章發布、電子報排程寄送、drip email、referral 積分結算、高單價預約 17:00 提醒信等（見 `routes/console.php`）
- SSL 憑證 + DNS 指到新網域（做法見第 1 節）
- MySQL 版本/連線設定（官網 `.pkg` 安裝、3306 port）

## 7. 上線前實測

用 OTP 登入流程（`VerificationCodeMail`）寄一封真的信到 Gmail/Outlook 各測一次，確認：

- 沒有進垃圾信
- 寄件人顯示名稱、地址正確
- 可用 [mail-tester.com](https://www.mail-tester.com) 之類外部工具抓 spam score（是否使用自行判斷）
