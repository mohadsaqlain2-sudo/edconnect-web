{{-- The Join Class page: the application's own WebRTC room (FR-18, FR-20, FR-22).
     The browser captures the camera/microphone and resources/js/live-room.js
     talks to our own signaling API; no external video server is loaded.
     Everything the JavaScript needs travels in data-* attributes: no inline script. --}}
<x-app-layout>
    <x-slot name="title">{{ $classRoom->title }} · {{ __('Live room') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.show', $classRoom) }}">{{ \Illuminate\Support\Str::limit($classRoom->title, 40) }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Live room') }}</li>
    </x-slot>

    <div class="container-fluid px-3 px-lg-4">
        {{-- Slim top bar: identity on the left, room state and exit on the right.
             On phones and tablets the bar stays sticky, so the exit link is
             always reachable (Phase 3D). --}}
        <div class="card edu-card edu-room-bar mb-3">
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

        {{-- The app's own WebRTC room --------------------------------------------------
             data-live-room is picked up by resources/js/live-room.js, which asks for
             the camera/microphone, joins through our own API (live.*) and renders one
             tile per participant. Nothing is embedded from an external server. --}}
        <div id="edu-live-room"
             data-live-room
             data-join-url="{{ route('live.join', $classRoom) }}"
             data-heartbeat-url="{{ route('live.heartbeat', $classRoom) }}"
             data-signals-url="{{ route('live.signals', $classRoom) }}"
             data-signal-url="{{ route('live.signal', $classRoom) }}"
             data-chat-url="{{ route('live.chat', $classRoom) }}"
             data-leave-url="{{ route('live.leave', $classRoom) }}"
             data-dashboard-url="{{ route('dashboard') }}"
             data-self-id="{{ auth()->id() }}"
             data-moderator="{{ $isModerator ? '1' : '0' }}"
             data-display-name="{{ $displayName }}"
             data-avatar-url="{{ $avatarUrl }}"
             data-ice="{{ json_encode($iceServers) }}">

            {{-- Shown while the camera starts and the room is being joined --}}
            <div class="alert bg-body-secondary text-body border d-flex align-items-center gap-2" data-live-status role="status">
                <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                <span>{{ __('Connecting to the room...') }}</span>
            </div>

            {{-- Shown when the camera/microphone is denied or the room cannot start --}}
            <div class="alert alert-danger d-none" data-live-error role="alert">
                <div class="fw-semibold mb-1">
                    <i class="bi bi-camera-video-off me-1" aria-hidden="true"></i> {{ __('The video room could not be loaded.') }}
                </div>
                <p class="small mb-2" data-live-error-text>
                    {{ __('Allow the camera and the microphone in your browser, then try again.') }}
                </p>
                <button type="button" class="btn btn-sm btn-outline-danger" data-live-retry>
                    <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i> {{ __('Try again') }}
                </button>
            </div>

            <div class="edu-live-layout">
                <div class="edu-live-main">
                    {{-- One tile per participant is added here; the local one first --}}
                    <div class="edu-live-grid" data-live-grid></div>

                    {{-- Control bar: microphone, camera, screen share, chat, leave --}}
                    <div class="edu-live-controls" role="toolbar" aria-label="{{ __('Room controls') }}">
                        <button type="button" class="edu-live-btn" data-live-mic
                                aria-label="{{ __('Toggle microphone') }}" title="{{ __('Toggle microphone') }}">
                            <i class="bi bi-mic" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="edu-live-btn" data-live-camera
                                aria-label="{{ __('Toggle camera') }}" title="{{ __('Toggle camera') }}">
                            <i class="bi bi-camera-video" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="edu-live-btn" data-live-screen
                                aria-label="{{ __('Share your screen') }}" title="{{ __('Share your screen') }}">
                            <i class="bi bi-display" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="edu-live-btn" data-live-chat-toggle
                                aria-label="{{ __('Show the chat') }}" title="{{ __('Show the chat') }}"
                                aria-controls="edu-live-chat" aria-expanded="false">
                            <i class="bi bi-chat-dots" aria-hidden="true"></i>
                        </button>

                        <span class="edu-live-count">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            <span data-live-count aria-live="polite">1</span>
                        </span>

                        <button type="button" class="edu-live-btn edu-live-btn-danger ms-auto" data-live-leave
                                aria-label="{{ __('Leave the room') }}" title="{{ __('Leave the room') }}">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                {{-- Chat side panel: messages arrive through the signals poll (FR-22) --}}
                <aside class="edu-live-chat d-none" id="edu-live-chat" data-live-chat-panel
                       aria-label="{{ __('Class chat') }}">
                    <div class="edu-live-chat-head">
                        <i class="bi bi-chat-dots me-1" aria-hidden="true"></i> {{ __('Class chat') }}
                    </div>
                    <div class="edu-live-chat-list" data-live-chat-list aria-live="polite"></div>
                    <form class="edu-live-chat-form" data-live-chat-form>
                        <input type="text" class="form-control form-control-sm" data-live-chat-input
                               maxlength="500" autocomplete="off" placeholder="{{ __('Type a message...') }}"
                               aria-label="{{ __('Type a message') }}">
                        <button type="submit" class="btn btn-sm btn-primary"
                                aria-label="{{ __('Send') }}" title="{{ __('Send') }}">
                            <i class="bi bi-send" aria-hidden="true"></i>
                        </button>
                    </form>
                </aside>
            </div>
        </div>

        {{-- Collapsible room info: moderation controls and help, out of the way.
             Below lg it is a bottom sheet, not a panel that pushes the video;
             the close button and the sticky top bar keep "leave" reachable. --}}
        <div class="collapse d-lg-block mt-3 edu-room-sheet" id="room-info">
            <div class="edu-room-sheet-head d-lg-none">
                <span class="edu-room-sheet-handle" aria-hidden="true"></span>
                <span class="fw-semibold">{{ __('Room information') }}</span>
                <button type="button" class="btn-close ms-auto"
                        data-bs-toggle="collapse" data-bs-target="#room-info"
                        aria-expanded="true" aria-controls="room-info"
                        aria-label="{{ __('Close room information') }}"></button>
            </div>

            <div class="row g-3">
                {{-- Only the teacher can start or end the class (FR-24). --}}
                @if ($isModerator && ($classRoom->teacher_id === auth()->id()))
                    <div class="col-12 col-lg-7">
                        <div class="card edu-card h-100">
                            <div class="card-body">
                                <h2 class="h6 fw-bold">{{ __('Class controls') }}</h2>

                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($classRoom->allowedTransitions() as $next)
                                        <form method="POST" action="{{ route('classes.status', $classRoom) }}" class="d-inline"
                                              data-confirm="{{ __('Change the class status to :status?', ['status' => __($next)]) }}">
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
                                    @if ($isModerator)
                                        <i class="bi bi-mic me-1" aria-hidden="true"></i>
                                        {{ __('Your microphone starts on; mute yourself whenever you do not need to speak.') }}
                                    @else
                                        <i class="bi bi-mic-mute me-1" aria-hidden="true"></i>
                                        {{ __('Your microphone starts muted; unmute when you are ready to speak.') }}
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
