---
name: lessonkit.launch
description: 用 Forge API 一次上架一個 LessonKit 客戶站（空資料庫、跟 release 分支）：預檢 DNS/Resend → 建 DB、站台、.env、部署、worker、排程、SSL → 驗收。觸發詞：/lessonkit.launch、上架客戶站、新客戶網站上線。
---

# /lessonkit.launch — 上架 LessonKit 客戶站

所有客戶站都在同一台 Forge 伺服器（themustbig，129.212.217.5），跟 GitHub `release` 分支，資料庫全新、只跑 migrate 不跑 seed。執行全靠同目錄的 `launch.py`；這份文件只規定流程與注意事項。

## 0. 向使用者收齊資料

缺任何一項就停下來問，不要猜：

| 欄位 | 說明 |
|---|---|
| `domain` | 客戶主網域，例如 `nextcreates.com`（不含 www） |
| `app_name` | 網站名稱，同時用作寄件人名稱 |
| `first_admin_email` | 業主信箱。這個信箱第一次從 `/login` 登入就會成為管理員 |
| `resend_api_key` | 客戶自己 Resend 帳號的 API key |
| `mail_from` | 寄件地址，**網域必須是 Resend 上已驗證的那個**，通常是 `noreply@mail.<domain>`，不能直接用主網域 |

選填：`db_name`、`db_user`（預設取網域第一段），`php_version`（預設 `php84`）。

客戶事先要做好的事，可以提醒使用者轉告：
- DNS：`@` 的 A 記錄指向 `129.212.217.5`（`www` 可選，指過來就會自動做 www 轉址）。
- Resend：加 `mail.<domain>` 網域，並把 Resend 給的 DNS 記錄設好，直到狀態變成 verified。

## 1. 寫 input 檔（含密鑰，不進 git）

寫到 `~/.config/lessonkit/sites/<domain>.json`，權限 600（目錄 700）。用 Python 的 `os.open(..., 0o600)` 寫，**不要把 API key 放進 shell 指令參數，也不要在回覆裡貼出來**。

## 2. 預檢（唯讀）

```bash
python3 .claude/skills/lessonkit.launch/launch.py preflight ~/.config/lessonkit/sites/<domain>.json
```

會檢查：Forge token、A 記錄（直接問權威 NS，避開公共 DNS 快取）、Resend 網域是否 verified、站台名稱與資料庫名稱有沒有撞到、`release` 分支是否存在。

有任何 `[X]` 就停下來，把要客戶處理的事情整理給使用者，不要繼續。

## 3. 出版前確認 release 內容

`run` 會部署 `release` 目前指向的 commit。開始前先跑 `git log --oneline origin/release..origin/main`。若 `main` 有還沒出版的 commit，列給使用者看，問要不要先推 `release`（`git push origin main:release`，並依 `config/lessonkit.php` 的版本規則 bump 版號、打 tag）。不要擅自推送。

## 4. 執行上架

```bash
python3 .claude/skills/lessonkit.launch/launch.py run ~/.config/lessonkit/sites/<domain>.json
```

建立 Forge 資源前，先跟使用者確認一次網域、名稱和資料庫名稱。腳本依序做：

1. 建資料庫和使用者（隨機密碼）。
2. 建站台：站名直接用客戶網域，zero-downtime，共用 `storage`，repo `keroyu/yue_online_lessons`，分支 `release`，開 push-to-deploy。
3. 寫 `.env`（APP_KEY 由腳本產生）和部署腳本（跟正式站同一份）。
4. 觸發部署並等待完成，失敗會印出部署 log。
5. 建 queue worker（server 層級）和每分鐘排程（site 層級）。
6. 申請 Let's Encrypt（http-01）。若印出 `[!]`（token 少了 `site:meta` scope），請使用者到 Forge → 站台 → Domains → SSL 手動按一下。

進度存在 `~/.config/lessonkit/sites/<domain>.state.json`，裡面有 DB 密碼、APP_KEY、各 Forge id。中途失敗就修好原因再跑同一個指令，已經完成的步驟會跳過。**不要刪掉 state 檔重跑**，否則會重複建資源。

## 5. 驗收

```bash
python3 .claude/skills/lessonkit.launch/launch.py verify ~/.config/lessonkit/sites/<domain>.json
```

檢查項目：https 首頁/login/robots/sitemap、http→https 轉址、憑證、頁面帶 LessonKit 版本、DB 可連線、0 位使用者、Email 範本已建立、沒有待跑的 migration、storage symlink、`.env` 關鍵值、worker running、排程存在。剛申請 SSL 時 https 可能要等一兩分鐘，失敗就隔一下再跑一次 verify。

## 6. 收尾

- 更新 memory `reference_lessonkit_forge_api.md`：在客戶站清單加一行，寫網域、Forge site id、目錄、Resend 網域。
- 回報給使用者：網址、驗收結果、手動處理過的項目（例如 SSL）。另外提醒業主上線後要自己完成這些事：
  1. 用 `first_admin_email` 到 `/login` 收 OTP 登入，就會成為管理員。
  2. 後台上傳 logo 和 favicon，填網站名稱。
  3. 「API 設定」填 Portaly 商店帳號和金流參數（要改 `.env` 的金流金鑰時，改完必須重新部署，因為 `artisan optimize` 會快取設定）。

## 注意

- Forge token 在 `~/.config/lessonkit/forge_token`，絕不進 git、不在回覆中顯示。
- API 被 Cloudflare 擋（error 1010）是 User-Agent 問題，`launch.py` 已經處理。
- `release` 推送後所有客戶站都會自動部署（push-to-deploy），推之前務必列出差異讓使用者確認。
