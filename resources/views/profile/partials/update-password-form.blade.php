<section>
    <h2 class="h6 fw-bold mb-1" id="update-password-title">{{ __('Update password') }}</h2>
    <p class="text-muted small mb-4">{{ __('Use a long, random password to stay secure.') }}</p>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-3">
            <x-input-label for="update_password_current_password" :value="__('Current password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password"
                          :class="$errors->updatePassword->has('current_password') ? 'is-invalid' : ''"
                          autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password" :value="__('New password')" />
            <x-text-input id="update_password_password" name="password" type="password"
                          :class="$errors->updatePassword->has('password') ? 'is-invalid' : ''"
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password"
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="d-flex align-items-center gap-3">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <span class="text-success small">{{ __('Saved.') }}</span>
            @endif
        </div>
    </form>
</section>
