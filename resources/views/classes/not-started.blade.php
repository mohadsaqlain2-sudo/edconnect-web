<x-app-layout>
    <x-slot name="title">{{ $classRoom->title }} · {{ __('Not started yet') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.show', $classRoom) }}">{{ \Illuminate\Support\Str::limit($classRoom->title, 40) }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Not started yet') }}</li>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-6">
                <section class="card edu-card text-center" aria-labelledby="not-started-title">
                    <div class="card-body py-5">
                        <div class="edu-empty-illustration mx-auto mb-3" style="width: 74px; height: 74px; font-size: 1.8rem; border-radius: 22px;" aria-hidden="true">
                            <i class="bi bi-hourglass-split"></i>
                        </div>

                        <h1 class="h4 fw-bold mb-2" id="not-started-title">{{ __('This class has not started yet') }}</h1>

                        <p class="text-muted mb-3">
                            {{ __('You are enrolled, so the Join button will work as soon as :teacher starts the room.', ['teacher' => $classRoom->teacher?->name ?? __('your teacher')]) }}
                        </p>

                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
                            <x-status-badge :status="$classRoom->status" />
                            <span class="badge bg-body-secondary text-body border">
                                <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                {{ $classRoom->date?->format('D d M Y') }}
                            </span>
                            <span class="badge bg-body-secondary text-body border">
                                <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                {{ \Illuminate\Support\Str::substr((string) $classRoom->time, 0, 5) }}
                            </span>
                        </div>

                        <p class="small fw-semibold text-muted mb-4">
                            <i class="bi bi-stopwatch me-1" aria-hidden="true"></i>
                            {{ $classRoom->timelineLabel() }}
                        </p>

                        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                            <a href="{{ route('classes.show', $classRoom) }}" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to the class') }}
                            </a>
                            <a href="{{ route('student.classes') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-journal-bookmark me-1" aria-hidden="true"></i> {{ __('My enrolled classes') }}
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
