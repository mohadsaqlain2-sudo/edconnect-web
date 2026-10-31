<x-guest-layout>
    <div class="text-center mb-3">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary p-3 fs-4"
              aria-hidden="true">
            <i class="bi bi-envelope-check"></i>
        </span>
    </div>

    <h1 class="h4 fw-bold mb-1 text-center">{{ __('Verify your email') }}</h1>
    <p class="text-muted small mb-4 text-center">
        {{ __('Thanks for signing up! Please click the link we just emailed you to verify your address.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success py-2 small" role="status">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-send me-1" aria-hidden="true"></i>
            {{ __('Resend verification email') }}
        </x-primary-button>
    </form>

    <p class="text-center small mt-4 mb-0">
        {{ __('Wrong account?') }}
    </p>

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">
            {{ __('Log out') }}
        </button>
    </form>
</x-guest-layout>
