/**
 * Small page transition (Phase 3B).
 *
 * The CSS already fades a page in. This adds a quick fade-out when following a
 * normal same-site link, so moving between dashboard pages feels smooth instead
 * of abrupt. Modified clicks, new tabs, downloads, hash links and Bootstrap
 * toggles are left to the browser.
 */

const REDUCED_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)');

function isPlainLeftClick(event) {
    return event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;
}

function shouldTransition(link) {
    if (!link || !link.href) {
        return false;
    }

    if (link.target && link.target !== '_self') {
        return false;
    }

    if (link.hasAttribute('download') || link.dataset.bsToggle) {
        return false;
    }

    if (link.getAttribute('href')?.startsWith('#')) {
        return false;
    }

    if (link.dataset.noTransition !== undefined) {
        return false;
    }

    const url = new URL(link.href, window.location.href);

    if (url.origin !== window.location.origin) {
        return false;
    }

    // Same page (only a hash or query change) does not need a fade.
    return url.pathname !== window.location.pathname;
}

function bootTransitions() {
    if (REDUCED_MOTION.matches) {
        return;
    }

    document.addEventListener('click', (event) => {
        if (!isPlainLeftClick(event)) {
            return;
        }

        const link = event.target.closest('a');

        if (!shouldTransition(link)) {
            return;
        }

        const page = document.querySelector('.edu-page');

        if (!page) {
            return;
        }

        event.preventDefault();
        page.classList.add('is-leaving');

        window.setTimeout(() => {
            window.location.assign(link.href);
        }, 120);
    });

    // Coming back through the browser cache should restore the page.
    window.addEventListener('pageshow', () => {
        document.querySelector('.edu-page')?.classList.remove('is-leaving');
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootTransitions);
} else {
    bootTransitions();
}
