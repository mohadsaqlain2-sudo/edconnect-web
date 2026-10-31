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
