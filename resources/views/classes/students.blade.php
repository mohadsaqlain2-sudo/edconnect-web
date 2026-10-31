<x-app-layout>
    <x-slot name="title">{{ __('Enrolled students') }} · {{ $classRoom->title }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">{{ __('My classes') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.show', $classRoom) }}">{{ \Illuminate\Support\Str::limit($classRoom->title, 40) }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Students') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-people me-1 text-edu" aria-hidden="true"></i> {{ __('Enrolled students') }}
                </h1>
                <p class="small text-muted mb-0 text-break">{{ $classRoom->title }}</p>
            </div>
            <a href="{{ route('classes.show', $classRoom) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to the class') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        {{-- Which class are we looking at? --}}
        <div class="card edu-card mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="min-w-0">
                    <h2 class="h6 fw-bold mb-1 text-break">{{ $classRoom->title }}</h2>
                    <div class="small text-muted">
                        <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>{{ $classRoom->date?->format('D d M Y') }}
                        <span class="mx-1" aria-hidden="true">&middot;</span>
                        <i class="bi bi-clock me-1" aria-hidden="true"></i>{{ \Illuminate\Support\Str::substr((string) $classRoom->time, 0, 5) }}
                    </div>
                </div>
                <span class="edu-chip flex-shrink-0">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    {{ trans_choice(':count student|:count students', $classRoom->students_count, ['count' => $classRoom->students_count]) }}
                </span>
            </div>
        </div>

        @if ($students->isEmpty())
            <x-empty-state icon="bi-person-dash"
                           :title="__('Nobody has enrolled yet.')"
                           :message="__('Share the class link with your students so they can enroll in one click.')">
                <a href="{{ route('classes.show', $classRoom) }}" class="btn btn-primary">
                    {{ __('Back to the class') }}
                </a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('Students enrolled in this class') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Student') }}</th>
                                <th scope="col">{{ __('Email') }}</th>
                                <th scope="col" class="text-end">{{ __('Enrolled at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $student)
                                <tr>
                                    <td data-label="{{ __('Student') }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <x-avatar :user="$student" size="sm" />
                                            <a href="{{ route('users.show', $student) }}"
                                               class="fw-semibold text-decoration-none text-reset text-break">{{ $student->name }}</a>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('Email') }}" class="text-muted small text-break">{{ $student->email }}</td>
                                    <td data-label="{{ __('Enrolled at') }}" class="text-end text-nowrap small text-muted">
                                        {{-- The pivot timestamp comes from withTimestamps() --}}
                                        <time datetime="{{ $student->pivot?->created_at?->toIso8601String() }}">
                                            {{ $student->pivot?->created_at?->format('d M Y H:i') }}
                                        </time>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($students->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$students" />
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
