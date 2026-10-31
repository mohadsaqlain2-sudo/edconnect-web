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

{{-- Alpine-powered modal (Phase 3D).
     Open it with: $dispatch('open-modal', '{{ $name }}')
     On phones it is full-screen (modal-fullscreen-sm-down), on tablets and
     desktops it is a centred dialog. The body scrolls inside the dialog so a
     long form never grows taller than the viewport. --}}
<div
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:click.self="show = false"
    x-effect="document.body.classList.toggle('modal-open', show)"
    x-show="show"
    class="modal d-block"
    style="display: none; background: rgba(0, 0, 0, .5);"
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
