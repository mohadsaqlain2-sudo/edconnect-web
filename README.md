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
notes are in [`docs/`](docs/), with the final phase summarised in
[`docs/PHASE-8-DELIVERABLE.md`](docs/PHASE-8-DELIVERABLE.md).

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
Tests:  266 passed (1401 assertions)
```

Useful variations:

```bash
php artisan test --filter=SmokeTest          # full role walkthrough
php artisan test --filter=ReportTest         # FR-32 … FR-34
php artisan test --filter=AdminManagementTest
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

Every feature shipped across the seven phases is mapped to its requirement ID,
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

Last verified run: `php artisan test` → **266 passed, 1401 assertions**.

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
php artisan test --filter=PhaseEightTest      # 26 tests, 246 assertions
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
5. **The Jitsi settings live in `config/jitsi.php`.** The SRS names
   `config/services.php`; the Jitsi domain and script URL sit in their own config
   file instead, still read from `JITSI_DOMAIN` / `JITSI_SCRIPT_URL` in `.env`.
   The behaviour asked for (switch to a self-hosted Jitsi server without touching
   code) is unchanged.
