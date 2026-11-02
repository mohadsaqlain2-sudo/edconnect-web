{{-- Phase 3F: keyboard shortcut help, iOS "add to home screen" help and the
     large-text switch. Native <dialog> is keyboard accessible by default and
     resources/js/adaptive.js opens it with "?" (or the menu entries). --}}

<dialog id="eduShortcutsDialog" class="edu-dialog" aria-labelledby="eduShortcutsTitle">
    <div class="edu-dialog-head">
        <h2 class="h6 fw-semibold mb-0 flex-grow-1" id="eduShortcutsTitle">
            <i class="bi bi-keyboard me-1 text-edu" aria-hidden="true"></i>
            {{ __('Keyboard shortcuts') }}
        </h2>
        <button type="button" class="btn-close" data-dialog-close
                aria-label="{{ __('Close') }}"></button>
    </div>

    <div class="edu-dialog-body">
        <dl class="edu-shortcut-list mb-0">
            <dt><kbd>/</kbd></dt>
            <dd>{{ __('Search') }}</dd>

            <dt><kbd>n</kbd></dt>
            <dd>{{ __('New post') }}</dd>

            <dt><kbd>g</kbd> <kbd>d</kbd></dt>
            <dd>{{ __('Dashboard') }}</dd>

            <dt><kbd>g</kbd> <kbd>f</kbd></dt>
            <dd>{{ __('Feed') }}</dd>

            <dt><kbd>g</kbd> <kbd>n</kbd></dt>
            <dd>{{ __('Notifications') }}</dd>

            <dt><kbd>g</kbd> <kbd>p</kbd></dt>
            <dd>{{ __('My profile') }}</dd>

            <dt><kbd>?</kbd></dt>
            <dd>{{ __('This help') }}</dd>

            <dt><kbd>Esc</kbd></dt>
            <dd>{{ __('Close a dialog or the menu') }}</dd>
        </dl>

        <hr class="my-3">

        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary"
                    data-large-text-toggle
                    data-label-on="{{ __('Larger text') }}"
                    data-label-off="{{ __('Normal text') }}"
                    aria-pressed="false">
                <i class="bi bi-fonts me-1" aria-hidden="true"></i>
                <span data-large-text-label>{{ __('Larger text') }}</span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-secondary" data-install-app hidden>
                <i class="bi bi-download me-1" aria-hidden="true"></i>
                <span>{{ __('Install app') }}</span>
            </button>
        </div>

        <p class="small text-muted mb-0 mt-3">
            {{ __('Shortcuts work on a keyboard. On a TV, use the arrow keys to move between actions.') }}
        </p>

        <div class="d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-primary" data-dialog-close>
                {{ __('Done') }}
            </button>
        </div>
    </div>
</dialog>

<dialog id="eduInstallDialog" class="edu-dialog" aria-labelledby="eduInstallTitle">
    <div class="edu-dialog-head">
        <h2 class="h6 fw-semibold mb-0 flex-grow-1" id="eduInstallTitle">
            <i class="bi bi-phone me-1 text-edu" aria-hidden="true"></i>
            {{ __('Install EduConnect') }}
        </h2>
        <button type="button" class="btn-close" data-dialog-close
                aria-label="{{ __('Close') }}"></button>
    </div>

    <div class="edu-dialog-body">
        <ol class="ps-3 mb-3 small">
            <li>{{ __('Open the Share menu in Safari.') }}</li>
            <li>{{ __('Choose “Add to Home Screen”.') }}</li>
            <li>{{ __('Tap “Add”. EduConnect now opens full screen like an app.') }}</li>
        </ol>

        <p class="small text-muted mb-3">
            {{ __('On Android, Chrome offers an “Install app” button in the menu or in the address bar.') }}
        </p>

        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-primary" data-dialog-close>
                {{ __('Got it') }}
            </button>
        </div>
    </div>
</dialog>
