# Phase 7 – Reports, moderation and admin management

EduConnect · Laravel 12 · Bootstrap 5.3 · Vite 6

This deliverable covers FR-27, FR-28 and FR-32 → FR-34: reporting posts and
comments, the moderation queue, admin user/post/class management, blocked-user
safety and the pending-reports panel on the admin dashboard.

---

## 1. Requirement map

| Requirement | Implementation |
| --- | --- |
| FR-32 report button on every post and comment | Kebab item “Report post” in `components/post-card.blade.php`; flag button in `comments/_comment.blade.php`. Both open `reports/_modal.blade.php`. |
| FR-32 modal: reason required, details ≤ 500 | `StoreReportRequest` validates `reason` against `Report::REASONS` and `details` as `nullable|string|max:500`. The modal marks reason required, caps the textarea at 500 and shows inline errors. |
| FR-32 polymorphic report, pending status | `reports.reportable_type/id` + `reporter_id` + `status = pending`. Unique index `(reporter_id, reportable_type, reportable_id)`. |
| FR-32 no duplicate / no self-report / success flash | Duplicate → friendly `error` flash; self-report → 403 from `ReportPolicy`; success → `success` flash naming the reason. |
| FR-33 queue filters + columns | `/admin/reports` filters status, type and free text (reason, details, reporter, body, **offending author**), paginates 20, shows reporter, preview, offending user, reason, status and date. |
| FR-33 detail actions | Hide, Unhide, Delete content, Block offending user, Mark reviewed, Dismiss. |
| FR-33/34 status transitions | Hide/Unhide/Block/Reviewed → `reviewed`; Dismiss → `dismissed`; Delete content removes the content **and** every report about it. |
| FR-33 hidden content everywhere | `Post::visible()` / `Comment::visible()` used by feed, profile, hashtag, search, post detail, comments, counters, trending tags and hashtag search counts. |
| FR-27 user management | `/admin/users` search name/email, role + blocked filters, paginated table with avatar, role badge, status, post/class counters. Block/Unblock/Delete; Delete and Block confirm in Bootstrap modals. |
| FR-27 admin protection | `UserPolicy` blocks self and other admins; `role:admin` middleware protects every `/admin/*` route. |
| FR-27 blocked login/logout | `LoginRequest` refuses blocked accounts with a clear message; `EnsureUserIsNotBlocked` logs an active session out on the next request. |
| FR-28 post management | `/admin/posts` search body/author, visibility filter, paginated, author + report count + status; Hide/Unhide/Delete (modal). Hiding marks pending reports reviewed. |
| FR-28 class management | `/admin/classes` search title/teacher, status + visibility filters, paginated, teacher + students + visibility; Hide (cancel)/Unhide/Delete (modal). |
| Section 5.6 classroom safety | `ClassPolicy::join` allows only the owning teacher and enrolled students; hidden classes cannot be joined; admins moderate instead of entering. |
| Section 5.6 blocked users | `not.blocked` middleware on every authenticated group → no posting, commenting, enrolling, joining, liking or following. |
| Admin dashboard | “Pending reports” card with count, five newest reports, quick hide/dismiss and links to the full queue. |
| Tests | `ReportTest`, `AdminManagementTest`, `Auth/BlockedUserTest`, `ClassTest` plus new `PhaseSevenTest` (12 tests / 85 assertions). |

---

## 2. Data model

`reports`

| Column | Notes |
| --- | --- |
| `reporter_id` | FK users, cascade delete |
| `reportable_type`, `reportable_id` | morph: `App\Models\Post` or `App\Models\Comment` |
| `reason` | `spam`, `harassment`, `inappropriate_content`, `hate_speech`, `other` |
| `details` | nullable, max 500 enforced by validation |
| `status` | `pending` / `reviewed` / `dismissed`, indexed |
| unique | `(reporter_id, reportable_type, reportable_id)` |

No migration changes were needed in Phase 7; the schema was already correct.

---

## 3. Files created / changed

### Created

```
tests/Feature/PhaseSevenTest.php
docs/PHASE-7-DELIVERABLE.md
```

### Changed

```
app/Http/Controllers/UserProfileController.php   profile post counter ignores hidden posts
app/Http/Controllers/SearchController.php        hashtag counts ignore hidden posts
app/Http/Controllers/AdminReportController.php   search by offending author; friendly admin-block refusal
resources/views/components/confirm-delete-modal.blade.php   generic confirm class/icon
resources/views/admin/users/index.blade.php     Block + Delete confirmation modals
resources/views/admin/posts/index.blade.php     Delete confirmation modal
resources/views/admin/classes/index.blade.php   Reports column + Delete confirmation modal
resources/views/admin/reports/show.blade.php    Delete/Block confirmation modals
tests/Feature/PhaseSevenTest.php                 new coverage
```

### Already present and verified (no rewrite needed)

```
app/Models/Report.php, Post.php, Comment.php
app/Http/Requests/StoreReportRequest.php
app/Http/Controllers/ReportController.php
app/Http/Controllers/AdminUserController.php
app/Http/Controllers/AdminPostController.php
app/Http/Controllers/AdminClassController.php
app/Policies/ReportPolicy.php, UserPolicy.php, PostPolicy.php, CommentPolicy.php, ClassPolicy.php
app/Http/Middleware/EnsureUserIsNotBlocked.php, RoleMiddleware.php
app/Http/Requests/Auth/LoginRequest.php
resources/views/reports/_modal.blade.php
resources/views/components/post-card.blade.php
resources/views/comments/_comment.blade.php
resources/views/admin/reports/index.blade.php
resources/views/dashboards/admin.blade.php
app/Http/Controllers/AdminDashboardController.php
database/migrations/*reports*
```

---

## 4. Key code

### 4.1 Profile counter ignores hidden posts

`app/Http/Controllers/UserProfileController.php`

```php
$user->loadCount([
    'posts' => static fn ($query) => $query->where('is_hidden', false),
    'followers',
    'following',
]);
```

### 4.2 Hashtag counts ignore hidden posts

`app/Http/Controllers/SearchController.php`

```php
->withCount(['posts' => static fn (Builder $query) => $query->where('posts.is_hidden', false)])
```

Applied in `searchHashtags()` and `suggestHashtags()`, so the search result label
matches the hashtag page total.

### 4.3 Queue search matches the offending author

`app/Http/Controllers/AdminReportController.php`

```php
->orWhereHasMorph('reportable', [Post::class, Comment::class], fn (Builder $content) => $content
    ->whereRaw("body LIKE ? ESCAPE '!'", [$pattern])
    ->orWhereHas('user', fn (Builder $author) => $author
        ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])));
```

### 4.4 Admins are never blocked from the queue

`app/Http/Controllers/AdminReportController.php`

```php
if ($user->isAdmin()) {
    return back()->with('error', __('Administrator accounts cannot be blocked.'));
}
```

### 4.5 Reusable confirmation modal

`resources/views/components/confirm-delete-modal.blade.php`

```blade
@props([
    'id', 'action', 'method' => 'DELETE',
    'title' => 'Delete this item?',
    'message' => 'This action cannot be undone.',
    'confirmLabel' => 'Delete',
    'confirmClass' => 'btn btn-danger',
    'triggerLabel' => 'Delete',
    'triggerClass' => 'btn btn-sm btn-outline-danger',
    'triggerIcon' => 'bi-trash',
    'showTrigger' => true,
])
```

### 4.6 Admin user row uses a real Bootstrap modal

`resources/views/admin/users/index.blade.php`

```blade
<button type="button" class="btn btn-sm btn-outline-danger"
        data-bs-toggle="modal" data-bs-target="#delete-user-{{ $user->id }}">
    <i class="bi bi-trash" aria-hidden="true"></i>
</button>

{{-- after the table --}}
<x-confirm-delete-modal
    :id="'delete-user-'.$user->id"
    :action="route('admin.users.destroy', $user)"
    :title="__('Delete :name?', ['name' => $user->name])"
    :message="__('This deletes the account, their posts, comments and reports. This cannot be undone.')"
    :confirm-label="__('Delete account')"
    :show-trigger="false" />
```

Block uses the same component with `method="PATCH"` and
`confirm-class="btn btn-warning"`. Post, class and report-detail destructive
actions follow the same pattern.

### 4.7 Reporting rules (existing, verified)

`app/Http/Controllers/ReportController.php`

```php
$this->authorize('create', [Report::class, $reportable]);   // no self-report

if (($reportable->is_hidden ?? false) === true) {
    abort(404);                                             // hidden content
}

if ($reportable->reports()->where('reporter_id', $user->id)->exists()) {
    return back()->with('error', __('You have already reported this content.'));
}

Report::create([
    'reporter_id' => $user->id,
    'reportable_type' => $reportable->getMorphClass(),
    'reportable_id' => $reportable->getKey(),
    'reason' => $validated['reason'],
    'details' => $validated['details'] ?? null,
    'status' => Report::STATUS_PENDING,
]);
```

### 4.8 Classroom access (existing, verified)

`app/Policies/ClassPolicy.php`

```php
public function join(User $user, ClassRoom $classRoom): bool
{
    if ($classRoom->is_hidden) {
        return false;
    }

    if ($classRoom->teacher_id === $user->id) {
        return true;
    }

    return $classRoom->students()->whereKey($user->id)->exists();
}
```

---

## 5. Commands

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
npm run build
php artisan migrate --seed
vendor/bin/pint app tests
php artisan test
php artisan view:cache && php artisan view:clear
```

Verified result:

```
Tests:  240 passed (1155 assertions)
Vite:   app CSS 358.57 kB (55.56 gz), app JS 434.60 kB (146.13 gz)
```

---

## 6. Test checklist

### FR-27 – Admin user management

- [x] Only admins can open `/admin/users` (teacher/student 403).
- [x] Search by name or email.
- [x] Filter by role (admin/teacher/student) and blocked/active.
- [x] Paginated table shows avatar, role badge, status, posts and classes.
- [x] Block logs the user out and prevents the next authenticated request.
- [x] Unblock restores access.
- [x] Delete removes the account, posts, comments and related reports.
- [x] Admin cannot block/delete themselves or another admin.
- [x] Block and Delete confirm in Bootstrap modals.
- [x] Blocked users see a clear message at login.

### FR-28 – Post and class management

- [x] Only admins can open `/admin/posts` and `/admin/classes`.
- [x] Search by post body/author and class title/teacher.
- [x] Filter posts by visibility; classes by status and visibility.
- [x] Lists are paginated and show author/teacher, status and report count (posts).
- [x] Hide/Unhide post; Hide/Unhide (cancel) class.
- [x] Delete post/class with confirmation modal.
- [x] Hiding a post marks its pending reports reviewed.
- [x] Hidden posts disappear from feed, profile, hashtag page, search and counters.
- [x] Hidden comments disappear from the post page and comment counter.
- [x] Hidden classes disappear from the catalogue and cannot be joined.
- [x] The class teacher still sees their hidden class; admins see everything.

### FR-32 – Reporting

- [x] Every post and comment exposes a Report action and its own modal.
- [x] Reason is required and restricted to the five allowed values.
- [x] Details are optional and capped at 500 characters.
- [x] Report is stored as pending with reporter + polymorphic target.
- [x] A user cannot report the same item twice.
- [x] A user cannot report their own content (403).
- [x] Hidden content cannot be reported (404).
- [x] Success flash names the reason; duplicate attempt gets a friendly error flash.
- [x] Guests are redirected to login; throttling limits report flooding.

### FR-33 – Moderation queue

- [x] `/admin/reports` is admin-only.
- [x] Filters: status, type, free text (reason, details, reporter, content, author).
- [x] Pagination and empty state.
- [x] Columns: reporter, content preview, offending user, reason, status, date.
- [x] Detail page shows full content, report details and related reports.
- [x] Hide / Unhide / Delete content / Block user / Mark reviewed / Dismiss.
- [x] Every action gives a flash message and updates the report status.
- [x] Delete content removes the content and all its reports.
- [x] Admin accounts cannot be blocked from the queue.

### FR-34 – Outcomes and dashboard

- [x] Hide/Unhide/Block/Reviewed → `reviewed`.
- [x] Dismiss → `dismissed` and content remains visible.
- [x] Deleting content removes the queue entries.
- [x] Admin dashboard shows the pending count, five newest reports and quick links.
- [x] Quick hide/dismiss buttons work from the dashboard.

---

## 7. Manual UI checklist

### Student / Teacher (reporter)

- [ ] Open a post menu → Report post → modal opens with focus inside.
- [ ] Submit with no reason → inline error, input kept.
- [ ] Choose a reason, add 500 characters → report saved, success flash.
- [ ] Report the same post again → friendly “already reported” message.
- [ ] Report a comment with the flag button → same behaviour.
- [ ] Your own posts/comments show no Report action.
- [ ] Keyboard: Tab through reason/details/Cancel/Send; Escape closes the modal.

### Admin (moderator)

- [ ] `/admin/reports` opens; status/type/search filters combine and reset.
- [ ] Status chips filter without losing the search term.
- [ ] Row shows reporter, preview, offending user, reason and date.
- [ ] Review → Hide: content disappears from the feed, report becomes Reviewed.
- [ ] Unhide restores it; Dismiss keeps it visible and marks Dismissed.
- [ ] Delete content asks in a modal, then removes content + reports.
- [ ] Block author asks in a modal, logs them out and marks Reviewed.
- [ ] `/admin/users`: search, role/status filters, block/unblock, delete modal.
- [ ] `/admin/posts`: search, visibility filter, hide/unhide, delete modal, report count.
- [ ] `/admin/classes`: search, status/visibility filters, hide/unhide, delete modal.
- [ ] Self and other admin rows show “protected” and offer no block/delete.
- [ ] Dashboard “Pending reports” card links to the filtered queue.

### Blocked user

- [ ] Login is refused with a clear “blocked” message.
- [ ] An already-open session is logged out on the next request.
- [ ] Posting, commenting, enrolling and joining all redirect to login.

---

## 8. Done when

- Reporting works on every post and comment, with required reason, 500-char cap,
  duplicate protection, self-report protection and flash feedback.
- `/admin/reports` filters by status/type/text, paginates, and every moderation
  action updates the report and hides/deletes content everywhere.
- `/admin/users`, `/admin/posts` and `/admin/classes` offer search, filters,
  pagination and the required actions with confirmation modals.
- Blocked accounts cannot log in or perform any action, and active sessions end.
- Only the owning teacher and enrolled students can enter a class room.
- Admin routes return 403 for teachers/students; policies protect self/other admins.
- The admin dashboard exposes pending reports with quick links.
- `npm run build`, `vendor/bin/pint` and `php artisan test` all pass
  (**240 tests / 1155 assertions**).
