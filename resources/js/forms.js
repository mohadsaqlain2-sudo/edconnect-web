/**
 * Small, global form helpers (Phase 3C).
 *
 * 1. Busy state: the submit button shows the spinner from the design system
 *    and cannot be pressed twice while the server works. This covers every
 *    classic form post, including confirm dialogs on destructive actions.
 * 2. Optional live character counters: any field with data-count-target gets
 *    a "used / limit" readout, so long text areas are never a mystery.
 *
 * Everything degrades gracefully: with JavaScript off the forms simply post.
 */

const FETCH_FORMS = [
    '[data-like-form]',
    '[data-follow-form]',
    '[data-comment-form]',
    '[data-delete-comment-form]',
];

/** Put a submit button into the loading state from the style guide. */
function markBusy(button) {
    if (!button || button.dataset.busy === '1') {
        return;
    }

    button.dataset.busy = '1';
    button.classList.add('is-loading');
    button.setAttribute('aria-busy', 'true');
    button.setAttribute('aria-disabled', 'true');

    // Keep the original text so the page can be restored from bfcache.
    button.dataset.originalLabel = button.innerHTML;
}

/** Undo the loading state (back button, failed client-side check). */
function clearBusy(button) {
    if (!button || button.dataset.busy !== '1') {
        return;
    }

    delete button.dataset.busy;
    button.classList.remove('is-loading');
    button.removeAttribute('aria-busy');
    button.removeAttribute('aria-disabled');
}

function bootBusyForms() {
    document.addEventListener('submit', (event) => {
        const form = event.target;

        // Already handled by a fetch() upgrade: those manage their own state.
        if (FETCH_FORMS.some((selector) => form.matches(selector))) {
            return;
        }

        // The browser's own validation failed: nothing is going to the server.
        if (form.checkValidity && !form.checkValidity()) {
            return;
        }

        const submitter = event.submitter
            ?? form.querySelector('button[type="submit"], input[type="submit"]');

        if (!submitter) {
            return;
        }

        // Wait one tick so the browser has already started the navigation.
        window.setTimeout(() => markBusy(submitter), 0);
    });

    // Restoring a page from the back/forward cache must not leave a spinner.
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-busy="1"]').forEach((button) => {
            clearBusy(button);

            if (button.dataset.originalLabel) {
                button.innerHTML = button.dataset.originalLabel;
            }
        });
    });
}

/** Live "used / max" counters for fields that ask for one. */
function bootCharacterCounters() {
    document.querySelectorAll('[data-count-target]').forEach((output) => {
        const field = document.getElementById(output.dataset.countTarget);

        if (!field) {
            return;
        }

        const limit = Number(field.getAttribute('maxlength')) || null;

        const update = () => {
            const length = field.value.length;
            output.textContent = limit ? `${length} / ${limit}` : String(length);
            output.classList.toggle('text-danger', limit !== null && length >= limit);
        };

        field.addEventListener('input', update);
        update();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        bootBusyForms();
        bootCharacterCounters();
    });
} else {
    bootBusyForms();
    bootCharacterCounters();
}
