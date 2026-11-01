<x-app-layout>
    <x-slot name="title">{{ __('Notifications') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Notifications') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-bell me-1 text-edu" aria-hidden="true"></i>{{ __('Notifications') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Likes, comments, new followers and class reminders.') }}</p>
            </div>

            @if ($notifications->total() > 0)
                {{-- FR-25: mark everything as read in one click. --}}
                <form method="POST" action="{{ route('notifications.read-all') }}" data-notification-read-all>
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-check2-all me-1" aria-hidden="true"></i> {{ __('Mark all as read') }}
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                {{-- All / unread filter, carried through the pagination links (FR-25). --}}
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <a class="edu-filter-chip {{ $filter === 'all' ? 'is-active' : '' }}"
                       href="{{ route('notifications.index') }}"
                       @if ($filter === 'all') aria-current="page" @endif>
                        <i class="bi bi-inbox" aria-hidden="true"></i> {{ __('All') }}
                    </a>
                    <a class="edu-filter-chip {{ $filter === 'unread' ? 'is-active' : '' }}"
                       href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                       @if ($filter === 'unread') aria-current="page" @endif>
                        <i class="bi bi-dot" aria-hidden="true"></i> {{ __('Unread') }}
                    </a>

                    <span class="ms-auto small text-muted">
                        {{ trans_choice(':count notification|:count notifications', $notifications->total(), ['count' => $notifications->total()]) }}
                    </span>
                </div>

                @if ($notifications->isEmpty())
                    <x-empty-state icon="bi-bell-slash"
                                   :title="$filter === 'unread' ? __('Nothing unread') : __('No notifications yet')"
                                   :message="$filter === 'unread'
                                        ? __('You are all caught up. New activity will appear here.')
                                        : __('Follow a teacher or write a post and the activity shows up here.')">
                        @if ($filter === 'unread')
                            <a href="{{ route('notifications.index') }}" class="btn btn-primary">{{ __('See all notifications') }}</a>
                        @else
                            <a href="{{ route('feed.index') }}" class="btn btn-primary">{{ __('Go to the feed') }}</a>
                        @endif
                    </x-empty-state>
                @else
                    @php
                        // Group without losing the paginator: Today first, then everything older.
                        $groups = $notifications->getCollection()->groupBy(
                            static fn ($notification) => $notification->created_at?->isToday() ? 'today' : 'earlier'
                        );
                    @endphp

                    @foreach (['today' => __('Today'), 'earlier' => __('Earlier')] as $key => $label)
                        @if ($groups->has($key))
                            <h2 class="edu-notif-group-label" id="notif-group-{{ $key }}">{{ $label }}</h2>

                            <div class="list-group list-group-flush border rounded-3 overflow-hidden mb-3"
                                 aria-labelledby="notif-group-{{ $key }}">
                                @foreach ($groups->get($key) as $notification)
                                    @php $data = $notification->data; @endphp

                                    <div class="list-group-item d-flex gap-3 align-items-start {{ $notification->read_at ? '' : 'notification-unread' }}">
                                        <span class="edu-notif-icon flex-shrink-0" aria-hidden="true">
                                            <i class="bi {{ $data['icon'] ?? 'bi-bell' }}"></i>
                                        </span>

                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-start gap-2">
                                                @unless ($notification->read_at)
                                                    <span class="edu-notif-dot mt-2" data-notification-unread-dot aria-hidden="true"></span>
                                                    <span class="visually-hidden" data-notification-unread-label>{{ __('Unread') }}</span>
                                                @endunless

                                                {{-- Opening the entry marks it read and follows its link (FR-25). --}}
                                                <a href="{{ route('notifications.open', $notification->id) }}"
                                                   class="text-decoration-none text-reset fw-semibold text-break">
                                                    {{ $data['message'] ?? __('Notification') }}
                                                </a>
                                            </div>

                                            @isset($data['excerpt'])
                                                <div class="small text-muted text-break">&ldquo;{{ $data['excerpt'] }}&rdquo;</div>
                                            @endisset

                                            <div class="small text-muted">
                                                <time datetime="{{ $notification->created_at?->toIso8601String() }}">
                                                    {{ $notification->created_at?->diffForHumans() }}
                                                </time>

                                                @unless ($notification->read_at)
                                                    <span class="mx-1" aria-hidden="true">·</span>
                                                    <span class="text-edu fw-semibold" data-notification-unread-label>{{ __('Unread') }}</span>
                                                @endunless
                                            </div>
                                        </div>

                                        <div class="d-flex gap-1 flex-shrink-0">
                                            @unless ($notification->read_at)
                                                {{-- FR-25: mark this single entry as read. Works as a
                                                     normal form, resources/js/notifications.js turns it
                                                     into a fetch() call when JavaScript is available. --}}
                                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}"
                                                      data-notification-read>
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                            title="{{ __('Mark as read') }}"
                                                            aria-label="{{ __('Mark as read') }}">
                                                        <i class="bi bi-check2" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            @endunless

                                            {{-- FR-25: remove the notification for good. --}}
                                            <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}"
                                                  data-notification-delete
                                                  data-confirm="{{ __('Delete this notification?') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="{{ __('Delete') }}"
                                                        aria-label="{{ __('Delete') }}">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                    <x-pagination-links :paginator="$notifications" />
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
