@php
    // Either the followers or the following list (FR-17).
    $title = $mode === 'followers' ? __('Followers') : __('Following');
@endphp

<x-app-layout>
    <x-slot name="title">{{ $title }} · {{ $profile->name }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('users.show', $profile) }}">{{ $profile->name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-people me-1 text-edu" aria-hidden="true"></i> {{ $title }}
                    <span class="text-muted fw-normal" aria-hidden="true">·</span>
                    <span class="fw-normal">{{ $profile->name }}</span>
                </h1>
                <p class="small text-muted mb-0">{{ __('People in this part of the community.') }}</p>
            </div>
            <a href="{{ route('users.show', $profile) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to profile') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                {{-- Switch between the two lists without going back first --}}
                <div class="d-flex flex-wrap gap-2 mb-3" aria-label="{{ __('Switch list') }}">
                    <a class="edu-filter-chip {{ $mode === 'followers' ? 'is-active' : '' }}"
                       href="{{ route('users.followers', $profile) }}"
                       @if ($mode === 'followers') aria-current="page" @endif>
                        <i class="bi bi-person-heart" aria-hidden="true"></i>
                        {{ __('Followers') }}
                        <span class="visually-hidden">{{ __('of :name', ['name' => $profile->name]) }}</span>
                    </a>
                    <a class="edu-filter-chip {{ $mode === 'following' ? 'is-active' : '' }}"
                       href="{{ route('users.following', $profile) }}"
                       @if ($mode === 'following') aria-current="page" @endif>
                        <i class="bi bi-person-check" aria-hidden="true"></i>
                        {{ __('Following') }}
                    </a>
                </div>

                @if ($people->isEmpty())
                    <x-empty-state icon="bi-people"
                                   :title="$mode === 'followers' ? __('Nobody follows this user yet.') : __('This user does not follow anybody yet.')"
                                   :message="__('When people connect on EduConnect they appear here.')">
                        <a href="{{ route('search.index', ['tab' => 'users']) }}" class="btn btn-primary">
                            <i class="bi bi-search me-1" aria-hidden="true"></i> {{ __('Find people') }}
                        </a>
                    </x-empty-state>
                @else
                    <div class="card edu-card">
                        <ul class="list-group list-group-flush">
                            @foreach ($people as $person)
                                @php
                                    $personRoleColor = match ($person->role) {
                                        \App\Models\User::ROLE_ADMIN => 'dark',
                                        \App\Models\User::ROLE_TEACHER => 'primary',
                                        default => 'success',
                                    };
                                @endphp

                                <li class="list-group-item d-flex flex-wrap align-items-center gap-3 py-3">
                                    <x-avatar :user="$person" size="md" />

                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <a href="{{ route('users.show', $person) }}"
                                               class="fw-semibold text-decoration-none text-reset text-break">{{ $person->name }}</a>
                                            <span class="badge text-bg-{{ $personRoleColor }} text-capitalize">{{ $person->role }}</span>
                                        </div>

                                        @if ($person->subject)
                                            <div class="small text-muted">
                                                <i class="bi bi-book me-1" aria-hidden="true"></i>{{ $person->subject }}
                                            </div>
                                        @endif
                                    </div>

                                    @include('users._follow-button', ['user' => $person, 'followingIds' => $followingIds])
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-3">
                        <x-pagination-links :paginator="$people" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
