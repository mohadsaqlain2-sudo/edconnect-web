@props([
    'align' => 'right',
    'contentClasses' => '',
])

@php
// Bootstrap needs the menu to sit right next to the toggle button.
$alignmentClasses = $align === 'left' ? 'dropdown-menu-start' : 'dropdown-menu-end';
@endphp

<div class="dropdown">
    {{ $trigger }}

    <div class="dropdown-menu shadow {{ $alignmentClasses }} {{ $contentClasses }}">
        {{ $content }}
    </div>
</div>
