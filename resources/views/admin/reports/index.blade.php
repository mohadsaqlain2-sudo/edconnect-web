<x-app-layout>
    <x-slot name="title">{{ __('Reports') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Reports') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-flag me-1 text-edu" aria-hidden="true"></i> {{ __('Reports') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Review what the community reported (FR-33, FR-34).') }}</p>
            </div>
            <span class="badge text-bg-warning">
                <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>
                {{ $counts[\App\Models\Report::STATUS_PENDING] ?? 0 }} {{ __('pending') }}
            </span>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        {{-- Filters --}}
        <section class="card edu-card mb-3" aria-labelledby="report-filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="report-filters-title">{{ __('Filter reports') }}</h2>

                <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="report-q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="report-q" value="{{ $term }}" class="form-control"
                                   placeholder="{{ __('Reason, details, reporter or content') }}" maxlength="150">
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="report-status" class="form-label small">{{ __('Status') }}</label>
                        <select name="status" id="report-status" class="form-select">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach (\App\Models\Report::STATUSES as $value)
                                <option value="{{ $value }}" @selected($status === $value)>
                                    {{ __(\Illuminate\Support\Str::headline($value)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label for="report-type" class="form-label small">{{ __('Type') }}</label>
                        <select name="type" id="report-type" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="post" @selected($type === 'post')>{{ __('Posts') }}</option>
                            <option value="comment" @selected($type === 'comment')>{{ __('Comments') }}</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>

                {{-- One-tap status chips, keeping the other filters. --}}
                <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by status') }}">
                    <a class="edu-filter-chip {{ $status === '' ? 'is-active' : '' }}"
                       href="{{ route('admin.reports.index', array_filter(['q' => $term, 'type' => $type])) }}">
                        {{ __('All statuses') }}
                    </a>
                    @foreach (\App\Models\Report::STATUSES as $value)
                        <a class="edu-filter-chip {{ $status === $value ? 'is-active' : '' }}"
                           href="{{ route('admin.reports.index', array_filter(['q' => $term, 'status' => $value, 'type' => $type])) }}">
                            {{ __(\Illuminate\Support\Str::headline($value)) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($reports->isEmpty())
            <x-empty-state icon="bi-shield-check"
                           :title="__('No reports match these filters.')"
                           :message="__('The community is calm right now. Try another status or reset the filters.')">
                <a href="{{ route('admin.reports.index') }}" class="btn btn-primary">{{ __('Reset the filters') }}</a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('All reports, newest first') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('Content') }}</th>
                                <th scope="col">{{ __('Reported user') }}</th>
                                <th scope="col">{{ __('Reporter') }}</th>
                                <th scope="col">{{ __('Reason') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col">{{ __('Date') }}</th>
                                <th scope="col" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                @php($content = $report->reportable)
                                <tr>
                                    <td data-label="ID" class="text-muted">{{ $report->id }}</td>
                                    <td data-label="{{ __('Content') }}" class="edu-admin-post-cell">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <span class="badge bg-body-secondary text-body border">{{ $report->content_type }}</span>
                                            @if ($content && $content->is_hidden)
                                                <span class="badge text-bg-warning">{{ __('hidden') }}</span>
                                            @endif
                                        </div>
                                        <div class="small">{{ $report->content_preview }}</div>
                                    </td>
                                    <td data-label="{{ __('Reported user') }}">
                                        @if ($report->reported_user)
                                            <a href="{{ route('users.show', $report->reported_user) }}"
                                               class="text-decoration-none text-reset">{{ $report->reported_user->name }}</a>
                                        @else
                                            <span class="text-muted small">{{ __('Deleted') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Reporter') }}">
                                        <a href="{{ route('users.show', $report->reporter) }}"
                                           class="text-decoration-none text-reset">{{ $report->reporter?->name ?? __('Deleted') }}</a>
                                    </td>
                                    <td data-label="{{ __('Reason') }}">{{ $report->reason_label }}</td>
                                    <td data-label="{{ __('Status') }}">
                                        <x-status-badge :status="$report->status" />
                                    </td>
                                    <td data-label="{{ __('Date') }}" class="text-nowrap small text-muted">
                                        <time datetime="{{ $report->created_at?->toIso8601String() }}">{{ $report->created_at?->format('d M Y H:i') }}</time>
                                    </td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <a href="{{ route('admin.reports.show', $report) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ __('Review') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($reports->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$reports" />
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
