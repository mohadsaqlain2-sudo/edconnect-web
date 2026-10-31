{{-- Report modal for one post or comment (FR-32).
     Included by components/post-card and comments/_comment, so every piece of
     content carries its own form and the page keeps working without JavaScript. --}}
@php
    $isPost = $reportable instanceof \App\Models\Post;
    $prefix = $isPost ? 'post' : 'comment';
    $modalId = 'report-'.$prefix.'-'.$reportable->id;
    $action = $isPost
        ? route('reports.posts.store', $reportable)
        : route('reports.comments.store', $reportable);
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ $action }}">
                @csrf

                <div class="modal-header">
                    <h2 class="modal-title h5" id="{{ $modalId }}-label">
                        <i class="bi bi-flag me-1"></i> {{ __('Report this :type', ['type' => $prefix]) }}
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>

                <div class="modal-body">
                    <p class="small text-muted">
                        {{ __('Tell the moderators what is wrong. Your report is private.') }}
                    </p>

                    <div class="mb-3">
                        <label for="{{ $modalId }}-reason" class="form-label">
                            {{ __('Reason') }} <span class="text-danger">*</span>
                        </label>
                        <select name="reason" id="{{ $modalId }}-reason" class="form-select" required>
                            <option value="">{{ __('Choose a reason') }}</option>
                            @foreach (\App\Models\Report::REASONS as $value => $label)
                                <option value="{{ $value }}" @selected(old('reason') === $value)>
                                    {{ __($label) }}
                                </option>
                            @endforeach
                        </select>
                        @error('reason')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label for="{{ $modalId }}-details" class="form-label">
                            {{ __('Additional details') }} <span class="text-muted">({{ __('optional') }})</span>
                        </label>
                        <textarea name="details" id="{{ $modalId }}-details" rows="3" maxlength="500"
                                  class="form-control" placeholder="{{ __('What happened?') }}">{{ old('details') }}</textarea>
                        <div class="form-text">{{ __('At most 500 characters.') }}</div>
                        @error('details')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-flag me-1"></i> {{ __('Send report') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
