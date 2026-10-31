<x-app-layout>
    <x-slot name="title">{{ __('Post') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('feed.index') }}">{{ __('Feed') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Post') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-chat-square-text me-1 text-edu"></i> {{ __('Post') }}
                </h1>
                <div class="small text-muted">{{ __('Read the conversation and join in.') }}</div>
            </div>
            <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('Back to feed') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                {{-- The post and its comments share one counter scope, so an
                     AJAX add/delete updates every number on this page at once. --}}
                <div data-comment-scope="{{ $post->id }}">
                    <x-post-card :post="$post" />

                    {{-- Comments (FR-10) --}}
                    <section class="card edu-card mt-4" id="comments" aria-labelledby="comments-title">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2 class="h6 fw-bold mb-0" id="comments-title">
                                <i class="bi bi-chat me-1 text-edu" aria-hidden="true"></i>
                                {{ __('Comments') }}
                            </h2>
                            <span class="badge bg-body-secondary text-body border">
                                <span data-comment-count>{{ $post->comments_count }}</span>
                            </span>
                        </div>

                        <div class="card-body">
                            <div data-comment-region>
                                {{-- Add a comment: fetch() when JavaScript is on, normal POST otherwise. --}}
                                <form method="POST" action="{{ route('comments.store', $post) }}"
                                      data-comment-form class="mb-3">
                                    @csrf
                                    <label for="comment-body" class="visually-hidden">{{ __('Your comment') }}</label>
                                    <textarea name="body" id="comment-body" rows="2" maxlength="280" required
                                              class="form-control @error('body') is-invalid @enderror"
                                              placeholder="{{ __('Write a comment...') }}">{{ old('body') }}</textarea>
                                    @error('body')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                                        <div class="form-text mt-0">{{ __('Up to 280 characters.') }}</div>
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-send me-1" aria-hidden="true"></i> {{ __('Comment') }}
                                        </button>
                                    </div>
                                </form>

                                {{-- New comments are appended here by feed.js --}}
                                <div data-comment-list>
                                    @forelse ($comments as $comment)
                                        @include('comments._comment', ['comment' => $comment])
                                    @empty
                                        <p class="text-muted small mb-0" data-comment-empty>
                                            {{ __('No comments yet. Be the first one!') }}
                                        </p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        @if ($comments->hasPages())
                            <div class="card-footer bg-transparent">
                                <x-pagination-links :paginator="$comments" />
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
