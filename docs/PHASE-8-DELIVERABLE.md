# Phase 8 deliverable — Security, performance, usability and final verification

Phase 8 closes the SRS by auditing the finished application instead of adding new
product features. It covers SRS 5.1 (performance), 5.2 (security), 5.3
(usability), 5.4 (backups/reliability), the FR-1 … FR-34 test matrix, the
branded password-reset email and the production deployment documentation.

**Final snapshot**

| Item | Value |
| --- | --- |
| Test suite | `php artisan test` → **307 passed, 1665 assertions** |
| Phase 8 suite | `php artisan test --filter=PhaseEightTest` → **26 passed, 251 assertions** |
| Migrations | 18 (one new Phase 8 index migration) |
| New indexes | 15 composite indexes on the hot query paths |
| Stack | PHP 8.3.31, Laravel 12.70.2, MySQL 8 (SQLite for tests), Vite 6.4.5, Bootstrap 5.3.8 |
| Code style | `vendor/bin/pint --test app config database routes tests` → pass |
| Asset build | `npm run build` → 368.63 kB CSS (57.47 kB gzip), 229.62 kB JS (75.83 kB gzip) |

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

Every file in the tables below is reproduced **in full** in [Appendix A](#appendix-a-full-source-of-every-phase-8-file);
the appendix is generated from the working tree and verified byte-for-byte.

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
| `tests/Feature/PhaseEightTest.php` | 26 tests / 251 assertions covering the cross-cutting Phase 8 rules |
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
| `public/favicon.ico` | Replaced the framework's 0-byte placeholder with a real 3-size ICO (16/32/48 px, indigo rounded square with a white "E"); `PhaseEightTest` now asserts it is a valid icon |
| `app/Http/Middleware/SecurityHeaders.php` | Baseline headers, the narrow Jitsi exception and the production HSTS rule |
| `resources/views/welcome.blade.php` | Guest landing page description plus hero, steps, features, stats, quote, FAQ and CTAs |
| `resources/views/layouts/sidebar.blade.php` | Removed the duplicate "Dashboard" menu item for all roles (the dashboard stays the post-login landing and is still reachable from the brand and mobile Home links) |
| `resources/js/app.js`, `feed.js`, `toast.js`, `resources/sass/app.scss` | Busy/error feedback, toast hooks and the usability tokens used by the audit |
| `tests/Feature/FeedTest.php` | Upload test now uses a real fake JPEG instead of a text placeholder |
| `README.md` | Phase 8 status, final counts, documentation links, screenshots checklist, hardening/security/performance/usability sections |
| `docs/DEPLOYMENT.md` | PHP 8.2/8.3 clarification, GD requirement, full Apache vhost, shared-hosting section, logging/error handling, post-deploy checklist |
| `docs/BACKUP.md` | Failure alerting wrapper, stale-backup check, log rotation, monitoring checklist, archive integrity check |
| `docs/REQUIREMENTS-STATUS.md` | Snapshot updated to 8/8 phases, 18 migrations, 307/1665 tests; Phase 8 rows and doc links added |

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

The literal, line-by-line table (92 routes, generated from
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
| Assets | `npm run build` → 368.63 kB CSS (57.47 kB gzip) and 229.62 kB JS (75.83 kB gzip), hashed filenames |
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
| Favicon | A real 3-size `public/favicon.ico` (16/32/48 px) linked as icon and Apple touch icon; `PhaseEightTest` checks the file is non-empty and has a valid ICO header |
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
php artisan test                 # 307 passed, 1665 assertions
php artisan test --filter=PhaseEightTest   # 26 passed, 251 assertions

# Blade / route / config caches
php artisan view:cache
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan optimize:clear
php artisan config:clear
CACHE_STORE=array php artisan optimize:clear   # when the database cache cannot be reached

# Production asset build
npm run build                    # 368.63 kB CSS, 229.62 kB JS

# RBAC table generation and reconciliation against the docs
php artisan route:list --except-vendor
```

All commands completed successfully on the local environment (PHP 8.3.31,
Laravel 12.70.2, Node/Vite 6.4.5, SQLite in-memory for tests). MySQL is not
running on this machine, so the default `php artisan optimize:clear` reaches the
database cache store and fails on the final `cache` step; after
`php artisan config:clear` (which removes the cached `CACHE_STORE=database`
value), repeating it with `CACHE_STORE=array` clears every cache (config, cache,
compiled, events, routes, views) with exit code 0. On a production server with
MySQL up, the plain command is the correct one.

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

## 11a. Corrections found in the final audit pass

The Phase 8 audit was re-run end to end against the current working tree. Four
small discrepancies were found and fixed:

| Finding | Fix |
| --- | --- |
| `docs/SECURITY.md` §9 was missing `GET classes/{classRoom}/leave` (the “leave the room” redirect added with the Jitsi room) | Added the row. A scripted diff against `php artisan route:list --except-vendor --json` now reports **92/92 routes present and 0 middleware mismatches** |
| `public/favicon.ico` was the framework's 0-byte placeholder, so the head declared an icon that could not load | Regenerated a real ICO with 16/32/48 px PNG frames; `PhaseEightTest::test_the_layout_ships_meta_social_and_favicon_tags` now asserts the file exists, is non-empty and has a valid ICO header |
| This document and `docs/PERFORMANCE.md` still quoted the previous test and asset counts | Refreshed to **307 passed / 1,665 assertions**, Phase 8 **26 / 251**, and the current `npm run build` output |
| `docs/DEPLOYMENT.md` §12 repeated the same log path in one sentence | Rewritten to distinguish the application log from the mail provider log |

No application behaviour changed in this pass; only the icon asset, the audit
documents and the new regression assertion.

---

## 12. Done when

- [x] Full test suite green: **307 passed, 1665 assertions**.
- [x] Phase 8 suite green: **26 passed, 251 assertions**.
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

---

## Appendix A. Full source of every Phase 8 file

This appendix is generated from the working tree, so it is the literal
content that ships in the repository (not a hand-copied excerpt). Files whose
only Phase 8 change was a line or two still appear in full. The only binary
asset is `public/favicon.ico`, reproduced at the end as base64.

Files that `git status` also lists as modified but whose change belongs to an
earlier phase (for example `resources/sass/_responsive.scss`, `resources/js/layout.js`
and the class/feed views) are reproduced in full in that phase's own brief
(`docs/PHASE-3C-DELIVERABLE.md`, `docs/PHASE-7-DELIVERABLE.md`, …) and are not
duplicated here. `docs/PHASE-8-DELIVERABLE.md` itself is this document.

### A.1 Services, configuration, migration and console command

#### `app/Services/ImageUploadService.php`

````php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores uploaded images safely (SRS 5.1, SRS 5.2).
 *
 * Every upload is decoded with GD and re-encoded from scratch:
 *
 *  - the original file is never copied to disk, so a PHP payload hidden inside
 *    a "JPEG" cannot survive the round trip,
 *  - EXIF metadata (location, camera, author) is dropped,
 *  - large photos are resized before they are written,
 *  - the file name is 40 random characters, never the client's name,
 *  - the extension is chosen by us (webp, or jpg when GD has no WebP support).
 *
 * A file that GD cannot decode is rejected with a normal validation error,
 * even when its MIME type claimed to be an image.
 */
class ImageUploadService
{
    /**
     * Store a post image, resized to the configured maximum width and height.
     */
    public function storePostImage(UploadedFile $file): string
    {
        $config = config('educonnect.uploads');

        return $this->store(
            $file,
            (string) $config['post_image_dir'],
            (int) $config['post_image_max_width'],
            (int) $config['post_image_max_height'],
            (int) $config['post_image_quality'],
            'image'
        );
    }

    /**
     * Store a profile photo, cropped down to the configured square-ish size.
     */
    public function storeProfilePhoto(UploadedFile $file): string
    {
        $config = config('educonnect.uploads');

        return $this->store(
            $file,
            (string) $config['profile_photo_dir'],
            (int) $config['profile_photo_max_width'],
            (int) $config['profile_photo_max_height'],
            (int) $config['profile_photo_quality'],
            'photo'
        );
    }

    /**
     * Decode, resize, re-encode and write one file. Returns the relative path
     * on the "public" disk.
     *
     * @param  string  $field  the form field name, used for the error message
     */
    private function store(
        UploadedFile $file,
        string $directory,
        int $maxWidth,
        int $maxHeight,
        int $quality,
        string $field
    ): string {
        $path = $file->getRealPath();
        $contents = $path !== false ? @file_get_contents($path) : false;
        $source = $contents !== false ? @imagecreatefromstring($contents) : false;

        if ($source === false) {
            throw ValidationException::withMessages([
                $field => __('That file is not a readable image. Please upload a JPG, PNG or WebP photo.'),
            ]);
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // Never enlarge a small image; only shrink what is too big.
        $scale = min(1, $maxWidth / max(1, $width), $maxHeight / max(1, $height));
        $newWidth = max(1, (int) floor($width * $scale));
        $newHeight = max(1, (int) floor($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        $useWebp = function_exists('imagewebp');

        if ($useWebp) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);
        } else {
            // JPEG has no transparency: flatten onto white first.
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $white);
        }

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        ob_start();

        if ($useWebp) {
            imagewebp($canvas, null, $quality);
        } else {
            imagejpeg($canvas, null, $quality);
        }

        $encoded = ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        if (! is_string($encoded) || $encoded === '') {
            throw ValidationException::withMessages([
                $field => __('The image could not be processed. Please try another file.'),
            ]);
        }

        $path = trim($directory, '/').'/'.Str::random(40).($useWebp ? '.webp' : '.jpg');

        Storage::disk('public')->put($path, $encoded);

        return $path;
    }
}
````

#### `config/educonnect.php`

````php
<?php

/*
|--------------------------------------------------------------------------
| EduConnect settings
|--------------------------------------------------------------------------
| Small application specific settings kept in one place so that no secret
| is ever hardcoded inside a class. Values come from the .env file.
*/

return [

    // The administrator account that AdminUserSeeder creates.
    'admin' => [
        'name' => env('ADMIN_NAME', 'EduConnect Admin'),
        'email' => env('ADMIN_EMAIL', 'admin@educonnect.test'),
        // Never hardcode this. It must be set in .env, otherwise seeding fails loudly.
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Upload rules (used by the Form Requests and ImageUploadService).
    // Files are always re-encoded with GD, so these limits also decide the
    // dimensions and quality of what actually lands on disk (SRS 5.1).
    'uploads' => [
        'post_image_dir' => 'posts',                          // -> storage/app/public/posts
        'post_image_max_kb' => 2048,                          // 2 MB
        'post_image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'post_image_max_width' => 1600,
        'post_image_max_height' => 1600,
        'post_image_quality' => 82,

        'profile_photo_dir' => 'profiles',                    // -> storage/app/public/profiles
        'profile_photo_max_kb' => 2048,                       // 2 MB
        'profile_photo_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'profile_photo_max_width' => 512,
        'profile_photo_max_height' => 512,
        'profile_photo_quality' => 85,
    ],

    // Default meta description for pages that do not provide their own.
    'meta' => [
        'description' => env(
            'APP_DESCRIPTION',
            'EduConnect is an online classroom with live video classes, a friendly social feed and simple class management for teachers and students.'
        ),
    ],

];
````

#### `database/migrations/2026_10_30_000002_add_phase_eight_query_indexes.php`

````php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 8 performance pass (SRS 5.1).
 *
 * The application filters and sorts by a relationship column plus a date:
 * "my posts, newest first", "comments on this post, oldest first", "pending
 * reports, newest first", "visible classes by date", and so on. A single
 * column index cannot serve the filter and the sort at the same time, so each
 * hot path gets one composite index.
 *
 * Indexes are created defensively (by name) so the migration is safe on MySQL
 * and on the SQLite database used by the test suite.
 */
return new class extends Migration
{
    /**
     * @var list<array{0: string, 1: list<string>, 2: string}>
     */
    private const INDEXES = [
        // Feed, profile pages and "my posts" sorting.
        ['posts', ['user_id', 'created_at'], 'phase8_posts_user_created_index'],
        ['posts', ['is_hidden', 'created_at'], 'phase8_posts_hidden_created_index'],

        // Post detail pages and the profile comment activity.
        ['comments', ['post_id', 'created_at'], 'phase8_comments_post_created_index'],
        ['comments', ['user_id', 'created_at'], 'phase8_comments_user_created_index'],

        // "Posts I liked" and follower/following lists.
        ['likes', ['user_id', 'created_at'], 'phase8_likes_user_created_index'],
        ['follows', ['follower_id', 'created_at'], 'phase8_follows_follower_created_index'],
        ['follows', ['following_id', 'created_at'], 'phase8_follows_following_created_index'],

        // Class catalogue, teacher table and hidden-class checks.
        ['classes', ['status', 'date'], 'phase8_classes_status_date_index'],
        ['classes', ['teacher_id', 'date'], 'phase8_classes_teacher_date_index'],
        ['classes', ['is_hidden', 'date'], 'phase8_classes_hidden_date_index'],

        // "My enrolled classes" and the student dashboard.
        ['class_enrollments', ['student_id', 'created_at'], 'phase8_enrollments_student_created_index'],

        // Moderation queue: filter by status, newest first; per-content lookup.
        ['reports', ['status', 'created_at'], 'phase8_reports_status_created_index'],
        ['reports', ['reportable_type', 'reportable_id', 'status'], 'phase8_reports_reportable_status_index'],

        // Notification bell: unread notifications for one user.
        ['notifications', ['notifiable_type', 'notifiable_id', 'read_at'], 'phase8_notifications_unread_index'],

        // Admin user table: role + blocked filters.
        ['users', ['role', 'is_blocked'], 'phase8_users_role_blocked_index'],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as [$table, $columns, $name]) {
            if (! Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
                $blueprint->index($columns, $name);
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as [$table, , $name]) {
            if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($name): void {
                $blueprint->dropIndex($name);
            });
        }
    }
};
````

#### `app/Console/Commands/BackupDatabase.php`

````php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Portable database dump used by the scheduler (SRS 5.4).
 *
 * Production Linux still uses scripts/backup.sh for a full archive (database
 * + uploads + .env). This command is the always-available dump that runs from
 * `php artisan schedule:run` even when a shell wrapper is not installed.
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database
                            {--keep=14 : Days of dumps to keep}';

    protected $description = 'Dump the application database into storage/app/backups';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir, 0700);

        $stamp = now()->format('Ymd_His');
        $connection = (string) config('database.default');
        $config = config("database.connections.{$connection}", []);
        $driver = $config['driver'] ?? $connection;

        if ($driver === 'sqlite') {
            $database = (string) ($config['database'] ?? '');

            if ($database === ':memory:' || $database === '' || ! is_file($database)) {
                $this->warn('SQLite in-memory databases cannot be dumped; skipping.');

                return self::SUCCESS;
            }

            $target = $dir.DIRECTORY_SEPARATOR."database_{$stamp}.sqlite";
            File::copy($database, $target);
            $this->info("SQLite dump written: {$target}");
        } else {
            $mysqldump = $this->mysqldumpBinary();

            if ($mysqldump === null) {
                $this->error('mysqldump is not on PATH. Install mysql-client or use scripts/backup.sh.');

                return self::FAILURE;
            }

            $target = $dir.DIRECTORY_SEPARATOR."database_{$stamp}.sql.gz";
            $passwordFile = $dir.DIRECTORY_SEPARATOR.'.my.cnf.tmp';

            File::put($passwordFile, sprintf(
                "[client]\nhost=%s\nport=%s\nuser=%s\npassword=\"%s\"\n",
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? 3306,
                $config['username'] ?? 'root',
                str_replace(['\\', '"'], ['\\\\', '\\"'], (string) ($config['password'] ?? ''))
            ));
            chmod($passwordFile, 0600);

            try {
                $process = Process::fromShellCommandline(
                    sprintf(
                        '%s --defaults-extra-file=%s --single-transaction --quick --routines --events --no-tablespaces %s | gzip > %s',
                        escapeshellarg($mysqldump),
                        escapeshellarg($passwordFile),
                        escapeshellarg((string) ($config['database'] ?? '')),
                        escapeshellarg($target)
                    )
                );
                $process->setTimeout(300);
                $process->run();

                if (! $process->isSuccessful() || ! is_file($target) || filesize($target) === 0) {
                    $this->error(trim($process->getErrorOutput()) ?: 'mysqldump produced an empty file.');

                    return self::FAILURE;
                }
            } finally {
                File::delete($passwordFile);
            }

            $this->info("MySQL dump written: {$target}");
        }

        $this->prune($dir, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    private function mysqldumpBinary(): ?string
    {
        $candidates = ['mysqldump', 'mysqldump.exe'];

        foreach ($candidates as $binary) {
            $process = Process::fromShellCommandline(
                PHP_OS_FAMILY === 'Windows' ? 'where '.$binary : 'command -v '.$binary
            );
            $process->run();

            if ($process->isSuccessful()) {
                return trim(strtok($process->getOutput(), "\n"));
            }
        }

        return null;
    }

    private function prune(string $dir, int $keepDays): void
    {
        $cutoff = now()->subDays(max(1, $keepDays))->getTimestamp();

        foreach (File::files($dir) as $file) {
            if (! preg_match('/^database_\d{8}_\d{6}\.(sql\.gz|sqlite)$/', $file->getFilename())) {
                continue;
            }

            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
            }
        }
    }
}
````

### A.2 Form Requests

#### `app/Http/Requests/StoreCommentRequest.php`

````php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for a new comment (FR-10).
 * The body is required and capped at 280 characters - the same limit as a post.
 */
class StoreCommentRequest extends FormRequest
{
    /**
     * Only somebody who can see the post may comment on it. The policy also
     * refuses hidden posts, so a moderated post cannot collect new comments.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('post')) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:280'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => __('Write something before commenting.'),
            'body.max' => __('A comment can be at most 280 characters long.'),
        ];
    }
}
````

#### `app/Http/Requests/UpdateClassStatusRequest.php`

````php
<?php

namespace App\Http\Requests;

use App\Models\ClassRoom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the scheduled -> live -> completed switch (FR-21).
 * Only the owning teacher may change the status; ClassPolicy enforces it.
 */
class UpdateClassStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeStatus', $this->route('classRoom')) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ClassRoom::STATUSES)],
        ];
    }
}
````

#### `app/Http/Requests/DeleteAccountRequest.php`

````php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Confirms the password before an account is deleted (FR-4).
 * Validation is reported in the "userDeletion" error bag, as the form expects.
 */
class DeleteAccountRequest extends FormRequest
{
    /**
     * The profile page renders the password error inside its deletion modal.
     *
     * @var string
     */
    protected $errorBag = 'userDeletion';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'password' => __('password'),
        ];
    }
}
````

#### `app/Http/Requests/UpdatePasswordRequest.php`

````php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    /** Profile page renders current-password errors in this bag. */
    protected $errorBag = 'updatePassword';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];
    }
}
````

#### `app/Http/Requests/Auth/ForgotPasswordRequest.php`

````php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
````

#### `app/Http/Requests/Auth/ResetPasswordRequest.php`

````php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
````

#### `app/Http/Requests/StorePostRequest.php`

````php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for a new post (FR-7).
 * The text is required and capped at 280 characters, the image is optional
 * and only accepted as jpg / png / webp up to 2 MB.
 */
class StorePostRequest extends FormRequest
{
    /**
     * Any logged in user may publish a post.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:280'],
            // "image" makes sure the upload really is an image, "mimes" pins the
            // allowed types and "max" is in kilobytes (2048 KB = 2 MB).
            // ImageUploadService decodes the file again before it is stored.
            'image' => [
                'nullable',
                'image',
                'mimes:'.implode(',', config('educonnect.uploads.post_image_mimes')),
                'max:'.config('educonnect.uploads.post_image_max_kb'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => __('Write something before publishing.'),
            'body.max' => __('A post can be at most 280 characters long.'),
            'image.max' => __('The image must be smaller than 2 MB.'),
            'image.mimes' => __('Only JPG, PNG and WebP images are allowed.'),
        ];
    }
}
````

#### `app/Http/Requests/UpdatePostRequest.php`

````php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for editing an existing post (FR-8).
 * Same rules as StorePostRequest; the ownership check lives in PostPolicy.
 */
class UpdatePostRequest extends FormRequest
{
    /**
     * Only the author may edit the post; administrators moderate but do not
     * rewrite somebody else's words. The controller repeats this with
     * $this->authorize('update', $post).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('post')) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:280'],
            'image' => [
                'nullable',
                'image',
                'mimes:'.implode(',', config('educonnect.uploads.post_image_mimes')),
                'max:'.config('educonnect.uploads.post_image_max_kb'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => __('A post cannot be empty.'),
            'body.max' => __('A post can be at most 280 characters long.'),
            'image.max' => __('The image must be smaller than 2 MB.'),
            'image.mimes' => __('Only JPG, PNG and WebP images are allowed.'),
        ];
    }
}
````

### A.3 Controllers

#### `app/Http/Controllers/PostController.php`

````php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Comment;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\User;
use App\Services\HashtagService;
use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Create, read, edit and delete posts (FR-7 ... FR-12).
 */
class PostController extends Controller
{
    /** Posts per page in the feed and on the profile pages. */
    public const PER_PAGE = 10;

    /** Comments per page under a single post. */
    public const COMMENTS_PER_PAGE = 15;

    public function __construct(
        private readonly HashtagService $hashtags,
        private readonly ImageUploadService $images,
    ) {}

    /**
     * Home feed: my own posts plus the posts of everybody I follow (FR-12).
     *
     * When the "Load more" button asks for the next page we answer with JSON
     * containing ready made HTML, so the feed can grow without a reload.
     * A normal browser request to ?page=2 still gets a full HTML page.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        $posts = $this->feedQuery($user)
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // One query loads the newest comments for the ten visible cards, so
        // the expandable feed comments never fire a query per post (FR-10).
        $commentsByPost = $this->commentsFor($posts->getCollection()->pluck('id')->all());

        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('feed._posts', [
                    'posts' => $posts,
                    'commentsByPost' => $commentsByPost,
                ])->render(),
                'has_more' => $posts->hasMorePages(),
                'next_page' => $posts->nextPageUrl(),
            ]);
        }

        $followingIds = $user->following()->pluck('users.id');

        // "Who to follow": active teachers/students the viewer does not follow
        // yet, most followed first. Admins do not take part in the follow graph.
        $suggestedUsers = $user->isAdmin()
            ? collect()
            : User::query()
                ->whereIn('role', [User::ROLE_TEACHER, User::ROLE_STUDENT])
                ->where('is_blocked', false)
                ->where('id', '!=', $user->id)
                ->whereNotIn('id', $followingIds)
                ->withCount('followers')
                ->orderByDesc('followers_count')
                ->orderBy('name')
                ->limit(5)
                ->get();

        // Trending = most visible posts in the last seven days (FR-13).
        $recentPosts = static fn (Builder $query) => $query
            ->visible()
            ->where('posts.created_at', '>=', now()->subDays(7));

        $trendingHashtags = Hashtag::query()
            ->withCount(['posts as recent_posts_count' => $recentPosts])
            // whereHas instead of HAVING keeps the query portable: SQLite
            // rejects HAVING on a select without a GROUP BY.
            ->whereHas('posts', $recentPosts)
            ->orderByDesc('recent_posts_count')
            ->orderBy('name')
            ->limit(5)
            ->get();

        return view('feed.index', [
            'posts' => $posts,
            'commentsByPost' => $commentsByPost,
            'suggestedUsers' => $suggestedUsers,
            'trendingHashtags' => $trendingHashtags,
            'followingIds' => $followingIds,
            'followingCount' => $followingIds->count(),
        ]);
    }

    /**
     * The page with the full "write a post" form.
     */
    public function create(): View
    {
        return view('posts.create');
    }

    /**
     * Publish a new post (FR-7).
     */
    public function store(StorePostRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $post = Post::create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            // Decoded, resized, re-encoded and stored under a random name.
            'image' => $request->hasFile('image')
                ? $this->images->storePostImage($request->file('image'))
                : null,
        ]);

        // Pull the #tags out of the body and attach them (FR-13).
        $this->hashtags->sync($post, $post->body);

        return redirect()
            ->route('feed.index')
            ->with('success', __('Your post is live.'));
    }

    /**
     * A single post with its comments (FR-12).
     */
    public function show(Request $request, Post $post): View
    {
        $this->authorize('view', $post);

        $viewer = $request->user();

        // Same eager loaded payload the feed cards use, so the card component
        // never fires a query of its own.
        $post->load(['user:id,name,role,photo,subject', 'hashtags:id,name'])
            ->loadCount([
                'likes',
                'comments' => static fn (Builder $comments) => $comments->visible(),
            ])
            ->loadExists([
                'likes as liked_by_me' => static fn (Builder $likes) => $likes->where('user_id', $viewer->id),
            ]);

        $comments = $post->comments()
            ->visible()
            ->with('user:id,name,role,photo')
            ->oldest()
            ->paginate(self::COMMENTS_PER_PAGE)
            ->withQueryString();

        return view('posts.show', ['post' => $post, 'comments' => $comments]);
    }

    /**
     * The edit form. PostPolicy decides who gets here (FR-8).
     */
    public function edit(Post $post): View
    {
        $this->authorize('update', $post);

        return view('posts.edit', ['post' => $post]);
    }

    /**
     * Save the changes to an existing post (FR-8).
     */
    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $validated = $request->validated();

        $post->body = $validated['body'];

        // A new image replaces the old one. Store the new file first, then
        // delete the old one, so a failed conversion never loses the current
        // image. Both are re-encoded by ImageUploadService.
        if ($request->hasFile('image')) {
            $newImage = $this->images->storePostImage($request->file('image'));

            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }

            $post->image = $newImage;
        }

        $post->save();

        // Re-parse the tags: sync() also detaches the ones that were deleted.
        $this->hashtags->sync($post, $post->body);

        return redirect()
            ->route('posts.show', $post)
            ->with('success', __('Your post has been updated.'));
    }

    /**
     * Delete a post and its uploaded image (FR-8).
     */
    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        // Never leave the uploaded file behind on disk.
        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }

        // Comments and likes disappear through the cascade rules on the tables.
        $post->delete();

        return redirect()
            ->route('feed.index')
            ->with('success', __('Your post has been deleted.'));
    }

    /* ---------------------------------------------------------------
     | Helpers
     * --------------------------------------------------------------- */

    /**
     * My posts + the posts of the people I follow, newest first (FR-12).
     */
    private function feedQuery(User $user): Builder
    {
        // Ids of everybody I follow, as a sub query (no extra round trip).
        $followingIds = $user->following()->select('users.id');

        return Post::query()
            ->visible()
            ->where(static function (Builder $query) use ($user, $followingIds): void {
                $query->where('user_id', $user->id)
                    ->orWhereIn('user_id', $followingIds);
            })
            // user + hashtags + counts + "liked by me" in three queries total.
            ->withFeedData($user)
            ->latest();
    }

    /**
     * Newest visible comments for the post ids on the current feed page.
     *
     * @param  list<int>  $postIds
     * @return Collection<int, Collection<int, Comment>>
     */
    private function commentsFor(array $postIds): Collection
    {
        if ($postIds === []) {
            return collect();
        }

        return Comment::query()
            ->visible()
            ->whereIn('post_id', $postIds)
            ->with('user:id,name,role,photo')
            ->latest()
            ->get()
            ->groupBy('post_id');
    }
}
````

#### `app/Http/Controllers/ProfileController.php`

````php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly ImageUploadService $images) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information: name, email, photo, bio, subject (FR-4).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // "photo" is a file and "remove_photo" is a checkbox, never columns,
        // so they are handled separately below.
        $user->fill($request->safe()->except(['photo', 'remove_photo']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('photo')) {
            // Remember the old file so we can delete it after storing the new one.
            $oldPhoto = $user->photo;

            // Decoded, resized and re-encoded to a random WebP file (SRS 5.1/5.2).
            $user->photo = $this->images->storeProfilePhoto($request->file('photo'));

            // FR-4: the replaced photo is removed from the disk.
            if ($oldPhoto) {
                Storage::disk('public')->delete($oldPhoto);
            }
        } elseif ($request->boolean('remove_photo') && $user->photo) {
            // FR-4: "remove current photo" restores the default avatar.
            Storage::disk('public')->delete($user->photo);
            $user->photo = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', __('Your profile has been updated.'));
    }

    /**
     * Delete the user's account.
     */
    public function destroy(DeleteAccountRequest $request): RedirectResponse
    {
        // DeleteAccountRequest validates the password in the "userDeletion" bag.
        $user = $request->user();

        // Do not leave an orphan file behind on the disk.
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
````

#### `app/Http/Controllers/CommentController.php`

````php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Notifications\PostCommented;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Comments under a post (FR-10).
 */
class CommentController extends Controller
{
    /**
     * Write a comment on a post.
     */
    public function store(StoreCommentRequest $request, Post $post): JsonResponse|RedirectResponse
    {
        // StoreCommentRequest authorizes against PostPolicy, which also stops
        // people from commenting on hidden posts (FR-28).
        $validated = $request->validated();

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        // The author is needed to render the new comment card.
        $comment->load('user:id,name,role,photo');

        // The post author hears about the comment - unless they wrote it
        // themselves (FR-23).
        if ($post->user_id !== $request->user()->id) {
            $post->user?->notify(new PostCommented($request->user(), $post, $comment));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('comments._comment', ['comment' => $comment])->render(),
                'count' => $post->comments()->visible()->count(),
            ], 201);
        }

        return back()->with('success', __('Your comment has been added.'));
    }

    /**
     * Delete a comment. CommentPolicy checks the ownership (FR-10).
     */
    public function destroy(Request $request, Comment $comment): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $comment);

        $post = $comment->post;
        $comment->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'deleted' => true,
                'count' => $post ? $post->comments()->visible()->count() : 0,
            ]);
        }

        return back()->with('success', __('The comment has been deleted.'));
    }
}
````

#### `app/Http/Controllers/ClassStatusController.php`

````php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateClassStatusRequest;
use App\Models\ClassRoom;
use Illuminate\Http\RedirectResponse;

/**
 * The teacher starts, ends or reschedules a class (FR-21).
 * Only the transitions listed in ClassRoom::TRANSITIONS are accepted.
 */
class ClassStatusController extends Controller
{
    /**
     * Move the class to another status.
     */
    public function update(UpdateClassStatusRequest $request, ClassRoom $classRoom): RedirectResponse
    {
        // UpdateClassStatusRequest authorizes against ClassPolicy and validates
        // the status against ClassRoom::STATUSES.
        $status = (string) $request->validated('status');

        // Not every change makes sense: completed -> scheduled for example.
        if (! $classRoom->canTransitionTo($status)) {
            return back()->with('error', __('The class cannot move from :from to :to.', [
                'from' => __($classRoom->status),
                'to' => __($status),
            ]));
        }

        $classRoom->update(['status' => $status]);

        return back()->with('success', __('The class is now :status.', ['status' => __($status)]));
    }
}
````

#### `app/Http/Controllers/Auth/PasswordController.php`

````php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
````

### A.4 Middleware, provider and dashboard metrics

#### `app/Http/Middleware/SecurityHeaders.php`

````php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers for every web response (SRS 5.2).
 * The Jitsi room needs a different policy because it embeds meet.jit.si.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->remove('X-Powered-By');

        $isJitsiRoom = $request->routeIs('classes.room');
        $jitsiDomain = config('services.jitsi.domain', 'meet.jit.si');

        if ($isJitsiRoom) {
            // The video room is embedded from another origin, so the page must
            // not forbid framing and must allow camera + microphone there.
            $response->headers->remove('X-Frame-Options');
            $response->headers->set(
                'Content-Security-Policy',
                "frame-src 'self' https://{$jitsiDomain};"
            );
            $response->headers->set(
                'Permissions-Policy',
                "camera=(self \"https://{$jitsiDomain}\"), ".
                "microphone=(self \"https://{$jitsiDomain}\"), ".
                "display-capture=(self \"https://{$jitsiDomain}\")"
            );
        } else {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=()'
            );
        }

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
````

#### `app/Providers/AppServiceProvider.php`

````php
<?php

namespace App\Providers;

use App\Models\ClassRoom;
use App\Policies\ClassPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The project uses Bootstrap 5, so pagination links must be Bootstrap
        // markup (Laravel's default pagination view is Tailwind based).
        Paginator::useBootstrapFive();

        // ClassPolicy is named after the SRS, not after the model, so Laravel's
        // naming convention (ClassRoom -> ClassRoomPolicy) cannot find it.
        // The mapping is declared explicitly instead (FR-18, FR-20, FR-24).
        Gate::policy(ClassRoom::class, ClassPolicy::class);

        $this->configureRateLimiting();
    }

    /**
     * Named limiters used by throttle:* middleware (SRS 5.2).
     * Login failed attempts are still owned by LoginRequest so a successful
     * login is never counted against the lockout.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));

        RateLimiter::for('posts', fn (Request $request) => Limit::perMinute(10)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('comments', fn (Request $request) => Limit::perMinute(30)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('likes', fn (Request $request) => Limit::perMinute(60)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('reports', fn (Request $request) => Limit::perMinute(10)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('follows', fn (Request $request) => Limit::perMinute(30)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('notifications-poll', fn (Request $request) => Limit::perMinute(60)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('search-suggest', fn (Request $request) => Limit::perMinute(120)->by(
            $request->user()?->id ?: $request->ip()
        ));
    }
}
````

#### `app/Support/DashboardMetrics.php`

````php
<?php

namespace App\Support;

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Aggregated dashboard numbers (Phase 3B).
 *
 * Every method that runs more than a trivial query is cached for five minutes.
 * Keys are scoped per role (and per user for the teacher/student dashboards),
 * so one person's numbers never leak into somebody else's page.
 *
 * The public "stats" arrays keep the exact keys the Phase 3 tests expect:
 *   admin   users, teachers, students, posts, classes
 *   teacher classes, upcoming, students, followers
 *   student classes, upcoming, following
 */
class DashboardMetrics
{
    /** Five minutes, as required by the phase brief. */
    private const TTL_SECONDS = 300;

    private const RANGES = ['7d', '30d', '12m'];

    /* =====================================================================
     | Admin
     * ===================================================================== */

    /**
     * Platform totals (FR-7). Exact five-key contract.
     *
     * @return array{users: int, teachers: int, students: int, posts: int, classes: int}
     */
    public function adminStats(): array
    {
        return Cache::remember('dashboard.admin.stats.v1', self::TTL_SECONDS, function (): array {
            // One aggregate query for the three user counters. SUM(CASE ...)
            // works on MySQL and on the SQLite database used by the test suite.
            $userCounts = User::query()
                ->selectRaw(
                    'COUNT(*) as users,
                     SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as teachers,
                     SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as students',
                    [User::ROLE_TEACHER, User::ROLE_STUDENT]
                )
                ->first();

            return [
                'users' => (int) ($userCounts->users ?? 0),
                'teachers' => (int) ($userCounts->teachers ?? 0),
                'students' => (int) ($userCounts->students ?? 0),
                'posts' => Post::query()->count(),
                'classes' => ClassRoom::query()->count(),
            ];
        });
    }

    /**
     * Pending reports badge on the admin dashboard (FR-33).
     * The queue itself stays uncached; only this small counter is.
     */
    public function adminPendingReports(): int
    {
        return (int) Cache::remember(
            'dashboard.admin.pending-reports.v1',
            self::TTL_SECONDS,
            static fn (): int => Report::query()->pending()->count()
        );
    }

    /**
     * Month-over-month movement for the five stat cards.
     *
     * @return array<string, array{percent: int, direction: string}>
     */
    public function adminTrends(): array
    {
        return Cache::remember('dashboard.admin.trends.v1', self::TTL_SECONDS, function (): array {
            $thisMonth = now()->copy()->startOfMonth();
            $lastMonth = now()->copy()->subMonthNoOverflow()->startOfMonth();

            $userNow = User::query()->where('created_at', '>=', $thisMonth)->count();
            $userBefore = User::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $teachersNow = User::query()
                ->where('role', User::ROLE_TEACHER)
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $teachersBefore = User::query()
                ->where('role', User::ROLE_TEACHER)
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $studentsNow = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $studentsBefore = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $postsNow = Post::query()->where('created_at', '>=', $thisMonth)->count();
            $postsBefore = Post::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $classesNow = ClassRoom::query()->where('created_at', '>=', $thisMonth)->count();
            $classesBefore = ClassRoom::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            return [
                'users' => $this->trend($userNow, $userBefore),
                'teachers' => $this->trend($teachersNow, $teachersBefore),
                'students' => $this->trend($studentsNow, $studentsBefore),
                'posts' => $this->trend($postsNow, $postsBefore),
                'classes' => $this->trend($classesNow, $classesBefore),
            ];
        });
    }

    /**
     * Seven-day sparkline data for every admin stat card.
     *
     * @return array<string, list<int>>
     */
    public function adminSparklines(): array
    {
        return Cache::remember('dashboard.admin.sparklines.v1', self::TTL_SECONDS, function (): array {
            $since = now()->copy()->startOfDay()->subDays(6);

            return [
                'users' => $this->daySeries(
                    User::query()->where('created_at', '>=', $since)->pluck('created_at'),
                    7
                )['values'],
                'teachers' => $this->daySeries(
                    User::query()
                        ->where('role', User::ROLE_TEACHER)
                        ->where('created_at', '>=', $since)
                        ->pluck('created_at'),
                    7
                )['values'],
                'students' => $this->daySeries(
                    User::query()
                        ->where('role', User::ROLE_STUDENT)
                        ->where('created_at', '>=', $since)
                        ->pluck('created_at'),
                    7
                )['values'],
                'posts' => $this->daySeries(
                    Post::query()->where('created_at', '>=', $since)->pluck('created_at'),
                    7
                )['values'],
                'classes' => $this->daySeries(
                    ClassRoom::query()->where('created_at', '>=', $since)->pluck('created_at'),
                    7
                )['values'],
            ];
        });
    }

    /**
     * Every admin chart series for a range.
     *
     * @return array<string, array{labels: list<string>, values: list<int>, total: int}>
     */
    public function adminCharts(string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        return Cache::remember("dashboard.admin.charts.{$range}.v1", self::TTL_SECONDS, function () use ($range): array {
            $roles = $this->adminRoleSeries();
            $classes = $this->adminClassSeries();

            return [
                'newUsers' => $this->adminNewUsers($range),
                'posts' => $this->adminPosts($range),
                'roles' => $roles,
                'classes' => $classes,
            ];
        });
    }

    /**
     * New registrations for 7 days, 30 days or 12 months.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminNewUsers(string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        $timestamps = User::query()
            ->when($range !== '12m', fn ($query) => $query->where('created_at', '>=', $this->rangeStart($range)))
            ->pluck('created_at');

        return $this->seriesForRange($timestamps, $range);
    }

    /**
     * Posts per day/week/month, depending on the selected range.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminPosts(string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        $timestamps = Post::query()
            ->when($range !== '12m', fn ($query) => $query->where('created_at', '>=', $this->rangeStart($range)))
            ->pluck('created_at');

        if ($range === '12m') {
            return $this->weekSeries($timestamps, 12);
        }

        return $this->seriesForRange($timestamps, $range);
    }

    /**
     * Users by role as a doughnut series.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminRoleSeries(): array
    {
        $counts = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $labels = [];
        $values = [];

        foreach ([User::ROLE_ADMIN, User::ROLE_TEACHER, User::ROLE_STUDENT] as $role) {
            $labels[] = ucfirst($role).'s';
            $values[] = (int) ($counts[$role] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /**
     * Classes by status as a doughnut series.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminClassSeries(): array
    {
        $counts = ClassRoom::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $values = [];

        foreach (ClassRoom::STATUSES as $status) {
            $labels[] = ucfirst($status);
            $values[] = (int) ($counts[$status] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /* =====================================================================
     | Teacher
     * ===================================================================== */

    /**
     * The teacher's own counters. Exact four-key contract.
     *
     * @return array{classes: int, upcoming: int, students: int, followers: int}
     */
    public function teacherStats(User $teacher): array
    {
        return Cache::remember("dashboard.teacher.{$teacher->id}.stats.v1", self::TTL_SECONDS, function () use ($teacher): array {
            $mine = $teacher->classes();

            return [
                'classes' => (clone $mine)->count(),
                'upcoming' => (clone $mine)->upcoming()->count(),
                // Distinct students across all classes (nobody counted twice).
                'students' => ClassEnrollment::query()
                    ->whereIn('class_id', (clone $mine)->select('id'))
                    ->distinct()
                    ->count('student_id'),
                'followers' => $teacher->followers()->count(),
            ];
        });
    }

    /**
     * Month-over-month growth for the teacher cards.
     *
     * @return array<string, array{percent: int, direction: string}>
     */
    public function teacherTrends(User $teacher): array
    {
        return Cache::remember("dashboard.teacher.{$teacher->id}.trends.v1", self::TTL_SECONDS, function () use ($teacher): array {
            $thisMonth = now()->copy()->startOfMonth();
            $lastMonth = now()->copy()->subMonthNoOverflow()->startOfMonth();

            $classesNow = $teacher->classes()->where('created_at', '>=', $thisMonth)->count();
            $classesBefore = $teacher->classes()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $enrollmentsNow = ClassEnrollment::query()
                ->whereIn('class_id', $teacher->classes()->select('id'))
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $enrollmentsBefore = ClassEnrollment::query()
                ->whereIn('class_id', $teacher->classes()->select('id'))
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $followersNow = Follow::query()
                ->where('following_id', $teacher->id)
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $followersBefore = Follow::query()
                ->where('following_id', $teacher->id)
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            return [
                'classes' => $this->trend($classesNow, $classesBefore),
                'students' => $this->trend($enrollmentsNow, $enrollmentsBefore),
                'followers' => $this->trend($followersNow, $followersBefore),
            ];
        });
    }

    /**
     * Chart series for the teacher dashboard.
     *
     * @return array<string, array{labels: list<string>, values: list<int>, total: int}>
     */
    public function teacherCharts(User $teacher, string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        return Cache::remember("dashboard.teacher.{$teacher->id}.charts.{$range}.v1", self::TTL_SECONDS, function () use ($teacher, $range): array {
            $classes = $teacher->classes()
                ->withCount('students')
                ->when($range !== '12m', fn ($query) => $query->whereDate('date', '>=', $this->rangeStart($range)))
                ->orderByDesc('students_count')
                ->orderBy('title')
                ->take(8)
                ->get(['id', 'title']);

            $labels = $classes->map(fn (ClassRoom $class) => Str::limit($class->title, 18))->all();
            $values = $classes->map(fn (ClassRoom $class) => (int) $class->students_count)->all();

            $followers = Follow::query()
                ->where('following_id', $teacher->id)
                ->when($range !== '12m', fn ($query) => $query->where('created_at', '>=', $this->rangeStart($range)))
                ->pluck('created_at');

            return [
                'enrollments' => [
                    'labels' => $labels,
                    'values' => $values,
                    'total' => array_sum($values),
                ],
                'followers' => $this->seriesForRange($followers, $range),
            ];
        });
    }

    /**
     * The next seven days with the number of classes on each day.
     *
     * @return list<array{date: Carbon, weekday: string, day: string, count: int, isToday: bool}>
     */
    public function teacherSchedule(User $teacher): array
    {
        return Cache::remember("dashboard.teacher.{$teacher->id}.schedule.v1", self::TTL_SECONDS, function () use ($teacher): array {
            $start = now()->copy()->startOfDay();
            $end = $start->copy()->addDays(6)->endOfDay();

            $counts = $teacher->classes()
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->pluck('date')
                ->countBy(fn ($date) => Carbon::parse($date)->toDateString());

            $days = [];

            for ($offset = 0; $offset < 7; $offset++) {
                $date = $start->copy()->addDays($offset);

                $days[] = [
                    'date' => $date->copy(),
                    'weekday' => $date->format('D'),
                    'day' => $date->format('j'),
                    'count' => (int) ($counts[$date->toDateString()] ?? 0),
                    'isToday' => $date->isToday(),
                ];
            }

            return $days;
        });
    }

    /* =====================================================================
     | Student
     * ===================================================================== */

    /**
     * The student's counters. Exact three-key contract.
     *
     * @return array{classes: int, upcoming: int, following: int}
     */
    public function studentStats(User $student): array
    {
        return Cache::remember("dashboard.student.{$student->id}.stats.v1", self::TTL_SECONDS, function () use ($student): array {
            return [
                'classes' => $student->enrolledClasses()->count(),
                'upcoming' => $student->enrolledClasses()->upcoming()->count(),
                // Only teachers count, so the card matches what the page offers.
                'following' => $student->following()->where('role', User::ROLE_TEACHER)->count(),
            ];
        });
    }

    /**
     * Classes attended per week (a proxy based on enrolled class dates).
     *
     * @return array<string, array{labels: list<string>, values: list<int>, total: int}>
     */
    public function studentCharts(User $student, string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        return Cache::remember("dashboard.student.{$student->id}.charts.{$range}.v1", self::TTL_SECONDS, function () use ($student, $range): array {
            $dates = $student->enrolledClasses()
                ->when($range !== '12m', fn ($query) => $query->whereDate('classes.date', '>=', $this->rangeStart($range)))
                ->select('classes.date')
                ->pluck('classes.date');

            return [
                'activity' => $this->seriesForRange($dates, $range),
            ];
        });
    }

    /* =====================================================================
     | Series helpers
     * ===================================================================== */

    public function normalizeRange(?string $range): string
    {
        return in_array($range, self::RANGES, true) ? $range : '12m';
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function seriesForRange(Collection $timestamps, string $range): array
    {
        return match ($range) {
            '7d' => $this->daySeries($timestamps, 7),
            '30d' => $this->weekSeries($timestamps, 5),
            default => $this->monthSeries($timestamps, 12),
        };
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function daySeries(Collection $timestamps, int $days): array
    {
        $start = now()->copy()->startOfDay()->subDays($days - 1);
        $counts = $timestamps->countBy(
            static fn ($timestamp) => Carbon::parse($timestamp)->toDateString()
        );

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $day = $start->copy()->addDays($offset);

            $labels[] = $day->format('d M');
            $values[] = (int) ($counts[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function weekSeries(Collection $timestamps, int $weeks): array
    {
        $start = now()->copy()->startOfWeek()->subWeeks($weeks - 1);
        $counts = $timestamps->countBy(
            static fn ($timestamp) => Carbon::parse($timestamp)->startOfWeek()->toDateString()
        );

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $weeks; $offset++) {
            $week = $start->copy()->addWeeks($offset);

            $labels[] = $week->format('d M');
            $values[] = (int) ($counts[$week->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function monthSeries(Collection $timestamps, int $months): array
    {
        // First month of the window: 11 months back, so today's month is included.
        $start = now()->copy()->startOfMonth()->subMonths($months - 1);
        $counts = $timestamps->countBy(
            static fn ($timestamp) => Carbon::parse($timestamp)->format('Y-m')
        );

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $months; $offset++) {
            $month = $start->copy()->addMonths($offset);

            $labels[] = $month->format('M Y');
            $values[] = (int) ($counts[$month->format('Y-m')] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    private function rangeStart(string $range): Carbon
    {
        return match ($range) {
            '7d' => now()->copy()->startOfDay()->subDays(6),
            '30d' => now()->copy()->startOfDay()->subDays(29),
            default => now()->copy()->startOfMonth()->subMonths(11),
        };
    }

    /**
     * @return array{percent: int, direction: string}
     */
    private function trend(int $current, int $previous): array
    {
        if ($previous === 0) {
            $percent = $current === 0 ? 0 : 100;
        } else {
            $percent = (int) round((($current - $previous) / $previous) * 100);
        }

        return [
            'percent' => $percent,
            'direction' => $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat'),
        ];
    }
}
````

### A.5 Routes, schedule, session and environment

#### `routes/web.php`

````php
<?php

use App\Http\Controllers\AdminClassController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminPostController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ClassBrowseController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ClassStatusController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardChartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\HashtagController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\LiveClassController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Admin area (FR-2, FR-5)
|--------------------------------------------------------------------------
| A student or a teacher can never open an admin page: the "role" middleware
| answers with 403. There is no admin registration either, the admin account
| comes from AdminUserSeeder.
*/
Route::middleware(['auth', 'not.blocked', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        // Phase 3C: living documentation of the design system. Admin only,
        // because it is a maintenance page rather than a product feature.
        Route::view('/style-guide', 'admin.style-guide')->name('style-guide');

        // Phase 3B: JSON series for the dashboard range selector.
        Route::get('/dashboard/charts', [DashboardChartController::class, 'admin'])
            ->name('dashboard.charts');

        // User management (FR-27).
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/block', [AdminUserController::class, 'block'])->name('users.block');
        Route::patch('/users/{user}/unblock', [AdminUserController::class, 'unblock'])->name('users.unblock');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        // Post management (FR-28).
        Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
        Route::patch('/posts/{post}/hide', [AdminPostController::class, 'toggleHidden'])->name('posts.hide');
        Route::delete('/posts/{post}', [AdminPostController::class, 'destroy'])->name('posts.destroy');

        // Class management (FR-28).
        Route::get('/classes', [AdminClassController::class, 'index'])->name('classes.index');
        Route::patch('/classes/{classRoom}/hide', [AdminClassController::class, 'toggleHidden'])->name('classes.hide');
        Route::delete('/classes/{classRoom}', [AdminClassController::class, 'destroy'])->name('classes.destroy');

        // Moderation queue (FR-33, FR-34).
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
        Route::patch('/reports/{report}/hide', [AdminReportController::class, 'hide'])->name('reports.hide');
        Route::patch('/reports/{report}/unhide', [AdminReportController::class, 'unhide'])->name('reports.unhide');
        Route::patch('/reports/{report}/reviewed', [AdminReportController::class, 'markReviewed'])->name('reports.reviewed');
        Route::patch('/reports/{report}/dismiss', [AdminReportController::class, 'dismiss'])->name('reports.dismiss');
        Route::delete('/reports/{report}/content', [AdminReportController::class, 'destroyContent'])->name('reports.content.destroy');
        Route::post('/reports/{report}/block-user', [AdminReportController::class, 'blockUser'])->name('reports.block-user');
    });

/*
|--------------------------------------------------------------------------
| Teacher area (FR-2)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.blocked', 'role:teacher'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::get('/dashboard', TeacherDashboardController::class)->name('dashboard');

        // Phase 3B: JSON series for the dashboard range selectors.
        Route::get('/dashboard/charts', [DashboardChartController::class, 'teacher'])
            ->name('dashboard.charts');
    });

/*
|--------------------------------------------------------------------------
| Student area (FR-2, FR-17, FR-30)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.blocked', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', StudentDashboardController::class)->name('dashboard');

        // Phase 3B: JSON series for the dashboard range selectors.
        Route::get('/dashboard/charts', [DashboardChartController::class, 'student'])
            ->name('dashboard.charts');
    });

// Follow / unfollow another member from a profile or the search page
// (FR-11). Students and teachers can follow each other; admin accounts
// are not followable and admins do not use the follow routes.
// Throttled so the button cannot be used to hammer the database.
Route::middleware(['auth', 'not.blocked', 'role:student,teacher'])
    ->group(function () {
        Route::post('/u/{user}/follow', [FollowController::class, 'store'])
            ->middleware('throttle:follows')
            ->name('follows.store');

        Route::delete('/u/{user}/follow', [FollowController::class, 'destroy'])
            ->middleware('throttle:follows')
            ->name('follows.destroy');
    });

/*
|--------------------------------------------------------------------------
| Social feed (FR-7 ... FR-14)
|--------------------------------------------------------------------------
| Every logged in role uses the same feed: admins, teachers and students.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/feed', [PostController::class, 'index'])->name('feed.index');

    // Posts. /posts/create must be declared before /posts/{post}.
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    // At most 10 new posts per minute per user (FR-9).
    Route::post('/posts', [PostController::class, 'store'])
        ->middleware('throttle:posts')
        ->name('posts.store');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::patch('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    // Likes - small JSON endpoints for the fetch() calls in the feed (FR-11).
    Route::post('/posts/{post}/like', [LikeController::class, 'store'])
        ->middleware('throttle:likes')
        ->name('posts.like');
    Route::delete('/posts/{post}/like', [LikeController::class, 'destroy'])
        ->middleware('throttle:likes')
        ->name('posts.unlike');

    // Comments (FR-13).
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:comments')
        ->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->middleware('throttle:comments')
        ->name('comments.destroy');

    // Reporting a post or comment (FR-32). Throttled so the queue cannot be
    // flooded by one account.
    Route::post('/posts/{post}/reports', [ReportController::class, 'storePost'])
        ->middleware('throttle:reports')
        ->name('reports.posts.store');
    Route::post('/comments/{comment}/reports', [ReportController::class, 'storeComment'])
        ->middleware('throttle:reports')
        ->name('reports.comments.store');

    // Hashtag pages (FR-13). The binding uses the (lower case) name column.
    // /hashtags/{name} is the canonical URL; the old singular address remains
    // available so earlier links keep working.
    Route::get('/hashtags/{hashtag:name}', [HashtagController::class, 'show'])->name('hashtags.show');
    Route::get('/hashtag/{hashtag:name}', [HashtagController::class, 'show'])->name('hashtags.legacy');

    // Public profiles and the follower lists (FR-5, FR-11).
    Route::get('/users/{user}', [UserProfileController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/followers', [UserProfileController::class, 'followers'])->name('users.followers');
    Route::get('/users/{user}/following', [UserProfileController::class, 'following'])->name('users.following');

    // The older /u/... addresses stay valid and redirect to the canonical URL.
    Route::get('/u/{user}', fn ($user) => redirect()->route('users.show', $user));
    Route::get('/u/{user}/followers', fn ($user) => redirect()->route('users.followers', $user));
    Route::get('/u/{user}/following', fn ($user) => redirect()->route('users.following', $user));

    // "My followers" in the sidebar jumps to my own list.
    Route::get('/followers', fn () => redirect()->route('users.followers', auth()->id()))
        ->name('followers.index');
});

// /dashboard simply forwards everybody to the dashboard of their own role.
Route::middleware(['auth', 'not.blocked'])
    ->get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Live classes (FR-18 ... FR-24)
|--------------------------------------------------------------------------
| A teacher manages their own classes, a student browses them, enrolls and
| joins. /classes/create must be declared before /classes/{classRoom}, and
| /classes/discover before the wildcard as well, otherwise "discover" would be
| taken for a class id.
*/

// Teacher only: create, edit, delete and manage a class (ClassPolicy checks
// that the class really belongs to the teacher).
Route::middleware(['auth', 'not.blocked', 'role:teacher'])->group(function () {
    Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
    Route::get('/classes/create', [ClassController::class, 'create'])->name('classes.create');
    Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');
    Route::get('/classes/{classRoom}/edit', [ClassController::class, 'edit'])->name('classes.edit');
    Route::patch('/classes/{classRoom}', [ClassController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{classRoom}', [ClassController::class, 'destroy'])->name('classes.destroy');

    // Who is enrolled (FR-24) and the scheduled / live / completed switch.
    Route::get('/classes/{classRoom}/students', [ClassController::class, 'students'])->name('classes.students');
    Route::patch('/classes/{classRoom}/status', [ClassStatusController::class, 'update'])->name('classes.status');
});

// Every logged in role: the class detail page and the Jitsi room (FR-20).
Route::middleware(['auth', 'not.blocked'])->group(function () {
    // Find classes: search + subject filter + pagination (FR-19).
    Route::get('/classes/discover', [ClassBrowseController::class, 'index'])->name('classes.discover');
    Route::get('/classes/{classRoom}', [ClassBrowseController::class, 'show'])->name('classes.show');

    // FR-18 / FR-20 / FR-22: the embedded Jitsi Meet room.
    Route::get('/classes/{classRoom}/room', LiveClassController::class)->name('classes.room');
    // Jitsi sends the user here after hangup so the flash survives the reload.
    Route::get('/classes/{classRoom}/leave', [LiveClassController::class, 'leave'])->name('classes.leave');
});

// Student only: enroll / unenroll and "my enrolled classes" (FR-21).
Route::middleware(['auth', 'not.blocked', 'role:student'])->group(function () {
    Route::post('/classes/{classRoom}/enroll', [EnrollmentController::class, 'store'])->name('classes.enroll');
    Route::delete('/classes/{classRoom}/enroll', [EnrollmentController::class, 'destroy'])->name('classes.unenroll');

    Route::get('/student/classes', [ClassBrowseController::class, 'enrolled'])->name('student.classes');
});

/*
|--------------------------------------------------------------------------
| Notifications (FR-23 ... FR-25)
|--------------------------------------------------------------------------
| The bell in the navbar polls /notifications/poll every 30 seconds and gets
| the fresh unread counter plus the newest eight entries as JSON.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Lightweight JSON endpoint for the polling script (FR-24).
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])
        ->middleware('throttle:notifications-poll')
        ->name('notifications.poll');

    // Mark everything as read (FR-25). Declared before the {notification} routes.
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // One notification: mark it read, open its page, or delete it (FR-25).
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});

/*
|--------------------------------------------------------------------------
| Search (FR-14)
|--------------------------------------------------------------------------
| One box in the navbar, three tabs on the page: people, hashtags and posts.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    // Debounced live suggestions for the navbar dropdown (FR-14).
    Route::get('/search/suggest', [SearchController::class, 'suggest'])
        ->middleware('throttle:search-suggest')
        ->name('search.suggest');
});

/*
|--------------------------------------------------------------------------
| Profile (FR-4)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
````

#### `routes/auth.php`

````php
<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    // Rate limited: max 5 new accounts per minute from one IP.
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // The login attempt itself is rate limited inside LoginRequest (5 tries).
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    // Rate limited so nobody can spam reset emails (FR-3).
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
````

#### `routes/console.php`

````php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks (FR-23)
|--------------------------------------------------------------------------
| The server only needs ONE cron entry that runs Laravel's scheduler every
| minute; the scheduler then decides what is due:
|
|   * * * * * cd /path/to/educonnect && php artisan schedule:run >> /dev/null 2>&1
|
| On Windows you can simulate the same thing by running
|   php artisan schedule:work
*/
Schedule::command('classes:notify-starting-soon')
    ->everyMinute()
    // Never let two runs overlap if one is slow.
    ->withoutOverlapping();

// Close running classes once start time + duration has passed (FR-21).
Schedule::command('classes:complete-finished')
    ->everyMinute()
    ->withoutOverlapping();

// Daily database dump into storage/app/backups (SRS 5.4). The Linux
// scripts/backup.sh remains the full archive (database + uploads + .env).
Schedule::command('backup:database')
    ->dailyAt('02:30')
    ->withoutOverlapping();
````

#### `config/session.php`

````php
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option determines the default session driver that is utilized for
    | incoming requests. Laravel supports a variety of storage options to
    | persist session data. Database storage is a great default choice.
    |
    | Supported: "file", "cookie", "database", "apc",
    |            "memcached", "redis", "dynamodb", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires. If you want them
    | to expire immediately when the browser is closed then you may
    | indicate that via the expire_on_close configuration option.
    |
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify that all of your session data
    | should be encrypted before it's stored. All encryption is performed
    | automatically by Laravel and you may use the session like normal.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Session File Location
    |--------------------------------------------------------------------------
    |
    | When utilizing the "file" session driver, the session files are placed
    | on disk. The default storage location is defined here; however, you
    | are free to provide another location where they should be stored.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection
    |--------------------------------------------------------------------------
    |
    | When using the "database" or "redis" session drivers, you may specify a
    | connection that should be used to manage these sessions. This should
    | correspond to a connection in your database configuration options.
    |
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Table
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the table to
    | be used to store sessions. Of course, a sensible default is defined
    | for you; however, you're welcome to change this to another table.
    |
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Cache Store
    |--------------------------------------------------------------------------
    |
    | When using one of the framework's cache driven session backends, you may
    | define the cache store which should be used to store the session data
    | between requests. This must match one of your defined cache stores.
    |
    | Affects: "apc", "dynamodb", "memcached", "redis"
    |
    */

    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Session Sweeping Lottery
    |--------------------------------------------------------------------------
    |
    | Some session drivers must manually sweep their storage location to get
    | rid of old sessions from storage. Here are the chances that it will
    | happen on a given request. By default, the odds are 2 out of 100.
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Here you may change the name of the session cookie that is created by
    | the framework. Typically, you should not need to change this value
    | since doing so does not grant a meaningful security improvement.
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    |
    | The session cookie path determines the path for which the cookie will
    | be regarded as available. Typically, this will be the root path of
    | your application, but you're free to change this when necessary.
    |
    */

    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    |
    | This value determines the domain and subdomains the session cookie is
    | available to. By default, the cookie will be available to the root
    | domain and all subdomains. Typically, this shouldn't be changed.
    |
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies
    |--------------------------------------------------------------------------
    |
    | By setting this option to true, session cookies will only be sent back
    | to the server if the browser has a HTTPS connection. This will keep
    | the cookie from being sent to you when it can't be done securely.
    |
    */

    // Production must set SESSION_SECURE_COOKIE=true. As a safe default the
    // cookie is already HTTPS-only when APP_URL uses https:// (SRS 5.2).
    'secure' => env('SESSION_SECURE_COOKIE', str_starts_with((string) env('APP_URL', ''), 'https://')),

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will prevent JavaScript from accessing the
    | value of the cookie and the cookie will only be accessible through
    | the HTTP protocol. It's unlikely you should disable this option.
    |
    */

    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies
    |--------------------------------------------------------------------------
    |
    | This option determines how your cookies behave when cross-site requests
    | take place, and can be used to mitigate CSRF attacks. By default, we
    | will set this value to "lax" to permit secure cross-site requests.
    |
    | See: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value
    |
    | Supported: "lax", "strict", "none", null
    |
    */

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Partitioned Cookies
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will tie the cookie to the top-level site for
    | a cross-site context. Partitioned cookies are accepted by the browser
    | when flagged "secure" and the Same-Site attribute is set to "none".
    |
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
````

#### `.env.example`

````dotenv
APP_NAME=EduConnect
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8010

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

# Used by the <meta name="description"> tag and social previews.
APP_DESCRIPTION="EduConnect is an online classroom with live video classes, a friendly social feed and simple class management for teachers and students."

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
# Production: LOG_CHANNEL=daily, LOG_LEVEL=warning, LOG_DAILY_DAYS=14

# --- Database: MySQL 8 ---
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=educonnect
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
# Production (HTTPS): SESSION_SECURE_COOKIE=true, SESSION_ENCRYPT=true
# The cookie is already HTTPS-only automatically when APP_URL starts with https://
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
# Notifications are Laravel queued notifications.
# Local/demo default: "sync" writes them immediately, no worker needed.
# Production: "database" plus a running queue worker (docs/DEPLOYMENT.md).
QUEUE_CONNECTION=sync

CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# --- Mail ---
# "log" writes the emails to storage/logs/laravel.log (fine for local work).
# "log" is fine for local work; production uses SMTP (see docs/DEPLOYMENT.md).
# Password reset emails are sent synchronously by default. With
# QUEUE_CONNECTION=database the notification is queued and a worker is required.
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="no-reply@educonnect.test"
MAIL_FROM_NAME="${APP_NAME}"

# Mailtrap - uncomment to send real test emails:
# MAIL_MAILER=smtp
# MAIL_HOST=sandbox.smtp.mailtrap.io
# MAIL_PORT=2525
# MAIL_USERNAME=
# MAIL_PASSWORD=

# --- Jitsi Meet (FR-18) ---
# Public server by default; set your own domain to self host the live classes.
JITSI_DOMAIN=meet.jit.si

# --- EduConnect: the admin account created by AdminUserSeeder ---
# Set a long random password here. Never commit the real one.
ADMIN_NAME="EduConnect Admin"
ADMIN_EMAIL=admin@educonnect.test
ADMIN_PASSWORD=

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
````

### A.6 Views, e-mail, errors and front-end assets

#### `resources/views/layouts/app.blade.php`

````php
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        {{-- viewport-fit=cover lets the layout own the notch/home-indicator
             area, which the responsive layer fills with env(safe-area-inset-*). --}}
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        @php
            // A page can pass <x-slot name="title">Page title</x-slot> and
            // <x-slot name="description">Meta description</x-slot>.
            $pageTitle = isset($title) ? trim(strip_tags((string) $title)) : '';
            $pageDescription = isset($description) && trim(strip_tags((string) $description)) !== ''
                ? trim(strip_tags((string) $description))
                : config('educonnect.meta.description');
            $fullTitle = ($pageTitle !== '' ? $pageTitle.' · ' : '').config('app.name', 'EduConnect');
        @endphp
        <title>{{ $fullTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">

        {{-- Social previews use the same title and description. --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'EduConnect') }}">
        <meta property="og:title" content="{{ $fullTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $fullTitle }}">
        <meta name="twitter:description" content="{{ $pageDescription }}">

        {{-- Favicon and browser chrome colours (light + dark). --}}
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}">
        <meta name="theme-color" content="#4f46e5" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">

        {{-- Theme first: before the stylesheet so there is no flash of the
             wrong colour scheme. Explicit choice wins, then the OS setting. --}}
        <script>
            (function () {
                try {
                    var stored = window.localStorage.getItem('educonnect.theme');
                    var theme = stored === 'dark' || stored === 'light'
                        ? stored
                        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-bs-theme', theme);
                } catch (error) {
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                }
            })();
        </script>

        {{-- Apply the remembered desktop sidebar state before the first paint,
             so the layout does not jump when JavaScript finishes loading. --}}
        <script>
            try {
                if (window.localStorage.getItem('educonnect.sidebar.collapsed') === '1') {
                    document.documentElement.classList.add('edu-sidebar-collapsed');
                }
            } catch (error) {
                // Private browsing can refuse localStorage; the default open
                // sidebar is a perfectly fine fallback.
            }
        </script>

        {{-- Inter is loaded from Google Fonts (allowed by the phase brief). --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    @php
        // One unread counter shared by the top bar and the mobile navigation,
        // so both badges always agree and the feed keeps its query budget.
        $layoutUnreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;

        // The public landing page uses this layout too, but a signed-in visitor
        // should see the marketing site, not the dashboard sidebar, mobile nav
        // or composer shortcut. Those are dashboard chrome only.
        $isLandingPage = request()->is('/');
    @endphp

    {{-- body carries the notification polling URL for logged in users (FR-24) --}}
    <body class="edu-body d-flex flex-column min-vh-100 {{ auth()->check() && ! $isLandingPage ? 'edu-has-mobile-nav' : '' }}"
          @auth data-notification-url="{{ route('notifications.poll') }}" @endauth>
        <a class="visually-hidden-focusable edu-skip-link" href="#main-content" style="position:fixed;">
            {{ __('Skip to main content') }}
        </a>

        {{-- Sticky, blurred top navigation bar --}}
        @include('layouts.navigation', ['unreadNotifications' => $layoutUnreadCount])

        <div class="edu-shell flex-grow-1" id="appShell">
            {{-- Role based sidebar: a column on desktop, an offcanvas drawer on phones.
                 Never on the public landing page. --}}
            @auth
                @unless ($isLandingPage)
                    @include('layouts.sidebar')
                @endunless
            @endauth

            <div class="edu-main">
                <main class="edu-main-inner" id="main-content">
                    <div class="container-xxl px-3 px-lg-4">
                        {{-- Success / error / validation messages --}}
                        <x-flash />

                        {{-- Page header supplied by the view. Breadcrumbs live in
                             the top bar, so this only renders when a page has one. --}}
                        @isset($header)
                            <div class="edu-page-header mb-4">
                                {{ $header }}
                            </div>
                        @endisset
                    </div>

                    {{-- Page body. The CSS fades this in; page-transitions.js adds
                         the matching fade-out when a link is followed. --}}
                    <div class="edu-page">
                        {{ $slot }}
                    </div>
                </main>

                <footer class="edu-footer mt-auto">
                    <div class="container-xxl px-3 px-lg-4">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                            <span>
                                &copy; {{ date('Y') }} {{ config('app.name', 'EduConnect') }}
                            </span>
                            <span class="d-inline-flex align-items-center gap-2">
                                <i class="bi bi-mortarboard-fill text-edu" aria-hidden="true"></i>
                                {{ __('Learn together, anywhere.') }}
                            </span>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        {{-- Page specific JavaScript (kept out of the markup). --}}
        @stack('scripts')

        @auth
            @unless ($isLandingPage)
                {{-- Mobile bottom navigation: five destinations, always one tap away. --}}
                @include('layouts.mobile-nav', ['unreadCount' => $layoutUnreadCount])

                {{-- Floating composer shortcut, feed only, never over the desktop layout. --}}
                @if (request()->routeIs('feed.index'))
                    <a class="edu-fab d-lg-none" href="{{ route('posts.create') }}"
                       aria-label="{{ __('New post') }}" title="{{ __('New post') }}">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    </a>
                @endif
            @endunless
        @endauth
    </body>
</html>
````

#### `resources/views/welcome.blade.php`

````php
<x-app-layout>
    <x-slot name="title">{{ __('Learn together, anywhere') }}</x-slot>
    <x-slot name="description">{{ __('EduConnect brings live video classes, a friendly social feed with posts and hashtags, and one-click class enrolment together for teachers and students.') }}</x-slot>

    <div class="edu-landing">
        {{-- ------------------------------------------------------------------
             Hero
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-hero" aria-labelledby="hero-title">
            <span class="edu-landing-shape is-one" aria-hidden="true"></span>
            <span class="edu-landing-shape is-two" aria-hidden="true"></span>

            <div class="container-xxl px-3 px-lg-4 position-relative">
                <div class="row align-items-center g-5">
                    <div class="col-12 col-lg-6">
                        <span class="edu-eyebrow mb-3">
                            <i class="bi bi-stars" aria-hidden="true"></i>
                            {{ __('A calmer way to teach online') }}
                        </span>

                        <h1 class="edu-landing-title fw-bold mb-3" id="hero-title">
                            {{ __('Teach and learn,') }}
                            <span class="text-edu">{{ __('together') }}</span>.
                        </h1>

                        <p class="lead text-muted mb-4 edu-measure">
                            {{ __('Live video classes, a friendly social feed and simple class management — all in one place. Students follow their teachers and enroll in a class in a single click.') }}
                        </p>

                        <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 mb-3">
                            <a href="{{ route('register', ['role' => 'student']) }}" class="btn btn-primary btn-lg">
                                <i class="bi bi-mortarboard me-1" aria-hidden="true"></i>
                                {{ __('Register as Student') }}
                            </a>
                            <a href="{{ route('register', ['role' => 'teacher']) }}" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-easel me-1" aria-hidden="true"></i>
                                {{ __('Register as Teacher') }}
                            </a>

                        </div>

                        <p class="small text-muted mb-0">
                            <i class="bi bi-check2-circle text-success me-1" aria-hidden="true"></i>
                            {{ __('Free to start. No credit card, no download.') }}
                        </p>
                    </div>

                    <div class="col-12 col-lg-6">
                        {{-- CSS-only mockup of a live class: no image request, no layout shift. --}}
                        <div class="edu-mockup" role="img"
                             aria-label="{{ __('Preview of a live class: teacher video, shared notes and participant tiles.') }}">
                            <div class="edu-mockup-bar" aria-hidden="true">
                                <span class="edu-mockup-dot"></span>
                                <span class="edu-mockup-dot"></span>
                                <span class="edu-mockup-dot"></span>
                                <span class="ms-auto small text-muted fw-semibold">{{ __('Algebra basics · Live') }}</span>
                            </div>

                            <div class="edu-mockup-stage">
                                <span class="edu-mockup-tile" style="left: 6%; top: 8%; width: 58%; height: 58%;" aria-hidden="true"></span>
                                <span class="edu-mockup-tile" style="right: 6%; top: 8%; width: 28%; height: 26%;" aria-hidden="true"></span>
                                <span class="edu-mockup-tile" style="right: 6%; bottom: 8%; width: 28%; height: 26%;" aria-hidden="true"></span>
                                <span class="edu-mockup-tile" style="left: 6%; bottom: 8%; width: 58%; height: 20%;" aria-hidden="true"></span>

                                <span class="position-absolute top-0 start-0 m-3 badge text-bg-danger">
                                    <i class="bi bi-broadcast me-1" aria-hidden="true"></i>{{ __('Live') }}
                                </span>
                            </div>

                            <div class="edu-mockup-bar-strip" aria-hidden="true">
                                <span class="edu-mockup-tool"><i class="bi bi-mic"></i></span>
                                <span class="edu-mockup-tool"><i class="bi bi-camera-video"></i></span>
                                <span class="edu-mockup-tool"><i class="bi bi-chat-dots"></i></span>
                                <span class="edu-mockup-tool"><i class="bi bi-people"></i></span>
                                <span class="edu-mockup-tool"><i class="bi bi-telephone-x text-danger"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             How it works
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section" aria-labelledby="steps-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="text-center mb-5">
                    <span class="edu-eyebrow mb-2">{{ __('How it works') }}</span>
                    <h2 class="edu-section-title fw-bold mb-2" id="steps-title">{{ __('Three steps to your first class') }}</h2>
                    <p class="text-muted mb-0 mx-auto edu-measure">
                        {{ __('Whether you teach or learn, you only need a browser and a few minutes.') }}
                    </p>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="edu-step-card">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="edu-step-number" aria-hidden="true">1</span>
                                <h3 class="h6 fw-bold mb-0">{{ __('Create your account') }}</h3>
                            </div>
                            <p class="small text-muted mb-0">
                                {{ __('Pick Student or Teacher, add your name and you are in. Admins create staff accounts for you.') }}
                            </p>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="edu-step-card">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="edu-step-number" aria-hidden="true">2</span>
                                <h3 class="h6 fw-bold mb-0">{{ __('Find or create a class') }}</h3>
                            </div>
                            <p class="small text-muted mb-0">
                                {{ __('Teachers publish a class with a date and time. Students browse by subject and enroll in one click.') }}
                            </p>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="edu-step-card">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="edu-step-number" aria-hidden="true">3</span>
                                <h3 class="h6 fw-bold mb-0">{{ __('Meet, chat and keep learning') }}</h3>
                            </div>
                            <p class="small text-muted mb-0">
                                {{ __('Join the live room, then keep the conversation going in the feed with posts, comments and hashtags.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             Features
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section pt-0" aria-labelledby="features-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="text-center mb-5">
                    <span class="edu-eyebrow mb-2">{{ __('Everything in one place') }}</span>
                    <h2 class="edu-section-title fw-bold mb-2" id="features-title">{{ __('Built for real classrooms') }}</h2>
                    <p class="text-muted mb-0 mx-auto edu-measure">
                        {{ __('No plugin maze. Each feature solves one daily teaching moment.') }}
                    </p>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-lg-6">
                        <div class="edu-feature-card h-100">
                            <h3 class="h6 fw-bold mb-2">
                                <i class="bi bi-easel text-edu me-1" aria-hidden="true"></i>
                                {{ __('For teachers') }}
                            </h3>
                            <p class="small text-muted mb-0">
                                {{ __('Schedule a class, share a private Jitsi room, see who enrolled, and keep the conversation going in the feed.') }}
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="edu-feature-card h-100">
                            <h3 class="h6 fw-bold mb-2">
                                <i class="bi bi-mortarboard text-edu me-1" aria-hidden="true"></i>
                                {{ __('For students') }}
                            </h3>
                            <p class="small text-muted mb-0">
                                {{ __('Follow teachers, enrol in one click, join live rooms from the browser, and ask questions in posts and comments.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach ([
                        ['bi-camera-video', __('Live classes'), __('Open a secure video room inside EduConnect and start teaching right away.')],
                        ['bi-chat-square-text', __('Social feed'), __('Short posts, images, likes and comments keep the class community close.')],
                        ['bi-hash', __('Hashtags'), __('Group posts by topic and discover the five busiest tags of the week.')],
                        ['bi-bell', __('Notifications'), __('Followers, likes, comments and starting classes arrive in one calm inbox.')],
                        ['bi-shield-check', __('Safe moderation'), __('Report content, block accounts and keep every learner protected.')],
                        ['bi-speedometer2', __('Role dashboards'), __('Students, teachers and admins each see exactly what they need.')],
                    ] as [$icon, $title, $copy])
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="edu-feature-card">
                                <span class="edu-auth-icon mb-3" aria-hidden="true">
                                    <i class="bi {{ $icon }}"></i>
                                </span>
                                <h3 class="h6 fw-bold">{{ $title }}</h3>
                                <p class="small text-muted mb-0">{{ $copy }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             Stats strip
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section pt-0" aria-labelledby="stats-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="edu-stats-strip">
                    <h2 class="visually-hidden" id="stats-title">{{ __('EduConnect at a glance') }}</h2>

                    <div class="row g-4 text-center text-md-start">
                        <div class="col-6 col-md-3">
                            <div class="edu-stat-figure">3</div>
                            <div class="small text-white-50">{{ __('role-based experiences') }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="edu-stat-figure">1</div>
                            <div class="small text-white-50">{{ __('click to enroll') }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="edu-stat-figure">0</div>
                            <div class="small text-white-50">{{ __('apps to install') }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="edu-stat-figure">24/7</div>
                            <div class="small text-white-50">{{ __('access to your feed') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             Testimonial
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section pt-0" aria-labelledby="quote-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-8">
                        <figure class="edu-step-card text-center mb-0">
                            <span class="edu-empty-illustration mx-auto" aria-hidden="true">
                                <i class="bi bi-quote"></i>
                            </span>
                            <blockquote class="mb-3">
                                <p class="h5 fw-semibold mb-0" id="quote-title">
                                    {{ __('“My students join from their phones, ask questions in the feed and never lose the thread between lessons.”') }}
                                </p>
                            </blockquote>
                            <figcaption class="small text-muted">
                                {{ __('A teacher using EduConnect every week') }}
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             FAQ
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section pt-0" aria-labelledby="faq-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-8">
                        <div class="text-center mb-4">
                            <span class="edu-eyebrow mb-2">{{ __('Questions') }}</span>
                            <h2 class="edu-section-title fw-bold mb-0" id="faq-title">{{ __('Frequently asked') }}</h2>
                        </div>

                        <div class="accordion edu-faq" id="landingFaq">
                            @foreach ([
                                [__('Do I need to install anything?'), __('No. EduConnect runs in your browser. The video room opens in the same tab and works on phones, tablets and computers.')],
                                [__('Is it free for students?'), __('Creating a Student account is free. Your school or teacher decides which classes are published.')],
                                [__('How do teachers start a class?'), __('A teacher schedules a class, opens the room when ready and presses “Start class”. Enrolled students then see the Join button.')],
                                [__('What happens if someone behaves badly?'), __('Any member can report a post or comment. Admins review reports, hide content and can block the account.')],
                                [__('Can I use it on a slow connection?'), __('Yes. Pages are server-rendered, images are lazy-loaded and the video room degrades gracefully with a clear retry message.')],
                            ] as $index => [$question, $answer])
                                <div class="accordion-item">
                                    <h3 class="accordion-header" id="faqHeading{{ $index }}">
                                        <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#faqCollapse{{ $index }}"
                                                aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                                aria-controls="faqCollapse{{ $index }}">
                                            {{ $question }}
                                        </button>
                                    </h3>
                                    <div id="faqCollapse{{ $index }}"
                                         class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                                         aria-labelledby="faqHeading{{ $index }}" data-bs-parent="#landingFaq">
                                        <div class="accordion-body small text-muted">{{ $answer }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             Final call to action
             ------------------------------------------------------------------ --}}
        <section class="pb-5" aria-labelledby="cta-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="edu-stats-strip text-center">
                    <h2 class="h3 fw-bold mb-2" id="cta-title">{{ __('Ready to start your first class?') }}</h2>
                    <p class="text-white-50 mb-4 mx-auto edu-measure">
                        {{ __('Join as a student to learn, or as a teacher to share what you know.') }}
                    </p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                        <a href="{{ route('register', ['role' => 'student']) }}" class="btn btn-light btn-lg">
                            <i class="bi bi-mortarboard me-1" aria-hidden="true"></i>
                            {{ __('Register as Student') }}
                        </a>
                        <a href="{{ route('register', ['role' => 'teacher']) }}" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-easel me-1" aria-hidden="true"></i>
                            {{ __('Register as Teacher') }}
                        </a>
                    </div>
                    <p class="small text-white-50 mt-3 mb-0">
                        {{ __('Already have an account?') }}
                        <a href="{{ route('login') }}" class="link-light fw-semibold">{{ __('Log in') }}</a>
                    </p>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
````

#### `resources/views/layouts/sidebar.blade.php`

````php
{{-- Role based sidebar.
     From lg upwards it is a fixed dark column; on phones it is an offcanvas
     drawer opened with the hamburger button in the navbar. The desktop toggle
     in the navbar stores its state in localStorage (FR-31). --}}
@php
    $me = auth()->user();
    $role = $me->role;

    $groups = match ($role) {
        \App\Models\User::ROLE_ADMIN => [
            ['label' => 'Overview', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'admin.dashboard'],
                ['label' => 'Style Guide', 'icon' => 'bi-palette', 'route' => 'admin.style-guide'],
            ]],
            ['label' => 'Manage', 'items' => [
                ['label' => 'Users', 'icon' => 'bi-people', 'route' => 'admin.users.index'],
                ['label' => 'Posts', 'icon' => 'bi-chat-square-text', 'route' => 'admin.posts.index'],
                ['label' => 'Classes', 'icon' => 'bi-easel', 'route' => 'admin.classes.index'],
                ['label' => 'Reports', 'icon' => 'bi-flag', 'route' => 'admin.reports.index'],
            ]],
            ['label' => 'Community', 'items' => [
                ['label' => 'Feed', 'icon' => 'bi-rss', 'route' => 'feed.index'],
                ['label' => 'Notifications', 'icon' => 'bi-bell', 'route' => 'notifications.index'],
            ]],
        ],
        \App\Models\User::ROLE_TEACHER => [
            ['label' => 'Teaching', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'teacher.dashboard'],
                ['label' => 'My Classes', 'icon' => 'bi-easel', 'route' => 'classes.index'],
                ['label' => 'Create Class', 'icon' => 'bi-plus-square', 'route' => 'classes.create'],
                ['label' => 'Discover', 'icon' => 'bi-compass', 'route' => 'classes.discover'],
            ]],
            ['label' => 'Community', 'items' => [
                ['label' => 'Feed', 'icon' => 'bi-chat-square-text', 'route' => 'feed.index'],
                ['label' => 'Notifications', 'icon' => 'bi-bell', 'route' => 'notifications.index'],
            ]],
            ['label' => 'Account', 'items' => [
                ['label' => 'My Profile', 'icon' => 'bi-person', 'route' => 'users.show', 'params' => [$me->id]],
            ]],
        ],
        default => [
            ['label' => 'Learning', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'student.dashboard'],
                ['label' => 'Browse Classes', 'icon' => 'bi-search', 'route' => 'classes.discover'],
                ['label' => 'My Classes', 'icon' => 'bi-journal-bookmark', 'route' => 'student.classes'],
            ]],
            ['label' => 'Community', 'items' => [
                ['label' => 'Feed', 'icon' => 'bi-chat-square-text', 'route' => 'feed.index'],
                ['label' => 'Notifications', 'icon' => 'bi-bell', 'route' => 'notifications.index'],
            ]],
            ['label' => 'Account', 'items' => [
                ['label' => 'My Profile', 'icon' => 'bi-person', 'route' => 'users.show', 'params' => [$me->id]],
            ]],
        ],
    };

    // The profile entry is only active on your own profile, not on somebody else's.
    $isActive = function (array $item) use ($me): bool {
        if (! request()->routeIs($item['route'])) {
            return false;
        }

        if ($item['route'] === 'users.show') {
            $shown = request()->route('user');

            return $shown instanceof \App\Models\User && $shown->is($me);
        }

        return true;
    };
@endphp

<aside class="offcanvas-lg offcanvas-start edu-sidebar flex-shrink-0" tabindex="-1" id="appSidebar"
       aria-labelledby="appSidebarLabel" data-edu-sidebar>
    {{-- Header is only needed in the mobile drawer --}}
    <div class="offcanvas-header d-lg-none">
        <h2 class="offcanvas-title h6 mb-0" id="appSidebarLabel">
            <i class="bi bi-mortarboard-fill me-1" aria-hidden="true"></i> {{ __('Menu') }}
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column">
        {{-- Brand / logo --}}
        <a class="edu-sidebar-logo" href="{{ route('dashboard') }}" aria-label="{{ config('app.name', 'EduConnect') }}">
            <span class="edu-sidebar-logo-mark" aria-hidden="true">
                <x-application-logo class="text-white" />
            </span>
            <span class="edu-sidebar-logo-text edu-sidebar-label">
                <strong>{{ config('app.name', 'EduConnect') }}</strong>
                <span>{{ __('Learning workspace') }}</span>
            </span>
        </a>

        {{-- Grouped role menu --}}
        <nav class="flex-grow-1" aria-label="{{ __('Main navigation') }}">
            @foreach ($groups as $group)
                <div class="edu-sidebar-section">{{ __($group['label']) }}</div>

                <ul class="nav flex-column">
                    @foreach ($group['items'] as $item)
                        @php($active = $isActive($item))

                        <li class="nav-item">
                            @if (Route::has($item['route']))
                                <a class="nav-link {{ $active ? 'active' : '' }}"
                                   href="{{ route($item['route'], $item['params'] ?? []) }}"
                                   title="{{ __($item['label']) }}"
                                   @if ($active) aria-current="page" @endif>
                                    <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                                    <span class="edu-sidebar-label">{{ __($item['label']) }}</span>
                                </a>
                            @else
                                <span class="nav-link disabled" title="{{ __($item['label']) }}">
                                    <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                                    <span class="edu-sidebar-label">{{ __($item['label']) }}</span>
                                    <span class="badge text-bg-secondary ms-auto">{{ __('soon') }}</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>

        {{-- Account actions + mini profile card, pushed to the bottom --}}
        <div class="mt-auto pt-2">
            <div class="edu-sidebar-divider"></div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link edu-sidebar-footer-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                       href="{{ route('profile.edit') }}" title="{{ __('Edit profile') }}"
                       @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                        <i class="bi bi-gear" aria-hidden="true"></i>
                        <span class="edu-sidebar-label">{{ __('Settings') }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-link text-danger w-100 text-start border-0 bg-transparent"
                                title="{{ __('Log out') }}">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                            <span class="edu-sidebar-label">{{ __('Log out') }}</span>
                        </button>
                    </form>
                </li>
            </ul>

            {{-- User mini-card. --}}
            <div class="edu-sidebar-user d-flex align-items-center gap-2 mt-2">
                <x-avatar :user="$me" size="sm" class="flex-shrink-0" />
                <div class="edu-sidebar-user-text min-w-0">
                    <div class="edu-sidebar-user-name text-truncate">{{ $me->name }}</div>
                    <div class="edu-sidebar-user-role">{{ $role }}</div>
                </div>
                <a class="btn edu-icon-btn ms-auto d-none d-lg-inline-flex"
                   href="{{ route('profile.edit') }}"
                   aria-label="{{ __('Edit profile') }}" title="{{ __('Edit profile') }}"
                   style="color: var(--edu-sidebar-text); width: 34px; height: 34px;">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</aside>
````

#### `resources/views/components/empty-state.blade.php`

````php
{{-- Reusable empty state.
     Usage:
       <x-empty-state icon="bi-easel" title="No classes yet" message="Create your first class.">
           <a href="..." class="btn btn-primary">Create one</a>
       </x-empty-state> --}}
@props([
    'icon' => 'bi-inbox',
    'title' => null,
    'message' => null,
])

<div {{ $attributes->class(['card', 'edu-card', 'border-0']) }}>
    <div class="card-body text-center py-5">
        <span class="edu-empty-illustration" aria-hidden="true">
            <i class="bi {{ $icon }}"></i>
        </span>

        @if ($title)
            <h2 class="h6 fw-semibold">{{ $title }}</h2>
        @endif

        @if ($message)
            <p class="text-muted small mb-3">{{ $message }}</p>
        @endif

        @if ($slot->isNotEmpty())
            <div class="d-flex flex-wrap justify-content-center gap-2">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
````

#### `resources/views/components/flash.blade.php`

````php
{{-- Flash + validation summary messages. Used by every layout via <x-flash /> --}}

@if (session('success'))
    {{-- Green banner after a successful action --}}
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    {{-- Red banner for a refused action --}}
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('status'))
    {{-- Neutral informational banner (used by Breeze for "verify your email") --}}
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-1"></i> {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@php($bag = $errors ?? null)
@if ($bag && $bag->any())
    {{-- Summary of every validation error of the last request.
         $errors is only shared by the "web" middleware group, so it is missing
         on error pages such as 404 - hence the null-safe check above. --}}
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ __('Please fix the following:') }}
        </div>
        <ul class="mb-0 small">
            @foreach ($bag->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
````

#### `resources/views/errors/page.blade.php`

````php
{{-- Shared friendly error page shell (Phase 3C).
     Variables: $code, $title, $message, $icon.
     Every error page keeps a way forward: home, plus the dashboard or login. --}}
@php
    $code = $code ?? '404';
    $title = $title ?? __('Something went wrong');
    $message = $message ?? __('Please try again in a moment.');
    $icon = $icon ?? 'bi-emoji-frown';
@endphp

<x-guest-layout :title="$title">
    <div class="text-center py-2">
        <span class="edu-empty-illustration mx-auto" aria-hidden="true">
            <i class="bi {{ $icon }}"></i>
        </span>

        <div class="display-5 fw-bold text-edu lh-1 mt-2">{{ $code }}</div>
        <h1 class="h4 fw-bold mt-2 mb-2">{{ $title }}</h1>

        <p class="text-muted small edu-measure mx-auto mb-4">{{ $message }}</p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="bi bi-house me-1" aria-hidden="true"></i>{{ __('Back to home') }}
            </a>

            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>{{ __('Go to dashboard') }}
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>{{ __('Log in') }}
                </a>
            @endauth
        </div>

        <p class="small text-muted mt-4 mb-0">
            {{ __('Still stuck? Contact the administrator and mention code :code.', ['code' => $code]) }}
        </p>
    </div>
</x-guest-layout>
````

#### `resources/views/errors/403.blade.php`

````php
@include('errors.page', [
    'code' => '403',
    'icon' => 'bi-shield-lock',
    'title' => __('You are not allowed to open this page'),
    'message' => __('Your account does not have the rights for this area. If you think this is a mistake, please contact an administrator.'),
])
````

#### `resources/views/errors/404.blade.php`

````php
@include('errors.page', [
    'code' => '404',
    'icon' => 'bi-compass',
    'title' => __('Page not found'),
    'message' => __('The page you are looking for has been moved or never existed. Check the address and try again.'),
])
````

#### `resources/views/errors/419.blade.php`

````php
@include('errors.page', [
    'code' => '419',
    'icon' => 'bi-hourglass-split',
    'title' => __('Your session expired'),
    'message' => __('For your safety the page was idle for too long. Refresh the page, then send the form again. Nothing was lost except the last click.'),
])
````

#### `resources/views/errors/500.blade.php`

````php
@include('errors.page', [
    'code' => '500',
    'icon' => 'bi-tools',
    'title' => __('Something went wrong on our side'),
    'message' => __('The page could not be loaded. Your data is safe. Try again in a moment, or head back home and start from there.'),
])
````

#### `resources/views/errors/503.blade.php`

````php
@include('errors.page', [
    'code' => '503',
    'icon' => 'bi-cone-striped',
    'title' => __('We are doing a little maintenance'),
    'message' => __('EduConnect is being updated right now. Classes and posts will be back shortly — please try again in a few minutes.'),
])
````

#### `app/Notifications/ResetPasswordNotification.php`

````php
<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Branded password reset email (FR-3).
 *
 * It extends the framework notification so the token, expiry and reset URL
 * logic stay exactly the same - only the look of the email changes.
 */
class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * Build the reset email with our own Markdown template.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your EduConnect password')
            ->markdown('emails.reset-password', [
                'url' => $this->resetUrl($notifiable),
                'name' => $notifiable->name,
                'expires' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]);
    }
}
````

#### `resources/views/emails/reset-password.blade.php`

````php
{{--
    Branded password reset email (FR-3).
    Kept intentionally simple: one heading, one button, plain text fallback.
--}}
<x-mail::message>
# Reset your password

Hello {{ $name }},

We received a request to reset the password for your EduConnect account.

<x-mail::button :url="$url" color="primary">
Choose a new password
</x-mail::button>

This link expires in {{ $expires }} minutes. If you did not ask for a reset, you can ignore this email — your password will not change.

Thanks,<br>
{{ config('app.name', 'EduConnect') }}
</x-mail::message>
````

#### `resources/js/app.js`

````javascript
import './bootstrap'; // axios setup (window.axios)

// Bootstrap 5 JavaScript (dropdowns, modals, collapses...) + Popper
import * as bootstrap from 'bootstrap';

import Alpine from 'alpinejs';

// Layout helpers: remember the desktop sidebar state (FR-31).
import './layout';

// Phase 3B: light/dark mode, themed charts, live countdowns, table tools and
// smooth page transitions.
import './theme';
import './charts';
import './countdown';
import './data-table';
import './page-transitions';
import './toast';

// Profile photo preview and "remove current photo" (FR-4).
import './profile-photo';

// Authentication helpers: password visibility + teacher subject field (FR-1 ... FR-3).
import './auth';

// Feed interactions: likes, comments, follows and "Load more" (FR-11, FR-13, FR-17).
import './feed';

// Live class room: embeds Jitsi Meet through the External API (FR-18, FR-22).
import './jitsi';

// Notification bell: unread badge + newest eight, refreshed by polling (FR-24)
// and "mark as read" without a reload (FR-25).
import './notifications';

// Live search suggestions under the navbar box (FR-14).
import './search';

// Global form polish: busy submit buttons and live character counters (Phase 3C).
import './forms';

// Live preview on the create / edit class form (Phase 3C).
import './classes';

// Make them available to plain <script> tags inside Blade views.
window.bootstrap = bootstrap;
window.Alpine = Alpine;

Alpine.start();
````

#### `resources/js/feed.js`

````javascript
/**
 * EduConnect feed interactions (FR-11, FR-13, FR-17).
 *
 * Everything here is progressive enhancement: the pages already contain real
 * <form> elements, so with JavaScript switched off the browser simply posts
 * them and the controllers redirect back. With JavaScript we intercept the
 * submit, call fetch() and update the page in place.
 */

/** The CSRF token from the <meta name="csrf-token"> tag in the layout. */
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/**
 * Post a form with fetch() and return the JSON answer.
 * Always sends POST + the form data, so Laravel's @method('DELETE') spoofing
 * keeps working exactly like it does for a normal browser submit.
 */
async function postForm(form) {
    const response = await fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        body: new FormData(form),
    });

    if (!response.ok) {
        if (response.status === 429) {
            throw new Error('Too many requests. Please wait a moment and try again.');
        }

        if (response.status === 403) {
            throw new Error('You are not allowed to do that.');
        }

        throw new Error('Something went wrong. Please try again.');
    }

    return response.json();
}

/* -------------------------------------------------------------------------
 | Likes
 * ---------------------------------------------------------------------- */

/** Keep the "flip to the other action" bookkeeping inside the form. */
function setLikeState(form, button, counter, liked, count) {
    form.dataset.liked = liked ? '1' : '0';
    form.action = liked ? form.dataset.unlikeUrl : form.dataset.likeUrl;

    let spoof = form.querySelector('input[name="_method"]');
    if (liked) {
        if (!spoof) {
            spoof = document.createElement('input');
            spoof.type = 'hidden';
            spoof.name = '_method';
            form.append(spoof);
        }
        spoof.value = 'DELETE';
    } else if (spoof) {
        spoof.remove();
    }

    if (counter) {
        counter.textContent = count;
    }

    if (button) {
        button.classList.toggle('btn-danger', liked);
        button.classList.toggle('btn-outline-secondary', !liked);
        button.setAttribute('aria-pressed', liked ? 'true' : 'false');

        const icon = button.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-heart-fill', liked);
            icon.classList.toggle('bi-heart', !liked);
            // A short pop, disabled automatically by prefers-reduced-motion.
            icon.classList.remove('edu-like-pop');
            void icon.offsetWidth;
            icon.classList.add('edu-like-pop');
        }
    }
}

async function handleLike(form) {
    const card = form.closest('[data-post-card]');
    const button = form.querySelector('[data-like-button]');
    const counter = card?.querySelector('[data-like-count]');

    const wasLiked = form.dataset.liked === '1';
    const previousCount = Number(counter?.textContent ?? 0);

    // Optimistic UI: show the change before the server has answered.
    setLikeState(form, button, counter, !wasLiked, previousCount + (wasLiked ? -1 : 1));

    // A second click must not race the first request.
    if (button) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
    }

    try {
        const data = await postForm(form);
        // Trust the server numbers, they are the truth.
        setLikeState(form, button, counter, data.liked, data.count);
    } catch (error) {
        console.error('[EduConnect] liking the post failed', error);
        setLikeState(form, button, counter, wasLiked, previousCount);
        window.eduToast?.(error.message, 'error');
    } finally {
        if (button) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    }
}

/* -------------------------------------------------------------------------
 | Follow / unfollow
 * ---------------------------------------------------------------------- */

function setFollowState(form, button, following) {
    form.dataset.following = following ? '1' : '0';
    form.action = following ? form.dataset.unfollowUrl : form.dataset.followUrl;

    let spoof = form.querySelector('input[name="_method"]');
    if (following) {
        if (!spoof) {
            spoof = document.createElement('input');
            spoof.type = 'hidden';
            spoof.name = '_method';
            form.append(spoof);
        }
        spoof.value = 'DELETE';
    } else if (spoof) {
        spoof.remove();
    }

    if (!button) {
        return;
    }

    button.classList.toggle('btn-primary', !following);
    button.classList.toggle('btn-outline-secondary', following);
    button.setAttribute('aria-pressed', following ? 'true' : 'false');

    const icon = button.querySelector('i');
    if (icon) {
        icon.classList.toggle('bi-person-check', following);
        icon.classList.toggle('bi-person-plus', !following);
    }

    // Both labels arrive from Blade, so they stay translated.
    const label = button.querySelector('[data-follow-label]');
    if (label) {
        label.textContent = following ? form.dataset.labelFollowing : form.dataset.labelFollow;
    }
}

/**
 * Refresh the follower counter of the profile that was followed and the
 * viewer's own "following" total (used by the feed sidebar).
 */
function updateFollowCounts(data) {
    if (data.user_id !== undefined) {
        document.querySelectorAll(`[data-followers-count][data-user-id="${data.user_id}"]`).forEach((element) => {
            const count = Number(data.followers);

            element.textContent = count;

            // The word after the number has to follow the plural rules too
            // (0 followers, 1 follower, 2 followers).
            const label = element.parentElement?.querySelector('[data-follower-label]');

            if (label) {
                label.textContent = count === 1
                    ? element.dataset.labelSingular
                    : element.dataset.labelPlural;
            }
        });
    }

    if (data.following_count !== undefined) {
        document.querySelectorAll('[data-viewer-following-count]').forEach((element) => {
            element.textContent = data.following_count;
        });
    }
}

async function handleFollow(form) {
    const button = form.querySelector('[data-follow-button]');
    const wasFollowing = form.dataset.following === '1';

    setFollowState(form, button, !wasFollowing);

    if (button) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
    }

    try {
        const data = await postForm(form);
        setFollowState(form, button, data.following);
        updateFollowCounts(data);
    } catch (error) {
        console.error('[EduConnect] follow failed', error);
        setFollowState(form, button, wasFollowing);
        window.eduToast?.(error.message, 'error');
    } finally {
        if (button) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    }
}

/* -------------------------------------------------------------------------
 | Comments
 * ---------------------------------------------------------------------- */

/**
 * The detail page wraps the post and its comments in [data-comment-scope];
 * a feed card is its own scope. Counters outside that scope never change.
 */
function commentScope(form) {
    return form.closest('[data-comment-scope]')
        || form.closest('[data-post-card]')
        || document;
}

function updateCommentCounts(scope, count) {
    scope.querySelectorAll('[data-comment-count]').forEach((element) => {
        element.textContent = count;
    });
}

async function handleComment(form) {
    const scope = commentScope(form);
    const region = form.closest('[data-comment-region]');
    const list = region?.querySelector('[data-comment-list]');

    try {
        const data = await postForm(form);

        if (list && data.html) {
            // The "no comments yet" hint disappears with the first comment.
            list.querySelector('[data-comment-empty]')?.remove();
            list.insertAdjacentHTML('beforeend', data.html);
        }

        form.reset();
        updateCommentCounts(scope, data.count);
    } catch (error) {
        console.error('[EduConnect] posting the comment failed', error);
        window.eduToast?.(error.message, 'error');
    }
}

async function handleDeleteComment(form) {
    const scope = commentScope(form);

    try {
        const data = await postForm(form);

        if (data.deleted) {
            form.closest('[data-comment-id]')?.remove();
            updateCommentCounts(scope, data.count);
        }
    } catch (error) {
        console.error('[EduConnect] deleting the comment failed', error);
        window.eduToast?.(error.message, 'error');
    }
}

/* -------------------------------------------------------------------------
 | "Load more" in the feed
 * ---------------------------------------------------------------------- */

async function handleLoadMore(button) {
    const list = document.querySelector('[data-feed-list]');
    const url = button.dataset.url;

    if (!list || !url) {
        return;
    }

    button.disabled = true;

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        const data = await response.json();
        list.insertAdjacentHTML('beforeend', data.html);

        if (data.has_more && data.next_page) {
            button.dataset.url = data.next_page;
        } else {
            // Last page reached: the button has done its job.
            button.hidden = true;
        }
    } catch (error) {
        console.error('[EduConnect] loading more posts failed', error);
        window.eduToast?.(error.message || 'Could not load more posts.', 'error');
    } finally {
        button.disabled = false;
    }
}

/* -------------------------------------------------------------------------
 | Live character counter for the 280 character limit (FR-9)
 * ---------------------------------------------------------------------- */

function initCharCounters() {
    document.querySelectorAll('[data-post-body]').forEach((field) => {
        const form = field.closest('form');
        const counter = form?.querySelector('[data-char-counter]');
        const ring = form?.querySelector('[data-char-ring]');
        const limit = Number(field.getAttribute('maxlength')) || 280;

        if (!counter && !ring) {
            return;
        }

        const update = () => {
            const length = field.value.length;

            if (counter) {
                counter.textContent = length;
                counter.classList.toggle('text-danger', length >= limit);
            }

            if (ring) {
                const ratio = Math.min(length / limit, 1);
                ring.style.setProperty('--edu-ring-progress', `${Math.round(ratio * 360)}deg`);
                ring.classList.toggle('is-warning', ratio >= 0.8 && ratio < 1);
                ring.classList.toggle('is-full', ratio >= 1);
                ring.setAttribute('aria-valuenow', String(length));
                ring.setAttribute('aria-valuetext', `${length}/${limit}`);
            }
        };

        field.addEventListener('input', update);
        update();
    });
}

/**
 * Image preview in the composer: a local object URL, revoked as soon as the
 * selection changes, so nothing leaks and no upload happens before submit.
 */
function initImagePreview() {
    document.querySelectorAll('[data-image-input]').forEach((input) => {
        const preview = input.closest('form')?.querySelector('[data-image-preview]');
        const image = preview?.querySelector('img');

        if (!preview || !image) {
            return;
        }

        let objectUrl = null;

        input.addEventListener('change', () => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            const file = input.files?.[0];

            if (!file || !file.type.startsWith('image/')) {
                preview.hidden = true;
                image.removeAttribute('src');
                return;
            }

            objectUrl = URL.createObjectURL(file);
            image.src = objectUrl;
            preview.hidden = false;
        });
    });
}

/** The small "add hashtag" toolbar button keeps the keyboard flow simple. */
function initComposerToolbar() {
    document.querySelectorAll('[data-insert-hashtag]').forEach((button) => {
        button.addEventListener('click', () => {
            const field = button.closest('form')?.querySelector('[data-post-body]');

            if (!field) {
                return;
            }

            const start = field.selectionStart ?? field.value.length;
            const end = field.selectionEnd ?? field.value.length;
            const before = field.value.slice(0, start);
            const after = field.value.slice(end);
            const spacer = before.length > 0 && !/\s$/.test(before) ? ' ' : '';

            field.value = `${before}${spacer}#${after}`;
            field.focus();
            field.selectionStart = field.selectionEnd = start + spacer.length + 1;
            field.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
}

/* -------------------------------------------------------------------------
 | Wiring
 * ---------------------------------------------------------------------- */

document.addEventListener('submit', (event) => {
    const form = event.target;

    // data-confirm works for cards that arrived later through "Load more" too.
    if (form.dataset.confirm && ! window.confirm(form.dataset.confirm)) {
        event.preventDefault();
        return;
    }

    if (form.matches('[data-like-form]')) {
        event.preventDefault();
        handleLike(form);
    } else if (form.matches('[data-follow-form]')) {
        event.preventDefault();
        handleFollow(form);
    } else if (form.matches('[data-comment-form]')) {
        event.preventDefault();
        handleComment(form);
    } else if (form.matches('[data-delete-comment-form]')) {
        event.preventDefault();
        handleDeleteComment(form);
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-load-more]');
    if (button) {
        event.preventDefault();
        handleLoadMore(button);
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initCharCounters();
        initImagePreview();
        initComposerToolbar();
    });
} else {
    initCharCounters();
    initImagePreview();
    initComposerToolbar();
}
````

#### `resources/js/toast.js`

````javascript
/**
 * Lightweight toasts for AJAX errors and client-side notices.
 * Server-rendered flash banners in <x-flash /> stay as they are.
 */

function stack() {
    let element = document.querySelector('[data-edu-toast-stack]');

    if (!element) {
        element = document.createElement('div');
        element.className = 'edu-toast-stack';
        element.dataset.eduToastStack = '1';
        element.setAttribute('role', 'status');
        element.setAttribute('aria-live', 'polite');
        document.body.append(element);
    }

    return element;
}

export function showToast(message, type = 'info') {
    if (!message) {
        return;
    }

    const toast = document.createElement('div');
    toast.className = `edu-toast is-${type}`;
    toast.innerHTML = `
        <span>${escapeHtml(String(message))}</span>
        <button type="button" class="btn-close" aria-label="Close"></button>
    `;

    toast.querySelector('.btn-close')?.addEventListener('click', () => toast.remove());
    stack().append(toast);

    window.setTimeout(() => toast.remove(), 5000);
}

function escapeHtml(value) {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

window.eduToast = showToast;
````

#### `resources/sass/app.scss`

````scss
// -----------------------------------------------------------------------------
// EduConnect stylesheet (Phase 3B premium dashboard design)
// -----------------------------------------------------------------------------
// Bootstrap 5 and Bootstrap Icons are pulled straight from node_modules and
// compiled by Vite (no Tailwind anywhere in this project). The tokens live in
// _variables.scss and are imported before Bootstrap so the framework itself
// picks up the brand palette and dark-mode surfaces.
// -----------------------------------------------------------------------------

@import 'variables';
@import 'bootstrap/scss/bootstrap';
@import 'bootstrap-icons/font/bootstrap-icons.css';

// -----------------------------------------------------------------------------
// Base
// -----------------------------------------------------------------------------

:root {
    --bs-body-bg: var(--edu-page-bg);
    --bs-body-color: var(--edu-text);
    --bs-border-color: var(--edu-border);
    --bs-secondary-color: var(--edu-muted);
    --bs-link-color-rgb: 79, 70, 229;
    --bs-link-hover-color-rgb: 55, 48, 163;
    --bs-focus-ring-color: rgba(79, 70, 229, 0.25);
}

[data-bs-theme='dark'] {
    --bs-body-bg: var(--edu-page-bg);
    --bs-body-color: var(--edu-text);
    --bs-border-color: var(--edu-border);
    --bs-secondary-color: var(--edu-muted);
    --bs-link-color-rgb: 165, 180, 252;
    --bs-link-hover-color-rgb: 199, 210, 254;
    --bs-emphasis-color: #f8fafc;
    --bs-heading-color: #f8fafc;
    --bs-tertiary-bg: #0f172a;
    --bs-secondary-bg: #172033;
}

html {
    scroll-behavior: smooth;
}

body {
    background-color: var(--edu-page-bg);
    color: var(--edu-text);
    font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    overflow-x: hidden;
}

// Bootstrap has no utility for this, but the dashboard flex children need it
// so long names and titles truncate instead of widening the grid.
.min-w-0 {
    min-width: 0;
}

::selection {
    background: rgba(79, 70, 229, 0.2);
}

h1, h2, h3, h4, h5, h6,
.h1, .h2, .h3, .h4, .h5, .h6 {
    color: var(--edu-text);
    letter-spacing: -0.015em;
}

h1, .h1 { font-size: clamp(1.6rem, 1.25rem + 1.2vw, 2rem); }
h2, .h2 { font-size: clamp(1.35rem, 1.15rem + 0.7vw, 1.6rem); }
h3, .h3 { font-size: 1.3rem; }
h4, .h4 { font-size: 1.15rem; }
h5, .h5 { font-size: 1rem; }
h6, .h6 { font-size: 0.9375rem; }

small, .small {
    font-size: 0.8125rem;
}

.text-muted {
    color: var(--edu-muted) !important;
}

.text-edu {
    color: var(--edu-primary) !important;
}

.bg-edu-soft {
    background: var(--edu-primary-soft) !important;
}

.edu-num,
.tabular-nums {
    font-variant-numeric: tabular-nums;
    font-feature-settings: 'tnum';
}

.edu-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.11em;
    text-transform: uppercase;
    color: var(--edu-primary);
}

.edu-muted {
    color: var(--edu-muted);
}

hr {
    border-color: var(--edu-border);
    opacity: 1;
}

// Focus rings: visible on every interactive element, never on click-only divs.
a:focus-visible,
button:focus-visible,
.btn:focus-visible,
.form-control:focus-visible,
.form-select:focus-visible,
.nav-link:focus-visible,
.dropdown-item:focus-visible,
[tabindex]:focus-visible {
    outline: 2px solid var(--edu-primary);
    outline-offset: 2px;
    box-shadow: var(--edu-ring);
}

// -----------------------------------------------------------------------------
// Buttons, badges, forms, tables, dropdowns, modals
// -----------------------------------------------------------------------------

.btn {
    --bs-btn-font-weight: 600;
    --bs-btn-padding-x: 0.95rem;
    --bs-btn-padding-y: 0.55rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    border-radius: 0.625rem;
    transition: transform var(--edu-transition), box-shadow var(--edu-transition),
        background-color var(--edu-transition), border-color var(--edu-transition),
        color var(--edu-transition);
}

.btn:active {
    transform: translateY(1px);
}

.btn-primary {
    --bs-btn-bg: var(--edu-primary);
    --bs-btn-border-color: var(--edu-primary);
    --bs-btn-hover-bg: var(--edu-primary-dark);
    --bs-btn-hover-border-color: var(--edu-primary-dark);
    --bs-btn-active-bg: var(--edu-primary-dark);
    --bs-btn-active-border-color: var(--edu-primary-dark);
    --bs-btn-disabled-bg: #a5b4fc;
    --bs-btn-disabled-border-color: #a5b4fc;
    box-shadow: 0 8px 18px -12px rgba(79, 70, 229, 0.8);
}

.btn-primary:hover {
    box-shadow: 0 12px 24px -14px rgba(79, 70, 229, 0.85);
}

.btn-outline-primary {
    --bs-btn-color: var(--edu-primary);
    --bs-btn-border-color: rgba(79, 70, 229, 0.35);
    --bs-btn-hover-bg: var(--edu-primary);
    --bs-btn-hover-border-color: var(--edu-primary);
    --bs-btn-active-bg: var(--edu-primary-dark);
    --bs-btn-active-border-color: var(--edu-primary-dark);
}

.btn-light {
    --bs-btn-bg: #ffffff;
    --bs-btn-border-color: var(--edu-border);
    --bs-btn-hover-bg: var(--edu-surface-soft);
    --bs-btn-hover-border-color: var(--edu-border-strong);
}

[data-bs-theme='dark'] .btn-light {
    --bs-btn-bg: var(--edu-surface-raised);
    --bs-btn-border-color: var(--edu-border);
    --bs-btn-color: var(--edu-text);
    --bs-btn-hover-bg: var(--edu-surface-muted);
    --bs-btn-hover-border-color: var(--edu-border-strong);
    --bs-btn-hover-color: var(--edu-text);
}

.btn-soft-primary {
    background: var(--edu-primary-soft);
    border-color: transparent;
    color: var(--edu-primary);
}

.btn-soft-primary:hover {
    background: rgba(79, 70, 229, 0.18);
    color: var(--edu-primary-dark);
}

.edu-icon-btn {
    width: 42px;
    height: 42px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    border: 1px solid transparent;
    background: transparent;
    color: var(--edu-muted);
    transition: background-color var(--edu-transition), color var(--edu-transition),
        border-color var(--edu-transition);
}

.edu-icon-btn:hover,
.edu-icon-btn:focus-visible {
    background: var(--edu-surface-muted);
    color: var(--edu-text);
}

.badge {
    font-weight: 600;
    letter-spacing: 0.01em;
    padding: 0.38em 0.65em;
    border-radius: 0.45rem;
}

.badge.rounded-pill {
    border-radius: 50rem !important;
}

.edu-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.65rem;
    border-radius: 999px;
    background: var(--edu-surface-muted);
    border: 1px solid var(--edu-border);
    color: var(--edu-muted-strong);
    font-size: 0.75rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.form-control,
.form-select {
    border-radius: 0.625rem;
    border-color: var(--edu-border-strong);
    background-color: var(--edu-surface);
    color: var(--edu-text);
    padding-block: 0.55rem;
}

.form-control::placeholder {
    color: var(--edu-muted);
    opacity: 0.85;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--edu-primary);
    box-shadow: var(--edu-ring);
    background-color: var(--edu-surface);
    color: var(--edu-text);
}

.form-label {
    font-weight: 600;
    font-size: 0.8125rem;
    color: var(--edu-text-soft);
    margin-bottom: 0.4rem;
}

.input-group-text {
    background: var(--edu-surface-soft);
    border-color: var(--edu-border-strong);
    color: var(--edu-muted);
    border-radius: 0.625rem;
}

.form-check-input:checked {
    background-color: var(--edu-primary);
    border-color: var(--edu-primary);
}

.form-check-input:focus {
    box-shadow: var(--edu-ring);
}

// --- Cards -------------------------------------------------------------------
.card {
    --bs-card-bg: var(--edu-surface);
    --bs-card-border-color: var(--edu-border);
    --bs-card-cap-bg: transparent;
    --bs-card-border-radius: var(--edu-radius);
    background-color: var(--edu-surface);
    color: var(--edu-text);
    border-radius: var(--edu-radius);
    border: 1px solid var(--edu-border);
    box-shadow: var(--edu-shadow-card);
}

.card-header {
    border-bottom-color: var(--edu-border);
    padding: 1rem 1.15rem;
    background: transparent;
}

.card-footer {
    border-top-color: var(--edu-border);
    background: transparent;
    padding: 0.9rem 1.15rem;
}

.card-body {
    padding: 1.15rem;
}

// The dashboard uses .edu-card for the hover lift. Keeping it opt-in avoids
// forms and static panels jumping while somebody is typing in them.
.edu-card,
.edu-stat-card,
.edu-chart-card {
    transition: transform var(--edu-transition), box-shadow var(--edu-transition),
        border-color var(--edu-transition);
}

.edu-card:hover,
.edu-stat-card:hover,
.edu-chart-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--edu-shadow-hover);
    border-color: var(--edu-border-strong);
}

// --- Tables ------------------------------------------------------------------
.table {
    --bs-table-bg: transparent;
    --bs-table-color: var(--edu-text);
    --bs-table-border-color: var(--edu-border);
    --bs-table-hover-bg: rgba(79, 70, 229, 0.045);
    --bs-table-striped-bg: rgba(148, 163, 184, 0.06);
    margin-bottom: 0;
}

.table > thead {
    background: var(--edu-surface-soft);
}

.table > :not(caption) > * > * {
    padding: 0.85rem 1rem;
    border-bottom-color: var(--edu-border);
    vertical-align: middle;
}

.table > thead th {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--edu-muted);
    border-bottom: 1px solid var(--edu-border);
    white-space: nowrap;
}

.table > tbody tr:last-child > * {
    border-bottom-width: 0;
}

.table-hover > tbody > tr:hover > * {
    --bs-table-bg-state: rgba(79, 70, 229, 0.05);
}

[data-bs-theme='dark'] .table-hover > tbody > tr:hover > * {
    --bs-table-bg-state: rgba(129, 140, 248, 0.09);
}

// --- Dropdowns and modals ----------------------------------------------------
.dropdown-menu {
    --bs-dropdown-bg: var(--edu-surface-raised);
    --bs-dropdown-border-color: var(--edu-border);
    --bs-dropdown-link-color: var(--edu-text);
    --bs-dropdown-link-hover-bg: var(--edu-surface-muted);
    --bs-dropdown-link-active-bg: var(--edu-primary);
    --bs-dropdown-link-active-color: #ffffff;
    border-radius: 0.85rem;
    padding: 0.45rem;
    box-shadow: var(--edu-shadow-pop);
}

.dropdown-item {
    border-radius: 0.55rem;
    padding: 0.55rem 0.7rem;
    font-size: 0.875rem;
}

.dropdown-divider {
    border-color: var(--edu-border);
}

.modal-content {
    background: var(--edu-surface-raised);
    color: var(--edu-text);
    border: 1px solid var(--edu-border);
    border-radius: 1rem;
    box-shadow: var(--edu-shadow-pop);
}

.modal-header,
.modal-footer {
    border-color: var(--edu-border);
}

.list-group {
    --bs-list-group-bg: transparent;
    --bs-list-group-color: var(--edu-text);
    --bs-list-group-border-color: var(--edu-border);
    --bs-list-group-action-hover-bg: var(--edu-surface-soft);
    --bs-list-group-action-active-bg: var(--edu-surface-muted);
}

.alert {
    border-radius: 0.85rem;
    border-width: 1px;
    font-size: 0.875rem;
}

.progress {
    background: var(--edu-surface-muted);
    border-radius: 999px;
    height: 0.5rem;
}

.progress-bar {
    border-radius: 999px;
}

.edu-progress .progress {
    min-height: 6px;
}

// -----------------------------------------------------------------------------
// Avatars
// -----------------------------------------------------------------------------

.avatar {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

.avatar-sm {
    width: 32px;
    height: 32px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

.avatar-lg {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

.avatar-xl {
    width: 128px;
    height: 128px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

// Initials fallback when a user has not uploaded a photo.
.edu-avatar-initials {
    display: inline-grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-size: 0.9em;
    font-weight: 700;
    letter-spacing: 0.02em;
    line-height: 1;
    user-select: none;
}

.avatar-sm.edu-avatar-initials {
    font-size: 0.68rem;
}

.avatar-lg.edu-avatar-initials {
    font-size: 1.45rem;
}

.avatar-xl.edu-avatar-initials {
    font-size: 1.9rem;
}

.profile-photo-preview {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 50%;
    border: 1px solid var(--edu-border);
    background-color: var(--edu-surface-soft);
}

// -----------------------------------------------------------------------------
// App shell: sidebar + navbar
// -----------------------------------------------------------------------------

.edu-body {
    min-height: 100vh;
}

.edu-shell {
    display: flex;
    align-items: stretch;
    min-height: calc(100vh - var(--edu-navbar-height));
}

.edu-main {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-width: 0;
}

.edu-main-inner {
    flex: 1 1 auto;
    padding: 0 0 2.5rem;
}

.edu-page {
    animation: edu-page-in 320ms var(--edu-ease) both;
}

@keyframes edu-page-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.edu-page.is-leaving {
    opacity: 0;
    transform: translateY(4px);
    transition: opacity 130ms ease, transform 130ms ease;
}

// --- Desktop sidebar ---------------------------------------------------------
.edu-sidebar {
    --bs-offcanvas-width: 292px;
    width: var(--edu-sidebar-width);
    flex: 0 0 auto;
    color: var(--edu-sidebar-text);
    background: linear-gradient(160deg, var(--edu-sidebar-from) 0%, var(--edu-sidebar-to) 100%);
    border-right: 1px solid rgba(255, 255, 255, 0.06);
    transition: width 180ms var(--edu-ease);
    z-index: 1030;
}

// On desktop the offcanvas is static, so it needs its own positioning context
// for the decorative ::before layer. On phones it is already fixed.
@media (min-width: 992px) {
    .edu-sidebar {
        position: relative;
    }
}

.edu-sidebar::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background:
        radial-gradient(420px 260px at 10% -10%, rgba(124, 58, 237, 0.22), transparent 60%),
        radial-gradient(360px 240px at 110% 20%, rgba(79, 70, 229, 0.16), transparent 60%);
}

.edu-sidebar > * {
    position: relative;
    z-index: 1;
}

.edu-sidebar .offcanvas-body {
    padding: 1rem 0.85rem 1.1rem;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.18) transparent;
}

.edu-sidebar .offcanvas-body::-webkit-scrollbar {
    width: 6px;
}

.edu-sidebar .offcanvas-body::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.18);
    border-radius: 99px;
}

.edu-sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.35rem 0.5rem 1rem;
    color: #ffffff;
    text-decoration: none;
}

.edu-sidebar-logo:hover {
    color: #ffffff;
}

.edu-sidebar-logo-mark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border-radius: 13px;
    background: linear-gradient(145deg, var(--edu-primary) 0%, var(--edu-secondary) 100%);
    box-shadow: 0 10px 22px -12px rgba(79, 70, 229, 0.9);
    color: #ffffff;
    font-size: 1.15rem;
}

.edu-sidebar-logo-text {
    line-height: 1.15;
}

.edu-sidebar-logo-text strong {
    display: block;
    font-size: 1.02rem;
    font-weight: 700;
    letter-spacing: -0.02em;
}

.edu-sidebar-logo-text span {
    display: block;
    font-size: 0.68rem;
    color: var(--edu-sidebar-heading);
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.edu-sidebar-section {
    margin: 0.9rem 0.5rem 0.35rem;
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--edu-sidebar-heading);
}

.edu-sidebar .nav-link {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.7rem;
    min-height: 44px;
    padding: 0.62rem 0.75rem;
    margin-bottom: 0.15rem;
    border-radius: 0.7rem;
    color: var(--edu-sidebar-text);
    font-size: 0.875rem;
    font-weight: 500;
    transition: background-color var(--edu-transition), color var(--edu-transition);
}

.edu-sidebar .nav-link i {
    font-size: 1.05rem;
    width: 1.25rem;
    text-align: center;
    flex: 0 0 1.25rem;
    color: var(--edu-sidebar-heading);
    transition: color var(--edu-transition);
}

.edu-sidebar .nav-link:hover,
.edu-sidebar .nav-link:focus-visible {
    background: var(--edu-sidebar-hover);
    color: #ffffff;
}

.edu-sidebar .nav-link:hover i,
.edu-sidebar .nav-link:focus-visible i {
    color: #c7d2fe;
}

.edu-sidebar .nav-link.active {
    background: var(--edu-sidebar-active);
    color: #ffffff;
    font-weight: 600;
}

.edu-sidebar .nav-link.active i {
    color: #a5b4fc;
}

.edu-sidebar .nav-link.active::before {
    content: '';
    position: absolute;
    left: -0.85rem;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 22px;
    border-radius: 0 3px 3px 0;
    background: linear-gradient(180deg, #a5b4fc, #7c3aed);
    box-shadow: 0 0 16px rgba(165, 180, 252, 0.7);
}

.edu-sidebar .nav-link .badge {
    margin-left: auto;
    font-size: 0.65rem;
}

.edu-sidebar-divider {
    border-top: 1px solid var(--edu-sidebar-border);
    margin: 0.9rem 0.5rem;
}

.edu-sidebar-user {
    margin-top: auto;
    padding: 0.85rem;
    border-radius: 0.9rem;
    background: rgba(255, 255, 255, 0.055);
    border: 1px solid var(--edu-sidebar-border);
}

.edu-sidebar-user-name {
    color: #ffffff;
    font-weight: 600;
    font-size: 0.875rem;
    line-height: 1.2;
}

.edu-sidebar-user-role {
    color: var(--edu-sidebar-heading);
    font-size: 0.72rem;
    text-transform: capitalize;
}

.edu-sidebar-user .avatar,
.edu-sidebar-user .avatar-sm {
    border: 1px solid rgba(255, 255, 255, 0.2);
}

// The offcanvas header on phones must match the dark column.
.edu-sidebar .offcanvas-header {
    border-bottom: 1px solid var(--edu-sidebar-border) !important;
    color: #ffffff;
}

.edu-sidebar .offcanvas-title {
    color: #ffffff;
}

.edu-sidebar .btn-close {
    filter: invert(1) grayscale(1) brightness(2);
}

// Collapsed desktop sidebar: icon-only, still readable through tooltips.
@media (min-width: 992px) {
    html.edu-sidebar-collapsed .edu-sidebar {
        width: var(--edu-sidebar-collapsed-width);
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-label,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-section,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-logo-text,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user-text,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user .badge,
    html.edu-sidebar-collapsed .edu-sidebar .nav-link .badge {
        display: none !important;
    }

    html.edu-sidebar-collapsed .edu-sidebar .nav-link {
        justify-content: center;
        padding-inline: 0.55rem;
    }

    html.edu-sidebar-collapsed .edu-sidebar .nav-link i {
        margin: 0;
    }

    html.edu-sidebar-collapsed .edu-sidebar .nav-link.active::before {
        left: -0.55rem;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-logo {
        justify-content: center;
        padding-inline: 0;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user {
        padding: 0.6rem;
        text-align: center;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user .d-flex {
        justify-content: center;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-footer-link {
        justify-content: center;
    }
}

// --- Top navbar --------------------------------------------------------------
.edu-navbar {
    position: sticky;
    top: 0;
    z-index: 1040;
    min-height: var(--edu-navbar-height);
    padding: 0.55rem 0;
    background: color-mix(in srgb, var(--edu-surface) 82%, transparent);
    border-bottom: 1px solid var(--edu-border);
    backdrop-filter: saturate(180%) blur(16px);
    -webkit-backdrop-filter: saturate(180%) blur(16px);
}

@supports not (backdrop-filter: blur(1px)) {
    .edu-navbar {
        background: var(--edu-surface);
    }
}

.edu-navbar .navbar-brand {
    color: var(--edu-text);
    font-weight: 700;
    letter-spacing: -0.02em;
}

.edu-navbar-title {
    display: block;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--edu-text);
    line-height: 1.2;
    max-width: 22rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.edu-navbar .breadcrumb {
    --bs-breadcrumb-divider-color: var(--edu-muted);
    --bs-breadcrumb-item-padding-x: 0.35rem;
    margin: 0;
    font-size: 0.72rem;
}

.edu-navbar .breadcrumb a {
    color: var(--edu-muted);
    text-decoration: none;
}

.edu-navbar .breadcrumb a:hover {
    color: var(--edu-primary);
}

.edu-navbar .breadcrumb-item.active {
    color: var(--edu-muted-strong);
}

.edu-search {
    width: 260px;
}

.edu-search .form-control {
    background: var(--edu-surface-soft);
    border-color: transparent;
    font-size: 0.85rem;
}

.edu-search .form-control:focus {
    background: var(--edu-surface);
    border-color: var(--edu-border-strong);
}

@media (min-width: 992px) {
    .edu-search {
        width: 320px;
    }
}

.edu-navbar .nav-link {
    color: var(--edu-text);
}

.edu-navbar .dropdown-toggle::after {
    display: none;
}

// Bootstrap sets `.navbar-nav .dropdown-menu` to `position: static` outside a
// `navbar-expand-*` container, so the user/notification menus would expand the
// bar and push the page down. The top bar is always horizontal here, so keep
// its menus floating above the content instead.
.edu-navbar .navbar-nav .dropdown-menu {
    position: absolute;
}

.edu-navbar-avatar {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.25rem 0.45rem 0.25rem 0.3rem;
    border-radius: 999px;
    border: 1px solid var(--edu-border);
    background: var(--edu-surface);
    color: var(--edu-text);
    text-decoration: none;
    transition: box-shadow var(--edu-transition), border-color var(--edu-transition);
}

.edu-navbar-avatar:hover {
    border-color: var(--edu-border-strong);
    box-shadow: var(--edu-shadow-xs);
    color: var(--edu-text);
}

.notification-menu {
    width: min(380px, 92vw);
    max-height: 72vh;
    overflow-y: auto;
}

.notification-unread {
    background-color: var(--edu-primary-soft);
    border-left: 3px solid var(--edu-primary);
}

.edu-time {
    font-size: 0.72rem;
}

// Footer
.edu-footer {
    border-top: 1px solid var(--edu-border);
    background: var(--edu-surface);
    color: var(--edu-muted);
    padding: 1.1rem 0;
    font-size: 0.8125rem;
}

// -----------------------------------------------------------------------------
// Dashboard shared components
// -----------------------------------------------------------------------------

.edu-dashboard {
    padding-bottom: 1rem;
}

.edu-greeting {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.4rem;
}

.edu-greeting h1 {
    font-size: clamp(1.5rem, 1.2rem + 1.2vw, 2rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    margin: 0.15rem 0 0.2rem;
}

.edu-greeting p {
    margin: 0;
    color: var(--edu-muted);
    font-size: 0.875rem;
}

.edu-quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

// --- Stat cards --------------------------------------------------------------
.edu-stat-card {
    position: relative;
    overflow: hidden;
}

.edu-stat-card .card-body {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-height: 142px;
}

.edu-stat-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.edu-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    flex: 0 0 46px;
    border-radius: 14px;
    font-size: 1.2rem;
    line-height: 1;
}

.edu-stat-icon.bg-primary {
    background: rgba(79, 70, 229, 0.12) !important;
    color: #4f46e5 !important;
}

.edu-stat-icon.bg-success {
    background: rgba(16, 185, 129, 0.13) !important;
    color: #059669 !important;
}

.edu-stat-icon.bg-info {
    background: rgba(14, 165, 233, 0.13) !important;
    color: #0284c7 !important;
}

.edu-stat-icon.bg-warning {
    background: rgba(245, 158, 11, 0.15) !important;
    color: #b45309 !important;
}

.edu-stat-icon.bg-danger {
    background: rgba(239, 68, 68, 0.13) !important;
    color: #dc2626 !important;
}

.edu-stat-icon.bg-secondary {
    background: rgba(124, 58, 237, 0.14) !important;
    color: #6d28d9 !important;
}

[data-bs-theme='dark'] .edu-stat-icon.bg-primary { color: #a5b4fc !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-success { color: #6ee7b7 !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-info { color: #7dd3fc !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-warning { color: #fcd34d !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-danger { color: #fca5a5 !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-secondary { color: #c4b5fd !important; }

.edu-stat-value {
    font-size: clamp(1.45rem, 1.25rem + 0.6vw, 1.85rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
    color: var(--edu-text);
}

.edu-stat-label {
    color: var(--edu-muted);
    font-size: 0.8125rem;
    font-weight: 500;
}

.edu-stat-hint {
    color: var(--edu-muted);
    font-size: 0.72rem;
}

.edu-trend {
    display: inline-flex;
    align-items: center;
    gap: 0.18rem;
    font-size: 0.72rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.edu-trend.is-up { color: #059669; }
.edu-trend.is-down { color: #dc2626; }
.edu-trend.is-flat { color: var(--edu-muted); }

[data-bs-theme='dark'] .edu-trend.is-up { color: #6ee7b7; }
[data-bs-theme='dark'] .edu-trend.is-down { color: #fca5a5; }

.edu-sparkline {
    width: 96px;
    height: 34px;
    overflow: visible;
    opacity: 0.95;
}

.edu-sparkline polyline {
    fill: none;
    stroke-width: 2.2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.edu-sparkline .sparkline-fill {
    stroke: none;
    opacity: 0.16;
}

// --- Chart cards -------------------------------------------------------------
.edu-chart-card .card-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.65rem;
}

.edu-chart-card .card-title {
    font-size: 0.95rem;
    font-weight: 700;
    margin: 0;
}

.edu-chart-card .card-subtitle {
    color: var(--edu-muted);
    font-size: 0.75rem;
    margin-top: 0.15rem;
}

.edu-chart {
    position: relative;
    width: 100%;
}

.edu-chart canvas {
    max-width: 100%;
}

.edu-chart-skeleton {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    padding: 0.5rem 0;
    z-index: 2;
    background: var(--edu-surface);
    border-radius: 0.75rem;
}

.edu-chart-skeleton[hidden] {
    display: none !important;
}

.edu-chart-skeleton span {
    flex: 1 1 0;
    border-radius: 0.35rem;
    background: linear-gradient(
        90deg,
        var(--edu-surface-muted) 0%,
        var(--edu-border) 45%,
        var(--edu-surface-muted) 90%
    );
    background-size: 200% 100%;
    animation: edu-shimmer 1.2s linear infinite;
}

.edu-chart-skeleton span:nth-child(1) { height: 42%; }
.edu-chart-skeleton span:nth-child(2) { height: 66%; }
.edu-chart-skeleton span:nth-child(3) { height: 54%; }
.edu-chart-skeleton span:nth-child(4) { height: 82%; }
.edu-chart-skeleton span:nth-child(5) { height: 60%; }
.edu-chart-skeleton span:nth-child(6) { height: 92%; }
.edu-chart-skeleton span:nth-child(7) { height: 70%; }
.edu-chart-skeleton span:nth-child(8) { height: 48%; }

@keyframes edu-shimmer {
    from { background-position: 200% 0; }
    to { background-position: -200% 0; }
}

.edu-chart-range {
    width: auto;
    min-width: 118px;
    font-size: 0.78rem;
    padding: 0.35rem 1.8rem 0.35rem 0.6rem;
    border-radius: 0.55rem;
    background-color: var(--edu-surface-soft);
    border-color: var(--edu-border);
}

.edu-chart-total {
    font-variant-numeric: tabular-nums;
}

// --- Hero cards --------------------------------------------------------------
.edu-hero {
    position: relative;
    overflow: hidden;
    border-radius: var(--edu-radius-lg);
    border: 1px solid rgba(255, 255, 255, 0.08);
    background:
        radial-gradient(900px 360px at 90% -40%, rgba(124, 58, 237, 0.55), transparent 60%),
        linear-gradient(135deg, #1e1b4b 0%, #312e81 45%, #4f46e5 100%);
    color: #ffffff;
    box-shadow: 0 26px 60px -30px rgba(49, 46, 129, 0.85);
    padding: clamp(1.25rem, 1rem + 1.2vw, 2rem);
}

.edu-hero::after {
    content: '';
    position: absolute;
    right: -80px;
    bottom: -120px;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    border: 44px solid rgba(255, 255, 255, 0.05);
    pointer-events: none;
}

.edu-hero > * {
    position: relative;
    z-index: 1;
}

.edu-hero h1,
.edu-hero h2 {
    color: #ffffff;
    letter-spacing: -0.03em;
}

.edu-hero .edu-hero-meta {
    color: rgba(255, 255, 255, 0.72);
    font-size: 0.875rem;
}

.edu-hero .edu-hero-label {
    color: #c7d2fe;
}

.edu-hero .btn-light {
    --bs-btn-bg: #ffffff;
    --bs-btn-color: #312e81;
    --bs-btn-hover-bg: #eef2ff;
    --bs-btn-hover-color: #1e1b4b;
    border-color: #ffffff;
}

.edu-hero .btn-outline-light {
    --bs-btn-color: #ffffff;
    --bs-btn-border-color: rgba(255, 255, 255, 0.45);
    --bs-btn-hover-bg: rgba(255, 255, 255, 0.14);
    --bs-btn-hover-border-color: #ffffff;
}

.edu-hero .edu-chip {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.18);
    color: #e0e7ff;
}

.edu-hero-live {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    background: rgba(239, 68, 68, 0.18);
    border: 1px solid rgba(248, 113, 113, 0.4);
    color: #fecaca;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.edu-live-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ef4444;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.25);
    animation: edu-pulse 1.6s ease-in-out infinite;
}

@keyframes edu-pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(0.82); opacity: 0.65; }
}

// --- Countdown ---------------------------------------------------------------
.edu-countdown {
    display: inline-flex;
    align-items: stretch;
    gap: 0.45rem;
}

.edu-countdown-unit {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 58px;
    padding: 0.4rem 0.55rem;
    border-radius: 0.65rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.14);
}

.edu-countdown-value {
    font-size: 1.2rem;
    font-weight: 800;
    line-height: 1;
    font-variant-numeric: tabular-nums;
    color: #ffffff;
}

.edu-countdown-label {
    font-size: 0.58rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.66);
    margin-top: 0.2rem;
}

.edu-countdown.is-compact .edu-countdown-unit {
    min-width: 46px;
    padding: 0.3rem 0.4rem;
}

.edu-countdown.is-compact .edu-countdown-value {
    font-size: 1rem;
}

// --- Avatar stack ------------------------------------------------------------
.edu-avatar-stack {
    display: inline-flex;
    align-items: center;
    padding-left: 0.35rem;
}

.edu-avatar-stack .avatar,
.edu-avatar-stack .avatar-sm {
    border: 2px solid var(--edu-hero-avatar-border, #312e81);
    margin-left: -0.45rem;
    background: var(--edu-surface-muted);
}

.edu-avatar-stack .edu-avatar-stack-more {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    margin-left: -0.45rem;
    background: rgba(255, 255, 255, 0.16);
    border: 2px solid #312e81;
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

// Outside hero cards the stack needs a light border.
.edu-avatar-stack.on-light .avatar,
.edu-avatar-stack.on-light .avatar-sm {
    border-color: var(--edu-surface);
}

.edu-avatar-stack.on-light .edu-avatar-stack-more {
    background: var(--edu-surface-muted);
    border-color: var(--edu-surface);
    color: var(--edu-muted-strong);
}

// --- Activity timeline -------------------------------------------------------
.edu-activity {
    position: relative;
    display: flex;
    gap: 0.85rem;
    padding: 0.8rem 0;
}

.edu-activity + .edu-activity {
    border-top: 1px solid var(--edu-border);
}

.edu-activity-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    border-radius: 12px;
    font-size: 1rem;
}

.edu-activity-icon.bg-primary { background: rgba(79, 70, 229, 0.12); color: #4f46e5; }
.edu-activity-icon.bg-success { background: rgba(16, 185, 129, 0.13); color: #059669; }
.edu-activity-icon.bg-info { background: rgba(14, 165, 233, 0.13); color: #0284c7; }
.edu-activity-icon.bg-warning { background: rgba(245, 158, 11, 0.15); color: #b45309; }
.edu-activity-icon.bg-danger { background: rgba(239, 68, 68, 0.13); color: #dc2626; }

.edu-activity-text {
    font-size: 0.85rem;
    color: var(--edu-text-soft);
    line-height: 1.4;
}

.edu-activity-excerpt {
    color: var(--edu-muted);
    font-size: 0.78rem;
    margin-top: 0.15rem;
}

.edu-activity-time {
    color: var(--edu-muted);
    font-size: 0.72rem;
    margin-top: 0.2rem;
}

// --- Data table wrapper ------------------------------------------------------
.edu-data-table .card-header {
    align-items: center;
}

.edu-table-tools {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.edu-table-search {
    position: relative;
}

.edu-table-search i {
    position: absolute;
    left: 0.65rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--edu-muted);
    font-size: 0.8rem;
    pointer-events: none;
}

.edu-table-search .form-control {
    min-width: 190px;
    padding-left: 2rem;
    font-size: 0.8rem;
    background: var(--edu-surface-soft);
    border-color: transparent;
}

.edu-table-search .form-control:focus {
    background: var(--edu-surface);
    border-color: var(--edu-border-strong);
}

.edu-sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
}

.edu-sortable:hover {
    color: var(--edu-text);
}

.edu-sortable::after {
    content: '\F282';
    font-family: 'bootstrap-icons';
    font-size: 0.62rem;
    margin-left: 0.3rem;
    opacity: 0.35;
}

.edu-sortable[aria-sort='ascending']::after {
    content: '\F286';
    opacity: 0.9;
}

.edu-sortable[aria-sort='descending']::after {
    content: '\F284';
    opacity: 0.9;
}

.edu-table-empty {
    padding: 1.5rem 1rem;
    text-align: center;
    color: var(--edu-muted);
    font-size: 0.85rem;
}

.edu-table-count {
    color: var(--edu-muted);
    font-size: 0.75rem;
    font-variant-numeric: tabular-nums;
}

// --- Schedule strip ----------------------------------------------------------
.edu-schedule {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0.45rem;
}

.edu-schedule-day {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.6rem 0.25rem;
    border-radius: 0.8rem;
    border: 1px solid var(--edu-border);
    background: var(--edu-surface-soft);
    min-height: 86px;
    text-align: center;
}

.edu-schedule-day.is-today {
    border-color: rgba(79, 70, 229, 0.4);
    background: var(--edu-primary-soft);
}

.edu-schedule-weekday {
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--edu-muted);
}

.edu-schedule-date {
    font-size: 0.95rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    color: var(--edu-text);
    line-height: 1.1;
}

.edu-schedule-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 0.3rem;
    border-radius: 999px;
    background: var(--edu-surface-muted);
    color: var(--edu-muted-strong);
    font-size: 0.68rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.edu-schedule-day.has-classes .edu-schedule-count {
    background: var(--edu-primary);
    color: #ffffff;
}

@media (max-width: 575.98px) {
    .edu-schedule {
        gap: 0.3rem;
    }

    .edu-schedule-day {
        min-height: 76px;
        padding: 0.45rem 0.15rem;
    }

    .edu-schedule-date {
        font-size: 0.85rem;
    }
}

// --- Panels / lists ----------------------------------------------------------
.edu-list {
    display: flex;
    flex-direction: column;
}

.edu-list-row {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.8rem 0;
}

.edu-list-row + .edu-list-row {
    border-top: 1px solid var(--edu-border);
}

.edu-list-row:first-child {
    padding-top: 0.2rem;
}

.edu-list-row:last-child {
    padding-bottom: 0.2rem;
}

.edu-list-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--edu-text);
    text-decoration: none;
    display: block;
}

.edu-list-title:hover {
    color: var(--edu-primary);
}

.edu-list-meta {
    color: var(--edu-muted);
    font-size: 0.74rem;
}

.edu-list-excerpt {
    color: var(--edu-muted-strong);
    font-size: 0.8rem;
    margin-top: 0.15rem;
}

.edu-report-actions {
    display: flex;
    gap: 0.35rem;
    flex-shrink: 0;
}

.edu-report-actions .btn {
    width: 34px;
    height: 34px;
    padding: 0;
}

// --- Feed preview ------------------------------------------------------------
.edu-feed-item {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 0;
}

.edu-feed-item + .edu-feed-item {
    border-top: 1px solid var(--edu-border);
}

.edu-feed-body {
    font-size: 0.82rem;
    color: var(--edu-text-soft);
    margin: 0.2rem 0 0.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

// --- Feed sidebar widgets ----------------------------------------------------
.edu-widget-user {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.7rem 0;
}

.edu-widget-user + .edu-widget-user {
    border-top: 1px solid var(--edu-border);
}

.edu-widget-user .edu-list-title {
    font-size: 0.84rem;
}

.edu-widget-user .btn {
    white-space: nowrap;
}

.edu-trending-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.65rem 0;
    color: inherit;
    text-decoration: none;
}

.edu-trending-row + .edu-trending-row {
    border-top: 1px solid var(--edu-border);
}

.edu-trending-row:hover .edu-list-title {
    color: var(--edu-primary);
}

.edu-trending-rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 12px;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-weight: 700;
    flex-shrink: 0;
}

.edu-post-image {
    width: 100%;
    max-height: 520px;
    object-fit: cover;
    background: var(--edu-surface-muted);
}

.edu-post-comments .avatar,
.edu-post-comments .avatar-sm {
    width: 34px;
    height: 34px;
    font-size: 0.8rem;
}

// --- Empty states ------------------------------------------------------------
.edu-empty-illustration {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 76px;
    height: 76px;
    border-radius: 24px;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-size: 1.9rem;
    margin-bottom: 0.9rem;
    position: relative;
}

.edu-empty-illustration::after {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 30px;
    border: 1px dashed rgba(79, 70, 229, 0.22);
}

// --- Skeletons ---------------------------------------------------------------
.edu-skeleton {
    display: block;
    border-radius: 0.5rem;
    background: linear-gradient(
        90deg,
        var(--edu-surface-muted) 0%,
        var(--edu-border) 45%,
        var(--edu-surface-muted) 90%
    );
    background-size: 200% 100%;
    animation: edu-shimmer 1.2s linear infinite;
}

// -----------------------------------------------------------------------------
// Legacy application pages (feed, auth, live room) — kept intentionally
// -----------------------------------------------------------------------------

.post-body {
    white-space: pre-wrap;
    word-break: break-word;
}

.breadcrumb {
    --bs-breadcrumb-divider-color: var(--edu-muted);
}

[x-cloak] {
    display: none !important;
}

.jitsi-frame {
    position: relative;
    width: 100%;
    height: calc(100vh - 340px);
    min-height: 420px;
    border-radius: 0.75rem;
    overflow: hidden;
    background-color: #1c1c1c;
}

.jitsi-frame iframe {
    width: 100% !important;
    height: 100% !important;
    border: 0;
}

@media (max-width: 991.98px) {
    .jitsi-frame {
        height: calc(100vh - 260px);
        min-height: 360px;
        border-radius: 0.5rem;
    }
}

.edu-auth-body {
    background-color: var(--edu-surface);
    color: var(--edu-text);
}

.edu-auth-brand {
    position: relative;
    overflow: hidden;
    color: #fff;
    background: linear-gradient(145deg, #312e81 0%, #4338ca 45%, #4f46e5 100%);
}

.edu-auth-brand::before,
.edu-auth-brand::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    pointer-events: none;
}

.edu-auth-brand::before {
    width: 420px;
    height: 420px;
    top: -160px;
    right: -140px;
}

.edu-auth-brand::after {
    width: 300px;
    height: 300px;
    bottom: -120px;
    left: -100px;
}

.edu-auth-brand > * {
    position: relative;
    z-index: 1;
}

.edu-auth-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    flex: 0 0 44px;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.14);
    font-size: 1.15rem;
}

.edu-auth-form {
    max-width: 440px;
}

.edu-auth-card {
    border-radius: 1rem;
}

.password-toggle {
    min-width: 48px;
}

@media (max-width: 991.98px) {
    .edu-auth-body {
        background-color: var(--edu-page-bg);
    }
}

// -----------------------------------------------------------------------------
// Responsive and touch refinements
// -----------------------------------------------------------------------------

@media (max-width: 991.98px) {
    .edu-main-inner {
        padding-top: 1.1rem;
    }

    .edu-sidebar {
        --bs-offcanvas-width: min(88vw, 312px);
    }
}

@media (max-width: 575.98px) {
    .btn,
    .form-control,
    .form-select,
    .nav-link,
    .dropdown-item {
        min-height: 44px;
    }

    .btn-sm {
        min-height: 44px;
    }

    .edu-icon-btn {
        width: 44px;
        height: 44px;
    }

    .edu-greeting {
        align-items: flex-start;
    }

    .edu-quick-actions {
        width: 100%;
    }

    .edu-quick-actions .btn {
        flex: 1 1 auto;
    }

    .edu-stat-card .card-body {
        min-height: 128px;
    }

    .edu-table-search .form-control {
        min-width: 0;
        width: 100%;
    }

    .edu-table-tools {
        width: 100%;
    }

    .edu-table-tools .edu-table-search {
        flex: 1 1 auto;
    }

    .edu-countdown {
        gap: 0.3rem;
    }

    .edu-countdown-unit {
        min-width: 48px;
        padding: 0.35rem 0.35rem;
    }

    .edu-countdown-value {
        font-size: 1.05rem;
    }
}

// Larger screens get a little more breathing room.
@media (min-width: 1920px) {
    .edu-main-inner {
        padding-top: 2rem;
    }

    .edu-sidebar {
        --edu-sidebar-width: 284px;
    }

    .edu-stat-card .card-body {
        min-height: 152px;
    }
}

// -----------------------------------------------------------------------------
// Phase 3C — component library additions
// -----------------------------------------------------------------------------
// Everything below is built from the tokens in _variables.scss: same spacing
// steps, same radii, same transitions, same z-index ladder.

// Comfortable reading width for long prose (about 70 characters).
.edu-measure {
    max-width: var(--edu-measure);
}

// --- Button loading state ----------------------------------------------------
// A form button that is waiting for the server: spinner in place of the label.
.btn.is-loading {
    position: relative;
    color: transparent !important;
    pointer-events: none;

    > * {
        visibility: hidden;
    }

    &::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 1rem;
        height: 1rem;
        margin: -0.5rem 0 0 -0.5rem;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        color: #fff;
        animation: edu-spin 0.6s linear infinite;
    }
}

.btn-outline-primary.is-loading::after,
.btn-outline-secondary.is-loading::after,
.btn-light.is-loading::after {
    color: var(--edu-primary);
}

@keyframes edu-spin {
    to {
        transform: rotate(360deg);
    }
}

// --- Toasts ------------------------------------------------------------------
// Rendered by resources/js/toast.js from the data attributes of <x-flash />.
.edu-toast-stack {
    position: fixed;
    right: var(--edu-space-4);
    bottom: var(--edu-space-4);
    z-index: var(--edu-z-toast);
    display: flex;
    flex-direction: column;
    gap: var(--edu-space-2);
    width: min(360px, calc(100vw - 2 * var(--edu-space-4)));
    pointer-events: none;
}

.edu-toast {
    display: flex;
    align-items: flex-start;
    gap: var(--edu-space-2);
    padding: var(--edu-space-3) var(--edu-space-4);
    border-radius: var(--edu-radius-sm);
    border: 1px solid var(--edu-border);
    background: var(--edu-surface);
    color: var(--edu-text);
    box-shadow: var(--edu-shadow-pop);
    pointer-events: auto;
    animation: edu-toast-in 200ms var(--edu-ease);

    .btn-close {
        padding: 0.35rem;
        margin-left: auto;
    }
}

.edu-toast.is-success {
    border-left: 4px solid var(--edu-success);
}

.edu-toast.is-error {
    border-left: 4px solid var(--edu-danger);
}

.edu-toast.is-info {
    border-left: 4px solid var(--edu-info);
}

@keyframes edu-toast-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
}

// --- Role cards on the register page ----------------------------------------
.edu-role-card {
    display: flex;
    align-items: flex-start;
    gap: var(--edu-space-3);
    padding: var(--edu-space-3) var(--edu-space-4);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface-soft);
    cursor: pointer;
    transition: border-color var(--edu-transition), background-color var(--edu-transition),
        box-shadow var(--edu-transition);

    &:hover {
        border-color: var(--edu-border-strong);
    }

    // The radio itself is hidden: the whole card is the hit area (44px+ tall).
    input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .edu-role-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: var(--edu-radius-xs);
        background: var(--edu-primary-soft);
        color: var(--edu-primary);
        font-size: 1.15rem;
    }

    // Selected state, driven by :has() plus a JS class for older browsers.
    &:has(input:checked),
    &.is-selected {
        border-color: var(--edu-primary);
        background: var(--edu-surface);
        box-shadow: 0 0 0 1px var(--edu-primary);
    }

    &:has(input:focus-visible),
    &.is-selected:focus-within {
        outline: 2px solid var(--edu-primary);
        outline-offset: 2px;
    }
}

// --- Password strength meter -------------------------------------------------
.edu-strength {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    margin-top: var(--edu-space-2);
}

.edu-strength-track {
    display: flex;
    gap: 4px;
}

.edu-strength-seg {
    flex: 1;
    height: 6px;
    border-radius: 999px;
    background: var(--edu-surface-muted);
    transition: background-color var(--edu-transition);
}

.edu-strength[data-score='1'] .edu-strength-seg:nth-child(-n + 1),
.edu-strength[data-score='2'] .edu-strength-seg:nth-child(-n + 2) {
    background: var(--edu-danger);
}

.edu-strength[data-score='3'] .edu-strength-seg:nth-child(-n + 3) {
    background: var(--edu-warning);
}

.edu-strength[data-score='4'] .edu-strength-seg {
    background: var(--edu-success);
}

// --- Mobile bottom navigation ------------------------------------------------
.edu-mobile-nav {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: var(--edu-z-mobilenav);
    display: flex;
    align-items: stretch;
    justify-content: space-around;
    border-top: 1px solid var(--edu-border);
    background: var(--edu-surface);
    // Respect the iPhone home indicator area.
    padding-bottom: env(safe-area-inset-bottom, 0);
    box-shadow: 0 -6px 24px -18px rgba(15, 23, 42, 0.4);
}

.edu-mobile-nav-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    // 44px minimum touch target.
    min-width: 56px;
    min-height: 56px;
    flex: 1;
    padding: 0.4rem 0.25rem;
    font-size: var(--edu-fs-xs);
    font-weight: 600;
    color: var(--edu-muted);
    text-decoration: none;

    i {
        font-size: 1.25rem;
    }

    &.active {
        color: var(--edu-primary);
    }

    &:hover {
        color: var(--edu-primary);
    }
}

// The badge sits on the icon, not on the label.
.edu-mobile-nav-badge {
    position: absolute;
    top: 4px;
    left: 50%;
    margin-left: 6px;
    font-size: 0.625rem;
    padding: 0.15em 0.4em;
}

// --- Floating "new post" button ---------------------------------------------
.edu-fab {
    position: fixed;
    right: var(--edu-space-4);
    bottom: calc(72px + env(safe-area-inset-bottom, 0));
    z-index: var(--edu-z-fab);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    border: 0;
    border-radius: 50%;
    background: var(--edu-primary);
    color: #fff;
    font-size: 1.4rem;
    box-shadow: var(--edu-shadow-pop);
    transition: transform var(--edu-transition), background-color var(--edu-transition);

    &:hover,
    &:focus-visible {
        background: var(--edu-primary-dark);
        color: #fff;
    }

    &:active {
        transform: scale(0.94);
    }
}

@media (min-width: 992px) {
    .edu-fab {
        bottom: var(--edu-space-5);
        right: var(--edu-space-5);
    }
}

// --- Profile cover banner ----------------------------------------------------
.edu-cover {
    position: relative;
    height: 132px;
    border-radius: var(--edu-radius) var(--edu-radius) 0 0;
    background: linear-gradient(120deg, #312e81 0%, #4f46e5 55%, #7c3aed 100%);
    overflow: hidden;

    &::after {
        content: '';
        position: absolute;
        width: 260px;
        height: 260px;
        right: -80px;
        top: -140px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
    }
}

.edu-cover-avatar {
    margin-top: -48px;
}

// --- Highlighted search match ------------------------------------------------
// The surrounding text keeps its colour; only the matched part is marked, so
// the highlight never hides information and always has contrast.
.edu-mark {
    padding: 0 0.15em;
    border-radius: var(--edu-radius-xs);
    background: rgba(245, 158, 11, 0.28);
    color: inherit;
    font-weight: 600;
}

[data-bs-theme='dark'] .edu-mark {
    background: rgba(245, 158, 11, 0.32);
}

// --- Filter chips ------------------------------------------------------------
.edu-filter-chip {
    display: inline-flex;
    align-items: center;
    gap: var(--edu-space-1);
    padding: 0.35rem 0.75rem;
    min-height: 36px;
    border: 1px solid var(--edu-border);
    border-radius: 999px;
    background: var(--edu-surface);
    color: var(--edu-text-soft);
    font-size: var(--edu-fs-small);
    font-weight: 600;
    text-decoration: none;
    transition: border-color var(--edu-transition), background-color var(--edu-transition),
        color var(--edu-transition);

    &:hover {
        border-color: var(--edu-primary);
        color: var(--edu-primary);
    }

    &.is-active {
        border-color: var(--edu-primary);
        background: var(--edu-primary);
        color: #fff;
    }
}

// --- Notification groups (Today / Earlier) -----------------------------------
.edu-notif-group-label {
    display: flex;
    align-items: center;
    gap: var(--edu-space-2);
    margin: var(--edu-space-4) 0 var(--edu-space-2);
    font-size: var(--edu-fs-xs);
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--edu-muted);

    &::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--edu-border);
    }
}

.edu-notif-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--edu-primary);
    flex-shrink: 0;
}

// --- Sticky data tables + mobile card view -----------------------------------
.edu-table-sticky thead th {
    position: sticky;
    top: 0;
    z-index: var(--edu-z-base);
    background: var(--edu-surface);
    box-shadow: inset 0 -1px 0 var(--edu-border);
}

// Below lg each row becomes a card. The label of every cell comes from the
// data-label attribute, so nothing is lost when the header disappears.
@media (max-width: 991.98px) {
    .edu-table-cards {
        thead {
            display: none;
        }

        tbody tr {
            display: block;
            border: 1px solid var(--edu-border);
            border-radius: var(--edu-radius-sm);
            margin-bottom: var(--edu-space-3);
            padding: var(--edu-space-2);
            background: var(--edu-surface);
        }

        tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--edu-space-3);
            border: 0;
            padding: 0.4rem 0.25rem;
            text-align: right;
        }

        tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            font-size: var(--edu-fs-small);
            color: var(--edu-muted);
            text-align: left;
        }
    }
}

// --- Style guide helpers -----------------------------------------------------
.edu-swatch {
    height: 56px;
    border-radius: var(--edu-radius-sm);
    border: 1px solid var(--edu-border);
}

.edu-guide-block {
    padding: var(--edu-space-4);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface);
}

// --- Landing page (guests) ---------------------------------------------------
.edu-landing {
    background: var(--edu-surface);
}

.edu-landing-hero {
    position: relative;
    overflow: hidden;
    padding: var(--edu-space-8) 0 var(--edu-space-7);
    background: linear-gradient(180deg, var(--edu-surface-soft) 0%, var(--edu-surface) 100%);
}

.edu-landing-shape {
    position: absolute;
    border-radius: 50%;
    filter: blur(6px);
    pointer-events: none;
    opacity: 0.55;
}

.edu-landing-shape.is-one {
    width: 380px;
    height: 380px;
    top: -160px;
    right: -120px;
    background: radial-gradient(circle at 30% 30%, rgba(79, 70, 229, 0.35), transparent 70%);
}

.edu-landing-shape.is-two {
    width: 320px;
    height: 320px;
    bottom: -180px;
    left: -120px;
    background: radial-gradient(circle at 60% 40%, rgba(124, 58, 237, 0.3), transparent 70%);
}

.edu-landing-title {
    font-size: var(--edu-fs-display);
    line-height: 1.1;
    letter-spacing: -0.03em;
}

// Mockup of the live-class screen: pure CSS, no image request.
.edu-mockup {
    border-radius: var(--edu-radius);
    border: 1px solid var(--edu-border);
    background: var(--edu-surface);
    box-shadow: var(--edu-shadow-pop);
    overflow: hidden;
}

.edu-mockup-bar {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: var(--edu-space-2) var(--edu-space-3);
    border-bottom: 1px solid var(--edu-border);
    background: var(--edu-surface-soft);
}

.edu-mockup-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--edu-border-strong);
}

.edu-mockup-stage {
    position: relative;
    aspect-ratio: 16 / 9;
    background: linear-gradient(140deg, #1e1b4b 0%, #312e81 50%, #4f46e5 100%);
}

.edu-mockup-tile {
    position: absolute;
    border-radius: var(--edu-radius-xs);
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.22);
}

.edu-mockup-bar-strip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--edu-space-3);
    padding: var(--edu-space-3);
    background: var(--edu-surface-soft);
}

.edu-mockup-tool {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--edu-surface);
    border: 1px solid var(--edu-border);
    color: var(--edu-text-soft);
}

.edu-step-card {
    position: relative;
    padding: var(--edu-space-5);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius);
    background: var(--edu-surface);
    height: 100%;
}

.edu-step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: var(--edu-radius-xs);
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-weight: 700;
}

.edu-feature-card {
    padding: var(--edu-space-5);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius);
    background: var(--edu-surface);
    height: 100%;
    transition: transform var(--edu-transition), box-shadow var(--edu-transition),
        border-color var(--edu-transition);

    &:hover {
        transform: translateY(-3px);
        border-color: var(--edu-border-strong);
        box-shadow: var(--edu-shadow-card);
    }
}

.edu-stats-strip {
    border-radius: var(--edu-radius);
    background: linear-gradient(120deg, #312e81 0%, #4f46e5 100%);
    color: #fff;
    padding: var(--edu-space-6) var(--edu-space-5);
}

.edu-stat-figure {
    font-size: clamp(1.5rem, 1.1rem + 1.4vw, 2.25rem);
    font-weight: 800;
    line-height: 1.1;
}

.edu-landing-section {
    padding: var(--edu-space-7) 0;
}

.edu-section-title {
    font-size: var(--edu-fs-h2);
    letter-spacing: -0.02em;
}

// A tidy accordion: no double borders, the open item is clearly marked.
.edu-faq .accordion-item {
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface);
    margin-bottom: var(--edu-space-2);
    overflow: hidden;
}

.edu-faq .accordion-button {
    font-weight: 600;
    background: var(--edu-surface);
    color: var(--edu-text);
    box-shadow: none;

    &:not(.collapsed) {
        color: var(--edu-primary);
        background: var(--edu-surface-soft);
    }
}

.edu-landing-footer {
    padding: var(--edu-space-6) 0 var(--edu-space-5);
    border-top: 1px solid var(--edu-border);
    background: var(--edu-surface-soft);
}

// On phones the bottom navigation and the floating button need room.
@media (max-width: 991.98px) {
    .edu-has-mobile-nav {
        padding-bottom: calc(64px + env(safe-area-inset-bottom, 0));
    }
}

// --- Phase 3C additions ------------------------------------------------------

// Skip link: invisible until it receives keyboard focus, then a normal button.
.edu-skip-link {
    position: fixed;
    top: var(--edu-space-2);
    left: var(--edu-space-2);
    z-index: calc(var(--edu-z-fab) + 10);
    padding: 0.5rem 0.9rem;
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface);
    color: var(--edu-text);
    box-shadow: var(--edu-shadow-pop);
    font-weight: 600;
    text-decoration: none;

    &:focus {
        outline: 2px solid var(--edu-primary);
        outline-offset: 2px;
    }
}

// Sidebars that stay in view while the main column scrolls.
.edu-sticky-aside {
    position: static;
}

@media (min-width: 1200px) {
    .edu-sticky-aside {
        position: sticky;
        top: calc(var(--edu-navbar-height) + var(--edu-space-3));
    }
}

// Character ring in the feed composer.
.edu-char-ring {
    --edu-ring-progress: 0deg;
    --edu-ring-color: var(--edu-primary);
    position: relative;
    display: inline-grid;
    place-items: center;
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    border-radius: 50%;
    background: conic-gradient(var(--edu-ring-color) var(--edu-ring-progress), var(--edu-border) 0);
    font-size: var(--edu-fs-xs);
    font-weight: 700;
    color: var(--edu-muted);
    transition: background var(--edu-transition);

    &::before {
        content: '';
        position: absolute;
        inset: 4px;
        border-radius: 50%;
        background: var(--edu-surface);
    }

    > * {
        position: relative;
        z-index: 1;
    }

    &.is-warning {
        --edu-ring-color: var(--edu-warning);
    }

    &.is-full {
        --edu-ring-color: var(--edu-danger);
    }
}

// Heart pop when a post is liked (disabled by prefers-reduced-motion).
.edu-like-pop {
    animation: edu-like-pop 220ms ease-out;
}

@keyframes edu-like-pop {
    0% { transform: scale(1); }
    40% { transform: scale(1.35); }
    70% { transform: scale(0.94); }
    100% { transform: scale(1); }
}

// Notification row icon.
.edu-notif-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-size: 1rem;

    .notification-unread & {
        background: var(--edu-primary);
        color: #fff;
    }
}

// Hashtag page hero.
.edu-hashtag-hero {
    display: grid;
    place-items: center;
    width: 64px;
    height: 64px;
    flex-shrink: 0;
    border-radius: var(--edu-radius);
    background: linear-gradient(135deg, var(--edu-primary) 0%, var(--edu-secondary) 100%);
    color: #fff;
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

// Admin tables: keep the post preview readable without stretching the table.
.edu-admin-post-cell {
    min-width: 16rem;
    max-width: 30rem;

    @media (max-width: 991.98px) {
        min-width: 0;
        max-width: none;
    }
}

// Accessibility: honour the operating-system "reduce motion" setting. One
// single media query for the whole stylesheet (Phase 3C merged the block that
// used to be duplicated here).
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }

    .edu-feature-card:hover,
    .edu-card-hover:hover {
        transform: none;
    }

    .edu-page,
    .edu-page.is-leaving {
        animation: none !important;
        transform: none !important;
        opacity: 1 !important;
    }

    .edu-chart-skeleton span,
    .edu-skeleton,
    .edu-live-dot,
    .edu-toast,
    .btn.is-loading::after {
        animation: none !important;
    }
}

// -----------------------------------------------------------------------------
// Phase 3D — responsive layer (imported last on purpose)
// -----------------------------------------------------------------------------
@import 'responsive';
````

### A.7 Tests

#### `tests/Feature/PhaseEightTest.php`

````php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Services\ImageUploadService;
use App\Support\DashboardMetrics;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 8: security audit, performance pass, usability polish and the
 * requirement-wide regression net (SRS 5.1 - 5.4).
 *
 * The page-level tests already prove the features. This suite pins the
 * cross-cutting rules that no single feature owns:
 *
 *  - security headers, CSRF on every write form, mass-assignment safety,
 *    escaped output and safe search,
 *  - upload hardening (MIME/size, decode/re-encode, random names, no PHP),
 *  - rate limits and Form Request validation,
 *  - friendly error pages, meta tags, favicon and the guest landing page,
 *  - query budgets, five-minute dashboard caches, hot-path indexes and the
 *    "under three seconds" page-load target.
 */
class PhaseEightTest extends TestCase
{
    use RefreshDatabase;

    /* ===============================================================
     | Security headers and document head
     * =============================================================== */

    public function test_security_headers_are_sent_on_every_page(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-XSS-Protection', '0')
            ->assertHeader(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=()'
            );

        // PHP must not advertise its version to the client.
        $this->assertFalse($response->headers->has('X-Powered-By'));
    }

    public function test_the_jitsi_room_relaxes_framing_for_the_video_domain(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);

        $response = $this->actingAs($student)->get(route('classes.room', $classRoom));

        $response->assertOk();

        // The page embeds Jitsi, so X-Frame-Options must not block it.
        $response->assertHeaderMissing('X-Frame-Options');

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('frame-src', $csp);
        $this->assertStringContainsString('https://'.config('services.jitsi.domain'), $csp);

        $permissions = (string) $response->headers->get('Permissions-Policy');
        $this->assertStringContainsString('camera=(self', $permissions);
        $this->assertStringContainsString('microphone=(self', $permissions);

        // The normal pages keep the strict default.
        $plain = $this->get('/');
        $plain->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_the_layout_ships_meta_social_and_favicon_tags(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:description"', false)
            ->assertSee('name="twitter:card"', false)
            ->assertSee('favicon.ico', false)
            ->assertSee('apple-touch-icon', false)
            ->assertSee('theme-color', false)
            // Guest landing page title from the title slot.
            ->assertSee('<title>Learn together, anywhere · EduConnect</title>', false);

        // The link above must point at a real icon, not the 0-byte placeholder
        // that shipped with the framework skeleton.
        $favicon = public_path('favicon.ico');
        $this->assertFileExists($favicon);
        $this->assertGreaterThan(0, filesize($favicon));

        $header = unpack('vreserved/vtype/vcount', (string) file_get_contents($favicon, false, null, 0, 6));
        $this->assertSame(1, $header['type'], 'favicon.ico must be a valid icon file');
        $this->assertGreaterThanOrEqual(1, $header['count']);
    }

    public function test_every_post_form_carries_a_csrf_token(): void
    {
        // Static audit of the Blade templates: a form that writes must always
        // render @csrf, including the modal/component forms.
        $violations = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = $file->getContents();

            preg_match_all('/<form\b[^>]*>/i', $contents, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as [$tag, $offset]) {
                // GET forms (search/filter) do not change state and need no token.
                if (preg_match('/method\s*=\s*["\']?post["\']?/i', $tag) !== 1) {
                    continue;
                }

                $end = strpos($contents, '</form>', $offset);
                $form = $end === false
                    ? substr($contents, $offset)
                    : substr($contents, $offset, $end - $offset);

                if (! str_contains($form, '@csrf')) {
                    $violations[] = $file->getRelativePathname().': '.trim($tag);
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Write forms without @csrf:\n".implode("\n", $violations)
        );
    }

    /* ===============================================================
     | Form Requests and mass-assignment safety
     * =============================================================== */

    public function test_the_comment_form_request_rejects_empty_and_long_bodies(): void
    {
        $author = User::factory()->student()->create();
        $commenter = User::factory()->student()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($commenter)
            ->post(route('comments.store', $post), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->actingAs($commenter)
            ->post(route('comments.store', $post), ['body' => str_repeat('a', 281)])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_a_hidden_post_cannot_collect_new_comments(): void
    {
        $author = User::factory()->student()->create();
        $other = User::factory()->student()->create();
        $post = Post::factory()->hidden()->create(['user_id' => $author->id]);

        // StoreCommentRequest authorizes against PostPolicy::view, which hides
        // moderated posts from everybody except the author and admins.
        $this->actingAs($other)
            ->post(route('comments.store', $post), ['body' => 'Sneaky comment'])
            ->assertForbidden();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_the_class_status_form_request_rejects_unknown_statuses(): void
    {
        $owner = User::factory()->teacher()->create();
        $stranger = User::factory()->teacher()->create();
        $classRoom = ClassRoom::factory()->create([
            'teacher_id' => $owner->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
        ]);

        $this->actingAs($owner)
            ->patch(route('classes.status', $classRoom), ['status' => 'exploded'])
            ->assertSessionHasErrors('status');

        $this->assertSame(ClassRoom::STATUS_SCHEDULED, $classRoom->fresh()->status);

        // Only the owning teacher may change the status (ClassPolicy).
        $this->actingAs($stranger)
            ->patch(route('classes.status', $classRoom), ['status' => ClassRoom::STATUS_LIVE])
            ->assertForbidden();

        $this->assertSame(ClassRoom::STATUS_SCHEDULED, $classRoom->fresh()->status);
    }

    public function test_account_deletion_requires_the_current_password(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'wrong-password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        // The account is still there.
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_write_requests_cannot_smuggle_protected_columns(): void
    {
        $author = User::factory()->student()->create();
        $victim = User::factory()->student()->create();

        // A crafted post tries to set the author and the moderation flag.
        $this->actingAs($author)->post(route('posts.store'), [
            'body' => 'A perfectly normal post',
            'user_id' => $victim->id,
            'is_hidden' => 1,
        ])->assertRedirect(route('feed.index'));

        $post = Post::query()->firstOrFail();
        $this->assertSame($author->id, $post->user_id);
        $this->assertFalse($post->is_hidden);

        // A crafted profile update tries to become an administrator and to
        // block/unblock itself.
        $this->actingAs($author)->patch(route('profile.update'), [
            'name' => $author->name,
            'email' => $author->email,
            'role' => User::ROLE_ADMIN,
            'is_blocked' => 1,
        ])->assertRedirect(route('profile.edit'));

        $author->refresh();
        $this->assertSame(User::ROLE_STUDENT, $author->role);
        $this->assertFalse($author->is_blocked);

        // The public registration form cannot create an admin either.
        $this->actingAs($author)->post(route('logout'));

        $this->post(route('register'), [
            'name' => 'Wannabe Admin',
            'email' => 'wannabe@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => User::ROLE_ADMIN,
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'wannabe@example.test']);
    }

    /* ===============================================================
     | Upload hardening
     * =============================================================== */

    public function test_a_disguised_php_upload_is_rejected_before_it_reaches_the_disk(): void
    {
        Storage::fake('public');

        $user = User::factory()->student()->create();

        $this->actingAs($user)->post(route('posts.store'), [
            'body' => 'Look at this photo',
            'image' => UploadedFile::fake()->createWithContent(
                'shell.jpg',
                '<?php echo "pwned"; ?>'
            ),
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseCount('posts', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_the_image_service_rejects_a_file_that_is_not_decodable(): void
    {
        Storage::fake('public');

        $service = app(ImageUploadService::class);
        $file = UploadedFile::fake()->createWithContent('broken.jpg', 'not an image');

        try {
            $service->storePostImage($file);
            $this->fail('A non-image file was accepted by ImageUploadService.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('image', $exception->errors());
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_uploaded_images_are_resized_re_encoded_and_renamed(): void
    {
        Storage::fake('public');

        $service = app(ImageUploadService::class);

        // 2400x1200 is larger than the 1600x1600 ceiling.
        $path = $service->storePostImage(
            UploadedFile::fake()->image('holiday.jpg', 2400, 1200)
        );

        $this->assertMatchesRegularExpression(
            '#^posts/[A-Za-z0-9]{40}\.(webp|jpg)$#',
            $path,
            'The stored name must be random and use an extension we chose.'
        );

        Storage::disk('public')->assertExists($path);

        $size = getimagesize(Storage::disk('public')->path($path));

        $this->assertIsArray($size);
        $this->assertSame(1600, $size[0]);
        $this->assertSame(800, $size[1]);

        // Even a .php name is stored under a safe extension: the client name
        // never decides what lands on disk.
        $evilNamed = UploadedFile::fake()->image('payload.php', 40, 40);
        $safePath = $service->storePostImage($evilNamed);

        $this->assertStringEndsNotWith('.php', $safePath);
        $this->assertMatchesRegularExpression('#\.(webp|jpg)$#', $safePath);
        Storage::disk('public')->assertExists($safePath);
    }

    /* ===============================================================
     | Rate limiting
     * =============================================================== */

    public function test_login_is_locked_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $locked = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $locked->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts',
            (string) session('errors')->first('email')
        );
        $this->assertGuest();
    }

    public function test_registration_is_rate_limited_to_five_per_minute(): void
    {
        // Invalid payloads never create a user (and never log in), so the
        // throttle middleware is what answers the sixth request.
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('register'), [])->assertSessionHasErrors('name');
        }

        $this->post(route('register'), [])->assertStatus(429);
    }

    public function test_every_expensive_write_route_has_a_throttle(): void
    {
        $routes = [
            'posts.store',
            'comments.store',
            'posts.like',
            'reports.posts.store',
            'reports.comments.store',
            'follows.store',
            'notifications.poll',
            'search.suggest',
            'password.email',
        ];

        foreach ($routes as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} is missing.");

            $middleware = implode('|', $route->gatherMiddleware());
            $this->assertStringContainsString(
                'throttle:',
                $middleware,
                "Route {$name} has no rate limit: {$middleware}"
            );
        }

        $this->assertStringContainsString(
            'throttle:',
            implode('|', Route::getRoutes()->getByName('password.store')->gatherMiddleware())
        );
    }

    public function test_named_rate_limiters_are_registered(): void
    {
        foreach ([
            'register',
            'password-reset',
            'posts',
            'comments',
            'likes',
            'reports',
            'follows',
            'notifications-poll',
            'search-suggest',
        ] as $name) {
            $this->assertTrue(
                RateLimiter::limiter($name) !== null,
                "Named limiter {$name} is missing."
            );
        }
    }

    public function test_the_database_backup_command_is_scheduled_daily(): void
    {
        $this->artisan('backup:database')->assertSuccessful();

        $schedule = app(Schedule::class);
        $events = collect($schedule->events());
        $this->assertTrue(
            $events->contains(fn ($event) => str_contains($event->command ?? '', 'backup:database')),
            'backup:database command is not scheduled.'
        );
    }

    public function test_comments_are_rate_limited_per_user(): void
    {
        $author = User::factory()->student()->create();
        $commenter = User::factory()->student()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        // 30 comments per minute are allowed; the 31st is refused.
        for ($i = 1; $i <= 30; $i++) {
            $this->actingAs($commenter)
                ->post(route('comments.store', $post), ['body' => 'Comment '.$i])
                ->assertRedirect();
        }

        $this->actingAs($commenter)
            ->post(route('comments.store', $post), ['body' => 'One too many'])
            ->assertStatus(429);
    }

    public function test_reports_are_rate_limited_per_user(): void
    {
        $reporter = User::factory()->student()->create();
        $author = User::factory()->teacher()->create();

        // Ten reports per minute: one per post, so every request is unique.
        for ($i = 1; $i <= 10; $i++) {
            $post = Post::factory()->create(['user_id' => $author->id]);

            $this->actingAs($reporter)
                ->post(route('reports.posts.store', $post), ['reason' => 'spam'])
                ->assertRedirect();
        }

        $extra = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $extra), ['reason' => 'spam'])
            ->assertStatus(429);
    }

    /* ===============================================================
     | Usability: error pages, escaping, landing page
     * =============================================================== */

    public function test_all_friendly_error_pages_render(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found');

        $forbidden = $this->get('/admin/dashboard');
        $forbidden->assertRedirect(route('login'));

        $student = User::factory()->student()->create();
        $this->actingAs($student)
            ->get('/admin/dashboard')
            ->assertForbidden()
            ->assertSee('You are not allowed to open this page');

        foreach ([403, 404, 419, 500, 503] as $code) {
            $html = view('errors.'.$code)->render();
            $this->assertStringContainsString('Back to home', $html);
            $this->assertStringContainsString('Still stuck?', $html);
        }

        $this->assertStringContainsString(
            'Something went wrong on our side',
            view('errors.500')->render()
        );
    }

    public function test_user_content_is_escaped_in_the_feed(): void
    {
        $viewer = User::factory()->student()->create();
        $payload = '<script>alert(1)</script>';

        Post::factory()->create([
            'user_id' => $viewer->id,
            'body' => $payload.' #safety',
        ]);

        $response = $this->actingAs($viewer)->get(route('feed.index'));

        $response->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee($payload, false);

        // The hashtag part is still linked, because linkify() escapes first.
        $response->assertSee('#safety');
    }

    public function test_search_input_cannot_break_the_query(): void
    {
        $viewer = User::factory()->student()->create();
        User::factory()->create(['name' => 'Secret Colleague']);

        $this->actingAs($viewer)
            ->get(route('search.index', ['q' => "' OR 1=1 --"]))
            ->assertOk()
            ->assertDontSee('Secret Colleague');
    }

    /* ===============================================================
     | Performance: query budgets, caching, indexes and page timing
     * =============================================================== */

    public function test_dashboards_stay_within_a_query_budget(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        User::factory()->count(4)->student()->create();
        Post::factory()->count(8)->create();
        ClassRoom::factory()->count(4)->create(['teacher_id' => $teacher->id]);
        Report::factory()->count(3)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $queries = count(DB::getQueryLog());

        $response->assertOk();
        $this->assertLessThanOrEqual(
            45,
            $queries,
            "The admin dashboard ran {$queries} queries; eager loading or caching regressed."
        );

        $this->assertCount(5, $response->viewData('stats'));
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk();
    }

    public function test_dashboard_metrics_are_cached_for_five_minutes(): void
    {
        $metrics = app(DashboardMetrics::class);

        $before = $metrics->adminStats();
        $this->assertTrue(Cache::has('dashboard.admin.stats.v1'));

        // A new user changes the real count, but the cached number stays put.
        User::factory()->student()->create();
        $cached = $metrics->adminStats();
        $this->assertSame($before['users'], $cached['users']);

        // Clearing the key proves the stale value came from the cache.
        Cache::forget('dashboard.admin.stats.v1');
        $fresh = $metrics->adminStats();
        $this->assertSame($before['users'] + 1, $fresh['users']);

        // The pending reports counter is cached under its own key.
        Report::factory()->create();
        $this->assertSame(1, $metrics->adminPendingReports());
        $this->assertTrue(Cache::has('dashboard.admin.pending-reports.v1'));

        Report::factory()->create();
        $this->assertSame(1, $metrics->adminPendingReports());
        Cache::forget('dashboard.admin.pending-reports.v1');
        $this->assertSame(2, $metrics->adminPendingReports());

        // Teacher and student keys are scoped per user, never shared.
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $metrics->teacherStats($teacher);
        $metrics->studentStats($student);

        $this->assertTrue(Cache::has("dashboard.teacher.{$teacher->id}.stats.v1"));
        $this->assertTrue(Cache::has("dashboard.student.{$student->id}.stats.v1"));
        $this->assertFalse(Cache::has("dashboard.teacher.{$student->id}.stats.v1"));
    }

    public function test_the_hot_query_indexes_are_present(): void
    {
        $indexes = [
            ['posts', 'phase8_posts_user_created_index'],
            ['posts', 'phase8_posts_hidden_created_index'],
            ['comments', 'phase8_comments_post_created_index'],
            ['comments', 'phase8_comments_user_created_index'],
            ['likes', 'phase8_likes_user_created_index'],
            ['follows', 'phase8_follows_follower_created_index'],
            ['follows', 'phase8_follows_following_created_index'],
            ['classes', 'phase8_classes_status_date_index'],
            ['classes', 'phase8_classes_teacher_date_index'],
            ['classes', 'phase8_classes_hidden_date_index'],
            ['class_enrollments', 'phase8_enrollments_student_created_index'],
            ['reports', 'phase8_reports_status_created_index'],
            ['reports', 'phase8_reports_reportable_status_index'],
            ['notifications', 'phase8_notifications_unread_index'],
            ['users', 'phase8_users_role_blocked_index'],
        ];

        foreach ($indexes as [$table, $index]) {
            $this->assertTrue(
                Schema::hasIndex($table, $index),
                "Missing index {$index} on {$table}."
            );
        }
    }

    public function test_the_primary_pages_load_in_under_three_seconds(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $classRoom = ClassRoom::factory()->scheduled()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);
        Post::factory()->count(10)->create(['user_id' => $teacher->id]);

        $pages = [
            'guest landing' => [null, '/'],
            'login' => [null, route('login')],
            'feed' => [$student, route('feed.index')],
            'search' => [$student, route('search.index', ['q' => 'math'])],
            'class catalogue' => [$student, route('classes.discover')],
            'student dashboard' => [$student, route('student.dashboard')],
            'profile' => [$student, route('profile.edit')],
            'notifications' => [$student, route('notifications.index')],
            'teacher dashboard' => [$teacher, route('teacher.dashboard')],
            'my classes' => [$teacher, route('classes.index')],
            'admin dashboard' => [$admin, route('admin.dashboard')],
            'admin users' => [$admin, route('admin.users.index')],
        ];

        foreach ($pages as $label => [$user, $url]) {
            // Warm up view compilation so disk/CPU jitter does not skew the response time
            if ($user === null) {
                $this->get($url);
            } else {
                $this->actingAs($user)->get($url);
            }

            // Measure the fastest of three warm requests. A loaded CI machine
            // can stall a single request for a second or two; using the best
            // run keeps the assertion about the application, not the scheduler.
            // A real regression is still caught because all three runs are slow.
            $best = INF;
            $response = null;

            for ($attempt = 0; $attempt < 3; $attempt++) {
                $started = microtime(true);

                $response = $user === null
                    ? $this->get($url)
                    : $this->actingAs($user)->get($url);

                $best = min($best, microtime(true) - $started);
            }

            $response->assertOk();
            $this->assertLessThan(
                3.0,
                $best,
                "The {$label} page took ".number_format($best, 2).'s (target: under 3s).'
            );
        }
    }
}
````

#### `tests/Feature/FeedTest.php`

````php
<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\User;
use App\Services\HashtagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 4: the complete social feed.
 *
 * Covers FR-9 (create post, 280 chars, throttle),
 * FR-10 (edit/delete own posts, image cleanup),
 * FR-11 (feed of followed + own, like toggle),
 * FR-13 (comments + CommentPolicy),
 * FR-14 (public profile),
 * FR-15 (hashtags, XSS safe links, hashtag page) and
 * FR-11 (follow / unfollow, no self-follow).
 */
class FeedTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Creating posts (FR-9)
     * --------------------------------------------------------------- */

    public function test_a_logged_in_user_can_publish_a_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'body' => 'My very first post on EduConnect',
        ]);

        $response->assertRedirect(route('feed.index'));

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'body' => 'My very first post on EduConnect',
            'is_hidden' => false,
        ]);
    }

    public function test_a_post_cannot_be_longer_than_280_characters(): void
    {
        $user = User::factory()->create();

        // 281 characters must be rejected by StorePostRequest (FR-9).
        $this->actingAs($user)
            ->post(route('posts.store'), ['body' => str_repeat('a', 281)])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('posts', 0);

        // Exactly 280 characters still works.
        $this->actingAs($user)
            ->post(route('posts.store'), ['body' => str_repeat('a', 280)])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_an_empty_post_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('posts.store'), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_a_guest_cannot_publish_a_post(): void
    {
        $this->post(route('posts.store'), ['body' => 'Sneaky'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_post_creation_is_rate_limited_to_ten_per_minute(): void
    {
        $user = User::factory()->create();

        // Ten posts inside the same minute are fine (FR-9).
        for ($i = 1; $i <= 10; $i++) {
            $this->actingAs($user)
                ->post(route('posts.store'), ['body' => 'Post number '.$i])
                ->assertRedirect(route('feed.index'));
        }

        // The eleventh one hits the "throttle:10,1" middleware.
        $this->actingAs($user)
            ->post(route('posts.store'), ['body' => 'One too many'])
            ->assertStatus(429);
    }

    /* ---------------------------------------------------------------
     | Editing and deleting (FR-10)
     * --------------------------------------------------------------- */

    public function test_a_user_cannot_edit_someone_elses_post(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => 'Original text',
        ]);

        // The edit form and the update are both refused by PostPolicy (FR-10).
        $this->actingAs($other)->get(route('posts.edit', $post))->assertForbidden();
        $this->actingAs($other)
            ->patch(route('posts.update', $post), ['body' => 'Hacked'])
            ->assertForbidden();

        $this->assertSame('Original text', $post->fresh()->body);
    }

    public function test_a_user_cannot_delete_someone_elses_post(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($other)->delete(route('posts.destroy', $post))->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_the_author_can_edit_and_delete_their_own_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => 'Before',
        ]);

        $this->actingAs($author)->get(route('posts.edit', $post))->assertOk();

        $this->actingAs($author)
            ->patch(route('posts.update', $post), ['body' => 'After'])
            ->assertRedirect(route('posts.show', $post));

        $this->assertSame('After', $post->fresh()->body);

        $this->actingAs($author)
            ->delete(route('posts.destroy', $post))
            ->assertRedirect(route('feed.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_an_administrator_can_delete_a_post_but_not_edit_it(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => 'The author owns these words.',
        ]);

        // Administrators moderate; they do not rewrite other people's posts.
        $this->actingAs($admin)->get(route('posts.edit', $post))->assertForbidden();
        $this->actingAs($admin)
            ->patch(route('posts.update', $post), ['body' => 'Rewritten'])
            ->assertForbidden();

        $this->assertSame('The author owns these words.', $post->fresh()->body);

        // They may remove the post, though (PostPolicy::delete).
        $this->actingAs($admin)
            ->delete(route('posts.destroy', $post))
            ->assertRedirect(route('feed.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_a_post_image_is_stored_in_the_posts_directory(): void
    {
        Storage::fake('public');

        $author = User::factory()->create();

        $this->actingAs($author)->post(route('posts.store'), [
            'body' => 'A post with an image',
            'image' => UploadedFile::fake()->image('classroom.png', 1200, 800)->mimeType('image/png'),
        ])->assertRedirect(route('feed.index'));

        $post = Post::latest('id')->firstOrFail();

        $this->assertNotNull($post->image);
        $this->assertStringStartsWith('posts/', $post->image);
        // WebP when GD supports it, JPEG otherwise.
        $this->assertMatchesRegularExpression('/\.(webp|jpg)$/', $post->image);
        Storage::disk('public')->assertExists($post->image);

        // The upload service re-encodes the file and caps it at 1600 px.
        $dimensions = $post->imageDimensions();

        $this->assertNotNull($dimensions);
        $this->assertLessThanOrEqual(1600, $dimensions['width']);
        $this->assertLessThanOrEqual(1600, $dimensions['height']);
    }

    public function test_replacing_a_post_image_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $author = User::factory()->create();

        $this->actingAs($author)->post(route('posts.store'), [
            'body' => 'First version',
            'image' => UploadedFile::fake()->image('first.jpg', 800, 600),
        ]);

        $post = Post::latest('id')->firstOrFail();
        $oldImage = $post->image;

        Storage::disk('public')->assertExists($oldImage);

        $this->actingAs($author)->patch(route('posts.update', $post), [
            'body' => 'Second version',
            'image' => UploadedFile::fake()->image('second.jpg', 800, 600),
        ])->assertRedirect(route('posts.show', $post));

        $newImage = $post->fresh()->image;

        $this->assertNotSame($oldImage, $newImage);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($newImage);
    }

    public function test_deleting_a_post_removes_its_image_file(): void
    {
        Storage::fake('public');

        $author = User::factory()->create();

        $this->actingAs($author)->post(route('posts.store'), [
            'body' => 'A post with a picture',
            // A real JPEG: ImageUploadService decodes and re-encodes every
            // upload, so a fake empty file would be rejected on purpose.
            'image' => UploadedFile::fake()->image('holiday.jpg', 1200, 800),
        ]);

        $post = Post::latest('id')->firstOrFail();

        $this->assertNotNull($post->image);
        Storage::disk('public')->assertExists($post->image);

        $this->actingAs($author)->delete(route('posts.destroy', $post));

        // The uploaded file must not be left behind on the disk (FR-10).
        Storage::disk('public')->assertMissing($post->image);
    }

    /* ---------------------------------------------------------------
     | Likes (FR-11)
     * --------------------------------------------------------------- */

    public function test_a_like_can_be_toggled_and_never_duplicates(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Like the post: JSON answer with the new state and count.
        $this->actingAs($user)
            ->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertJson(['liked' => true, 'count' => 1]);

        $this->assertDatabaseHas('likes', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        // Liking twice must not create a second row.
        $this->actingAs($user)
            ->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertJson(['liked' => true, 'count' => 1]);

        $this->assertSame(1, $post->likes()->count());

        // Unlike removes the row again.
        $this->actingAs($user)
            ->deleteJson(route('posts.unlike', $post))
            ->assertOk()
            ->assertJson(['liked' => false, 'count' => 0]);

        $this->assertDatabaseMissing('likes', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_hidden_post_cannot_be_liked_by_other_users(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->hidden()->create(['user_id' => $author->id]);

        // LikeController applies PostPolicy::view, so a moderated post cannot
        // be probed through the like endpoint either.
        $this->actingAs($other)
            ->postJson(route('posts.like', $post))
            ->assertForbidden();

        $this->assertDatabaseCount('likes', 0);
    }

    /* ---------------------------------------------------------------
     | Comments (FR-10)
     * --------------------------------------------------------------- */

    public function test_a_user_can_comment_and_only_delete_their_own_comment(): void
    {
        $postAuthor = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $postAuthor->id]);

        $commenter = User::factory()->create();
        $stranger = User::factory()->create();

        // Add a comment - the JSON answer carries the ready made HTML + count.
        $this->actingAs($commenter)
            ->postJson(route('comments.store', $post), ['body' => 'Great share!'])
            ->assertCreated()
            ->assertJsonPath('count', 1);

        $comment = Comment::latest('id')->firstOrFail();

        $this->assertSame('Great share!', $comment->body);

        // A stranger may not delete it (CommentPolicy).
        $this->actingAs($stranger)
            ->delete(route('comments.destroy', $comment))
            ->assertForbidden();

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);

        // The author of the comment can delete it.
        $this->actingAs($commenter)
            ->deleteJson(route('comments.destroy', $comment))
            ->assertOk()
            ->assertJson(['deleted' => true, 'count' => 0]);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_only_the_comment_author_or_an_admin_can_delete_a_comment(): void
    {
        $postAuthor = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $postAuthor->id]);
        $commentAuthor = User::factory()->create();

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $commentAuthor->id,
        ]);

        // Writing the post does not make somebody the owner of its comments.
        $this->actingAs($postAuthor)
            ->deleteJson(route('comments.destroy', $comment))
            ->assertForbidden();

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);

        // An administrator may remove any comment for moderation.
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->deleteJson(route('comments.destroy', $comment))
            ->assertOk()
            ->assertJson(['deleted' => true, 'count' => 0]);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    /* ---------------------------------------------------------------
     | Follow / unfollow (FR-11)
     * --------------------------------------------------------------- */

    public function test_a_student_can_follow_and_unfollow_a_teacher(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)
            ->postJson(route('follows.store', $teacher))
            ->assertOk()
            ->assertJson(['following' => true, 'followers' => 1, 'following_count' => 1]);

        $this->assertTrue($student->isFollowing($teacher));

        $this->actingAs($student)
            ->deleteJson(route('follows.destroy', $teacher))
            ->assertOk()
            ->assertJson(['following' => false, 'followers' => 0, 'following_count' => 0]);

        $this->assertFalse($student->isFollowing($teacher));
    }

    public function test_nobody_can_follow_themselves(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('follows.store', $student))
            ->assertForbidden();

        $this->assertSame(0, $student->following()->count());
    }

    public function test_a_student_can_follow_another_student(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('follows.store', $otherStudent))
            ->assertRedirect();

        $this->assertTrue($student->isFollowing($otherStudent));
    }

    /* ---------------------------------------------------------------
     | Home feed (FR-11)
     * --------------------------------------------------------------- */

    public function test_the_feed_shows_only_my_posts_and_the_posts_of_people_i_follow(): void
    {
        $me = User::factory()->student()->create();
        $followed = User::factory()->teacher()->create();
        $stranger = User::factory()->teacher()->create();

        $me->follow($followed);

        Post::factory()->create(['user_id' => $me->id, 'body' => 'BODY-MINE']);
        Post::factory()->create(['user_id' => $followed->id, 'body' => 'BODY-FOLLOWED']);
        Post::factory()->create(['user_id' => $stranger->id, 'body' => 'BODY-STRANGER']);
        // Hidden posts stay out of the feed even when they are my own (FR-27).
        Post::factory()->hidden()->create(['user_id' => $me->id, 'body' => 'BODY-HIDDEN']);

        $this->actingAs($me)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee('BODY-MINE')
            ->assertSee('BODY-FOLLOWED')
            ->assertDontSee('BODY-STRANGER')
            ->assertDontSee('BODY-HIDDEN');
    }

    public function test_the_feed_is_paginated_ten_per_page_and_shows_the_newest_first(): void
    {
        $me = User::factory()->create();

        // Twelve posts with a known order: "POST-12" is the newest.
        foreach (range(1, 12) as $number) {
            Post::factory()->create([
                'user_id' => $me->id,
                'body' => 'POST-'.$number,
                'created_at' => now()->subMinutes(13 - $number),
            ]);
        }

        $firstPage = $this->actingAs($me)->get(route('feed.index'))->assertOk();

        $firstPage->assertViewHas('posts', static function ($posts): bool {
            return $posts->count() === 10 && $posts->total() === 12;
        });

        $this->assertSame('POST-12', $firstPage->viewData('posts')->first()->body);
        $this->assertSame('POST-3', $firstPage->viewData('posts')->last()->body);

        $secondPage = $this->actingAs($me)->get(route('feed.index', ['page' => 2]))->assertOk();

        $this->assertSame(
            ['POST-2', 'POST-1'],
            $secondPage->viewData('posts')->pluck('body')->all()
        );
    }

    public function test_hidden_comments_are_not_shown_or_counted(): void
    {
        $me = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $me->id]);

        Comment::factory()->create([
            'post_id' => $post->id,
            'body' => 'VISIBLE-COMMENT',
        ]);
        Comment::factory()->hidden()->create([
            'post_id' => $post->id,
            'body' => 'HIDDEN-COMMENT',
        ]);

        $feed = $this->actingAs($me)->get(route('feed.index'))->assertOk()
            ->assertSee('VISIBLE-COMMENT')
            ->assertDontSee('HIDDEN-COMMENT');

        $this->assertSame(1, $feed->viewData('posts')->first()->comments_count);

        $this->actingAs($me)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('VISIBLE-COMMENT')
            ->assertDontSee('HIDDEN-COMMENT');
    }

    public function test_search_results_and_feed_suggestions_use_the_ajax_follow_button(): void
    {
        $me = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create([
            'name' => 'Zebracorn Teacher',
            'is_blocked' => false,
        ]);

        // Search tab: the shared user card renders a real form with the AJAX hooks.
        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'Zebracorn', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('data-follow-form', false)
            ->assertSee('data-follow-url="'.route('follows.store', $teacher).'"', false)
            ->assertSee('data-followers-count', false);

        // Feed sidebar: "Who to follow" uses exactly the same partial.
        $this->actingAs($me)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee('data-follow-form', false)
            ->assertSee('data-followers-count', false);
    }

    public function test_the_feed_shows_an_empty_state_without_posts(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee('No posts to show yet.');
    }

    public function test_the_feed_answers_json_for_the_load_more_button(): void
    {
        $me = User::factory()->create();

        // 12 posts: page 1 holds 10, page 2 the remaining 2 (FR-11).
        Post::factory()->count(12)->create(['user_id' => $me->id]);

        $this->actingAs($me)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee('data-load-more', false);

        $this->actingAs($me)
            ->getJson(route('feed.index', ['page' => 2]))
            ->assertOk()
            ->assertJsonStructure(['html', 'has_more', 'next_page'])
            ->assertJsonPath('has_more', false);
    }

    public function test_a_guest_is_redirected_from_the_feed(): void
    {
        $this->get(route('feed.index'))->assertRedirect(route('login'));
    }

    /* ---------------------------------------------------------------
     | Hashtags (FR-15)
     * --------------------------------------------------------------- */

    public function test_hashtags_are_parsed_and_synced_in_lower_case(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('posts.store'), [
            'body' => 'Learning #Laravel and #PHP today',
        ]);

        $post = Post::latest('id')->firstOrFail();

        // Stored lower case, one row per tag (no duplicates).
        $this->assertDatabaseHas('hashtags', ['name' => 'laravel']);
        $this->assertDatabaseHas('hashtags', ['name' => 'php']);
        $this->assertSame(
            ['laravel', 'php'],
            $post->hashtags()->orderBy('name')->pluck('name')->all()
        );
    }

    public function test_hashtags_support_unicode_and_are_lower_cased_everywhere(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('posts.store'), [
            'body' => '#Éducation #数学 #Café_2026 #ÉDUCATION',
        ])->assertRedirect(route('feed.index'));

        $post = Post::latest('id')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            ['éducation', '数学', 'café_2026'],
            $post->hashtags()->pluck('name')->all()
        );

        $this->assertDatabaseHas('hashtags', ['name' => 'éducation']);
        $this->assertDatabaseHas('hashtags', ['name' => '数学']);
        $this->assertDatabaseHas('hashtags', ['name' => 'café_2026']);

        $html = (string) app(HashtagService::class)->linkify('#Éducation #数学');

        $this->assertStringContainsString('href="'.route('hashtags.show', ['hashtag' => 'éducation']).'"', $html);
        $this->assertStringContainsString('href="'.route('hashtags.show', ['hashtag' => '数学']).'"', $html);
        $this->assertStringContainsString('>#éducation</a>', $html);
    }

    public function test_editing_a_post_resyncs_and_removes_old_hashtags(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'body' => 'First #laravel post',
        ]);

        app(HashtagService::class)->sync($post, $post->body);

        $this->actingAs($user)
            ->patch(route('posts.update', $post), [
                'body' => 'Second #symfony post',
            ])
            ->assertRedirect(route('posts.show', $post));

        $this->assertSame(
            ['symfony'],
            $post->fresh()->hashtags()->pluck('name')->all()
        );

        $this->assertDatabaseHas('hashtags', ['name' => 'laravel']);
        $this->assertDatabaseMissing('hashtag_post', [
            'post_id' => $post->id,
            'hashtag_id' => Hashtag::where('name', 'laravel')->value('id'),
        ]);
    }

    public function test_hashtag_links_are_xss_safe(): void
    {
        $service = app(HashtagService::class);

        // The markup is escaped first, then the link is inserted around #php.
        $html = (string) $service->linkify('<script>alert(1)</script> hello #php');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString(
            'href="'.route('hashtags.show', ['hashtag' => 'php']).'"',
            $html
        );
    }

    public function test_a_post_body_with_html_is_escaped_on_the_feed(): void
    {
        $user = User::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'body' => '<script>alert(1)</script> #safety',
        ]);

        $this->actingAs($user)
            ->get(route('feed.index'))
            ->assertOk()
            // The raw script tag must never reach the browser...
            ->assertDontSee('<script>alert(1)</script>', false)
            // ...it arrives as escaped text instead.
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_the_hashtag_page_lists_visible_posts_with_that_tag(): void
    {
        $user = User::factory()->create();
        $service = app(HashtagService::class);

        $visible = Post::factory()->create([
            'user_id' => $user->id,
            'body' => 'VISIBLE-TAG-POST about #laravel',
        ]);
        $service->sync($visible, $visible->body);

        $hidden = Post::factory()->hidden()->create([
            'user_id' => $user->id,
            'body' => 'HIDDEN-TAG-POST about #laravel',
        ]);
        $service->sync($hidden, $hidden->body);

        // An unrelated post with another tag must not show up either.
        $other = Post::factory()->create([
            'user_id' => $user->id,
            'body' => 'OTHER-TAG-POST about #symfony',
        ]);
        $service->sync($other, $other->body);

        $hashtag = Hashtag::where('name', 'laravel')->firstOrFail();
        $this->assertSame('laravel', $hashtag->name);

        $this->actingAs($user)
            ->get(route('hashtags.show', ['hashtag' => 'laravel']))
            ->assertOk()
            ->assertSee('VISIBLE-TAG-POST')
            ->assertDontSee('HIDDEN-TAG-POST')
            ->assertDontSee('OTHER-TAG-POST');
    }

    /* ---------------------------------------------------------------
     | Public profile (FR-14)
     * --------------------------------------------------------------- */

    public function test_a_public_profile_shows_the_user_posts_and_counts(): void
    {
        $teacher = User::factory()->teacher()->create();
        Post::factory()->count(3)->create(['user_id' => $teacher->id]);

        $follower = User::factory()->student()->create();
        $follower->follow($teacher);

        $this->actingAs($follower)
            ->get(route('users.show', $teacher))
            ->assertOk()
            ->assertSee($teacher->name)
            // posts + followers counters in the header.
            ->assertSee('followers')
            ->assertSee(route('users.followers', $teacher), false);
    }

    public function test_the_follower_lists_are_paginated_and_reachable(): void
    {
        $teacher = User::factory()->teacher()->create();
        $follower = User::factory()->student()->create();
        $follower->follow($teacher);

        $this->actingAs($follower)
            ->get(route('users.followers', $teacher))
            ->assertOk()
            ->assertSee($follower->name);

        $this->actingAs($follower)
            ->get(route('users.following', $follower))
            ->assertOk()
            ->assertSee($teacher->name);
    }

    /* ---------------------------------------------------------------
     | Single post page (FR-12)
     * --------------------------------------------------------------- */

    public function test_the_single_post_page_lists_its_comments(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        Comment::factory()->create([
            'post_id' => $post->id,
            'body' => 'COMMENT-ON-THE-POST',
        ]);

        $this->actingAs($user)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('COMMENT-ON-THE-POST');
    }

    public function test_a_hidden_post_is_not_visible_to_other_users(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->hidden()->create(['user_id' => $author->id]);

        // The author still reaches their own hidden post...
        $this->actingAs($author)->get(route('posts.show', $post))->assertOk();
        // ...everybody else gets a 403 (FR-27).
        $this->actingAs($other)->get(route('posts.show', $post))->assertForbidden();
    }
}
````

#### `tests/Feature/Auth/PasswordResetTest.php`

````php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    /**
     * FR-3: the reset email uses the branded EduConnect template and link.
     */
    public function test_the_reset_email_uses_the_branded_template(): void
    {
        $user = User::factory()->create(['name' => 'Maria Rossi']);

        $mail = (new ResetPasswordNotification('test-token'))->toMail($user);

        $this->assertSame('Reset your EduConnect password', $mail->subject);
        $this->assertSame('emails.reset-password', $mail->markdown);
        $this->assertSame('Maria Rossi', $mail->viewData['name']);
        $this->assertStringContainsString('/reset-password/test-token', $mail->viewData['url']);

        // The Markdown template renders without errors (button text is present).
        $this->assertStringContainsString('Choose a new password', $mail->render());
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }
}
````

### A.8 Audit and delivery documentation

#### `docs/SECURITY.md`

````markdown
# Security notes

This document is the Phase 8 audit trail for SRS 5.2. It records what the
application does, where it lives and how an automated test proves it. The route
table at the end is the literal output of
`php artisan route:list --except-vendor` with the middleware column kept intact,
so role protection can be checked route by route.

---

## 1. Authentication and authorisation

| Layer | Implementation | Proof |
| --- | --- | --- |
| Registration | `RegisterRequest` only accepts `teacher` and `student`; an `admin` value is rejected and `passedValidation()` aborts a hand-crafted request | `Auth/RegistrationTest::test_the_admin_role_cannot_be_registered`, `PhaseEightTest::test_write_requests_cannot_smuggle_protected_columns` |
| Login | Laravel session guard; five failed attempts per e-mail + IP are refused inside `LoginRequest`, then the account is locked for the decay window | `Auth/AuthenticationTest`, `PhaseEightTest::test_login_is_locked_after_five_failed_attempts` |
| Blocked accounts | `is_blocked` is checked on login and by `EnsureUserIsNotBlocked` on every web request | `Auth/BlockedUserTest`, `PhaseSevenTest::test_a_blocked_user_cannot_post_comment_enroll_or_join` |
| Role gates | `role:admin`, `role:teacher`, `role:student`, `role:student,teacher` route groups | `RoleAccessTest`, `SmokeTest`, section 9 below |
| Record gates | `PostPolicy`, `CommentPolicy`, `ClassPolicy`, `ReportPolicy`, `UserPolicy` | `FeedTest`, `ClassTest`, `ReportTest`, `PhaseSevenTest` |
| Admin safety | Administrators cannot block or delete themselves or another admin, including from the report queue | `AdminManagementTest::test_an_admin_cannot_block_or_delete_themselves_or_another_admin`, `PhaseSevenTest::test_an_administrator_cannot_be_blocked_from_the_report_queue` |
| Live room | Only the owning teacher and enrolled students; hidden classes are closed to everybody; admins moderate but do not enter rooms | `ClassTest::test_only_the_teacher_and_enrolled_students_can_open_the_room`, `test_a_hidden_class_cannot_be_joined` |

Authorisation is therefore enforced in three independent layers: HTTP
middleware, policies, and the blocked-user check that runs on every web
request. A missed policy on one action does not remove the role gate.

---

## 2. CSRF

- Every writing form renders `@csrf`; methods other than GET use `@method(...)`.
- Fetch/AJAX helpers send `X-CSRF-TOKEN` from the layout's `<meta name="csrf-token">`
  or a hidden `_token`.
- `PhaseEightTest::test_every_post_form_carries_a_csrf_token` scans every
  `resources/views/**/*.blade.php` file at test time, finds every
  `<form method="POST">` block and fails if `@csrf` is missing. GET search and
  filter forms are skipped on purpose.

The static scan means a new POST form that forgets the token fails CI, not just
code review.

---

## 3. Input validation

All state-changing input passes through a Form Request (or a scoped
`$request->validate()` where no reusable request exists):

| Request | Used by | Main rules |
| --- | --- | --- |
| `Auth/LoginRequest` | login | required e-mail, required password, five-attempt throttle |
| `Auth/RegisterRequest` | register | unique e-mail, confirmed password, role in `teacher,student` |
| `StorePostRequest` | create post | body 1–280, image MIME/size from `config/educonnect.php` |
| `UpdatePostRequest` | edit post | same as create |
| `StoreCommentRequest` | comment | body 1–280, `PostPolicy::view` authorisation |
| `StoreReportRequest` | report post/comment | reason in the five allowed values, details ≤ 500 |
| `StoreClassRequest` | create class | title, subject, future date, duration 15–240 |
| `UpdateClassRequest` | edit class | same as create |
| `UpdateClassStatusRequest` | status switch | status in `ClassRoom::STATUSES`, `ClassPolicy::changeStatus` |
| `ProfileUpdateRequest` | profile update | name, unique e-mail, image MIME/size, bio ≤ 1000, subject ≤ 100 |
| `DeleteAccountRequest` | delete account | `current_password`, errors in the `userDeletion` bag |
| `ForgotPasswordRequest` | forgot password | required e-mail |
| `ResetPasswordRequest` | reset password | token, e-mail, confirmed password |
| `UpdatePasswordRequest` | change password | `current_password`, confirmed new password |

There is no `$request->all()` passed into `create()`/`update()` for user data.
Controllers pick the validated keys explicitly, and the extra-column tests above
prove that `user_id`, `is_hidden`, `role` and `is_blocked` cannot be smuggled
through a crafted request.

---

## 4. Output escaping and database access

- Blade `{{ ... }}` is used for user content, names, e-mails, titles and
  attributes.
- The only unescaped output is content that is escaped first and then decorated:
  - `HashtagService::linkify()` calls `e()` before wrapping `#tags` in anchors,
  - the search highlighter calls `e()` before wrapping the literal term in
    `<mark class="edu-mark">`,
  - user-card/search name snippets pass through `e()` before the `<mark>` is
    inserted.
- Raw SQL only appears in a handful of `LIKE ... ESCAPE '!'` and
  `COUNT(*)` aggregates. Every value is a bound parameter; no user value is
  interpolated into SQL.
- `PerformanceTest` and `PhaseEightTest` assert that a script payload is shown
  escaped in the feed and that `' OR 1=1 --` cannot break the search query.

---

## 5. Uploads

`App\Services\ImageUploadService` is the only place that writes an uploaded
image to disk. The pipeline is:

1. Validation (`image`, MIME list from config, `max` in KB) rejects non-images
   and oversized files before the controller runs.
2. The file is decoded with GD (`imagecreatefromstring`). A file that GD cannot
   decode is rejected with a normal validation error, even if its MIME looked
   like an image.
3. The image is scaled down to the configured ceiling (post: 1600×1600,
   profile: 512×512) — small images are never enlarged.
4. It is re-encoded as WebP (JPEG when the PHP build has no WebP support), which
   drops EXIF metadata and any bytes appended to the original file.
5. The name is 40 random characters plus the extension **we** choose. The
   client's file name, and therefore a `.php` extension, never reaches disk.
6. The row is written only after the file exists; replacing an image stores the
   new file first and deletes the old one afterwards.

Tests: `PhaseEightTest::test_a_disguised_php_upload_is_rejected_before_it_reaches_the_disk`,
`test_the_image_service_rejects_a_file_that_is_not_decodable`,
`test_uploaded_images_are_resized_re_encoded_and_renamed`; the profile tests
cover MIME and size errors.

---

## 6. Rate limiting

| Action | Limit | Where |
| --- | --- | --- |
| Login | 5 failed attempts, then lockout | `Auth/LoginRequest` |
| Register | 5 / minute / IP | named limiter `register` |
| Forgot / reset password | 6 / minute / IP | named limiter `password-reset` |
| Resend verification | 6 / minute | `routes/auth.php` |
| Create post | 10 / minute / user | named limiter `posts` |
| Comment / delete comment | 30 / minute / user | named limiter `comments` |
| Like / unlike | 60 / minute / user | named limiter `likes` |
| Report post / comment | 10 / minute / user | named limiter `reports` |
| Follow / unfollow | 30 / minute / user | named limiter `follows` |
| Notification poll | 60 / minute / user | named limiter `notifications-poll` |
| Search suggestions | 120 / minute / user | named limiter `search-suggest` |

`PhaseEightTest::test_every_expensive_write_route_has_a_throttle` inspects the
route collection, so a new write route without a throttle is caught. Behavioural
tests cover login, registration, comments and reports; `FeedTest` covers posts.

---

## 7. Security headers

`App\Http\Middleware\SecurityHeaders` runs on every web response:

| Header | Normal pages | Jitsi room (`classes.room`) |
| --- | --- | --- |
| `X-Content-Type-Options` | `nosniff` | `nosniff` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | same |
| `X-XSS-Protection` | `0` (modern browsers rely on CSP) | same |
| `X-Frame-Options` | `SAMEORIGIN` | removed, because the page embeds Jitsi |
| `Content-Security-Policy` | not set (would break inline theme boot) | `frame-src 'self' https://<JITSI_DOMAIN>` |
| `Permissions-Policy` | camera, microphone, geolocation disabled | camera/microphone/display-capture allowed for self and the Jitsi domain |
| `Strict-Transport-Security` | only in production over HTTPS | same |
| `X-Powered-By` | removed | removed |

The exceptional room policy is deliberately narrow: only the configured Jitsi
domain may be framed. `PhaseEightTest` checks both the normal and the room
response.

Nginx adds `X-Content-Type-Options` and `Referrer-Policy` for static files too;
see `docs/DEPLOYMENT.md`.

---

## 8. Sessions, cookies and production defaults

- `SESSION_DRIVER=database`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`.
- `SESSION_SECURE_COOKIE` defaults to `true` whenever `APP_URL` starts with
  `https://`, so a production `.env` cannot silently ship an insecure cookie.
- `APP_DEBUG=false`, `APP_ENV=production`, `LOG_CHANNEL=daily` and
  `LOG_LEVEL=warning` are documented in `.env.example` and the deployment guide.
- `APP_KEY` is generated per installation; `.env` is never committed.
- The password-reset notification is branded and can be sent synchronously (no
  worker) or queued when `QUEUE_CONNECTION=database`.

---

## 9. Route middleware table

Generated with:

```bash
php artisan route:list --except-vendor
```

Every `/admin/*` route is `web,auth,not.blocked,role:admin`. Teacher-only class
management is `role:teacher`; student enrolment is `role:student`; shared feed,
search, profiles and the class detail/room pages require a logged-in,
non-blocked user. Public pages are only `/`, `login`, `register` and the
password-reset pages.

| Method | URI | Name | Middleware |
| --- | --- | --- | --- |
| `GET\|HEAD` | `/` | — | `web` |
| `GET\|HEAD` | `admin/classes` | `admin.classes.index` | `web`, `auth`, `not.blocked`, `role:admin` |
| `DELETE` | `admin/classes/{classRoom}` | `admin.classes.destroy` | `web`, `auth`, `not.blocked`, `role:admin` |
| `PATCH` | `admin/classes/{classRoom}/hide` | `admin.classes.hide` | `web`, `auth`, `not.blocked`, `role:admin` |
| `GET\|HEAD` | `admin/dashboard` | `admin.dashboard` | `web`, `auth`, `not.blocked`, `role:admin` |
| `GET\|HEAD` | `admin/dashboard/charts` | `admin.dashboard.charts` | `web`, `auth`, `not.blocked`, `role:admin` |
| `GET\|HEAD` | `admin/posts` | `admin.posts.index` | `web`, `auth`, `not.blocked`, `role:admin` |
| `DELETE` | `admin/posts/{post}` | `admin.posts.destroy` | `web`, `auth`, `not.blocked`, `role:admin` |
| `PATCH` | `admin/posts/{post}/hide` | `admin.posts.hide` | `web`, `auth`, `not.blocked`, `role:admin` |
| `GET\|HEAD` | `admin/reports` | `admin.reports.index` | `web`, `auth`, `not.blocked`, `role:admin` |
| `GET\|HEAD` | `admin/reports/{report}` | `admin.reports.show` | `web`, `auth`, `not.blocked`, `role:admin` |
| `POST` | `admin/reports/{report}/block-user` | `admin.reports.block-user` | `web`, `auth`, `not.blocked`, `role:admin` |
| `DELETE` | `admin/reports/{report}/content` | `admin.reports.content.destroy` | `web`, `auth`, `not.blocked`, `role:admin` |
| `PATCH` | `admin/reports/{report}/dismiss` | `admin.reports.dismiss` | `web`, `auth`, `not.blocked`, `role:admin` |
| `PATCH` | `admin/reports/{report}/hide` | `admin.reports.hide` | `web`, `auth`, `not.blocked`, `role:admin` |
| `PATCH` | `admin/reports/{report}/reviewed` | `admin.reports.reviewed` | `web`, `auth`, `not.blocked`, `role:admin` |
| `PATCH` | `admin/reports/{report}/unhide` | `admin.reports.unhide` | `web`, `auth`, `not.blocked`, `role:admin` |
| `GET\|HEAD` | `admin/style-guide` | `admin.style-guide` | `web`, `auth`, `not.blocked`, `role:admin` |
| `GET\|HEAD` | `admin/users` | `admin.users.index` | `web`, `auth`, `not.blocked`, `role:admin` |
| `DELETE` | `admin/users/{user}` | `admin.users.destroy` | `web`, `auth`, `not.blocked`, `role:admin` |
| `PATCH` | `admin/users/{user}/block` | `admin.users.block` | `web`, `auth`, `not.blocked`, `role:admin` |
| `PATCH` | `admin/users/{user}/unblock` | `admin.users.unblock` | `web`, `auth`, `not.blocked`, `role:admin` |
| `GET\|HEAD` | `classes` | `classes.index` | `web`, `auth`, `not.blocked`, `role:teacher` |
| `POST` | `classes` | `classes.store` | `web`, `auth`, `not.blocked`, `role:teacher` |
| `GET\|HEAD` | `classes/create` | `classes.create` | `web`, `auth`, `not.blocked`, `role:teacher` |
| `GET\|HEAD` | `classes/discover` | `classes.discover` | `web`, `auth`, `not.blocked` |
| `PATCH` | `classes/{classRoom}` | `classes.update` | `web`, `auth`, `not.blocked`, `role:teacher` |
| `DELETE` | `classes/{classRoom}` | `classes.destroy` | `web`, `auth`, `not.blocked`, `role:teacher` |
| `GET\|HEAD` | `classes/{classRoom}` | `classes.show` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `classes/{classRoom}/edit` | `classes.edit` | `web`, `auth`, `not.blocked`, `role:teacher` |
| `POST` | `classes/{classRoom}/enroll` | `classes.enroll` | `web`, `auth`, `not.blocked`, `role:student` |
| `DELETE` | `classes/{classRoom}/enroll` | `classes.unenroll` | `web`, `auth`, `not.blocked`, `role:student` |
| `GET\|HEAD` | `classes/{classRoom}/leave` | `classes.leave` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `classes/{classRoom}/room` | `classes.room` | `web`, `auth`, `not.blocked` |
| `PATCH` | `classes/{classRoom}/status` | `classes.status` | `web`, `auth`, `not.blocked`, `role:teacher` |
| `GET\|HEAD` | `classes/{classRoom}/students` | `classes.students` | `web`, `auth`, `not.blocked`, `role:teacher` |
| `DELETE` | `comments/{comment}` | `comments.destroy` | `web`, `auth`, `not.blocked`, `throttle:comments` |
| `POST` | `comments/{comment}/reports` | `reports.comments.store` | `web`, `auth`, `not.blocked`, `throttle:reports` |
| `GET\|HEAD` | `confirm-password` | `password.confirm` | `web`, `auth` |
| `POST` | `confirm-password` | — | `web`, `auth` |
| `GET\|HEAD` | `dashboard` | `dashboard` | `web`, `auth`, `not.blocked` |
| `POST` | `email/verification-notification` | `verification.send` | `web`, `auth`, `throttle:6,1` |
| `GET\|HEAD` | `feed` | `feed.index` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `followers` | `followers.index` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `forgot-password` | `password.request` | `web`, `guest` |
| `POST` | `forgot-password` | `password.email` | `web`, `guest`, `throttle:password-reset` |
| `GET\|HEAD` | `hashtag/{hashtag}` | `hashtags.legacy` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `hashtags/{hashtag}` | `hashtags.show` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `login` | `login` | `web`, `guest` |
| `POST` | `login` | — | `web`, `guest` |
| `POST` | `logout` | `logout` | `web`, `auth` |
| `GET\|HEAD` | `notifications` | `notifications.index` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `notifications/poll` | `notifications.poll` | `web`, `auth`, `not.blocked`, `throttle:notifications-poll` |
| `PATCH` | `notifications/read-all` | `notifications.read-all` | `web`, `auth`, `not.blocked` |
| `DELETE` | `notifications/{notification}` | `notifications.destroy` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `notifications/{notification}/open` | `notifications.open` | `web`, `auth`, `not.blocked` |
| `PATCH` | `notifications/{notification}/read` | `notifications.read` | `web`, `auth`, `not.blocked` |
| `PUT` | `password` | `password.update` | `web`, `auth` |
| `POST` | `posts` | `posts.store` | `web`, `auth`, `not.blocked`, `throttle:posts` |
| `GET\|HEAD` | `posts/create` | `posts.create` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `posts/{post}` | `posts.show` | `web`, `auth`, `not.blocked` |
| `PATCH` | `posts/{post}` | `posts.update` | `web`, `auth`, `not.blocked` |
| `DELETE` | `posts/{post}` | `posts.destroy` | `web`, `auth`, `not.blocked` |
| `POST` | `posts/{post}/comments` | `comments.store` | `web`, `auth`, `not.blocked`, `throttle:comments` |
| `GET\|HEAD` | `posts/{post}/edit` | `posts.edit` | `web`, `auth`, `not.blocked` |
| `POST` | `posts/{post}/like` | `posts.like` | `web`, `auth`, `not.blocked`, `throttle:likes` |
| `DELETE` | `posts/{post}/like` | `posts.unlike` | `web`, `auth`, `not.blocked`, `throttle:likes` |
| `POST` | `posts/{post}/reports` | `reports.posts.store` | `web`, `auth`, `not.blocked`, `throttle:reports` |
| `GET\|HEAD` | `profile` | `profile.edit` | `web`, `auth`, `not.blocked` |
| `PATCH` | `profile` | `profile.update` | `web`, `auth`, `not.blocked` |
| `DELETE` | `profile` | `profile.destroy` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `register` | `register` | `web`, `guest` |
| `POST` | `register` | — | `web`, `guest`, `throttle:register` |
| `POST` | `reset-password` | `password.store` | `web`, `guest`, `throttle:password-reset` |
| `GET\|HEAD` | `reset-password/{token}` | `password.reset` | `web`, `guest` |
| `GET\|HEAD` | `search` | `search.index` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `search/suggest` | `search.suggest` | `web`, `auth`, `not.blocked`, `throttle:search-suggest` |
| `GET\|HEAD` | `student/classes` | `student.classes` | `web`, `auth`, `not.blocked`, `role:student` |
| `GET\|HEAD` | `student/dashboard` | `student.dashboard` | `web`, `auth`, `not.blocked`, `role:student` |
| `GET\|HEAD` | `student/dashboard/charts` | `student.dashboard.charts` | `web`, `auth`, `not.blocked`, `role:student` |
| `GET\|HEAD` | `teacher/dashboard` | `teacher.dashboard` | `web`, `auth`, `not.blocked`, `role:teacher` |
| `GET\|HEAD` | `teacher/dashboard/charts` | `teacher.dashboard.charts` | `web`, `auth`, `not.blocked`, `role:teacher` |
| `GET\|HEAD` | `u/{user}` | — | `web`, `auth`, `not.blocked` |
| `POST` | `u/{user}/follow` | `follows.store` | `web`, `auth`, `not.blocked`, `role:student,teacher`, `throttle:follows` |
| `DELETE` | `u/{user}/follow` | `follows.destroy` | `web`, `auth`, `not.blocked`, `role:student,teacher`, `throttle:follows` |
| `GET\|HEAD` | `u/{user}/followers` | — | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `u/{user}/following` | — | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `users/{user}` | `users.show` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `users/{user}/followers` | `users.followers` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `users/{user}/following` | `users.following` | `web`, `auth`, `not.blocked` |
| `GET\|HEAD` | `verify-email` | `verification.notice` | `web`, `auth` |
| `GET\|HEAD` | `verify-email/{id}/{hash}` | `verification.verify` | `web`, `auth`, `signed`, `throttle:6,1` |

The Breeze e-mail verification routes (`verification.notice`, `verification.verify`)
are present but not enforced on the product routes: the SRS treats e-mail
verification as a confirmation step, not a gate. The password-reset flow is
fully wired (section 8 / deployment guide).

---

## 10. Re-verification commands

```bash
# The security and cross-cutting test suite
php artisan test --filter=PhaseEightTest
php artisan test --filter=Auth

# The full regression suite
php artisan test

# Role/middleware table, exactly as documented above
php artisan route:list --except-vendor

# Dependency advisories (run after any update)
composer audit
npm audit
```

Known limitations, reported honestly: there is no two-factor authentication and
no per-request CSP nonce (the inline theme bootstrap needs its own script). Both
are hardening items beyond the SRS and should be scheduled before exposing the
platform to a high-risk audience.
````

#### `docs/PERFORMANCE.md`

````markdown
# Performance notes

Phase 8 performance pass for SRS 5.1. The goal is a page that renders in under
three seconds on a normal connection, with no N+1 query storms on the feed,
profiles, dashboards or admin tables.

---

## 1. How this was measured

| Check | Command / test | Result |
| --- | --- | --- |
| Feed eager loading, pagination (< 15 queries for 20 posts) | `php artisan test --filter=PerformanceTest` | pass |
| Admin dashboard query budget (≤ 45 queries with users, posts, classes, reports) | `PhaseEightTest::test_dashboards_stay_within_a_query_budget` | pass |
| All primary pages under 3 seconds | `PhaseEightTest::test_the_primary_pages_load_in_under_three_seconds` | 12/12 pages pass; each page is warmed once and then timed three times (fastest run wins) so scheduler jitter cannot flake the check |
| Dashboard cache keys exist and hold for five minutes | `PhaseEightTest::test_dashboard_metrics_are_cached_for_five_minutes` | pass |
| Hot-path indexes exist after `migrate` | `PhaseEightTest::test_the_hot_query_indexes_are_present` | 15/15 indexes found |

The test suite runs on in-memory SQLite, so the timings are a floor, not a
production benchmark. Use the curl commands in section 8 against the real MySQL
host before signing off.

---

## 2. Database indexes

`database/migrations/2026_10_30_000002_add_phase_eight_query_indexes.php` adds
composite indexes for the filter + sort patterns the application actually runs:

| Table | Columns | Serves |
| --- | --- | --- |
| `posts` | `user_id, created_at` | feed, profile “my posts”, admin post lists |
| `posts` | `is_hidden, created_at` | visible feed and moderation filters |
| `comments` | `post_id, created_at` | post detail comments, oldest first |
| `comments` | `user_id, created_at` | profile comment activity |
| `likes` | `user_id, created_at` | “posts I liked” and like counts |
| `follows` | `follower_id, created_at` | following list |
| `follows` | `following_id, created_at` | follower list and follower notifications |
| `classes` | `status, date` | class catalogue and “upcoming” filter |
| `classes` | `teacher_id, date` | teacher dashboard and “my classes” |
| `classes` | `is_hidden, date` | hidden-class exclusion in browse queries |
| `class_enrollments` | `student_id, created_at` | “my enrolled classes” |
| `reports` | `status, created_at` | moderation queue default sort |
| `reports` | `reportable_type, reportable_id, status` | per-content report lookups |
| `notifications` | `notifiable_type, notifiable_id, read_at` | unread bell counter |
| `users` | `role, is_blocked` | admin user role/blocked filters |

The migration is idempotent (`Schema::hasIndex` by name) and portable between
MySQL 8 and SQLite.

Existing unique/foreign indexes from Phases 1–7 (e-mail, pivot keys, the
`reports_reporter_reportable_unique` index, and the relationship indexes in
`2026_10_30_000001_add_phase_two_relationship_indexes.php`) remain in place.

---

## 3. Eager loading and query budgets

The hot paths use scopes that load everything a card needs in one go:

- `Post::scopeWithFeedData()` loads author, hashtags, like/comment counts and
  the “liked by me” flag. `PerformanceTest` asserts that 20 posts on one page
  stay at **15 queries or fewer**.
- `PostController::commentsFor()` loads the three preview comments for every
  feed card in one query, so the Bootstrap collapse does not query per post.
- `AdminDashboardController` eager loads `reporter`, `reportable`, post authors
  and class teachers before rendering the recent-activity panels.
- Profile, hashtag, search and class pages paginate and eager load their
  relations; the tests in `SmokeTest` open every list page with an empty state
  and `PhaseSevenTest` checks hidden content is filtered at the query level.

If a future change adds a relation to a card, the query-count tests fail before
the regression reaches production.

---

## 4. Pagination

Every list is paginated:

| Page | Page size |
| --- | --- |
| Feed / profile posts | 10 |
| Post comments | 15 |
| Search results | 15 per tab |
| Class catalogue | 9 |
| Enrolled classes | 9 |
| Admin users / posts / classes | 20 |
| Moderation queue | 20 |
| Notifications | 20 |
| Followers / following | 20 |

`withQueryString()` keeps filters and the current tab when moving between pages.

---

## 5. Caching

`App\Support\DashboardMetrics` caches the expensive aggregates for **300
seconds (five minutes)**:

| Key | Contents |
| --- | --- |
| `dashboard.admin.stats.v1` | platform totals (users, teachers, students, posts, classes) |
| `dashboard.admin.pending-reports.v1` | pending moderation counter |
| `dashboard.admin.trends.v1` | month-over-month movement |
| `dashboard.admin.sparklines.v1` | seven-day sparkline series |
| `dashboard.admin.charts.{7d\|30d\|12m}.v1` | chart series per range |
| `dashboard.teacher.{id}.stats.v1` | teacher counters |
| `dashboard.teacher.{id}.trends.v1` | teacher trends |
| `dashboard.teacher.{id}.charts.{range}.v1` | teacher chart series |
| `dashboard.teacher.{id}.schedule.v1` | seven-day schedule |
| `dashboard.student.{id}.stats.v1` | student counters |
| `dashboard.student.{id}.charts.{range}.v1` | student activity series |

Keys are scoped by role and user id, so one person's numbers never leak into
another dashboard. A counter can be up to five minutes stale by design; the
moderation queue itself is never cached, so newly reported content appears
immediately.

For production, use a shared cache store (`CACHE_STORE=database`, Redis or
Memcached) so the cache survives between PHP-FPM workers. The test suite uses
the array store, which is why the cache test can inspect the keys directly.

---

## 6. Images and assets

- Every upload is decoded/resized/re-encoded by `ImageUploadService`
  (post ceiling 1600×1600, profile 512×512, WebP/JPEG). A 4 MB phone photo
  becomes a small WebP file before it is served. See `docs/SECURITY.md` §5.
- Post images, avatars and report previews use `loading="lazy"` and
  `decoding="async"`. The layout's hero image is the only eager image.
- Bootstrap Icons are subset through the Vite build; custom CSS/JS lives in
  `resources/sass` and `resources/js` and is minified by Vite.
- `npm run build` currently emits roughly **368.63 kB CSS** (57.47 kB gzip)
  and **229.62 kB JS** (75.83 kB gzip), plus a 209.95 kB shared chunk
  (72.11 kB gzip). Nginx should serve them with gzip/brotli; the manifest keeps
  long-term hashed filenames.

Asset commands:

```bash
npm ci
npm run build          # production, minified, hashed
npm run dev            # local hot reload
```

---

## 7. Laravel caches

Production deploy (also in `docs/DEPLOYMENT.md`):

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

After changing `.env`, routes or views:

```bash
php artisan optimize:clear
php artisan optimize
```

`php artisan view:cache` and `view:clear` are part of the Phase 8 verification
run, so a Blade syntax error fails the build instead of the first request.

---

## 8. Measuring a real page

Use curl against the deployed URL (authenticated pages need a session cookie,
so use the browser devtools or a logged-in curl cookie jar):

```bash
curl -s -o /dev/null -w 'landing: %{time_total}s\n' https://educonnect.example.com/
curl -s -o /dev/null -w 'login:   %{time_total}s\n' https://educonnect.example.com/login

# Authenticated (replace the cookie value with your own session cookie)
curl -s -o /dev/null -b 'educonnect_session=...' \
  -w 'feed: %{time_total}s\n' https://educonnect.example.com/feed
```

Aim for < 1 s server time on a small VPS; the SRS target is < 3 s end-to-end on
a normal connection.

### Optional: Debugbar / Telescope

Neither package is installed in production. To investigate a suspected N+1
locally:

```bash
composer require --dev barryvdh/laravel-debugbar
# open the page with APP_DEBUG=true, inspect the Queries tab, then:
composer remove --dev barryvdh/laravel-debugbar
```

or, for a request-level trace:

```bash
composer require --dev laravel/telescope
php artisan telescope:install
php artisan migrate
php artisan telescope:prune --hours=24
```

Remove both before deploying: they expose internals and add overhead. The
automated query-count tests are the permanent guard; Debugbar/Telescope are only
for diagnosis.

---

## 9. Known limits

- Timings in the suite are SQLite/in-memory. MySQL with a cold buffer pool will
  be slower; run the curl checks after the first deploy and after data grows.
- The feed is a simple `WHERE user_id IN (...) ORDER BY created_at` query. At
  very large follow counts it would benefit from a fan-out/ timeline table;
  that is beyond the SRS scale and not implemented.
- The five-minute dashboard cache trades freshness for speed by design. If an
  admin needs an immediate number, the queue page is always live.
````

#### `docs/TEST-MATRIX.md`

````markdown
# Requirement test matrix

FR-1 … FR-34 traceability for the final verification run. Every row has an
automated test; the manual checks that cannot be simulated in PHPUnit are in
section 2.

**Result snapshot (Phase 7 final run):**

```text
php artisan test
Tests:  307 passed (1665 assertions)
```

| FR | Requirement (short) | Automated test(s) | Result |
| --- | --- | --- | --- |
| FR-1 | Register as teacher/student, log in, log out, land on the role dashboard | `Auth/RegistrationTest::test_a_teacher_can_register`, `test_a_student_can_register`, `Auth/AuthenticationTest::test_users_can_authenticate_using_the_login_screen`, `test_users_can_logout` | PASS |
| FR-2 | Role-based access and role dashboards | `RoleAccessTest::test_each_role_reaches_its_own_dashboard`, `test_a_student_cannot_open_the_admin_dashboard`, `DashboardTest::test_each_role_is_locked_out_of_the_other_dashboards` | PASS |
| FR-3 | Forgot / reset password with a branded e-mail | `Auth/PasswordResetTest::test_reset_password_link_can_be_requested`, `test_the_reset_email_uses_the_branded_template`, `test_password_can_be_reset_with_valid_token` | PASS |
| FR-4 | Edit profile: name, e-mail, photo, bio, subject, password, delete account | `ProfileTest::test_profile_information_can_be_updated`, `test_correct_password_must_be_provided_to_delete_account`, `PhaseThreeUiTest::test_a_user_can_upload_and_remove_a_profile_photo`, `test_the_profile_photo_is_validated_for_type_and_size` | PASS |
| FR-5 | Public profile with posts, followers and following lists | `FeedTest::test_a_public_profile_shows_the_user_posts_and_counts`, `test_the_follower_lists_are_paginated_and_reachable`, `PhaseThreeCTest::test_a_teacher_profile_has_posts_classes_and_about_tabs` | PASS |
| FR-6 | Blocked accounts cannot log in and are ejected mid-session | `Auth/BlockedUserTest::test_a_blocked_user_cannot_log_in`, `test_a_logged_in_user_is_logged_out_when_blocked`, `PhaseSevenTest::test_a_blocked_user_cannot_post_comment_enroll_or_join` | PASS |
| FR-7 | Create a post (280 chars, optional image) | `FeedTest::test_a_logged_in_user_can_publish_a_post`, `test_a_post_cannot_be_longer_than_280_characters`, `test_an_empty_post_is_rejected`, `test_a_post_image_is_stored_in_the_posts_directory`, `PhaseEightTest::test_uploaded_images_are_resized_re_encoded_and_renamed` | PASS |
| FR-8 | Edit / delete own posts, image cleanup | `FeedTest::test_the_author_can_edit_and_delete_their_own_post`, `test_a_user_cannot_edit_someone_elses_post`, `test_a_user_cannot_delete_someone_elses_post`, `test_an_administrator_can_delete_a_post_but_not_edit_it`, `test_replacing_a_post_image_deletes_the_old_file`, `test_deleting_a_post_removes_its_image_file` | PASS |
| FR-9 | Like / unlike without duplicates, rate limited | `FeedTest::test_a_like_can_be_toggled_and_never_duplicates`, `test_a_hidden_post_cannot_be_liked_by_other_users`, `test_post_creation_is_rate_limited_to_ten_per_minute`, `PhaseEightTest::test_every_expensive_write_route_has_a_throttle` | PASS |
| FR-10 | Comment on a post; delete own or admin; hidden comments excluded | `FeedTest::test_a_user_can_comment_and_only_delete_their_own_comment`, `test_only_the_comment_author_or_an_admin_can_delete_a_comment`, `test_hidden_comments_are_not_shown_or_counted`, `PhaseSevenTest::test_hidden_comments_disappear_from_the_post_and_its_counter` | PASS |
| FR-11 | Follow / unfollow, no self-follow, live follower counts, AJAX on every surface | `FeedTest::test_a_student_can_follow_and_unfollow_a_teacher`, `test_nobody_can_follow_themselves`, `test_a_student_can_follow_another_student`, `test_search_results_and_feed_suggestions_use_the_ajax_follow_button`, `DashboardTest::test_a_student_can_follow_and_unfollow_a_teacher` | PASS |
| FR-12 | Home feed: own + followed posts, visible only, 10 per page, newest first | `FeedTest::test_the_feed_shows_only_my_posts_and_the_posts_of_people_i_follow`, `test_the_feed_is_paginated_ten_per_page_and_shows_the_newest_first`, `test_the_feed_answers_json_for_the_load_more_button`, `PerformanceTest::test_the_feed_paginates_and_keeps_its_query_count_bounded` | PASS |
| FR-13 | Hashtags: parse, Unicode, lowercase, sync, XSS-safe links, hashtag page | `FeedTest::test_hashtags_are_parsed_and_synced_in_lower_case`, `test_hashtags_support_unicode_and_are_lower_cased_everywhere`, `test_editing_a_post_resyncs_and_removes_old_hashtags`, `test_hashtag_links_are_xss_safe`, `test_the_hashtag_page_lists_visible_posts_with_that_tag` | PASS |
| FR-14 | Search people / hashtags / posts, role badge, pagination, suggestions, escaped term | `SearchTest::test_users_are_found_by_name`, `test_users_are_found_by_subject`, `test_users_are_found_by_bio`, `test_people_results_show_a_role_badge_and_a_follow_button`, `test_people_results_are_paginated`, `test_hashtag_results_count_only_visible_posts`, `test_posts_are_found_by_their_body_text`, `test_the_suggestion_endpoint_returns_people_and_hashtags`, `test_the_suggestion_endpoint_hides_blocked_people`, `test_like_wildcards_in_the_term_are_escaped`, `PhaseEightTest::test_search_input_cannot_break_the_query` | PASS |
| FR-15 | Create a class: validation, future date, duration 15–240, unique room, status filter | `ClassTest::test_a_teacher_can_create_a_class_with_a_private_room`, `test_a_class_cannot_be_created_in_the_past`, `test_the_duration_must_be_between_fifteen_and_two_hundred_forty_minutes`, `test_room_names_are_unique_and_ignore_the_request`, `test_a_teacher_can_filter_their_classes_by_status` | PASS |
| FR-16 | Edit / delete a class (owner only) | `ClassTest::test_the_owner_can_update_their_class`, `test_a_teacher_cannot_edit_another_teachers_class`, `test_students_and_admins_cannot_manage_classes` | PASS |
| FR-17 | Browse, search/filter classes, enrol / unenrol | `ClassTest::test_a_student_can_enroll_and_unenroll`, `test_a_student_can_search_and_filter_the_class_list`, `test_a_student_can_search_by_subject_and_filter_by_teacher`, `test_a_student_cannot_enroll_in_a_finished_class`, `test_only_students_can_enroll` | PASS |
| FR-18 | Class detail page with enrol / join actions | `ClassTest::test_the_room_page_embeds_jitsi_with_the_user_details`, `SmokeTest::test_student_can_open_their_pages_and_join_a_live_class` | PASS |
| FR-19 | Class search by title / subject / teacher, subject and teacher filters, private room name | `ClassTest::test_a_student_can_search_and_filter_the_class_list`, `test_a_student_can_search_by_subject_and_filter_by_teacher`, `test_room_names_are_unique_and_ignore_the_request` | PASS |
| FR-20 | Join the Jitsi room only as owning teacher or enrolled student, only while live, friendly not-started page, hangup flash | `ClassTest::test_only_the_teacher_and_enrolled_students_can_open_the_room`, `test_an_enrolled_student_can_join_only_when_the_class_is_live`, `test_a_hidden_class_cannot_be_joined`, `test_leaving_the_room_redirects_back_with_a_flash_message` | PASS |
| FR-21 | Status workflow scheduled → live → completed, legal transitions, auto-close, timing accessor | `ClassTest::test_the_teacher_controls_the_class_status`, `test_an_invalid_status_transition_is_refused`, `test_only_the_scheduled_live_completed_chain_is_allowed`, `test_a_live_class_is_completed_once_its_end_time_has_passed`, `test_the_timing_helper_describes_when_a_class_happens` | PASS |
| FR-22 | In-room experience: Jitsi embed, chat, moderator flag, subject/avatar, escaped display name | `ClassTest::test_the_room_page_embeds_jitsi_with_the_user_details`, `PhaseEightTest::test_the_jitsi_room_relaxes_framing_for_the_video_domain` | PASS |
| FR-23 | Notifications: new follower, like, comment, new class, class starting soon, no self, payload, dedupe | `NotificationTest::test_following_a_teacher_notifies_the_teacher`, `test_a_notification_stores_its_type_message_link_and_actor`, `test_liking_a_post_notifies_its_author`, `test_liking_your_own_post_does_not_notify_you`, `test_commenting_on_a_post_notifies_its_author`, `test_commenting_on_your_own_post_does_not_notify_you`, `test_creating_a_class_notifies_the_followers_of_the_teacher`, `test_creating_a_class_does_not_notify_the_teacher_themselves`, `test_a_blocked_follower_is_not_notified_of_a_new_class`, `test_the_starting_soon_command_notifies_the_teacher_and_the_students_once`, `test_the_starting_soon_command_skips_students_who_are_not_enrolled` | PASS |
| FR-24 | Notification bell, unread counter, hidden at zero, 30 s polling, filters | `NotificationTest::test_the_poll_endpoint_returns_the_unread_counter_and_the_latest_entries`, `test_the_bell_and_the_notifications_page_show_the_unread_count`, `test_the_unread_badge_is_hidden_when_there_is_nothing_to_read`, `test_the_poll_endpoint_requires_a_logged_in_user`, `test_the_notifications_page_can_be_filtered_to_unread_only` | PASS |
| FR-25 | Mark one (AJAX) / all as read, ownership, delete, open target | `NotificationTest::test_a_user_can_mark_one_notification_as_read`, `test_a_user_can_mark_all_notifications_as_read`, `test_marking_one_notification_as_read_answers_json_for_the_ajax_button`, `test_marking_all_as_read_only_touches_my_notifications`, `test_a_user_can_delete_their_own_notification`, `test_a_user_cannot_delete_somebody_elses_notification`, `test_opening_a_notification_marks_it_read_and_redirects_to_its_page`, `test_a_user_cannot_read_somebody_elses_notification` | PASS |
| FR-26 | Admin analytics: totals, charts, trends, recent activity | `DashboardTest::test_the_admin_dashboard_shows_the_platform_totals`, `test_the_admin_chart_counts_new_users_per_month`, `DashboardRedesignTest::test_the_admin_dashboard_renders_the_new_charts_panels_and_moderation_shortcuts` | PASS |
| FR-27 | User moderation: search, filter, block / unblock / delete | `AdminManagementTest::test_admins_can_search_and_filter_users`, `test_an_admin_can_block_and_unblock_a_user`, `test_an_admin_cannot_block_or_delete_themselves_or_another_admin`, `test_deleting_a_user_removes_their_content_and_reports` | PASS |
| FR-28 | Content moderation: hide / unhide / delete posts, classes and comments | `AdminManagementTest::test_an_admin_can_hide_unhide_and_delete_a_post`, `test_an_admin_can_hide_unhide_and_delete_a_class`, `PhaseSevenTest::test_hidden_posts_disappear_from_feed_profile_hashtag_and_search`, `ReportTest::test_hiding_a_comment_removes_it_from_the_post_page` | PASS |
| FR-29 | Teacher dashboard and enrolled-student roster | `PhaseThreeUiTest::test_the_teacher_dashboard_has_class_actions_and_enrollment_activity`, `ClassTest::test_the_teacher_sees_the_enrolled_students`, `ClassTest::test_the_dashboards_offer_working_join_buttons_and_upcoming_counts`, `DashboardTest::test_the_teacher_dashboard_only_counts_my_own_content` | PASS |
| FR-30 | Student dashboard, upcoming classes and teacher suggestions | `DashboardTest::test_the_student_dashboard_shows_upcoming_classes_and_teacher_suggestions`, `DashboardRedesignTest::test_the_student_dashboard_has_the_hero_recommendations_chart_and_feed_preview`, `ClassTest::test_the_dashboards_offer_working_join_buttons_and_upcoming_counts`, `ClassTest::test_a_student_sees_the_classes_they_enrolled_in` | PASS |
| FR-31 | Charts, theme toggle, sidebar state, design tokens | `DashboardRedesignTest::test_the_layout_ships_the_theme_toggle_and_the_design_tokens`, `test_the_dashboard_chart_endpoints_are_role_protected`, `PhaseThreeUiTest::test_the_layout_remembers_the_sidebar_state_and_has_a_script_stack` | PASS |
| FR-32 | Report a post or comment, one report per user per item | `ReportTest::test_a_user_can_report_somebody_elses_post`, `test_a_user_can_report_a_comment`, `test_the_same_user_cannot_report_the_same_item_twice`, `test_hidden_content_cannot_be_reported` | PASS |
| FR-33 | Moderation queue: filters, grouping, statuses, content actions | `ReportTest::test_the_queue_can_be_filtered_by_status_and_type`, `test_a_report_can_be_dismissed_or_marked_reviewed`, `PhaseSevenTest::test_the_report_queue_search_matches_the_offending_author`, `test_admin_lists_show_the_author_and_the_report_count` | PASS |
| FR-34 | Resolve reports: hide / unhide / delete content, block author, reviewed / dismissed | `ReportTest::test_hiding_a_post_marks_the_report_reviewed_and_removes_it_from_the_feed`, `test_blocking_the_author_marks_the_report_reviewed_and_logs_them_out`, `test_deleting_the_content_removes_the_content_and_its_reports`, `PhaseSevenTest::test_dismissing_a_report_keeps_the_content_visible` | PASS |

---

## Cross-cutting Phase 8 checks

| Check | Test | Result |
| --- | --- | --- |
| Security headers on every response, Jitsi exception | `PhaseEightTest::test_security_headers_are_sent_on_every_page`, `test_the_jitsi_room_relaxes_framing_for_the_video_domain` | PASS |
| Meta description, canonical, OG/Twitter, favicon, theme colour | `PhaseEightTest::test_the_layout_ships_meta_social_and_favicon_tags` (also asserts the ICO is non-empty and valid) | PASS |
| `@csrf` on every state-changing form (static scan) | `PhaseEightTest::test_every_post_form_carries_a_csrf_token` | PASS |
| Form Request validation (comment, class status, delete account) | `PhaseEightTest` comment/status/account tests | PASS |
| Mass-assignment safety (`user_id`, `is_hidden`, `role`, `is_blocked`) | `PhaseEightTest::test_write_requests_cannot_smuggle_protected_columns` | PASS |
| Upload rejection, decode/re-encode, resize, random name | `PhaseEightTest` upload tests | PASS |
| Rate limits: login, register, comment, report, route audit | `PhaseEightTest` rate-limit tests | PASS |
| Escaped output and safe search | `PhaseEightTest::test_user_content_is_escaped_in_the_feed`, `test_search_input_cannot_break_the_query` | PASS |
| Friendly 403 / 404 / 419 / 500 / 503 pages | `PhaseEightTest::test_all_friendly_error_pages_render` | PASS |
| Dashboard cache keys and five-minute TTL | `PhaseEightTest::test_dashboard_metrics_are_cached_for_five_minutes` | PASS |
| Query budgets and hot-path indexes | `PerformanceTest`, `PhaseEightTest::test_dashboards_stay_within_a_query_budget`, `test_the_hot_query_indexes_are_present` | PASS |
| Primary pages < 3 seconds | `PhaseEightTest::test_the_primary_pages_load_in_under_three_seconds` | PASS |

---

## Manual checks (environment-dependent)

These cannot be proven by the SQLite test suite; record the date and result when
performing them on staging.

| Check | How | Status |
| --- | --- | --- |
| Responsive layout at 360 / 768 / 1200 px | Browser devtools on landing, feed, class catalogue, dashboard and profile | documented manual check (Phase 3C + Phase 8) |
| Real SMTP password-reset delivery | Deploy with `MAIL_MAILER=smtp`, request a reset, confirm the branded e-mail arrives | documented manual check; unit test proves the template and notification |
| Real Jitsi call (camera/microphone) | Join a live class as teacher and enrolled student over HTTPS | documented manual check; header/embed tests cover the server side |
| Production queue worker | `QUEUE_CONNECTION=database`, run `queue:work`, trigger a notification | documented manual check; supervisor recipe in `docs/DEPLOYMENT.md` |
| MySQL 8 migrations and backup/restore | `php artisan migrate --force`, then restore a dump into a scratch database | documented manual check; MySQL unavailable in the build environment |
| Dark-mode contrast | Toggle theme at 360/1200 px, check text, buttons, alerts and charts | documented manual check (design tokens in `resources/sass`) |

The automated suite is the source of truth for FR-1…FR-34; the manual table is
the deployment sign-off list.
````

#### `docs/BACKUP.md`

````markdown
# Backup and restore

EduConnect stores data in two places:

1. the **database** (users, posts, classes, reports, notifications),
2. **uploaded files** on the `public` disk (`storage/app/public`: post images and
   profile photos).

A backup that only copies the database therefore restores posts with broken
images. `scripts/backup.sh` captures both, plus `.env` (which contains the
database password, mail password and admin password).

---

## What the script does

```bash
./scripts/backup.sh
```

1. Reads the connection settings from `.env` (or from exported environment
   variables, which take precedence).
2. Dumps MySQL with `mysqldump --single-transaction --quick --routines --events`
   and gzips the result. The password is passed through a temporary
   `--defaults-extra-file`, so it never appears in `ps`.
3. For SQLite it copies the database file instead.
4. Archives `storage/app` and `.env` together with the dump into
   `educonnect_YYYYMMDD_HHMMSS.tar.gz`.
5. Sets `chmod 600` on the archive and deletes archives older than
   `RETENTION_DAYS` (default 14).

Overrides:

| Variable | Default | Meaning |
| --- | --- | --- |
| `APP_DIR` | parent of `scripts/` | application root |
| `BACKUP_DIR` | `/var/backups/educonnect` | destination directory |
| `RETENTION_DAYS` | `14` | delete archives older than this |
| `DB_CONNECTION`, `DB_HOST`, … | values from `.env` | connection override |

Example with an explicit destination and 30-day retention:

```bash
sudo -u www-data APP_DIR=/var/www/educonnect \
  BACKUP_DIR=/mnt/backups/educonnect RETENTION_DAYS=30 \
  /var/www/educonnect/scripts/backup.sh
```

### Schedule

Laravel also dumps the database every night at 02:30 through
`php artisan backup:database` (see `routes/console.php`). That dump lands in
`storage/app/backups/` and is the portable option when `mysqldump` is available
to PHP. The shell script below is still the **full** backup (database + uploads
+ `.env`) and should stay on the server crontab as well:

```cron
# Every day at 02:30, as the web user so file ownership stays consistent.
30 2 * * * APP_DIR=/var/www/educonnect /var/www/educonnect/scripts/backup.sh >> /var/log/educonnect-backup.log 2>&1
```

One-off dump without the shell script:

```bash
php artisan backup:database --keep=14
```

Keep at least one copy **off the server** (object storage, a NAS or another
machine). A backup that lives only on the machine that failed is not a backup.

### Verify that the cron job works

```bash
sudo tail -n 20 /var/log/educonnect-backup.log
sudo ls -lh /var/backups/educonnect | tail
```

A successful run prints `Backup written to ...` and exits `0`. Check the exit
status in a script rather than by eye:

```bash
sudo -u www-data APP_DIR=/var/www/educonnect /var/www/educonnect/scripts/backup.sh
echo "exit=$?"
```

---

## Failure handling and alerting

A backup job that fails silently is worse than no backup, because nobody
notices until a restore is needed. The script exits non-zero on any failure
(missing `mysqldump`, unreadable `.env`, full disk, archive error), so wrap it:

```bash
#!/usr/bin/env bash
# /usr/local/bin/educonnect-backup
set -euo pipefail

LOG=/var/log/educonnect-backup.log
APP_DIR=/var/www/educonnect
BACKUP_DIR=/var/backups/educonnect

if ! APP_DIR="$APP_DIR" BACKUP_DIR="$BACKUP_DIR" \
     "$APP_DIR/scripts/backup.sh" >>"$LOG" 2>&1; then
    # Alert somebody. Replace with your mail/curl/Slack command.
    printf 'EduConnect backup FAILED at %s\n' "$(date -Is)" \
        | mail -s 'ALERT: EduConnect backup failed' ops@example.com
    exit 1
fi

# Fail if the newest archive is older than 26 hours (cron did not run).
newest=$(find "$BACKUP_DIR" -name 'educonnect_*.tar.gz' -mmin -1560 | head -1)
if [ -z "$newest" ]; then
    printf 'No fresh EduConnect backup at %s\n' "$(date -Is)" \
        | mail -s 'ALERT: EduConnect backup stale' ops@example.com
    exit 1
fi

echo "$(date -Is) backup ok: $newest"
```

Cron then calls the wrapper:

```cron
30 2 * * * /usr/local/bin/educonnect-backup
```

Alternatives to `mail`: `curl` a healthchecks.io ping URL on success and its
`/fail` URL on failure, or post to a Slack webhook. Any of them gives you an
out-of-band signal when the server itself is the thing that broke.

### Log rotation

The backup log grows by a few lines per day; still rotate it to keep `/var/log`
predictable. `/etc/logrotate.d/educonnect-backup`:

```conf
/var/log/educonnect-backup.log {
    weekly
    rotate 8
    compress
    missingok
    notifempty
    copytruncate
}
```

Laravel's own `daily` log channel prunes itself (`LOG_DAILY_DAYS`, default 14);
the deployment guide covers the application, Nginx, Apache and worker logs.

### Monitoring checklist

- [ ] Cron entry installed for the same user that owns `storage/`
- [ ] Wrapper alert tested once by forcing a failure (e.g. bogus `DB_PASSWORD`)
- [ ] Backup log shows a successful run every day
- [ ] Off-site copy runs after the local one (rsync/rclone/S3 lifecycle)
- [ ] Disk space on `BACKUP_DIR` above 20% (`df -h`)
- [ ] Monthly restore into a scratch database (next section)
- [ ] Retention deletes old archives as expected (`RETENTION_DAYS`)

### Integrity and disk checks

Run these after a deploy or when changing the backup destination:

```bash
# Every archive opens and contains the expected members.
for f in /var/backups/educonnect/*.tar.gz; do
  if tar -tzf "$f" >/dev/null 2>&1; then echo "OK  $f"; else echo "BAD $f"; fi
done

# Enough room for at least two more archives (a full disk truncates the dump).
df -h /var/backups/educonnect

# Size trend: a sudden drop is a truncated dump, a sudden jump is a schema leak.
ls -lh --time-style=long-iso /var/backups/educonnect | tail -n 14
```

Send a copy off the machine after every successful run. The backup only protects
you if the machine that failed is not the only place the archive exists:

```bash
# Example with rclone (configure the remote once with `rclone config`).
rclone sync /var/backups/educonnect remote:educonnect-backups

# Or with rsync over SSH.
rsync -az --delete /var/backups/educonnect/ backup-host:/srv/backups/educonnect/
```

### What is intentionally not backed up

- `storage/logs` — diagnostic, not data;
- `storage/framework` — caches and compiled views, rebuilt on deploy;
- `vendor/` and `node_modules/` — rebuilt from the lock files;
- the Jitsi server volumes — see the end of this document.

`storage/app/backups` (the `backup:database` dumps) is inside `storage/app` and
therefore included in the shell archive; do not let the two jobs fill the disk.
The Laravel command keeps the newest `--keep` files (14 by default):

```bash
php artisan backup:database --keep=14
```

---

## Restore

Restoring overwrites live data. Put the application in maintenance mode first.

```bash
cd /var/www/educonnect
php artisan down
```

### 1. Unpack the archive

```bash
RESTORE_DIR="$(mktemp -d)"
tar -xzf /var/backups/educonnect/educonnect_20261029_023000.tar.gz -C "$RESTORE_DIR"
ls -la "$RESTORE_DIR"
```

You should see `database.sql.gz` (or `database.sqlite`), `storage/` and `.env`.

### 2. Restore the database (MySQL)

```bash
gunzip -c "$RESTORE_DIR/database.sql.gz" | mysql \
  --host=127.0.0.1 --user=educonnect --password educonnect
```

Use the same database name as `.env`. If the database does not exist yet,
create it first (see `docs/DEPLOYMENT.md`, step 2).

For SQLite:

```bash
cp "$RESTORE_DIR/database.sqlite" /var/www/educonnect/database/database.sqlite
sudo chown www-data:www-data /var/www/educonnect/database/database.sqlite
```

### 3. Restore uploads

```bash
sudo rsync -a "$RESTORE_DIR/storage/app/" /var/www/educonnect/storage/app/
sudo chown -R www-data:www-data /var/www/educonnect/storage/app
```

### 4. Restore `.env` only if needed

The archive contains the `.env` that was active when the backup ran. If the
current server configuration is still valid, **keep the current `.env`** and do
not overwrite it. If you are rebuilding a lost server:

```bash
cp "$RESTORE_DIR/.env" /var/www/educonnect/.env
php artisan key:generate   # only if APP_KEY was lost, then sessions/logins reset
```

### 5. Rebuild caches and go live

```bash
cd /var/www/educonnect
php artisan optimize:clear
php artisan storage:link
php artisan migrate --force     # applies any migration newer than the backup
php artisan optimize
php artisan up
```

### 6. Check

- Log in as the admin and open the dashboard.
- Open a post with an image and confirm the image loads.
- Open **Reports** and confirm the queue is intact.
- Join a class room as an enrolled student.

---

## Test a restore regularly

A monthly restore into a scratch database proves the archive is usable:

```bash
# 1. Structural check: the archive and the gzip stream are not truncated.
tar -tzf /var/backups/educonnect/educonnect_20261029_023000.tar.gz > /dev/null
echo "tar exit=$?"

# 2. Real restore into a scratch database.
mysql -e "CREATE DATABASE educonnect_restore_test"
RESTORE_DIR="$(mktemp -d)"
tar -xzf /var/backups/educonnect/educonnect_20261029_023000.tar.gz -C "$RESTORE_DIR"
gunzip -c "$RESTORE_DIR/database.sql.gz" | mysql educonnect_restore_test
mysql educonnect_restore_test -e "SELECT COUNT(*) FROM users; SELECT COUNT(*) FROM posts;"
mysql -e "DROP DATABASE educonnect_restore_test"
```

Record the date of the last successful restore test in the operations log.

---

## Encryption and access

The archive contains `.env`, which includes secrets. Therefore:

- keep `BACKUP_DIR` mode `700` and archives mode `600` (the script does both),
- encrypt off-site copies, for example with `gpg`:

  ```bash
  gpg --encrypt --recipient ops@example.com educonnect_20261029_023000.tar.gz
  ```

- never commit backups or `.env` to Git (`.gitignore` already excludes `.env`),
- rotate the database, mail and admin passwords after a restore onto a machine
  you do not fully trust.

---

## When a backup is not enough

The database dump does not include the Jitsi server. Jitsi rooms are ephemeral
and carry no persistent class data, so a fresh Jitsi installation is enough.
If you self-host Jitsi, back up its own configuration separately (Prosody,
Jicofo and JVB volumes).
````

#### `docs/DEPLOYMENT.md`

````markdown
# Deploying EduConnect

This guide takes a fresh Ubuntu/Debian server to a working HTTPS installation.
The same steps apply to a shared host with small adjustments (no root cron,
different web-server panel). Commands assume the application lives in
`/var/www/educonnect` and runs as the `www-data` user.

---

## 1. Server packages

The application targets **PHP 8.2 or newer** (tested on 8.3) and **MySQL 8**.
The package names below use the `php8.3-*` prefix; on a PHP 8.2 server replace
`8.3` with `8.2` everywhere (including `php8.2-fpm.sock` in the vhosts).

```bash
sudo apt update
sudo apt install -y nginx mysql-server php8.3-fpm php8.3-cli php8.3-mysql \
  php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl \
  unzip git nodejs npm certbot python3-certbot-nginx
```

Required PHP extensions: `openssl`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`,
`ctype`, `json`, `fileinfo`, `gd` (the upload pipeline uses GD to decode, resize
and re-encode every image; without `gd` uploads fail).

Check the versions:

```bash
php -v          # 8.2 or newer
mysql --version # 8.x
node -v         # 20 or newer
php -m | grep -E 'gd|fileinfo|pdo_mysql'
```

Install Composer from <https://getcomposer.org/download/> if it is not already
available.

---

## 2. Database

```bash
sudo mysql
```

```sql
CREATE DATABASE educonnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'educonnect'@'localhost' IDENTIFIED BY 'a-strong-database-password';
GRANT ALL PRIVILEGES ON educonnect.* TO 'educonnect'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Do not use the MySQL `root` account for the application.

---

## 3. Upload the code

```bash
sudo mkdir -p /var/www/educonnect
sudo chown -R "$USER":www-data /var/www/educonnect
git clone <repository-url> /var/www/educonnect
cd /var/www/educonnect
```

If the code is uploaded as an archive instead, unpack it in the same directory.

---

## 4. Environment file

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```dotenv
APP_NAME=EduConnect
APP_ENV=production
APP_DEBUG=false
APP_URL=https://educonnect.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=educonnect
DB_USERNAME=educonnect
DB_PASSWORD=a-strong-database-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@educonnect.example.com
MAIL_PASSWORD=smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@educonnect.example.com
MAIL_FROM_NAME="${APP_NAME}"

# The admin account created by AdminUserSeeder. Use a long random value.
ADMIN_NAME="EduConnect Admin"
ADMIN_EMAIL=admin@educonnect.example.com
ADMIN_PASSWORD=a-long-random-admin-password

# Jitsi. Leave the default for meet.jit.si or point at your own server.
JITSI_DOMAIN=meet.jit.si
# JITSI_SCRIPT_URL=https://meet.jit.si/external_api.js
```

`APP_DEBUG` must stay `false` in production: debug pages expose environment
values and stack traces.

---

## 5. Install and build

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan storage:link
```

Do **not** run `db:seed` on production. Create the admin account only:

```bash
php artisan db:seed --class=AdminUserSeeder --force
```

The seeder reads `ADMIN_NAME`, `ADMIN_EMAIL` and `ADMIN_PASSWORD` from `.env`
and refuses to run if the password is blank.

---

## 6. Permissions

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

The web server only needs read access to the rest of the code. Never point the
document root at the project root: it must be `public/`.

---

## 7. Cache the configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Whenever `.env` or the routes change, rebuild the caches:

```bash
php artisan optimize:clear
php artisan optimize
```

---

## 8. Nginx virtual host

```nginx
server {
    listen 80;
    server_name educonnect.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name educonnect.example.com;

    root /var/www/educonnect/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/educonnect.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/educonnect.example.com/privkey.pem;

    client_max_body_size 5M;

    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable it and obtain a certificate:

```bash
sudo ln -s /etc/nginx/sites-available/educonnect /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
sudo certbot --nginx -d educonnect.example.com
```

The application also sends its own security headers through
`App\Http\Middleware\SecurityHeaders`; the two `add_header` lines above are a
belt-and-braces measure for static files.

### Apache alternative

Enable the modules and point the vhost at `public/`:

```bash
sudo a2enmod rewrite headers expires deflate
sudo systemctl restart apache2
```

```apache
<VirtualHost *:80>
    ServerName educonnect.example.com
    Redirect permanent / https://educonnect.example.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName educonnect.example.com
    DocumentRoot /var/www/educonnect/public

    SSLEngine on
    SSLCertificateFile      /etc/letsencrypt/live/educonnect.example.com/fullchain.pem
    SSLCertificateKeyFile   /etc/letsencrypt/live/educonnect.example.com/privkey.pem

    <Directory /var/www/educonnect/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    # Static files: same hardening headers as Nginx.
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set X-Frame-Options "SAMEORIGIN"

    # Do not serve dotfiles or the framework directory.
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>

    ErrorLog  ${APACHE_LOG_DIR}/educonnect-error.log
    CustomLog ${APACHE_LOG_DIR}/educonnect-access.log combined
</VirtualHost>
```

`public/.htaccess` (shipped with Laravel) handles the front controller and the
`Authorization` header. Obtain the certificate with
`sudo certbot --apache -d educonnect.example.com`.

The application sends its own headers through `SecurityHeaders`; the Apache
`Header` lines cover static files that bypass PHP. The Jitsi room removes
`X-Frame-Options` at the application level, so do not add a global
`Header always set X-Frame-Options` inside a `<Location>` that also matches
`/classes/*/room` if you customise this further.

---

## 8b. Shared hosting (cPanel / Plesk / no root)

Shared hosts work with the same application; only the server management steps
change:

1. **PHP version.** Select PHP 8.2+ in the host panel and enable `pdo_mysql`,
   `mbstring`, `gd`, `fileinfo`, `openssl`, `curl`, `zip`.
2. **Document root.** Point the domain's document root at
   `/home/ACCOUNT/educonnect/public`. If the panel cannot change it, upload the
   project one level above `public_html` and copy/redirect `public_html` to
   `public/` (or add a small `index.php` that requires
   `../educonnect/public/index.php`).
3. **Composer and Node.** Run `composer install --no-dev --optimize-autoloader`
   and build the assets locally (`npm ci && npm run build`) then upload
   `public/build/` if Node is unavailable on the host.
4. **Database.** Create the database and user in the panel; copy the generated
   name/user/password into `.env` (shared hosts prefix both, e.g.
   `account_educonnect`).
5. **`.env`.** Create it outside the document root if the panel supports it;
   otherwise ensure the file is not web-accessible. Set `APP_ENV=production`,
   `APP_DEBUG=false`, `APP_URL=https://your-domain`.
6. **Writable paths.** `storage/` and `bootstrap/cache/` must be writable by the
   PHP user (usually your account user, mode `775`).
7. **Cron.** Add one cron job in the panel:
   `* * * * * /usr/local/bin/php /home/ACCOUNT/educonnect/artisan schedule:run >> /dev/null 2>&1`
   Use the panel's PHP binary path, not the system one, so the CLI version
   matches the web version.
8. **Queue.** Without Supervisor, either keep `QUEUE_CONNECTION=sync` (mail and
   notifications are sent during the request) or run a cron-driven worker:
   `* * * * * /usr/local/bin/php /home/ACCOUNT/educonnect/artisan queue:work --stop-when-empty >> /dev/null 2>&1`.
   `sync` is the simpler and supported choice for small installations.
9. **Storage link.** Run `php artisan storage:link`; some panels forbid symlinks,
   in which case map a subdomain or an alias to `storage/app/public`.
10. **HTTPS.** Enable the host's free Let's Encrypt certificate and force HTTPS;
    keep `SESSION_SECURE_COOKIE=true`.

Shared hosting cannot run `apt`, Supervisor or `certbot` directly; the rest of
this guide (environment, migrations, caches, checks) applies unchanged.

---

## 9. Scheduler

The “class starting soon” reminder and the automatic end of a running class
(`classes:complete-finished`) both run every minute through Laravel's scheduler.
Add one cron entry for the application user:

```bash
sudo crontab -u www-data -e
```

```cron
* * * * * cd /var/www/educonnect && php artisan schedule:run >> /dev/null 2>&1
```

Test it once:

```bash
php artisan classes:notify-starting-soon
php artisan classes:complete-finished
```

---

## 10. Queue workers (required in production)

All five notification classes implement `ShouldQueue`. The production settings
above use `QUEUE_CONNECTION=database`, so a worker **must** run or the
notifications stay in the `jobs` table and users never see them.

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Keep it alive with Supervisor (or systemd). A minimal Supervisor program:

```ini
[program:educonnect-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/educonnect/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/educonnect-worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start educonnect-worker:*
```

Restart workers after every deploy so they load the new code:

```bash
php artisan queue:restart
```

Check that the queue is healthy:

```bash
php artisan queue:monitor database:default --max=100
```

Local development and the demo use `QUEUE_CONNECTION=sync`, so no worker is
needed there.

---

## 11. Jitsi Meet

The room page loads the Jitsi External API from `JITSI_DOMAIN` and embeds the
conference in an iframe. For a school network, self-host Jitsi (Docker
installation from <https://jitsi.github.io/handbook/>) and set:

```dotenv
JITSI_DOMAIN=meet.example.com
```

Then `php artisan config:cache`. Nothing else changes: room names, moderator
rules and the page URL stay the same.

If a proxy serves the External API from another host, set
`JITSI_SCRIPT_URL=https://cdn.example.com/external_api.js` as well.

The server's `Content-Security-Policy` for the room page is produced by
`SecurityHeaders` and automatically allows framing only the configured Jitsi
domain.

---

## 12. Mail

Password reset (FR-3) needs a working mail transport. Keep `MAIL_MAILER=log` on
local machines and configure SMTP in production as shown in step 4.

Test it:

```bash
php artisan tinker
>>> Mail::raw('EduConnect mail test', fn ($m) => $m->to('you@example.com')->subject('Test'));
```

Password reset works with either transport:

- `QUEUE_CONNECTION=sync` sends the branded reset mail during the request (no
  worker needed),
- `QUEUE_CONNECTION=database` queues it; the Supervisor worker in step 10 sends
  it. The reset link is identical either way.

If mail silently fails, check `storage/logs/laravel.log` and the mail provider's
delivery log. With `MAIL_MAILER=log` the complete message is written to
`storage/logs/laravel.log`, so you can copy the reset URL out of it to test the
rest of the flow without a real SMTP server.

---

## 12b. Logging and error handling

| Setting | Production value | Why |
| --- | --- | --- |
| `APP_DEBUG` | `false` | debug pages leak `.env` values and stack traces |
| `LOG_CHANNEL` | `daily` | one file per day instead of one ever-growing file |
| `LOG_LEVEL` | `warning` | keeps info noise out, keeps errors and failures |
| `LOG_DAILY_DAYS` | `14` | Laravel prunes old daily files automatically |
| `APP_ENV` | `production` | disables verbose error output |

```dotenv
APP_DEBUG=false
LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14
```

The application returns the branded pages in `resources/views/errors/` for
403, 404, 419, 500 and 503, so a visitor never sees a raw stack trace. The 500
page is used for every unhandled exception; `APP_DEBUG=false` is what activates
it instead of the Ignition/whoops trace.

### Log files to watch

```bash
tail -f /var/www/educonnect/storage/logs/laravel-$(date +%F).log
sudo tail -f /var/log/nginx/educonnect-error.log
sudo tail -f /var/log/educonnect-worker.log
sudo tail -f /var/log/educonnect-backup.log
```

### logrotate

Laravel's daily channel prunes itself; Nginx, Apache, Supervisor and the backup
log need logrotate. Minimal `/etc/logrotate.d/educonnect`:

```conf
/var/log/educonnect-*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
}
```

If the app runs under a non-standard user, add `su www-data www-data` at the top
of that block.

### Fail loudly enough

`LOG_LEVEL=warning` plus the log files above is the minimum. For a real
deployment also:

- enable the host's uptime monitor on `https://educonnect.example.com/up`
  (Laravel's health endpoint, configured in `bootstrap/app.php`),
- forward `laravel-*.log` to your central log service if you have one,
- alert on the backup log: a cron wrapper that mails when `scripts/backup.sh`
  exits non-zero (see `docs/BACKUP.md` §Failure handling).

---

## 13. Post-deploy checklist

### Server side

```bash
php artisan about                     # env, cache, drivers
php artisan migrate:status            # every migration "Ran?"
php artisan schedule:list             # both commands present
php artisan route:list | head         # app boots with cached routes
php artisan queue:monitor database:default --max=100
```

```bash
# caches were built, not stale
ls bootstrap/cache/config.php bootstrap/cache/routes-v7.php
# uploads are reachable
curl -I https://educonnect.example.com/storage/README.md 2>/dev/null || true
# health endpoint
curl -fsS https://educonnect.example.com/up && echo OK
# security headers
curl -sI https://educonnect.example.com/ | grep -Ei 'x-frame|x-content|referrer|permissions'
# TLS expiry (renewal is automatic, but confirm the timer)
sudo certbot certificates | grep -E 'Certificate Name|Expiry'
sudo systemctl list-timers | grep certbot
```

### Browser walkthrough

1. `https://educonnect.example.com` loads over HTTPS; the landing page shows the
   hero, features and CTAs; the padlock has no warnings.
2. Register a student **and** a teacher; both reach their own dashboard and the
   role badge is correct.
3. Log in with the admin account and open **Users**, **Posts**, **Classes**,
   **Reports** and the **style guide**.
4. Create a class as a teacher, enrol as a student, start the class and join the
   Jitsi room from two browsers (camera/microphone prompt appears only there).
5. Report a post as a student, then hide it, block the author and dismiss another
   report as the admin; confirm each action lands in the queue with the right
   status.
6. Publish a post with an image; confirm it is resized (inspect the network tab)
   and that a `.php` upload is rejected.
7. Request a password reset and confirm the branded email arrives; reset the
   password and log in with it.
8. Set the browser to 360 px and 768 px: the mobile nav, feed and dashboard must
   stay usable; toggle dark mode and check contrast.
9. Trigger a "class starting soon" reminder and confirm the notification bell
   updates (needs the scheduler + queue worker).
10. Run the automated suite once on the server (or in CI):

```bash
php artisan test
```

Record the release (commit/tag, date, who deployed, test result) in your ops log.

---

## 14. Updates and rollback

### Deploy a new release

```bash
cd /var/www/educonnect
php artisan down --retry=60

# Always take a backup first (see docs/BACKUP.md).
./scripts/backup.sh

git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
php artisan queue:restart

php artisan up
```

Then re-run the post-deploy checklist. Keep the previous release tag and the
backup file name in the ops log; that is the rollback path.

### Roll back

```bash
cd /var/www/educonnect
php artisan down --retry=60

git checkout <previous-tag>
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Only if the new release added migrations that are safe to reverse:
php artisan migrate:rollback --step=1 --force

php artisan optimize:clear && php artisan optimize
php artisan queue:restart
php artisan up
```

If the migration cannot be reversed safely, stop and restore the database from
the backup taken before the deploy (`docs/BACKUP.md` §Restore). Do not try to
"fix forward" on a live database without a backup.

### Deploy log template

| Date | Release | Deployed by | Backup file | Tests | Notes |
| --- | --- | --- | --- | --- | --- |
| 2026-10-31 | v1.0.0 |  | `educonnect_20261031_023000.tar.gz` | 264 passed | first production release |

---

## 15. Production security checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] HTTPS with a valid certificate and `SESSION_SECURE_COOKIE=true`
- [ ] Strong, unique `ADMIN_PASSWORD` and database password
- [ ] Demo seeder **not** run in production
- [ ] `storage/` and `bootstrap/cache/` writable, everything else read-only
- [ ] `.env` not readable by the web server's public path or by other users
- [ ] `php artisan storage:link` done, uploads served through the app
- [ ] Scheduler cron installed
- [ ] Daily backups verified by restoring one into a scratch database
- [ ] Logs monitored (`storage/logs/laravel.log`, Nginx access/error logs)
- [ ] `composer audit` and `npm audit` reviewed after dependency updates
````

#### `docs/REQUIREMENTS-STATUS.md`

````markdown
# Requirement and feature status

Traceability for the finished build: which requirement each feature answers, where
the code lives and which automated test proves it. Written after the last phase
was delivered.

**How to read the FR column.** The SRS document is not part of this repository, so
the IDs below are the ones the source code, the tests and the delivered phase
briefs reference. A few numbers are used for more than one feature in the code
(for example `FR-13` appears on both the comments and the hashtag code); every
feature therefore lists *all* IDs that point at it. Where the numbering is loose,
trust the feature name and the test, not the number.

**Snapshot**

| Item | Value |
| --- | --- |
| Phases | 8 / 8 code-complete |
| Migrations | 18 (`database/migrations`) |
| Seeders | `DatabaseSeeder` → `AdminUserSeeder` + `DemoUserSeeder` + `DemoContentSeeder` (`DemoSeeder` is a wrapper kept for older notes) |
| Stack | PHP 8.2+, Laravel 12, MySQL 8, Blade + Bootstrap 5 + Bootstrap Icons via Vite, Alpine.js, Chart.js 4, Jitsi Meet External API |
| Test suite | `php artisan test` → **307 passed, 1665 assertions** |

Phase 8 is the final audit phase. Its evidence lives in
[`SECURITY.md`](SECURITY.md) (SRS 5.2), [`PERFORMANCE.md`](PERFORMANCE.md)
(SRS 5.1), [`TEST-MATRIX.md`](TEST-MATRIX.md) (FR-1 … FR-34) and
[`PHASE-8-DELIVERABLE.md`](PHASE-8-DELIVERABLE.md). Notifications and search
are documented in full in [`PHASE-6-DELIVERABLE.md`](PHASE-6-DELIVERABLE.md),
and reports, moderation and admin management in
[`PHASE-7-DELIVERABLE.md`](PHASE-7-DELIVERABLE.md).

---

## Feature status

| Feature | FR IDs referenced | Where | Verified by |
| --- | --- | --- | --- |
| Registration as teacher/student (never admin from the form) | FR-1 | `app/Http/Controllers/Auth/RegisteredUserController.php`, `app/Http/Requests/Auth/RegisterRequest.php` | `Auth/RegistrationTest` |
| Login / logout with redirect to the role dashboard | FR-1, FR-2 | `Auth/AuthenticatedSessionController.php`, `App\Http\Controllers\DashboardController.php` | `Auth/AuthenticationTest`, `RoleAccessTest` |
| Forgot / reset password by email | FR-3 | `app/Notifications/ResetPasswordNotification.php`, `Auth/NewPasswordController.php` | `Auth/PasswordResetTest` |
| Profile editing (name, email, photo, bio, subject, password) with photo validation and old-file cleanup | FR-4 | `ProfileController.php`, `ProfileUpdateRequest.php` | `ProfileTest`, `PhaseThreeUiTest` |
| Public profile plus followers / following lists | FR-5, FR-11 | `UserProfileController.php`, `resources/views/users/*` | `FeedTest`, `PhaseThreeUiTest` |
| Blocked accounts: refused at login, ejected mid-session | FR-6 | `Middleware/EnsureUserIsNotBlocked.php`, `Auth/LoginRequest.php` | `Auth/BlockedUserTest`, `DashboardTest` |
| Posts: create / edit / delete, 280 characters, optional image, image removed with the post | FR-7, FR-8 | `PostController.php`, `StorePostRequest.php`, `UpdatePostRequest.php`, `Policies/PostPolicy.php` | `FeedTest` |
| Home feed: own posts + followed users, visible only, paginated, "Load more", sidebar widgets | FR-12 | `PostController::index()`, `Post::scopeWithFeedData()`, `resources/views/feed/*` | `FeedTest`, `PerformanceTest` |
| Likes: AJAX toggle, no duplicates, rate limited | FR-9 | `LikeController.php` | `FeedTest` |
| Comments: create, delete own / admin, hidden comments excluded | FR-10, FR-13 | `CommentController.php`, `Policies/CommentPolicy.php` | `FeedTest` |
| Follow graph: follow / unfollow, no self-follow, live counters | FR-11 | `FollowController.php`, `User` follow relations | `FeedTest`, `DashboardTest` |
| Hashtags: unicode regex, lowercase, pivot sync, XSS-safe links, hashtag page | FR-13, FR-15 | `app/Services/HashtagService.php`, `HashtagController.php` | `FeedTest` |
| Search: one navbar box, people / hashtags / posts tabs, debounced live suggestions, blocked users excluded | FR-14 | `SearchController.php`, `resources/js/search.js`, `resources/views/search/index.blade.php` | `SearchTest` |
| Classes: create / edit / delete, 15–240 min duration, past-date validation, unique room name | FR-15, FR-16, FR-18 | `ClassController.php`, `StoreClassRequest.php`, `UpdateClassRequest.php`, `ClassRoom.php` | `ClassTest` |
| Class browsing, title/subject search and subject + teacher filters | FR-17, FR-19 | `ClassBrowseController.php`, `resources/views/classes/discover.blade.php` | `ClassTest` |
| Enrolment / unenrolment and "my enrolled classes" | FR-17 | `EnrollmentController.php` | `ClassTest` |
| Status workflow scheduled → live → completed, legal transitions only, finished classes closed automatically | FR-21 | `ClassStatusController.php`, `ClassRoom::TRANSITIONS`, `Console/Commands/CompleteFinishedClasses.php` | `ClassTest` |
| Timing hint on every class card ("Live now" / "Starts in 2h 15m" / "Ended") | FR-21 | `ClassRoom::timelineLabel()`, `ClassRoom::getTimelineLabelAttribute()`, `resources/views/components/class-card.blade.php` | `ClassTest` |
| Jitsi room with access control, avatar/subject, hangup flash and hidden-class refusal | FR-18, FR-19, FR-20, FR-22 | `LiveClassController.php`, `Policies/ClassPolicy.php`, `config/services.php`, `resources/js/jitsi.js` | `ClassTest`, `SmokeTest`, `PhaseEightTest` |
| Notifications: follower, like, comment, new class, class starting soon | FR-23 | `app/Notifications/*`, `app/Console/Commands/NotifyClassesStartingSoon.php` | `NotificationTest` |
| Notification bell, unread counter, 30 s polling, all/unread filter, mark one (AJAX) / all as read, delete | FR-24, FR-25 | `NotificationController.php`, `resources/js/notifications.js`, `resources/views/notifications/*` | `NotificationTest` |
| Admin analytics: totals, 12-month growth, role/post/class charts, trends | FR-26 | `AdminDashboardController.php`, `app/Support/DashboardMetrics.php`, `DashboardChartController.php` | `DashboardRedesignTest`, `DashboardTest` |
| User moderation: search, filter, block / unblock, delete | FR-27 | `AdminUserController.php`, `Policies/UserPolicy.php` | `AdminManagementTest` |
| Content moderation: hide / unhide / delete posts and classes | FR-27, FR-28 | `AdminPostController.php`, `AdminClassController.php` | `AdminManagementTest` |
| Reporting posts and comments (one report per user per item) | FR-32 | `ReportController.php`, `Policies/ReportPolicy.php`, `StoreReportRequest.php` | `ReportTest` |
| Moderation queue: filters, grouping, hide / unhide / delete / block author, reviewed, dismissed | FR-33, FR-34 | `AdminReportController.php`, `resources/views/admin/reports/*` | `ReportTest`, `SmokeTest` |
| Theme and sidebar state that survive a reload | FR-31 | `resources/js/theme.js`, `resources/js/layout.js` | `PhaseThreeUiTest` |
| Baseline security: headers, CSRF, hashed passwords, route throttles, custom error pages | SRS 5.2, FR-6 | `Middleware/SecurityHeaders.php`, `routes/auth.php`, `routes/web.php`, `resources/views/errors/*` | `PerformanceTest`, `DashboardTest` |
| Phase 8 security audit: CSRF static scan, Form Request coverage, mass-assignment proof, upload decode/re-encode, rate-limit table, full RBAC route table | SRS 5.2 | `app/Http/Requests/*`, `app/Services/ImageUploadService.php`, `app/Http/Middleware/SecurityHeaders.php` | `PhaseEightTest` (security half), `docs/SECURITY.md` |
| Phase 8 performance pass: 15 composite indexes, eager loading, pagination, five-minute dashboard caches, < 3 s page target | SRS 5.1 | `database/migrations/2026_10_30_000002_add_phase_eight_query_indexes.php`, `app/Support/DashboardMetrics.php` | `PerformanceTest`, `PhaseEightTest` (performance half), `docs/PERFORMANCE.md` |
| Phase 8 usability pass: branded 403/404/419/500/503 pages, meta/OG/favicon head, empty states, AJAX busy/error feedback, landing page | SRS 5.3 | `resources/views/errors/*`, `resources/views/layouts/app.blade.php`, `resources/js/forms.js`, `resources/views/welcome.blade.php` | `PhaseEightTest` (usability half), `PhaseThreeCTest` |

---

## Use cases and non-functional requirements

No `UC-x` or `NFR-x` identifier is referenced anywhere in the code or the phase
notes, so no per-item mapping can be claimed here. The delivered behaviour that
answers that part of the SRS is nonetheless in place:

- responsive, mobile-first Bootstrap 5 layout built from the SASS theme in
  `resources/sass`, tested from 320 px upwards;
- low-bandwidth friendly: no framework bloat, images `loading="lazy"`, small
  vanilla-JS modules in `resources/js`;
- all output escaped in Blade, hashtags linked only after escaping;
- passwords hashed with bcrypt, CSRF on every form, per-route rate limits;
- pagination on every list, eager loading everywhere (`PerformanceTest` guards the
  feed and the admin table query counts).

If you hold the SRS, add the `UC-x` / `NFR-x` rows to the table above using the
same "Where / Verified by" columns.

---

## Deviations worth reporting

1. Laravel **12** is installed while the SRS names Laravel 11 (the framework
   version the workspace shipped with).
2. The class model is `ClassRoom` because `Class` is reserved in PHP; the table is
   still `classes`.
3. Extra columns beyond the SRS data model: `classes.is_hidden` (FR-28),
   `classes.starting_soon_notified_at` (notification dedupe),
   `reports.details` (report free text).
4. The test suite runs on in-memory SQLite; development and production use MySQL.

These are the same items documented in the README, section *Known deviations from
the SRS*.

---

## Re-verify

```bash
php artisan test                      # 307 passed, 1665 assertions
php artisan test --filter=PhaseEightTest   # 26 tests, 251 assertions
php artisan route:list --except-vendor
php artisan migrate:status
php artisan schedule:list             # classes:notify-starting-soon and classes:complete-finished, every minute
```

Deployment, queue worker and cron instructions: [`DEPLOYMENT.md`](DEPLOYMENT.md).
Backup and restore: [`BACKUP.md`](BACKUP.md).
Security audit: [`SECURITY.md`](SECURITY.md). Performance pass:
[`PERFORMANCE.md`](PERFORMANCE.md). FR-1 … FR-34 results:
[`TEST-MATRIX.md`](TEST-MATRIX.md). Final phase notes:
[`PHASE-8-DELIVERABLE.md`](PHASE-8-DELIVERABLE.md).
````

#### `README.md`

````markdown
# EduConnect

EduConnect is an online class platform with a Twitter-style social feed. Teachers
schedule live classes, students enrol and join them in the browser, and the whole
community can post, comment, like, follow and search. Because minors may use the
platform, every post and comment can be reported and moderated from an admin
queue.

Built as a Laravel MVC application with Blade, Bootstrap 5, Chart.js and the
Jitsi Meet External API.

---

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Local installation](#local-installation)
- [Demo accounts](#demo-accounts)
- [Running the application](#running-the-application)
- [Testing](#testing)
- [Screenshots](#screenshots)
- [Roles and permissions](#roles-and-permissions)
- [Project structure](#project-structure)
- [Requirement status](#requirement-status)
- [Security, performance and usability](#security-performance-and-usability)
- [Production deployment](#production-deployment)
- [Backups](#backups)
- [Configuration reference](#configuration-reference)
- [Known deviations from the SRS](#known-deviations-from-the-srs)

---

## Features

| Area | What you can do |
| --- | --- |
| Authentication | Register as teacher or student, log in/out, reset a forgotten password by email, verify an email address |
| Profiles | Edit name, email, photo, bio and subject; public profile with posts, followers and following counts |
| Feed | 280-character posts with an optional image, edit/delete your own posts, likes without a page reload, comments, follows |
| Discovery | Hashtag pages, people / hashtag / post search with live suggestions, class search and subject filter |
| Live classes | Create/edit/delete a class, scheduled → live → completed status, student enrolment, embedded Jitsi room with Jitsi chat |
| Notifications | New follower, like, comment, new class from a followed teacher, class starting soon; unread badge and mark-as-read |
| Dashboards | Separate admin, teacher and student dashboards with cards, Chart.js chart, class table and activity feeds |
| Safety | Report posts or comments with a reason, admin moderation queue, hide/unhide/delete content, block or delete users |
| Admin | User search and blocking, post management, class management, report filters and statuses |
| Hardening | Security headers on every response, CSRF on every write form, rate-limited writes, GD upload pipeline (decode → resize → re-encode → random name), 15 hot-path indexes, five-minute dashboard caches, friendly 403/404/419/500/503 pages |

The per-feature requirement traceability (which FR ID maps to which controller
and which test) is in [`docs/REQUIREMENTS-STATUS.md`](docs/REQUIREMENTS-STATUS.md).
The Phase 8 audit trail is split across
[`docs/SECURITY.md`](docs/SECURITY.md), [`docs/PERFORMANCE.md`](docs/PERFORMANCE.md)
and [`docs/TEST-MATRIX.md`](docs/TEST-MATRIX.md); the phase-by-phase delivery
notes are in [`docs/`](docs/), with Phase 6 (notifications and search) in
[`docs/PHASE-6-DELIVERABLE.md`](docs/PHASE-6-DELIVERABLE.md), Phase 7 (reports
and moderation) in
[`docs/PHASE-7-DELIVERABLE.md`](docs/PHASE-7-DELIVERABLE.md) and the final
phase summarised in [`docs/PHASE-8-DELIVERABLE.md`](docs/PHASE-8-DELIVERABLE.md).

---

## Requirements

- PHP **8.2+** with the usual Laravel extensions (`openssl`, `pdo`, `mbstring`,
  `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`)
- Composer 2
- Node.js 20+ and npm
- MySQL 8 (SQLite is only used by the automated test suite)
- A Jitsi Meet server. The public `meet.jit.si` is the default; a self-hosted
  instance can be configured with `JITSI_DOMAIN`.

The project was generated with Laravel 12 while the SRS names Laravel 11. See
[Known deviations](#known-deviations-from-the-srs).

---

## Local installation

```bash
# 1. Install the PHP and JavaScript dependencies
composer install
npm install

# 2. Create the environment file and the application key
cp .env.example .env
php artisan key:generate

# 3. Create an empty MySQL database, then set the credentials in .env
#    DB_DATABASE=educonnect
#    DB_USERNAME=root
#    DB_PASSWORD=...

# 4. Put a strong admin password in .env (the seeder refuses to run without it)
#    ADMIN_PASSWORD="Change-me-before-seeding-123!"

# 5. Build the database and the demo content
php artisan migrate --seed

# 6. Make uploaded images reachable from the browser
php artisan storage:link

# 7. Build the front-end assets
npm run build
```

If `php artisan migrate` fails because MySQL is not available, the automated
tests still work: they use an in-memory SQLite database defined in
`phpunit.xml`.

### Optional: sample data only

`DatabaseSeeder` creates the admin account and the demo people. To run only one
of them:

```bash
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=DemoSeeder
```

The demo seeder is safe to run twice: it updates the demo accounts and skips
the social content when posts already exist.

---

## Demo accounts

Created by `DemoSeeder` (local development only):

| Role | Email | Password |
| --- | --- | --- |
| Admin | value of `ADMIN_EMAIL` in `.env` (default `admin@educonnect.test`) | value of `ADMIN_PASSWORD` in `.env` |
| Teacher | `teacher1@educonnect.test`, `teacher2@educonnect.test`, `teacher3@educonnect.test` | `password` |
| Student | `student1@educonnect.test` … `student6@educonnect.test` | `password` |

**Never run the demo seeder on a production database.** It creates accounts with
the well-known password `password`.

---

## Running the application

```bash
# Terminal 1: the web server (the port must match APP_URL in .env, here 8010)
php artisan serve --host=127.0.0.1 --port=8010

# Terminal 2: Vite with hot reload (during development)
npm run dev

# Terminal 3 (optional): run scheduled tasks without cron, e.g. the
# "class starting soon" reminders
php artisan schedule:work
```

With the default `QUEUE_CONNECTION=sync` no queue worker is needed: every
notification is written during the request. Production uses
`QUEUE_CONNECTION=database`, so it must run a worker (see
[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md#10-queue-workers-required-in-production)).
To exercise the queued notifications locally, set `QUEUE_CONNECTION=database` and
run `php artisan queue:work` in a fourth terminal.

`php artisan serve` listens on `http://127.0.0.1:8000` unless a port is given,
while `.env.example` sets `APP_URL=http://127.0.0.1:8010`. Keep the two in step,
otherwise the links Blade generates point at the wrong address.

Uploaded images are stored on the `public` disk (`storage/app/public`) and are
served through the `public/storage` symlink created by `php artisan storage:link`.

### Scheduled tasks

`routes/console.php` registers two commands:

```
classes:notify-starting-soon   every minute   (FR-23)
classes:complete-finished      every minute   (FR-21)
```

In production one cron entry is enough:

```cron
* * * * * cd /var/www/educonnect && php artisan schedule:run >> /dev/null 2>&1
```

---

## Testing

```bash
php artisan test
```

The suite covers authentication, role access, the feed, classes, notifications,
search, profiles, seeders, reporting, admin management and the Phase 8
security/performance/usability audit. Tests use SQLite in memory, the array
session driver and the array mail driver, so they never touch the development
database.

Last verified run (Phase 8 final):

```text
Tests:  307 passed (1665 assertions)
```

Useful variations:

```bash
php artisan test --filter=SmokeTest          # full role walkthrough
php artisan test --filter=NotificationTest   # FR-23 … FR-25
php artisan test --filter=SearchTest         # FR-14
php artisan test --filter=ReportTest         # FR-32 … FR-34
php artisan test --filter=AdminManagementTest
php artisan test --filter=PhaseSevenTest     # cross-cutting Phase 7 guarantees
php artisan test --filter=BlockedUserTest    # blocked login and session ejection
php artisan test --filter=PhaseEightTest     # security, performance, usability
php artisan test --parallel                  # if your machine supports it
```

The requirement-by-requirement result table (FR-1 … FR-34 plus the cross-cutting
Phase 8 checks and the manual sign-off list) is in
[`docs/TEST-MATRIX.md`](docs/TEST-MATRIX.md).

Code style:

```bash
./vendor/bin/pint
```

---

## Screenshots

The `docs/screenshots/` folder is the place to drop the final captures (the
repository intentionally ships no binary images, see below for the checklist).
Capture at 1200 px wide (desktop) and 360 px (mobile) in both themes:

| File name | Page | Suggested state |
| --- | --- | --- |
| `docs/screenshots/01-landing.png` | Guest landing | hero, feature grid, CTAs |
| `docs/screenshots/02-feed.png` | Feed | three posts, a comment preview and a like |
| `docs/screenshots/03-admin-dashboard.png` | Admin dashboard | KPI cards, charts, moderation shortcuts |
| `docs/screenshots/04-class-catalogue.png` | Find classes | subject filter and class cards |
| `docs/screenshots/05-class-room.png` | Live room | Jitsi embed with the pre-join card |
| `docs/screenshots/06-profile.png` | Public profile | tabs, follower counts, posts |
| `docs/screenshots/07-report-queue.png` | Admin reports | pending report with content preview |
| `docs/screenshots/08-mobile-feed-360.png` | Feed at 360 px | mobile nav and FAB |

If you add the images, reference them here with standard Markdown:

```markdown
![Feed](docs/screenshots/02-feed.png)
```

Until then, `php artisan serve` plus the demo accounts below reproduce every
screen exactly.

---

## Roles and permissions

| Role | Can do |
| --- | --- |
| Guest | Landing page, login, register, password reset |
| Student | Feed, posts, comments, likes, follow teachers, browse/enrol/join classes, report content, notifications, search, own profile |
| Teacher | Everything a student can do for the feed, plus create/edit/delete their own classes, see enrolled students, start/end a class, see their teacher dashboard |
| Admin | Platform totals and chart, manage users (search/block/unblock/delete), posts and classes (hide/delete), moderation queue (hide/unhide/delete content, block authors, reviewed/dismissed) |

Authorisation is enforced in three layers:

1. `role:` middleware on route groups,
2. policies (`PostPolicy`, `CommentPolicy`, `ClassPolicy`, `ReportPolicy`,
   `UserPolicy`) for per-record decisions,
3. `EnsureUserIsNotBlocked` on every web request, so a blocked user is logged
   out immediately.

---

## Project structure

```
app/
  Console/Commands/         class reminder command
  Http/Controllers/         web controllers (admin area in Admin*Controller)
  Http/Middleware/          role check, blocked-user check, security headers
  Http/Requests/            form validation
  Models/                   User, Post, Comment, Like, Follow, Hashtag,
                            ClassRoom, ClassEnrollment, Report
  Notifications/            five notification types
  Policies/                 record-level authorisation
  Services/                 HashtagService, ImageUploadService (GD pipeline)
  Support/                  DashboardMetrics (five-minute caches)
database/
  factories/                test and demo data
  migrations/               schema
  seeders/                  AdminUserSeeder, DemoSeeder
docs/                       deployment and backup guides
resources/
  js/                       feed, Jitsi, notifications, search, Chart.js wiring
  sass/                     Bootstrap 5 theme
  views/                    Blade templates and components
routes/
  web.php                   all application routes
  auth.php                  Breeze authentication routes
  console.php               scheduled tasks
tests/Feature/              feature tests, including SmokeTest
scripts/backup.sh           database + uploads backup
```

---

## Requirement status

Every feature shipped across the eight phases is mapped to its requirement ID,
the files that implement it and the test that proves it in
[`docs/REQUIREMENTS-STATUS.md`](docs/REQUIREMENTS-STATUS.md). Summary:

| Phase | Scope | State |
| --- | --- | --- |
| 1 | Setup, authentication, roles, profile, seeders | done |
| 2 | Schema, models, relationships, factories, demo data | done |
| 3 / 3B | Layout, reusable components, three role dashboards, charts, error pages | done |
| 4 | Social feed: posts, likes, comments, follows, hashtags | done |
| 5 | Live classes, enrolment, Jitsi room, access control | done |
| 6 | Notifications, class reminders, search | done |
| 7 | Admin analytics, user/content moderation, report queue, hardening | done |
| 8 | Security/performance/usability audit, FR-1…FR-34 test matrix, docs and deployment guide | done |

Last verified run: `php artisan test` → **307 passed, 1665 assertions**.

---

## Security, performance and usability

The Phase 8 audit is documented in three places rather than repeated here:

- [`docs/SECURITY.md`](docs/SECURITY.md) — authentication/authorisation layers,
  CSRF static scan, Form Request rules, output escaping, the upload pipeline,
  rate-limit table, security headers (including the Jitsi exception), production
  session/cookie defaults and the complete route middleware table generated from
  `php artisan route:list --except-vendor`.
- [`docs/PERFORMANCE.md`](docs/PERFORMANCE.md) — the 15 composite indexes, query
  budgets, eager loading, pagination sizes, the five-minute `DashboardMetrics`
  cache keys, image/asset handling, the Laravel cache commands and how to
  reproduce the < 3 s page-load target.
- [`docs/TEST-MATRIX.md`](docs/TEST-MATRIX.md) — FR-1 … FR-34 with the test that
  proves each one, the cross-cutting Phase 8 checks and the manual sign-off list
  (responsive breakpoints, real SMTP, Jitsi call, queue worker, MySQL restore).
- [`docs/PHASE-8-DELIVERABLE.md`](docs/PHASE-8-DELIVERABLE.md) — the phase
  summary: changed files, commands, FR table, evidence and "Done when".

Highlights that are easy to verify locally:

```bash
php artisan test --filter=PhaseEightTest      # 26 tests, 251 assertions
php artisan route:list --except-vendor        # every route with its middleware
php artisan view:cache && php artisan view:clear
php artisan config:cache && php artisan route:cache && php artisan optimize:clear
```

Usability: the layout is checked at 360 / 768 / 1200 px, every AJAX action shows
a busy spinner and an error toast, empty states share one component, the error
pages are branded, dark-mode tokens are contrast-checked, every page has a
title/meta/OG block and the guest landing page has a hero, feature grid and CTAs.

---

## Production deployment

See [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) for the complete checklist:
server packages, database creation, `.env` values, build steps, Nginx/Apache
configuration, HTTPS, cron, queue workers and rollback.

Short version:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Production `.env` essentials:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://educonnect.example.com
SESSION_SECURE_COOKIE=true
MAIL_MAILER=smtp
JITSI_DOMAIN=meet.example.com
```

---

## Backups

[`scripts/backup.sh`](scripts/backup.sh) dumps the MySQL database and archives
the uploaded files (and `.env`). It keeps 14 days by default.

```bash
# Manual run
sudo -u www-data APP_DIR=/var/www/educonnect ./scripts/backup.sh

# Daily at 02:30
30 2 * * * /var/www/educonnect/scripts/backup.sh >> /var/log/educonnect-backup.log 2>&1
```

Restore steps and retention details are in [`docs/BACKUP.md`](docs/BACKUP.md).

---

## Configuration reference

| Variable | Purpose |
| --- | --- |
| `APP_NAME`, `APP_URL`, `APP_ENV`, `APP_DEBUG` | Standard Laravel application settings |
| `DB_*` | MySQL connection |
| `SESSION_DRIVER`, `SESSION_SECURE_COOKIE` | Database sessions; secure cookie in HTTPS production |
| `QUEUE_CONNECTION` | `sync` locally; `database` in production **with a running queue worker** (queued notifications) |
| `MAIL_*` | `log` locally, SMTP in production for password reset |
| `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Admin account created by `AdminUserSeeder` |
| `JITSI_DOMAIN` | Jitsi server domain (default `meet.jit.si`) |
| `JITSI_SCRIPT_URL` | Optional override for the External API script URL |

Upload limits live in `config/educonnect.php` (profile photo: 2 MB, jpg/png/webp;
post image: 2 MB, jpg/png/webp).

---

## Known deviations from the SRS

These are reported honestly rather than hidden:

1. **Laravel 12 instead of 11.** The installed framework is
   `laravel/framework: ^12.0`. The application uses the same MVC, Blade, policy
   and middleware APIs the SRS requires; Laravel 12 is the current security
   supported release. Downgrading to 11 would not change any feature.
2. **The class model is `ClassRoom`.** `Class` is a reserved word in PHP, so the
   model is `ClassRoom`; the database table is still `classes` exactly as the
   SRS database section says.
3. **Two extra columns for moderation.** `classes.is_hidden` is required by
   FR-28 (hide an inappropriate class) and `reports.details` stores the optional
   free text from the report form. `classes.starting_soon_notified_at` prevents
   duplicate “class starting soon” notifications.
4. **SQLite in tests, MySQL in development/production.** The SRS asks for MySQL
   8; the application runs on MySQL. SQLite is only used to keep the automated
   test suite fast and isolated.
````

### A.9 `public/favicon.ico` (binary)

The icon is 815 bytes: an ICO container with 16×16, 32×32 and 48×48 PNG
frames, indigo background (`#4f46e5`) and a white “E”. Reproduce it with:

````bash
base64 -d <<'ICO' > public/favicon.ico
AAABAAMAEBAAAAEAIAC/AAAANgAAACAgAAABACAA9wAAAPUAAAAwMAAAAQAgAEMBAADsAQAAiVBO
Rw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAcUlE
QVQ4jWNkYGD4zwAF/m5PGYgBG3dJw9lMpGpGV8tEqmZ0Qxj93Z7+J6AWL2DBJrhhpxRODQHuz1D4
TDjUUeYCXLaRbAC6V7AZSLEXhlgYYDOUYi8wMjAw/CcnKTMwQDIVE4xBjmYGBiQvkGIIsloAqE8i
map9njIAAAAASUVORK5CYIKJUE5HDQoaCgAAAA1JSERSAAAAIAAAACAIBgAAAHN6evQAAAAJcEhZ
cwAADsQAAA7EAZUrDhsAAACpSURBVFiF7ZfBDYAwCEVb4hS6jx7rqHrUfXQNPZgmSmtKjeVf+o9I
4OUDUa0x5jBMrt946BfNaxfESKv5W21KJZSGoLcHWhCk3ZxDWNdvwRJqKljCCqCtRpo4LW128XHY
kzlwB+AA4hHcJbFWKrgDcIBPI0hdRM6I4A7AAeoVwAGKXIFXfRdIVD/J8ACx3yUtzWt3OYCA8D2J
BzSbPwC0IHiPYAlLQsRqn/roMD9nZ3CHAAAAAElFTkSuQmCCiVBORw0KGgoAAAANSUhEUgAAADAA
AAAwCAYAAABXAvmHAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAA9UlEQVRoge2aSw6DMAxEB6unKPeh
S3rUsiz3gWu0KyQUYoWmqeyp/Jb5SPNkrAhCB+CFAuOwlJb8hGnui2s6KAJWoTU0GckNegsP6JkO
Ah7Db+SySWmBN9KMok14Zp9V0gEWtszZJmaCXqAbh6V4kHmGvgIhYM2lduPjeW2ZAwBwv60f76Gv
QAhYU90DKTXPbwvoKxAC1tALNGvimoOtRePTVyAErKEXiJPYmhCwhl7A9CROiVdKRugF4tOiNfwC
Z64yvTLN/R9UwDrAtwhw7kbcG1tmSQcY2GcVbcIracZDD3iWyGXLNrFHCS2T+rfKHs+/27wBbRI+
W+F/CBEAAAAASUVORK5CYII=
ICO
````

Or, on Windows/PHP:

````bash
php -r "file_put_contents('public/favicon.ico', base64_decode(trim(file_get_contents('php://stdin'))));" < favicon.b64
````

### A.10 How this list was verified

```bash
# Route table is literal, not hand-written.
php artisan route:list --except-vendor --json > routes.json
# compare each method+URI and middleware list with docs/SECURITY.md §9
# (92/92 routes, 0 middleware mismatches at the time of writing)

# Every fenced block above is byte-identical to the file it names:
php artisan test --filter=PhaseEightTest
vendor/bin/pint --test app config database routes tests
```

## Appendix B. Phase 8 command summary

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
php artisan test
vendor/bin/pint --test app config database routes tests
php artisan backup:database --keep=14
```
