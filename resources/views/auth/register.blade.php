<x-guest-layout :title="__('Create your account')">
    @php
        $requestedRole = request()->query('role');
        $selectedRole = in_array(old('role'), ['teacher', 'student'], true)
            ? old('role')
            : (in_array($requestedRole, ['teacher', 'student'], true) ? $requestedRole : 'student');
    @endphp

    <h1 class="h4 fw-bold mb-1">{{ __('Create your account') }}</h1>
    <p class="text-muted small mb-4">{{ __('Join as a teacher or as a student. You can switch later from your profile.') }}</p>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <!-- Name -->
        <div class="mb-3">
            <x-input-label for="name" :value="__('Full name')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')"
                          :class="$errors->has('name') ? 'is-invalid' : ''"
                          required autofocus autocomplete="name"
                          placeholder="Maria Rossi" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <!-- Email -->
        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autocomplete="username"
                          placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Role: only Teacher or Student can ever be posted. -->
        <fieldset class="mb-3" data-role-select>
            <legend class="form-label mb-2">{{ __('I am a') }}</legend>

            <div class="row g-2">
                @foreach ([
                    'student' => [
                        'icon' => 'bi-mortarboard',
                        'title' => __('Student'),
                        'copy' => __('Follow teachers and enroll in live classes.'),
                    ],
                    'teacher' => [
                        'icon' => 'bi-easel',
                        'title' => __('Teacher'),
                        'copy' => __('Publish classes and share posts with your students.'),
                    ],
                ] as $value => $card)
                    <div class="col-12 col-sm-6">
                        <label class="edu-role-card w-100 h-100 {{ $selectedRole === $value ? 'is-selected' : '' }}">
                            <input type="radio" name="role" value="{{ $value }}"
                                   data-role-radio
                                   @checked($selectedRole === $value) required>

                            <span class="edu-role-icon" aria-hidden="true">
                                <i class="bi {{ $card['icon'] }}"></i>
                            </span>

                            <span class="min-w-0">
                                <span class="d-block fw-semibold">{{ $card['title'] }}</span>
                                <span class="d-block small text-muted">{{ $card['copy'] }}</span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="form-text">{{ __('Admin accounts are created by the platform.') }}</div>
            <x-input-error :messages="$errors->get('role')" class="mt-1" />
        </fieldset>

        <!-- Optional subject, revealed for teachers by auth.js. -->
        <div class="mb-3" data-subject-field @if ($selectedRole !== 'teacher') hidden @endif>
            <x-input-label for="subject" :value="__('Subject you teach (optional)')" />
            <x-text-input id="subject" type="text" name="subject" :value="old('subject')"
                          placeholder="{{ __('e.g. Mathematics') }}"
                          maxlength="100"
                          :class="$errors->has('subject') ? 'is-invalid' : ''" />
            <x-input-error :messages="$errors->get('subject')" class="mt-1" />
        </div>

        <!-- Password with show/hide toggle and strength meter -->
        <x-password-input name="password" :label="__('Password')" autocomplete="new-password" :strength="true" />

        <!-- Confirm password -->
        <x-password-input name="password_confirmation" :label="__('Confirm password')" autocomplete="new-password" />

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-person-plus me-1" aria-hidden="true"></i>
            {{ __('Create account') }}
        </x-primary-button>

        <p class="text-center small mt-4 mb-0">
            {{ __('Already registered?') }}
            <a class="text-decoration-none fw-semibold" href="{{ route('login') }}">{{ __('Log in') }}</a>
        </p>
    </form>
</x-guest-layout>
