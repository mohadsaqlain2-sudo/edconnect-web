@props([
    'name',
    'show' => false,
    'maxWidth' => 'md',
])

@php
// Map Breeze's size names to Bootstrap modal classes
$maxWidth = [
    'sm' => 'modal-sm',
    'md' => '',
    'lg' => 'modal-lg',
    'xl' => 'modal-xl',
][$maxWidth] ?? '';
@endphp

{{-- Simple Alpine-powered modal. Open it with:
     $dispatch('open-modal', '{{ $name }}') --}}
<div
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="modal d-block"
    style="display: none; background: rgba(0, 0, 0, .5);"
    tabindex="-1"
>
    <div class="modal-dialog modal-dialog-centered {{ $maxWidth }}">
        <div class="modal-content">
            {{ $slot }}
        </div>
    </div>
</div>
