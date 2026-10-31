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
| Test suite | `php artisan test` → **266 passed, 1401 assertions** |

Phase 8 is the final audit phase. Its evidence lives in
[`SECURITY.md`](SECURITY.md) (SRS 5.2), [`PERFORMANCE.md`](PERFORMANCE.md)
(SRS 5.1), [`TEST-MATRIX.md`](TEST-MATRIX.md) (FR-1 … FR-34) and
[`PHASE-8-DELIVERABLE.md`](PHASE-8-DELIVERABLE.md).

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
| Comments: create, delete own / post author / admin, hidden comments excluded | FR-10, FR-13 | `CommentController.php`, `Policies/CommentPolicy.php` | `FeedTest` |
| Follow graph: follow / unfollow, no self-follow, live counters | FR-11 | `FollowController.php`, `User` follow relations | `FeedTest`, `DashboardTest` |
| Hashtags: unicode regex, lowercase, pivot sync, XSS-safe links, hashtag page | FR-13, FR-15 | `app/Services/HashtagService.php`, `HashtagController.php` | `FeedTest` |
| Search: one navbar box, people / hashtags / posts tabs, debounced live suggestions, blocked users excluded | FR-14 | `SearchController.php`, `resources/js/search.js`, `resources/views/search/index.blade.php` | `SearchTest` |
| Classes: create / edit / delete, 15–240 min duration, past-date validation, unique room name | FR-15, FR-16, FR-18 | `ClassController.php`, `StoreClassRequest.php`, `UpdateClassRequest.php`, `ClassRoom.php` | `ClassTest` |
| Class browsing, subject/teacher filter and title search | FR-17 | `ClassBrowseController.php` | `ClassTest` |
| Enrolment / unenrolment and "my enrolled classes" | FR-17 | `EnrollmentController.php` | `ClassTest` |
| Status workflow scheduled → live → completed, legal transitions only, finished classes closed automatically | FR-21 | `ClassStatusController.php`, `ClassRoom::TRANSITIONS`, `Console/Commands/CompleteFinishedClasses.php` | `ClassTest` |
| Timing hint on every class card ("Live now" / "Starts in 2h 15m" / "Ended") | FR-21 | `ClassRoom::timelineLabel()`, `resources/views/components/class-card.blade.php` | `ClassTest` |
| Jitsi room with access control and hidden-class refusal | FR-18, FR-19, FR-20, FR-22 | `LiveClassController.php`, `Policies/ClassPolicy.php`, `config/jitsi.php`, `resources/js/jitsi.js` | `ClassTest`, `SmokeTest` |
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
5. The Jitsi domain lives in `config/jitsi.php` (env `JITSI_DOMAIN`) rather than
   in `config/services.php` as the SRS suggests; the env-driven switching it is
   meant to provide works exactly the same.

These are the same items documented in the README, section *Known deviations from
the SRS*.

---

## Re-verify

```bash
php artisan test                      # 266 passed, 1401 assertions
php artisan test --filter=PhaseEightTest   # 26 tests, 246 assertions
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
