<section>
    <h2 class="h6 fw-bold mb-1 text-danger" id="delete-account-title">{{ __('Delete account') }}</h2>
    <p class="text-muted small mb-4">
        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}
    </p>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-4">
            @csrf
            @method('delete')

            <h4 class="h6 fw-bold">{{ __('Are you sure you want to delete your account?') }}</h4>

            <p class="text-muted small">
                {{ __('Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mb-3">
                <x-input-label for="password" value="{{ __('Password') }}" class="visually-hidden" />
                <x-text-input id="password" name="password" type="password"
                              :class="$errors->userDeletion->has('password') ? 'is-invalid' : ''"
                              placeholder="{{ __('Password') }}" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1" />
            </div>

            <div class="d-flex justify-content-end gap-2">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button>{{ __('Delete account') }}</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
