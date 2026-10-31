@props(['value'])

<label {{ $attributes->merge(['class' => 'form-label fw-semibold small']) }}>
    {{ $value ?? $slot }}
</label>
