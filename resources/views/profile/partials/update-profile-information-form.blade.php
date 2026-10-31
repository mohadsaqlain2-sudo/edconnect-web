<section>
    <h2 class="h6 fw-bold mb-1" id="profile-information-title">{{ __('Profile information') }}</h2>
    <p class="text-muted small mb-4">{{ __('Update your photo, name, email and bio.') }}</p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    {{-- enctype is required for the photo upload (FR-4). --}}
    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data"
          data-photo-form>
        @csrf
        @method('patch')

        {{-- Current photo and live preview --}}
        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mb-4">
            <img id="photo-preview"
                 src="{{ old('remove_photo') ? asset('images/default-avatar.svg') : $user->photoUrl() }}"
                 alt="{{ $user->name }}"
                 class="profile-photo-preview"
                 data-photo-preview
                 data-default-src="{{ asset('images/default-avatar.svg') }}"
                 data-current-src="{{ $user->photoUrl() }}">

            <div class="flex-grow-1">
                <x-input-label for="photo" :value="__('Profile photo')" />
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
                       class="form-control {{ $errors->has('photo') ? 'is-invalid' : '' }}"
                       data-photo-input>
                <div class="form-text">{{ __('JPG, PNG or WEBP - max 2 MB. The preview updates before you save.') }}</div>
                <x-input-error class="mt-1" :messages="$errors->get('photo')" />

                @if ($user->photo)
                    {{-- Removing the uploaded photo falls back to the default avatar. --}}
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_photo"
                               id="remove_photo" value="1" data-photo-remove
                               @checked(old('remove_photo'))>
                        <label class="form-check-label small" for="remove_photo">
                            {{ __('Remove current photo') }}
                        </label>
                    </div>
                @endif
            </div>
        </div>

        <div class="mb-3">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)"
                          :class="$errors->has('name') ? 'is-invalid' : ''"
                          required autofocus autocomplete="name" />
            <x-input-error class="mt-1" :messages="$errors->get('name')" />
        </div>

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autocomplete="username" />
            <x-input-error class="mt-1" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="small mt-2">
                    <p class="mb-1 text-muted">{{ __('Your email address is unverified.') }}</p>

                    <button form="send-verification" class="btn btn-link btn-sm p-0">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="text-success mt-2 mb-0">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Subject: only teachers have one (FR-4). --}}
        @if ($user->isTeacher())
            <div class="mb-3">
                <x-input-label for="subject" :value="__('Subject you teach')" />
                <x-text-input id="subject" name="subject" type="text" :value="old('subject', $user->subject)"
                              :class="$errors->has('subject') ? 'is-invalid' : ''"
                              placeholder="{{ __('e.g. Mathematics') }}" />
                <x-input-error class="mt-1" :messages="$errors->get('subject')" />
            </div>
        @endif

        <div class="mb-3">
            <x-input-label for="bio" :value="__('Bio')" />
            <textarea id="bio" name="bio" rows="3" maxlength="1000"
                      class="form-control {{ $errors->has('bio') ? 'is-invalid' : '' }}"
                      placeholder="{{ __('Tell people a little about yourself.') }}">{{ old('bio', $user->bio) }}</textarea>
            <div class="form-text d-flex justify-content-between gap-2">
                <span>{{ __('Max 1000 characters.') }}</span>
                <span data-count-target="bio" aria-live="polite"></span>
            </div>
            <x-input-error class="mt-1" :messages="$errors->get('bio')" />
        </div>

        <div class="d-flex align-items-center gap-3">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <span class="text-success small">{{ __('Saved.') }}</span>
            @endif
        </div>
    </form>
</section>
