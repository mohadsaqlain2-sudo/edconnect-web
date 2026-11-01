<x-app-layout>
    <x-slot name="title">{{ $classRoom->title }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.discover') }}">{{ __('Classes') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit($classRoom->title, 48) }}</li>
    </x-slot>

    @php
        $statusColor = match ($classRoom->status) {
            \App\Models\ClassRoom::STATUS_LIVE => 'danger',
            \App\Models\ClassRoom::STATUS_COMPLETED => 'secondary',
            default => 'primary',
        };
        $isFinished = $classRoom->status === \App\Models\ClassRoom::STATUS_COMPLETED;
    @endphp

    <div class="container-xxl px-3 px-lg-4">
        <div class="row g-4">
            {{-- ------------------------------------------------------------------
                 Main column: the class itself
                 ------------------------------------------------------------------ --}}
            <div class="col-12 col-xl-8">
                <article class="card edu-card mb-4" aria-labelledby="class-title">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div class="min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <x-status-badge :status="$classRoom->status" />

                                    @if ($classRoom->isLive())
                                        <span class="badge text-bg-danger">
                                            <span class="edu-live-dot" aria-hidden="true"></span> {{ __('Live now') }}
                                        </span>
                                    @endif

                                    @if ($classRoom->subject)
                                        <span class="edu-chip">
                                            <i class="bi bi-book" aria-hidden="true"></i> {{ $classRoom->subject }}
                                        </span>
                                    @endif

                                    @if ($classRoom->is_hidden)
                                        {{-- An administrator hid this class (FR-28) --}}
                                        <span class="badge text-bg-warning">{{ __('Hidden by an admin') }}</span>
                                    @endif
                                </div>

                                <h1 class="h3 fw-bold mb-1 text-break" id="class-title">{{ $classRoom->title }}</h1>

                                <p class="small text-muted mb-0">
                                    <i class="bi bi-person-video3 me-1" aria-hidden="true"></i>
                                    {{ __('Hosted by') }}
                                    @if ($classRoom->teacher)
                                        <a href="{{ route('users.show', $classRoom->teacher) }}" class="fw-semibold text-decoration-none">
                                            {{ $classRoom->teacher->name }}
                                        </a>
                                    @else
                                        {{ __('Unknown teacher') }}
                                    @endif
                                </p>
                            </div>

                            {{-- Countdown to the start: server-rendered, then kept live by JS. --}}
                            @if (! $classRoom->isLive() && ! $isFinished)
                                <x-countdown :target="$classRoom->startsAt()->toIso8601String()" compact class="flex-shrink-0" />
                            @endif
                        </div>

                        <hr class="my-4">

                        {{-- When and how long --}}
                        <dl class="row g-3 small mb-4">
                            <div class="col-12 col-sm-4">
                                <dt class="text-muted fw-normal">{{ __('Date') }}</dt>
                                <dd class="fw-semibold mb-0">
                                    <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                    {{ $classRoom->date?->format('D d M Y') }}
                                </dd>
                            </div>
                            <div class="col-12 col-sm-4">
                                <dt class="text-muted fw-normal">{{ __('Start time') }}</dt>
                                <dd class="fw-semibold mb-0">
                                    <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                    {{ \Illuminate\Support\Str::substr((string) $classRoom->time, 0, 5) }}
                                </dd>
                            </div>
                            <div class="col-12 col-sm-4">
                                <dt class="text-muted fw-normal">{{ __('Duration') }}</dt>
                                <dd class="fw-semibold mb-0">
                                    <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>
                                    {{ $classRoom->duration }} {{ __('min') }}
                                </dd>
                            </div>
                        </dl>

                        <h2 class="h6 fw-bold">{{ __('About this class') }}</h2>

                        @if ($classRoom->description)
                            {{-- Escaped by Blade, line breaks kept --}}
                            <p class="text-break mb-0 edu-measure">{{ $classRoom->description }}</p>
                        @else
                            <p class="text-muted mb-0">{{ __('The teacher did not add a description.') }}</p>
                        @endif
                    </div>
                </article>

                {{-- Teacher card on narrow screens, where the sidebar stacks below. --}}
                <div class="card edu-card d-xl-none mb-4">
                    <div class="card-body d-flex align-items-center gap-3">
                        <x-avatar :user="$classRoom->teacher" size="lg" :link="$classRoom->teacher ? route('users.show', $classRoom->teacher) : null" />

                        <div class="min-w-0">
                            <div class="small text-muted">{{ __('Teacher') }}</div>

                            @if ($classRoom->teacher)
                                <a href="{{ route('users.show', $classRoom->teacher) }}"
                                   class="fw-semibold text-decoration-none text-reset text-break">
                                    {{ $classRoom->teacher->name }}
                                </a>
                            @endif

                            @if ($classRoom->teacher?->subject)
                                <div class="small text-muted">{{ $classRoom->teacher->subject }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------------------------------
                 Sidebar: the primary action, the teacher and the students
                 ------------------------------------------------------------------ --}}
            <div class="col-12 col-xl-4">
                <div class="edu-sticky-aside">
                    {{-- ----------------------------------------------------------
                         Actions: join / enroll / teacher tools (FR-20, FR-21, FR-24)
                         ---------------------------------------------------------- --}}
                    <section class="card edu-card mb-4" aria-labelledby="class-actions-title">
                        <div class="card-body d-flex flex-column gap-3">
                            <h2 class="h6 fw-bold mb-0" id="class-actions-title">{{ __('Your place in this class') }}</h2>

                            @if ($isOwner)
                                {{-- Only the teacher may open the room at any time (FR-20) --}}
                                <a href="{{ route('classes.room', $classRoom) }}" class="btn btn-danger btn-lg w-100">
                                    <i class="bi bi-camera-video me-1" aria-hidden="true"></i>
                                    {{ $classRoom->isLive() ? __('Enter the live room') : __('Open the room') }}
                                </a>

                                {{-- Status switches, limited to the allowed transitions (FR-24) --}}
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($classRoom->allowedTransitions() as $next)
                                        <form method="POST" action="{{ route('classes.status', $classRoom) }}" class="flex-grow-1"
                                              data-confirm="{{ __('Change the class status to :status?', ['status' => __($next)]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $next }}">
                                            <button type="submit"
                                                    class="btn w-100 {{ $next === \App\Models\ClassRoom::STATUS_LIVE ? 'btn-success' : 'btn-outline-secondary' }}">
                                                @if ($next === \App\Models\ClassRoom::STATUS_LIVE)
                                                    <i class="bi bi-broadcast me-1" aria-hidden="true"></i> {{ __('Start class') }}
                                                @elseif ($next === \App\Models\ClassRoom::STATUS_COMPLETED)
                                                    <i class="bi bi-stop-circle me-1" aria-hidden="true"></i> {{ __('End class') }}
                                                @else
                                                    <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> {{ __('Back to scheduled') }}
                                                @endif
                                            </button>
                                        </form>
                                    @endforeach
                                </div>

                                <hr class="my-1">

                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('classes.students', $classRoom) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-people me-1" aria-hidden="true"></i>
                                        {{ trans_choice(':count student|:count students', $classRoom->students_count, ['count' => $classRoom->students_count]) }}
                                    </a>
                                    <a href="{{ route('classes.edit', $classRoom) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil me-1" aria-hidden="true"></i> {{ __('Edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('classes.destroy', $classRoom) }}" class="d-inline"
                                          data-confirm="{{ __('Delete this class? The enrollments are removed as well.') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash me-1" aria-hidden="true"></i> {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>

                                {{-- Handy for a teacher who wants to share the room on another device. --}}
                                <div class="alert bg-body-secondary text-body border mb-0 small text-break">
                                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
                                    {{ __('Private room code') }}: <code>{{ $classRoom->room_name }}</code>
                                </div>
                            @elseif (auth()->user()->isStudent())
                                @if ($isEnrolled)
                                    {{-- An enrolled student --}}
                                    @if ($classRoom->isLive())
                                        <a href="{{ route('classes.room', $classRoom) }}" class="btn btn-danger btn-lg w-100">
                                            <i class="bi bi-camera-video me-1" aria-hidden="true"></i> {{ __('Join the live class') }}
                                        </a>
                                    @elseif ($isFinished)
                                        <div class="alert alert-secondary mb-0">
                                            <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                                            {{ __('This class is over. Come back for the next one!') }}
                                        </div>
                                    @else
                                        {{-- Clear message instead of a dead Join button (FR-20) --}}
                                        <div class="alert alert-info mb-0 small">
                                            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>
                                            {{ __('You are enrolled. The Join button appears as soon as the teacher starts the class.') }}
                                        </div>
                                    @endif

                                    <div class="d-flex align-items-center gap-2 small text-success fw-semibold">
                                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                        {{ __('You are enrolled in this class') }}
                                    </div>

                                    <form method="POST" action="{{ route('classes.unenroll', $classRoom) }}"
                                          data-confirm="{{ __('Leave this class?') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                            <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> {{ __('Unenroll') }}
                                        </button>
                                    </form>
                                @else
                                    {{-- Not enrolled yet --}}
                                    @if ($isFinished)
                                        <div class="alert alert-secondary mb-0">
                                            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                                            {{ __('This class is already over, enrolling is no longer possible.') }}
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('classes.enroll', $classRoom) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> {{ __('Enroll in this class') }}
                                            </button>
                                        </form>
                                        <p class="text-muted small mb-0">
                                            {{ __('Enroll to get the Join button and to appear on the attendance list.') }}
                                        </p>
                                        @if ($classRoom->isLive())
                                            <div class="alert alert-warning mb-0 small">
                                                <i class="bi bi-broadcast me-1" aria-hidden="true"></i>
                                                {{ __('This class is live right now. Enroll to join immediately.') }}
                                            </div>
                                        @endif
                                    @endif
                                @endif
                            @else
                                {{-- Admins and other roles: read-only summary. --}}
                                <div class="alert bg-body-secondary text-body border mb-0 small">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                                    {{ __('You can view this class but not enroll as your role does not take classes.') }}
                                </div>
                            @endif
                        </div>
                    </section>

                    {{-- Teacher (desktop sidebar) --}}
                    <section class="card edu-card mb-4 d-none d-xl-block" aria-labelledby="teacher-title">
                        <div class="card-body">
                            <h2 class="h6 fw-bold mb-3" id="teacher-title">{{ __('Teacher') }}</h2>

                            <div class="d-flex align-items-center gap-3">
                                <x-avatar :user="$classRoom->teacher" size="lg" />

                                <div class="min-w-0">
                                    @if ($classRoom->teacher)
                                        <a href="{{ route('users.show', $classRoom->teacher) }}"
                                           class="fw-semibold text-decoration-none text-reset text-break">
                                            {{ $classRoom->teacher->name }}
                                        </a>
                                    @endif

                                    @if ($classRoom->teacher?->subject)
                                        <div class="small text-muted">{{ $classRoom->teacher->subject }}</div>
                                    @endif
                                </div>
                            </div>

                            @if ($classRoom->teacher?->bio)
                                <p class="small text-muted mt-3 mb-0">{{ \Illuminate\Support\Str::limit($classRoom->teacher->bio, 160) }}</p>
                            @endif
                        </div>
                    </section>

                    {{-- Students preview --}}
                    <section class="card edu-card" aria-labelledby="students-title">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h6 fw-bold mb-0" id="students-title">{{ __('Students') }}</h2>
                                <span class="edu-chip">
                                    <i class="bi bi-people" aria-hidden="true"></i> {{ $classRoom->students_count }}
                                </span>
                            </div>

                            @forelse ($previewStudents as $student)
                                <div class="d-flex align-items-center gap-2 {{ ! $loop->last ? 'mb-2' : '' }}">
                                    <x-avatar :user="$student" size="sm" />
                                    <a href="{{ route('users.show', $student) }}"
                                       class="small text-decoration-none text-reset text-break">{{ $student->name }}</a>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-person-dash me-1" aria-hidden="true"></i> {{ __('Nobody has enrolled yet.') }}
                                </p>
                            @endforelse

                            @if ($classRoom->students_count > $previewStudents->count())
                                <div class="small text-muted mt-2">
                                    {{ __('and :count more', ['count' => $classRoom->students_count - $previewStudents->count()]) }}
                                </div>
                            @endif

                            @if ($isOwner || auth()->user()->isAdmin())
                                <a href="{{ route('classes.students', $classRoom) }}" class="btn btn-sm btn-outline-secondary mt-3 w-100">
                                    <i class="bi bi-list-ul me-1" aria-hidden="true"></i>{{ __('See the full list') }}
                                </a>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <div class="mt-2">
            <a href="{{ route('classes.discover') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to the classes') }}
            </a>
        </div>
    </div>
</x-app-layout>
