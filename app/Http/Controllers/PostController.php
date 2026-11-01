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
