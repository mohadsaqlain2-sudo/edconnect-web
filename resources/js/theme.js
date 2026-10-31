/**
 * Light / dark mode (Phase 3B).
 *
 * The choice is stored in localStorage under `educonnect.theme`. When there is
 * no stored choice the page follows the operating system preference
 * (`prefers-color-scheme`). The first paint is handled by a tiny inline script
 * in the layout, so there is no flash of the wrong theme.
 */

const STORAGE_KEY = 'educonnect.theme';
const media = window.matchMedia('(prefers-color-scheme: dark)');

function storedTheme() {
    try {
        const value = window.localStorage.getItem(STORAGE_KEY);

        return value === 'dark' || value === 'light' ? value : null;
    } catch (error) {
        return null;
    }
}

function preferredTheme() {
    return storedTheme() ?? (media.matches ? 'dark' : 'light');
}

function currentTheme() {
    return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
}

function paintToggle(theme) {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const next = theme === 'dark' ? 'light' : 'dark';
        const label = next === 'dark' ? 'Switch to dark mode' : 'Switch to light mode';

        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        button.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');

        const icon = button.querySelector('[data-theme-icon]');
        if (icon) {
            icon.classList.toggle('bi-moon-stars', theme !== 'dark');
            icon.classList.toggle('bi-sun', theme === 'dark');
        }
    });
}

function applyTheme(theme, persist = false) {
    document.documentElement.setAttribute('data-bs-theme', theme);

    if (persist) {
        try {
            window.localStorage.setItem(STORAGE_KEY, theme);
        } catch (error) {
            // Private browsing can refuse storage; the theme still applies now.
        }
    }

    paintToggle(theme);

    // Let interested components (charts) restyle themselves.
    document.dispatchEvent(new CustomEvent('edu:theme-changed', { detail: { theme } }));
}

function bootTheme() {
    // The inline head script already applied the correct value; make sure the
    // attribute exists even if that script was blocked.
    if (!document.documentElement.hasAttribute('data-bs-theme')) {
        applyTheme(preferredTheme());
    } else {
        paintToggle(currentTheme());
    }

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const next = currentTheme() === 'dark' ? 'light' : 'dark';
            applyTheme(next, true);
        });
    });

    // Follow the OS while the visitor has made no explicit choice.
    media.addEventListener('change', (event) => {
        if (storedTheme() === null) {
            applyTheme(event.matches ? 'dark' : 'light');
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootTheme);
} else {
    bootTheme();
}
