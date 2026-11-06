<x-app-layout>
    <x-slot name="title">{{ __('Profile settings') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('users.show', auth()->user()) }}">{{ __('My profile') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Settings') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-gear me-1 text-edu" aria-hidden="true"></i> {{ __('Profile settings') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Manage your photo, personal details and account security.') }}</p>
            </div>
            <a href="{{ route('users.show', auth()->user()) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-person me-1" aria-hidden="true"></i> {{ __('View my profile') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card edu-card mb-4" id="profile-information">
                    <div class="card-body p-3 p-md-4">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="card edu-card mb-4" id="update-password">
                    <div class="card-body p-3 p-md-4">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="card edu-card border-danger-subtle" id="delete-account">
                    <div class="card-body p-3 p-md-4">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
