<x-app-layout>
    <x-slot name="title">#{{ $hashtag->name }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('feed.index') }}">{{ __('Feed') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">#{{ $hashtag->name }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-hash text-edu" aria-hidden="true"></i>{{ $hashtag->name }}
                </h1>
                <p class="small text-muted mb-0">
                    {{-- e.g. "3 posts have this tag" --}}
                    {{ trans_choice(':count post has this tag.|:count posts have this tag.', $posts->total(), ['count' => $posts->total()]) }}
                </p>
            </div>
            <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to feed') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                <section class="card edu-card mb-3">
                    <div class="card-body d-flex flex-wrap align-items-center gap-3">
                        <span class="edu-hashtag-hero" aria-hidden="true">#</span>

                        <div class="min-w-0">
                            <h2 class="h4 fw-bold mb-0 text-break">#{{ $hashtag->name }}</h2>
                            <p class="small text-muted mb-0">
                                {{ __('Every visible post that uses this hashtag, newest first.') }}
                            </p>
                        </div>

                        <span class="edu-chip ms-auto">
                            <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                            {{ trans_choice(':count post|:count posts', $posts->total(), ['count' => $posts->total()]) }}
                        </span>
                    </div>
                </section>

                {{-- Every visible post carrying this tag (FR-15) --}}
                @include('feed._posts', [
                    'posts' => $posts,
                    'emptyMessage' => __('No visible post uses this hashtag yet.'),
                    'emptyActionUrl' => route('feed.index'),
                    'emptyActionLabel' => __('Go to the feed'),
                ])

                <div class="mt-3">
                    <x-pagination-links :paginator="$posts" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
