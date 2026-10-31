{{-- Renders a list of post cards. Used by the feed (initial page + "Load more"),
     the hashtag page and the public profile (FR-7 ... FR-13).

     $commentsByPost is supplied by the feed so comments can be expanded in
     place. Other pages leave it out and the cards simply link to the detail
     page. --}}
@php
    // null on the profile and hashtag pages: the card then links to the
    // detail page instead of opening an inline comment section.
    $commentsByPost = $commentsByPost ?? null;
@endphp

@forelse ($posts as $post)
    <div class="mb-3">
        <x-post-card
            :post="$post"
            :comments="$commentsByPost?->get($post->id, collect())"
        />
    </div>
@empty
    <div class="card edu-card">
        <div class="card-body text-center py-5">
            <div class="edu-empty-illustration mx-auto" aria-hidden="true">
                <i class="bi bi-inbox"></i>
            </div>
            <h2 class="h6 fw-bold mb-1">{{ __('No posts to show yet.') }}</h2>

            @if (! empty($emptyMessage))
                <p class="text-muted small mb-3">{{ $emptyMessage }}</p>
            @endif

            @if (! empty($emptyActionUrl))
                <a href="{{ $emptyActionUrl }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1" aria-hidden="true"></i>
                    {{ $emptyActionLabel ?? __('Explore') }}
                </a>
            @endif
        </div>
    </div>
@endforelse
