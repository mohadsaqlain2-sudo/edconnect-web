<x-app-layout>
    @php
        $myClassesUrl = match (auth()->user()->role) {
            \App\Models\User::ROLE_ADMIN => route('admin.classes.index'),
            \App\Models\User::ROLE_TEACHER => route('classes.index'),
            default => route('student.classes'),
        };
    @endphp

    <x-slot name="title">{{ __('Find classes') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Find classes') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-compass me-1 text-edu" aria-hidden="true"></i> {{ __('Find classes') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Browse what is live now and what is coming next.') }}</p>
            </div>
            <a href="{{ $myClassesUrl }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-journal-bookmark me-1" aria-hidden="true"></i> {{ __('My classes') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        {{-- Search by title, subject or teacher + subject + teacher + date (FR-17).
             A plain GET form, so the filters work without JavaScript and can be
             shared. --}}
        <section class="card edu-card mb-3" aria-labelledby="filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="filters-title">{{ __('Filter classes') }}</h2>

                <form method="GET" action="{{ route('classes.discover') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label for="q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="q" value="{{ $search }}" maxlength="150"
                                   class="form-control" placeholder="{{ __('Class title, subject or teacher') }}">
                        </div>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="subject" class="form-label small">{{ __('Subject') }}</label>
                        <select name="subject" id="subject" class="form-select">
                            <option value="">{{ __('All subjects') }}</option>
                            @foreach ($subjects as $option)
                                <option value="{{ $option }}" @selected($subject === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="teacher" class="form-label small">{{ __('Teacher') }}</label>
                        <select name="teacher" id="teacher" class="form-select">
                            <option value="">{{ __('All teachers') }}</option>
                            @foreach ($teachers as $option)
                                <option value="{{ $option->id }}" @selected($teacher === $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="date" class="form-label small">{{ __('Date') }}</label>
                        <input type="date" name="date" id="date" value="{{ $date }}" class="form-control">
                    </div>

                    <div class="col-6 col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        @if ($search !== '' || $subject !== '' || $teacher > 0 || $date !== '')
                            <a href="{{ route('classes.discover') }}" class="btn btn-outline-secondary"
                               title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                </form>

                {{-- One-tap subject chips, preserving the other filters. --}}
                @if ($subjects->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by subject') }}">
                        <a class="edu-filter-chip {{ $subject === '' ? 'is-active' : '' }}"
                           href="{{ route('classes.discover', array_filter(['q' => $search, 'teacher' => $teacher ?: null, 'date' => $date])) }}">
                            {{ __('All') }}
                        </a>

                        @foreach ($subjects as $option)
                            <a class="edu-filter-chip {{ $subject === $option ? 'is-active' : '' }}"
                               href="{{ route('classes.discover', array_filter(['q' => $search, 'subject' => $option, 'teacher' => $teacher ?: null, 'date' => $date])) }}">
                                {{ $option }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 fw-bold mb-0">{{ __('Upcoming and live classes') }}</h2>
            <span class="small text-muted">
                {{ trans_choice(':count result|:count results', $classes->total(), ['count' => $classes->total()]) }}
            </span>
        </div>

        <div class="row g-3">
            @forelse ($classes as $class)
                @php($isEnrolled = $enrolledIds->contains($class->id))

                <div class="col-12 col-md-6 col-xl-4">
                    <x-class-card :class-room="$class" :url="route('classes.show', $class)">
                        <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ __('Details') }}
                        </a>

                        @if (auth()->user()->isStudent())
                            @if ($isEnrolled)
                                {{-- Enrolled: leave again (FR-21) --}}
                                <form method="POST" action="{{ route('classes.unenroll', $class) }}" class="d-inline"
                                      data-confirm="{{ __('Leave this class?') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> {{ __('Unenroll') }}
                                    </button>
                                </form>

                                @if ($class->isLive())
                                    {{-- Students may only enter a running class (FR-20) --}}
                                    <a href="{{ route('classes.room', $class) }}" class="btn btn-sm btn-danger">
                                        <i class="bi bi-camera-video me-1" aria-hidden="true"></i> {{ __('Enter live class') }}
                                    </a>
                                @else
                                    <span class="badge bg-body-secondary text-body border align-self-center">
                                        {{ __('Join when it is live') }}
                                    </span>
                                @endif
                            @else
                                {{-- Not enrolled yet (FR-21) --}}
                                <form method="POST" action="{{ route('classes.enroll', $class) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> {{ __('Enroll') }}
                                    </button>
                                </form>
                            @endif
                        @endif
                    </x-class-card>
                </div>
            @empty
                <div class="col-12">
                    <x-empty-state icon="bi-calendar-x"
                                   :title="__('No class matches your search')"
                                   :message="__('Try another subject or date, or clear the filters to see everything.')">
                        <a href="{{ route('classes.discover') }}" class="btn btn-primary">
                            <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>
                            {{ __('Clear filters') }}
                        </a>
                    </x-empty-state>
                </div>
            @endforelse
        </div>

        <div class="mt-3">
            <x-pagination-links :paginator="$classes" />
        </div>
    </div>
</x-app-layout>
