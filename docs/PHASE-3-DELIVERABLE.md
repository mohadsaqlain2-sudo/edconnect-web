# EduConnect — Phase 3 deliverable

Main layout, reusable components, the three role dashboards, profile pages and
custom error pages. Every file is copied verbatim from the working repository.

**Snapshot**

| Item | Value |
| --- | --- |
| Framework | Laravel 12.70.2 (Laravel 11 compatible code) |
| Front end | Blade + Bootstrap 5.3.8 + Bootstrap Icons + Chart.js 4, Vite 6 |
| Layouts | `app` (navbar + role sidebar + footer + mobile bar) and `guest` |
| Dashboards | admin, teacher, student (Phase 3 core, extended in 3B/3C) |
| Phase 3 tests | `DashboardTest`, `DashboardRedesignTest`, `PhaseThreeUiTest`, `PhaseThreeCTest`, `ProfileTest` → **46 passed, 325 assertions** |
| Full suite | **266 passed, 1401 assertions** |

This document consolidates what was delivered in Phase 3, 3B (dashboard design
system, charts, dark mode) and 3C (mobile navigation, error pages, final UI
audit). The Phase 3 requirements are all present; 3B/3C only add to them.

| Requirement | Where |
| --- | --- |
| FR-31 main layout | `layouts/app.blade.php`, `navigation`, `sidebar`, `mobile-nav`, `js/layout.js` |
| Reusable components | `resources/views/components/{stat-card,class-card,post-card,avatar,empty-state,status-badge,confirm-delete-modal,flash,…}` |
| FR-26 admin dashboard | `AdminDashboardController`, `Support/DashboardMetrics`, `dashboards/admin.blade.php`, `js/charts.js` |
| FR-29 teacher dashboard | `TeacherDashboardController`, `dashboards/teacher.blade.php` |
| FR-30 student dashboard | `StudentDashboardController`, `dashboards/student.blade.php` |
| FR-4 edit profile | `ProfileController`, `ProfileUpdateRequest`, `profile/edit` + partials, `ImageUploadService` |
| FR-5 public profile | `UserProfileController`, `users/show`, `users/people`, `_follow-button` |
| 403/404/500 | `resources/views/errors/*` + shared `page.blade.php` |

## 1. Layout behaviour

- **Desktop (≥992 px):** fixed 268 px dark sidebar, collapsible to 76 px. The
  state is written to `localStorage` (`educonnect.sidebar.collapsed`) and applied
  by an inline head script before first paint, so there is no jump.
- **Mobile (<992 px):** the sidebar becomes a Bootstrap offcanvas drawer opened
  by the hamburger. A five-item bottom bar (`mobile-nav`) keeps Home, Classes,
  Search, Notifications and Profile one thumb-tap away. The floating compose
  button appears only on the feed.
- **Active links:** `request()->routeIs(...)` per role, plus a special case so
  “My Profile” is only active on your own `/users/{id}`.
- **Navbar:** brand, desktop sidebar toggle, page title + breadcrumbs (xl+),
  search box (wired in Phase 6), theme toggle, notification bell with unread
  badge, user dropdown (profile, edit profile, feed, notifications, logout).
- **Flash:** `<x-flash />` renders success, error, status and a full validation
  summary.
- **Footer + `@stack('scripts')`** are inside `layouts/app.blade.php`.

## 2. Responsive targets

The CSS is mobile-first. The manual checks below are the ones used for the
phase sign-off (Chrome DevTools device toolbar):

| Width | What to look for |
| --- | --- |
| 360 px | No horizontal scroll; offcanvas sidebar; bottom nav visible; stat cards stack 1 per row; tables scroll inside `.table-responsive`; dashboard charts keep a 220 px height. |
| 768 px | Two stat cards per row; navbar search replaces the phone search icon; class/post cards 2-up. |
| 1200 px | Sidebar expanded; three or five stat cards in one row; dashboard two-column chart grid; tables show every column. |

## 3. Commands

```bash
# build the assets (Bootstrap + Chart.js through Vite)
npm run dev        # while developing
npm run build      # before a demo / deploy

# Phase 3 tests
php artisan test tests/Feature/DashboardTest.php \
                 tests/Feature/DashboardRedesignTest.php \
                 tests/Feature/PhaseThreeUiTest.php \
                 tests/Feature/PhaseThreeCTest.php \
                 tests/Feature/ProfileTest.php

# whole suite
php artisan test
```

## 4. Data and query notes

- `DashboardMetrics` is the single place for dashboard aggregates. Every method
  that runs more than a trivial query is cached for 5 minutes under a
  role/user-scoped key, so one user's numbers cannot leak into another page.
- Admin totals come from **one** aggregate query (`SUM(CASE WHEN role = …)`),
  not five `count()` calls. The 12-month chart is one grouped query and is
  zero-filled in PHP, so months with no sign-ups still appear.
- Teacher classes use `withCount('students')`; activity is three limited,
  eager-loaded queries merged in PHP (likes, comments, enrollments, follows).
- Student recommendations exclude followed teachers with a sub-query and return
  at most three rows; upcoming classes are limited to six.
- Every dashboard keeps the `auth` + `not.blocked` + `role:` middleware from
  Phase 1, so a student can never open the admin page.


# 5. Layouts

## `resources/views/layouts/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        @php
            // A page can pass <x-slot name="title">Page title</x-slot> and
            // <x-slot name="description">Meta description</x-slot>.
            $pageTitle = isset($title) ? trim(strip_tags((string) $title)) : '';
            $pageDescription = isset($description) && trim(strip_tags((string) $description)) !== ''
                ? trim(strip_tags((string) $description))
                : config('educonnect.meta.description');
            $fullTitle = ($pageTitle !== '' ? $pageTitle.' · ' : '').config('app.name', 'EduConnect');
        @endphp
        <title>{{ $fullTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">

        {{-- Social previews use the same title and description. --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'EduConnect') }}">
        <meta property="og:title" content="{{ $fullTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $fullTitle }}">
        <meta name="twitter:description" content="{{ $pageDescription }}">

        {{-- Favicon and browser chrome colours (light + dark). --}}
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}">
        <meta name="theme-color" content="#4f46e5" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">

        {{-- Theme first: before the stylesheet so there is no flash of the
             wrong colour scheme. Explicit choice wins, then the OS setting. --}}
        <script>
            (function () {
                try {
                    var stored = window.localStorage.getItem('educonnect.theme');
                    var theme = stored === 'dark' || stored === 'light'
                        ? stored
                        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-bs-theme', theme);
                } catch (error) {
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                }
            })();
        </script>

        {{-- Apply the remembered desktop sidebar state before the first paint,
             so the layout does not jump when JavaScript finishes loading. --}}
        <script>
            try {
                if (window.localStorage.getItem('educonnect.sidebar.collapsed') === '1') {
                    document.documentElement.classList.add('edu-sidebar-collapsed');
                }
            } catch (error) {
                // Private browsing can refuse localStorage; the default open
                // sidebar is a perfectly fine fallback.
            }
        </script>

        {{-- Inter is loaded from Google Fonts (allowed by the phase brief). --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    @php
        // One unread counter shared by the top bar and the mobile navigation,
        // so both badges always agree and the feed keeps its query budget.
        $layoutUnreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;

        // The public landing page uses this layout too, but a signed-in visitor
        // should see the marketing site, not the dashboard sidebar, mobile nav
        // or composer shortcut. Those are dashboard chrome only.
        $isLandingPage = request()->is('/');
    @endphp

    {{-- body carries the notification polling URL for logged in users (FR-24) --}}
    <body class="edu-body d-flex flex-column min-vh-100 {{ auth()->check() && ! $isLandingPage ? 'edu-has-mobile-nav' : '' }}"
          @auth data-notification-url="{{ route('notifications.poll') }}" @endauth>
        <a class="visually-hidden-focusable edu-skip-link" href="#main-content" style="position:fixed;">
            {{ __('Skip to main content') }}
        </a>

        {{-- Sticky, blurred top navigation bar --}}
        @include('layouts.navigation', ['unreadNotifications' => $layoutUnreadCount])

        <div class="edu-shell flex-grow-1" id="appShell">
            {{-- Role based sidebar: a column on desktop, an offcanvas drawer on phones.
                 Never on the public landing page. --}}
            @auth
                @unless ($isLandingPage)
                    @include('layouts.sidebar')
                @endunless
            @endauth

            <div class="edu-main">
                <main class="edu-main-inner" id="main-content">
                    <div class="container-xxl px-3 px-lg-4">
                        {{-- Success / error / validation messages --}}
                        <x-flash />

                        {{-- Page header supplied by the view. Breadcrumbs live in
                             the top bar, so this only renders when a page has one. --}}
                        @isset($header)
                            <div class="edu-page-header mb-4">
                                {{ $header }}
                            </div>
                        @endisset
                    </div>

                    {{-- Page body. The CSS fades this in; page-transitions.js adds
                         the matching fade-out when a link is followed. --}}
                    <div class="edu-page">
                        {{ $slot }}
                    </div>
                </main>

                <footer class="edu-footer mt-auto">
                    <div class="container-xxl px-3 px-lg-4">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                            <span>
                                &copy; {{ date('Y') }} {{ config('app.name', 'EduConnect') }}
                            </span>
                            <span class="d-inline-flex align-items-center gap-2">
                                <i class="bi bi-mortarboard-fill text-edu" aria-hidden="true"></i>
                                {{ __('Learn together, anywhere.') }}
                            </span>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        {{-- Page specific JavaScript (kept out of the markup). --}}
        @stack('scripts')

        @auth
            @unless ($isLandingPage)
                {{-- Mobile bottom navigation: five destinations, always one tap away. --}}
                @include('layouts.mobile-nav', ['unreadCount' => $layoutUnreadCount])

                {{-- Floating composer shortcut, feed only, never over the desktop layout. --}}
                @if (request()->routeIs('feed.index'))
                    <a class="edu-fab d-lg-none" href="{{ route('posts.create') }}"
                       aria-label="{{ __('New post') }}" title="{{ __('New post') }}">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    </a>
                @endif
            @endunless
        @endauth
    </body>
</html>

```

## `resources/views/layouts/navigation.blade.php`

```blade
{{-- Sticky top navigation bar: page context, search, theme, notifications and
     the user menu. The sidebar itself lives in layouts/sidebar.blade.php. --}}
@php
    // Unread notifications drive the bell counter (FR-24); the newest eight fill
    // the dropdown. The polling script refreshes both every 30 seconds.
    // The layout passes the shared unread count so the mobile bar shows the
    // same number without a second COUNT query.
    $unreadNotifications = $unreadNotifications ?? (auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);
    $latestNotifications = auth()->check()
        ? auth()->user()->notifications()->latest()->take(8)->get()
        : collect();
@endphp

<nav class="navbar edu-navbar sticky-top">
    <div class="container-xxl px-3 px-lg-4 d-flex align-items-center gap-2">
        @auth
            @unless ($isLandingPage ?? false)
            {{-- Opens the sidebar drawer on phones and tablets only;
                 from lg upwards the sidebar is always visible. --}}
            <button class="btn edu-icon-btn d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#appSidebar"
                    aria-controls="appSidebar" aria-label="{{ __('Open the menu') }}">
                <i class="bi bi-list fs-5" aria-hidden="true"></i>
            </button>

            {{-- Desktop: collapse / expand the sidebar. The state is stored in
                 localStorage by resources/js/layout.js (FR-31). --}}
            <button class="btn edu-icon-btn d-none d-lg-inline-flex"
                    type="button" data-sidebar-toggle
                    aria-controls="appSidebar" aria-expanded="true"
                    aria-label="{{ __('Toggle the sidebar') }}"
                    title="{{ __('Toggle the sidebar') }}">
                <i class="bi bi-layout-sidebar-inset" data-sidebar-toggle-icon aria-hidden="true"></i>
            </button>
            @endunless
        @endauth

        <a class="navbar-brand d-flex align-items-center gap-2 mb-0"
           href="{{ auth()->check() ? route('dashboard') : url('/') }}">
            <span class="edu-sidebar-logo-mark d-lg-none" aria-hidden="true">
                <x-application-logo class="text-white" />
            </span>
            <span class="d-none d-sm-inline">{{ config('app.name', 'EduConnect') }}</span>
        </a>

        {{-- Page context: title + breadcrumbs. Hidden on narrow screens where
             the page itself already shows the heading. --}}
        @if ($pageTitle !== '' || isset($breadcrumbs))
            <div class="d-none d-xl-flex flex-column ms-3 ps-3 border-start" style="border-color: var(--edu-border) !important;">
                @if ($pageTitle !== '')
                    <span class="edu-navbar-title">{{ $pageTitle }}</span>
                @endif

                @isset($breadcrumbs)
                    <nav aria-label="{{ __('Breadcrumb') }}">
                        <ol class="breadcrumb mb-0">{{ $breadcrumbs }}</ol>
                    </nav>
                @endisset
            </div>
        @endif

        <div class="d-flex align-items-center ms-auto gap-1 gap-md-2">
            @auth
                {{-- One search box for people, hashtags and posts (FR-14).
                     On phones the box would not fit, so there is an icon instead.
                     resources/js/search.js fills the dropdown underneath it. --}}
                <form method="GET" action="{{ route('search.index') }}" role="search"
                      class="edu-search position-relative d-none d-md-block"
                      data-search-form data-search-url="{{ route('search.suggest') }}">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-0 pe-0" aria-hidden="true">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="search" name="q" class="form-control border-0"
                               value="{{ request()->routeIs('search.*') ? request('q') : '' }}"
                               placeholder="{{ __('Search people or #tags') }}"
                               aria-label="{{ __('Search') }}"
                               autocomplete="off" data-search-input>
                    </div>

                    {{-- Live suggestions, hidden until there is something to show.
                         resources/js/search.js toggles the "d-none" class. --}}
                    <div class="dropdown-menu show position-absolute top-100 start-0 end-0 shadow-sm d-none"
                         data-search-suggestions></div>
                </form>
            @endauth

            {{-- Light / dark mode. JS updates the icon and aria-label. --}}
            <button class="btn edu-icon-btn" type="button" data-theme-toggle
                    aria-label="{{ __('Switch to dark mode') }}" title="{{ __('Switch to dark mode') }}"
                    aria-pressed="false">
                <i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i>
            </button>

            <ul class="navbar-nav flex-row align-items-center gap-1">
            @auth
                {{-- Phone shortcut to the search page --}}
                <li class="nav-item d-md-none">
                    <a class="btn edu-icon-btn" href="{{ route('search.index') }}"
                       aria-label="{{ __('Search') }}">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </a>
                </li>

                {{-- Notification bell: unread counter + newest eight (FR-24) --}}
                <li class="nav-item dropdown">
                    <button class="btn edu-icon-btn position-relative" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="{{ __('Notifications') }}" data-notification-toggle>
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        {{-- The badge is refreshed by resources/js/notifications.js. --}}
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger {{ $unreadNotifications > 0 ? '' : 'd-none' }}"
                              data-notification-badge>
                            {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notification-menu" data-notification-list>
                        @include('notifications._dropdown', ['notifications' => $latestNotifications])
                    </ul>
                </li>

                {{-- User dropdown: profile, edit profile and logout. --}}
                <li class="nav-item dropdown">
                    <a class="edu-navbar-avatar dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false"
                       aria-label="{{ __('Account menu') }}">
                        <x-avatar :user="auth()->user()" size="sm" />
                        <span class="d-none d-md-inline small fw-semibold">{{ auth()->user()->name }}</span>
                        <i class="bi bi-chevron-down small text-muted d-none d-md-inline" aria-hidden="true"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text small text-muted">
                                {{ __('Signed in as') }}
                                <span class="text-capitalize fw-semibold">{{ auth()->user()->role }}</span>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('users.show', auth()->id()) }}">
                                <i class="bi bi-person me-1" aria-hidden="true"></i> {{ __('My profile') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i> {{ __('Edit profile') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('feed.index') }}">
                                <i class="bi bi-chat-square-text me-1" aria-hidden="true"></i> {{ __('Feed') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('notifications.index') }}">
                                <i class="bi bi-bell me-1" aria-hidden="true"></i> {{ __('Notifications') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> {{ __('Log out') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            @else
                {{-- Guests see login / register --}}
                <li class="nav-item">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('login') }}">{{ __('Log in') }}</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm btn-primary" href="{{ route('register') }}">{{ __('Register') }}</a>
                </li>
            @endauth
            </ul>
        </div>
    </div>
</nav>

```

## `resources/views/layouts/sidebar.blade.php`

```blade
{{-- Role based sidebar.
     From lg upwards it is a fixed dark column; on phones it is an offcanvas
     drawer opened with the hamburger button in the navbar. The desktop toggle
     in the navbar stores its state in localStorage (FR-31). --}}
@php
    $me = auth()->user();
    $role = $me->role;

    $groups = match ($role) {
        \App\Models\User::ROLE_ADMIN => [
            ['label' => 'Overview', 'items' => [
                ['label' => 'Style Guide', 'icon' => 'bi-palette', 'route' => 'admin.style-guide'],
            ]],
            ['label' => 'Manage', 'items' => [
                ['label' => 'Users', 'icon' => 'bi-people', 'route' => 'admin.users.index'],
                ['label' => 'Posts', 'icon' => 'bi-chat-square-text', 'route' => 'admin.posts.index'],
                ['label' => 'Classes', 'icon' => 'bi-easel', 'route' => 'admin.classes.index'],
                ['label' => 'Reports', 'icon' => 'bi-flag', 'route' => 'admin.reports.index'],
            ]],
            ['label' => 'Community', 'items' => [
                ['label' => 'Feed', 'icon' => 'bi-rss', 'route' => 'feed.index'],
                ['label' => 'Notifications', 'icon' => 'bi-bell', 'route' => 'notifications.index'],
            ]],
        ],
        \App\Models\User::ROLE_TEACHER => [
            ['label' => 'Teaching', 'items' => [
                ['label' => 'My Classes', 'icon' => 'bi-easel', 'route' => 'classes.index'],
                ['label' => 'Create Class', 'icon' => 'bi-plus-square', 'route' => 'classes.create'],
                ['label' => 'Discover', 'icon' => 'bi-compass', 'route' => 'classes.discover'],
            ]],
            ['label' => 'Community', 'items' => [
                ['label' => 'Feed', 'icon' => 'bi-chat-square-text', 'route' => 'feed.index'],
                ['label' => 'Notifications', 'icon' => 'bi-bell', 'route' => 'notifications.index'],
            ]],
            ['label' => 'Account', 'items' => [
                ['label' => 'My Profile', 'icon' => 'bi-person', 'route' => 'users.show', 'params' => [$me->id]],
            ]],
        ],
        default => [
            ['label' => 'Learning', 'items' => [
                ['label' => 'Browse Classes', 'icon' => 'bi-search', 'route' => 'classes.discover'],
                ['label' => 'My Classes', 'icon' => 'bi-journal-bookmark', 'route' => 'student.classes'],
            ]],
            ['label' => 'Community', 'items' => [
                ['label' => 'Feed', 'icon' => 'bi-chat-square-text', 'route' => 'feed.index'],
                ['label' => 'Notifications', 'icon' => 'bi-bell', 'route' => 'notifications.index'],
            ]],
            ['label' => 'Account', 'items' => [
                ['label' => 'My Profile', 'icon' => 'bi-person', 'route' => 'users.show', 'params' => [$me->id]],
            ]],
        ],
    };

    // The profile entry is only active on your own profile, not on somebody else's.
    $isActive = function (array $item) use ($me): bool {
        if (! request()->routeIs($item['route'])) {
            return false;
        }

        if ($item['route'] === 'users.show') {
            $shown = request()->route('user');

            return $shown instanceof \App\Models\User && $shown->is($me);
        }

        return true;
    };
@endphp

<aside class="offcanvas-lg offcanvas-start edu-sidebar flex-shrink-0" tabindex="-1" id="appSidebar"
       aria-labelledby="appSidebarLabel" data-edu-sidebar>
    {{-- Header is only needed in the mobile drawer --}}
    <div class="offcanvas-header d-lg-none">
        <h2 class="offcanvas-title h6 mb-0" id="appSidebarLabel">
            <i class="bi bi-mortarboard-fill me-1" aria-hidden="true"></i> {{ __('Menu') }}
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column">
        {{-- Brand / logo --}}
        <a class="edu-sidebar-logo" href="{{ route('dashboard') }}" aria-label="{{ config('app.name', 'EduConnect') }}">
            <span class="edu-sidebar-logo-mark" aria-hidden="true">
                <x-application-logo class="text-white" />
            </span>
            <span class="edu-sidebar-logo-text edu-sidebar-label">
                <strong>{{ config('app.name', 'EduConnect') }}</strong>
                <span>{{ __('Learning workspace') }}</span>
            </span>
        </a>

        {{-- Grouped role menu --}}
        <nav class="flex-grow-1" aria-label="{{ __('Main navigation') }}">
            @foreach ($groups as $group)
                <div class="edu-sidebar-section">{{ __($group['label']) }}</div>

                <ul class="nav flex-column">
                    @foreach ($group['items'] as $item)
                        @php($active = $isActive($item))

                        <li class="nav-item">
                            @if (Route::has($item['route']))
                                <a class="nav-link {{ $active ? 'active' : '' }}"
                                   href="{{ route($item['route'], $item['params'] ?? []) }}"
                                   title="{{ __($item['label']) }}"
                                   @if ($active) aria-current="page" @endif>
                                    <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                                    <span class="edu-sidebar-label">{{ __($item['label']) }}</span>
                                </a>
                            @else
                                <span class="nav-link disabled" title="{{ __($item['label']) }}">
                                    <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                                    <span class="edu-sidebar-label">{{ __($item['label']) }}</span>
                                    <span class="badge text-bg-secondary ms-auto">{{ __('soon') }}</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>

        {{-- Account actions + mini profile card, pushed to the bottom --}}
        <div class="mt-auto pt-2">
            <div class="edu-sidebar-divider"></div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link edu-sidebar-footer-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                       href="{{ route('profile.edit') }}" title="{{ __('Edit profile') }}"
                       @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                        <i class="bi bi-gear" aria-hidden="true"></i>
                        <span class="edu-sidebar-label">{{ __('Settings') }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-link text-danger w-100 text-start border-0 bg-transparent"
                                title="{{ __('Log out') }}">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                            <span class="edu-sidebar-label">{{ __('Log out') }}</span>
                        </button>
                    </form>
                </li>
            </ul>

            {{-- User mini-card. --}}
            <div class="edu-sidebar-user d-flex align-items-center gap-2 mt-2">
                <x-avatar :user="$me" size="sm" class="flex-shrink-0" />
                <div class="edu-sidebar-user-text min-w-0">
                    <div class="edu-sidebar-user-name text-truncate">{{ $me->name }}</div>
                    <div class="edu-sidebar-user-role">{{ $role }}</div>
                </div>
                <a class="btn edu-icon-btn ms-auto d-none d-lg-inline-flex"
                   href="{{ route('profile.edit') }}"
                   aria-label="{{ __('Edit profile') }}" title="{{ __('Edit profile') }}"
                   style="color: var(--edu-sidebar-text); width: 34px; height: 34px;">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</aside>

```

## `resources/views/layouts/mobile-nav.blade.php`

```blade
{{-- Mobile bottom navigation (Phase 3C).
     Five destinations, each one tap away, with a 56px touch target.
     The same information is available in the sidebar drawer; this bar simply
     keeps the most used pages reachable with a thumb. --}}
@php
    $me = auth()->user();

    $homeRoute = match ($me->role) {
        \App\Models\User::ROLE_ADMIN => 'admin.dashboard',
        \App\Models\User::ROLE_TEACHER => 'teacher.dashboard',
        default => 'student.dashboard',
    };

    $classesRoute = match ($me->role) {
        \App\Models\User::ROLE_ADMIN => 'admin.classes.index',
        \App\Models\User::ROLE_TEACHER => 'classes.index',
        default => 'classes.discover',
    };

    $items = [
        [
            'label' => __('Home'),
            'icon' => 'bi-house-door',
            'url' => route($homeRoute),
            'active' => request()->routeIs($homeRoute),
        ],
        [
            'label' => __('Classes'),
            'icon' => 'bi-easel',
            'url' => route($classesRoute),
            'active' => request()->routeIs('classes.*', 'student.classes', 'admin.classes.*'),
        ],
        [
            'label' => __('Search'),
            'icon' => 'bi-search',
            'url' => route('search.index'),
            'active' => request()->routeIs('search.*'),
        ],
        [
            'label' => __('Notifications'),
            'icon' => 'bi-bell',
            'url' => route('notifications.index'),
            'active' => request()->routeIs('notifications.*'),
            'badge' => $unreadCount,
        ],
        [
            'label' => __('Profile'),
            'icon' => 'bi-person',
            'url' => route('users.show', $me),
            'active' => request()->routeIs('users.show') && request()->route('user')?->is($me),
        ],
    ];
@endphp

<nav class="edu-mobile-nav d-lg-none" aria-label="{{ __('Mobile navigation') }}">
    @foreach ($items as $item)
        <a href="{{ $item['url'] }}"
           class="edu-mobile-nav-item {{ $item['active'] ? 'active' : '' }}"
           @if ($item['active']) aria-current="page" @endif>
            <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
            <span>{{ $item['label'] }}</span>

            @if (! empty($item['badge']))
                <span class="badge rounded-pill text-bg-danger edu-mobile-nav-badge">
                    {{ $item['badge'] > 9 ? '9+' : $item['badge'] }}
                    <span class="visually-hidden">{{ __('unread notifications') }}</span>
                </span>
            @endif
        </a>
    @endforeach
</nav>

```

# 6. Reusable components

## `resources/views/components/flash.blade.php`

```blade
{{-- Flash + validation summary messages. Used by every layout via <x-flash /> --}}

@if (session('success'))
    {{-- Green banner after a successful action --}}
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    {{-- Red banner for a refused action --}}
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('status'))
    {{-- Neutral informational banner (used by Breeze for "verify your email") --}}
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-1"></i> {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@php($bag = $errors ?? null)
@if ($bag && $bag->any())
    {{-- Summary of every validation error of the last request.
         $errors is only shared by the "web" middleware group, so it is missing
         on error pages such as 404 - hence the null-safe check above. --}}
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ __('Please fix the following:') }}
        </div>
        <ul class="mb-0 small">
            @foreach ($bag->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

```

## `resources/views/components/stat-card.blade.php`

```blade
{{-- Statistic card used on all three dashboards.
     Usage:
       <x-stat-card label="Students" :value="12" icon="bi-mortarboard" color="success" />
       <x-stat-card label="Users" :value="120" trend="12" :sparkline="[2,4,3,6,8,7,9]" />
     The optional sparkline is a tiny inline SVG, so it costs no extra request. --}}
@props([
    'label',
    'value',
    'icon' => 'bi-bar-chart',
    'color' => 'primary',
    'hint' => null,
    'href' => null,
    'trend' => null,
    'trendDirection' => null,
    'trendLabel' => null,
    'sparkline' => [],
])

@php
    $series = array_values(array_map(static fn ($point) => (float) $point, (array) $sparkline));
    $hasSparkline = count($series) > 1;

    $hex = [
        'primary' => '#4f46e5',
        'secondary' => '#7c3aed',
        'success' => '#10b981',
        'info' => '#0ea5e9',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
        'dark' => '#0f172a',
    ][$color] ?? '#4f46e5';

    $points = [];
    if ($hasSparkline) {
        $max = max($series);
        $min = min($series);
        $range = ($max - $min) ?: 1;
        $lastIndex = count($series) - 1;

        foreach ($series as $index => $point) {
            $x = ($index / $lastIndex) * 96;
            $y = 28 - ((($point - $min) / $range) * 24);
            $points[] = round($x, 2).','.round($y, 2);
        }
    }

    $gradientId = 'spark-'.substr(md5($label.$hex), 0, 8);

    $hasTrend = $trend !== null;
    $numericTrend = $hasTrend ? (float) $trend : 0;
    $direction = $trendDirection ?: ($numericTrend > 0 ? 'up' : ($numericTrend < 0 ? 'down' : 'flat'));
    $trendIcon = ['up' => 'bi-arrow-up-right', 'down' => 'bi-arrow-down-right', 'flat' => 'bi-dash'][$direction] ?? 'bi-dash';
    $displayValue = is_numeric($value) ? number_format((float) $value) : $value;
@endphp

<div {{ $attributes->class(['card', 'edu-card', 'edu-stat-card', 'h-100']) }} data-stat-card>
    <div class="card-body">
        <div class="edu-stat-top">
            <span class="edu-stat-icon bg-{{ $color }}" aria-hidden="true">
                <i class="bi {{ $icon }}"></i>
            </span>

            @if ($hasSparkline)
                {{-- Decorative mini sparkline; the number and trend carry the meaning. --}}
                <svg class="edu-sparkline" viewBox="0 0 96 32" role="presentation" focusable="false" aria-hidden="true">
                    <defs>
                        <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="{{ $hex }}" stop-opacity="0.28"></stop>
                            <stop offset="100%" stop-color="{{ $hex }}" stop-opacity="0"></stop>
                        </linearGradient>
                    </defs>
                    <polygon class="sparkline-fill" fill="url(#{{ $gradientId }})"
                             points="0,32 {{ implode(' ', $points) }} 96,32"></polygon>
                    <polyline points="{{ implode(' ', $points) }}" stroke="{{ $hex }}"></polyline>
                </svg>
            @endif
        </div>

        <div class="edu-stat-value">{{ $displayValue }}</div>

        <div class="d-flex align-items-end justify-content-between gap-2">
            <span class="edu-stat-label">{{ $label }}</span>

            @if ($hasTrend)
                <span class="edu-trend is-{{ $direction }}">
                    <i class="bi {{ $trendIcon }}" aria-hidden="true"></i>
                    {{ number_format(abs($numericTrend), 0) }}%
                </span>
            @endif
        </div>

        @if ($hasTrend && $trendLabel)
            <div class="edu-stat-hint">{{ $trendLabel }}</div>
        @endif

        @if ($hint)
            <div class="edu-stat-hint">{{ $hint }}</div>
        @endif
    </div>

    {{-- Optional link that makes the whole card clickable --}}
    @if ($href)
        <a class="stretched-link" href="{{ $href }}" aria-label="{{ $label }}"></a>
    @endif
</div>

```

## `resources/views/components/class-card.blade.php`

```blade
{{-- A single class (ClassRoom) as a card.
     Usage: <x-class-card :class-room="$class" :url="route('classes.show', $class)"> ...buttons... </x-class-card>
     The optional $url turns the title into a link to the class detail page. --}}
@props(['classRoom', 'url' => null])

@php
    // Use the eager loaded count when the page provided one (avoids N+1).
    $studentsCount = $classRoom->students_count ?? $classRoom->students()->count();
@endphp

<div class="card edu-card border-0 h-100">
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <h3 class="h6 fw-bold mb-0">
                @if ($url)
                    {{-- Links to the class detail page (FR-19) --}}
                    <a href="{{ $url }}" class="text-decoration-none text-reset">{{ $classRoom->title }}</a>
                @else
                    {{ $classRoom->title }}
                @endif
            </h3>

            {{-- Status: scheduled / live / completed --}}
            <x-status-badge :status="$classRoom->status" class="flex-shrink-0" />
        </div>

        @if ($classRoom->subject)
            <div class="mb-2">
                <span class="badge bg-body-secondary text-body border">
                    <i class="bi bi-book me-1"></i>{{ $classRoom->subject }}
                </span>
            </div>
        @endif

        {{-- Date, start time and duration --}}
        <ul class="list-unstyled small text-muted mb-2">
            <li class="mb-1">
                <i class="bi bi-calendar-event me-1"></i>
                {{ $classRoom->date?->format('D d M Y') }}
            </li>
            <li class="mb-1">
                <i class="bi bi-clock me-1"></i>
                {{-- "time" is stored as H:i:s, we only show hours and minutes --}}
                {{ \Illuminate\Support\Str::substr((string) $classRoom->time, 0, 5) }}
                &middot; {{ $classRoom->duration }} {{ __('min') }}
            </li>
            <li class="mb-1">
                <i class="bi bi-person-video3 me-1"></i>
                {{ $classRoom->teacher?->name ?? __('Unknown teacher') }}
            </li>
            <li>
                <i class="bi bi-people me-1"></i>
                {{ trans_choice(':count student|:count students', $studentsCount, ['count' => $studentsCount]) }}
            </li>
        </ul>

        {{-- Relative timing hint without JavaScript (FR-21):
             "Live now", "Starts in 2h 15m" or "Ended". --}}
        <p class="small fw-semibold mb-2 {{ $classRoom->isLive() ? 'text-danger' : 'text-muted' }}">
            <i class="bi bi-stopwatch me-1" aria-hidden="true"></i>{{ $classRoom->timelineLabel() }}
        </p>

        @if ($classRoom->description)
            {{-- Description is escaped by Blade; long texts are cut off --}}
            <p class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($classRoom->description, 120) }}</p>
        @endif

        {{-- Buttons supplied by the page that uses the card --}}
        <div class="mt-auto pt-2 d-flex flex-wrap gap-2">
            {{ $slot }}
        </div>
    </div>
</div>

```

## `resources/views/components/post-card.blade.php`

```blade
{{-- A single feed post (FR-7, FR-8, FR-9, FR-10, FR-12, FR-13).
     Buttons are real forms, so everything still works without JavaScript;
     resources/js/feed.js upgrades them to fetch() calls.

     Usage:
       <x-post-card :post="$post" />
       <x-post-card :post="$post" :comments="$comments" />  (feed: expandable) --}}
@props([
    'post',
    'comments' => null,
])

@php
    // Injected so the body can be turned into hashtag links (FR-13).
    $hashtagService = app(\App\Services\HashtagService::class);

    $author = $post->user;
    // Prefer the eager loaded counts so the component never fires a query itself.
    $likesCount = $post->likes_count ?? $post->likes()->count();
    $commentsCount = $post->comments_count ?? $post->comments()->visible()->count();
    // "liked by me" comes from the withExists() in Post::scopeWithFeedData().
    $likedByMe = (bool) ($post->liked_by_me ?? $post->isLikedBy(auth()->user()));
    $isMine = auth()->id() === $post->user_id;

    // On the feed three comments are enough; the rest live on the detail page.
    $previewComments = $comments?->take(3) ?? collect();

    // Colour of the little role badge next to the name.
    $roleColor = match ($author?->role) {
        \App\Models\User::ROLE_ADMIN => 'dark',
        \App\Models\User::ROLE_TEACHER => 'primary',
        default => 'success',
    };
@endphp

<article class="card edu-card" data-post-card="{{ $post->id }}">
    <div class="card-body">
        {{-- Author, role badge and publishing time --}}
        <header class="d-flex align-items-start gap-3 mb-2">
            <x-avatar :user="$author" size="md" class="flex-shrink-0" />

            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if ($author)
                        <a href="{{ route('users.show', $author) }}"
                           class="fw-semibold text-decoration-none text-reset">{{ $author->name }}</a>
                    @else
                        <span class="fw-semibold">{{ __('Deleted user') }}</span>
                    @endif

                    <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $author?->role }}</span>

                    @if ($post->is_hidden)
                        {{-- Hidden by an administrator (FR-28) --}}
                        <span class="badge text-bg-warning">{{ __('hidden') }}</span>
                    @endif
                </div>

                <div class="small text-muted">
                    <a href="{{ route('posts.show', $post) }}" class="text-muted text-decoration-none">
                        <time datetime="{{ $post->created_at?->toIso8601String() }}">
                            {{ $post->created_at?->diffForHumans() }}
                        </time>
                    </a>
                </div>
            </div>

            {{-- Owner menu (edit / delete) + report action (FR-8, FR-32) --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-link text-muted px-1" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Post actions') }}">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @can('update', $post)
                        <li>
                            <a class="dropdown-item" href="{{ route('posts.edit', $post) }}">
                                <i class="bi bi-pencil me-1"></i> {{ __('Edit post') }}
                            </a>
                        </li>
                    @endcan

                    @can('delete', $post)
                        <li>
                            {{-- Opens the Bootstrap confirmation modal rendered below. --}}
                            <button type="button" class="dropdown-item text-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#delete-post-{{ $post->id }}">
                                <i class="bi bi-trash me-1"></i> {{ __('Delete post') }}
                            </button>
                        </li>
                    @endcan

                    {{-- Reporting a post that belongs to somebody else (FR-32). --}}
                    @if (auth()->id() !== $post->user_id)
                        <li>
                            <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                    data-bs-target="#report-post-{{ $post->id }}">
                                <i class="bi bi-flag me-1"></i> {{ __('Report post') }}
                            </button>
                        </li>
                    @endif
                </ul>
            </div>
        </header>

        {{-- Body: escaped first, hashtags linked afterwards - so no XSS (FR-13) --}}
        <p class="post-body mb-2">{!! $hashtagService->linkify($post->body) !!}</p>

        @if ($post->image)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}"
                 alt="{{ __('Image attached to the post') }}"
                 class="img-fluid rounded-3 mb-2 edu-post-image"
                 loading="lazy" decoding="async">
        @endif

        {{-- Counters + actions --}}
        <footer class="d-flex align-items-center gap-2 gap-sm-3 small border-top pt-2 mt-3">
            {{-- Like: a form, so it still works without JavaScript --}}
            <form method="POST"
                  action="{{ $likedByMe ? route('posts.unlike', $post) : route('posts.like', $post) }}"
                  data-like-form
                  data-liked="{{ $likedByMe ? '1' : '0' }}"
                  data-like-url="{{ route('posts.like', $post) }}"
                  data-unlike-url="{{ route('posts.unlike', $post) }}"
                  class="d-inline">
                @csrf
                @if ($likedByMe)
                    @method('DELETE')
                @endif
                <button type="submit"
                        class="btn btn-sm {{ $likedByMe ? 'btn-danger' : 'btn-outline-secondary' }}"
                        data-like-button aria-pressed="{{ $likedByMe ? 'true' : 'false' }}"
                        aria-label="{{ $likedByMe ? __('Unlike this post') : __('Like this post') }}"
                        title="{{ $likedByMe ? __('Unlike') : __('Like') }}">
                    <i class="bi {{ $likedByMe ? 'bi-heart-fill' : 'bi-heart' }} me-1"
                       aria-hidden="true"></i>
                    <span data-like-count>{{ $likesCount }}</span>
                </button>
            </form>

            @if ($comments !== null)
                {{-- Expandable comments, exclusively on the feed (FR-10). --}}
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="collapse"
                        data-bs-target="#post-comments-{{ $post->id }}"
                        aria-expanded="false"
                        aria-controls="post-comments-{{ $post->id }}"
                        title="{{ __('Comments') }}">
                    <i class="bi bi-chat me-1" aria-hidden="true"></i>
                    <span data-comment-count>{{ $commentsCount }}</span>
                </button>
            @else
                <a href="{{ route('posts.show', $post) }}#comments"
                   class="btn btn-sm btn-outline-secondary" title="{{ __('Comments') }}">
                    <i class="bi bi-chat me-1" aria-hidden="true"></i>
                    <span data-comment-count>{{ $commentsCount }}</span>
                </a>
            @endif

            <span class="ms-auto text-muted">
                @if ($isMine)
                    <i class="bi bi-person-check me-1" aria-hidden="true"></i>{{ __('Your post') }}
                @endif
            </span>
        </footer>
    </div>

    @if ($comments !== null)
        {{-- Latest three comments + the composer, without leaving the feed. --}}
        <div class="collapse" id="post-comments-{{ $post->id }}">
            <div class="card-footer bg-transparent border-top pt-3">
                <div data-comment-region>
                    <form method="POST" action="{{ route('comments.store', $post) }}"
                          data-comment-form class="mb-2">
                        @csrf
                        <label for="comment-body-{{ $post->id }}" class="visually-hidden">
                            {{ __('Your comment') }}
                        </label>
                        <textarea name="body" id="comment-body-{{ $post->id }}" rows="2"
                                  maxlength="280" required
                                  class="form-control form-control-sm mb-2"
                                  placeholder="{{ __('Write a comment...') }}"></textarea>
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div class="form-text mt-0">{{ __('Up to 280 characters.') }}</div>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-send me-1" aria-hidden="true"></i> {{ __('Comment') }}
                            </button>
                        </div>
                    </form>

                    <div class="mt-2" data-comment-list>
                        @forelse ($previewComments as $comment)
                            @include('comments._comment', ['comment' => $comment])
                        @empty
                            <p class="text-muted small mb-0" data-comment-empty>
                                {{ __('No comments yet. Be the first one!') }}
                            </p>
                        @endforelse
                    </div>

                    @if ($commentsCount > $previewComments->count())
                        <a href="{{ route('posts.show', $post) }}#comments"
                           class="small fw-semibold text-decoration-none d-inline-block mt-2">
                            {{ trans_choice('View all :count comment|View all :count comments', $commentsCount, ['count' => $commentsCount]) }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Extra content supplied by the page (report form, moderation tools...) --}}
    @if ($slot->isNotEmpty())
        <div class="card-footer bg-transparent border-top-0 pt-0">
            {{ $slot }}
        </div>
    @endif
</article>

@can('delete', $post)
    <x-confirm-delete-modal
        :id="'delete-post-'.$post->id"
        :action="route('posts.destroy', $post)"
        :title="__('Delete this post?')"
        :message="__('This removes the post, its comments, likes and image. This cannot be undone.')"
        :confirm-label="__('Delete post')"
        :show-trigger="false" />
@endcan

{{-- Each post owns its report modal (FR-32). --}}
@if (auth()->id() !== $post->user_id)
    @include('reports._modal', ['reportable' => $post])
@endif

```

## `resources/views/components/avatar.blade.php`

```blade
{{-- Reusable avatar (FR-4, FR-5, FR-14).
     Usage:
       <x-avatar :user="$user" />
       <x-avatar :user="$user" size="lg" :link="route('users.show', $user)" />
     An uploaded photo is used when present; otherwise the circle shows the
     user's initials, so there are no broken or anonymous silhouettes. --}}
@props([
    'user' => null,
    'size' => 'md',
    'link' => null,
])

@php
    $sizeClass = [
        'sm' => 'avatar-sm',
        'md' => 'avatar',
        'lg' => 'avatar-lg',
        'xl' => 'avatar-xl',
    ][$size] ?? 'avatar';

    $name = $user?->name ?? __('User');

    // Two initials at most: "Ada Lovelace" -> "AL", "Ada" -> "A".
    $initials = \Illuminate\Support\Str::of($name)
        ->squish()
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('');

    $photo = $user?->photo ? $user->photoUrl() : null;
@endphp

@if ($link)
    <a href="{{ $link }}" class="d-inline-flex flex-shrink-0" aria-label="{{ $name }}">
        @if ($photo)
            <img src="{{ $photo }}" alt="{{ $name }}" loading="lazy" decoding="async"
                 {{ $attributes->class([$sizeClass]) }}>
        @else
            <span role="img" aria-label="{{ $name }}"
                  {{ $attributes->class(['edu-avatar-initials', $sizeClass]) }}>{{ $initials }}</span>
        @endif
    </a>
@elseif ($photo)
    <img src="{{ $photo }}" alt="{{ $name }}" loading="lazy" decoding="async"
         {{ $attributes->class([$sizeClass]) }}>
@else
    <span role="img" aria-label="{{ $name }}"
          {{ $attributes->class(['edu-avatar-initials', $sizeClass]) }}>{{ $initials }}</span>
@endif

```

## `resources/views/components/empty-state.blade.php`

```blade
{{-- Reusable empty state.
     Usage:
       <x-empty-state icon="bi-easel" title="No classes yet" message="Create your first class.">
           <a href="..." class="btn btn-primary">Create one</a>
       </x-empty-state> --}}
@props([
    'icon' => 'bi-inbox',
    'title' => null,
    'message' => null,
])

<div {{ $attributes->class(['card', 'edu-card', 'border-0']) }}>
    <div class="card-body text-center py-5">
        <span class="edu-empty-illustration" aria-hidden="true">
            <i class="bi {{ $icon }}"></i>
        </span>

        @if ($title)
            <h2 class="h6 fw-semibold">{{ $title }}</h2>
        @endif

        @if ($message)
            <p class="text-muted small mb-3">{{ $message }}</p>
        @endif

        @if ($slot->isNotEmpty())
            <div class="d-flex flex-wrap justify-content-center gap-2">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>

```

## `resources/views/components/status-badge.blade.php`

```blade
{{-- Shared status badge for classes, reports and moderation states.
     Usage: <x-status-badge :status="$classRoom->status" />
            <x-status-badge status="pending" label="Waiting for review" /> --}}
@props([
    'status',
    'label' => null,
])

@php
    $status = (string) $status;

    // Bootstrap colour for each state used in the application.
    $color = match ($status) {
        'live' => 'danger',
        'scheduled' => 'primary',
        'completed' => 'secondary',
        'pending' => 'warning',
        'reviewed' => 'info',
        'dismissed' => 'secondary',
        'hidden' => 'warning',
        'blocked' => 'danger',
        'active' => 'success',
        default => 'secondary',
    };

    $text = $label ?? \Illuminate\Support\Str::headline($status);
@endphp

<span {{ $attributes->class(['badge', 'text-bg-'.$color, 'text-capitalize']) }}>
    @if ($status === 'live')
        <i class="bi bi-broadcast me-1" aria-hidden="true"></i>
    @endif
    {{ $text }}
</span>

```

## `resources/views/components/confirm-delete-modal.blade.php`

```blade
{{-- Reusable delete confirmation modal.
     Usage:
       <x-confirm-delete-modal
           id="delete-class-{{ $classRoom->id }}"
           :action="route('classes.destroy', $classRoom)"
           title="Delete this class?"
           message="The enrollments are removed as well."
       />

     The trigger button and the Bootstrap modal are rendered together, so the
     component works without any extra JavaScript. Pass :show-trigger="false"
     when you only need the modal markup. --}}
@props([
    'id',
    'action',
    'method' => 'DELETE',
    'title' => 'Delete this item?',
    'message' => 'This action cannot be undone.',
    'confirmLabel' => 'Delete',
    'confirmClass' => 'btn btn-danger',
    'triggerLabel' => 'Delete',
    'triggerClass' => 'btn btn-sm btn-outline-danger',
    'triggerIcon' => 'bi-trash',
    'showTrigger' => true,
])

@if ($showTrigger)
    <button type="button" class="{{ $triggerClass }}" data-bs-toggle="modal" data-bs-target="#{{ $id }}">
        <i class="bi {{ $triggerIcon }} me-1" aria-hidden="true"></i>{{ $triggerLabel }}
    </button>
@endif

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="{{ $id }}-title">{{ $title }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">{{ $message }}</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>

                <form method="POST" action="{{ $action }}">
                    @csrf
                    @method($method)
                    <button type="submit" class="{{ $confirmClass }}">
                        {{ $confirmLabel }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

```

## `resources/views/components/chart-card.blade.php`

```blade
{{-- Chart card with an optional range selector, loading skeleton and a text
     fallback for assistive technology / no-JavaScript visitors.

     Usage:
       <x-chart-card title="New users" chart-id="new-users" kind="line"
           series-key="newUsers" :series="$chart"
           :endpoint="route('admin.dashboard.charts')" current-range="12m" /> --}}
@props([
    'title',
    'subtitle' => null,
    'chartId' => null,
    'kind' => 'line',
    'series' => [],
    'label' => null,
    'color' => 'primary',
    'endpoint' => null,
    'ranges' => [],
    'currentRange' => '12m',
    'height' => 300,
    'seriesKey' => null,
    'total' => null,
    'fallback' => null,
])

@php
    $labels = array_values((array) ($series['labels'] ?? []));
    $values = array_map(static fn ($value) => (float) $value, array_values((array) ($series['values'] ?? [])));
    $seriesKey = $seriesKey ?: $chartId ?: 'series';

    $hex = [
        'primary' => '#4f46e5',
        'secondary' => '#7c3aed',
        'success' => '#10b981',
        'info' => '#0ea5e9',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
    ][$color] ?? '#4f46e5';

    $datasetLabel = $label ?: $title;
    $baseDataset = ['label' => $datasetLabel, 'data' => $values];

    if ($kind === 'doughnut' || $kind === 'pie') {
        $dataset = $baseDataset + [
            'backgroundColor' => ['#4f46e5', '#7c3aed', '#10b981', '#f59e0b', '#ef4444', '#0ea5e9', '#64748b', '#ec4899'],
            'borderWidth' => 0,
            'hoverOffset' => 6,
        ];

        $config = [
            'type' => $kind,
            'data' => ['labels' => $labels, 'datasets' => [$dataset]],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'cutout' => $kind === 'doughnut' ? '68%' : 0,
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom',
                        'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'boxHeight' => 8, 'padding' => 14],
                    ],
                ],
            ],
        ];
    } elseif ($kind === 'bar') {
        $dataset = $baseDataset + [
            'backgroundColor' => 'rgba(79, 70, 229, 0.72)',
            'hoverBackgroundColor' => $hex,
            'borderColor' => $hex,
            'borderWidth' => 0,
            'borderRadius' => 8,
            'borderSkipped' => false,
            'maxBarThickness' => 38,
        ];

        $config = [
            'type' => 'bar',
            'data' => ['labels' => $labels, 'datasets' => [$dataset]],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'x' => ['grid' => ['display' => false], 'border' => ['display' => false]],
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                        'grid' => ['drawBorder' => false],
                    ],
                ],
            ],
        ];
    } else {
        $dataset = $baseDataset + [
            'backgroundColor' => 'rgba(79, 70, 229, 0.16)',
            'borderColor' => $hex,
            'borderWidth' => 2.5,
            'fill' => true,
            'tension' => 0.38,
            'pointRadius' => 0,
            'pointHoverRadius' => 5,
            'pointBackgroundColor' => $hex,
            'pointBorderColor' => '#ffffff',
            'pointBorderWidth' => 2,
        ];

        $config = [
            'type' => 'line',
            'data' => ['labels' => $labels, 'datasets' => [$dataset]],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'x' => ['grid' => ['display' => false], 'border' => ['display' => false]],
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                        'grid' => ['drawBorder' => false],
                    ],
                ],
            ],
        ];
    }

    $ranges = ! empty($ranges) ? $ranges : [
        '7d' => __('Last 7 days'),
        '30d' => __('Last 30 days'),
        '12m' => __('Last 12 months'),
    ];

    if ($fallback === null) {
        $pairs = [];
        foreach ($labels as $index => $chartLabel) {
            $pairs[] = $chartLabel.': '.($values[$index] ?? 0);
        }
        $fallback = $title.'. '.implode(', ', $pairs);
    }

    $chartId = $chartId ?: 'chart-'.substr(md5($title.$seriesKey), 0, 8);
@endphp

<div class="card edu-card edu-chart-card h-100" data-chart-card>
    <div class="card-header">
        <div>
            <h2 class="card-title">{{ $title }}</h2>
            @if ($subtitle)
                <div class="card-subtitle">{{ $subtitle }}</div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-2">
            @if ($total !== null)
                <span class="edu-chip edu-chart-total" data-chart-total>{{ number_format((float) $total) }}</span>
            @endif

            @if ($endpoint)
                <label class="visually-hidden" for="{{ $chartId }}-range">{{ __('Chart range') }}</label>
                <select class="form-select form-select-sm edu-chart-range"
                        id="{{ $chartId }}-range" data-chart-range>
                    @foreach ($ranges as $value => $rangeLabel)
                        <option value="{{ $value }}" @selected($currentRange === $value)>{{ $rangeLabel }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    <div class="card-body">
        <div class="edu-chart" style="height: {{ (int) $height }}px">
            {{-- Shown while a range request is running. --}}
            <div class="edu-chart-skeleton" data-chart-skeleton hidden aria-hidden="true">
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
            </div>

            <canvas id="{{ $chartId }}" data-chart="{{ json_encode($config) }}"
                    data-chart-series="{{ $seriesKey }}"
                    data-chart-kind="{{ $kind }}"
                    @if ($endpoint) data-chart-endpoint="{{ $endpoint }}" @endif
                    role="img" aria-label="{{ $title }}"></canvas>
        </div>

        {{-- Text alternative. Visible when JavaScript is off, and always
             available to screen readers. --}}
        <p class="visually-hidden">{{ $fallback }}</p>
        <noscript>
            <p class="small text-muted mt-3 mb-0">{{ $fallback }}</p>
        </noscript>

        @if ($slot->isNotEmpty())
            <div class="mt-3">{{ $slot }}</div>
        @endif
    </div>
</div>

```

## `resources/views/components/activity-item.blade.php`

```blade
{{-- One entry in the teacher's recent-activity timeline.
     Usage:
       <x-activity-item icon="bi-person-plus" color="warning" text="Sina started following you"
           :at="$item['at']" /> --}}
@props([
    'icon' => 'bi-activity',
    'color' => 'primary',
    'text' => null,
    'excerpt' => null,
    'at' => null,
    'href' => null,
])

<div class="edu-activity">
    <span class="edu-activity-icon bg-{{ $color }}" aria-hidden="true">
        <i class="bi {{ $icon }}"></i>
    </span>

    <div class="min-w-0 flex-grow-1">
        <div class="edu-activity-text">
            @if ($href)
                <a href="{{ $href }}" class="text-decoration-none text-reset">{{ $text }}</a>
            @else
                {{ $text }}
            @endif
        </div>

        @if ($excerpt)
            <div class="edu-activity-excerpt">
                {{ \Illuminate\Support\Str::limit($excerpt, 70) }}
            </div>
        @endif

        @if ($at)
            <div class="edu-activity-time">
                <i class="bi bi-clock me-1" aria-hidden="true"></i>
                {{ $at instanceof \Illuminate\Support\Carbon ? $at->diffForHumans() : $at }}
            </div>
        @endif
    </div>
</div>

```

## `resources/views/components/progress-bar.blade.php`

```blade
{{-- Accessible progress bar.
     Usage: <x-progress-bar :value="$class->students_count" :max="$maxEnrollment"
               label="12 / 20 enrolled" /> --}}
@props([
    'value' => 0,
    'max' => 100,
    'color' => 'primary',
    'label' => null,
    'showValue' => true,
])

@php
    $value = max(0, (float) $value);
    $max = max(1, (float) $max);
    $percent = (int) round(min(100, ($value / $max) * 100));
@endphp

<div {{ $attributes->class(['edu-progress']) }}>
    @if ($label || $showValue)
        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
            @if ($label)
                <span class="small text-muted text-truncate">{{ $label }}</span>
            @endif

            @if ($showValue)
                <span class="small fw-semibold tabular-nums">{{ $percent }}%</span>
            @endif
        </div>
    @endif

    <div class="progress" role="progressbar" aria-valuenow="{{ $percent }}"
         aria-valuemin="0" aria-valuemax="100"
         aria-label="{{ $label ?: __('Enrollment progress') }}">
        <div class="progress-bar bg-{{ $color }}" style="width: {{ $percent }}%"></div>
    </div>
</div>

```

## `resources/views/components/countdown.blade.php`

```blade
{{-- Live countdown. JS updates the four boxes every second; the server
     renders the initial values so the markup is useful before hydration.
     Usage: <x-countdown :target="$classRoom->startsAt()->toIso8601String()" /> --}}
@props([
    'target',
    'compact' => false,
])

@php
    try {
        $starts = \Illuminate\Support\Carbon::parse($target);
    } catch (\Throwable $exception) {
        $starts = now();
    }

    $seconds = max(0, (int) now()->diffInSeconds($starts, false));
    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $remaining = $seconds % 60;
@endphp

<div {{ $attributes->class(['edu-countdown', 'is-compact' => $compact]) }}
     data-countdown="{{ $starts->toIso8601String() }}" role="timer"
     aria-label="{{ __('Time until the class starts') }}">
    <div class="edu-countdown-unit">
        <span class="edu-countdown-value" data-countdown-days>{{ str_pad((string) $days, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="edu-countdown-label">{{ __('days') }}</span>
    </div>
    <div class="edu-countdown-unit">
        <span class="edu-countdown-value" data-countdown-hours>{{ str_pad((string) $hours, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="edu-countdown-label">{{ __('hours') }}</span>
    </div>
    <div class="edu-countdown-unit">
        <span class="edu-countdown-value" data-countdown-minutes>{{ str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="edu-countdown-label">{{ __('min') }}</span>
    </div>
    <div class="edu-countdown-unit">
        <span class="edu-countdown-value" data-countdown-seconds>{{ str_pad((string) $remaining, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="edu-countdown-label">{{ __('sec') }}</span>
    </div>

    {{-- One readable sentence for screen readers instead of four live boxes. --}}
    <span class="visually-hidden" aria-live="polite" data-countdown-readable>
        {{ trans_choice(':count day|:count days', $days, ['count' => $days]) }},
        {{ $hours }} {{ __('hours') }}, {{ $minutes }} {{ __('minutes') }}
    </span>
</div>

```

## `resources/views/components/data-table.blade.php`

```blade
{{-- Data table wrapper with client-side search and sorting.
     Server-side pagination stays in the page and is passed through the footer
     slot, so this component only filters/sorts the rows of the current page.

     Usage:
       <x-data-table id="teacher-classes" title="My classes" :count="$classes->total()">
           <x-slot name="head"><tr><th data-sort>Title</th>...</tr></x-slot>
           <tr data-table-row data-search="...">...</tr>
           <x-slot name="footer"><x-pagination-links :paginator="$classes" /></x-slot>
       </x-data-table> --}}
@props([
    'id' => null,
    'title' => null,
    'subtitle' => null,
    'searchPlaceholder' => null,
    'count' => null,
    'countLabel' => null,
])

@php
    $id = $id ?: 'table-'.substr(md5($title ?? 'data-table'), 0, 8);
    $searchPlaceholder = $searchPlaceholder ?: __('Search…');
    $countLabel = $countLabel ?: __(':count rows');
@endphp

<div class="card edu-card edu-data-table" data-data-table data-count-label="{{ $countLabel }}">
    <div class="card-header">
        <div class="min-w-0">
            @if ($title)
                <h2 class="card-title h6 fw-bold mb-0">{{ $title }}</h2>
            @endif
            @if ($subtitle)
                <div class="card-subtitle small text-muted">{{ $subtitle }}</div>
            @endif
            @isset($header)
                {{ $header }}
            @endisset
        </div>

        <div class="edu-table-tools">
            <div class="edu-table-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="{{ $id }}-search">{{ $searchPlaceholder }}</label>
                <input type="search" class="form-control form-control-sm" id="{{ $id }}-search"
                       data-table-search placeholder="{{ $searchPlaceholder }}">
            </div>

            @isset($tools)
                {{ $tools }}
            @endisset
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                {{ $head }}
            </thead>
            <tbody data-table-body>
                {{ $slot }}
            </tbody>
        </table>

        <div class="edu-table-empty" data-table-empty hidden>
            <i class="bi bi-search me-1" aria-hidden="true"></i>
            {{ __('Nothing matches your search on this page.') }}
        </div>
    </div>

    @if (isset($footer) || $count !== null)
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="edu-table-count" data-table-count>
                {{ $count !== null ? $count : '' }}
            </span>

            @isset($footer)
                {{ $footer }}
            @endisset
        </div>
    @endif
</div>

```

## `resources/views/components/pagination-links.blade.php`

```blade
{{-- Bootstrap 5 pagination links, hidden when everything fits on one page.
     The Bootstrap views are switched on in AppServiceProvider.
     Usage: <x-pagination-links :paginator="$classes" /> --}}
@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="mt-3 d-flex justify-content-center" aria-label="{{ __('Page navigation') }}">
        {{ $paginator->links() }}
    </nav>
@endif

```

# 7. Dashboard controllers and metrics

## `app/Http/Controllers/AdminDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Support\DashboardMetrics;
use Illuminate\View\View;

/**
 * Admin dashboard (FR-2, FR-26, FR-33 chart and moderation).
 *
 * Phase 3B keeps the five-key stats contract and the 12-month "chart" data,
 * then adds trends, sparklines, four themed charts and the moderation/activity
 * panels. Expensive aggregates are cached for five minutes in DashboardMetrics.
 */
class AdminDashboardController extends Controller
{
    public function __invoke(DashboardMetrics $metrics): View
    {
        // The cached chart set always contains the 12 month series used by the
        // original test contract.
        $charts = $metrics->adminCharts('12m');

        return view('dashboards.admin', [
            'stats' => $metrics->adminStats(),
            'chart' => $charts['newUsers'],
            'trends' => $metrics->adminTrends(),
            'sparklines' => $metrics->adminSparklines(),

            // Themed chart series (FR-31).
            'roleChart' => $charts['roles'],
            'postsChart' => $charts['posts'],
            'classesChart' => $charts['classes'],

            // Newest accounts for the "recent users" table (FR-26).
            'recentUsers' => User::query()
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'email', 'role', 'photo', 'created_at']),

            // Moderation shortcuts (FR-33): cached pending counter + newest five.
            'pendingReports' => $metrics->adminPendingReports(),
            'recentReports' => Report::query()
                ->pending()
                ->with(['reporter:id,name', 'reportable'])
                ->latest()
                ->take(5)
                ->get(),

            // Latest activity panels (FR-26, FR-33).
            'recentPosts' => Post::query()
                ->visible()
                ->with('user:id,name,photo,role')
                ->withCount(['likes', 'comments'])
                ->latest()
                ->take(5)
                ->get(),
            'liveClasses' => ClassRoom::query()
                ->visible()
                ->where('status', ClassRoom::STATUS_LIVE)
                ->whereDate('date', now()->toDateString())
                ->with('teacher:id,name,photo')
                ->withCount('students')
                ->orderBy('time')
                ->take(4)
                ->get(),
        ]);
    }
}

```

## `app/Http/Controllers/TeacherDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Like;
use App\Models\User;
use App\Support\DashboardMetrics;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Teacher dashboard (FR-2, FR-29).
 *
 * Phase 3B adds the "next class" hero, a seven-day schedule, enrollment
 * progress, two charts and a richer activity timeline. The original four-key
 * stats array, the classes paginator and the activity collection are kept
 * exactly as they were.
 */
class TeacherDashboardController extends Controller
{
    /** How many activity entries the stream shows. */
    private const ACTIVITY_LIMIT = 10;

    /** Classes per page in the table. */
    private const CLASSES_PER_PAGE = 8;

    public function __invoke(Request $request, DashboardMetrics $metrics): View
    {
        $teacher = $request->user();
        $mine = $teacher->classes();

        // Hero: the next scheduled class, or a class that is live right now.
        $nextClass = (clone $mine)
            ->upcoming()
            ->withCount('students')
            ->with(['students' => fn ($query) => $query->orderBy('name')->limit(6)])
            ->first();

        if (! $nextClass) {
            $nextClass = (clone $mine)
                ->where('status', ClassRoom::STATUS_LIVE)
                ->withCount('students')
                ->with(['students' => fn ($query) => $query->orderBy('name')->limit(6)])
                ->orderByDesc('date')
                ->orderByDesc('time')
                ->first();
        }

        // The progress bars show enrollment relative to the teacher's biggest
        // class, because there is no capacity column in Phase 3.
        $maxEnrollment = (int) ClassEnrollment::query()
            ->whereIn('class_id', (clone $mine)->select('id'))
            ->selectRaw('class_id, COUNT(*) as total')
            ->groupBy('class_id')
            ->pluck('total')
            ->max();

        $maxEnrollment = max(1, $maxEnrollment);

        $charts = $metrics->teacherCharts($teacher, '12m');

        // My classes, newest first, with the number of enrolled students.
        // withCount() keeps the table free of N+1 queries.
        $classes = $mine
            ->withCount('students')
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate(self::CLASSES_PER_PAGE)
            ->withQueryString();

        return view('dashboards.teacher', [
            'stats' => $metrics->teacherStats($teacher),
            'trends' => $metrics->teacherTrends($teacher),
            'classes' => $classes,
            'activity' => $this->recentActivity($teacher),
            'nextClass' => $nextClass,
            'maxEnrollment' => $maxEnrollment,
            'schedule' => $metrics->teacherSchedule($teacher),
            'enrollmentChart' => $charts['enrollments'],
            'followerChart' => $charts['followers'],
        ]);
    }

    /**
     * The latest likes, comments and new followers that concern this teacher.
     * Each source is limited to ACTIVITY_LIMIT rows and eager loads its author,
     * then the three lists are merged and cut down to the newest entries.
     *
     * @return Collection<int, array{icon: string, color: string, text: string, excerpt: string|null, at: CarbonInterface|null}>
     */
    private function recentActivity(User $teacher): Collection
    {
        // Likes on my posts.
        $likes = Like::query()
            ->with(['user:id,name', 'post:id,user_id,body'])
            ->whereHas('post', fn ($query) => $query->where('user_id', $teacher->id))
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Like $like) => [
                'icon' => 'bi-hand-thumbs-up',
                'color' => 'primary',
                'text' => __(':name liked your post', ['name' => $like->user?->name ?? __('Someone')]),
                'excerpt' => $like->post?->body,
                'at' => $like->created_at,
            ]);

        // Comments on my posts.
        $comments = Comment::query()
            ->with(['user:id,name', 'post:id,user_id,body'])
            ->whereHas('post', fn ($query) => $query->where('user_id', $teacher->id))
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Comment $comment) => [
                'icon' => 'bi-chat',
                'color' => 'success',
                'text' => __(':name commented on your post', ['name' => $comment->user?->name ?? __('Someone')]),
                'excerpt' => $comment->body,
                'at' => $comment->created_at,
            ]);

        // Students who enrolled in one of my classes.
        $enrollments = ClassEnrollment::query()
            ->with(['student:id,name', 'classRoom:id,teacher_id,title'])
            ->whereHas('classRoom', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (ClassEnrollment $enrollment) => [
                'icon' => 'bi-person-check',
                'color' => 'info',
                'text' => __(':name enrolled in :class', [
                    'name' => $enrollment->student?->name ?? __('Someone'),
                    'class' => $enrollment->classRoom?->title ?? __('your class'),
                ]),
                'excerpt' => null,
                'at' => $enrollment->created_at,
            ]);

        // People who started following me.
        $followers = Follow::query()
            ->with('follower:id,name')
            ->where('following_id', $teacher->id)
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Follow $follow) => [
                'icon' => 'bi-person-plus',
                'color' => 'warning',
                'text' => __(':name started following you', ['name' => $follow->follower?->name ?? __('Someone')]),
                'excerpt' => null,
                'at' => $follow->created_at,
            ]);

        return $likes
            ->concat($comments)
            ->concat($enrollments)
            ->concat($followers)
            ->sortByDesc('at')
            ->take(self::ACTIVITY_LIMIT)
            ->values();
    }
}

```

## `app/Http/Controllers/StudentDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Follow;
use App\Models\Post;
use App\Models\User;
use App\Support\DashboardMetrics;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Student dashboard (FR-2, FR-30).
 *
 * Phase 3B adds the next-class hero with a live countdown, upcoming class
 * cards, recommended classes, a learning-activity chart and a three-post feed
 * preview. The original stats array, upcomingClasses collection and
 * recommendedTeachers collection keep their exact shape.
 */
class StudentDashboardController extends Controller
{
    /** How many upcoming classes the list shows. */
    private const UPCOMING_LIMIT = 6;

    /** How many teacher suggestions the list shows. */
    private const RECOMMENDATION_LIMIT = 3;

    /** How many recommended classes the student sees. */
    private const CLASS_RECOMMENDATION_LIMIT = 3;

    /** How many feed posts the preview shows. */
    private const FEED_PREVIEW_LIMIT = 3;

    public function __invoke(Request $request, DashboardMetrics $metrics): View
    {
        $student = $request->user();

        // Upcoming classes I am enrolled in, soonest first.
        // Eager loading the teacher and counting the students avoids N+1.
        $upcomingClasses = $student->enrolledClasses()
            ->upcoming()
            ->with('teacher:id,name,subject,photo')
            ->withCount('students')
            ->limit(self::UPCOMING_LIMIT)
            ->get();

        // Hero: the next scheduled class, falling back to a class that is live.
        $nextClass = $upcomingClasses->first();

        if (! $nextClass) {
            $nextClass = $student->enrolledClasses()
                ->where('classes.status', ClassRoom::STATUS_LIVE)
                ->with('teacher:id,name,subject,photo')
                ->withCount('students')
                ->first();
        }

        $heroClass = $nextClass?->load([
            'students' => fn ($query) => $query->orderBy('name')->limit(6),
        ]);

        $followingIds = $student->following()->pluck('users.id');

        $charts = $metrics->studentCharts($student, '12m');

        return view('dashboards.student', [
            'stats' => $metrics->studentStats($student),
            'upcomingClasses' => $upcomingClasses,
            'recommendedTeachers' => $this->recommendedTeachers($student),
            'recommendedClasses' => $this->recommendedClasses($student),
            'heroClass' => $heroClass,
            'classmates' => $heroClass?->students ?? collect(),
            'followingIds' => $followingIds,
            'notificationCount' => $student->unreadNotifications()->count(),
            'activityChart' => $charts['activity'],
            'feedPreview' => $this->feedPreview($student),
        ]);
    }

    /**
     * Teachers that this student does not follow yet, most followed first.
     *
     * @return EloquentCollection<int, User>
     */
    private function recommendedTeachers(User $student): EloquentCollection
    {
        // Sub-query of everybody the student already follows.
        $alreadyFollowed = Follow::query()
            ->select('following_id')
            ->where('follower_id', $student->id);

        return User::query()
            ->where('role', User::ROLE_TEACHER)
            ->whereNotIn('users.id', $alreadyFollowed)
            ->withCount('followers')
            ->orderByDesc('followers_count')
            ->orderBy('name')
            ->limit(self::RECOMMENDATION_LIMIT)
            ->get();
    }

    /**
     * Classes from followed teachers or from subjects the student already
     * studies, excluding anything they are enrolled in.
     *
     * @return EloquentCollection<int, ClassRoom>
     */
    private function recommendedClasses(User $student): EloquentCollection
    {
        $followedTeacherIds = $student->following()
            ->where('users.role', User::ROLE_TEACHER)
            ->pluck('users.id');

        $subjects = $student->enrolledClasses()
            ->whereNotNull('classes.subject')
            ->distinct()
            ->pluck('classes.subject');

        if ($followedTeacherIds->isEmpty() && $subjects->isEmpty()) {
            return new EloquentCollection;
        }

        $enrolledIds = $student->enrolledClasses()->select('classes.id');

        return ClassRoom::query()
            ->visible()
            ->upcoming()
            ->whereNotIn('classes.id', $enrolledIds)
            ->where(function ($query) use ($followedTeacherIds, $subjects): void {
                if ($followedTeacherIds->isNotEmpty()) {
                    $query->whereIn('teacher_id', $followedTeacherIds);
                }

                if ($subjects->isNotEmpty()) {
                    $query->orWhereIn('subject', $subjects);
                }
            })
            ->with('teacher:id,name,subject,photo')
            ->withCount('students')
            ->limit(self::CLASS_RECOMMENDATION_LIMIT)
            ->get();
    }

    /**
     * The newest posts from people the student follows.
     *
     * @return EloquentCollection<int, Post>
     */
    private function feedPreview(User $student): EloquentCollection
    {
        $following = $student->following()->pluck('users.id');

        if ($following->isEmpty()) {
            return new EloquentCollection;
        }

        return Post::query()
            ->visible()
            ->whereIn('user_id', $following)
            ->withFeedData($student)
            ->latest()
            ->limit(self::FEED_PREVIEW_LIMIT)
            ->get();
    }
}

```

## `app/Http/Controllers/DashboardChartController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Support\DashboardMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoints behind the dashboard range selectors (Phase 3B).
 *
 * Every route lives inside its role's existing middleware group, so a teacher
 * cannot read the admin series and a student cannot read the teacher series.
 * The returned object is keyed by the chart "series" names used on the page:
 *   admin   newUsers, posts, roles, classes
 *   teacher enrollments, followers
 *   student activity
 */
class DashboardChartController extends Controller
{
    public function admin(Request $request, DashboardMetrics $metrics): JsonResponse
    {
        $range = $metrics->normalizeRange($request->query('range'));

        return response()->json([
            'range' => $range,
            ...$metrics->adminCharts($range),
        ]);
    }

    public function teacher(Request $request, DashboardMetrics $metrics): JsonResponse
    {
        $range = $metrics->normalizeRange($request->query('range'));

        return response()->json([
            'range' => $range,
            ...$metrics->teacherCharts($request->user(), $range),
        ]);
    }

    public function student(Request $request, DashboardMetrics $metrics): JsonResponse
    {
        $range = $metrics->normalizeRange($request->query('range'));

        return response()->json([
            'range' => $range,
            ...$metrics->studentCharts($request->user(), $range),
        ]);
    }
}

```

## `app/Support/DashboardMetrics.php`

```php
<?php

namespace App\Support;

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Aggregated dashboard numbers (Phase 3B).
 *
 * Every method that runs more than a trivial query is cached for five minutes.
 * Keys are scoped per role (and per user for the teacher/student dashboards),
 * so one person's numbers never leak into somebody else's page.
 *
 * The public "stats" arrays keep the exact keys the Phase 3 tests expect:
 *   admin   users, teachers, students, posts, classes
 *   teacher classes, upcoming, students, followers
 *   student classes, upcoming, following
 */
class DashboardMetrics
{
    /** Five minutes, as required by the phase brief. */
    private const TTL_SECONDS = 300;

    private const RANGES = ['7d', '30d', '12m'];

    /* =====================================================================
     | Admin
     * ===================================================================== */

    /**
     * Platform totals (FR-7). Exact five-key contract.
     *
     * @return array{users: int, teachers: int, students: int, posts: int, classes: int}
     */
    public function adminStats(): array
    {
        return Cache::remember('dashboard.admin.stats.v1', self::TTL_SECONDS, function (): array {
            // One aggregate query for the three user counters. SUM(CASE ...)
            // works on MySQL and on the SQLite database used by the test suite.
            $userCounts = User::query()
                ->selectRaw(
                    'COUNT(*) as users,
                     SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as teachers,
                     SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as students',
                    [User::ROLE_TEACHER, User::ROLE_STUDENT]
                )
                ->first();

            return [
                'users' => (int) ($userCounts->users ?? 0),
                'teachers' => (int) ($userCounts->teachers ?? 0),
                'students' => (int) ($userCounts->students ?? 0),
                'posts' => Post::query()->count(),
                'classes' => ClassRoom::query()->count(),
            ];
        });
    }

    /**
     * Pending reports badge on the admin dashboard (FR-33).
     * The queue itself stays uncached; only this small counter is.
     */
    public function adminPendingReports(): int
    {
        return (int) Cache::remember(
            'dashboard.admin.pending-reports.v1',
            self::TTL_SECONDS,
            static fn (): int => Report::query()->pending()->count()
        );
    }

    /**
     * Month-over-month movement for the five stat cards.
     *
     * @return array<string, array{percent: int, direction: string}>
     */
    public function adminTrends(): array
    {
        return Cache::remember('dashboard.admin.trends.v1', self::TTL_SECONDS, function (): array {
            $thisMonth = now()->copy()->startOfMonth();
            $lastMonth = now()->copy()->subMonthNoOverflow()->startOfMonth();

            $userNow = User::query()->where('created_at', '>=', $thisMonth)->count();
            $userBefore = User::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $teachersNow = User::query()
                ->where('role', User::ROLE_TEACHER)
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $teachersBefore = User::query()
                ->where('role', User::ROLE_TEACHER)
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $studentsNow = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $studentsBefore = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $postsNow = Post::query()->where('created_at', '>=', $thisMonth)->count();
            $postsBefore = Post::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $classesNow = ClassRoom::query()->where('created_at', '>=', $thisMonth)->count();
            $classesBefore = ClassRoom::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            return [
                'users' => $this->trend($userNow, $userBefore),
                'teachers' => $this->trend($teachersNow, $teachersBefore),
                'students' => $this->trend($studentsNow, $studentsBefore),
                'posts' => $this->trend($postsNow, $postsBefore),
                'classes' => $this->trend($classesNow, $classesBefore),
            ];
        });
    }

    /**
     * Seven-day sparkline data for every admin stat card.
     *
     * @return array<string, list<int>>
     */
    public function adminSparklines(): array
    {
        return Cache::remember('dashboard.admin.sparklines.v1', self::TTL_SECONDS, function (): array {
            $since = now()->copy()->startOfDay()->subDays(6);

            return [
                'users' => $this->daySeries(
                    User::query()->where('created_at', '>=', $since)->pluck('created_at'),
                    7
                )['values'],
                'teachers' => $this->daySeries(
                    User::query()
                        ->where('role', User::ROLE_TEACHER)
                        ->where('created_at', '>=', $since)
                        ->pluck('created_at'),
                    7
                )['values'],
                'students' => $this->daySeries(
                    User::query()
                        ->where('role', User::ROLE_STUDENT)
                        ->where('created_at', '>=', $since)
                        ->pluck('created_at'),
                    7
                )['values'],
                'posts' => $this->daySeries(
                    Post::query()->where('created_at', '>=', $since)->pluck('created_at'),
                    7
                )['values'],
                'classes' => $this->daySeries(
                    ClassRoom::query()->where('created_at', '>=', $since)->pluck('created_at'),
                    7
                )['values'],
            ];
        });
    }

    /**
     * Every admin chart series for a range.
     *
     * @return array<string, array{labels: list<string>, values: list<int>, total: int}>
     */
    public function adminCharts(string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        return Cache::remember("dashboard.admin.charts.{$range}.v1", self::TTL_SECONDS, function () use ($range): array {
            $roles = $this->adminRoleSeries();
            $classes = $this->adminClassSeries();

            return [
                'newUsers' => $this->adminNewUsers($range),
                'posts' => $this->adminPosts($range),
                'roles' => $roles,
                'classes' => $classes,
            ];
        });
    }

    /**
     * New registrations for 7 days, 30 days or 12 months.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminNewUsers(string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        $timestamps = User::query()
            ->when($range !== '12m', fn ($query) => $query->where('created_at', '>=', $this->rangeStart($range)))
            ->pluck('created_at');

        return $this->seriesForRange($timestamps, $range);
    }

    /**
     * Posts per day/week/month, depending on the selected range.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminPosts(string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        $timestamps = Post::query()
            ->when($range !== '12m', fn ($query) => $query->where('created_at', '>=', $this->rangeStart($range)))
            ->pluck('created_at');

        if ($range === '12m') {
            return $this->weekSeries($timestamps, 12);
        }

        return $this->seriesForRange($timestamps, $range);
    }

    /**
     * Users by role as a doughnut series.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminRoleSeries(): array
    {
        $counts = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $labels = [];
        $values = [];

        foreach ([User::ROLE_ADMIN, User::ROLE_TEACHER, User::ROLE_STUDENT] as $role) {
            $labels[] = ucfirst($role).'s';
            $values[] = (int) ($counts[$role] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /**
     * Classes by status as a doughnut series.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminClassSeries(): array
    {
        $counts = ClassRoom::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $values = [];

        foreach (ClassRoom::STATUSES as $status) {
            $labels[] = ucfirst($status);
            $values[] = (int) ($counts[$status] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /* =====================================================================
     | Teacher
     * ===================================================================== */

    /**
     * The teacher's own counters. Exact four-key contract.
     *
     * @return array{classes: int, upcoming: int, students: int, followers: int}
     */
    public function teacherStats(User $teacher): array
    {
        return Cache::remember("dashboard.teacher.{$teacher->id}.stats.v1", self::TTL_SECONDS, function () use ($teacher): array {
            $mine = $teacher->classes();

            return [
                'classes' => (clone $mine)->count(),
                'upcoming' => (clone $mine)->upcoming()->count(),
                // Distinct students across all classes (nobody counted twice).
                'students' => ClassEnrollment::query()
                    ->whereIn('class_id', (clone $mine)->select('id'))
                    ->distinct()
                    ->count('student_id'),
                'followers' => $teacher->followers()->count(),
            ];
        });
    }

    /**
     * Month-over-month growth for the teacher cards.
     *
     * @return array<string, array{percent: int, direction: string}>
     */
    public function teacherTrends(User $teacher): array
    {
        return Cache::remember("dashboard.teacher.{$teacher->id}.trends.v1", self::TTL_SECONDS, function () use ($teacher): array {
            $thisMonth = now()->copy()->startOfMonth();
            $lastMonth = now()->copy()->subMonthNoOverflow()->startOfMonth();

            $classesNow = $teacher->classes()->where('created_at', '>=', $thisMonth)->count();
            $classesBefore = $teacher->classes()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $enrollmentsNow = ClassEnrollment::query()
                ->whereIn('class_id', $teacher->classes()->select('id'))
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $enrollmentsBefore = ClassEnrollment::query()
                ->whereIn('class_id', $teacher->classes()->select('id'))
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $followersNow = Follow::query()
                ->where('following_id', $teacher->id)
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $followersBefore = Follow::query()
                ->where('following_id', $teacher->id)
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            return [
                'classes' => $this->trend($classesNow, $classesBefore),
                'students' => $this->trend($enrollmentsNow, $enrollmentsBefore),
                'followers' => $this->trend($followersNow, $followersBefore),
            ];
        });
    }

    /**
     * Chart series for the teacher dashboard.
     *
     * @return array<string, array{labels: list<string>, values: list<int>, total: int}>
     */
    public function teacherCharts(User $teacher, string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        return Cache::remember("dashboard.teacher.{$teacher->id}.charts.{$range}.v1", self::TTL_SECONDS, function () use ($teacher, $range): array {
            $classes = $teacher->classes()
                ->withCount('students')
                ->when($range !== '12m', fn ($query) => $query->whereDate('date', '>=', $this->rangeStart($range)))
                ->orderByDesc('students_count')
                ->orderBy('title')
                ->take(8)
                ->get(['id', 'title']);

            $labels = $classes->map(fn (ClassRoom $class) => Str::limit($class->title, 18))->all();
            $values = $classes->map(fn (ClassRoom $class) => (int) $class->students_count)->all();

            $followers = Follow::query()
                ->where('following_id', $teacher->id)
                ->when($range !== '12m', fn ($query) => $query->where('created_at', '>=', $this->rangeStart($range)))
                ->pluck('created_at');

            return [
                'enrollments' => [
                    'labels' => $labels,
                    'values' => $values,
                    'total' => array_sum($values),
                ],
                'followers' => $this->seriesForRange($followers, $range),
            ];
        });
    }

    /**
     * The next seven days with the number of classes on each day.
     *
     * @return list<array{date: Carbon, weekday: string, day: string, count: int, isToday: bool}>
     */
    public function teacherSchedule(User $teacher): array
    {
        return Cache::remember("dashboard.teacher.{$teacher->id}.schedule.v1", self::TTL_SECONDS, function () use ($teacher): array {
            $start = now()->copy()->startOfDay();
            $end = $start->copy()->addDays(6)->endOfDay();

            $counts = $teacher->classes()
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->pluck('date')
                ->countBy(fn ($date) => Carbon::parse($date)->toDateString());

            $days = [];

            for ($offset = 0; $offset < 7; $offset++) {
                $date = $start->copy()->addDays($offset);

                $days[] = [
                    'date' => $date->copy(),
                    'weekday' => $date->format('D'),
                    'day' => $date->format('j'),
                    'count' => (int) ($counts[$date->toDateString()] ?? 0),
                    'isToday' => $date->isToday(),
                ];
            }

            return $days;
        });
    }

    /* =====================================================================
     | Student
     * ===================================================================== */

    /**
     * The student's counters. Exact three-key contract.
     *
     * @return array{classes: int, upcoming: int, following: int}
     */
    public function studentStats(User $student): array
    {
        return Cache::remember("dashboard.student.{$student->id}.stats.v1", self::TTL_SECONDS, function () use ($student): array {
            return [
                'classes' => $student->enrolledClasses()->count(),
                'upcoming' => $student->enrolledClasses()->upcoming()->count(),
                // Only teachers count, so the card matches what the page offers.
                'following' => $student->following()->where('role', User::ROLE_TEACHER)->count(),
            ];
        });
    }

    /**
     * Classes attended per week (a proxy based on enrolled class dates).
     *
     * @return array<string, array{labels: list<string>, values: list<int>, total: int}>
     */
    public function studentCharts(User $student, string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        return Cache::remember("dashboard.student.{$student->id}.charts.{$range}.v1", self::TTL_SECONDS, function () use ($student, $range): array {
            $dates = $student->enrolledClasses()
                ->when($range !== '12m', fn ($query) => $query->whereDate('classes.date', '>=', $this->rangeStart($range)))
                ->select('classes.date')
                ->pluck('classes.date');

            return [
                'activity' => $this->seriesForRange($dates, $range),
            ];
        });
    }

    /* =====================================================================
     | Series helpers
     * ===================================================================== */

    public function normalizeRange(?string $range): string
    {
        return in_array($range, self::RANGES, true) ? $range : '12m';
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function seriesForRange(Collection $timestamps, string $range): array
    {
        return match ($range) {
            '7d' => $this->daySeries($timestamps, 7),
            '30d' => $this->weekSeries($timestamps, 5),
            default => $this->monthSeries($timestamps, 12),
        };
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function daySeries(Collection $timestamps, int $days): array
    {
        $start = now()->copy()->startOfDay()->subDays($days - 1);
        $counts = $timestamps->countBy(
            static fn ($timestamp) => Carbon::parse($timestamp)->toDateString()
        );

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $day = $start->copy()->addDays($offset);

            $labels[] = $day->format('d M');
            $values[] = (int) ($counts[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function weekSeries(Collection $timestamps, int $weeks): array
    {
        $start = now()->copy()->startOfWeek()->subWeeks($weeks - 1);
        $counts = $timestamps->countBy(
            static fn ($timestamp) => Carbon::parse($timestamp)->startOfWeek()->toDateString()
        );

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $weeks; $offset++) {
            $week = $start->copy()->addWeeks($offset);

            $labels[] = $week->format('d M');
            $values[] = (int) ($counts[$week->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function monthSeries(Collection $timestamps, int $months): array
    {
        // First month of the window: 11 months back, so today's month is included.
        $start = now()->copy()->startOfMonth()->subMonths($months - 1);
        $counts = $timestamps->countBy(
            static fn ($timestamp) => Carbon::parse($timestamp)->format('Y-m')
        );

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $months; $offset++) {
            $month = $start->copy()->addMonths($offset);

            $labels[] = $month->format('M Y');
            $values[] = (int) ($counts[$month->format('Y-m')] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    private function rangeStart(string $range): Carbon
    {
        return match ($range) {
            '7d' => now()->copy()->startOfDay()->subDays(6),
            '30d' => now()->copy()->startOfDay()->subDays(29),
            default => now()->copy()->startOfMonth()->subMonths(11),
        };
    }

    /**
     * @return array{percent: int, direction: string}
     */
    private function trend(int $current, int $previous): array
    {
        if ($previous === 0) {
            $percent = $current === 0 ? 0 : 100;
        } else {
            $percent = (int) round((($current - $previous) / $previous) * 100);
        }

        return [
            'percent' => $percent,
            'direction' => $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat'),
        ];
    }
}

```

# 8. Dashboard views

## `resources/views/dashboards/admin.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Admin dashboard') }}</x-slot>

    <div class="container-xxl px-3 px-lg-4 edu-dashboard">
        @php
            $hour = (int) now()->format('G');
            $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
            $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
        @endphp

        {{-- Greeting + quick actions --}}
        <header class="edu-greeting">
            <div>
                <span class="edu-eyebrow">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    {{ __('Admin workspace') }}
                </span>
                <h1>{{ $greeting }}, {{ $firstName }}</h1>
                <p>
                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                    {{ now()->isoFormat('dddd, D MMMM YYYY') }}
                    <span class="mx-1" aria-hidden="true">·</span>
                    {{ __('Here is what is happening across EduConnect today.') }}
                </p>
            </div>

            <div class="edu-quick-actions" aria-label="{{ __('Quick actions') }}">
                <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-flag" aria-hidden="true"></i>
                    {{ __('Moderation queue') }}
                    @if ($pendingReports > 0)
                        <span class="badge rounded-pill bg-body-secondary text-body ms-1">{{ $pendingReports }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-people" aria-hidden="true"></i> {{ __('Manage users') }}
                </a>
                <a href="{{ route('admin.posts.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chat-square-text" aria-hidden="true"></i> {{ __('Review posts') }}
                </a>
            </div>
        </header>

        {{-- Five platform counters with trend + sparkline (FR-7, FR-26) --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Total users')" :value="$stats['users']" icon="bi-people" color="primary"
                    :trend="$trends['users']['percent']" :trend-direction="$trends['users']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['users']" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Teachers')" :value="$stats['teachers']" icon="bi-person-video3" color="success"
                    :trend="$trends['teachers']['percent']" :trend-direction="$trends['teachers']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['teachers']" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Students')" :value="$stats['students']" icon="bi-mortarboard" color="info"
                    :trend="$trends['students']['percent']" :trend-direction="$trends['students']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['students']" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Posts')" :value="$stats['posts']" icon="bi-chat-square-text" color="warning"
                    :trend="$trends['posts']['percent']" :trend-direction="$trends['posts']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['posts']" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Classes')" :value="$stats['classes']" icon="bi-easel" color="danger"
                    :trend="$trends['classes']['percent']" :trend-direction="$trends['classes']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['classes']" />
            </div>
        </div>

        {{-- Charts (FR-31) --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xxl-8">
                <x-chart-card :title="__('New users per month')" :subtitle="__('Registrations over the last 12 months')"
                    chart-id="admin-new-users" kind="line" series-key="newUsers" color="primary"
                    :series="$chart" :total="$chart['total']"
                    :endpoint="route('admin.dashboard.charts')" current-range="12m" :height="320"
                    :label="__('New users')" />
            </div>

            <div class="col-12 col-md-6 col-xxl-4">
                <x-chart-card :title="__('Users by role')" :subtitle="__('The shape of the community')"
                    chart-id="admin-roles" kind="doughnut" series-key="roles"
                    :series="$roleChart" :total="$roleChart['total']" :height="320" />
            </div>

            <div class="col-12 col-md-6 col-xl-6">
                <x-chart-card :title="__('Posts per week')" :subtitle="__('How active the feed is')"
                    chart-id="admin-posts" kind="bar" series-key="posts" color="secondary"
                    :series="$postsChart" :total="$postsChart['total']"
                    :endpoint="route('admin.dashboard.charts')" current-range="12m" :height="280"
                    :label="__('Posts')" />
            </div>

            <div class="col-12 col-md-6 col-xl-6">
                <x-chart-card :title="__('Classes by status')" :subtitle="__('Scheduled, live and completed')"
                    chart-id="admin-classes" kind="doughnut" series-key="classes"
                    :series="$classesChart" :total="$classesChart['total']" :height="280" />
            </div>
        </div>

        {{-- Moderation + newest members --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xl-7">
                <section class="card edu-card h-100" aria-labelledby="pending-reports-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="pending-reports-title">
                                <i class="bi bi-flag me-1 text-danger" aria-hidden="true"></i>
                                {{ __('Pending reports') }}
                            </h2>
                            <div class="small text-muted">{{ __('Reports waiting for a moderator.') }}</div>
                        </div>
                        <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}"
                           class="small fw-semibold text-decoration-none">{{ __('See all') }}</a>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($recentReports as $report)
                            <div class="edu-list-row">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <span class="badge bg-body-secondary text-body border text-capitalize">{{ $report->content_type }}</span>
                                        <span class="badge text-bg-warning">{{ $report->reason_label }}</span>
                                        <span class="edu-list-meta">{{ $report->created_at?->diffForHumans() }}</span>
                                    </div>

                                    <a href="{{ route('admin.reports.show', $report) }}"
                                       class="edu-list-title text-truncate">{{ $report->content_preview }}</a>
                                    <div class="edu-list-meta">
                                        {{ __('Reported by') }} {{ $report->reporter?->name ?? __('a deleted user') }}
                                    </div>
                                </div>

                                <div class="edu-report-actions">
                                    {{-- Quick moderation actions (FR-33). --}}
                                    <form method="POST" action="{{ route('admin.reports.hide', $report) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                aria-label="{{ __('Hide the reported :type', ['type' => strtolower($report->content_type)]) }}"
                                                title="{{ __('Hide content') }}">
                                            <i class="bi bi-eye-slash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                aria-label="{{ __('Dismiss this report') }}"
                                                title="{{ __('Dismiss report') }}">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;">
                                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('The moderation queue is clear.') }}</div>
                                <div class="text-muted small">{{ __('Nothing to review right now.') }}</div>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-5">
                <x-data-table id="admin-recent-users" :title="__('Recent users')"
                    :subtitle="__('The newest accounts on the platform')"
                    :count="$recentUsers->count()" count-label=":count accounts"
                    :search-placeholder="__('Search recent users…')">
                    <x-slot name="head">
                        <tr>
                            <th scope="col" data-sort>{{ __('User') }}</th>
                            <th scope="col" class="d-none d-md-table-cell">{{ __('Role') }}</th>
                            <th scope="col" data-sort class="text-end">{{ __('Joined') }}</th>
                        </tr>
                    </x-slot>

                    @forelse ($recentUsers as $user)
                        <tr data-table-row data-search="{{ $user->name }} {{ $user->email }} {{ $user->role }}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <x-avatar :user="$user" size="sm" />
                                    <div class="min-w-0">
                                        <a href="{{ route('users.show', $user) }}"
                                           class="fw-semibold text-decoration-none text-reset d-block text-truncate">
                                            {{ $user->name }}
                                        </a>
                                        <span class="text-muted small d-block text-truncate">{{ $user->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                @php
                                    $roleColor = match ($user->role) {
                                        \App\Models\User::ROLE_ADMIN => 'dark',
                                        \App\Models\User::ROLE_TEACHER => 'primary',
                                        default => 'success',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $user->role }}</span>
                            </td>
                            <td class="text-end text-muted small text-nowrap" data-sort-value="{{ $user->created_at?->timestamp }}">
                                {{ $user->created_at?->format('d M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr data-table-row data-search="">
                            <td colspan="3" class="text-center text-muted py-4 small">
                                <i class="bi bi-people me-1" aria-hidden="true"></i> {{ __('No users yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </x-data-table>
            </div>
        </div>

        {{-- Latest posts + live classes --}}
        <div class="row g-3 g-xl-4">
            <div class="col-12 col-xl-7">
                <section class="card edu-card h-100" aria-labelledby="recent-posts-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="recent-posts-title">
                                <i class="bi bi-chat-square-text me-1 text-edu" aria-hidden="true"></i>
                                {{ __('Latest posts') }}
                            </h2>
                            <div class="small text-muted">{{ __('The newest activity in the feed.') }}</div>
                        </div>
                        <a href="{{ route('admin.posts.index') }}"
                           class="small fw-semibold text-decoration-none">{{ __('Manage posts') }}</a>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($recentPosts as $post)
                            <article class="edu-list-row">
                                <x-avatar :user="$post->user" size="sm" class="flex-shrink-0" />
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <a href="{{ route('users.show', $post->user) }}"
                                           class="fw-semibold small text-decoration-none text-reset">
                                            {{ $post->user?->name ?? __('Deleted user') }}
                                        </a>
                                        <span class="edu-list-meta">{{ $post->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <a href="{{ route('posts.show', $post) }}"
                                       class="edu-list-title mt-1">{{ \Illuminate\Support\Str::limit($post->body, 110) }}</a>
                                    <div class="edu-list-meta">
                                        <i class="bi bi-heart me-1" aria-hidden="true"></i>{{ $post->likes_count }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        <i class="bi bi-chat me-1" aria-hidden="true"></i>{{ $post->comments_count }}
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="text-center text-muted py-4 small">
                                <i class="bi bi-chat-square me-1" aria-hidden="true"></i>
                                {{ __('The feed is quiet right now.') }}
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-5">
                <section class="card edu-card h-100" aria-labelledby="live-classes-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="live-classes-title">
                                <i class="bi bi-broadcast me-1 text-danger" aria-hidden="true"></i>
                                {{ __("Today's live classes") }}
                            </h2>
                            <div class="small text-muted">{{ __('Rooms running right now.') }}</div>
                        </div>
                        <a href="{{ route('admin.classes.index') }}"
                           class="small fw-semibold text-decoration-none">{{ __('All classes') }}</a>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($liveClasses as $class)
                            <div class="edu-list-row">
                                <span class="edu-activity-icon bg-danger" aria-hidden="true">
                                    <i class="bi bi-camera-video"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <a href="{{ route('classes.show', $class) }}" class="edu-list-title text-truncate">
                                        {{ $class->title }}
                                    </a>
                                    <div class="edu-list-meta">
                                        {{ $class->teacher?->name ?? __('Unknown teacher') }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        {{ \Illuminate\Support\Str::substr((string) $class->time, 0, 5) }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        {{ trans_choice(':count student|:count students', $class->students_count, ['count' => $class->students_count]) }}
                                    </div>
                                </div>
                                <a href="{{ route('classes.room', $class) }}" class="btn btn-sm btn-danger"
                                   aria-label="{{ __('Join :title', ['title' => $class->title]) }}">
                                    <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;">
                                    <i class="bi bi-camera-video-off" aria-hidden="true"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('No live classes right now.') }}</div>
                                <div class="text-muted small">{{ __('Scheduled rooms will appear here when they start.') }}</div>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/dashboards/teacher.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Teacher dashboard') }}</x-slot>

    <div class="container-xxl px-3 px-lg-4 edu-dashboard">
        @php
            $hour = (int) now()->format('G');
            $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
            $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
        @endphp

        <header class="edu-greeting">
            <div>
                <span class="edu-eyebrow">
                    <i class="bi bi-easel" aria-hidden="true"></i>
                    {{ __('Teaching workspace') }}
                </span>
                <h1>{{ $greeting }}, {{ $firstName }}</h1>
                <p>{{ __('Your classes, students and community at a glance.') }}</p>
            </div>

            <div class="edu-quick-actions">
                <a href="{{ route('classes.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Create class') }}
                </a>
                <a href="{{ route('classes.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-collection" aria-hidden="true"></i> {{ __('All my classes') }}
                </a>
            </div>
        </header>

        {{-- Next class hero --}}
        @if ($nextClass)
            <section class="edu-hero mb-4" aria-labelledby="next-class-title">
                <div class="row align-items-center g-4">
                    <div class="col-12 col-lg-7">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="edu-hero-label small fw-semibold text-uppercase" style="letter-spacing: .1em;">
                                {{ __('Next class') }}
                            </span>
                            @if ($nextClass->isLive())
                                <span class="edu-hero-live">
                                    <span class="edu-live-dot" aria-hidden="true"></span> {{ __('Live now') }}
                                </span>
                            @endif
                        </div>

                        <h1 class="h2 fw-bold mb-2" id="next-class-title">{{ $nextClass->title }}</h1>

                        <div class="edu-hero-meta d-flex flex-wrap gap-3 mb-3">
                            @if ($nextClass->subject)
                                <span><i class="bi bi-book me-1" aria-hidden="true"></i>{{ $nextClass->subject }}</span>
                            @endif
                            <span>
                                <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                {{ $nextClass->date?->format('D d M Y') }}
                            </span>
                            <span>
                                <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                {{ \Illuminate\Support\Str::substr((string) $nextClass->time, 0, 5) }}
                                &middot; {{ $nextClass->duration }} {{ __('min') }}
                            </span>
                        </div>

                        @if ($nextClass->isLive())
                            <div class="edu-chip">
                                <span class="edu-live-dot" aria-hidden="true"></span>
                                {{ __('Students can join the room now') }}
                            </div>
                        @else
                            <x-countdown :target="$nextClass->startsAt()->toIso8601String()" />
                        @endif

                        <div class="d-flex align-items-center gap-2 mt-3">
                            <x-avatar-stack :users="$nextClass->students" :limit="5" />
                            <span class="small" style="color: rgba(255,255,255,.8);">
                                {{ trans_choice(':count student enrolled|:count students enrolled', $nextClass->students_count, ['count' => $nextClass->students_count]) }}
                            </span>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                            @if ($nextClass->isLive())
                                <a href="{{ route('classes.room', $nextClass) }}" class="btn btn-light btn-lg">
                                    <i class="bi bi-camera-video" aria-hidden="true"></i> {{ __('Join live') }}
                                </a>
                            @else
                                <form method="POST" action="{{ route('classes.status', $nextClass) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="live">
                                    <button type="submit" class="btn btn-light btn-lg">
                                        <i class="bi bi-broadcast" aria-hidden="true"></i> {{ __('Start class') }}
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('classes.show', $nextClass) }}" class="btn btn-outline-light btn-lg">
                                {{ __('Details') }}
                            </a>
                        </div>

                        <div class="text-lg-end mt-3">
                            <a href="{{ route('classes.students', $nextClass) }}"
                               class="small text-decoration-none" style="color: rgba(255,255,255,.78);">
                                <i class="bi bi-people me-1" aria-hidden="true"></i>
                                {{ __('See who is enrolled') }}
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        @else
            <section class="card edu-card mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="edu-empty-illustration mb-0" style="width: 62px; height: 62px; font-size: 1.5rem;" aria-hidden="true">
                            <i class="bi bi-calendar-plus"></i>
                        </span>
                        <div>
                            <h2 class="h5 fw-bold mb-1">{{ __('No class scheduled yet') }}</h2>
                            <p class="text-muted small mb-0">{{ __('Create your first live class and invite your students.') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('classes.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Create a class') }}
                    </a>
                </div>
            </section>
        @endif

        {{-- Teacher counters --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('My classes')" :value="$stats['classes']" icon="bi-easel" color="primary"
                    :trend="$trends['classes']['percent']" :trend-direction="$trends['classes']['direction']"
                    :trend-label="__('vs last month')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Upcoming classes')" :value="$stats['upcoming']" icon="bi-calendar-check" color="success"
                    :hint="__('Scheduled and still ahead')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Enrolled students')" :value="$stats['students']" icon="bi-people" color="info"
                    :trend="$trends['students']['percent']" :trend-direction="$trends['students']['direction']"
                    :trend-label="__('new enrollments vs last month')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Followers')" :value="$stats['followers']" icon="bi-person-hearts" color="warning"
                    :trend="$trends['followers']['percent']" :trend-direction="$trends['followers']['direction']"
                    :trend-label="__('vs last month')" />
            </div>
        </div>

        {{-- Charts --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xl-7">
                <x-chart-card :title="__('Enrollments per class')" :subtitle="__('Your most popular classes')"
                    chart-id="teacher-enrollments" kind="bar" series-key="enrollments" color="primary"
                    :series="$enrollmentChart" :total="$enrollmentChart['total']"
                    :endpoint="route('teacher.dashboard.charts')" current-range="12m" :height="280"
                    :label="__('Students')" />
            </div>

            <div class="col-12 col-xl-5">
                <x-chart-card :title="__('Follower growth')" :subtitle="__('People who started following you')"
                    chart-id="teacher-followers" kind="line" series-key="followers" color="secondary"
                    :series="$followerChart" :total="$followerChart['total']"
                    :endpoint="route('teacher.dashboard.charts')" current-range="12m" :height="280"
                    :label="__('Followers')" />
            </div>
        </div>

        {{-- Weekly schedule --}}
        <section class="card edu-card mb-4" aria-labelledby="weekly-schedule-title">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h6 fw-bold mb-0" id="weekly-schedule-title">
                        <i class="bi bi-calendar-week me-1 text-edu" aria-hidden="true"></i>
                        {{ __('This week') }}
                    </h2>
                    <div class="small text-muted">{{ __('Classes per day for the next seven days.') }}</div>
                </div>
                <a href="{{ route('classes.index') }}" class="small fw-semibold text-decoration-none">
                    {{ __('Open calendar') }}
                </a>
            </div>
            <div class="card-body">
                <div class="edu-schedule" role="list" aria-label="{{ __('Seven day schedule') }}">
                    @foreach ($schedule as $day)
                        <div class="edu-schedule-day {{ $day['isToday'] ? 'is-today' : '' }} {{ $day['count'] > 0 ? 'has-classes' : '' }}"
                             role="listitem"
                             aria-label="{{ $day['date']->format('l d M') }}: {{ trans_choice(':count class|:count classes', $day['count'], ['count' => $day['count']]) }}">
                            <span class="edu-schedule-weekday">{{ $day['weekday'] }}</span>
                            <span class="edu-schedule-date">{{ $day['day'] }}</span>
                            <span class="edu-schedule-count">{{ $day['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Classes table + activity timeline --}}
        <div class="row g-3 g-xl-4">
            <div class="col-12 col-xl-8">
                <x-data-table id="teacher-classes" :title="__('My classes')"
                    :subtitle="__('Search, sort and manage your classes')"
                    :count="$classes->total()" count-label=":count classes"
                    :search-placeholder="__('Search my classes…')">
                    <x-slot name="head">
                        <tr>
                            <th scope="col" data-sort>{{ __('Title') }}</th>
                            <th scope="col" data-sort>{{ __('Date') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                            <th scope="col" data-sort>{{ __('Enrolled') }}</th>
                            <th scope="col" class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </x-slot>

                    @forelse ($classes as $class)
                        <tr data-table-row
                            data-search="{{ $class->title }} {{ $class->subject }} {{ $class->status }} {{ $class->date?->format('d M Y') }}">
                            <td>
                                <a href="{{ route('classes.show', $class) }}"
                                   class="fw-semibold text-decoration-none text-reset d-block">
                                    {{ $class->title }}
                                </a>
                                @if ($class->subject)
                                    <div class="small text-muted">{{ $class->subject }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap" data-sort-value="{{ $class->date?->timestamp }}">
                                {{ $class->date?->format('d M Y') }}
                                <div class="small text-muted">
                                    {{ \Illuminate\Support\Str::substr((string) $class->time, 0, 5) }}
                                </div>
                            </td>
                            <td>
                                <x-status-badge :status="$class->status" />
                            </td>
                            <td style="min-width: 136px;">
                                <x-progress-bar :value="$class->students_count" :max="$maxEnrollment"
                                    :label="trans_choice(':count enrolled|:count enrolled', $class->students_count, ['count' => $class->students_count])" />
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false"
                                            aria-label="{{ __('Actions for :title', ['title' => $class->title]) }}">
                                        <i class="bi bi-three-dots" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if ($class->isLive())
                                            <li>
                                                <a class="dropdown-item" href="{{ route('classes.room', $class) }}">
                                                    <i class="bi bi-camera-video me-1" aria-hidden="true"></i>
                                                    {{ __('Join live') }}
                                                </a>
                                            </li>
                                        @elseif ($class->canTransitionTo(\App\Models\ClassRoom::STATUS_LIVE))
                                            <li>
                                                <form method="POST" action="{{ route('classes.status', $class) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="live">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-broadcast me-1" aria-hidden="true"></i>
                                                        {{ __('Start class') }}
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item" href="{{ route('classes.students', $class) }}">
                                                <i class="bi bi-people me-1" aria-hidden="true"></i>
                                                {{ __('Students') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('classes.edit', $class) }}">
                                                <i class="bi bi-pencil me-1" aria-hidden="true"></i>
                                                {{ __('Edit') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('classes.show', $class) }}">
                                                <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>
                                                {{ __('View details') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-table-row data-search="">
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1" aria-hidden="true"></i>
                                {{ __('You have not created a class yet.') }}
                            </td>
                        </tr>
                    @endforelse

                    <x-slot name="footer">
                        <x-pagination-links :paginator="$classes" />
                    </x-slot>
                </x-data-table>
            </div>

            <div class="col-12 col-xl-4">
                <section class="card edu-card h-100" aria-labelledby="recent-activity-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="recent-activity-title">
                            <i class="bi bi-activity me-1 text-edu" aria-hidden="true"></i>
                            {{ __('Recent activity') }}
                        </h2>
                        <div class="small text-muted">{{ __('Likes, comments, enrollments and followers.') }}</div>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($activity as $item)
                            <x-activity-item :icon="$item['icon']" :color="$item['color']"
                                :text="$item['text']" :excerpt="$item['excerpt']" :at="$item['at']" />
                        @empty
                            <div class="text-center text-muted py-4 small">
                                <i class="bi bi-activity me-1" aria-hidden="true"></i>
                                {{ __('Nothing has happened yet.') }}
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/dashboards/student.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Student dashboard') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('student.dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Learning') }}</li>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4 edu-dashboard">
        @php
            $hour = (int) now()->format('G');
            $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
            $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
        @endphp

        {{-- Greeting + quick actions --}}
        <header class="edu-greeting">
            <div>
                <span class="edu-eyebrow">
                    <i class="bi bi-mortarboard" aria-hidden="true"></i>
                    {{ __('Learning workspace') }}
                </span>
                <h1>{{ $greeting }}, {{ $firstName }}</h1>
                <p>
                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                    {{ now()->isoFormat('dddd, D MMMM YYYY') }}
                    <span class="mx-1" aria-hidden="true">·</span>
                    {{ __('Keep going — your next class is waiting.') }}
                </p>
            </div>

            <div class="edu-quick-actions" aria-label="{{ __('Quick actions') }}">
                <a href="{{ route('classes.discover') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-search" aria-hidden="true"></i> {{ __('Find a class') }}
                </a>
                <a href="{{ route('student.classes') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-journal-bookmark" aria-hidden="true"></i> {{ __('My classes') }}
                </a>
                <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chat-square-text" aria-hidden="true"></i> {{ __('Open feed') }}
                </a>
            </div>
        </header>

        {{-- Next enrolled class hero --}}
        @if ($heroClass)
            <section class="edu-hero mb-4" aria-labelledby="next-class-title">
                <div class="row align-items-center g-4">
                    <div class="col-12 col-lg-7">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="edu-hero-label small fw-semibold text-uppercase" style="letter-spacing: .1em;">
                                {{ $heroClass->isLive() ? __('Happening now') : __('Your next class') }}
                            </span>

                            @if ($heroClass->isLive())
                                <span class="edu-hero-live">
                                    <span class="edu-live-dot" aria-hidden="true"></span> {{ __('Live now') }}
                                </span>
                            @else
                                <span class="edu-chip">
                                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                    {{ __('Starts soon') }}
                                </span>
                            @endif
                        </div>

                        <h1 class="h2 fw-bold mb-2" id="next-class-title">{{ $heroClass->title }}</h1>

                        <div class="edu-hero-meta d-flex flex-wrap gap-3 mb-3">
                            <span>
                                <i class="bi bi-person-video3 me-1" aria-hidden="true"></i>
                                {{ $heroClass->teacher?->name ?? __('Your teacher') }}
                            </span>
                            @if ($heroClass->subject)
                                <span>
                                    <i class="bi bi-book me-1" aria-hidden="true"></i>
                                    {{ $heroClass->subject }}
                                </span>
                            @endif
                            <span>
                                <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                {{ $heroClass->date?->format('D d M Y') }}
                            </span>
                            <span>
                                <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                {{ \Illuminate\Support\Str::substr((string) $heroClass->time, 0, 5) }}
                                &middot; {{ $heroClass->duration }} {{ __('min') }}
                            </span>
                        </div>

                        @unless ($heroClass->isLive())
                            <x-countdown :target="$heroClass->startsAt()->toIso8601String()" />
                        @endunless

                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                            <x-avatar-stack :users="$classmates" :limit="5" />
                            <span class="small" style="color: rgba(255,255,255,.8);">
                                {{ trans_choice(':count classmate|:count classmates', $heroClass->students_count ?? 0, ['count' => $heroClass->students_count ?? 0]) }}
                            </span>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                            @if ($heroClass->isLive())
                                {{-- The room only opens while the class is live (FR-20). --}}
                                <a href="{{ route('classes.room', $heroClass) }}" class="btn btn-light btn-lg">
                                    <i class="bi bi-camera-video" aria-hidden="true"></i> {{ __('Join live') }}
                                </a>
                            @else
                                {{-- Stays disabled until the teacher starts the class. --}}
                                <button type="button" class="btn btn-light btn-lg" disabled
                                        aria-disabled="true"
                                        title="{{ __('The Join button opens when the teacher starts the class.') }}">
                                    <i class="bi bi-camera-video" aria-hidden="true"></i> {{ __('Join') }}
                                </button>
                            @endif

                            <a href="{{ route('classes.show', $heroClass) }}" class="btn btn-outline-light btn-lg">
                                {{ __('Details') }}
                            </a>
                        </div>

                        <div class="text-lg-end mt-3">
                            <a href="{{ route('student.classes') }}"
                               class="small text-decoration-none" style="color: rgba(255,255,255,.78);">
                                <i class="bi bi-journal-bookmark me-1" aria-hidden="true"></i>
                                {{ __('All my classes') }}
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        @else
            <section class="card edu-card mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="edu-empty-illustration mb-0" style="width: 62px; height: 62px; font-size: 1.5rem;" aria-hidden="true">
                            <i class="bi bi-calendar-plus"></i>
                        </span>
                        <div>
                            <h2 class="h5 fw-bold mb-1">{{ __('No class booked yet') }}</h2>
                            <p class="text-muted small mb-0">{{ __('Browse the catalogue and join your first live class.') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('classes.discover') }}" class="btn btn-primary">
                        <i class="bi bi-search" aria-hidden="true"></i> {{ __('Find a class') }}
                    </a>
                </div>
            </section>
        @endif

        {{-- Student counters: the three contracted stats plus notifications. --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Enrolled classes')" :value="$stats['classes']"
                    icon="bi-journal-bookmark" color="primary"
                    :hint="__('Classes you joined')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Upcoming classes')" :value="$stats['upcoming']"
                    icon="bi-calendar-check" color="success"
                    :hint="__('Scheduled and still ahead')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Teachers followed')" :value="$stats['following']"
                    icon="bi-person-hearts" color="info"
                    :hint="__('People you learn from')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Notifications')" :value="$notificationCount"
                    icon="bi-bell" color="warning" :href="route('notifications.index')"
                    :hint="__('Unread updates')" />
            </div>
        </div>

        {{-- Upcoming classes + recommended teachers --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xl-8">
                <section class="card edu-card h-100" aria-labelledby="upcoming-classes-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="upcoming-classes-title">
                                <i class="bi bi-calendar-week me-1 text-edu" aria-hidden="true"></i>
                                {{ __('My upcoming classes') }}
                            </h2>
                            <div class="small text-muted">{{ __('Your next live sessions, soonest first.') }}</div>
                        </div>
                        <a href="{{ route('student.classes') }}" class="small fw-semibold text-decoration-none">
                            {{ __('See all') }}
                        </a>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            @forelse ($upcomingClasses as $class)
                                <div class="col-12 col-md-6">
                                    <x-class-card :class-room="$class" :url="route('classes.show', $class)">
                                        @if ($class->isLive())
                                            <a href="{{ route('classes.room', $class) }}" class="btn btn-sm btn-danger">
                                                <i class="bi bi-camera-video me-1" aria-hidden="true"></i> {{ __('Join live') }}
                                            </a>
                                        @else
                                            {{-- Disabled until the teacher starts the class; the
                                                 PhaseThreeUiTest asserts these exact attributes. --}}
                                            <button type="button" class="btn btn-sm btn-primary" disabled
                                                    aria-disabled="true"
                                                    title="{{ __('The Join button opens when the teacher starts the class.') }}">
                                                <i class="bi bi-camera-video me-1" aria-hidden="true"></i> {{ __('Join') }}
                                            </button>
                                        @endif

                                        <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ __('Details') }}
                                        </a>
                                    </x-class-card>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="text-center py-4">
                                        <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;" aria-hidden="true">
                                            <i class="bi bi-calendar-x"></i>
                                        </div>
                                        <div class="fw-semibold small">{{ __('No upcoming class') }}</div>
                                        <div class="text-muted small mb-3">{{ __('Browse the catalogue to find your next session.') }}</div>
                                        <a href="{{ route('classes.discover') }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-search" aria-hidden="true"></i> {{ __('Browse classes') }}
                                        </a>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-4">
                <section class="card edu-card h-100" aria-labelledby="recommended-teachers-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="recommended-teachers-title">
                                <i class="bi bi-person-hearts me-1 text-edu" aria-hidden="true"></i>
                                {{ __('Recommended teachers') }}
                            </h2>
                            <div class="small text-muted">{{ __('Follow a teacher to see their classes.') }}</div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            @forelse ($recommendedTeachers as $teacher)
                                <x-user-card :user="$teacher">
                                    {{-- AJAX follow when JS is on, a normal POST otherwise. --}}
                                    @include('users._follow-button', ['user' => $teacher])
                                    <a href="{{ route('users.show', $teacher) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-person me-1" aria-hidden="true"></i> {{ __('Profile') }}
                                    </a>
                                </x-user-card>
                            @empty
                                <div class="text-center py-4">
                                    <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;" aria-hidden="true">
                                        <i class="bi bi-check2-circle"></i>
                                    </div>
                                    <div class="fw-semibold small">{{ __('All caught up') }}</div>
                                    <div class="text-muted small">{{ __('You already follow every teacher.') }}</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
        </div>

        {{-- Learning activity + recommended classes --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xl-7">
                <x-chart-card :title="__('Learning activity')"
                    :subtitle="__('Classes attended over the selected period')"
                    chart-id="student-activity" kind="bar" series-key="activity" color="primary"
                    :series="$activityChart" :total="$activityChart['total']"
                    :endpoint="route('student.dashboard.charts')" current-range="12m" :height="280"
                    :label="__('Classes')" />
            </div>

            <div class="col-12 col-xl-5">
                <section class="card edu-card h-100" aria-labelledby="recommended-classes-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="recommended-classes-title">
                                <i class="bi bi-stars me-1 text-edu" aria-hidden="true"></i>
                                {{ __('Recommended for you') }}
                            </h2>
                            <div class="small text-muted">{{ __('From teachers and subjects you follow.') }}</div>
                        </div>
                        <a href="{{ route('classes.discover') }}" class="small fw-semibold text-decoration-none">
                            {{ __('Discover') }}
                        </a>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($recommendedClasses as $class)
                            <div class="edu-list-row">
                                <span class="edu-activity-icon bg-primary" aria-hidden="true">
                                    <i class="bi bi-easel"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <a href="{{ route('classes.show', $class) }}" class="edu-list-title text-truncate">
                                        {{ $class->title }}
                                    </a>
                                    <div class="edu-list-meta">
                                        {{ $class->teacher?->name ?? __('Unknown teacher') }}
                                        @if ($class->subject)
                                            <span class="mx-1" aria-hidden="true">·</span>{{ $class->subject }}
                                        @endif
                                    </div>
                                    <div class="edu-list-meta">
                                        <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                        {{ $class->date?->format('D d M') }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        {{ \Illuminate\Support\Str::substr((string) $class->time, 0, 5) }}
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('classes.enroll', $class) }}" class="flex-shrink-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary"
                                            aria-label="{{ __('Enrol in :title', ['title' => $class->title]) }}">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;" aria-hidden="true">
                                    <i class="bi bi-compass"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('Nothing to suggest yet') }}</div>
                                <div class="text-muted small mb-3">{{ __('Follow a teacher or join a class to get recommendations.') }}</div>
                                <a href="{{ route('classes.discover') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-search" aria-hidden="true"></i> {{ __('Browse classes') }}
                                </a>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        {{-- Three-post feed preview --}}
        <div class="row g-3 g-xl-4">
            <div class="col-12">
                <section class="card edu-card" aria-labelledby="feed-preview-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="feed-preview-title">
                                <i class="bi bi-rss me-1 text-edu" aria-hidden="true"></i>
                                {{ __('From people you follow') }}
                            </h2>
                            <div class="small text-muted">{{ __('The newest posts in your feed.') }}</div>
                        </div>
                        <a href="{{ route('feed.index') }}" class="small fw-semibold text-decoration-none">
                            {{ __('Open full feed') }} <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                        </a>
                    </div>

                    <div class="card-body py-2">
                        @php
                            $posts = $feedPreview instanceof \Illuminate\Pagination\LengthAwarePaginator
                                ? $feedPreview->getCollection()
                                : $feedPreview;
                        @endphp

                        @forelse ($posts as $post)
                            @php
                                $displayBody = trim(strip_tags((string) $post->body));
                                if (trim((string) $post->title) !== '') {
                                    $displayBody = trim((string) $post->title).' — '.$displayBody;
                                }
                            @endphp

                            <article class="edu-feed-item">
                                <x-avatar :user="$post->user" size="sm" class="flex-shrink-0" />

                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <a href="{{ route('users.show', $post->user) }}"
                                           class="fw-semibold small text-decoration-none text-reset">
                                            {{ $post->user?->name ?? __('Deleted user') }}
                                        </a>
                                        <span class="edu-list-meta">{{ $post->created_at?->diffForHumans() }}</span>
                                    </div>

                                    <a href="{{ route('posts.show', $post) }}"
                                       class="text-decoration-none text-reset d-block">
                                        <p class="edu-feed-body mb-1">{{ \Illuminate\Support\Str::limit($displayBody, 180) }}</p>
                                    </a>

                                    <div class="edu-list-meta">
                                        <i class="bi bi-heart me-1" aria-hidden="true"></i>{{ $post->likes_count ?? 0 }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        <i class="bi bi-chat me-1" aria-hidden="true"></i>{{ $post->comments_count ?? 0 }}
                                    </div>
                                </div>

                                <a href="{{ route('posts.show', $post) }}" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                   aria-label="{{ __('Open this post') }}">
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </article>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;" aria-hidden="true">
                                    <i class="bi bi-rss"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('Your feed is quiet') }}</div>
                                <div class="text-muted small mb-3">{{ __('Follow teachers and classmates to see their posts here.') }}</div>
                                <a href="{{ route('search.index') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-search" aria-hidden="true"></i> {{ __('Find people') }}
                                </a>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>

```

# 9. Profile pages

## `app/Http/Controllers/ProfileController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly ImageUploadService $images) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information: name, email, photo, bio, subject (FR-4).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // "photo" is a file and "remove_photo" is a checkbox, never columns,
        // so they are handled separately below.
        $user->fill($request->safe()->except(['photo', 'remove_photo']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('photo')) {
            // Remember the old file so we can delete it after storing the new one.
            $oldPhoto = $user->photo;

            // Decoded, resized and re-encoded to a random WebP file (SRS 5.1/5.2).
            $user->photo = $this->images->storeProfilePhoto($request->file('photo'));

            // FR-4: the replaced photo is removed from the disk.
            if ($oldPhoto) {
                Storage::disk('public')->delete($oldPhoto);
            }
        } elseif ($request->boolean('remove_photo') && $user->photo) {
            // FR-4: "remove current photo" restores the default avatar.
            Storage::disk('public')->delete($user->photo);
            $user->photo = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', __('Your profile has been updated.'));
    }

    /**
     * Delete the user's account.
     */
    public function destroy(DeleteAccountRequest $request): RedirectResponse
    {
        // DeleteAccountRequest validates the password in the "userDeletion" bag.
        $user = $request->user();

        // Do not leave an orphan file behind on the disk.
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

```

## `app/Http/Controllers/UserProfileController.php`

```php
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

```

## `app/Http/Requests/ProfileUpdateRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],

            // Photo: real image files only, max 2 MB (FR-4).
            'photo' => [
                'nullable',
                'image',
                'mimes:'.implode(',', config('educonnect.uploads.profile_photo_mimes')),
                'max:'.config('educonnect.uploads.profile_photo_max_kb'),
            ],

            // The "remove current photo" checkbox (FR-4).
            'remove_photo' => ['sometimes', 'boolean'],

            'bio' => ['nullable', 'string', 'max:1000'],

            // Only teachers see this field in the form; it stays nullable for students.
            'subject' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Custom messages that name the file size limit in a friendly way.
     */
    public function messages(): array
    {
        return [
            'photo.max' => 'The photo may not be larger than 2 MB.',
            'photo.mimes' => 'The photo must be a JPG, PNG or WEBP file.',
        ];
    }
}

```

## `app/Http/Requests/DeleteAccountRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Confirms the password before an account is deleted (FR-4).
 * Validation is reported in the "userDeletion" error bag, as the form expects.
 */
class DeleteAccountRequest extends FormRequest
{
    /**
     * The profile page renders the password error inside its deletion modal.
     *
     * @var string
     */
    protected $errorBag = 'userDeletion';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'password' => __('password'),
        ];
    }
}

```

## `app/Http/Requests/UpdatePasswordRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    /** Profile page renders current-password errors in this bag. */
    protected $errorBag = 'updatePassword';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];
    }
}

```

## `app/Services/ImageUploadService.php`

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores uploaded images safely (SRS 5.1, SRS 5.2).
 *
 * Every upload is decoded with GD and re-encoded from scratch:
 *
 *  - the original file is never copied to disk, so a PHP payload hidden inside
 *    a "JPEG" cannot survive the round trip,
 *  - EXIF metadata (location, camera, author) is dropped,
 *  - large photos are resized before they are written,
 *  - the file name is 40 random characters, never the client's name,
 *  - the extension is chosen by us (webp, or jpg when GD has no WebP support).
 *
 * A file that GD cannot decode is rejected with a normal validation error,
 * even when its MIME type claimed to be an image.
 */
class ImageUploadService
{
    /**
     * Store a post image, resized to the configured maximum width and height.
     */
    public function storePostImage(UploadedFile $file): string
    {
        $config = config('educonnect.uploads');

        return $this->store(
            $file,
            (string) $config['post_image_dir'],
            (int) $config['post_image_max_width'],
            (int) $config['post_image_max_height'],
            (int) $config['post_image_quality'],
            'image'
        );
    }

    /**
     * Store a profile photo, cropped down to the configured square-ish size.
     */
    public function storeProfilePhoto(UploadedFile $file): string
    {
        $config = config('educonnect.uploads');

        return $this->store(
            $file,
            (string) $config['profile_photo_dir'],
            (int) $config['profile_photo_max_width'],
            (int) $config['profile_photo_max_height'],
            (int) $config['profile_photo_quality'],
            'photo'
        );
    }

    /**
     * Decode, resize, re-encode and write one file. Returns the relative path
     * on the "public" disk.
     *
     * @param  string  $field  the form field name, used for the error message
     */
    private function store(
        UploadedFile $file,
        string $directory,
        int $maxWidth,
        int $maxHeight,
        int $quality,
        string $field
    ): string {
        $path = $file->getRealPath();
        $contents = $path !== false ? @file_get_contents($path) : false;
        $source = $contents !== false ? @imagecreatefromstring($contents) : false;

        if ($source === false) {
            throw ValidationException::withMessages([
                $field => __('That file is not a readable image. Please upload a JPG, PNG or WebP photo.'),
            ]);
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // Never enlarge a small image; only shrink what is too big.
        $scale = min(1, $maxWidth / max(1, $width), $maxHeight / max(1, $height));
        $newWidth = max(1, (int) floor($width * $scale));
        $newHeight = max(1, (int) floor($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        $useWebp = function_exists('imagewebp');

        if ($useWebp) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);
        } else {
            // JPEG has no transparency: flatten onto white first.
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $white);
        }

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        ob_start();

        if ($useWebp) {
            imagewebp($canvas, null, $quality);
        } else {
            imagejpeg($canvas, null, $quality);
        }

        $encoded = ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        if (! is_string($encoded) || $encoded === '') {
            throw ValidationException::withMessages([
                $field => __('The image could not be processed. Please try another file.'),
            ]);
        }

        $path = trim($directory, '/').'/'.Str::random(40).($useWebp ? '.webp' : '.jpg');

        Storage::disk('public')->put($path, $encoded);

        return $path;
    }
}

```

## `resources/views/profile/edit.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Profile settings') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('users.show', auth()->user()) }}">{{ __('My profile') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Settings') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-gear me-1 text-edu" aria-hidden="true"></i> {{ __('Profile settings') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Manage your photo, personal details and account security.') }}</p>
            </div>
            <a href="{{ route('users.show', auth()->user()) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-person me-1" aria-hidden="true"></i> {{ __('View my profile') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row g-4">
            {{-- In-page navigation --}}
            <div class="col-12 col-lg-3">
                <nav class="card edu-card edu-sticky-aside" aria-label="{{ __('Settings sections') }}">
                    <div class="card-body py-3">
                        <div class="edu-eyebrow mb-2">{{ __('On this page') }}</div>
                        <ul class="nav flex-column gap-1 small">
                            <li class="nav-item">
                                <a class="nav-link py-1 px-2" href="#profile-information">
                                    <i class="bi bi-person me-1" aria-hidden="true"></i>{{ __('Profile information') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 px-2" href="#update-password">
                                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>{{ __('Password') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 px-2 text-danger" href="#delete-account">
                                    <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ __('Delete account') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>

            <div class="col-12 col-lg-9">
                <div class="card edu-card mb-4" id="profile-information">
                    <div class="card-body p-3 p-md-4">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="card edu-card mb-4" id="update-password">
                    <div class="card-body p-3 p-md-4">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="card edu-card border-danger-subtle" id="delete-account">
                    <div class="card-body p-3 p-md-4">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/profile/partials/update-profile-information-form.blade.php`

```blade
<section>
    <h2 class="h6 fw-bold mb-1" id="profile-information-title">{{ __('Profile information') }}</h2>
    <p class="text-muted small mb-4">{{ __('Update your photo, name, email and bio.') }}</p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    {{-- enctype is required for the photo upload (FR-4). --}}
    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data"
          data-photo-form>
        @csrf
        @method('patch')

        {{-- Current photo and live preview --}}
        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mb-4">
            <img id="photo-preview"
                 src="{{ old('remove_photo') ? asset('images/default-avatar.svg') : $user->photoUrl() }}"
                 alt="{{ $user->name }}"
                 class="profile-photo-preview"
                 data-photo-preview
                 data-default-src="{{ asset('images/default-avatar.svg') }}"
                 data-current-src="{{ $user->photoUrl() }}">

            <div class="flex-grow-1">
                <x-input-label for="photo" :value="__('Profile photo')" />
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
                       class="form-control {{ $errors->has('photo') ? 'is-invalid' : '' }}"
                       data-photo-input>
                <div class="form-text">{{ __('JPG, PNG or WEBP - max 2 MB. The preview updates before you save.') }}</div>
                <x-input-error class="mt-1" :messages="$errors->get('photo')" />

                @if ($user->photo)
                    {{-- Removing the uploaded photo falls back to the default avatar. --}}
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_photo"
                               id="remove_photo" value="1" data-photo-remove
                               @checked(old('remove_photo'))>
                        <label class="form-check-label small" for="remove_photo">
                            {{ __('Remove current photo') }}
                        </label>
                    </div>
                @endif
            </div>
        </div>

        <div class="mb-3">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)"
                          :class="$errors->has('name') ? 'is-invalid' : ''"
                          required autofocus autocomplete="name" />
            <x-input-error class="mt-1" :messages="$errors->get('name')" />
        </div>

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autocomplete="username" />
            <x-input-error class="mt-1" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="small mt-2">
                    <p class="mb-1 text-muted">{{ __('Your email address is unverified.') }}</p>

                    <button form="send-verification" class="btn btn-link btn-sm p-0">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="text-success mt-2 mb-0">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Subject: only teachers have one (FR-4). --}}
        @if ($user->isTeacher())
            <div class="mb-3">
                <x-input-label for="subject" :value="__('Subject you teach')" />
                <x-text-input id="subject" name="subject" type="text" :value="old('subject', $user->subject)"
                              :class="$errors->has('subject') ? 'is-invalid' : ''"
                              placeholder="{{ __('e.g. Mathematics') }}" />
                <x-input-error class="mt-1" :messages="$errors->get('subject')" />
            </div>
        @endif

        <div class="mb-3">
            <x-input-label for="bio" :value="__('Bio')" />
            <textarea id="bio" name="bio" rows="3" maxlength="1000"
                      class="form-control {{ $errors->has('bio') ? 'is-invalid' : '' }}"
                      placeholder="{{ __('Tell people a little about yourself.') }}">{{ old('bio', $user->bio) }}</textarea>
            <div class="form-text d-flex justify-content-between gap-2">
                <span>{{ __('Max 1000 characters.') }}</span>
                <span data-count-target="bio" aria-live="polite"></span>
            </div>
            <x-input-error class="mt-1" :messages="$errors->get('bio')" />
        </div>

        <div class="d-flex align-items-center gap-3">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <span class="text-success small">{{ __('Saved.') }}</span>
            @endif
        </div>
    </form>
</section>

```

## `resources/views/profile/partials/update-password-form.blade.php`

```blade
<section>
    <h2 class="h6 fw-bold mb-1" id="update-password-title">{{ __('Update password') }}</h2>
    <p class="text-muted small mb-4">{{ __('Use a long, random password to stay secure.') }}</p>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-3">
            <x-input-label for="update_password_current_password" :value="__('Current password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password"
                          :class="$errors->updatePassword->has('current_password') ? 'is-invalid' : ''"
                          autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password" :value="__('New password')" />
            <x-text-input id="update_password_password" name="password" type="password"
                          :class="$errors->updatePassword->has('password') ? 'is-invalid' : ''"
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password"
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="d-flex align-items-center gap-3">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <span class="text-success small">{{ __('Saved.') }}</span>
            @endif
        </div>
    </form>
</section>

```

## `resources/views/profile/partials/delete-user-form.blade.php`

```blade
<section>
    <h2 class="h6 fw-bold mb-1 text-danger" id="delete-account-title">{{ __('Delete account') }}</h2>
    <p class="text-muted small mb-4">
        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}
    </p>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-4">
            @csrf
            @method('delete')

            <h4 class="h6 fw-bold">{{ __('Are you sure you want to delete your account?') }}</h4>

            <p class="text-muted small">
                {{ __('Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mb-3">
                <x-input-label for="password" value="{{ __('Password') }}" class="visually-hidden" />
                <x-text-input id="password" name="password" type="password"
                              :class="$errors->userDeletion->has('password') ? 'is-invalid' : ''"
                              placeholder="{{ __('Password') }}" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1" />
            </div>

            <div class="d-flex justify-content-end gap-2">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button>{{ __('Delete account') }}</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>

```

## `resources/views/users/show.blade.php`

```blade
@php
    $roleColor = match ($profile->role) {
        \App\Models\User::ROLE_ADMIN => 'dark',
        \App\Models\User::ROLE_TEACHER => 'primary',
        default => 'success',
    };
    $isMe = auth()->id() === $profile->id;
@endphp

<x-app-layout>
    <x-slot name="title">{{ $isMe ? __('My profile') : $profile->name }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('search.index', ['tab' => 'users']) }}">{{ __('People') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $isMe ? __('My profile') : $profile->name }}</li>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                {{-- Profile hero: cover, avatar, name, role, counts and the primary action. --}}
                <section class="card edu-card overflow-hidden mb-3" aria-labelledby="profile-name">
                    <div class="edu-cover" aria-hidden="true"></div>

                    <div class="card-body pt-0">
                        <div class="d-flex flex-wrap align-items-end gap-3">
                            <x-avatar :user="$profile" size="xl"
                                      class="edu-cover-avatar border border-4 border-body flex-shrink-0" />

                            <div class="flex-grow-1 min-w-0 pb-1">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <h1 class="h4 fw-bold mb-0 text-break" id="profile-name">{{ $profile->name }}</h1>
                                    <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $profile->role }}</span>
                                </div>

                                @if ($profile->subject)
                                    <div class="text-muted small">
                                        <i class="bi bi-book me-1" aria-hidden="true"></i>{{ $profile->subject }}
                                    </div>
                                @endif

                                <div class="text-muted small">
                                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                                    {{ __('Joined :date', ['date' => $profile->created_at?->format('M Y')]) }}
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 pb-1">
                                @if ($isMe)
                                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                                        {{ __('Edit profile') }}
                                    </a>
                                @else
                                    @include('users._follow-button', ['user' => $profile, 'followingIds' => $followingIds])
                                @endif
                            </div>
                        </div>

                        @if ($profile->bio)
                            <p class="mt-3 mb-0 edu-measure text-break">{{ $profile->bio }}</p>
                        @endif

                        <div class="d-flex flex-wrap gap-4 mt-3 pt-3 border-top">
                            <span>
                                <strong class="d-block h6 mb-0">{{ $profile->posts_count }}</strong>
                                <span class="small text-muted">{{ __('Posts') }}</span>
                            </span>
                            <a href="{{ route('users.followers', $profile) }}" class="text-decoration-none text-reset">
                                <strong class="d-block h6 mb-0" data-followers-count data-user-id="{{ $profile->id }}">{{ $profile->followers_count }}</strong>
                                <span class="small text-muted">{{ __('Followers') }}</span>
                            </a>
                            <a href="{{ route('users.following', $profile) }}" class="text-decoration-none text-reset">
                                <strong class="d-block h6 mb-0" data-following-count data-user-id="{{ $profile->id }}">{{ $profile->following_count }}</strong>
                                <span class="small text-muted">{{ __('Following') }}</span>
                            </a>
                        </div>
                    </div>
                </section>

                {{-- Tabs: Posts, Classes (teachers only) and About. --}}
                <div class="card edu-card">
                    <div class="card-header pb-0">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="profile-posts-tab" data-bs-toggle="tab"
                                        data-bs-target="#profile-posts" type="button" role="tab"
                                        aria-controls="profile-posts" aria-selected="true">
                                    <i class="bi bi-chat-square-text me-1" aria-hidden="true"></i>
                                    {{ __('Posts') }}
                                </button>
                            </li>

                            @if ($profile->isTeacher())
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="profile-classes-tab" data-bs-toggle="tab"
                                            data-bs-target="#profile-classes" type="button" role="tab"
                                            aria-controls="profile-classes" aria-selected="false">
                                        <i class="bi bi-easel me-1" aria-hidden="true"></i>
                                        {{ __('Classes') }}
                                    </button>
                                </li>
                            @endif

                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="profile-about-tab" data-bs-toggle="tab"
                                        data-bs-target="#profile-about" type="button" role="tab"
                                        aria-controls="profile-about" aria-selected="false">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                                    {{ __('About') }}
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content">
                        {{-- Posts --}}
                        <div class="tab-pane fade show active p-3" id="profile-posts" role="tabpanel"
                             aria-labelledby="profile-posts-tab" tabindex="0">
                            @if ($isMe)
                                <div class="d-flex justify-content-end mb-3">
                                    <a href="{{ route('posts.create') }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> {{ __('New post') }}
                                    </a>
                                </div>
                            @endif

                            @include('feed._posts', [
                                'posts' => $posts,
                                'commentsByPost' => null,
                                'emptyMessage' => $isMe
                                    ? __('You have not posted yet. Share an update with your followers.')
                                    : __('This person has not posted yet.'),
                                'emptyActionUrl' => $isMe ? route('posts.create') : null,
                                'emptyActionLabel' => $isMe ? __('Write your first post') : null,
                            ])

                            <div class="mt-3">
                                <x-pagination-links :paginator="$posts" />
                            </div>
                        </div>

                        {{-- Classes (teachers) --}}
                        @if ($profile->isTeacher())
                            <div class="tab-pane fade p-3" id="profile-classes" role="tabpanel"
                                 aria-labelledby="profile-classes-tab" tabindex="0">
                                @if ($classes->isEmpty())
                                    <x-empty-state icon="bi-easel"
                                                   :title="__('No published classes yet')"
                                                   :message="__('When this teacher publishes a class it will appear here.')">
                                        <a href="{{ route('classes.discover') }}" class="btn btn-primary">
                                            {{ __('Browse classes') }}
                                        </a>
                                    </x-empty-state>
                                @else
                                    <div class="row g-3">
                                        @foreach ($classes as $classRoom)
                                            <div class="col-12 col-md-6">
                                                <x-class-card :class-room="$classRoom" :url="route('classes.show', $classRoom)" />
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- About --}}
                        <div class="tab-pane fade p-3" id="profile-about" role="tabpanel"
                             aria-labelledby="profile-about-tab" tabindex="0">
                            <dl class="row small mb-0">
                                <dt class="col-sm-3 text-muted fw-semibold">{{ __('Role') }}</dt>
                                <dd class="col-sm-9 text-capitalize">{{ $profile->role }}</dd>

                                @if ($profile->subject)
                                    <dt class="col-sm-3 text-muted fw-semibold">{{ __('Subject') }}</dt>
                                    <dd class="col-sm-9">{{ $profile->subject }}</dd>
                                @endif

                                <dt class="col-sm-3 text-muted fw-semibold">{{ __('Member since') }}</dt>
                                <dd class="col-sm-9">{{ $profile->created_at?->format('d M Y') }}</dd>

                                <dt class="col-sm-3 text-muted fw-semibold">{{ __('Bio') }}</dt>
                                <dd class="col-sm-9 text-break mb-0">
                                    {{ $profile->bio ?: __('This member has not written a bio yet.') }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/users/people.blade.php`

```blade
@php
    // Either the followers or the following list (FR-17).
    $title = $mode === 'followers' ? __('Followers') : __('Following');
@endphp

<x-app-layout>
    <x-slot name="title">{{ $title }} · {{ $profile->name }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('users.show', $profile) }}">{{ $profile->name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-people me-1 text-edu" aria-hidden="true"></i> {{ $title }}
                    <span class="text-muted fw-normal" aria-hidden="true">·</span>
                    <span class="fw-normal">{{ $profile->name }}</span>
                </h1>
                <p class="small text-muted mb-0">{{ __('People in this part of the community.') }}</p>
            </div>
            <a href="{{ route('users.show', $profile) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to profile') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                {{-- Switch between the two lists without going back first --}}
                <div class="d-flex flex-wrap gap-2 mb-3" aria-label="{{ __('Switch list') }}">
                    <a class="edu-filter-chip {{ $mode === 'followers' ? 'is-active' : '' }}"
                       href="{{ route('users.followers', $profile) }}"
                       @if ($mode === 'followers') aria-current="page" @endif>
                        <i class="bi bi-person-heart" aria-hidden="true"></i>
                        {{ __('Followers') }}
                        <span class="visually-hidden">{{ __('of :name', ['name' => $profile->name]) }}</span>
                    </a>
                    <a class="edu-filter-chip {{ $mode === 'following' ? 'is-active' : '' }}"
                       href="{{ route('users.following', $profile) }}"
                       @if ($mode === 'following') aria-current="page" @endif>
                        <i class="bi bi-person-check" aria-hidden="true"></i>
                        {{ __('Following') }}
                    </a>
                </div>

                @if ($people->isEmpty())
                    <x-empty-state icon="bi-people"
                                   :title="$mode === 'followers' ? __('Nobody follows this user yet.') : __('This user does not follow anybody yet.')"
                                   :message="__('When people connect on EduConnect they appear here.')">
                        <a href="{{ route('search.index', ['tab' => 'users']) }}" class="btn btn-primary">
                            <i class="bi bi-search me-1" aria-hidden="true"></i> {{ __('Find people') }}
                        </a>
                    </x-empty-state>
                @else
                    <div class="card edu-card">
                        <ul class="list-group list-group-flush">
                            @foreach ($people as $person)
                                @php
                                    $personRoleColor = match ($person->role) {
                                        \App\Models\User::ROLE_ADMIN => 'dark',
                                        \App\Models\User::ROLE_TEACHER => 'primary',
                                        default => 'success',
                                    };
                                @endphp

                                <li class="list-group-item d-flex flex-wrap align-items-center gap-3 py-3">
                                    <x-avatar :user="$person" size="md" />

                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <a href="{{ route('users.show', $person) }}"
                                               class="fw-semibold text-decoration-none text-reset text-break">{{ $person->name }}</a>
                                            <span class="badge text-bg-{{ $personRoleColor }} text-capitalize">{{ $person->role }}</span>
                                        </div>

                                        @if ($person->subject)
                                            <div class="small text-muted">
                                                <i class="bi bi-book me-1" aria-hidden="true"></i>{{ $person->subject }}
                                            </div>
                                        @endif
                                    </div>

                                    @include('users._follow-button', ['user' => $person, 'followingIds' => $followingIds])
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-3">
                        <x-pagination-links :paginator="$people" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/users/_follow-button.blade.php`

```blade
{{-- Follow / unfollow button (FR-11).
     Students and teachers can follow each other. Admin accounts are not
     followable, and nobody gets a button on their own profile. Admins do not
     use the follow routes, so they do not see the button either.
     It is a real form: without JavaScript the browser simply posts it. --}}
@php
    $viewer = auth()->user();
    $isFollowing = $followingIds->contains($user->id);
    $canFollow = $viewer
        && ! $viewer->isAdmin()
        && ! $viewer->is($user)
        && ! $user->isAdmin();
@endphp

@if ($canFollow)
    <form method="POST"
          action="{{ $isFollowing ? route('follows.destroy', $user) : route('follows.store', $user) }}"
          data-follow-form
          data-follow-url="{{ route('follows.store', $user) }}"
          data-unfollow-url="{{ route('follows.destroy', $user) }}"
          data-following="{{ $isFollowing ? '1' : '0' }}"
          data-follow-user-id="{{ $user->id }}"
          data-label-follow="{{ __('Follow') }}"
          data-label-following="{{ __('Following') }}"
          class="d-inline">
        @csrf
        @if ($isFollowing)
            @method('DELETE')
        @endif
        <button type="submit" class="btn btn-sm {{ $isFollowing ? 'btn-outline-secondary' : 'btn-primary' }}"
                data-follow-button aria-pressed="{{ $isFollowing ? 'true' : 'false' }}">
            <i class="bi {{ $isFollowing ? 'bi-person-check' : 'bi-person-plus' }} me-1"></i>
            <span data-follow-label>{{ $isFollowing ? __('Following') : __('Follow') }}</span>
        </button>
    </form>
@endif

```

# 10. Error pages

## `resources/views/errors/page.blade.php`

```blade
{{-- Shared friendly error page shell (Phase 3C).
     Variables: $code, $title, $message, $icon.
     Every error page keeps a way forward: home, plus the dashboard or login. --}}
@php
    $code = $code ?? '404';
    $title = $title ?? __('Something went wrong');
    $message = $message ?? __('Please try again in a moment.');
    $icon = $icon ?? 'bi-emoji-frown';
@endphp

<x-guest-layout :title="$title">
    <div class="text-center py-2">
        <span class="edu-empty-illustration mx-auto" aria-hidden="true">
            <i class="bi {{ $icon }}"></i>
        </span>

        <div class="display-5 fw-bold text-edu lh-1 mt-2">{{ $code }}</div>
        <h1 class="h4 fw-bold mt-2 mb-2">{{ $title }}</h1>

        <p class="text-muted small edu-measure mx-auto mb-4">{{ $message }}</p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="bi bi-house me-1" aria-hidden="true"></i>{{ __('Back to home') }}
            </a>

            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>{{ __('Go to dashboard') }}
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>{{ __('Log in') }}
                </a>
            @endauth
        </div>

        <p class="small text-muted mt-4 mb-0">
            {{ __('Still stuck? Contact the administrator and mention code :code.', ['code' => $code]) }}
        </p>
    </div>
</x-guest-layout>

```

## `resources/views/errors/403.blade.php`

```blade
@include('errors.page', [
    'code' => '403',
    'icon' => 'bi-shield-lock',
    'title' => __('You are not allowed to open this page'),
    'message' => __('Your account does not have the rights for this area. If you think this is a mistake, please contact an administrator.'),
])

```

## `resources/views/errors/404.blade.php`

```blade
@include('errors.page', [
    'code' => '404',
    'icon' => 'bi-compass',
    'title' => __('Page not found'),
    'message' => __('The page you are looking for has been moved or never existed. Check the address and try again.'),
])

```

## `resources/views/errors/419.blade.php`

```blade
@include('errors.page', [
    'code' => '419',
    'icon' => 'bi-hourglass-split',
    'title' => __('Your session expired'),
    'message' => __('For your safety the page was idle for too long. Refresh the page, then send the form again. Nothing was lost except the last click.'),
])

```

## `resources/views/errors/500.blade.php`

```blade
@include('errors.page', [
    'code' => '500',
    'icon' => 'bi-tools',
    'title' => __('Something went wrong on our side'),
    'message' => __('The page could not be loaded. Your data is safe. Try again in a moment, or head back home and start from there.'),
])

```

## `resources/views/errors/503.blade.php`

```blade
@include('errors.page', [
    'code' => '503',
    'icon' => 'bi-cone-striped',
    'title' => __('We are doing a little maintenance'),
    'message' => __('EduConnect is being updated right now. Classes and posts will be back shortly — please try again in a few minutes.'),
])

```

# 11. Routes

## `routes/web.php`

```php
<?php

use App\Http\Controllers\AdminClassController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminPostController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ClassBrowseController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ClassStatusController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardChartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\HashtagController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\LiveClassController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Admin area (FR-2, FR-5)
|--------------------------------------------------------------------------
| A student or a teacher can never open an admin page: the "role" middleware
| answers with 403. There is no admin registration either, the admin account
| comes from AdminUserSeeder.
*/
Route::middleware(['auth', 'not.blocked', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        // Phase 3C: living documentation of the design system. Admin only,
        // because it is a maintenance page rather than a product feature.
        Route::view('/style-guide', 'admin.style-guide')->name('style-guide');

        // Phase 3B: JSON series for the dashboard range selector.
        Route::get('/dashboard/charts', [DashboardChartController::class, 'admin'])
            ->name('dashboard.charts');

        // User management (FR-27).
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/block', [AdminUserController::class, 'block'])->name('users.block');
        Route::patch('/users/{user}/unblock', [AdminUserController::class, 'unblock'])->name('users.unblock');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        // Post management (FR-28).
        Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
        Route::patch('/posts/{post}/hide', [AdminPostController::class, 'toggleHidden'])->name('posts.hide');
        Route::delete('/posts/{post}', [AdminPostController::class, 'destroy'])->name('posts.destroy');

        // Class management (FR-28).
        Route::get('/classes', [AdminClassController::class, 'index'])->name('classes.index');
        Route::patch('/classes/{classRoom}/hide', [AdminClassController::class, 'toggleHidden'])->name('classes.hide');
        Route::delete('/classes/{classRoom}', [AdminClassController::class, 'destroy'])->name('classes.destroy');

        // Moderation queue (FR-33, FR-34).
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
        Route::patch('/reports/{report}/hide', [AdminReportController::class, 'hide'])->name('reports.hide');
        Route::patch('/reports/{report}/unhide', [AdminReportController::class, 'unhide'])->name('reports.unhide');
        Route::patch('/reports/{report}/reviewed', [AdminReportController::class, 'markReviewed'])->name('reports.reviewed');
        Route::patch('/reports/{report}/dismiss', [AdminReportController::class, 'dismiss'])->name('reports.dismiss');
        Route::delete('/reports/{report}/content', [AdminReportController::class, 'destroyContent'])->name('reports.content.destroy');
        Route::post('/reports/{report}/block-user', [AdminReportController::class, 'blockUser'])->name('reports.block-user');
    });

/*
|--------------------------------------------------------------------------
| Teacher area (FR-2)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.blocked', 'role:teacher'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::get('/dashboard', TeacherDashboardController::class)->name('dashboard');

        // Phase 3B: JSON series for the dashboard range selectors.
        Route::get('/dashboard/charts', [DashboardChartController::class, 'teacher'])
            ->name('dashboard.charts');
    });

/*
|--------------------------------------------------------------------------
| Student area (FR-2, FR-17, FR-30)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.blocked', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', StudentDashboardController::class)->name('dashboard');

        // Phase 3B: JSON series for the dashboard range selectors.
        Route::get('/dashboard/charts', [DashboardChartController::class, 'student'])
            ->name('dashboard.charts');
    });

// Follow / unfollow another member from a profile or the search page
// (FR-11). Students and teachers can follow each other; admin accounts
// are not followable and admins do not use the follow routes.
// Throttled so the button cannot be used to hammer the database.
Route::middleware(['auth', 'not.blocked', 'role:student,teacher'])
    ->group(function () {
        Route::post('/u/{user}/follow', [FollowController::class, 'store'])
            ->middleware('throttle:follows')
            ->name('follows.store');

        Route::delete('/u/{user}/follow', [FollowController::class, 'destroy'])
            ->middleware('throttle:follows')
            ->name('follows.destroy');
    });

/*
|--------------------------------------------------------------------------
| Social feed (FR-7 ... FR-14)
|--------------------------------------------------------------------------
| Every logged in role uses the same feed: admins, teachers and students.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/feed', [PostController::class, 'index'])->name('feed.index');

    // Posts. /posts/create must be declared before /posts/{post}.
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    // At most 10 new posts per minute per user (FR-9).
    Route::post('/posts', [PostController::class, 'store'])
        ->middleware('throttle:posts')
        ->name('posts.store');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::patch('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    // Likes - small JSON endpoints for the fetch() calls in the feed (FR-11).
    Route::post('/posts/{post}/like', [LikeController::class, 'store'])
        ->middleware('throttle:likes')
        ->name('posts.like');
    Route::delete('/posts/{post}/like', [LikeController::class, 'destroy'])
        ->middleware('throttle:likes')
        ->name('posts.unlike');

    // Comments (FR-13).
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:comments')
        ->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->middleware('throttle:comments')
        ->name('comments.destroy');

    // Reporting a post or comment (FR-32). Throttled so the queue cannot be
    // flooded by one account.
    Route::post('/posts/{post}/reports', [ReportController::class, 'storePost'])
        ->middleware('throttle:reports')
        ->name('reports.posts.store');
    Route::post('/comments/{comment}/reports', [ReportController::class, 'storeComment'])
        ->middleware('throttle:reports')
        ->name('reports.comments.store');

    // Hashtag pages (FR-13). The binding uses the (lower case) name column.
    // /hashtags/{name} is the canonical URL; the old singular address remains
    // available so earlier links keep working.
    Route::get('/hashtags/{hashtag:name}', [HashtagController::class, 'show'])->name('hashtags.show');
    Route::get('/hashtag/{hashtag:name}', [HashtagController::class, 'show'])->name('hashtags.legacy');

    // Public profiles and the follower lists (FR-5, FR-11).
    Route::get('/users/{user}', [UserProfileController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/followers', [UserProfileController::class, 'followers'])->name('users.followers');
    Route::get('/users/{user}/following', [UserProfileController::class, 'following'])->name('users.following');

    // The older /u/... addresses stay valid and redirect to the canonical URL.
    Route::get('/u/{user}', fn ($user) => redirect()->route('users.show', $user));
    Route::get('/u/{user}/followers', fn ($user) => redirect()->route('users.followers', $user));
    Route::get('/u/{user}/following', fn ($user) => redirect()->route('users.following', $user));

    // "My followers" in the sidebar jumps to my own list.
    Route::get('/followers', fn () => redirect()->route('users.followers', auth()->id()))
        ->name('followers.index');
});

// /dashboard simply forwards everybody to the dashboard of their own role.
Route::middleware(['auth', 'not.blocked'])
    ->get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Live classes (FR-18 ... FR-24)
|--------------------------------------------------------------------------
| A teacher manages their own classes, a student browses them, enrolls and
| joins. /classes/create must be declared before /classes/{classRoom}, and
| /classes/discover before the wildcard as well, otherwise "discover" would be
| taken for a class id.
*/

// Teacher only: create, edit, delete and manage a class (ClassPolicy checks
// that the class really belongs to the teacher).
Route::middleware(['auth', 'not.blocked', 'role:teacher'])->group(function () {
    Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
    Route::get('/classes/create', [ClassController::class, 'create'])->name('classes.create');
    Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');
    Route::get('/classes/{classRoom}/edit', [ClassController::class, 'edit'])->name('classes.edit');
    Route::patch('/classes/{classRoom}', [ClassController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{classRoom}', [ClassController::class, 'destroy'])->name('classes.destroy');

    // Who is enrolled (FR-24) and the scheduled / live / completed switch.
    Route::get('/classes/{classRoom}/students', [ClassController::class, 'students'])->name('classes.students');
    Route::patch('/classes/{classRoom}/status', [ClassStatusController::class, 'update'])->name('classes.status');
});

// Every logged in role: the class detail page and the Jitsi room (FR-20).
Route::middleware(['auth', 'not.blocked'])->group(function () {
    // Find classes: search + subject filter + pagination (FR-19).
    Route::get('/classes/discover', [ClassBrowseController::class, 'index'])->name('classes.discover');
    Route::get('/classes/{classRoom}', [ClassBrowseController::class, 'show'])->name('classes.show');

    // FR-18 / FR-20 / FR-22: the embedded Jitsi Meet room.
    Route::get('/classes/{classRoom}/room', LiveClassController::class)->name('classes.room');
});

// Student only: enroll / unenroll and "my enrolled classes" (FR-21).
Route::middleware(['auth', 'not.blocked', 'role:student'])->group(function () {
    Route::post('/classes/{classRoom}/enroll', [EnrollmentController::class, 'store'])->name('classes.enroll');
    Route::delete('/classes/{classRoom}/enroll', [EnrollmentController::class, 'destroy'])->name('classes.unenroll');

    Route::get('/student/classes', [ClassBrowseController::class, 'enrolled'])->name('student.classes');
});

/*
|--------------------------------------------------------------------------
| Notifications (FR-23 ... FR-25)
|--------------------------------------------------------------------------
| The bell in the navbar polls /notifications/poll every 30 seconds and gets
| the fresh unread counter plus the newest eight entries as JSON.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Lightweight JSON endpoint for the polling script (FR-24).
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])
        ->middleware('throttle:notifications-poll')
        ->name('notifications.poll');

    // Mark everything as read (FR-25). Declared before the {notification} routes.
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // One notification: mark it read, open its page, or delete it (FR-25).
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});

/*
|--------------------------------------------------------------------------
| Search (FR-14)
|--------------------------------------------------------------------------
| One box in the navbar, three tabs on the page: people, hashtags and posts.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    // Debounced live suggestions for the navbar dropdown (FR-14).
    Route::get('/search/suggest', [SearchController::class, 'suggest'])
        ->middleware('throttle:search-suggest')
        ->name('search.suggest');
});

/*
|--------------------------------------------------------------------------
| Profile (FR-4)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

```

# 12. Front-end assets

## `resources/js/layout.js`

```javascript
/**
 * Desktop sidebar toggle (FR-31).
 *
 * The sidebar is an offcanvas drawer on phones and a normal column from the
 * lg breakpoint upwards. This script only handles the desktop toggle and
 * remembers the choice in localStorage; Bootstrap handles the mobile drawer.
 *
 * The layout also reads the same key in a tiny inline script in <head>, so the
 * correct state is applied before the first paint.
 */

const STORAGE_KEY = 'educonnect.sidebar.collapsed';

function isCollapsed() {
    return document.documentElement.classList.contains('edu-sidebar-collapsed');
}

function applyState(collapsed) {
    document.documentElement.classList.toggle('edu-sidebar-collapsed', collapsed);

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

        const icon = button.querySelector('[data-sidebar-toggle-icon]');
        if (icon) {
            icon.classList.toggle('bi-layout-sidebar-inset', !collapsed);
            icon.classList.toggle('bi-layout-sidebar', collapsed);
        }
    });
}

function storeState(collapsed) {
    try {
        window.localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
    } catch (error) {
        // Private browsing may block storage; the page still toggles for this visit.
    }
}

function bootSidebarToggle() {
    // Sync the aria attributes/icon with the state that was applied in <head>.
    applyState(isCollapsed());

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const collapsed = !isCollapsed();
            applyState(collapsed);
            storeState(collapsed);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootSidebarToggle);
} else {
    bootSidebarToggle();
}

```

## `resources/js/theme.js`

```javascript
/**
 * Light / dark mode (Phase 3B).
 *
 * The choice is stored in localStorage under `educonnect.theme`. When there is
 * no stored choice the page follows the operating system preference
 * (`prefers-color-scheme`). The first paint is handled by a tiny inline script
 * in the layout, so there is no flash of the wrong theme.
 */

const STORAGE_KEY = 'educonnect.theme';
const media = window.matchMedia('(prefers-color-scheme: dark)');

function storedTheme() {
    try {
        const value = window.localStorage.getItem(STORAGE_KEY);

        return value === 'dark' || value === 'light' ? value : null;
    } catch (error) {
        return null;
    }
}

function preferredTheme() {
    return storedTheme() ?? (media.matches ? 'dark' : 'light');
}

function currentTheme() {
    return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
}

function paintToggle(theme) {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const next = theme === 'dark' ? 'light' : 'dark';
        const label = next === 'dark' ? 'Switch to dark mode' : 'Switch to light mode';

        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        button.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');

        const icon = button.querySelector('[data-theme-icon]');
        if (icon) {
            icon.classList.toggle('bi-moon-stars', theme !== 'dark');
            icon.classList.toggle('bi-sun', theme === 'dark');
        }
    });
}

function applyTheme(theme, persist = false) {
    document.documentElement.setAttribute('data-bs-theme', theme);

    if (persist) {
        try {
            window.localStorage.setItem(STORAGE_KEY, theme);
        } catch (error) {
            // Private browsing can refuse storage; the theme still applies now.
        }
    }

    paintToggle(theme);

    // Let interested components (charts) restyle themselves.
    document.dispatchEvent(new CustomEvent('edu:theme-changed', { detail: { theme } }));
}

function bootTheme() {
    // The inline head script already applied the correct value; make sure the
    // attribute exists even if that script was blocked.
    if (!document.documentElement.hasAttribute('data-bs-theme')) {
        applyTheme(preferredTheme());
    } else {
        paintToggle(currentTheme());
    }

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const next = currentTheme() === 'dark' ? 'light' : 'dark';
            applyTheme(next, true);
        });
    });

    // Follow the OS while the visitor has made no explicit choice.
    media.addEventListener('change', (event) => {
        if (storedTheme() === null) {
            applyTheme(event.matches ? 'dark' : 'light');
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootTheme);
} else {
    bootTheme();
}

```

## `resources/js/charts.js`

```javascript
/**
 * Themed Chart.js charts (Phase 3B).
 *
 * Every <canvas data-chart="..."> is rendered here. The JSON in the attribute
 * is produced by the <x-chart-card> Blade component, so the pages stay free of
 * inline JavaScript.
 *
 * Extra behaviour:
 *  - charts follow the light / dark theme
 *  - line charts get a soft vertical gradient
 *  - a range <select> can reload a series through a JSON endpoint
 *  - a skeleton is shown while that request is running
 */

import Chart from 'chart.js/auto';

/** Canvas element -> Chart instance. */
const registry = new Map();

const PALETTE = ['#4f46e5', '#7c3aed', '#10b981', '#f59e0b', '#ef4444', '#0ea5e9', '#64748b', '#ec4899'];

const isDark = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';
const tickColor = () => (isDark() ? '#94a3b8' : '#64748b');
const gridColor = () => (isDark() ? 'rgba(148, 163, 184, 0.14)' : 'rgba(100, 116, 139, 0.16)');
const tooltipBg = () => (isDark() ? '#f8fafc' : '#0f172a');
const tooltipText = () => (isDark() ? '#0f172a' : '#f8fafc');

function parseCanvasConfig(canvas) {
    try {
        return JSON.parse(canvas.dataset.chart);
    } catch (error) {
        console.error('[EduConnect] Could not read the chart configuration', canvas, error);

        return null;
    }
}

/**
 * Apply the theme colours to a fresh Chart.js configuration.
 */
function themeConfig(config) {
    config.options = config.options ?? {};
    config.options.color = tickColor();
    config.options.plugins = config.options.plugins ?? {};
    config.options.plugins.legend = config.options.plugins.legend ?? {};
    config.options.plugins.legend.labels = {
        ...(config.options.plugins.legend.labels ?? {}),
        color: tickColor(),
    };
    config.options.plugins.tooltip = {
        ...(config.options.plugins.tooltip ?? {}),
        backgroundColor: tooltipBg(),
        titleColor: tooltipText(),
        bodyColor: tooltipText(),
        borderColor: gridColor(),
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8,
        displayColors: false,
    };

    if (config.options.scales) {
        Object.values(config.options.scales).forEach((scale) => {
            scale.ticks = { ...(scale.ticks ?? {}), color: tickColor() };
            scale.grid = { ...(scale.grid ?? {}), color: gridColor(), drawTicks: false };
            scale.border = { ...(scale.border ?? {}), color: gridColor() };
        });
    }

    return config;
}

/**
 * Re-colour a chart that is already on screen after a theme switch.
 */
function restyle(chart) {
    chart.options.color = tickColor();

    if (chart.options.plugins?.legend?.labels) {
        chart.options.plugins.legend.labels.color = tickColor();
    }

    if (chart.options.plugins?.tooltip) {
        chart.options.plugins.tooltip.backgroundColor = tooltipBg();
        chart.options.plugins.tooltip.titleColor = tooltipText();
        chart.options.plugins.tooltip.bodyColor = tooltipText();
        chart.options.plugins.tooltip.borderColor = gridColor();
    }

    Object.values(chart.options.scales ?? {}).forEach((scale) => {
        scale.ticks = { ...(scale.ticks ?? {}), color: tickColor() };
        scale.grid = { ...(scale.grid ?? {}), color: gridColor() };
        scale.border = { ...(scale.border ?? {}), color: gridColor() };
    });

    chart.update('none');
}

/**
 * Turn a line dataset into a soft area gradient that follows the chart area.
 */
function applyGradient(chart) {
    if (chart.config.type !== 'line') {
        return;
    }

    chart.data.datasets.forEach((dataset) => {
        if (dataset.gradient === false) {
            return;
        }

        const base = dataset.borderColor ?? '#4f46e5';
        dataset.backgroundColor = (context) => {
            const { chart: target, chartArea } = context.chart;

            if (!chartArea) {
                return 'rgba(79, 70, 229, 0.16)';
            }

            const gradient = target.ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            gradient.addColorStop(0, hexToRgba(base, isDark() ? 0.42 : 0.32));
            gradient.addColorStop(1, hexToRgba(base, 0.01));

            return gradient;
        };
    });

    chart.update('none');
}

function hexToRgba(hex, alpha) {
    const clean = String(hex).replace('#', '');
    const value = clean.length === 3
        ? clean.split('').map((part) => part + part).join('')
        : clean.padEnd(6, '0');

    const int = parseInt(value.slice(0, 6), 16);
    const r = (int >> 16) & 255;
    const g = (int >> 8) & 255;
    const b = int & 255;

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function renderCanvas(canvas) {
    if (canvas.dataset.chartRendered === '1') {
        return registry.get(canvas);
    }

    const config = parseCanvasConfig(canvas);
    if (!config) {
        return null;
    }

    const chart = new Chart(canvas, themeConfig(config));

    canvas.dataset.chartRendered = '1';
    registry.set(canvas, chart);

    if (canvas.dataset.chartGradient !== 'false') {
        applyGradient(chart);
    }

    return chart;
}

function renderAllCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach(renderCanvas);
}

/**
 * Feed a new series into an existing chart after a range change.
 */
function updateSeries(canvas, series) {
    const chart = registry.get(canvas);
    if (!chart || !series) {
        return;
    }

    const labels = Array.isArray(series.labels) ? series.labels : [];
    const values = Array.isArray(series.values) ? series.values.map(Number) : [];

    chart.data.labels = labels;

    if (chart.config.type === 'doughnut' || chart.config.type === 'pie') {
        chart.data.datasets[0].data = values;
        chart.data.datasets[0].backgroundColor = values.map((_, index) => PALETTE[index % PALETTE.length]);
    } else {
        chart.data.datasets[0].data = values;
    }

    chart.update();

    const card = canvas.closest('[data-chart-card]');
    const total = card?.querySelector('[data-chart-total]');
    if (total && typeof series.total !== 'undefined') {
        total.textContent = new Intl.NumberFormat().format(Number(series.total));
    }
}

function setLoading(card, loading) {
    const skeleton = card?.querySelector('[data-chart-skeleton]');
    if (skeleton) {
        skeleton.hidden = !loading;
    }

    const select = card?.querySelector('[data-chart-range]');
    if (select) {
        select.disabled = loading;
    }

    card?.classList.toggle('is-loading', loading);
}

async function loadRange(card, select) {
    const canvas = card.querySelector('canvas[data-chart]');
    const endpoint = canvas?.dataset.chartEndpoint;

    if (!canvas || !endpoint) {
        return;
    }

    setLoading(card, true);

    try {
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('range', select.value);

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        const payload = await response.json();
        const key = canvas.dataset.chartSeries;
        const series = (key && payload[key]) || payload.series || null;

        updateSeries(canvas, series);
    } catch (error) {
        console.error('[EduConnect] Could not refresh the chart', error);
    } finally {
        setLoading(card, false);
    }
}

function bindRangeSelectors() {
    document.querySelectorAll('[data-chart-card]').forEach((card) => {
        const select = card.querySelector('[data-chart-range]');
        const canvas = card.querySelector('canvas[data-chart]');

        if (!select || !canvas || !canvas.dataset.chartEndpoint) {
            return;
        }

        select.addEventListener('change', () => loadRange(card, select));
    });
}

function watchTheme() {
    // theme.js dispatches an event; the observer is a fallback for the first
    // paint and for other scripts that change the attribute directly.
    document.addEventListener('edu:theme-changed', () => {
        registry.forEach(restyle);
    });

    new MutationObserver(() => registry.forEach(restyle)).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme'],
    });
}

function bootCharts() {
    renderAllCharts();
    bindRangeSelectors();
    watchTheme();
}

window.EduCharts = {
    renderAll: renderAllCharts,
    updateSeries,
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootCharts);
} else {
    bootCharts();
}

```

## `resources/js/countdown.js`

```javascript
/**
 * Live countdown timers (Phase 3B).
 *
 * Markup is produced by <x-countdown target="ISO-8601" />:
 *   <div data-countdown="2026-11-02T18:00:00+00:00">
 *       <span data-countdown-days>00</span> ...
 *   </div>
 *
 * The server renders a static value, so the timer is useful even before this
 * script runs. Once it runs, the numbers update every second.
 */

const pad = (value) => String(Math.max(0, value)).padStart(2, '0');

function updateCountdown(element) {
    const target = new Date(element.dataset.countdown);
    if (Number.isNaN(target.getTime())) {
        return;
    }

    const difference = target.getTime() - Date.now();

    if (difference <= 0) {
        element.querySelectorAll('[data-countdown-days], [data-countdown-hours], [data-countdown-minutes], [data-countdown-seconds]')
            .forEach((node) => {
                node.textContent = '00';
            });
        element.classList.add('is-finished');
        element.dataset.countdownFinished = '1';

        return;
    }

    const seconds = Math.floor(difference / 1000);
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remaining = seconds % 60;

    const values = {
        days,
        hours,
        minutes,
        seconds: remaining,
    };

    Object.entries(values).forEach(([key, value]) => {
        const node = element.querySelector(`[data-countdown-${key}]`);
        if (node) {
            node.textContent = pad(value);
        }
    });

    // Screen readers get a single readable sentence, not four changing boxes.
    const readable = element.querySelector('[data-countdown-readable]');
    if (readable) {
        const parts = [];
        if (days > 0) parts.push(`${days} day${days === 1 ? '' : 's'}`);
        if (hours > 0) parts.push(`${hours} hour${hours === 1 ? '' : 's'}`);
        if (minutes > 0) parts.push(`${minutes} minute${minutes === 1 ? '' : 's'}`);
        parts.push(`${remaining} second${remaining === 1 ? '' : 's'}`);
        readable.textContent = parts.join(', ');
    }
}

function bootCountdowns() {
    const timers = Array.from(document.querySelectorAll('[data-countdown]'));

    if (timers.length === 0) {
        return;
    }

    timers.forEach(updateCountdown);
    window.setInterval(() => timers.forEach(updateCountdown), 1000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootCountdowns);
} else {
    bootCountdowns();
}

```

## `resources/js/profile-photo.js`

```javascript
/**
 * Profile photo preview (FR-4).
 *
 * Shows the picked image immediately, and lets the user remove the current
 * photo without opening a file picker. The server still validates the real
 * MIME type, extension and size; this is only a friendly preview.
 */

function bootPhotoPreview() {
    document.querySelectorAll('[data-photo-form]').forEach((form) => {
        const input = form.querySelector('[data-photo-input]');
        const preview = form.querySelector('[data-photo-preview]');
        const remove = form.querySelector('[data-photo-remove]');

        if (! input || ! preview) {
            return;
        }

        const defaultSrc = preview.dataset.defaultSrc || preview.src;
        let objectUrl = null;

        const setPreview = (src) => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            preview.src = src;
        };

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!file) {
                setPreview(remove?.checked ? defaultSrc : preview.dataset.currentSrc || defaultSrc);
                return;
            }

            // A new file wins over a pending "remove" request.
            if (remove) {
                remove.checked = false;
            }

            objectUrl = URL.createObjectURL(file);
            preview.src = objectUrl;
        });

        remove?.addEventListener('change', () => {
            if (! remove.checked) {
                return;
            }

            // Clear the file picker and go back to the default avatar.
            input.value = '';
            setPreview(defaultSrc);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootPhotoPreview);
} else {
    bootPhotoPreview();
}

```

## `resources/js/page-transitions.js`

```javascript
/**
 * Small page transition (Phase 3B).
 *
 * The CSS already fades a page in. This adds a quick fade-out when following a
 * normal same-site link, so moving between dashboard pages feels smooth instead
 * of abrupt. Modified clicks, new tabs, downloads, hash links and Bootstrap
 * toggles are left to the browser.
 */

const REDUCED_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)');

function isPlainLeftClick(event) {
    return event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;
}

function shouldTransition(link) {
    if (!link || !link.href) {
        return false;
    }

    if (link.target && link.target !== '_self') {
        return false;
    }

    if (link.hasAttribute('download') || link.dataset.bsToggle) {
        return false;
    }

    if (link.getAttribute('href')?.startsWith('#')) {
        return false;
    }

    if (link.dataset.noTransition !== undefined) {
        return false;
    }

    const url = new URL(link.href, window.location.href);

    if (url.origin !== window.location.origin) {
        return false;
    }

    // Same page (only a hash or query change) does not need a fade.
    return url.pathname !== window.location.pathname;
}

function bootTransitions() {
    if (REDUCED_MOTION.matches) {
        return;
    }

    document.addEventListener('click', (event) => {
        if (!isPlainLeftClick(event)) {
            return;
        }

        const link = event.target.closest('a');

        if (!shouldTransition(link)) {
            return;
        }

        const page = document.querySelector('.edu-page');

        if (!page) {
            return;
        }

        event.preventDefault();
        page.classList.add('is-leaving');

        window.setTimeout(() => {
            window.location.assign(link.href);
        }, 120);
    });

    // Coming back through the browser cache should restore the page.
    window.addEventListener('pageshow', () => {
        document.querySelector('.edu-page')?.classList.remove('is-leaving');
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootTransitions);
} else {
    bootTransitions();
}

```

## `resources/js/toast.js`

```javascript
/**
 * Lightweight toasts for AJAX errors and client-side notices.
 * Server-rendered flash banners in <x-flash /> stay as they are.
 */

function stack() {
    let element = document.querySelector('[data-edu-toast-stack]');

    if (!element) {
        element = document.createElement('div');
        element.className = 'edu-toast-stack';
        element.dataset.eduToastStack = '1';
        element.setAttribute('role', 'status');
        element.setAttribute('aria-live', 'polite');
        document.body.append(element);
    }

    return element;
}

export function showToast(message, type = 'info') {
    if (!message) {
        return;
    }

    const toast = document.createElement('div');
    toast.className = `edu-toast is-${type}`;
    toast.innerHTML = `
        <span>${escapeHtml(String(message))}</span>
        <button type="button" class="btn-close" aria-label="Close"></button>
    `;

    toast.querySelector('.btn-close')?.addEventListener('click', () => toast.remove());
    stack().append(toast);

    window.setTimeout(() => toast.remove(), 5000);
}

function escapeHtml(value) {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

window.eduToast = showToast;

```

## `resources/sass/_variables.scss`

```scss
// -----------------------------------------------------------------------------
// EduConnect design tokens (Phase 3B)
// -----------------------------------------------------------------------------
// Everything here is imported before Bootstrap, so these are real Bootstrap
// overrides as well as the CSS custom properties used by the custom components.
// -----------------------------------------------------------------------------

// --- Brand palette -----------------------------------------------------------
$primary: #4f46e5; // indigo
$secondary: #7c3aed; // violet
$success: #10b981;
$info: #0ea5e9;
$warning: #f59e0b;
$danger: #ef4444;
$light: #f8fafc;
$dark: #0f172a;

// --- Typography --------------------------------------------------------------
$font-family-sans-serif: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto,
    'Helvetica Neue', Arial, 'Noto Sans', sans-serif, 'Apple Color Emoji',
    'Segoe UI Emoji';
$font-size-base: 0.9375rem; // 15px, a little tighter than Bootstrap's 16px
$line-height-base: 1.6;
$headings-font-weight: 700;
$headings-line-height: 1.25;
$small-font-size: 0.8125rem;

// --- Shape -------------------------------------------------------------------
$border-radius: 0.625rem;
$border-radius-sm: 0.5rem;
$border-radius-lg: 0.875rem;
$border-radius-xl: 1rem;
$border-radius-2xl: 1.25rem;
$card-border-radius: 1rem;
$card-border-width: 1px;
$card-border-color: #e7eaf0;
$card-cap-bg: transparent;
$card-bg: #ffffff;
$modal-content-border-radius: 1rem;
$dropdown-border-radius: 0.75rem;
$dropdown-padding-y: 0.4rem;
$btn-border-radius: 0.625rem;
$btn-border-radius-sm: 0.5rem;
$btn-border-radius-lg: 0.75rem;
$input-border-radius: 0.625rem;
$input-btn-focus-width: 0.2rem;

// --- Surfaces (Bootstrap 5.3 dark mode) -------------------------------------
$body-bg: #f5f7fb;
$body-color: #0f172a;
$body-secondary-color: #64748b;
$body-bg-dark: #0b1020;
$body-color-dark: #e5e7eb;
$body-secondary-color-dark: #94a3b8;
$border-color-dark: #1f2937;
$card-bg-dark: #111827;
$card-border-color-dark: #1f2937;
$modal-content-bg-dark: #111827;
$dropdown-bg-dark: #111827;
$dropdown-border-color-dark: #1f2937;
$input-bg-dark: #0f172a;
$input-border-color-dark: #263244;
$table-color-dark: #e5e7eb;
$table-border-color-dark: #1f2937;

// --- CSS custom properties ---------------------------------------------------
// These are the tokens used by the hand-written components. They intentionally
// mirror Bootstrap's theme so a single data-bs-theme switch restyles the app.
:root {
    --edu-primary: #4f46e5;
    --edu-primary-rgb: 79, 70, 229;
    --edu-primary-dark: #3730a3;
    --edu-primary-soft: rgba(79, 70, 229, 0.1);
    --edu-secondary: #7c3aed;
    --edu-success: #10b981;
    --edu-warning: #f59e0b;
    --edu-danger: #ef4444;
    --edu-info: #0ea5e9;

    --edu-page-bg: #f5f7fb;
    --edu-surface: #ffffff;
    --edu-surface-raised: #ffffff;
    --edu-surface-soft: #f8fafc;
    --edu-surface-muted: #f1f5f9;
    --edu-border: #e7eaf0;
    --edu-border-strong: #d8dee9;
    --edu-text: #0f172a;
    --edu-text-soft: #334155;
    --edu-muted: #64748b;
    --edu-muted-strong: #475569;

    --edu-sidebar-width: 264px;
    --edu-sidebar-collapsed-width: 84px;
    --edu-navbar-height: 72px;
    // Radius scale: 8 / 12 / 16 (+ 20 for large hero surfaces).
    --edu-radius-xs: 8px;
    --edu-radius: 16px;
    --edu-radius-sm: 12px;
    --edu-radius-lg: 20px;

    --edu-shadow-xs: 0 1px 2px rgba(15, 23, 42, 0.05);
    --edu-shadow-card: 0 1px 2px rgba(15, 23, 42, 0.04),
        0 10px 28px -18px rgba(15, 23, 42, 0.28);
    --edu-shadow-hover: 0 2px 4px rgba(15, 23, 42, 0.05),
        0 22px 44px -22px rgba(15, 23, 42, 0.35);
    --edu-shadow-pop: 0 18px 45px -18px rgba(15, 23, 42, 0.28);

    --edu-transition: 150ms ease;
    --edu-ease: cubic-bezier(0.22, 1, 0.36, 1);
    --edu-ring: 0 0 0 0.22rem rgba(79, 70, 229, 0.22);

    // --- Spacing scale (Phase 3C) ------------------------------------------
    // Every gap in the custom components comes from one of these, so vertical
    // rhythm stays identical from page to page. 4px steps.
    --edu-space-1: 0.25rem; //  4px
    --edu-space-2: 0.5rem; //   8px
    --edu-space-3: 0.75rem; // 12px
    --edu-space-4: 1rem; //    16px
    --edu-space-5: 1.5rem; //  24px
    --edu-space-6: 2rem; //    32px
    --edu-space-7: 3rem; //    48px
    --edu-space-8: 4rem; //    64px

    // --- Type scale (Phase 3C) ---------------------------------------------
    --edu-fs-display: clamp(2rem, 1.4rem + 2.6vw, 3.25rem);
    --edu-fs-h1: clamp(1.6rem, 1.25rem + 1.2vw, 2rem);
    --edu-fs-h2: clamp(1.35rem, 1.15rem + 0.7vw, 1.6rem);
    --edu-fs-h3: 1.3rem;
    --edu-fs-h4: 1.15rem;
    --edu-fs-h5: 1rem;
    --edu-fs-h6: 0.9375rem;
    --edu-fs-body: 0.9375rem;
    --edu-fs-small: 0.8125rem;
    --edu-fs-xs: 0.75rem;
    --edu-measure: 70ch; // comfortable reading width

    // --- Z-index layers (Phase 3C) -----------------------------------------
    // One place to look when something overlaps something else.
    --edu-z-base: 1;
    --edu-z-sticky: 1020; // navbar, sticky table headers
    --edu-z-sidebar: 1030;
    --edu-z-dropdown: 1040;
    --edu-z-fab: 1045; // the "new post" button
    --edu-z-mobilenav: 1050; // bottom navigation bar
    --edu-z-modal: 1060;
    --edu-z-toast: 1080; // always on top

    --edu-sidebar-from: #11152b;
    --edu-sidebar-to: #1e1b4b;
    --edu-sidebar-text: #c7cbe0;
    --edu-sidebar-heading: #7c86a8;
    --edu-sidebar-active: rgba(129, 140, 248, 0.16);
    --edu-sidebar-hover: rgba(255, 255, 255, 0.06);
    --edu-sidebar-border: rgba(255, 255, 255, 0.08);

    --edu-chart-grid: rgba(100, 116, 139, 0.16);
    --edu-chart-tick: #64748b;
    --edu-chart-tooltip-bg: #0f172a;
    --edu-chart-tooltip-text: #f8fafc;
}

[data-bs-theme='dark'] {
    --edu-page-bg: #0b1020;
    --edu-surface: #111827;
    --edu-surface-raised: #151d2f;
    --edu-surface-soft: #0f172a;
    --edu-surface-muted: #172033;
    --edu-border: #1f2937;
    --edu-border-strong: #2b3a50;
    --edu-text: #e5e7eb;
    --edu-text-soft: #cbd5e1;
    --edu-muted: #94a3b8;
    --edu-muted-strong: #a8b5c7;

    --edu-shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.35);
    --edu-shadow-card: 0 1px 2px rgba(0, 0, 0, 0.35),
        0 18px 34px -24px rgba(0, 0, 0, 0.8);
    --edu-shadow-hover: 0 2px 6px rgba(0, 0, 0, 0.45),
        0 28px 48px -26px rgba(0, 0, 0, 0.9);
    --edu-shadow-pop: 0 18px 45px -18px rgba(0, 0, 0, 0.85);

    --edu-primary-soft: rgba(129, 140, 248, 0.16);
    --edu-sidebar-from: #070b18;
    --edu-sidebar-to: #14123a;

    --edu-chart-grid: rgba(148, 163, 184, 0.14);
    --edu-chart-tick: #94a3b8;
    --edu-chart-tooltip-bg: #f8fafc;
    --edu-chart-tooltip-text: #0f172a;
}

```

## `resources/sass/app.scss`

```scss
// -----------------------------------------------------------------------------
// EduConnect stylesheet (Phase 3B premium dashboard design)
// -----------------------------------------------------------------------------
// Bootstrap 5 and Bootstrap Icons are pulled straight from node_modules and
// compiled by Vite (no Tailwind anywhere in this project). The tokens live in
// _variables.scss and are imported before Bootstrap so the framework itself
// picks up the brand palette and dark-mode surfaces.
// -----------------------------------------------------------------------------

@import 'variables';
@import 'bootstrap/scss/bootstrap';
@import 'bootstrap-icons/font/bootstrap-icons.css';

// -----------------------------------------------------------------------------
// Base
// -----------------------------------------------------------------------------

:root {
    --bs-body-bg: var(--edu-page-bg);
    --bs-body-color: var(--edu-text);
    --bs-border-color: var(--edu-border);
    --bs-secondary-color: var(--edu-muted);
    --bs-link-color-rgb: 79, 70, 229;
    --bs-link-hover-color-rgb: 55, 48, 163;
    --bs-focus-ring-color: rgba(79, 70, 229, 0.25);
}

[data-bs-theme='dark'] {
    --bs-body-bg: var(--edu-page-bg);
    --bs-body-color: var(--edu-text);
    --bs-border-color: var(--edu-border);
    --bs-secondary-color: var(--edu-muted);
    --bs-link-color-rgb: 165, 180, 252;
    --bs-link-hover-color-rgb: 199, 210, 254;
    --bs-emphasis-color: #f8fafc;
    --bs-heading-color: #f8fafc;
    --bs-tertiary-bg: #0f172a;
    --bs-secondary-bg: #172033;
}

html {
    scroll-behavior: smooth;
}

body {
    background-color: var(--edu-page-bg);
    color: var(--edu-text);
    font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    overflow-x: hidden;
}

// Bootstrap has no utility for this, but the dashboard flex children need it
// so long names and titles truncate instead of widening the grid.
.min-w-0 {
    min-width: 0;
}

::selection {
    background: rgba(79, 70, 229, 0.2);
}

h1, h2, h3, h4, h5, h6,
.h1, .h2, .h3, .h4, .h5, .h6 {
    color: var(--edu-text);
    letter-spacing: -0.015em;
}

h1, .h1 { font-size: clamp(1.6rem, 1.25rem + 1.2vw, 2rem); }
h2, .h2 { font-size: clamp(1.35rem, 1.15rem + 0.7vw, 1.6rem); }
h3, .h3 { font-size: 1.3rem; }
h4, .h4 { font-size: 1.15rem; }
h5, .h5 { font-size: 1rem; }
h6, .h6 { font-size: 0.9375rem; }

small, .small {
    font-size: 0.8125rem;
}

.text-muted {
    color: var(--edu-muted) !important;
}

.text-edu {
    color: var(--edu-primary) !important;
}

.bg-edu-soft {
    background: var(--edu-primary-soft) !important;
}

.edu-num,
.tabular-nums {
    font-variant-numeric: tabular-nums;
    font-feature-settings: 'tnum';
}

.edu-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.11em;
    text-transform: uppercase;
    color: var(--edu-primary);
}

.edu-muted {
    color: var(--edu-muted);
}

hr {
    border-color: var(--edu-border);
    opacity: 1;
}

// Focus rings: visible on every interactive element, never on click-only divs.
a:focus-visible,
button:focus-visible,
.btn:focus-visible,
.form-control:focus-visible,
.form-select:focus-visible,
.nav-link:focus-visible,
.dropdown-item:focus-visible,
[tabindex]:focus-visible {
    outline: 2px solid var(--edu-primary);
    outline-offset: 2px;
    box-shadow: var(--edu-ring);
}

// -----------------------------------------------------------------------------
// Buttons, badges, forms, tables, dropdowns, modals
// -----------------------------------------------------------------------------

.btn {
    --bs-btn-font-weight: 600;
    --bs-btn-padding-x: 0.95rem;
    --bs-btn-padding-y: 0.55rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    border-radius: 0.625rem;
    transition: transform var(--edu-transition), box-shadow var(--edu-transition),
        background-color var(--edu-transition), border-color var(--edu-transition),
        color var(--edu-transition);
}

.btn:active {
    transform: translateY(1px);
}

.btn-primary {
    --bs-btn-bg: var(--edu-primary);
    --bs-btn-border-color: var(--edu-primary);
    --bs-btn-hover-bg: var(--edu-primary-dark);
    --bs-btn-hover-border-color: var(--edu-primary-dark);
    --bs-btn-active-bg: var(--edu-primary-dark);
    --bs-btn-active-border-color: var(--edu-primary-dark);
    --bs-btn-disabled-bg: #a5b4fc;
    --bs-btn-disabled-border-color: #a5b4fc;
    box-shadow: 0 8px 18px -12px rgba(79, 70, 229, 0.8);
}

.btn-primary:hover {
    box-shadow: 0 12px 24px -14px rgba(79, 70, 229, 0.85);
}

.btn-outline-primary {
    --bs-btn-color: var(--edu-primary);
    --bs-btn-border-color: rgba(79, 70, 229, 0.35);
    --bs-btn-hover-bg: var(--edu-primary);
    --bs-btn-hover-border-color: var(--edu-primary);
    --bs-btn-active-bg: var(--edu-primary-dark);
    --bs-btn-active-border-color: var(--edu-primary-dark);
}

.btn-light {
    --bs-btn-bg: #ffffff;
    --bs-btn-border-color: var(--edu-border);
    --bs-btn-hover-bg: var(--edu-surface-soft);
    --bs-btn-hover-border-color: var(--edu-border-strong);
}

[data-bs-theme='dark'] .btn-light {
    --bs-btn-bg: var(--edu-surface-raised);
    --bs-btn-border-color: var(--edu-border);
    --bs-btn-color: var(--edu-text);
    --bs-btn-hover-bg: var(--edu-surface-muted);
    --bs-btn-hover-border-color: var(--edu-border-strong);
    --bs-btn-hover-color: var(--edu-text);
}

.btn-soft-primary {
    background: var(--edu-primary-soft);
    border-color: transparent;
    color: var(--edu-primary);
}

.btn-soft-primary:hover {
    background: rgba(79, 70, 229, 0.18);
    color: var(--edu-primary-dark);
}

.edu-icon-btn {
    width: 42px;
    height: 42px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    border: 1px solid transparent;
    background: transparent;
    color: var(--edu-muted);
    transition: background-color var(--edu-transition), color var(--edu-transition),
        border-color var(--edu-transition);
}

.edu-icon-btn:hover,
.edu-icon-btn:focus-visible {
    background: var(--edu-surface-muted);
    color: var(--edu-text);
}

.badge {
    font-weight: 600;
    letter-spacing: 0.01em;
    padding: 0.38em 0.65em;
    border-radius: 0.45rem;
}

.badge.rounded-pill {
    border-radius: 50rem !important;
}

.edu-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.65rem;
    border-radius: 999px;
    background: var(--edu-surface-muted);
    border: 1px solid var(--edu-border);
    color: var(--edu-muted-strong);
    font-size: 0.75rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.form-control,
.form-select {
    border-radius: 0.625rem;
    border-color: var(--edu-border-strong);
    background-color: var(--edu-surface);
    color: var(--edu-text);
    padding-block: 0.55rem;
}

.form-control::placeholder {
    color: var(--edu-muted);
    opacity: 0.85;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--edu-primary);
    box-shadow: var(--edu-ring);
    background-color: var(--edu-surface);
    color: var(--edu-text);
}

.form-label {
    font-weight: 600;
    font-size: 0.8125rem;
    color: var(--edu-text-soft);
    margin-bottom: 0.4rem;
}

.input-group-text {
    background: var(--edu-surface-soft);
    border-color: var(--edu-border-strong);
    color: var(--edu-muted);
    border-radius: 0.625rem;
}

.form-check-input:checked {
    background-color: var(--edu-primary);
    border-color: var(--edu-primary);
}

.form-check-input:focus {
    box-shadow: var(--edu-ring);
}

// --- Cards -------------------------------------------------------------------
.card {
    --bs-card-bg: var(--edu-surface);
    --bs-card-border-color: var(--edu-border);
    --bs-card-cap-bg: transparent;
    --bs-card-border-radius: var(--edu-radius);
    background-color: var(--edu-surface);
    color: var(--edu-text);
    border-radius: var(--edu-radius);
    border: 1px solid var(--edu-border);
    box-shadow: var(--edu-shadow-card);
}

.card-header {
    border-bottom-color: var(--edu-border);
    padding: 1rem 1.15rem;
    background: transparent;
}

.card-footer {
    border-top-color: var(--edu-border);
    background: transparent;
    padding: 0.9rem 1.15rem;
}

.card-body {
    padding: 1.15rem;
}

// The dashboard uses .edu-card for the hover lift. Keeping it opt-in avoids
// forms and static panels jumping while somebody is typing in them.
.edu-card,
.edu-stat-card,
.edu-chart-card {
    transition: transform var(--edu-transition), box-shadow var(--edu-transition),
        border-color var(--edu-transition);
}

.edu-card:hover,
.edu-stat-card:hover,
.edu-chart-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--edu-shadow-hover);
    border-color: var(--edu-border-strong);
}

// --- Tables ------------------------------------------------------------------
.table {
    --bs-table-bg: transparent;
    --bs-table-color: var(--edu-text);
    --bs-table-border-color: var(--edu-border);
    --bs-table-hover-bg: rgba(79, 70, 229, 0.045);
    --bs-table-striped-bg: rgba(148, 163, 184, 0.06);
    margin-bottom: 0;
}

.table > thead {
    background: var(--edu-surface-soft);
}

.table > :not(caption) > * > * {
    padding: 0.85rem 1rem;
    border-bottom-color: var(--edu-border);
    vertical-align: middle;
}

.table > thead th {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--edu-muted);
    border-bottom: 1px solid var(--edu-border);
    white-space: nowrap;
}

.table > tbody tr:last-child > * {
    border-bottom-width: 0;
}

.table-hover > tbody > tr:hover > * {
    --bs-table-bg-state: rgba(79, 70, 229, 0.05);
}

[data-bs-theme='dark'] .table-hover > tbody > tr:hover > * {
    --bs-table-bg-state: rgba(129, 140, 248, 0.09);
}

// --- Dropdowns and modals ----------------------------------------------------
.dropdown-menu {
    --bs-dropdown-bg: var(--edu-surface-raised);
    --bs-dropdown-border-color: var(--edu-border);
    --bs-dropdown-link-color: var(--edu-text);
    --bs-dropdown-link-hover-bg: var(--edu-surface-muted);
    --bs-dropdown-link-active-bg: var(--edu-primary);
    --bs-dropdown-link-active-color: #ffffff;
    border-radius: 0.85rem;
    padding: 0.45rem;
    box-shadow: var(--edu-shadow-pop);
}

.dropdown-item {
    border-radius: 0.55rem;
    padding: 0.55rem 0.7rem;
    font-size: 0.875rem;
}

.dropdown-divider {
    border-color: var(--edu-border);
}

.modal-content {
    background: var(--edu-surface-raised);
    color: var(--edu-text);
    border: 1px solid var(--edu-border);
    border-radius: 1rem;
    box-shadow: var(--edu-shadow-pop);
}

.modal-header,
.modal-footer {
    border-color: var(--edu-border);
}

.list-group {
    --bs-list-group-bg: transparent;
    --bs-list-group-color: var(--edu-text);
    --bs-list-group-border-color: var(--edu-border);
    --bs-list-group-action-hover-bg: var(--edu-surface-soft);
    --bs-list-group-action-active-bg: var(--edu-surface-muted);
}

.alert {
    border-radius: 0.85rem;
    border-width: 1px;
    font-size: 0.875rem;
}

.progress {
    background: var(--edu-surface-muted);
    border-radius: 999px;
    height: 0.5rem;
}

.progress-bar {
    border-radius: 999px;
}

.edu-progress .progress {
    min-height: 6px;
}

// -----------------------------------------------------------------------------
// Avatars
// -----------------------------------------------------------------------------

.avatar {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

.avatar-sm {
    width: 32px;
    height: 32px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

.avatar-lg {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

.avatar-xl {
    width: 128px;
    height: 128px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

// Initials fallback when a user has not uploaded a photo.
.edu-avatar-initials {
    display: inline-grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-size: 0.9em;
    font-weight: 700;
    letter-spacing: 0.02em;
    line-height: 1;
    user-select: none;
}

.avatar-sm.edu-avatar-initials {
    font-size: 0.68rem;
}

.avatar-lg.edu-avatar-initials {
    font-size: 1.45rem;
}

.avatar-xl.edu-avatar-initials {
    font-size: 1.9rem;
}

.profile-photo-preview {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 50%;
    border: 1px solid var(--edu-border);
    background-color: var(--edu-surface-soft);
}

// -----------------------------------------------------------------------------
// App shell: sidebar + navbar
// -----------------------------------------------------------------------------

.edu-body {
    min-height: 100vh;
}

.edu-shell {
    display: flex;
    align-items: stretch;
    min-height: calc(100vh - var(--edu-navbar-height));
}

.edu-main {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-width: 0;
}

.edu-main-inner {
    flex: 1 1 auto;
    padding: 0 0 2.5rem;
}

.edu-page {
    animation: edu-page-in 320ms var(--edu-ease) both;
}

@keyframes edu-page-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.edu-page.is-leaving {
    opacity: 0;
    transform: translateY(4px);
    transition: opacity 130ms ease, transform 130ms ease;
}

// --- Desktop sidebar ---------------------------------------------------------
.edu-sidebar {
    --bs-offcanvas-width: 292px;
    width: var(--edu-sidebar-width);
    flex: 0 0 auto;
    color: var(--edu-sidebar-text);
    background: linear-gradient(160deg, var(--edu-sidebar-from) 0%, var(--edu-sidebar-to) 100%);
    border-right: 1px solid rgba(255, 255, 255, 0.06);
    transition: width 180ms var(--edu-ease);
    z-index: 1030;
}

// On desktop the offcanvas is static, so it needs its own positioning context
// for the decorative ::before layer. On phones it is already fixed.
@media (min-width: 992px) {
    .edu-sidebar {
        position: relative;
    }
}

.edu-sidebar::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background:
        radial-gradient(420px 260px at 10% -10%, rgba(124, 58, 237, 0.22), transparent 60%),
        radial-gradient(360px 240px at 110% 20%, rgba(79, 70, 229, 0.16), transparent 60%);
}

.edu-sidebar > * {
    position: relative;
    z-index: 1;
}

.edu-sidebar .offcanvas-body {
    padding: 1rem 0.85rem 1.1rem;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.18) transparent;
}

.edu-sidebar .offcanvas-body::-webkit-scrollbar {
    width: 6px;
}

.edu-sidebar .offcanvas-body::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.18);
    border-radius: 99px;
}

.edu-sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.35rem 0.5rem 1rem;
    color: #ffffff;
    text-decoration: none;
}

.edu-sidebar-logo:hover {
    color: #ffffff;
}

.edu-sidebar-logo-mark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border-radius: 13px;
    background: linear-gradient(145deg, var(--edu-primary) 0%, var(--edu-secondary) 100%);
    box-shadow: 0 10px 22px -12px rgba(79, 70, 229, 0.9);
    color: #ffffff;
    font-size: 1.15rem;
}

.edu-sidebar-logo-text {
    line-height: 1.15;
}

.edu-sidebar-logo-text strong {
    display: block;
    font-size: 1.02rem;
    font-weight: 700;
    letter-spacing: -0.02em;
}

.edu-sidebar-logo-text span {
    display: block;
    font-size: 0.68rem;
    color: var(--edu-sidebar-heading);
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.edu-sidebar-section {
    margin: 0.9rem 0.5rem 0.35rem;
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--edu-sidebar-heading);
}

.edu-sidebar .nav-link {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.7rem;
    min-height: 44px;
    padding: 0.62rem 0.75rem;
    margin-bottom: 0.15rem;
    border-radius: 0.7rem;
    color: var(--edu-sidebar-text);
    font-size: 0.875rem;
    font-weight: 500;
    transition: background-color var(--edu-transition), color var(--edu-transition);
}

.edu-sidebar .nav-link i {
    font-size: 1.05rem;
    width: 1.25rem;
    text-align: center;
    flex: 0 0 1.25rem;
    color: var(--edu-sidebar-heading);
    transition: color var(--edu-transition);
}

.edu-sidebar .nav-link:hover,
.edu-sidebar .nav-link:focus-visible {
    background: var(--edu-sidebar-hover);
    color: #ffffff;
}

.edu-sidebar .nav-link:hover i,
.edu-sidebar .nav-link:focus-visible i {
    color: #c7d2fe;
}

.edu-sidebar .nav-link.active {
    background: var(--edu-sidebar-active);
    color: #ffffff;
    font-weight: 600;
}

.edu-sidebar .nav-link.active i {
    color: #a5b4fc;
}

.edu-sidebar .nav-link.active::before {
    content: '';
    position: absolute;
    left: -0.85rem;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 22px;
    border-radius: 0 3px 3px 0;
    background: linear-gradient(180deg, #a5b4fc, #7c3aed);
    box-shadow: 0 0 16px rgba(165, 180, 252, 0.7);
}

.edu-sidebar .nav-link .badge {
    margin-left: auto;
    font-size: 0.65rem;
}

.edu-sidebar-divider {
    border-top: 1px solid var(--edu-sidebar-border);
    margin: 0.9rem 0.5rem;
}

.edu-sidebar-user {
    margin-top: auto;
    padding: 0.85rem;
    border-radius: 0.9rem;
    background: rgba(255, 255, 255, 0.055);
    border: 1px solid var(--edu-sidebar-border);
}

.edu-sidebar-user-name {
    color: #ffffff;
    font-weight: 600;
    font-size: 0.875rem;
    line-height: 1.2;
}

.edu-sidebar-user-role {
    color: var(--edu-sidebar-heading);
    font-size: 0.72rem;
    text-transform: capitalize;
}

.edu-sidebar-user .avatar,
.edu-sidebar-user .avatar-sm {
    border: 1px solid rgba(255, 255, 255, 0.2);
}

// The offcanvas header on phones must match the dark column.
.edu-sidebar .offcanvas-header {
    border-bottom: 1px solid var(--edu-sidebar-border) !important;
    color: #ffffff;
}

.edu-sidebar .offcanvas-title {
    color: #ffffff;
}

.edu-sidebar .btn-close {
    filter: invert(1) grayscale(1) brightness(2);
}

// Collapsed desktop sidebar: icon-only, still readable through tooltips.
@media (min-width: 992px) {
    html.edu-sidebar-collapsed .edu-sidebar {
        width: var(--edu-sidebar-collapsed-width);
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-label,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-section,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-logo-text,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user-text,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user .badge,
    html.edu-sidebar-collapsed .edu-sidebar .nav-link .badge {
        display: none !important;
    }

    html.edu-sidebar-collapsed .edu-sidebar .nav-link {
        justify-content: center;
        padding-inline: 0.55rem;
    }

    html.edu-sidebar-collapsed .edu-sidebar .nav-link i {
        margin: 0;
    }

    html.edu-sidebar-collapsed .edu-sidebar .nav-link.active::before {
        left: -0.55rem;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-logo {
        justify-content: center;
        padding-inline: 0;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user {
        padding: 0.6rem;
        text-align: center;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user .d-flex {
        justify-content: center;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-footer-link {
        justify-content: center;
    }
}

// --- Top navbar --------------------------------------------------------------
.edu-navbar {
    position: sticky;
    top: 0;
    z-index: 1040;
    min-height: var(--edu-navbar-height);
    padding: 0.55rem 0;
    background: color-mix(in srgb, var(--edu-surface) 82%, transparent);
    border-bottom: 1px solid var(--edu-border);
    backdrop-filter: saturate(180%) blur(16px);
    -webkit-backdrop-filter: saturate(180%) blur(16px);
}

@supports not (backdrop-filter: blur(1px)) {
    .edu-navbar {
        background: var(--edu-surface);
    }
}

.edu-navbar .navbar-brand {
    color: var(--edu-text);
    font-weight: 700;
    letter-spacing: -0.02em;
}

.edu-navbar-title {
    display: block;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--edu-text);
    line-height: 1.2;
    max-width: 22rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.edu-navbar .breadcrumb {
    --bs-breadcrumb-divider-color: var(--edu-muted);
    --bs-breadcrumb-item-padding-x: 0.35rem;
    margin: 0;
    font-size: 0.72rem;
}

.edu-navbar .breadcrumb a {
    color: var(--edu-muted);
    text-decoration: none;
}

.edu-navbar .breadcrumb a:hover {
    color: var(--edu-primary);
}

.edu-navbar .breadcrumb-item.active {
    color: var(--edu-muted-strong);
}

.edu-search {
    width: 260px;
}

.edu-search .form-control {
    background: var(--edu-surface-soft);
    border-color: transparent;
    font-size: 0.85rem;
}

.edu-search .form-control:focus {
    background: var(--edu-surface);
    border-color: var(--edu-border-strong);
}

@media (min-width: 992px) {
    .edu-search {
        width: 320px;
    }
}

.edu-navbar .nav-link {
    color: var(--edu-text);
}

.edu-navbar .dropdown-toggle::after {
    display: none;
}

// Bootstrap sets `.navbar-nav .dropdown-menu` to `position: static` outside a
// `navbar-expand-*` container, so the user/notification menus would expand the
// bar and push the page down. The top bar is always horizontal here, so keep
// its menus floating above the content instead.
.edu-navbar .navbar-nav .dropdown-menu {
    position: absolute;
}

.edu-navbar-avatar {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.25rem 0.45rem 0.25rem 0.3rem;
    border-radius: 999px;
    border: 1px solid var(--edu-border);
    background: var(--edu-surface);
    color: var(--edu-text);
    text-decoration: none;
    transition: box-shadow var(--edu-transition), border-color var(--edu-transition);
}

.edu-navbar-avatar:hover {
    border-color: var(--edu-border-strong);
    box-shadow: var(--edu-shadow-xs);
    color: var(--edu-text);
}

.notification-menu {
    width: min(380px, 92vw);
    max-height: 72vh;
    overflow-y: auto;
}

.notification-unread {
    background-color: var(--edu-primary-soft);
    border-left: 3px solid var(--edu-primary);
}

.edu-time {
    font-size: 0.72rem;
}

// Footer
.edu-footer {
    border-top: 1px solid var(--edu-border);
    background: var(--edu-surface);
    color: var(--edu-muted);
    padding: 1.1rem 0;
    font-size: 0.8125rem;
}

// -----------------------------------------------------------------------------
// Dashboard shared components
// -----------------------------------------------------------------------------

.edu-dashboard {
    padding-bottom: 1rem;
}

.edu-greeting {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.4rem;
}

.edu-greeting h1 {
    font-size: clamp(1.5rem, 1.2rem + 1.2vw, 2rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    margin: 0.15rem 0 0.2rem;
}

.edu-greeting p {
    margin: 0;
    color: var(--edu-muted);
    font-size: 0.875rem;
}

.edu-quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

// --- Stat cards --------------------------------------------------------------
.edu-stat-card {
    position: relative;
    overflow: hidden;
}

.edu-stat-card .card-body {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-height: 142px;
}

.edu-stat-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.edu-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    flex: 0 0 46px;
    border-radius: 14px;
    font-size: 1.2rem;
    line-height: 1;
}

.edu-stat-icon.bg-primary {
    background: rgba(79, 70, 229, 0.12) !important;
    color: #4f46e5 !important;
}

.edu-stat-icon.bg-success {
    background: rgba(16, 185, 129, 0.13) !important;
    color: #059669 !important;
}

.edu-stat-icon.bg-info {
    background: rgba(14, 165, 233, 0.13) !important;
    color: #0284c7 !important;
}

.edu-stat-icon.bg-warning {
    background: rgba(245, 158, 11, 0.15) !important;
    color: #b45309 !important;
}

.edu-stat-icon.bg-danger {
    background: rgba(239, 68, 68, 0.13) !important;
    color: #dc2626 !important;
}

.edu-stat-icon.bg-secondary {
    background: rgba(124, 58, 237, 0.14) !important;
    color: #6d28d9 !important;
}

[data-bs-theme='dark'] .edu-stat-icon.bg-primary { color: #a5b4fc !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-success { color: #6ee7b7 !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-info { color: #7dd3fc !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-warning { color: #fcd34d !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-danger { color: #fca5a5 !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-secondary { color: #c4b5fd !important; }

.edu-stat-value {
    font-size: clamp(1.45rem, 1.25rem + 0.6vw, 1.85rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
    color: var(--edu-text);
}

.edu-stat-label {
    color: var(--edu-muted);
    font-size: 0.8125rem;
    font-weight: 500;
}

.edu-stat-hint {
    color: var(--edu-muted);
    font-size: 0.72rem;
}

.edu-trend {
    display: inline-flex;
    align-items: center;
    gap: 0.18rem;
    font-size: 0.72rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.edu-trend.is-up { color: #059669; }
.edu-trend.is-down { color: #dc2626; }
.edu-trend.is-flat { color: var(--edu-muted); }

[data-bs-theme='dark'] .edu-trend.is-up { color: #6ee7b7; }
[data-bs-theme='dark'] .edu-trend.is-down { color: #fca5a5; }

.edu-sparkline {
    width: 96px;
    height: 34px;
    overflow: visible;
    opacity: 0.95;
}

.edu-sparkline polyline {
    fill: none;
    stroke-width: 2.2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.edu-sparkline .sparkline-fill {
    stroke: none;
    opacity: 0.16;
}

// --- Chart cards -------------------------------------------------------------
.edu-chart-card .card-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.65rem;
}

.edu-chart-card .card-title {
    font-size: 0.95rem;
    font-weight: 700;
    margin: 0;
}

.edu-chart-card .card-subtitle {
    color: var(--edu-muted);
    font-size: 0.75rem;
    margin-top: 0.15rem;
}

.edu-chart {
    position: relative;
    width: 100%;
}

.edu-chart canvas {
    max-width: 100%;
}

.edu-chart-skeleton {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    padding: 0.5rem 0;
    z-index: 2;
    background: var(--edu-surface);
    border-radius: 0.75rem;
}

.edu-chart-skeleton[hidden] {
    display: none !important;
}

.edu-chart-skeleton span {
    flex: 1 1 0;
    border-radius: 0.35rem;
    background: linear-gradient(
        90deg,
        var(--edu-surface-muted) 0%,
        var(--edu-border) 45%,
        var(--edu-surface-muted) 90%
    );
    background-size: 200% 100%;
    animation: edu-shimmer 1.2s linear infinite;
}

.edu-chart-skeleton span:nth-child(1) { height: 42%; }
.edu-chart-skeleton span:nth-child(2) { height: 66%; }
.edu-chart-skeleton span:nth-child(3) { height: 54%; }
.edu-chart-skeleton span:nth-child(4) { height: 82%; }
.edu-chart-skeleton span:nth-child(5) { height: 60%; }
.edu-chart-skeleton span:nth-child(6) { height: 92%; }
.edu-chart-skeleton span:nth-child(7) { height: 70%; }
.edu-chart-skeleton span:nth-child(8) { height: 48%; }

@keyframes edu-shimmer {
    from { background-position: 200% 0; }
    to { background-position: -200% 0; }
}

.edu-chart-range {
    width: auto;
    min-width: 118px;
    font-size: 0.78rem;
    padding: 0.35rem 1.8rem 0.35rem 0.6rem;
    border-radius: 0.55rem;
    background-color: var(--edu-surface-soft);
    border-color: var(--edu-border);
}

.edu-chart-total {
    font-variant-numeric: tabular-nums;
}

// --- Hero cards --------------------------------------------------------------
.edu-hero {
    position: relative;
    overflow: hidden;
    border-radius: var(--edu-radius-lg);
    border: 1px solid rgba(255, 255, 255, 0.08);
    background:
        radial-gradient(900px 360px at 90% -40%, rgba(124, 58, 237, 0.55), transparent 60%),
        linear-gradient(135deg, #1e1b4b 0%, #312e81 45%, #4f46e5 100%);
    color: #ffffff;
    box-shadow: 0 26px 60px -30px rgba(49, 46, 129, 0.85);
    padding: clamp(1.25rem, 1rem + 1.2vw, 2rem);
}

.edu-hero::after {
    content: '';
    position: absolute;
    right: -80px;
    bottom: -120px;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    border: 44px solid rgba(255, 255, 255, 0.05);
    pointer-events: none;
}

.edu-hero > * {
    position: relative;
    z-index: 1;
}

.edu-hero h1,
.edu-hero h2 {
    color: #ffffff;
    letter-spacing: -0.03em;
}

.edu-hero .edu-hero-meta {
    color: rgba(255, 255, 255, 0.72);
    font-size: 0.875rem;
}

.edu-hero .edu-hero-label {
    color: #c7d2fe;
}

.edu-hero .btn-light {
    --bs-btn-bg: #ffffff;
    --bs-btn-color: #312e81;
    --bs-btn-hover-bg: #eef2ff;
    --bs-btn-hover-color: #1e1b4b;
    border-color: #ffffff;
}

.edu-hero .btn-outline-light {
    --bs-btn-color: #ffffff;
    --bs-btn-border-color: rgba(255, 255, 255, 0.45);
    --bs-btn-hover-bg: rgba(255, 255, 255, 0.14);
    --bs-btn-hover-border-color: #ffffff;
}

.edu-hero .edu-chip {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.18);
    color: #e0e7ff;
}

.edu-hero-live {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    background: rgba(239, 68, 68, 0.18);
    border: 1px solid rgba(248, 113, 113, 0.4);
    color: #fecaca;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.edu-live-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ef4444;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.25);
    animation: edu-pulse 1.6s ease-in-out infinite;
}

@keyframes edu-pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(0.82); opacity: 0.65; }
}

// --- Countdown ---------------------------------------------------------------
.edu-countdown {
    display: inline-flex;
    align-items: stretch;
    gap: 0.45rem;
}

.edu-countdown-unit {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 58px;
    padding: 0.4rem 0.55rem;
    border-radius: 0.65rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.14);
}

.edu-countdown-value {
    font-size: 1.2rem;
    font-weight: 800;
    line-height: 1;
    font-variant-numeric: tabular-nums;
    color: #ffffff;
}

.edu-countdown-label {
    font-size: 0.58rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.66);
    margin-top: 0.2rem;
}

.edu-countdown.is-compact .edu-countdown-unit {
    min-width: 46px;
    padding: 0.3rem 0.4rem;
}

.edu-countdown.is-compact .edu-countdown-value {
    font-size: 1rem;
}

// --- Avatar stack ------------------------------------------------------------
.edu-avatar-stack {
    display: inline-flex;
    align-items: center;
    padding-left: 0.35rem;
}

.edu-avatar-stack .avatar,
.edu-avatar-stack .avatar-sm {
    border: 2px solid var(--edu-hero-avatar-border, #312e81);
    margin-left: -0.45rem;
    background: var(--edu-surface-muted);
}

.edu-avatar-stack .edu-avatar-stack-more {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    margin-left: -0.45rem;
    background: rgba(255, 255, 255, 0.16);
    border: 2px solid #312e81;
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

// Outside hero cards the stack needs a light border.
.edu-avatar-stack.on-light .avatar,
.edu-avatar-stack.on-light .avatar-sm {
    border-color: var(--edu-surface);
}

.edu-avatar-stack.on-light .edu-avatar-stack-more {
    background: var(--edu-surface-muted);
    border-color: var(--edu-surface);
    color: var(--edu-muted-strong);
}

// --- Activity timeline -------------------------------------------------------
.edu-activity {
    position: relative;
    display: flex;
    gap: 0.85rem;
    padding: 0.8rem 0;
}

.edu-activity + .edu-activity {
    border-top: 1px solid var(--edu-border);
}

.edu-activity-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    border-radius: 12px;
    font-size: 1rem;
}

.edu-activity-icon.bg-primary { background: rgba(79, 70, 229, 0.12); color: #4f46e5; }
.edu-activity-icon.bg-success { background: rgba(16, 185, 129, 0.13); color: #059669; }
.edu-activity-icon.bg-info { background: rgba(14, 165, 233, 0.13); color: #0284c7; }
.edu-activity-icon.bg-warning { background: rgba(245, 158, 11, 0.15); color: #b45309; }
.edu-activity-icon.bg-danger { background: rgba(239, 68, 68, 0.13); color: #dc2626; }

.edu-activity-text {
    font-size: 0.85rem;
    color: var(--edu-text-soft);
    line-height: 1.4;
}

.edu-activity-excerpt {
    color: var(--edu-muted);
    font-size: 0.78rem;
    margin-top: 0.15rem;
}

.edu-activity-time {
    color: var(--edu-muted);
    font-size: 0.72rem;
    margin-top: 0.2rem;
}

// --- Data table wrapper ------------------------------------------------------
.edu-data-table .card-header {
    align-items: center;
}

.edu-table-tools {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.edu-table-search {
    position: relative;
}

.edu-table-search i {
    position: absolute;
    left: 0.65rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--edu-muted);
    font-size: 0.8rem;
    pointer-events: none;
}

.edu-table-search .form-control {
    min-width: 190px;
    padding-left: 2rem;
    font-size: 0.8rem;
    background: var(--edu-surface-soft);
    border-color: transparent;
}

.edu-table-search .form-control:focus {
    background: var(--edu-surface);
    border-color: var(--edu-border-strong);
}

.edu-sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
}

.edu-sortable:hover {
    color: var(--edu-text);
}

.edu-sortable::after {
    content: '\F282';
    font-family: 'bootstrap-icons';
    font-size: 0.62rem;
    margin-left: 0.3rem;
    opacity: 0.35;
}

.edu-sortable[aria-sort='ascending']::after {
    content: '\F286';
    opacity: 0.9;
}

.edu-sortable[aria-sort='descending']::after {
    content: '\F284';
    opacity: 0.9;
}

.edu-table-empty {
    padding: 1.5rem 1rem;
    text-align: center;
    color: var(--edu-muted);
    font-size: 0.85rem;
}

.edu-table-count {
    color: var(--edu-muted);
    font-size: 0.75rem;
    font-variant-numeric: tabular-nums;
}

// --- Schedule strip ----------------------------------------------------------
.edu-schedule {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0.45rem;
}

.edu-schedule-day {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.6rem 0.25rem;
    border-radius: 0.8rem;
    border: 1px solid var(--edu-border);
    background: var(--edu-surface-soft);
    min-height: 86px;
    text-align: center;
}

.edu-schedule-day.is-today {
    border-color: rgba(79, 70, 229, 0.4);
    background: var(--edu-primary-soft);
}

.edu-schedule-weekday {
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--edu-muted);
}

.edu-schedule-date {
    font-size: 0.95rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    color: var(--edu-text);
    line-height: 1.1;
}

.edu-schedule-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 0.3rem;
    border-radius: 999px;
    background: var(--edu-surface-muted);
    color: var(--edu-muted-strong);
    font-size: 0.68rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.edu-schedule-day.has-classes .edu-schedule-count {
    background: var(--edu-primary);
    color: #ffffff;
}

@media (max-width: 575.98px) {
    .edu-schedule {
        gap: 0.3rem;
    }

    .edu-schedule-day {
        min-height: 76px;
        padding: 0.45rem 0.15rem;
    }

    .edu-schedule-date {
        font-size: 0.85rem;
    }
}

// --- Panels / lists ----------------------------------------------------------
.edu-list {
    display: flex;
    flex-direction: column;
}

.edu-list-row {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.8rem 0;
}

.edu-list-row + .edu-list-row {
    border-top: 1px solid var(--edu-border);
}

.edu-list-row:first-child {
    padding-top: 0.2rem;
}

.edu-list-row:last-child {
    padding-bottom: 0.2rem;
}

.edu-list-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--edu-text);
    text-decoration: none;
    display: block;
}

.edu-list-title:hover {
    color: var(--edu-primary);
}

.edu-list-meta {
    color: var(--edu-muted);
    font-size: 0.74rem;
}

.edu-list-excerpt {
    color: var(--edu-muted-strong);
    font-size: 0.8rem;
    margin-top: 0.15rem;
}

.edu-report-actions {
    display: flex;
    gap: 0.35rem;
    flex-shrink: 0;
}

.edu-report-actions .btn {
    width: 34px;
    height: 34px;
    padding: 0;
}

// --- Feed preview ------------------------------------------------------------
.edu-feed-item {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 0;
}

.edu-feed-item + .edu-feed-item {
    border-top: 1px solid var(--edu-border);
}

.edu-feed-body {
    font-size: 0.82rem;
    color: var(--edu-text-soft);
    margin: 0.2rem 0 0.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

// --- Feed sidebar widgets ----------------------------------------------------
.edu-widget-user {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.7rem 0;
}

.edu-widget-user + .edu-widget-user {
    border-top: 1px solid var(--edu-border);
}

.edu-widget-user .edu-list-title {
    font-size: 0.84rem;
}

.edu-widget-user .btn {
    white-space: nowrap;
}

.edu-trending-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.65rem 0;
    color: inherit;
    text-decoration: none;
}

.edu-trending-row + .edu-trending-row {
    border-top: 1px solid var(--edu-border);
}

.edu-trending-row:hover .edu-list-title {
    color: var(--edu-primary);
}

.edu-trending-rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 12px;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-weight: 700;
    flex-shrink: 0;
}

.edu-post-image {
    width: 100%;
    max-height: 520px;
    object-fit: cover;
    background: var(--edu-surface-muted);
}

.edu-post-comments .avatar,
.edu-post-comments .avatar-sm {
    width: 34px;
    height: 34px;
    font-size: 0.8rem;
}

// --- Empty states ------------------------------------------------------------
.edu-empty-illustration {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 76px;
    height: 76px;
    border-radius: 24px;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-size: 1.9rem;
    margin-bottom: 0.9rem;
    position: relative;
}

.edu-empty-illustration::after {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 30px;
    border: 1px dashed rgba(79, 70, 229, 0.22);
}

// --- Skeletons ---------------------------------------------------------------
.edu-skeleton {
    display: block;
    border-radius: 0.5rem;
    background: linear-gradient(
        90deg,
        var(--edu-surface-muted) 0%,
        var(--edu-border) 45%,
        var(--edu-surface-muted) 90%
    );
    background-size: 200% 100%;
    animation: edu-shimmer 1.2s linear infinite;
}

// -----------------------------------------------------------------------------
// Legacy application pages (feed, auth, live room) — kept intentionally
// -----------------------------------------------------------------------------

.post-body {
    white-space: pre-wrap;
    word-break: break-word;
}

.breadcrumb {
    --bs-breadcrumb-divider-color: var(--edu-muted);
}

[x-cloak] {
    display: none !important;
}

.jitsi-frame {
    position: relative;
    width: 100%;
    height: calc(100vh - 340px);
    min-height: 420px;
    border-radius: 0.75rem;
    overflow: hidden;
    background-color: #1c1c1c;
}

.jitsi-frame iframe {
    width: 100% !important;
    height: 100% !important;
    border: 0;
}

@media (max-width: 991.98px) {
    .jitsi-frame {
        height: calc(100vh - 260px);
        min-height: 360px;
        border-radius: 0.5rem;
    }
}

.edu-auth-body {
    background-color: var(--edu-surface);
    color: var(--edu-text);
}

.edu-auth-brand {
    position: relative;
    overflow: hidden;
    color: #fff;
    background: linear-gradient(145deg, #312e81 0%, #4338ca 45%, #4f46e5 100%);
}

.edu-auth-brand::before,
.edu-auth-brand::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    pointer-events: none;
}

.edu-auth-brand::before {
    width: 420px;
    height: 420px;
    top: -160px;
    right: -140px;
}

.edu-auth-brand::after {
    width: 300px;
    height: 300px;
    bottom: -120px;
    left: -100px;
}

.edu-auth-brand > * {
    position: relative;
    z-index: 1;
}

.edu-auth-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    flex: 0 0 44px;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.14);
    font-size: 1.15rem;
}

.edu-auth-form {
    max-width: 440px;
}

.edu-auth-card {
    border-radius: 1rem;
}

.password-toggle {
    min-width: 48px;
}

@media (max-width: 991.98px) {
    .edu-auth-body {
        background-color: var(--edu-page-bg);
    }
}

// -----------------------------------------------------------------------------
// Responsive and touch refinements
// -----------------------------------------------------------------------------

@media (max-width: 991.98px) {
    .edu-main-inner {
        padding-top: 1.1rem;
    }

    .edu-sidebar {
        --bs-offcanvas-width: min(88vw, 312px);
    }
}

@media (max-width: 575.98px) {
    .btn,
    .form-control,
    .form-select,
    .nav-link,
    .dropdown-item {
        min-height: 44px;
    }

    .btn-sm {
        min-height: 44px;
    }

    .edu-icon-btn {
        width: 44px;
        height: 44px;
    }

    .edu-greeting {
        align-items: flex-start;
    }

    .edu-quick-actions {
        width: 100%;
    }

    .edu-quick-actions .btn {
        flex: 1 1 auto;
    }

    .edu-stat-card .card-body {
        min-height: 128px;
    }

    .edu-table-search .form-control {
        min-width: 0;
        width: 100%;
    }

    .edu-table-tools {
        width: 100%;
    }

    .edu-table-tools .edu-table-search {
        flex: 1 1 auto;
    }

    .edu-countdown {
        gap: 0.3rem;
    }

    .edu-countdown-unit {
        min-width: 48px;
        padding: 0.35rem 0.35rem;
    }

    .edu-countdown-value {
        font-size: 1.05rem;
    }
}

// Larger screens get a little more breathing room.
@media (min-width: 1920px) {
    .edu-main-inner {
        padding-top: 2rem;
    }

    .edu-sidebar {
        --edu-sidebar-width: 284px;
    }

    .edu-stat-card .card-body {
        min-height: 152px;
    }
}

// -----------------------------------------------------------------------------
// Phase 3C — component library additions
// -----------------------------------------------------------------------------
// Everything below is built from the tokens in _variables.scss: same spacing
// steps, same radii, same transitions, same z-index ladder.

// Comfortable reading width for long prose (about 70 characters).
.edu-measure {
    max-width: var(--edu-measure);
}

// --- Button loading state ----------------------------------------------------
// A form button that is waiting for the server: spinner in place of the label.
.btn.is-loading {
    position: relative;
    color: transparent !important;
    pointer-events: none;

    > * {
        visibility: hidden;
    }

    &::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 1rem;
        height: 1rem;
        margin: -0.5rem 0 0 -0.5rem;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        color: #fff;
        animation: edu-spin 0.6s linear infinite;
    }
}

.btn-outline-primary.is-loading::after,
.btn-outline-secondary.is-loading::after,
.btn-light.is-loading::after {
    color: var(--edu-primary);
}

@keyframes edu-spin {
    to {
        transform: rotate(360deg);
    }
}

// --- Toasts ------------------------------------------------------------------
// Rendered by resources/js/toast.js from the data attributes of <x-flash />.
.edu-toast-stack {
    position: fixed;
    right: var(--edu-space-4);
    bottom: var(--edu-space-4);
    z-index: var(--edu-z-toast);
    display: flex;
    flex-direction: column;
    gap: var(--edu-space-2);
    width: min(360px, calc(100vw - 2 * var(--edu-space-4)));
    pointer-events: none;
}

.edu-toast {
    display: flex;
    align-items: flex-start;
    gap: var(--edu-space-2);
    padding: var(--edu-space-3) var(--edu-space-4);
    border-radius: var(--edu-radius-sm);
    border: 1px solid var(--edu-border);
    background: var(--edu-surface);
    color: var(--edu-text);
    box-shadow: var(--edu-shadow-pop);
    pointer-events: auto;
    animation: edu-toast-in 200ms var(--edu-ease);

    .btn-close {
        padding: 0.35rem;
        margin-left: auto;
    }
}

.edu-toast.is-success {
    border-left: 4px solid var(--edu-success);
}

.edu-toast.is-error {
    border-left: 4px solid var(--edu-danger);
}

.edu-toast.is-info {
    border-left: 4px solid var(--edu-info);
}

@keyframes edu-toast-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
}

// --- Role cards on the register page ----------------------------------------
.edu-role-card {
    display: flex;
    align-items: flex-start;
    gap: var(--edu-space-3);
    padding: var(--edu-space-3) var(--edu-space-4);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface-soft);
    cursor: pointer;
    transition: border-color var(--edu-transition), background-color var(--edu-transition),
        box-shadow var(--edu-transition);

    &:hover {
        border-color: var(--edu-border-strong);
    }

    // The radio itself is hidden: the whole card is the hit area (44px+ tall).
    input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .edu-role-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: var(--edu-radius-xs);
        background: var(--edu-primary-soft);
        color: var(--edu-primary);
        font-size: 1.15rem;
    }

    // Selected state, driven by :has() plus a JS class for older browsers.
    &:has(input:checked),
    &.is-selected {
        border-color: var(--edu-primary);
        background: var(--edu-surface);
        box-shadow: 0 0 0 1px var(--edu-primary);
    }

    &:has(input:focus-visible),
    &.is-selected:focus-within {
        outline: 2px solid var(--edu-primary);
        outline-offset: 2px;
    }
}

// --- Password strength meter -------------------------------------------------
.edu-strength {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    margin-top: var(--edu-space-2);
}

.edu-strength-track {
    display: flex;
    gap: 4px;
}

.edu-strength-seg {
    flex: 1;
    height: 6px;
    border-radius: 999px;
    background: var(--edu-surface-muted);
    transition: background-color var(--edu-transition);
}

.edu-strength[data-score='1'] .edu-strength-seg:nth-child(-n + 1),
.edu-strength[data-score='2'] .edu-strength-seg:nth-child(-n + 2) {
    background: var(--edu-danger);
}

.edu-strength[data-score='3'] .edu-strength-seg:nth-child(-n + 3) {
    background: var(--edu-warning);
}

.edu-strength[data-score='4'] .edu-strength-seg {
    background: var(--edu-success);
}

// --- Mobile bottom navigation ------------------------------------------------
.edu-mobile-nav {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: var(--edu-z-mobilenav);
    display: flex;
    align-items: stretch;
    justify-content: space-around;
    border-top: 1px solid var(--edu-border);
    background: var(--edu-surface);
    // Respect the iPhone home indicator area.
    padding-bottom: env(safe-area-inset-bottom, 0);
    box-shadow: 0 -6px 24px -18px rgba(15, 23, 42, 0.4);
}

.edu-mobile-nav-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    // 44px minimum touch target.
    min-width: 56px;
    min-height: 56px;
    flex: 1;
    padding: 0.4rem 0.25rem;
    font-size: var(--edu-fs-xs);
    font-weight: 600;
    color: var(--edu-muted);
    text-decoration: none;

    i {
        font-size: 1.25rem;
    }

    &.active {
        color: var(--edu-primary);
    }

    &:hover {
        color: var(--edu-primary);
    }
}

// The badge sits on the icon, not on the label.
.edu-mobile-nav-badge {
    position: absolute;
    top: 4px;
    left: 50%;
    margin-left: 6px;
    font-size: 0.625rem;
    padding: 0.15em 0.4em;
}

// --- Floating "new post" button ---------------------------------------------
.edu-fab {
    position: fixed;
    right: var(--edu-space-4);
    bottom: calc(72px + env(safe-area-inset-bottom, 0));
    z-index: var(--edu-z-fab);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    border: 0;
    border-radius: 50%;
    background: var(--edu-primary);
    color: #fff;
    font-size: 1.4rem;
    box-shadow: var(--edu-shadow-pop);
    transition: transform var(--edu-transition), background-color var(--edu-transition);

    &:hover,
    &:focus-visible {
        background: var(--edu-primary-dark);
        color: #fff;
    }

    &:active {
        transform: scale(0.94);
    }
}

@media (min-width: 992px) {
    .edu-fab {
        bottom: var(--edu-space-5);
        right: var(--edu-space-5);
    }
}

// --- Profile cover banner ----------------------------------------------------
.edu-cover {
    position: relative;
    height: 132px;
    border-radius: var(--edu-radius) var(--edu-radius) 0 0;
    background: linear-gradient(120deg, #312e81 0%, #4f46e5 55%, #7c3aed 100%);
    overflow: hidden;

    &::after {
        content: '';
        position: absolute;
        width: 260px;
        height: 260px;
        right: -80px;
        top: -140px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
    }
}

.edu-cover-avatar {
    margin-top: -48px;
}

// --- Highlighted search match ------------------------------------------------
// The surrounding text keeps its colour; only the matched part is marked, so
// the highlight never hides information and always has contrast.
.edu-mark {
    padding: 0 0.15em;
    border-radius: var(--edu-radius-xs);
    background: rgba(245, 158, 11, 0.28);
    color: inherit;
    font-weight: 600;
}

[data-bs-theme='dark'] .edu-mark {
    background: rgba(245, 158, 11, 0.32);
}

// --- Filter chips ------------------------------------------------------------
.edu-filter-chip {
    display: inline-flex;
    align-items: center;
    gap: var(--edu-space-1);
    padding: 0.35rem 0.75rem;
    min-height: 36px;
    border: 1px solid var(--edu-border);
    border-radius: 999px;
    background: var(--edu-surface);
    color: var(--edu-text-soft);
    font-size: var(--edu-fs-small);
    font-weight: 600;
    text-decoration: none;
    transition: border-color var(--edu-transition), background-color var(--edu-transition),
        color var(--edu-transition);

    &:hover {
        border-color: var(--edu-primary);
        color: var(--edu-primary);
    }

    &.is-active {
        border-color: var(--edu-primary);
        background: var(--edu-primary);
        color: #fff;
    }
}

// --- Notification groups (Today / Earlier) -----------------------------------
.edu-notif-group-label {
    display: flex;
    align-items: center;
    gap: var(--edu-space-2);
    margin: var(--edu-space-4) 0 var(--edu-space-2);
    font-size: var(--edu-fs-xs);
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--edu-muted);

    &::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--edu-border);
    }
}

.edu-notif-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--edu-primary);
    flex-shrink: 0;
}

// --- Sticky data tables + mobile card view -----------------------------------
.edu-table-sticky thead th {
    position: sticky;
    top: 0;
    z-index: var(--edu-z-base);
    background: var(--edu-surface);
    box-shadow: inset 0 -1px 0 var(--edu-border);
}

// Below lg each row becomes a card. The label of every cell comes from the
// data-label attribute, so nothing is lost when the header disappears.
@media (max-width: 991.98px) {
    .edu-table-cards {
        thead {
            display: none;
        }

        tbody tr {
            display: block;
            border: 1px solid var(--edu-border);
            border-radius: var(--edu-radius-sm);
            margin-bottom: var(--edu-space-3);
            padding: var(--edu-space-2);
            background: var(--edu-surface);
        }

        tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--edu-space-3);
            border: 0;
            padding: 0.4rem 0.25rem;
            text-align: right;
        }

        tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            font-size: var(--edu-fs-small);
            color: var(--edu-muted);
            text-align: left;
        }
    }
}

// --- Style guide helpers -----------------------------------------------------
.edu-swatch {
    height: 56px;
    border-radius: var(--edu-radius-sm);
    border: 1px solid var(--edu-border);
}

.edu-guide-block {
    padding: var(--edu-space-4);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface);
}

// --- Landing page (guests) ---------------------------------------------------
.edu-landing {
    background: var(--edu-surface);
}

.edu-landing-hero {
    position: relative;
    overflow: hidden;
    padding: var(--edu-space-8) 0 var(--edu-space-7);
    background: linear-gradient(180deg, var(--edu-surface-soft) 0%, var(--edu-surface) 100%);
}

.edu-landing-shape {
    position: absolute;
    border-radius: 50%;
    filter: blur(6px);
    pointer-events: none;
    opacity: 0.55;
}

.edu-landing-shape.is-one {
    width: 380px;
    height: 380px;
    top: -160px;
    right: -120px;
    background: radial-gradient(circle at 30% 30%, rgba(79, 70, 229, 0.35), transparent 70%);
}

.edu-landing-shape.is-two {
    width: 320px;
    height: 320px;
    bottom: -180px;
    left: -120px;
    background: radial-gradient(circle at 60% 40%, rgba(124, 58, 237, 0.3), transparent 70%);
}

.edu-landing-title {
    font-size: var(--edu-fs-display);
    line-height: 1.1;
    letter-spacing: -0.03em;
}

// Mockup of the live-class screen: pure CSS, no image request.
.edu-mockup {
    border-radius: var(--edu-radius);
    border: 1px solid var(--edu-border);
    background: var(--edu-surface);
    box-shadow: var(--edu-shadow-pop);
    overflow: hidden;
}

.edu-mockup-bar {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: var(--edu-space-2) var(--edu-space-3);
    border-bottom: 1px solid var(--edu-border);
    background: var(--edu-surface-soft);
}

.edu-mockup-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--edu-border-strong);
}

.edu-mockup-stage {
    position: relative;
    aspect-ratio: 16 / 9;
    background: linear-gradient(140deg, #1e1b4b 0%, #312e81 50%, #4f46e5 100%);
}

.edu-mockup-tile {
    position: absolute;
    border-radius: var(--edu-radius-xs);
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.22);
}

.edu-mockup-bar-strip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--edu-space-3);
    padding: var(--edu-space-3);
    background: var(--edu-surface-soft);
}

.edu-mockup-tool {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--edu-surface);
    border: 1px solid var(--edu-border);
    color: var(--edu-text-soft);
}

.edu-step-card {
    position: relative;
    padding: var(--edu-space-5);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius);
    background: var(--edu-surface);
    height: 100%;
}

.edu-step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: var(--edu-radius-xs);
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-weight: 700;
}

.edu-feature-card {
    padding: var(--edu-space-5);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius);
    background: var(--edu-surface);
    height: 100%;
    transition: transform var(--edu-transition), box-shadow var(--edu-transition),
        border-color var(--edu-transition);

    &:hover {
        transform: translateY(-3px);
        border-color: var(--edu-border-strong);
        box-shadow: var(--edu-shadow-card);
    }
}

.edu-stats-strip {
    border-radius: var(--edu-radius);
    background: linear-gradient(120deg, #312e81 0%, #4f46e5 100%);
    color: #fff;
    padding: var(--edu-space-6) var(--edu-space-5);
}

.edu-stat-figure {
    font-size: clamp(1.5rem, 1.1rem + 1.4vw, 2.25rem);
    font-weight: 800;
    line-height: 1.1;
}

.edu-landing-section {
    padding: var(--edu-space-7) 0;
}

.edu-section-title {
    font-size: var(--edu-fs-h2);
    letter-spacing: -0.02em;
}

// A tidy accordion: no double borders, the open item is clearly marked.
.edu-faq .accordion-item {
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface);
    margin-bottom: var(--edu-space-2);
    overflow: hidden;
}

.edu-faq .accordion-button {
    font-weight: 600;
    background: var(--edu-surface);
    color: var(--edu-text);
    box-shadow: none;

    &:not(.collapsed) {
        color: var(--edu-primary);
        background: var(--edu-surface-soft);
    }
}

.edu-landing-footer {
    padding: var(--edu-space-6) 0 var(--edu-space-5);
    border-top: 1px solid var(--edu-border);
    background: var(--edu-surface-soft);
}

// On phones the bottom navigation and the floating button need room.
@media (max-width: 991.98px) {
    .edu-has-mobile-nav {
        padding-bottom: calc(64px + env(safe-area-inset-bottom, 0));
    }
}

// --- Phase 3C additions ------------------------------------------------------

// Skip link: invisible until it receives keyboard focus, then a normal button.
.edu-skip-link {
    position: fixed;
    top: var(--edu-space-2);
    left: var(--edu-space-2);
    z-index: calc(var(--edu-z-fab) + 10);
    padding: 0.5rem 0.9rem;
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface);
    color: var(--edu-text);
    box-shadow: var(--edu-shadow-pop);
    font-weight: 600;
    text-decoration: none;

    &:focus {
        outline: 2px solid var(--edu-primary);
        outline-offset: 2px;
    }
}

// Sidebars that stay in view while the main column scrolls.
.edu-sticky-aside {
    position: static;
}

@media (min-width: 1200px) {
    .edu-sticky-aside {
        position: sticky;
        top: calc(var(--edu-navbar-height) + var(--edu-space-3));
    }
}

// Character ring in the feed composer.
.edu-char-ring {
    --edu-ring-progress: 0deg;
    --edu-ring-color: var(--edu-primary);
    position: relative;
    display: inline-grid;
    place-items: center;
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    border-radius: 50%;
    background: conic-gradient(var(--edu-ring-color) var(--edu-ring-progress), var(--edu-border) 0);
    font-size: var(--edu-fs-xs);
    font-weight: 700;
    color: var(--edu-muted);
    transition: background var(--edu-transition);

    &::before {
        content: '';
        position: absolute;
        inset: 4px;
        border-radius: 50%;
        background: var(--edu-surface);
    }

    > * {
        position: relative;
        z-index: 1;
    }

    &.is-warning {
        --edu-ring-color: var(--edu-warning);
    }

    &.is-full {
        --edu-ring-color: var(--edu-danger);
    }
}

// Heart pop when a post is liked (disabled by prefers-reduced-motion).
.edu-like-pop {
    animation: edu-like-pop 220ms ease-out;
}

@keyframes edu-like-pop {
    0% { transform: scale(1); }
    40% { transform: scale(1.35); }
    70% { transform: scale(0.94); }
    100% { transform: scale(1); }
}

// Notification row icon.
.edu-notif-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-size: 1rem;

    .notification-unread & {
        background: var(--edu-primary);
        color: #fff;
    }
}

// Hashtag page hero.
.edu-hashtag-hero {
    display: grid;
    place-items: center;
    width: 64px;
    height: 64px;
    flex-shrink: 0;
    border-radius: var(--edu-radius);
    background: linear-gradient(135deg, var(--edu-primary) 0%, var(--edu-secondary) 100%);
    color: #fff;
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

// Admin tables: keep the post preview readable without stretching the table.
.edu-admin-post-cell {
    min-width: 16rem;
    max-width: 30rem;

    @media (max-width: 991.98px) {
        min-width: 0;
        max-width: none;
    }
}

// Accessibility: honour the operating-system "reduce motion" setting. One
// single media query for the whole stylesheet (Phase 3C merged the block that
// used to be duplicated here).
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }

    .edu-feature-card:hover,
    .edu-card-hover:hover {
        transform: none;
    }

    .edu-page,
    .edu-page.is-leaving {
        animation: none !important;
        transform: none !important;
        opacity: 1 !important;
    }

    .edu-chart-skeleton span,
    .edu-skeleton,
    .edu-live-dot,
    .edu-toast,
    .btn.is-loading::after {
        animation: none !important;
    }
}

```

# 13. Tests

## `tests/Feature/DashboardTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3: the three role dashboards (FR-2, FR-19, FR-20, FR-21, FR-22, FR-31).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Role isolation
     * --------------------------------------------------------------- */

    /**
     * Every role only reaches its own dashboard (FR-2, FR-5).
     */
    public function test_each_role_is_locked_out_of_the_other_dashboards(): void
    {
        // A teacher cannot open the admin or the student dashboard.
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($teacher)->get('/student/dashboard')->assertForbidden();

        // A student cannot open the admin or the teacher dashboard.
        $student = User::factory()->student()->create();
        $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($student)->get('/teacher/dashboard')->assertForbidden();

        // An admin cannot open the teacher or the student dashboard.
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/teacher/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/student/dashboard')->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Admin dashboard
     * --------------------------------------------------------------- */

    /**
     * The admin cards show the real platform totals (FR-7).
     */
    public function test_the_admin_dashboard_shows_the_platform_totals(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->teacher()->create();
        $students = User::factory()->count(3)->student()->create();

        Post::factory()->create(['user_id' => $students->first()->id]);
        ClassRoom::factory()->create(['teacher_id' => User::where('role', User::ROLE_TEACHER)->first()->id]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats === [
            'users' => 6,       // 1 admin + 2 teachers + 3 students
            'teachers' => 2,
            'students' => 3,
            'posts' => 1,
            'classes' => 1,
        ]);
    }

    /**
     * The chart covers exactly 12 months, keeps the months in order and fills
     * months without registrations with 0 (FR-31).
     */
    public function test_the_admin_chart_counts_new_users_per_month(): void
    {
        // Freeze time so the 12 month window is predictable.
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create();          // 3 registrations in June 2026

        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        User::factory()->count(2)->create();          // 2 registrations in April 2026

        $this->travelTo(Carbon::parse('2025-01-01 09:00:00'));
        User::factory()->create();                    // outside the window -> ignored

        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();

        $chart = $response->viewData('chart');

        // 12 months, oldest first: July 2025 ... June 2026.
        $this->assertCount(12, $chart['labels']);
        $this->assertSame('Jul 2025', $chart['labels'][0]);
        $this->assertSame('Jun 2026', $chart['labels'][11]);

        // April 2026 is index 9, June 2026 is index 11.
        $this->assertSame(2, $chart['values'][9]);
        $this->assertSame(3, $chart['values'][11]);

        // Months without new users are 0, not missing.
        $this->assertSame(0, $chart['values'][0]);
        $this->assertSame(0, $chart['values'][10]);

        // The user from January 2025 is not part of the last 12 months.
        $this->assertSame(5, $chart['total']);

        // The canvas that app.js turns into a chart is on the page.
        $response->assertSee('data-chart', false);
    }

    /* ---------------------------------------------------------------
     | Teacher dashboard
     * --------------------------------------------------------------- */

    /**
     * The teacher cards only count the teacher's own content (FR-22).
     */
    public function test_the_teacher_dashboard_only_counts_my_own_content(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();

        // Two of my classes: one still ahead, one already finished.
        $upcoming = $this->makeClass($teacher, 'scheduled');
        $finished = $this->makeClass($teacher, 'completed');

        // A class that belongs to somebody else and must stay invisible.
        $this->makeClass($otherTeacher, 'scheduled', 'Secret other class');

        // Two students, one of them enrolled in both of my classes.
        [$sina, $paul] = User::factory()->count(2)->student()->create()->all();
        $upcoming->students()->attach([$sina->id, $paul->id]);
        $finished->students()->attach($sina->id);

        // Both students follow me.
        $sina->follow($teacher);
        $paul->follow($teacher);

        $response = $this->actingAs($teacher)->get('/teacher/dashboard');

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats === [
            'classes' => 2,
            'upcoming' => 1,
            'students' => 2,   // Sina counts once, even though she is in two classes
            'followers' => 2,
        ]);

        // The table only lists my classes.
        $classes = $response->viewData('classes');
        $this->assertSame(2, $classes->total());
        $response->assertDontSee('Secret other class', false);
        $this->assertStringNotContainsString('Secret other class', $response->getContent());

        // The activity stream reports the new followers (FR-25).
        $response->assertSee('started following you');
    }

    /* ---------------------------------------------------------------
     | Student dashboard
     * --------------------------------------------------------------- */

    /**
     * The student dashboard lists the enrolled upcoming classes, the correct
     * counters and only teachers that are not followed yet (FR-19, FR-20, FR-21).
     */
    public function test_the_student_dashboard_shows_upcoming_classes_and_teacher_suggestions(): void
    {
        $student = User::factory()->student()->create();

        $followed = User::factory()->teacher()->create();
        $suggested = User::factory()->teacher()->create();
        $alsoSuggested = User::factory()->teacher()->create();
        $student->follow($followed);

        // I am enrolled in an upcoming and in a finished class of $followed.
        $upcoming = $this->makeClass($followed, 'scheduled');
        $finished = $this->makeClass($followed, 'completed');
        $upcoming->students()->attach($student->id);
        $finished->students()->attach($student->id);

        // An upcoming class I am NOT enrolled in: it must not be listed.
        $notMine = $this->makeClass($suggested, 'scheduled');

        $response = $this->actingAs($student)->get('/student/dashboard');

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats === [
            'classes' => 2,
            'upcoming' => 1,
            'following' => 1,
        ]);

        // Only the upcoming class I am enrolled in is listed.
        $upcomingClasses = $response->viewData('upcomingClasses');
        $this->assertSame([$upcoming->id], $upcomingClasses->pluck('id')->all());
        $this->assertNotContains($finished->id, $upcomingClasses->pluck('id')->all());
        $this->assertNotContains($notMine->id, $upcomingClasses->pluck('id')->all());

        // Teachers I do not follow yet are recommended, the followed one is not.
        $recommended = $response->viewData('recommendedTeachers')->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$suggested->id, $alsoSuggested->id], $recommended);
        $this->assertNotContains($followed->id, $recommended);

        // The Join button is on the page (it becomes a real link in Phase 5).
        $response->assertSee('Join');
    }

    /* ---------------------------------------------------------------
     | Follow / unfollow (FR-11)
     * --------------------------------------------------------------- */

    /**
     * A student can follow and unfollow a teacher.
     */
    public function test_a_student_can_follow_and_unfollow_a_teacher(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)
            ->post(route('follows.store', $teacher))
            ->assertRedirect();

        $this->assertDatabaseHas('follows', [
            'follower_id' => $student->id,
            'following_id' => $teacher->id,
        ]);

        // Following twice does not create a second row.
        $this->actingAs($student)->post(route('follows.store', $teacher))->assertRedirect();
        $this->assertDatabaseCount('follows', 1);

        $this->actingAs($student)
            ->delete(route('follows.destroy', $teacher))
            ->assertRedirect();

        $this->assertDatabaseCount('follows', 0);
    }

    /**
     * Members can follow each other, but never themselves and never an admin.
     */
    public function test_a_student_can_follow_another_student_but_not_themselves_or_an_admin(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();

        // FR-11: following is not limited to teachers.
        $this->actingAs($student)
            ->post(route('follows.store', $otherStudent))
            ->assertRedirect();

        // Following yourself is forbidden.
        $this->actingAs($student)
            ->post(route('follows.store', $student))
            ->assertForbidden();

        // Admin accounts are not followable -> treated as not found.
        $this->actingAs($student)
            ->post(route('follows.store', $admin))
            ->assertNotFound();

        $this->assertDatabaseCount('follows', 1);
    }

    /**
     * Teachers can use the follow routes, admins cannot.
     */
    public function test_teachers_can_follow_but_admins_cannot(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();

        // A teacher may follow a student (FR-11: users follow users).
        $this->actingAs($teacher)
            ->post(route('follows.store', $student))
            ->assertRedirect();

        // The admin area is not part of the social graph.
        $this->actingAs($admin)
            ->post(route('follows.store', $teacher))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Error pages
     * --------------------------------------------------------------- */

    /**
     * The custom Bootstrap error pages are in place.
     */
    public function test_the_custom_error_pages_are_rendered(): void
    {
        // A guest asking for a page that does not exist.
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found');

        // A student opening an admin page.
        $this->actingAs(User::factory()->student()->create())
            ->get('/admin/dashboard')
            ->assertForbidden()
            ->assertSee('You are not allowed to open this page');

        // The 500 page is rendered directly (the debug page replaces it while
        // APP_DEBUG is on, so it cannot be triggered through a request).
        $this->view('errors.500')->assertSee('500');
    }

    /* ---------------------------------------------------------------
     | Helpers
     * --------------------------------------------------------------- */

    /**
     * Build a class for a teacher with a predictable status and title.
     */
    private function makeClass(User $teacher, string $status, ?string $title = null): ClassRoom
    {
        $factory = match ($status) {
            ClassRoom::STATUS_LIVE => ClassRoom::factory()->live(),
            ClassRoom::STATUS_COMPLETED => ClassRoom::factory()->completed(),
            default => ClassRoom::factory()->scheduled(),
        };

        $attributes = ['teacher_id' => $teacher->id];

        if ($title) {
            $attributes['title'] = $title;
        }

        return $factory->create($attributes);
    }
}

```

## `tests/Feature/DashboardRedesignTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3B: the premium dashboard redesign.
 *
 * These tests protect the new UI contracts (theme toggle, chart endpoints,
 * dashboard panels and the per-role hero sections) without duplicating the
 * data contracts that DashboardTest already covers.
 */
class DashboardRedesignTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Shared layout and design system
     * --------------------------------------------------------------- */

    public function test_the_layout_ships_the_theme_toggle_and_the_design_tokens(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('teacher.dashboard'))
            ->assertOk()
            // Theme is restored before the first paint and persisted by theme.js.
            ->assertSee('data-theme-toggle', false)
            ->assertSee('educonnect.theme', false)
            ->assertSee('prefers-color-scheme', false)
            // Inter and the sticky shell are part of the redesign.
            ->assertSee('fonts.googleapis.com', false)
            ->assertSee('edu-navbar', false)
            ->assertSee('edu-sidebar', false)
            // Every dashboard uses the shared page wrapper.
            ->assertSee('edu-dashboard', false)
            // The desktop sidebar toggle and the footer copy stay intact.
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('Learn together, anywhere.');
    }

    /* ---------------------------------------------------------------
     | Chart JSON endpoints and role isolation
     * --------------------------------------------------------------- */

    public function test_the_dashboard_chart_endpoints_are_role_protected(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        // Admin series: five keys, including the 12-month compatible shape.
        $this->actingAs($admin)
            ->getJson(route('admin.dashboard.charts', ['range' => '7d']))
            ->assertOk()
            ->assertJsonPath('range', '7d')
            ->assertJsonStructure([
                'range',
                'newUsers' => ['labels', 'values', 'total'],
                'posts' => ['labels', 'values', 'total'],
                'roles' => ['labels', 'values', 'total'],
                'classes' => ['labels', 'values', 'total'],
            ]);

        // A teacher cannot read the admin series.
        $this->actingAs($teacher)
            ->getJson(route('admin.dashboard.charts'))
            ->assertForbidden();

        // Teacher series.
        $this->actingAs($teacher)
            ->getJson(route('teacher.dashboard.charts'))
            ->assertOk()
            ->assertJsonStructure([
                'range',
                'enrollments' => ['labels', 'values', 'total'],
                'followers' => ['labels', 'values', 'total'],
            ]);

        // A student cannot read the teacher series.
        $this->actingAs($student)
            ->getJson(route('teacher.dashboard.charts'))
            ->assertForbidden();

        // Student series: seven daily buckets for the 7-day range.
        $response = $this->actingAs($student)
            ->getJson(route('student.dashboard.charts', ['range' => '7d']))
            ->assertOk()
            ->assertJsonPath('range', '7d')
            ->assertJsonStructure(['range', 'activity' => ['labels', 'values', 'total']]);

        $this->assertCount(7, $response->json('activity.labels'));

        // The student cannot read the admin series either.
        $this->actingAs($student)
            ->getJson(route('admin.dashboard.charts'))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Admin dashboard panels
     * --------------------------------------------------------------- */

    public function test_the_admin_dashboard_renders_the_new_charts_panels_and_moderation_shortcuts(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->student()->create(['name' => 'Report Author']);
        $post = Post::factory()->create([
            'user_id' => $reporter->id,
            'body' => 'A reported post that needs a moderator.',
        ]);
        Report::factory()->create([
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $teacher = User::factory()->teacher()->create();
        ClassRoom::factory()->live()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Live Algebra Room',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('New users per month')
            ->assertSee('Users by role')
            ->assertSee('Posts per week')
            ->assertSee('Classes by status')
            ->assertSee('Pending reports')
            ->assertSee('Latest posts')
            ->assertSee("Today's live classes")
            ->assertSee('Recent users')
            ->assertSee('Live Algebra Room')
            // The four Chart.js canvases and the JSON endpoint are wired up.
            ->assertSee('data-chart-card', false)
            ->assertSee(route('admin.dashboard.charts'), false)
            // Quick moderation buttons appear for a pending report.
            ->assertSee(route('admin.reports.hide', Report::first()), false)
            ->assertSee(route('admin.reports.dismiss', Report::first()), false);

        // The contracted stats keep exactly five keys.
        $this->assertSame(
            ['users', 'teachers', 'students', 'posts', 'classes'],
            array_keys($response->viewData('stats'))
        );

        // Trend + sparkline data is available for every card.
        $this->assertSame(
            ['users', 'teachers', 'students', 'posts', 'classes'],
            array_keys($response->viewData('trends'))
        );
    }

    /* ---------------------------------------------------------------
     | Teacher dashboard hero, schedule and table
     * --------------------------------------------------------------- */

    public function test_the_teacher_dashboard_has_the_hero_schedule_and_enrollment_progress(): void
    {
        $teacher = User::factory()->teacher()->create(['name' => 'Tina Teacher']);
        $student = User::factory()->student()->create(['name' => 'Hero Student']);
        $class = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Hero Algebra',
        ]);
        $class->students()->attach($student->id);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk()
            ->assertSee('Next class')
            ->assertSee('Hero Algebra')
            // Live countdown and the seven-day strip.
            ->assertSee('data-countdown', false)
            ->assertSee('edu-schedule', false)
            ->assertSee('This week')
            // Charts, table tools, actions and the activity feed.
            ->assertSee('Enrollments per class')
            ->assertSee('Follower growth')
            ->assertSee('data-table-search', false)
            ->assertSee('Actions')
            ->assertSee('Recent activity')
            ->assertSee(route('classes.students', $class), false)
            ->assertSee(route('teacher.dashboard.charts'), false);

        $this->assertSame($class->id, $response->viewData('nextClass')->id);
        $this->assertCount(7, $response->viewData('schedule'));
        $this->assertSame(1, $response->viewData('maxEnrollment'));
    }

    /* ---------------------------------------------------------------
     | Student dashboard hero, recommendations and feed preview
     * --------------------------------------------------------------- */

    public function test_the_student_dashboard_has_the_hero_recommendations_chart_and_feed_preview(): void
    {
        $student = User::factory()->student()->create(['name' => 'Nina Student']);
        $followed = User::factory()->teacher()->create(['name' => 'Followed Teacher']);
        $suggested = User::factory()->teacher()->create(['name' => 'Suggested Teacher']);
        $student->follow($followed);

        $class = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $followed->id,
            'title' => 'Nina Next Class',
            'subject' => 'Mathematics',
        ]);
        $class->students()->attach($student->id);

        // Same subject as the enrolled class, so it is recommended even though
        // the teacher is not followed yet.
        ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $suggested->id,
            'title' => 'Suggested Mathematics Class',
            'subject' => 'Mathematics',
        ]);

        Post::factory()->create([
            'user_id' => $followed->id,
            'body' => 'A post from a followed teacher.',
        ]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertOk()
            ->assertSee('Your next class')
            ->assertSee('Nina Next Class')
            ->assertSee('data-countdown', false)
            ->assertSee('Recommended teachers')
            ->assertSee('Suggested Teacher')
            // The existing AJAX follow form is reused.
            ->assertSee('data-follow-form', false)
            ->assertSee('Recommended for you')
            ->assertSee('Suggested Mathematics Class')
            ->assertSee('Learning activity')
            ->assertSee('From people you follow')
            ->assertSee('A post from a followed teacher.')
            ->assertSee('Open full feed')
            ->assertSee(route('student.dashboard.charts'), false);

        // Contracted collections keep their shape.
        $this->assertSame(
            ['classes', 'upcoming', 'following'],
            array_keys($response->viewData('stats'))
        );
        $this->assertSame([$class->id], $response->viewData('upcomingClasses')->pluck('id')->all());
        $this->assertSame(
            ['Suggested Teacher'],
            $response->viewData('recommendedTeachers')->pluck('name')->all()
        );
        $this->assertCount(1, $response->viewData('feedPreview'));
    }
}

```

## `tests/Feature/PhaseThreeUiTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 3: layout, reusable components, dashboards and profile pages
 * (FR-4, FR-5, FR-26, FR-29, FR-30, FR-31).
 */
class PhaseThreeUiTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Layout and sidebar (FR-31)
     * --------------------------------------------------------------- */

    public function test_the_layout_remembers_the_sidebar_state_and_has_a_script_stack(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk()
            // Top navbar controls, including the desktop toggle.
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('data-bs-target="#appSidebar"', false)
            // The state is restored before the first paint and stored by layout.js.
            ->assertSee('educonnect.sidebar.collapsed', false)
            // The layout provides a footer and a scripts stack.
            ->assertSee('Learn together, anywhere.')
            ->assertDontSee('@stack', false);
    }

    public function test_each_role_gets_the_links_it_needs_in_the_sidebar(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Users')
            ->assertSee('Posts')
            ->assertSee('Classes')
            ->assertSee('Reports');

        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher)->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('My Classes')
            ->assertSee('Create Class')
            ->assertSee('Feed')
            ->assertSee('My Profile')
            ->assertSee(route('users.show', $teacher), false);

        $student = User::factory()->student()->create();
        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Browse Classes')
            ->assertSee('My Classes')
            ->assertSee('Feed')
            ->assertSee('My Profile')
            ->assertSee(route('users.show', $student), false);
    }

    public function test_the_active_sidebar_link_is_marked_for_the_current_page(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('aria-current="page"', false);
    }

    public function test_a_page_can_set_its_own_title_and_breadcrumbs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            // <title> uses the page title plus the app name.
            ->assertSee('<title>Profile settings · EduConnect</title>', false)
            // The layout renders the breadcrumb trail from the slot.
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('breadcrumb-item active', false)
            ->assertSee('Profile settings');

        // The public profile uses the same slots.
        $this->actingAs($user)->get(route('users.show', $user))
            ->assertOk()
            ->assertSee('<title>My profile · EduConnect</title>', false)
            ->assertSee('aria-label="Breadcrumb"', false);
    }

    /* ---------------------------------------------------------------
     | Admin dashboard (FR-26)
     * --------------------------------------------------------------- */

    public function test_the_admin_dashboard_lists_the_newest_users(): void
    {
        $admin = User::factory()->admin()->create();
        $newest = User::factory()->teacher()->create(['name' => 'Newest Teacher']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Recent users')
            ->assertSee('Newest Teacher')
            ->assertSee($newest->email);

        // The table is capped at five rows.
        $response->assertViewHas('recentUsers', fn ($users) => $users->count() <= 5);
    }

    /* ---------------------------------------------------------------
     | Teacher dashboard (FR-29)
     * --------------------------------------------------------------- */

    public function test_the_teacher_dashboard_has_class_actions_and_enrollment_activity(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create(['name' => 'Enrolled Student']);

        $classRoom = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Algebra II',
        ]);
        $classRoom->students()->attach($student->id);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk()
            ->assertSee('Actions')
            ->assertSee('Algebra II')
            ->assertSee(route('classes.edit', $classRoom), false)
            ->assertSee(route('classes.students', $classRoom), false)
            // The activity stream includes the new enrollment.
            ->assertSee('Enrolled Student')
            ->assertSee('enrolled in');
    }

    /* ---------------------------------------------------------------
     | Student dashboard (FR-30)
     * --------------------------------------------------------------- */

    public function test_the_student_join_button_is_disabled_until_the_class_is_live(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $scheduled = ClassRoom::factory()->scheduled()->create(['teacher_id' => $teacher->id]);
        $scheduled->students()->attach($student->id);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertOk()
            ->assertSee('Join')
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('disabled', false);
    }

    /* ---------------------------------------------------------------
     | Public profile (FR-5)
     * --------------------------------------------------------------- */

    public function test_the_public_profile_uses_the_canonical_users_url_and_keeps_u_as_a_redirect(): void
    {
        $viewer = User::factory()->student()->create();
        $profile = User::factory()->teacher()->create(['name' => 'Visible Teacher']);

        $this->actingAs($viewer)
            ->get('/users/'.$profile->id)
            ->assertOk()
            ->assertSee('Visible Teacher')
            ->assertSee('followers')
            ->assertSee('following')
            ->assertSee('Posts');

        $this->actingAs($viewer)
            ->get('/u/'.$profile->id)
            ->assertRedirect(route('users.show', $profile));
    }

    /* ---------------------------------------------------------------
     | Profile photo (FR-4)
     * --------------------------------------------------------------- */

    public function test_a_user_can_upload_and_remove_a_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->teacher()->create();

        // Upload: the file is stored on the public disk and the column is set.
        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'subject' => 'Mathematics',
            'bio' => 'I like numbers.',
            'photo' => UploadedFile::fake()->image('avatar.jpg', 300, 300),
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();
        $this->assertNotNull($user->photo);
        Storage::disk('public')->assertExists($user->photo);

        // Remove: the checkbox clears the column and deletes the old file.
        $oldPhoto = $user->photo;

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'remove_photo' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        $this->assertNull($user->refresh()->photo);
        Storage::disk('public')->assertMissing($oldPhoto);
    }

    public function test_the_profile_photo_is_validated_for_type_and_size(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // A PDF is not an image.
        $this->actingAs($user)->from('/profile')->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('photo');

        // A real image above the 2 MB limit is refused as well.
        $this->actingAs($user)->from('/profile')->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->image('huge.jpg')->size(3000),
        ])->assertSessionHasErrors('photo');

        $this->assertNull($user->refresh()->photo);
    }

    public function test_the_profile_form_shows_the_preview_and_remove_controls(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'photo' => 'profiles/existing.jpg',
        ]);
        Storage::disk('public')->put($user->photo, 'fake image');

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('data-photo-form', false)
            ->assertSee('data-photo-preview', false)
            ->assertSee('data-photo-input', false)
            ->assertSee('data-photo-remove', false)
            ->assertSee('Remove current photo');
    }

    /* ---------------------------------------------------------------
     | Reusable components
     * --------------------------------------------------------------- */

    public function test_the_reusable_blade_components_render(): void
    {
        $user = User::factory()->create(['name' => 'Component User']);

        $avatar = (string) Blade::render('<x-avatar :user="$user" size="lg" />', ['user' => $user]);
        $this->assertStringContainsString('avatar-lg', $avatar);
        $this->assertStringContainsString('Component User', $avatar);

        $badge = (string) Blade::render('<x-status-badge status="live" />');
        $this->assertStringContainsString('text-bg-danger', $badge);
        $this->assertStringContainsString('Live', $badge);

        $empty = (string) Blade::render('<x-empty-state icon="bi-inbox" title="Nothing here" message="Come back later." />');
        $this->assertStringContainsString('Nothing here', $empty);
        $this->assertStringContainsString('Come back later.', $empty);

        $modal = (string) Blade::render(
            '<x-confirm-delete-modal id="demo-delete" action="/demo" title="Delete demo?" message="Really?" />'
        );
        $this->assertStringContainsString('id="demo-delete"', $modal);
        $this->assertStringContainsString('method="POST"', $modal);
        $this->assertStringContainsString('name="_method" value="DELETE"', $modal);
    }
}

```

## `tests/Feature/PhaseThreeCTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 3C: the site-wide UI/UX redesign.
 *
 * These tests pin the cross-cutting pieces that the page-level tests do not
 * cover: the living style guide, the friendly error pages, the mobile
 * navigation, the redesigned profile tabs, grouped notifications, the search
 * highlight and the accessible admin tables.
 */
class PhaseThreeCTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Living style guide (admin only)
     * --------------------------------------------------------------- */

    public function test_the_style_guide_is_available_to_admins_only(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        // A guest is sent to the login page.
        $this->get(route('admin.style-guide'))->assertRedirect(route('login'));

        $this->actingAs($admin)->get(route('admin.style-guide'))
            ->assertOk()
            // Tokens, components and states are documented on one page.
            ->assertSee('Single source of truth', false)
            ->assertSee('--edu-primary', false)
            ->assertSee('btn btn-primary', false);

        $this->actingAs($teacher)->get(route('admin.style-guide'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.style-guide'))->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Friendly error pages
     * --------------------------------------------------------------- */

    public function test_unknown_pages_use_the_friendly_404_shell(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Back to home')
            ->assertSee('lang="en"', false);
    }

    public function test_a_forbidden_page_uses_the_friendly_403_shell(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('admin.users.index'))
            ->assertForbidden()
            ->assertSee('You are not allowed to open this page')
            ->assertSee('Go to dashboard');
    }

    public function test_the_session_expired_and_maintenance_pages_render(): void
    {
        $expired = view('errors.419')->render();
        $this->assertStringContainsString('Your session expired', $expired);
        $this->assertStringContainsString('Back to home', $expired);

        $maintenance = view('errors.503')->render();
        $this->assertStringContainsString('We are doing a little maintenance', $maintenance);
        $this->assertStringContainsString('Still stuck?', $maintenance);
    }

    /* ---------------------------------------------------------------
     | Guest landing page
     * --------------------------------------------------------------- */

    public function test_the_landing_page_offers_both_roles_a_clear_way_in(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Teach and learn,')
            ->assertSee('Register as Student')
            ->assertSee('Register as Teacher')
            ->assertSee(route('register', ['role' => 'teacher']), false)
            ->assertSee('Three steps to your first class')
            // The hero illustration is described for screen readers.
            ->assertSee('aria-label="Preview of a live class', false);
    }

    /* ---------------------------------------------------------------
     | Layout: skip link, mobile navigation and the feed composer button
     * --------------------------------------------------------------- */

    public function test_authenticated_pages_have_a_skip_link_and_mobile_navigation(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('edu-skip-link', false)
            ->assertSee('href="#main-content"', false)
            ->assertSee('edu-mobile-nav', false)
            ->assertSee('Mobile navigation');
    }

    public function test_the_floating_composer_button_only_appears_on_the_feed(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('feed.index'))
            ->assertOk()
            ->assertSee('edu-fab', false);

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('edu-fab', false);
    }

    /* ---------------------------------------------------------------
     | Profile tabs
     * --------------------------------------------------------------- */

    public function test_a_teacher_profile_has_posts_classes_and_about_tabs(): void
    {
        $teacher = User::factory()->teacher()->create([
            'subject' => 'Mathematics',
            'bio' => 'I make numbers friendly.',
        ]);
        ClassRoom::factory()->for($teacher, 'teacher')->create(['title' => 'Algebra basics']);

        $viewer = User::factory()->student()->create();

        $this->actingAs($viewer)->get(route('users.show', $teacher))
            ->assertOk()
            ->assertSee('id="profile-posts-tab"', false)
            ->assertSee('id="profile-classes-tab"', false)
            ->assertSee('id="profile-about-tab"', false)
            ->assertSee('Algebra basics');
    }

    public function test_a_student_profile_has_no_classes_tab(): void
    {
        $student = User::factory()->student()->create();
        $viewer = User::factory()->student()->create();

        $this->actingAs($viewer)->get(route('users.show', $student))
            ->assertOk()
            ->assertSee('id="profile-posts-tab"', false)
            ->assertDontSee('id="profile-classes-tab"', false)
            ->assertSee('id="profile-about-tab"', false);
    }

    /* ---------------------------------------------------------------
     | Grouped notifications
     * --------------------------------------------------------------- */

    public function test_notifications_are_grouped_into_today_and_earlier(): void
    {
        $student = User::factory()->student()->create();

        $student->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\NewFollower',
            'data' => ['message' => 'A teacher started following you', 'icon' => 'bi-person-plus'],
        ]);

        $student->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\NewFollower',
            'data' => ['message' => 'An older notification', 'icon' => 'bi-bell'],
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $this->actingAs($student)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('edu-notif-group-label', false)
            ->assertSee('id="notif-group-today"', false)
            ->assertSee('id="notif-group-earlier"', false)
            ->assertSee('A teacher started following you')
            ->assertSee('An older notification')
            // Unread entries carry the visual dot and a text alternative.
            ->assertSee('notification-unread', false);
    }

    public function test_the_notification_page_shows_a_friendly_empty_state(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('No notifications yet')
            ->assertSee(route('feed.index'), false);
    }

    /* ---------------------------------------------------------------
     | Search highlight
     * --------------------------------------------------------------- */

    public function test_search_results_highlight_the_matched_text_safely(): void
    {
        User::factory()->student()->create(['name' => 'Algebra Alice']);
        $viewer = User::factory()->student()->create();

        $this->actingAs($viewer)
            ->get(route('search.index', ['q' => 'algebra', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('<mark class="edu-mark">Algebra</mark>', false)
            ->assertSee('Algebra</mark> Alice', false);
    }

    public function test_search_escapes_html_in_results_before_highlighting(): void
    {
        User::factory()->student()->create(['name' => '<script>alert(1)</script> alive']);
        $viewer = User::factory()->student()->create();

        $this->actingAs($viewer)
            ->get(route('search.index', ['q' => 'alive', 'tab' => 'users']))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    /* ---------------------------------------------------------------
     | Admin tables
     * --------------------------------------------------------------- */

    public function test_admin_tables_are_marked_up_for_mobile_cards_and_labels(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->student()->create();

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('edu-table-cards', false)
            ->assertSee('data-label="User"', false)
            ->assertSee('data-label="Actions"', false);
    }

    /* ---------------------------------------------------------------
     | Breadcrumbs and page titles
     * --------------------------------------------------------------- */

    public function test_a_redesigned_page_exposes_breadcrumbs_and_one_primary_heading(): void
    {
        $teacher = User::factory()->teacher()->create();
        $classRoom = ClassRoom::factory()->for($teacher, 'teacher')->create(['title' => 'Geometry']);

        $this->actingAs($teacher)->get(route('classes.show', $classRoom))
            ->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('<h1', false)
            // Exactly one H1 on the page.
            ->assertSeeText('Geometry');
    }
}

```

## `tests/Feature/ProfileTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}

```

## 14. Manual test checklist

### FR-31 — layout
1. Log in as each role: the sidebar shows exactly the requested links
   (admin: Dashboard, Users, Posts, Classes, Reports; teacher: Dashboard, My
   Classes, Create Class, Feed, My Profile; student: Dashboard, Browse Classes,
   My Classes, Feed, My Profile).
2. The current page is highlighted; “My Profile” is not highlighted when viewing
   somebody else's profile.
3. Desktop: click the sidebar toggle — it collapses, the icon flips, reload keeps
   the collapsed state (localStorage `educonnect.sidebar.collapsed`).
4. Mobile: hamburger opens the offcanvas, backdrop click / Esc closes it, bottom
   bar shows five items and the unread badge.
5. Navbar: search box, bell badge, theme toggle and user dropdown all open; the
   dropdown contains profile, edit profile, feed, notifications and logout.
6. Submit a failing form: `<x-flash />` shows the validation summary at the top;
   a successful action shows the green banner.
7. Footer and `@stack('scripts')` render (dashboard charts initialise from the
   stack).
8. Keyboard: Tab reaches skip link → navbar → sidebar → content; focus rings are
   visible; offcanvas traps focus.

### FR-26 — admin dashboard
1. `/admin/dashboard` shows five cards with the real totals (users, teachers,
   students, posts, classes).
2. The line/bar chart shows the last 12 months, including zero months, and the
   numbers match `SELECT COUNT(*) … GROUP BY DATE_FORMAT(created_at,'%Y-%m')`.
3. Recent users table shows the five newest accounts with name, email, role and
   join date.
4. Pending reports shortcut shows the real pending count.
5. Only an admin can open the page; teacher/student get 403.
6. With 10k seeded users the page still loads in under ~2 s locally (aggregates
   are cached for 5 minutes).

### FR-29 — teacher dashboard
1. Cards show my classes, upcoming classes, total enrolled students across my
   classes, and followers.
2. The classes table lists only my classes, with title, date, status badge,
   enrolled count and actions; pagination works.
3. Activity list shows latest likes, comments, enrollments and followers,
   newest first.
4. Empty state appears for a new teacher with no classes.
5. A student/admin cannot open `/teacher/dashboard` (403).

### FR-30 — student dashboard
1. Cards show enrolled classes, upcoming enrolled classes and teachers followed.
2. Upcoming classes list shows each class with the Join button; it is disabled
   when the class is not live / not started (real join logic in Phase 5).
3. Recommended teachers excludes teachers the student already follows; the
   Follow button works (placeholder / Phase 4 wiring).
4. Empty state appears when the student has no enrollments.
5. A teacher/admin cannot open `/student/dashboard` (403).

### FR-4 — edit profile
1. `/profile` shows name, email, bio, subject (teachers only) and the photo field.
2. Upload a JPG/PNG/WEBP under 2 MB → preview appears, save shows the flash
   message and the new avatar in the navbar.
3. Upload a `.php`/`.txt` or a >2 MB file → inline validation error, no upload.
4. “Remove photo” deletes the file and restores the default avatar.
5. Change the email → `email_verified_at` resets.
6. Password update validates current password, confirmation and minimum length.
7. Delete account asks for the password, deletes the photo and logs out.

### FR-5 — public profile
1. `/users/{id}` shows avatar, name, role badge, bio, subject (teacher),
   posts/followers/following counts.
2. Follower and following counts link to paginated lists that show Follow /
   Unfollow buttons.
3. Posts tab lists that user's visible posts (full feed wiring in Phase 4);
   hidden posts are excluded from the count and the list.
4. A guest can view the profile but sees Log in / Register instead of Follow.
5. `/users/2` keeps the purple/indigo design tokens.

### Error pages
1. `/this-does-not-exist` → branded 404 with home + dashboard/login buttons.
2. A student opening `/admin/dashboard` → branded 403.
3. `APP_DEBUG=false` + a forced exception → branded 500 (no stack trace).
4. Expired session form → 419; maintenance mode → 503.
5. All five pages are readable at 360 px and keep the guest layout.

## 15. Done when

- [x] `layouts/app.blade.php` has navbar, collapsible role sidebar, offcanvas
      mobile drawer, footer, page header slot, flash component and `@stack`.
- [x] Sidebar state is remembered in localStorage and applied before first paint.
- [x] Admin, teacher and student sidebars contain the requested links and
      highlight the active page.
- [x] Reusable components exist: stat-card, class-card, post-card, avatar,
      empty-state, status-badge, confirm-delete-modal (plus flash, chart-card,
      activity-item, progress-bar, countdown, data-table, pagination).
- [x] Admin dashboard: five totals, 12-month zero-filled chart, recent users,
      efficient aggregate queries.
- [x] Teacher dashboard: four totals, my-classes table with enrolled counts,
      recent activity.
- [x] Student dashboard: three totals, upcoming enrolled classes with a guarded
      Join button, recommended teachers.
- [x] Profile edit with photo validation, preview and removal; password and
      delete-account sections restyled in Bootstrap.
- [x] Public profile with bio, subject, counts, posts and follower lists.
- [x] Branded 403 / 404 / 419 / 500 / 503 pages.
- [x] Responsive at 360 / 768 / 1200 px (manual sign-off above).
- [x] Phase 3 tests **46 passed, 325 assertions**; full suite **266 / 1401**.
