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
