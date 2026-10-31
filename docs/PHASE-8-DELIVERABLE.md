# Phase 8 deliverable — Security, performance, usability and final verification

Phase 8 closes the SRS by auditing the finished application instead of adding new
product features. It covers SRS 5.1 (performance), 5.2 (security), 5.3
(usability), 5.4 (backups/reliability), the FR-1 … FR-34 test matrix, the
branded password-reset email and the production deployment documentation.

**Final snapshot**

| Item | Value |
| --- | --- |
| Test suite | `php artisan test` → **266 passed, 1401 assertions** |
| Phase 8 suite | `php artisan test --filter=PhaseEightTest` → **26 passed, 246 assertions** |
| Migrations | 18 (one new Phase 8 index migration) |
| New indexes | 15 composite indexes on the hot query paths |
| Stack | PHP 8.3.31, Laravel 12.70.2, MySQL 8 (SQLite for tests), Vite 6.4.5, Bootstrap 5.3.8 |
| Code style | `vendor/bin/pint --test app config database routes tests` → pass |
| Asset build | `npm run build` → 358.56 kB CSS (55.56 kB gzip), 435.81 kB JS (146.56 kB gzip) |

---

## 1. Scope delivered

- **Security audit (SRS 5.2).** CSRF on every write form, Form Request coverage,
  mass-assignment proof, escaped output and safe search, upload
  decode/resize/re-encode with random names, route throttles, security headers
  (including the Jitsi exception), production session/cookie defaults and a full
  route-by-route RBAC table.
- **Performance pass (SRS 5.1).** 15 composite indexes, eager loading, pagination
  on every list, five-minute dashboard caches, lazy images, compressed uploads,
  minified assets, Laravel caches and a < 3 s page-load regression test.
- **Usability pass (SRS 5.3).** Branded 403/404/419/500/503 pages, consistent
  empty states, AJAX busy spinners and error toasts, meta/OG/Twitter/favicon head,
  dark-mode tokens, guest landing page with hero/features/CTAs and a responsive
  check at 360 / 768 / 1200 px.
- **Testing.** Full suite green plus an FR-1 … FR-34 matrix with an automated test
  for every row and a manual sign-off list for environment-dependent checks.
- **Email.** Branded password-reset notification, tested with `Notification::fake`
  and the rendered Markdown template; SMTP and sync/queue operation documented.
- **Backups and reliability (SRS 5.4).** `scripts/backup.sh`, scheduled
  `backup:database` command, failure alerting wrapper, log rotation and a
  monthly restore drill.
- **Deployment.** Linux VPS steps, Nginx and Apache vhosts, shared hosting
  section, PHP 8.2/8.3 guidance, production env, caches, permissions, cron,
  Supervisor, post-deploy checklist, updates/rollback and logging.

---

## 2. Files changed

### New files

| File | Purpose |
| --- | --- |
| `app/Services/ImageUploadService.php` | Single GD pipeline for post images and profile photos: decode → scale → re-encode (WebP/JPEG) → random 40-character name; deletes old files on replace/delete |
| `app/Http/Requests/StoreCommentRequest.php` | Comment body (required, ≤ 280) + `PostPolicy::view` authorisation, so hidden posts cannot collect comments |
| `app/Http/Requests/UpdateClassStatusRequest.php` | Status must be in `ClassRoom::STATUSES`; authorises via `ClassPolicy::changeStatus` |
| `app/Http/Requests/DeleteAccountRequest.php` | `current_password` confirmation; errors go to the `userDeletion` bag used by the modal |
| `config/educonnect.php` | Admin env values, upload dimensions/quality/MIME/size limits, default meta description |
| `database/migrations/2026_10_30_000002_add_phase_eight_query_indexes.php` | 15 idempotent composite indexes; portable across MySQL 8 and SQLite |
| `app/Console/Commands/BackupDatabase.php` | `backup:database` dump (MySQL gzip or SQLite copy) scheduled daily at 02:30 with retention |
| `tests/Feature/PhaseEightTest.php` | 26 tests / 246 assertions covering the cross-cutting Phase 8 rules |
| `docs/SECURITY.md` | SRS 5.2 audit: auth layers, CSRF scan, validation table, escaping, uploads, throttles, headers, sessions, full route middleware table |
| `docs/PERFORMANCE.md` | SRS 5.1 audit: measurements, indexes, eager loading, pagination, caches, assets, Laravel caches, curl checks, Debugbar/Telescope |
| `docs/TEST-MATRIX.md` | FR-1 … FR-34 result matrix, cross-cutting checks and manual sign-off list |
| `docs/PHASE-8-DELIVERABLE.md` | This document |

### Modified files

| File | Change |
| --- | --- |
| `app/Http/Controllers/PostController.php` | Injects `ImageUploadService`; create/update store the processed image and delete the old file; delete removes the file |
| `app/Http/Controllers/ProfileController.php` | Uses `ImageUploadService` for profile photos and `DeleteAccountRequest` for deletion |
| `app/Http/Controllers/CommentController.php` | Uses `StoreCommentRequest` |
| `app/Http/Controllers/ClassStatusController.php` | Uses `UpdateClassStatusRequest` |
| `app/Http/Controllers/Auth/PasswordController.php` | Uses the new `UpdatePasswordRequest` |
| `app/Http/Requests/StorePostRequest.php`, `UpdatePostRequest.php` | Image rules read from `config/educonnect.php`; comments reference the GD re-encode |
| `app/Http/Requests/Auth/ForgotPasswordRequest.php`, `ResetPasswordRequest.php` | Dedicated Form Requests with throttling/validation moved out of the controller |
| `app/Http/Requests/UpdatePasswordRequest.php` | Current password + `Password::defaults()` + confirmation; `updatePassword` error bag |
| `app/Support/DashboardMetrics.php` | Added cached `adminPendingReports()` (`dashboard.admin.pending-reports.v1`, 300 s) |
| `app/Providers/AppServiceProvider.php` | Named rate limiters for register, password reset, posts, comments, likes, reports, follows, notification poll and search suggest |
| `routes/web.php`, `routes/auth.php` | Throttles/named limiters applied to every expensive write route |
| `routes/console.php` | Daily `backup:database` schedule added next to the class reminder/auto-close jobs |
| `config/session.php` | `SESSION_SECURE_COOKIE` defaults to true when `APP_URL` starts with `https://` |
| `.env.example` | Production notes for `APP_ENV`, `APP_DEBUG`, logging, secure cookies, queue and cache |
| `resources/views/layouts/app.blade.php` | Canonical URL, meta description, OG/Twitter tags, favicon, Apple touch icon and light/dark `theme-color` |
| `resources/views/welcome.blade.php` | Guest landing page description plus hero, steps, features, stats, quote, FAQ and CTAs |
| `resources/views/layouts/sidebar.blade.php` | Removed the duplicate "Dashboard" menu item for all roles (the dashboard stays the post-login landing and is still reachable from the brand and mobile Home links) |
| `resources/js/app.js`, `feed.js`, `toast.js`, `resources/sass/app.scss` | Busy/error feedback, toast hooks and the usability tokens used by the audit |
| `tests/Feature/FeedTest.php` | Upload test now uses a real fake JPEG instead of a text placeholder |
| `README.md` | Phase 8 status, final counts, documentation links, screenshots checklist, hardening/security/performance/usability sections |
| `docs/DEPLOYMENT.md` | PHP 8.2/8.3 clarification, GD requirement, full Apache vhost, shared-hosting section, logging/error handling, post-deploy checklist |
| `docs/BACKUP.md` | Failure alerting wrapper, stale-backup check, log rotation, monitoring checklist, archive integrity check |
| `docs/REQUIREMENTS-STATUS.md` | Snapshot updated to 8/8 phases, 18 migrations, 266/1401 tests; Phase 8 rows and doc links added |

---

## 3. Security evidence (SRS 5.2)

| Control | Implementation | Proof |
| --- | --- | --- |
| CSRF on every write form | `@csrf` in all POST/PATCH/DELETE forms, including modal/component forms | `PhaseEightTest::test_every_post_form_carries_a_csrf_token` — static scan of every Blade file |
| Form Request validation | Login, register, forgot/reset password, password update, post, comment, report, class, class status, profile, delete account | `PhaseEightTest` comment/status/account tests; the per-feature suites |
| Mass-assignment safety | Explicit validated keys; `user_id`, `is_hidden`, `role`, `is_blocked` cannot be injected | `PhaseEightTest::test_write_requests_cannot_smuggle_protected_columns` |
| No raw user SQL | Eloquent/bindings; only bound `LIKE ... ESCAPE` and `COUNT(*)` | `PhaseEightTest::test_search_input_cannot_break_the_query` |
| Output escaping | Blade `{{ }}`; `linkify()` escapes before linking; search escapes before `<mark>` | `PhaseEightTest::test_user_content_is_escaped_in_the_feed`, `FeedTest::test_hashtag_links_are_xss_safe` |
| Upload hardening | MIME + size validation, GD decode, resize, re-encode, random name, no client extension, old-file cleanup | `PhaseEightTest` upload tests; `FeedTest` image cleanup test |
| Rate limits | Login 5 attempts (in `LoginRequest`), register 5/min, reset 6/min, posts 10/min, comments 30/min, likes 60/min, reports 10/min, follows 30/min, poll 60/min, suggestions 120/min | `PhaseEightTest` throttle tests + route audit; `FeedTest` post throttle |
| Security headers | `X-Content-Type-Options`, `Referrer-Policy`, `X-XSS-Protection: 0`, `X-Frame-Options: SAMEORIGIN`, `Permissions-Policy`, HSTS in production; Jitsi room relaxes framing only for `JITSI_DOMAIN` | `PhaseEightTest::test_security_headers_are_sent_on_every_page`, `test_the_jitsi_room_relaxes_framing_for_the_video_domain` |
| Production sessions | `database` sessions, HTTP-only, `SameSite=lax`, secure cookie default for HTTPS, `APP_DEBUG=false` | `config/session.php`, `.env.example`, `docs/SECURITY.md` §8 |
| RBAC | `role:admin`, `role:teacher`, `role:student`, `role:student,teacher` groups + policies + `not.blocked` on every web request | `RoleAccessTest`, `SmokeTest`, full table in `docs/SECURITY.md` §9 |

### RBAC route summary

| Area | Middleware |
| --- | --- |
| `/admin/*` (dashboard, users, posts, classes, reports, style guide) | `web, auth, not.blocked, role:admin` |
| Teacher class management (`classes.index/store/create/edit/update/destroy/status/students`) | `web, auth, not.blocked, role:teacher` |
| Student enrolment (`classes.enroll/unenroll`, `student.*`) | `web, auth, not.blocked, role:student` |
| Shared feed, posts, comments, likes, follows, search, notifications, profiles, class detail/room | `web, auth, not.blocked` (+ throttle on writes) |
| Public | `/`, `login`, `register`, `forgot-password`, `reset-password/*` |

The literal, line-by-line table (93 lines, generated from
`php artisan route:list --except-vendor`) is in `docs/SECURITY.md` §9.

---

## 4. Performance evidence (SRS 5.1)

| Check | Result |
| --- | --- |
| Feed query budget (20 posts + hashtags, likes, comments, followed authors) | ≤ 15 queries — `PerformanceTest` |
| Admin dashboard query budget (users, posts, classes, reports seeded) | ≤ 45 queries — `PhaseEightTest::test_dashboards_stay_within_a_query_budget` |
| Dashboard caches | 300 s TTL for admin/teacher/student stats, trends, sparklines, charts, schedule and pending reports — `PhaseEightTest::test_dashboard_metrics_are_cached_for_five_minutes` |
| Hot-path indexes | 15/15 present after migration — `PhaseEightTest::test_the_hot_query_indexes_are_present` |
| Page-load target | 12 primary pages under 3 s — `PhaseEightTest::test_the_primary_pages_load_in_under_three_seconds` |
| Pagination | Feed/profile 10, comments 15, search 15/tab, catalogue/enrolled 9, admin/queue/notifications/followers 20 |
| Uploads | Max 1600×1600 (post) / 512×512 (profile), WebP q82 / q85, JPEG fallback |
| Assets | `npm run build` → 358.56 kB CSS (55.56 kB gzip) and 435.81 kB JS (146.56 kB gzip), hashed filenames |
| Laravel caches | `config:cache`, `route:cache`, `view:cache`, `event:cache`, `optimize:clear` verified locally |
| Lazy images | `loading="lazy"` + `decoding="async"` on post images, avatars and report previews |

Full details, the index table and the curl measurement recipe are in
`docs/PERFORMANCE.md`. Debugbar and Telescope are documented as temporary,
local-only diagnostics and are not installed in production.

---

## 5. Usability evidence (SRS 5.3)

| Item | Evidence |
| --- | --- |
| Responsive 360 / 768 / 1200 px | Mobile-first Bootstrap grid, offcanvas sidebar, bottom mobile nav, `container-xxl`; manual browser check documented in `docs/TEST-MATRIX.md` |
| AJAX spinners | `resources/js/forms.js` busy state for every form (skips AJAX forms); feed/like/follow/comment scripts show inline busy states |
| Error toasts | `resources/js/toast.js` + flash partial; validation and network errors surface as Bootstrap toasts |
| Consistent empty states | One empty-state component used by feed, search, classes, notifications, reports and admin tables |
| Error pages | Branded 403 / 404 / 419 / 500 / 503 with "Back to home" and "Still stuck?" — `PhaseEightTest::test_all_friendly_error_pages_render` |
| Dark mode | SCSS tokens for both themes, toggle persisted in `localStorage`, contrast-safe text/soft/primary tokens |
| Titles and meta | Per-page `<title>`, description, canonical, OG and Twitter cards, light/dark theme-color — `PhaseEightTest::test_the_layout_ships_meta_social_and_favicon_tags` |
| Favicon | `public/favicon.ico` linked as icon and Apple touch icon |
| Guest landing | Hero, steps, feature grid, stats, quote, FAQ and primary/secondary CTAs — `resources/views/welcome.blade.php` |
| Confirmation UX | Bootstrap modals for post/class deletion and admin block/delete; native confirm for AJAX comment deletion |
| Sidebar cleanup | Removed the redundant "Dashboard" menu item for admin, teacher and student; dashboards remain the post-login landing page |

---

## 6. Password reset email

- `App\Notifications\ResetPasswordNotification` extends Laravel's reset
  notification, keeping the token/expiry logic, and renders the branded
  `emails.reset-password` Markdown template.
- `Auth\PasswordResetLinkController` and `NewPasswordController` use dedicated
  Form Requests; the reset request route is limited to 6/minute per IP.
- Works synchronously (`QUEUE_CONNECTION=sync`, no worker) or queued
  (`QUEUE_CONNECTION=database` + Supervisor worker).
- Tests: `Auth/PasswordResetTest::test_the_reset_email_uses_the_branded_template`
  asserts subject, markdown view, recipient name, reset URL and rendered button;
  the request/reset flow is covered by the remaining tests in that class.
- Real SMTP delivery is a documented manual deployment check (see
  `docs/TEST-MATRIX.md`, manual table).

---

## 7. FR-1 … FR-34 result matrix

Every row is automated and green in the final run. Exact test method names are in
`docs/TEST-MATRIX.md`; this is the compact result view.

| FR | Requirement | Automated coverage | Result |
| --- | --- | --- | --- |
| FR-1 | Register/login/logout, role dashboard landing | `Auth/RegistrationTest`, `Auth/AuthenticationTest` | PASS |
| FR-2 | Role-based access and three dashboards | `RoleAccessTest`, `DashboardTest` | PASS |
| FR-3 | Forgot/reset password, branded email | `Auth/PasswordResetTest` | PASS |
| FR-4 | Profile edit, photo, password, delete account | `ProfileTest`, `PhaseThreeUiTest` | PASS |
| FR-5 | Public profile, followers/following lists | `FeedTest`, `PhaseThreeCTest` | PASS |
| FR-6 | Blocked accounts refused/ejected | `Auth/BlockedUserTest`, `PhaseSevenTest` | PASS |
| FR-7 | Create post (280, image) | `FeedTest`, `PhaseEightTest` uploads | PASS |
| FR-8 | Edit/delete own post, image cleanup | `FeedTest` | PASS |
| FR-9 | Like/unlike, no duplicates, throttle | `FeedTest`, `PhaseEightTest` | PASS |
| FR-10 | Comments, deletion rights, hidden comments | `FeedTest`, `PhaseSevenTest` | PASS |
| FR-11 | Follow/unfollow, no self-follow, counters | `FeedTest`, `DashboardTest` | PASS |
| FR-12 | Feed of own + followed, visible only, paginated | `FeedTest`, `PerformanceTest` | PASS |
| FR-13 | Hashtag parse/sync, XSS-safe links, tag page | `FeedTest` | PASS |
| FR-14 | People/hashtag/post search, suggestions, escaping | `SearchTest`, `PhaseEightTest` | PASS |
| FR-15 | Class creation validation, duration, unique room | `ClassTest` | PASS |
| FR-16 | Edit/delete class (owner only) | `ClassTest` | PASS |
| FR-17 | Browse/filter classes, enrol/unenrol | `ClassTest` | PASS |
| FR-18 | Class detail with enrol/join | `ClassTest`, `SmokeTest` | PASS |
| FR-19 | Class search by title/teacher/subject/date | `ClassTest` | PASS |
| FR-20 | Jitsi room access: owner/enrolled, live, visible | `ClassTest` | PASS |
| FR-21 | Status workflow, legal transitions, auto-close | `ClassTest` | PASS |
| FR-22 | In-room Jitsi, chat, moderator, escaped name | `ClassTest`, `PhaseEightTest` headers | PASS |
| FR-23 | Follower/like/comment/new-class/starting-soon notifications | `NotificationTest` | PASS |
| FR-24 | Bell, unread counter, 30 s polling, filters | `NotificationTest` | PASS |
| FR-25 | Mark one/all read, delete, open target | `NotificationTest` | PASS |
| FR-26 | Admin totals, charts, trends, recent activity | `DashboardTest`, `DashboardRedesignTest` | PASS |
| FR-27 | User moderation: search/filter/block/unblock/delete | `AdminManagementTest` | PASS |
| FR-28 | Content moderation: hide/unhide/delete posts/classes/comments | `AdminManagementTest`, `PhaseSevenTest`, `ReportTest` | PASS |
| FR-29 | Teacher dashboard and student roster | `PhaseThreeUiTest`, `ClassTest` | PASS |
| FR-30 | Student dashboard, upcoming classes, suggestions | `DashboardTest`, `DashboardRedesignTest` | PASS |
| FR-31 | Charts, theme toggle, sidebar state, tokens | `DashboardRedesignTest`, `PhaseThreeUiTest` | PASS |
| FR-32 | Report post/comment, one report per user per item | `ReportTest` | PASS |
| FR-33 | Moderation queue filters, grouping, statuses | `ReportTest`, `PhaseSevenTest` | PASS |
| FR-34 | Resolve reports: hide/unhide/delete, block, reviewed/dismissed | `ReportTest`, `PhaseSevenTest` | PASS |

Cross-cutting Phase 8 checks (headers, CSRF scan, validation, uploads, throttles,
error pages, caches, indexes, page timing) are listed in `docs/TEST-MATRIX.md`.

---

## 8. Backups and reliability (SRS 5.4)

- `scripts/backup.sh` — MySQL `mysqldump --single-transaction --quick --routines
  --events` (password in a temporary defaults file, never in `ps`), uploads and
  `.env` in one `tar.gz`, mode `600`, retention `RETENTION_DAYS` (14).
- `php artisan backup:database` — daily 02:30 dump into `storage/app/backups`
  with retention, works when no shell wrapper is installed; SQLite copy fallback.
- Failure handling — wrapper exits non-zero on failure, alerts by mail/webhook
  and fails when the newest archive is older than 26 hours.
- Logging — `LOG_CHANNEL=daily`, `LOG_LEVEL=warning`, `LOG_DAILY_DAYS=14`;
  logrotate recipes for app/worker/backup logs; health endpoint `/up`.
- Restore drill — archive integrity check plus monthly restore into a scratch
  database; full unpack, database, uploads, `.env`, cache and verification steps.
- Secrets — archives contain `.env`; encrypt off-site copies, keep mode `600`,
  rotate credentials after restoring onto an untrusted machine.

See `docs/BACKUP.md`.

---

## 9. Deployment documentation

`docs/DEPLOYMENT.md` now covers:

1. Ubuntu/Debian packages with the PHP 8.2/8.3 note and the required `gd` /
   `fileinfo` / `pdo_mysql` extensions.
2. MySQL database/user creation (no root account).
3. Code upload and `.env` values (production, mail, queue, cache, admin, Jitsi).
4. Composer/npm build, migrations, `storage:link`, admin-only seed.
5. Permissions and the `public/` document root rule.
6. Config/route/view/event caches and `optimize:clear`.
7. Nginx vhost, HTTPS with certbot, and a complete Apache vhost.
8. Shared-hosting section (cPanel/Plesk): document root, PHP extensions,
   database prefixes, cron/queue without Supervisor, storage link, HTTPS.
9. Scheduler cron and queue worker under Supervisor.
10. Jitsi self-hosting switch, mail/reset testing, logging and error handling.
11. Post-deploy checklist, updates/rollback and the production security checklist.

---

## 10. Commands run for this deliverable

```bash
# Code style
vendor/bin/pint tests/Feature/PhaseEightTest.php
vendor/bin/pint --test app config database routes tests

# Full regression suite
php artisan test                 # 266 passed, 1401 assertions
php artisan test --filter=PhaseEightTest   # 26 passed, 246 assertions

# Blade / route / config caches
php artisan view:cache
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan optimize:clear

# Production asset build
npm run build                    # 358.56 kB CSS, 435.81 kB JS

# RBAC table generation (docs/SECURITY.md §9)
php artisan route:list --except-vendor
```

All commands completed successfully on the local environment (PHP 8.3.31,
Laravel 12.70.2, Node/Vite 6.4.5, SQLite in-memory for tests).

---

## 11. Manual sign-off still required on staging

These cannot be proven by the SQLite test suite and are recorded in
`docs/TEST-MATRIX.md`:

- Responsive visual check at 360 / 768 / 1200 px in both themes.
- Real SMTP delivery of the password-reset email.
- Real Jitsi call with camera/microphone over HTTPS.
- Production queue worker with `QUEUE_CONNECTION=database`.
- MySQL 8 `migrate --force` and a real backup/restore cycle.
- Dark-mode contrast review of text, buttons, alerts and charts.

Known limitations, reported honestly: no two-factor authentication, no
per-request CSP nonce (the inline theme bootstrap needs its own script), and no
fan-out timeline table for very large follow graphs. None blocks the SRS.

---

## 12. Done when

- [x] Full test suite green: **266 passed, 1401 assertions**.
- [x] Phase 8 suite green: **26 passed, 246 assertions**.
- [x] `vendor/bin/pint --test app config database routes tests` passes.
- [x] Every write form carries `@csrf` (static scan).
- [x] Mass-assignment payloads for `user_id`, `is_hidden`, `role`, `is_blocked`
      are ignored.
- [x] Uploads are validated, decoded, resized, re-encoded and stored under a
      random name; `.php` payloads cannot survive.
- [x] Login, registration, posts, comments, likes, reports, follows, polling and
      suggestions are rate limited.
- [x] Security headers are present on normal pages and correctly relaxed only for
      the Jitsi room.
- [x] 403 / 404 / 419 / 500 / 503 pages render with the branded shell.
- [x] Feed, dashboards and admin tables stay inside their query budgets.
- [x] 15/15 hot-path indexes exist after migration.
- [x] Dashboard aggregates are cached for five minutes under role-scoped keys.
- [x] All 12 primary pages render under 3 seconds in the regression test.
- [x] Branded password-reset email renders and the reset flow works.
- [x] `docs/SECURITY.md`, `docs/PERFORMANCE.md`, `docs/TEST-MATRIX.md`,
      `docs/DEPLOYMENT.md`, `docs/BACKUP.md` and `docs/REQUIREMENTS-STATUS.md`
      are current.
- [x] `view:cache`, `config:cache`, `route:cache`, `optimize:clear` and
      `npm run build` succeed.
- [x] README documents features, setup, demo accounts, screenshots, testing,
      security/performance/usability evidence, deployment and backups.
- [ ] Staging-only manual checks in section 11 completed and dated.
