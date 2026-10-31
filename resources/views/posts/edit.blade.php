<x-app-layout>
    <x-slot name="title">{{ __('Edit post') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('feed.index') }}">{{ __('Feed') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('posts.show', $post) }}">{{ __('Post') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-pencil me-1 text-edu" aria-hidden="true"></i> {{ __('Edit post') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Update the text or replace the image.') }}</p>
            </div>
            <a href="{{ route('posts.show', $post) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to the post') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                <div class="card edu-card">
                    <div class="card-body">
                        {{-- Only reachable when PostPolicy allows it (FR-10). --}}
                        @include('posts._form', ['post' => $post])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
