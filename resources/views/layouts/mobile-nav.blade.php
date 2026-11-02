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
            // The bottom bar is only ~64px wide per item on a 320px phone,
            // so it shows the short label and keeps the full one for AT.
            'short' => __('Alerts'),
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
            <span class="edu-mobile-nav-label" aria-hidden="true">{{ $item['short'] ?? $item['label'] }}</span>
            <span class="visually-hidden">{{ $item['label'] }}</span>

            @if (! empty($item['badge']))
                <span class="badge rounded-pill text-bg-danger edu-mobile-nav-badge">
                    {{ $item['badge'] > 9 ? '9+' : $item['badge'] }}
                    <span class="visually-hidden">{{ __('unread notifications') }}</span>
                </span>
            @endif
        </a>
    @endforeach
</nav>
