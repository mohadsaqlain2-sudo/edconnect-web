{{-- Flash + validation summary messages. Used by every layout via <x-flash /> --}}

@if (session('success'))
    {{-- Green banner after a successful action --}}
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    {{-- Red banner for a refused action --}}
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('status'))
    {{-- Neutral informational banner (used by Breeze for "verify your email") --}}
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-1"></i> {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@php($bag = $errors ?? null)
@if ($bag && $bag->any())
    {{-- Summary of every validation error of the last request.
         $errors is only shared by the "web" middleware group, so it is missing
         on error pages such as 404 - hence the null-safe check above. --}}
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ __('Please fix the following:') }}
        </div>
        <ul class="mb-0 small">
            @foreach ($bag->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
