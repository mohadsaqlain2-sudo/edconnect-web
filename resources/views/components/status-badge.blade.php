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
