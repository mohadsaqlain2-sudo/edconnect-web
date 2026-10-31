<?php

use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Short names so routes can use ->middleware('role:admin') and
        // ->middleware('not.blocked').
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'not.blocked' => EnsureUserIsNotBlocked::class,
        ]);

        // Run the block check on EVERY web request: a user who is blocked while
        // logged in is kicked out immediately, on any page (FR-6).
        $middleware->appendToGroup('web', EnsureUserIsNotBlocked::class);

        // Security headers on every HTML response (SRS 5.2).
        $middleware->appendToGroup('web', SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
