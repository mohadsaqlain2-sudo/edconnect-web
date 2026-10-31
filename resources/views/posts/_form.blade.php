{{-- Shared "write a post" form, used by the feed composer and by
     posts/create + posts/edit (FR-9, FR-10).
     $post is null for a new post and a Post model while editing.
     resources/js/feed.js updates the character ring and the image preview. --}}
@php
    $post = $post ?? null;
    $editing = $post !== null;
    $value = old('body', $post?->body);
    $length = \Illuminate\Support\Str::length((string) $value);
@endphp

<form method="POST"
      action="{{ $editing ? route('posts.update', $post) : route('posts.store') }}"
      enctype="multipart/form-data"
      class="d-flex flex-column gap-2">
    @csrf
    @if ($editing)
        @method('PATCH')
    @endif

    <div>
        <label for="body" class="form-label fw-semibold">
            {{ $editing ? __('Edit your post') : __('What do you want to share?') }}
        </label>

        <textarea name="body" id="body" rows="{{ $editing ? 4 : 3 }}"
                  maxlength="280" required
                  data-post-body
                  class="form-control @error('body') is-invalid @enderror"
                  placeholder="{{ __('Share an update, a tip or a question. Use #hashtags.') }}">{{ $value }}</textarea>

        @error('body')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
            {{-- A small, text-only toolbar: no emoji picker, no hidden state. --}}
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-insert-hashtag
                        title="{{ __('Add a hashtag') }}" aria-label="{{ __('Add a hashtag') }}">
                    <i class="bi bi-hash" aria-hidden="true"></i>
                </button>

                <label for="image" class="btn btn-sm btn-outline-secondary mb-0">
                    <i class="bi bi-image me-1" aria-hidden="true"></i>
                    <span>{{ __('Image') }}</span>
                </label>
            </div>

            {{-- Live character counter, updated by resources/js/feed.js --}}
            <div class="d-flex align-items-center gap-2">
                <span class="form-text mt-0">{{ __('280 max') }}</span>
                <span class="edu-char-ring {{ $length >= 224 ? 'is-warning' : '' }} {{ $length >= 280 ? 'is-full' : '' }}"
                      data-char-ring
                      role="progressbar"
                      aria-label="{{ __('Characters used') }}"
                      aria-valuemin="0" aria-valuemax="280" aria-valuenow="{{ $length }}"
                      aria-valuetext="{{ $length }}/280"
                      style="--edu-ring-progress: {{ min(360, (int) round($length / 280 * 360)) }}deg;">
                    <span data-char-counter aria-live="polite">{{ $length }}</span>
                </span>
            </div>
        </div>
    </div>

    <div>
        {{-- The actual file input stays visually hidden; the label above opens it. --}}
        <input type="file" name="image" id="image"
               accept="image/jpeg,image/png,image/webp"
               data-image-input
               class="visually-hidden @error('image') is-invalid @enderror">
        @error('image')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <div class="form-text">{{ __('JPG, PNG or WebP, up to 2 MB.') }}</div>

        {{-- Local preview before the upload (no request, no layout jump). --}}
        <div class="mt-2" data-image-preview hidden>
            <img src="" alt="{{ __('Preview of the selected image') }}"
                 class="img-fluid rounded-3 border" style="max-height: 180px;">
        </div>

        @if ($editing && $post->image)
            <div class="mt-2">
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}"
                     alt="{{ __('Current image') }}" class="img-fluid rounded-3 border" style="max-height: 160px;"
                     loading="lazy" decoding="async">
                <div class="form-text">{{ __('Uploading a new image replaces the current one.') }}</div>
            </div>
        @endif
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-send me-1" aria-hidden="true"></i>
            {{ $editing ? __('Save changes') : __('Publish') }}
        </button>

        @if ($editing)
            <a href="{{ route('posts.show', $post) }}" class="btn btn-outline-secondary">
                {{ __('Cancel') }}
            </a>
        @endif
    </div>
</form>
