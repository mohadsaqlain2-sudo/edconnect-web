<x-app-layout>
    <x-slot name="title">{{ __('Edit class') }} · {{ $classRoom->title }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">{{ __('My classes') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.show', $classRoom) }}">{{ \Illuminate\Support\Str::limit($classRoom->title, 40) }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-pencil-square me-1 text-edu" aria-hidden="true"></i> {{ __('Edit class') }}
                </h1>
                <p class="small text-muted mb-0 text-break">{{ $classRoom->title }}</p>
            </div>
            <a href="{{ route('classes.show', $classRoom) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to the class') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        @include('classes._form', ['classRoom' => $classRoom])
    </div>
</x-app-layout>
