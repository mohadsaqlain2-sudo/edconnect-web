{{-- One comment under a post (FR-13).
     Included by posts/show and returned as HTML by CommentController@store. --}}
@php
    $author = $comment->user;

    $roleColor = match ($author?->role) {
        \App\Models\User::ROLE_ADMIN => 'dark',
        \App\Models\User::ROLE_TEACHER => 'primary',
        default => 'success',
    };
@endphp

<div class="d-flex gap-3 py-3 border-bottom" data-comment-id="{{ $comment->id }}">
    @if ($author?->photo)
        <img src="{{ $author->photoUrl() }}" alt="{{ $author->name }}" class="avatar" loading="lazy" decoding="async">
    @else
        <span class="avatar bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0"
              role="img" aria-label="{{ $author?->name ?? __('Deleted user') }}">
            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) $author?->name, 0, 1)) }}
        </span>
    @endif

    <div class="flex-grow-1">
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if ($author)
                <a href="{{ route('users.show', $author) }}"
                   class="fw-semibold small text-decoration-none text-reset">{{ $author->name }}</a>
            @else
                <span class="fw-semibold small">{{ __('Deleted user') }}</span>
            @endif

            <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $author?->role }}</span>

            <span class="text-muted small">{{ $comment->created_at?->diffForHumans() }}</span>
        </div>

        {{-- Comment text is escaped by Blade (no HTML allowed). --}}
        <div class="post-body small">{{ $comment->body }}</div>
    </div>

    @can('delete', $comment)
        {{-- AJAX with a normal POST fallback; CommentPolicy protects the route. --}}
        <form method="POST" action="{{ route('comments.destroy', $comment) }}"
              data-delete-comment-form
              data-confirm="{{ __('Delete this comment?') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-link text-danger px-1" title="{{ __('Delete comment') }}">
                <i class="bi bi-trash"></i>
            </button>
        </form>
    @endcan

    {{-- Report somebody else's comment (FR-32). --}}
    @if (auth()->id() !== $comment->user_id)
        <button type="button" class="btn btn-sm btn-link text-muted px-1"
                data-bs-toggle="modal" data-bs-target="#report-comment-{{ $comment->id }}"
                title="{{ __('Report comment') }}" aria-label="{{ __('Report comment') }}">
            <i class="bi bi-flag"></i>
        </button>
    @endif
</div>

@if (auth()->id() !== $comment->user_id)
    @include('reports._modal', ['reportable' => $comment])
@endif
