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
                    // The class cards show the teacher name and the student
                    // count, so both are loaded for all six cards at once.
                    ->with('teacher:id,name,subject')
                    ->withCount('students')
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
