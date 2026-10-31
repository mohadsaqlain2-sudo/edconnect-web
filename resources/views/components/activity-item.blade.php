{{-- One entry in the teacher's recent-activity timeline.
     Usage:
       <x-activity-item icon="bi-person-plus" color="warning" text="Sina started following you"
           :at="$item['at']" /> --}}
@props([
    'icon' => 'bi-activity',
    'color' => 'primary',
    'text' => null,
    'excerpt' => null,
    'at' => null,
    'href' => null,
])

<div class="edu-activity">
    <span class="edu-activity-icon bg-{{ $color }}" aria-hidden="true">
        <i class="bi {{ $icon }}"></i>
    </span>

    <div class="min-w-0 flex-grow-1">
        <div class="edu-activity-text">
            @if ($href)
                <a href="{{ $href }}" class="text-decoration-none text-reset">{{ $text }}</a>
            @else
                {{ $text }}
            @endif
        </div>

        @if ($excerpt)
            <div class="edu-activity-excerpt">
                {{ \Illuminate\Support\Str::limit($excerpt, 70) }}
            </div>
        @endif

        @if ($at)
            <div class="edu-activity-time">
                <i class="bi bi-clock me-1" aria-hidden="true"></i>
                {{ $at instanceof \Illuminate\Support\Carbon ? $at->diffForHumans() : $at }}
            </div>
        @endif
    </div>
</div>
