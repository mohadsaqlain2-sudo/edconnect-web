<x-app-layout>
    <x-slot name="title">{{ __('My classes') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('My classes') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-easel me-1 text-edu" aria-hidden="true"></i> {{ __('My classes') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Everything you scheduled, live right now and already taught.') }}</p>
            </div>
            <a href="{{ route('classes.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-square me-1" aria-hidden="true"></i> {{ __('New class') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        @if ($classes->isEmpty())
            <x-empty-state icon="bi-easel"
                           :title="__('No classes yet')"
                           :message="__('You have not created a class yet. Publish your first lesson and your followers get a notification.')">
                <a href="{{ route('classes.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-square me-1" aria-hidden="true"></i> {{ __('Create my first class') }}
                </a>
                <a href="{{ route('classes.discover') }}" class="btn btn-outline-secondary">
                    {{ __('Browse classes') }}
                </a>
            </x-empty-state>
        @else
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <span class="small text-muted">
                    {{ trans_choice(':count class|:count classes', $classes->total(), ['count' => $classes->total()]) }}
                </span>
                <span class="small text-muted d-inline-flex align-items-center gap-2">
                    <span class="badge text-bg-danger"><span class="edu-live-dot" aria-hidden="true"></span> {{ __('Live') }}</span>
                    <span class="badge text-bg-primary">{{ __('Scheduled') }}</span>
                    <span class="badge text-bg-secondary">{{ __('Completed') }}</span>
                </span>
            </div>

            <div class="row g-3">
                @foreach ($classes as $class)
                    <div class="col-12 col-md-6 col-xl-4">
                        <x-class-card :class-room="$class" :url="route('classes.show', $class)">
                            {{-- Open the class detail page --}}
                            <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i> {{ __('Open') }}
                            </a>

                            {{-- Enrolled students (FR-24) --}}
                            <a href="{{ route('classes.students', $class) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-people me-1" aria-hidden="true"></i> {{ __('Students') }}
                            </a>

                            {{-- Status control: only the transitions the model allows
                                 are offered as buttons (FR-24). --}}
                            @foreach ($class->allowedTransitions() as $next)
                                <form method="POST" action="{{ route('classes.status', $class) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $next }}">
                                    <button type="submit"
                                            class="btn btn-sm {{ $next === \App\Models\ClassRoom::STATUS_LIVE ? 'btn-success' : 'btn-outline-danger' }}">
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

                            {{-- Edit / delete (ClassPolicy: my own classes only) --}}
                            <a href="{{ route('classes.edit', $class) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil me-1" aria-hidden="true"></i> {{ __('Edit') }}
                            </a>

                            <x-confirm-delete-modal
                                :id="'delete-class-'.$class->id"
                                :action="route('classes.destroy', $class)"
                                :title="__('Delete this class?')"
                                :message="__('The enrollments are removed as well. This action cannot be undone.')" />
                        </x-class-card>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                <x-pagination-links :paginator="$classes" />
            </div>
        @endif
    </div>
</x-app-layout>
