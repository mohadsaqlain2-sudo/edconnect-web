<x-guest-layout>
    <h1 class="h4 fw-bold mb-1">{{ __('Welcome back') }}</h1>
    <p class="text-muted small mb-4">{{ __('Log in to reach your classes and feed.') }}</p>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <!-- Email -->
        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autofocus autocomplete="username"
                          placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Password with show/hide toggle -->
        <x-password-input name="password" :label="__('Password')" autocomplete="current-password" />

        <!-- Remember me -->
        <div class="form-check mb-4">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember"
                   @checked(old('remember'))>
            <label for="remember_me" class="form-check-label small">{{ __('Remember me') }}</label>
        </div>

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
            {{ __('Log in') }}
        </x-primary-button>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4">
            @if (Route::has('password.request'))
                <a class="small text-decoration-none" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            @if (Route::has('register'))
                <a class="small text-decoration-none" href="{{ route('register') }}">
                    {{ __('Create an account') }}
                </a>
            @endif
        </div>
    </form>
</x-guest-layout>
