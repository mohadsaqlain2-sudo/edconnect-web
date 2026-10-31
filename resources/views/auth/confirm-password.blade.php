<x-guest-layout>
    <h1 class="h4 fw-bold mb-1">{{ __('Confirm your password') }}</h1>
    <p class="text-muted small mb-4">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf

        <x-password-input name="password" :label="__('Password')" autocomplete="current-password" autofocus />

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
            {{ __('Confirm') }}
        </x-primary-button>
    </form>
</x-guest-layout>
