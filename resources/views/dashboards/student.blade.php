<x-app-layout>
    <x-slot name="title">{{ __('Student dashboard') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('student.dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Learning') }}</li>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4 edu-dashboard">
        @php
            $hour = (int) now()->format('G');
            $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
            $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
        @endphp

        {{-- Greeting + quick actions --}}
        <header class="edu-greeting">
            <div>
                <span class="edu-eyebrow">
                    <i class="bi bi-mortarboard" aria-hidden="true"></i>
                    {{ __('Learning workspace') }}
                </span>
                <h1>{{ $greeting }}, {{ $firstName }}</h1>
                <p>
                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                    {{ now()->isoFormat('dddd, D MMMM YYYY') }}
                    <span class="mx-1" aria-hidden="true">·</span>
                    {{ __('Keep going — your next class is waiting.') }}
                </p>
            </div>

            <div class="edu-quick-actions" aria-label="{{ __('Quick actions') }}">
                <a href="{{ route('classes.discover') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-search" aria-hidden="true"></i> {{ __('Find a class') }}
                </a>
                <a href="{{ route('student.classes') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-journal-bookmark" aria-hidden="true"></i> {{ __('My classes') }}
                </a>
                <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chat-square-text" aria-hidden="true"></i> {{ __('Open feed') }}
                </a>
            </div>
        </header>

        {{-- Next enrolled class hero --}}
        @if ($heroClass)
            <section class="edu-hero mb-4" aria-labelledby="next-class-title">
                <div class="row align-items-center g-4">
                    <div class="col-12 col-lg-7">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="edu-hero-label small fw-semibold text-uppercase" style="letter-spacing: .1em;">
                                {{ $heroClass->isLive() ? __('Happening now') : __('Your next class') }}
                            </span>

                            @if ($heroClass->isLive())
                                <span class="edu-hero-live">
                                    <span class="edu-live-dot" aria-hidden="true"></span> {{ __('Live now') }}
                                </span>
                            @else
                                <span class="edu-chip">
                                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                    {{ __('Starts soon') }}
                                </span>
                            @endif
                        </div>

                        <h1 class="h2 fw-bold mb-2" id="next-class-title">{{ $heroClass->title }}</h1>

                        <div class="edu-hero-meta d-flex flex-wrap gap-3 mb-3">
                            <span>
                                <i class="bi bi-person-video3 me-1" aria-hidden="true"></i>
                                {{ $heroClass->teacher?->name ?? __('Your teacher') }}
                            </span>
                            @if ($heroClass->subject)
                                <span>
                                    <i class="bi bi-book me-1" aria-hidden="true"></i>
                                    {{ $heroClass->subject }}
                                </span>
                            @endif
                            <span>
                                <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                {{ $heroClass->date?->format('D d M Y') }}
                            </span>
                            <span>
                                <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                {{ \Illuminate\Support\Str::substr((string) $heroClass->time, 0, 5) }}
                                &middot; {{ $heroClass->duration }} {{ __('min') }}
                            </span>
                        </div>

                        @unless ($heroClass->isLive())
                            <x-countdown :target="$heroClass->startsAt()->toIso8601String()" />
                        @endunless

                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                            <x-avatar-stack :users="$classmates" :limit="5" />
                            <span class="small" style="color: rgba(255,255,255,.8);">
                                {{ trans_choice(':count classmate|:count classmates', $heroClass->students_count ?? 0, ['count' => $heroClass->students_count ?? 0]) }}
                            </span>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                            @if ($heroClass->isLive())
                                {{-- The room only opens while the class is live (FR-20). --}}
                                <a href="{{ route('classes.room', $heroClass) }}" class="btn btn-light btn-lg">
                                    <i class="bi bi-camera-video" aria-hidden="true"></i> {{ __('Join live') }}
                                </a>
                            @else
                                {{-- Stays disabled until the teacher starts the class. --}}
                                <button type="button" class="btn btn-light btn-lg" disabled
                                        aria-disabled="true"
                                        title="{{ __('The Join button opens when the teacher starts the class.') }}">
                                    <i class="bi bi-camera-video" aria-hidden="true"></i> {{ __('Join') }}
                                </button>
                            @endif

                            <a href="{{ route('classes.show', $heroClass) }}" class="btn btn-outline-light btn-lg">
                                {{ __('Details') }}
                            </a>
                        </div>

                        <div class="text-lg-end mt-3">
                            <a href="{{ route('student.classes') }}"
                               class="small text-decoration-none" style="color: rgba(255,255,255,.78);">
                                <i class="bi bi-journal-bookmark me-1" aria-hidden="true"></i>
                                {{ __('All my classes') }}
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        @else
            <section class="card edu-card mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="edu-empty-illustration mb-0" style="width: 62px; height: 62px; font-size: 1.5rem;" aria-hidden="true">
                            <i class="bi bi-calendar-plus"></i>
                        </span>
                        <div>
                            <h2 class="h5 fw-bold mb-1">{{ __('No class booked yet') }}</h2>
                            <p class="text-muted small mb-0">{{ __('Browse the catalogue and join your first live class.') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('classes.discover') }}" class="btn btn-primary">
                        <i class="bi bi-search" aria-hidden="true"></i> {{ __('Find a class') }}
                    </a>
                </div>
            </section>
        @endif

        {{-- Student counters: the three contracted stats plus notifications. --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Enrolled classes')" :value="$stats['classes']"
                    icon="bi-journal-bookmark" color="primary"
                    :hint="__('Classes you joined')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Upcoming classes')" :value="$stats['upcoming']"
                    icon="bi-calendar-check" color="success"
                    :hint="__('Scheduled and still ahead')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Teachers followed')" :value="$stats['following']"
                    icon="bi-person-hearts" color="info"
                    :hint="__('People you learn from')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Notifications')" :value="$notificationCount"
                    icon="bi-bell" color="warning" :href="route('notifications.index')"
                    :hint="__('Unread updates')" />
            </div>
        </div>

        {{-- Upcoming classes + recommended teachers --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xl-8">
                <section class="card edu-card h-100" aria-labelledby="upcoming-classes-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="upcoming-classes-title">
                                <i class="bi bi-calendar-week me-1 text-edu" aria-hidden="true"></i>
                                {{ __('My upcoming classes') }}
                            </h2>
                            <div class="small text-muted">{{ __('Your next live sessions, soonest first.') }}</div>
                        </div>
                        <a href="{{ route('student.classes') }}" class="small fw-semibold text-decoration-none">
                            {{ __('See all') }}
                        </a>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            @forelse ($upcomingClasses as $class)
                                <div class="col-12 col-md-6">
                                    <x-class-card :class-room="$class" :url="route('classes.show', $class)">
                                        @if ($class->isLive())
                                            <a href="{{ route('classes.room', $class) }}" class="btn btn-sm btn-danger">
                                                <i class="bi bi-camera-video me-1" aria-hidden="true"></i> {{ __('Join live') }}
                                            </a>
                                        @else
                                            {{-- Disabled until the teacher starts the class; the
                                                 PhaseThreeUiTest asserts these exact attributes. --}}
                                            <button type="button" class="btn btn-sm btn-primary" disabled
                                                    aria-disabled="true"
                                                    title="{{ __('The Join button opens when the teacher starts the class.') }}">
                                                <i class="bi bi-camera-video me-1" aria-hidden="true"></i> {{ __('Join') }}
                                            </button>
                                        @endif

                                        <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ __('Details') }}
                                        </a>
                                    </x-class-card>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="text-center py-4">
                                        <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;" aria-hidden="true">
                                            <i class="bi bi-calendar-x"></i>
                                        </div>
                                        <div class="fw-semibold small">{{ __('No upcoming class') }}</div>
                                        <div class="text-muted small mb-3">{{ __('Browse the catalogue to find your next session.') }}</div>
                                        <a href="{{ route('classes.discover') }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-search" aria-hidden="true"></i> {{ __('Browse classes') }}
                                        </a>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-4">
                <section class="card edu-card h-100" aria-labelledby="recommended-teachers-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="recommended-teachers-title">
                                <i class="bi bi-person-hearts me-1 text-edu" aria-hidden="true"></i>
                                {{ __('Recommended teachers') }}
                            </h2>
                            <div class="small text-muted">{{ __('Follow a teacher to see their classes.') }}</div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            @forelse ($recommendedTeachers as $teacher)
                                <x-user-card :user="$teacher">
                                    {{-- AJAX follow when JS is on, a normal POST otherwise. --}}
                                    @include('users._follow-button', ['user' => $teacher])
                                    <a href="{{ route('users.show', $teacher) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-person me-1" aria-hidden="true"></i> {{ __('Profile') }}
                                    </a>
                                </x-user-card>
                            @empty
                                <div class="text-center py-4">
                                    <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;" aria-hidden="true">
                                        <i class="bi bi-check2-circle"></i>
                                    </div>
                                    <div class="fw-semibold small">{{ __('All caught up') }}</div>
                                    <div class="text-muted small">{{ __('You already follow every teacher.') }}</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
        </div>

        {{-- Learning activity + recommended classes --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xl-7">
                <x-chart-card :title="__('Learning activity')"
                    :subtitle="__('Classes attended over the selected period')"
                    chart-id="student-activity" kind="bar" series-key="activity" color="primary"
                    :series="$activityChart" :total="$activityChart['total']"
                    :endpoint="route('student.dashboard.charts')" current-range="12m" :height="280"
                    :label="__('Classes')" />
            </div>

            <div class="col-12 col-xl-5">
                <section class="card edu-card h-100" aria-labelledby="recommended-classes-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="recommended-classes-title">
                                <i class="bi bi-stars me-1 text-edu" aria-hidden="true"></i>
                                {{ __('Recommended for you') }}
                            </h2>
                            <div class="small text-muted">{{ __('From teachers and subjects you follow.') }}</div>
                        </div>
                        <a href="{{ route('classes.discover') }}" class="small fw-semibold text-decoration-none">
                            {{ __('Discover') }}
                        </a>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($recommendedClasses as $class)
                            <div class="edu-list-row">
                                <span class="edu-activity-icon bg-primary" aria-hidden="true">
                                    <i class="bi bi-easel"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <a href="{{ route('classes.show', $class) }}" class="edu-list-title text-truncate">
                                        {{ $class->title }}
                                    </a>
                                    <div class="edu-list-meta">
                                        {{ $class->teacher?->name ?? __('Unknown teacher') }}
                                        @if ($class->subject)
                                            <span class="mx-1" aria-hidden="true">·</span>{{ $class->subject }}
                                        @endif
                                    </div>
                                    <div class="edu-list-meta">
                                        <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                        {{ $class->date?->format('D d M') }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        {{ \Illuminate\Support\Str::substr((string) $class->time, 0, 5) }}
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('classes.enroll', $class) }}" class="flex-shrink-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary"
                                            aria-label="{{ __('Enrol in :title', ['title' => $class->title]) }}">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;" aria-hidden="true">
                                    <i class="bi bi-compass"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('Nothing to suggest yet') }}</div>
                                <div class="text-muted small mb-3">{{ __('Follow a teacher or join a class to get recommendations.') }}</div>
                                <a href="{{ route('classes.discover') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-search" aria-hidden="true"></i> {{ __('Browse classes') }}
                                </a>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        {{-- Three-post feed preview --}}
        <div class="row g-3 g-xl-4">
            <div class="col-12">
                <section class="card edu-card" aria-labelledby="feed-preview-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="feed-preview-title">
                                <i class="bi bi-rss me-1 text-edu" aria-hidden="true"></i>
                                {{ __('From people you follow') }}
                            </h2>
                            <div class="small text-muted">{{ __('The newest posts in your feed.') }}</div>
                        </div>
                        <a href="{{ route('feed.index') }}" class="small fw-semibold text-decoration-none">
                            {{ __('Open full feed') }} <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                        </a>
                    </div>

                    <div class="card-body py-2">
                        @php
                            $posts = $feedPreview instanceof \Illuminate\Pagination\LengthAwarePaginator
                                ? $feedPreview->getCollection()
                                : $feedPreview;
                        @endphp

                        @forelse ($posts as $post)
                            @php
                                $displayBody = trim(strip_tags((string) $post->body));
                                if (trim((string) $post->title) !== '') {
                                    $displayBody = trim((string) $post->title).' — '.$displayBody;
                                }
                            @endphp

                            <article class="edu-feed-item">
                                <x-avatar :user="$post->user" size="sm" class="flex-shrink-0" />

                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <a href="{{ route('users.show', $post->user) }}"
                                           class="fw-semibold small text-decoration-none text-reset">
                                            {{ $post->user?->name ?? __('Deleted user') }}
                                        </a>
                                        <span class="edu-list-meta">{{ $post->created_at?->diffForHumans() }}</span>
                                    </div>

                                    <a href="{{ route('posts.show', $post) }}"
                                       class="text-decoration-none text-reset d-block">
                                        <p class="edu-feed-body mb-1">{{ \Illuminate\Support\Str::limit($displayBody, 180) }}</p>
                                    </a>

                                    <div class="edu-list-meta">
                                        <i class="bi bi-heart me-1" aria-hidden="true"></i>{{ $post->likes_count ?? 0 }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        <i class="bi bi-chat me-1" aria-hidden="true"></i>{{ $post->comments_count ?? 0 }}
                                    </div>
                                </div>

                                <a href="{{ route('posts.show', $post) }}" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                   aria-label="{{ __('Open this post') }}">
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </article>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;" aria-hidden="true">
                                    <i class="bi bi-rss"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('Your feed is quiet') }}</div>
                                <div class="text-muted small mb-3">{{ __('Follow teachers and classmates to see their posts here.') }}</div>
                                <a href="{{ route('search.index') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-search" aria-hidden="true"></i> {{ __('Find people') }}
                                </a>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
