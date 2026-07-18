# Exam Management System — TODO

> Last reviewed against the codebase: 2026-07-18
> Completed capabilities are documented in `public/docs/index.html` (served at `/docs/`); this file tracks remaining work only.

## Priority 0 — Security and access control

- [ ] Define canonical roles/permissions for Admin, Org Admin, Editor, and Viewer.
- [ ] Add route middleware so authentication alone does not grant access to every `/admin/*` module.
- [ ] Add policies for organizations, questions, categories, exams, blog/news content, gallery items, imports, and attempts.
- [ ] Audit every bulk action and JSON endpoint for policy checks in addition to organization scoping.
- [ ] Decide whether public registration remains enabled; otherwise restrict it to invitations or administrators.
- [ ] Add security tests proving each role can access only its permitted actions.
- [ ] Add upload security controls appropriate for production, including optional malware scanning and stricter server-side MIME inspection.

## Priority 1 — Complete the candidate exam lifecycle

Attempt creation and stable question assignment are implemented. Remaining:

- [ ] Add candidate-facing start/resume routes outside the administration namespace.
- [ ] Build the responsive attempt workspace and question navigation.
- [ ] Implement answer autosave with ownership and active-attempt validation.
- [ ] Implement countdown timer and server-authoritative expiry.
- [ ] Implement transactional submission and idempotent resubmission handling.
- [ ] Score single-answer, multi-answer, true/false, fill-blank, short-answer, and written/manual-review types.
- [ ] Apply marks overrides, negative marking, pass criteria, and result visibility rules.
- [ ] Implement auto-submit on expiry and abandoned-attempt behavior.
- [ ] Build result detail/review pages and complete account attempt history.
- [ ] Add feature tests for answer persistence, expiry, scoring, penalties, submission, and result privacy.

## Priority 1 — Organization context and administration

- [ ] Reconcile organization resolution so helpers, controllers, and shared navigation use one source of truth.
- [ ] Add an active organization switcher backed by session state.
- [ ] Validate active membership and status on every organization switch/request.
- [ ] Implement organization CRUD, branding uploads, and ownership rules.
- [ ] Implement user administration and status management.
- [ ] Implement organization member assignment, role changes, removal, and invitation flow.
- [ ] Allow users to choose a valid default organization from profile/account settings.
- [ ] Add factories and feature tests for organizations, memberships, role boundaries, and switching.

## Priority 2 — Placeholder modules

### Candidates

- [ ] Replace `CandidateController` Coming Soon page with organization-scoped candidate list.
- [ ] Show registrations, assigned exams, attempt status, scores, and import/source details.
- [ ] Add manual invitation and spreadsheet import workflows if required by product scope.

### Notifications

- [ ] Add database notification storage and service layer.
- [ ] Notify candidates about invitations, schedules, publication changes, and results.
- [ ] Build notification list, unread count, mark-read, and mark-all-read actions.
- [ ] Configure and test production mail delivery.

### Activity logs

- [ ] Choose a durable activity-log schema rather than relying only on actor columns/JSON history.
- [ ] Record actor, organization, action, subject, changes, IP, user agent, and timestamp.
- [ ] Build searchable filters and protected detail views.
- [ ] Define retention and privacy rules.

## Priority 2 — CMS and settings administration

- [ ] Expand Settings beyond cache clearing to validated `SystemSetting`/site configuration updates.
- [ ] Add protected administration screens for CMS pages, menus, hero banners, FAQs, testimonials, partners, advertisements, contact submissions, and newsletter subscribers.
- [ ] Add cache invalidation for CMS/site-setting changes.
- [ ] Add preview and revision/history behavior for published content where needed.
- [ ] Add sitemap, robots, and structured-data generation.

## Priority 2 — Exam and question enhancements

- [ ] Add duplicate-exam action with explicit rules for copied schedules, questions, media, and status.
- [ ] Add protected question preview and remove-from-exam quick actions on the exam detail page.
- [ ] Lazy-load, search, filter, sort, and paginate large linked-question lists on exam details.
- [ ] Load heavy exam analytics asynchronously with skeleton states if real datasets require it.
- [ ] Add import history listing and retention controls, not only per-question import detail.
- [ ] Add retry/export workflow for failed import rows.
- [ ] Decide whether old private import files should expire; implement a scheduled cleanup policy if required.

## Priority 3 — Quality, performance, and operations

- [ ] Add CI to run Pint, Pest, and `npm run build` on pushes and pull requests.
- [ ] Remove placeholder `ExampleTest` files and add focused unit tests for services/query helpers.
- [ ] Add factories for organizations, categories, questions, exams, attempts, blogs, news, and gallery records.
- [ ] Profile list and analytics queries with production-sized datasets; add indexes only from measured evidence.
- [ ] Add queue-failure monitoring, log rotation, backup/restore runbook, and health checks.
- [ ] Add browser-level smoke tests for critical create/edit/import/publish flows.
- [ ] Review accessibility with keyboard navigation, focus states, labels, contrast, and screen readers.
- [ ] Test all major backend and frontend screens at mobile, tablet, and desktop breakpoints.
- [ ] Remove or consolidate unused legacy layouts, views, CSS, and JavaScript after confirming no route references.

## Known constraints

- All administration routes currently use `auth` middleware but not role middleware.
- The application has organization-scoped queries, but active multi-organization switching is not complete.
- Candidate start/assignment exists; answer submission and scoring do not.
- Candidates, Notifications, and Logs currently render the shared Coming Soon view.
- Settings currently supports cache clearing rather than complete persisted configuration.
- The full demo seeder clears configured upload directories before rebuilding sample media.
- There is no repository CI workflow.

## Recently completed

- [x] Functional question, exam, category, blog, news, gallery, dashboard, and public frontend flows.
- [x] Live question-bank APIs and fixed/pool/dynamic attempt question assignment.
- [x] Tracked XLSX/CSV question imports with private source files, logs, row counts, source filtering, and import details.
- [x] Gallery-backed Open Graph images across existing SEO-enabled modules.
- [x] Duplicate Blog and News slug fields removed; slug now lives in the SEO section.
- [x] Comprehensive standalone documentation and refreshed setup guide.
- [x] Admin review pass: org-scoped import validation, OG preview loading, gallery-picker double-bind guard, detail-page Tailwind fixes, expanded README/HTML docs.
