<x-app-layout>
    <x-slot name="title">{{ __('Teacher dashboard') }}</x-slot>

    <div class="container-xxl px-3 px-lg-4 edu-dashboard">
        @php
            $hour = (int) now()->format('G');
            $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
            $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
        @endphp

        <header class="edu-greeting">
            <div>
                <span class="edu-eyebrow">
                    <i class="bi bi-easel" aria-hidden="true"></i>
                    {{ __('Teaching workspace') }}
                </span>
                <h1>{{ $greeting }}, {{ $firstName }}</h1>
                <p>{{ __('Your classes, students and community at a glance.') }}</p>
            </div>

            <div class="edu-quick-actions">
                <a href="{{ route('classes.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Create class') }}
                </a>
                <a href="{{ route('classes.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-collection" aria-hidden="true"></i> {{ __('All my classes') }}
                </a>
            </div>
        </header>

        {{-- Next class hero --}}
        @if ($nextClass)
            <section class="edu-hero mb-4" aria-labelledby="next-class-title">
                <div class="row align-items-center g-4">
                    <div class="col-12 col-lg-7">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="edu-hero-label small fw-semibold text-uppercase" style="letter-spacing: .1em;">
                                {{ __('Next class') }}
                            </span>
                            @if ($nextClass->isLive())
                                <span class="edu-hero-live">
                                    <span class="edu-live-dot" aria-hidden="true"></span> {{ __('Live now') }}
                                </span>
                            @endif
                        </div>

                        <h1 class="h2 fw-bold mb-2" id="next-class-title">{{ $nextClass->title }}</h1>

                        <div class="edu-hero-meta d-flex flex-wrap gap-3 mb-3">
                            @if ($nextClass->subject)
                                <span><i class="bi bi-book me-1" aria-hidden="true"></i>{{ $nextClass->subject }}</span>
                            @endif
                            <span>
                                <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                {{ $nextClass->date?->format('D d M Y') }}
                            </span>
                            <span>
                                <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                {{ \Illuminate\Support\Str::substr((string) $nextClass->time, 0, 5) }}
                                &middot; {{ $nextClass->duration }} {{ __('min') }}
                            </span>
                        </div>

                        @if ($nextClass->isLive())
                            <div class="edu-chip">
                                <span class="edu-live-dot" aria-hidden="true"></span>
                                {{ __('Students can join the room now') }}
                            </div>
                        @else
                            <x-countdown :target="$nextClass->startsAt()->toIso8601String()" />
                        @endif

                        <div class="d-flex align-items-center gap-2 mt-3">
                            <x-avatar-stack :users="$nextClass->students" :limit="5" />
                            <span class="small" style="color: rgba(255,255,255,.8);">
                                {{ trans_choice(':count student enrolled|:count students enrolled', $nextClass->students_count, ['count' => $nextClass->students_count]) }}
                            </span>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                            @if ($nextClass->isLive())
                                <a href="{{ route('classes.room', $nextClass) }}" class="btn btn-light btn-lg">
                                    <i class="bi bi-camera-video" aria-hidden="true"></i> {{ __('Join live') }}
                                </a>
                            @else
                                <form method="POST" action="{{ route('classes.status', $nextClass) }}"
                                      data-confirm="{{ __('Start this class now? Students will be able to join.') }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="live">
                                    <button type="submit" class="btn btn-light btn-lg">
                                        <i class="bi bi-broadcast" aria-hidden="true"></i> {{ __('Start class') }}
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('classes.show', $nextClass) }}" class="btn btn-outline-light btn-lg">
                                {{ __('Details') }}
                            </a>
                        </div>

                        <div class="text-lg-end mt-3">
                            <a href="{{ route('classes.students', $nextClass) }}"
                               class="small text-decoration-none" style="color: rgba(255,255,255,.78);">
                                <i class="bi bi-people me-1" aria-hidden="true"></i>
                                {{ __('See who is enrolled') }}
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
                            <h2 class="h5 fw-bold mb-1">{{ __('No class scheduled yet') }}</h2>
                            <p class="text-muted small mb-0">{{ __('Create your first live class and invite your students.') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('classes.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Create a class') }}
                    </a>
                </div>
            </section>
        @endif

        {{-- Teacher counters --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('My classes')" :value="$stats['classes']" icon="bi-easel" color="primary"
                    :trend="$trends['classes']['percent']" :trend-direction="$trends['classes']['direction']"
                    :trend-label="__('vs last month')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Upcoming classes')" :value="$stats['upcoming']" icon="bi-calendar-check" color="success"
                    :hint="__('Scheduled and still ahead')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Enrolled students')" :value="$stats['students']" icon="bi-people" color="info"
                    :trend="$trends['students']['percent']" :trend-direction="$trends['students']['direction']"
                    :trend-label="__('new enrollments vs last month')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Followers')" :value="$stats['followers']" icon="bi-person-hearts" color="warning"
                    :trend="$trends['followers']['percent']" :trend-direction="$trends['followers']['direction']"
                    :trend-label="__('vs last month')" />
            </div>
        </div>

        {{-- Charts --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xl-7">
                <x-chart-card :title="__('Enrollments per class')" :subtitle="__('Your most popular classes')"
                    chart-id="teacher-enrollments" kind="bar" series-key="enrollments" color="primary"
                    :series="$enrollmentChart" :total="$enrollmentChart['total']"
                    :endpoint="route('teacher.dashboard.charts')" current-range="12m" :height="280"
                    :label="__('Students')" />
            </div>

            <div class="col-12 col-xl-5">
                <x-chart-card :title="__('Follower growth')" :subtitle="__('People who started following you')"
                    chart-id="teacher-followers" kind="line" series-key="followers" color="secondary"
                    :series="$followerChart" :total="$followerChart['total']"
                    :endpoint="route('teacher.dashboard.charts')" current-range="12m" :height="280"
                    :label="__('Followers')" />
            </div>
        </div>

        {{-- Weekly schedule --}}
        <section class="card edu-card mb-4" aria-labelledby="weekly-schedule-title">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h6 fw-bold mb-0" id="weekly-schedule-title">
                        <i class="bi bi-calendar-week me-1 text-edu" aria-hidden="true"></i>
                        {{ __('This week') }}
                    </h2>
                    <div class="small text-muted">{{ __('Classes per day for the next seven days.') }}</div>
                </div>
                <a href="{{ route('classes.index') }}" class="small fw-semibold text-decoration-none">
                    {{ __('Open calendar') }}
                </a>
            </div>
            <div class="card-body">
                <div class="edu-schedule" role="list" aria-label="{{ __('Seven day schedule') }}">
                    @foreach ($schedule as $day)
                        <div class="edu-schedule-day {{ $day['isToday'] ? 'is-today' : '' }} {{ $day['count'] > 0 ? 'has-classes' : '' }}"
                             role="listitem"
                             aria-label="{{ $day['date']->format('l d M') }}: {{ trans_choice(':count class|:count classes', $day['count'], ['count' => $day['count']]) }}">
                            <span class="edu-schedule-weekday">{{ $day['weekday'] }}</span>
                            <span class="edu-schedule-date">{{ $day['day'] }}</span>
                            <span class="edu-schedule-count">{{ $day['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Classes table + activity timeline --}}
        <div class="row g-3 g-xl-4">
            <div class="col-12 col-xl-8">
                <x-data-table id="teacher-classes" :title="__('My classes')"
                    :subtitle="__('Search, sort and manage your classes')"
                    :count="$classes->total()" count-label=":count classes"
                    :search-placeholder="__('Search my classes…')">
                    <x-slot name="head">
                        <tr>
                            <th scope="col" data-sort>{{ __('Title') }}</th>
                            <th scope="col" data-sort>{{ __('Date') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                            <th scope="col" data-sort>{{ __('Enrolled') }}</th>
                            <th scope="col" class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </x-slot>

                    @forelse ($classes as $class)
                        <tr data-table-row
                            data-search="{{ $class->title }} {{ $class->subject }} {{ $class->status }} {{ $class->date?->format('d M Y') }}">
                            <td>
                                <a href="{{ route('classes.show', $class) }}"
                                   class="fw-semibold text-decoration-none text-reset d-block">
                                    {{ $class->title }}
                                </a>
                                @if ($class->subject)
                                    <div class="small text-muted">{{ $class->subject }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap" data-sort-value="{{ $class->date?->timestamp }}">
                                {{ $class->date?->format('d M Y') }}
                                <div class="small text-muted">
                                    {{ \Illuminate\Support\Str::substr((string) $class->time, 0, 5) }}
                                </div>
                            </td>
                            <td>
                                <x-status-badge :status="$class->status" />
                            </td>
                            <td style="min-width: 136px;">
                                <x-progress-bar :value="$class->students_count" :max="$maxEnrollment"
                                    :label="trans_choice(':count enrolled|:count enrolled', $class->students_count, ['count' => $class->students_count])" />
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false"
                                            aria-label="{{ __('Actions for :title', ['title' => $class->title]) }}">
                                        <i class="bi bi-three-dots" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if ($class->isLive())
                                            <li>
                                                <a class="dropdown-item" href="{{ route('classes.room', $class) }}">
                                                    <i class="bi bi-camera-video me-1" aria-hidden="true"></i>
                                                    {{ __('Join live') }}
                                                </a>
                                            </li>
                                        @elseif ($class->canTransitionTo(\App\Models\ClassRoom::STATUS_LIVE))
                                            <li>
                                                <form method="POST" action="{{ route('classes.status', $class) }}"
                                                      data-confirm="{{ __('Start this class now? Students will be able to join.') }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="live">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-broadcast me-1" aria-hidden="true"></i>
                                                        {{ __('Start class') }}
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item" href="{{ route('classes.students', $class) }}">
                                                <i class="bi bi-people me-1" aria-hidden="true"></i>
                                                {{ __('Students') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('classes.edit', $class) }}">
                                                <i class="bi bi-pencil me-1" aria-hidden="true"></i>
                                                {{ __('Edit') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('classes.show', $class) }}">
                                                <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>
                                                {{ __('View details') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-table-row data-search="">
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1" aria-hidden="true"></i>
                                {{ __('You have not created a class yet.') }}
                            </td>
                        </tr>
                    @endforelse

                    <x-slot name="footer">
                        <x-pagination-links :paginator="$classes" />
                    </x-slot>
                </x-data-table>
            </div>

            <div class="col-12 col-xl-4">
                <section class="card edu-card h-100" aria-labelledby="recent-activity-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="recent-activity-title">
                            <i class="bi bi-activity me-1 text-edu" aria-hidden="true"></i>
                            {{ __('Recent activity') }}
                        </h2>
                        <div class="small text-muted">{{ __('Likes, comments, enrollments and followers.') }}</div>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($activity as $item)
                            <x-activity-item :icon="$item['icon']" :color="$item['color']"
                                :text="$item['text']" :excerpt="$item['excerpt']" :at="$item['at']" />
                        @empty
                            <div class="text-center text-muted py-4 small">
                                <i class="bi bi-activity me-1" aria-hidden="true"></i>
                                {{ __('Nothing has happened yet.') }}
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
