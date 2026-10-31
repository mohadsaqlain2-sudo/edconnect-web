{{--
    Branded password reset email (FR-3).
    Kept intentionally simple: one heading, one button, plain text fallback.
--}}
<x-mail::message>
# Reset your password

Hello {{ $name }},

We received a request to reset the password for your EduConnect account.

<x-mail::button :url="$url" color="primary">
Choose a new password
</x-mail::button>

This link expires in {{ $expires }} minutes. If you did not ask for a reset, you can ignore this email — your password will not change.

Thanks,<br>
{{ config('app.name', 'EduConnect') }}
</x-mail::message>
