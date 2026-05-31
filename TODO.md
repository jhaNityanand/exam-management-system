# Exam Management System — TODO

> Last reviewed: 2026-05-31
> Stack: Laravel 11 · Blade · Tailwind CSS v3 · Alpine.js · PestPHP

---

## Legend
- ✅ Done
- ⬜ Not started
- 🔧 Partially done / stubbed

---

## 1. Project Foundation & Setup

- ✅ Laravel 11 project scaffolded
- ✅ Laravel Breeze installed (session-based auth)
- ✅ Tailwind CSS v3 + Alpine.js + Vite configured
- ✅ Chart.js, Tom Select, SweetAlert2 integrated
- ✅ PestPHP v4 configured for testing
- ✅ SQLite dev database configured
- ✅ `.env.example` provided
- ✅ `config/organization.php` — organization session key config
- ✅ `app/helpers.php` registered via composer autoload (file exists but is empty)
- ⬜ Define `current_organization_id()` helper in `app/helpers.php` (used everywhere but not yet implemented)

---

## 2. Database — Migrations

- ✅ `users` table (with `status`, soft deletes)
- ✅ `cache` and `jobs` tables
- ✅ `organizations` table (name, slug, description, status)
- ✅ `categories` table (org-scoped, soft deletes)
- ✅ `exams` table (org-scoped, soft deletes)
- ✅ `questions` table (org-scoped, soft deletes)
- ✅ `user_organizations` pivot table (role, status)
- ✅ `exam_attempts` table (score, passed, answers JSON, timestamps)
- ✅ `profiles` table (1:1 with users, shared PK)
- ✅ `system_settings` table (key-value store)
- ✅ Extension migration — adds: `user_id` to orgs, `parent_id` to categories, `correct_answers`/`allows_multiple` to questions, scheduling/shuffle/mode fields to exams, `exam_question` pivot table, address fields to profiles, `user_app_settings` table, `default_organization_id` to profiles
- ✅ `social_links` column added to profiles
- ⬜ Migration for `status` column on `users` table (referenced in seeders but not in base migration)
- ⬜ Indexes on frequently queried foreign keys (organization_id, category_id, created_by) for performance

---

## 3. Database — Seeders & Factories

- ✅ `UserSeeder` — creates `admin@examms.test` and `student@examms.test` (password: `password`)
- ✅ `ProfileSeeder` — seeds profile records
- ✅ `OrganizationSeeder` — creates "Demo Organization"
- ✅ `UserOrganizationSeeder` — assigns users to org with roles
- ✅ `CategorySeeder` — seeds demo categories
- ✅ `QuestionSeeder` — seeds demo questions
- ✅ `ExamSeeder` — seeds demo exams
- ✅ `DatabaseSeeder` — orchestrates all seeders with login info output
- ✅ `UserFactory` — Faker-based user factory
- ⬜ `OrganizationFactory`
- ⬜ `CategoryFactory`
- ⬜ `QuestionFactory`
- ⬜ `ExamFactory`
- ⬜ `ExamAttemptFactory`

---

## 4. Models & Relationships

- ✅ `User` — relationships: profile, appSettings, organizations (BelongsToMany), questions, exams, examAttempts; `belongsToOrganization()` helper
- ✅ `Profile` — relationships: user, defaultOrganization; social_links cast; soft deletes; audit trail
- ✅ `UserAppSetting` — theme, sidebar_collapsed, preferences (JSON)
- ✅ `Organization` — relationships: owner, users (BelongsToMany), exams, categories, questions; soft deletes; audit trail
- ✅ `UserOrganization` — pivot model with role, status; soft deletes; audit trail
- ✅ `Category` — hierarchical (parent/children), org-scoped; cascade soft-delete to children + questions; scopes: `roots`, `forOrg`; audit trail
- ✅ `Question` — types: mcq/true_false/short_answer; JSON options + correct_answers; scopes: `forOrg`, `byDifficulty`; audit trail
- ✅ `Exam` — scheduling, negative marking, shuffle, exam_mode, category_question_rules (JSON); scopes: `published`, `draft`, `forOrg`; audit trail
- ✅ `ExamAttempt` — answers snapshot (JSON), score, passed, timestamps; soft deletes; audit trail
- ✅ `SystemSetting` — simple key-value model
- ✅ `HasAuditTrails` trait — auto-populates `created_by`, `updated_by`, `updated_by_history` on all writes

---

## 5. Authentication (Laravel Breeze)

- ✅ User registration
- ✅ User login / logout
- ✅ Forgot password (email link)
- ✅ Reset password
- ✅ Email verification (prompt + verify routes)
- ✅ Password confirmation
- ✅ Password change (authenticated)
- ⬜ Redirect after login based on user role (currently all go to `/admin/dashboard`)
- ⬜ Restrict registration — admin-invite-only or disable public registration

---

## 6. Authorization & Middleware

> **Single-org mode active** — org-level membership checks are bypassed. All items marked 🔒 are deferred until multi-org mode.

- ✅ `current_organization_id()` helper — **single-org mode**: always returns the first org in DB (no session, no membership check). Multi-org instructions left as comments.
- ✅ `AppServiceProvider` — **single-org mode**: always resolves `currentOrgModel` as `Organization::first()` (no session lookup).
- ✅ `CategoryController` — org ownership `abort_if` checks commented out with `// MULTI-ORG:` markers.
- ✅ `StoreCategoryRequest` / `UpdateCategoryRequest` — `parent_id` exists check has no org scope (single-org safe). Multi-org instructions left as comments.
- ✅ `StoreQuestionRequest` / `UpdateQuestionRequest` — `category_id` exists check has no org scope. Multi-org instructions left as comments.
- ✅ `ExamDataController` — now scoped to single org via `forOrg()`.
- ✅ `QuestionDataController` — `abort_if` changed to 503 (no org in DB) instead of 404 (no session).
- ⬜ 🔒 Define role constants or enum (admin / org_admin / editor / viewer)
- ⬜ 🔒 Middleware: `SetOrganizationContext` — resolve org from session, share to views
- ⬜ 🔒 Middleware: `RequireOrganization` — abort if no org in session
- ⬜ 🔒 Route-level role guards (currently all `/admin/*` routes only require `auth`)
- ⬜ 🔒 Policy: `OrganizationPolicy` (view, create, update, delete — admin only)
- ⬜ 🔒 Policy: `ExamPolicy` (scoped to org, role-based)
- ⬜ 🔒 Policy: `QuestionPolicy` (scoped to org, role-based)
- ⬜ 🔒 Policy: `CategoryPolicy` (scoped to org, role-based)
- ⬜ 🔒 Policy: `UserOrganizationPolicy` (manage members — org_admin only)

---

## 7. Services Layer

- ✅ `CategoryService` — create, update, delete, paginateForOrg, treeForOrg (3 levels), listForSelect
- ✅ `ExamService` — create, update, delete, publish, syncQuestions (with sort_order), getByOrganization, getStats, getAttemptStats
- ✅ `QuestionService` — create, update, delete, getByOrganization, getCategoriesForOrg, getStats, normalizeAnswers, normalizeOptionsFromRequest
- ✅ `OrganizationService` — create (auto-slug), update, delete, assignUser, removeUser, getAll, getGlobalStats
- ✅ `DashboardService` — role-specific stats: adminStats, orgAdminStats, editorStats, viewerStats
- ✅ `ProfileAvatarService` — storeFromBase64, delete (with type/size validation)
- ⬜ `ExamAttemptService` — start attempt, save answers, submit, calculate score, check pass/fail
- ⬜ `NotificationService` — create, mark-as-read, list for user
- ⬜ `ActivityLogService` — record and retrieve audit log entries for the log viewer

---

## 8. Form Requests (Validation)

- ✅ `LoginRequest` — email, password, rate limiting
- ✅ `ProfileUpdateRequest` — name, email, phone, bio, address fields, social_links, avatar
- ✅ `StoreCategoryRequest` — name, description, status, parent_id (org-scoped exists check)
- ✅ `UpdateCategoryRequest` — same rules as store
- ✅ `StoreExamRequest` — title, duration, pass_percentage, max_attempts, scheduling, shuffle, mode, question_ids
- ✅ `UpdateExamRequest` — same rules as store
- ✅ `StoreQuestionRequest` — body, type, allows_multiple, options, correct_answer(s), marks, difficulty, category_id (org-scoped)
- ✅ `UpdateQuestionRequest` — same rules as store
- ✅ `StoreOrganizationRequest` — name, slug (unique), description, status, logo, banner
- ✅ `UpdateOrganizationRequest` — same with unique-ignore on current record
- ⬜ `StoreUserRequest` — for admin user creation
- ⬜ `UpdateUserRequest` — for admin user editing
- ⬜ `AssignMemberRequest` — for adding a user to an organization with a role

---

## 9. Controllers — Backend

### Dashboard
- 🔧 `DashboardController@index` — renders view but does NOT pass stats from `DashboardService` (view uses static/dummy data)
- ⬜ Wire `DashboardController@index` to `DashboardService` with role detection

### Categories
- ✅ `CategoryController@index` — renders view (datatable loads via JS)
- ✅ `CategoryController@create` — renders form (parents list currently empty/dummy)
- ✅ `CategoryController@store` — fully wired to `CategoryService`, saves to DB
- ✅ `CategoryController@edit` — loads real category + parent list, org-scoped
- ✅ `CategoryController@update` — fully wired to `CategoryService`
- ✅ `CategoryController@destroy` — fully wired to `CategoryService`
- ⬜ Fix `CategoryController@create` — load real parent categories for the select dropdown

### Questions
- ✅ `QuestionController@index` — renders view (datatable loads via JS)
- ✅ `QuestionController@create` — renders form view
- 🔧 `QuestionController@store` — **Dummy Mode** (redirects without saving)
- ✅ `QuestionController@show` — renders show view
- ✅ `QuestionController@edit` — renders edit view
- 🔧 `QuestionController@update` — **Dummy Mode** (redirects without saving)
- 🔧 `QuestionController@destroy` — **Dummy Mode** (redirects without deleting)
- ⬜ Wire `QuestionController@store` to `QuestionService::create()` using `StoreQuestionRequest`
- ⬜ Wire `QuestionController@update` to `QuestionService::update()` using `UpdateQuestionRequest`
- ⬜ Wire `QuestionController@destroy` to `QuestionService::delete()`
- ⬜ Load real question data in `QuestionController@show` and `@edit`
- ⬜ Pass org-scoped categories to `create` and `edit` views

### Exams
- ✅ `ExamController@index` — renders view (datatable loads via JS)
- ✅ `ExamController@create` — loads categories + questions (up to 500), renders form
- 🔧 `ExamController@store` — **Dummy Mode** (redirects without saving)
- ✅ `ExamController@show` — loads real exam if exists, falls back to demo object
- ✅ `ExamController@edit` — loads real exam + categories + questions
- 🔧 `ExamController@update` — **Dummy Mode** (redirects without saving)
- 🔧 `ExamController@destroy` — **Dummy Mode** (redirects without deleting)
- 🔧 `ExamController@publish` — **Dummy Mode** (redirects without publishing)
- ⬜ Wire `ExamController@store` to `ExamService::create()` using `StoreExamRequest`
- ⬜ Wire `ExamController@update` to `ExamService::update()` using `UpdateExamRequest`
- ⬜ Wire `ExamController@destroy` to `ExamService::delete()`
- ⬜ Wire `ExamController@publish` to `ExamService::publish()`
- ⬜ Scope questions and categories in create/edit to current organization

### Organizations (Admin)
- ⬜ Create `OrganizationController` (Backend/Admin)
- ⬜ Wire `index` to `OrganizationService::getAll()`
- ⬜ Wire `create` / `store` to `OrganizationService::create()` using `StoreOrganizationRequest`
- ⬜ Wire `edit` / `update` to `OrganizationService::update()` using `UpdateOrganizationRequest`
- ⬜ Wire `destroy` to `OrganizationService::delete()`
- ⬜ Add organization routes to `web.php`
- ⬜ Handle logo/banner file uploads in store/update

### Users (Admin)
- ⬜ Create `UserController` (Backend/Admin)
- ⬜ Wire `index` — list all users with pagination
- ⬜ Wire `show` — view user profile + org memberships
- ⬜ Wire `edit` / `update` — edit user name, email, status
- ⬜ Wire `destroy` — soft-delete user
- ⬜ Add user routes to `web.php`

### Members (Org Admin)
- ⬜ Create `MemberController` (Backend)
- ⬜ Wire `index` — list members of current org
- ⬜ Wire `store` — assign user to org with role (via `OrganizationService::assignUser()`)
- ⬜ Wire `update` — change member role
- ⬜ Wire `destroy` — remove user from org (via `OrganizationService::removeUser()`)
- ⬜ Add member routes to `web.php`

### Candidates
- 🔧 `CandidateController@index` — shows `coming-soon` view
- ⬜ Implement candidate list (users with viewer role in current org + their attempt stats)

### Settings
- ✅ `SettingController@edit` — renders settings view
- ✅ `SettingController@update` — handles `clear-cache` action via `Artisan::call('optimize:clear')`
- ⬜ Expand settings to read/write `SystemSetting` key-value records
- ⬜ Add more system settings (app name, mail config, etc.)

### Notifications
- 🔧 `NotificationController@index` — shows `coming-soon` view
- ⬜ Implement notification list using Laravel's built-in notification system

### Logs / Activity
- 🔧 `LogController@index` — shows `coming-soon` view
- ⬜ Implement activity log viewer (read from `updated_by_history` or a dedicated log table)

---

## 10. Controllers — API (Internal)

- ✅ `ExamDataController` — server-side datatable JSON for exams (search, filter, sort, paginate)
- ✅ `QuestionDataController` — server-side datatable JSON for questions (org-scoped, category filter)
- ⬜ Scope `ExamDataController` to current organization (currently returns all exams)
- ⬜ Add `CategoryDataController` for category datatable (if needed)
- ⬜ Add `MemberDataController` for member list datatable

---

## 11. Controllers — Frontend (Public)

- ✅ `HomeController@index` — renders public landing page
- ⬜ Build out public landing page content (features, CTA, etc.)
- ⬜ Public exam listing page (for candidates to browse available exams)

---

## 12. Profile

- ✅ `ProfileController@edit` — loads user + profile, renders edit form
- ✅ `ProfileController@update` — updates name, email, phone, bio, address, social_links, avatar (base64 crop)
- ✅ `ProfileController@destroy` — deletes account with password confirmation
- ✅ `ProfileAvatarService` — base64 decode, type/size validation, store to `public` disk, delete old
- ⬜ Allow user to set `default_organization_id` from profile edit page
- ⬜ Show avatar preview before upload (crop UI — may already be in view, verify)

---

## 13. Organization Context (Session)

- ⬜ Implement `current_organization_id()` helper (reads `current_organization_id` from session)
- ⬜ Route/middleware to switch active organization (store org ID in session)
- ⬜ UI: organization switcher in the top nav / sidebar (already has nav orgs list in `AppServiceProvider`)
- ⬜ Validate that the session org ID belongs to the authenticated user on every request

---

## 14. Views — Backend UI

### Layouts & Partials
- ✅ `base.blade.php` — HTML shell, Vite assets, CSRF, theme init
- ✅ `app.blade.php` — full sidebar layout (collapsible, dark mode)
- ✅ `admin.blade.php` — alternative admin layout
- ✅ `navigation.blade.php` — top nav
- ✅ `panel-topbar.blade.php` — header actions bar
- ✅ `sidebar-links.blade.php` / `sidebar-top-links.blade.php` / `sidebar-bottom-links.blade.php`
- ✅ `admin-sidebar.blade.php` — super admin navigation
- ✅ `org-admin-sidebar.blade.php` — org admin navigation
- ✅ `editor-sidebar.blade.php` — editor navigation
- ✅ `viewer-sidebar.blade.php` — candidate navigation
- ✅ `org-panel-footer.blade.php`

### Dashboard
- ✅ `backend/dashboard.blade.php` — stats cards + Chart.js charts + quick actions (static/dummy data)
- ⬜ Connect dashboard to real `DashboardService` stats per role

### Categories
- ✅ `categories/index.blade.php`
- ✅ `categories/create.blade.php`
- ✅ `categories/edit.blade.php`

### Questions
- ✅ `questions/index.blade.php` — JS datatable
- ✅ `questions/create.blade.php`
- ✅ `questions/edit.blade.php`
- ✅ `questions/show.blade.php`

### Exams
- ✅ `exams/index.blade.php` — JS datatable with filter drawer
- ✅ `exams/create.blade.php` — with Question Bank Accordion component
- ✅ `exams/edit.blade.php`
- ✅ `exams/show.blade.php`

### Organizations
- ✅ `organizations/index.blade.php` (UI built, no controller wired)
- ✅ `organizations/create.blade.php` (UI built, no controller wired)
- ✅ `organizations/edit.blade.php` (UI built, no controller wired)
- ✅ `organizations/show.blade.php` (UI built, no controller wired)

### Users
- ✅ `users/index.blade.php` (UI built, no controller wired)
- ✅ `users/edit.blade.php` (UI built, no controller wired)
- ✅ `users/show.blade.php` (UI built, no controller wired)

### Members
- ✅ `members/index.blade.php` (UI built, no controller wired)

### Attempts
- ✅ `attempts/index.blade.php` (UI built, no controller wired)

### Exam Browse (Candidate)
- ✅ `exam-browse/index.blade.php` (UI built, no controller wired)

### Exam Categories (Static UI / Dummy Routes)
- ✅ `exam-categories/index.blade.php` (static dummy route)
- ✅ `exam-categories/create.blade.php` (static dummy route)
- ✅ `exam-categories/edit.blade.php` (static dummy route)
- ⬜ Remove dummy routes and wire to real controller, or merge into `CategoryController`

### Settings
- ✅ `settings/edit.blade.php`

### Other
- ✅ `backend/coming-soon.blade.php`

---

## 15. Views — Auth & Frontend

- ✅ Auth views (login, register, forgot-password, reset-password, verify-email, confirm-password)
- ✅ `frontend/home.blade.php` — public landing page
- ✅ `profile/edit.blade.php` + partials (password, delete account, profile info)
- ✅ `dashboard.blade.php` (root-level, redirects to `/admin/dashboard`)
- ✅ `welcome.blade.php` (default Laravel welcome, likely unused)

---

## 16. Blade Components

- ✅ `application-logo`
- ✅ `auth-session-status`
- ✅ `breadcrumb`
- ✅ `danger-button`
- ✅ `dropdown` + `dropdown-link`
- ✅ `input-error` + `input-label` + `text-input`
- ✅ `modal`
- ✅ `nav-link` + `responsive-nav-link`
- ✅ `page-card`
- ✅ `primary-button` + `secondary-button`
- ✅ `rich-text-editor`
- ✅ `theme-toggle`
- ✅ `AppLayout` + `GuestLayout` PHP component classes
- ⬜ `alert` / `flash-message` component (for success/error toasts)
- ⬜ `status-badge` component (reusable pill for draft/published/active/inactive)

---

## 17. JavaScript — Frontend Features

- ✅ Dark mode — system/light/dark toggle, persisted in `user_app_settings.theme`, applied via inline JS in `theme-init.blade.php`
- ✅ Collapsible sidebar — state persisted in `user_app_settings.sidebar_collapsed`
- ✅ Datatable pattern — fetch-based JSON API for exam and question list views (search, filter, sort, paginate)
- ✅ Filter drawer — slide-in filter panel on exam list (status, mode, sort)
- ✅ Question Bank Accordion — JS class for exam create page (category-grouped, animated, searchable, keyboard-navigable, ARIA)
- ⬜ Wire Question Bank Accordion to real API data (currently loads from static JSON)
- ⬜ Theme/sidebar preference persistence via AJAX (save to `user_app_settings` without page reload)
- ⬜ Organization switcher — AJAX call to set session org and reload

---

## 18. Exam-Taking Flow (Candidate)

- ⬜ Route: `GET /exam/{exam}` — exam detail / start page
- ⬜ Route: `POST /exam/{exam}/start` — create `ExamAttempt` record, redirect to workspace
- ⬜ Route: `GET /exam/{exam}/attempt/{attempt}` — exam workspace (timed, question-by-question or all-at-once)
- ⬜ Route: `POST /exam/{exam}/attempt/{attempt}/submit` — submit answers, calculate score
- ⬜ `ExamAttemptService::start()` — validate max_attempts, create attempt record
- ⬜ `ExamAttemptService::submit()` — store answers JSON, calculate score, set `passed` flag
- ⬜ Handle `shuffle_questions` and `shuffle_options` during attempt start
- ⬜ Handle `negative_mark_per_question` in score calculation
- ⬜ Handle `exam_mode` (standard / practice / proctored) differences
- ⬜ Enforce `scheduled_start` / `scheduled_end` windows
- ⬜ Enforce `max_attempts` limit per user per exam
- ⬜ Exam timer UI (countdown, auto-submit on expiry)
- ⬜ Results page — show score, pass/fail, correct answers (if allowed)
- ⬜ Candidate attempt history page (wire `attempts/index.blade.php`)

---

## 19. Notifications

- ⬜ Set up Laravel notification channels (database + mail)
- ⬜ Notification: exam published (notify org members)
- ⬜ Notification: exam attempt result (notify candidate)
- ⬜ Notification: new member added to org
- ⬜ Wire `NotificationController@index` to display real notifications
- ⬜ Mark notifications as read

---

## 20. Activity Log / Audit

- ⬜ Decide on log storage strategy: use existing `updated_by_history` JSON or a dedicated `activity_logs` table
- ⬜ Create `activity_logs` migration (if dedicated table chosen)
- ⬜ Wire `LogController@index` to display real activity log entries
- ⬜ Filter logs by user, entity type, date range

---

## 21. System Settings

- ✅ `SystemSetting` model (key-value)
- ✅ `system_settings` migration
- ✅ `SettingController` — clear-cache action
- ⬜ Seed default system settings (app_name, mail_from, etc.)
- ⬜ Read/write settings via `SettingController` form
- ⬜ Helper: `system_setting(string $key, $default = null)` to retrieve settings

---

## 22. Routes

- ✅ Frontend: `GET /` (home)
- ✅ Auth routes (Breeze standard)
- ✅ `GET /admin/` — dashboard
- ✅ `GET|PATCH|DELETE /admin/profile` — profile
- ✅ `GET /admin/internal-api/exams-table` — exam datatable JSON
- ✅ `GET /admin/internal-api/questions-table` — question datatable JSON
- ✅ Resource: `/admin/categories`
- ✅ Resource: `/admin/questions`
- ✅ Resource: `/admin/exams` + `PATCH /admin/exams/{exam}/publish`
- ✅ `GET|PUT /admin/settings`
- ✅ `GET /admin/candidates` (index only)
- ✅ `GET /admin/notifications` (index only)
- ✅ `GET /admin/logs` (index only)
- ✅ Static dummy routes for `exam-categories`
- ⬜ Resource: `/admin/organizations`
- ⬜ Resource: `/admin/users`
- ⬜ Resource: `/admin/members` (or nested under org)
- ⬜ Candidate exam-taking routes (`/exam/*`)
- ⬜ AJAX route: `POST /admin/settings/organization` — switch active org in session
- ⬜ AJAX route: `POST /admin/preferences` — save theme/sidebar state

---

## 23. Testing

- ✅ PestPHP configured (`tests/Pest.php`, `tests/TestCase.php`)
- ✅ `tests/Feature/Auth/` — Breeze auth tests
- ✅ `tests/Feature/ProfileTest.php` — profile update/delete tests
- ✅ `tests/Feature/ExampleTest.php` + `tests/Unit/ExampleTest.php` — placeholder tests
- ⬜ Feature tests: Category CRUD
- ⬜ Feature tests: Question CRUD
- ⬜ Feature tests: Exam CRUD + publish
- ⬜ Feature tests: Organization CRUD
- ⬜ Feature tests: Member management
- ⬜ Feature tests: Exam attempt flow (start, submit, score calculation)
- ⬜ Unit tests: `ExamService`
- ⬜ Unit tests: `QuestionService` (answer normalization)
- ⬜ Unit tests: `CategoryService` (tree builder)
- ⬜ Unit tests: `DashboardService` (stats per role)
- ⬜ Unit tests: `ProfileAvatarService`
- ⬜ Unit tests: `DatatableQuery`

---

## 24. Code Quality & DevOps

- ✅ Laravel Pint configured (code style)
- ✅ `.editorconfig` present
- ✅ `.gitignore` + `.gitattributes` configured
- ✅ Composer `dev` script — runs server, queue, pail, and vite concurrently
- ⬜ Set up CI pipeline (GitHub Actions or similar) — run Pint + Pest on push
- ⬜ Add `php artisan db:seed` to dev setup documentation
- ⬜ Write `README.md` with setup instructions, demo credentials, and feature overview
- ⬜ Environment-specific config review (mail driver, queue driver for production)
- ⬜ Storage link setup (`php artisan storage:link`) documented

---

## 25. Known Issues / Tech Debt

- ✅ `current_organization_id()` — **fixed**: single-org mode returns `Organization::first()->id` automatically
- ✅ `ExamDataController` does not scope to current organization — **fixed**: now uses `forOrg()`
- ✅ `AppServiceProvider` always resolves the single org — **fixed**: no session dependency
- ✅ `CategoryController@create` passes an empty `collect()` for parents — **fixed**: now loads real org categories
- ⬜ `DashboardController` does not pass real stats to the view
- ⬜ `QuestionController@show` and `@edit` do not load the actual question from the database
- ⬜ `ExamController` fallback `makeFallbackExam()` should be removed once store/update are wired
- ⬜ Static dummy routes for `exam-categories` should be replaced or removed
- ⬜ No role-based access control on any route — any authenticated user can access all `/admin/*` pages (deferred to multi-org phase)
