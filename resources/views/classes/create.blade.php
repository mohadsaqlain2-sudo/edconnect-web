<x-app-layout>
    <x-slot name="title">{{ __('New class') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">{{ __('My classes') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('New class') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-plus-square me-1 text-edu" aria-hidden="true"></i> {{ __('New class') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Pick a time, describe the lesson and publish it to your followers.') }}</p>
            </div>
            <a href="{{ route('classes.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('My classes') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        {{-- Shared form, same partial as the edit page (FR-18). --}}
        @include('classes._form', ['classRoom' => null])
    </div>
</x-app-layout>
