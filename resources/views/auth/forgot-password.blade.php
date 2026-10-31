<x-guest-layout>
    <h1 class="h4 fw-bold mb-1">{{ __('Forgot your password?') }}</h1>
    <p class="text-muted small mb-4">
        {{ __('No problem. Tell us your email address and we will send you a link to choose a new password.') }}
    </p>

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="mb-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autofocus autocomplete="username"
                          placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-envelope me-1" aria-hidden="true"></i>
            {{ __('Email password reset link') }}
        </x-primary-button>

        <p class="text-center small mt-4 mb-0">
            <a class="text-decoration-none" href="{{ route('login') }}">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                {{ __('Back to login') }}
            </a>
        </p>
    </form>
</x-guest-layout>
