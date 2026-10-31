{{-- Chart card with an optional range selector, loading skeleton and a text
     fallback for assistive technology / no-JavaScript visitors.

     Usage:
       <x-chart-card title="New users" chart-id="new-users" kind="line"
           series-key="newUsers" :series="$chart"
           :endpoint="route('admin.dashboard.charts')" current-range="12m" /> --}}
@props([
    'title',
    'subtitle' => null,
    'chartId' => null,
    'kind' => 'line',
    'series' => [],
    'label' => null,
    'color' => 'primary',
    'endpoint' => null,
    'ranges' => [],
    'currentRange' => '12m',
    'height' => 300,
    'seriesKey' => null,
    'total' => null,
    'fallback' => null,
])

@php
    $labels = array_values((array) ($series['labels'] ?? []));
    $values = array_map(static fn ($value) => (float) $value, array_values((array) ($series['values'] ?? [])));
    $seriesKey = $seriesKey ?: $chartId ?: 'series';

    $hex = [
        'primary' => '#4f46e5',
        'secondary' => '#7c3aed',
        'success' => '#10b981',
        'info' => '#0ea5e9',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
    ][$color] ?? '#4f46e5';

    $datasetLabel = $label ?: $title;
    $baseDataset = ['label' => $datasetLabel, 'data' => $values];

    if ($kind === 'doughnut' || $kind === 'pie') {
        $dataset = $baseDataset + [
            'backgroundColor' => ['#4f46e5', '#7c3aed', '#10b981', '#f59e0b', '#ef4444', '#0ea5e9', '#64748b', '#ec4899'],
            'borderWidth' => 0,
            'hoverOffset' => 6,
        ];

        $config = [
            'type' => $kind,
            'data' => ['labels' => $labels, 'datasets' => [$dataset]],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'cutout' => $kind === 'doughnut' ? '68%' : 0,
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom',
                        'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'boxHeight' => 8, 'padding' => 14],
                    ],
                ],
            ],
        ];
    } elseif ($kind === 'bar') {
        $dataset = $baseDataset + [
            'backgroundColor' => 'rgba(79, 70, 229, 0.72)',
            'hoverBackgroundColor' => $hex,
            'borderColor' => $hex,
            'borderWidth' => 0,
            'borderRadius' => 8,
            'borderSkipped' => false,
            'maxBarThickness' => 38,
        ];

        $config = [
            'type' => 'bar',
            'data' => ['labels' => $labels, 'datasets' => [$dataset]],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'x' => ['grid' => ['display' => false], 'border' => ['display' => false]],
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                        'grid' => ['drawBorder' => false],
                    ],
                ],
            ],
        ];
    } else {
        $dataset = $baseDataset + [
            'backgroundColor' => 'rgba(79, 70, 229, 0.16)',
            'borderColor' => $hex,
            'borderWidth' => 2.5,
            'fill' => true,
            'tension' => 0.38,
            'pointRadius' => 0,
            'pointHoverRadius' => 5,
            'pointBackgroundColor' => $hex,
            'pointBorderColor' => '#ffffff',
            'pointBorderWidth' => 2,
        ];

        $config = [
            'type' => 'line',
            'data' => ['labels' => $labels, 'datasets' => [$dataset]],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'x' => ['grid' => ['display' => false], 'border' => ['display' => false]],
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                        'grid' => ['drawBorder' => false],
                    ],
                ],
            ],
        ];
    }

    $ranges = ! empty($ranges) ? $ranges : [
        '7d' => __('Last 7 days'),
        '30d' => __('Last 30 days'),
        '12m' => __('Last 12 months'),
    ];

    if ($fallback === null) {
        $pairs = [];
        foreach ($labels as $index => $chartLabel) {
            $pairs[] = $chartLabel.': '.($values[$index] ?? 0);
        }
        $fallback = $title.'. '.implode(', ', $pairs);
    }

    $chartId = $chartId ?: 'chart-'.substr(md5($title.$seriesKey), 0, 8);
@endphp

<div class="card edu-card edu-chart-card h-100" data-chart-card>
    <div class="card-header">
        <div>
            <h2 class="card-title">{{ $title }}</h2>
            @if ($subtitle)
                <div class="card-subtitle">{{ $subtitle }}</div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-2">
            @if ($total !== null)
                <span class="edu-chip edu-chart-total" data-chart-total>{{ number_format((float) $total) }}</span>
            @endif

            @if ($endpoint)
                <label class="visually-hidden" for="{{ $chartId }}-range">{{ __('Chart range') }}</label>
                <select class="form-select form-select-sm edu-chart-range"
                        id="{{ $chartId }}-range" data-chart-range>
                    @foreach ($ranges as $value => $rangeLabel)
                        <option value="{{ $value }}" @selected($currentRange === $value)>{{ $rangeLabel }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    <div class="card-body">
        <div class="edu-chart" style="height: {{ (int) $height }}px">
            {{-- Shown while a range request is running. --}}
            <div class="edu-chart-skeleton" data-chart-skeleton hidden aria-hidden="true">
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
            </div>

            <canvas id="{{ $chartId }}" data-chart="{{ json_encode($config) }}"
                    data-chart-series="{{ $seriesKey }}"
                    data-chart-kind="{{ $kind }}"
                    @if ($endpoint) data-chart-endpoint="{{ $endpoint }}" @endif
                    role="img" aria-label="{{ $title }}"></canvas>
        </div>

        {{-- Text alternative. Visible when JavaScript is off, and always
             available to screen readers. --}}
        <p class="visually-hidden">{{ $fallback }}</p>
        <noscript>
            <p class="small text-muted mt-3 mb-0">{{ $fallback }}</p>
        </noscript>

        @if ($slot->isNotEmpty())
            <div class="mt-3">{{ $slot }}</div>
        @endif
    </div>
</div>
