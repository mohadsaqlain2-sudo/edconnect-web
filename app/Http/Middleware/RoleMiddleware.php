<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Only let the request through when the logged-in user has one of the given roles.
     *
     * Usage in routes/web.php:
     *   ->middleware('role:admin')
     *   ->middleware('role:teacher,admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Not logged in yet -> send them to the login page.
        if (! $user) {
            return redirect()->route('login');
        }

        // Wrong role -> 403 Forbidden.
        if (! in_array($user->role, $roles, true)) {
            abort(403, 'You are not allowed to open this page.');
        }

        return $next($request);
    }
}
