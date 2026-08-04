# Exam Management System

Laravel 11 platform for organization-scoped question banks, exam authoring, content publishing, media management, and a public learning website (Examtube.in).

| Resource | Link |
|----------|------|
| **Client frontend delivery** | [`public/docs/frontend.html`](public/docs/frontend.html) (or `/docs/frontend.html`) |
| **Backend & admin (RBAC)** | [`public/docs/backend.html`](public/docs/backend.html) (or `/docs/backend.html`) |
| **Full technical guide** | Open [`public/docs/index.html`](public/docs/index.html) (or `/docs/` when the app is running) |
| **Hostinger / production deploy** | [`deployment.md`](deployment.md) |
| **Product backlog** | [`TODO.md`](TODO.md) |

---

## Requirements

| Component | Version / notes |
|---|---|
| PHP | 8.2+ (tested on 8.3) with common Laravel extensions (openssl, pdo, mbstring, tokenizer, xml, ctype, json, fileinfo, gd) |
| Composer | 2.x |
| Node.js | 18+ and npm |
| Database | MySQL 8 / MariaDB 10.4+ (SQLite is fine for automated tests) |
| Web server | Apache, Nginx, WAMP, or `php artisan serve` |

---

## Quick setup

### 1. Install PHP and Node dependencies

```bash
composer install
npm install
```

### 2. Environment file

```bash
# macOS / Linux / Git Bash
cp .env.example .env

# Windows Command Prompt
copy .env.example .env
```

```bash
php artisan key:generate
```

### 3. Configure `.env`

Minimum values:

```env
APP_NAME="Exam Management System"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=exam-management-system
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log

GALLERY_DISK=public
```

Create the MySQL database named in `DB_DATABASE` before migrating.

### 4. Database, storage, and demo data

```bash
php artisan migrate --seed
php artisan storage:link
```

> **Seeder warning:** Full reseed clears demo upload directories under `storage/app/public` and rebuilds sample media. Do **not** run `--seed` against production uploads.

### 5. Build frontend assets

```bash
npm run build          # production
npm run dev            # Vite watch (development)
```

### 6. Run the app

**Laravel development server**

```bash
php artisan serve
```

Open `http://127.0.0.1:8000`.

**All local processes** (HTTP, queue, logs, Vite):

```bash
composer run dev
```

**WAMP / Apache:** point the vhost at `public/`, set `APP_URL` accordingly, run `npm run build` (or `npm run dev`), and optionally `php artisan queue:work`.

---

## Demo accounts

After seeding, password for all accounts is `password`:

| Email | Role | Entry |
|---|---|---|
| `admin@examtube.in` | Application Admin | `/admin` |
| `info@examtube.in` | Organization Admin | `/admin` |
| `candidate@examtube.in` | Candidate | `/account` |

Admin panel access is limited to Application Admin and Organization Admin roles only.

**Admin panel roles:** Application Admin (`admin`) and Organization Admin (`org_admin`) currently have **the same full panel access**. Capability hooks (`content` / `organization` / `platform`) are in place for a future split — see [`/docs/backend.html`](public/docs/backend.html). Model policies still enforce organization ownership.

Seeded public contact defaults: `support@examtube.in`, phone `+91 0000000000`, address `Mumbai Dock Yard`.

---

## What works today

### Public website

- Home, exams, blogs, news, categories, questions, FAQs, search, authors, CMS pages
- Contact form and newsletter (rate-limited)
- Light / dark / system theme
- Full SEO meta, Open Graph / Twitter cards, and branded default share images (`public/frontend/images/seo/`)

### Candidate experience

- Account dashboard, profile (with crop/zoom/rotate avatar editor), settings, results, activity
- Exam rules, prepare, live runner (timer, autosave, proctoring hooks)
- Multi-part question palette with paging; rich text for long answers
- Attempt submit and result views

### Admin

- Organization-scoped dashboard
- Questions (CRUD, categories, XLSX/CSV import)
- Exams (authoring, assignment modes, publish)
- Blog and news publishing
- Gallery media library
- Settings: maintenance, SEO files, branding, email/SMTP, analytics, cookie consent, reCAPTCHA, security, feature flags

Still unfinished (see `TODO.md`): organization switching UI, real notifications/logs, real payment gateway (purchase remains a demo placeholder), splitting admin vs org_admin privileges, Editor/Viewer RBAC, and CI. Policy foundations are in place (`/docs/backend.html`).

---

## LLM / AI SEO

Provider-based architecture (`config/llm.php`). The app talks only to `App\Services\Llm\LlmService` — never to Groq/OpenRouter/Gemini directly.

### Switch providers (no code changes)

```env
LLM_PROVIDER=groq          # groq | openrouter | gemini
GROQ_API_KEY=...
GROQ_MODEL=llama-3.3-70b-versatile
# or
OPENROUTER_API_KEY=...
OPENROUTER_MODEL=openai/gpt-4o-mini
# or
GEMINI_API_KEY=...
GEMINI_MODEL=gemini-2.0-flash
```

Shared knobs: `LLM_BATCH_SIZE` (default 6), `LLM_TIMEOUT`, `LLM_RETRY`, `LLM_TEMPERATURE`, `LLM_MAX_TOKENS`.

### How SEO generation works

1. Create forms default **Create with AI = on**, **Improve with AI = off** (UI only; DB column default remains `false`)
2. Record saves immediately (`ai_generated` / `ai_improve` flags only)
3. Scheduler runs `llm:process-seo` every few minutes
4. A batch of ~5–6 pending rows is processed in **one** LLM request
5. SEO fields are updated and `is_ai_generated = true`
6. Question Excel import sets `ai_generated = true` automatically

Manual sync (no queue worker needed):

```bash
php artisan llm:process-seo --sync
```

Queue worker (when `QUEUE_CONNECTION=database`):

```bash
php artisan queue:work --queue=llm-seo,default
```

Logs: `storage/logs/llm-*.log` (provider, model, timing, tokens, retries, errors).

`is_sitemap_url_created` tracks whether a record’s URL was written by `seo:generate` so future incremental sitemap builds can skip already-indexed rows.

OG images are **not** generated by the LLM — existing default / gallery OG images are reused.
---

## Important URLs

| URL | Purpose |
|---|---|
| `/` | Public home |
| `/exams`, `/blogs`, `/news`, `/categories` | Public listings |
| `/login` | Sign in |
| `/account` | Candidate account |
| `/admin` | Admin dashboard |
| `/admin/questions` | Question bank + import |
| `/admin/exams` | Exam management |
| `/admin/gallery` | Media library |
| `/admin/settings` | Settings shell |
| `/docs/` | Technical documentation (when served) |

---

## Question import (admin)

1. **Admin → Questions → Import Questions**
2. Download a sample template if needed
3. Upload `.xlsx` or `.csv` (max **15 MB**, max **10,000** rows, first Excel sheet only)
4. Fix validation issues in the editable preview
5. Import in AJAX batches of **100** — keep the window open until completion

Required columns: Question, Type, Category, Difficulty, Marks Type, Marks.  
Nested categories use paths like `Development > PHP > Laravel`.

---

## Common commands

```bash
php artisan test
php artisan test --filter=QuestionImport
vendor/bin/pint

npm run build
npm run dev

php artisan about
php artisan route:list --except-vendor
php artisan migrate:status
php artisan optimize:clear
php artisan optimize          # after production .env is set
```

---

## Storage notes

| Kind | Disk | Notes |
|---|---|---|
| Gallery images/files | `GALLERY_DISK` (default `public`) | Requires `php artisan storage:link` |
| Question import sources | `local` (private) | Admin-only download routes |
| Profile avatars | Public storage / gallery | Cropped upload from account or admin profile |
| Default SEO images | `public/frontend/images/seo/*.png` | Deploy with the app; used when content has no image |

If gallery images 404, re-run `php artisan storage:link` and confirm `APP_URL` matches the URL you browse.

---

## Testing

```bash
php artisan test
```

Coverage includes auth, profile, questions, imports, exams, attempts, categories, blogs, news, gallery, editor media, and public frontend pages.

---

## Production checklist (short)

Full Hostinger steps, cron, permissions, and troubleshooting: **[`deployment.md`](deployment.md)**.

1. `APP_ENV=production`, `APP_DEBUG=false`, real `APP_KEY`, HTTPS `APP_URL`, `SESSION_SECURE_COOKIE=true`
2. Production database, mail, cache, session; `QUEUE_CONNECTION=sync` on shared hosting (no Supervisor)
3. `composer install --no-dev --optimize-autoloader`
4. `npm ci && npm run build` (or upload `public/build`)
5. `php artisan migrate --force` (**no** demo seed on live data)
6. `php artisan storage:link`
7. `php artisan optimize` and `php artisan seo:generate`
8. Cron: `* * * * * cd /path && php artisan schedule:run`
9. Document root = `public/` only
10. Walk through the client checklist in [`public/docs/frontend.html`](public/docs/frontend.html) §9

---

## Documentation map

| File | Purpose |
|---|---|
| [`README.md`](README.md) | Setup and developer entry point |
| [`deployment.md`](deployment.md) | Hostinger / production deployment |
| [`public/docs/frontend.html`](public/docs/frontend.html) | Client-friendly frontend delivery summary (`/docs/frontend.html`) |
| [`public/docs/backend.html`](public/docs/backend.html) | Admin roles, capabilities, policies (`/docs/backend.html`) |
| [`public/docs/index.html`](public/docs/index.html) | Complete technical guide |
| [`TODO.md`](TODO.md) | Remaining product work |

Organization context resolves from the authenticated user’s first active membership (with a first-organization fallback for CLI/guests). A validated multi-org switcher is future work.
