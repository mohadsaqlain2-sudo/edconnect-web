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
        <div class="row g-4">
            {{-- In-page navigation --}}
            <div class="col-12 col-lg-3">
                <nav class="card edu-card edu-sticky-aside" aria-label="{{ __('Settings sections') }}">
                    <div class="card-body py-3">
                        <div class="edu-eyebrow mb-2">{{ __('On this page') }}</div>
                        <ul class="nav flex-column gap-1 small">
                            <li class="nav-item">
                                <a class="nav-link py-1 px-2" href="#profile-information">
                                    <i class="bi bi-person me-1" aria-hidden="true"></i>{{ __('Profile information') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 px-2" href="#update-password">
                                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>{{ __('Password') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 px-2 text-danger" href="#delete-account">
                                    <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ __('Delete account') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>

            <div class="col-12 col-lg-9">
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
