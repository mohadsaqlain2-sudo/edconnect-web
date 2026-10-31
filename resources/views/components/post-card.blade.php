{{-- A single feed post (FR-7, FR-8, FR-9, FR-10, FR-12, FR-13).
     Buttons are real forms, so everything still works without JavaScript;
     resources/js/feed.js upgrades them to fetch() calls.

     Usage:
       <x-post-card :post="$post" />
       <x-post-card :post="$post" :comments="$comments" />  (feed: expandable) --}}
@props([
    'post',
    'comments' => null,
])

@php
    // Injected so the body can be turned into hashtag links (FR-13).
    $hashtagService = app(\App\Services\HashtagService::class);

    $author = $post->user;
    // Prefer the eager loaded counts so the component never fires a query itself.
    $likesCount = $post->likes_count ?? $post->likes()->count();
    $commentsCount = $post->comments_count ?? $post->comments()->visible()->count();
    // "liked by me" comes from the withExists() in Post::scopeWithFeedData().
    $likedByMe = (bool) ($post->liked_by_me ?? $post->isLikedBy(auth()->user()));
    $isMine = auth()->id() === $post->user_id;

    // On the feed three comments are enough; the rest live on the detail page.
    $previewComments = $comments?->take(3) ?? collect();

    // Colour of the little role badge next to the name.
    $roleColor = match ($author?->role) {
        \App\Models\User::ROLE_ADMIN => 'dark',
        \App\Models\User::ROLE_TEACHER => 'primary',
        default => 'success',
    };

    // Intrinsic image size (cached) so the card reserves its height before the
    // photo loads. The upload service already caps post images at 1600px.
    $imageDimensions = $post->imageDimensions();
    $imageUrl = $post->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) : null;
@endphp

<article class="card edu-card" data-post-card="{{ $post->id }}">
    <div class="card-body">
        {{-- Author, role badge and publishing time --}}
        <header class="d-flex align-items-start gap-3 mb-2">
            <x-avatar :user="$author" size="md" class="flex-shrink-0" />

            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if ($author)
                        <a href="{{ route('users.show', $author) }}"
                           class="fw-semibold text-decoration-none text-reset">{{ $author->name }}</a>
                    @else
                        <span class="fw-semibold">{{ __('Deleted user') }}</span>
                    @endif

                    <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $author?->role }}</span>

                    @if ($post->is_hidden)
                        {{-- Hidden by an administrator (FR-28) --}}
                        <span class="badge text-bg-warning">{{ __('hidden') }}</span>
                    @endif
                </div>

                <div class="small text-muted">
                    <a href="{{ route('posts.show', $post) }}" class="text-muted text-decoration-none">
                        <time datetime="{{ $post->created_at?->toIso8601String() }}">
                            {{ $post->created_at?->diffForHumans() }}
                        </time>
                    </a>
                </div>
            </div>

            {{-- Owner menu (edit / delete) + report action (FR-8, FR-32) --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-link text-muted px-1" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Post actions') }}">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @can('update', $post)
                        <li>
                            <a class="dropdown-item" href="{{ route('posts.edit', $post) }}">
                                <i class="bi bi-pencil me-1"></i> {{ __('Edit post') }}
                            </a>
                        </li>
                    @endcan

                    @can('delete', $post)
                        <li>
                            {{-- Opens the Bootstrap confirmation modal rendered below. --}}
                            <button type="button" class="dropdown-item text-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#delete-post-{{ $post->id }}">
                                <i class="bi bi-trash me-1"></i> {{ __('Delete post') }}
                            </button>
                        </li>
                    @endcan

                    {{-- Reporting a post that belongs to somebody else (FR-32). --}}
                    @if (auth()->id() !== $post->user_id)
                        <li>
                            <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                    data-bs-target="#report-post-{{ $post->id }}">
                                <i class="bi bi-flag me-1"></i> {{ __('Report post') }}
                            </button>
                        </li>
                    @endif
                </ul>
            </div>
        </header>

        {{-- Body: escaped first, hashtags linked afterwards - so no XSS (FR-13) --}}
        <p class="post-body mb-2">{!! $hashtagService->linkify($post->body) !!}</p>

        @if ($post->image)
            <img src="{{ $imageUrl }}"
                 @if ($imageDimensions)
                     width="{{ $imageDimensions['width'] }}"
                     height="{{ $imageDimensions['height'] }}"
                     srcset="{{ $imageUrl }} {{ $imageDimensions['width'] }}w"
                     sizes="(max-width: 575.98px) 100vw, (max-width: 991.98px) 60vw, 720px"
                 @endif
                 alt="{{ __('Image attached to the post') }}"
                 class="img-fluid rounded-3 mb-2 edu-post-image"
                 loading="lazy" decoding="async">
        @endif

        {{-- Counters + actions --}}
        <footer class="d-flex align-items-center gap-2 gap-sm-3 small border-top pt-2 mt-3">
            {{-- Like: a form, so it still works without JavaScript --}}
            <form method="POST"
                  action="{{ $likedByMe ? route('posts.unlike', $post) : route('posts.like', $post) }}"
                  data-like-form
                  data-liked="{{ $likedByMe ? '1' : '0' }}"
                  data-like-url="{{ route('posts.like', $post) }}"
                  data-unlike-url="{{ route('posts.unlike', $post) }}"
                  class="d-inline">
                @csrf
                @if ($likedByMe)
                    @method('DELETE')
                @endif
                <button type="submit"
                        class="btn btn-sm {{ $likedByMe ? 'btn-danger' : 'btn-outline-secondary' }}"
                        data-like-button aria-pressed="{{ $likedByMe ? 'true' : 'false' }}"
                        aria-label="{{ $likedByMe ? __('Unlike this post') : __('Like this post') }}"
                        title="{{ $likedByMe ? __('Unlike') : __('Like') }}">
                    <i class="bi {{ $likedByMe ? 'bi-heart-fill' : 'bi-heart' }} me-1"
                       aria-hidden="true"></i>
                    <span data-like-count>{{ $likesCount }}</span>
                </button>
            </form>

            @if ($comments !== null)
                {{-- Expandable comments, exclusively on the feed (FR-10). --}}
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="collapse"
                        data-bs-target="#post-comments-{{ $post->id }}"
                        aria-expanded="false"
                        aria-controls="post-comments-{{ $post->id }}"
                        title="{{ __('Comments') }}">
                    <i class="bi bi-chat me-1" aria-hidden="true"></i>
                    <span data-comment-count>{{ $commentsCount }}</span>
                </button>
            @else
                <a href="{{ route('posts.show', $post) }}#comments"
                   class="btn btn-sm btn-outline-secondary" title="{{ __('Comments') }}">
                    <i class="bi bi-chat me-1" aria-hidden="true"></i>
                    <span data-comment-count>{{ $commentsCount }}</span>
                </a>
            @endif

            <span class="ms-auto text-muted">
                @if ($isMine)
                    <i class="bi bi-person-check me-1" aria-hidden="true"></i>{{ __('Your post') }}
                @endif
            </span>
        </footer>
    </div>

    @if ($comments !== null)
        {{-- Latest three comments + the composer, without leaving the feed. --}}
        <div class="collapse" id="post-comments-{{ $post->id }}">
            <div class="card-footer bg-transparent border-top pt-3">
                <div data-comment-region>
                    <form method="POST" action="{{ route('comments.store', $post) }}"
                          data-comment-form class="mb-2">
                        @csrf
                        <label for="comment-body-{{ $post->id }}" class="visually-hidden">
                            {{ __('Your comment') }}
                        </label>
                        <textarea name="body" id="comment-body-{{ $post->id }}" rows="2"
                                  maxlength="280" required
                                  class="form-control form-control-sm mb-2"
                                  placeholder="{{ __('Write a comment...') }}"></textarea>
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div class="form-text mt-0">{{ __('Up to 280 characters.') }}</div>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-send me-1" aria-hidden="true"></i> {{ __('Comment') }}
                            </button>
                        </div>
                    </form>

                    <div class="mt-2" data-comment-list>
                        @forelse ($previewComments as $comment)
                            @include('comments._comment', ['comment' => $comment])
                        @empty
                            <p class="text-muted small mb-0" data-comment-empty>
                                {{ __('No comments yet. Be the first one!') }}
                            </p>
                        @endforelse
                    </div>

                    @if ($commentsCount > $previewComments->count())
                        <a href="{{ route('posts.show', $post) }}#comments"
                           class="small fw-semibold text-decoration-none d-inline-block mt-2">
                            {{ trans_choice('View all :count comment|View all :count comments', $commentsCount, ['count' => $commentsCount]) }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Extra content supplied by the page (report form, moderation tools...) --}}
    @if ($slot->isNotEmpty())
        <div class="card-footer bg-transparent border-top-0 pt-0">
            {{ $slot }}
        </div>
    @endif
</article>

@can('delete', $post)
    <x-confirm-delete-modal
        :id="'delete-post-'.$post->id"
        :action="route('posts.destroy', $post)"
        :title="__('Delete this post?')"
        :message="__('This removes the post, its comments, likes and image. This cannot be undone.')"
        :confirm-label="__('Delete post')"
        :show-trigger="false" />
@endcan

{{-- Each post owns its report modal (FR-32). --}}
@if (auth()->id() !== $post->user_id)
    @include('reports._modal', ['reportable' => $post])
@endif
