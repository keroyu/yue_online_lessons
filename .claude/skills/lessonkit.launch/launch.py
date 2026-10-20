#!/usr/bin/env python3
"""LessonKit client-site launcher (Forge API).

Usage:
  launch.py preflight <input.json>   # read-only checks, nothing is created
  launch.py run <input.json>         # create + deploy; resumable via state file
  launch.py verify <input.json>      # post-launch checks only

input.json (chmod 600, kept under ~/.config/lessonkit/sites/, never in git):
  {"domain": "example.com", "app_name": "Example", "first_admin_email": "a@b.com",
   "resend_api_key": "re_...", "mail_from": "noreply@mail.example.com"}
Optional: "db_name", "db_user", "php_version" (default php84).

State is written next to the input as <domain>.state.json (holds the DB
password, APP_KEY and Forge ids) so a failed run can be resumed.
"""
import base64
import json
import os
import re
import secrets
import ssl
import subprocess
import sys
import time
import urllib.error
import urllib.request

ORG = "the-must-big-ltd"
SERVER_ID = "995274"
SERVER_IP = "129.212.217.5"
SSH = "forge@" + SERVER_IP
REPO = "keroyu/yue_online_lessons"
BRANCH = "release"
TOKEN_PATH = os.path.expanduser("~/.config/lessonkit/forge_token")
API = f"https://forge.laravel.com/api/orgs/{ORG}/servers/{SERVER_ID}"

DEPLOY_SCRIPT = """$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force

npm ci || npm install && npm run build

$ACTIVATE_RELEASE()

$RESTART_QUEUES()"""


# ---------------------------------------------------------------- helpers

def log(msg):
    print(msg, flush=True)


def die(msg):
    print("FAIL: " + msg, flush=True)
    sys.exit(1)


def token():
    try:
        return open(TOKEN_PATH).read().strip()
    except OSError:
        die(f"Forge token not found at {TOKEN_PATH}")


def forge(method, path, body=None, ok=(200, 201, 202, 204)):
    url = path if path.startswith("http") else API + path
    data = json.dumps(body).encode() if body is not None else None
    req = urllib.request.Request(url, data=data, method=method, headers={
        "Authorization": "Bearer " + token(),
        "Accept": "application/json",
        "Content-Type": "application/json",
        # Cloudflare in front of Forge rejects urllib's default User-Agent (error 1010).
        "User-Agent": "lessonkit-launch/1.0",
    })
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            raw = r.read().decode()
            code = r.status
    except urllib.error.HTTPError as e:
        raw = e.read().decode()
        code = e.code
    parsed = json.loads(raw) if raw.strip().startswith(("{", "[")) else raw
    if code not in ok:
        return code, parsed
    return code, parsed


def must(method, path, body=None, what=""):
    code, res = forge(method, path, body)
    if code not in (200, 201, 202, 204):
        die(f"{what or method + ' ' + path} -> HTTP {code}: {json.dumps(res, ensure_ascii=False)[:600]}")
    return res


def http_get(url, timeout=20):
    ctx = ssl.create_default_context()
    req = urllib.request.Request(url, headers={"User-Agent": "lessonkit-launch"})
    try:
        with urllib.request.urlopen(req, timeout=timeout, context=ctx) as r:
            return r.status, r.read().decode(errors="replace")
    except urllib.error.HTTPError as e:
        return e.code, ""
    except Exception as e:  # noqa: BLE001
        return 0, str(e)


def sh(cmd, check=False):
    p = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    if check and p.returncode != 0:
        die(f"command failed: {cmd}\n{p.stderr.strip()}")
    return p.stdout.strip()


def load(path):
    cfg = json.load(open(path))
    for k in ("domain", "app_name", "first_admin_email", "resend_api_key", "mail_from"):
        if not cfg.get(k):
            die(f"input missing '{k}'")
    d = cfg["domain"].lower().strip()
    cfg["domain"] = d
    slug = re.sub(r"[^a-z0-9]", "", d.split(".")[0])[:16] or "site"
    cfg.setdefault("db_name", slug)
    cfg.setdefault("db_user", slug)
    cfg.setdefault("php_version", "php84")
    cfg["state_path"] = os.path.join(os.path.dirname(os.path.abspath(path)), d + ".state.json")
    return cfg


def state(cfg):
    try:
        return json.load(open(cfg["state_path"]))
    except OSError:
        return {}


def save(cfg, st):
    fd = os.open(cfg["state_path"], os.O_WRONLY | os.O_CREAT | os.O_TRUNC, 0o600)
    with os.fdopen(fd, "w") as f:
        json.dump(st, f, indent=2)


def sites():
    res = must("GET", "/sites", what="list sites")
    return res.get("data", [])


def find_site(name):
    for s in sites():
        if s["attributes"]["name"] == name:
            return s
    return None


def site_dir(cfg):
    # Override only for sites created by hand under another name (e.g. *.on-forge.com).
    return cfg.get("site_dir") or f"/home/forge/{cfg['domain']}"


# ---------------------------------------------------------------- preflight

def authoritative_a(domain):
    apex = ".".join(domain.split(".")[-2:])
    ns = sh(f"dig +short NS {apex} @1.1.1.1").splitlines()
    if not ns:
        ns = sh(f"dig +short NS {apex} @8.8.8.8").splitlines()
    if not ns:
        return None, []
    return ns[0].rstrip("."), sh(f"dig +short A {domain} @{ns[0]}").splitlines()


def preflight(cfg):
    problems = []
    d = cfg["domain"]

    # 1. token + scopes we rely on
    code, _ = forge("GET", "/sites")
    log(f"[{'ok' if code == 200 else 'X'}] Forge token (list sites HTTP {code})")
    if code != 200:
        problems.append("Forge token 無效或過期")

    # 2. DNS A record (ask the authoritative NS; public resolvers can lag)
    ns, ips = authoritative_a(d)
    good = SERVER_IP in ips
    log(f"[{'ok' if good else 'X'}] DNS A {d} -> {ips or '(none)'} (NS {ns})")
    if not good:
        problems.append(f"{d} 的 A 記錄要指向 {SERVER_IP}（目前 {ips or '沒有'}）")
    _, www = authoritative_a("www." + d)
    log(f"[{'ok' if SERVER_IP in www else '--'}] DNS A www.{d} -> {www or '(none)'}（沒有也可以，SSL 只會簽主網域）")
    cfg["_www_ok"] = SERVER_IP in www

    # 3. Resend domain for mail_from
    mail_domain = cfg["mail_from"].split("@")[-1].lower()
    req = urllib.request.Request("https://api.resend.com/domains", headers={
        "Authorization": "Bearer " + cfg["resend_api_key"], "User-Agent": "lessonkit-launch"})
    try:
        with urllib.request.urlopen(req, timeout=20) as r:
            doms = json.loads(r.read().decode()).get("data", [])
        match = [x for x in doms if x.get("name", "").lower() == mail_domain]
        status = match[0].get("status") if match else None
        ok = status == "verified"
        log(f"[{'ok' if ok else 'X'}] Resend 網域 {mail_domain}: {status or '帳號裡沒有這個網域'}"
            f"（帳號裡有：{', '.join(x.get('name', '') for x in doms) or '無'}）")
        if not ok:
            problems.append(f"Resend 網域 {mail_domain} 尚未 verified（寄件地址必須用已驗證的網域）")
    except urllib.error.HTTPError as e:
        body = e.read().decode()[:200]
        # Sending-only keys cannot list domains; that is not fatal.
        if e.code in (401, 403) and "restricted" in body.lower():
            log(f"[--] Resend key 是 sending-only，無法查網域狀態；請到 Resend 後台確認 {mail_domain} 是 verified")
        else:
            log(f"[X] Resend API HTTP {e.code}: {body}")
            problems.append("Resend API key 無效")

    # 4. no clashes on Forge
    if code == 200:
        clash = find_site(d)
        st = state(cfg)
        if clash and str(clash["id"]) != str(st.get("site_id")):
            problems.append(f"Forge 上已經有站台 {d}（id {clash['id']}），不是這支腳本建的")
        log(f"[{'ok' if not clash or st.get('site_id') else 'X'}] Forge 站台名稱 {d} 可用")
        dbs = [x["attributes"]["name"] for x in must("GET", "/database/schemas").get("data", [])]
        if cfg["db_name"] in dbs and not st.get("db_id"):
            problems.append(f"資料庫 {cfg['db_name']} 已存在，請在 input 指定別的 db_name")
        log(f"[{'ok' if cfg['db_name'] not in dbs or st.get('db_id') else 'X'}] 資料庫名稱 {cfg['db_name']}")

    # 5. release branch exists
    rel = sh(f"git ls-remote --heads origin {BRANCH}")
    log(f"[{'ok' if rel else 'X'}] GitHub {BRANCH} 分支 {rel[:7] if rel else '不存在'}")
    if not rel:
        problems.append(f"origin 沒有 {BRANCH} 分支")

    if problems:
        log("\n無法上線，需要先處理：")
        for p in problems:
            log(" - " + p)
        return False
    log("\nPREFLIGHT OK")
    return True


# ---------------------------------------------------------------- run

def env_file(cfg, st):
    q = lambda v: '"' + str(v).replace('"', '\\"') + '"'  # noqa: E731
    return f"""APP_NAME={q(cfg['app_name'])}
APP_ENV=production
APP_KEY={st['app_key']}
APP_DEBUG=false
APP_URL="https://{cfg['domain']}"

APP_LOCALE=zh_TW
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=zh_TW

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE={cfg['db_name']}
DB_USERNAME={cfg['db_user']}
DB_PASSWORD="{st['db_password']}"

# 30 days in minutes
SESSION_DRIVER=database
SESSION_LIFETIME=43200
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=""
REDIS_PORT=6379

# Email (Resend.com)
MAIL_MAILER=resend
RESEND_API_KEY={cfg['resend_api_key']}
MAIL_FROM_ADDRESS={q(cfg['mail_from'])}
MAIL_FROM_NAME={q(cfg['app_name'])}

# PayUni 統一金流
PAYUNI_MERCHANT_ID=
PAYUNI_HASH_KEY=
PAYUNI_HASH_IV=
PAYUNI_SANDBOX=true

# 藍新金流 (NewebPay)
NEWEBPAY_MERCHANT_ID=
NEWEBPAY_HASH_KEY=
NEWEBPAY_HASH_IV=
NEWEBPAY_ENV=sandbox

# 第一位管理員（000 US13）
FIRST_ADMIN_EMAIL={cfg['first_admin_email']}

# Meta Pixel
META_PIXEL_ID=

# Portaly Webhook
PORTALY_WEBHOOK_KEY=

# Cloudflare Stream (signed playback)
CLOUDFLARE_STREAM_CUSTOMER_CODE=
CLOUDFLARE_STREAM_KEY_ID=
CLOUDFLARE_STREAM_PRIVATE_KEY=
CLOUDFLARE_STREAM_TOKEN_TTL=

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${{APP_NAME}}"
"""


def wait_site_installed(site_id, timeout=600):
    t0 = time.time()
    while time.time() - t0 < timeout:
        s = next((x for x in sites() if str(x["id"]) == str(site_id)), None)
        if s:
            a = s["attributes"]
            if a.get("status") == "installed" and (a.get("repository") or {}).get("status") == "installed":
                return
            log(f"   ...site {a.get('status')}, repo {(a.get('repository') or {}).get('status')}")
        time.sleep(15)
    die("site did not finish installing in time")


def wait_deploy(site_id, dep_id, timeout=900):
    t0 = time.time()
    while time.time() - t0 < timeout:
        res = must("GET", f"/sites/{site_id}/deployments/{dep_id}")
        status = res["data"]["attributes"]["status"]
        if status in ("finished", "failed", "cancelled"):
            return status
        log(f"   ...deploy {status}")
        time.sleep(15)
    die("deployment did not finish in time")


def step(st, key):
    return st.get("done", {}).get(key)


def mark(cfg, st, key, value=True):
    st.setdefault("done", {})[key] = value
    save(cfg, st)


def run(cfg):
    if not preflight(cfg):
        sys.exit(1)
    st = state(cfg)
    st.setdefault("db_password", secrets.token_urlsafe(24))
    st.setdefault("app_key", "base64:" + base64.b64encode(secrets.token_bytes(32)).decode())
    save(cfg, st)
    d = cfg["domain"]

    # 1. database + user
    if not step(st, "db"):
        log("1. 建資料庫")
        must("POST", "/database/schemas", {
            "name": cfg["db_name"], "user": cfg["db_user"], "password": st["db_password"],
        }, "create database")
        for _ in range(20):
            dbs = must("GET", "/database/schemas").get("data", [])
            db = next((x for x in dbs if x["attributes"]["name"] == cfg["db_name"]), None)
            users = must("GET", "/database/users").get("data", [])
            user = next((x for x in users if x["attributes"]["name"] == cfg["db_user"]), None)
            if db and user and db["attributes"].get("status", "installed") == "installed":
                st["db_id"], st["db_user_id"] = int(db["id"]), int(user["id"])
                break
            time.sleep(5)
        else:
            die("database not ready")
        mark(cfg, st, "db")
    log(f"   db {cfg['db_name']} (id {st['db_id']}) user {cfg['db_user']}")

    # 2. site (named after the real domain, so no extra domain step is needed)
    if not step(st, "site"):
        log("2. 建站台")
        res = must("POST", "/sites", {
            "type": "laravel",
            "name": d,
            "domain_mode": "custom",
            "www_redirect_type": "from-www" if cfg.get("_www_ok") else "none",
            "allow_wildcard_subdomains": False,
            "web_directory": "/public",
            "php_version": cfg["php_version"],
            "zero_downtime_deployments": True,
            "shared_paths": [{"from": "storage", "to": "storage"}],
            "source_control_provider": "github",
            "repository": REPO,
            "branch": BRANCH,
            "database_id": st["db_id"],
            "database_user_id": st["db_user_id"],
            "install_composer_dependencies": False,
            "generate_deploy_key": False,
            "push_to_deploy": True,
        }, "create site")
        st["site_id"] = res["data"]["id"]
        mark(cfg, st, "site")
    sid = st["site_id"]
    log(f"   site id {sid}")
    wait_site_installed(sid)

    # 3. .env + deploy script
    if not step(st, "env"):
        log("3. 寫入 .env 與部署腳本")
        must("PUT", f"/sites/{sid}/environment", {"environment": env_file(cfg, st)}, "update env")
        must("PUT", f"/sites/{sid}/deployments/script", {"content": DEPLOY_SCRIPT}, "update deploy script")
        mark(cfg, st, "env")

    # 4. first deploy
    if not step(st, "deploy"):
        log("4. 部署")
        res = must("POST", f"/sites/{sid}/deployments", what="deploy")
        dep = res["data"]["id"]
        status = wait_deploy(sid, dep)
        if status != "finished":
            code, out = forge("GET", f"/sites/{sid}/deployments/{dep}/log")
            log(json.dumps(out, ensure_ascii=False)[-3000:] if code == 200 else f"(log HTTP {code})")
            die(f"deployment {dep} {status}")
        mark(cfg, st, "deploy")

    # 5. queue worker (server-level) + scheduler (site-level)
    cur = f"{site_dir(cfg)}/current"
    if not step(st, "worker"):
        log("5. 建 queue worker")
        v = cfg["php_version"].replace("php", "")
        php = f"php{v[0]}.{v[1:]}"
        must("POST", "/background-processes", {
            "name": f"{d} queue",
            "command": f"{php} {cur}/artisan queue:work 'database' --sleep=3 --daemon --quiet "
                       f"--timeout=90 --backoff=60 --tries=3",
            "user": "forge", "directory": cur + "/", "processes": 1,
        }, "create worker")
        mark(cfg, st, "worker")
    if not step(st, "scheduler"):
        log("6. 建排程")
        v = cfg["php_version"].replace("php", "")
        must("POST", f"/sites/{sid}/scheduled-jobs", {
            "name": f"{d} schedule",
            "command": f"php{v[0]}.{v[1:]} {cur}/artisan schedule:run",
            "user": "forge", "frequency": "minutely",
        }, "create scheduler")
        mark(cfg, st, "scheduler")

    # 6. SSL (needs site:meta scope to look up the domain record)
    if not step(st, "ssl"):
        log("7. 申請 SSL")
        code, res = forge("GET", f"/sites/{sid}/domains")
        if code != 200:
            log(f"   [!] 查不到網域紀錄（HTTP {code}，token 缺 site:meta scope）。"
                f"請到 Forge → 站台 → Domains → {d} → SSL → Let's Encrypt 手動按一下。")
            st["ssl_manual"] = True
            save(cfg, st)
        else:
            rec = next((x for x in res.get("data", []) if x["attributes"].get("name") == d), None)
            if not rec:
                die(f"domain record for {d} not found: {json.dumps(res)[:400]}")
            must("POST", f"/sites/{sid}/domains/{rec['id']}/certificates", {
                "type": "letsencrypt",
                "letsencrypt": {"verification_method": "http-01", "key_type": "ecdsa"},
            }, "create certificate")
            mark(cfg, st, "ssl")

    log("\nRUN OK — 接著執行 verify")


# ---------------------------------------------------------------- verify

def verify(cfg):
    st = state(cfg)
    d = cfg["domain"]
    rows = []

    def check(name, ok, detail=""):
        rows.append(ok)
        log(f"[{'ok' if ok else 'X'}] {name} {detail}")

    # HTTPS may need a minute after the certificate is issued.
    code = 0
    for _ in range(12):
        code, body = http_get(f"https://{d}/")
        if code == 200:
            break
        time.sleep(10)
    check("https 首頁", code == 200, f"HTTP {code}")
    check("頁面帶 LessonKit 版本", code == 200 and "lessonkit" in body)
    for p in ("/login", "/robots.txt", "/sitemap.xml"):
        c, _ = http_get(f"https://{d}{p}")
        check(p, c == 200, f"HTTP {c}")
    redirect = sh(f"curl -s -o /dev/null -w '%{{http_code}}' http://{d}/")
    check("http → https 轉址", redirect in ("301", "302", "308"), redirect)
    expiry = sh(f"echo | openssl s_client -servername {d} -connect {d}:443 2>/dev/null "
                f"| openssl x509 -noout -issuer -enddate 2>/dev/null")
    check("SSL 憑證", "notAfter" in expiry, expiry.replace("\n", " | "))

    cur = f"{site_dir(cfg)}/current"
    php = ('echo "LK=" . json_encode(["users" => App\\Models\\User::count(), '
           '"templates" => DB::table("email_templates")->count()]) . PHP_EOL;')
    p = subprocess.run(["ssh", "-o", "BatchMode=yes", SSH, f"cd {cur} && php artisan tinker"],
                       input=php, capture_output=True, text=True)
    m = re.search(r"LK=(\{.*\})", p.stdout)
    if m:
        info = json.loads(m.group(1))
        check("資料庫可連線", True, json.dumps(info))
        check("全新資料庫（0 位使用者）", info["users"] == 0, f"users={info['users']}")
        check("Email 範本已建立", info["templates"] > 0, f"templates={info['templates']}")
    else:
        check("資料庫可連線", False, (p.stdout + p.stderr)[-300:])
    pending = sh(f"ssh -o BatchMode=yes {SSH} 'cd {cur} && php artisan migrate:status' | grep -c Pending")
    check("migration 全部跑完", pending.strip() == "0", f"pending={pending or 0}")

    link = sh(f"ssh -o BatchMode=yes {SSH} 'readlink {cur}/storage; readlink {cur}/public/storage'")
    check("storage 共用目錄", "storage" in link, link.replace("\n", " | "))
    env = sh(f"ssh -o BatchMode=yes {SSH} \"grep -E '^(APP_ENV|APP_DEBUG|APP_URL|MAIL_MAILER|MAIL_FROM_ADDRESS|FIRST_ADMIN_EMAIL)=' {site_dir(cfg)}/.env\"")
    check(".env 關鍵值", "APP_ENV=production" in env and "APP_DEBUG=false" in env, env.replace("\n", " | "))

    procs = must("GET", "/background-processes").get("data", [])
    w = [x for x in procs if cur in x["attributes"].get("command", "")]
    check("queue worker", bool(w) and w[0]["attributes"].get("status") == "running",
          f"{w[0]['id']} {w[0]['attributes'].get('status')}" if w else "沒有")
    sid = st.get("site_id") or cfg.get("site_id")
    if sid:
        jobs = must("GET", f"/sites/{sid}/scheduled-jobs").get("data", [])
        check("排程", bool(jobs), ", ".join(f"{j['id']} {j['attributes'].get('status')}" for j in jobs) or "沒有")

    failed = rows.count(False)
    log(f"\nVERIFY {'OK' if not failed else f'有 {failed} 項失敗'}")
    return failed == 0


if __name__ == "__main__":
    if len(sys.argv) != 3 or sys.argv[1] not in ("preflight", "run", "verify"):
        print(__doc__)
        sys.exit(2)
    c = load(sys.argv[2])
    if sys.argv[1] == "preflight":
        sys.exit(0 if preflight(c) else 1)
    if sys.argv[1] == "run":
        run(c)
    else:
        sys.exit(0 if verify(c) else 1)
