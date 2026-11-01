{{-- A user (teacher or student) as a compact card.
     Usage: <x-user-card :user="$teacher"> <button>Follow</button> </x-user-card> --}}
@props(['user', 'nameHtml' => null])

<div class="card edu-card border-0 h-100">
    <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center gap-3 mb-2">
            @if ($user->photo)
                {{-- Escaped attribute value; the photo lives on the public disk --}}
                <img src="{{ $user->photoUrl() }}" alt="{{ $user->name }}" class="avatar" width="40" height="40" loading="lazy" decoding="async">
            @else
                {{-- Fallback: a circle with the first letter of the name --}}
                <span class="avatar bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fw-bold"
                      role="img" aria-label="{{ $user->name }}">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
                </span>
            @endif

            <div>
                <div class="fw-semibold">{!! $nameHtml ?? e($user->name) !!}</div>
                <div class="small text-muted d-flex align-items-center flex-wrap gap-1">
                    {{-- A real badge, so the role is recognisable at a glance (FR-14). --}}
                    <span class="badge rounded-pill text-bg-light border text-capitalize fw-normal">
                        <i class="bi bi-person-badge me-1" aria-hidden="true"></i>{{ $user->role }}
                    </span>
                    @if ($user->subject)
                        <span>{{ $user->subject }}</span>
                    @endif
                </div>
            </div>
        </div>

        @if ($user->bio)
            <p class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($user->bio, 100) }}</p>
        @endif

        @if (isset($user->followers_count))
            {{-- The follow AJAX refreshes this number in place (FR-11). --}}
            <div class="small text-muted mb-2">
                <i class="bi bi-people me-1"></i>
                <span data-followers-count data-user-id="{{ $user->id }}"
                      data-label-singular="{{ __('follower') }}"
                      data-label-plural="{{ __('followers') }}">{{ $user->followers_count }}</span>
                <span data-follower-label>{{ trans_choice('follower|followers', $user->followers_count) }}</span>
            </div>
        @endif

        {{-- Action buttons supplied by the page --}}
        <div class="mt-auto pt-2 d-flex flex-wrap gap-2">
            {{ $slot }}
        </div>
    </div>
</div>
