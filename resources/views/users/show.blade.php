@php
    $roleColor = match ($profile->role) {
        \App\Models\User::ROLE_ADMIN => 'dark',
        \App\Models\User::ROLE_TEACHER => 'primary',
        default => 'success',
    };
    $isMe = auth()->id() === $profile->id;
@endphp

<x-app-layout>
    <x-slot name="title">{{ $isMe ? __('My profile') : $profile->name }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('search.index', ['tab' => 'users']) }}">{{ __('People') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $isMe ? __('My profile') : $profile->name }}</li>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                {{-- Profile hero: cover, avatar, name, role, counts and the primary action. --}}
                <section class="card edu-card overflow-hidden mb-3" aria-labelledby="profile-name">
                    <div class="edu-cover" aria-hidden="true"></div>

                    <div class="card-body pt-0">
                        <div class="d-flex flex-wrap align-items-end gap-3">
                            <x-avatar :user="$profile" size="xl"
                                      class="edu-cover-avatar border border-4 border-body flex-shrink-0" />

                            <div class="flex-grow-1 min-w-0 pb-1">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <h1 class="h4 fw-bold mb-0 text-break" id="profile-name">{{ $profile->name }}</h1>
                                    <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $profile->role }}</span>
                                </div>

                                @if ($profile->subject)
                                    <div class="text-muted small">
                                        <i class="bi bi-book me-1" aria-hidden="true"></i>{{ $profile->subject }}
                                    </div>
                                @endif

                                <div class="text-muted small">
                                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                                    {{ __('Joined :date', ['date' => $profile->created_at?->format('M Y')]) }}
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 pb-1">
                                @if ($isMe)
                                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                                        {{ __('Edit profile') }}
                                    </a>
                                @else
                                    @include('users._follow-button', ['user' => $profile, 'followingIds' => $followingIds])
                                @endif
                            </div>
                        </div>

                        @if ($profile->bio)
                            <p class="mt-3 mb-0 edu-measure text-break">{{ $profile->bio }}</p>
                        @endif

                        <div class="d-flex flex-wrap gap-4 mt-3 pt-3 border-top">
                            <span>
                                <strong class="d-block h6 mb-0">{{ $profile->posts_count }}</strong>
                                <span class="small text-muted">{{ __('Posts') }}</span>
                            </span>
                            <a href="{{ route('users.followers', $profile) }}" class="text-decoration-none text-reset">
                                <strong class="d-block h6 mb-0" data-followers-count data-user-id="{{ $profile->id }}">{{ $profile->followers_count }}</strong>
                                <span class="small text-muted">{{ __('Followers') }}</span>
                            </a>
                            <a href="{{ route('users.following', $profile) }}" class="text-decoration-none text-reset">
                                <strong class="d-block h6 mb-0" data-following-count data-user-id="{{ $profile->id }}">{{ $profile->following_count }}</strong>
                                <span class="small text-muted">{{ __('Following') }}</span>
                            </a>
                        </div>
                    </div>
                </section>

                {{-- Tabs: Posts, Classes (teachers only) and About. --}}
                <div class="card edu-card">
                    <div class="card-header pb-0">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="profile-posts-tab" data-bs-toggle="tab"
                                        data-bs-target="#profile-posts" type="button" role="tab"
                                        aria-controls="profile-posts" aria-selected="true">
                                    <i class="bi bi-chat-square-text me-1" aria-hidden="true"></i>
                                    {{ __('Posts') }}
                                </button>
                            </li>

                            @if ($profile->isTeacher())
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="profile-classes-tab" data-bs-toggle="tab"
                                            data-bs-target="#profile-classes" type="button" role="tab"
                                            aria-controls="profile-classes" aria-selected="false">
                                        <i class="bi bi-easel me-1" aria-hidden="true"></i>
                                        {{ __('Classes') }}
                                    </button>
                                </li>
                            @endif

                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="profile-about-tab" data-bs-toggle="tab"
                                        data-bs-target="#profile-about" type="button" role="tab"
                                        aria-controls="profile-about" aria-selected="false">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                                    {{ __('About') }}
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content">
                        {{-- Posts --}}
                        <div class="tab-pane fade show active p-3" id="profile-posts" role="tabpanel"
                             aria-labelledby="profile-posts-tab" tabindex="0">
                            @if ($isMe)
                                <div class="d-flex justify-content-end mb-3">
                                    <a href="{{ route('posts.create') }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> {{ __('New post') }}
                                    </a>
                                </div>
                            @endif

                            @include('feed._posts', [
                                'posts' => $posts,
                                'commentsByPost' => null,
                                'emptyMessage' => $isMe
                                    ? __('You have not posted yet. Share an update with your followers.')
                                    : __('This person has not posted yet.'),
                                'emptyActionUrl' => $isMe ? route('posts.create') : null,
                                'emptyActionLabel' => $isMe ? __('Write your first post') : null,
                            ])

                            <div class="mt-3">
                                <x-pagination-links :paginator="$posts" />
                            </div>
                        </div>

                        {{-- Classes (teachers) --}}
                        @if ($profile->isTeacher())
                            <div class="tab-pane fade p-3" id="profile-classes" role="tabpanel"
                                 aria-labelledby="profile-classes-tab" tabindex="0">
                                @if ($classes->isEmpty())
                                    <x-empty-state icon="bi-easel"
                                                   :title="__('No published classes yet')"
                                                   :message="__('When this teacher publishes a class it will appear here.')">
                                        <a href="{{ route('classes.discover') }}" class="btn btn-primary">
                                            {{ __('Browse classes') }}
                                        </a>
                                    </x-empty-state>
                                @else
                                    <div class="row g-3">
                                        @foreach ($classes as $classRoom)
                                            <div class="col-12 col-md-6">
                                                <x-class-card :class-room="$classRoom" :url="route('classes.show', $classRoom)" />
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- About --}}
                        <div class="tab-pane fade p-3" id="profile-about" role="tabpanel"
                             aria-labelledby="profile-about-tab" tabindex="0">
                            <dl class="row small mb-0">
                                <dt class="col-sm-3 text-muted fw-semibold">{{ __('Role') }}</dt>
                                <dd class="col-sm-9 text-capitalize">{{ $profile->role }}</dd>

                                @if ($profile->subject)
                                    <dt class="col-sm-3 text-muted fw-semibold">{{ __('Subject') }}</dt>
                                    <dd class="col-sm-9">{{ $profile->subject }}</dd>
                                @endif

                                <dt class="col-sm-3 text-muted fw-semibold">{{ __('Member since') }}</dt>
                                <dd class="col-sm-9">{{ $profile->created_at?->format('d M Y') }}</dd>

                                <dt class="col-sm-3 text-muted fw-semibold">{{ __('Bio') }}</dt>
                                <dd class="col-sm-9 text-break mb-0">
                                    {{ $profile->bio ?: __('This member has not written a bio yet.') }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
