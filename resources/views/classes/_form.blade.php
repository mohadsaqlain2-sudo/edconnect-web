{{-- Shared "create / edit a class" form (FR-18).
     $classRoom is null while creating and a ClassRoom model while editing.
     Left: the fields. Right: a live preview kept in sync by resources/js/classes.js. --}}
@php
    $classRoom = $classRoom ?? null;
    $editing = $classRoom !== null;

    // The stored time is "H:i:s"; <input type="time"> wants "H:i".
    $timeValue = old('time', $classRoom
        ? \Illuminate\Support\Str::substr((string) $classRoom->time, 0, 5)
        : '');

    $previewTitle = old('title', $classRoom?->title) ?: __('Your class title');
    $previewSubject = old('subject', $classRoom?->subject ?? auth()->user()->subject);
    $previewDate = old('date', $classRoom?->date?->format('Y-m-d'));
    $previewDuration = old('duration', $classRoom?->duration ?? 60);
@endphp

<form method="POST"
      action="{{ $editing ? route('classes.update', $classRoom) : route('classes.store') }}"
      class="row g-4"
      data-class-form>
    @csrf
    @if ($editing)
        @method('PATCH')
    @endif

    {{-- ------------------------------------------------------------
         Fields
         ------------------------------------------------------------ --}}
    <div class="col-12 col-lg-7">
        <div class="card edu-card">
            <div class="card-body d-flex flex-column gap-3">
                <div>
                    <label for="title" class="form-label fw-semibold">
                        {{ __('Title') }} <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="text" name="title" id="title" maxlength="150" required
                           class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title', $classRoom?->title) }}"
                           placeholder="{{ __('Algebra basics') }}"
                           @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
                    @error('title')
                        <div class="invalid-feedback" id="title-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="subject" class="form-label">{{ __('Subject') }}</label>
                    <input type="text" name="subject" id="subject" maxlength="100"
                           class="form-control @error('subject') is-invalid @enderror"
                           value="{{ old('subject', $classRoom?->subject ?? auth()->user()->subject) }}"
                           placeholder="{{ __('Mathematics') }}"
                           @error('subject') aria-invalid="true" aria-describedby="subject-help subject-error" @enderror>
                    <div class="form-text" id="subject-help">{{ __('Students can filter the class list on this word.') }}</div>
                    @error('subject')
                        <div class="invalid-feedback" id="subject-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="description" class="form-label">{{ __('Description') }}</label>
                    <textarea name="description" id="description" rows="5" maxlength="2000"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="{{ __('What will you cover? Any material the students need?') }}"
                              @error('description') aria-invalid="true" aria-describedby="description-help description-error" @enderror>{{ old('description', $classRoom?->description) }}</textarea>
                    <div class="form-text d-flex justify-content-between gap-2" id="description-help">
                        <span>{{ __('Optional. 2000 characters max.') }}</span>
                        <span data-count-target="description" aria-live="polite"></span>
                    </div>
                    @error('description')
                        <div class="invalid-feedback" id="description-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3">
                    <div class="col-12 col-sm-4">
                        <label for="date" class="form-label fw-semibold">
                            {{ __('Date') }} <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="date" name="date" id="date" required
                               class="form-control @error('date') is-invalid @enderror"
                               value="{{ old('date', $classRoom?->date?->format('Y-m-d')) }}"
                               @error('date') aria-invalid="true" aria-describedby="date-error" @enderror>
                        @error('date')
                            <div class="invalid-feedback" id="date-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-sm-4">
                        <label for="time" class="form-label fw-semibold">
                            {{ __('Start time') }} <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="time" name="time" id="time" required
                               class="form-control @error('time') is-invalid @enderror"
                               value="{{ $timeValue }}"
                               @error('time') aria-invalid="true" aria-describedby="time-error" @enderror>
                        @error('time')
                            <div class="invalid-feedback" id="time-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-sm-4">
                        <label for="duration" class="form-label fw-semibold">
                            {{ __('Duration (minutes)') }} <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="number" name="duration" id="duration" required
                               min="15" max="240" step="5"
                               class="form-control @error('duration') is-invalid @enderror"
                               value="{{ old('duration', $classRoom?->duration ?? 60) }}"
                               @error('duration') aria-invalid="true" aria-describedby="duration-help duration-error" @enderror>
                        <div class="form-text" id="duration-help">{{ __('15 to 240 minutes.') }}</div>
                        @error('duration')
                            <div class="invalid-feedback" id="duration-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @unless ($editing)
                    <div class="alert alert-info mb-0 small">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        {{ __('The class starts as "scheduled". A private video room is created automatically.') }}
                    </div>
                @endunless

                <div class="d-flex flex-column flex-sm-row gap-2 pt-1">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2 me-1" aria-hidden="true"></i>
                        {{ $editing ? __('Save changes') : __('Create class') }}
                    </button>

                    <a href="{{ $editing ? route('classes.show', $classRoom) : route('classes.index') }}"
                       class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------
         Live preview
         ------------------------------------------------------------ --}}
    <div class="col-12 col-lg-5">
        <div class="edu-sticky-aside">
            <div class="card edu-card"
                 data-class-preview
                 data-empty-title="{{ __('Your class title') }}"
                 data-empty-subject="{{ __('Subject') }}"
                 data-empty-date="{{ __('Pick a date') }}"
                 data-empty-time="{{ __('Pick a time') }}"
                 data-duration-label="{{ __('min') }}">
                <div class="card-body">
                    <div class="edu-eyebrow mb-3">{{ __('Students will see') }}</div>

                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <h2 class="h6 fw-bold mb-0 text-break" data-preview-title>{{ $previewTitle }}</h2>
                        <x-status-badge :status="$editing ? $classRoom->status : 'scheduled'" class="flex-shrink-0" />
                    </div>

                    <div class="mb-2" data-preview-subject-chip @if (! $previewSubject) hidden @endif>
                        <span class="edu-chip">
                            <i class="bi bi-book" aria-hidden="true"></i>
                            <span data-preview-subject>{{ $previewSubject ?: __('Subject') }}</span>
                        </span>
                    </div>

                    <ul class="list-unstyled small text-muted mb-2">
                        <li class="mb-1">
                            <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                            <span data-preview-date>{{ $previewDate ? \Illuminate\Support\Carbon::parse($previewDate)->format('D d M Y') : __('Pick a date') }}</span>
                        </li>
                        <li class="mb-1">
                            <i class="bi bi-clock me-1" aria-hidden="true"></i>
                            <span data-preview-time>{{ $timeValue ?: __('Pick a time') }}</span>
                            <span aria-hidden="true">&middot;</span>
                            <span data-preview-duration>{{ $previewDuration }} {{ __('min') }}</span>
                        </li>
                        <li>
                            <i class="bi bi-person-video3 me-1" aria-hidden="true"></i>
                            {{ auth()->user()->name }}
                        </li>
                    </ul>

                    <p class="small fw-semibold text-muted mb-0">
                        <i class="bi bi-stopwatch me-1" aria-hidden="true"></i>
                        {{ __('Starts when you press "Start class".') }}
                    </p>
                </div>
            </div>

            <p class="small text-muted mt-3 mb-0">
                <i class="bi bi-lightbulb me-1" aria-hidden="true"></i>
                {{ __('A clear title and a short description help students decide quickly.') }}
            </p>
        </div>
    </div>
</form>
