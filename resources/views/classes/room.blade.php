{{-- The Join Class page: Jitsi Meet embedded through the External API (FR-18, FR-20, FR-22).
     The script URL and the domain come from config/jitsi.php, so a self hosted
     Jitsi server only needs a change in .env (JITSI_DOMAIN).
     Everything the JavaScript needs travels in data-* attributes: no inline script. --}}
<x-app-layout>
    <x-slot name="title">{{ $classRoom->title }} · {{ __('Live room') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.show', $classRoom) }}">{{ \Illuminate\Support\Str::limit($classRoom->title, 40) }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Live room') }}</li>
    </x-slot>

    <div class="container-fluid px-3 px-lg-4">
        {{-- Slim top bar: identity on the left, room state and exit on the right. --}}
        <div class="card edu-card mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <span class="edu-auth-icon flex-shrink-0" aria-hidden="true">
                        <i class="bi bi-camera-video"></i>
                    </span>

                    <div class="min-w-0">
                        <h1 class="h6 fw-bold mb-0 text-break">{{ $classRoom->title }}</h1>
                        <div class="small text-muted text-break">
                            <i class="bi bi-person-video3 me-1" aria-hidden="true"></i>{{ $classRoom->teacher?->name }}
                            @if ($classRoom->subject)
                                <span class="mx-1" aria-hidden="true">&middot;</span>{{ $classRoom->subject }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if ($classRoom->isLive())
                        <span class="badge text-bg-danger">
                            <span class="edu-live-dot" aria-hidden="true"></span> {{ __('Live') }}
                        </span>
                    @else
                        <x-status-badge :status="$classRoom->status" />
                    @endif

                    {{-- Only the teacher moderates the room (FR-20) --}}
                    @if ($isModerator)
                        <span class="badge text-bg-dark">
                            <i class="bi bi-shield-check me-1" aria-hidden="true"></i> {{ __('Moderator') }}
                        </span>
                    @else
                        <span class="badge text-bg-success">{{ __('Student') }}</span>
                    @endif

                    <button type="button" class="btn btn-sm btn-outline-secondary d-lg-none"
                            data-bs-toggle="collapse" data-bs-target="#room-info"
                            aria-expanded="false" aria-controls="room-info">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i> {{ __('Info') }}
                    </button>

                    <a href="{{ route('classes.show', $classRoom) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to the class') }}
                    </a>
                </div>
            </div>
        </div>

        @if (! $classRoom->isLive() && $classRoom->teacher_id === auth()->id())
            <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    {{ __('The class is not live yet, so students cannot join. Press "Start the class for the students" when you are ready.') }}
                </span>
            </div>
        @endif

        {{-- The Jitsi room ------------------------------------------------------------------
             data-jitsi is picked up by resources/js/jitsi.js, which loads the
             External API and asks it to build the iframe inside data-jitsi-container. --}}
        <div id="edu-jitsi"
             data-jitsi
             data-domain="{{ $domain }}"
             data-script-url="{{ $scriptUrl }}"
             data-room="{{ $roomName }}"
             data-display-name="{{ $displayName }}"
             data-email="{{ $email }}"
             data-moderator="{{ $isModerator ? '1' : '0' }}"
             data-dashboard-url="{{ route('dashboard') }}">

            {{-- Shown while the API and the room are loading --}}
            <div class="alert bg-body-secondary text-body border d-flex align-items-center gap-2" data-jitsi-status>
                <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                <span>{{ __('Connecting to the room...') }}</span>
            </div>

            {{-- Shown when the External API cannot be loaded (offline, blocked, ...) --}}
            <div class="alert alert-danger d-none" data-jitsi-error role="alert">
                <div class="fw-semibold mb-1">
                    <i class="bi bi-wifi-off me-1" aria-hidden="true"></i> {{ __('The video room could not be loaded.') }}
                </div>
                <p class="small mb-2">
                    {{ __('Check your internet connection and try again. If you are on a school network, the video server might be blocked.') }}
                </p>
                <button type="button" class="btn btn-sm btn-outline-danger" data-jitsi-retry>
                    <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i> {{ __('Try again') }}
                </button>
            </div>

            {{-- Jitsi builds the iframe in here --}}
            <div class="jitsi-frame" data-jitsi-container></div>
        </div>

        {{-- Collapsible room info: moderation controls and help, out of the way. --}}
        <div class="collapse d-lg-block mt-3" id="room-info">
            <div class="row g-3">
                {{-- Only the teacher can start or end the class (FR-24). --}}
                @if ($isModerator && ($classRoom->teacher_id === auth()->id()))
                    <div class="col-12 col-lg-7">
                        <div class="card edu-card h-100">
                            <div class="card-body">
                                <h2 class="h6 fw-bold">{{ __('Class controls') }}</h2>

                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($classRoom->allowedTransitions() as $next)
                                        <form method="POST" action="{{ route('classes.status', $classRoom) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $next }}">
                                            <button type="submit"
                                                    class="btn btn-sm {{ $next === \App\Models\ClassRoom::STATUS_LIVE ? 'btn-success' : 'btn-outline-danger' }}">
                                                @if ($next === \App\Models\ClassRoom::STATUS_LIVE)
                                                    <i class="bi bi-broadcast me-1" aria-hidden="true"></i> {{ __('Start the class for the students') }}
                                                @elseif ($next === \App\Models\ClassRoom::STATUS_COMPLETED)
                                                    <i class="bi bi-stop-circle me-1" aria-hidden="true"></i> {{ __('End the class') }}
                                                @else
                                                    <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> {{ __('Back to scheduled') }}
                                                @endif
                                            </button>
                                        </form>
                                    @endforeach
                                </div>

                                <p class="small text-muted mb-0 mt-3">
                                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
                                    {{ __('Room') }}: <code class="text-break">{{ $classRoom->room_name }}</code>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-12 {{ $isModerator && ($classRoom->teacher_id === auth()->id()) ? 'col-lg-5' : '' }}">
                    <div class="card edu-card h-100">
                        <div class="card-body">
                            <h2 class="h6 fw-bold">{{ __('Inside the room') }}</h2>

                            <ul class="list-unstyled small text-muted mb-0 d-flex flex-column gap-2">
                                <li>
                                    <i class="bi bi-chat-dots me-1" aria-hidden="true"></i>
                                    {{ __('Use the chat button inside the room to send a message to everybody (FR-22).') }}
                                </li>
                                @if ($isModerator)
                                    <li>
                                        <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                                        {{ __('As the moderator you can mute participants and remove them from the room.') }}
                                    </li>
                                @endif
                                <li>
                                    <i class="bi bi-mic-mute me-1" aria-hidden="true"></i>
                                    {{ __('Your microphone starts muted; unmute when you are ready to speak.') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
