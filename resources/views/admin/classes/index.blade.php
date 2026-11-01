<x-app-layout>
    <x-slot name="title">{{ __('Classes') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Classes') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-easel me-1 text-edu" aria-hidden="true"></i> {{ __('Classes') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Hide or delete inappropriate classes (FR-28).') }}</p>
            </div>
            <span class="edu-chip">
                <i class="bi bi-easel" aria-hidden="true"></i>
                {{ $classes->total() }} {{ __('classes') }}
            </span>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <section class="card edu-card mb-3" aria-labelledby="class-filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="class-filters-title">{{ __('Filter classes') }}</h2>

                <form method="GET" action="{{ route('admin.classes.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="class-q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="class-q" value="{{ $term }}" class="form-control"
                                   placeholder="{{ __('Class title or teacher name') }}" maxlength="150">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="class-status" class="form-label small">{{ __('Status') }}</label>
                        <select name="status" id="class-status" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            @foreach (\App\Models\ClassRoom::STATUSES as $value)
                                <option value="{{ $value }}" @selected($status === $value)>
                                    {{ __(\Illuminate\Support\Str::headline($value)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="class-hidden" class="form-label small">{{ __('Visibility') }}</label>
                        <select name="hidden" id="class-hidden" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="0" @selected($hidden === '0')>{{ __('Visible') }}</option>
                            <option value="1" @selected($hidden === '1')>{{ __('Hidden') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by status') }}">
                    <a class="edu-filter-chip {{ $status === '' ? 'is-active' : '' }}"
                       href="{{ route('admin.classes.index', array_filter(['q' => $term, 'hidden' => $hidden])) }}">
                        {{ __('All statuses') }}
                    </a>
                    @foreach (\App\Models\ClassRoom::STATUSES as $value)
                        <a class="edu-filter-chip {{ $status === $value ? 'is-active' : '' }}"
                           href="{{ route('admin.classes.index', array_filter(['q' => $term, 'status' => $value, 'hidden' => $hidden])) }}">
                            {{ __(\Illuminate\Support\Str::headline($value)) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($classes->isEmpty())
            <x-empty-state icon="bi-calendar-x"
                           :title="__('No classes match these filters.')"
                           :message="__('Try another title, status or visibility, or reset the filters.')">
                <a href="{{ route('admin.classes.index') }}" class="btn btn-primary">{{ __('Reset the filters') }}</a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('All classes, newest first') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Class') }}</th>
                                <th scope="col">{{ __('Teacher') }}</th>
                                <th scope="col">{{ __('When') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col" class="text-end">{{ __('Students') }}</th>
                                <th scope="col" class="text-end">{{ __('Reports') }}</th>
                                <th scope="col">{{ __('Visibility') }}</th>
                                <th scope="col" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classes as $classRoom)
                                <tr>
                                    <td data-label="{{ __('Class') }}">
                                        <a href="{{ route('classes.show', $classRoom) }}"
                                           class="fw-semibold text-decoration-none text-reset">{{ $classRoom->title }}</a>
                                        @if ($classRoom->subject)
                                            <div class="small text-muted">{{ $classRoom->subject }}</div>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Teacher') }}" class="text-nowrap">
                                        {{ $classRoom->teacher?->name ?? __('Deleted teacher') }}
                                    </td>
                                    <td data-label="{{ __('When') }}" class="text-nowrap small">
                                        {{ $classRoom->date?->format('d M Y') }}
                                        <div class="text-muted">{{ \Illuminate\Support\Str::substr((string) $classRoom->time, 0, 5) }}</div>
                                    </td>
                                    <td data-label="{{ __('Status') }}">
                                        <x-status-badge :status="$classRoom->status" />
                                    </td>
                                    <td data-label="{{ __('Students') }}" class="text-end">{{ $classRoom->students_count }}</td>
                                    <td data-label="{{ __('Reports') }}" class="text-end">
                                        {{-- Reports target posts and comments; a class is moderated with Hide (cancel). --}}
                                        <span class="text-muted" title="{{ __('Classes are not reportable') }}">&mdash;</span>
                                    </td>
                                    <td data-label="{{ __('Visibility') }}">
                                        @if ($classRoom->is_hidden)
                                            <span class="badge text-bg-warning">{{ __('Hidden') }}</span>
                                        @else
                                            <span class="badge text-bg-success">{{ __('Visible') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <form method="POST" action="{{ route('admin.classes.hide', $classRoom) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-{{ $classRoom->is_hidden ? 'success' : 'warning' }}">
                                                    <i class="bi bi-eye{{ $classRoom->is_hidden ? '' : '-slash' }} me-1" aria-hidden="true"></i>
                                                    {{ $classRoom->is_hidden ? __('Unhide') : __('Hide (cancel)') }}
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#delete-class-admin-{{ $classRoom->id }}"
                                                    title="{{ __('Delete') }}" aria-label="{{ __('Delete this class') }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($classes->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$classes" />
                    </div>
                @endif
            </div>

            @foreach ($classes as $classRoom)
                <x-confirm-delete-modal
                    :id="'delete-class-admin-'.$classRoom->id"
                    :action="route('admin.classes.destroy', $classRoom)"
                    :title="__('Delete this class?')"
                    :message="__('The class and every enrollment are removed. This cannot be undone.')"
                    :confirm-label="__('Delete class')"
                    :show-trigger="false" />
            @endforeach
        @endif
    </div>
</x-app-layout>
