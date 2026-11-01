# Phase 4 — Social Feed: Posts, Likes, Comments, Follows and Hashtags

**Project:** EduConnect — Laravel 12.70.2 / Bootstrap 5.3.8 / Vite 6.4.5  
**Phase:** 4 — social feed (FR-7 … FR-13)  
**Status:** Implemented, built and verified against the full test suite  
**Date:** 31 October 2026

---

## 1. Objective

Deliver a complete, safe social feed on top of the Phase 1–3D platform:

- posts with a 280-character limit, an optional image and owner-only editing
- a home feed made of the viewer's own posts plus the people they follow
- likes and comments without page reloads
- follow / unfollow with live counters on every surface
- Unicode hashtags, safe linkification and a paginated hashtag page
- a final post card used by the feed, search, hashtag pages and public profiles
- feature tests for creation, policies, likes, comments, follows, hashtags and feed content

No earlier feature was removed. Every change is additive or narrows a rule to
the exact behaviour requested for Phase 4.

---

## 2. Feature map (FR-7 … FR-13)

| FR | Feature | Implementation |
| --- | --- | --- |
| **FR-7** | Create a post, 280 characters, live counter, optional image | `PostController@create/store`, `StorePostRequest`, `posts/_form`, `feed.js` |
| **FR-8** | Edit / delete own post, admin delete, confirm modal, image cleanup | `PostController@edit/update/destroy`, `PostPolicy`, `post-card`, `confirm-delete-modal` |
| **FR-9** | Like / unlike with fetch + JSON, no duplicates | `LikeController`, `Post::likes()`, unique `(post_id, user_id)` index, `feed.js` |
| **FR-10** | Comments: create, own/admin delete, pagination, hidden excluded | `CommentController`, `StoreCommentRequest`, `CommentPolicy`, `comments/_comment` |
| **FR-11** | Follow / unfollow AJAX, no self-follow, live counts, follower lists | `FollowController`, `User` follow helpers, `users/_follow-button`, `feed.js` |
| **FR-12** | Home feed: own + followed, visible only, 10/page, Load more, widgets | `PostController@index`, `Post::scopeWithFeedData`, `feed/index`, `feed/_posts` |
| **FR-13** | Hashtags: Unicode regex, lowercase, pivot sync, safe links, hashtag page | `HashtagService`, `HashtagController`, `hashtags/show`, `hashtags` tables |

---

## 3. Behaviour details

### Posts (FR-7, FR-8)

- `StorePostRequest` and `UpdatePostRequest` require a non-empty body of at
  most **280 characters**.
- The image is optional, decoded and re-encoded by `ImageUploadService`, then
  stored under `storage/app/public/posts` (the configured `posts` directory).
  Accepted inputs are JPG, PNG and WebP, at most **2 MB**; large images are
  capped at 1600 px and re-encoded as WebP (JPEG when GD has no WebP support).
- Replacing an image deletes the previous file after the new one is stored, so
  a failed conversion never loses the current image.
- Deleting a post removes its image file as well as its comments, likes and
  hashtag links (database cascades).
- `PostPolicy::update` is owner-only. `PostPolicy::delete` allows the owner or
  an administrator. Editors are available from the post card dropdown; deletion
  goes through the reusable Bootstrap confirmation modal.

### Feed (FR-12)

- `PostController@feedQuery` selects visible posts whose author is the viewer or
  somebody the viewer follows, newest first.
- `Post::scopeWithFeedData` eager loads the author and hashtags, counts likes
  and visible comments and adds `liked_by_me`, so ten cards do not produce ten
  queries per relation.
- Ten posts per page. The **Load more** button fetches the next page as JSON
  and appends the rendered `feed._posts` HTML; without JavaScript it still
  works as pagination.
- The right sidebar contains **Who to follow** (people not followed yet, most
  followed first) and the **top five trending hashtags from the last 7 days**.
- An empty feed shows a friendly empty state with a **Find people to follow**
  link to the people search tab.

### Likes (FR-9)

- `POST /posts/{post}/like` likes, `DELETE /posts/{post}/like` unlikes. Both
  answer JSON with `liked` and the fresh `count`, or redirect without JS.
- `firstOrCreate` plus the unique index on `(post_id, user_id)` makes a
  duplicate like impossible; a double click cannot create a second row.
- `feed.js` updates the heart, the button colour, `aria-pressed` and the count
  instantly, disables the button while the request runs and rolls the UI back
  on failure. CSRF is sent in `X-CSRF-TOKEN`.
- Likes on hidden posts are refused by `PostPolicy::view`.

### Comments (FR-10)

- A comment can be written on the post detail page and from the expandable
  comment section on every feed card.
- `StoreCommentRequest`: required, at most **280 characters**, and only for a
  post the viewer may see.
- `CommentPolicy::delete`: the comment author or an administrator. The author
  of the post does not own other people's comments and cannot delete them.
- New comments return rendered HTML plus the new count. Deletion removes the
  row and updates every counter in the same scope.
- Comments are paginated (15 per page) on the detail page. Hidden comments are
  excluded from both the list and the count.

### Follow / unfollow (FR-11)

- AJAX forms are used on public profiles, the feed's suggested users, search
  results, follower/following lists and the student dashboard.
- Self-follow is **403**; admin accounts are not followable and answer **404**.
  Following twice is idempotent and does not queue a second notification.
- The JSON answer contains `following`, `followers`, `user_id`, `viewer_id` and
  `following_count`. `feed.js` refreshes the followed profile's follower number
  and its singular/plural label, plus the viewer's "Following" total in the feed
  sidebar.
- `/users/{user}/followers` and `/users/{user}/following` list people with a
  follow button and paginate at 20 per page.

### Hashtags (FR-13)

- `HashtagService::PATTERN` accepts Unicode letters, numbers and underscores:
  `/#([\p{L}\p{N}_]{1,50})/u`. `#Éducation`, `#数学` and `#Café_2026` all work.
- Names are stored lower case. `sync()` creates missing hashtags and calls
  `sync()` on the pivot, so tags removed during an edit are detached.
- `linkify()` escapes the whole body first and only then inserts `<a>` tags, so
  a body containing `<script>` is rendered as text. Links point to
  `/hashtags/{name}`.
- The hashtag page lists visible posts carrying the tag, newest first, paginated
  at ten per page, with the tag name and the post count.

### Post card and public profile

- The final card shows the avatar, name, role badge, relative time, body with
  linked hashtags, lazy-loaded image with intrinsic dimensions, like button,
  comment button / expandable comments, report action and the owner dropdown
  (edit, delete).
- Public profiles list the user's visible posts with pagination and expose the
  posts / followers / following counters and the follow button.

---

## 4. Changed in this Phase 4 pass

| # | File | Change |
| --- | --- | --- |
| 1 | `app/Policies/CommentPolicy.php` | Comment deletion is now own-comment-or-admin only (FR-10) |
| 2 | `app/Http/Controllers/LikeController.php` | Like/unlike now respects `PostPolicy::view` for hidden posts |
| 3 | `app/Http/Requests/UpdatePostRequest.php` | Corrected the ownership comment (admins moderate, not edit) |
| 4 | `resources/views/components/user-card.blade.php` | Follower count is a live AJAX target |
| 5 | `resources/views/feed/index.blade.php` | Suggested-user follower count is a live AJAX target |
| 6 | `resources/js/feed.js` | Live follower count + singular/plural label update |
| 7 | `tests/Feature/FeedTest.php` | 9 new / updated tests (see section 7) |
| 8 | `docs/REQUIREMENTS-STATUS.md` | Comment rule updated |
| 9 | `docs/TEST-MATRIX.md` | FR-7 … FR-13 test references updated |
| 10 | `docs/PHASE-4-DELIVERABLE.md` | This document |

---

## 5. Phase 4 file inventory

**Controllers**

- `app/Http/Controllers/PostController.php`
- `app/Http/Controllers/LikeController.php`
- `app/Http/Controllers/CommentController.php`
- `app/Http/Controllers/FollowController.php`
- `app/Http/Controllers/HashtagController.php`
- `app/Http/Controllers/UserProfileController.php`

**Requests and policies**

- `app/Http/Requests/StorePostRequest.php`
- `app/Http/Requests/UpdatePostRequest.php`
- `app/Http/Requests/StoreCommentRequest.php`
- `app/Policies/PostPolicy.php`
- `app/Policies/CommentPolicy.php`

**Services and models**

- `app/Services/HashtagService.php`
- `app/Services/ImageUploadService.php`
- `app/Models/Post.php`
- `app/Models/Comment.php`
- `app/Models/Hashtag.php`

**Routes and configuration**

- `routes/web.php`
- `config/educonnect.php`

**Views**

- `resources/views/feed/index.blade.php`
- `resources/views/feed/_posts.blade.php`
- `resources/views/components/post-card.blade.php`
- `resources/views/components/confirm-delete-modal.blade.php`
- `resources/views/components/user-card.blade.php`
- `resources/views/comments/_comment.blade.php`
- `resources/views/posts/show.blade.php`
- `resources/views/posts/create.blade.php`
- `resources/views/posts/edit.blade.php`
- `resources/views/posts/_form.blade.php`
- `resources/views/users/show.blade.php`
- `resources/views/users/people.blade.php`
- `resources/views/users/_follow-button.blade.php`
- `resources/views/hashtags/show.blade.php`
- `resources/views/search/index.blade.php`

**JavaScript**

- `resources/js/feed.js`

**Database**

- `database/migrations/2026_10_29_100001_create_posts_table.php`
- `database/migrations/2026_10_29_100002_create_comments_table.php`
- `database/migrations/2026_10_29_100003_create_likes_table.php`
- `database/migrations/2026_10_29_100004_create_follows_table.php`
- `database/migrations/2026_10_29_100005_create_hashtags_table.php`
- `database/migrations/2026_10_29_100006_create_hashtag_post_table.php`

**Tests**

- `tests/Feature/FeedTest.php`

---

## 6. Full source

Every file below is the complete current content, copied verbatim from the
project. Paths are relative to the project root.


---
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

```

---
### `app/Http/Controllers/LikeController.php`

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

---
### `app/Http/Controllers/CommentController.php`

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

---
### `app/Http/Controllers/FollowController.php`

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

---
### `app/Http/Controllers/HashtagController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The page behind a hashtag link: /hashtag/{name} (FR-13).
 */
class HashtagController extends Controller
{
    /**
     * All visible posts that carry this tag, newest first and paginated.
     */
    public function show(Request $request, Hashtag $hashtag): View
    {
        $posts = Post::query()
            ->visible()
            // Only posts that really carry this tag.
            ->whereHas('hashtags', static fn ($query) => $query->whereKey($hashtag->id))
            ->withFeedData($request->user())
            ->latest()
            ->paginate(PostController::PER_PAGE)
            ->withQueryString();

        return view('hashtags.show', [
            'hashtag' => $hashtag,
            'posts' => $posts,
        ]);
    }
}

```

---
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

---
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

---
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

---
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

---
### `app/Policies/PostPolicy.php`

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

---
### `app/Policies/CommentPolicy.php`

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

---
### `app/Services/HashtagService.php`

```php
<?php

namespace App\Services;

use App\Models\Hashtag;
use App\Models\Post;
use Illuminate\Support\HtmlString;

/**
 * Everything that has to do with hashtags (FR-15).
 *
 * The service has two jobs:
 *   1. pull the #tags out of a post body and sync the hashtag_post pivot,
 *   2. turn those tags into links inside an already escaped body.
 */
class HashtagService
{
    /**
     * Matches "#word" where word is 1-50 Unicode letters, digits or underscores.
     * The /u flag makes the pattern operate on UTF-8 characters, so tags such
     * as #Éducation and #数学 are extracted as single hashtags (FR-13).
     */
    public const PATTERN = '/#([\p{L}\p{N}_]{1,50})/u';

    /**
     * Every hashtag found in the text, lower case and without duplicates.
     *
     * @return list<string>
     */
    public function extract(?string $body): array
    {
        if ($body === null || $body === '') {
            return [];
        }

        preg_match_all(self::PATTERN, $body, $matches);

        // Storing them lower case means #Maths and #maths are the same tag (FR-15).
        return array_values(array_unique(array_map(
            static fn (string $name): string => mb_strtolower($name),
            $matches[1] ?? []
        )));
    }

    /**
     * Replace the hashtags of a post with the tags found in its body.
     * sync() also detaches tags the author removed while editing.
     *
     * @return list<string> the hashtag names that are now attached
     */
    public function sync(Post $post, ?string $body): array
    {
        $names = $this->extract($body);

        $ids = [];

        foreach ($names as $name) {
            // firstOrCreate keeps one row per tag, the mutator lowercases the name.
            $ids[] = Hashtag::firstOrCreate(['name' => $name])->id;
        }

        $post->hashtags()->sync($ids);

        return $names;
    }

    /**
     * Turn the hashtags of a body into links to /hashtag/{name} (FR-15).
     *
     * Safety: the text is escaped FIRST with e() and only then are the <a>
     * tags inserted. A body like "<script>alert(1)</script>" therefore reaches
     * the browser as text, never as markup, so there is no XSS hole.
     */
    public function linkify(?string $body): HtmlString
    {
        $safe = e($body ?? '');

        $html = preg_replace_callback(self::PATTERN, static function (array $match): string {
            $name = mb_strtolower($match[1]);

            // Built by the router, then escaped again because it lands in an attribute.
            $url = e(route('hashtags.show', ['hashtag' => $name]));

            return '<a href="'.$url.'" class="text-edu fw-semibold text-decoration-none">#'.$name.'</a>';
        }, $safe);

        // preg_replace_callback only returns null on a broken pattern.
        return new HtmlString($html ?? $safe);
    }
}

```

---
### `app/Services/ImageUploadService.php`

```php
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

```

---
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

---
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

---
### `app/Models/Hashtag.php`

```php
<?php

namespace App\Models;

use Database\Factories\HashtagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hashtag extends Model
{
    /** @use HasFactory<HashtagFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Hashtags are always stored in lower case.
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = mb_strtolower(ltrim(trim($value), '#'));
    }

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'hashtag_post');
    }
}

```

---
### `routes/web.php`

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

---
### `config/educonnect.php`

```php
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

```

---
### `resources/views/feed/index.blade.php`

```php
<x-app-layout>
    <x-slot name="title">{{ __('Feed') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Feed') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-house-door me-1 text-edu"></i> {{ __('Feed') }}
                </h1>
                <div class="small text-muted">{{ __('Posts from you and the people you follow.') }}</div>
            </div>
            <a href="{{ route('posts.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square me-1"></i> {{ __('New post') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row g-4 justify-content-center edu-feed-row">
            {{-- Main column: composer + feed --}}
            <div class="col-12 col-md-8">
                {{-- Compose box (FR-7) --}}
                <section class="card edu-card mb-4" aria-labelledby="compose-title">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <x-avatar :user="auth()->user()" size="md" class="flex-shrink-0" />
                            <div>
                                <h2 class="h6 fw-bold mb-0" id="compose-title">{{ __('Share something') }}</h2>
                                <div class="small text-muted">{{ __('Your next post could help somebody learn.') }}</div>
                            </div>
                        </div>

                        @include('posts._form', ['post' => null])
                    </div>
                </section>

                {{-- Posts of the people I follow + my own (FR-12) --}}
                <div data-feed-list>
                    @include('feed._posts', [
                        'posts' => $posts,
                        'commentsByPost' => $commentsByPost,
                        'emptyMessage' => __('Follow a few teachers or classmates to fill this page with their posts.'),
                        'emptyActionUrl' => route('search.index', ['tab' => 'users']),
                        'emptyActionLabel' => __('Find people to follow'),
                    ])
                </div>

                {{-- Progressive enhancement: without JavaScript the button is a
                     normal link to ?page=2, with it the cards are appended (FR-12). --}}
                <div class="text-center my-4">
                    <button type="button" class="btn btn-outline-primary"
                            data-load-more
                            data-url="{{ $posts->nextPageUrl() }}"
                            @if (! $posts->hasMorePages()) hidden @endif>
                        <i class="bi bi-arrow-down-circle me-1"></i> {{ __('Load more') }}
                    </button>
                </div>
            </div>

            {{-- Right sidebar widgets --}}
            <aside class="col-12 col-md-4">
                @unless (auth()->user()->isAdmin())
                    <section class="card edu-card mb-3" aria-labelledby="who-to-follow-title">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h2 class="h6 fw-bold mb-0" id="who-to-follow-title">
                                    <i class="bi bi-person-plus me-1 text-edu" aria-hidden="true"></i>
                                    {{ __('Who to follow') }}
                                </h2>
                                <div class="small text-muted">
                                    {{ __('Following') }}
                                    <span data-viewer-following-count>{{ $followingCount }}</span>
                                    {{ __('people') }}
                                </div>
                            </div>
                            <a href="{{ route('search.index', ['tab' => 'users']) }}"
                               class="small fw-semibold text-decoration-none">{{ __('See all') }}</a>
                        </div>

                        <div class="card-body py-2">
                            @forelse ($suggestedUsers as $person)
                                <div class="edu-widget-user">
                                    <x-avatar :user="$person" size="sm" class="flex-shrink-0" />

                                    <div class="flex-grow-1 min-w-0">
                                        <a href="{{ route('users.show', $person) }}"
                                           class="edu-list-title text-truncate d-block">{{ $person->name }}</a>
                                        <div class="edu-list-meta text-capitalize">
                                            {{ $person->role }}
                                            @if ($person->subject)
                                                <span class="mx-1" aria-hidden="true">·</span>{{ $person->subject }}
                                            @endif
                                        </div>
                                        <div class="edu-list-meta">
                                            <i class="bi bi-people me-1" aria-hidden="true"></i>
                                            <span data-followers-count data-user-id="{{ $person->id }}"
                                                  data-label-singular="{{ __('follower') }}"
                                                  data-label-plural="{{ __('followers') }}">{{ $person->followers_count }}</span>
                                            <span data-follower-label>{{ trans_choice('follower|followers', $person->followers_count) }}</span>
                                        </div>
                                    </div>

                                    <div class="flex-shrink-0">
                                        @include('users._follow-button', ['user' => $person, 'followingIds' => $followingIds])
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <div class="edu-empty-illustration mx-auto mb-2"
                                         style="width: 52px; height: 52px; font-size: 1.3rem; border-radius: 16px;"
                                         aria-hidden="true">
                                        <i class="bi bi-check2-circle"></i>
                                    </div>
                                    <div class="fw-semibold small">{{ __('You follow everybody') }}</div>
                                    <div class="text-muted small">{{ __('Check the search page for new people.') }}</div>
                                </div>
                            @endforelse
                        </div>

                        <div class="card-footer bg-transparent">
                            <a href="{{ route('search.index', ['tab' => 'users']) }}"
                               class="small fw-semibold text-decoration-none">
                                {{ __('Find more people') }} <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                            </a>
                        </div>
                    </section>
                @endunless

                <section class="card edu-card" aria-labelledby="trending-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="trending-title">
                            <i class="bi bi-fire me-1 text-edu" aria-hidden="true"></i>
                            {{ __('Trending hashtags') }}
                        </h2>
                        <div class="small text-muted">{{ __('The five busiest tags from the last 7 days.') }}</div>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($trendingHashtags as $tag)
                            <a href="{{ route('hashtags.show', $tag->name) }}" class="edu-trending-row">
                                <span class="edu-trending-rank" aria-hidden="true">#</span>
                                <span class="flex-grow-1 min-w-0">
                                    <span class="edu-list-title d-block text-truncate">{{ $tag->name }}</span>
                                    <span class="edu-list-meta">
                                        {{ trans_choice(':count post this week|:count posts this week', $tag->recent_posts_count, ['count' => $tag->recent_posts_count]) }}
                                    </span>
                                </span>
                                <i class="bi bi-arrow-up-right text-muted small" aria-hidden="true"></i>
                            </a>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto mb-2"
                                     style="width: 52px; height: 52px; font-size: 1.3rem; border-radius: 16px;"
                                     aria-hidden="true">
                                    <i class="bi bi-hash"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('No trends yet') }}</div>
                                <div class="text-muted small">{{ __('Post with a #hashtag to start one.') }}</div>
                            </div>
                        @endforelse
                    </div>

                    <div class="card-footer bg-transparent">
                        <a href="{{ route('search.index', ['tab' => 'hashtags']) }}"
                           class="small fw-semibold text-decoration-none">
                            {{ __('Browse hashtags') }} <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                        </a>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>

```

---
### `resources/views/feed/_posts.blade.php`

```php
{{-- Renders a list of post cards. Used by the feed (initial page + "Load more"),
     the hashtag page and the public profile (FR-7 ... FR-13).

     $commentsByPost is supplied by the feed so comments can be expanded in
     place. Other pages leave it out and the cards simply link to the detail
     page. --}}
@php
    // null on the profile and hashtag pages: the card then links to the
    // detail page instead of opening an inline comment section.
    $commentsByPost = $commentsByPost ?? null;
@endphp

@forelse ($posts as $post)
    <div class="mb-3">
        <x-post-card
            :post="$post"
            :comments="$commentsByPost?->get($post->id, collect())"
        />
    </div>
@empty
    <div class="card edu-card">
        <div class="card-body text-center py-5">
            <div class="edu-empty-illustration mx-auto" aria-hidden="true">
                <i class="bi bi-inbox"></i>
            </div>
            <h2 class="h6 fw-bold mb-1">{{ __('No posts to show yet.') }}</h2>

            @if (! empty($emptyMessage))
                <p class="text-muted small mb-3">{{ $emptyMessage }}</p>
            @endif

            @if (! empty($emptyActionUrl))
                <a href="{{ $emptyActionUrl }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1" aria-hidden="true"></i>
                    {{ $emptyActionLabel ?? __('Explore') }}
                </a>
            @endif
        </div>
    </div>
@endforelse

```

---
### `resources/views/components/post-card.blade.php`

```php
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

                    {{-- Reporting a post that belongs to somebody else (FR-32). --}}
                    @if (auth()->id() !== $post->user_id)
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

{{-- Each post owns its report modal (FR-32). --}}
@if (auth()->id() !== $post->user_id)
    @include('reports._modal', ['reportable' => $post])
@endif

```

---
### `resources/views/components/confirm-delete-modal.blade.php`

```php
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

---
### `resources/views/components/user-card.blade.php`

```php
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
                <div class="small text-muted text-capitalize">
                    <i class="bi bi-person-badge me-1"></i>{{ $user->role }}
                    @if ($user->subject)
                        &middot; {{ $user->subject }}
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

---
### `resources/views/comments/_comment.blade.php`

```php
{{-- One comment under a post (FR-13).
     Included by posts/show and returned as HTML by CommentController@store. --}}
@php
    $author = $comment->user;

    $roleColor = match ($author?->role) {
        \App\Models\User::ROLE_ADMIN => 'dark',
        \App\Models\User::ROLE_TEACHER => 'primary',
        default => 'success',
    };
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

    {{-- Report somebody else's comment (FR-32). --}}
    @if (auth()->id() !== $comment->user_id)
        <button type="button" class="btn btn-sm btn-link text-muted px-1"
                data-bs-toggle="modal" data-bs-target="#report-comment-{{ $comment->id }}"
                title="{{ __('Report comment') }}" aria-label="{{ __('Report comment') }}">
            <i class="bi bi-flag"></i>
        </button>
    @endif
</div>

@if (auth()->id() !== $comment->user_id)
    @include('reports._modal', ['reportable' => $comment])
@endif

```

---
### `resources/views/posts/show.blade.php`

```php
<x-app-layout>
    <x-slot name="title">{{ __('Post') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('feed.index') }}">{{ __('Feed') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Post') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-chat-square-text me-1 text-edu"></i> {{ __('Post') }}
                </h1>
                <div class="small text-muted">{{ __('Read the conversation and join in.') }}</div>
            </div>
            <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('Back to feed') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                {{-- The post and its comments share one counter scope, so an
                     AJAX add/delete updates every number on this page at once. --}}
                <div data-comment-scope="{{ $post->id }}">
                    <x-post-card :post="$post" />

                    {{-- Comments (FR-10) --}}
                    <section class="card edu-card mt-4" id="comments" aria-labelledby="comments-title">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2 class="h6 fw-bold mb-0" id="comments-title">
                                <i class="bi bi-chat me-1 text-edu" aria-hidden="true"></i>
                                {{ __('Comments') }}
                            </h2>
                            <span class="badge bg-body-secondary text-body border">
                                <span data-comment-count>{{ $post->comments_count }}</span>
                            </span>
                        </div>

                        <div class="card-body">
                            <div data-comment-region>
                                {{-- Add a comment: fetch() when JavaScript is on, normal POST otherwise. --}}
                                <form method="POST" action="{{ route('comments.store', $post) }}"
                                      data-comment-form class="mb-3">
                                    @csrf
                                    <label for="comment-body" class="visually-hidden">{{ __('Your comment') }}</label>
                                    <textarea name="body" id="comment-body" rows="2" maxlength="280" required
                                              class="form-control @error('body') is-invalid @enderror"
                                              placeholder="{{ __('Write a comment...') }}">{{ old('body') }}</textarea>
                                    @error('body')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                                        <div class="form-text mt-0">{{ __('Up to 280 characters.') }}</div>
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-send me-1" aria-hidden="true"></i> {{ __('Comment') }}
                                        </button>
                                    </div>
                                </form>

                                {{-- New comments are appended here by feed.js --}}
                                <div data-comment-list>
                                    @forelse ($comments as $comment)
                                        @include('comments._comment', ['comment' => $comment])
                                    @empty
                                        <p class="text-muted small mb-0" data-comment-empty>
                                            {{ __('No comments yet. Be the first one!') }}
                                        </p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        @if ($comments->hasPages())
                            <div class="card-footer bg-transparent">
                                <x-pagination-links :paginator="$comments" />
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

---
### `resources/views/posts/create.blade.php`

```php
<x-app-layout>
    <x-slot name="title">{{ __('New post') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('feed.index') }}">{{ __('Feed') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('New post') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-pencil-square me-1 text-edu" aria-hidden="true"></i> {{ __('New post') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Short updates work best. Add one image if it helps.') }}</p>
            </div>
            <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-lg me-1" aria-hidden="true"></i> {{ __('Cancel') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                <div class="card edu-card">
                    <div class="card-body">
                        {{-- Same partial the feed composer uses (FR-9). --}}
                        @include('posts._form', ['post' => null])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

---
### `resources/views/posts/edit.blade.php`

```php
<x-app-layout>
    <x-slot name="title">{{ __('Edit post') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('feed.index') }}">{{ __('Feed') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('posts.show', $post) }}">{{ __('Post') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-pencil me-1 text-edu" aria-hidden="true"></i> {{ __('Edit post') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Update the text or replace the image.') }}</p>
            </div>
            <a href="{{ route('posts.show', $post) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to the post') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                <div class="card edu-card">
                    <div class="card-body">
                        {{-- Only reachable when PostPolicy allows it (FR-10). --}}
                        @include('posts._form', ['post' => $post])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

---
### `resources/views/posts/_form.blade.php`

```php
{{-- Shared "write a post" form, used by the feed composer and by
     posts/create + posts/edit (FR-9, FR-10).
     $post is null for a new post and a Post model while editing.
     resources/js/feed.js updates the character ring and the image preview. --}}
@php
    $post = $post ?? null;
    $editing = $post !== null;
    $value = old('body', $post?->body);
    $length = \Illuminate\Support\Str::length((string) $value);
@endphp

<form method="POST"
      action="{{ $editing ? route('posts.update', $post) : route('posts.store') }}"
      enctype="multipart/form-data"
      class="d-flex flex-column gap-2">
    @csrf
    @if ($editing)
        @method('PATCH')
    @endif

    <div>
        <label for="body" class="form-label fw-semibold">
            {{ $editing ? __('Edit your post') : __('What do you want to share?') }}
        </label>

        <textarea name="body" id="body" rows="{{ $editing ? 4 : 3 }}"
                  maxlength="280" required
                  data-post-body
                  class="form-control @error('body') is-invalid @enderror"
                  placeholder="{{ __('Share an update, a tip or a question. Use #hashtags.') }}">{{ $value }}</textarea>

        @error('body')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
            {{-- A small, text-only toolbar: no emoji picker, no hidden state. --}}
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-insert-hashtag
                        title="{{ __('Add a hashtag') }}" aria-label="{{ __('Add a hashtag') }}">
                    <i class="bi bi-hash" aria-hidden="true"></i>
                </button>

                <label for="image" class="btn btn-sm btn-outline-secondary mb-0">
                    <i class="bi bi-image me-1" aria-hidden="true"></i>
                    <span>{{ __('Image') }}</span>
                </label>
            </div>

            {{-- Live character counter, updated by resources/js/feed.js --}}
            <div class="d-flex align-items-center gap-2">
                <span class="form-text mt-0">{{ __('280 max') }}</span>
                <span class="edu-char-ring {{ $length >= 224 ? 'is-warning' : '' }} {{ $length >= 280 ? 'is-full' : '' }}"
                      data-char-ring
                      role="progressbar"
                      aria-label="{{ __('Characters used') }}"
                      aria-valuemin="0" aria-valuemax="280" aria-valuenow="{{ $length }}"
                      aria-valuetext="{{ $length }}/280"
                      style="--edu-ring-progress: {{ min(360, (int) round($length / 280 * 360)) }}deg;">
                    <span data-char-counter aria-live="polite">{{ $length }}</span>
                </span>
            </div>
        </div>
    </div>

    <div>
        {{-- The actual file input stays visually hidden; the label above opens it. --}}
        <input type="file" name="image" id="image"
               accept="image/jpeg,image/png,image/webp"
               data-image-input
               class="visually-hidden @error('image') is-invalid @enderror">
        @error('image')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <div class="form-text">{{ __('JPG, PNG or WebP, up to 2 MB.') }}</div>

        {{-- Local preview before the upload (no request, no layout jump). --}}
        <div class="mt-2" data-image-preview hidden>
            <img src="" alt="{{ __('Preview of the selected image') }}"
                 class="img-fluid rounded-3 border" style="max-height: 180px;">
        </div>

        @if ($editing && $post->image)
            <div class="mt-2">
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}"
                     alt="{{ __('Current image') }}" class="img-fluid rounded-3 border" style="max-height: 160px;"
                     loading="lazy" decoding="async">
                <div class="form-text">{{ __('Uploading a new image replaces the current one.') }}</div>
            </div>
        @endif
    </div>

    <div class="d-flex flex-wrap gap-2 edu-form-actions">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-send me-1" aria-hidden="true"></i>
            {{ $editing ? __('Save changes') : __('Publish') }}
        </button>

        @if ($editing)
            <a href="{{ route('posts.show', $post) }}" class="btn btn-outline-secondary">
                {{ __('Cancel') }}
            </a>
        @endif
    </div>
</form>

```

---
### `resources/views/users/show.blade.php`

```php
@php
    $roleColor = match ($profile->role) {
        \App\Models\User::ROLE_ADMIN => 'dark',
        \App\Models\User::ROLE_TEACHER => 'primary',
        default => 'success',
    };
    $isMe = auth()->id() === $profile->id;
@endphp

<x-app-layout>
    <x-slot name="title">{{ $isMe ? __('My profile') : $profile->name }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('search.index', ['tab' => 'users']) }}">{{ __('People') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $isMe ? __('My profile') : $profile->name }}</li>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                {{-- Profile hero: cover, avatar, name, role, counts and the primary action. --}}
                <section class="card edu-card overflow-hidden mb-3" aria-labelledby="profile-name">
                    <div class="edu-cover" aria-hidden="true"></div>

                    <div class="card-body pt-0">
                        <div class="d-flex flex-wrap align-items-end gap-3">
                            <x-avatar :user="$profile" size="xl"
                                      class="edu-cover-avatar border border-4 border-body flex-shrink-0" />

                            <div class="flex-grow-1 min-w-0 pb-1">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <h1 class="h4 fw-bold mb-0 text-break" id="profile-name">{{ $profile->name }}</h1>
                                    <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $profile->role }}</span>
                                </div>

                                @if ($profile->subject)
                                    <div class="text-muted small">
                                        <i class="bi bi-book me-1" aria-hidden="true"></i>{{ $profile->subject }}
                                    </div>
                                @endif

                                <div class="text-muted small">
                                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                                    {{ __('Joined :date', ['date' => $profile->created_at?->format('M Y')]) }}
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 pb-1">
                                @if ($isMe)
                                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                                        {{ __('Edit profile') }}
                                    </a>
                                @else
                                    @include('users._follow-button', ['user' => $profile, 'followingIds' => $followingIds])
                                @endif
                            </div>
                        </div>

                        @if ($profile->bio)
                            <p class="mt-3 mb-0 edu-measure text-break">{{ $profile->bio }}</p>
                        @endif

                        <div class="d-flex flex-wrap gap-4 mt-3 pt-3 border-top">
                            <span>
                                <strong class="d-block h6 mb-0">{{ $profile->posts_count }}</strong>
                                <span class="small text-muted">{{ __('Posts') }}</span>
                            </span>
                            <a href="{{ route('users.followers', $profile) }}" class="text-decoration-none text-reset">
                                <strong class="d-block h6 mb-0" data-followers-count data-user-id="{{ $profile->id }}">{{ $profile->followers_count }}</strong>
                                <span class="small text-muted">{{ __('Followers') }}</span>
                            </a>
                            <a href="{{ route('users.following', $profile) }}" class="text-decoration-none text-reset">
                                <strong class="d-block h6 mb-0" data-following-count data-user-id="{{ $profile->id }}">{{ $profile->following_count }}</strong>
                                <span class="small text-muted">{{ __('Following') }}</span>
                            </a>
                        </div>
                    </div>
                </section>

                {{-- Tabs: Posts, Classes (teachers only) and About. --}}
                <div class="card edu-card">
                    <div class="card-header pb-0">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="profile-posts-tab" data-bs-toggle="tab"
                                        data-bs-target="#profile-posts" type="button" role="tab"
                                        aria-controls="profile-posts" aria-selected="true">
                                    <i class="bi bi-chat-square-text me-1" aria-hidden="true"></i>
                                    {{ __('Posts') }}
                                </button>
                            </li>

                            @if ($profile->isTeacher())
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="profile-classes-tab" data-bs-toggle="tab"
                                            data-bs-target="#profile-classes" type="button" role="tab"
                                            aria-controls="profile-classes" aria-selected="false">
                                        <i class="bi bi-easel me-1" aria-hidden="true"></i>
                                        {{ __('Classes') }}
                                    </button>
                                </li>
                            @endif

                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="profile-about-tab" data-bs-toggle="tab"
                                        data-bs-target="#profile-about" type="button" role="tab"
                                        aria-controls="profile-about" aria-selected="false">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                                    {{ __('About') }}
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content">
                        {{-- Posts --}}
                        <div class="tab-pane fade show active p-3" id="profile-posts" role="tabpanel"
                             aria-labelledby="profile-posts-tab" tabindex="0">
                            @if ($isMe)
                                <div class="d-flex justify-content-end mb-3">
                                    <a href="{{ route('posts.create') }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> {{ __('New post') }}
                                    </a>
                                </div>
                            @endif

                            @include('feed._posts', [
                                'posts' => $posts,
                                'commentsByPost' => null,
                                'emptyMessage' => $isMe
                                    ? __('You have not posted yet. Share an update with your followers.')
                                    : __('This person has not posted yet.'),
                                'emptyActionUrl' => $isMe ? route('posts.create') : null,
                                'emptyActionLabel' => $isMe ? __('Write your first post') : null,
                            ])

                            <div class="mt-3">
                                <x-pagination-links :paginator="$posts" />
                            </div>
                        </div>

                        {{-- Classes (teachers) --}}
                        @if ($profile->isTeacher())
                            <div class="tab-pane fade p-3" id="profile-classes" role="tabpanel"
                                 aria-labelledby="profile-classes-tab" tabindex="0">
                                @if ($classes->isEmpty())
                                    <x-empty-state icon="bi-easel"
                                                   :title="__('No published classes yet')"
                                                   :message="__('When this teacher publishes a class it will appear here.')">
                                        <a href="{{ route('classes.discover') }}" class="btn btn-primary">
                                            {{ __('Browse classes') }}
                                        </a>
                                    </x-empty-state>
                                @else
                                    <div class="row g-3">
                                        @foreach ($classes as $classRoom)
                                            <div class="col-12 col-md-6">
                                                <x-class-card :class-room="$classRoom" :url="route('classes.show', $classRoom)" />
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- About --}}
                        <div class="tab-pane fade p-3" id="profile-about" role="tabpanel"
                             aria-labelledby="profile-about-tab" tabindex="0">
                            <dl class="row small mb-0">
                                <dt class="col-sm-3 text-muted fw-semibold">{{ __('Role') }}</dt>
                                <dd class="col-sm-9 text-capitalize">{{ $profile->role }}</dd>

                                @if ($profile->subject)
                                    <dt class="col-sm-3 text-muted fw-semibold">{{ __('Subject') }}</dt>
                                    <dd class="col-sm-9">{{ $profile->subject }}</dd>
                                @endif

                                <dt class="col-sm-3 text-muted fw-semibold">{{ __('Member since') }}</dt>
                                <dd class="col-sm-9">{{ $profile->created_at?->format('d M Y') }}</dd>

                                <dt class="col-sm-3 text-muted fw-semibold">{{ __('Bio') }}</dt>
                                <dd class="col-sm-9 text-break mb-0">
                                    {{ $profile->bio ?: __('This member has not written a bio yet.') }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

---
### `resources/views/users/people.blade.php`

```php
@php
    // Either the followers or the following list (FR-17).
    $title = $mode === 'followers' ? __('Followers') : __('Following');
@endphp

<x-app-layout>
    <x-slot name="title">{{ $title }} · {{ $profile->name }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('users.show', $profile) }}">{{ $profile->name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-people me-1 text-edu" aria-hidden="true"></i> {{ $title }}
                    <span class="text-muted fw-normal" aria-hidden="true">·</span>
                    <span class="fw-normal">{{ $profile->name }}</span>
                </h1>
                <p class="small text-muted mb-0">{{ __('People in this part of the community.') }}</p>
            </div>
            <a href="{{ route('users.show', $profile) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to profile') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                {{-- Switch between the two lists without going back first --}}
                <div class="d-flex flex-wrap gap-2 mb-3" aria-label="{{ __('Switch list') }}">
                    <a class="edu-filter-chip {{ $mode === 'followers' ? 'is-active' : '' }}"
                       href="{{ route('users.followers', $profile) }}"
                       @if ($mode === 'followers') aria-current="page" @endif>
                        <i class="bi bi-person-heart" aria-hidden="true"></i>
                        {{ __('Followers') }}
                        <span class="visually-hidden">{{ __('of :name', ['name' => $profile->name]) }}</span>
                    </a>
                    <a class="edu-filter-chip {{ $mode === 'following' ? 'is-active' : '' }}"
                       href="{{ route('users.following', $profile) }}"
                       @if ($mode === 'following') aria-current="page" @endif>
                        <i class="bi bi-person-check" aria-hidden="true"></i>
                        {{ __('Following') }}
                    </a>
                </div>

                @if ($people->isEmpty())
                    <x-empty-state icon="bi-people"
                                   :title="$mode === 'followers' ? __('Nobody follows this user yet.') : __('This user does not follow anybody yet.')"
                                   :message="__('When people connect on EduConnect they appear here.')">
                        <a href="{{ route('search.index', ['tab' => 'users']) }}" class="btn btn-primary">
                            <i class="bi bi-search me-1" aria-hidden="true"></i> {{ __('Find people') }}
                        </a>
                    </x-empty-state>
                @else
                    <div class="card edu-card">
                        <ul class="list-group list-group-flush">
                            @foreach ($people as $person)
                                @php
                                    $personRoleColor = match ($person->role) {
                                        \App\Models\User::ROLE_ADMIN => 'dark',
                                        \App\Models\User::ROLE_TEACHER => 'primary',
                                        default => 'success',
                                    };
                                @endphp

                                <li class="list-group-item d-flex flex-wrap align-items-center gap-3 py-3">
                                    <x-avatar :user="$person" size="md" />

                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <a href="{{ route('users.show', $person) }}"
                                               class="fw-semibold text-decoration-none text-reset text-break">{{ $person->name }}</a>
                                            <span class="badge text-bg-{{ $personRoleColor }} text-capitalize">{{ $person->role }}</span>
                                        </div>

                                        @if ($person->subject)
                                            <div class="small text-muted">
                                                <i class="bi bi-book me-1" aria-hidden="true"></i>{{ $person->subject }}
                                            </div>
                                        @endif
                                    </div>

                                    @include('users._follow-button', ['user' => $person, 'followingIds' => $followingIds])
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-3">
                        <x-pagination-links :paginator="$people" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

```

---
### `resources/views/users/_follow-button.blade.php`

```php
{{-- Follow / unfollow button (FR-11).
     Students and teachers can follow each other. Admin accounts are not
     followable, and nobody gets a button on their own profile. Admins do not
     use the follow routes, so they do not see the button either.
     It is a real form: without JavaScript the browser simply posts it. --}}
@php
    $viewer = auth()->user();
    $isFollowing = $followingIds->contains($user->id);
    $canFollow = $viewer
        && ! $viewer->isAdmin()
        && ! $viewer->is($user)
        && ! $user->isAdmin();
@endphp

@if ($canFollow)
    <form method="POST"
          action="{{ $isFollowing ? route('follows.destroy', $user) : route('follows.store', $user) }}"
          data-follow-form
          data-follow-url="{{ route('follows.store', $user) }}"
          data-unfollow-url="{{ route('follows.destroy', $user) }}"
          data-following="{{ $isFollowing ? '1' : '0' }}"
          data-follow-user-id="{{ $user->id }}"
          data-label-follow="{{ __('Follow') }}"
          data-label-following="{{ __('Following') }}"
          class="d-inline">
        @csrf
        @if ($isFollowing)
            @method('DELETE')
        @endif
        <button type="submit" class="btn btn-sm {{ $isFollowing ? 'btn-outline-secondary' : 'btn-primary' }}"
                data-follow-button aria-pressed="{{ $isFollowing ? 'true' : 'false' }}">
            <i class="bi {{ $isFollowing ? 'bi-person-check' : 'bi-person-plus' }} me-1"></i>
            <span data-follow-label>{{ $isFollowing ? __('Following') : __('Follow') }}</span>
        </button>
    </form>
@endif

```

---
### `resources/views/hashtags/show.blade.php`

```php
<x-app-layout>
    <x-slot name="title">#{{ $hashtag->name }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('feed.index') }}">{{ __('Feed') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">#{{ $hashtag->name }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-hash text-edu" aria-hidden="true"></i>{{ $hashtag->name }}
                </h1>
                <p class="small text-muted mb-0">
                    {{-- e.g. "3 posts have this tag" --}}
                    {{ trans_choice(':count post has this tag.|:count posts have this tag.', $posts->total(), ['count' => $posts->total()]) }}
                </p>
            </div>
            <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to feed') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                <section class="card edu-card mb-3">
                    <div class="card-body d-flex flex-wrap align-items-center gap-3">
                        <span class="edu-hashtag-hero" aria-hidden="true">#</span>

                        <div class="min-w-0">
                            <h2 class="h4 fw-bold mb-0 text-break">#{{ $hashtag->name }}</h2>
                            <p class="small text-muted mb-0">
                                {{ __('Every visible post that uses this hashtag, newest first.') }}
                            </p>
                        </div>

                        <span class="edu-chip ms-auto">
                            <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                            {{ trans_choice(':count post|:count posts', $posts->total(), ['count' => $posts->total()]) }}
                        </span>
                    </div>
                </section>

                {{-- Every visible post carrying this tag (FR-15) --}}
                @include('feed._posts', [
                    'posts' => $posts,
                    'emptyMessage' => __('No visible post uses this hashtag yet.'),
                    'emptyActionUrl' => route('feed.index'),
                    'emptyActionLabel' => __('Go to the feed'),
                ])

                <div class="mt-3">
                    <x-pagination-links :paginator="$posts" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

---
### `resources/views/search/index.blade.php`

```php
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

---
### `resources/js/feed.js`

```javascript
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

```

---
### `database/migrations/2026_10_29_100001_create_posts_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The social feed posts (FR-9 ... FR-12).
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // Author. If the author is deleted, the post goes with them.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('body', 280);                       // max 280 characters
            $table->string('image')->nullable();               // relative path on the "public" disk
            $table->boolean('is_hidden')->default(false);      // admin moderation (FR-27/FR-28)

            $table->timestamps();

            // Feeds always filter on this column.
            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};

```

---
### `database/migrations/2026_10_29_100002_create_comments_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comments that users write under a post (FR-13).
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // Deleting the post removes its comments.
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();

            // Deleting the author removes their comments.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->text('body');
            $table->boolean('is_hidden')->default(false);      // admin moderation

            $table->timestamps();

            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};

```

---
### `database/migrations/2026_10_29_100003_create_likes_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per "user liked post" (FR-11).
     */
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            // A user can like a post only once.
            $table->unique(['post_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};

```

---
### `database/migrations/2026_10_29_100004_create_follows_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who follows whom (FR-17).
     * follower_id follows following_id.
     */
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id();

            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('following_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            // Nobody can follow the same person twice.
            // (Self-following is blocked in User::follow() as well.)
            $table->unique(['follower_id', 'following_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};

```

---
### `database/migrations/2026_10_29_100005_create_hashtags_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hashtags extracted from post bodies (FR-15).
     */
    public function up(): void
    {
        Schema::create('hashtags', function (Blueprint $table) {
            $table->id();

            // Always stored in lower case so "#Maths" and "#maths" are the same tag.
            $table->string('name')->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hashtags');
    }
};

```

---
### `database/migrations/2026_10_29_100006_create_hashtag_post_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table between posts and hashtags (FR-15).
     * A plain pivot: no id, no timestamps.
     */
    public function up(): void
    {
        Schema::create('hashtag_post', function (Blueprint $table) {
            $table->foreignId('hashtag_id')->constrained('hashtags')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();

            // Composite primary key: the same tag cannot be attached to the
            // same post twice, and the pivot needs no id of its own.
            $table->primary(['hashtag_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hashtag_post');
    }
};

```

---
### `tests/Feature/FeedTest.php`

```php
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

```

---

## 7. Test checklist (FR-7 … FR-13)

The full suite passes: **275 tests / 1,463 assertions**. `FeedTest` alone has
**36 tests / 183 assertions**.

### FR-7 — Create a post

- [x] A logged-in member publishes a post and is redirected to the feed.
- [x] A post with exactly 280 characters is accepted.
- [x] A post with 281 characters fails validation and stores nothing.
- [x] An empty post fails validation.
- [x] A guest is redirected to the login page.
- [x] Post creation is rate-limited to ten per minute.
- [x] An optional image is accepted, decoded, re-encoded and stored under
      `posts/`; the stored width/height are capped at 1600 px.
- [x] The live character counter is wired to `[data-post-body]` and
      `[data-char-counter]` / `[data-char-ring]`.
- [x] Tests: `test_a_logged_in_user_can_publish_a_post`,
      `test_a_post_cannot_be_longer_than_280_characters`,
      `test_an_empty_post_is_rejected`, `test_a_guest_cannot_publish_a_post`,
      `test_post_creation_is_rate_limited_to_ten_per_minute`,
      `test_a_post_image_is_stored_in_the_posts_directory`.

### FR-8 — Edit and delete

- [x] The author reaches the edit page and can update the body.
- [x] A non-owner cannot open the edit page or update the post (403).
- [x] A non-owner cannot delete the post (403).
- [x] The author can delete their own post.
- [x] An administrator can delete any post but cannot edit it.
- [x] Replacing an image deletes the old file and keeps the new one.
- [x] Deleting a post removes its image file.
- [x] The post card exposes edit/delete only through `@can` and opens the
      reusable confirmation modal before deletion.
- [x] Tests: `test_a_user_cannot_edit_someone_elses_post`,
      `test_a_user_cannot_delete_someone_elses_post`,
      `test_the_author_can_edit_and_delete_their_own_post`,
      `test_an_administrator_can_delete_a_post_but_not_edit_it`,
      `test_replacing_a_post_image_deletes_the_old_file`,
      `test_deleting_a_post_removes_its_image_file`.

### FR-9 — Likes

- [x] `POST /posts/{post}/like` returns JSON `{liked: true, count: 1}`.
- [x] Liking twice does not create a second row (code + unique index).
- [x] `DELETE /posts/{post}/like` removes the like and returns the new count.
- [x] A hidden post cannot be liked by another user (403).
- [x] `feed.js` updates the heart, `aria-pressed`, button style and count
      instantly, disables the button in-flight and restores it on error.
- [x] CSRF is sent in `X-CSRF-TOKEN`; non-JS requests redirect back.
- [x] Tests: `test_a_like_can_be_toggled_and_never_duplicates`,
      `test_a_hidden_post_cannot_be_liked_by_other_users`.

### FR-10 — Comments

- [x] A member can comment on a visible post and receive HTML + the new count.
- [x] The comment form exists on the post detail page and inside the feed card.
- [x] The body is required and capped at 280 characters.
- [x] The comment author can delete their own comment.
- [x] The post author cannot delete another person's comment.
- [x] An administrator can delete any comment.
- [x] Hidden comments are excluded from the list and the count.
- [x] Comments are paginated on the detail page; counters update after AJAX
      add/delete.
- [x] Tests: `test_a_user_can_comment_and_only_delete_their_own_comment`,
      `test_only_the_comment_author_or_an_admin_can_delete_a_comment`,
      `test_hidden_comments_are_not_shown_or_counted`,
      `PhaseSevenTest::test_hidden_comments_disappear_from_the_post_and_its_counter`.

### FR-11 — Follow / unfollow

- [x] A student can follow and unfollow a teacher; following twice is idempotent.
- [x] Nobody can follow themselves (403).
- [x] Admin accounts are not followable (404) and admins cannot use the routes.
- [x] Students can follow other students; teachers can follow students.
- [x] The JSON answer updates follower counts and the viewer's following total.
- [x] AJAX follow forms are present on the profile page, feed suggestions and
      search results; the shared button is also used by follower lists and the
      student dashboard.
- [x] Follower counts and their singular/plural labels update in place.
- [x] `/users/{user}/followers` and `/users/{user}/following` are reachable,
      paginated and list follow buttons.
- [x] Tests: `test_a_student_can_follow_and_unfollow_a_teacher`,
      `test_nobody_can_follow_themselves`, `test_a_student_can_follow_another_student`,
      `test_search_results_and_feed_suggestions_use_the_ajax_follow_button`,
      `DashboardTest::test_a_student_can_follow_and_unfollow_a_teacher`,
      `test_a_student_can_follow_another_student_but_not_themselves_or_an_admin`.

### FR-12 — Home feed

- [x] The feed contains the viewer's posts and the posts of followed users.
- [x] Posts of strangers are excluded.
- [x] Hidden posts are excluded, including the viewer's own hidden posts.
- [x] Ten posts per page, newest first; page 2 contains the remainder.
- [x] The **Load more** button fetches JSON HTML + `has_more` + `next_page`.
- [x] A non-JS request to `?page=2` still renders a normal page.
- [x] Author, hashtags, like count, visible-comment count and `liked_by_me`
      are eager loaded; the performance test keeps the query count bounded.
- [x] The right sidebar shows who to follow and the top five hashtags from the
      last seven days.
- [x] An empty feed shows an empty state and a **Find people to follow** link.
- [x] Tests: `test_the_feed_shows_only_my_posts_and_the_posts_of_people_i_follow`,
      `test_the_feed_is_paginated_ten_per_page_and_shows_the_newest_first`,
      `test_the_feed_answers_json_for_the_load_more_button`,
      `test_the_feed_shows_an_empty_state_without_posts`,
      `test_a_guest_is_redirected_from_the_feed`,
      `PerformanceTest::test_the_feed_paginates_and_keeps_its_query_count_bounded`.

### FR-13 — Hashtags

- [x] Hashtags are extracted from the body with a Unicode-aware regex.
- [x] `#Laravel` and `#laravel` resolve to the same lower-case tag.
- [x] Unicode tags (`#Éducation`, `#数学`, `#Café_2026`) are extracted and
      lower-cased.
- [x] The `hashtag_post` pivot is synced on create and update; removed tags are
      detached.
- [x] The body is escaped first and linkified afterwards, so HTML/JS cannot
      execute.
- [x] Hashtag links point to `/hashtags/{name}`.
- [x] The hashtag page lists only visible posts, newest first, paginated, with
      the tag name and total post count.
- [x] Tests: `test_hashtags_are_parsed_and_synced_in_lower_case`,
      `test_hashtags_support_unicode_and_are_lower_cased_everywhere`,
      `test_editing_a_post_resyncs_and_removes_old_hashtags`,
      `test_hashtag_links_are_xss_safe`,
      `test_a_post_body_with_html_is_escaped_on_the_feed`,
      `test_the_hashtag_page_lists_visible_posts_with_that_tag`.

### Post detail and public profile

- [x] The post detail page shows the post and its visible comments.
- [x] Hidden posts answer 403 to everybody except the author and admins.
- [x] A public profile lists the user's visible posts with pagination.
- [x] Profile counters are posts (visible only), followers and following.
- [x] Follower / following list pages are reachable and paginated.
- [x] Tests: `test_the_single_post_page_lists_its_comments`,
      `test_a_hidden_post_is_not_visible_to_other_users`,
      `test_a_public_profile_shows_the_user_posts_and_counts`,
      `test_the_follower_lists_are_paginated_and_reachable`.

---

## 8. Commands and verification

Run from the project root:

```bash
php artisan test
vendor/bin/pint --test app tests routes config database
npm run build
php artisan view:cache
node --check resources/js/feed.js
```

Verified results on this machine:

| Command | Result |
| --- | --- |
| `php artisan test` | **275 passed / 1,463 assertions** |
| `php artisan test tests/Feature/FeedTest.php` | **36 passed / 183 assertions** |
| `vendor/bin/pint --test app tests routes config database` | passed |
| `npm run build` | 140 modules transformed, built in ~24 s |
| `php artisan view:cache` | Blade templates cached successfully |
| `node --check resources/js/feed.js` | syntax OK |

Assets produced: `app-C-UklRRc.css` (368.63 kB / 57.47 kB gzip),
`app-BHAHDvOe.js` (229.56 kB / 75.81 kB gzip) and the lazy Chart.js chunk.

---

## 9. Manual test checklist

Use `student1@…` / `password`, `teacher1@…` / `password`, or the admin account.

### Posts

1. Open `/feed`, type 280 characters and watch the ring reach full; a 281st
   character is blocked by `maxlength`.
2. Publish with and without a JPG/PNG/WebP under 2 MB; confirm the image appears
   in the feed and is stored under `storage/app/public/posts`.
3. Try a 3 MB file and a `.txt` file; both show a validation error.
4. Edit your own post, replace the image, then confirm the old file is gone from
   `storage/app/public/posts`.
5. Open another member's post: no edit/delete entry. As admin: delete is
   available, edit is not.
6. Delete a post from the kebab menu: the confirmation modal appears first.

### Feed

7. Follow two people and confirm only their posts plus your own appear.
8. Confirm hidden posts are absent.
9. Scroll to **Load more**; ten more cards append without a reload.
10. Disable JavaScript and open `?page=2`; the page still works.
11. Confirm the sidebar shows who to follow and the top five hashtags from the
    last seven days.
12. Unfollow everybody and confirm the empty state links to people search.

### Likes and comments

13. Click the heart: it turns red, the count changes immediately and the button
    is disabled during the request. Click again to unlike.
14. Double-click quickly; the count never duplicates.
15. Comment from the detail page and from an expanded feed card; the count and
    the new comment update in place.
16. Try a 281-character comment; validation rejects it.
17. Delete your own comment. As the post author, confirm you cannot delete
    somebody else's comment. As admin, confirm you can.
18. Hide a comment as admin; it disappears from the post page and the counter.

### Follow

19. Follow/unfollow from a profile, the feed suggestion list and search results;
    the follower number and plural label update in place.
20. Confirm there is no follow button on your own profile or on an admin profile.
21. Open `/users/{id}/followers` and `/following`; both paginate and show follow
    buttons.

### Hashtags

22. Publish `#Éducation #数学 #Café_2026`; all three become links.
23. Click a tag; `/hashtags/{name}` shows the tag, its post count and only
    visible posts, paginated.
24. Edit the post and remove a tag; the old tag is detached.
25. Publish `<script>alert(1)</script> #safety`; the script is shown as text and
    `#safety` is a link.

### Profile

26. Open a public profile; posts paginate and the counters match the database.
27. Open a hidden post as its author (200) and as another member (403).

---

## 10. Done when

- [x] `PostController` provides create, store, edit, update and destroy.
- [x] The body is required and limited to 280 characters with a live counter.
- [x] Images are optional, restricted to JPG/PNG/WebP ≤ 2 MB, stored under
      `storage/app/public/posts` and re-encoded.
- [x] Replacing or deleting a post removes the old image file.
- [x] Only the owner can edit; the owner or an admin can delete; a confirmation
      modal guards deletion.
- [x] `/feed` shows own + followed posts, visible only, newest first, 10 per
      page with Load more and eager loading.
- [x] The feed sidebar has who-to-follow and the top five trending hashtags from
      the last seven days.
- [x] The empty feed links to people search.
- [x] Likes use fetch + JSON, send CSRF, update instantly, disable in-flight and
      cannot duplicate (code + unique index).
- [x] Comments exist on the detail page and in expandable feed cards, validate
      required/max 280, paginate, update counts and exclude hidden comments.
- [x] Only the comment author or an admin can delete a comment (`CommentPolicy`).
- [x] Follow/unfollow is AJAX on profiles, suggestions, search and lists; self
      follow is forbidden; counts update live.
- [x] Follower and following pages list users with follow buttons.
- [x] Hashtags use a Unicode regex, are stored lower case, sync the pivot, are
      escaped before linkification and have a paginated visible-posts page.
- [x] The final post card contains avatar, name, role badge, time ago, linked
      hashtags, lazy image, like, comment, report and owner edit/delete.
- [x] Public profiles and post detail pages paginate their visible posts/comments.
- [x] Feature tests cover creation with/without image, >280, policies, like
      toggle, comment permissions, follow rules, hashtag extraction and feed
      contents.
- [x] `php artisan test` passes: **275 tests / 1,463 assertions**.
- [x] `vendor/bin/pint --test app tests routes config database` passes.
- [x] `npm run build` succeeds (140 modules).
- [x] `php artisan view:cache` succeeds.
- [x] `/users/2` keeps its purple/indigo theme; no theme tokens changed.
- [x] No earlier feature was removed.

---

## 11. Known environment limits

- MySQL 8 was not available on this machine; migrations and the suite were
  verified on SQLite. The queries use portable SQL (for example `whereHas`
  instead of `HAVING` without `GROUP BY`).
- No Playwright/Puppeteer installation is present, so the browser checklist in
  section 9 is manual. The JavaScript was syntax-checked and bundled by Vite.
- Real SMTP delivery and a production queue worker were not exercised; the
  notifications use the existing `database`/`sync` configuration from earlier
  phases.
