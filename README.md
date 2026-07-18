# Exam Management System

A Laravel 11 application for organization-scoped question banks, exam authoring, content publishing, media management, and a public exam/content website.

**Full reference:** open [`public/docs/index.html`](public/docs/index.html) in a browser (or `/docs/` on a running app) for architecture, modules, workflows, APIs, testing, deployment, security, and troubleshooting.

**Backlog:** [`TODO.md`](TODO.md)

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

Generate the application key:

```bash
php artisan key:generate
```

### 3. Configure `.env`

Minimum values to set:

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
GALLERY_MAX_IMAGE_KB=5120
GALLERY_MAX_VIDEO_KB=51200
GALLERY_MAX_FILE_KB=20480
```

Create the MySQL database named in `DB_DATABASE` before migrating.

### 4. Database, storage, and demo data

```bash
php artisan migrate --seed
php artisan storage:link
```

> **Seeder warning:** `DatabaseSeeder` runs `ClearUploadedMediaSeeder` first and wipes configured demo upload directories under `storage/app/public` before rebuilding sample media. Do **not** run a full reseed against production uploads.

### 5. Build frontend assets

```bash
# Production / one-shot build
npm run build

# Or keep Vite watching during development
npm run dev
```

### 6. Run the app

**Option A — Laravel development server**

```bash
php artisan serve
```

Then open `http://127.0.0.1:8000`.

**Option B — All local processes together** (HTTP server, queue worker, log tail, Vite)

```bash
composer run dev
```

**Option C — WAMP / Apache**

1. Point the vhost (or `http://localhost/exam-management-system/public`) at the `public/` directory.
2. Set `APP_URL` to that public URL.
3. Run `npm run dev` (or `npm run build`) for assets.
4. Optionally run `php artisan queue:work` if you need queued jobs.

---

## Demo accounts

After seeding, all of these use password `password`:

| Email | Role label | Typical entry point |
|---|---|---|
| `admin@examms.test` | Admin | `/admin` |
| `orgadmin@examms.test` | Org Admin | `/admin` |
| `editor@examms.test` | Editor | `/admin` |
| `student@examms.test` | Viewer | `/account` |

Role labels are stored on organization memberships. Backend routes currently require authentication only; role middleware/policies are still on the roadmap. Do not treat these accounts as a production authorization model.

---

## What works today

- Authentication (login, register, password reset, email verification), profiles, avatars
- Organization-scoped admin dashboard with live stats
- Questions: CRUD, hierarchical categories, bulk actions, filters, tracked XLSX/CSV import
- Exams: CRUD, categories, question-bank APIs, fixed/pool/dynamic assignment, publish, attempt start snapshots
- Blog and news publishing with categories, tags, banners, attachments, and public pages
- Gallery media library (upload, edit, recycle bin, restore, permanent delete)
- Public CMS home, pages, search, contact, newsletter
- SEO metadata and gallery-backed Open Graph images across supported modules
- Light / dark / system theme

Still unfinished (see `TODO.md`): candidate answer submission/scoring, role-based route guards, organization switching UI, candidates/notifications/logs modules, richer settings/CMS admin.

---

## Important URLs

| URL | Purpose |
|---|---|
| `/` | Public home |
| `/exams`, `/blogs`, `/news` | Public listings |
| `/login` | Sign in |
| `/account` | Authenticated frontend account area |
| `/admin` | Admin dashboard |
| `/admin/questions` | Question bank + import |
| `/admin/exams` | Exam management |
| `/admin/gallery` | Media library |
| `/admin/blogs`, `/admin/news` | Content publishing |
| `/admin/settings` | Cache clear and settings shell |

---

## Question import (admin)

1. Open **Admin → Questions → Import Questions**.
2. Download a sample template if needed.
3. Upload an `.xlsx` or `.csv` file (max **15 MB**, max **10,000** rows, first Excel sheet only).
4. Fix validation issues in the editable preview.
5. Import — rows are sent in AJAX batches of **100**. Keep the window open until completion.

The original file is stored privately (local disk) and linked via `import_questions`. Each imported question stores `import_question_id`. The question list can filter All / Imported / Manual and open import details from the source badge.

Required columns: Question, Type, Category, Difficulty, Marks Type, Marks.  
Conditional: Option A/B for MCQ, Correct Answer / Correct Answers.  
Optional: Option C–F, Explanation, Reference, Status.

Nested categories use paths like `Development > PHP > Laravel`.

---

## Common commands

```bash
# Tests
php artisan test
php artisan test --filter=QuestionImport

# Code style
vendor/bin/pint

# Assets
npm run build
npm run dev

# Diagnostics
php artisan about
php artisan route:list --except-vendor
php artisan migrate:status
php artisan optimize:clear

# Production-ish cache (after configuring production .env)
php artisan optimize
```

---

## Storage notes

| Kind | Disk | Notes |
|---|---|---|
| Gallery images/files | `GALLERY_DISK` (default `public`) | Requires `php artisan storage:link` |
| Question import source files | `local` (private) | Downloaded only through authenticated admin routes |
| Profile avatars | Gallery / public storage | Cropped upload flow |

If gallery images 404, re-run `php artisan storage:link` and confirm `APP_URL` matches the site you are browsing.

---

## Testing

```bash
php artisan test
```

Feature coverage includes auth, profile, questions, imports, exams, attempt assignment, categories, blogs, news, gallery, editor media, and public frontend pages.

---

## Production checklist (short)

1. `APP_ENV=production`, `APP_DEBUG=false`, real `APP_KEY`, canonical `APP_URL`
2. Production database, mail, cache, session, and queue drivers
3. `composer install --no-dev --optimize-autoloader`
4. `npm ci && npm run build`
5. `php artisan migrate --force` (do not seed production with demo data)
6. `php artisan storage:link`
7. `php artisan optimize`
8. Persistent queue worker + scheduler cron if needed
9. Web root must be `public/` only

---

## Documentation map

| File | Purpose |
|---|---|
| [`README.md`](README.md) | Setup and day-to-day developer entry point |
| [`public/docs/index.html`](public/docs/index.html) | Complete standalone technical guide (`/docs/` when served) |
| [`TODO.md`](TODO.md) | Remaining work only |

Organization context currently resolves from the authenticated user’s first active membership (with a first-organization fallback for CLI/guests). A validated multi-org switcher is future work.
