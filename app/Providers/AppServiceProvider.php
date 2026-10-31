<?php

namespace App\Providers;

use App\Models\ClassRoom;
use App\Policies\ClassPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The project uses Bootstrap 5, so pagination links must be Bootstrap
        // markup (Laravel's default pagination view is Tailwind based).
        Paginator::useBootstrapFive();

        // ClassPolicy is named after the SRS, not after the model, so Laravel's
        // naming convention (ClassRoom -> ClassRoomPolicy) cannot find it.
        // The mapping is declared explicitly instead (FR-18, FR-20, FR-24).
        Gate::policy(ClassRoom::class, ClassPolicy::class);

        $this->configureRateLimiting();
    }

    /**
     * Named limiters used by throttle:* middleware (SRS 5.2).
     * Login failed attempts are still owned by LoginRequest so a successful
     * login is never counted against the lockout.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));

        RateLimiter::for('posts', fn (Request $request) => Limit::perMinute(10)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('comments', fn (Request $request) => Limit::perMinute(30)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('likes', fn (Request $request) => Limit::perMinute(60)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('reports', fn (Request $request) => Limit::perMinute(10)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('follows', fn (Request $request) => Limit::perMinute(30)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('notifications-poll', fn (Request $request) => Limit::perMinute(60)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('search-suggest', fn (Request $request) => Limit::perMinute(120)->by(
            $request->user()?->id ?: $request->ip()
        ));
    }
}
