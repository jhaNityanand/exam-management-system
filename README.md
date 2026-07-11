# Exam Management System

Laravel-based workspace for building question banks, organizing categories, and configuring exams.

## Stack

- **Backend:** Laravel 11, PHP 8.2+
- **Frontend:** Blade, Alpine.js, Tailwind CSS, Axios
- **Build:** Vite
- **Auth:** Laravel Breeze (Blade)
- **Tests:** Pest

## Features (current)

- Authentication (login, register, password reset, email verification)
- Admin workspace under `/admin`
- **Questions** — AJAX list (search, filters, pagination, per-page), create/edit/show/delete
- **Question categories** — hierarchical tree with AJAX interactions
- **Exams** — AJAX list aligned with the Question List pattern, create wizard with live question bank API, publish, edit/show/delete
- Exam create/edit option lists come from `App\Support\ExamFormOptions` (aligned with validation rules)
- **Exam categories** — hierarchical management
- **Dashboard** — org-scoped live stats, charts, and recent activity
- **Profile** & **Settings** (cache clear)
- Dark / light / system theme (see `THEMING.md`)

Candidates, notifications, and logs appear in the sidebar as placeholders (`coming-soon`).

## Requirements

- PHP 8.2+, Composer, Node.js 18+, MySQL/MariaDB (or SQLite for local testing)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure DB in .env, then:
php artisan migrate --seed
npm install
npm run build   # or: npm run dev
php artisan serve
```

Demo users (password: `password`):

| Email | Role label |
|-------|------------|
| `admin@examms.test` | Admin |
| `orgadmin@examms.test` | Org Admin |
| `editor@examms.test` | Editor |
| `student@examms.test` | Student |

The app currently runs in **single-organization mode** (`current_organization_id()`). Multi-org switching is reserved for a later phase.

## Key URLs

| Path | Description |
|------|-------------|
| `/` | Public home |
| `/login` | Sign in |
| `/admin` | Dashboard |
| `/admin/questions` | Question list (AJAX) |
| `/admin/exams` | Exam list (AJAX) |
| `/admin/settings` | Settings |

## AJAX patterns

**Lists (Questions / Exams)** share `public/js/backend/ajax-table.js`:

1. Blade renders chrome (search, per-page, filter drawer, empty table body)
2. Named routes are injected as `window.*ApiUrl`
3. Module JS supplies row templates and domain hooks
4. JSON comes from `admin/internal-api/{questions,exams}-table`

**Category trees** share `public/js/backend/category-tree.js`, configured via `window.categoryTreeConfig` (`indexUrl`, `detailsBaseUrl`, `linkedResourceLabel`).

## Tests

```bash
php artisan test
```

## Docs

- `README.md` — this file
- `THEMING.md` — theme system
- `TODO.md` — backlog / roadmap
