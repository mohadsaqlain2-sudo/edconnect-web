# EduConnect - Phase 3 deliverable

Layout, reusable Blade components, the three role dashboards, profile pages,
public profiles and the custom error pages for SRS section 3.1
(FR-4, FR-5, FR-26, FR-29, FR-30, FR-31).

> Runtime note: this repository runs on **Laravel 12.70.2** (the SRS asks for
> Laravel 11). The code uses no Laravel-12-only API; every view, controller and
> request below is compatible with Laravel 11. Only the framework version
> differs. This was already disclosed in Phase 1 and Phase 2.

---

## 1. What Phase 3 adds

| Area | Result |
|---|---|
| Main layout | Top navbar, role sidebar (desktop column + mobile offcanvas), remembered desktop toggle, search box, notification bell + badge, user dropdown, flash messages, footer, page title slot, breadcrumbs slot, `@stack('scripts')`, skip link |
| Reusable components | `stat-card`, `class-card`, `post-card`, `avatar`, `empty-state`, `status-badge`, `confirm-delete-modal`, plus the existing `flash`, `user-card`, `modal`, form components |
| Admin dashboard | Total users / teachers / students / posts / classes, zero-filled 12-month new-user chart, recent-users table, pending-report shortcuts |
| Teacher dashboard | My classes / upcoming / enrolled students / followers cards, paginated class table with join-students-edit actions, activity stream with likes, comments, enrollments and new followers |
| Student dashboard | Enrolled / upcoming / followed-teachers cards, upcoming enrolled classes, Join disabled until the class is live, teacher recommendations |
| Profile | Name, email, bio, teacher subject, photo upload (type + 2 MB limit), live preview, remove current photo, `ProfileUpdateRequest` |
| Public profile | Canonical `/users/{user}` page with posts, followers and following counts, follower/following lists, follow button; legacy `/u/...` URLs redirect |
| Error pages | Custom 403, 404 and 500 pages, no stack trace shown |

---

## 2. Verification performed

```text
php -v                          PHP 8.3.31 (cli), ZTS
php artisan --version           Laravel Framework 12.70.2

./vendor/bin/pint --test <changed PHP files>
                                passed (2 files auto-fixed first: import order)

npm run build                   vite v6.4.5, 130 modules transformed
                                public/build/assets/app-*.css  314.33 kB
                                public/build/assets/app-*.js   416.87 kB
                                built in ~16 s

php artisan test                193 passed (849 assertions), ~21 s
php artisan test --filter=PhaseThreeUiTest
                                12 passed (86 assertions)

php artisan route:list --path=users
                                GET|HEAD  users/{user}             users.show
                                GET|HEAD  users/{user}/followers   users.followers
                                GET|HEAD  users/{user}/following   users.following
```

Tests run on SQLite `:memory:` with array mail/session and the sync queue
(`phpunit.xml`). MySQL-specific statements are standard SQL and were not run
because MySQL 8 is not installed on this machine.

---

## 3. File map

### New files

| File | Purpose |
|---|---|
| `resources/views/components/avatar.blade.php` | Photo or default avatar in 4 sizes, optional profile link |
| `resources/views/components/empty-state.blade.php` | Shared icon + title + message + action block |
| `resources/views/components/status-badge.blade.php` | One badge for class, report and moderation states |
| `resources/views/components/confirm-delete-modal.blade.php` | Bootstrap delete-confirmation modal + trigger form |
| `resources/js/layout.js` | Desktop sidebar toggle, persisted in `localStorage` |
| `resources/js/profile-photo.js` | Photo preview and remove-photo behaviour |
| `tests/Feature/PhaseThreeUiTest.php` | 12 Phase 3 tests |
| `docs/PHASE-3-DELIVERABLE.md` | This document |

### Modified files

| File | Change |
|---|---|
| `resources/views/layouts/app.blade.php` | Title slot, breadcrumbs, `@stack`, skip link, footer, pre-paint sidebar state |
| `resources/views/layouts/navigation.blade.php` | Desktop sidebar toggle, avatar, profile/edit-profile menu items |
| `resources/views/layouts/sidebar.blade.php` | Role menus, own-profile active state, account links |
| `resources/views/components/class-card.blade.php` | Uses `x-status-badge` |
| `resources/views/dashboards/admin.blade.php` | Recent-users table, shared components, avatar links |
| `resources/views/dashboards/teacher.blade.php` | Actions column, status badge, activity stream |
| `resources/views/dashboards/student.blade.php` | Disabled Join button until live |
| `resources/views/profile/edit.blade.php` | Title + breadcrumbs + intro |
| `resources/views/profile/partials/update-profile-information-form.blade.php` | Preview, remove-photo checkbox |
| `resources/views/users/show.blade.php` | Title, breadcrumbs, avatar component, post heading |
| `resources/views/classes/index.blade.php` | Shared empty state and delete modal |
| `app/Http/Controllers/AdminDashboardController.php` | One aggregate user query + `recentUsers` |
| `app/Http/Controllers/TeacherDashboardController.php` | Enrollment activity entries |
| `app/Http/Controllers/StudentDashboardController.php` | (formatting only, no behaviour change) |
| `app/Http/Controllers/ProfileController.php` | Remove-photo handling |
| `app/Http/Controllers/UserProfileController.php` | Canonical `/users` docblock |
| `app/Http/Requests/ProfileUpdateRequest.php` | `remove_photo` rule |
| `resources/js/app.js` | Imports `layout.js` and `profile-photo.js` |
| `resources/sass/app.scss` | `avatar-sm`/`avatar-xl`, collapsed sidebar, preview, breadcrumbs |
| `routes/web.php` | Canonical `/users/...` routes + `/u/...` redirects |

---

## 4. Layouts

### `resources/views/layouts/app.blade.php`

```php
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            // A page can pass <x-slot name="title">Page title</x-slot>.
            $pageTitle = isset($title) ? trim(strip_tags((string) $title)) : '';
        @endphp
        <title>{{ $pageTitle !== '' ? $pageTitle.' · ' : '' }}{{ config('app.name', 'EduConnect') }}</title>

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

        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    {{-- body carries the notification polling URL for logged in users (FR-24) --}}
    <body class="d-flex flex-column min-vh-100" @auth data-notification-url="{{ route('notifications.poll') }}" @endauth>
        <a class="visually-hidden-focusable d-inline-flex p-2 bg-white" href="#main-content">
            {{ __('Skip to main content') }}
        </a>

        {{-- Top navigation bar --}}
        @include('layouts.navigation')

        <div class="flex-grow-1 d-lg-flex" id="appShell">
            {{-- Role based sidebar: a column on desktop, an offcanvas drawer on phones. --}}
            @auth
                @include('layouts.sidebar')
            @endauth

            <main class="flex-grow-1 py-4" id="main-content">
                <div class="container">
                    {{-- Success / error / validation messages --}}
                    <x-flash />

                    {{-- Page header: title and optional breadcrumbs. --}}
                    @if (isset($header) || isset($breadcrumbs))
                        <div class="mb-4">
                            @isset($breadcrumbs)
                                <nav aria-label="{{ __('Breadcrumb') }}" class="mb-1">
                                    <ol class="breadcrumb small mb-0">{{ $breadcrumbs }}</ol>
                                </nav>
                            @endisset

                            @isset($header)
                                {{ $header }}
                            @endisset
                        </div>
                    @endif
                </div>

                {{ $slot }}
            </main>
        </div>

        <footer class="py-4 mt-auto border-top bg-white">
            <div class="container">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 text-muted small">
                    <span>&copy; {{ date('Y') }} {{ config('app.name', 'EduConnect') }}</span>
                    <span>{{ __('Learn together, anywhere.') }}</span>
                </div>
            </div>
        </footer>

        {{-- Page specific JavaScript (kept out of the markup). --}}
        @stack('scripts')
    </body>
</html>
```

### `resources/views/layouts/navigation.blade.php`

```php
{{-- Top navigation bar: menu toggle (mobile), brand, search, notifications, user menu. --}}
@php
    // Unread notifications drive the bell counter (FR-24); the newest five fill
    // the dropdown. The polling script refreshes both every 45 seconds.
    $unreadNotifications = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    $latestNotifications = auth()->check()
        ? auth()->user()->notifications()->latest()->take(5)->get()
        : collect();
@endphp

<nav class="navbar navbar-dark bg-dark sticky-top">
    <div class="container-fluid px-3 px-lg-4">
        @auth
            {{-- Opens the sidebar drawer on phones and tablets only;
                 from lg upwards the sidebar is always visible. --}}
            <button class="btn btn-outline-light btn-sm me-2 d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#appSidebar"
                    aria-controls="appSidebar" aria-label="{{ __('Open the menu') }}">
                <i class="bi bi-list fs-5"></i>
            </button>

            {{-- Desktop: collapse / expand the sidebar. The state is stored in
                 localStorage by resources/js/layout.js (FR-31). --}}
            <button class="btn btn-outline-light btn-sm me-2 d-none d-lg-inline-flex align-items-center"
                    type="button" data-sidebar-toggle
                    aria-controls="appSidebar" aria-expanded="true"
                    aria-label="{{ __('Toggle the sidebar') }}"
                    title="{{ __('Toggle the sidebar') }}">
                <i class="bi bi-layout-sidebar-inset" data-sidebar-toggle-icon aria-hidden="true"></i>
            </button>
        @endauth

        <a class="navbar-brand fw-bold d-flex align-items-center gap-2"
           href="{{ auth()->check() ? route('dashboard') : url('/') }}">
            <x-application-logo class="text-white" />
            <span class="d-none d-sm-inline">{{ config('app.name', 'EduConnect') }}</span>
        </a>

        <div class="d-flex align-items-center ms-auto gap-2">
            @auth
                {{-- One search box for people and hashtags (FR-14).
                     On phones the box would not fit, so there is an icon instead. --}}
                <form method="GET" action="{{ route('search.index') }}" role="search" class="edu-search d-none d-md-block">
                    <div class="input-group input-group-sm">
                        <input type="search" name="q" class="form-control border-0"
                               value="{{ request()->routeIs('search.*') ? request('q') : '' }}"
                               placeholder="{{ __('Search people or #tags') }}"
                               aria-label="{{ __('Search') }}">
                        <button class="btn btn-light" type="submit" aria-label="{{ __('Search') }}">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            @endauth

            <ul class="navbar-nav flex-row align-items-center gap-1">
            @auth
                {{-- Phone shortcut to the search page --}}
                <li class="nav-item d-md-none">
                    <a class="btn btn-outline-light btn-sm" href="{{ route('search.index') }}"
                       aria-label="{{ __('Search') }}">
                        <i class="bi bi-search"></i>
                    </a>
                </li>

                {{-- Notification bell: unread counter + newest five (FR-24) --}}
                <li class="nav-item dropdown">
                    <button class="btn btn-outline-light btn-sm position-relative" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="{{ __('Notifications') }}" data-notification-toggle>
                        <i class="bi bi-bell"></i>
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
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <x-avatar :user="auth()->user()" size="sm" class="me-2" />
                        <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
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
                                <i class="bi bi-person me-1"></i> {{ __('My profile') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-pencil-square me-1"></i> {{ __('Edit profile') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('feed.index') }}">
                                <i class="bi bi-chat-square-text me-1"></i> {{ __('Feed') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('notifications.index') }}">
                                <i class="bi bi-bell me-1"></i> {{ __('Notifications') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-1"></i> {{ __('Log out') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            @else
                {{-- Guests see login / register --}}
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm" href="{{ route('login') }}">{{ __('Log in') }}</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-light btn-sm" href="{{ route('register') }}">{{ __('Register') }}</a>
                </li>
            @endauth
            </ul>
        </div>
    </div>
</nav>
```

### `resources/views/layouts/sidebar.blade.php`

```php
{{-- Role based sidebar.
     From lg upwards it is a fixed column; on phones it is an offcanvas drawer
     opened with the hamburger button in the navbar. The desktop toggle in the
     navbar stores its state in localStorage (FR-31). --}}
@php
    $role = auth()->user()->role;
    $me = auth()->user();

    $menu = match ($role) {
        \App\Models\User::ROLE_ADMIN => [
            ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'admin.dashboard'],
            ['label' => 'Users', 'icon' => 'bi-people', 'route' => 'admin.users.index'],
            ['label' => 'Posts', 'icon' => 'bi-chat-square-text', 'route' => 'admin.posts.index'],
            ['label' => 'Classes', 'icon' => 'bi-easel', 'route' => 'admin.classes.index'],
            ['label' => 'Reports', 'icon' => 'bi-flag', 'route' => 'admin.reports.index'],
        ],
        \App\Models\User::ROLE_TEACHER => [
            ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'teacher.dashboard'],
            ['label' => 'My Classes', 'icon' => 'bi-easel', 'route' => 'classes.index'],
            ['label' => 'Create Class', 'icon' => 'bi-plus-square', 'route' => 'classes.create'],
            ['label' => 'Feed', 'icon' => 'bi-chat-square-text', 'route' => 'feed.index'],
            ['label' => 'My Profile', 'icon' => 'bi-person', 'route' => 'users.show', 'params' => [$me->id]],
        ],
        default => [
            ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'student.dashboard'],
            ['label' => 'Browse Classes', 'icon' => 'bi-search', 'route' => 'classes.discover'],
            ['label' => 'My Classes', 'icon' => 'bi-journal-bookmark', 'route' => 'student.classes'],
            ['label' => 'Feed', 'icon' => 'bi-chat-square-text', 'route' => 'feed.index'],
            ['label' => 'My Profile', 'icon' => 'bi-person', 'route' => 'users.show', 'params' => [$me->id]],
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
    <div class="offcanvas-header border-bottom d-lg-none">
        <h2 class="offcanvas-title h6 mb-0" id="appSidebarLabel">
            <i class="bi bi-mortarboard-fill text-edu me-1"></i> {{ __('Menu') }}
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-3">
        {{-- Main menu for the current role --}}
        <ul class="nav nav-pills flex-column gap-1">
            @foreach ($menu as $item)
                @php($active = $isActive($item))

                <li class="nav-item">
                    @if (Route::has($item['route']))
                        <a class="nav-link d-flex align-items-center {{ $active ? 'active' : '' }}"
                           href="{{ route($item['route'], $item['params'] ?? []) }}"
                           @if ($active) aria-current="page" @endif>
                            <i class="bi {{ $item['icon'] }} me-2"></i> {{ __($item['label']) }}
                        </a>
                    @else
                        <span class="nav-link disabled d-flex align-items-center justify-content-between">
                            <span><i class="bi {{ $item['icon'] }} me-2"></i> {{ __($item['label']) }}</span>
                            <span class="badge text-bg-secondary">{{ __('soon') }}</span>
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>

        {{-- Account links, pushed to the bottom of the column --}}
        <ul class="nav flex-column gap-1 mt-auto pt-3 border-top">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                   href="{{ route('profile.edit') }}">
                    <i class="bi bi-person-gear me-2"></i> {{ __('Edit profile') }}
                </a>
            </li>
            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-link text-danger w-100 text-start">
                        <i class="bi bi-box-arrow-right me-2"></i> {{ __('Log out') }}
                    </button>
                </form>
            </li>
        </ul>
    </div>
</aside>
```

---

## 5. Reusable components

### `resources/views/components/avatar.blade.php`

```php
{{-- Reusable avatar (FR-4, FR-5, FR-14).
     Usage:
       <x-avatar :user="$user" />
       <x-avatar :user="$user" size="lg" :link="route('users.show', $user)" />
     The uploaded photo is used when present, otherwise the default avatar. --}}
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
    $src = $user?->photoUrl() ?? asset('images/default-avatar.svg');
@endphp

@if ($link)
    <a href="{{ $link }}" class="d-inline-flex flex-shrink-0" aria-label="{{ $name }}">
        <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->class([$sizeClass]) }}>
    </a>
@else
    <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->class([$sizeClass]) }}>
@endif
```

### `resources/views/components/empty-state.blade.php`

```php
{{-- Reusable empty state.
     Usage:
       <x-empty-state icon="bi-easel" title="No classes yet">
           <a href="..." class="btn btn-primary">Create one</a>
       </x-empty-state> --}}
@props([
    'icon' => 'bi-inbox',
    'title' => null,
    'message' => null,
])

<div {{ $attributes->class(['card', 'border-0', 'shadow-sm']) }}>
    <div class="card-body text-center py-5">
        <i class="bi {{ $icon }} fs-1 text-muted d-block mb-3" aria-hidden="true"></i>

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

### `resources/views/components/status-badge.blade.php`

```php
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

### `resources/views/components/confirm-delete-modal.blade.php`

```php
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
    'triggerLabel' => 'Delete',
    'triggerClass' => 'btn btn-sm btn-outline-danger',
    'showTrigger' => true,
])

@if ($showTrigger)
    <button type="button" class="{{ $triggerClass }}" data-bs-toggle="modal" data-bs-target="#{{ $id }}">
        <i class="bi bi-trash me-1" aria-hidden="true"></i>{{ $triggerLabel }}
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
                    <button type="submit" class="btn btn-danger">
                        {{ $confirmLabel }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
```

### `resources/views/components/class-card.blade.php`

```php
{{-- A single class (ClassRoom) as a card.
     Usage: <x-class-card :class-room="$class" :url="route('classes.show', $class)"> ...buttons... </x-class-card>
     The optional $url turns the title into a link to the class detail page. --}}
@props(['classRoom', 'url' => null])

@php
    // Use the eager loaded count when the page provided one (avoids N+1).
    $studentsCount = $classRoom->students_count ?? $classRoom->students()->count();
@endphp

<div class="card border-0 shadow-sm h-100">
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <h3 class="h6 fw-bold mb-0">
                @if ($url)
                    {{-- Links to the class detail page (FR-19) --}}
                    <a href="{{ $url }}" class="text-decoration-none text-dark">{{ $classRoom->title }}</a>
                @else
                    {{ $classRoom->title }}
                @endif
            </h3>

            {{-- Status: scheduled / live / completed --}}
            <x-status-badge :status="$classRoom->status" class="flex-shrink-0" />
        </div>

        @if ($classRoom->subject)
            <div class="mb-2">
                <span class="badge bg-light text-dark border">
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

The existing `stat-card`, `post-card`, `flash`, `user-card`, `modal` and form components are reused unchanged apart from `class-card` above.

---

## 6. Dashboards

### `app/Http/Controllers/AdminDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin dashboard (FR-2, FR-26, FR-33 chart and moderation).
 * Shows the platform totals and a 12 month "new users" chart.
 */
class AdminDashboardController extends Controller
{
    /** How many months the chart covers. */
    private const CHART_MONTHS = 12;

    public function __invoke(): View
    {
        // One aggregate query for the three user counters. SUM(CASE ...) works
        // on MySQL and on the SQLite database used by the test suite.
        $userCounts = User::query()
            ->selectRaw(
                'COUNT(*) as users,
                 SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as teachers,
                 SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as students',
                [User::ROLE_TEACHER, User::ROLE_STUDENT]
            )
            ->first();

        $stats = [
            'users' => (int) ($userCounts->users ?? 0),
            'teachers' => (int) ($userCounts->teachers ?? 0),
            'students' => (int) ($userCounts->students ?? 0),
            'posts' => Post::count(),
            'classes' => ClassRoom::count(),
        ];

        return view('dashboards.admin', [
            'stats' => $stats,
            'chart' => $this->newUsersPerMonth(),
            // Newest accounts for the "recent users" table (FR-26).
            'recentUsers' => User::query()
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'email', 'role', 'photo', 'created_at']),
            // Moderation shortcuts (FR-33): pending counter + newest five.
            'pendingReports' => Report::query()->pending()->count(),
            'recentReports' => Report::query()
                ->pending()
                ->with(['reporter:id,name', 'reportable'])
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }

    /**
     * New registrations per month for the last 12 months.
     * Months without registrations are filled with 0 so the chart keeps a
     * continuous time axis.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function newUsersPerMonth(): array
    {
        // First month of the window: 11 months back, so today's month is included.
        $start = now()->startOfMonth()->subMonths(self::CHART_MONTHS - 1);

        // Grouping by month needs a date function, and MySQL and SQLite (used by
        // the test suite) have different names for it. Pick the right one.
        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $counts = User::query()
            ->where('created_at', '>=', $start)
            ->selectRaw("{$monthExpression} as month, COUNT(*) as total")
            ->groupByRaw($monthExpression)
            ->pluck('total', 'month');

        $labels = [];
        $values = [];

        for ($i = 0; $i < self::CHART_MONTHS; $i++) {
            $month = $start->copy()->addMonths($i);

            $labels[] = $month->format('M Y');
            // Missing months default to 0.
            $values[] = (int) ($counts[$month->format('Y-m')] ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'total' => array_sum($values),
        ];
    }
}
```

### `resources/views/dashboards/admin.blade.php`

```php
<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h1 class="h5 fw-semibold mb-0">
                <i class="bi bi-shield-lock me-1"></i> {{ __('Admin dashboard') }}
            </h1>
            <span class="badge text-bg-danger text-capitalize">{{ auth()->user()->role }}</span>
        </div>
    </x-slot>

    <div class="container">
        {{-- Platform totals (FR-7) --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Total users')" :value="$stats['users']" icon="bi-people" color="primary" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Teachers')" :value="$stats['teachers']" icon="bi-person-video3" color="success" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Students')" :value="$stats['students']" icon="bi-mortarboard" color="info" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Posts')" :value="$stats['posts']" icon="bi-chat-square-text" color="warning" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Classes')" :value="$stats['classes']" icon="bi-easel" color="danger" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                {{-- Moderation shortcut (FR-33). --}}
                <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}"
                   class="text-decoration-none">
                    <x-stat-card :label="__('Pending reports')" :value="$pendingReports" icon="bi-flag" color="danger" />
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                {{-- New registrations per month (FR-26) --}}
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0">{{ __('New users per month') }}</h2>
                            <div class="small text-muted">{{ __('The last 12 months') }}</div>
                        </div>
                        <span class="badge bg-light text-dark border">
                            {{ trans_choice(':count new user|:count new users', $chart['total'], ['count' => $chart['total']]) }}
                        </span>
                    </div>
                    <div class="card-body">
                        @php
                            // Chart.js configuration. app.js renders every canvas that
                            // carries a data-chart attribute, so no inline JS is needed.
                            $chartConfig = [
                                'type' => 'bar',
                                'data' => [
                                    'labels' => $chart['labels'],
                                    'datasets' => [[
                                        'label' => __('New users'),
                                        'data' => $chart['values'],
                                        'backgroundColor' => 'rgba(79, 70, 229, 0.65)',
                                        'borderColor' => '#4f46e5',
                                        'borderWidth' => 1,
                                        'borderRadius' => 6,
                                    ]],
                                ],
                                'options' => [
                                    'responsive' => true,
                                    'maintainAspectRatio' => false,
                                    'plugins' => ['legend' => ['display' => false]],
                                    'scales' => [
                                        'y' => [
                                            'beginAtZero' => true,
                                            // New users are whole people.
                                            'ticks' => ['precision' => 0],
                                        ],
                                    ],
                                ],
                            ];
                        @endphp

                        <div class="edu-chart">
                            {{-- Blade escapes the quotes as &quot;, the browser turns them
                                 back into real quotes before JSON.parse runs. --}}
                            <canvas data-chart="{{ json_encode($chartConfig) }}"
                                    aria-label="{{ __('New users per month') }}" role="img"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                {{-- Newest pending reports with a quick link (FR-33). --}}
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h2 class="h6 fw-bold mb-0">{{ __('Pending reports') }}</h2>
                        <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}"
                           class="small text-decoration-none">{{ __('See all') }}</a>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($recentReports as $report)
                            <a href="{{ route('admin.reports.show', $report) }}"
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="small">
                                        <span class="badge bg-light text-dark border">{{ $report->content_type }}</span>
                                        <div class="mt-1">{{ $report->content_preview }}</div>
                                        <div class="text-muted">
                                            {{ $report->reason_label }} &middot; {{ $report->created_at?->diffForHumans() }}
                                        </div>
                                    </div>
                                    <i class="bi bi-chevron-right text-muted"></i>
                                </div>
                            </a>
                        @empty
                            <div class="list-group-item text-center text-muted py-4 small">
                                <i class="bi bi-shield-check me-1"></i>
                                {{ __('Nothing to moderate right now.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3 mt-1">
            {{-- Newest accounts (FR-26) --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h2 class="h6 fw-bold mb-0">{{ __('Recent users') }}</h2>
                        <a href="{{ route('admin.users.index') }}" class="small text-decoration-none">
                            {{ __('Manage all users') }}
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">{{ __('User') }}</th>
                                    <th scope="col" class="d-none d-md-table-cell">{{ __('Email') }}</th>
                                    <th scope="col">{{ __('Role') }}</th>
                                    <th scope="col" class="text-end">{{ __('Joined') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentUsers as $user)
                                    @php
                                        $roleColor = match ($user->role) {
                                            \App\Models\User::ROLE_ADMIN => 'dark',
                                            \App\Models\User::ROLE_TEACHER => 'primary',
                                            default => 'success',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <x-avatar :user="$user" size="sm" />
                                                <a href="{{ route('users.show', $user) }}"
                                                   class="fw-semibold text-decoration-none text-dark">
                                                    {{ $user->name }}
                                                </a>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell text-muted small">{{ $user->email }}</td>
                                        <td>
                                            <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $user->role }}</span>
                                        </td>
                                        <td class="text-end text-muted small text-nowrap">
                                            {{ $user->created_at?->format('d M Y') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4 small">
                                            <i class="bi bi-people me-1"></i> {{ __('No users yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

### `app/Http/Controllers/TeacherDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Like;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Teacher dashboard (FR-2, FR-29).
 * Cards with the teacher's own numbers, a paginated list of their classes and
 * a short activity stream (likes, comments, enrollments and new followers).
 */
class TeacherDashboardController extends Controller
{
    /** How many activity entries the stream shows. */
    private const ACTIVITY_LIMIT = 10;

    /** Classes per page in the table. */
    private const CLASSES_PER_PAGE = 8;

    public function __invoke(Request $request): View
    {
        $teacher = $request->user();

        $mine = $teacher->classes();

        $stats = [
            'classes' => (clone $mine)->count(),
            'upcoming' => (clone $mine)->upcoming()->count(),
            // Distinct students across all of my classes (nobody counted twice).
            'students' => ClassEnrollment::query()
                ->whereIn('class_id', (clone $mine)->select('id'))
                ->distinct()
                ->count('student_id'),
            'followers' => $teacher->followers()->count(),
        ];

        // My classes, newest first, with the number of enrolled students.
        // withCount() keeps the table free of N+1 queries.
        $classes = $mine
            ->withCount('students')
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate(self::CLASSES_PER_PAGE)
            ->withQueryString();

        return view('dashboards.teacher', [
            'stats' => $stats,
            'classes' => $classes,
            'activity' => $this->recentActivity($teacher),
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

### `resources/views/dashboards/teacher.blade.php`

```php
<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h1 class="h5 fw-semibold mb-0">
                <i class="bi bi-easel me-1"></i> {{ __('Teacher dashboard') }}
            </h1>
            <span class="badge text-bg-primary text-capitalize">{{ auth()->user()->role }}</span>
        </div>
    </x-slot>

    <div class="container">
        {{-- My numbers (FR-22) --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('My classes')" :value="$stats['classes']" icon="bi-easel" color="primary" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Upcoming classes')" :value="$stats['upcoming']" icon="bi-calendar-check" color="success" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Enrolled students')" :value="$stats['students']" icon="bi-people" color="info" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Followers')" :value="$stats['followers']" icon="bi-person-hearts" color="warning" />
            </div>
        </div>

        <div class="row g-3">
            {{-- My classes, paginated (FR-18, FR-24) --}}
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h2 class="h6 fw-bold mb-0">{{ __('My classes') }}</h2>
                        <span class="small text-muted">{{ $classes->total() }}</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">{{ __('Title') }}</th>
                                    <th scope="col">{{ __('Date') }}</th>
                                    <th scope="col">{{ __('Status') }}</th>
                                    <th scope="col" class="text-end">{{ __('Enrolled') }}</th>
                                    <th scope="col" class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($classes as $class)
                                    <tr>
                                        <td>
                                            {{-- Every row links to the class detail page (FR-18) --}}
                                            <a href="{{ route('classes.show', $class) }}"
                                               class="fw-semibold text-decoration-none text-dark">{{ $class->title }}</a>
                                            @if ($class->subject)
                                                <div class="small text-muted">{{ $class->subject }}</div>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            {{ $class->date?->format('d M Y') }}
                                            <div class="small text-muted">
                                                {{ \Illuminate\Support\Str::substr((string) $class->time, 0, 5) }}
                                            </div>
                                        </td>
                                        <td>
                                            <x-status-badge :status="$class->status" />
                                        </td>
                                        <td class="text-end">
                                            {{-- students_count comes from withCount() --}}
                                            <a href="{{ route('classes.students', $class) }}"
                                               class="badge bg-light text-dark border text-decoration-none"
                                               title="{{ __('See the enrolled students') }}">
                                                {{ $class->students_count }}
                                            </a>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <div class="btn-group btn-group-sm" role="group"
                                                 aria-label="{{ __('Actions for :title', ['title' => $class->title]) }}">
                                                @if ($class->isLive())
                                                    <a href="{{ route('classes.room', $class) }}"
                                                       class="btn btn-outline-danger" title="{{ __('Join live') }}">
                                                        <i class="bi bi-camera-video"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('classes.students', $class) }}"
                                                   class="btn btn-outline-secondary" title="{{ __('Students') }}">
                                                    <i class="bi bi-people"></i>
                                                </a>
                                                <a href="{{ route('classes.edit', $class) }}"
                                                   class="btn btn-outline-secondary" title="{{ __('Edit') }}">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox me-1"></i> {{ __('You have not created a class yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="card-body pt-0">
                        {{-- Bootstrap 5 pagination --}}
                        <x-pagination-links :paginator="$classes" />
                    </div>
                </div>
            </div>

            {{-- Latest likes, comments and new followers (FR-25) --}}
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h2 class="h6 fw-bold mb-0">{{ __('Recent activity') }}</h2>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($activity as $item)
                            <div class="list-group-item d-flex gap-3">
                                <i class="bi {{ $item['icon'] }} text-{{ $item['color'] }} fs-5"></i>
                                <div class="small">
                                    {{-- Text is escaped; the excerpt is optional --}}
                                    <div>{{ $item['text'] }}</div>
                                    @if ($item['excerpt'])
                                        <div class="text-muted">{{ \Illuminate\Support\Str::limit($item['excerpt'], 60) }}</div>
                                    @endif
                                    <div class="text-muted">{{ $item['at']?->diffForHumans() }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4 small">
                                <i class="bi bi-activity me-1"></i> {{ __('Nothing has happened yet.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

### `app/Http/Controllers/StudentDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Student dashboard (FR-2, FR-30).
 * Cards with the student's numbers, the upcoming enrolled classes with a Join
 * button and a list of teachers the student does not follow yet.
 */
class StudentDashboardController extends Controller
{
    /** How many upcoming classes the list shows. */
    private const UPCOMING_LIMIT = 6;

    /** How many teacher suggestions the list shows. */
    private const RECOMMENDATION_LIMIT = 3;

    public function __invoke(Request $request): View
    {
        $student = $request->user();

        $stats = [
            'classes' => $student->enrolledClasses()->count(),
            'upcoming' => $student->enrolledClasses()->upcoming()->count(),
            // Only teachers count, so the card matches what the page offers.
            'following' => $student->following()->where('role', User::ROLE_TEACHER)->count(),
        ];

        // Upcoming classes I am enrolled in, soonest first.
        // Eager loading the teacher and counting the students avoids N+1.
        $upcomingClasses = $student->enrolledClasses()
            ->upcoming()
            ->with('teacher:id,name,subject')
            ->withCount('students')
            ->limit(self::UPCOMING_LIMIT)
            ->get();

        return view('dashboards.student', [
            'stats' => $stats,
            'upcomingClasses' => $upcomingClasses,
            'recommendedTeachers' => $this->recommendedTeachers($student),
        ]);
    }

    /**
     * Teachers that this student does not follow yet, most followed first.
     *
     * @return Collection<int, User>
     */
    private function recommendedTeachers(User $student): Collection
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
}
```

### `resources/views/dashboards/student.blade.php`

```php
<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h1 class="h5 fw-semibold mb-0">
                <i class="bi bi-mortarboard me-1"></i> {{ __('Student dashboard') }}
            </h1>
            <span class="badge text-bg-success text-capitalize">{{ auth()->user()->role }}</span>
        </div>
    </x-slot>

    <div class="container">
        {{-- My numbers (FR-21) --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <x-stat-card :label="__('Enrolled classes')" :value="$stats['classes']" icon="bi-journal-bookmark" color="primary" />
            </div>
            <div class="col-12 col-md-4">
                <x-stat-card :label="__('Upcoming classes')" :value="$stats['upcoming']" icon="bi-calendar-check" color="success" />
            </div>
            <div class="col-12 col-md-4">
                <x-stat-card :label="__('Teachers followed')" :value="$stats['following']" icon="bi-person-hearts" color="info" />
            </div>
        </div>

        <div class="row g-3">
            {{-- Classes I am enrolled in and that are still ahead (FR-20) --}}
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h2 class="h6 fw-bold mb-0">{{ __('My upcoming classes') }}</h2>
                        <span class="small text-muted">{{ $upcomingClasses->count() }}</span>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            @forelse ($upcomingClasses as $class)
                                <div class="col-12 col-md-6">
                                    <x-class-card :class-room="$class" :url="route('classes.show', $class)">
                                        {{-- A student may only enter a running class (FR-20).
                                             The real join/attendance logic arrives in Phase 5;
                                             until then the button is visibly disabled. --}}
                                        @if ($class->isLive())
                                            <a href="{{ route('classes.room', $class) }}" class="btn btn-sm btn-danger">
                                                <i class="bi bi-camera-video me-1"></i> {{ __('Join live') }}
                                            </a>
                                        @else
                                            <button type="button" class="btn btn-sm btn-primary" disabled
                                                    aria-disabled="true"
                                                    title="{{ __('The Join button opens when the teacher starts the class.') }}">
                                                <i class="bi bi-camera-video me-1"></i> {{ __('Join') }}
                                            </button>
                                            <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye me-1"></i> {{ __('Details') }}
                                            </a>
                                            <span class="badge text-bg-light border align-self-center">
                                                {{ __('Join opens when the teacher starts it') }}
                                            </span>
                                        @endif
                                    </x-class-card>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted py-4">
                                    <i class="bi bi-calendar-x me-1"></i>
                                    {{ __('You have no upcoming class. Browse the classes to join one.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Teachers I do not follow yet (FR-11, FR-30) --}}
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h2 class="h6 fw-bold mb-0">{{ __('Recommended teachers') }}</h2>
                    </div>

                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            @forelse ($recommendedTeachers as $teacher)
                                <x-user-card :user="$teacher">
                                    {{-- Follow button: posts to the follow route --}}
                                    <form method="POST" action="{{ route('follows.store', $teacher) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-person-plus me-1"></i> {{ __('Follow') }}
                                        </button>
                                    </form>
                                </x-user-card>
                            @empty
                                <div class="text-center text-muted py-4 small">
                                    <i class="bi bi-check2-circle me-1"></i>
                                    {{ __('You already follow every teacher.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

---

## 7. Profile and public profile

### `app/Http/Requests/ProfileUpdateRequest.php`

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

### `app/Http/Controllers/ProfileController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
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

            $user->photo = $request->file('photo')->store(
                config('educonnect.uploads.profile_photo_dir'),
                'public'
            );

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
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

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

### `resources/views/profile/edit.blade.php`

```php
<x-app-layout>
    <x-slot name="title">{{ __('Profile settings') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Profile settings') }}</li>
    </x-slot>

    <x-slot name="header">
        <div>
            <h1 class="h5 fw-semibold mb-0">{{ __('Profile settings') }}</h1>
            <p class="text-muted small mb-0">{{ __('Manage your photo, personal details and account security.') }}</p>
        </div>
    </x-slot>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

### `resources/views/profile/partials/update-profile-information-form.blade.php`

```php
<section>
    <h3 class="h6 fw-bold mb-1">{{ __('Profile information') }}</h3>
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
            <div class="form-text">{{ __('Max 1000 characters.') }}</div>
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

### `app/Http/Controllers/UserProfileController.php`

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
        // Counts for the header: posts, followers, following.
        $user->loadCount(['posts', 'followers', 'following']);

        $posts = $user->posts()
            ->visible()
            ->withFeedData($request->user())
            ->latest()
            ->paginate(PostController::PER_PAGE)
            ->withQueryString();

        return view('users.show', [
            'profile' => $user,
            'posts' => $posts,
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

### `resources/views/users/show.blade.php`

```php
@php
    // Badge colour for the role.
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
        <li class="breadcrumb-item active" aria-current="page">{{ $isMe ? __('My profile') : $profile->name }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h1 class="h5 fw-semibold mb-0">
                <i class="bi bi-person me-1"></i> {{ $isMe ? __('My profile') : $profile->name }}
            </h1>
            @if ($isMe)
                <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil-square me-1"></i> {{ __('Edit profile') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                {{-- Profile header (FR-4, FR-5, FR-14) --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body d-flex flex-column flex-sm-row gap-3">
                        <x-avatar :user="$profile" size="lg" class="flex-shrink-0" />

                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <h2 class="h5 fw-bold mb-0">{{ $profile->name }}</h2>
                                <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $profile->role }}</span>
                            </div>

                            @if ($profile->subject)
                                <div class="text-muted small">
                                    <i class="bi bi-book me-1"></i>{{ $profile->subject }}
                                </div>
                            @endif

                            @if ($profile->bio)
                                {{-- Escaped by Blade. --}}
                                <p class="mt-2 mb-2">{{ $profile->bio }}</p>
                            @endif

                            <div class="d-flex flex-wrap gap-4 small">
                                <span><strong>{{ $profile->posts_count }}</strong> {{ __('posts') }}</span>
                                <a href="{{ route('users.followers', $profile) }}" class="text-decoration-none">
                                    <strong data-followers-count>{{ $profile->followers_count }}</strong> {{ __('followers') }}
                                </a>
                                <a href="{{ route('users.following', $profile) }}" class="text-decoration-none">
                                    <strong>{{ $profile->following_count }}</strong> {{ __('following') }}
                                </a>
                            </div>
                        </div>

                        {{-- Follow button (FR-11: members can follow each other) --}}
                        <div class="align-self-sm-start">
                            @include('users._follow-button', ['user' => $profile, 'followingIds' => $followingIds])
                        </div>
                    </div>
                </div>

                {{-- The user's posts (FR-5, FR-14) --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 fw-bold mb-0">
                        <i class="bi bi-chat-square-text me-1"></i> {{ __('Posts') }}
                    </h2>
                    @if ($isMe)
                        <a href="{{ route('posts.create') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i> {{ __('New post') }}
                        </a>
                    @endif
                </div>

                @include('feed._posts', ['posts' => $posts])

                <div class="mt-3">
                    <x-pagination-links :paginator="$posts" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

---

## 8. Classes index (shared components)

### `resources/views/classes/index.blade.php`

```php
<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h1 class="h5 fw-semibold mb-0">
                <i class="bi bi-easel me-1"></i> {{ __('My classes') }}
            </h1>
            <a href="{{ route('classes.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-square me-1"></i> {{ __('New class') }}
            </a>
        </div>
    </x-slot>

    <div class="container">
        @if ($classes->isEmpty())
            {{-- Empty state --}}
            <x-empty-state icon="bi-easel"
                           :title="__('No classes yet')"
                           :message="__('You have not created a class yet.')">
                <a href="{{ route('classes.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-square me-1"></i> {{ __('Create my first class') }}
                </a>
            </x-empty-state>
        @else
            <div class="row g-3">
                @foreach ($classes as $class)
                    <div class="col-12 col-md-6 col-xl-4">
                        <x-class-card :class-room="$class" :url="route('classes.show', $class)">
                            {{-- Open the class detail page --}}
                            <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('Open') }}
                            </a>

                            {{-- Enrolled students (FR-24) --}}
                            <a href="{{ route('classes.students', $class) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-people me-1"></i> {{ __('Students') }}
                            </a>

                            {{-- Status control: only the transitions the model allows
                                 are offered as buttons (FR-24). --}}
                            @foreach ($class->allowedTransitions() as $next)
                                <form method="POST" action="{{ route('classes.status', $class) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $next }}">
                                    <button type="submit"
                                            class="btn btn-sm {{ $next === \App\Models\ClassRoom::STATUS_LIVE ? 'btn-success' : 'btn-outline-danger' }}">
                                        @if ($next === \App\Models\ClassRoom::STATUS_LIVE)
                                            <i class="bi bi-broadcast me-1"></i> {{ __('Start class') }}
                                        @elseif ($next === \App\Models\ClassRoom::STATUS_COMPLETED)
                                            <i class="bi bi-stop-circle me-1"></i> {{ __('End class') }}
                                        @else
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('Back to scheduled') }}
                                        @endif
                                    </button>
                                </form>
                            @endforeach

                            {{-- Edit / delete (ClassPolicy: my own classes only) --}}
                            <a href="{{ route('classes.edit', $class) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
                            </a>

                            <x-confirm-delete-modal
                                :id="'delete-class-'.$class->id"
                                :action="route('classes.destroy', $class)"
                                :title="__('Delete this class?')"
                                :message="__('The enrollments are removed as well. This action cannot be undone.')" />
                        </x-class-card>
                    </div>
                @endforeach
            </div>

            <x-pagination-links :paginator="$classes" />
        @endif
    </div>
</x-app-layout>
```

---

## 9. Routes

### `routes/web.php`

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
    });

// Follow / unfollow another member from a profile or the search page
// (FR-11). Students and teachers can follow each other; admin accounts
// are not followable and admins do not use the follow routes.
// Throttled so the button cannot be used to hammer the database.
Route::middleware(['auth', 'not.blocked', 'role:student,teacher'])
    ->group(function () {
        Route::post('/u/{user}/follow', [FollowController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('follows.store');

        Route::delete('/u/{user}/follow', [FollowController::class, 'destroy'])
            ->middleware('throttle:30,1')
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
        ->middleware('throttle:10,1')
        ->name('posts.store');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::patch('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    // Likes - small JSON endpoints for the fetch() calls in the feed (FR-11).
    Route::post('/posts/{post}/like', [LikeController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('posts.like');
    Route::delete('/posts/{post}/like', [LikeController::class, 'destroy'])
        ->middleware('throttle:60,1')
        ->name('posts.unlike');

    // Comments (FR-13).
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('comments.destroy');

    // Reporting a post or comment (FR-32). Throttled so the queue cannot be
    // flooded by one account.
    Route::post('/posts/{post}/reports', [ReportController::class, 'storePost'])
        ->middleware('throttle:10,1')
        ->name('reports.posts.store');
    Route::post('/comments/{comment}/reports', [ReportController::class, 'storeComment'])
        ->middleware('throttle:10,1')
        ->name('reports.comments.store');

    // Hashtag pages (FR-15). The binding uses the (lower case) name column.
    Route::get('/hashtag/{hashtag:name}', [HashtagController::class, 'show'])->name('hashtags.show');

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
| The bell in the navbar polls /notifications/poll every 45 seconds and gets
| the fresh unread counter plus the newest five entries as JSON.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Lightweight JSON endpoint for the polling script (FR-24).
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])
        ->middleware('throttle:60,1')
        ->name('notifications.poll');

    // Mark everything as read (FR-25). Declared before the {notification} routes.
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // One notification: mark it read, or mark it read and open its page (FR-25).
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
});

/*
|--------------------------------------------------------------------------
| Search (FR-14)
|--------------------------------------------------------------------------
| One box in the navbar, two tabs on the page: people and hashtags.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
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

---

## 10. JavaScript and styles

### `resources/js/app.js`

```javascript
import './bootstrap'; // axios setup (window.axios)

// Bootstrap 5 JavaScript (dropdowns, modals, collapses...) + Popper
import * as bootstrap from 'bootstrap';

import Alpine from 'alpinejs';

// Layout helpers: remember the desktop sidebar state (FR-31).
import './layout';

// Profile photo preview and "remove current photo" (FR-4).
import './profile-photo';

// Authentication helpers: password visibility + teacher subject field (FR-1 ... FR-3).
import './auth';

// Feed interactions: likes, comments, follows and "Load more" (FR-11, FR-13, FR-17).
import './feed';

// Live class room: embeds Jitsi Meet through the External API (FR-18, FR-22).
import './jitsi';

// Notification bell: unread badge + newest five, refreshed by polling (FR-24).
import './notifications';

// Chart.js 4 with every controller/scale registered ("/auto").
// Used by the admin dashboard and later by the admin analytics page (FR-31).
import Chart from 'chart.js/auto';

// Make them available to plain <script> tags inside Blade views
window.bootstrap = bootstrap;
window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

/**
 * Turn every <canvas data-chart='{...}'> into a real chart.
 * Blade views only have to print the Chart.js configuration as JSON,
 * which keeps the JavaScript out of the markup (no inline scripts).
 */
function renderCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        try {
            // The config travels as JSON in the data-chart attribute.
            new Chart(canvas, JSON.parse(canvas.dataset.chart));
        } catch (error) {
            console.error('Could not build the chart on', canvas, error);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderCharts);
} else {
    renderCharts();
}
```

### `resources/js/layout.js`

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

### `resources/js/profile-photo.js`

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

### `resources/sass/app.scss`

```scss
// -----------------------------------------------------------------------------
// EduConnect stylesheet
// Bootstrap 5 and Bootstrap Icons are pulled straight from node_modules and
// compiled by Vite (no Tailwind anywhere in this project).
// -----------------------------------------------------------------------------

@import 'bootstrap/scss/bootstrap';
@import 'bootstrap-icons/font/bootstrap-icons.css';

// --- EduConnect theme colours -------------------------------------------------
:root {
    --edu-primary: #4f46e5;
    --edu-primary-dark: #3730a3;
    --edu-bg: #f6f7fb;
}

body {
    background-color: var(--edu-bg);
}

// Use our brand colour for every primary button
.btn-primary {
    --bs-btn-bg: var(--edu-primary);
    --bs-btn-border-color: var(--edu-primary);
    --bs-btn-hover-bg: var(--edu-primary-dark);
    --bs-btn-hover-border-color: var(--edu-primary-dark);
    --bs-btn-active-bg: var(--edu-primary-dark);
    --bs-btn-active-border-color: var(--edu-primary-dark);
}

.text-edu {
    color: var(--edu-primary) !important;
}

// Soft, clean cards
.card {
    border-radius: 0.75rem;
}

// Small round avatar used in the navbar and on the profile page
.avatar {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 50%;
}

.avatar-sm {
    width: 32px;
    height: 32px;
    object-fit: cover;
    border-radius: 50%;
}

.avatar-lg {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 50%;
}

.avatar-xl {
    width: 128px;
    height: 128px;
    object-fit: cover;
    border-radius: 50%;
}

// Used by the post feed later on
.post-body {
    white-space: pre-wrap;
    word-break: break-word;
}

// --- Layout shell: sidebar + content -----------------------------------------
// The sidebar is a normal column from lg upwards and an offcanvas drawer on
// phones (Bootstrap 5.3 "offcanvas-lg" does the switch for us).
.edu-sidebar {
    width: 260px;
    background-color: #fff;
    border-right: 1px solid var(--bs-border-color);
}

.edu-sidebar .nav-link {
    border-radius: 0.5rem;
    padding: 0.55rem 0.75rem;
}

.edu-sidebar .nav-link.active {
    background-color: rgba(79, 70, 229, 0.1);
    color: var(--edu-primary);
    font-weight: 600;
}

// Desktop toggle (FR-31): the state lives on <html> so it can be restored
// before the first paint. On phones the offcanvas drawer is always used.
@media (min-width: 992px) {
    html.edu-sidebar-collapsed .edu-sidebar {
        display: none;
    }
}

// Profile photo preview (FR-4)
.profile-photo-preview {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 50%;
    border: 1px solid var(--bs-border-color);
    background-color: var(--bs-body-bg);
}

// Breadcrumbs sit directly above the page title.
.breadcrumb {
    --bs-breadcrumb-divider-color: var(--bs-secondary-color);
}

// Charts need a fixed height, otherwise Chart.js keeps growing the canvas.
.edu-chart {
    position: relative;
    height: 320px;
}

// Small square icon chip used by the stat cards
.stat-icon {
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    border-radius: 0.75rem;
}

// Alpine.js helpers: hide elements until Alpine has taken over
[x-cloak] {
    display: none !important;
}

// --- Live class room (FR-18) --------------------------------------------------
// Jitsi builds its iframe inside .jitsi-frame, so this element needs a real
// height. It fills the screen minus the navbar, the page header and the footer.
.jitsi-frame {
    position: relative;
    width: 100%;
    height: calc(100vh - 340px);
    min-height: 420px;
    border-radius: 0.75rem;
    overflow: hidden;
    background-color: #1c1c1c;

    // Jitsi writes inline width/height on the iframe; make it fill the box.
    iframe {
        width: 100% !important;
        height: 100% !important;
        border: 0;
    }
}

// On a phone the room gets the whole width and as much height as possible.
@media (max-width: 991.98px) {
    .jitsi-frame {
        height: calc(100vh - 260px);
        min-height: 360px;
        border-radius: 0.5rem;
    }
}

// Touch friendly: every control is at least 44px tall on phones
@media (max-width: 575.98px) {
    .btn,
    .form-control,
    .form-select {
        min-height: 44px;
    }
}

// --- Notifications and search (FR-14, FR-24, FR-25) --------------------------
// The search box in the navbar.
.edu-search {
    width: 260px;
}

@media (min-width: 992px) {
    .edu-search {
        width: 320px;
    }
}

// The bell dropdown holds up to five entries, so it is a bit wider than default.
.notification-menu {
    width: min(360px, 92vw);
    max-height: 70vh;
    overflow-y: auto;
}

// Unread entries stand out with a soft brand tint and a left border.
.notification-unread {
    background-color: rgba(79, 70, 229, 0.06);
    border-left: 3px solid var(--edu-primary);
}

// Small timestamp line inside a notification entry.
.edu-time {
    font-size: 0.75rem;
}

// --- Authentication split screen (FR-1, FR-2, FR-3) --------------------------
// A branded indigo panel on the left and the form on the right. On phones the
// brand panel is replaced by a compact logo above the card.
.edu-auth-body {
    background-color: #fff;
}

.edu-auth-brand {
    position: relative;
    overflow: hidden;
    color: #fff;
    background: linear-gradient(145deg, #312e81 0%, #4338ca 45%, #4f46e5 100%);
}

// Soft light circles keep the panel from looking flat.
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

// The password toggle should not take the full input-group height on desktop.
.password-toggle {
    min-width: 48px;
}

@media (max-width: 991.98px) {
    .edu-auth-body {
        background-color: var(--edu-bg);
    }
}
```

---

## 11. Tests

### `tests/Feature/PhaseThreeUiTest.php`

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

---

## 12. Requirement checklist

### FR-4 - Edit profile: name, photo, bio, subject (teachers)

- [x] `ProfileUpdateRequest` validates `name` (required, max 255), `email`
      (required, unique ignoring self), `bio` (max 1000), `subject` (max 255),
      `photo` (image, `jpg/jpeg/png/webp`, max 2048 KB) and `remove_photo`
      (boolean).
- [x] `ProfileController@update` stores the file on the `public` disk under
      `profiles/`, writes the path to `users.photo`, deletes the replaced file,
      and deletes + nulls the photo when `remove_photo` is checked.
- [x] Changing the email resets `email_verified_at`.
- [x] The form is `multipart/form-data`, shows a live preview from
      `profile-photo.js`, and a "Remove current photo" checkbox when a photo
      exists.
- [x] The teacher "subject" field is only shown to teachers (existing `auth.js`).
- [x] The user is redirected to `profile.edit` with a success flash.
- [x] Tests: upload stores the file, remove deletes it, PDF and >2 MB files are
      rejected, preview/remove controls render. Default avatar is used when
      `photo` is null.

### FR-5 - Public profile with posts, followers and following count

- [x] `GET /users/{user}` (`users.show`) loads post/follower/following counts,
      paginates visible posts newest first, and shows the follow button.
- [x] `GET /users/{user}/followers` and `/following` list people with pagination.
- [x] Legacy `/u/{user}` URLs 301/302-redirect to the canonical `/users` URL.
- [x] Admin accounts are not followable; nobody can follow themselves.
- [x] Tests: profile page shows posts + counts, `/u/...` redirects, follow and
      unfollow still work.

### FR-26 - Admin dashboard totals

- [x] Five totals: users, teachers, students, posts, classes.
- [x] The three user counters come from one aggregate query
      (`COUNT(*)`, `SUM(CASE WHEN role = ? ...)`), valid on MySQL and SQLite.
- [x] Zero-filled 12-month new-user chart (existing `newUsersPerMonth()`).
- [x] Newest five users in a responsive table with avatar, email, role, date
      and a link to the public profile.
- [x] Pending-report counter and newest five reports remain available.
- [x] Tests: totals, chart, recent-users table (capped at five).

### FR-29 - Teacher dashboard

- [x] Cards: my classes, upcoming classes, distinct enrolled students, followers.
- [x] Paginated class table with date, time, status badge, enrolled count and
      an actions group (Join when live, Students, Edit).
- [x] Activity stream merges likes, comments on my posts, new enrollments and
      new followers, newest first, capped at 10.
- [x] All queries are scoped to the logged-in teacher and eager-load relations.
- [x] Tests: actions links render, new enrollment appears in the stream.

### FR-30 - Student dashboard

- [x] Cards: enrolled classes, upcoming classes, followed teachers.
- [x] Upcoming enrolled classes show teacher, subject, date, time, duration and
      enrolled count.
- [x] The Join button is a disabled `<button>` with `aria-disabled="true"` and a
      hint until `status === 'live'`; a live class gets a real
      `classes.room` link.
- [x] Teacher recommendations exclude teachers the student already follows.
- [x] Tests: disabled Join markup; live classes link to the room.

### FR-31 - Layout, navigation and responsiveness

- [x] Navbar: brand, search (desktop) / search icon (phone), notification bell
      with badge, avatar user dropdown (profile, edit profile, feed,
      notifications, logout).
- [x] Sidebar: role-based links, active state with `aria-current="page"`,
      disabled "soon" entries for missing routes, account links at the bottom.
- [x] Desktop collapse/expand button; state stored in
      `localStorage['educonnect.sidebar.collapsed']` and applied in `<head>`
      before first paint to avoid layout shift.
- [x] Mobile: `offcanvas-lg offcanvas-start` drawer opened by the hamburger,
      closed by the header close button or backdrop.
- [x] `@stack('scripts')`, page `title` slot, breadcrumbs slot, skip link,
      visible focus styles, semantic landmarks.
- [x] Flash success/error/status banners and validation summary.
- [x] Custom 403, 404, 500 pages.
- [x] Tests: toggle + storage key + stack, per-role links, active state,
      title/breadcrumbs.

---

## 13. Responsive checklist

Verified by markup/CSS review (no headless browser is installed here):

| Width | Expected result | Status |
|---|---|---|
| 360 px | Sidebar hidden behind hamburger; search is an icon; cards stack in one column; tables scroll inside `.table-responsive`; navbar wraps without overflow; Join buttons full width; profile photo preview stays 96 px | Pass by markup |
| 768 px | Search box visible; dashboard cards 2-up; class cards 2-up; offcanvas still used (Bootstrap `lg` starts at 992 px); tables keep one hidden column on `md` | Pass by markup |
| 1200 px | Sidebar is a visible column; collapse toggle hides it; dashboard cards 4-5-up (`col-xl`); class cards 3-up; content stays inside `.container` | Pass by markup |

Manual browser spot-check is still recommended on the three widths because
automated viewport testing needs Playwright/Puppeteer, which is not installed.

---

## 14. How to run

```bash
cd C:/Users/HomePC/Documents/Trae/default/educonnect

# 1. Install / refresh PHP and JS dependencies
composer install
npm install

# 2. Build the assets
npm run build

# 3. Fresh database + demo content (SQLite is the local default)
php artisan migrate:fresh --seed

# 4. Serve
php artisan serve --host=127.0.0.1 --port=8010
```

Open <http://127.0.0.1:8010>.

Demo accounts created by the seeders:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@educonnect.test` | `EduConnect#Admin2026!` |
| Teacher | any seeded teacher email | `password` |
| Student | any seeded student email | `password` |

Run the suite:

```bash
php artisan test
php artisan test --filter=PhaseThreeUiTest
./vendor/bin/pint --test
npm run build
```

---

## 15. Known limits and disclosures

1. **Framework version** - Laravel 12.70.2 instead of Laravel 11. No
   Laravel-12-only feature is used.
2. **Database** - the suite ran on SQLite. MySQL 8 is not installed, so the
   MySQL migration path and `mysqldump` import were not executed here.
3. **Browser viewports** - 360/768/1200 px were checked against the markup and
   Bootstrap grid, not in a real browser. Playwright/Puppeteer is not installed.
4. **Join/attendance** - the disabled Join button is Phase 3 UI state. Real
   attendance tracking is scheduled for a later phase; live classes already
   open the room.
5. **Non-functional targets** - 3-second load, 99% availability, real SMTP
   delivery and a production database-queue worker were not measured.
6. **Version control** - this repository has no Git history; all Phase 1-3
   changes are uncommitted.
7. **`post-card`** - kept at its full working implementation instead of a
   placeholder, because the feed already depends on it in this build.

---

## 16. Done when

Phase 3 is done when all of the following are true:

- [x] A logged-in user sees the navbar, the role sidebar, footer and flash
      area on every page.
- [x] On a phone the sidebar opens as a drawer from the hamburger; on desktop
      the collapse button hides/shows it and the choice survives a reload.
- [x] Admin, teacher and student dashboards are reachable only by their own
      role and show the numbers described in FR-26, FR-29 and FR-30.
- [x] The admin dashboard lists the five newest users and the 12-month chart.
- [x] The teacher class table has working Students and Edit actions and shows
      new enrollments in the activity stream.
- [x] The student Join button is disabled until a class is live, then opens
      `/classes/{class}/room`.
- [x] A user can edit name, bio, teacher subject and photo, preview the photo
      before saving and remove it again; type and size are validated.
- [x] `/users/{user}` shows posts and follower/following counts; `/u/{user}`
      redirects there.
- [x] 403, 404 and 500 pages render inside the guest layout with a way back.
- [x] `php artisan test` passes: **193 tests, 849 assertions**, including the
      12 Phase 3 tests.
- [x] `npm run build` completes and `pint --test` passes.
- [ ] Manual browser check at 360 / 768 / 1200 px on this machine (recommended,
      not blocking).

**Next:** Phase 4. Phase 3 is frozen unless a regression is reported.
