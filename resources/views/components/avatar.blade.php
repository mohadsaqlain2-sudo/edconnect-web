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

    // Intrinsic size of each class, so the browser reserves the square before
    // the photo downloads (no layout shift / CLS).
    $dimensions = [
        'sm' => 32,
        'md' => 40,
        'lg' => 96,
        'xl' => 128,
    ][$size] ?? 40;

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
                 width="{{ $dimensions }}" height="{{ $dimensions }}"
                 {{ $attributes->class([$sizeClass]) }}>
        @else
            <span role="img" aria-label="{{ $name }}"
                  {{ $attributes->class(['edu-avatar-initials', $sizeClass]) }}>{{ $initials }}</span>
        @endif
    </a>
@elseif ($photo)
    <img src="{{ $photo }}" alt="{{ $name }}" loading="lazy" decoding="async"
         width="{{ $dimensions }}" height="{{ $dimensions }}"
         {{ $attributes->class([$sizeClass]) }}>
@else
    <span role="img" aria-label="{{ $name }}"
          {{ $attributes->class(['edu-avatar-initials', $sizeClass]) }}>{{ $initials }}</span>
@endif
