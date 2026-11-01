<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $name = $user->name;

        // The User model removes the profile photo, the post images and every
        // report about this user's content in its deleting hook. Posts,
        // comments, likes, follows and enrollments go with the account through
        // the cascade rules on the foreign keys.
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
