/**
 * Desktop sidebar toggle (FR-31).
 *
 * The sidebar is an offcanvas drawer on phones and a normal column from the
 * lg breakpoint upwards. This script only handles the desktop toggle and
 * remembers the choice in localStorage; Bootstrap handles the mobile drawer.
 *
 * The layout also reads the same key in a tiny inline script in <head>, so the
 * correct state is applied before the first paint.
 */

const STORAGE_KEY = 'educonnect.sidebar.collapsed';

function isCollapsed() {
    return document.documentElement.classList.contains('edu-sidebar-collapsed');
}

function applyState(collapsed) {
    document.documentElement.classList.toggle('edu-sidebar-collapsed', collapsed);

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

        const icon = button.querySelector('[data-sidebar-toggle-icon]');
        if (icon) {
            icon.classList.toggle('bi-layout-sidebar-inset', !collapsed);
            icon.classList.toggle('bi-layout-sidebar', collapsed);
        }
    });
}

function storeState(collapsed) {
    try {
        window.localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
    } catch (error) {
        // Private browsing may block storage; the page still toggles for this visit.
    }
}

function bootSidebarToggle() {
    // Sync the aria attributes/icon with the state that was applied in <head>.
    applyState(isCollapsed());

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const collapsed = !isCollapsed();
            applyState(collapsed);
            storeState(collapsed);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootSidebarToggle);
} else {
    bootSidebarToggle();
}

/* -----------------------------------------------------------------------------
 | Sidebar drawer: close after tapping a link (Phase 3D)
 | -----------------------------------------------------------------------------
 | Bootstrap's offcanvas stays open when a link inside it is clicked. On a
 | phone that leaves the drawer covering the page, so hide it first; the link
 | still navigates because the default action is not prevented.
 */

function bootSidebarAutoClose() {
    const sidebar = document.getElementById('appSidebar');

    if (!sidebar) {
        return;
    }

    sidebar.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');

        if (!link || !window.bootstrap?.Offcanvas) {
            return;
        }

        // Only the drawer (< lg) needs closing; on desktop the sidebar is a column.
        if (window.matchMedia('(max-width: 991.98px)').matches) {
            window.bootstrap.Offcanvas.getOrCreateInstance(sidebar).hide();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootSidebarAutoClose);
} else {
    bootSidebarAutoClose();
}

/* -----------------------------------------------------------------------------
 | Expandable phone search (Phase 3D)
 | -----------------------------------------------------------------------------
 | The navbar hides the full search box below md (there is not enough room).
 | The magnifier button opens a full-width panel underneath, moves focus into
 | the input, closes on Escape / outside tap, and resets when the viewport
 | grows back to a desktop size.
 */

function bootMobileSearch() {
    const toggle = document.querySelector('[data-mobile-search-toggle]');
    const panel = document.getElementById('mobileSearchPanel');

    if (!toggle || !panel) {
        return;
    }

    const input = panel.querySelector('[data-search-input]');

    const setOpen = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (open) {
            window.requestAnimationFrame(() => input?.focus());
        }
    };

    toggle.addEventListener('click', () => setOpen(panel.hidden));

    panel.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
            toggle.focus();
        }
    });

    document.addEventListener('click', (event) => {
        if (panel.hidden) {
            return;
        }

        if (!panel.contains(event.target) && !toggle.contains(event.target)) {
            setOpen(false);
        }
    });

    window.addEventListener('resize', () => {
        if (window.matchMedia('(min-width: 768px)').matches) {
            setOpen(false);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootMobileSearch);
} else {
    bootMobileSearch();
}
