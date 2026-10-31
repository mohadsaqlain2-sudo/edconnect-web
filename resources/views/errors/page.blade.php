{{-- Shared friendly error page shell (Phase 3C).
     Variables: $code, $title, $message, $icon.
     Every error page keeps a way forward: home, plus the dashboard or login. --}}
@php
    $code = $code ?? '404';
    $title = $title ?? __('Something went wrong');
    $message = $message ?? __('Please try again in a moment.');
    $icon = $icon ?? 'bi-emoji-frown';
@endphp

<x-guest-layout :title="$title">
    <div class="text-center py-2">
        <span class="edu-empty-illustration mx-auto" aria-hidden="true">
            <i class="bi {{ $icon }}"></i>
        </span>

        <div class="display-5 fw-bold text-edu lh-1 mt-2">{{ $code }}</div>
        <h1 class="h4 fw-bold mt-2 mb-2">{{ $title }}</h1>

        <p class="text-muted small edu-measure mx-auto mb-4">{{ $message }}</p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="bi bi-house me-1" aria-hidden="true"></i>{{ __('Back to home') }}
            </a>

            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>{{ __('Go to dashboard') }}
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>{{ __('Log in') }}
                </a>
            @endauth
        </div>

        <p class="small text-muted mt-4 mb-0">
            {{ __('Still stuck? Contact the administrator and mention code :code.', ['code' => $code]) }}
        </p>
    </div>
</x-guest-layout>
