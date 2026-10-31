<x-app-layout>
    <x-slot name="title">{{ __('Feed') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Feed') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-house-door me-1 text-edu"></i> {{ __('Feed') }}
                </h1>
                <div class="small text-muted">{{ __('Posts from you and the people you follow.') }}</div>
            </div>
            <a href="{{ route('posts.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square me-1"></i> {{ __('New post') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row g-4 justify-content-center">
            {{-- Main column: composer + feed --}}
            <div class="col-12 col-lg-8">
                {{-- Compose box (FR-7) --}}
                <section class="card edu-card mb-4" aria-labelledby="compose-title">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <x-avatar :user="auth()->user()" size="md" class="flex-shrink-0" />
                            <div>
                                <h2 class="h6 fw-bold mb-0" id="compose-title">{{ __('Share something') }}</h2>
                                <div class="small text-muted">{{ __('Your next post could help somebody learn.') }}</div>
                            </div>
                        </div>

                        @include('posts._form', ['post' => null])
                    </div>
                </section>

                {{-- Posts of the people I follow + my own (FR-12) --}}
                <div data-feed-list>
                    @include('feed._posts', [
                        'posts' => $posts,
                        'commentsByPost' => $commentsByPost,
                        'emptyMessage' => __('Follow a few teachers or classmates to fill this page with their posts.'),
                        'emptyActionUrl' => route('search.index', ['tab' => 'users']),
                        'emptyActionLabel' => __('Find people to follow'),
                    ])
                </div>

                {{-- Progressive enhancement: without JavaScript the button is a
                     normal link to ?page=2, with it the cards are appended (FR-12). --}}
                <div class="text-center my-4">
                    <button type="button" class="btn btn-outline-primary"
                            data-load-more
                            data-url="{{ $posts->nextPageUrl() }}"
                            @if (! $posts->hasMorePages()) hidden @endif>
                        <i class="bi bi-arrow-down-circle me-1"></i> {{ __('Load more') }}
                    </button>
                </div>
            </div>

            {{-- Right sidebar widgets --}}
            <aside class="col-12 col-lg-4">
                @unless (auth()->user()->isAdmin())
                    <section class="card edu-card mb-3" aria-labelledby="who-to-follow-title">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h2 class="h6 fw-bold mb-0" id="who-to-follow-title">
                                    <i class="bi bi-person-plus me-1 text-edu" aria-hidden="true"></i>
                                    {{ __('Who to follow') }}
                                </h2>
                                <div class="small text-muted">
                                    {{ __('Following') }}
                                    <span data-viewer-following-count>{{ $followingCount }}</span>
                                    {{ __('people') }}
                                </div>
                            </div>
                            <a href="{{ route('search.index', ['tab' => 'users']) }}"
                               class="small fw-semibold text-decoration-none">{{ __('See all') }}</a>
                        </div>

                        <div class="card-body py-2">
                            @forelse ($suggestedUsers as $person)
                                <div class="edu-widget-user">
                                    <x-avatar :user="$person" size="sm" class="flex-shrink-0" />

                                    <div class="flex-grow-1 min-w-0">
                                        <a href="{{ route('users.show', $person) }}"
                                           class="edu-list-title text-truncate d-block">{{ $person->name }}</a>
                                        <div class="edu-list-meta text-capitalize">
                                            {{ $person->role }}
                                            @if ($person->subject)
                                                <span class="mx-1" aria-hidden="true">·</span>{{ $person->subject }}
                                            @endif
                                        </div>
                                        <div class="edu-list-meta">
                                            <i class="bi bi-people me-1" aria-hidden="true"></i>
                                            {{ trans_choice(':count follower|:count followers', $person->followers_count, ['count' => $person->followers_count]) }}
                                        </div>
                                    </div>

                                    <div class="flex-shrink-0">
                                        @include('users._follow-button', ['user' => $person, 'followingIds' => $followingIds])
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <div class="edu-empty-illustration mx-auto mb-2"
                                         style="width: 52px; height: 52px; font-size: 1.3rem; border-radius: 16px;"
                                         aria-hidden="true">
                                        <i class="bi bi-check2-circle"></i>
                                    </div>
                                    <div class="fw-semibold small">{{ __('You follow everybody') }}</div>
                                    <div class="text-muted small">{{ __('Check the search page for new people.') }}</div>
                                </div>
                            @endforelse
                        </div>

                        <div class="card-footer bg-transparent">
                            <a href="{{ route('search.index', ['tab' => 'users']) }}"
                               class="small fw-semibold text-decoration-none">
                                {{ __('Find more people') }} <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                            </a>
                        </div>
                    </section>
                @endunless

                <section class="card edu-card" aria-labelledby="trending-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="trending-title">
                            <i class="bi bi-fire me-1 text-edu" aria-hidden="true"></i>
                            {{ __('Trending hashtags') }}
                        </h2>
                        <div class="small text-muted">{{ __('The five busiest tags from the last 7 days.') }}</div>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($trendingHashtags as $tag)
                            <a href="{{ route('hashtags.show', $tag->name) }}" class="edu-trending-row">
                                <span class="edu-trending-rank" aria-hidden="true">#</span>
                                <span class="flex-grow-1 min-w-0">
                                    <span class="edu-list-title d-block text-truncate">{{ $tag->name }}</span>
                                    <span class="edu-list-meta">
                                        {{ trans_choice(':count post this week|:count posts this week', $tag->recent_posts_count, ['count' => $tag->recent_posts_count]) }}
                                    </span>
                                </span>
                                <i class="bi bi-arrow-up-right text-muted small" aria-hidden="true"></i>
                            </a>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto mb-2"
                                     style="width: 52px; height: 52px; font-size: 1.3rem; border-radius: 16px;"
                                     aria-hidden="true">
                                    <i class="bi bi-hash"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('No trends yet') }}</div>
                                <div class="text-muted small">{{ __('Post with a #hashtag to start one.') }}</div>
                            </div>
                        @endforelse
                    </div>

                    <div class="card-footer bg-transparent">
                        <a href="{{ route('search.index', ['tab' => 'hashtags']) }}"
                           class="small fw-semibold text-decoration-none">
                            {{ __('Browse hashtags') }} <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                        </a>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
