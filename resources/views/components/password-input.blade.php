{{--
    Password field with an accessible show/hide toggle (FR-1, FR-2, FR-3).
    The toggle is wired by resources/js/auth.js, so it also works without Alpine.
    Usage: <x-password-input name="password" :label="__('Password')" />
--}}
@props([
    'name',
    'label',
    'autocomplete' => 'current-password',
    'required' => true,
    'autofocus' => false,
    'strength' => false,
])

<div class="mb-3">
    <x-input-label :for="$name" :value="$label" />

    <div class="input-group">
        <input id="{{ $name }}"
               name="{{ $name }}"
               type="password"
               @if ($required) required @endif
               @if ($autofocus) autofocus @endif
               autocomplete="{{ $autocomplete }}"
               @if ($strength) data-password-strength-input @endif
               @class(['form-control', 'is-invalid' => $errors->has($name)])
               {{ $attributes }}>

        <button type="button"
                class="btn btn-outline-secondary password-toggle"
                data-password-toggle="{{ $name }}"
                data-show-label="{{ __('Show password') }}"
                data-hide-label="{{ __('Hide password') }}"
                aria-label="{{ __('Show password') }}"
                aria-pressed="false">
            <i class="bi bi-eye" aria-hidden="true"></i>
        </button>
    </div>

    @if ($strength)
        {{-- Progressive enhancement: auth.js fills this in. Without JavaScript
             the server-side rule ("at least 8 characters") still applies. --}}
        <div class="edu-strength" data-password-strength data-score="0"
             data-strength-empty="{{ __('Use at least 8 characters, with a number or a symbol.') }}"
             data-strength-weak="{{ __('Weak password — add more characters.') }}"
             data-strength-fair="{{ __('Fair password — mix letters, numbers and a symbol.') }}"
             data-strength-good="{{ __('Good password — almost there.') }}"
             data-strength-strong="{{ __('Strong password. Well done!') }}"
             data-strength-input>
            <div class="edu-strength-track" aria-hidden="true">
                <span class="edu-strength-seg"></span>
                <span class="edu-strength-seg"></span>
                <span class="edu-strength-seg"></span>
                <span class="edu-strength-seg"></span>
            </div>
            <div class="small text-muted" data-password-strength-label aria-live="polite">
                {{ __('Use at least 8 characters, with a number or a symbol.') }}
            </div>
        </div>
    @endif

    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
