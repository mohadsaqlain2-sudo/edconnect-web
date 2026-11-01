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
