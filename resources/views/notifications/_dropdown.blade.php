{{--
    The newest notifications as Bootstrap dropdown items (FR-24).
    Included by the navbar bell on page load and re-rendered by
    /notifications/poll every 30 seconds, so it only ever emits <li> elements
    (it lives inside a dropdown <ul>).
--}}
@forelse ($notifications as $notification)
    @php $data = $notification->data; @endphp
    <li>
        {{-- Clicking the entry opens the related page and marks it read (FR-25). --}}
        <a class="dropdown-item d-flex gap-2 py-2 {{ $notification->read_at ? '' : 'notification-unread' }}"
           href="{{ route('notifications.open', $notification->id) }}">
            <i class="bi {{ $data['icon'] ?? 'bi-bell' }} text-edu mt-1"></i>
            <span class="flex-grow-1">
                {{-- Everything is escaped by Blade. --}}
                <span class="d-block small">{{ $data['message'] ?? __('Notification') }}</span>
                <span class="d-block text-muted edu-time">{{ $notification->created_at?->diffForHumans() }}</span>
            </span>
        </a>
    </li>
@empty
    <li>
        <span class="dropdown-item-text small text-muted">{{ __('No notifications yet.') }}</span>
    </li>
@endforelse

<li><hr class="dropdown-divider"></li>
<li>
    <a class="dropdown-item text-center small fw-semibold" href="{{ route('notifications.index') }}">
        <i class="bi bi-list-ul me-1"></i>{{ __('See all notifications') }}
    </a>
</li>
