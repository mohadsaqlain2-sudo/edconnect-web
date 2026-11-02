{{-- Statistic card used on all three dashboards.
     Usage:
       <x-stat-card label="Students" :value="12" icon="bi-mortarboard" color="success" />
       <x-stat-card label="Users" :value="120" trend="12" :sparkline="[2,4,3,6,8,7,9]" />
     The optional sparkline is a tiny inline SVG, so it costs no extra request. --}}
@props([
    'label',
    'value',
    'icon' => 'bi-bar-chart',
    'color' => 'primary',
    'hint' => null,
    'href' => null,
    'trend' => null,
    'trendDirection' => null,
    'trendLabel' => null,
    'sparkline' => [],
])

@php
    $series = array_values(array_map(static fn ($point) => (float) $point, (array) $sparkline));
    $hasSparkline = count($series) > 1;

    $hex = [
        'primary' => '#4f46e5',
        'secondary' => '#7c3aed',
        'success' => '#10b981',
        'info' => '#0ea5e9',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
        'dark' => '#0f172a',
    ][$color] ?? '#4f46e5';

    $points = [];
    if ($hasSparkline) {
        $max = max($series);
        $min = min($series);
        $range = ($max - $min) ?: 1;
        $lastIndex = count($series) - 1;

        foreach ($series as $index => $point) {
            $x = ($index / $lastIndex) * 96;
            $y = 28 - ((($point - $min) / $range) * 24);
            $points[] = round($x, 2).','.round($y, 2);
        }
    }

    $gradientId = 'spark-'.substr(md5($label.$hex), 0, 8);

    $hasTrend = $trend !== null;
    $numericTrend = $hasTrend ? (float) $trend : 0;
    $direction = $trendDirection ?: ($numericTrend > 0 ? 'up' : ($numericTrend < 0 ? 'down' : 'flat'));
    $trendIcon = ['up' => 'bi-arrow-up-right', 'down' => 'bi-arrow-down-right', 'flat' => 'bi-dash'][$direction] ?? 'bi-dash';

    // Phase 3F: locale-aware grouping ("1.234" in de, "1,234" in en), with a
    // fallback for servers without ext-intl.
    $displayValue = is_numeric($value)
        ? \App\Support\LocaleNumber::format((float) $value)
        : $value;
    $displayTrend = $hasTrend
        ? \App\Support\LocaleNumber::format(abs($numericTrend))
        : null;
@endphp

<div {{ $attributes->class(['card', 'edu-card', 'edu-stat-card', 'h-100']) }} data-stat-card>
    <div class="card-body">
        <div class="edu-stat-top">
            <span class="edu-stat-icon bg-{{ $color }}" aria-hidden="true">
                <i class="bi {{ $icon }}"></i>
            </span>

            @if ($hasSparkline)
                {{-- Decorative mini sparkline; the number and trend carry the meaning. --}}
                <svg class="edu-sparkline" viewBox="0 0 96 32" role="presentation" focusable="false" aria-hidden="true">
                    <defs>
                        <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="{{ $hex }}" stop-opacity="0.28"></stop>
                            <stop offset="100%" stop-color="{{ $hex }}" stop-opacity="0"></stop>
                        </linearGradient>
                    </defs>
                    <polygon class="sparkline-fill" fill="url(#{{ $gradientId }})"
                             points="0,32 {{ implode(' ', $points) }} 96,32"></polygon>
                    <polyline points="{{ implode(' ', $points) }}" stroke="{{ $hex }}"></polyline>
                </svg>
            @endif
        </div>

        <div class="edu-stat-value">{{ $displayValue }}</div>

        <div class="d-flex align-items-end justify-content-between gap-2">
            <span class="edu-stat-label">{{ $label }}</span>

            @if ($hasTrend)
                <span class="edu-trend is-{{ $direction }}">
                    <i class="bi {{ $trendIcon }}" aria-hidden="true"></i>
                    {{ $displayTrend }}%
                </span>
            @endif
        </div>

        @if ($hasTrend && $trendLabel)
            <div class="edu-stat-hint">{{ $trendLabel }}</div>
        @endif

        @if ($hint)
            <div class="edu-stat-hint">{{ $hint }}</div>
        @endif
    </div>

    {{-- Optional link that makes the whole card clickable --}}
    @if ($href)
        <a class="stretched-link" href="{{ $href }}" aria-label="{{ $label }}"></a>
    @endif
</div>
