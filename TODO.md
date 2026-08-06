# Exam Management System — TODO

> Last reviewed against the codebase: **2026-08-02**
> Completed capabilities are documented in `public/docs/index.html` and `public/docs/backend.html` (served at `/docs/`); this file tracks remaining work and known deferred items.

---

## Completed (Aug 2026)

- [x] **Candidate exam lifecycle (core)** — start/resume, runner, autosave, timer, submit, scoring types, results/history
- [x] **Negative marking** — admin type → grading fraction mapping fixed and applied on score
- [x] **Exam session token** — single-session ownership checks for active attempts
- [x] **Inactive login block** — non-`active` users cannot authenticate
- [x] **Payment placeholder UX** — demo checkout/entitlement path (not a real gateway)
- [x] **Ads module redesign** — custom ads, Google ads, placements, custom code
- [x] **SEO generation** — `php artisan seo:generate`, settings UI, scheduled daily run
- [x] **Gallery** — media library, editor uploads, public disk + `storage:link`
- [x] **Settings** — email/SMTP, SEO, security (plus existing maintenance/branding/analytics surfaces)
- [x] **Notification placeholders** — admin notifications index shell (no real delivery yet)
- [x] **Policies / RBAC foundations** — capabilities (`content`, `organization`, `platform`), middleware, and model policies. Today **admin** and **org_admin** share full access; privilege split is deferred.
- [x] **Legacy Examtube Data & Media Migration** — complete importer service pipeline (`php artisan legacy:import-examtube` or `ExamtubeLegacyDataSeeder`) importing Categories, Blogs, Users & Profiles, Comments, Newsletter Subscribers, and SHA-256 deduplicated media assets from `legacy/examtube` branch / `public/old-application/`.
- [x] Earlier foundations: questions/exams/categories, blog/news, imports, public frontend, docs

---

## Deferred (explicitly out of current scope)

- [ ] Real payment gateway (Razorpay / Stripe / etc.) and paid-exam settlement
- [ ] Transaction processing beyond the placeholder entitlement grant
- [ ] Email verification enforcement on login/registration flows
- [ ] Real email / SMS / push notification delivery (storage + send pipelines)
- [ ] Split Application Admin vs Organization Admin privileges (capability hooks already exist)
- [ ] Fine-grained policies / RBAC for legacy Editor & Viewer roles (panel is **admin** + **org_admin** only today)
- [ ] CI pipeline (Pint, Pest, `npm run build` on push/PR)

---

## Remaining — medium priority

### Access and organizations

- [ ] Broader permission matrix beyond admin / org_admin / candidate; extra hardening on bulk/JSON endpoints where still thin
- [ ] Active organization switcher (session-backed) with membership validation
- [ ] Broader org/user administration (invites, role changes beyond current org-member tools)
- [ ] Decide whether public registration stays open or becomes invite-only

### Candidate / exam polish

- [ ] Feature tests for persistence, expiry, scoring, penalties, submission, result privacy
- [ ] Duplicate-exam action; richer exam-detail question UX (search/filter/lazy analytics)
- [ ] Import history listing, failed-row retry/export, optional private-file retention cleanup

### CMS and ops

- [ ] Deeper CMS admin (menus, heroes, FAQs, testimonials, contact/newsletter tools) where still thin
- [ ] Activity log schema, searchable UI, retention rules (beyond Coming Soon / actor columns)
- [ ] Upload hardening (stricter MIME checks; optional malware scanning)
- [ ] Factories, remove placeholder ExampleTests, a11y/responsive pass, backup/health runbooks

---

## Known constraints

- Admin panel roles: **Application Admin** and **Organization Admin** only (`EnsureAdminAccess` / `OrganizationRoles::adminPanelRoles()`).
- Capabilities: `admin` → content + organization + platform; `org_admin` → content + organization. Details in [`public/docs/backend.html`](public/docs/backend.html).
- Organization context uses highest-privilege active membership; multi-org switcher is not complete.
- Paid exams use a **placeholder** gateway — not production payment processing.
- Notifications UI is a placeholder; no production mail/SMS/push sending yet.
- Full demo seeder clears configured upload directories before rebuilding sample media — never `--seed` on live uploads.
- No repository CI workflow yet.
- Hostinger / production steps: see [`deployment.md`](deployment.md).
