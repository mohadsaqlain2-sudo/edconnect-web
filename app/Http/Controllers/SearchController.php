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
