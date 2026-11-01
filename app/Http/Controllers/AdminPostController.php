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
