{{-- Overlapping avatar stack.
     Usage: <x-avatar-stack :users="$classRoom->students" :limit="5" /> --}}
@props([
    'users' => [],
    'limit' => 5,
    'size' => 'sm',
    'onLight' => false,
])

@php
    $collection = collect($users);
    $shown = $collection->take($limit);
    $extra = max(0, $collection->count() - $shown->count());
    $names = $collection->pluck('name')->filter()->implode(', ');
@endphp

<div {{ $attributes->class(['edu-avatar-stack', 'on-light' => $onLight]) }}
     @if ($names !== '') title="{{ $names }}" @endif>
    @foreach ($shown as $member)
        <x-avatar :user="$member" :size="$size" />
    @endforeach

    @if ($extra > 0)
        <span class="edu-avatar-stack-more" aria-label="{{ __(':count more students', ['count' => $extra]) }}">
            +{{ $extra }}
        </span>
    @endif
</div>
