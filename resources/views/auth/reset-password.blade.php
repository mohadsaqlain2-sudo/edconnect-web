<x-guest-layout>
    <h1 class="h4 fw-bold mb-1">{{ __('Choose a new password') }}</h1>
    <p class="text-muted small mb-4">{{ __('Pick something long and hard to guess.') }}</p>

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf

        <!-- Reset token (comes from the emailed link) -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <x-password-input name="password" :label="__('New password')" autocomplete="new-password" />

        <x-password-input name="password_confirmation" :label="__('Confirm new password')" autocomplete="new-password" />

        <p class="form-text mt-0 mb-4">{{ __('Use at least 8 characters.') }}</p>

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-key me-1" aria-hidden="true"></i>
            {{ __('Reset password') }}
        </x-primary-button>

        <p class="text-center small mt-4 mb-0">
            <a class="text-decoration-none" href="{{ route('login') }}">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                {{ __('Back to login') }}
            </a>
        </p>
    </form>
</x-guest-layout>
