<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        {{-- viewport-fit=cover lets the layout own the notch/home-indicator
             area, which the responsive layer fills with env(safe-area-inset-*). --}}
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        <title>{{ $attributes->get('title') ? $attributes->get('title').' · ' : '' }}{{ config('app.name', 'EduConnect') }}</title>

        {{-- Apply the saved theme before the first paint (same key as the app layout). --}}
        <script>
            (function () {
                try {
                    var stored = window.localStorage.getItem('educonnect.theme');
                    var theme = stored === 'dark' || stored === 'light'
                        ? stored
                        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-bs-theme', theme);
                } catch (error) {
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                }
            })();
        </script>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        {{-- Bootstrap 5 + Bootstrap Icons compiled by Vite. --}}
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body class="edu-auth-body">
        <main class="min-vh-100">
            <div class="row g-0 min-vh-100">
                {{-- Branded panel: desktop and tablet only. --}}
                <section class="col-lg-6 d-none d-lg-flex edu-auth-brand p-5 flex-column justify-content-between"
                         aria-label="{{ __('About EduConnect') }}">
                    <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none">
                        <span class="fs-2" aria-hidden="true">
                            <x-application-logo class="text-white" />
                        </span>
                        <span class="h4 fw-bold mb-0">{{ config('app.name', 'EduConnect') }}</span>
                    </a>

                    <div class="my-5">
                        <h1 class="display-6 fw-bold mb-3">{{ __('Learn together, wherever you are.') }}</h1>
                        <p class="lead text-white-50 mb-4">
                            {{ __('Live classes, a friendly social feed and the tools teachers need to keep every student close.') }}
                        </p>

                        <ul class="list-unstyled d-grid gap-3 mb-0">
                            <li class="d-flex gap-3">
                                <span class="edu-auth-icon" aria-hidden="true"><i class="bi bi-camera-video"></i></span>
                                <span>
                                    <strong class="d-block">{{ __('Live video classes') }}</strong>
                                    <span class="small text-white-50">{{ __('Join a class from any browser, no extra app required.') }}</span>
                                </span>
                            </li>
                            <li class="d-flex gap-3">
                                <span class="edu-auth-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                                <span>
                                    <strong class="d-block">{{ __('A real school community') }}</strong>
                                    <span class="small text-white-50">{{ __('Follow teachers, share posts and learn together.') }}</span>
                                </span>
                            </li>
                            <li class="d-flex gap-3">
                                <span class="edu-auth-icon" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                                <span>
                                    <strong class="d-block">{{ __('Safe by design') }}</strong>
                                    <span class="small text-white-50">{{ __('Reporting and moderation tools protect every learner.') }}</span>
                                </span>
                            </li>
                        </ul>
                    </div>

                    <p class="small text-white-50 mb-0">
                        &copy; {{ date('Y') }} {{ config('app.name', 'EduConnect') }}
                    </p>
                </section>

                {{-- Form panel: full width on phones, half width on desktop. --}}
                <section class="col-12 col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5">
                    <div class="w-100 edu-auth-form">
                        {{-- Compact brand for phones and small tablets. --}}
                        <div class="d-lg-none text-center mb-4">
                            <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                                <span class="fs-2 text-primary" aria-hidden="true">
                                    <x-application-logo />
                                </span>
                                <span class="h4 fw-bold text-body mb-0">{{ config('app.name', 'EduConnect') }}</span>
                            </a>
                            <p class="text-muted small mb-0 mt-1">{{ __('Learn together, anywhere.') }}</p>
                        </div>

                        <div class="card border-0 shadow-sm edu-auth-card">
                            <div class="card-body p-4 p-md-5">
                                {{-- Flash messages and the validation summary. --}}
                                <x-flash />

                                {{ $slot }}
                            </div>
                        </div>

                        <p class="text-center text-muted small mt-4 mb-0">
                            {{ __('Learn together, anywhere.') }}
                        </p>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
