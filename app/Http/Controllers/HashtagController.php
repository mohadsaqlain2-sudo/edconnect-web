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
