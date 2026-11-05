@props([
    'name',
    'show' => false,
    'maxWidth' => 'md',
])

@php
// Map Breeze's size names to Bootstrap modal classes.
$maxWidth = [
    'sm' => 'modal-sm',
    'md' => '',
    'lg' => 'modal-lg',
    'xl' => 'modal-xl',
][$maxWidth] ?? '';

// The dialog needs an accessible name. The content slot usually starts with a
// heading, but it has no guaranteed id, so the machine name is the fallback.
$dialogLabel = \Illuminate\Support\Str::headline($name);
@endphp

{{-- Alpine-powered modal.
     Open:  $dispatch('open-modal', '{{ $name }}')
     Close: $dispatch('close') or press Escape or click the backdrop. --}}
<div
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.window="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:click.self="show = false"
    x-effect="document.body.classList.toggle('modal-open', show)"
    x-show="show"
    x-cloak
    class="modal"
    style="background: rgba(0, 0, 0, .5);"
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-label="{{ $dialogLabel }}"
>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down {{ $maxWidth }}">
        <div class="modal-content">
            {{ $slot }}
        </div>
    </div>
</div>
