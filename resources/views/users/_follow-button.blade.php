{{-- Follow / unfollow button (FR-11).
     Students and teachers can follow each other. Admin accounts are not
     followable, and nobody gets a button on their own profile. Admins do not
     use the follow routes, so they do not see the button either.
     It is a real form: without JavaScript the browser simply posts it. --}}
@php
    $viewer = auth()->user();
    $isFollowing = $followingIds->contains($user->id);
    $canFollow = $viewer
        && ! $viewer->isAdmin()
        && ! $viewer->is($user)
        && ! $user->isAdmin();
@endphp

@if ($canFollow)
    <form method="POST"
          action="{{ $isFollowing ? route('follows.destroy', $user) : route('follows.store', $user) }}"
          data-follow-form
          data-follow-url="{{ route('follows.store', $user) }}"
          data-unfollow-url="{{ route('follows.destroy', $user) }}"
          data-following="{{ $isFollowing ? '1' : '0' }}"
          data-follow-user-id="{{ $user->id }}"
          data-label-follow="{{ __('Follow') }}"
          data-label-following="{{ __('Following') }}"
          class="d-inline">
        @csrf
        @if ($isFollowing)
            @method('DELETE')
        @endif
        <button type="submit" class="btn btn-sm {{ $isFollowing ? 'btn-outline-secondary' : 'btn-primary' }}"
                data-follow-button aria-pressed="{{ $isFollowing ? 'true' : 'false' }}">
            <i class="bi {{ $isFollowing ? 'bi-person-check' : 'bi-person-plus' }} me-1"></i>
            <span data-follow-label>{{ $isFollowing ? __('Following') : __('Follow') }}</span>
        </button>
    </form>
@endif
