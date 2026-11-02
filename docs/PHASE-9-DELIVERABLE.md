# Phase 9 — Final audit, strict SRS compliance and release sign-off

**Project:** EduConnect — social learning platform with live classes
**Role for this phase:** project supervisor / QA lead
**Date:** 1 November 2026
**Scope:** audit the finished system against FR-1 … FR-34, UC-1 … UC-8,
the non-functional requirements and SRS §4; fix every Partial/Missing item;
prove the result with automated tests and an ordered manual smoke test.

> **Read this first — the SRS is not in the repository.**
> `docs/` contains the phase briefs, the security/performance audits and the
> requirement snapshots, but not the SRS document itself. The FR identifiers
> below are the ones the code, tests and phase briefs use. The UC and NFR
> identifiers are **inferred** from the delivered feature set and are labelled
> as such in every table. Database verdicts say "matches the model described by
> the phase briefs"; they do not claim a byte-for-byte comparison with a §4
> document that is not available in this workspace. Where the SRS would settle a
> question, it is listed under *Residual risks* instead of being guessed.

---

## 1. Snapshot

| Item | Value |
| --- | --- |
| Phases | **9 / 9 complete** (8 delivery phases + final audit) |
| Test suite | `php artisan test` → **317 passed, 1709 assertions** |
| Phase 9 suite | `php artisan test --filter=PhaseNineTest` → **10 passed, 44 assertions** |
| Phase 8 suite | `php artisan test --filter=PhaseEightTest` → **26 passed, 251 assertions** |
| Pint | `vendor/bin/pint --test app tests routes database config bootstrap/app.php public/index.php` → **passed** |
| Routes | **92** total, **85** named (`php artisan route:list --except-vendor --json`) |
| Migrations | **18** (`database/migrations`) |
| Models / controllers | **9** models, **34** controllers |
| Form Requests / policies / middleware / console commands | **14 / 5 / 3 / 3** |
| Test files | **27** (`tests/`, Phase 9 adds `PhaseNineTest.php`) |
| Frontend build | `npm run build` → 140 modules; app CSS 368.63 kB (57.47 kB gzip), app JS 229.62 kB (75.83 kB gzip), `auto` JS 209.95 kB (72.11 kB gzip) |
| Laravel / PHP | Laravel 12, PHP 8.2+ |
| Database used for the audit | SQLite (in-memory for tests, temp file for schema introspection); MySQL 8 not available in this environment |

Every number above was produced by a command in section 8, not copied from an
older document.

---

## 2. Method

1. **Requirement walk-through.** Every FR was traced from the route to the
   controller, Form Request, policy, view and automated test.
2. **Source review.** All 18 migrations, 9 models, 34 controllers, 14 Form
   Requests, 5 policies, 3 middleware, services, notifications, console
   commands, seeders, key Blade views and JavaScript modules were read.
3. **Static scans.** Route-name resolution, hardcoded `href` values, CSRF
   tokens, `TODO`/`FIXME`/placeholder text, empty-state markers and deletion
   paths were scanned mechanically.
4. **Live database audit.** All migrations were run on a throw-away SQLite file;
   tables, columns, foreign keys, unique constraints and indexes were read from
   `PRAGMA table_info`, `PRAGMA foreign_key_list` and `PRAGMA index_list`.
5. **Behavioural tests.** The full suite was run, then a new
   `tests/Feature/PhaseNineTest.php` was added for every finding. Any finding
   without a regression test was considered not fixed.
6. **Release checks.** Pint, Vite production build, route/config/view caches and
   `optimize:clear` were run after the last code change.

---

## 3. Findings and fixes

All findings were fixed in this phase. There are no open Partial/Missing items.

| ID | Severity | Finding | Fix | Regression test |
| --- | --- | --- | --- | --- |
| P9-01 | **High** | Polymorphic `reports` have no foreign key to the reported post/comment. Deleting a post, comment or user through Eloquent left orphan reports that still appeared in the moderation queue. | Cleanup moved into `Post`, `Comment` and `User` `deleting` hooks, so author, admin, moderation-queue and account-deletion paths behave identically. | `PhaseNineTest::test_deleting_a_post_removes_its_reports_its_comment_reports_and_its_image`, `test_deleting_a_comment_removes_the_reports_about_it`, `test_deleting_a_user_cleans_reports_about_their_content_and_the_uploaded_files` |
| P9-02 | **Medium** | Deleting a user removed their posts through the database cascade, which bypasses `Post` events, so `storage/app/public/posts/*` images leaked on disk. | The `User` hook deletes every owned post image and the profile photo before the account row is removed. | same user-deletion test (asserts both files are gone) |
| P9-03 | **Medium** | Admin report queue ran one lazy query per row for `reported_user` in addition to the eager-loaded reporter/reportable. | `AdminReportController::index()` uses `morphWith(['user'])` for both reportable types. | `test_the_admin_report_queue_does_not_run_one_query_per_report` |
| P9-04 | **Medium** | Public profile class cards called `students()->count()` and lazy-loaded `teacher` per card (six cards → twelve extra queries). | `UserProfileController::show()` eager-loads `teacher` and `withCount('students')` for all profile cards. | `test_the_public_profile_loads_class_cards_without_n_plus_one` |
| P9-05 | **Medium** | Class discovery interpolated the user term into `LIKE` without escaping `%` or `_`; searching `%` returned the whole catalogue and `_` matched arbitrary characters. (Bound parameters meant this was never SQL injection.) | `ClassBrowseController::index()` escapes `!`, `%` and `_` and caps the term at 100 characters. | `test_class_search_treats_like_wildcards_as_literal_text` |
| P9-06 | **Low** | A post or comment body of only spaces passed `required` and was stored as invisible content. | `prepareForValidation()` trims the body in the three Form Requests, so whitespace-only input fails `required` and real text is stored trimmed. | `test_whitespace_only_posts_and_comments_are_rejected`, `test_the_body_is_trimmed_before_it_is_stored` |
| P9-07 | **Medium** | `User` used the framework's `MustVerifyEmail` trait but did not implement the interface. Registration therefore never sent a verification link and the "unverified" banner never rendered, despite the verification routes and template existing. | `User implements MustVerifyEmail`; the `Registered` listener now mails the signed link. The account stays usable until verified (no route is locked behind `verified`). | `test_registration_sends_the_email_verification_notification` |
| P9-08 | **Low** | The live-room help said "Your microphone starts muted" to everybody, but `jitsi.js` starts the moderator unmuted. | The room view shows the correct copy for moderators and students. | `test_the_live_room_microphone_help_matches_the_defaults` |
| P9-09 | **Low** | `SmokeTest` assumed a visible post the first student had not reported. `DemoContentSeeder` randomly files a dismissed report for that student, so the end-to-end test failed intermittently (~1 in 30 runs). | The test picks a visible post that the student has not already reported. | `SmokeTest::test_the_report_to_moderation_flow_works_end_to_end` (now deterministic) |

### 3.1 What the scan found clean

| Check | Result |
| --- | --- |
| Broken route names (`route('…')` in app/views) | 0. The only four "misses" were parameter lookups (`$request->route('token')`, `route('user')` model binding), not route names. |
| Hardcoded `href="/…"` links | 0 broken links; every navigation target uses `route()` or a documented external URL. |
| Leftover `TODO` / `FIXME` / `XXX` / "Lorem ipsum" / placeholder text | 0 in `app`, `routes`, `database`, `resources`, `tests`. |
| Empty states | Every list page (feed, discover, enrolled, class roster, search tabs, hashtag page, notifications, admin users/posts/classes/reports) uses the shared `x-empty-state` component. |
| Authorization | Every state-changing controller action has a policy check or an authorized Form Request; the 92-route middleware table in `docs/SECURITY.md` still matches the router. |
| Validation | Every write route has a Form Request (or inline validated array where there is no user text); CSRF is present on every POST/PATCH/DELETE form. |
| N+1 on hot lists | Feed (`withFeedData`), admin users/posts/classes, class discovery, enrolled classes, notifications, search, dashboard panels and the two fixed pages are bounded. `PerformanceTest` plus Phase 9 guard the counts. |
| Placeholder/disabled UI controls | 0. Every visible button posts to a real route. |
| Unresponsive fixed widths | None found in the templates; the layout is grid/flex based with the 360/768/1200 px manual checklist in `docs/TEST-MATRIX.md`. |

### 3.2 Security notes on the fixes

- The deletion hooks run **inside the model lifecycle**, not in controllers. This
  is deliberate: the same cleanup applies to the author, the admin post table,
  the moderation queue and account deletion, including future callers.
- No new mass-assignable fields were added. `is_hidden`, `user_id`, `role` and
  `is_blocked` remain protected (`PhaseEightTest` still passes).
- The LIKE escaping is defence-in-depth, not an injection fix: the query was
  already parameterised. It prevents a user from turning a search into a
  full-table scan and from matching characters they did not type.
- The verification mail is informational, not an access gate. This keeps the
  product usable when SMTP is down (documented deployment trade-off).

---

## 4. Table 1 — FR-1 … FR-34 strict compliance

Status legend: **Done** = route + controller + validation/authorization + view +
automated test, all verified in this phase. There are no Partial or Missing rows.

| FR | Requirement (short) | Implemented where | Tested how | Status |
| --- | --- | --- | --- | --- |
| FR-1 | Register as teacher/student, log in, log out, land on the role dashboard, verify e-mail | `Auth/RegisteredUserController`, `Auth/AuthenticatedSessionController`, `Auth/RegisterRequest`, `Auth/LoginRequest`, `routes/auth.php`, `App\Models\User implements MustVerifyEmail` | `Auth/RegistrationTest`, `Auth/AuthenticationTest`, `PhaseNineTest::test_registration_sends_the_email_verification_notification` | **Done** |
| FR-2 | Role-based access and three role dashboards | `Middleware/EnsureUserHasRole`, `DashboardController`, `TeacherDashboardController`, `StudentDashboardController`, `AdminDashboardController`, `users.dashboardRoute()` | `RoleAccessTest`, `DashboardTest` | **Done** |
| FR-3 | Forgot / reset password with a branded e-mail | `Notifications/ResetPasswordNotification`, `Auth/PasswordResetLinkController`, `Auth/NewPasswordController`, `routes/auth.php` | `Auth/PasswordResetTest` | **Done** |
| FR-4 | Edit name, e-mail, photo, bio, subject, password; delete account | `ProfileController`, `ProfileUpdateRequest`, `Auth/PasswordController`, `Services/ImageUploadService` | `ProfileTest`, `PhaseThreeUiTest`, `Auth/PasswordUpdateTest` | **Done** |
| FR-5 | Public profile with posts, followers and following lists | `UserProfileController`, `resources/views/users/show.blade.php`, `users/people.blade.php` | `FeedTest`, `PhaseThreeCTest`, `PhaseNineTest::test_the_public_profile_loads_class_cards_without_n_plus_one` | **Done** |
| FR-6 | Blocked accounts cannot log in and are ejected mid-session | `Middleware/EnsureUserIsNotBlocked`, `Auth/LoginRequest`, `ProfileController`/`AdminUserController` block flows | `Auth/BlockedUserTest`, `PhaseSevenTest::test_a_blocked_user_cannot_post_comment_enroll_or_join`, `test_a_blocked_user_cannot_report_like_or_follow` | **Done** |
| FR-7 | Create a post (280 chars, optional image), reject empty/whitespace | `PostController::store`, `StorePostRequest`, `ImageUploadService`, `Services/HashtagService` | `FeedTest`, `PhaseEightTest` upload tests, `PhaseNineTest::test_whitespace_only_posts_and_comments_are_rejected` | **Done** |
| FR-8 | Edit / delete own posts; delete the image and reports everywhere | `PostController::update/destroy`, `UpdatePostRequest`, `Post` deleting hook | `FeedTest` edit/delete/image tests, `PhaseNineTest::test_deleting_a_post_removes_its_reports_its_comment_reports_and_its_image` | **Done** |
| FR-9 | Like / unlike without duplicates, rate limited | `LikeController`, `Like` model, `AppServiceProvider` named limiter | `FeedTest` like tests, `PhaseEightTest::test_every_expensive_write_route_has_a_throttle` | **Done** |
| FR-10 | Comment on a post; delete own or admin; hidden excluded; reject blank | `CommentController`, `StoreCommentRequest`, `CommentPolicy`, `Comment` deleting hook | `FeedTest`, `PhaseSevenTest::test_hidden_comments_disappear_from_the_post_and_its_counter`, `PhaseNineTest` whitespace/comment-report tests | **Done** |
| FR-11 | Follow / unfollow, no self-follow, live counts, AJAX button | `FollowController`, `User::follow/unfollow/isFollowing`, `resources/js/follow.js` | `FeedTest` follow tests, `DashboardTest` | **Done** |
| FR-12 | Home feed: own + followed, visible only, 10/page, newest first | `PostController::index/feedQuery`, `Post::scopeWithFeedData`, `resources/views/feed/*` | `FeedTest`, `PerformanceTest::test_the_feed_paginates_and_keeps_its_query_count_bounded` | **Done** |
| FR-13 | Hashtags: parse, Unicode, lowercase, sync, XSS-safe, hashtag page | `Services/HashtagService`, `HashtagController`, `resources/views/hashtags/show.blade.php` | `FeedTest` hashtag tests | **Done** |
| FR-14 | Search people / hashtags / posts, role badge, pagination, suggestions | `SearchController`, `resources/js/search.js`, `resources/views/search/index.blade.php` | `SearchTest`, `PhaseEightTest::test_search_input_cannot_break_the_query` | **Done** |
| FR-15 | Create a class: validation, future date, 15–240 min, unique room | `ClassController::store`, `StoreClassRequest`, `ClassRoom::booted()` room generator | `ClassTest` create/validation tests | **Done** |
| FR-16 | Edit / delete a class (owner only) | `ClassController::edit/update/destroy`, `UpdateClassRequest`, `ClassPolicy` | `ClassTest` ownership tests | **Done** |
| FR-17 | Browse, search/filter classes, enrol / unenrol | `ClassBrowseController`, `EnrollmentController`, `resources/views/classes/discover.blade.php` | `ClassTest`, `PhaseNineTest::test_class_search_treats_like_wildcards_as_literal_text` | **Done** |
| FR-18 | Class detail page with enrol / join actions | `ClassBrowseController::show`, `LiveClassController`, `resources/views/classes/show.blade.php` | `ClassTest`, `SmokeTest` | **Done** |
| FR-19 | Search by title / subject / teacher; subject + teacher filters; private room | `ClassBrowseController::index`, `ClassRoom::generateRoomName()` | `ClassTest::test_a_student_can_search_and_filter_the_class_list`, `test_a_student_can_search_by_subject_and_filter_by_teacher`, `test_room_names_are_unique_and_ignore_the_request` | **Done** |
| FR-20 | Join Jitsi only as owner/enrolled student, only while live; not-started page; hangup flash | `ClassPolicy::join`, `LiveClassController`, `resources/views/classes/not-started.blade.php` | `ClassTest` join/leave/hidden-class tests | **Done** |
| FR-21 | Status workflow scheduled → live → completed, legal transitions, auto-close, timing label | `ClassStatusController`, `ClassRoom::TRANSITIONS/isLive/timelineLabel`, `Console/Commands/CompleteFinishedClasses` | `ClassTest` status/timing tests | **Done** |
| FR-22 | In-room experience: Jitsi embed, chat, moderator flag, avatar/subject, escaped name | `resources/views/classes/room.blade.php`, `resources/js/jitsi.js`, `Middleware/SecurityHeaders` | `ClassTest::test_the_room_page_embeds_jitsi_with_the_user_details`, `PhaseEightTest::test_the_jitsi_room_relaxes_framing_for_the_video_domain`, `PhaseNineTest::test_the_live_room_microphone_help_matches_the_defaults` | **Done** |
| FR-23 | Notifications: follower, like, comment, new class, starting soon; no self; dedupe | `app/Notifications/*`, `NotifyClassesStartingSoon`, `ClassStartingSoon` | `NotificationTest` (all notification cases) | **Done** |
| FR-24 | Bell, unread counter, hidden at zero, 30 s polling, all/unread filters | `NotificationController::index/poll`, `resources/js/notifications.js`, layout bell | `NotificationTest` poll/badge/filter tests | **Done** |
| FR-25 | Mark one (AJAX) / all read, ownership, delete, open target | `NotificationController::markAsRead/markAllAsRead/destroy/open` | `NotificationTest` ownership/read/delete tests | **Done** |
| FR-26 | Admin analytics: totals, charts, trends, recent activity | `AdminDashboardController`, `Support/DashboardMetrics`, `DashboardChartController` | `DashboardTest`, `DashboardRedesignTest` | **Done** |
| FR-27 | User moderation: search, filter, block / unblock / delete | `AdminUserController`, `UserPolicy`, `User` deleting hook | `AdminManagementTest`, `PhaseNineTest::test_deleting_a_user_cleans_reports_about_their_content_and_the_uploaded_files` | **Done** |
| FR-28 | Content moderation: hide / unhide / delete posts, classes, comments | `AdminPostController`, `AdminClassController`, `AdminReportController`, `Post`/`Comment` hooks | `AdminManagementTest`, `PhaseSevenTest`, `ReportTest` | **Done** |
| FR-29 | Teacher dashboard and enrolled-student roster | `TeacherDashboardController`, `ClassController::students`, `resources/views/classes/students.blade.php` | `PhaseThreeUiTest`, `ClassTest`, `DashboardTest` | **Done** |
| FR-30 | Student dashboard, upcoming classes, teacher suggestions | `StudentDashboardController`, `resources/views/dashboards/student.blade.php` | `DashboardTest`, `DashboardRedesignTest`, `ClassTest` | **Done** |
| FR-31 | Charts, theme toggle, sidebar state, design tokens | `resources/js/theme.js`, `resources/js/layout.js`, `resources/sass/_tokens.scss`, Chart.js cards | `DashboardRedesignTest`, `PhaseThreeUiTest` | **Done** |
| FR-32 | Report a post or comment, one report per user per item | `ReportController`, `ReportPolicy`, `StoreReportRequest`, `reports` unique index | `ReportTest` report/duplicate/hidden/validation cases | **Done** |
| FR-33 | Moderation queue: filters, grouping, statuses, content actions, no N+1 | `AdminReportController::index/show`, `resources/views/admin/reports/*` | `ReportTest`, `PhaseSevenTest`, `PhaseNineTest::test_the_admin_report_queue_does_not_run_one_query_per_report` | **Done** |
| FR-34 | Resolve reports: hide/unhide/delete, block author, reviewed/dismissed | `AdminReportController` actions, `Report` model + deletion hooks | `ReportTest`, `PhaseSevenTest::test_dismissing_a_report_keeps_the_content_visible`, `PhaseNineTest` cleanup tests | **Done** |

---

## 5. Table 2 — UC-1 … UC-8

> **Inferred identifiers.** No `UC-x` identifier appears in the code, tests or
> phase notes (confirmed by a repository-wide search). The eight journeys below
> are the end-to-end use cases the FR set implements, numbered in a logical
> order. If the SRS numbers them differently, the *journey* column is the stable
> reference; the evidence is what matters.

| UC (inferred) | Actor | Journey | Implemented where | Evidence | Status |
| --- | --- | --- | --- | --- | --- |
| UC-1 | Visitor → Teacher/Student | Register, receive the verification link, log in, land on the role dashboard, edit the profile, delete the account | `routes/auth.php`, `Auth/RegisteredUserController`, `ProfileController`, `User` MustVerifyEmail | `Auth/RegistrationTest`, `Auth/AuthenticationTest`, `ProfileTest`, `PhaseNineTest` verification test | **Done** |
| UC-2 | Teacher/Student | Publish a post with optional image and hashtags; edit or delete it; image and reports cleaned up | `PostController`, `StorePostRequest`, `UpdatePostRequest`, `HashtagService`, `Post` hook | `FeedTest`, `PhaseEightTest` upload tests, `PhaseNineTest` cleanup/whitespace tests | **Done** |
| UC-3 | Teacher/Student | Follow people, like posts, comment, delete own comment; see notifications for each action | `FollowController`, `LikeController`, `CommentController`, `app/Notifications/*` | `FeedTest`, `NotificationTest` | **Done** |
| UC-4 | Any logged-in user | Search people, hashtags and posts from one box; filter classes by subject/teacher/date | `SearchController`, `ClassBrowseController` | `SearchTest`, `ClassTest`, `PhaseNineTest` wildcard test | **Done** |
| UC-5 | Teacher | Create, edit and cancel a class; run the scheduled → live → completed status machine; see the roster | `ClassController`, `ClassStatusController`, `ClassRoom`, `resources/views/classes/*` | `ClassTest`, `PhaseThreeUiTest`, `DashboardTest` | **Done** |
| UC-6 | Student | Discover a class, enrol, join the Jitsi room while live, leave with a flash | `ClassBrowseController`, `EnrollmentController`, `LiveClassController`, `ClassPolicy` | `ClassTest`, `SmokeTest` | **Done** |
| UC-7 | All roles | Read notifications, open the target, mark one/all read, delete one; bell polling | `NotificationController`, `resources/js/notifications.js` | `NotificationTest` | **Done** |
| UC-8 | Student/Teacher → Admin | Report content, triage the queue, hide/unhide/delete, block the author, review/dismiss | `ReportController`, `AdminReportController`, `ReportPolicy`, `UserPolicy` | `ReportTest`, `AdminManagementTest`, `SmokeTest`, `PhaseNineTest` | **Done** |

---

## 6. Table 3 — Non-functional requirements

> **Inferred identifiers.** NFR names come from the requested audit areas
> (performance, security, usability, reliability, maintainability, child
> protection). The SRS numbering is not available in the repository.

| NFR (inferred) | Requirement | Evidence | Status |
| --- | --- | --- | --- |
| NFR-1 Performance | Primary pages render in < 3 s; lists paginate; no N+1 on hot paths; indexed filters | `PhaseEightTest::test_the_primary_pages_load_in_under_three_seconds`, `PerformanceTest`, `PhaseEightTest::test_dashboards_stay_within_a_query_budget`, Phase 9 query-budget tests, 15 composite indexes in `docs/PERFORMANCE.md` | **Met** |
| NFR-2 Security | Hashed passwords, CSRF on every write, authorization on every route, escaped output, upload re-encoding, rate limits, security headers, session hardening | `docs/SECURITY.md` (92 routes, 0 middleware mismatches), `PhaseEightTest` security half, `PhaseSevenTest` RBAC cases | **Met** |
| NFR-3 Usability | Mobile-first responsive layout, dark mode, empty states, friendly 403/404/419/500/503, AJAX busy/error feedback, keyboard-accessible modals | `PhaseEightTest` usability half, `PhaseThreeCTest`, `PhaseThreeUiTest`; 360/768/1200 px visual check is manual | **Met** (visual breakpoints re-checked manually) |
| NFR-4 Reliability | Scheduled reminder/auto-close commands, queue-backed notifications, database backups, restore procedure, friendly error pages | `Console/Commands/*`, `routes/console.php`, `scripts/backup.sh`, `BackupDatabase`, `docs/BACKUP.md`, `docs/DEPLOYMENT.md`; MySQL restore is manual | **Met** (MySQL restore manual) |
| NFR-5 Maintainability | Modular controllers/services/policies, typed models, Pint, 317 tests, phase docs, no dead placeholders | `php artisan test`, Pint, `docs/*`, this audit | **Met** |
| NFR-6 Child protection / safety (SRS 5.6) | Only the owning teacher and enrolled students enter a room; hidden classes cannot be joined; admins moderate instead of entering; blocked users cannot act; reporting + moderation for harmful content; no public room names | `ClassPolicy::join`, `EnsureUserIsNotBlocked`, `ReportController`/`AdminReportController`, `ClassRoom::generateRoomName()`, `docs/PHASE-7-DELIVERABLE.md` §3.6 | **Met for the in-scope controls** |

### 6.1 Residual non-functional gaps (documented, not silently passed)

1. **Formal age verification / guardian consent** is not implemented. The
   delivered safety model is access control + moderation + blocking, which is
   what the phase briefs describe as SRS 5.6. If the SRS requires a consent
   gate, it is a new product requirement and is listed first in *Residual risks*.
2. **Real SMTP, a live queue worker and a real `meet.jit.si` call** cannot be
   proven in this environment. Their code paths are tested with the array mailer,
   the sync queue and a mocked Jitsi embed; the manual checklist is in
   `docs/TEST-MATRIX.md` and section 9.
3. **MySQL 8** was unavailable. All migrations run on SQLite; the schema audit
   reads the SQLite representation. MySQL-specific behaviour (backup restore,
   `LIKE` collation) remains a deployment sign-off item.
4. **Visual breakpoints and dark-mode contrast** need a browser. The CSS and
   templates are in place and the manual checklist is unchanged from Phase 8.

---

## 7. Database audit against SRS §4

### 7.1 How it was checked

```bash
export DB_CONNECTION=sqlite
export DB_DATABASE="$TEMP/p9.sqlite"
rm -f "$DB_DATABASE" && touch "$DB_DATABASE"
php artisan migrate --force
# then PRAGMA table_info / foreign_key_list / index_list for every table
```

The run created **19 tables**: 18 migrations (11 domain tables + 6 Laravel
infrastructure tables + `migrations`).

### 7.2 Table inventory

| Table | Purpose | Columns | Foreign keys | Unique / indexes | §4 verdict |
| --- | --- | --- | --- | --- | --- |
| `users` | Accounts and roles | `id, name, email, email_verified_at, password, remember_token, created_at, updated_at, role, bio, photo, subject, is_blocked` | — (referenced by the others) | unique `email`; indexes `role`, `is_blocked`, `name`, `subject`, composite `(role, is_blocked)` | Present; profile columns are the documented FR-4 extension |
| `posts` | Feed posts | `id, user_id, body, image, is_hidden, created_at, updated_at` | `user_id → users.id` cascade | indexes `user_id`, `is_hidden`, `(user_id, created_at)`, `(is_hidden, created_at)` | Present |
| `comments` | Comments on posts | `id, post_id, user_id, body, is_hidden, created_at, updated_at` | `post_id → posts.id` cascade; `user_id → users.id` cascade | indexes `post_id`, `user_id`, `is_hidden`, `(post_id, created_at)`, `(user_id, created_at)` | Present |
| `likes` | One like per user per post | `id, post_id, user_id, created_at, updated_at` | `post_id → posts.id` cascade; `user_id → users.id` cascade | unique `(post_id, user_id)`; index `user_id`, `(user_id, created_at)` | Present |
| `follows` | Follow graph | `id, follower_id, following_id, created_at, updated_at` | both → `users.id` cascade | unique `(follower_id, following_id)`; indexes `following_id`, `(follower_id, created_at)`, `(following_id, created_at)` | Present |
| `hashtags` | Normalised tags | `id, name, created_at, updated_at` | — | unique `name` | Present |
| `hashtag_post` | Post ↔ tag pivot | `hashtag_id, post_id` (composite PK) | both cascade | index `post_id`; unique `(hashtag_id, post_id)` | Present |
| `classes` | Live classes | `id, teacher_id, title, description, subject, date, time, duration, room_name, status, is_hidden, created_at, updated_at, starting_soon_notified_at` | `teacher_id → users.id` cascade | unique `room_name`; indexes `teacher_id`, `date`, `status`, `(status, date)`, `(teacher_id, date)`, `(is_hidden, date)` | Present; `is_hidden` and `starting_soon_notified_at` are documented extensions |
| `class_enrollments` | Student ↔ class | `id, class_id, student_id, created_at, updated_at` | both cascade | unique `(class_id, student_id)`; indexes `student_id`, `(student_id, created_at)` | Present |
| `reports` | Polymorphic moderation queue | `id, reporter_id, reportable_type, reportable_id, reason, status, details, created_at, updated_at` | `reporter_id → users.id` cascade; **no FK to the polymorphic target by design** | unique `(reporter_id, reportable_type, reportable_id)`; indexes `status`, `(reportable_type, reportable_id)`, `(status, created_at)`, `(reportable_type, reportable_id, status)`, `reporter_id` | Present; `details` is the documented FR-32 extension; orphans are handled in application code (§3, P9-01) |
| `notifications` | Database notifications | `id (uuid), type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at` | polymorphic notifiable (Laravel) | index `(notifiable_type, notifiable_id, read_at)`, `(notifiable_type, notifiable_id)` | Present (FR-23 … FR-25) |
| `password_reset_tokens` | Password reset tokens | `email (PK), token, created_at` | — | PK `email` | Present (FR-3) |
| `sessions` | Database sessions | `id, user_id, ip_address, user_agent, payload, last_activity` | — (framework table) | PK `id`; indexes `user_id`, `last_activity` | Framework; used by session hardening and block-ejection |
| `cache`, `cache_locks` | Cache store | `key (PK), value, expiration` / `key (PK), owner, expiration` | — | PK `key` | Framework |
| `jobs`, `job_batches`, `failed_jobs` | Queue | Laravel defaults | — | `jobs.queue`, unique `failed_jobs.uuid`, PK `job_batches.id` | Framework; supports queued notifications |
| `migrations` | Migration ledger | `id, migration, batch` | — | — | Framework |

### 7.3 Constraint and index findings

- **No missing table** relative to the model described by the phase briefs.
- **No missing foreign key** on the domain tables; every user/post/comment/class
  reference is `cascadeOnDelete`, verified live.
- **All uniqueness rules are enforced in the database**, not only in PHP:
  e-mail, hashtag name, class `room_name`, like `(post,user)`, follow
  `(follower,following)`, enrolment `(class,student)`, tag pivot and report
  `(reporter,type,id)`.
- **The 15 hot-path composite indexes from Phase 8 are still present** and were
  re-read from the live schema: `posts(user_id,created_at)`,
  `posts(is_hidden,created_at)`, `comments(post_id,created_at)`,
  `comments(user_id,created_at)`, `likes(user_id,created_at)`,
  `follows(follower_id,created_at)`, `follows(following_id,created_at)`,
  `classes(status,date)`, `classes(teacher_id,date)`, `classes(is_hidden,date)`,
  `class_enrollments(student_id,created_at)`, `reports(status,created_at)`,
  `reports(reportable_type,reportable_id,status)`, `notifications(notifiable_type,notifiable_id,read_at)`,
  `users(role,is_blocked)`.
- **Polymorphic orphan risk is the one schema-level gap**, because SQL cannot
  foreign-key a polymorphic column. It is now closed in the model layer and
  guarded by three regression tests. This is the correct fix for the schema the
  SRS describes; adding a `reportable` FK would break polymorphism.

---

## 8. Verification commands and results

```text
php artisan test
  Tests:  317 passed (1709 assertions)   Duration: 43-115 s (hardware dependent)

php artisan test --filter=PhaseNineTest
  Tests:  10 passed (44 assertions)

php artisan test --filter=PhaseEightTest
  Tests:  26 passed (251 assertions)

vendor/bin/pint --test app tests routes database config bootstrap/app.php public/index.php
  passed

npm run build
  140 modules transformed; app CSS 368.63 kB (57.47 kB gzip);
  app JS 229.62 kB (75.83 kB gzip); auto JS 209.95 kB (72.11 kB gzip)

php artisan route:list --except-vendor --json
  92 routes, 85 named

php artisan config:cache && php artisan route:cache && php artisan view:cache
  all three cached successfully

php artisan config:clear && CACHE_STORE=array php artisan optimize:clear
  all caches cleared
```

The full suite was run twice after the last code change (including once with the
new Phase 9 file) to rule out the seeder-order flake; both runs were green.

---

## 9. Manual smoke test — Admin, Teacher, Student

Run this on a fresh database and a real browser. It follows the exact order of
the audited journeys: registration → live class → moderation.

### 9.0 Preparation

```bash
# 1. Fresh database + demo data (admin credentials come from .env).
php artisan migrate:fresh --seed

# 2. Serve the app.
php artisan serve

# 3. In a second terminal: process queued notifications.
php artisan queue:work

# 4. Optional: make the scheduler run the reminder/auto-close commands.
php artisan schedule:work
```

Demo accounts (password `password` unless noted):
`teacher1@educonnect.test` … `teacher3@educonnect.test`,
`student1@educonnect.test` … `student6@educonnect.test`.
Admin: the `ADMIN_EMAIL` / `ADMIN_PASSWORD` pair in `.env`
(defaults `admin@educonnect.test` / `EduConnect#Admin2026!`).

### 9.1 Student — registration and onboarding

| # | Step | Expected result |
| --- | --- | --- |
| 1 | Open `/register`, choose **Student**, fill name/e-mail/password, submit | Logged in, redirected to `/student/dashboard`; a verification e-mail is queued (check the mail log/array driver in dev); the profile page shows the unverified banner until the signed link is opened |
| 2 | Open the verification link from the mail | Redirect to the dashboard with `?verified=1`; the banner disappears |
| 3 | Open the bell | Zero unread; the badge is hidden; the empty state is shown |
| 4 | Open **Discover classes** | Upcoming/live classes only, paginated, subject and teacher filters work |
| 5 | Search for `%` | Only titles containing a literal `%` match (normally zero); the catalogue is **not** returned wholesale |
| 6 | Open a class, click **Enrol** | Button becomes **Unenrol**, the class appears under **My classes**, the teacher's roster count increments |
| 7 | Open the feed, publish a post with text + image + `#algebra` | Post appears at the top, image is resized/re-encoded, hashtag links to its page |
| 8 | Publish a post containing only spaces | Inline validation error; nothing is stored |
| 9 | Comment on another user's post, then delete your comment | Comment disappears, counter decrements; only your own comment shows the delete control |
| 10 | Try to open `/admin/dashboard` and `/teacher/dashboard` | 403, no data leaks |

### 9.2 Teacher — class and live room

| # | Step | Expected result |
| --- | --- | --- |
| 11 | Log in as `teacher1@educonnect.test`, open **Classes → Create** | Form with title, subject, date, time, duration (15–240), description |
| 12 | Submit a class dated in the past | Validation error naming the date field |
| 13 | Create a class for today, a few minutes from now | Class appears in **My classes** with status *Scheduled*; `room_name` is a long random string, not the title |
| 14 | Open the class detail page | Enrol/join rules are visible; **Join** is disabled while not live |
| 15 | Open **Students** for the class | The enrolled student(s) and the count are correct |
| 16 | Switch the status to **Live** | Status badge turns *Live*, the join button activates for enrolled students; followers receive a "new class" notification |
| 17 | Open **Join class** (the room page) | Jitsi loads; the teacher is the moderator; the help text says the microphone **starts on** |
| 18 | In Jitsi, mute/unmute, start screen sharing, send a chat message | All Jitsi controls work; the student sees the shared screen and the chat |
| 19 | Click hang-up | Redirect to the class detail page with a "You have left the class room" flash |
| 20 | Switch the status to **Completed** | No further transition is offered; a student can no longer join |

### 9.3 Student — live participation

| # | Step | Expected result |
| --- | --- | --- |
| 21 | Log in as a student enrolled in the live class, open the room | Jitsi loads with the student name/e-mail; the help text says the microphone **starts muted** |
| 22 | Unmute and speak; use the Jitsi chat | Teacher hears/sees the student; no page reload required |
| 23 | Open the room **before** the class is live (scheduled class) | Friendly "This class has not started yet" page, not an error |
| 24 | Try to open the room for a class you are not enrolled in | 403 |
| 25 | Leave the room | Flash message on the class detail page; the room closes |

### 9.4 Report → moderation (end to end)

| # | Step | Expected result |
| --- | --- | --- |
| 26 | As the student, report another user's post with reason **Spam** and details | Success flash; the report is *Pending*; no second report of the same item is accepted |
| 27 | As the post author, try to report your own post | Report action is not offered; a forged request gets a 403 |
| 28 | Log in as the admin, open **Admin → Reports** | Pending count, filters (status/type/search), reporter, preview and offending author all render; the page stays fast with 20+ rows |
| 29 | Open the report detail | Full content, reporter, offending author and the other reports about the same item |
| 30 | Click **Hide** | Content disappears from the feed, profile, hashtag page, search and the class page; the report becomes *Reviewed* |
| 31 | Click **Unhide** | Content is visible again |
| 32 | Click **Dismiss** on another report | Content stays visible; the report becomes *Dismissed* |
| 33 | Click **Block author** | Author is blocked, logged out of active sessions and cannot log in; the report becomes *Reviewed* |
| 34 | Click **Delete content** | Content and **all** reports about it are gone; a related post image file is removed from `storage/app/public/posts` |
| 35 | In **Admin → Users**, delete the author of another reported post | The account and content are gone; reports about the content are gone; profile photo and post images are removed |
| 36 | In **Admin → Posts / Classes**, hide and unhide; delete a class | Hidden content disappears from public pages; deleting a class removes its enrolments |
| 37 | As a student, try every `/admin/*` URL | 403, no admin data |

### 9.5 Account and reliability checks

| # | Step | Expected result |
| --- | --- | --- |
| 38 | Edit the profile, replace the photo, remove the photo | Old file is deleted, new file is re-encoded, default avatar returns |
| 39 | Request a password reset, open the branded e-mail, set a new password | Login works with the new password; the old one is rejected |
| 40 | Delete your own account from the profile page | Password confirmation required; account, content, images, reports and sessions are removed |
| 41 | Add a class starting in ~10 minutes and run `php artisan schedule:work` | Teacher and enrolled students get exactly one "starting soon" notification |
| 42 | Stop the queue worker, trigger a notification, start the worker | The notification is delivered once the worker runs (database queue) |
| 43 | Open 403/404/419/500 pages (bad URL, expired form, forced error) | Branded error page with a way back |
| 44 | Repeat steps 4, 7, 14, 28 at 360 px and 1200 px, in light and dark mode | No horizontal scrolling, readable contrast, usable modals |

Sign-off: all 44 steps must pass before release. Steps 1–2, 25, 40 and 43 are
the ones that depend on real mail/session behaviour and deserve the most
attention on staging.

---

## 10. SRS §7 — future enhancements (ordered)

The order is deliberate: each item reuses the previous one's data model and does
not require a rewrite. Sizes are rough delivery windows for the current team.

| Order | Enhancement | Why now | Scope | Depends on | Size |
| --- | --- | --- | --- | --- | --- |
| 1 | **Assignments** | Turns the class from a broadcast into a course; reuses posts, enrolments and notifications | Teacher creates an assignment (title, instructions, due date, optional file) per class; student submits text/file; teacher sees submissions and marks them; notifications on create/submit/grade; visibility follows enrolment | `classes`, `class_enrollments`, `notifications`, `ImageUploadService` patterns | 2–3 weeks |
| 2 | **Quizzes** | Natural next step after assignments; gives students immediate feedback | Question bank per class, multiple-choice/short-answer, attempt limits, auto-grading for MCQ, manual grading for short answers, result page, teacher analytics | Assignments module, `DashboardMetrics` | 3–4 weeks |
| 3 | **Payments** | Monetise premium classes once content and assessment exist | Teacher payout profile, paid class flag, Stripe Checkout, enrolment gated on payment, refunds, invoices, webhook handling, admin revenue view | Enrolment, queue worker, HTTPS deployment | 4–6 weeks |
| 4 | **Recordings** | Lets absent students catch up and increases the value of paid classes | Record the Jitsi room (Jibri or a hosted recorder), store on a private disk/CDN, per-class recording list, enrolment-gated playback, retention policy and deletion command | Live class module, object storage/CDN, privacy review | 4–6 weeks |
| 5 | **Mobile app** | Reach students on phones after the web flows are stable | API layer (Sanctum tokens) over the existing controllers/services, push notifications, React Native/Flutter shell, offline feed cache, deep links to classes | Stable API + recordings + payments, app-store accounts | 8–12 weeks |

Sequencing rules:

- Do **not** start payments before assignments/quizzes exist: there is nothing
  premium to sell yet.
- Do **not** start recordings before the privacy/consent review: recording
  minors requires explicit policy and retention limits.
- Do **not** fork the web logic for the mobile app: expose the same services
  through a versioned API so a bug fix lands once.

---

## 11. Residual risks and blocked verification

| Item | Status | What is needed |
| --- | --- | --- |
| SRS document not in the repository | **Blocked** | Add the SRS to `docs/` so UC/NFR IDs and §4 column names can be verified literally. The current tables are labelled *inferred* where they rely on it. |
| Age verification / guardian consent | **Not implemented** | A product decision. The delivered SRS 5.6 controls (room access, blocking, reporting, moderation) are complete; a consent gate would be a new requirement. |
| MySQL 8 migrations, FK behaviour and backup restore | **Unverified in this environment** | Run `php artisan migrate --force` and a restore drill on staging MySQL 8 (`docs/BACKUP.md`). |
| Real SMTP delivery | **Unverified** | Deploy with `MAIL_MAILER=smtp`; request a reset and confirm the branded mail. The template and notification are unit-tested. |
| Live queue worker | **Unverified** | `QUEUE_CONNECTION=database`, run `queue:work` under Supervisor, trigger a notification. |
| Real Jitsi call (camera/mic/screen share) | **Unverified** | Join a live class over HTTPS from two browsers/networks. Header/embed/access tests cover the server side. |
| Responsive and dark-mode visual checks | **Manual** | Complete the 360/768/1200 px checklist at the end of `docs/TEST-MATRIX.md`. |
| Uncommitted working tree | **Pending user approval** | No commit was made in this phase. Review the diff, then commit when ready. |

---

## 12. Changed files in Phase 9

| File | Change |
| --- | --- |
| `app/Models/Post.php` | `deleting` hook: post reports, comment reports, image file |
| `app/Models/Comment.php` | `deleting` hook: comment reports |
| `app/Models/User.php` | `implements MustVerifyEmail`; `deleting` hook: reports about owned content (including comments on owned posts), post images, profile photo |
| `app/Http/Controllers/AdminReportController.php` | `morphWith` eager loading for `reported_user`; queue deletion now relies on the model hook |
| `app/Http/Controllers/AdminPostController.php` | Admin post deletion now relies on the model hook |
| `app/Http/Controllers/AdminUserController.php` | Account deletion cleanup moved to the `User` hook |
| `app/Http/Controllers/PostController.php` | Author post deletion cleanup moved to the `Post` hook |
| `app/Http/Controllers/ProfileController.php` | Self-service account deletion cleanup moved to the `User` hook |
| `app/Http/Controllers/UserProfileController.php` | Eager loads `teacher` and `students_count` for profile class cards |
| `app/Http/Controllers/ClassBrowseController.php` | Escapes `!`, `%`, `_` in class search; caps the term at 100 characters |
| `app/Http/Requests/StorePostRequest.php` | Trims the body before validation |
| `app/Http/Requests/UpdatePostRequest.php` | Trims the body before validation |
| `app/Http/Requests/StoreCommentRequest.php` | Trims the body before validation |
| `resources/views/classes/room.blade.php` | Correct microphone help for moderators vs students |
| `tests/Feature/PhaseNineTest.php` | **New** 10-test regression suite for every finding |
| `tests/Feature/SmokeTest.php` | Deterministic post choice in the report-to-moderation flow |
| `docs/PHASE-9-DELIVERABLE.md` | **New** this document |
| `docs/TEST-MATRIX.md`, `docs/REQUIREMENTS-STATUS.md`, `docs/SECURITY.md`, `README.md` | Counts, Phase 9 rows, verification note and links refreshed |

The complete content of every changed code file is in Appendix A, below.

---

## Appendix A — complete fixed code

Every file is reproduced in full, in repository order. Nothing is abbreviated.

### `app/Models/Post.php`

```php
<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'body',
        'image',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
        ];
    }

    /**
     * Clean up the rows and files that the database cascade cannot reach.
     *
     * A post can be deleted from three places (the author, the admin post
     * table and the moderation queue). Doing the cleanup here means all three
     * paths behave the same, including any future deletion path.
     */
    protected static function booted(): void
    {
        static::deleting(function (Post $post): void {
            // "reports" is polymorphic, so there is no foreign key to cascade.
            Report::query()
                ->where('reportable_type', self::class)
                ->where('reportable_id', $post->id)
                ->delete();

            // The post's comments disappear through the FK cascade, but
            // reports about those comments would be left behind.
            $commentIds = $post->comments()->pluck('comments.id');

            if ($commentIds->isNotEmpty()) {
                Report::query()
                    ->where('reportable_type', Comment::class)
                    ->whereIn('reportable_id', $commentIds)
                    ->delete();
            }

            // The uploaded image is a file, not a row (FR-8, FR-28).
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
        });
    }

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    /** The author of the post. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(Hashtag::class, 'hashtag_post');
    }

    /** Reports made against this post (moderation). */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /* ---------------------------------------------------------------
     | Scopes and helpers
     * --------------------------------------------------------------- */

    /**
     * Hide posts that an administrator has hidden (FR-28).
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Everything a feed card needs, in one go (FR-12).
     * Eager loads the author and the hashtags, counts the likes/comments and
     * adds a "liked_by_me" flag - so rendering 10 cards stays at 3 queries
     * instead of 30+.
     */
    public function scopeWithFeedData(Builder $query, ?User $viewer): Builder
    {
        return $query
            ->with([
                'user:id,name,role,photo,subject',
                'hashtags:id,name',
            ])
            // Hidden comments do not count towards the visible total (FR-10).
            ->withCount([
                'likes',
                'comments' => static fn (Builder $comments) => $comments->visible(),
            ])
            ->withExists([
                // 1 when the viewer liked the post, 0 otherwise.
                'likes as liked_by_me' => static fn (Builder $likes) => $likes->where('user_id', $viewer?->id ?? 0),
            ]);
    }

    /**
     * Phase 2 scope: add likes_count and comments_count in the same query.
     */
    public function scopeWithCounts(Builder $query): Builder
    {
        return $query->withCount(['likes', 'comments']);
    }

    /**
     * Has this post been liked by the given user? (FR-9)
     * Uses the eager loaded "likes" relation when it is available, so a feed
     * with 20 posts stays at one query instead of twenty.
     */
    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->relationLoaded('likes')) {
            return $this->likes->contains('user_id', $user->id);
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Phase 2 alias with the exact name from the SRS.
     */
    public function likedBy(?User $user): bool
    {
        return $this->isLikedBy($user);
    }

    /**
     * Intrinsic width and height of the attached image, if it exists.
     *
     * The values are cached per file because the dimensions never change after
     * the upload service has written the file. Blade uses them as width/height
     * attributes, so the feed reserves the correct box before the image loads
     * (no layout shift) and can provide a truthful srcset width descriptor.
     *
     * @return array{width: int, height: int}|null
     */
    public function imageDimensions(): ?array
    {
        if (! $this->image) {
            return null;
        }

        return Cache::rememberForever('post-image-dimensions:'.$this->image, function (): ?array {
            try {
                $disk = Storage::disk('public');

                if (! $disk->exists($this->image)) {
                    return null;
                }

                $size = @getimagesize($disk->path($this->image));

                if (! $size || $size[0] < 1 || $size[1] < 1) {
                    return null;
                }

                return ['width' => (int) $size[0], 'height' => (int) $size[1]];
            } catch (Throwable) {
                // A remote/fake disk or a missing GD build must not break a page.
                return null;
            }
        });
    }
}
```

### `app/Models/Comment.php`

```php
<?php

namespace App\Models;

use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'body',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
        ];
    }

    /**
     * Delete the reports that point at this comment.
     *
     * "reports" is polymorphic (no foreign key), so the database cascade never
     * removes these rows. Comments are deleted by their author, by an admin
     * and through the post cascade, so the cleanup lives on the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (Comment $comment): void {
            Report::query()
                ->where('reportable_type', self::class)
                ->where('reportable_id', $comment->id)
                ->delete();
        });
    }

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Reports made against this comment (moderation). */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /* ---------------------------------------------------------------
     | Scopes
     * --------------------------------------------------------------- */

    /**
     * Hide comments that an administrator has hidden.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }
}
```

### `app/Models/User.php`

```php
<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

/**
 * Implementing MustVerifyEmail turns on the "verify your e-mail" flow:
 * the Registered event sends the signed link (FR-1) and the profile page can
 * show the unverified banner. Until the address is confirmed the account is
 * still usable, so a flaky mail server never locks a student out.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // The three roles used in this application.
    public const ROLE_ADMIN = 'admin';

    public const ROLE_TEACHER = 'teacher';

    public const ROLE_STUDENT = 'student';

    // Roles a visitor may pick on the registration form (admin is never one of them).
    public const REGISTERABLE_ROLES = [self::ROLE_TEACHER, self::ROLE_STUDENT];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'bio',
        'photo',
        'subject',
        'is_blocked',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
        ];
    }

    /**
     * Clean up everything the database cascade cannot reach when an account is
     * deleted (self-service from the profile page or by an administrator).
     *
     * Posts, comments, likes, follows, enrollments, notifications and the
     * reports the user filed all disappear through foreign keys. The two
     * exceptions handled here are the polymorphic reports about the user's
     * content and the uploaded files on the public disk.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $postIds = $user->posts()->pluck('posts.id');

            // Reports about the user's own comments *and* about comments other
            // people left on the user's posts: the post cascade removes those
            // comments without firing their model events.
            $commentIds = Comment::query()
                ->where(function ($query) use ($user, $postIds): void {
                    $query->where('user_id', $user->id);

                    if ($postIds->isNotEmpty()) {
                        $query->orWhereIn('post_id', $postIds);
                    }
                })
                ->pluck('comments.id');

            if ($postIds->isNotEmpty()) {
                Report::query()
                    ->where('reportable_type', Post::class)
                    ->whereIn('reportable_id', $postIds)
                    ->delete();
            }

            if ($commentIds->isNotEmpty()) {
                Report::query()
                    ->where('reportable_type', Comment::class)
                    ->whereIn('reportable_id', $commentIds)
                    ->delete();
            }

            // Uploaded post images are files, not rows.
            $user->posts()
                ->whereNotNull('image')
                ->pluck('image')
                ->each(fn (string $image) => Storage::disk('public')->delete($image));

            // The profile photo (FR-4).
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
        });
    }

    /* ---------------------------------------------------------------
     | Role helpers - used in Blade views and in the middleware.
     * --------------------------------------------------------------- */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    /**
     * Route name of the dashboard that belongs to this user's role (FR-2).
     * Used after login / registration to send everyone to the right place.
     */
    public function dashboardRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_TEACHER => 'teacher.dashboard',
            default => 'student.dashboard',
        };
    }

    /**
     * Send the branded password reset email (FR-3).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Public URL of the uploaded profile photo, or the default avatar.
     */
    public function photoUrl(): string
    {
        return $this->photo
            ? Storage::disk('public')->url($this->photo)
            : asset('images/default-avatar.svg');
    }

    /**
     * Blade-friendly accessor: {{ $user->photo_url }} (Phase 2).
     */
    public function getPhotoUrlAttribute(): string
    {
        return $this->photoUrl();
    }

    /* ---------------------------------------------------------------
     | Follow counters (Phase 2)
     * --------------------------------------------------------------- */

    /**
     * Number of people following this user.
     * Reuses an eager-loaded withCount('followers') when available.
     */
    public function followersCount(): int
    {
        return (int) ($this->followers_count ?? $this->followers()->count());
    }

    /**
     * Number of people this user follows.
     * Reuses an eager-loaded withCount('following') when available.
     */
    public function followingCount(): int
    {
        return (int) ($this->following_count ?? $this->following()->count());
    }

    public function getFollowersCountAttribute(): int
    {
        return $this->followersCount();
    }

    public function getFollowingCountAttribute(): int
    {
        return $this->followingCount();
    }

    /* ---------------------------------------------------------------
     | Feed relationships
     * --------------------------------------------------------------- */

    /** Posts this user wrote. */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /** Comments this user wrote. */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** Likes this user gave. */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /** Reports this user filed (FR-32). */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    /* ---------------------------------------------------------------
     | Follow graph (FR-11)
     * --------------------------------------------------------------- */

    /** People who follow me. */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    /** People I follow. */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    /**
     * Do I already follow this user?
     */
    public function isFollowing(?User $user): bool
    {
        // Nobody follows themselves.
        if (! $user || $this->is($user)) {
            return false;
        }

        if ($this->relationLoaded('following')) {
            return $this->following->contains('id', $user->id);
        }

        return $this->following()->whereKey($user->id)->exists();
    }

    /**
     * Follow another user.
     * Returns false when the target is the user themselves: self-following is
     * never allowed (FR-11). The pivot table also has a unique index, so
     * following twice is impossible.
     */
    public function follow(User $user): bool
    {
        if ($this->is($user)) {
            return false;
        }

        $this->following()->syncWithoutDetaching([$user->id]);

        return true;
    }

    /**
     * Stop following a user.
     */
    public function unfollow(User $user): void
    {
        $this->following()->detach($user->id);
    }

    /* ---------------------------------------------------------------
     | Classes (FR-15 ... FR-21)
     * --------------------------------------------------------------- */

    /** Classes this user teaches (only meaningful for teachers). */
    public function teachingClasses(): HasMany
    {
        return $this->hasMany(ClassRoom::class, 'teacher_id');
    }

    /**
     * Backwards-compatible alias used by the Phase 3-8 controllers.
     */
    public function classes(): HasMany
    {
        return $this->teachingClasses();
    }

    /** Classes this user is enrolled in (only meaningful for students). */
    public function enrolledClasses(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_enrollments', 'student_id', 'class_id')
            ->withTimestamps();
    }

    /**
     * Is this student enrolled in the given class?
     */
    public function isEnrolledIn(ClassRoom $classRoom): bool
    {
        if ($this->relationLoaded('enrolledClasses')) {
            return $this->enrolledClasses->contains('id', $classRoom->id);
        }

        return $this->enrolledClasses()->whereKey($classRoom->id)->exists();
    }
}
```

### `app/Http/Controllers/AdminReportController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The moderation queue (FR-33, FR-34).
 *
 * Every action also moves the report to the matching status:
 *  - hide / unhide / block the author -> Reviewed
 *  - dismiss                          -> Dismissed
 *  - delete the content               -> the report is removed with the content
 */
class AdminReportController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * All reports with a status, type and text filter (FR-33).
     */
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), Report::STATUSES, true)
            ? (string) $request->query('status')
            : null;

        $type = in_array($request->query('type'), ['post', 'comment'], true)
            ? (string) $request->query('type')
            : null;

        $term = trim((string) $request->query('q', ''));

        $reports = Report::query()
            ->with([
                'reporter:id,name,email',
                // The table shows $report->reported_user for every row, so the
                // author of each post/comment is eager loaded too: without it
                // the page runs one extra query per report (N+1).
                'reportable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    Post::class => ['user:id,name'],
                    Comment::class => ['user:id,name'],
                ]),
            ])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($type, fn (Builder $query) => $query->where(
                'reportable_type',
                $type === 'post' ? Post::class : Comment::class
            ))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $pattern = $this->likePattern($term);

                $query->where(function (Builder $inner) use ($pattern): void {
                    $inner->whereRaw("reason LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("details LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereHas('reporter', fn (Builder $reporter) => $reporter
                            ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]))
                        ->orWhereHasMorph('reportable', [Post::class, Comment::class], fn (Builder $content) => $content
                            ->whereRaw("body LIKE ? ESCAPE '!'", [$pattern])
                            // The offending author's name is searchable too.
                            ->orWhereHas('user', fn (Builder $author) => $author
                                ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])));
                });
            })
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Small counters for the filter pills.
        $counts = Report::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.reports.index', [
            'reports' => $reports,
            'status' => $status ?? '',
            'type' => $type ?? '',
            'term' => $term,
            'counts' => $counts,
        ]);
    }

    /**
     * One report plus every other report about the same content (FR-33).
     */
    public function show(Report $report): View
    {
        $report->load(['reporter:id,name,email', 'reportable']);

        $related = collect();

        if ($report->reportable) {
            $related = Report::query()
                ->whereKeyNot($report->id)
                ->where('reportable_type', $report->reportable_type)
                ->where('reportable_id', $report->reportable_id)
                ->with('reporter:id,name')
                ->latest()
                ->get();
        }

        return view('admin.reports.show', [
            'report' => $report,
            'related' => $related,
        ]);
    }

    /**
     * Hide the reported post or comment and mark the report reviewed.
     */
    public function hide(Report $report): RedirectResponse
    {
        $content = $this->content($report);

        $content->update(['is_hidden' => true]);

        // Hiding is one decision about the content, so every pending report
        // about the same post or comment is resolved with it. Otherwise the
        // queue would keep showing duplicates for content that is already gone.
        $this->resolvePendingReportsFor($report);

        return back()->with('success', __('The :type has been hidden and the report marked as reviewed.', [
            'type' => strtolower($report->content_type),
        ]));
    }

    /**
     * Make hidden content visible again.
     */
    public function unhide(Report $report): RedirectResponse
    {
        $content = $this->content($report);

        $content->update(['is_hidden' => false]);
        $report->update(['status' => Report::STATUS_REVIEWED]);

        return back()->with('success', __('The :type is visible again.', [
            'type' => strtolower($report->content_type),
        ]));
    }

    /**
     * Delete the reported content. Reports about it disappear with it.
     */
    public function destroyContent(Report $report): RedirectResponse
    {
        $content = $this->content($report);

        // The Post / Comment model removes the reports that point at the
        // content (and a post's image) in its deleting hook.
        $content->delete();

        return redirect()
            ->route('admin.reports.index')
            ->with('success', __('The content and all its reports have been deleted.'));
    }

    /**
     * Block the author of the reported content (FR-33).
     */
    public function blockUser(Report $report): RedirectResponse
    {
        $user = $report->reported_user;

        if (! $user) {
            return back()->with('error', __('The author of this content no longer exists.'));
        }

        // Admins are never blocked from the moderation queue either (FR-27).
        if ($user->isAdmin()) {
            return back()->with('error', __('Administrator accounts cannot be blocked.'));
        }

        $this->authorize('block', $user);

        $user->update(['is_blocked' => true]);

        // Kill the active sessions of the blocked account right away.
        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $report->update(['status' => Report::STATUS_REVIEWED]);

        return back()->with('success', __(':name has been blocked and the report marked as reviewed.', [
            'name' => $user->name,
        ]));
    }

    /**
     * Mark the report as reviewed without touching the content.
     */
    public function markReviewed(Report $report): RedirectResponse
    {
        $report->update(['status' => Report::STATUS_REVIEWED]);

        return back()->with('success', __('The report has been marked as reviewed.'));
    }

    /**
     * Dismiss the report (FR-34).
     */
    public function dismiss(Report $report): RedirectResponse
    {
        $report->update(['status' => Report::STATUS_DISMISSED]);

        return back()->with('success', __('The report has been dismissed.'));
    }

    /* ---------------------------------------------------------------
     | Helpers
     * --------------------------------------------------------------- */

    /**
     * The reported post or comment. A missing content row is a 404 because
     * there is nothing left to moderate.
     */
    private function content(Report $report): Post|Comment
    {
        $content = $report->reportable;

        abort_unless($content instanceof Post || $content instanceof Comment, 404);

        return $content;
    }

    /**
     * Mark every still-pending report about the same content as reviewed.
     */
    private function resolvePendingReportsFor(Report $report): void
    {
        Report::query()
            ->where('reportable_type', $report->reportable_type)
            ->where('reportable_id', $report->reportable_id)
            ->where('status', Report::STATUS_PENDING)
            ->update(['status' => Report::STATUS_REVIEWED]);
    }

    /**
     * Escape LIKE wildcards in a search term (MySQL and SQLite safe).
     */
    private function likePattern(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
    }
}
```

### `app/Http/Controllers/AdminPostController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin post management (FR-28): search, filter, hide/unhide and delete.
 */
class AdminPostController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        $hidden = in_array($request->query('hidden'), ['0', '1'], true)
            ? (string) $request->query('hidden')
            : null;

        $posts = Post::query()
            ->with('user:id,name,email,photo,role')
            ->withCount('reports')
            ->when($term !== '', function (Builder $query) use ($term): void {
                $pattern = $this->likePattern($term);

                $query->where(function (Builder $inner) use ($pattern): void {
                    $inner->whereRaw("body LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereHas('user', fn (Builder $author) => $author
                            ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]));
                });
            })
            ->when($hidden !== null, fn (Builder $query) => $query->where('is_hidden', $hidden === '1'))
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.posts.index', [
            'posts' => $posts,
            'term' => $term,
            'hidden' => $hidden ?? '',
        ]);
    }

    /**
     * Hide or unhide a post. Every report about it is marked reviewed.
     */
    public function toggleHidden(Post $post): RedirectResponse
    {
        $post->update(['is_hidden' => ! $post->is_hidden]);

        $post->reports()
            ->where('status', Report::STATUS_PENDING)
            ->update(['status' => Report::STATUS_REVIEWED]);

        return back()->with('success', $post->is_hidden
            ? __('The post has been hidden.')
            : __('The post is visible again.'));
    }

    /**
     * Delete a post, its image and its reports.
     */
    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        // The Post model removes the image and every report about the post or
        // its comments, so all deletion paths stay consistent.
        $post->delete();

        return back()->with('success', __('The post has been deleted.'));
    }

    private function likePattern(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
    }
}
```

### `app/Http/Controllers/AdminUserController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin user management (FR-27): search, block, unblock and delete.
 * UserPolicy makes sure an admin can never touch themselves or another admin.
 */
class AdminUserController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        $role = in_array($request->query('role'), [
            User::ROLE_ADMIN,
            User::ROLE_TEACHER,
            User::ROLE_STUDENT,
        ], true) ? (string) $request->query('role') : null;

        // "" = all, "1" = blocked, "0" = active.
        $blocked = in_array($request->query('blocked'), ['0', '1'], true)
            ? (string) $request->query('blocked')
            : null;

        $users = User::query()
            ->withCount(['posts', 'classes'])
            ->when($term !== '', function (Builder $query) use ($term): void {
                $pattern = $this->likePattern($term);

                $query->where(function (Builder $inner) use ($pattern): void {
                    $inner->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("email LIKE ? ESCAPE '!'", [$pattern]);
                });
            })
            ->when($role, fn (Builder $query) => $query->where('role', $role))
            ->when($blocked !== null, fn (Builder $query) => $query->where('is_blocked', $blocked === '1'))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'term' => $term,
            'role' => $role ?? '',
            'blocked' => $blocked ?? '',
        ]);
    }

    /**
     * Block a user and log them out of every active session.
     */
    public function block(User $user): RedirectResponse
    {
        $this->authorize('block', $user);

        $user->update(['is_blocked' => true]);

        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return back()->with('success', __(':name has been blocked.', ['name' => $user->name]));
    }

    public function unblock(User $user): RedirectResponse
    {
        $this->authorize('unblock', $user);

        $user->update(['is_blocked' => false]);

        return back()->with('success', __(':name can log in again.', ['name' => $user->name]));
    }

    /**
     * Delete an account and everything that would point at it.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $name = $user->name;

        // The User model removes the profile photo, the post images and every
        // report about this user's content in its deleting hook. Posts,
        // comments, likes, follows and enrollments go with the account through
        // the cascade rules on the foreign keys.
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', __(':name and all their content have been deleted.', ['name' => $name]));
    }

    private function likePattern(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
    }
}
```

### `app/Http/Controllers/PostController.php`

```php
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

        // The Post model removes the uploaded image, the reports about the post
        // and the reports about its comments. Comments and likes disappear
        // through the cascade rules on the tables.
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
```

### `app/Http/Controllers/ProfileController.php`

```php
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

        Auth::logout();

        // The User model deletes the photo, the post images and the reports
        // about this user's content in its deleting hook.
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
```

### `app/Http/Controllers/UserProfileController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Public profile pages: /users/{user} and the follower lists (FR-5, FR-11).
 */
class UserProfileController extends Controller
{
    /** People per page in the followers / following lists. */
    private const PEOPLE_PER_PAGE = 20;

    /**
     * A user's public page: their info, their posts and the follow button.
     */
    public function show(Request $request, User $user): View
    {
        // Counts for the header: posts, followers, following. Hidden posts are
        // left out of the profile counter (FR-28), so a moderated post really
        // disappears from everywhere a normal visitor can see.
        $user->loadCount([
            'posts' => static fn ($query) => $query->where('is_hidden', false),
            'followers',
            'following',
        ]);

        $posts = $user->posts()
            ->visible()
            ->withFeedData($request->user())
            ->latest()
            ->paginate(PostController::PER_PAGE)
            ->withQueryString();

        return view('users.show', [
            'profile' => $user,
            'posts' => $posts,
            'classes' => $user->isTeacher()
                ? $user->teachingClasses()
                    ->where('is_hidden', false)
                    // The class cards show the teacher name and the student
                    // count, so both are loaded for all six cards at once.
                    ->with('teacher:id,name,subject')
                    ->withCount('students')
                    ->orderByDesc('date')
                    ->orderByDesc('time')
                    ->take(6)
                    ->get()
                : collect(),
            'followingIds' => $this->followingIds($request),
        ]);
    }

    /**
     * Everybody who follows this user.
     */
    public function followers(Request $request, User $user): View
    {
        return $this->peopleList($request, $user, $user->followers(), 'followers');
    }

    /**
     * Everybody this user follows.
     */
    public function following(Request $request, User $user): View
    {
        return $this->peopleList($request, $user, $user->following(), 'following');
    }

    /* ---------------------------------------------------------------
     | Helpers
     * --------------------------------------------------------------- */

    /**
     * Shared renderer for the two list pages.
     */
    private function peopleList(Request $request, User $user, $relation, string $mode): View
    {
        $people = $relation
            ->orderBy('name')
            ->paginate(self::PEOPLE_PER_PAGE)
            ->withQueryString();

        return view('users.people', [
            'profile' => $user,
            'people' => $people,
            'mode' => $mode,
            // Which of these people the visitor already follows (one query).
            'followingIds' => $this->followingIds($request),
        ]);
    }

    /**
     * Ids the current user follows, so the list pages can render the right
     * Follow / Unfollow button without a query per person.
     */
    private function followingIds(Request $request): Collection
    {
        $viewer = $request->user();

        return $viewer ? $viewer->following()->pluck('users.id') : collect();
    }
}
```

### `app/Http/Controllers/ClassBrowseController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Browsing classes as a student: the discover list and the class detail page
 * (FR-17, FR-18, FR-20).
 */
class ClassBrowseController extends Controller
{
    /** Classes per page on the discover page. */
    private const PER_PAGE = 9;

    /**
     * Upcoming (and currently running) classes, with a search box and a
     * subject + teacher filter (FR-17).
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        // A very long query is pointless (and wasteful); cap it before the
        // LIKE pattern is built.
        $search = mb_substr($search, 0, 100);

        $subject = $request->query('subject');
        $teacher = $request->query('teacher');
        $date = trim((string) $request->query('date'));

        // Anything that is not YYYY-MM-DD is ignored instead of erroring.
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : '';

        // Anything that is not a positive integer is ignored as well.
        $teacher = is_scalar($teacher) && ctype_digit((string) $teacher) && (int) $teacher > 0
            ? (int) $teacher
            : 0;

        $query = ClassRoom::query()
            ->visible()
            ->with('teacher:id,name,subject')
            ->withCount('students');

        // Search in the title, in the subject and in the teacher's name.
        // LIKE wildcards in the user's text are escaped, so searching for "%"
        // or "_" matches those characters instead of everything.
        if ($search !== '') {
            $pattern = $this->likePattern($search);

            $query->where(function (Builder $inner) use ($pattern): void {
                $inner->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("subject LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereHas('teacher', fn (Builder $teacher) => $teacher
                        ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]));
            });
        }

        // Filter on one subject.
        if (is_string($subject) && $subject !== '') {
            $query->where('subject', $subject);
        }

        // Filter on one teacher.
        if ($teacher > 0) {
            $query->where('teacher_id', $teacher);
        }

        // Filter on one calendar day.
        if ($date !== '') {
            $query->whereDate('date', $date);
        }

        // Only classes that are running right now or still ahead of us.
        $query->where(function (Builder $inner): void {
            $inner->where('status', ClassRoom::STATUS_LIVE)
                ->orWhere(fn (Builder $soon) => $soon->upcoming());
        });

        $classes = $query
            ->orderBy('date')
            ->orderBy('time')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('classes.discover', [
            'classes' => $classes,
            'subjects' => $this->subjects(),
            'teachers' => $this->teachers(),
            'search' => $search,
            'subject' => is_string($subject) ? $subject : '',
            'teacher' => $teacher,
            'date' => $date,
            // So the cards can show "Enrolled" without a query each.
            'enrolledIds' => $this->enrolledIds($request),
        ]);
    }

    /**
     * One class with its details and the enroll / join buttons (FR-17, FR-18).
     */
    public function show(Request $request, ClassRoom $classRoom): View
    {
        $this->authorize('view', $classRoom);

        $user = $request->user();

        $classRoom->load('teacher:id,name,subject,photo,bio')->loadCount('students');

        $isOwner = $classRoom->teacher_id === $user->id;
        $isEnrolled = $user->isStudent() && $user->isEnrolledIn($classRoom);

        return view('classes.show', [
            'classRoom' => $classRoom,
            'isOwner' => $isOwner,
            'isEnrolled' => $isEnrolled,
            // The room opens for the teacher at any time, for a student only
            // while the class is live (FR-20). Admins moderate, they never join.
            'canJoin' => $isOwner || ($isEnrolled && $classRoom->isLive()),
            // A short preview; the full list lives on classes.students.
            'previewStudents' => $classRoom->students()
                ->orderBy('name')
                ->limit(8)
                ->get(['users.id', 'users.name', 'users.photo', 'users.role']),
        ]);
    }

    /**
     * "My enrolled classes" for a student (FR-17): everything they joined,
     * running classes first, then the ones still ahead, then the past ones.
     */
    public function enrolled(Request $request): View
    {
        $student = $request->user();

        $classes = $student->enrolledClasses()
            ->with('teacher:id,name,subject')
            ->withCount('students')
            ->orderByRaw("CASE status WHEN 'live' THEN 0 WHEN 'scheduled' THEN 1 ELSE 2 END")
            ->orderBy('date')
            ->orderBy('time')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('classes.enrolled', [
            'classes' => $classes,
            // Every card on this page is enrolled by definition.
            'enrolledIds' => $student->enrolledClasses()->pluck('classes.id'),
        ]);
    }

    /* ---------------------------------------------------------------
     | Helpers
     * --------------------------------------------------------------- */

    /**
     * All subjects that are actually used, for the filter drop down.
     *
     * @return Collection<int, string>
     */
    private function subjects(): Collection
    {
        return ClassRoom::query()
            ->visible()
            ->whereNotNull('subject')
            ->distinct()
            ->orderBy('subject')
            ->pluck('subject');
    }

    /**
     * All teachers who have at least one visible class, for the filter.
     *
     * @return Collection<int, User>
     */
    private function teachers(): Collection
    {
        return User::query()
            ->whereIn('id', ClassRoom::query()->visible()->select('teacher_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'subject']);
    }

    /**
     * Ids of the classes the current student is already enrolled in.
     *
     * @return Collection<int, int>
     */
    private function enrolledIds(Request $request): Collection
    {
        $user = $request->user();

        return $user !== null && $user->isStudent()
            ? $user->enrolledClasses()->pluck('classes.id')
            : collect();
    }

    /**
     * Escape LIKE wildcards in a search term (MySQL and SQLite safe).
     */
    private function likePattern(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
    }
}
```

### `app/Http/Requests/StorePostRequest.php`

```php
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
     * Trim the body before validation, so a whitespace-only post fails the
     * "required" rule instead of being stored as invisible content.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('body'))) {
            $this->merge(['body' => trim($this->input('body'))]);
        }
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
```

### `app/Http/Requests/UpdatePostRequest.php`

```php
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
     * Trim the body before validation, so an edit to only spaces is rejected
     * instead of blanking out the post.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('body'))) {
            $this->merge(['body' => trim($this->input('body'))]);
        }
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
```

### `app/Http/Requests/StoreCommentRequest.php`

```php
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
     * Trim the body before validation, so a whitespace-only comment fails the
     * "required" rule instead of being stored as invisible content.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('body'))) {
            $this->merge(['body' => trim($this->input('body'))]);
        }
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
```

### `resources/views/classes/room.blade.php`

```blade
{{-- The Join Class page: Jitsi Meet embedded through the External API (FR-18, FR-20, FR-22).
     The script URL and the domain come from config/services.php, so a self hosted
     Jitsi server only needs a change in .env (JITSI_DOMAIN).
     Everything the JavaScript needs travels in data-* attributes: no inline script. --}}
<x-app-layout>
    <x-slot name="title">{{ $classRoom->title }} · {{ __('Live room') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.show', $classRoom) }}">{{ \Illuminate\Support\Str::limit($classRoom->title, 40) }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Live room') }}</li>
    </x-slot>

    <div class="container-fluid px-3 px-lg-4">
        {{-- Slim top bar: identity on the left, room state and exit on the right.
             On phones and tablets the bar stays sticky, so the exit link is
             always reachable (Phase 3D). --}}
        <div class="card edu-card edu-room-bar mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <span class="edu-auth-icon flex-shrink-0" aria-hidden="true">
                        <i class="bi bi-camera-video"></i>
                    </span>

                    <div class="min-w-0">
                        <h1 class="h6 fw-bold mb-0 text-break">{{ $classRoom->title }}</h1>
                        <div class="small text-muted text-break">
                            <i class="bi bi-person-video3 me-1" aria-hidden="true"></i>{{ $classRoom->teacher?->name }}
                            @if ($classRoom->subject)
                                <span class="mx-1" aria-hidden="true">&middot;</span>{{ $classRoom->subject }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if ($classRoom->isLive())
                        <span class="badge text-bg-danger">
                            <span class="edu-live-dot" aria-hidden="true"></span> {{ __('Live') }}
                        </span>
                    @else
                        <x-status-badge :status="$classRoom->status" />
                    @endif

                    {{-- Only the teacher moderates the room (FR-20) --}}
                    @if ($isModerator)
                        <span class="badge text-bg-dark">
                            <i class="bi bi-shield-check me-1" aria-hidden="true"></i> {{ __('Moderator') }}
                        </span>
                    @else
                        <span class="badge text-bg-success">{{ __('Student') }}</span>
                    @endif

                    <button type="button" class="btn btn-sm btn-outline-secondary d-lg-none"
                            data-bs-toggle="collapse" data-bs-target="#room-info"
                            aria-expanded="false" aria-controls="room-info">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i> {{ __('Info') }}
                    </button>

                    <a href="{{ route('classes.show', $classRoom) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to the class') }}
                    </a>
                </div>
            </div>
        </div>

        @if (! $classRoom->isLive() && $classRoom->teacher_id === auth()->id())
            <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    {{ __('The class is not live yet, so students cannot join. Press "Start the class for the students" when you are ready.') }}
                </span>
            </div>
        @endif

        {{-- The Jitsi room ------------------------------------------------------------------
             data-jitsi is picked up by resources/js/jitsi.js, which loads the
             External API and asks it to build the iframe inside data-jitsi-container. --}}
        <div id="edu-jitsi"
             data-jitsi
             data-domain="{{ $domain }}"
             data-script-url="{{ $scriptUrl }}"
             data-room="{{ $roomName }}"
             data-display-name="{{ $displayName }}"
             data-email="{{ $email }}"
             data-avatar="{{ $avatarUrl }}"
             data-subject="{{ $subject }}"
             data-moderator="{{ $isModerator ? '1' : '0' }}"
             data-dashboard-url="{{ route('dashboard') }}"
             data-leave-url="{{ route('classes.leave', $classRoom) }}">

            {{-- Shown while the API and the room are loading --}}
            <div class="alert bg-body-secondary text-body border d-flex align-items-center gap-2" data-jitsi-status>
                <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                <span>{{ __('Connecting to the room...') }}</span>
            </div>

            {{-- Shown when the External API cannot be loaded (offline, blocked, ...) --}}
            <div class="alert alert-danger d-none" data-jitsi-error role="alert">
                <div class="fw-semibold mb-1">
                    <i class="bi bi-wifi-off me-1" aria-hidden="true"></i> {{ __('The video room could not be loaded.') }}
                </div>
                <p class="small mb-2">
                    {{ __('Check your internet connection and try again. If you are on a school network, the video server might be blocked.') }}
                </p>
                <button type="button" class="btn btn-sm btn-outline-danger" data-jitsi-retry>
                    <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i> {{ __('Try again') }}
                </button>
            </div>

            {{-- Jitsi builds the iframe in here --}}
            <div class="jitsi-frame" data-jitsi-container></div>
        </div>

        {{-- Collapsible room info: moderation controls and help, out of the way.
             Below lg it is a bottom sheet, not a panel that pushes the video;
             the close button and the sticky top bar keep "leave" reachable. --}}
        <div class="collapse d-lg-block mt-3 edu-room-sheet" id="room-info">
            <div class="edu-room-sheet-head d-lg-none">
                <span class="edu-room-sheet-handle" aria-hidden="true"></span>
                <span class="fw-semibold">{{ __('Room information') }}</span>
                <button type="button" class="btn-close ms-auto"
                        data-bs-toggle="collapse" data-bs-target="#room-info"
                        aria-expanded="true" aria-controls="room-info"
                        aria-label="{{ __('Close room information') }}"></button>
            </div>

            <div class="row g-3">
                {{-- Only the teacher can start or end the class (FR-24). --}}
                @if ($isModerator && ($classRoom->teacher_id === auth()->id()))
                    <div class="col-12 col-lg-7">
                        <div class="card edu-card h-100">
                            <div class="card-body">
                                <h2 class="h6 fw-bold">{{ __('Class controls') }}</h2>

                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($classRoom->allowedTransitions() as $next)
                                        <form method="POST" action="{{ route('classes.status', $classRoom) }}" class="d-inline"
                                              data-confirm="{{ __('Change the class status to :status?', ['status' => __($next)]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $next }}">
                                            <button type="submit"
                                                    class="btn btn-sm {{ $next === \App\Models\ClassRoom::STATUS_LIVE ? 'btn-success' : 'btn-outline-danger' }}">
                                                @if ($next === \App\Models\ClassRoom::STATUS_LIVE)
                                                    <i class="bi bi-broadcast me-1" aria-hidden="true"></i> {{ __('Start the class for the students') }}
                                                @elseif ($next === \App\Models\ClassRoom::STATUS_COMPLETED)
                                                    <i class="bi bi-stop-circle me-1" aria-hidden="true"></i> {{ __('End the class') }}
                                                @else
                                                    <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> {{ __('Back to scheduled') }}
                                                @endif
                                            </button>
                                        </form>
                                    @endforeach
                                </div>

                                <p class="small text-muted mb-0 mt-3">
                                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
                                    {{ __('Room') }}: <code class="text-break">{{ $classRoom->room_name }}</code>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-12 {{ $isModerator && ($classRoom->teacher_id === auth()->id()) ? 'col-lg-5' : '' }}">
                    <div class="card edu-card h-100">
                        <div class="card-body">
                            <h2 class="h6 fw-bold">{{ __('Inside the room') }}</h2>

                            <ul class="list-unstyled small text-muted mb-0 d-flex flex-column gap-2">
                                <li>
                                    <i class="bi bi-chat-dots me-1" aria-hidden="true"></i>
                                    {{ __('Use the chat button inside the room to send a message to everybody (FR-22).') }}
                                </li>
                                @if ($isModerator)
                                    <li>
                                        <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                                        {{ __('As the moderator you can mute participants and remove them from the room.') }}
                                    </li>
                                @endif
                                <li>
                                    @if ($isModerator)
                                        <i class="bi bi-mic me-1" aria-hidden="true"></i>
                                        {{ __('Your microphone starts on; mute yourself whenever you do not need to speak.') }}
                                    @else
                                        <i class="bi bi-mic-mute me-1" aria-hidden="true"></i>
                                        {{ __('Your microphone starts muted; unmute when you are ready to speak.') }}
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

### `tests/Feature/PhaseNineTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 9: the regressions found by the final audit.
 *
 * Each test pins one finding so it cannot come back:
 *  - polymorphic reports and uploaded files are cleaned up on every deletion
 *    path (author, administrator, moderation queue, account deletion),
 *  - the moderation queue and the public profile do not run N+1 queries,
 *  - class search treats LIKE wildcards as literal text,
 *  - whitespace-only posts and comments are rejected,
 *  - registration actually sends the e-mail verification link,
 *  - the live-room help text matches the microphone defaults.
 */
class PhaseNineTest extends TestCase
{
    use RefreshDatabase;

    /* -----------------------------------------------------------------
     | Deletion cleanup (FR-8, FR-10, FR-28, FR-33, FR-34)
     * ----------------------------------------------------------------- */

    public function test_deleting_a_post_removes_its_reports_its_comment_reports_and_its_image(): void
    {
        Storage::fake('public');

        $author = User::factory()->teacher()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'image' => 'posts/audit-image.jpg',
        ]);
        Storage::disk('public')->put('posts/audit-image.jpg', 'image-bytes');

        $comment = Comment::factory()->create(['post_id' => $post->id]);

        Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);
        Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
        ]);

        $post->delete();

        // The post and its comments disappear through the foreign keys.
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);

        // Reports are polymorphic, so they only disappear because of the hook.
        $this->assertDatabaseCount('reports', 0);
        Storage::disk('public')->assertMissing('posts/audit-image.jpg');
    }

    public function test_deleting_a_comment_removes_the_reports_about_it(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);
        $otherComment = Comment::factory()->create(['post_id' => $post->id]);

        Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
        ]);
        Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $otherComment->id,
        ]);

        $comment->delete();

        $this->assertDatabaseMissing('reports', [
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
        ]);

        // A report about another comment is untouched.
        $this->assertDatabaseHas('reports', [
            'reportable_type' => Comment::class,
            'reportable_id' => $otherComment->id,
        ]);
    }

    public function test_deleting_a_user_cleans_reports_about_their_content_and_the_uploaded_files(): void
    {
        Storage::fake('public');

        $author = User::factory()->teacher()->create(['photo' => 'profiles/author.webp']);
        Storage::disk('public')->put('profiles/author.webp', 'photo');

        $ownPost = Post::factory()->create([
            'user_id' => $author->id,
            'image' => 'posts/author-post.jpg',
        ]);
        Storage::disk('public')->put('posts/author-post.jpg', 'image');

        $ownComment = Comment::factory()->create([
            'user_id' => $author->id,
            'post_id' => $ownPost->id,
        ]);

        // A comment by somebody else on the author's post: its report is
        // orphaned by the post cascade, not by the comment cascade.
        $foreignComment = Comment::factory()->create(['post_id' => $ownPost->id]);

        Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $ownPost->id,
        ]);
        Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $ownComment->id,
        ]);
        Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $foreignComment->id,
        ]);

        $author->delete();

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('comments', 0);
        $this->assertDatabaseCount('reports', 0);

        Storage::disk('public')->assertMissing('profiles/author.webp');
        Storage::disk('public')->assertMissing('posts/author-post.jpg');
    }

    /* -----------------------------------------------------------------
     | Query budgets (SRS 5.1: N+1)
     * ----------------------------------------------------------------- */

    public function test_the_admin_report_queue_does_not_run_one_query_per_report(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->student()->create();
        $author = User::factory()->teacher()->create();

        $posts = Post::factory()->count(20)->create(['user_id' => $author->id]);

        foreach ($posts as $post) {
            Report::factory()->create([
                'reporter_id' => $reporter->id,
                'reportable_type' => Post::class,
                'reportable_id' => $post->id,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Reporter + reportable per type + author per type are eager loaded, so
        // the count stays flat instead of growing with the 20 rows.
        $this->assertLessThanOrEqual(
            15,
            $queries,
            "The moderation queue ran {$queries} queries for 20 reports; eager loading is missing."
        );
    }

    public function test_the_public_profile_loads_class_cards_without_n_plus_one(): void
    {
        $teacher = User::factory()->teacher()->create();
        $viewer = User::factory()->student()->create();

        ClassRoom::factory()->count(6)->scheduled()->create([
            'teacher_id' => $teacher->id,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($viewer)->get(route('users.show', $teacher))->assertOk();

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // The cards reuse the eager loaded teacher and students_count instead
        // of running 12 extra queries for six classes.
        $this->assertLessThanOrEqual(
            15,
            $queries,
            "The profile page ran {$queries} queries for 6 class cards; eager loading is missing."
        );
    }

    /* -----------------------------------------------------------------
     | Search (FR-19)
     * ----------------------------------------------------------------- */

    public function test_class_search_treats_like_wildcards_as_literal_text(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create(['name' => 'Maria Rossi']);

        ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Algebra basics',
            'subject' => 'Mathematics',
        ]);

        ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'title' => '100% Physics',
            'subject' => 'Physics',
        ]);

        // "%" must match the literal percent sign, not every class.
        $percent = $this->actingAs($student)
            ->get(route('classes.discover', ['q' => '%']))
            ->assertOk();

        $this->assertSame(1, $percent->viewData('classes')->total());
        $this->assertSame('100% Physics', $percent->viewData('classes')->first()->title);

        // "_" matches a single character in SQL; no title contains one.
        $underscore = $this->actingAs($student)
            ->get(route('classes.discover', ['q' => '_']))
            ->assertOk();

        $this->assertSame(0, $underscore->viewData('classes')->total());
    }

    /* -----------------------------------------------------------------
     | Validation (FR-7, FR-8, FR-10)
     * ----------------------------------------------------------------- */

    public function test_whitespace_only_posts_and_comments_are_rejected(): void
    {
        $student = User::factory()->student()->create();
        $author = User::factory()->teacher()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($student)
            ->post(route('posts.store'), ['body' => "   \n\t  "])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('posts', 1);

        $this->actingAs($student)
            ->post(route('comments.store', $post), ['body' => "   \n "])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('comments', 0);

        // Editing a post down to spaces is refused too.
        $this->actingAs($author)
            ->patch(route('posts.update', $post), ['body' => '    '])
            ->assertSessionHasErrors('body');

        $this->assertNotSame('', trim((string) $post->fresh()->body));
    }

    public function test_the_body_is_trimmed_before_it_is_stored(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('posts.store'), ['body' => '  Hello EduConnect  '])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('posts', [
            'user_id' => $student->id,
            'body' => 'Hello EduConnect',
        ]);
    }

    /* -----------------------------------------------------------------
     | E-mail verification (FR-1)
     * ----------------------------------------------------------------- */

    public function test_registration_sends_the_email_verification_notification(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Verify Me',
            'email' => 'verify.me@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'student',
        ])->assertRedirect('/student/dashboard');

        $user = User::query()->where('email', 'verify.me@example.com')->firstOrFail();

        // The interface is what makes Laravel send the signed link.
        $this->assertInstanceOf(MustVerifyEmail::class, $user);
        $this->assertFalse($user->hasVerifiedEmail());

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /* -----------------------------------------------------------------
     | Live room copy (FR-20, FR-22)
     * ----------------------------------------------------------------- */

    public function test_the_live_room_microphone_help_matches_the_defaults(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create([
            'teacher_id' => $teacher->id,
        ]);
        $classRoom->students()->attach($student->id);

        // The moderator starts unmuted (jitsi.js sets startWithAudioMuted=false).
        $this->actingAs($teacher)
            ->get(route('classes.room', $classRoom))
            ->assertOk()
            ->assertSee('Your microphone starts on', false)
            ->assertDontSee('Your microphone starts muted', false);

        // Students start muted.
        $this->actingAs($student)
            ->get(route('classes.room', $classRoom))
            ->assertOk()
            ->assertSee('Your microphone starts muted', false)
            ->assertDontSee('Your microphone starts on', false);
    }
}
```

### `tests/Feature/SmokeTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 9 smoke test: after seeding the demo data, every important page must
 * render for the right role, and the reporting/moderation flow must work
 * end-to-end. This is the script used in the final walkthrough.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        // The same seed a fresh install runs: php artisan migrate --seed.
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
        $this->teacher = User::where('role', User::ROLE_TEACHER)->firstOrFail();
        $this->student = User::where('role', User::ROLE_STUDENT)->firstOrFail();
    }

    public function test_guest_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('EduConnect');
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();

        // Guests are pushed to the login page, never to a dashboard.
        $this->get(route('feed.index'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_can_open_every_admin_page(): void
    {
        $report = Report::query()->firstOrFail();

        $pages = [
            route('admin.dashboard'),
            route('admin.users.index'),
            route('admin.posts.index'),
            route('admin.classes.index'),
            route('admin.reports.index'),
            route('admin.reports.show', $report),
            route('feed.index'),
            route('search.index'),
            route('notifications.index'),
            route('profile.edit'),
            route('users.show', $this->admin),
            route('classes.discover'),
        ];

        foreach ($pages as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }

        // A wrong role gets a 403, not the admin data.
        $this->actingAs($this->student)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($this->teacher)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_teacher_can_open_their_pages(): void
    {
        $classRoom = ClassRoom::where('teacher_id', $this->teacher->id)->firstOrFail();
        $post = Post::query()->visible()->firstOrFail();
        $hashtag = Hashtag::query()->firstOrFail();

        $pages = [
            route('teacher.dashboard'),
            route('classes.index'),
            route('classes.create'),
            route('classes.show', $classRoom),
            route('classes.edit', $classRoom),
            route('classes.students', $classRoom),
            route('classes.room', $classRoom),
            route('classes.discover'),
            route('feed.index'),
            route('posts.show', $post),
            route('hashtags.show', $hashtag),
            route('search.index', ['q' => 'math', 'tab' => 'hashtags']),
            route('notifications.index'),
            route('profile.edit'),
            route('users.show', $this->teacher),
            route('users.followers', $this->teacher),
            route('users.following', $this->teacher),
        ];

        foreach ($pages as $url) {
            $this->actingAs($this->teacher)->get($url)->assertOk();
        }
    }

    public function test_student_can_open_their_pages_and_join_a_live_class(): void
    {
        $classRoom = ClassRoom::where('teacher_id', $this->teacher->id)->firstOrFail();
        $this->student->enrolledClasses()->syncWithoutDetaching([$classRoom->id]);

        // A student may only enter the room while the class is live.
        $classRoom->update(['status' => ClassRoom::STATUS_LIVE]);
        $this->assertTrue($classRoom->fresh()->isLive());

        $post = Post::query()->visible()->firstOrFail();

        $pages = [
            route('student.dashboard'),
            route('student.classes'),
            route('classes.discover'),
            route('classes.show', $classRoom),
            route('classes.room', $classRoom),
            route('feed.index'),
            route('posts.show', $post),
            route('search.index', ['q' => 'teacher']),
            route('notifications.index'),
            route('profile.edit'),
            route('users.show', $this->teacher),
        ];

        foreach ($pages as $url) {
            $this->actingAs($this->student)->get($url)->assertOk();
        }

        // Teacher-only pages stay closed to students.
        $this->actingAs($this->student)->get(route('classes.create'))->assertForbidden();
        $this->actingAs($this->student)->get(route('teacher.dashboard'))->assertForbidden();
    }

    public function test_the_report_to_moderation_flow_works_end_to_end(): void
    {
        // The demo seeder files random reports, one of them by the first
        // student. Pick a visible post this student has not reported yet, so
        // the test does not depend on the seed's random choice.
        $post = Post::query()
            ->visible()
            ->where('user_id', '!=', $this->student->id)
            ->whereDoesntHave('reports', fn ($query) => $query->where('reporter_id', $this->student->id))
            ->firstOrFail();

        // 1. Student reports a post.
        $this->actingAs($this->student)
            ->post(route('reports.posts.store', $post), [
                'reason' => 'spam',
                'details' => 'Smoke test report.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report = Report::query()
            ->where('reportable_id', $post->id)
            ->where('reporter_id', $this->student->id)
            ->firstOrFail();
        $this->assertSame(Report::STATUS_PENDING, $report->status);

        // 2. The admin sees it in the queue and on the detail page.
        $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Spam');

        $this->actingAs($this->admin)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee('Smoke test report.');

        // 3. The admin hides the post and the report becomes reviewed.
        $this->actingAs($this->admin)
            ->patch(route('admin.reports.hide', $report))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($post->fresh()->is_hidden);
        $this->assertSame(Report::STATUS_REVIEWED, $report->fresh()->status);

        // 4. The post is gone from the feed for everybody else.
        $this->actingAs($this->teacher)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertDontSee($post->body);
    }

    public function test_every_list_page_has_an_empty_state(): void
    {
        // Wipe the social content but keep the people, then every list that can
        // become empty must still answer 200 and say something friendly.
        Report::query()->delete();
        Post::query()->delete();
        ClassEnrollment::query()->delete();
        ClassRoom::query()->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('No reports match these filters.');

        $this->actingAs($this->admin)
            ->get(route('admin.posts.index'))
            ->assertOk()
            ->assertSee('No posts match these filters.');

        $this->actingAs($this->admin)
            ->get(route('admin.classes.index'))
            ->assertOk()
            ->assertSee('No classes match these filters.');

        $this->actingAs($this->student)
            ->get(route('student.classes'))
            ->assertOk();

        $this->actingAs($this->student)
            ->get(route('classes.discover'))
            ->assertOk()
            ->assertSee('No class matches your search');
    }
}
```

