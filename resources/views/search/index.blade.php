<x-app-layout>
    <x-slot name="title">{{ __('Search') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Search') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-search me-1 text-edu" aria-hidden="true"></i>{{ __('Search') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Find people, hashtags and posts in one place.') }}</p>
            </div>
            <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to feed') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                {{-- The same search box as in the navbar, with live suggestions. --}}
                <form method="GET" action="{{ route('search.index') }}" role="search" class="mb-3 position-relative"
                      data-search-form data-search-url="{{ route('search.suggest') }}">
                    <input type="hidden" name="tab" value="{{ $tab }}">

                    <label for="search-q" class="visually-hidden">{{ __('Search') }}</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-body border-end-0" aria-hidden="true">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="search" name="q" id="search-q" class="form-control border-start-0"
                               value="{{ $term }}"
                               placeholder="{{ __('Search people, #hashtags or post text') }}"
                               autocomplete="off" data-search-input>
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search me-1" aria-hidden="true"></i> {{ __('Search') }}
                        </button>
                    </div>

                    <div class="dropdown-menu show position-absolute top-100 start-0 end-0 shadow d-none"
                         data-search-suggestions></div>
                </form>

                @if ($term !== '')
                    <p class="small text-muted mb-2">
                        {{ __('Results for') }} <strong class="text-body">“{{ $term }}”</strong>
                    </p>
                @endif

                {{-- Three tabs: people, hashtags and posts (FR-14). --}}
                <ul class="nav nav-tabs mb-3">
                    @foreach ([
                        'users' => ['bi-people', __('People')],
                        'hashtags' => ['bi-hash', __('Hashtags')],
                        'posts' => ['bi-chat-square-text', __('Posts')],
                    ] as $value => [$icon, $label])
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === $value ? 'active' : '' }}"
                               href="{{ route('search.index', ['q' => $term, 'tab' => $value]) }}"
                               @if ($tab === $value) aria-current="page" @endif>
                                <i class="bi {{ $icon }} me-1" aria-hidden="true"></i> {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if (mb_strlen($term) < 2)
                    <div class="alert alert-info mb-0" role="status">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        {{ $term === ''
                            ? __('Type a name, a subject, a hashtag or some post text to start searching.')
                            : __('Type at least two characters to search.') }}
                    </div>
                @elseif ($tab === 'hashtags')
                    {{-- ----- Hashtag results ----- --}}
                    @if ($hashtags->isEmpty())
                        <x-empty-state icon="bi-hash"
                                       :title="__('No hashtags found')"
                                       :message="__('No hashtag matches \':term\'. Try a shorter word or browse the feed.', ['term' => $term])">
                            <a href="{{ route('feed.index') }}" class="btn btn-primary">{{ __('Back to the feed') }}</a>
                        </x-empty-state>
                    @else
                        <p class="text-muted small">
                            {{ trans_choice(':count hashtag found|:count hashtags found', $hashtags->total(), ['count' => $hashtags->total()]) }}
                        </p>

                        <div class="card edu-card">
                            <div class="list-group list-group-flush">
                                @foreach ($hashtags as $hashtag)
                                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2"
                                       href="{{ route('hashtags.show', $hashtag->name) }}">
                                        <span class="fw-semibold min-w-0 text-break">
                                            <i class="bi bi-hash text-edu" aria-hidden="true"></i>{!! $highlight($hashtag->name) !!}
                                        </span>
                                        <span class="edu-chip flex-shrink-0">
                                            {{ trans_choice(':count post|:count posts', $hashtag->posts_count, ['count' => $hashtag->posts_count]) }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-3">
                            <x-pagination-links :paginator="$hashtags" />
                        </div>
                    @endif
                @elseif ($tab === 'posts')
                    {{-- ----- Post results ----- --}}
                    @if ($posts->isEmpty())
                        <x-empty-state icon="bi-chat-square-text"
                                       :title="__('No posts found')"
                                       :message="__('No post matches \':term\'. Try another word or a hashtag.', ['term' => $term])">
                            <a href="{{ route('search.index', ['q' => $term, 'tab' => 'hashtags']) }}" class="btn btn-primary">
                                {{ __('Search hashtags instead') }}
                            </a>
                        </x-empty-state>
                    @else
                        <p class="text-muted small">
                            {{ trans_choice(':count post found|:count posts found', $posts->total(), ['count' => $posts->total()]) }}
                        </p>

                        <div class="d-flex flex-column gap-3">
                            @foreach ($posts as $post)
                                <x-post-card :post="$post" />
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <x-pagination-links :paginator="$posts" />
                        </div>
                    @endif
                @else
                    {{-- ----- People results ----- --}}
                    @if ($users->isEmpty())
                        <x-empty-state icon="bi-person-x"
                                       :title="__('No people found')"
                                       :message="__('No user matches \':term\'. Check the spelling or try a subject instead.', ['term' => $term])">
                            <a href="{{ route('search.index', ['q' => $term, 'tab' => 'hashtags']) }}" class="btn btn-primary">
                                {{ __('Search hashtags instead') }}
                            </a>
                        </x-empty-state>
                    @else
                        <p class="text-muted small">
                            {{ trans_choice(':count person found|:count people found', $users->total(), ['count' => $users->total()]) }}
                        </p>

                        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                            @foreach ($users as $user)
                                <div class="col">
                                    {{-- The shared user card, with a link to the public profile
                                         and a Follow button (FR-11, FR-17). --}}
                                    <x-user-card :user="$user" :name-html="$highlight($user->name)">
                                        <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-primary w-100">
                                            <i class="bi bi-person me-1" aria-hidden="true"></i> {{ __('View profile') }}
                                        </a>
                                        @include('users._follow-button', ['user' => $user, 'followingIds' => $followingIds])
                                    </x-user-card>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <x-pagination-links :paginator="$users" />
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
