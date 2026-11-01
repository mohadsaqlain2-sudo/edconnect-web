# Phase 6 — Notifications and Search

**Project:** EduConnect — Laravel 12.70.2 / Bootstrap 5.3.8 / Vite 6.4.5  
**Phase:** 6 — notifications and discovery (FR-14, FR-23, FR-24, FR-25)  
**Status:** Implemented, built and verified against the full test suite  
**Date:** 31 October 2026

---

## 1. Objective

Deliver database-backed notifications and one search box for the whole app:

- five notification types: new follower, post liked, post commented, new class from a followed teacher and class starting soon
- every notification stores a type, a human message, a target URL and the actor’s name and avatar
- notifications are queued (`ShouldQueue`) and use Laravel’s database channel; the demo can run synchronously with no worker
- the navbar bell shows the unread count, hides the badge at zero, lists the newest eight entries and polls every 30 seconds
- `/notifications` lists every notification with an all/unread filter, single AJAX mark-as-read, mark-all-as-read and delete
- `/search?q=…` searches people (name, subject, bio), hashtags (with or without `#`) and post text, with escaped `LIKE` queries, pagination and empty states
- debounced live suggestions return at most five people or hashtags
- users can only read, mark or delete their own notifications; blocked accounts never appear in search or suggestions

No earlier feature was changed. The work is additive, with small UI polish in the
shared user card and the notification row.

---

## 2. Requirement map

| FR | Requirement | Implementation |
| --- | --- | --- |
| **FR-23** | Five database notification classes, no self-notifications, triggers in the existing controllers, queued with a sync fallback | `app/Notifications/*`, `FollowController`, `LikeController`, `CommentController`, `ClassController`, `NotifyClassesStartingSoon` |
| **FR-23** | “Class starting soon” every minute, 15 minutes ahead, never twice | `classes:notify-starting-soon` + `classes.starting_soon_notified_at` one-shot flag |
| **FR-24** | Bell with unread badge (hidden at zero), latest 8, unread highlight, time ago, click marks read, 30 s polling | `layouts/navigation.blade.php`, `notifications/_dropdown.blade.php`, `resources/js/notifications.js`, `NotificationController@poll` |
| **FR-25** | Paginated page, all/unread filter, single AJAX read, mark all, delete, own-record authorization | `NotificationController`, `resources/views/notifications/index.blade.php` |
| **FR-14** | Navbar search, People / Hashtags / Posts tabs, role badge + follow button, post counts, escaped LIKE, pagination, 2-character minimum, empty state, debounced suggestions | `SearchController`, `resources/views/search/index.blade.php`, `resources/js/search.js`, `components/user-card.blade.php` |

---

## 3. Behaviour details

### Notifications (FR-23)

- All five classes implement `ShouldQueue` and use the `database` channel only.
- `toArray()` stores exactly the keys the UI needs: `type`, `icon`, `message`,
  `url`, `actor_id`, `actor_name` and `actor_photo` (`PostCommented` adds a
  short `excerpt`).
- A user is never notified about their own action:
  - following yourself is blocked before the notification,
  - liking your own post is skipped,
  - commenting on your own post is skipped,
  - a teacher creating a class only notifies their followers.
- Following the same member twice is idempotent and queues only one
  `NewFollower`; a duplicate like is impossible because of the unique index.
- New followers, likes and comments are triggered directly from the existing
  controllers. Creating a class notifies every non-blocked follower through
  `ClassController::notifyFollowers()`.
- `classes:notify-starting-soon` selects scheduled, visible classes whose start
  falls in the next 15 minutes, skips rows whose
  `starting_soon_notified_at` flag is already set, queues one notification per
  enrolled student plus one for the teacher, then writes the flag in the same
  pass. Running the command again is a no-op.
- The command is scheduled every minute in `routes/console.php` with
  `withoutOverlapping()`.

### Queue operation

The code is queue-ready; the driver is configuration:

```dotenv
# Local/demo: notifications are written during the request, no worker needed.
QUEUE_CONNECTION=sync

# Production: notifications are written by a worker.
QUEUE_CONNECTION=database
```

```bash
# Run a worker locally after switching to the database driver.
php artisan queue:work --sleep=3 --tries=3

# Production (Supervisor, see docs/DEPLOYMENT.md §10)
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

With `sync` (the default in `.env` and `.env.example`) the `ShouldQueue`
classes run inline, so the demo works without a queue worker. Tests use
`QUEUE_CONNECTION=sync`, which is why they can assert the notification rows
immediately.

### Bell and unread badge (FR-24)

- The layout computes one unread count and shares it with the top bar and the
  mobile bottom navigation, so there is no duplicate `COUNT` query.
- The bell badge is rendered with `d-none` when the count is zero and is capped
  at `9+`.
- The dropdown contains the latest eight notifications: icon, message, human
  time and an unread background. Clicking an entry goes through
  `notifications.open`, which marks it read and redirects to the stored URL.
- `resources/js/notifications.js` polls `/notifications/poll` every 30 seconds
  with `fetch()`. The endpoint returns the fresh unread count and the
  re-rendered dropdown HTML, so the badge and the list update without a reload.
  Polling pauses while the page is hidden, resumes on `visibilitychange`, and
  also refreshes when the bell is opened.
- The endpoint is protected by `auth`, `not.blocked` and a named
  `notifications-poll` limiter (60 requests per minute per user/IP).

### Notifications page (FR-25)

- `/notifications` paginates 20 entries, newest first, with an `all` / `unread`
  filter that survives pagination through `withQueryString()`.
- Today and older entries are grouped; every row shows the icon, message,
  optional comment excerpt, relative time and unread marker.
- Single “Mark as read” is a real `PATCH` form. With JavaScript it becomes a
  `fetch()` call: the row loses its unread background, dot and label, the bell
  badge drops immediately, and the dropdown refreshes in the background.
  Without JavaScript the same form still works with a redirect and flash.
- “Mark all as read” uses `unreadNotifications->markAsRead()` in one query.
- Delete is a `DELETE` form with a confirmation prompt.
- Every lookup uses `$request->user()->notifications()` and
  `firstOrFail()`, so another user’s notification id answers 404. Mark-all only
  touches the current user’s unread collection.

### Search (FR-14)

- The navbar (desktop and the phone panel) submits `GET /search?q=…`.
- `/search` has three tabs: People, Hashtags and Posts. Only the visible tab is
  queried, which keeps the page cheap.
- People are matched against `name`, `subject` and `bio`. Blocked accounts and
  admins are excluded. Each result uses the shared user card with a real role
  badge, a profile link and the AJAX Follow / Following button.
- Hashtags are matched with or without a leading `#`; the stored name never
  contains the symbol. The label shows a count of visible posts only.
- Posts match the body, exclude hidden posts and posts by blocked authors, and
  reuse the feed card with the same eager loading.
- Every `LIKE` uses bound parameters and `ESCAPE '!'`; `%`, `_` and `!` inside
  the term are escaped as literals. `!` is used instead of a backslash because
  MySQL and SQLite treat backslashes differently inside string literals.
- The term must be at least two characters. Shorter or empty terms render a
  friendly hint instead of querying.
- Results paginate 12 per page and keep the term and tab in every page link.
  Each tab has an empty state.
- `/search/suggest` returns at most five entries, people first and hashtags
  second. The script debounces 250 ms, aborts stale requests and builds the
  dropdown with `textContent`, never `innerHTML`.
- Search output is escaped; the highlight helper escapes first and only then
  wraps the matched text in `<mark>`.

---

## 4. Files created / changed

### Created in this phase

```
app/Notifications/NewFollower.php
app/Notifications/PostLiked.php
app/Notifications/PostCommented.php
app/Notifications/NewClassFromFollowedTeacher.php
app/Notifications/ClassStartingSoon.php
app/Console/Commands/NotifyClassesStartingSoon.php
app/Http/Controllers/NotificationController.php
app/Http/Controllers/SearchController.php
resources/js/notifications.js
resources/js/search.js
resources/views/notifications/_dropdown.blade.php
resources/views/notifications/index.blade.php
resources/views/search/index.blade.php
database/migrations/2026_10_29_124725_create_notifications_table.php
database/migrations/2026_10_29_130000_add_notification_support_to_classes_and_users.php
tests/Feature/NotificationTest.php
tests/Feature/SearchTest.php
docs/PHASE-6-DELIVERABLE.md
```

### Changed in this phase

```
app/Http/Controllers/FollowController.php            queues NewFollower
app/Http/Controllers/LikeController.php             queues PostLiked, skips self
app/Http/Controllers/CommentController.php          queues PostCommented, skips self
app/Http/Controllers/ClassController.php            queues NewClassFromFollowedTeacher
app/Models/ClassRoom.php                            starting_soon_notified_at cast/fillable
routes/web.php                                      notification + search routes
routes/console.php                                  every-minute reminder schedule
resources/views/layouts/navigation.blade.php        bell, desktop + phone search
resources/views/layouts/app.blade.php               polling URL + shared unread count
resources/views/components/user-card.blade.php      role badge
resources/js/app.js                                 imports notifications.js and search.js
.env / .env.example                                 queue notes (sync demo, database production)
docs/REQUIREMENTS-STATUS.md, docs/TEST-MATRIX.md, README.md
```

---

## 5. Full code

The sections below are the complete source of every Phase 6 file (plus the
touched controllers and layout). Earlier-phase code that was not changed is not
repeated.

### Notification classes

#### `app/Notifications/NewFollower.php`

```php
<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * "X started following you" (FR-23).
 *
 * The notification is queued (QUEUE_CONNECTION=database), so following somebody
 * answers immediately and the row is written by the queue worker.
 */
class NewFollower extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $follower) {}

    /**
     * Only the database channel is used in EduConnect.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * The payload stored in the notifications table.
     * The views only print "message", "icon" and "url", so the sentence is
     * translated once, here.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_follower',
            'icon' => 'bi-person-plus',
            'actor_id' => $this->follower->id,
            'actor_name' => $this->follower->name,
            'actor_photo' => $this->follower->photoUrl(),
            'message' => __(':name started following you.', ['name' => $this->follower->name]),
            // Clicking the entry opens the follower's public profile (FR-25).
            'url' => route('users.show', $this->follower),
        ];
    }
}
```

#### `app/Notifications/PostLiked.php`

```php
<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * "X liked your post" (FR-23). Queued, database channel only.
 */
class PostLiked extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $liker, public Post $post) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'post_liked',
            'icon' => 'bi-hand-thumbs-up',
            'actor_id' => $this->liker->id,
            'actor_name' => $this->liker->name,
            'actor_photo' => $this->liker->photoUrl(),
            'message' => __(':name liked your post.', ['name' => $this->liker->name]),
            'url' => route('posts.show', $this->post),
        ];
    }
}
```

#### `app/Notifications/PostCommented.php`

```php
<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * "X commented on your post" (FR-23). Queued, database channel only.
 */
class PostCommented extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $commenter,
        public Post $post,
        public Comment $comment,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'post_commented',
            'icon' => 'bi-chat-dots',
            'actor_id' => $this->commenter->id,
            'actor_name' => $this->commenter->name,
            'actor_photo' => $this->commenter->photoUrl(),
            'message' => __(':name commented on your post.', ['name' => $this->commenter->name]),
            // A short preview so the entry makes sense in the dropdown.
            'excerpt' => Str::limit($this->comment->body, 60),
            'url' => route('posts.show', $this->post),
        ];
    }
}
```

#### `app/Notifications/NewClassFromFollowedTeacher.php`

```php
<?php

namespace App\Notifications;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * "The teacher you follow created a class" (FR-23).
 * One notification per follower, sent when the class is created.
 */
class NewClassFromFollowedTeacher extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $teacher, public ClassRoom $classRoom) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_class',
            'icon' => 'bi-easel',
            'actor_id' => $this->teacher->id,
            'actor_name' => $this->teacher->name,
            'actor_photo' => $this->teacher->photoUrl(),
            'message' => __(':name scheduled a new class: :title', [
                'name' => $this->teacher->name,
                'title' => $this->classRoom->title,
            ]),
            'url' => route('classes.show', $this->classRoom),
        ];
    }
}
```

#### `app/Notifications/ClassStartingSoon.php`

```php
<?php

namespace App\Notifications;

use App\Models\ClassRoom;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * "Your class starts in a few minutes" (FR-23).
 * Built by the scheduled command classes:notify-starting-soon and sent to the
 * teacher plus every enrolled student, once per class.
 */
class ClassStartingSoon extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ClassRoom $classRoom) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'class_starting_soon',
            'icon' => 'bi-alarm',
            'actor_id' => $this->classRoom->teacher_id,
            'actor_name' => $this->classRoom->teacher?->name,
            'actor_photo' => $this->classRoom->teacher?->photoUrl(),
            'message' => __(':title starts at :time - get ready to join.', [
                'title' => $this->classRoom->title,
                'time' => $this->classRoom->startsAt()->format('H:i'),
            ]),
            // The class page then offers the Join button (FR-20).
            'url' => route('classes.show', $this->classRoom),
        ];
    }
}
```

### Scheduled reminder command

#### `app/Console/Commands/NotifyClassesStartingSoon.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\ClassRoom;
use App\Notifications\ClassStartingSoon;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * FR-23: remind the teacher and the enrolled students a few minutes before a
 * class starts.
 *
 * The scheduler calls this command every minute (see routes/console.php).
 * The "starting_soon_notified_at" column is the flag that makes sure a class
 * is announced exactly once: it is written in the same pass that queues the
 * notifications, and only rows with a NULL flag are picked up.
 */
class NotifyClassesStartingSoon extends Command
{
    /**
     * @var string
     */
    protected $signature = 'classes:notify-starting-soon {--minutes=15 : How far ahead to look}';

    /**
     * @var string
     */
    protected $description = 'Notify the teacher and enrolled students of classes starting in the next 15 minutes';

    public function handle(): int
    {
        $from = now();
        $until = now()->addMinutes((int) $this->option('minutes'));

        // Cheapest possible pre-filter: only scheduled, visible classes on the
        // right day that were never announced. The exact minute is then checked
        // in PHP, which behaves the same on MySQL and on SQLite (tests).
        $candidates = ClassRoom::query()
            ->visible()
            ->where('status', ClassRoom::STATUS_SCHEDULED)
            ->whereNull('starting_soon_notified_at')
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $until->toDateString())
            ->with(['teacher', 'students'])
            ->get();

        $notified = 0;

        foreach ($candidates as $classRoom) {
            if (! $this->startsWithin($classRoom, $from, $until)) {
                continue;
            }

            // Enrolled students first, then the teacher who runs the class.
            foreach ($classRoom->students as $student) {
                $student->notify(new ClassStartingSoon($classRoom));
            }

            $classRoom->teacher?->notify(new ClassStartingSoon($classRoom));

            // One shot: the flag keeps the reminder from being sent again.
            $classRoom->forceFill(['starting_soon_notified_at' => now()])->save();

            $notified++;
        }

        $this->info("Starting-soon notifications queued for {$notified} class(es).");

        return self::SUCCESS;
    }

    /**
     * Does the class start between $from and $until?
     */
    private function startsWithin(ClassRoom $classRoom, Carbon $from, Carbon $until): bool
    {
        $startsAt = $classRoom->startsAt();

        return $startsAt->greaterThanOrEqualTo($from) && $startsAt->lessThanOrEqualTo($until);
    }
}
```

### Controllers

#### `app/Http/Controllers/NotificationController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * The notification bell, the notifications page and "mark as read"
 * (FR-23 notifications, FR-24 unread counter, FR-25 read handling).
 *
 * Every lookup goes through $request->user()->notifications(), so one user can
 * never see or change the notifications of somebody else - a wrong id simply
 * answers 404.
 */
class NotificationController extends Controller
{
    /** How many entries the bell dropdown shows. */
    private const DROPDOWN_LIMIT = 8;

    /** Notifications per page on the full page. */
    private const PER_PAGE = 20;

    /**
     * The full notifications page, newest first, with an "unread only" filter.
     */
    public function index(Request $request): View
    {
        // Anything that is not "unread" shows everything.
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        $notifications = $request->user()
            ->notifications()
            ->when($filter === 'unread', static fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
        ]);
    }

    /**
     * Small JSON answer for the polling script in the navbar (FR-24).
     * It carries the fresh unread counter and the newest eight entries as HTML,
     * so one request keeps the whole bell up to date.
     */
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->take(self::DROPDOWN_LIMIT)
            ->get();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'html' => view('notifications._dropdown', ['notifications' => $notifications])->render(),
        ]);
    }

    /**
     * Mark one notification as read (FR-25).
     */
    public function markAsRead(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $this->findOwn($request, $notification)->markAsRead();

        if ($request->wantsJson()) {
            return response()->json([
                'read' => true,
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        return back()->with('success', __('Notification marked as read.'));
    }

    /**
     * Mark everything as read (FR-25).
     */
    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        // unreadNotifications is a DatabaseNotificationCollection that knows
        // how to mark all of its rows as read in one query.
        $request->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['read' => true, 'unread_count' => 0]);
        }

        return back()->with('success', __('All notifications marked as read.'));
    }

    /**
     * Delete one of MY notifications (FR-25).
     */
    public function destroy(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $this->findOwn($request, $notification)->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'deleted' => true,
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        return back()->with('success', __('Notification deleted.'));
    }

    /**
     * Clicking a notification: mark it read, then open the related page (FR-25).
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $model = $this->findOwn($request, $notification);
        $model->markAsRead();

        // The target URL was built by the notification itself; fall back to the
        // list when an older entry has none.
        $url = $model->data['url'] ?? null;

        return $url
            ? redirect()->to($url)
            : redirect()->route('notifications.index');
    }

    /**
     * Fetch one of MY notifications or answer 404.
     */
    private function findOwn(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->whereKey($id)->firstOrFail();
    }
}
```

#### `app/Http/Controllers/SearchController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;

/**
 * FR-14: one search box for people, hashtags and posts.
 *
 * The term is bound as a parameter by the query builder (no SQL injection) and
 * every LIKE wildcard inside the term is escaped, so typing "%" cannot turn the
 * box into "match everything". All output is escaped by Blade.
 */
class SearchController extends Controller
{
    /** Results per page. */
    private const PER_PAGE = 12;

    /** The three tabs of the search page. */
    public const TABS = ['users', 'hashtags', 'posts'];

    /** Below this length a search matches almost everything, so we wait. */
    private const MIN_TERM_LENGTH = 2;

    /** How many entries the navbar suggestion dropdown shows. */
    private const SUGGESTION_LIMIT = 5;

    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        // Anything that is not a known tab falls back to "users".
        $tab = in_array($request->query('tab'), self::TABS, true)
            ? (string) $request->query('tab')
            : 'users';

        // Only the visible tab is queried: half the work per request.
        $users = null;
        $hashtags = null;
        $posts = null;

        if (mb_strlen($term) >= self::MIN_TERM_LENGTH) {
            match ($tab) {
                'hashtags' => $hashtags = $this->searchHashtags($term),
                'posts' => $posts = $this->searchPosts($term, $request->user()),
                default => $users = $this->searchUsers($term),
            };
        }

        return view('search.index', [
            'term' => $term,
            'tab' => $tab,
            'users' => $users,
            'hashtags' => $hashtags,
            'posts' => $posts,
            // One query so each person card knows whether to show Follow or Following.
            'followingIds' => $request->user()->following()->pluck('users.id'),
            // Escaped highlight helper: matched text gets a <mark>, everything
            // else is escaped exactly once.
            'highlight' => fn (string $text): HtmlString => $this->highlight($text, $term),
        ]);
    }

    /**
     * Live suggestions for the navbar box: at most five people or hashtags.
     * Answers an empty list for a term that is too short, so the script can
     * clear its dropdown without special-casing anything.
     */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < self::MIN_TERM_LENGTH) {
            return response()->json(['suggestions' => []]);
        }

        $pattern = $this->likePattern($term);

        // People come first, because that is what the box is mostly used for.
        $suggestions = $this->suggestUsers($pattern);

        // Fill the remaining slots with hashtags.
        if (count($suggestions) < self::SUGGESTION_LIMIT) {
            $suggestions = array_merge(
                $suggestions,
                $this->suggestHashtags($term, self::SUGGESTION_LIMIT - count($suggestions))
            );
        }

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * People whose name, subject or bio matches the term.
     */
    private function searchUsers(string $term): LengthAwarePaginator
    {
        $pattern = $this->likePattern($term);

        return User::query()
            // Blocked accounts never show up in the results (FR-6, FR-14).
            ->where('is_blocked', false)
            // Admins are not discoverable: the search is for teachers and students.
            ->whereIn('role', [User::ROLE_TEACHER, User::ROLE_STUDENT])
            ->where(function (Builder $query) use ($pattern) {
                // The column names are fixed strings, never user input.
                $query->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("subject LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("bio LIKE ? ESCAPE '!'", [$pattern]);
            })
            // One query for all follower counters instead of one per card.
            ->withCount('followers')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Hashtags whose name contains the term ("algebra" or "#algebra").
     */
    private function searchHashtags(string $term): LengthAwarePaginator
    {
        return Hashtag::query()
            ->whereRaw("name LIKE ? ESCAPE '!'", [$this->likePattern($this->stripHash($term))])
            // withCount feeds the "x posts" label without an extra query. Hidden
            // posts do not count (FR-28), so the label matches the hashtag page.
            ->withCount(['posts' => static fn (Builder $query) => $query->where('posts.is_hidden', false)])
            ->orderByDesc('posts_count')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Visible posts whose body contains the term (FR-14).
     */
    private function searchPosts(string $term, ?User $viewer): LengthAwarePaginator
    {
        return Post::query()
            ->visible()
            // Posts written by a blocked account stay out of the results too.
            ->whereHas('user', static fn (Builder $query) => $query->where('is_blocked', false))
            ->whereRaw("body LIKE ? ESCAPE '!'", [$this->likePattern($term)])
            // Same eager loading as the feed, so the cards cost no extra queries.
            ->withFeedData($viewer)
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return list<array<string, string>>
     */
    private function suggestUsers(string $pattern): array
    {
        return User::query()
            ->where('is_blocked', false)
            ->whereIn('role', [User::ROLE_TEACHER, User::ROLE_STUDENT])
            ->where(function (Builder $query) use ($pattern) {
                $query->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("subject LIKE ? ESCAPE '!'", [$pattern]);
            })
            ->orderBy('name')
            ->take(self::SUGGESTION_LIMIT)
            ->get(['id', 'name', 'role', 'subject'])
            ->map(static fn (User $user) => [
                'type' => 'user',
                'icon' => 'bi-person',
                'label' => $user->name,
                'meta' => $user->subject ?: __(ucfirst((string) $user->role)),
                'url' => route('users.show', $user),
            ])
            ->all();
    }

    /**
     * @return list<array<string, string>>
     */
    private function suggestHashtags(string $term, int $limit): array
    {
        return Hashtag::query()
            ->whereRaw("name LIKE ? ESCAPE '!'", [$this->likePattern($this->stripHash($term))])
            ->withCount(['posts' => static fn (Builder $query) => $query->where('posts.is_hidden', false)])
            ->orderByDesc('posts_count')
            ->orderBy('name')
            ->take($limit)
            ->get()
            ->map(static fn (Hashtag $hashtag) => [
                'type' => 'hashtag',
                'icon' => 'bi-hash',
                'label' => '#'.$hashtag->name,
                'meta' => trans_choice(':count post|:count posts', $hashtag->posts_count, ['count' => $hashtag->posts_count]),
                'url' => route('hashtags.show', $hashtag->name),
            ])
            ->all();
    }

    /**
     * Drop a single leading "#" so "algebra" and "#algebra" behave the same.
     * The stored hashtag name never contains the symbol.
     */
    private function stripHash(string $term): string
    {
        return str_starts_with($term, '#') ? substr($term, 1) : $term;
    }

    /**
     * Build the LIKE pattern for a term.
     *
     * "%", "_" and the escape character itself are escaped, so they are
     * searched as ordinary characters instead of acting as wildcards.
     * "!" is used as the escape character on purpose: a backslash is an escape
     * character inside MySQL string literals but not inside SQLite ones, so
     * "ESCAPE '\\'" would behave differently on the two databases.
     */
    private function likePattern(string $term): string
    {
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);

        return '%'.$escaped.'%';
    }

    /**
     * Mark the matched part of a label without ever trusting the label as HTML.
     * The text is escaped first, then a case-insensitive replacement wraps the
     * literal term in a <mark>; the result is safe to print with {!! !!}.
     */
    private function highlight(string $text, string $term): HtmlString
    {
        $escaped = e($text);
        $needle = trim(str_starts_with($term, '#') ? substr($term, 1) : $term);

        if ($needle === '' || mb_strlen($needle) < self::MIN_TERM_LENGTH) {
            return new HtmlString($escaped);
        }

        $pattern = '/'.preg_quote(e($needle), '/').'/iu';
        $marked = preg_replace($pattern, '<mark class="edu-mark">$0</mark>', $escaped);

        return new HtmlString($marked ?? $escaped);
    }
}
```

#### `app/Http/Controllers/FollowController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\NewFollower;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Follow / unfollow another member (FR-11).
 * Students and teachers may follow each other; admin accounts are never
 * followable and admins do not use these routes.
 * Every action answers JSON when it is called with fetch() and falls back to
 * a normal redirect when JavaScript is switched off.
 */
class FollowController extends Controller
{
    /**
     * Start following a member.
     */
    public function store(Request $request, User $user): JsonResponse|RedirectResponse
    {
        // Nobody follows themselves (the model refuses it too).
        // Checked first so that self-follow always answers 403.
        abort_if($request->user()->is($user), 403);

        // Admin accounts are not part of the social graph; hide them.
        abort_if($user->isAdmin(), 404);

        // Was the follow already there? syncWithoutDetaching() is idempotent,
        // so without this check every click would queue another notification.
        $alreadyFollowing = $request->user()->isFollowing($user);

        $request->user()->follow($user);

        // Tell the followed member about the new follower (FR-23). Self-follow
        // never gets here: the abort above already answered 403.
        if (! $alreadyFollowing) {
            $user->notify(new NewFollower($request->user()));
        }

        if ($request->wantsJson()) {
            return $this->jsonState($request, $user);
        }

        return back()->with('success', __('You now follow :name.', ['name' => $user->name]));
    }

    /**
     * Stop following a member.
     */
    public function destroy(Request $request, User $user): JsonResponse|RedirectResponse
    {
        abort_if($user->isAdmin(), 404);

        $request->user()->unfollow($user);

        if ($request->wantsJson()) {
            return $this->jsonState($request, $user);
        }

        return back()->with('success', __('You no longer follow :name.', ['name' => $user->name]));
    }

    /**
     * The follow state plus both counts, so the page can update its numbers.
     */
    private function jsonState(Request $request, User $user): JsonResponse
    {
        $viewer = $request->user();

        return response()->json([
            'following' => $viewer->isFollowing($user),
            // Identifies which profile's follower counter must be refreshed.
            'user_id' => $user->id,
            'viewer_id' => $viewer->id,
            'followers' => $user->followers()->count(),
            // The viewer's own "following" total, used by the feed sidebar.
            'following_count' => $viewer->following()->count(),
        ]);
    }
}
```

#### `app/Http/Controllers/LikeController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Notifications\PostLiked;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Like / unlike a post (FR-9).
 * Both actions answer JSON for the fetch() call from the feed, and fall back
 * to a normal redirect when JavaScript is not available.
 */
class LikeController extends Controller
{
    /**
     * Like a post.
     */
    public function store(Request $request, Post $post): JsonResponse|RedirectResponse
    {
        // A hidden post cannot be liked by people who are not allowed to see it.
        $this->authorize('view', $post);

        // firstOrCreate + the unique index on (post_id, user_id) make a
        // duplicate like impossible, even on a double click.
        $like = $post->likes()->firstOrCreate(['user_id' => $request->user()->id]);

        // Only a brand new like notifies the author, and a user is never
        // notified about their own action (FR-23).
        if ($like->wasRecentlyCreated && $post->user_id !== $request->user()->id) {
            $post->user?->notify(new PostLiked($request->user(), $post));
        }

        return $this->payload($request, $post);
    }

    /**
     * Remove my like from a post.
     */
    public function destroy(Request $request, Post $post): JsonResponse|RedirectResponse
    {
        // Keep the same visibility rule as store(), so a hidden post cannot be
        // probed through the unlike endpoint either.
        $this->authorize('view', $post);

        $post->likes()->where('user_id', $request->user()->id)->delete();

        return $this->payload($request, $post);
    }

    /**
     * The fresh like count and whether the viewer likes the post now.
     */
    private function payload(Request $request, Post $post): JsonResponse|RedirectResponse
    {
        // No JavaScript? Then a plain redirect keeps the page working.
        if (! $request->wantsJson()) {
            return back();
        }

        return response()->json([
            'liked' => $post->likes()->where('user_id', $request->user()->id)->exists(),
            'count' => $post->likes()->count(),
        ]);
    }
}
```

#### `app/Http/Controllers/CommentController.php`

```php
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
```

#### `app/Http/Controllers/ClassController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRequest;
use App\Http\Requests\UpdateClassRequest;
use App\Models\ClassRoom;
use App\Models\User;
use App\Notifications\NewClassFromFollowedTeacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A teacher manages their own live classes here (FR-15, FR-16, FR-21).
 * Create, list, edit, delete and the list of enrolled students.
 */
class ClassController extends Controller
{
    /** Classes per page. */
    private const PER_PAGE = 12;

    /** Students per page on the enrolled students page. */
    private const STUDENTS_PER_PAGE = 20;

    /**
     * "My classes": everything this teacher created, newest first.
     * A teacher can narrow the list to one status (FR-15).
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $status = is_string($status) && in_array($status, ClassRoom::STATUSES, true)
            ? $status
            : '';

        $classes = $request->user()->classes()
            // withCount keeps the card free of one query per class (no N+1).
            ->withCount('students')
            ->when($status !== '', fn ($query) => $query->byStatus($status))
            ->orderByRaw("CASE status WHEN 'live' THEN 0 WHEN 'scheduled' THEN 1 ELSE 2 END")
            ->orderBy('date')
            ->orderBy('time')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('classes.index', [
            'classes' => $classes,
            'status' => $status,
        ]);
    }

    /**
     * The "create a class" form.
     */
    public function create(): View
    {
        return view('classes.create');
    }

    /**
     * Store a new class and give it a hard to guess Jitsi room (FR-19).
     */
    public function store(StoreClassRequest $request): RedirectResponse
    {
        $classRoom = $request->user()->classes()->create([
            ...$request->validated(),
            // Never trust the client for the room name.
            'room_name' => ClassRoom::generateRoomName(),
            'status' => ClassRoom::STATUS_SCHEDULED,
        ]);

        // Everybody who follows this teacher gets a heads up (FR-23).
        $this->notifyFollowers($request->user(), $classRoom);

        return redirect()
            ->route('classes.show', $classRoom)
            ->with('success', __('Your class has been created.'));
    }

    /**
     * Queue a notification for each follower of the teacher (FR-23).
     * Blocked accounts receive nothing.
     */
    private function notifyFollowers(User $teacher, ClassRoom $classRoom): void
    {
        $teacher->followers()
            ->where('users.is_blocked', false)
            ->get()
            ->each(static fn (User $follower) => $follower->notify(
                new NewClassFromFollowedTeacher($teacher, $classRoom)
            ));
    }

    /**
     * The edit form. ClassPolicy decides who gets here (FR-16).
     */
    public function edit(ClassRoom $classRoom): View
    {
        $this->authorize('update', $classRoom);

        return view('classes.edit', ['classRoom' => $classRoom]);
    }

    /**
     * Save the changes to a class.
     */
    public function update(UpdateClassRequest $request, ClassRoom $classRoom): RedirectResponse
    {
        $this->authorize('update', $classRoom);

        // room_name and status are not part of validated(), so they stay as they are.
        $classRoom->update($request->validated());

        return redirect()
            ->route('classes.show', $classRoom)
            ->with('success', __('Your class has been updated.'));
    }

    /**
     * Delete a class. Enrollments disappear through the cascade rule (FR-16).
     */
    public function destroy(ClassRoom $classRoom): RedirectResponse
    {
        $this->authorize('delete', $classRoom);

        $classRoom->delete();

        return redirect()
            ->route('classes.index')
            ->with('success', __('Your class has been deleted.'));
    }

    /**
     * Everybody who enrolled in this class (FR-29).
     * Only the teacher of the class (or an admin) may look at this.
     */
    public function students(ClassRoom $classRoom): View
    {
        $this->authorize('update', $classRoom);

        $classRoom->load('teacher:id,name,subject')->loadCount('students');

        $students = $classRoom->students()
            ->orderBy('name')
            ->paginate(self::STUDENTS_PER_PAGE)
            ->withQueryString();

        return view('classes.students', [
            'classRoom' => $classRoom,
            'students' => $students,
        ]);
    }
}
```

### Model and migrations

#### `app/Models/ClassRoom.php`

```php
<?php

namespace App\Models;

use Database\Factories\ClassRoomFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A scheduled live class (FR-15 ... FR-21).
 * The class is called ClassRoom because "Class" is a reserved word in PHP;
 * the database table is "classes".
 */
class ClassRoom extends Model
{
    /** @use HasFactory<ClassRoomFactory> */
    use HasFactory;

    // The three possible states of a class.
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_LIVE = 'live';

    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_LIVE,
        self::STATUS_COMPLETED,
    ];

    /**
     * Which status changes a teacher is allowed to make (FR-21).
     * The only accepted workflow is scheduled -> live -> completed. A class
     * can never skip a step, jump backwards, or be reopened after it ended:
     * every button the teacher sees is derived from this map.
     */
    public const TRANSITIONS = [
        self::STATUS_SCHEDULED => [self::STATUS_LIVE],
        self::STATUS_LIVE => [self::STATUS_COMPLETED],
        self::STATUS_COMPLETED => [],
    ];

    /**
     * The table name does not follow the model name, so it is set explicitly.
     *
     * @var string
     */
    protected $table = 'classes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'teacher_id',
        'title',
        'description',
        'subject',
        'date',
        'time',
        'duration',
        'room_name',
        'status',
        'is_hidden',
        // Set by classes:notify-starting-soon (FR-23).
        'starting_soon_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'duration' => 'integer',
            'is_hidden' => 'boolean',
            'starting_soon_notified_at' => 'datetime',
        ];
    }

    /**
     * Every class gets an unguessable Jitsi room name, even when it is created
     * from code or a factory instead of the controller (Phase 2).
     */
    protected static function booted(): void
    {
        static::creating(function (ClassRoom $classRoom) {
            if (blank($classRoom->room_name)) {
                $classRoom->room_name = static::generateRoomName();
            }
        });
    }

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    /** The teacher who runs the class. */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** The enrolled students. */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_enrollments', 'class_id', 'student_id')
            ->withTimestamps();
    }

    /** Raw enrollment rows (used for attendance in Phase 5). */
    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class, 'class_id');
    }

    /* ---------------------------------------------------------------
     | Scopes and helpers
     * --------------------------------------------------------------- */

    /**
     * Classes that are still ahead of us (FR-17):
     * status "scheduled" and a date/time in the future, soonest first.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        $today = now()->toDateString();
        $now = now()->format('H:i:s');

        // whereDate/whereTime compare only the date or the time part, so the
        // result is the same on MySQL and on the SQLite database used by tests.
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where(function (Builder $query) use ($today, $now) {
                $query->whereDate('date', '>', $today)
                    // Same day: the start time must not have passed yet.
                    ->orWhere(function (Builder $query) use ($today, $now) {
                        $query->whereDate('date', $today)->whereTime('time', '>', $now);
                    });
            })
            ->orderBy('date')
            ->orderBy('time');
    }

    /**
     * Hide classes that an administrator has hidden (FR-28).
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Phase 2: filter by one of the three statuses.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Was this class given by the given teacher?
     */
    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->teacher_id === $user->id;
    }

    /** Is the class running right now? (FR-21) */
    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    /**
     * The exact moment the class starts (date + time merged into one Carbon).
     */
    public function startsAt(): Carbon
    {
        // "date" is cast to a Carbon date, "time" stays a plain string.
        $date = $this->date instanceof \DateTimeInterface
            ? $this->date->format('Y-m-d')
            : (string) $this->date;

        $time = $this->time instanceof \DateTimeInterface
            ? $this->time->format('H:i:s')
            : substr((string) $this->time, 0, 8);

        return Carbon::parse($date.' '.$time);
    }

    /** The moment the class ends (start + duration). */
    public function endsAt(): Carbon
    {
        return $this->startsAt()->addMinutes((int) $this->duration);
    }

    /**
     * Phase 2 accessor: {{ $classRoom->start_datetime }}.
     * Returns the merged date + time as a Carbon instance.
     */
    public function getStartDateTimeAttribute(): Carbon
    {
        return $this->startsAt();
    }

    /**
     * Has the class not started yet?
     */
    public function isInTheFuture(): bool
    {
        return $this->startsAt()->isFuture();
    }

    /**
     * Short timing hint for a class card (FR-21): "Live now",
     * "Starts in 2h 15m" or "Ended". No JavaScript needed, so the card stays
     * readable on a slow connection.
     */
    public function timelineLabel(): string
    {
        if ($this->isLive()) {
            return __('Live now');
        }

        // A class the teacher ended (or that the scheduler closed) is over.
        if ($this->status === self::STATUS_COMPLETED) {
            return __('Ended');
        }

        if ($this->startsAt()->isFuture()) {
            return __('Starts in :time', ['time' => $this->humanGap($this->startsAt())]);
        }

        // Still scheduled but its slot has already passed.
        return __('Ended');
    }

    /**
     * Blade-friendly accessor: {{ $classRoom->timeline_label }}.
     * Mirrors timelineLabel() so views and tests can use either the method or
     * the property without duplicating the wording rules (FR-21).
     */
    public function getTimelineLabelAttribute(): string
    {
        return $this->timelineLabel();
    }

    /**
     * "2h 15m" style gap between now and the given moment.
     * Deliberately coarse: the card only needs a hint, not the seconds.
     */
    private function humanGap(Carbon $moment): string
    {
        // "true" asks for the absolute value, which is the same on Carbon 2 and 3.
        $minutes = (int) now()->diffInMinutes($moment, true);

        $parts = [];

        if ($minutes >= 1440) {
            $parts[] = intdiv($minutes, 1440).'d';
        }

        if (intdiv($minutes % 1440, 60) > 0) {
            $parts[] = intdiv($minutes % 1440, 60).'h';
        }

        if ($minutes % 60 > 0) {
            $parts[] = ($minutes % 60).'m';
        }

        return $parts === [] ? '< 1m' : implode(' ', $parts);
    }

    /* ---------------------------------------------------------------
     | Status transitions (FR-21)
     * --------------------------------------------------------------- */

    /**
     * The statuses this class may move to next.
     *
     * @return list<string>
     */
    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    /**
     * May the class switch to the given status?
     */
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /* ---------------------------------------------------------------
     | Jitsi room (FR-19)
     * --------------------------------------------------------------- */

    /**
     * A new room name nobody can guess: "educonnect-" plus 20 random
     * characters. The loop guarantees the name is not used yet, and the
     * unique index on the column is the final safety net.
     */
    public static function generateRoomName(): string
    {
        do {
            $name = 'educonnect-'.Str::random(20);
        } while (static::query()->where('room_name', $name)->exists());

        return $name;
    }
}
```

#### `database/migrations/2026_10_29_124725_create_notifications_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
```

#### `database/migrations/2026_10_29_130000_add_notification_support_to_classes_and_users.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 support columns.
 *
 * classes.starting_soon_notified_at
 *   A one-shot flag: the "classes:notify-starting-soon" command sets it after
 *   it has warned the teacher and the enrolled students, so the reminder for
 *   that class can never be sent twice (FR-23).
 *
 * users.name / users.subject
 *   Indexes for the people search in the navbar (FR-14).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->timestamp('starting_soon_notified_at')->nullable()->after('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('name');
            $table->index('subject');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('starting_soon_notified_at');
        });

        Schema::table('users', function (Blueprint $table) {
            // Laravel builds the names "users_name_index" / "users_subject_index".
            $table->dropIndex(['name']);
            $table->dropIndex(['subject']);
        });
    }
};
```

### Routes and schedule

#### `routes/web.php`

```php
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
```

#### `routes/console.php`

```php
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
```

### JavaScript

#### `resources/js/notifications.js`

```javascript
/**
 * The navbar notification bell + the "mark as read" buttons (FR-24, FR-25).
 *
 * Every 30 seconds this asks /notifications/poll for the fresh unread counter
 * and the newest eight entries as HTML, then updates the badge and the dropdown
 * without a page reload. Polling pauses while the tab is in the background, so
 * it costs almost nothing.
 *
 * On the notifications page the single "mark as read" forms are upgraded to
 * fetch() calls: the row loses its unread styling and the bell counter drops,
 * with no reload. Without JavaScript the same forms still work.
 */
const POLL_INTERVAL_MS = 30000;

/** Put the server answer into the bell (badge + dropdown list). */
function updateBell(data) {
    const badge = document.querySelector('[data-notification-badge]');

    if (badge) {
        badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
        // Hide the badge completely when there is nothing unread.
        badge.classList.toggle('d-none', data.unread_count === 0);
    }

    const list = document.querySelector('[data-notification-list]');
    if (list && typeof data.html === 'string') {
        list.innerHTML = data.html;
    }
}

/** One polling round. Fails silently: the bell keeps its last known state. */
async function refreshNotifications() {
    const url = document.body.dataset.notificationUrl;

    if (!url) {
        return;
    }

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

        updateBell(await response.json());
    } catch (error) {
        console.error('[EduConnect] refreshing the notifications failed', error);
    }
}

function startNotificationPolling() {
    // Nothing to do for guests or on pages without the bell.
    if (!document.querySelector('[data-notification-list]')) {
        return;
    }

    setInterval(() => {
        // No point in polling a tab nobody is looking at.
        if (!document.hidden) {
            refreshNotifications();
        }
    }, POLL_INTERVAL_MS);

    // Coming back to the tab: refresh straight away.
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshNotifications();
        }
    });

    // Opening the bell also refreshes it.
    document.querySelectorAll('[data-notification-toggle]').forEach((button) => {
        button.addEventListener('click', refreshNotifications);
    });
}

/** Mark one notification as read over fetch() instead of a full page load. */
function upgradeReadButtons() {
    document.querySelectorAll('form[data-notification-read]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button = form.querySelector('button');
            if (button) {
                button.disabled = true;
            }

            try {
                // FormData carries the CSRF token hidden field with it.
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                        'X-HTTP-Method-Override': 'PATCH',
                    },
                    body: new FormData(form),
                });

                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                const data = await response.json();

                // Drop the unread look (dot, "Unread" label) and the button itself.
                const row = form.closest('.list-group-item');
                if (row) {
                    row.classList.remove('notification-unread');
                    row.querySelectorAll('[data-notification-unread-label], [data-notification-unread-dot]')
                        .forEach((node) => node.remove());
                }

                form.remove();

                if (typeof data.unread_count === 'number') {
                    updateBell({ unread_count: data.unread_count });
                }

                // The dropdown may still show this entry as unread, so refresh
                // it in the background too. Failures are already handled above.
                refreshNotifications();
            } catch (error) {
                // Something went wrong: let the plain form submit instead.
                console.error('[EduConnect] marking the notification as read failed', error);
                form.submit();
            }
        });
    });
}

function bootNotifications() {
    startNotificationPolling();
    upgradeReadButtons();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootNotifications);
} else {
    bootNotifications();
}
```

#### `resources/js/search.js`

```javascript
/**
 * Live search suggestions in the navbar (FR-14).
 *
 * While the user types, the box asks /search/suggest for at most five people or
 * hashtags and shows them in a dropdown. Requests are debounced (250 ms) and the
 * previous request is aborted, so typing fast does not fire one request per key.
 *
 * The entries are built with document.createElement + textContent, never with
 * innerHTML, so a name like "<script>" can never be turned into markup.
 */

/** Wait this long after the last keystroke before asking the server. */
const DEBOUNCE_MS = 250;

/** A shorter term than this matches almost everything, so it is not sent. */
const MIN_TERM_LENGTH = 2;

function bootSearchSuggestions() {
    document.querySelectorAll('[data-search-form]').forEach(bootSearchForm);
}

function bootSearchForm(form) {
    const url = form.dataset.searchUrl;
    const input = form.querySelector('[data-search-input]');
    const panel = form.querySelector('[data-search-suggestions]');

    if (!url || !input || !panel) {
        return;
    }

    let timer = null;
    let controller = null;

    /** Hide the dropdown and drop whatever it showed. */
    function hide() {
        panel.classList.add('d-none');
        panel.replaceChildren();
    }

    /** Draw the suggestions returned by the server. */
    function render(suggestions) {
        panel.replaceChildren();

        if (!Array.isArray(suggestions) || suggestions.length === 0) {
            panel.classList.add('d-none');

            return;
        }

        suggestions.forEach((item) => {
            const link = document.createElement('a');
            link.className = 'dropdown-item d-flex align-items-center gap-2';
            link.href = item.url;

            const icon = document.createElement('i');
            icon.className = `bi ${item.icon || 'bi-search'} text-edu`;
            icon.setAttribute('aria-hidden', 'true');

            const label = document.createElement('span');
            label.className = 'flex-grow-1';
            // textContent, not innerHTML: the label is plain text, always.
            label.textContent = item.label || '';

            const meta = document.createElement('span');
            meta.className = 'small text-muted';
            meta.textContent = item.meta || '';

            link.append(icon, label, meta);
            panel.appendChild(link);
        });

        panel.classList.remove('d-none');
    }

    /** One lookup for the current value of the box. */
    async function load() {
        const term = input.value.trim();

        if (term.length < MIN_TERM_LENGTH) {
            hide();

            return;
        }

        // A newer keystroke makes the answer of the running request useless.
        controller?.abort();
        controller = new AbortController();

        try {
            const response = await fetch(`${url}?q=${encodeURIComponent(term)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const data = await response.json();
            render(data.suggestions);
        } catch (error) {
            // An aborted request is not an error worth reporting.
            if (error.name !== 'AbortError') {
                console.error('[EduConnect] loading search suggestions failed', error);
            }
        }
    }

    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(load, DEBOUNCE_MS);
    });

    // Escape closes the dropdown.
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hide();
        }
    });

    // Clicking anywhere outside the box closes it too.
    document.addEventListener('click', (event) => {
        if (!form.contains(event.target)) {
            hide();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootSearchSuggestions);
} else {
    bootSearchSuggestions();
}
```

#### `resources/js/app.js`

```javascript
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
```

### Views and layout

#### `resources/views/notifications/_dropdown.blade.php`

```blade
{{--
    The newest notifications as Bootstrap dropdown items (FR-24).
    Included by the navbar bell on page load and re-rendered by
    /notifications/poll every 30 seconds, so it only ever emits <li> elements
    (it lives inside a dropdown <ul>).
--}}
@forelse ($notifications as $notification)
    @php $data = $notification->data; @endphp
    <li>
        {{-- Clicking the entry opens the related page and marks it read (FR-25). --}}
        <a class="dropdown-item d-flex gap-2 py-2 {{ $notification->read_at ? '' : 'notification-unread' }}"
           href="{{ route('notifications.open', $notification->id) }}">
            <i class="bi {{ $data['icon'] ?? 'bi-bell' }} text-edu mt-1"></i>
            <span class="flex-grow-1">
                {{-- Everything is escaped by Blade. --}}
                <span class="d-block small">{{ $data['message'] ?? __('Notification') }}</span>
                <span class="d-block text-muted edu-time">{{ $notification->created_at?->diffForHumans() }}</span>
            </span>
        </a>
    </li>
@empty
    <li>
        <span class="dropdown-item-text small text-muted">{{ __('No notifications yet.') }}</span>
    </li>
@endforelse

<li><hr class="dropdown-divider"></li>
<li>
    <a class="dropdown-item text-center small fw-semibold" href="{{ route('notifications.index') }}">
        <i class="bi bi-list-ul me-1"></i>{{ __('See all notifications') }}
    </a>
</li>
```

#### `resources/views/notifications/index.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Notifications') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Notifications') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-bell me-1 text-edu" aria-hidden="true"></i>{{ __('Notifications') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Likes, comments, new followers and class reminders.') }}</p>
            </div>

            @if ($notifications->total() > 0)
                {{-- FR-25: mark everything as read in one click. --}}
                <form method="POST" action="{{ route('notifications.read-all') }}" data-notification-read-all>
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-check2-all me-1" aria-hidden="true"></i> {{ __('Mark all as read') }}
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                {{-- All / unread filter, carried through the pagination links (FR-25). --}}
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <a class="edu-filter-chip {{ $filter === 'all' ? 'is-active' : '' }}"
                       href="{{ route('notifications.index') }}"
                       @if ($filter === 'all') aria-current="page" @endif>
                        <i class="bi bi-inbox" aria-hidden="true"></i> {{ __('All') }}
                    </a>
                    <a class="edu-filter-chip {{ $filter === 'unread' ? 'is-active' : '' }}"
                       href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                       @if ($filter === 'unread') aria-current="page" @endif>
                        <i class="bi bi-dot" aria-hidden="true"></i> {{ __('Unread') }}
                    </a>

                    <span class="ms-auto small text-muted">
                        {{ trans_choice(':count notification|:count notifications', $notifications->total(), ['count' => $notifications->total()]) }}
                    </span>
                </div>

                @if ($notifications->isEmpty())
                    <x-empty-state icon="bi-bell-slash"
                                   :title="$filter === 'unread' ? __('Nothing unread') : __('No notifications yet')"
                                   :message="$filter === 'unread'
                                        ? __('You are all caught up. New activity will appear here.')
                                        : __('Follow a teacher or write a post and the activity shows up here.')">
                        @if ($filter === 'unread')
                            <a href="{{ route('notifications.index') }}" class="btn btn-primary">{{ __('See all notifications') }}</a>
                        @else
                            <a href="{{ route('feed.index') }}" class="btn btn-primary">{{ __('Go to the feed') }}</a>
                        @endif
                    </x-empty-state>
                @else
                    @php
                        // Group without losing the paginator: Today first, then everything older.
                        $groups = $notifications->getCollection()->groupBy(
                            static fn ($notification) => $notification->created_at?->isToday() ? 'today' : 'earlier'
                        );
                    @endphp

                    @foreach (['today' => __('Today'), 'earlier' => __('Earlier')] as $key => $label)
                        @if ($groups->has($key))
                            <h2 class="edu-notif-group-label" id="notif-group-{{ $key }}">{{ $label }}</h2>

                            <div class="list-group list-group-flush border rounded-3 overflow-hidden mb-3"
                                 aria-labelledby="notif-group-{{ $key }}">
                                @foreach ($groups->get($key) as $notification)
                                    @php $data = $notification->data; @endphp

                                    <div class="list-group-item d-flex gap-3 align-items-start {{ $notification->read_at ? '' : 'notification-unread' }}">
                                        <span class="edu-notif-icon flex-shrink-0" aria-hidden="true">
                                            <i class="bi {{ $data['icon'] ?? 'bi-bell' }}"></i>
                                        </span>

                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-start gap-2">
                                                @unless ($notification->read_at)
                                                    <span class="edu-notif-dot mt-2" data-notification-unread-dot aria-hidden="true"></span>
                                                    <span class="visually-hidden" data-notification-unread-label>{{ __('Unread') }}</span>
                                                @endunless

                                                {{-- Opening the entry marks it read and follows its link (FR-25). --}}
                                                <a href="{{ route('notifications.open', $notification->id) }}"
                                                   class="text-decoration-none text-reset fw-semibold text-break">
                                                    {{ $data['message'] ?? __('Notification') }}
                                                </a>
                                            </div>

                                            @isset($data['excerpt'])
                                                <div class="small text-muted text-break">&ldquo;{{ $data['excerpt'] }}&rdquo;</div>
                                            @endisset

                                            <div class="small text-muted">
                                                <time datetime="{{ $notification->created_at?->toIso8601String() }}">
                                                    {{ $notification->created_at?->diffForHumans() }}
                                                </time>

                                                @unless ($notification->read_at)
                                                    <span class="mx-1" aria-hidden="true">·</span>
                                                    <span class="text-edu fw-semibold" data-notification-unread-label>{{ __('Unread') }}</span>
                                                @endunless
                                            </div>
                                        </div>

                                        <div class="d-flex gap-1 flex-shrink-0">
                                            @unless ($notification->read_at)
                                                {{-- FR-25: mark this single entry as read. Works as a
                                                     normal form, resources/js/notifications.js turns it
                                                     into a fetch() call when JavaScript is available. --}}
                                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}"
                                                      data-notification-read>
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                            title="{{ __('Mark as read') }}"
                                                            aria-label="{{ __('Mark as read') }}">
                                                        <i class="bi bi-check2" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            @endunless

                                            {{-- FR-25: remove the notification for good. --}}
                                            <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}"
                                                  data-notification-delete
                                                  data-confirm="{{ __('Delete this notification?') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="{{ __('Delete') }}"
                                                        aria-label="{{ __('Delete') }}">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                    <x-pagination-links :paginator="$notifications" />
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
```

#### `resources/views/search/index.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Search') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Search') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-search me-1 text-edu" aria-hidden="true"></i>{{ __('Search') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Find people, hashtags and posts in one place.') }}</p>
            </div>
            <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to feed') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                {{-- The same search box as in the navbar, with live suggestions. --}}
                <form method="GET" action="{{ route('search.index') }}" role="search" class="mb-3 position-relative"
                      data-search-form data-search-url="{{ route('search.suggest') }}">
                    <input type="hidden" name="tab" value="{{ $tab }}">

                    <label for="search-q" class="visually-hidden">{{ __('Search') }}</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-body border-end-0" aria-hidden="true">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="search" name="q" id="search-q" class="form-control border-start-0"
                               value="{{ $term }}"
                               placeholder="{{ __('Search people, #hashtags or post text') }}"
                               autocomplete="off" data-search-input>
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search me-1" aria-hidden="true"></i> {{ __('Search') }}
                        </button>
                    </div>

                    <div class="dropdown-menu show position-absolute top-100 start-0 end-0 shadow d-none"
                         data-search-suggestions></div>
                </form>

                @if ($term !== '')
                    <p class="small text-muted mb-2">
                        {{ __('Results for') }} <strong class="text-body">“{{ $term }}”</strong>
                    </p>
                @endif

                {{-- Three tabs: people, hashtags and posts (FR-14). --}}
                <ul class="nav nav-tabs mb-3">
                    @foreach ([
                        'users' => ['bi-people', __('People')],
                        'hashtags' => ['bi-hash', __('Hashtags')],
                        'posts' => ['bi-chat-square-text', __('Posts')],
                    ] as $value => [$icon, $label])
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === $value ? 'active' : '' }}"
                               href="{{ route('search.index', ['q' => $term, 'tab' => $value]) }}"
                               @if ($tab === $value) aria-current="page" @endif>
                                <i class="bi {{ $icon }} me-1" aria-hidden="true"></i> {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if (mb_strlen($term) < 2)
                    <div class="alert alert-info mb-0" role="status">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        {{ $term === ''
                            ? __('Type a name, a subject, a hashtag or some post text to start searching.')
                            : __('Type at least two characters to search.') }}
                    </div>
                @elseif ($tab === 'hashtags')
                    {{-- ----- Hashtag results ----- --}}
                    @if ($hashtags->isEmpty())
                        <x-empty-state icon="bi-hash"
                                       :title="__('No hashtags found')"
                                       :message="__('No hashtag matches \':term\'. Try a shorter word or browse the feed.', ['term' => $term])">
                            <a href="{{ route('feed.index') }}" class="btn btn-primary">{{ __('Back to the feed') }}</a>
                        </x-empty-state>
                    @else
                        <p class="text-muted small">
                            {{ trans_choice(':count hashtag found|:count hashtags found', $hashtags->total(), ['count' => $hashtags->total()]) }}
                        </p>

                        <div class="card edu-card">
                            <div class="list-group list-group-flush">
                                @foreach ($hashtags as $hashtag)
                                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2"
                                       href="{{ route('hashtags.show', $hashtag->name) }}">
                                        <span class="fw-semibold min-w-0 text-break">
                                            <i class="bi bi-hash text-edu" aria-hidden="true"></i>{!! $highlight($hashtag->name) !!}
                                        </span>
                                        <span class="edu-chip flex-shrink-0">
                                            {{ trans_choice(':count post|:count posts', $hashtag->posts_count, ['count' => $hashtag->posts_count]) }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-3">
                            <x-pagination-links :paginator="$hashtags" />
                        </div>
                    @endif
                @elseif ($tab === 'posts')
                    {{-- ----- Post results ----- --}}
                    @if ($posts->isEmpty())
                        <x-empty-state icon="bi-chat-square-text"
                                       :title="__('No posts found')"
                                       :message="__('No post matches \':term\'. Try another word or a hashtag.', ['term' => $term])">
                            <a href="{{ route('search.index', ['q' => $term, 'tab' => 'hashtags']) }}" class="btn btn-primary">
                                {{ __('Search hashtags instead') }}
                            </a>
                        </x-empty-state>
                    @else
                        <p class="text-muted small">
                            {{ trans_choice(':count post found|:count posts found', $posts->total(), ['count' => $posts->total()]) }}
                        </p>

                        <div class="d-flex flex-column gap-3">
                            @foreach ($posts as $post)
                                <x-post-card :post="$post" />
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <x-pagination-links :paginator="$posts" />
                        </div>
                    @endif
                @else
                    {{-- ----- People results ----- --}}
                    @if ($users->isEmpty())
                        <x-empty-state icon="bi-person-x"
                                       :title="__('No people found')"
                                       :message="__('No user matches \':term\'. Check the spelling or try a subject instead.', ['term' => $term])">
                            <a href="{{ route('search.index', ['q' => $term, 'tab' => 'hashtags']) }}" class="btn btn-primary">
                                {{ __('Search hashtags instead') }}
                            </a>
                        </x-empty-state>
                    @else
                        <p class="text-muted small">
                            {{ trans_choice(':count person found|:count people found', $users->total(), ['count' => $users->total()]) }}
                        </p>

                        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                            @foreach ($users as $user)
                                <div class="col">
                                    {{-- The shared user card, with a link to the public profile
                                         and a Follow button (FR-11, FR-17). --}}
                                    <x-user-card :user="$user" :name-html="$highlight($user->name)">
                                        <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-primary w-100">
                                            <i class="bi bi-person me-1" aria-hidden="true"></i> {{ __('View profile') }}
                                        </a>
                                        @include('users._follow-button', ['user' => $user, 'followingIds' => $followingIds])
                                    </x-user-card>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <x-pagination-links :paginator="$users" />
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
```

#### `resources/views/layouts/navigation.blade.php`

```blade
{{-- Sticky top navigation bar: page context, search, theme, notifications and
     the user menu. The sidebar itself lives in layouts/sidebar.blade.php. --}}
@php
    // Unread notifications drive the bell counter (FR-24); the newest eight fill
    // the dropdown. The polling script refreshes both every 30 seconds.
    // The layout passes the shared unread count so the mobile bar shows the
    // same number without a second COUNT query.
    $unreadNotifications = $unreadNotifications ?? (auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);
    $latestNotifications = auth()->check()
        ? auth()->user()->notifications()->latest()->take(8)->get()
        : collect();
@endphp

<nav class="navbar edu-navbar sticky-top">
    <div class="container-xxl px-3 px-lg-4 d-flex align-items-center gap-2">
        @auth
            @unless ($isLandingPage ?? false)
            {{-- Opens the sidebar drawer on phones and tablets only;
                 from lg upwards the sidebar is always visible. --}}
            <button class="btn edu-icon-btn d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#appSidebar"
                    aria-controls="appSidebar" aria-label="{{ __('Open the menu') }}">
                <i class="bi bi-list fs-5" aria-hidden="true"></i>
            </button>

            {{-- Desktop: collapse / expand the sidebar. The state is stored in
                 localStorage by resources/js/layout.js (FR-31). --}}
            <button class="btn edu-icon-btn d-none d-lg-inline-flex"
                    type="button" data-sidebar-toggle
                    aria-controls="appSidebar" aria-expanded="true"
                    aria-label="{{ __('Toggle the sidebar') }}"
                    title="{{ __('Toggle the sidebar') }}">
                <i class="bi bi-layout-sidebar-inset" data-sidebar-toggle-icon aria-hidden="true"></i>
            </button>
            @endunless
        @endauth

        <a class="navbar-brand d-flex align-items-center gap-2 mb-0"
           href="{{ auth()->check() ? route('dashboard') : url('/') }}">
            <span class="edu-sidebar-logo-mark d-lg-none" aria-hidden="true">
                <x-application-logo class="text-white" />
            </span>
            <span class="d-none d-sm-inline">{{ config('app.name', 'EduConnect') }}</span>
        </a>

        {{-- Page context: title + breadcrumbs. Hidden on narrow screens where
             the page itself already shows the heading. --}}
        @if ($pageTitle !== '' || isset($breadcrumbs))
            <div class="d-none d-xl-flex flex-column ms-3 ps-3 border-start" style="border-color: var(--edu-border) !important;">
                @if ($pageTitle !== '')
                    <span class="edu-navbar-title">{{ $pageTitle }}</span>
                @endif

                @isset($breadcrumbs)
                    <nav aria-label="{{ __('Breadcrumb') }}">
                        <ol class="breadcrumb mb-0">{{ $breadcrumbs }}</ol>
                    </nav>
                @endisset
            </div>
        @endif

        <div class="d-flex align-items-center ms-auto gap-1 gap-md-2">
            @auth
                {{-- One search box for people, hashtags and posts (FR-14).
                     On phones the box would not fit, so there is an icon instead.
                     resources/js/search.js fills the dropdown underneath it. --}}
                <form method="GET" action="{{ route('search.index') }}" role="search"
                      class="edu-search position-relative d-none d-md-block"
                      data-search-form data-search-url="{{ route('search.suggest') }}">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-0 pe-0" aria-hidden="true">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="search" name="q" class="form-control border-0"
                               value="{{ request()->routeIs('search.*') ? request('q') : '' }}"
                               placeholder="{{ __('Search people or #tags') }}"
                               aria-label="{{ __('Search') }}"
                               autocomplete="off" data-search-input>
                    </div>

                    {{-- Live suggestions, hidden until there is something to show.
                         resources/js/search.js toggles the "d-none" class. --}}
                    <div class="dropdown-menu show position-absolute top-100 start-0 end-0 shadow-sm d-none"
                         data-search-suggestions></div>
                </form>
            @endauth

            {{-- Light / dark mode. JS updates the icon and aria-label. --}}
            <button class="btn edu-icon-btn" type="button" data-theme-toggle
                    aria-label="{{ __('Switch to dark mode') }}" title="{{ __('Switch to dark mode') }}"
                    aria-pressed="false">
                <i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i>
            </button>

            <ul class="navbar-nav flex-row align-items-center gap-1">
            @auth
                {{-- Phone search: the icon expands a full-width panel under the
                     navbar instead of leaving the page (Phase 3D). --}}
                <li class="nav-item d-md-none">
                    <button class="btn edu-icon-btn" type="button"
                            data-mobile-search-toggle
                            aria-expanded="false" aria-controls="mobileSearchPanel"
                            aria-label="{{ __('Search') }}" title="{{ __('Search') }}">
                        <i class="bi bi-search" data-mobile-search-icon aria-hidden="true"></i>
                    </button>
                </li>

                {{-- Notification bell: unread counter + newest eight (FR-24) --}}
                <li class="nav-item dropdown">
                    <button class="btn edu-icon-btn position-relative" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="{{ __('Notifications') }}" data-notification-toggle>
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        {{-- The badge is refreshed by resources/js/notifications.js. --}}
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger {{ $unreadNotifications > 0 ? '' : 'd-none' }}"
                              data-notification-badge>
                            {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notification-menu" data-notification-list>
                        @include('notifications._dropdown', ['notifications' => $latestNotifications])
                    </ul>
                </li>

                {{-- User dropdown: profile, edit profile and logout. --}}
                <li class="nav-item dropdown">
                    <a class="edu-navbar-avatar dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false"
                       aria-label="{{ __('Account menu') }}">
                        <x-avatar :user="auth()->user()" size="sm" />
                        <span class="d-none d-md-inline small fw-semibold">{{ auth()->user()->name }}</span>
                        <i class="bi bi-chevron-down small text-muted d-none d-md-inline" aria-hidden="true"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text small text-muted">
                                {{ __('Signed in as') }}
                                <span class="text-capitalize fw-semibold">{{ auth()->user()->role }}</span>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('users.show', auth()->id()) }}">
                                <i class="bi bi-person me-1" aria-hidden="true"></i> {{ __('My profile') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i> {{ __('Edit profile') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('feed.index') }}">
                                <i class="bi bi-chat-square-text me-1" aria-hidden="true"></i> {{ __('Feed') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('notifications.index') }}">
                                <i class="bi bi-bell me-1" aria-hidden="true"></i> {{ __('Notifications') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> {{ __('Log out') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            @else
                {{-- Guests see login / register --}}
                <li class="nav-item">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('login') }}">{{ __('Log in') }}</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm btn-primary" href="{{ route('register') }}">{{ __('Register') }}</a>
                </li>
            @endauth
            </ul>
        </div>
    </div>

    @auth
        {{-- The expanded phone search. The panel is outside the flex row so it
             can use the full viewport width; layout.js moves focus into it. --}}
        <div class="edu-search-mobile d-md-none" id="mobileSearchPanel" hidden>
            <div class="container-xxl px-3 px-lg-4">
                <form method="GET" action="{{ route('search.index') }}" role="search"
                      class="edu-search-mobile-form position-relative"
                      data-search-form data-search-url="{{ route('search.suggest') }}">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0" aria-hidden="true">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="search" name="q" class="form-control border-start-0"
                               value="{{ request()->routeIs('search.*') ? request('q') : '' }}"
                               placeholder="{{ __('Search people or #tags') }}"
                               aria-label="{{ __('Search') }}"
                               autocomplete="off" data-search-input>
                        <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
                    </div>

                    {{-- Suggestions are filled by resources/js/search.js. --}}
                    <div class="dropdown-menu show position-absolute top-100 start-0 end-0 shadow d-none"
                         data-search-suggestions role="listbox"
                         aria-label="{{ __('Search suggestions') }}"></div>
                </form>
            </div>
        </div>
    @endauth
</nav>
```

#### `resources/views/layouts/app.blade.php`

```blade
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
```

#### `resources/views/components/user-card.blade.php`

```blade
{{-- A user (teacher or student) as a compact card.
     Usage: <x-user-card :user="$teacher"> <button>Follow</button> </x-user-card> --}}
@props(['user', 'nameHtml' => null])

<div class="card edu-card border-0 h-100">
    <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center gap-3 mb-2">
            @if ($user->photo)
                {{-- Escaped attribute value; the photo lives on the public disk --}}
                <img src="{{ $user->photoUrl() }}" alt="{{ $user->name }}" class="avatar" width="40" height="40" loading="lazy" decoding="async">
            @else
                {{-- Fallback: a circle with the first letter of the name --}}
                <span class="avatar bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fw-bold"
                      role="img" aria-label="{{ $user->name }}">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
                </span>
            @endif

            <div>
                <div class="fw-semibold">{!! $nameHtml ?? e($user->name) !!}</div>
                <div class="small text-muted d-flex align-items-center flex-wrap gap-1">
                    {{-- A real badge, so the role is recognisable at a glance (FR-14). --}}
                    <span class="badge rounded-pill text-bg-light border text-capitalize fw-normal">
                        <i class="bi bi-person-badge me-1" aria-hidden="true"></i>{{ $user->role }}
                    </span>
                    @if ($user->subject)
                        <span>{{ $user->subject }}</span>
                    @endif
                </div>
            </div>
        </div>

        @if ($user->bio)
            <p class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($user->bio, 100) }}</p>
        @endif

        @if (isset($user->followers_count))
            {{-- The follow AJAX refreshes this number in place (FR-11). --}}
            <div class="small text-muted mb-2">
                <i class="bi bi-people me-1"></i>
                <span data-followers-count data-user-id="{{ $user->id }}"
                      data-label-singular="{{ __('follower') }}"
                      data-label-plural="{{ __('followers') }}">{{ $user->followers_count }}</span>
                <span data-follower-label>{{ trans_choice('follower|followers', $user->followers_count) }}</span>
            </div>
        @endif

        {{-- Action buttons supplied by the page --}}
        <div class="mt-auto pt-2 d-flex flex-wrap gap-2">
            {{ $slot }}
        </div>
    </div>
</div>
```

### Tests

#### `tests/Feature/NotificationTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewFollower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6: notifications (FR-23, FR-24, FR-25).
 *
 * The test environment uses the "sync" queue driver, so a queued notification
 * is written to the notifications table straight away and can be asserted.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Follow (FR-23)
     * --------------------------------------------------------------- */

    public function test_following_a_teacher_notifies_the_teacher(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)->post(route('follows.store', $teacher))->assertRedirect();

        $notification = $teacher->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('new_follower', $notification->data['type']);
        // A brand new notification is unread.
        $this->assertNull($notification->read_at);
    }

    public function test_a_notification_stores_its_type_message_link_and_actor(): void
    {
        $student = User::factory()->student()->create(['name' => 'Actor Alice']);
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)->post(route('follows.store', $teacher));

        $data = $teacher->notifications()->first()->data;

        $this->assertSame('new_follower', $data['type']);
        $this->assertSame($student->id, $data['actor_id']);
        $this->assertSame('Actor Alice', $data['actor_name']);
        $this->assertSame($student->photoUrl(), $data['actor_photo']);
        $this->assertStringContainsString('Actor Alice', $data['message']);
        $this->assertSame(route('users.show', $student), $data['url']);
    }

    public function test_following_the_same_teacher_twice_notifies_only_once(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)->post(route('follows.store', $teacher));
        $this->actingAs($student)->post(route('follows.store', $teacher));

        $this->assertSame(1, $teacher->notifications()->count());
    }

    public function test_unfollowing_does_not_notify(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $student->follow($teacher);

        $this->actingAs($student)->delete(route('follows.destroy', $teacher));

        $this->assertSame(0, $teacher->notifications()->count());
    }

    /* ---------------------------------------------------------------
     | Likes (FR-23)
     * --------------------------------------------------------------- */

    public function test_liking_a_post_notifies_its_author(): void
    {
        $author = User::factory()->create();
        $liker = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($liker)->post(route('posts.like', $post));

        $this->assertSame('post_liked', $author->notifications()->first()?->data['type']);
    }

    public function test_liking_your_own_post_does_not_notify_you(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author)->post(route('posts.like', $post));

        $this->assertSame(0, $author->notifications()->count());
    }

    public function test_unliking_does_not_remove_or_add_a_notification(): void
    {
        $author = User::factory()->create();
        $liker = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($liker)->post(route('posts.like', $post));
        $this->actingAs($liker)->delete(route('posts.unlike', $post));

        // The "you were liked" message stays in the inbox.
        $this->assertSame(1, $author->notifications()->count());
    }

    /* ---------------------------------------------------------------
     | Comments (FR-23)
     * --------------------------------------------------------------- */

    public function test_commenting_on_a_post_notifies_its_author(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($commenter)->post(route('comments.store', $post), [
            'body' => 'Very useful, thank you!',
        ])->assertRedirect();

        $notification = $author->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('post_commented', $notification->data['type']);
    }

    public function test_commenting_on_your_own_post_does_not_notify_you(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author)->post(route('comments.store', $post), [
            'body' => 'Adding something to my own post.',
        ]);

        $this->assertSame(0, $author->notifications()->count());
    }

    /* ---------------------------------------------------------------
     | New class from a followed teacher (FR-23)
     * --------------------------------------------------------------- */

    public function test_creating_a_class_notifies_the_followers_of_the_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        $follower = User::factory()->student()->create();
        $follower->follow($teacher);

        $this->actingAs($teacher)->post(route('classes.store'), [
            'title' => 'Introduction to Algebra',
            'subject' => 'Mathematics',
            'date' => now()->addDays(3)->toDateString(),
            'time' => '10:00',
            'duration' => 60,
        ])->assertRedirect();

        $this->assertSame('new_class', $follower->notifications()->first()?->data['type']);
    }

    public function test_creating_a_class_does_not_notify_the_teacher_themselves(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->post(route('classes.store'), [
            'title' => 'No Self Notification',
            'date' => now()->addDays(3)->toDateString(),
            'time' => '12:00',
            'duration' => 60,
        ])->assertRedirect();

        $this->assertSame(0, $teacher->notifications()->count());
    }

    public function test_a_blocked_follower_is_not_notified_of_a_new_class(): void
    {
        $teacher = User::factory()->teacher()->create();
        $blocked = User::factory()->student()->blocked()->create();
        $blocked->follow($teacher);

        $this->actingAs($teacher)->post(route('classes.store'), [
            'title' => 'Blocked Followers Stay Quiet',
            'date' => now()->addDays(3)->toDateString(),
            'time' => '12:30',
            'duration' => 45,
        ])->assertRedirect();

        $this->assertSame(0, $blocked->notifications()->count());
    }

    public function test_a_class_does_not_notify_people_who_do_not_follow_the_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        $stranger = User::factory()->student()->create();

        $this->actingAs($teacher)->post(route('classes.store'), [
            'title' => 'Advanced Physics',
            'date' => now()->addDays(3)->toDateString(),
            'time' => '11:00',
            'duration' => 45,
        ]);

        $this->assertSame(0, $stranger->notifications()->count());
    }

    /* ---------------------------------------------------------------
     | Unread counter and polling endpoint (FR-24)
     * --------------------------------------------------------------- */

    public function test_the_poll_endpoint_returns_the_unread_counter_and_the_latest_entries(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create(['name' => 'Polling Penny'])));

        $this->actingAs($user)
            ->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('html', fn (string $html) => str_contains($html, 'Polling Penny'));
    }

    public function test_the_bell_and_the_notifications_page_show_the_unread_count(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create()));

        // The bell is part of every page of the layout.
        $this->actingAs($user)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee('data-notification-badge', false);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('started following you');
    }

    public function test_the_unread_badge_is_hidden_when_there_is_nothing_to_read(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('feed.index'))
            ->assertOk()
            // The badge exists for the polling script but starts hidden.
            ->assertSee('badge rounded-pill text-bg-danger d-none', false);
    }

    public function test_the_poll_endpoint_requires_a_logged_in_user(): void
    {
        $this->getJson(route('notifications.poll'))->assertUnauthorized();
    }

    /* ---------------------------------------------------------------
     | Mark as read (FR-25)
     * --------------------------------------------------------------- */

    public function test_a_user_can_mark_one_notification_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create()));
        $id = $user->notifications()->first()->id;

        $this->actingAs($user)->patch(route('notifications.read', $id))->assertRedirect();

        $this->assertNotNull($user->notifications()->first()->read_at);
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_a_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create()));
        $user->notify(new NewFollower(User::factory()->student()->create()));

        $this->assertSame(2, $user->unreadNotifications()->count());

        $this->actingAs($user)->patch(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_marking_one_notification_as_read_answers_json_for_the_ajax_button(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create()));
        $id = $user->notifications()->first()->id;

        $this->actingAs($user)
            ->patchJson(route('notifications.read', $id))
            ->assertOk()
            ->assertJsonPath('read', true)
            ->assertJsonPath('unread_count', 0);
    }

    /* ---------------------------------------------------------------
     | Filter and delete (FR-25)
     * --------------------------------------------------------------- */

    public function test_the_notifications_page_can_be_filtered_to_unread_only(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create(['name' => 'Unread Ursula'])));
        $user->notify(new NewFollower(User::factory()->student()->create(['name' => 'Read Rita'])));

        // Pick the entry by its actor instead of by its timestamp: two rows
        // created in the same second would make "newest" ambiguous.
        $user->notifications
            ->first(fn ($notification) => $notification->data['actor_name'] === 'Read Rita')
            ->markAsRead();

        // Without a filter both entries are listed...
        $all = $this->actingAs($user)->get(route('notifications.index'));
        $all->assertOk();
        $this->assertSame(2, $all->viewData('notifications')->total());

        // ...with the filter only the unread one. The bell dropdown always
        // shows the newest entries, so the assertion looks at the page data
        // instead of at the rendered HTML.
        $unread = $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => 'unread']));

        $unread->assertOk();
        $this->assertSame(1, $unread->viewData('notifications')->total());
        $this->assertSame('Unread Ursula', $unread->viewData('notifications')->first()->data['actor_name']);
    }

    public function test_marking_all_as_read_only_touches_my_notifications(): void
    {
        $mine = User::factory()->create();
        $other = User::factory()->create();

        $mine->notify(new NewFollower(User::factory()->student()->create()));
        $other->notify(new NewFollower(User::factory()->student()->create()));

        $this->actingAs($other)->patch(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $other->unreadNotifications()->count());
        $this->assertSame(1, $mine->unreadNotifications()->count());
    }

    public function test_a_user_can_delete_their_own_notification(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create()));
        $id = $user->notifications()->first()->id;

        $this->actingAs($user)->delete(route('notifications.destroy', $id))->assertRedirect();

        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_a_user_cannot_delete_somebody_elses_notification(): void
    {
        $owner = User::factory()->create();
        $owner->notify(new NewFollower(User::factory()->student()->create()));
        $id = $owner->notifications()->first()->id;

        $this->actingAs(User::factory()->create())
            ->delete(route('notifications.destroy', $id))
            ->assertNotFound();

        $this->assertSame(1, $owner->notifications()->count());
    }

    public function test_opening_a_notification_marks_it_read_and_redirects_to_its_page(): void
    {
        $user = User::factory()->create();
        $follower = User::factory()->student()->create();
        $user->notify(new NewFollower($follower));
        $id = $user->notifications()->first()->id;

        $this->actingAs($user)
            ->get(route('notifications.open', $id))
            ->assertRedirect(route('users.show', $follower));

        $this->assertNotNull($user->notifications()->first()->read_at);
    }

    public function test_a_user_cannot_read_somebody_elses_notification(): void
    {
        $owner = User::factory()->create();
        $owner->notify(new NewFollower(User::factory()->student()->create()));
        $id = $owner->notifications()->first()->id;

        $other = User::factory()->create();

        // The lookup goes through the relation of the current user, so a
        // foreign id is simply "not found".
        $this->actingAs($other)->patch(route('notifications.read', $id))->assertNotFound();
        $this->actingAs($other)->get(route('notifications.open', $id))->assertNotFound();

        $this->assertNull($owner->notifications()->first()->read_at);
    }

    /* ---------------------------------------------------------------
     | classes:notify-starting-soon (FR-23)
     * --------------------------------------------------------------- */

    public function test_the_starting_soon_command_notifies_the_teacher_and_the_students_once(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        // The class starts in ten minutes, inside the 15 minute window.
        $start = now()->addMinutes(10);

        $classRoom = ClassRoom::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => $start->toDateString(),
            'time' => $start->format('H:i:s'),
            'duration' => 60,
        ]);
        $classRoom->students()->attach($student->id);

        $this->artisan('classes:notify-starting-soon')->assertSuccessful();

        $this->assertSame(1, $teacher->notifications()->count());
        $this->assertSame(1, $student->notifications()->count());
        $this->assertSame('class_starting_soon', $student->notifications()->first()->data['type']);
        // The flag column is set, so the reminder can never be sent twice.
        $this->assertNotNull($classRoom->fresh()->starting_soon_notified_at);

        // Second run: nothing new.
        $this->artisan('classes:notify-starting-soon')->assertSuccessful();

        $this->assertSame(1, $teacher->notifications()->count());
        $this->assertSame(1, $student->notifications()->count());
    }

    public function test_the_starting_soon_command_skips_students_who_are_not_enrolled(): void
    {
        $teacher = User::factory()->teacher()->create();
        $enrolled = User::factory()->student()->create();
        $outsider = User::factory()->student()->create();

        $start = now()->addMinutes(10);

        $classRoom = ClassRoom::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => $start->toDateString(),
            'time' => $start->format('H:i:s'),
            'duration' => 60,
        ]);
        $classRoom->students()->attach($enrolled->id);

        $this->artisan('classes:notify-starting-soon')->assertSuccessful();

        $this->assertSame(1, $enrolled->notifications()->count());
        $this->assertSame(0, $outsider->notifications()->count());
    }

    public function test_the_starting_soon_command_ignores_classes_that_start_later(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $start = now()->addHours(3);

        $classRoom = ClassRoom::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => $start->toDateString(),
            'time' => $start->format('H:i:s'),
            'duration' => 60,
        ]);
        $classRoom->students()->attach($student->id);

        $this->artisan('classes:notify-starting-soon')->assertSuccessful();

        $this->assertSame(0, $teacher->notifications()->count());
        $this->assertSame(0, $student->notifications()->count());
        $this->assertNull($classRoom->fresh()->starting_soon_notified_at);
    }

    public function test_the_starting_soon_command_ignores_hidden_classes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $start = now()->addMinutes(10);

        $classRoom = ClassRoom::factory()->hidden()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => $start->toDateString(),
            'time' => $start->format('H:i:s'),
            'duration' => 60,
        ]);
        $classRoom->students()->attach($student->id);

        $this->artisan('classes:notify-starting-soon')->assertSuccessful();

        $this->assertSame(0, $student->notifications()->count());
        $this->assertSame(0, $teacher->notifications()->count());
    }
}
```

#### `tests/Feature/SearchTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Hashtag;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6: search (FR-14).
 *
 * One search box, three tabs - people (name, subject or bio), hashtags and
 * posts. Blocked accounts never appear, the LIKE wildcards inside the term are
 * escaped, and a term shorter than two characters is not searched at all.
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requires_a_logged_in_user(): void
    {
        $this->get(route('search.index'))->assertRedirect(route('login'));
    }

    public function test_the_search_box_is_in_the_navbar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee(route('search.index'), false);
    }

    /* ---------------------------------------------------------------
     | People tab
     * --------------------------------------------------------------- */

    public function test_users_are_found_by_name(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->create(['name' => 'Ada Lovelace']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'Lovelace', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('Ada Lovelace');
    }

    public function test_people_results_show_a_role_badge_and_a_follow_button(): void
    {
        $me = User::factory()->student()->create();
        User::factory()->teacher()->create(['name' => 'Badge Beatrice']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'Beatrice', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('Badge Beatrice')
            // The shared card renders a real role badge (FR-14).
            ->assertSee('bi-person-badge', false)
            ->assertSee('badge rounded-pill', false)
            // The viewer can follow the teacher straight from the results (FR-11).
            ->assertSee('data-follow-button', false);
    }

    public function test_users_are_found_by_subject(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->create([
            'name' => 'Grace Hopper',
            'subject' => 'Computer Science',
        ]);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'Computer', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('Grace Hopper');
    }

    public function test_users_are_found_by_bio(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->create([
            'name' => 'Grace Hopper',
            'bio' => 'I teach compiler design and COBOL.',
        ]);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'COBOL', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('Grace Hopper');
    }

    public function test_blocked_users_are_not_in_the_results(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->blocked()->create(['name' => 'Hidden Hubert']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'Hubert', 'tab' => 'users']))
            ->assertOk()
            ->assertDontSee('Hidden Hubert');
    }

    public function test_a_term_shorter_than_two_characters_is_not_searched(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->create(['name' => 'Ada Lovelace']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'a', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('Type at least two characters')
            ->assertDontSee('Ada Lovelace');
    }

    public function test_admins_are_not_in_the_results(): void
    {
        $me = User::factory()->create();
        User::factory()->admin()->create(['name' => 'Admin Alice']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'Alice', 'tab' => 'users']))
            ->assertOk()
            ->assertDontSee('Admin Alice');
    }

    public function test_an_empty_term_does_not_list_anybody(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->create(['name' => 'Ada Lovelace']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => '', 'tab' => 'users']))
            ->assertOk()
            ->assertDontSee('Ada Lovelace');
    }

    /* ---------------------------------------------------------------
     | Hashtag tab
     * --------------------------------------------------------------- */

    public function test_hashtags_are_found_by_name(): void
    {
        $me = User::factory()->create();
        // The model lowercases the name, exactly like the feed does.
        Hashtag::create(['name' => 'algebra']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'alge', 'tab' => 'hashtags']))
            ->assertOk()
            ->assertSee('algebra');
    }

    public function test_a_hashtag_that_does_not_exist_is_not_found(): void
    {
        $me = User::factory()->create();
        Hashtag::create(['name' => 'algebra']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'geometry', 'tab' => 'hashtags']))
            ->assertOk()
            ->assertSee('No hashtag matches');
    }

    public function test_a_hashtag_is_found_with_or_without_the_hash_symbol(): void
    {
        $me = User::factory()->create();
        Hashtag::create(['name' => 'algebra']);

        // "#algebra" and "algebra" must behave identically.
        foreach (['algebra', '#algebra'] as $term) {
            $this->actingAs($me)
                ->get(route('search.index', ['q' => $term, 'tab' => 'hashtags']))
                ->assertOk()
                ->assertSee('algebra');
        }
    }

    /* ---------------------------------------------------------------
     | Posts tab
     * --------------------------------------------------------------- */

    public function test_hashtag_results_count_only_visible_posts(): void
    {
        $me = User::factory()->student()->create();
        $hashtag = Hashtag::create(['name' => 'geometry']);

        $visible = Post::factory()->count(2)->create();
        $hidden = Post::factory()->hidden()->create();

        $hashtag->posts()->attach($visible->pluck('id')->push($hidden->id));

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'geometry', 'tab' => 'hashtags']))
            ->assertOk()
            ->assertSee('2 posts')
            ->assertDontSee('3 posts');
    }

    public function test_people_results_are_paginated(): void
    {
        $me = User::factory()->student()->create();

        foreach (range(1, 13) as $number) {
            User::factory()->teacher()->create([
                'name' => sprintf('Pagination Tutor %02d', $number),
            ]);
        }

        $first = $this->actingAs($me)
            ->get(route('search.index', ['q' => 'Pagination', 'tab' => 'users']))
            ->assertOk();

        $this->assertSame(13, $first->viewData('users')->total());
        $this->assertCount(12, $first->viewData('users')->items());

        $second = $this->actingAs($me)
            ->get(route('search.index', ['q' => 'Pagination', 'tab' => 'users', 'page' => 2]))
            ->assertOk();

        $this->assertCount(1, $second->viewData('users')->items());
    }

    public function test_the_people_tab_has_a_friendly_empty_state(): void
    {
        $me = User::factory()->student()->create();

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'NobodyHere', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('No people found');
    }

    public function test_posts_are_found_by_their_body_text(): void
    {
        $me = User::factory()->student()->create();
        $post = Post::factory()->create(['body' => 'Revision notes about photosynthesis']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'photosynthesis', 'tab' => 'posts']))
            ->assertOk()
            // The card itself is rendered, not just the term echoed back.
            ->assertSee('data-post-card="'.$post->id.'"', false);
    }

    public function test_hidden_posts_and_posts_by_blocked_users_stay_out_of_the_results(): void
    {
        $me = User::factory()->student()->create();

        $hidden = Post::factory()->hidden()->create(['body' => 'A hidden remark about photosynthesis']);

        $blockedAuthor = User::factory()->teacher()->blocked()->create();
        $blocked = Post::factory()->create([
            'user_id' => $blockedAuthor->id,
            'body' => 'A blocked remark about photosynthesis',
        ]);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'photosynthesis', 'tab' => 'posts']))
            ->assertOk()
            ->assertDontSee('data-post-card="'.$hidden->id.'"', false)
            ->assertDontSee('data-post-card="'.$blocked->id.'"', false);
    }

    /* ---------------------------------------------------------------
     | Live suggestions
     * --------------------------------------------------------------- */

    public function test_the_suggestion_endpoint_returns_people_and_hashtags(): void
    {
        $me = User::factory()->student()->create();
        User::factory()->teacher()->create(['name' => 'Ada Lovelace']);
        Hashtag::create(['name' => 'adaptive']);

        $this->actingAs($me)
            ->getJson(route('search.suggest', ['q' => 'ada']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Ada Lovelace'])
            ->assertJsonFragment(['label' => '#adaptive']);
    }

    public function test_the_suggestion_endpoint_hides_blocked_people(): void
    {
        $me = User::factory()->student()->create();
        User::factory()->teacher()->blocked()->create(['name' => 'Blocked Bruno']);

        $this->actingAs($me)
            ->getJson(route('search.suggest', ['q' => 'Bruno']))
            ->assertOk()
            ->assertJsonCount(0, 'suggestions');
    }

    public function test_the_suggestion_endpoint_shows_at_most_five_entries(): void
    {
        $me = User::factory()->student()->create();
        User::factory()->count(8)->teacher()->create(['name' => 'Tutor Number']);

        $this->actingAs($me)
            ->getJson(route('search.suggest', ['q' => 'Tutor']))
            ->assertOk()
            ->assertJsonCount(5, 'suggestions');
    }

    public function test_the_suggestion_endpoint_stays_empty_for_a_short_term(): void
    {
        $me = User::factory()->student()->create();
        User::factory()->teacher()->create(['name' => 'Ada Lovelace']);

        $this->actingAs($me)
            ->getJson(route('search.suggest', ['q' => 'a']))
            ->assertOk()
            ->assertJsonCount(0, 'suggestions');
    }

    /* ---------------------------------------------------------------
     | Safety
     * --------------------------------------------------------------- */

    public function test_like_wildcards_in_the_term_are_escaped(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->create(['name' => 'Wildcard Wanda']);

        // "%%" is long enough to be searched, but must be two literal percent
        // signs, not the "match everything" wildcard.
        $this->actingAs($me)
            ->get(route('search.index', ['q' => '%%', 'tab' => 'users']))
            ->assertOk()
            ->assertDontSee('Wildcard Wanda');
    }

    public function test_the_term_is_escaped_in_the_output(): void
    {
        $me = User::factory()->create();

        // A script tag in the query string is printed escaped, never executed.
        $this->actingAs($me)
            ->get(route('search.index', ['q' => '<script>alert(1)</script>', 'tab' => 'users']))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
```

### Environment and queue configuration

`.env` / `.env.example`

```dotenv
# Notifications are queued notifications.
# Local/demo default: "sync" writes them immediately, no worker needed.
# Production: "database" plus a running queue worker (docs/DEPLOYMENT.md).
QUEUE_CONNECTION=sync
```

```bash
# Optional local worker after switching to the database driver
php artisan queue:work --sleep=3 --tries=3

# Production worker (Supervisor example in docs/DEPLOYMENT.md)
php artisan queue:work --sleep=3 --tries=3 --max-time=3600

# The reminder command itself (the scheduler runs it every minute)
php artisan classes:notify-starting-soon
```

---

## 6. Test checklist

### FR-14 — Search

- [x] Search requires a logged-in user.
- [x] The navbar contains the search box (desktop) and the phone panel submits to `/search`.
- [x] People match by name — `SearchTest::test_users_are_found_by_name`.
- [x] People match by subject — `test_users_are_found_by_subject`.
- [x] People match by bio — `test_users_are_found_by_bio`.
- [x] People results show a role badge and a Follow button — `test_people_results_show_a_role_badge_and_a_follow_button`.
- [x] Blocked users are excluded from results — `test_blocked_users_are_not_in_the_results`.
- [x] Admin accounts are not listed — `test_admins_are_not_in_the_results`.
- [x] A one-character term is not searched — `test_a_term_shorter_than_two_characters_is_not_searched`.
- [x] An empty term lists nobody — `test_an_empty_term_does_not_list_anybody`.
- [x] People results paginate 12 per page — `test_people_results_are_paginated`.
- [x] People have an empty state — `test_the_people_tab_has_a_friendly_empty_state`.
- [x] Hashtags match by name — `test_hashtags_are_found_by_name`.
- [x] Hashtags match with or without `#` — `test_a_hashtag_is_found_with_or_without_the_hash_symbol`.
- [x] Hashtag post counts ignore hidden posts — `test_hashtag_results_count_only_visible_posts`.
- [x] Missing hashtags show an empty state — `test_a_hashtag_that_does_not_exist_is_not_found`.
- [x] Posts match body text and render feed cards — `test_posts_are_found_by_their_body_text`.
- [x] Hidden posts and posts by blocked authors are excluded — `test_hidden_posts_and_posts_by_blocked_users_stay_out_of_the_results`.
- [x] Suggestions return people and hashtags — `test_the_suggestion_endpoint_returns_people_and_hashtags`.
- [x] Suggestions hide blocked people — `test_the_suggestion_endpoint_hides_blocked_people`.
- [x] Suggestions are capped at five — `test_the_suggestion_endpoint_shows_at_most_five_entries`.
- [x] Short terms produce no suggestions — `test_the_suggestion_endpoint_stays_empty_for_a_short_term`.
- [x] `%`, `_` and `!` are escaped as literals — `test_like_wildcards_in_the_term_are_escaped`.
- [x] Query output is escaped — `test_the_term_is_escaped_in_the_output` plus `PhaseEightTest::test_search_input_cannot_break_the_query`.

### FR-23 — Notification creation

- [x] New follower notifies the followed member — `test_following_a_teacher_notifies_the_teacher`.
- [x] Every notification stores type, message, URL and actor name/avatar — `test_a_notification_stores_its_type_message_link_and_actor`.
- [x] Following twice queues only one notification — `test_following_the_same_teacher_twice_notifies_only_once`.
- [x] Unfollowing sends nothing — `test_unfollowing_does_not_notify`.
- [x] Liking notifies the author — `test_liking_a_post_notifies_its_author`.
- [x] Liking your own post sends nothing — `test_liking_your_own_post_does_not_notify_you`.
- [x] Unliking does not add or remove notifications — `test_unliking_does_not_remove_or_add_a_notification`.
- [x] Commenting notifies the author — `test_commenting_on_a_post_notifies_its_author`.
- [x] Commenting on your own post sends nothing — `test_commenting_on_your_own_post_does_not_notify_you`.
- [x] Creating a class notifies every follower — `test_creating_a_class_notifies_the_followers_of_the_teacher`.
- [x] Creating a class does not notify the teacher — `test_creating_a_class_does_not_notify_the_teacher_themselves`.
- [x] Blocked followers are skipped — `test_a_blocked_follower_is_not_notified_of_a_new_class`.
- [x] Non-followers are skipped — `test_a_class_does_not_notify_people_who_do_not_follow_the_teacher`.
- [x] Starting-soon notifies enrolled students and the teacher — `test_the_starting_soon_command_notifies_the_teacher_and_the_students_once`.
- [x] Starting-soon skips non-enrolled students — `test_the_starting_soon_command_skips_students_who_are_not_enrolled`.
- [x] Starting-soon ignores classes outside the 15-minute window — `test_the_starting_soon_command_ignores_classes_that_start_later`.
- [x] Starting-soon ignores hidden classes — `test_the_starting_soon_command_ignores_hidden_classes`.
- [x] A second command run creates no duplicates (flag set) — same test asserts unchanged counts.

### FR-24 — Bell and polling

- [x] Poll returns the unread count and newest entries as HTML — `test_the_poll_endpoint_returns_the_unread_counter_and_the_latest_entries`.
- [x] The bell and page show the unread count — `test_the_bell_and_the_notifications_page_show_the_unread_count`.
- [x] The badge is hidden at zero — `test_the_unread_badge_is_hidden_when_there_is_nothing_to_read`.
- [x] Poll requires authentication — `test_the_poll_endpoint_requires_a_logged_in_user`.
- [x] Poll is rate limited (`notifications-poll`, 60/min) — `PhaseEightTest::test_named_rate_limiters_are_registered`.
- [x] The dropdown shows the latest eight with relative time and unread styling (Blade + `notifications.js`).

### FR-25 — Page and read handling

- [x] A user can mark one notification as read — `test_a_user_can_mark_one_notification_as_read`.
- [x] A user can mark all as read — `test_a_user_can_mark_all_notifications_as_read`.
- [x] The single-read button answers JSON for AJAX — `test_marking_one_notification_as_read_answers_json_for_the_ajax_button`.
- [x] The all/unread filter works — `test_the_notifications_page_can_be_filtered_to_unread_only`.
- [x] Mark-all only touches the current user — `test_marking_all_as_read_only_touches_my_notifications`.
- [x] A user can delete their own notification — `test_a_user_can_delete_their_own_notification`.
- [x] A user cannot delete someone else’s notification — `test_a_user_cannot_delete_somebody_elses_notification`.
- [x] Opening marks read and redirects to the target — `test_opening_a_notification_marks_it_read_and_redirects_to_its_page`.
- [x] A user cannot read someone else’s notification — `test_a_user_cannot_read_somebody_elses_notification`.

---

## 7. Manual UI checklist

- [ ] Follow a teacher from the profile, search page and feed → their bell badge increments within 30 s.
- [ ] Open the bell → newest eight entries, unread rows highlighted, time-ago labels.
- [ ] Click an entry → target page opens and the row is read; the badge drops.
- [ ] Leave the tab open for 30 s → no reload, badge/list refresh.
- [ ] `/notifications` → All/Unread chips, pagination, Today/Earlier groups.
- [ ] Mark one as read with JavaScript → row loses dot/background/label, badge drops, no reload.
- [ ] Mark all as read → badge disappears, list is read.
- [ ] Delete a notification → confirmation, row removed, badge recalculated.
- [ ] Another account’s notification id → 404, no data leak.
- [ ] Create a class as a teacher with followers → each follower gets “scheduled a new class”.
- [ ] Enrol a student in a class starting in 10 minutes → run `php artisan classes:notify-starting-soon` → one reminder; run again → none.
- [ ] Search “ada” in the navbar → dropdown shows at most five people/hashtags; typing fast does not flood the network.
- [ ] `/search?q=algebra` → People/Hashtags/Posts tabs, hashtag found with and without `#`, post count shown.
- [ ] Submit a blocked user’s name → no result and no suggestion.
- [ ] Submit `%` / `%%` / `_` → no wildcard “match everything” behaviour.
- [ ] Submit a name with `<script>` → rendered as text, never executed.

---

## 8. Commands run for this deliverable

```bash
php artisan test
php artisan test tests/Feature/NotificationTest.php tests/Feature/SearchTest.php
vendor/bin/pint --test app config database routes tests
node --check resources/js/notifications.js
node --check resources/js/search.js
php artisan view:cache
php artisan view:clear
npm run build
```

Verified result:

```text
Full suite:      292 passed (1549 assertions)
Phase 6 suites:  54 passed (142 assertions)
Pint:            passed
Vite:            140 modules transformed
Blade cache:     OK
```

---

## 9. Done when

Phase 6 is done when all of the following are true:

1. The five notification classes exist, use the database channel, implement
   `ShouldQueue`, and store type, message, URL, actor name and actor avatar.
2. Following, liking, commenting and creating a class create the right
   notification, only for the other person, and idempotent actions never create
   duplicates.
3. `classes:notify-starting-soon` runs every minute, reminds enrolled students
   (and the teacher) 15 minutes before the start, uses the
   `starting_soon_notified_at` flag, and a second run sends nothing.
4. The navbar bell shows the unread count, hides the badge at zero, lists the
   latest eight entries, highlights unread rows, shows time ago, and marks an
   entry read when it is opened.
5. `fetch()` polling updates the badge and dropdown every 30 seconds without a
   page reload, pauses in a hidden tab, and resumes when the tab is visible.
6. `/notifications` paginates, filters all/unread, marks one entry read through
   AJAX, marks all read, deletes an entry, and gives each action a flash or JSON
   response.
7. A user can only read, mark or delete their own notifications; a foreign id
   returns 404 and mark-all leaves other inboxes untouched.
8. `/search?q=` offers People, Hashtags and Posts tabs; people match name,
   subject and bio with a role badge and follow button; hashtags match with or
   without `#` and show visible post counts; posts match body text.
9. Blocked accounts are absent from people results, suggestions and posts;
   admins are not discoverable; `LIKE` wildcards are escaped; output is escaped.
10. Search requires at least two characters, paginates with the term/tab
    preserved, and shows an empty state for every tab.
11. Live suggestions are debounced, abort stale requests, and show at most five
    people or hashtags.
12. `php artisan test` is green (**292 passed / 1549 assertions**), Pint passes,
    the JS syntax checks pass, `view:cache` succeeds and `npm run build`
    completes with 140 modules.
