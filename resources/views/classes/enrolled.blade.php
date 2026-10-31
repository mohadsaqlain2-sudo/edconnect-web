<x-app-layout>
    <x-slot name="title">{{ __('My enrolled classes') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('My enrolled classes') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-journal-bookmark me-1 text-edu" aria-hidden="true"></i> {{ __('My enrolled classes') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Live classes first, then the ones still ahead.') }}</p>
            </div>
            <a href="{{ route('classes.discover') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-search me-1" aria-hidden="true"></i> {{ __('Find more classes') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row g-3">
            @forelse ($classes as $class)
                <div class="col-12 col-md-6 col-xl-4">
                    <x-class-card :class-room="$class" :url="route('classes.show', $class)">
                        {{-- Details --}}
                        <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ __('Details') }}
                        </a>

                        @if ($class->isLive())
                            {{-- A running class can be joined right away (FR-20) --}}
                            <a href="{{ route('classes.room', $class) }}" class="btn btn-sm btn-danger">
                                <i class="bi bi-camera-video me-1" aria-hidden="true"></i> {{ __('Join live') }}
                            </a>
                        @elseif ($class->status === \App\Models\ClassRoom::STATUS_COMPLETED)
                            <span class="badge text-bg-secondary align-self-center">{{ __('Class finished') }}</span>
                        @else
                            <span class="badge bg-body-secondary text-body border align-self-center">
                                <i class="bi bi-clock me-1" aria-hidden="true"></i>{{ __('Not started yet') }}
                            </span>
                        @endif

                        {{-- Leave the class again (FR-21) --}}
                        <form method="POST" action="{{ route('classes.unenroll', $class) }}" class="d-inline"
                              data-confirm="{{ __('Leave this class?') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> {{ __('Unenroll') }}
                            </button>
                        </form>
                    </x-class-card>
                </div>
            @empty
                <div class="col-12">
                    <x-empty-state icon="bi-journal-x"
                                   :title="__('You are not enrolled in any class yet.')"
                                   :message="__('Find a class by subject, enroll in one click and it shows up here.')">
                        <a href="{{ route('classes.discover') }}" class="btn btn-primary">
                            <i class="bi bi-search me-1" aria-hidden="true"></i> {{ __('Browse the classes') }}
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
