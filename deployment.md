# Hostinger deployment guide

Production deploy notes for the Exam Management System on Hostinger (shared hosting or VPS). Local setup remains in [`README.md`](README.md).

---

## Server requirements

| Component | Requirement |
|-----------|-------------|
| PHP | **8.2+** (8.3 recommended) |
| Composer | **2.x** |
| Node.js | **18+** (needed only to build frontend assets; can build locally and upload `public/build`) |
| Database | MySQL 8 / MariaDB 10.4+ |
| Web server | Apache with `mod_rewrite` and `mod_headers` (typical on Hostinger) |

### Required PHP extensions

Enable (or confirm present) at least:

`openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `curl`

Also ensure `pdo_mysql` (or the PDO driver matching your database) is available.

---

## Document root

**The document root MUST be `public/`.**

Examples:

- Hostinger “public_html” → point the domain (or subdomain) document root to `…/exam-management-system/public`
- Or upload the Laravel app one level above `public_html` and set the domain root to that app’s `public` folder

If the host forces the domain root to the project root (not `public/`), keep the root [`.htaccess`](.htaccess) in place. It rewrites all traffic into `public/` and blocks sensitive paths (`app/`, `.env`, `vendor/`, etc.). Prefer a true `public/` document root when the panel allows it.

Never expose `vendor/`, `.env`, `storage/`, or application source as web-accessible paths.

---

## Production `.env` settings

Copy from `.env.example`, then set at least:

```env
APP_NAME="Exam Management System"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...          # php artisan key:generate
APP_URL=https://your-domain.com

LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1           # or Hostinger’s DB host
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true

CACHE_STORE=database

# Shared hosting without a long-running worker:
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=465               # or 587 with STARTTLS per host docs
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"

GALLERY_DISK=public
```

Notes:

- Keep **`APP_DEBUG=false`** in production so errors and internals stay hidden.
- `APP_URL` must be the real HTTPS origin (no trailing path). SEO files and asset URLs depend on it.
- After changing `APP_URL`, regenerate SEO artifacts with `php artisan seo:generate` (also available under Admin → Settings → SEO). Cron refreshes them daily once the scheduler is set.
- With `QUEUE_CONNECTION=sync`, jobs run inline in the web/CLI process — **no Supervisor / `queue:work` process is required**.
- Mail can also be managed later under **Admin → Settings → Email Configuration**; `.env` values are the baseline for first deploy.
- Panel roles: Application Admin (`admin`) and Organization Admin (`org_admin`) currently share **full** panel access (capability split deferred). See [`public/docs/backend.html`](public/docs/backend.html).

---

## Storage link and permissions

```bash
php artisan storage:link
```

That creates `public/storage` → `storage/app/public` so gallery and public uploads resolve.

Make these writable by the PHP user (typical Hostinger: `755`/`775` dirs, or panel “writable” flags):

- `storage/` (and all subdirs)
- `bootstrap/cache/`

If uploads or cache writes fail, check ownership and permissions on those paths first.

---

## Deploy steps

### 1. Upload the application

Upload the project (Git clone, SFTP, or Hostinger File Manager). Prefer keeping `.env` out of the upload and creating it on the server.

Exclude from production uploads when possible: `node_modules/`, local `.env`, IDE folders, and (if you build assets elsewhere) skip shipping a full Node toolchain.

### 2. Install PHP dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Frontend assets

**On the server** (if Node is available):

```bash
npm ci
npm run build
```

**Or build locally** and upload the generated `public/build/` directory (and keep `public/hot` absent in production).

### 4. Environment and key

```bash
# Ensure .env exists with production values
php artisan key:generate   # only if APP_KEY is empty
```

### 5. Database migrate

```bash
php artisan migrate --force
```

Do **not** run `--seed` against live data (demo seeders clear/rebuild sample media).

### 6. Storage, optimize, SEO

```bash
php artisan storage:link
php artisan optimize
php artisan seo:generate
```

`seo:generate` writes sitemap/robots-related files using the current `APP_URL`. Re-run it whenever the production URL or SEO settings change. The scheduler also runs it daily (see Cron).

### 7. Smoke check

- Open `https://your-domain.com/`
- Sign in as admin (`/admin`) and as a candidate (`/account`)
- Confirm gallery images load (`/storage/...`)
- Confirm `/robots.txt` / sitemap URLs reflect the HTTPS domain

---

## LLM Management & AI SEO Setup

LLM configurations are managed in the database via **Admin Panel → Settings → LLM Management**.

- **Supported Providers**: Mistral AI (Default Priority 1), Groq (Priority 2), Google Gemini (Priority 3), OpenRouter (Priority 4).
- **Multi-Account & Priority Cascading**: Administrators can register multiple accounts for each provider. The system automatically routes requests to the highest priority active account.
- **Automatic Failover & Cooldown**: If an API request encounters a rate limit, quota error, timeout, or authentication error, the account is temporarily placed in a 24-hour cooldown state, and the queue seamlessly fails over to the next available account.
- **Scheduler Auto-Reactivation**: The scheduler automatically reactivates accounts once their 24-hour cooldown expires and resets daily request counters at midnight.

---

## Queue & Scheduler Setup

### 1. Cron (Scheduler)

In Hostinger → Cron Jobs (or SSH crontab), run every minute:

```cron
* * * * * cd /path/to/exam-management-system && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/path/to/exam-management-system` with the real app root (the folder that contains `artisan`).

Scheduled work includes:
- Daily sitemap generation (`seo:generate`)
- Asynchronous SEO batch processing (`llm:process-seo`)
- LLM Account cooldown auto-reactivation and daily usage counter resets.

### 2. Queue Worker

For async SEO queue processing in production:

```bash
php artisan queue:work --queue=llm-seo,default --tries=3 --timeout=120
```

With `QUEUE_CONNECTION=sync` on shared hosting, jobs execute inline in-process.

---

## SSL and security headers

1. Enable Hostinger SSL (Let’s Encrypt) for the domain and force HTTPS in the panel when available.
2. Set `APP_URL=https://...` and `SESSION_SECURE_COOKIE=true`.
3. Security headers are already configured in [`public/.htaccess`](public/.htaccess) when `mod_headers` is enabled (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, etc.).
4. Optional HTTPS redirect rules are commented in `public/.htaccess` — enable them if the panel does not already force HTTPS.

---

## Backup recommendations

| What | Frequency | Notes |
|------|-----------|--------|
| MySQL database | Daily (or Hostinger automatic backups) | Primary restore source |
| `.env` | After each change | Store securely off-server |
| `storage/app/public` | Daily / with DB | Gallery and public uploads |
| Application code | Via Git tags/releases | Prefer redeploy over filesystem-only backups |

Test a restore once before go-live. Do not rely solely on `storage/` copies without the database.

---

## Troubleshooting

| Symptom | Likely cause | What to check |
|---------|--------------|---------------|
| HTTP 500 | Misconfigured env, missing extensions, permissions | `storage/logs/laravel.log`; `APP_DEBUG` only temporarily; PHP version ≥ 8.2 |
| Blank / white page | Fatal error, wrong docroot, failed autoload | Document root = `public/`; `composer install`; PHP error log |
| CSS/JS missing | Assets not built | `public/build` present; run `npm run build` or upload build output |
| Images 404 | Missing storage link | `php artisan storage:link`; confirm `public/storage` exists |
| Permission errors | Non-writable dirs | `storage/`, `bootstrap/cache/` writable by PHP |
| Session / login issues on HTTPS | Insecure cookies | `SESSION_SECURE_COOKIE=true`, correct `APP_URL` |
| “No application encryption key” | Empty `APP_KEY` | `php artisan key:generate` |
| Wrong hosts in sitemap/robots | Stale SEO files or wrong URL | Set `APP_URL`, then `php artisan seo:generate` |
| Sensitive files downloadable | Docroot not `public/` | Fix panel document root; keep root `.htaccess` as fallback |

Temporary debugging: set `APP_DEBUG=true` only long enough to read the error, then set it back to `false` immediately.

---

## Post-deploy SEO note

After the first successful production deploy (and any later `APP_URL` change):

```bash
php artisan seo:generate
```

Admin → Settings → SEO can also regenerate files from the UI (Application Admin / Organization Admin with organization capability). Cron will refresh them daily once the scheduler is configured.

Also confirm `APP_DEBUG=false` and that demo seed accounts are not used on live data. Role and capability details: [`public/docs/backend.html`](public/docs/backend.html).
