<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kicks out a user who is blocked while they are already logged in (FR-6).
 * Registered as "not.blocked" and also appended to the whole "web" group.
 */
class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Guests are not affected: this middleware only polices logged-in users.
        if ($user && $user->is_blocked) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Your account has been blocked by an administrator. Please contact support.');
        }

        return $next($request);
    }
}
