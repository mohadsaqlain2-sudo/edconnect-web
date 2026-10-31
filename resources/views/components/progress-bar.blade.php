{{-- Accessible progress bar.
     Usage: <x-progress-bar :value="$class->students_count" :max="$maxEnrollment"
               label="12 / 20 enrolled" /> --}}
@props([
    'value' => 0,
    'max' => 100,
    'color' => 'primary',
    'label' => null,
    'showValue' => true,
])

@php
    $value = max(0, (float) $value);
    $max = max(1, (float) $max);
    $percent = (int) round(min(100, ($value / $max) * 100));
@endphp

<div {{ $attributes->class(['edu-progress']) }}>
    @if ($label || $showValue)
        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
            @if ($label)
                <span class="small text-muted text-truncate">{{ $label }}</span>
            @endif

            @if ($showValue)
                <span class="small fw-semibold tabular-nums">{{ $percent }}%</span>
            @endif
        </div>
    @endif

    <div class="progress" role="progressbar" aria-valuenow="{{ $percent }}"
         aria-valuemin="0" aria-valuemax="100"
         aria-label="{{ $label ?: __('Enrollment progress') }}">
        <div class="progress-bar bg-{{ $color }}" style="width: {{ $percent }}%"></div>
    </div>
</div>
