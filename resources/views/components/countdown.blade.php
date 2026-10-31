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
