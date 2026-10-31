/**
 * Live search suggestions in the navbar (FR-14).
 *
 * While the user types, the box asks /search/suggest for at most five people or
 * hashtags and shows them in a dropdown. Requests are debounced (250 ms) and the
 * previous request is aborted, so typing fast does not fire one request per key.
 *
 * The entries are built with document.createElement + textContent, never with
 * innerHTML, so a name like "<script>" can never be turned into markup.
 */

/** Wait this long after the last keystroke before asking the server. */
const DEBOUNCE_MS = 250;

/** A shorter term than this matches almost everything, so it is not sent. */
const MIN_TERM_LENGTH = 2;

function bootSearchSuggestions() {
    document.querySelectorAll('[data-search-form]').forEach(bootSearchForm);
}

function bootSearchForm(form) {
    const url = form.dataset.searchUrl;
    const input = form.querySelector('[data-search-input]');
    const panel = form.querySelector('[data-search-suggestions]');

    if (!url || !input || !panel) {
        return;
    }

    let timer = null;
    let controller = null;

    /** Hide the dropdown and drop whatever it showed. */
    function hide() {
        panel.classList.add('d-none');
        panel.replaceChildren();
    }

    /** Draw the suggestions returned by the server. */
    function render(suggestions) {
        panel.replaceChildren();

        if (!Array.isArray(suggestions) || suggestions.length === 0) {
            panel.classList.add('d-none');

            return;
        }

        suggestions.forEach((item) => {
            const link = document.createElement('a');
            link.className = 'dropdown-item d-flex align-items-center gap-2';
            link.href = item.url;

            const icon = document.createElement('i');
            icon.className = `bi ${item.icon || 'bi-search'} text-edu`;
            icon.setAttribute('aria-hidden', 'true');

            const label = document.createElement('span');
            label.className = 'flex-grow-1';
            // textContent, not innerHTML: the label is plain text, always.
            label.textContent = item.label || '';

            const meta = document.createElement('span');
            meta.className = 'small text-muted';
            meta.textContent = item.meta || '';

            link.append(icon, label, meta);
            panel.appendChild(link);
        });

        panel.classList.remove('d-none');
    }

    /** One lookup for the current value of the box. */
    async function load() {
        const term = input.value.trim();

        if (term.length < MIN_TERM_LENGTH) {
            hide();

            return;
        }

        // A newer keystroke makes the answer of the running request useless.
        controller?.abort();
        controller = new AbortController();

        try {
            const response = await fetch(`${url}?q=${encodeURIComponent(term)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const data = await response.json();
            render(data.suggestions);
        } catch (error) {
            // An aborted request is not an error worth reporting.
            if (error.name !== 'AbortError') {
                console.error('[EduConnect] loading search suggestions failed', error);
            }
        }
    }

    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(load, DEBOUNCE_MS);
    });

    // Escape closes the dropdown.
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hide();
        }
    });

    // Clicking anywhere outside the box closes it too.
    document.addEventListener('click', (event) => {
        if (!form.contains(event.target)) {
            hide();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootSearchSuggestions);
} else {
    bootSearchSuggestions();
}
