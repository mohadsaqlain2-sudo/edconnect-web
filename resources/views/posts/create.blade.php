<x-app-layout>
    <x-slot name="title">{{ __('New post') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('feed.index') }}">{{ __('Feed') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('New post') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-pencil-square me-1 text-edu" aria-hidden="true"></i> {{ __('New post') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Short updates work best. Add one image if it helps.') }}</p>
            </div>
            <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-lg me-1" aria-hidden="true"></i> {{ __('Cancel') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                <div class="card edu-card">
                    <div class="card-body">
                        {{-- Same partial the feed composer uses (FR-9). --}}
                        @include('posts._form', ['post' => null])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
