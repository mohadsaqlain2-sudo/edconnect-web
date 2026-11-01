# Phase 7 – Reports, moderation and admin management

EduConnect · Laravel 12 · Bootstrap 5.3 · Vite 6

This phase adds the community-safety layer: members can report a post or a
comment, administrators work through a moderation queue, and the admin area
gains user, post and class management. It covers **FR-27, FR-28 and FR-32 →
FR-34**, the classroom/blocked-user guarantees from **SRS section 5.6**, and the
pending-reports panel on the admin dashboard.

Everything is implemented server-side with Blade forms, so the moderation tools
work without JavaScript; Bootstrap only supplies the modal presentation.

**Final status:** complete. `php artisan test` → **307 passed (1,660
assertions)**. Phase 7 suites → **54 passed (327 assertions)**. Pint passes.

---

## 1. What this phase delivers

- A Report action on every visible post and comment, with a reason picker, an
  optional 500-character note and validation through `StoreReportRequest`.
- A polymorphic `reports` table with a `pending` / `reviewed` / `dismissed`
  lifecycle and a unique index that makes a second report by the same user
  impossible.
- `/admin/reports`, a filterable, paginated moderation queue with a detail page
  that can hide, unhide, delete, block the author, mark reviewed or dismiss.
- `/admin/users`, `/admin/posts` and `/admin/classes` with search, filters,
  pagination and the required moderation actions behind confirmation modals.
- Account blocking that ends active sessions on the next request and refuses a
  new login with a clear message.
- A hidden-content rule that is applied consistently to the feed, post pages,
  profiles, hashtag pages, search, class catalogue and every counter.
- A "Pending reports" card and a five-row recent-reports list on the admin
  dashboard, with quick hide/dismiss actions.
- 27 cross-cutting tests in `PhaseSevenTest` on top of the existing
  `ReportTest`, `AdminManagementTest` and `Auth/BlockedUserTest` coverage.

---

## 2. Requirement map

| Requirement | Implementation |
| --- | --- |
| **FR-32** Report button on every post and comment | Kebab item "Report post" in `resources/views/components/post-card.blade.php`; flag button in `resources/views/comments/_comment.blade.php`. Both open `resources/views/reports/_modal.blade.php`. |
| **FR-32** Reason required, details optional ≤ 500 | `StoreReportRequest`: `reason` is required and restricted to `Report::REASONS`; `details` is `nullable|string|max:500`. The modal marks the select required, sets `maxlength="500"` and renders inline errors. |
| **FR-32** Polymorphic report with reporter and pending status | `reports.reportable_type/id`, `reporter_id`, `status = pending`; unique index `(reporter_id, reportable_type, reportable_id)`. |
| **FR-32** No duplicate, no self-report, success flash | Duplicate → friendly `error` flash before insert; self-report → 403 from `ReportPolicy::create`; success → `success` flash naming the reason. Hidden content → 404. |
| **FR-32** Comment inherits post visibility | `ReportController` refuses a report when the comment's parent post is hidden; the comment UI hides the action in that case. |
| **FR-33** Queue filters and columns | `/admin/reports` filters status, type and free text (reason, details, reporter, content body, offending author), paginates 20, shows reporter, content preview, offending user, reason, status and date. Status chips carry live counts. |
| **FR-33** Detail actions | Hide content, Unhide content, Delete content, Block offending user, Mark as reviewed, Dismiss. Related reports about the same content are listed. |
| **FR-33/34** Status transitions | Hide / Unhide / Block author / Mark reviewed → `reviewed`; Dismiss → `dismissed`; Delete content removes the content and every report about it. Hiding also resolves every other pending report about the same content. |
| **FR-33** Hidden content disappears everywhere | `Post::visible()` / `Comment::visible()` are used by the feed, post detail, profiles, hashtag page, search, class catalogue, dashboard lists and every count. |
| **FR-27** User management | `/admin/users`: search name/email, role and blocked filters, 20 per page, avatar, role badge, status, post/class counters. Block, Unblock, Delete (Bootstrap confirmation modals). |
| **FR-27** Admin protection | `UserPolicy` denies block/unblock/delete for self and for any other administrator; the UI hides those actions and the queue shows a disabled "protected" state. |
| **FR-27** Blocked login and session ejection | `LoginRequest` rejects a blocked account with a clear message; `EnsureUserIsNotBlocked` logs an active session out on the next request. |
| **FR-28** Post management | `/admin/posts`: search body/author, visibility filter, 20 per page, author, body preview, report count, status. Hide/Unhide/Delete with confirmation. Toggling visibility resolves pending reports. |
| **FR-28** Class management | `/admin/classes`: search title/teacher, status and visibility filters, 20 per page, teacher, enrolled count and status. Hide (cancel)/Unhide/Delete with confirmation. |
| **SRS 5.6** Class-room safety | `ClassPolicy::join` allows exactly the owning teacher and enrolled students; a hidden class cannot be joined; admins moderate instead of entering. |
| **SRS 5.6** Blocked users | `not.blocked` on every authenticated route group; blocked accounts cannot post, comment, like, follow, enroll, join, report or open a dashboard. |
| **Dashboard** | "Pending reports" card with cached count, five newest pending reports, quick hide/dismiss forms and links to the full queue. |
| **Access control** | Every `/admin/*` route sits behind `auth`, `not.blocked`, `role:admin`; teacher and student requests receive 403, verified for pages **and** mutations. |

---

## 3. Behaviour in detail

### 3.1 Reporting (FR-32)

1. A post card renders "Report post" only when the viewer is signed in, is not
   the author and the post is visible. A hidden post is only visible to its
   author and admins; neither the action nor its modal is rendered for them.
2. A comment renders a flag button under the same rules, plus one extra guard:
   comments under a hidden post are not reportable because the whole post is
   gone for normal members.
3. The modal posts to `reports.posts.store` or `reports.comments.store` with
   CSRF protection. The reason select offers Spam, Harassment, Inappropriate
   content, Hate speech and Other. Details are optional, trimmed and capped at
   500 characters both in HTML and in validation.
4. `ReportController` authorises against `ReportPolicy` **before** inserting:
   content with no owner or content owned by the reporter is refused with 403.
   Hidden content returns 404.
5. A duplicate is detected with a `reports()` existence check and returns the
   previous page with `error = "You have already reported this content."` The
   unique database index is the second line of defence.
6. A successful report stores `reporter_id`, the morph type/id, the reason, the
   optional details and `status = pending`, then flashes
   "Thank you. Your report (Harassment) was sent to the moderators."

Because the modal is included per item, a feed page with ten posts and their
comments contains independent forms and independent validation error bags; no
JavaScript is required to submit a report.

### 3.2 Moderation queue (FR-33)

`/admin/reports` is a server-rendered table with:

| Column | Source |
| --- | --- |
| Type | `reportable_type` → "Post" / "Comment" badge |
| Reported content | `content_preview` (140 characters) linked to the detail page |
| Offending user | `reported_user` (author of the content), linked when available |
| Reporter | `reporter.name` + e-mail |
| Reason | `reason_label` |
| Status | `status_label` badge (warning / success / secondary) |
| Date | `created_at` |

Filters combine and survive pagination through `withQueryString()`:

- `status` — `pending`, `reviewed`, `dismissed`; anything else is ignored.
- `type` — `post`, `comment`; anything else is ignored.
- `q` — escaped LIKE search across reason, details, reporter name, content body
  and the offending author's name. `%`, `_` and `!` are escaped, so a term such
  as `100%` is treated literally.

Status pills show total counts per status. The page has an empty state for "no
reports yet" and for "no matches", and paginates at 20 rows.

### 3.3 Report detail and outcomes (FR-34)

The detail page loads the reporter, the reported content and every other report
about the same content. It shows the full body (or a "content deleted" state),
the report details, the reporter and the offending user. Actions:

| Action | Effect on content | Effect on report(s) | Flash |
| --- | --- | --- | --- |
| **Hide** | `is_hidden = true` | the current report **and every other pending report about the same content** → `reviewed` | "The post/comment has been hidden and the report marked as reviewed." |
| **Unhide** | `is_hidden = false` | current report → `reviewed` | "The post/comment is visible again." |
| **Delete content** | row deleted (post image removed) | every report about it deleted; redirect to the queue | "The content and all its reports have been deleted." |
| **Block offending user** | unchanged | current report → `reviewed` | ":name has been blocked and the report marked as reviewed." |
| **Mark as reviewed** | unchanged | current report → `reviewed` | "The report has been marked as reviewed." |
| **Dismiss** | unchanged and still visible | current report → `dismissed` | "The report has been dismissed." |

Block is not offered for an administrator (policy + explicit guard) and shows a
disabled "Administrator accounts are protected" button instead. It is also not
offered for a user who is already blocked; that state reads "This user is
already blocked". The admin dashboard quick actions call the same hide/dismiss
routes, so they update the same statuses.

### 3.4 Admin user management (FR-27)

`/admin/users` orders by name and paginates at 20. Filters:

- `q` searches name and e-mail with escaped wildcards.
- `role` filters `admin`, `teacher`, `student`.
- `blocked` filters `1` (blocked) or `0` (active).

Each row shows avatar, name/e-mail, role badge, blocked/active badge, post and
class counters and the available actions. Block and Delete open Bootstrap
confirmation modals; Unblock is immediate. Both destructive routes are PATCH /
DELETE with CSRF and are authorised by `UserPolicy`:

```php
return $admin->isAdmin()
    && ! $admin->is($target)
    && ! $target->isAdmin();
```

Blocking also deletes the target's database sessions when the session driver is
`database`. `EnsureUserIsNotBlocked` then catches any still-valid cookie on the
next request, logs the user out, invalidates the session and redirects to login
with "Your account has been blocked by an administrator. Please contact
support." `LoginRequest` performs the same check before completing a login.

Deleting an account removes its posts, comments, likes, follows and enrolments
through the foreign-key cascades. Before the row is deleted, reports about the
user's posts and comments are removed explicitly, and their photo file is
deleted from the public disk. Reports the user filed disappear through the
`reporter_id` cascade.

### 3.5 Admin post and class management (FR-28)

`/admin/posts` shows the author, a body preview, visibility, report count and
date, filtered by `q` (body or author) and `hidden` (`0`/`1`). Hide/Unhide is a
single PATCH toggle; the pending reports for that post are marked `reviewed` at
the same time, because the moderation decision has been made. Delete is
confirmed in a modal, removes the image file, the reports and the post.

`/admin/classes` shows the teacher, date/time, duration, enrolled count, status
and visibility, filtered by `q` (title or teacher), `status`
(scheduled/live/completed) and `hidden`. The toggle is labelled
**Hide (cancel)** for a visible class and **Unhide** for a hidden one; the flash
for hiding reads "The class has been hidden (cancelled)." Delete is confirmed in
a modal and removes the class (enrolments cascade).

### 3.6 Safety (SRS section 5.6)

**Class rooms.** `ClassPolicy::join`:

```php
if ($classRoom->is_hidden) {
    return false;
}

if ($classRoom->teacher_id === $user->id) {
    return true;
}

return $classRoom->students()->whereKey($user->id)->exists();
```

- The owning teacher always enters (unless an admin hid the class).
- An enrolled student enters.
- Everybody else, including administrators, gets 403. Admins moderate from the
  admin panel instead of entering a room.
- A class hidden by moderation is closed to everyone, including the teacher and
  enrolled students.

**Blocked accounts.** Every authenticated group (`/feed`, `/posts`, `/comments`,
`/classes`, `/enrollments`, `/live`, `/admin`, role dashboards, follow, like,
report, notifications, search writes) uses `not.blocked`. The middleware runs
before the controller, so a blocked account cannot post, comment, like, follow,
enrol, join, report or moderate. Tests assert the redirect to login and that no
row was written.

**Admin routes.** The entire `/admin` prefix is inside
`['auth', 'not.blocked', 'role:admin']`. `RoleMiddleware` returns 403 for a
signed-in teacher or student and redirects guests to login. `PhaseSevenTest`
exercises every admin mutation route (not just the index pages) with both roles
and verifies that nothing changed.

### 3.7 Admin dashboard

`AdminDashboardController` adds two values to the existing dashboard payload:

- `pendingReports` — cached for five minutes by
  `DashboardMetrics::adminPendingReports()`.
- `recentReports` — the five newest `pending` reports with reporter and content.

The "Pending reports" card renders the count, a preview, reporter, reason and
age for each row, an inline Hide form, an inline Dismiss form and links to the
report detail and to `/admin/reports?status=pending`. When the queue is empty it
shows "The moderation queue is clear." The header also carries a "Moderation
queue" quick action with the pending badge.

### 3.8 Hidden-content visibility matrix

| Surface | Rule | Where |
| --- | --- | --- |
| Feed | `Post::visible()`; comments eager-loaded through `Comment::visible()` | `PostController@index` |
| Feed comment preview + counter | visible comments only | `Post::withFeedData()` |
| Post detail | `PostPolicy::view` allows author/admin only; comments `visible()` | `PostController@show` |
| Profile posts + post count | visible posts only | `UserProfileController` |
| Hashtag page + count | visible posts only | `HashtagController`, `Hashtag::posts()` count |
| Search posts | `visible()` + author not blocked | `SearchController` |
| Search hashtags | count excludes hidden posts | `SearchController` |
| Trending hashtags | count excludes hidden posts | `HashtagController` / dashboard metrics |
| Admin lists | admins see everything, with a "Hidden" badge | `AdminPostController`, `AdminClassController` |
| Class catalogue | `ClassRoom::visible()` | `ClassBrowseController` |
| Class room | hidden class cannot be joined by anyone | `ClassPolicy::join` |
| Report action | not rendered for hidden content | `post-card`, `_comment` |

---

## 4. Data model

### `reports`

| Column | Type / notes |
| --- | --- |
| `id` | bigint PK |
| `reporter_id` | FK `users`, cascade on delete |
| `reportable_type` | morph class: `App\Models\Post` or `App\Models\Comment` |
| `reportable_id` | morph key |
| `reason` | text; one of `spam`, `harassment`, `inappropriate_content`, `hate_speech`, `other` |
| `details` | nullable text; ≤ 500 enforced by `StoreReportRequest` |
| `status` | enum `pending` / `reviewed` / `dismissed`, default `pending`, indexed |
| `created_at`, `updated_at` | timestamps |
| unique | `reports_reporter_reportable_unique` on `(reporter_id, reportable_type, reportable_id)` |
| index | `(reportable_type, reportable_id)` from `morphs()` |

`Report` exposes `reporter`, `reportable`, `scopePending`, `scopeByStatus`,
`reason_label`, `status_label`, `content_type`, `reported_user` and
`content_preview`. `Post` and `Comment` each expose `reports()` as a
`morphMany` and a `visible()` scope on `is_hidden = false`.

### Status lifecycle

```text
                 hide / unhide / block author / mark reviewed
   pending ────────────────────────────────────────────────► reviewed
      │
      └────────────── dismiss ─────────────────────────────► dismissed

   delete content ──► content row + all its reports removed
```

Hiding is special: it resolves every still-pending report about the same
content, so the queue does not keep duplicates for content that is already
hidden.

---

## 5. Files in this phase

### Created

```text
app/Models/Report.php
app/Http/Requests/StoreReportRequest.php
app/Http/Controllers/ReportController.php
app/Http/Controllers/AdminReportController.php
app/Http/Controllers/AdminUserController.php
app/Http/Controllers/AdminPostController.php
app/Http/Controllers/AdminClassController.php
app/Http/Controllers/AdminDashboardController.php
app/Policies/ReportPolicy.php
app/Policies/UserPolicy.php
database/migrations/2026_10_29_100009_create_reports_table.php
database/migrations/2026_10_29_140000_add_moderation_support_to_reports_table.php
database/factories/ReportFactory.php
resources/views/reports/_modal.blade.php
resources/views/admin/reports/index.blade.php
resources/views/admin/reports/show.blade.php
resources/views/admin/users/index.blade.php
resources/views/admin/posts/index.blade.php
resources/views/admin/classes/index.blade.php
tests/Feature/ReportTest.php
tests/Feature/AdminManagementTest.php
tests/Feature/PhaseSevenTest.php
```

### Changed for Phase 7

```text
routes/web.php                                  admin + report routes
resources/views/components/post-card.blade.php  report action + modal
resources/views/components/confirm-delete-modal.blade.php  generic confirm target
resources/views/comments/_comment.blade.php     report flag + visibility guard
resources/views/dashboards/admin.blade.php      pending-reports card
app/Models/Post.php                             reports(), visible(), visible comment counts
app/Models/Comment.php                          reports(), visible()
app/Policies/PostPolicy.php                     hidden-post read + admin delete
app/Policies/CommentPolicy.php                  hidden-comment read + admin delete
app/Policies/ClassPolicy.php                    hidden class + classroom join rule
app/Http/Middleware/EnsureUserIsNotBlocked.php  session ejection
app/Http/Requests/Auth/LoginRequest.php         blocked-login message
```

### Changed during the final Phase 7 audit

```text
app/Http/Controllers/ReportController.php       reject reports on comments under a hidden post
app/Http/Controllers/AdminReportController.php  hiding resolves all pending reports for the content
app/Http/Controllers/AdminClassController.php   "hidden (cancelled)" flash
resources/views/components/post-card.blade.php  no report action/modal on a hidden post
resources/views/comments/_comment.blade.php     no report action on hidden comments/parents
resources/views/admin/reports/show.blade.php    protected/already-blocked Block states
resources/views/admin/classes/index.blade.php   "Hide (cancel)" label
resources/views/admin/users/index.blade.php     corrected table caption
tests/Feature/PhaseSevenTest.php                27 cross-cutting tests
docs/PHASE-7-DELIVERABLE.md                     this document
```

### Supporting code (unchanged, verified)

```text
app/Policies/ClassPolicy.php
app/Http/Middleware/RoleMiddleware.php
app/Support/DashboardMetrics.php
database/factories/PostFactory.php
database/factories/CommentFactory.php
database/factories/ClassRoomFactory.php
```

---

## 6. Full code

The source below is the exact code shipped for this phase. Shared models and
views are included in full where Phase 7 depends on their behaviour; the routes
section contains the Phase 7 route blocks rather than the whole file.


### 6.1 Models and requests

#### `app/Models/Report.php`

The report model: reasons, statuses, morph target and the admin labels.

```php
<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * A report against a post or a comment (FR-32 ... FR-34).
 */
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REVIEWED,
        self::STATUS_DISMISSED,
    ];

    /**
     * The reasons shown in the report form. The key is stored in the database.
     *
     * @var array<string, string>
     */
    public const REASONS = [
        'spam' => 'Spam',
        'harassment' => 'Harassment',
        'inappropriate_content' => 'Inappropriate content',
        'hate_speech' => 'Hate speech',
        'other' => 'Other',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'details',
        'status',
    ];

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    /** The user who filed the report. */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** The reported content: a Post or a Comment. */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /* ---------------------------------------------------------------
     | Scopes
     * --------------------------------------------------------------- */

    /**
     * Reports that still need a decision from an administrator (FR-34).
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Filter on one of the three statuses.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /* ---------------------------------------------------------------
     | Readable labels for the admin pages
     * --------------------------------------------------------------- */

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason]
            ?? Str::headline((string) $this->reason);
    }

    public function getStatusLabelAttribute(): string
    {
        return Str::headline((string) $this->status);
    }

    /**
     * "Post" or "Comment" - used in the moderation tables.
     */
    public function getContentTypeAttribute(): string
    {
        return $this->reportable_type === Post::class ? 'Post' : 'Comment';
    }

    /**
     * The author of the reported content. Null when the content was deleted.
     */
    public function getReportedUserAttribute(): ?User
    {
        return $this->reportable?->user;
    }

    /**
     * A short, already escaped-safe excerpt of the reported body.
     */
    public function getContentPreviewAttribute(): string
    {
        if (! $this->reportable) {
            return 'The reported content has been deleted.';
        }

        return Str::limit((string) $this->reportable->body, 140);
    }
}
```

#### `app/Models/Post.php`

Adds the reports() morph relation and the visible() scope used by moderation.

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

#### `app/Models/Comment.php`

Adds the reports() morph relation and the visible() scope used by moderation.

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

#### `app/Http/Requests/StoreReportRequest.php`

Validation for the report modal (FR-32).

```php
<?php

namespace App\Http\Requests;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the report modal (FR-32).
 */
class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'details' => trim((string) $this->input('details')),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(array_keys(Report::REASONS))],
            'details' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => __('Please choose a reason for the report.'),
            'reason.in' => __('Please choose a valid reason.'),
            'details.max' => __('Additional details may not be longer than 500 characters.'),
        ];
    }
}
```

### 6.2 Policies

#### `app/Policies/ReportPolicy.php`

A user may only report content owned by somebody else.

```php
<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Who may report which content (FR-32).
 */
class ReportPolicy
{
    /**
     * A user may report a post or comment that belongs to somebody else.
     * The duplicate check lives in the controller so that the user gets a
     * friendly flash message instead of a bare 403.
     */
    public function create(User $user, Model $reportable): bool
    {
        $ownerId = $reportable->user_id ?? null;

        return $ownerId !== null && $ownerId !== $user->id;
    }
}
```

#### `app/Policies/UserPolicy.php`

An administrator can never block or delete themselves or another administrator.

```php
<?php

namespace App\Policies;

use App\Models\User;

/**
 * User management rules for the admin area (FR-27).
 * An administrator can never block or delete themselves or another admin.
 */
class UserPolicy
{
    public function block(User $admin, User $target): bool
    {
        return $admin->isAdmin()
            && ! $admin->is($target)
            && ! $target->isAdmin();
    }

    public function unblock(User $admin, User $target): bool
    {
        return $this->block($admin, $target);
    }

    public function delete(User $admin, User $target): bool
    {
        return $this->block($admin, $target);
    }
}
```

#### `app/Policies/ClassPolicy.php`

Class room access and class moderation (SRS 5.6, FR-28).

```php
<?php

namespace App\Policies;

use App\Models\ClassRoom;
use App\Models\User;

/**
 * Authorization for live classes (FR-18, FR-20, FR-24, FR-28).
 * Laravel discovers this class automatically because it is called
 * ClassPolicy and lives next to the ClassRoom model namespace.
 */
class ClassPolicy
{
    /**
     * Who may read the class page? Everybody, unless an admin hid it (FR-28).
     * A hidden class stays visible for its own teacher and for admins.
     */
    public function view(User $user, ClassRoom $classRoom): bool
    {
        return ! $classRoom->is_hidden
            || $user->isAdmin()
            || $classRoom->teacher_id === $user->id;
    }

    /**
     * Only the teacher who created the class may change it. Admins keep
     * moderation rights over everything.
     */
    public function update(User $user, ClassRoom $classRoom): bool
    {
        return $classRoom->teacher_id === $user->id || $user->isAdmin();
    }

    /**
     * Same rule for deleting a class (FR-18).
     */
    public function delete(User $user, ClassRoom $classRoom): bool
    {
        return $this->update($user, $classRoom);
    }

    /**
     * Switching between scheduled / live / completed (FR-24).
     */
    public function changeStatus(User $user, ClassRoom $classRoom): bool
    {
        return $this->update($user, $classRoom);
    }

    /**
     * Who may open the live room (FR-20)?
     * Exactly the teacher who owns the class and the students enrolled in it.
     * Admins are moderators, not participants: they hide or delete a class
     * from the admin panel instead of entering a room with minors.
     * A class hidden by an admin is closed to everybody (FR-28).
     */
    public function join(User $user, ClassRoom $classRoom): bool
    {
        // Moderation: a hidden class cannot be joined by anybody.
        if ($classRoom->is_hidden) {
            return false;
        }

        // The teacher who owns the class.
        if ($classRoom->teacher_id === $user->id) {
            return true;
        }

        // Everybody else has to be an enrolled student of this class.
        return $classRoom->students()->whereKey($user->id)->exists();
    }
}
```

#### `app/Policies/PostPolicy.php`

Hidden posts stay readable for their author and admins; admins may delete.

```php
<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/**
 * Authorization for posts (FR-10, FR-27).
 * Laravel finds this class automatically because it sits in App\Policies and
 * is named PostPolicy for the App\Models\Post model.
 */
class PostPolicy
{
    /**
     * Who may read a post? Everybody, unless an admin hid it (FR-27) - then
     * only the author and admins still see it.
     */
    public function view(User $user, Post $post): bool
    {
        return ! $post->is_hidden
            || $user->isAdmin()
            || $user->id === $post->user_id;
    }

    /**
     * Only the author may edit their own post. Editing changes the author's
     * words, so administrators moderate content without rewriting it.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * The author may delete their own post; an administrator may remove any
     * post for moderation (FR-10, FR-28).
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->isAdmin();
    }
}
```

#### `app/Policies/CommentPolicy.php`

Hidden comments stay readable for their author and admins; admins may delete.

```php
<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

/**
 * Authorization for comments (FR-10).
 * A member can delete their own comment; administrators can delete any
 * comment for moderation. The author of the post does not own the comments
 * other people write under it, so they cannot delete them (FR-10).
 */
class CommentPolicy
{
    /**
     * Who may read a comment? Everybody, unless it was hidden (FR-27).
     */
    public function view(User $user, Comment $comment): bool
    {
        return ! $comment->is_hidden
            || $user->isAdmin()
            || $user->id === $comment->user_id;
    }

    /**
     * Delete your own comment, or any comment as an administrator.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id
            || $user->isAdmin();
    }
}
```

### 6.3 Controllers

#### `app/Http/Controllers/ReportController.php`

Reporting rules: self-report, duplicate, hidden content and hidden parent post.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * Users report an inappropriate post or comment (FR-32).
 *
 * Rules:
 *  - a user can never report their own content,
 *  - a user can report the same item only once,
 *  - the item must still be visible (a hidden item is not reportable),
 *  - every action ends with a flash message.
 */
class ReportController extends Controller
{
    public function storePost(StoreReportRequest $request, Post $post): RedirectResponse
    {
        return $this->store($request, $post);
    }

    public function storeComment(StoreReportRequest $request, Comment $comment): RedirectResponse
    {
        return $this->store($request, $comment);
    }

    /**
     * Shared implementation for posts and comments.
     */
    private function store(StoreReportRequest $request, Model $reportable): RedirectResponse
    {
        // Policy: the content must belong to somebody else.
        $this->authorize('create', [Report::class, $reportable]);

        // A hidden item is only visible to its author and admins; do not feed
        // the moderation queue with reports about content nobody can see.
        if (($reportable->is_hidden ?? false) === true) {
            abort(404);
        }

        // A comment inherits the visibility of its post: if the post was
        // hidden, the comment is gone from every page as well.
        if ($reportable instanceof Comment && $reportable->post?->is_hidden) {
            abort(404);
        }

        $user = $request->user();

        // One report per user per item (also enforced by a unique index).
        if ($reportable->reports()->where('reporter_id', $user->id)->exists()) {
            return back()->with('error', __('You have already reported this content.'));
        }

        $validated = $request->validated();

        $report = Report::create([
            'reporter_id' => $user->id,
            'reportable_type' => $reportable->getMorphClass(),
            'reportable_id' => $reportable->getKey(),
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'status' => Report::STATUS_PENDING,
        ]);

        return back()->with('success', __('Thank you. Your report (:reason) was sent to the moderators.', [
            'reason' => $report->reason_label,
        ]));
    }
}
```

#### `app/Http/Controllers/AdminReportController.php`

Moderation queue, detail page and all six moderation actions.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            ->with(['reporter:id,name,email', 'reportable'])
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

        $type = $report->reportable_type;
        $id = $report->reportable_id;

        if ($content instanceof Post && $content->image) {
            Storage::disk('public')->delete($content->image);
        }

        $content->delete();

        Report::query()
            ->where('reportable_type', $type)
            ->where('reportable_id', $id)
            ->delete();

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

#### `app/Http/Controllers/AdminUserController.php`

User search, role/blocked filters, block, unblock and delete.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        // Reports about the content this user wrote lose their subject.
        $postIds = $user->posts()->pluck('posts.id');
        $commentIds = $user->comments()->pluck('comments.id');

        Report::query()
            ->where('reportable_type', Post::class)
            ->whereIn('reportable_id', $postIds)
            ->delete();

        Report::query()
            ->where('reportable_type', Comment::class)
            ->whereIn('reportable_id', $commentIds)
            ->delete();

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $name = $user->name;

        // Posts, comments, likes, follows and enrollments go with the account
        // through the cascade rules on the foreign keys.
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

#### `app/Http/Controllers/AdminPostController.php`

Post search, visibility filter, hide/unhide and delete.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }

        $post->reports()->delete();
        $post->delete();

        return back()->with('success', __('The post has been deleted.'));
    }

    private function likePattern(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
    }
}
```

#### `app/Http/Controllers/AdminClassController.php`

Class search, status/visibility filters, hide (cancel) and delete.

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin class management (FR-28): search, filter, hide/unhide and delete.
 */
class AdminClassController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        $hidden = in_array($request->query('hidden'), ['0', '1'], true)
            ? (string) $request->query('hidden')
            : null;

        $status = in_array($request->query('status'), ClassRoom::STATUSES, true)
            ? (string) $request->query('status')
            : null;

        $classes = ClassRoom::query()
            ->with('teacher:id,name,email')
            ->withCount('students')
            ->when($term !== '', function (Builder $query) use ($term): void {
                $pattern = $this->likePattern($term);

                $query->where(function (Builder $inner) use ($pattern): void {
                    $inner->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereHas('teacher', fn (Builder $teacher) => $teacher
                            ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]));
                });
            })
            ->when($hidden !== null, fn (Builder $query) => $query->where('is_hidden', $hidden === '1'))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.classes.index', [
            'classes' => $classes,
            'term' => $term,
            'hidden' => $hidden ?? '',
            'status' => $status ?? '',
        ]);
    }

    /**
     * Hide (cancel) or restore a class.
     */
    public function toggleHidden(ClassRoom $classRoom): RedirectResponse
    {
        $classRoom->update(['is_hidden' => ! $classRoom->is_hidden]);

        return back()->with('success', $classRoom->is_hidden
            ? __('The class has been hidden (cancelled).')
            : __('The class is visible again.'));
    }

    /**
     * Delete a class. Enrollments disappear through the cascade rule.
     */
    public function destroy(ClassRoom $classRoom): RedirectResponse
    {
        $this->authorize('delete', $classRoom);

        $classRoom->delete();

        return back()->with('success', __('The class has been deleted.'));
    }

    private function likePattern(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
    }
}
```

#### `app/Http/Controllers/AdminDashboardController.php`

Admin dashboard payload including pending and recent reports.

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Support\DashboardMetrics;
use Illuminate\View\View;

/**
 * Admin dashboard (FR-2, FR-26, FR-33 chart and moderation).
 *
 * Phase 3B keeps the five-key stats contract and the 12-month "chart" data,
 * then adds trends, sparklines, four themed charts and the moderation/activity
 * panels. Expensive aggregates are cached for five minutes in DashboardMetrics.
 */
class AdminDashboardController extends Controller
{
    public function __invoke(DashboardMetrics $metrics): View
    {
        // The cached chart set always contains the 12 month series used by the
        // original test contract.
        $charts = $metrics->adminCharts('12m');

        return view('dashboards.admin', [
            'stats' => $metrics->adminStats(),
            'chart' => $charts['newUsers'],
            'trends' => $metrics->adminTrends(),
            'sparklines' => $metrics->adminSparklines(),

            // Themed chart series (FR-31).
            'roleChart' => $charts['roles'],
            'postsChart' => $charts['posts'],
            'classesChart' => $charts['classes'],

            // Newest accounts for the "recent users" table (FR-26).
            'recentUsers' => User::query()
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'email', 'role', 'photo', 'created_at']),

            // Moderation shortcuts (FR-33): cached pending counter + newest five.
            'pendingReports' => $metrics->adminPendingReports(),
            'recentReports' => Report::query()
                ->pending()
                ->with(['reporter:id,name', 'reportable'])
                ->latest()
                ->take(5)
                ->get(),

            // Latest activity panels (FR-26, FR-33).
            'recentPosts' => Post::query()
                ->visible()
                ->with('user:id,name,photo,role')
                ->withCount(['likes', 'comments'])
                ->latest()
                ->take(5)
                ->get(),
            'liveClasses' => ClassRoom::query()
                ->visible()
                ->where('status', ClassRoom::STATUS_LIVE)
                ->whereDate('date', now()->toDateString())
                ->with('teacher:id,name,photo')
                ->withCount('students')
                ->orderBy('time')
                ->take(4)
                ->get(),
        ]);
    }
}
```

### 6.4 Middleware and authentication

#### `app/Http/Middleware/EnsureUserIsNotBlocked.php`

Ends the session of an account blocked while logged in.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kicks out a user who is blocked while they are already logged in (FR-6).
 * Registered as "not.blocked" and also appended to the whole "web" group.
 */
class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Guests are not affected: this middleware only polices logged-in users.
        if ($user && $user->is_blocked) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Your account has been blocked by an administrator. Please contact support.');
        }

        return $next($request);
    }
}
```

#### `app/Http/Middleware/RoleMiddleware.php`

403 for a signed-in user with the wrong role.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Only let the request through when the logged-in user has one of the given roles.
     *
     * Usage in routes/web.php:
     *   ->middleware('role:admin')
     *   ->middleware('role:teacher,admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Not logged in yet -> send them to the login page.
        if (! $user) {
            return redirect()->route('login');
        }

        // Wrong role -> 403 Forbidden.
        if (! in_array($user->role, $roles, true)) {
            abort(403, 'You are not allowed to open this page.');
        }

        return $next($request);
    }
}
```

#### `app/Http/Requests/Auth/LoginRequest.php`

Refuses a blocked account at login with a clear message.

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // FR-6: a blocked account may not log in, even with the correct password.
        if (Auth::user()->is_blocked) {
            // Never leave a session behind.
            Auth::guard('web')->logout();

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Your account has been blocked by an administrator. Please contact support.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
```

### 6.5 Migrations and factories

#### `database/migrations/2026_10_29_100009_create_reports_table.php`

The polymorphic reports table.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reports against a post or a comment (FR-16, FR-26).
     * Polymorphic: reportable_type is App\Models\Post or App\Models\Comment.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // The user who reported the content.
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();

            // reportable_type + reportable_id + an index on both.
            $table->morphs('reportable');

            $table->text('reason');

            $table->enum('status', ['pending', 'reviewed', 'dismissed'])->default('pending');

            $table->timestamps();

            // The moderation queue is filtered by status.
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
```

#### `database/migrations/2026_10_29_140000_add_moderation_support_to_reports_table.php`

Adds details and the one-report-per-user unique index.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extra moderation data for reports (FR-32 ... FR-34).
     *
     *  - "details" is the optional free text the reporter may add.
     *  - the unique index makes a second report of the same item by the same
     *    user impossible, even if somebody bypasses the controller.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->text('details')->nullable()->after('reason');

            $table->unique(
                ['reporter_id', 'reportable_type', 'reportable_id'],
                'reports_reporter_reportable_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique('reports_reporter_reportable_unique');
            $table->dropColumn('details');
        });
    }
};
```

#### `database/factories/ReportFactory.php`

Factory states for post/comment reports and moderation statuses.

```php
<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * A report against a post by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reportable_type' => Post::class,
            'reportable_id' => Post::factory(),
            'reason' => fake()->sentence(8),
            'status' => Report::STATUS_PENDING,
        ];
    }

    /**
     * Report a post.
     */
    public function forPost(): static
    {
        return $this->state(fn (array $attributes) => [
            'reportable_type' => Post::class,
            'reportable_id' => Post::factory(),
        ]);
    }

    /**
     * Report a comment.
     */
    public function forComment(): static
    {
        return $this->state(fn (array $attributes) => [
            'reportable_type' => Comment::class,
            'reportable_id' => Comment::factory(),
        ]);
    }

    /**
     * A report that an administrator has already looked at.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_REVIEWED,
        ]);
    }

    /**
     * A report that was rejected.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_DISMISSED,
        ]);
    }
}
```

#### `database/factories/PostFactory.php`

Includes the hidden() state used by the Phase 7 tests.

```php
<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // Short enough to stay well under the 280 character limit.
            'body' => fake()->sentence(random_int(6, 16)),
            'image' => null,
            'is_hidden' => false,
        ];
    }

    /**
     * A post that an administrator has hidden (FR-27).
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }
}
```

#### `database/factories/CommentFactory.php`

Includes the hidden() state used by the Phase 7 tests.

```php
<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(random_int(4, 12)),
            'is_hidden' => false,
        ];
    }

    /**
     * A comment that an administrator has hidden.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }
}
```

#### `database/factories/ClassRoomFactory.php`

Includes the hidden() state used by the Phase 7 tests.

```php
<?php

namespace Database\Factories;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassRoom>
 */
class ClassRoomFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // A class is always created by a teacher.
            'teacher_id' => User::factory()->teacher(),
            'title' => fake()->sentence(4, false),
            'description' => fake()->paragraph(2),
            'subject' => fake()->randomElement(['Mathematics', 'Physics', 'English', 'History']),
            'date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'time' => fake()->time('H:i:s'),
            'duration' => fake()->randomElement([30, 45, 60, 90]),
            // room_name is generated by ClassRoom::booted() so it is always
            // unique and impossible to guess.
            'status' => ClassRoom::STATUS_SCHEDULED,
            'is_hidden' => false,
        ];
    }

    /**
     * A class that is scheduled in the future.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
        ]);
    }

    /**
     * A class that is running right now.
     */
    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClassRoom::STATUS_LIVE,
            'date' => now()->toDateString(),
        ]);
    }

    /**
     * A class that already took place.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClassRoom::STATUS_COMPLETED,
            'date' => fake()->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ]);
    }

    /**
     * A class hidden by an administrator (FR-28).
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }
}
```

### 6.6 Views

#### `resources/views/reports/_modal.blade.php`

The shared report modal with reason, details and inline errors.

```blade
{{-- Report modal for one post or comment (FR-32).
     Included by components/post-card and comments/_comment, so every piece of
     content carries its own form and the page keeps working without JavaScript. --}}
@php
    $isPost = $reportable instanceof \App\Models\Post;
    $prefix = $isPost ? 'post' : 'comment';
    $modalId = 'report-'.$prefix.'-'.$reportable->id;
    $action = $isPost
        ? route('reports.posts.store', $reportable)
        : route('reports.comments.store', $reportable);
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ $action }}">
                @csrf

                <div class="modal-header">
                    <h2 class="modal-title h5" id="{{ $modalId }}-label">
                        <i class="bi bi-flag me-1"></i> {{ __('Report this :type', ['type' => $prefix]) }}
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>

                <div class="modal-body">
                    <p class="small text-muted">
                        {{ __('Tell the moderators what is wrong. Your report is private.') }}
                    </p>

                    <div class="mb-3">
                        <label for="{{ $modalId }}-reason" class="form-label">
                            {{ __('Reason') }} <span class="text-danger">*</span>
                        </label>
                        <select name="reason" id="{{ $modalId }}-reason" class="form-select" required>
                            <option value="">{{ __('Choose a reason') }}</option>
                            @foreach (\App\Models\Report::REASONS as $value => $label)
                                <option value="{{ $value }}" @selected(old('reason') === $value)>
                                    {{ __($label) }}
                                </option>
                            @endforeach
                        </select>
                        @error('reason')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label for="{{ $modalId }}-details" class="form-label">
                            {{ __('Additional details') }} <span class="text-muted">({{ __('optional') }})</span>
                        </label>
                        <textarea name="details" id="{{ $modalId }}-details" rows="3" maxlength="500"
                                  class="form-control" placeholder="{{ __('What happened?') }}">{{ old('details') }}</textarea>
                        <div class="form-text">{{ __('At most 500 characters.') }}</div>
                        @error('details')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-flag me-1"></i> {{ __('Send report') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

#### `resources/views/components/post-card.blade.php`

Post card: report action and modal, hidden-content guard.

```blade
{{-- A single feed post (FR-7, FR-8, FR-9, FR-10, FR-12, FR-13).
     Buttons are real forms, so everything still works without JavaScript;
     resources/js/feed.js upgrades them to fetch() calls.

     Usage:
       <x-post-card :post="$post" />
       <x-post-card :post="$post" :comments="$comments" />  (feed: expandable) --}}
@props([
    'post',
    'comments' => null,
])

@php
    // Injected so the body can be turned into hashtag links (FR-13).
    $hashtagService = app(\App\Services\HashtagService::class);

    $author = $post->user;
    // Prefer the eager loaded counts so the component never fires a query itself.
    $likesCount = $post->likes_count ?? $post->likes()->count();
    $commentsCount = $post->comments_count ?? $post->comments()->visible()->count();
    // "liked by me" comes from the withExists() in Post::scopeWithFeedData().
    $likedByMe = (bool) ($post->liked_by_me ?? $post->isLikedBy(auth()->user()));
    $isMine = auth()->id() === $post->user_id;

    // On the feed three comments are enough; the rest live on the detail page.
    $previewComments = $comments?->take(3) ?? collect();

    // Colour of the little role badge next to the name.
    $roleColor = match ($author?->role) {
        \App\Models\User::ROLE_ADMIN => 'dark',
        \App\Models\User::ROLE_TEACHER => 'primary',
        default => 'success',
    };

    // Intrinsic image size (cached) so the card reserves its height before the
    // photo loads. The upload service already caps post images at 1600px.
    $imageDimensions = $post->imageDimensions();
    $imageUrl = $post->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) : null;
@endphp

<article class="card edu-card" data-post-card="{{ $post->id }}">
    <div class="card-body">
        {{-- Author, role badge and publishing time --}}
        <header class="d-flex align-items-start gap-3 mb-2">
            <x-avatar :user="$author" size="md" class="flex-shrink-0" />

            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if ($author)
                        <a href="{{ route('users.show', $author) }}"
                           class="fw-semibold text-decoration-none text-reset">{{ $author->name }}</a>
                    @else
                        <span class="fw-semibold">{{ __('Deleted user') }}</span>
                    @endif

                    <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $author?->role }}</span>

                    @if ($post->is_hidden)
                        {{-- Hidden by an administrator (FR-28) --}}
                        <span class="badge text-bg-warning">{{ __('hidden') }}</span>
                    @endif
                </div>

                <div class="small text-muted">
                    <a href="{{ route('posts.show', $post) }}" class="text-muted text-decoration-none">
                        <time datetime="{{ $post->created_at?->toIso8601String() }}">
                            {{ $post->created_at?->diffForHumans() }}
                        </time>
                    </a>
                </div>
            </div>

            {{-- Owner menu (edit / delete) + report action (FR-8, FR-32) --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-link text-muted px-1" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Post actions') }}">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @can('update', $post)
                        <li>
                            <a class="dropdown-item" href="{{ route('posts.edit', $post) }}">
                                <i class="bi bi-pencil me-1"></i> {{ __('Edit post') }}
                            </a>
                        </li>
                    @endcan

                    @can('delete', $post)
                        <li>
                            {{-- Opens the Bootstrap confirmation modal rendered below. --}}
                            <button type="button" class="dropdown-item text-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#delete-post-{{ $post->id }}">
                                <i class="bi bi-trash me-1"></i> {{ __('Delete post') }}
                            </button>
                        </li>
                    @endcan

                    {{-- Reporting a visible post that belongs to somebody else (FR-32).
                         Hidden content is not reportable, so no dead action is shown. --}}
                    @if (! $post->is_hidden && auth()->id() !== $post->user_id)
                        <li>
                            <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                    data-bs-target="#report-post-{{ $post->id }}">
                                <i class="bi bi-flag me-1"></i> {{ __('Report post') }}
                            </button>
                        </li>
                    @endif
                </ul>
            </div>
        </header>

        {{-- Body: escaped first, hashtags linked afterwards - so no XSS (FR-13) --}}
        <p class="post-body mb-2">{!! $hashtagService->linkify($post->body) !!}</p>

        @if ($post->image)
            <img src="{{ $imageUrl }}"
                 @if ($imageDimensions)
                     width="{{ $imageDimensions['width'] }}"
                     height="{{ $imageDimensions['height'] }}"
                     srcset="{{ $imageUrl }} {{ $imageDimensions['width'] }}w"
                     sizes="(max-width: 575.98px) 100vw, (max-width: 991.98px) 60vw, 720px"
                 @endif
                 alt="{{ __('Image attached to the post') }}"
                 class="img-fluid rounded-3 mb-2 edu-post-image"
                 loading="lazy" decoding="async">
        @endif

        {{-- Counters + actions --}}
        <footer class="d-flex align-items-center gap-2 gap-sm-3 small border-top pt-2 mt-3">
            {{-- Like: a form, so it still works without JavaScript --}}
            <form method="POST"
                  action="{{ $likedByMe ? route('posts.unlike', $post) : route('posts.like', $post) }}"
                  data-like-form
                  data-liked="{{ $likedByMe ? '1' : '0' }}"
                  data-like-url="{{ route('posts.like', $post) }}"
                  data-unlike-url="{{ route('posts.unlike', $post) }}"
                  class="d-inline">
                @csrf
                @if ($likedByMe)
                    @method('DELETE')
                @endif
                <button type="submit"
                        class="btn btn-sm {{ $likedByMe ? 'btn-danger' : 'btn-outline-secondary' }}"
                        data-like-button aria-pressed="{{ $likedByMe ? 'true' : 'false' }}"
                        aria-label="{{ $likedByMe ? __('Unlike this post') : __('Like this post') }}"
                        title="{{ $likedByMe ? __('Unlike') : __('Like') }}">
                    <i class="bi {{ $likedByMe ? 'bi-heart-fill' : 'bi-heart' }} me-1"
                       aria-hidden="true"></i>
                    <span data-like-count>{{ $likesCount }}</span>
                </button>
            </form>

            @if ($comments !== null)
                {{-- Expandable comments, exclusively on the feed (FR-10). --}}
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="collapse"
                        data-bs-target="#post-comments-{{ $post->id }}"
                        aria-expanded="false"
                        aria-controls="post-comments-{{ $post->id }}"
                        title="{{ __('Comments') }}">
                    <i class="bi bi-chat me-1" aria-hidden="true"></i>
                    <span data-comment-count>{{ $commentsCount }}</span>
                </button>
            @else
                <a href="{{ route('posts.show', $post) }}#comments"
                   class="btn btn-sm btn-outline-secondary" title="{{ __('Comments') }}">
                    <i class="bi bi-chat me-1" aria-hidden="true"></i>
                    <span data-comment-count>{{ $commentsCount }}</span>
                </a>
            @endif

            <span class="ms-auto text-muted">
                @if ($isMine)
                    <i class="bi bi-person-check me-1" aria-hidden="true"></i>{{ __('Your post') }}
                @endif
            </span>
        </footer>
    </div>

    @if ($comments !== null)
        {{-- Latest three comments + the composer, without leaving the feed. --}}
        <div class="collapse" id="post-comments-{{ $post->id }}">
            <div class="card-footer bg-transparent border-top pt-3">
                <div data-comment-region>
                    <form method="POST" action="{{ route('comments.store', $post) }}"
                          data-comment-form class="mb-2">
                        @csrf
                        <label for="comment-body-{{ $post->id }}" class="visually-hidden">
                            {{ __('Your comment') }}
                        </label>
                        <textarea name="body" id="comment-body-{{ $post->id }}" rows="2"
                                  maxlength="280" required
                                  class="form-control form-control-sm mb-2"
                                  placeholder="{{ __('Write a comment...') }}"></textarea>
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div class="form-text mt-0">{{ __('Up to 280 characters.') }}</div>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-send me-1" aria-hidden="true"></i> {{ __('Comment') }}
                            </button>
                        </div>
                    </form>

                    <div class="mt-2" data-comment-list>
                        @forelse ($previewComments as $comment)
                            @include('comments._comment', ['comment' => $comment])
                        @empty
                            <p class="text-muted small mb-0" data-comment-empty>
                                {{ __('No comments yet. Be the first one!') }}
                            </p>
                        @endforelse
                    </div>

                    @if ($commentsCount > $previewComments->count())
                        <a href="{{ route('posts.show', $post) }}#comments"
                           class="small fw-semibold text-decoration-none d-inline-block mt-2">
                            {{ trans_choice('View all :count comment|View all :count comments', $commentsCount, ['count' => $commentsCount]) }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Extra content supplied by the page (report form, moderation tools...) --}}
    @if ($slot->isNotEmpty())
        <div class="card-footer bg-transparent border-top-0 pt-0">
            {{ $slot }}
        </div>
    @endif
</article>

@can('delete', $post)
    <x-confirm-delete-modal
        :id="'delete-post-'.$post->id"
        :action="route('posts.destroy', $post)"
        :title="__('Delete this post?')"
        :message="__('This removes the post, its comments, likes and image. This cannot be undone.')"
        :confirm-label="__('Delete post')"
        :show-trigger="false" />
@endcan

{{-- Each visible post owns its report modal (FR-32). --}}
@if (! $post->is_hidden && auth()->id() !== $post->user_id)
    @include('reports._modal', ['reportable' => $post])
@endif
```

#### `resources/views/components/confirm-delete-modal.blade.php`

Reusable Bootstrap confirmation modal.

```blade
{{-- Reusable delete confirmation modal.
     Usage:
       <x-confirm-delete-modal
           id="delete-class-{{ $classRoom->id }}"
           :action="route('classes.destroy', $classRoom)"
           title="Delete this class?"
           message="The enrollments are removed as well."
       />

     The trigger button and the Bootstrap modal are rendered together, so the
     component works without any extra JavaScript. Pass :show-trigger="false"
     when you only need the modal markup. --}}
@props([
    'id',
    'action',
    'method' => 'DELETE',
    'title' => 'Delete this item?',
    'message' => 'This action cannot be undone.',
    'confirmLabel' => 'Delete',
    'confirmClass' => 'btn btn-danger',
    'triggerLabel' => 'Delete',
    'triggerClass' => 'btn btn-sm btn-outline-danger',
    'triggerIcon' => 'bi-trash',
    'showTrigger' => true,
])

@if ($showTrigger)
    <button type="button" class="{{ $triggerClass }}" data-bs-toggle="modal" data-bs-target="#{{ $id }}">
        <i class="bi {{ $triggerIcon }} me-1" aria-hidden="true"></i>{{ $triggerLabel }}
    </button>
@endif

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="{{ $id }}-title">{{ $title }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">{{ $message }}</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>

                <form method="POST" action="{{ $action }}">
                    @csrf
                    @method($method)
                    <button type="submit" class="{{ $confirmClass }}">
                        {{ $confirmLabel }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
```

#### `resources/views/comments/_comment.blade.php`

Comment card: report flag and visibility guard.

```blade
{{-- One comment under a post (FR-13).
     Included by posts/show and returned as HTML by CommentController@store. --}}
@php
    $author = $comment->user;

    $roleColor = match ($author?->role) {
        \App\Models\User::ROLE_ADMIN => 'dark',
        \App\Models\User::ROLE_TEACHER => 'primary',
        default => 'success',
    };

    // Hidden comments and comments under a hidden post are not reportable.
    // "$post" is the surrounding post card / post page when there is one.
    $parentPostHidden = isset($post) && $post instanceof \App\Models\Post && $post->is_hidden;
    $canReport = ! $comment->is_hidden
        && ! $parentPostHidden
        && auth()->id() !== $comment->user_id;
@endphp

<div class="d-flex gap-3 py-3 border-bottom" data-comment-id="{{ $comment->id }}">
    @if ($author?->photo)
        <img src="{{ $author->photoUrl() }}" alt="{{ $author->name }}" class="avatar" width="40" height="40" loading="lazy" decoding="async">
    @else
        <span class="avatar bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0"
              role="img" aria-label="{{ $author?->name ?? __('Deleted user') }}">
            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) $author?->name, 0, 1)) }}
        </span>
    @endif

    <div class="flex-grow-1">
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if ($author)
                <a href="{{ route('users.show', $author) }}"
                   class="fw-semibold small text-decoration-none text-reset">{{ $author->name }}</a>
            @else
                <span class="fw-semibold small">{{ __('Deleted user') }}</span>
            @endif

            <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $author?->role }}</span>

            <span class="text-muted small">{{ $comment->created_at?->diffForHumans() }}</span>
        </div>

        {{-- Comment text is escaped by Blade (no HTML allowed). --}}
        <div class="post-body small">{{ $comment->body }}</div>
    </div>

    @can('delete', $comment)
        {{-- AJAX with a normal POST fallback; CommentPolicy protects the route. --}}
        <form method="POST" action="{{ route('comments.destroy', $comment) }}"
              data-delete-comment-form
              data-confirm="{{ __('Delete this comment?') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-link text-danger px-1" title="{{ __('Delete comment') }}">
                <i class="bi bi-trash"></i>
            </button>
        </form>
    @endcan

    {{-- Report somebody else's visible comment (FR-32). --}}
    @if ($canReport)
        <button type="button" class="btn btn-sm btn-link text-muted px-1"
                data-bs-toggle="modal" data-bs-target="#report-comment-{{ $comment->id }}"
                title="{{ __('Report comment') }}" aria-label="{{ __('Report comment') }}">
            <i class="bi bi-flag"></i>
        </button>
    @endif
</div>

@if ($canReport)
    @include('reports._modal', ['reportable' => $comment])
@endif
```

#### `resources/views/admin/reports/index.blade.php`

Moderation queue with status/type/search filters.

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Reports') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Reports') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-flag me-1 text-edu" aria-hidden="true"></i> {{ __('Reports') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Review what the community reported (FR-33, FR-34).') }}</p>
            </div>
            <span class="badge text-bg-warning">
                <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>
                {{ $counts[\App\Models\Report::STATUS_PENDING] ?? 0 }} {{ __('pending') }}
            </span>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        {{-- Filters --}}
        <section class="card edu-card mb-3" aria-labelledby="report-filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="report-filters-title">{{ __('Filter reports') }}</h2>

                <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="report-q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="report-q" value="{{ $term }}" class="form-control"
                                   placeholder="{{ __('Reason, details, reporter or content') }}" maxlength="150">
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="report-status" class="form-label small">{{ __('Status') }}</label>
                        <select name="status" id="report-status" class="form-select">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach (\App\Models\Report::STATUSES as $value)
                                <option value="{{ $value }}" @selected($status === $value)>
                                    {{ __(\Illuminate\Support\Str::headline($value)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label for="report-type" class="form-label small">{{ __('Type') }}</label>
                        <select name="type" id="report-type" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="post" @selected($type === 'post')>{{ __('Posts') }}</option>
                            <option value="comment" @selected($type === 'comment')>{{ __('Comments') }}</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>

                {{-- One-tap status chips, keeping the other filters. --}}
                <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by status') }}">
                    <a class="edu-filter-chip {{ $status === '' ? 'is-active' : '' }}"
                       href="{{ route('admin.reports.index', array_filter(['q' => $term, 'type' => $type])) }}">
                        {{ __('All statuses') }}
                    </a>
                    @foreach (\App\Models\Report::STATUSES as $value)
                        <a class="edu-filter-chip {{ $status === $value ? 'is-active' : '' }}"
                           href="{{ route('admin.reports.index', array_filter(['q' => $term, 'status' => $value, 'type' => $type])) }}">
                            {{ __(\Illuminate\Support\Str::headline($value)) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($reports->isEmpty())
            <x-empty-state icon="bi-shield-check"
                           :title="__('No reports match these filters.')"
                           :message="__('The community is calm right now. Try another status or reset the filters.')">
                <a href="{{ route('admin.reports.index') }}" class="btn btn-primary">{{ __('Reset the filters') }}</a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('All reports, newest first') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('Content') }}</th>
                                <th scope="col">{{ __('Reported user') }}</th>
                                <th scope="col">{{ __('Reporter') }}</th>
                                <th scope="col">{{ __('Reason') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col">{{ __('Date') }}</th>
                                <th scope="col" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                @php($content = $report->reportable)
                                <tr>
                                    <td data-label="ID" class="text-muted">{{ $report->id }}</td>
                                    <td data-label="{{ __('Content') }}" class="edu-admin-post-cell">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <span class="badge bg-body-secondary text-body border">{{ $report->content_type }}</span>
                                            @if ($content && $content->is_hidden)
                                                <span class="badge text-bg-warning">{{ __('hidden') }}</span>
                                            @endif
                                        </div>
                                        <div class="small">{{ $report->content_preview }}</div>
                                    </td>
                                    <td data-label="{{ __('Reported user') }}">
                                        @if ($report->reported_user)
                                            <a href="{{ route('users.show', $report->reported_user) }}"
                                               class="text-decoration-none text-reset">{{ $report->reported_user->name }}</a>
                                        @else
                                            <span class="text-muted small">{{ __('Deleted') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Reporter') }}">
                                        <a href="{{ route('users.show', $report->reporter) }}"
                                           class="text-decoration-none text-reset">{{ $report->reporter?->name ?? __('Deleted') }}</a>
                                    </td>
                                    <td data-label="{{ __('Reason') }}">{{ $report->reason_label }}</td>
                                    <td data-label="{{ __('Status') }}">
                                        <x-status-badge :status="$report->status" />
                                    </td>
                                    <td data-label="{{ __('Date') }}" class="text-nowrap small text-muted">
                                        <time datetime="{{ $report->created_at?->toIso8601String() }}">{{ $report->created_at?->format('d M Y H:i') }}</time>
                                    </td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <a href="{{ route('admin.reports.show', $report) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ __('Review') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($reports->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$reports" />
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
```

#### `resources/views/admin/reports/show.blade.php`

Report detail and the six moderation actions.

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Report #:id', ['id' => $report->id]) }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">{{ __('Reports') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">#{{ $report->id }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-flag me-1 text-edu" aria-hidden="true"></i> {{ __('Report #:id', ['id' => $report->id]) }}
                </h1>
                <p class="small text-muted mb-0">
                    {{ $report->reason_label }}
                    <span aria-hidden="true">&middot;</span>
                    {{ $report->created_at?->format('d M Y H:i') }}
                </p>
            </div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to reports') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        @php
            $content = $report->reportable;
            $reportedUser = $report->reported_user;
            // Blocking is only possible for an active, non-admin account (FR-27).
            $canBlockReportedUser = $reportedUser !== null
                && ! $reportedUser->isAdmin()
                && ! $reportedUser->is_blocked;
        @endphp

        <div class="row g-4">
            <div class="col-12 col-lg-7">
                {{-- The reported content --}}
                <section class="card edu-card mb-4" aria-labelledby="reported-content-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <h2 class="h6 fw-bold mb-0" id="reported-content-title">{{ __('Reported content') }}</h2>
                            <span class="badge bg-body-secondary text-body border">{{ $report->content_type }}</span>
                            <x-status-badge :status="$report->status" />
                            @if ($content && $content->is_hidden)
                                <span class="badge text-bg-warning">{{ __('hidden') }}</span>
                            @endif
                        </div>

                        @if ($content)
                            <span class="small text-muted">
                                {{ __('by') }} {{ $content->user?->name ?? __('deleted user') }}
                            </span>
                        @endif
                    </div>

                    <div class="card-body">
                        @if (! $content)
                            <x-empty-state icon="bi-trash"
                                           :title="__('The reported content has been deleted.')"
                                           :message="__('Nothing left to moderate. You can still review or dismiss the report.')" />
                        @else
                            <p class="post-body mb-3 text-break">{{ $content->body }}</p>

                            @if ($content instanceof \App\Models\Post && $content->image)
                                @php($reportedDimensions = $content->imageDimensions())
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($content->image) }}"
                                     @if ($reportedDimensions)
                                         width="{{ $reportedDimensions['width'] }}"
                                         height="{{ $reportedDimensions['height'] }}"
                                     @endif
                                     alt="{{ __('Reported image') }}" class="img-fluid rounded mb-3"
                                     loading="lazy" decoding="async">
                            @endif

                            @if ($content instanceof \App\Models\Comment)
                                <a href="{{ route('posts.show', $content->post_id) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i> {{ __('Open the post') }}
                                </a>
                            @else
                                <a href="{{ route('posts.show', $content) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i> {{ __('Open the post') }}
                                </a>
                            @endif
                        @endif
                    </div>
                </section>

                {{-- Other reports about the same content --}}
                <section class="card edu-card" aria-labelledby="related-reports-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="related-reports-title">{{ __('Other reports on this content') }}</h2>
                    </div>

                    <div class="list-group list-group-flush">
                        @forelse ($related as $other)
                            <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="small min-w-0">
                                    <span class="fw-semibold">{{ $other->reporter?->name ?? __('Deleted user') }}</span>
                                    <span aria-hidden="true">&middot;</span> {{ $other->reason_label }}
                                    <div class="text-muted">{{ $other->created_at?->diffForHumans() }}</div>
                                </div>
                                <x-status-badge :status="$other->status" />
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted small py-4">
                                {{ __('No other reports for this content.') }}
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-12 col-lg-5">
                <div class="edu-sticky-aside">
                    {{-- Report details --}}
                    <section class="card edu-card mb-4" aria-labelledby="report-details-title">
                        <div class="card-header">
                            <h2 class="h6 fw-bold mb-0" id="report-details-title">{{ __('Report details') }}</h2>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between gap-3">
                                <span class="text-muted">{{ __('Reporter') }}</span>
                                <span class="fw-semibold text-end text-break">{{ $report->reporter?->name ?? __('Deleted') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between gap-3">
                                <span class="text-muted">{{ __('Reported user') }}</span>
                                <span class="fw-semibold text-end text-break">{{ $report->reported_user?->name ?? __('Deleted') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between gap-3">
                                <span class="text-muted">{{ __('Reason') }}</span>
                                <span class="fw-semibold text-end">{{ $report->reason_label }}</span>
                            </li>
                            <li class="list-group-item">
                                <div class="text-muted mb-1">{{ __('Details') }}</div>
                                <div class="small text-break">{{ $report->details ?: __('No extra details were given.') }}</div>
                            </li>
                        </ul>
                    </section>

                    {{-- Moderation actions (FR-33) --}}
                    <section class="card edu-card" aria-labelledby="moderation-title">
                        <div class="card-header">
                            <h2 class="h6 fw-bold mb-0" id="moderation-title">{{ __('Actions') }}</h2>
                        </div>
                        <div class="card-body d-flex flex-column gap-2">
                            @if ($content)
                                <form method="POST" action="{{ route('admin.reports.hide', $report) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-warning w-100" @disabled($content->is_hidden)>
                                        <i class="bi bi-eye-slash me-1" aria-hidden="true"></i> {{ __('Hide the content') }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.reports.unhide', $report) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-warning w-100" @disabled(! $content->is_hidden)>
                                        <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ __('Unhide the content') }}
                                    </button>
                                </form>

                                <button type="button" class="btn btn-danger w-100"
                                        data-bs-toggle="modal" data-bs-target="#delete-report-content-{{ $report->id }}">
                                    <i class="bi bi-trash me-1" aria-hidden="true"></i> {{ __('Delete the content') }}
                                </button>
                            @endif

                            @if ($canBlockReportedUser)
                                <button type="button" class="btn btn-outline-danger w-100"
                                        data-bs-toggle="modal" data-bs-target="#block-report-user-{{ $report->id }}">
                                    <i class="bi bi-person-slash me-1" aria-hidden="true"></i>
                                    {{ __('Block :name', ['name' => $reportedUser->name]) }}
                                </button>
                            @elseif ($reportedUser?->is_blocked)
                                <button type="button" class="btn btn-outline-secondary w-100" disabled>
                                    <i class="bi bi-person-check me-1" aria-hidden="true"></i>
                                    {{ __('This user is already blocked') }}
                                </button>
                            @elseif ($reportedUser?->isAdmin())
                                <button type="button" class="btn btn-outline-secondary w-100" disabled
                                        title="{{ __('Administrator accounts cannot be blocked.') }}">
                                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
                                    {{ __('Administrator accounts are protected') }}
                                </button>
                            @endif

                            <hr class="my-1">

                            <form method="POST" action="{{ route('admin.reports.reviewed', $report) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-success w-100" @disabled($report->status === \App\Models\Report::STATUS_REVIEWED)>
                                    <i class="bi bi-check2-circle me-1" aria-hidden="true"></i> {{ __('Mark as reviewed') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-secondary w-100" @disabled($report->status === \App\Models\Report::STATUS_DISMISSED)>
                                    <i class="bi bi-x-circle me-1" aria-hidden="true"></i> {{ __('Dismiss the report') }}
                                </button>
                            </form>
                        </div>
                    </section>

                    {{-- Destructive actions confirm in a Bootstrap modal. --}}
                    @if ($content)
                        <x-confirm-delete-modal
                            :id="'delete-report-content-'.$report->id"
                            :action="route('admin.reports.content.destroy', $report)"
                            :title="__('Delete this content?')"
                            :message="__('The content and every report about it are deleted. This cannot be undone.')"
                            :confirm-label="__('Delete content')"
                            :show-trigger="false" />
                    @endif

                    @if ($canBlockReportedUser)
                        <x-confirm-delete-modal
                            :id="'block-report-user-'.$report->id"
                            :action="route('admin.reports.block-user', $report)"
                            method="POST"
                            :title="__('Block :name?', ['name' => $reportedUser->name])"
                            :message="__('They will be logged out immediately and cannot sign in until an administrator unblocks them.')"
                            :confirm-label="__('Block account')"
                            confirm-class="btn btn-warning"
                            :show-trigger="false" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

#### `resources/views/admin/users/index.blade.php`

User management table, filters and confirmation modals.

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Users') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Users') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-people me-1 text-edu" aria-hidden="true"></i> {{ __('Users') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Search, block, unblock and delete accounts (FR-27).') }}</p>
            </div>
            <span class="edu-chip">
                <i class="bi bi-person-lines-fill" aria-hidden="true"></i>
                {{ $users->total() }} {{ __('accounts') }}
            </span>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <section class="card edu-card mb-3" aria-labelledby="user-filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="user-filters-title">{{ __('Filter users') }}</h2>

                <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="user-q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="user-q" value="{{ $term }}" class="form-control"
                                   placeholder="{{ __('Name or email') }}" maxlength="150">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="user-role" class="form-label small">{{ __('Role') }}</label>
                        <select name="role" id="user-role" class="form-select">
                            <option value="">{{ __('All roles') }}</option>
                            @foreach ([
                                \App\Models\User::ROLE_ADMIN => __('Admin'),
                                \App\Models\User::ROLE_TEACHER => __('Teacher'),
                                \App\Models\User::ROLE_STUDENT => __('Student'),
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="user-blocked" class="form-label small">{{ __('Status') }}</label>
                        <select name="blocked" id="user-blocked" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="0" @selected($blocked === '0')>{{ __('Active') }}</option>
                            <option value="1" @selected($blocked === '1')>{{ __('Blocked') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    </div>
                </form>

                {{-- One-tap role chips, keeping the search term. --}}
                <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by role') }}">
                    <a class="edu-filter-chip {{ $role === '' ? 'is-active' : '' }}"
                       href="{{ route('admin.users.index', array_filter(['q' => $term, 'blocked' => $blocked])) }}">
                        {{ __('All roles') }}
                    </a>
                    @foreach ([
                        \App\Models\User::ROLE_ADMIN => __('Admin'),
                        \App\Models\User::ROLE_TEACHER => __('Teacher'),
                        \App\Models\User::ROLE_STUDENT => __('Student'),
                    ] as $value => $label)
                        <a class="edu-filter-chip {{ $role === $value ? 'is-active' : '' }}"
                           href="{{ route('admin.users.index', array_filter(['q' => $term, 'role' => $value, 'blocked' => $blocked])) }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($users->isEmpty())
            <x-empty-state icon="bi-person-x"
                           :title="__('No users match these filters.')"
                           :message="__('Try a different name, role or status, or reset the filters.')">
                <a href="{{ route('admin.users.index') }}" class="btn btn-primary">{{ __('Reset the filters') }}</a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('All accounts, sorted by name') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('User') }}</th>
                                <th scope="col">{{ __('Role') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col" class="text-end">{{ __('Posts') }}</th>
                                <th scope="col" class="text-end">{{ __('Classes') }}</th>
                                <th scope="col">{{ __('Joined') }}</th>
                                <th scope="col" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                @php
                                    $roleColor = match ($user->role) {
                                        \App\Models\User::ROLE_ADMIN => 'dark',
                                        \App\Models\User::ROLE_TEACHER => 'primary',
                                        default => 'success',
                                    };
                                    $protected = auth()->id() === $user->id || $user->isAdmin();
                                @endphp
                                <tr>
                                    <td data-label="{{ __('User') }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <x-avatar :user="$user" size="sm" />
                                            <div class="min-w-0">
                                                <a href="{{ route('users.show', $user) }}"
                                                   class="fw-semibold text-decoration-none text-reset text-break">{{ $user->name }}</a>
                                                <div class="small text-muted text-break">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('Role') }}">
                                        <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $user->role }}</span>
                                    </td>
                                    <td data-label="{{ __('Status') }}">
                                        @if ($user->is_blocked)
                                            <span class="badge text-bg-danger">{{ __('Blocked') }}</span>
                                        @else
                                            <span class="badge text-bg-success">{{ __('Active') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Posts') }}" class="text-end">{{ $user->posts_count }}</td>
                                    <td data-label="{{ __('Classes') }}" class="text-end">{{ $user->classes_count }}</td>
                                    <td data-label="{{ __('Joined') }}" class="text-nowrap small text-muted">
                                        <time datetime="{{ $user->created_at?->toIso8601String() }}">{{ $user->created_at?->format('d M Y') }}</time>
                                    </td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            @if ($user->is_blocked)
                                                @can('unblock', $user)
                                                    <form method="POST" action="{{ route('admin.users.unblock', $user) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            <i class="bi bi-person-check me-1" aria-hidden="true"></i> {{ __('Unblock') }}
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('block', $user)
                                                    {{-- Opens the Bootstrap confirmation modal rendered under the table. --}}
                                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                                            data-bs-toggle="modal" data-bs-target="#block-user-{{ $user->id }}"
                                                            aria-label="{{ __('Block :name', ['name' => $user->name]) }}">
                                                        <i class="bi bi-person-slash me-1" aria-hidden="true"></i> {{ __('Block') }}
                                                    </button>
                                                @endcan
                                            @endif

                                            @can('delete', $user)
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal" data-bs-target="#delete-user-{{ $user->id }}"
                                                        title="{{ __('Delete') }}" aria-label="{{ __('Delete :name', ['name' => $user->name]) }}">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            @endcan

                                            @if ($protected)
                                                <span class="text-muted small align-self-center">{{ __('protected') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$users" />
                    </div>
                @endif
            </div>

            {{-- Confirmations live outside the table so the markup stays valid. --}}
            @foreach ($users as $user)
                @can('block', $user)
                    <x-confirm-delete-modal
                        :id="'block-user-'.$user->id"
                        :action="route('admin.users.block', $user)"
                        method="PATCH"
                        :title="__('Block :name?', ['name' => $user->name])"
                        :message="__('They will be logged out immediately and cannot sign in until you unblock them.')"
                        :confirm-label="__('Block account')"
                        confirm-class="btn btn-warning"
                        :show-trigger="false" />
                @endcan

                @can('delete', $user)
                    <x-confirm-delete-modal
                        :id="'delete-user-'.$user->id"
                        :action="route('admin.users.destroy', $user)"
                        :title="__('Delete :name?', ['name' => $user->name])"
                        :message="__('This deletes the account, their posts, comments and reports. This cannot be undone.')"
                        :confirm-label="__('Delete account')"
                        :show-trigger="false" />
                @endcan
            @endforeach
        @endif
    </div>
</x-app-layout>
```

#### `resources/views/admin/posts/index.blade.php`

Post management table and moderation actions.

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Posts') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Posts') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-chat-square-text me-1 text-edu" aria-hidden="true"></i> {{ __('Posts') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Hide or delete inappropriate posts (FR-28).') }}</p>
            </div>
            <span class="edu-chip">
                <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                {{ $posts->total() }} {{ __('posts') }}
            </span>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <section class="card edu-card mb-3" aria-labelledby="post-filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="post-filters-title">{{ __('Filter posts') }}</h2>

                <form method="GET" action="{{ route('admin.posts.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-6">
                        <label for="post-q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="post-q" value="{{ $term }}" class="form-control"
                                   placeholder="{{ __('Post text or author name') }}" maxlength="150">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="post-hidden" class="form-label small">{{ __('Visibility') }}</label>
                        <select name="hidden" id="post-hidden" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="0" @selected($hidden === '0')>{{ __('Visible') }}</option>
                            <option value="1" @selected($hidden === '1')>{{ __('Hidden') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by visibility') }}">
                    <a class="edu-filter-chip {{ $hidden === '' ? 'is-active' : '' }}"
                       href="{{ route('admin.posts.index', array_filter(['q' => $term])) }}">{{ __('All') }}</a>
                    <a class="edu-filter-chip {{ $hidden === '0' ? 'is-active' : '' }}"
                       href="{{ route('admin.posts.index', array_filter(['q' => $term, 'hidden' => '0'])) }}">{{ __('Visible') }}</a>
                    <a class="edu-filter-chip {{ $hidden === '1' ? 'is-active' : '' }}"
                       href="{{ route('admin.posts.index', array_filter(['q' => $term, 'hidden' => '1'])) }}">{{ __('Hidden') }}</a>
                </div>
            </div>
        </section>

        @if ($posts->isEmpty())
            <x-empty-state icon="bi-inbox"
                           :title="__('No posts match these filters.')"
                           :message="__('Try a different search or visibility, or reset the filters.')">
                <a href="{{ route('admin.posts.index') }}" class="btn btn-primary">{{ __('Reset the filters') }}</a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('All posts, newest first') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Author') }}</th>
                                <th scope="col">{{ __('Post') }}</th>
                                <th scope="col" class="text-end">{{ __('Reports') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col">{{ __('Posted') }}</th>
                                <th scope="col" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($posts as $post)
                                <tr>
                                    <td data-label="{{ __('Author') }}" class="text-nowrap">
                                        <a href="{{ route('users.show', $post->user) }}" class="text-decoration-none text-reset">
                                            {{ $post->user?->name ?? __('Deleted user') }}
                                        </a>
                                    </td>
                                    <td data-label="{{ __('Post') }}" class="edu-admin-post-cell">
                                        <a href="{{ route('posts.show', $post) }}" class="text-decoration-none text-reset">
                                            {{ \Illuminate\Support\Str::limit($post->body, 120) }}
                                        </a>
                                        @if ($post->image)
                                            <i class="bi bi-image text-muted ms-1" title="{{ __('Has an image') }}"
                                               aria-label="{{ __('Has an image') }}"></i>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Reports') }}" class="text-end">
                                        @if ($post->reports_count > 0)
                                            <span class="badge text-bg-warning">{{ $post->reports_count }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Status') }}">
                                        @if ($post->is_hidden)
                                            <span class="badge text-bg-warning">{{ __('Hidden') }}</span>
                                        @else
                                            <span class="badge text-bg-success">{{ __('Visible') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Posted') }}" class="text-nowrap small text-muted">
                                        <time datetime="{{ $post->created_at?->toIso8601String() }}">{{ $post->created_at?->format('d M Y H:i') }}</time>
                                    </td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <form method="POST" action="{{ route('admin.posts.hide', $post) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-{{ $post->is_hidden ? 'success' : 'warning' }}">
                                                    <i class="bi bi-eye{{ $post->is_hidden ? '' : '-slash' }} me-1" aria-hidden="true"></i>
                                                    {{ $post->is_hidden ? __('Unhide') : __('Hide') }}
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#delete-post-admin-{{ $post->id }}"
                                                    title="{{ __('Delete') }}" aria-label="{{ __('Delete this post') }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($posts->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$posts" />
                    </div>
                @endif
            </div>

            @foreach ($posts as $post)
                <x-confirm-delete-modal
                    :id="'delete-post-admin-'.$post->id"
                    :action="route('admin.posts.destroy', $post)"
                    :title="__('Delete this post?')"
                    :message="__('The post, its image, comments, likes and reports are deleted. This cannot be undone.')"
                    :confirm-label="__('Delete post')"
                    :show-trigger="false" />
            @endforeach
        @endif
    </div>
</x-app-layout>
```

#### `resources/views/admin/classes/index.blade.php`

Class management table with Hide (cancel).

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Classes') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Classes') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-easel me-1 text-edu" aria-hidden="true"></i> {{ __('Classes') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Hide or delete inappropriate classes (FR-28).') }}</p>
            </div>
            <span class="edu-chip">
                <i class="bi bi-easel" aria-hidden="true"></i>
                {{ $classes->total() }} {{ __('classes') }}
            </span>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <section class="card edu-card mb-3" aria-labelledby="class-filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="class-filters-title">{{ __('Filter classes') }}</h2>

                <form method="GET" action="{{ route('admin.classes.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="class-q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="class-q" value="{{ $term }}" class="form-control"
                                   placeholder="{{ __('Class title or teacher name') }}" maxlength="150">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="class-status" class="form-label small">{{ __('Status') }}</label>
                        <select name="status" id="class-status" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            @foreach (\App\Models\ClassRoom::STATUSES as $value)
                                <option value="{{ $value }}" @selected($status === $value)>
                                    {{ __(\Illuminate\Support\Str::headline($value)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="class-hidden" class="form-label small">{{ __('Visibility') }}</label>
                        <select name="hidden" id="class-hidden" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="0" @selected($hidden === '0')>{{ __('Visible') }}</option>
                            <option value="1" @selected($hidden === '1')>{{ __('Hidden') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by status') }}">
                    <a class="edu-filter-chip {{ $status === '' ? 'is-active' : '' }}"
                       href="{{ route('admin.classes.index', array_filter(['q' => $term, 'hidden' => $hidden])) }}">
                        {{ __('All statuses') }}
                    </a>
                    @foreach (\App\Models\ClassRoom::STATUSES as $value)
                        <a class="edu-filter-chip {{ $status === $value ? 'is-active' : '' }}"
                           href="{{ route('admin.classes.index', array_filter(['q' => $term, 'status' => $value, 'hidden' => $hidden])) }}">
                            {{ __(\Illuminate\Support\Str::headline($value)) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($classes->isEmpty())
            <x-empty-state icon="bi-calendar-x"
                           :title="__('No classes match these filters.')"
                           :message="__('Try another title, status or visibility, or reset the filters.')">
                <a href="{{ route('admin.classes.index') }}" class="btn btn-primary">{{ __('Reset the filters') }}</a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('All classes, newest first') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Class') }}</th>
                                <th scope="col">{{ __('Teacher') }}</th>
                                <th scope="col">{{ __('When') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col" class="text-end">{{ __('Students') }}</th>
                                <th scope="col" class="text-end">{{ __('Reports') }}</th>
                                <th scope="col">{{ __('Visibility') }}</th>
                                <th scope="col" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classes as $classRoom)
                                <tr>
                                    <td data-label="{{ __('Class') }}">
                                        <a href="{{ route('classes.show', $classRoom) }}"
                                           class="fw-semibold text-decoration-none text-reset">{{ $classRoom->title }}</a>
                                        @if ($classRoom->subject)
                                            <div class="small text-muted">{{ $classRoom->subject }}</div>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Teacher') }}" class="text-nowrap">
                                        {{ $classRoom->teacher?->name ?? __('Deleted teacher') }}
                                    </td>
                                    <td data-label="{{ __('When') }}" class="text-nowrap small">
                                        {{ $classRoom->date?->format('d M Y') }}
                                        <div class="text-muted">{{ \Illuminate\Support\Str::substr((string) $classRoom->time, 0, 5) }}</div>
                                    </td>
                                    <td data-label="{{ __('Status') }}">
                                        <x-status-badge :status="$classRoom->status" />
                                    </td>
                                    <td data-label="{{ __('Students') }}" class="text-end">{{ $classRoom->students_count }}</td>
                                    <td data-label="{{ __('Reports') }}" class="text-end">
                                        {{-- Reports target posts and comments; a class is moderated with Hide (cancel). --}}
                                        <span class="text-muted" title="{{ __('Classes are not reportable') }}">&mdash;</span>
                                    </td>
                                    <td data-label="{{ __('Visibility') }}">
                                        @if ($classRoom->is_hidden)
                                            <span class="badge text-bg-warning">{{ __('Hidden') }}</span>
                                        @else
                                            <span class="badge text-bg-success">{{ __('Visible') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <form method="POST" action="{{ route('admin.classes.hide', $classRoom) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-{{ $classRoom->is_hidden ? 'success' : 'warning' }}">
                                                    <i class="bi bi-eye{{ $classRoom->is_hidden ? '' : '-slash' }} me-1" aria-hidden="true"></i>
                                                    {{ $classRoom->is_hidden ? __('Unhide') : __('Hide (cancel)') }}
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#delete-class-admin-{{ $classRoom->id }}"
                                                    title="{{ __('Delete') }}" aria-label="{{ __('Delete this class') }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($classes->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$classes" />
                    </div>
                @endif
            </div>

            @foreach ($classes as $classRoom)
                <x-confirm-delete-modal
                    :id="'delete-class-admin-'.$classRoom->id"
                    :action="route('admin.classes.destroy', $classRoom)"
                    :title="__('Delete this class?')"
                    :message="__('The class and every enrollment are removed. This cannot be undone.')"
                    :confirm-label="__('Delete class')"
                    :show-trigger="false" />
            @endforeach
        @endif
    </div>
</x-app-layout>
```

#### `resources/views/dashboards/admin.blade.php`

Admin dashboard with the pending-reports card.

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Admin dashboard') }}</x-slot>

    <div class="container-xxl px-3 px-lg-4 edu-dashboard">
        @php
            $hour = (int) now()->format('G');
            $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
            $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
        @endphp

        {{-- Greeting + quick actions --}}
        <header class="edu-greeting">
            <div>
                <span class="edu-eyebrow">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    {{ __('Admin workspace') }}
                </span>
                <h1>{{ $greeting }}, {{ $firstName }}</h1>
                <p>
                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                    {{ now()->isoFormat('dddd, D MMMM YYYY') }}
                    <span class="mx-1" aria-hidden="true">·</span>
                    {{ __('Here is what is happening across EduConnect today.') }}
                </p>
            </div>

            <div class="edu-quick-actions" aria-label="{{ __('Quick actions') }}">
                <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-flag" aria-hidden="true"></i>
                    {{ __('Moderation queue') }}
                    @if ($pendingReports > 0)
                        <span class="badge rounded-pill bg-body-secondary text-body ms-1">{{ $pendingReports }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-people" aria-hidden="true"></i> {{ __('Manage users') }}
                </a>
                <a href="{{ route('admin.posts.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chat-square-text" aria-hidden="true"></i> {{ __('Review posts') }}
                </a>
            </div>
        </header>

        {{-- Five platform counters with trend + sparkline (FR-7, FR-26) --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Total users')" :value="$stats['users']" icon="bi-people" color="primary"
                    :trend="$trends['users']['percent']" :trend-direction="$trends['users']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['users']" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Teachers')" :value="$stats['teachers']" icon="bi-person-video3" color="success"
                    :trend="$trends['teachers']['percent']" :trend-direction="$trends['teachers']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['teachers']" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Students')" :value="$stats['students']" icon="bi-mortarboard" color="info"
                    :trend="$trends['students']['percent']" :trend-direction="$trends['students']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['students']" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Posts')" :value="$stats['posts']" icon="bi-chat-square-text" color="warning"
                    :trend="$trends['posts']['percent']" :trend-direction="$trends['posts']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['posts']" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Classes')" :value="$stats['classes']" icon="bi-easel" color="danger"
                    :trend="$trends['classes']['percent']" :trend-direction="$trends['classes']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['classes']" />
            </div>
        </div>

        {{-- Charts (FR-31) --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xxl-8">
                <x-chart-card :title="__('New users per month')" :subtitle="__('Registrations over the last 12 months')"
                    chart-id="admin-new-users" kind="line" series-key="newUsers" color="primary"
                    :series="$chart" :total="$chart['total']"
                    :endpoint="route('admin.dashboard.charts')" current-range="12m" :height="320"
                    :label="__('New users')" />
            </div>

            <div class="col-12 col-md-6 col-xxl-4">
                <x-chart-card :title="__('Users by role')" :subtitle="__('The shape of the community')"
                    chart-id="admin-roles" kind="doughnut" series-key="roles"
                    :series="$roleChart" :total="$roleChart['total']" :height="320" />
            </div>

            <div class="col-12 col-md-6 col-xl-6">
                <x-chart-card :title="__('Posts per week')" :subtitle="__('How active the feed is')"
                    chart-id="admin-posts" kind="bar" series-key="posts" color="secondary"
                    :series="$postsChart" :total="$postsChart['total']"
                    :endpoint="route('admin.dashboard.charts')" current-range="12m" :height="280"
                    :label="__('Posts')" />
            </div>

            <div class="col-12 col-md-6 col-xl-6">
                <x-chart-card :title="__('Classes by status')" :subtitle="__('Scheduled, live and completed')"
                    chart-id="admin-classes" kind="doughnut" series-key="classes"
                    :series="$classesChart" :total="$classesChart['total']" :height="280" />
            </div>
        </div>

        {{-- Moderation + newest members --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xl-7">
                <section class="card edu-card h-100" aria-labelledby="pending-reports-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="pending-reports-title">
                                <i class="bi bi-flag me-1 text-danger" aria-hidden="true"></i>
                                {{ __('Pending reports') }}
                            </h2>
                            <div class="small text-muted">{{ __('Reports waiting for a moderator.') }}</div>
                        </div>
                        <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}"
                           class="small fw-semibold text-decoration-none">{{ __('See all') }}</a>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($recentReports as $report)
                            <div class="edu-list-row">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <span class="badge bg-body-secondary text-body border text-capitalize">{{ $report->content_type }}</span>
                                        <span class="badge text-bg-warning">{{ $report->reason_label }}</span>
                                        <span class="edu-list-meta">{{ $report->created_at?->diffForHumans() }}</span>
                                    </div>

                                    <a href="{{ route('admin.reports.show', $report) }}"
                                       class="edu-list-title text-truncate">{{ $report->content_preview }}</a>
                                    <div class="edu-list-meta">
                                        {{ __('Reported by') }} {{ $report->reporter?->name ?? __('a deleted user') }}
                                    </div>
                                </div>

                                <div class="edu-report-actions">
                                    {{-- Quick moderation actions (FR-33). --}}
                                    <form method="POST" action="{{ route('admin.reports.hide', $report) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                aria-label="{{ __('Hide the reported :type', ['type' => strtolower($report->content_type)]) }}"
                                                title="{{ __('Hide content') }}">
                                            <i class="bi bi-eye-slash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                aria-label="{{ __('Dismiss this report') }}"
                                                title="{{ __('Dismiss report') }}">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;">
                                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('The moderation queue is clear.') }}</div>
                                <div class="text-muted small">{{ __('Nothing to review right now.') }}</div>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-5">
                <x-data-table id="admin-recent-users" :title="__('Recent users')"
                    :subtitle="__('The newest accounts on the platform')"
                    :count="$recentUsers->count()" count-label=":count accounts"
                    :search-placeholder="__('Search recent users…')">
                    <x-slot name="head">
                        <tr>
                            <th scope="col" data-sort>{{ __('User') }}</th>
                            <th scope="col" class="d-none d-md-table-cell">{{ __('Role') }}</th>
                            <th scope="col" data-sort class="text-end">{{ __('Joined') }}</th>
                        </tr>
                    </x-slot>

                    @forelse ($recentUsers as $user)
                        <tr data-table-row data-search="{{ $user->name }} {{ $user->email }} {{ $user->role }}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <x-avatar :user="$user" size="sm" />
                                    <div class="min-w-0">
                                        <a href="{{ route('users.show', $user) }}"
                                           class="fw-semibold text-decoration-none text-reset d-block text-truncate">
                                            {{ $user->name }}
                                        </a>
                                        <span class="text-muted small d-block text-truncate">{{ $user->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                @php
                                    $roleColor = match ($user->role) {
                                        \App\Models\User::ROLE_ADMIN => 'dark',
                                        \App\Models\User::ROLE_TEACHER => 'primary',
                                        default => 'success',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $user->role }}</span>
                            </td>
                            <td class="text-end text-muted small text-nowrap" data-sort-value="{{ $user->created_at?->timestamp }}">
                                {{ $user->created_at?->format('d M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr data-table-row data-search="">
                            <td colspan="3" class="text-center text-muted py-4 small">
                                <i class="bi bi-people me-1" aria-hidden="true"></i> {{ __('No users yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </x-data-table>
            </div>
        </div>

        {{-- Latest posts + live classes --}}
        <div class="row g-3 g-xl-4">
            <div class="col-12 col-xl-7">
                <section class="card edu-card h-100" aria-labelledby="recent-posts-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="recent-posts-title">
                                <i class="bi bi-chat-square-text me-1 text-edu" aria-hidden="true"></i>
                                {{ __('Latest posts') }}
                            </h2>
                            <div class="small text-muted">{{ __('The newest activity in the feed.') }}</div>
                        </div>
                        <a href="{{ route('admin.posts.index') }}"
                           class="small fw-semibold text-decoration-none">{{ __('Manage posts') }}</a>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($recentPosts as $post)
                            <article class="edu-list-row">
                                <x-avatar :user="$post->user" size="sm" class="flex-shrink-0" />
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <a href="{{ route('users.show', $post->user) }}"
                                           class="fw-semibold small text-decoration-none text-reset">
                                            {{ $post->user?->name ?? __('Deleted user') }}
                                        </a>
                                        <span class="edu-list-meta">{{ $post->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <a href="{{ route('posts.show', $post) }}"
                                       class="edu-list-title mt-1">{{ \Illuminate\Support\Str::limit($post->body, 110) }}</a>
                                    <div class="edu-list-meta">
                                        <i class="bi bi-heart me-1" aria-hidden="true"></i>{{ $post->likes_count }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        <i class="bi bi-chat me-1" aria-hidden="true"></i>{{ $post->comments_count }}
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="text-center text-muted py-4 small">
                                <i class="bi bi-chat-square me-1" aria-hidden="true"></i>
                                {{ __('The feed is quiet right now.') }}
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-5">
                <section class="card edu-card h-100" aria-labelledby="live-classes-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="live-classes-title">
                                <i class="bi bi-broadcast me-1 text-danger" aria-hidden="true"></i>
                                {{ __("Today's live classes") }}
                            </h2>
                            <div class="small text-muted">{{ __('Rooms running right now.') }}</div>
                        </div>
                        <a href="{{ route('admin.classes.index') }}"
                           class="small fw-semibold text-decoration-none">{{ __('All classes') }}</a>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($liveClasses as $class)
                            <div class="edu-list-row">
                                <span class="edu-activity-icon bg-danger" aria-hidden="true">
                                    <i class="bi bi-camera-video"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <a href="{{ route('classes.show', $class) }}" class="edu-list-title text-truncate">
                                        {{ $class->title }}
                                    </a>
                                    <div class="edu-list-meta">
                                        {{ $class->teacher?->name ?? __('Unknown teacher') }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        {{ \Illuminate\Support\Str::substr((string) $class->time, 0, 5) }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        {{ trans_choice(':count student|:count students', $class->students_count, ['count' => $class->students_count]) }}
                                    </div>
                                </div>
                                <a href="{{ route('classes.room', $class) }}" class="btn btn-sm btn-danger"
                                   aria-label="{{ __('Join :title', ['title' => $class->title]) }}">
                                    <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;">
                                    <i class="bi bi-camera-video-off" aria-hidden="true"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('No live classes right now.') }}</div>
                                <div class="text-muted small">{{ __('Scheduled rooms will appear here when they start.') }}</div>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
```

### 6.7 Tests

#### `tests/Feature/ReportTest.php`

FR-32 to FR-34: reporting rules, queue actions and outcomes.

```php
<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-32 ... FR-34: reporting and the moderation queue.
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    /* -----------------------------------------------------------------
     | Reporting (FR-32)
     * ----------------------------------------------------------------- */

    public function test_a_guest_cannot_report_content(): void
    {
        $post = Post::factory()->create();

        $this->post(route('reports.posts.store', $post), ['reason' => 'spam'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_a_user_can_report_somebody_elses_post(): void
    {
        $author = User::factory()->teacher()->create();
        $reporter = User::factory()->student()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $post), [
                'reason' => 'harassment',
                'details' => 'This post attacks a student.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'harassment',
            'details' => 'This post attacks a student.',
            'status' => Report::STATUS_PENDING,
        ]);
    }

    public function test_a_user_can_report_a_comment(): void
    {
        $reporter = User::factory()->student()->create();
        $comment = Comment::factory()->create();

        $this->actingAs($reporter)
            ->post(route('reports.comments.store', $comment), ['reason' => 'spam'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
            'status' => Report::STATUS_PENDING,
        ]);
    }

    public function test_a_user_cannot_report_their_own_content(): void
    {
        $author = User::factory()->student()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $comment = Comment::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author)
            ->post(route('reports.posts.store', $post), ['reason' => 'spam'])
            ->assertForbidden();

        $this->actingAs($author)
            ->post(route('reports.comments.store', $comment), ['reason' => 'spam'])
            ->assertForbidden();

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_the_same_user_cannot_report_the_same_item_twice(): void
    {
        $reporter = User::factory()->student()->create();
        $post = Post::factory()->create();

        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $post), ['reason' => 'spam'])
            ->assertSessionHas('success');

        // The second attempt is refused with a friendly flash message.
        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $post), ['reason' => 'harassment'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('reports', 1);
    }

    public function test_the_reason_is_required_and_details_are_limited(): void
    {
        $reporter = User::factory()->student()->create();
        $post = Post::factory()->create();

        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $post), ['reason' => '', 'details' => str_repeat('a', 501)])
            ->assertSessionHasErrors(['reason', 'details']);

        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $post), ['reason' => 'not-a-real-reason'])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_hidden_content_cannot_be_reported(): void
    {
        $reporter = User::factory()->student()->create();
        $post = Post::factory()->hidden()->create();

        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $post), ['reason' => 'spam'])
            ->assertNotFound();
    }

    /* -----------------------------------------------------------------
     | The admin queue (FR-33, FR-34)
     * ----------------------------------------------------------------- */

    public function test_only_admins_can_open_the_reports_queue(): void
    {
        $report = Report::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee($report->content_preview);

        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('admin.reports.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->student()->create())
            ->get(route('admin.reports.show', $report))
            ->assertForbidden();
    }

    public function test_the_queue_can_be_filtered_by_status_and_type(): void
    {
        $admin = User::factory()->admin()->create();

        $pending = Report::factory()->forPost()->create();
        Report::factory()->forComment()->reviewed()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.index', ['status' => 'pending', 'type' => 'post']))
            ->assertOk();

        $reports = $response->viewData('reports');

        $this->assertSame(1, $reports->total());
        $this->assertSame($pending->id, $reports->first()->id);
    }

    public function test_hiding_a_post_marks_the_report_reviewed_and_removes_it_from_the_feed(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->teacher()->create();
        $follower = User::factory()->student()->create();
        $follower->follow($author);

        $post = Post::factory()->create(['user_id' => $author->id, 'body' => 'Very rude post body']);
        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reports.hide', $report))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($post->fresh()->is_hidden);
        $this->assertSame(Report::STATUS_REVIEWED, $report->fresh()->status);

        // The follower no longer sees it in the feed.
        $this->actingAs($follower)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertDontSee('Very rude post body');

        // Unhide works too.
        $this->actingAs($admin)->patch(route('admin.reports.unhide', $report))->assertRedirect();
        $this->assertFalse($post->fresh()->is_hidden);
    }

    public function test_hiding_a_comment_removes_it_from_the_post_page(): void
    {
        $admin = User::factory()->admin()->create();

        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'body' => 'A comment that must disappear',
        ]);

        $report = Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reports.hide', $report))
            ->assertRedirect();

        $this->assertTrue($comment->fresh()->is_hidden);

        $this->actingAs($admin)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('A comment that must disappear');
    }

    public function test_blocking_the_author_marks_the_report_reviewed_and_logs_them_out(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->student()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reports.block-user', $report))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($author->fresh()->is_blocked);
        $this->assertSame(Report::STATUS_REVIEWED, $report->fresh()->status);

        // The blocked author is kicked out on their next request (FR-6).
        // Refresh first: actingAs() reuses the in-memory model.
        $this->actingAs($author->refresh())
            ->get(route('feed.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_a_report_can_be_dismissed_or_marked_reviewed(): void
    {
        $admin = User::factory()->admin()->create();
        $dismissed = Report::factory()->create();
        $reviewed = Report::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.reports.dismiss', $dismissed))
            ->assertSessionHas('success');

        $this->assertSame(Report::STATUS_DISMISSED, $dismissed->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.reports.reviewed', $reviewed))
            ->assertSessionHas('success');

        $this->assertSame(Report::STATUS_REVIEWED, $reviewed->fresh()->status);
    }

    public function test_deleting_the_content_removes_the_content_and_its_reports(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();
        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.reports.content.destroy', $report))
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_the_admin_dashboard_shows_the_pending_reports(): void
    {
        $admin = User::factory()->admin()->create();
        Report::factory()->create();
        Report::factory()->reviewed()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('pendingReports'));
        $this->assertCount(1, $response->viewData('recentReports'));
    }
}
```

#### `tests/Feature/AdminManagementTest.php`

FR-27 and FR-28: user, post and class management.

```php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-27, FR-28: admin user, post and class management.
 */
class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    /* -----------------------------------------------------------------
     | Access control
     * ----------------------------------------------------------------- */

    public function test_only_admins_can_reach_the_management_pages(): void
    {
        $routes = [
            'admin.users.index',
            'admin.posts.index',
            'admin.classes.index',
            'admin.reports.index',
        ];

        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        foreach ($routes as $name) {
            $this->actingAs($admin)->get(route($name))->assertOk();
            $this->actingAs($teacher)->get(route($name))->assertForbidden();
            $this->actingAs($student)->get(route($name))->assertForbidden();
        }
    }

    /* -----------------------------------------------------------------
     | Users (FR-27)
     * ----------------------------------------------------------------- */

    public function test_admins_can_search_and_filter_users(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create(['name' => 'Alice Teacher']);
        $blocked = User::factory()->student()->blocked()->create(['name' => 'Bob Blocked']);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Alice']))
            ->assertOk();

        $users = $response->viewData('users');
        $this->assertSame([$teacher->id], $users->pluck('id')->all());

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index', ['blocked' => '1']))
            ->assertOk();

        $ids = $response->viewData('users')->pluck('id')->all();
        $this->assertContains($blocked->id, $ids);
        $this->assertNotContains($teacher->id, $ids);
    }

    public function test_an_admin_can_block_and_unblock_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.block', $student))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($student->fresh()->is_blocked);

        // The blocked account can no longer browse the app. Refresh the model
        // first: actingAs() would otherwise reuse the stale is_blocked = false.
        $this->actingAs($student->refresh())
            ->get(route('feed.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($admin)
            ->patch(route('admin.users.unblock', $student))
            ->assertSessionHas('success');

        $this->assertFalse($student->fresh()->is_blocked);
    }

    public function test_an_admin_cannot_block_or_delete_themselves_or_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.block', $admin))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.users.block', $otherAdmin))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $otherAdmin))
            ->assertForbidden();

        $this->assertFalse($admin->fresh()->is_blocked);
        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }

    public function test_deleting_a_user_removes_their_content_and_reports(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $post = Post::factory()->create(['user_id' => $student->id]);
        Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $student))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseCount('reports', 0);
    }

    /* -----------------------------------------------------------------
     | Posts (FR-28)
     * ----------------------------------------------------------------- */

    public function test_an_admin_can_hide_unhide_and_delete_a_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['body' => 'Something inappropriate']);

        $this->actingAs($admin)
            ->patch(route('admin.posts.hide', $post))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($post->fresh()->is_hidden);

        $this->actingAs($admin)
            ->patch(route('admin.posts.hide', $post))
            ->assertRedirect();

        $this->assertFalse($post->fresh()->is_hidden);

        $this->actingAs($admin)
            ->delete(route('admin.posts.destroy', $post))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_hiding_a_post_marks_its_pending_reports_reviewed(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();

        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)->patch(route('admin.posts.hide', $post));

        $this->assertSame(Report::STATUS_REVIEWED, $report->fresh()->status);
    }

    public function test_admins_can_filter_posts_by_visibility(): void
    {
        $admin = User::factory()->admin()->create();
        $visible = Post::factory()->create();
        $hidden = Post::factory()->hidden()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.posts.index', ['hidden' => '1']))
            ->assertOk();

        $posts = $response->viewData('posts');

        $this->assertSame([$hidden->id], $posts->pluck('id')->all());
        $this->assertNotContains($visible->id, $posts->pluck('id')->all());
    }

    /* -----------------------------------------------------------------
     | Classes (FR-28)
     * ----------------------------------------------------------------- */

    public function test_an_admin_can_hide_unhide_and_delete_a_class(): void
    {
        $admin = User::factory()->admin()->create();
        $classRoom = ClassRoom::factory()->create(['title' => 'Bad class']);

        $this->actingAs($admin)
            ->patch(route('admin.classes.hide', $classRoom))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($classRoom->fresh()->is_hidden);

        // A hidden class is gone from the student catalogue.
        $this->actingAs(User::factory()->student()->create())
            ->get(route('classes.discover'))
            ->assertOk()
            ->assertDontSee('Bad class');

        // ... but the teacher still sees it (ClassPolicy::view).
        $this->actingAs($classRoom->teacher)
            ->get(route('classes.show', $classRoom))
            ->assertOk();

        $this->actingAs($admin)
            ->patch(route('admin.classes.hide', $classRoom))
            ->assertRedirect();

        $this->assertFalse($classRoom->fresh()->is_hidden);

        $this->actingAs($admin)
            ->delete(route('admin.classes.destroy', $classRoom))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('classes', ['id' => $classRoom->id]);
    }

    public function test_the_class_list_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $live = ClassRoom::factory()->live()->create();
        ClassRoom::factory()->completed()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.classes.index', ['status' => ClassRoom::STATUS_LIVE]))
            ->assertOk();

        $classes = $response->viewData('classes');

        $this->assertSame([$live->id], $classes->pluck('id')->all());
    }
}
```

#### `tests/Feature/PhaseSevenTest.php`

27 cross-cutting Phase 7 guarantees.

```php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Comment;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7 - reports, moderation and admin management (FR-27, FR-28, FR-32 - FR-34).
 *
 * The happy paths already live in ReportTest and AdminManagementTest. This file
 * covers the cross-cutting guarantees: the report UI, admin-only moderation
 * actions, hidden content disappearing from every surface and every counter,
 * and blocked users being unable to act anywhere.
 */
class PhaseSevenTest extends TestCase
{
    use RefreshDatabase;

    /* -----------------------------------------------------------------
     | Reporting UI (FR-32)
     * ----------------------------------------------------------------- */

    public function test_every_post_and_comment_offers_a_report_modal(): void
    {
        $author = User::factory()->teacher()->create();
        $viewer = User::factory()->student()->create();
        $viewer->follow($author);

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => 'A post that can be reported.',
        ]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'body' => 'A comment that can be reported.',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('feed.index'))
            ->assertOk();

        // The kebab menu and the comment row both open their own modal.
        $response->assertSee('report-post-'.$post->id, false);
        $response->assertSee('report-comment-'.$comment->id, false);
        $response->assertSee('Report post');
        $response->assertSee('Report comment');

        // Required reason + capped details (FR-32).
        $response->assertSee('name="reason"', false);
        $response->assertSee('Harassment');
        $response->assertSee('Inappropriate content');
        $response->assertSee('Hate speech');
        $response->assertSee('maxlength="500"', false);
    }

    public function test_a_report_cannot_be_filed_twice_on_the_same_comment(): void
    {
        $reporter = User::factory()->student()->create();
        $comment = Comment::factory()->create();

        $this->actingAs($reporter)
            ->post(route('reports.comments.store', $comment), ['reason' => 'spam'])
            ->assertSessionHas('success');

        $this->actingAs($reporter)
            ->post(route('reports.comments.store', $comment), ['reason' => 'harassment'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('reports', 1);
    }

    /* -----------------------------------------------------------------
     | Moderation actions (FR-33, FR-34)
     * ----------------------------------------------------------------- */

    public function test_moderation_actions_are_admin_only(): void
    {
        $report = Report::factory()->forPost()->create();

        $actions = [
            ['patch', 'admin.reports.hide'],
            ['patch', 'admin.reports.unhide'],
            ['patch', 'admin.reports.reviewed'],
            ['patch', 'admin.reports.dismiss'],
            ['post', 'admin.reports.block-user'],
            ['delete', 'admin.reports.content.destroy'],
        ];

        foreach ([User::factory()->teacher()->create(), User::factory()->student()->create()] as $user) {
            $this->actingAs($user)
                ->get(route('admin.reports.show', $report))
                ->assertForbidden();

            foreach ($actions as [$method, $name]) {
                $this->actingAs($user)
                    ->{$method}(route($name, $report))
                    ->assertForbidden();
            }
        }

        // Nothing changed while the non-admins were being refused.
        $report->refresh();
        $this->assertSame(Report::STATUS_PENDING, $report->status);
        $this->assertFalse((bool) $report->reportable->is_hidden);
    }

    public function test_deleting_a_reported_comment_removes_it_and_its_reports(): void
    {
        $admin = User::factory()->admin()->create();
        $comment = Comment::factory()->create();
        $report = Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.reports.content.destroy', $report))
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_dismissing_a_report_keeps_the_content_visible(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();
        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reports.dismiss', $report))
            ->assertSessionHas('success');

        $this->assertSame(Report::STATUS_DISMISSED, $report->fresh()->status);
        $this->assertFalse($post->fresh()->is_hidden);
    }

    public function test_an_administrator_cannot_be_blocked_from_the_report_queue(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $post = Post::factory()->create(['user_id' => $otherAdmin->id]);
        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reports.block-user', $report))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse($otherAdmin->fresh()->is_blocked);
        $this->assertSame(Report::STATUS_PENDING, $report->fresh()->status);
    }

    public function test_the_report_queue_search_matches_the_offending_author(): void
    {
        $admin = User::factory()->admin()->create();
        $offender = User::factory()->teacher()->create(['name' => 'Offender Zoe']);
        $post = Post::factory()->create(['user_id' => $offender->id]);

        Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.index', ['q' => 'Zoe']))
            ->assertOk();

        $this->assertSame(1, $response->viewData('reports')->total());
    }

    /* -----------------------------------------------------------------
     | Hidden content disappears everywhere (FR-28)
     * ----------------------------------------------------------------- */

    public function test_hidden_posts_disappear_from_feed_profile_hashtag_and_search(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->teacher()->create();
        $follower = User::factory()->student()->create();
        $follower->follow($author);

        $hashtag = Hashtag::factory()->create(['name' => 'moderated']);
        $body = 'moderated marker that must vanish';

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => $body,
        ]);
        $post->hashtags()->attach($hashtag);

        $this->actingAs($admin)
            ->patch(route('admin.posts.hide', $post))
            ->assertSessionHas('success');

        // Feed.
        $this->actingAs($follower)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertDontSee($body);

        // Profile: the card and the header counter.
        $profile = $this->actingAs($follower)
            ->get(route('users.show', $author))
            ->assertOk()
            ->assertDontSee($body);

        $this->assertSame(0, $profile->viewData('profile')->posts_count);

        // Hashtag page (card + total).
        $hashtagPage = $this->actingAs($follower)
            ->get(route('hashtags.show', $hashtag->name))
            ->assertOk()
            ->assertDontSee($body);

        $this->assertSame(0, $hashtagPage->viewData('posts')->total());

        // Search: posts tab and the hashtag post counter.
        $this->actingAs($follower)
            ->get(route('search.index', ['q' => 'marker', 'tab' => 'posts']))
            ->assertOk()
            ->assertDontSee($body);

        $hashtagSearch = $this->actingAs($follower)
            ->get(route('search.index', ['q' => 'moderated', 'tab' => 'hashtags']))
            ->assertOk();

        $this->assertSame(0, $hashtagSearch->viewData('hashtags')->first()->posts_count);

        // A normal visitor cannot open the post directly, but its author still can.
        $this->actingAs($follower)
            ->get(route('posts.show', $post))
            ->assertForbidden();

        $this->actingAs($author)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee($body);
    }

    public function test_hidden_comments_disappear_from_the_post_and_its_counter(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'body' => 'a comment that must not be counted',
        ]);

        $report = Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reports.hide', $report))
            ->assertSessionHas('success');

        $response = $this->actingAs($admin)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('a comment that must not be counted');

        $this->assertSame(0, $response->viewData('post')->comments_count);
    }

    /* -----------------------------------------------------------------
     | Blocked accounts (FR-6, section 5.6)
     * ----------------------------------------------------------------- */

    public function test_a_blocked_user_cannot_post_comment_enroll_or_join(): void
    {
        $blocked = User::factory()->student()->blocked()->create();
        $post = Post::factory()->create();
        $classRoom = ClassRoom::factory()->scheduled()->create();
        $liveClass = ClassRoom::factory()->live()->create();
        $liveClass->students()->attach($blocked->id);

        $this->actingAs($blocked)
            ->post(route('posts.store'), ['body' => 'Trying to post while blocked.'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
        $this->assertGuest();

        $this->actingAs($blocked)
            ->post(route('comments.store', $post), ['body' => 'Trying to comment while blocked.'])
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($blocked)
            ->post(route('classes.enroll', $classRoom))
            ->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertSame(0, $classRoom->students()->count());

        $this->actingAs($blocked)
            ->get(route('classes.room', $liveClass))
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->assertDatabaseCount('comments', 0);
        $this->assertSame(0, $post->comments()->count());
    }

    /* -----------------------------------------------------------------
     | Admin filters (FR-27, FR-28)
     * ----------------------------------------------------------------- */

    public function test_the_user_list_can_be_filtered_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        User::factory()->student()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => User::ROLE_TEACHER]))
            ->assertOk();

        $ids = $response->viewData('users')->pluck('id')->all();

        $this->assertContains($teacher->id, $ids);
        $this->assertNotContains($admin->id, $ids);
    }

    public function test_admin_lists_show_the_author_and_the_report_count(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->teacher()->create(['name' => 'Reported Rita']);
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => 'A post with reports on the admin list.',
        ]);

        Report::factory()->count(2)->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.posts.index'))
            ->assertOk()
            ->assertSee('Reported Rita')
            ->assertSee('A post with reports on the admin list.')
            ->assertSee('text-bg-warning">2', false);
    }

    /* -----------------------------------------------------------------
     | Admin-only mutations (FR-27, FR-28, FR-33)
     * ----------------------------------------------------------------- */

    public function test_every_admin_mutation_route_rejects_teachers_and_students(): void
    {
        $target = User::factory()->student()->create();
        $post = Post::factory()->create();
        $classRoom = ClassRoom::factory()->create();
        $report = Report::factory()->forPost()->create();

        $actions = [
            ['patch', 'admin.users.block', $target],
            ['patch', 'admin.users.unblock', $target],
            ['delete', 'admin.users.destroy', $target],
            ['patch', 'admin.posts.hide', $post],
            ['delete', 'admin.posts.destroy', $post],
            ['patch', 'admin.classes.hide', $classRoom],
            ['delete', 'admin.classes.destroy', $classRoom],
        ];

        foreach ([User::factory()->teacher()->create(), User::factory()->student()->create()] as $user) {
            foreach ($actions as [$method, $name, $parameter]) {
                $this->actingAs($user)
                    ->{$method}(route($name, $parameter))
                    ->assertForbidden();
            }
        }

        // Nothing was changed by any of the refused requests.
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_blocked' => false]);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'is_hidden' => false]);
        $this->assertDatabaseHas('classes', ['id' => $classRoom->id, 'is_hidden' => false]);
        $this->assertSame(Report::STATUS_PENDING, $report->fresh()->status);
    }

    /* -----------------------------------------------------------------
     | Report detail page (FR-33, FR-34)
     * ----------------------------------------------------------------- */

    public function test_the_report_detail_page_offers_every_moderation_action(): void
    {
        $admin = User::factory()->admin()->create();
        $offender = User::factory()->teacher()->create(['name' => 'Offender Olga']);
        $post = Post::factory()->create(['user_id' => $offender->id]);

        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'details' => 'The details the moderator has to read.',
        ]);

        Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee('The details the moderator has to read.')
            ->assertSee('Other reports on this content')
            ->assertSee('Hide the content')
            ->assertSee('Unhide the content')
            ->assertSee('Delete the content')
            ->assertSee('Block '.$offender->name)
            ->assertSee('Mark as reviewed')
            ->assertSee('Dismiss the report')
            ->assertSee(route('admin.reports.hide', $report), false)
            ->assertSee(route('admin.reports.content.destroy', $report), false)
            ->assertSee(route('admin.reports.block-user', $report), false);
    }

    public function test_the_queue_does_not_offer_to_block_an_administrator(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create(['name' => 'Protected Paula']);
        $post = Post::factory()->create(['user_id' => $otherAdmin->id]);

        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertDontSee('Block Protected Paula')
            ->assertSee('Administrator accounts are protected');
    }

    public function test_unhiding_restores_the_content_and_marks_the_report_reviewed(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->teacher()->create();
        $viewer = User::factory()->student()->create();
        $viewer->follow($author);

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => 'This post comes back after unhide.',
        ]);
        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)->patch(route('admin.reports.hide', $report))->assertSessionHas('success');
        $this->assertTrue($post->fresh()->is_hidden);

        $this->actingAs($admin)->patch(route('admin.reports.unhide', $report))->assertSessionHas('success');

        $this->assertFalse($post->fresh()->is_hidden);
        $this->assertSame(Report::STATUS_REVIEWED, $report->fresh()->status);

        $this->actingAs($viewer)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee('This post comes back after unhide.');
    }

    /* -----------------------------------------------------------------
     | Hidden comments on the feed (FR-28)
     * ----------------------------------------------------------------- */

    public function test_a_hidden_post_offers_no_report_action_to_its_viewers(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->hidden()->create();

        $this->actingAs($admin)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('report-post-'.$post->id, false)
            ->assertDontSee('Report post');
    }

    public function test_a_comment_under_a_hidden_post_cannot_be_reported(): void
    {
        $reporter = User::factory()->student()->create();
        $post = Post::factory()->hidden()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $this->actingAs($reporter)
            ->post(route('reports.comments.store', $comment), ['reason' => 'spam'])
            ->assertNotFound();

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_hiding_content_resolves_every_pending_report_about_it(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();

        $first = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);
        $second = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reports.hide', $first))
            ->assertSessionHas('success');

        $this->assertSame(Report::STATUS_REVIEWED, $first->fresh()->status);
        $this->assertSame(Report::STATUS_REVIEWED, $second->fresh()->status);
    }

    public function test_a_hidden_comment_disappears_from_the_feed_preview_and_counter(): void
    {
        $author = User::factory()->teacher()->create();
        $viewer = User::factory()->student()->create();
        $viewer->follow($author);

        $post = Post::factory()->create(['user_id' => $author->id]);

        Comment::factory()->create([
            'post_id' => $post->id,
            'body' => 'This visible comment stays on the feed.',
        ]);
        Comment::factory()->hidden()->create([
            'post_id' => $post->id,
            'body' => 'This hidden comment must never appear.',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee('This visible comment stays on the feed.')
            ->assertDontSee('This hidden comment must never appear.');

        $feedPost = $response->viewData('posts')->firstWhere('id', $post->id);

        $this->assertNotNull($feedPost);
        $this->assertSame(1, $feedPost->comments_count);
    }

    /* -----------------------------------------------------------------
     | Blocked accounts cannot interact (FR-6, section 5.6)
     * ----------------------------------------------------------------- */

    public function test_a_blocked_user_cannot_report_like_or_follow(): void
    {
        $blocked = User::factory()->student()->blocked()->create();
        $teacher = User::factory()->teacher()->create();
        $post = Post::factory()->create(['user_id' => $teacher->id]);

        $this->actingAs($blocked)
            ->post(route('reports.posts.store', $post), ['reason' => 'spam'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
        $this->assertGuest();

        $this->actingAs($blocked)
            ->post(route('posts.like', $post))
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($blocked)
            ->post(route('follows.store', $teacher))
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->assertDatabaseCount('reports', 0);
        $this->assertDatabaseCount('likes', 0);
        $this->assertDatabaseCount('follows', 0);
    }

    /* -----------------------------------------------------------------
     | Admin list search, pagination and confirmations (FR-27, FR-28)
     * ----------------------------------------------------------------- */

    public function test_admin_post_and_class_lists_can_be_searched(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->teacher()->create(['name' => 'Searchable Author']);
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => 'A body with a needle marker.',
        ]);
        Post::factory()->create(['body' => 'An unrelated body.']);

        $classTeacher = User::factory()->teacher()->create(['name' => 'Class Teacher X']);
        $classRoom = ClassRoom::factory()->create([
            'teacher_id' => $classTeacher->id,
            'title' => 'Algebra Special',
        ]);
        ClassRoom::factory()->create(['title' => 'Unrelated Class']);

        $byBody = $this->actingAs($admin)
            ->get(route('admin.posts.index', ['q' => 'needle']))
            ->assertOk();
        $this->assertSame([$post->id], $byBody->viewData('posts')->pluck('id')->all());

        $byAuthor = $this->actingAs($admin)
            ->get(route('admin.posts.index', ['q' => 'Searchable Author']))
            ->assertOk();
        $this->assertContains($post->id, $byAuthor->viewData('posts')->pluck('id')->all());

        $byTitle = $this->actingAs($admin)
            ->get(route('admin.classes.index', ['q' => 'Algebra']))
            ->assertOk();
        $this->assertSame([$classRoom->id], $byTitle->viewData('classes')->pluck('id')->all());

        $byTeacher = $this->actingAs($admin)
            ->get(route('admin.classes.index', ['q' => 'Class Teacher X']))
            ->assertOk();
        $this->assertContains($classRoom->id, $byTeacher->viewData('classes')->pluck('id')->all());
    }

    public function test_the_report_queue_searches_details_reason_and_reporter(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->student()->create(['name' => 'Reporter Quinn']);
        $post = Post::factory()->create();

        Report::factory()->create([
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'hate_speech',
            'details' => 'A unique details marker.',
        ]);

        foreach (['unique details marker', 'hate_speech', 'Quinn'] as $term) {
            $response = $this->actingAs($admin)
                ->get(route('admin.reports.index', ['q' => $term]))
                ->assertOk();

            $this->assertSame(1, $response->viewData('reports')->total(), $term);
        }
    }

    public function test_the_user_list_offers_block_and_delete_confirmation_modals(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('block-user-'.$student->id, false)
            ->assertSee('delete-user-'.$student->id, false)
            ->assertSee('Block account')
            ->assertSee('Delete account');
    }

    public function test_admin_lists_paginate_at_twenty_rows(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->teacher()->create();

        User::factory()->count(21)->create();
        Post::factory()->count(21)->create(['user_id' => $author->id]);
        ClassRoom::factory()->count(21)->create(['teacher_id' => $author->id]);
        Report::factory()->count(21)->create();

        $lists = [
            ['admin.users.index', 'users'],
            ['admin.posts.index', 'posts'],
            ['admin.classes.index', 'classes'],
            ['admin.reports.index', 'reports'],
        ];

        foreach ($lists as [$routeName, $key]) {
            $paginator = $this->actingAs($admin)
                ->get(route($routeName))
                ->assertOk()
                ->viewData($key);

            $this->assertSame(20, $paginator->perPage(), $key);
            $this->assertCount(20, $paginator->items(), $key);
            $this->assertTrue($paginator->hasMorePages(), $key);
        }
    }

    public function test_deleting_a_user_removes_the_reports_they_filed(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->student()->create();
        $post = Post::factory()->create();

        Report::factory()->create([
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $reporter))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $reporter->id]);
        $this->assertDatabaseCount('reports', 0);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_the_admin_dashboard_links_to_the_pending_reports(): void
    {
        $admin = User::factory()->admin()->create();
        $report = Report::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Pending reports')
            ->assertSee(route('admin.reports.index', ['status' => 'pending']), false)
            ->assertSee(route('admin.reports.show', $report), false)
            ->assertSee(route('admin.reports.hide', $report), false)
            ->assertSee(route('admin.reports.dismiss', $report), false);
    }
}
```

#### `tests/Feature/Auth/BlockedUserTest.php`

Blocked login and mid-session ejection.

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BlockedUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A blocked user may not log in, even with the correct password (FR-6).
     */
    public function test_a_blocked_user_cannot_log_in(): void
    {
        User::factory()->blocked()->create([
            'email' => 'blocked@example.com',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'blocked@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'blocked',
            (string) session('errors')->first('email')
        );
    }

    /**
     * A user who is blocked while logged in is kicked out on the next request (FR-6).
     */
    public function test_a_logged_in_user_is_logged_out_when_blocked(): void
    {
        $user = User::factory()->create();

        // The user is already authenticated (simulates a still-open session).
        Auth::login($user);

        // The administrator blocks the account.
        $user->forceFill(['is_blocked' => true])->save();

        $response = $this->get('/profile');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
    }
}
```

### 6.8 Route registration (excerpt)

`routes/web.php`

The admin group and the two report routes. The rest of the file is unchanged from earlier phases.

```php
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

// ... feed, teacher, student and public routes omitted ...

    // Reporting a post or comment (FR-32). Throttled so the queue cannot be
    // flooded by one account.
    Route::post('/posts/{post}/reports', [ReportController::class, 'storePost'])
        ->middleware('throttle:reports')
        ->name('reports.posts.store');
    Route::post('/comments/{comment}/reports', [ReportController::class, 'storeComment'])
        ->middleware('throttle:reports')
        ->name('reports.comments.store');

```

### 6.9 User model relations (excerpt)

`app/Models/User.php`

```php
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

/** Reports this user filed (FR-32). */
public function reports(): HasMany
{
    return $this->hasMany(Report::class, 'reporter_id');
}
```

### 6.10 Cached pending counter (excerpt)

`app/Support/DashboardMetrics.php`

```php
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

```

---

## 7. Test checklist

### FR-27 — Admin user management

- [x] Only an administrator can open `/admin/users`; teacher and student get
      403 (`AdminManagementTest::test_only_admins_can_reach_the_management_pages`).
- [x] Search matches name and e-mail, and LIKE wildcards are treated literally
      (`test_admins_can_search_and_filter_users`).
- [x] Role filter (admin / teacher / student) and blocked/active filter work
      (`test_admins_can_search_and_filter_users`,
      `PhaseSevenTest::test_the_user_list_can_be_filtered_by_role`).
- [x] The table shows avatar, role badge, blocked/active status, posts and
      classes and paginates at 20 (`PhaseSevenTest::test_admin_lists_paginate_at_twenty_rows`).
- [x] Block sets `is_blocked`, clears database sessions and prevents the next
      authenticated request; Unblock restores access
      (`AdminManagementTest::test_an_admin_can_block_and_unblock_a_user`,
      `Auth/BlockedUserTest::test_a_logged_in_user_is_logged_out_when_blocked`).
- [x] A blocked account cannot log in and sees a clear message
      (`Auth/BlockedUserTest::test_a_blocked_user_cannot_log_in`).
- [x] An admin cannot block, unblock or delete themselves or another admin
      (`AdminManagementTest::test_an_admin_cannot_block_or_delete_themselves_or_another_admin`).
- [x] Delete removes the account, its content and the reports about that
      content; reports the user filed are removed by the cascade
      (`AdminManagementTest::test_deleting_a_user_removes_their_content_and_reports`,
      `PhaseSevenTest::test_deleting_a_user_removes_the_reports_they_filed`).
- [x] Block and Delete open confirmation modals with CSRF-protected forms
      (`PhaseSevenTest::test_the_user_list_offers_block_and_delete_confirmation_modals`).

### FR-28 — Post and class management

- [x] Only administrators can open `/admin/posts` and `/admin/classes`
      (`AdminManagementTest::test_only_admins_can_reach_the_management_pages`).
- [x] Post search matches body and author; class search matches title and
      teacher (`PhaseSevenTest::test_admin_post_and_class_lists_can_be_searched`).
- [x] Posts filter by visibility; classes filter by status and visibility
      (`AdminManagementTest::test_admins_can_filter_posts_by_visibility`,
      `test_the_class_list_can_be_filtered_by_status`).
- [x] Lists paginate at 20 and show author/teacher, status and post report
      count (`PhaseSevenTest::test_admin_lists_paginate_at_twenty_rows`,
      `test_admin_lists_show_the_author_and_the_report_count`).
- [x] Hide/Unhide post and Hide (cancel)/Unhide class work and flash
      (`AdminManagementTest::test_an_admin_can_hide_unhide_and_delete_a_post`,
      `test_an_admin_can_hide_unhide_and_delete_a_class`).
- [x] Delete post/class is confirmed in a modal and removes the row
      (same two tests).
- [x] Hiding a post marks its pending reports reviewed
      (`AdminManagementTest::test_hiding_a_post_marks_its_pending_reports_reviewed`).
- [x] Hidden posts disappear from feed, profile, hashtag page, search and all
      counters (`PhaseSevenTest::test_hidden_posts_disappear_from_feed_profile_hashtag_and_search`).
- [x] Hidden comments disappear from the post page, feed preview and counters
      (`PhaseSevenTest::test_hidden_comments_disappear_from_the_post_and_its_counter`,
      `test_a_hidden_comment_disappears_from_the_feed_preview_and_counter`,
      `ReportTest::test_hiding_a_comment_removes_it_from_the_post_page`).
- [x] Hidden classes leave the catalogue and cannot be joined
      (`ClassPolicy::join`; `ClassTest` room-access cases).
- [x] The class teacher still sees their hidden class; admins see everything
      (`ClassPolicy::view`, admin list includes hidden rows).

### FR-32 — Reporting

- [x] Every visible post and comment exposes a Report action and its own modal
      (`PhaseSevenTest::test_every_post_and_comment_offers_a_report_modal`).
- [x] Reason is required and restricted to the five allowed values; details are
      optional and capped at 500
      (`ReportTest::test_the_reason_is_required_and_details_are_limited`).
- [x] A report stores reporter + polymorphic target with `pending`
      (`ReportTest::test_a_user_can_report_somebody_elses_post`,
      `test_a_user_can_report_a_comment`).
- [x] Reports are refused on your own content with 403
      (`ReportTest::test_a_user_cannot_report_their_own_content`).
- [x] The same user cannot report the same item twice; the friendly error flash
      is shown and only one row exists
      (`ReportTest::test_the_same_user_cannot_report_the_same_item_twice`,
      `PhaseSevenTest::test_a_report_cannot_be_filed_twice_on_the_same_comment`).
- [x] Hidden content cannot be reported (404)
      (`ReportTest::test_hidden_content_cannot_be_reported`).
- [x] A comment under a hidden post cannot be reported (404)
      (`PhaseSevenTest::test_a_comment_under_a_hidden_post_cannot_be_reported`).
- [x] A hidden post offers no report action to its viewers
      (`PhaseSevenTest::test_a_hidden_post_offers_no_report_action_to_its_viewers`).
- [x] Guests are redirected to login and no report is written
      (`ReportTest::test_a_guest_cannot_report_content`).
- [x] Success flash names the reason; blocked users cannot report
      (`ReportTest`, `PhaseSevenTest::test_a_blocked_user_cannot_report_like_or_follow`).

### FR-33 — Moderation queue

- [x] `/admin/reports` is admin-only; teachers and students get 403
      (`ReportTest::test_only_admins_can_open_the_reports_queue`).
- [x] Status and type filters combine and paginate
      (`ReportTest::test_the_queue_can_be_filtered_by_status_and_type`).
- [x] Search matches details, reason, reporter, content and offending author
      (`PhaseSevenTest::test_the_report_queue_searches_details_reason_and_reporter`,
      `test_the_report_queue_search_matches_the_offending_author`).
- [x] Pagination at 20 and empty states are covered
      (`PhaseSevenTest::test_admin_lists_paginate_at_twenty_rows`).
- [x] The detail page shows the full content, details and related reports
      (`PhaseSevenTest::test_the_report_detail_page_offers_every_moderation_action`).
- [x] Every action is present and works: Hide, Unhide, Delete content, Block
      user, Mark reviewed, Dismiss (same test + `ReportTest`).
- [x] The queue never offers Block for an administrator; the disabled
      "protected" state is rendered instead
      (`PhaseSevenTest::test_the_queue_does_not_offer_to_block_an_administrator`,
      `test_an_administrator_cannot_be_blocked_from_the_report_queue`).
- [x] Every admin mutation route rejects teachers and students with 403 and
      changes nothing (`PhaseSevenTest::test_every_admin_mutation_route_rejects_teachers_and_students`).
- [x] Deleting reported content removes the content and all its reports
      (`ReportTest::test_deleting_the_content_removes_the_content_and_its_reports`,
      `PhaseSevenTest::test_deleting_a_reported_comment_removes_it_and_its_reports`).

### FR-34 — Outcomes, status transitions and dashboard

- [x] Hide → content hidden + report `reviewed`, and it leaves the feed
      (`ReportTest::test_hiding_a_post_marks_the_report_reviewed_and_removes_it_from_the_feed`).
- [x] Hiding resolves every other pending report about the same content
      (`PhaseSevenTest::test_hiding_content_resolves_every_pending_report_about_it`).
- [x] Unhide → content visible + report `reviewed`
      (`PhaseSevenTest::test_unhiding_restores_the_content_and_marks_the_report_reviewed`).
- [x] Block author → user blocked, sessions ended, report `reviewed`
      (`ReportTest::test_blocking_the_author_marks_the_report_reviewed_and_logs_them_out`).
- [x] Mark reviewed and Dismiss set the expected statuses; dismissed content
      stays visible (`ReportTest::test_a_report_can_be_dismissed_or_marked_reviewed`,
      `PhaseSevenTest::test_dismissing_a_report_keeps_the_content_visible`).
- [x] The admin dashboard shows the pending count, five newest reports, quick
      hide/dismiss forms and links to the filtered queue
      (`ReportTest::test_the_admin_dashboard_shows_the_pending_reports`,
      `PhaseSevenTest::test_the_admin_dashboard_links_to_the_pending_reports`).

### SRS section 5.6 — Safety

- [x] Only the owning teacher and enrolled students enter a class room
      (`ClassPolicy::join`, `ClassTest` join cases).
- [x] A hidden class cannot be joined by anyone (`ClassPolicy::join`).
- [x] Administrators moderate classes but cannot enter a room
      (`ClassPolicy::join`).
- [x] A blocked user cannot post, comment, enroll or join
      (`PhaseSevenTest::test_a_blocked_user_cannot_post_comment_enroll_or_join`).
- [x] A blocked user cannot like, follow or report
      (`PhaseSevenTest::test_a_blocked_user_cannot_report_like_or_follow`).
- [x] Admin-only routes, including every mutation, return 403 for teacher and
      student (`PhaseSevenTest::test_every_admin_mutation_route_rejects_teachers_and_students`).

### Regression

- [x] Full suite: **307 passed (1,660 assertions)**.
- [x] Phase 7 targeted suites: **54 passed (327 assertions)**.
- [x] `vendor/bin/pint --test app config database routes tests` passes.
- [x] `npm run build` succeeds.
- [x] `php artisan view:cache` compiles every Blade template.

---

## 8. Manual UI checklist

These need a browser and are the only checks not covered by PHPUnit.

### Reporter (student or teacher)

- [ ] Open a post menu → "Report post" → the modal opens with focus inside.
- [ ] Submit with no reason → inline error under the select; the modal stays
      open and the details text are kept.
- [ ] Choose a reason and type exactly 500 characters → report saved, the
      success flash names the reason.
- [ ] Try 501 characters → the browser stops at 500 and server validation
      rejects a forged longer value.
- [ ] Report the same post again → friendly "already reported" message, no new
      queue row.
- [ ] Own posts and comments show no Report action.
- [ ] Report a comment with the flag button → same modal and success flash.
- [ ] A hidden post and its comments show no Report action.
- [ ] Keyboard: Tab through reason → details → Cancel → Send; Escape closes the
      modal and returns focus to the trigger.

### Moderator (admin)

- [ ] `/admin/reports` opens; status pills, type filter and search combine and
      survive pagination; "Clear" resets them.
- [ ] Each row shows reporter, preview, offending user, reason, status and date.
- [ ] Detail → Hide: content disappears from the feed, profile, hashtag page and
      search; the report is Reviewed.
- [ ] Unhide brings the content back; Dismiss leaves it visible and marks the
      report Dismissed.
- [ ] Delete content asks in a modal, then removes content and reports and
      returns to the queue.
- [ ] Block author asks in a modal, logs the user out and marks the report
      Reviewed.
- [ ] An administrator's report shows the disabled "protected" Block state; an
      already-blocked author shows "already blocked".
- [ ] `/admin/users`: search, role/status filters, block/unblock, delete modal,
      and protected self/admin rows.
- [ ] `/admin/posts`: search, visibility filter, hide/unhide, delete modal,
      report count.
- [ ] `/admin/classes`: search, status/visibility filters, "Hide (cancel)",
      unhide, delete modal.
- [ ] Dashboard "Pending reports" card links to the filtered queue; quick
      hide/dismiss buttons work and refresh the count.

### Blocked user

- [ ] Login is refused with "Your account has been blocked by an administrator.
      Please contact support."
- [ ] An already-open session is logged out on the next request with the same
      message.
- [ ] Posting, commenting, liking, following, enrolling and joining all
      redirect to login.

### Accessibility and layout

- [ ] Modals have accessible names, focus trapping and a visible close button.
- [ ] Tables scroll horizontally at 360 px without breaking the page.
- [ ] Status and role badges are not the only carrier of meaning (text labels
      accompany colour).
- [ ] Both themes keep the `/users/2` purple/indigo accent unchanged.

---

## 9. Commands and verification

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
vendor/bin/pint app config database routes tests
php artisan test
php artisan view:cache && php artisan view:clear
```

Result of the final run:

```text
php artisan test
Tests:  307 passed (1660 assertions)
Duration: 57.16s

php artisan test tests/Feature/ReportTest.php \
  tests/Feature/AdminManagementTest.php \
  tests/Feature/PhaseSevenTest.php \
  tests/Feature/Auth/BlockedUserTest.php
Tests:  54 passed (327 assertions)

vendor/bin/pint --test app config database routes tests
PASS

npm run build
✓ 140 modules transformed
public/build/assets/app-C-UklRRc.css     368.63 kB │ gzip: 57.47 kB
public/build/assets/auto-DwCtJEwz.js     209.95 kB │ gzip: 72.11 kB
public/build/assets/app-3WQrOwso.js      229.62 kB │ gzip: 75.83 kB
✓ built in 11.05s

php artisan view:cache
Blade templates cached successfully.
```

Focused commands:

```bash
php artisan test --filter=ReportTest
php artisan test --filter=AdminManagementTest
php artisan test --filter=PhaseSevenTest
php artisan test --filter=BlockedUserTest
php artisan test --filter=SmokeTest
```

Environment notes:

- The test suite uses SQLite in memory, the array session driver and the array
  mail driver. MySQL 8 is the production target but was not available in this
  environment, so MySQL-specific behaviour is covered by portable SQL only.
- `QUEUE_CONNECTION=sync` remains the local/demo default; Phase 6 queued
  notifications therefore run inline in this setup.
- Live modal behaviour, viewport layout and the real Jitsi room handshake are
  manual checks because there is no browser automation here.

---

## 10. Done when

- [x] Every visible post and comment offers a Report action; the reason is
      required, details are optional and capped at 500.
- [x] A report is stored polymorphically with its reporter and `pending`
      status; duplicates and self-reports are refused with clear feedback.
- [x] `/admin/reports` filters by status, type and text, paginates, and shows
      reporter, preview, offending user, reason, status and date.
- [x] Hide, Unhide, Delete content, Block author, Mark reviewed and Dismiss all
      work from the detail page, flash a message and update the report.
- [x] Hidden posts and comments disappear from feed, profile, hashtag page,
      search, post pages and every counter.
- [x] `/admin/users` supports search, role/blocked filters, pagination,
      block/unblock/delete with confirmation, and protects self and other
      admins.
- [x] Blocked accounts cannot log in, are ejected mid-session and cannot post,
      comment, like, follow, enrol, join or report.
- [x] `/admin/posts` and `/admin/classes` support search, filters, pagination
      and the required moderation actions with clear status and author info.
- [x] Only the owning teacher and enrolled students can enter a class room, and
      a hidden class is closed to everyone.
- [x] Every admin route, including mutations, returns 403 for teachers and
      students.
- [x] The admin dashboard exposes pending reports with quick links and actions.
- [x] Phase 1–6 and Phase 8 remain intact; the full suite, Pint, the asset build
      and view caching all pass.
