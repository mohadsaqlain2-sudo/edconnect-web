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
verification as a confirmation step, not a gate. Since Phase 9 `User`
implements `MustVerifyEmail`, so registration really queues the signed link and
the profile page shows the unverified banner; the account stays usable until
the address is confirmed. The password-reset flow is fully wired
(section 8 / deployment guide).

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
