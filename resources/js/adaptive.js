/**
 * EduConnect — Phase 3F adaptive runtime.
 *
 * One small module, loaded on every page. It never replaces the existing
 * Bootstrap / Phase 3B-3D behaviour; it only tells CSS and the other modules
 * what kind of device, input, orientation, network and preferences the visitor
 * currently has, and adds the behaviours that CSS cannot express.
 *
 * Everything is progressive: with JavaScript disabled the site is the same
 * responsive Laravel/Bootstrap site it was before this file existed.
 */

const root = document.documentElement;
const body = document.body;

/* -----------------------------------------------------------------------------
 | Media queries (all capability checks, no device sniffing required)
 | -------------------------------------------------------------------------- */

const media = {
    phone: window.matchMedia('(max-width: 575.98px)'),
    tablet: window.matchMedia('(min-width: 576px) and (max-width: 1199.98px)'),
    desktop: window.matchMedia('(min-width: 992px)'),
    tv: window.matchMedia('(min-width: 2200px)'),
    coarse: window.matchMedia('(pointer: coarse)'),
    fine: window.matchMedia('(pointer: fine)'),
    hover: window.matchMedia('(hover: hover)'),
    portrait: window.matchMedia('(orientation: portrait)'),
    dark: window.matchMedia('(prefers-color-scheme: dark)'),
    reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)'),
    moreContrast: window.matchMedia('(prefers-contrast: more)'),
    forcedColors: window.matchMedia('(forced-colors: active)'),
    reducedData: window.matchMedia('(prefers-reduced-data: reduce)'),
    standalone: window.matchMedia('(display-mode: standalone)'),
    retina: window.matchMedia('(min-resolution: 2dppx)'),
};

const TV_UA = /smart-?tv|smarttv|googletv|appletv|hbbtv|netcast|viera|bravia|tizen|webos|crkey|roku|firetv|android tv/i;
const TABLET_UA = /ipad|tablet|playbook|silk|kindle/i;
const IOS_UA = /iphone|ipad|ipod/i;

// Foldable / dual-screen: CSS Environment Variable API
function isFoldable() {
    try {
        // Chromium's viewport segments API
        if (window.matchMedia('(horizontal-viewport-segments: 2)').matches ||
            window.matchMedia('(vertical-viewport-segments: 2)').matches) {
            return true;
        }
        // Legacy Surface Duo spanning query
        if (window.matchMedia('(spanning: single-fold-horizontal)').matches ||
            window.matchMedia('(spanning: single-fold-vertical)').matches) {
            return true;
        }
    } catch (e) {
        // Ignore
    }
    return false;
}

function foldableSegments() {
    const horizontal = window.matchMedia('(horizontal-viewport-segments: 2)').matches;
    const vertical = window.matchMedia('(vertical-viewport-segments: 2)').matches;
    if (horizontal) return 'horizontal';
    if (vertical) return 'vertical';
    return null;
}

function connection() {
    return navigator.connection || navigator.mozConnection || navigator.webkitConnection || null;
}

function isSaveData() {
    return Boolean(connection()?.saveData) || media.reducedData.matches;
}

function isSlowNetwork() {
    const net = connection();

    if (!navigator.onLine) {
        return false;
    }

    if (isSaveData()) {
        return true;
    }

    if (!net) {
        return false;
    }

    if (typeof net.downlink === 'number' && net.downlink > 0 && net.downlink < 1.5) {
        return true;
    }

    return /(^|-)2g$|^3g$|slow-2g/i.test(String(net.effectiveType || ''));
}

function isTouch() {
    if (media.coarse.matches) {
        return true;
    }

    if (!media.fine.matches && !media.hover.matches && navigator.maxTouchPoints > 0) {
        return true;
    }

    // Hybrid laptops keep a fine pointer: they are mouse devices with a touch
    // screen, not phones, so the desktop chrome stays.
    return false;
}

function isTv() {
    return media.tv.matches || TV_UA.test(navigator.userAgent || '');
}

function deviceKind() {
    const width = window.innerWidth;
    const touch = isTouch();
    const ua = navigator.userAgent || '';

    if (isTv()) {
        return 'tv';
    }

    if (width <= 575.98) {
        return 'mobile';
    }

    if (width <= 1199.98 && (touch || TABLET_UA.test(ua) || media.portrait.matches)) {
        return 'tablet';
    }

    return 'desktop';
}

function breakpoint() {
    const width = window.innerWidth;

    if (width < 576) return 'xs';
    if (width < 768) return 'sm';
    if (width < 992) return 'md';
    if (width < 1200) return 'lg';
    if (width < 1400) return 'xl';

    return 'xxl';
}

function orientation() {
    if (screen.orientation?.type) {
        return screen.orientation.type.startsWith('portrait') ? 'portrait' : 'landscape';
    }

    return window.innerHeight >= window.innerWidth ? 'portrait' : 'landscape';
}

function currentTheme() {
    const theme = root.getAttribute('data-bs-theme');

    if (theme === 'dark' || theme === 'light') {
        return theme;
    }

    try {
        const stored = window.localStorage.getItem('educonnect.theme');
        if (stored === 'dark' || stored === 'light') {
            return stored;
        }
    } catch (error) {
        // Private mode: fall through to the OS preference.
    }

    return media.dark.matches ? 'dark' : 'light';
}

/* -----------------------------------------------------------------------------
 | Snapshot + attribute writer
 | -------------------------------------------------------------------------- */

let snapshot = {};

function readSnapshot() {
    const net = connection();
    const online = navigator.onLine;

    return {
        device: deviceKind(),
        input: isTouch() ? 'touch' : 'mouse',
        orientation: orientation(),
        breakpoint: breakpoint(),
        network: online && isSlowNetwork() ? 'slow' : 'fast',
        online,
        saveData: isSaveData(),
        theme: currentTheme(),
        standalone: media.standalone.matches || Boolean(navigator.standalone),
        reducedMotion: media.reducedMotion.matches,
        contrast: media.moreContrast.matches,
        forcedColors: media.forcedColors.matches,
        dpr: Math.round((window.devicePixelRatio || 1) * 100) / 100,
        retina: media.retina.matches,
        width: window.innerWidth,
        height: window.innerHeight,
        effectiveType: net?.effectiveType || null,
        downlink: typeof net?.downlink === 'number' ? net.downlink : null,
        rtt: typeof net?.rtt === 'number' ? net.rtt : null,
        deviceMemory: typeof navigator.deviceMemory === 'number' ? navigator.deviceMemory : null,
        hardwareConcurrency: typeof navigator.hardwareConcurrency === 'number'
            ? navigator.hardwareConcurrency
            : null,
        foldable: isFoldable(),
        foldSegments: foldableSegments(),
    };
}

function writeAttributes(next, previous) {
    root.dataset.device = next.device;
    root.dataset.input = next.input;
    root.dataset.orientation = next.orientation;
    root.dataset.breakpoint = next.breakpoint;
    root.dataset.network = next.network;
    root.dataset.online = next.online ? 'true' : 'false';
    root.dataset.saveData = next.saveData ? 'true' : 'false';
    root.dataset.theme = next.theme;
    root.dataset.standalone = next.standalone ? 'true' : 'false';
    root.dataset.reducedMotion = next.reducedMotion ? 'true' : 'false';
    root.dataset.contrast = next.contrast ? 'true' : 'false';
    root.dataset.forcedColors = next.forcedColors ? 'true' : 'false';
    root.dataset.dpr = String(next.dpr);
    root.dataset.retina = next.retina ? 'true' : 'false';
    root.dataset.viewport = `${next.width}x${next.height}`;
    root.dataset.foldable = next.foldable ? 'true' : 'false';
    if (next.foldSegments) {
        root.dataset.foldSegments = next.foldSegments;
    } else {
        delete root.dataset.foldSegments;
    }

    // A few px values are easier to use from CSS than re-reading the viewport.
    root.style.setProperty('--edu-vw', `${next.width}px`);
    root.style.setProperty('--edu-vh', `${next.height}px`);

    // Keep the browser UI colour in sync with the active theme. The layout
    // already ships both media variants; this covers a manual theme switch.
    document.querySelectorAll('meta[name="theme-color"]').forEach((meta) => {
        meta.setAttribute('content', next.theme === 'dark' ? '#0b1020' : '#4f46e5');
        meta.removeAttribute('media');
    });

    // Body also carries the network flag for the few components that use it.
    body?.classList.toggle('edu-is-offline', !next.online);
    body?.classList.toggle('edu-is-slow', next.network === 'slow');

    if (previous) {
        if (previous.device !== next.device) {
            emit('edu:device-changed', { next, previous });
        }
        if (previous.input !== next.input) {
            emit('edu:input-changed', { next, previous });
        }
        if (previous.orientation !== next.orientation) {
            emit('edu:orientation-changed', { next, previous });
        }
        if (previous.network !== next.network || previous.online !== next.online) {
            emit('edu:network-changed', { next, previous });
        }
        if (previous.theme !== next.theme) {
            emit('edu:theme-changed', { next, previous });
        }
    }
}

function emit(name, detail) {
    document.dispatchEvent(new CustomEvent(name, { detail }));
}

/* -----------------------------------------------------------------------------
 | Scheduling: one rAF + a short debounce for the burst of resize events
 | -------------------------------------------------------------------------- */

let frame = null;
let timer = null;

function scheduleUpdate() {
    if (frame !== null) {
        return;
    }

    frame = window.requestAnimationFrame(() => {
        frame = null;

        window.clearTimeout(timer);
        timer = window.setTimeout(update, 90);
    });
}

function update() {
    const previous = snapshot;
    snapshot = readSnapshot();
    writeAttributes(snapshot, previous);

    if (
        previous.width !== snapshot.width ||
        previous.height !== snapshot.height ||
        previous.orientation !== snapshot.orientation ||
        previous.breakpoint !== snapshot.breakpoint
    ) {
        reflow(previous, snapshot);
    }

    emit('edu:adapt', { next: snapshot, previous });
}

/* -----------------------------------------------------------------------------
 | Reflow: things that must be re-measured after rotation / resize / zoom
 | -------------------------------------------------------------------------- */

function reflow(previous, next) {
    emit('edu:viewport-changed', { next, previous });

    // Charts: Chart.js does this itself, but a 200% zoom or a rotation can
    // finish before the CSS layout settles. Ask every chart once more.
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        try {
            window.Chart?.getChart?.(canvas)?.resize();
        } catch (error) {
            // A chart that is still booting is fine; its own observer handles it.
        }
    });

    // Jitsi: the iframe is 100% of .jitsi-frame, so clearing an inline height
    // and notifying the room is enough for the new dvh calculation.
    document.querySelectorAll('[data-jitsi]').forEach((wrapper) => {
        const frameEl = wrapper.querySelector('.jitsi-frame');

        if (frameEl) {
            frameEl.style.removeProperty('height');
            void frameEl.offsetHeight; // force the new viewport unit to resolve
        }

        wrapper.dispatchEvent(new CustomEvent('edu:room-resize', {
            bubbles: true,
            detail: { next, previous },
        }));
    });

    // A drawer must not stay open when the window grows into the desktop
    // layout (or when a tablet rotates into landscape).
    if (next.breakpoint !== 'xs' && next.breakpoint !== 'sm' && next.breakpoint !== 'md') {
        const sidebar = document.getElementById('appSidebar');

        if (sidebar?.classList.contains('show')) {
            window.bootstrap?.Offcanvas?.getInstance(sidebar)?.hide();
        }
    }

    // Reset the mobile search panel when the desktop search box returns.
    if (window.matchMedia('(min-width: 768px)').matches) {
        const panel = document.getElementById('mobileSearchPanel');
        const toggle = document.querySelector('[data-mobile-search-toggle]');

        if (panel && !panel.hidden) {
            panel.hidden = true;
            toggle?.setAttribute('aria-expanded', 'false');
        }
    }

    // Data tables: recalculate whether the horizontal fade hint is needed.
    document.querySelectorAll('.table-responsive').forEach(syncTableHint);
}

/* -----------------------------------------------------------------------------
 | Network banner
 | -------------------------------------------------------------------------- */

let bannerTimer = null;

function banner() {
    return document.getElementById('eduNetworkBanner');
}

function hideBanner() {
    const el = banner();

    if (!el) {
        return;
    }

    el.hidden = true;
    el.classList.remove('is-visible');
}

function showBanner(kind) {
    const el = banner();

    if (!el) {
        return;
    }

    window.clearTimeout(bannerTimer);

    const text = kind === 'offline'
        ? el.dataset.offlineText
        : kind === 'slow'
            ? el.dataset.slowText
            : el.dataset.onlineText;

    const message = el.querySelector('[data-network-message]');
    if (message) {
        message.textContent = text || '';
    }

    el.dataset.state = kind;
    el.hidden = false;

    window.requestAnimationFrame(() => el.classList.add('is-visible'));

    // "Back online" is a confirmation, not a permanent bar.
    if (kind === 'online') {
        bannerTimer = window.setTimeout(hideBanner, 4000);
    }
}

function syncNetworkBanner(previous) {
    if (!navigator.onLine) {
        showBanner('offline');
        return;
    }

    if (isSlowNetwork()) {
        showBanner('slow');
        return;
    }

    if (previous && previous.online === false) {
        showBanner('online');
        return;
    }

    hideBanner();
}

function bootNetworkBanner() {
    const el = banner();

    if (!el) {
        return;
    }

    el.querySelector('[data-network-dismiss]')?.addEventListener('click', hideBanner);
    syncNetworkBanner(null);
}

/* -----------------------------------------------------------------------------
 | Touch gestures: edge-swipe for the sidebar, pull-to-refresh for the feed
 | -------------------------------------------------------------------------- */

function gesturesAllowed(target) {
    if (!target || !(target instanceof Element)) {
        return false;
    }

    return !target.closest(
        'input, textarea, select, [contenteditable="true"], .modal, .offcanvas, ' +
        '.table-responsive, .edu-scroll-x, .jitsi-frame, [data-no-swipe], [data-no-pull]'
    );
}

function bootSwipeSidebar() {
    const sidebar = document.getElementById('appSidebar');

    if (!sidebar) {
        return;
    }

    const rtl = root.dir === 'rtl';
    let startX = 0;
    let startY = 0;
    let tracking = false;
    let horizontal = false;

    document.addEventListener('touchstart', (event) => {
        if (event.touches.length !== 1 || !window.matchMedia('(max-width: 991.98px)').matches) {
            return;
        }

        const touch = event.touches[0];
        const edge = rtl ? window.innerWidth - touch.clientX : touch.clientX;

        tracking = edge <= 28 && gesturesAllowed(event.target);
        startX = touch.clientX;
        startY = touch.clientY;
        horizontal = false;
    }, { passive: true });

    document.addEventListener('touchmove', (event) => {
        if (!tracking || event.touches.length !== 1) {
            return;
        }

        const deltaX = event.touches[0].clientX - startX;
        const deltaY = event.touches[0].clientY - startY;

        if (Math.abs(deltaX) > 12 && Math.abs(deltaX) > Math.abs(deltaY) * 1.2) {
            horizontal = true;
            event.preventDefault(); // the drawer owns this gesture now
        }
    }, { passive: false });

    document.addEventListener('touchend', (event) => {
        if (!tracking) {
            return;
        }

        const touch = event.changedTouches[0];
        const deltaX = touch.clientX - startX;
        const deltaY = touch.clientY - startY;
        const open = rtl ? deltaX < -56 : deltaX > 56;

        if (horizontal && open && Math.abs(deltaY) < 70) {
            window.bootstrap?.Offcanvas?.getOrCreateInstance(sidebar)?.show();
        }

        tracking = false;
        horizontal = false;
    }, { passive: true });

    // Swipe the open drawer back towards its edge to close it.
    let drawerX = null;
    sidebar.addEventListener('touchstart', (event) => {
        if (event.touches.length === 1 && sidebar.classList.contains('show')) {
            drawerX = event.touches[0].clientX;
        }
    }, { passive: true });

    sidebar.addEventListener('touchend', (event) => {
        if (drawerX === null) {
            return;
        }

        const deltaX = event.changedTouches[0].clientX - drawerX;
        const close = rtl ? deltaX > 56 : deltaX < -56;

        if (close) {
            window.bootstrap?.Offcanvas?.getInstance(sidebar)?.hide();
        }

        drawerX = null;
    }, { passive: true });
}

function bootPullToRefresh() {
    const feed = document.querySelector('[data-feed-list]');

    if (!feed || !window.matchMedia('(pointer: coarse)').matches) {
        return;
    }

    const indicator = document.createElement('div');
    indicator.className = 'edu-pull-indicator';
    indicator.setAttribute('aria-hidden', 'true');
    indicator.innerHTML = '<i class="bi bi-arrow-clockwise" aria-hidden="true"></i>';
    document.body.appendChild(indicator);

    const threshold = 74;
    let startX = 0;
    let startY = 0;
    let pulling = false;
    let distance = 0;

    document.addEventListener('touchstart', (event) => {
        if (event.touches.length !== 1 || window.scrollY > 0 || !gesturesAllowed(event.target)) {
            return;
        }

        if (document.querySelector('.modal.show') || document.querySelector('dialog[open]')) {
            return;
        }

        startX = event.touches[0].clientX;
        startY = event.touches[0].clientY;
        pulling = false;
        distance = 0;
    }, { passive: true });

    document.addEventListener('touchmove', (event) => {
        if (event.touches.length !== 1 || startY === 0) {
            return;
        }

        const deltaY = event.touches[0].clientY - startY;
        const deltaX = event.touches[0].clientX - startX;

        if (deltaY <= 0 || window.scrollY > 0) {
            return;
        }

        // A diagonal / horizontal gesture belongs to the page, not the pull.
        if (Math.abs(deltaX) > Math.abs(deltaY)) {
            return;
        }

        pulling = true;
        distance = Math.min(130, deltaY * 0.55);
        indicator.style.setProperty('--edu-pull', `${distance}px`);
        indicator.style.transform = `translate(-50%, ${distance - 54}px) rotate(${distance * 2}deg)`;
        indicator.classList.add('is-visible');

        if (deltaY > 12) {
            event.preventDefault();
        }
    }, { passive: false });

    document.addEventListener('touchend', () => {
        if (!pulling) {
            startY = 0;
            return;
        }

        if (distance >= threshold) {
            indicator.classList.add('is-refreshing');
            window.setTimeout(() => window.location.reload(), 180);
        } else {
            indicator.classList.remove('is-visible');
            indicator.style.transform = '';
        }

        startX = 0;
        startY = 0;
        pulling = false;
        distance = 0;
    }, { passive: true });
}

/* -----------------------------------------------------------------------------
 | Form drafts on slow / metered connections
 | -------------------------------------------------------------------------- */

const DRAFT_PREFIX = 'educonnect.draft:';
const DRAFT_MAX_AGE = 24 * 60 * 60 * 1000;

function draftKey(form) {
    return DRAFT_PREFIX + window.location.pathname + ':' + (form.getAttribute('action') || '');
}

function shouldPersist(form) {
    const method = (form.getAttribute('method') || 'get').toLowerCase();

    return method === 'post' && !form.matches('[data-no-draft], [data-search-form], [data-like-form]');
}

function readDraft(form) {
    try {
        const raw = window.sessionStorage.getItem(draftKey(form));
        if (!raw) return null;

        const parsed = JSON.parse(raw);
        if (!parsed || Date.now() - Number(parsed.at) > DRAFT_MAX_AGE) {
            window.sessionStorage.removeItem(draftKey(form));
            return null;
        }

        return parsed.values || null;
    } catch (error) {
        return null;
    }
}

function saveDraft(form) {
    if (!shouldPersist(form)) {
        return;
    }

    const values = {};

    form.querySelectorAll('input, textarea, select').forEach((field) => {
        if (!field.name || field.disabled || field.type === 'password' || field.type === 'file') {
            return;
        }

        if (field.type === 'hidden' && field.name !== 'body') {
            // CSRF / method / status tokens are not useful across pages.
            return;
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            if (field.checked) {
                values[field.name] = values[field.name] || [];
                values[field.name].push(field.value);
            }
            return;
        }

        if (field.tagName === 'SELECT' && field.multiple) {
            values[field.name] = Array.from(field.selectedOptions).map((option) => option.value);
            return;
        }

        if (field.value !== '') {
            values[field.name] = field.value;
        }
    });

    try {
        if (Object.keys(values).length === 0) {
            window.sessionStorage.removeItem(draftKey(form));
            return;
        }

        window.sessionStorage.setItem(draftKey(form), JSON.stringify({ at: Date.now(), values }));
    } catch (error) {
        // Quota / private mode: drafts are a convenience, never a requirement.
    }
}

function restoreDraft(form) {
    if (!shouldPersist(form)) {
        return;
    }

    const values = readDraft(form);

    if (!values) {
        return;
    }

    Object.entries(values).forEach(([name, value]) => {
        form.querySelectorAll(`[name="${CSS.escape(name)}"]`).forEach((field) => {
            if (field.type === 'checkbox' || field.type === 'radio') {
                const list = Array.isArray(value) ? value : [value];
                if (list.includes(field.value)) {
                    field.checked = true;
                }
                return;
            }

            if (field.tagName === 'SELECT' && field.multiple && Array.isArray(value)) {
                Array.from(field.options).forEach((option) => {
                    option.selected = value.includes(option.value);
                });
                return;
            }

            // Never overwrite a value the server has already rendered (for
            // example after a validation error).
            if (field.value === '' && typeof value === 'string') {
                field.value = value;
            }
        });
    });
}

function bootFormDrafts() {
    const enhance = () => {
        if (!isSlowNetwork() && !isSaveData()) {
            return;
        }

        document.querySelectorAll('form').forEach((form) => {
            if (!shouldPersist(form) || form.dataset.draftBound === '1') {
                return;
            }

            form.dataset.draftBound = '1';
            restoreDraft(form);

            let timer = null;
            const queue = () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(() => saveDraft(form), 450);
            };

            form.addEventListener('input', queue);
            form.addEventListener('change', queue);

            form.addEventListener('submit', () => {
                try {
                    window.sessionStorage.removeItem(draftKey(form));
                } catch (error) {
                    // Nothing to clean up.
                }
            });
        });
    };

    enhance();

    // A connection that becomes slow after the page load still gets drafts.
    document.addEventListener('edu:network-changed', enhance);
}

/* -----------------------------------------------------------------------------
 | Keyboard shortcuts (desktop / laptop / TV) + helper dialog
 | -------------------------------------------------------------------------- */

function isTyping(target) {
    if (!target) {
        return false;
    }

    return target.matches?.('input, textarea, select, [contenteditable="true"]')
        || target.isContentEditable === true;
}

function focusSearch() {
    let input = Array.from(document.querySelectorAll('[data-search-input]'))
        .find((candidate) => candidate.offsetParent !== null);

    if (!input) {
        document.querySelector('[data-mobile-search-toggle]')?.click();
        input = Array.from(document.querySelectorAll('[data-search-input]'))
            .find((candidate) => candidate.offsetParent !== null);
    }

    input?.focus();
    input?.select?.();
}

function openShortcuts() {
    const dialog = document.getElementById('eduShortcutsDialog');

    if (dialog?.showModal) {
        dialog.showModal();
        dialog.querySelector('[data-dialog-close]')?.focus();
    }
}

function bootShortcuts() {
    let lastG = 0;

    document.addEventListener('keydown', (event) => {
        if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        if (root.dataset.device === 'mobile') {
            return;
        }

        const dialog = document.getElementById('eduShortcutsDialog');
        const dialogOpen = Boolean(dialog?.open);

        if (event.key === 'Escape') {
            if (dialogOpen) {
                dialog.close();
                return;
            }

            const sidebar = document.getElementById('appSidebar');
            if (sidebar?.classList.contains('show')) {
                window.bootstrap?.Offcanvas?.getInstance(sidebar)?.hide();
            }
            return;
        }

        if (dialogOpen || isTyping(event.target)) {
            return;
        }

        // "g" sequences: g d = dashboard, g f = feed, g n = notifications,
        // g p = profile. 1.2s window, exactly like GitHub.
        if (event.key.toLowerCase() === 'g') {
            lastG = Date.now();
            return;
        }

        if (Date.now() - lastG < 1200) {
            const destination = {
                d: body?.dataset.shortcutDashboard,
                f: body?.dataset.shortcutFeed,
                n: body?.dataset.shortcutNotifications,
                p: body?.dataset.shortcutProfile,
            }[event.key.toLowerCase()];

            if (destination) {
                event.preventDefault();
                window.location.assign(destination);
                return;
            }
        }

        if (event.key === '/') {
            event.preventDefault();
            focusSearch();
            return;
        }

        if (event.key === '?' || (event.key === '/' && event.shiftKey)) {
            event.preventDefault();
            openShortcuts();
            return;
        }

        if (event.key.toLowerCase() === 'n' && body?.dataset.shortcutNewPost) {
            event.preventDefault();
            window.location.assign(body.dataset.shortcutNewPost);
        }
    });

    document.querySelectorAll('[data-shortcuts-open]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            openShortcuts();
        });
    });
}

/* -----------------------------------------------------------------------------
 | TV / D-pad spatial navigation
 | -------------------------------------------------------------------------- */

function focusableElements() {
    const selector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
        'summary',
    ].join(',');

    return Array.from(document.querySelectorAll(selector)).filter((element) => {
        if (element.closest('[hidden], .d-none, dialog:not([open])')) {
            return false;
        }

        const rect = element.getBoundingClientRect();

        return rect.width > 0 && rect.height > 0 && window.getComputedStyle(element).visibility !== 'hidden';
    });
}

function moveFocus(direction) {
    const elements = focusableElements();

    if (elements.length === 0) {
        return;
    }

    const current = document.activeElement?.matches?.('a[href],button,input,select,textarea,[tabindex],summary')
        ? document.activeElement
        : null;

    if (!current) {
        elements[0].focus();
        return;
    }

    const from = current.getBoundingClientRect();
    const fromX = from.left + from.width / 2;
    const fromY = from.top + from.height / 2;

    let best = null;
    let bestScore = Infinity;

    elements.forEach((element) => {
        if (element === current) {
            return;
        }

        const rect = element.getBoundingClientRect();
        const x = rect.left + rect.width / 2;
        const y = rect.top + rect.height / 2;
        const dx = x - fromX;
        const dy = y - fromY;

        const inDirection = {
            ArrowUp: dy < -4,
            ArrowDown: dy > 4,
            ArrowLeft: dx < -4,
            ArrowRight: dx > 4,
        }[direction];

        if (!inDirection) {
            return;
        }

        const primary = direction === 'ArrowUp' || direction === 'ArrowDown' ? Math.abs(dy) : Math.abs(dx);
        const secondary = direction === 'ArrowUp' || direction === 'ArrowDown' ? Math.abs(dx) : Math.abs(dy);
        const score = primary + secondary * 2.2;

        if (score < bestScore) {
            best = element;
            bestScore = score;
        }
    });

    if (best) {
        best.focus({ preventScroll: true });
        best.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }
}

function bootSpatialNavigation() {
    document.addEventListener('keydown', (event) => {
        if (root.dataset.device !== 'tv' || event.defaultPrevented || isTyping(event.target)) {
            return;
        }

        if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(event.key)) {
            return;
        }

        event.preventDefault();
        moveFocus(event.key);
    });
}

/* -----------------------------------------------------------------------------
 | Install prompt + service worker update UI
 | -------------------------------------------------------------------------- */

let installEvent = null;

function installButtons() {
    return document.querySelectorAll('[data-install-app]');
}

function setInstallVisible(visible) {
    installButtons().forEach((button) => {
        button.hidden = !visible;
        button.classList.toggle('d-none', !visible);
        button.setAttribute('aria-hidden', visible ? 'false' : 'true');
    });
}

function bootInstallPrompt() {
    const isIos = IOS_UA.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    if (snapshot.standalone) {
        setInstallVisible(false);
        return;
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installEvent = event;
        setInstallVisible(true);
    });

    window.addEventListener('appinstalled', () => {
        installEvent = null;
        setInstallVisible(false);
        emit('edu:app-installed', {});
    });

    // iOS Safari never fires beforeinstallprompt; show the button and explain
    // the Share -> "Add to Home Screen" route instead of a dead button.
    if (isIos) {
        setInstallVisible(true);
    }

    installButtons().forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();

            if (installEvent) {
                installEvent.prompt();

                try {
                    const choice = await installEvent.userChoice;
                    if (choice?.outcome === 'accepted') {
                        setInstallVisible(false);
                    }
                } catch (error) {
                    // The browser dismissed the prompt; leave the button visible.
                } finally {
                    installEvent = null;
                }

                return;
            }

            const help = document.getElementById('eduInstallDialog');
            if (help?.showModal) {
                help.showModal();
                help.querySelector('[data-dialog-close]')?.focus();
            }
        });
    });
}

function bootServiceWorker() {
    if (!import.meta.env.PROD || !('serviceWorker' in navigator)) {
        return;
    }

    if (location.protocol !== 'https:' && !['localhost', '127.0.0.1', '[::1]'].includes(location.hostname)) {
        return;
    }

    let reloading = false;

    const showUpdate = (registration) => {
        if (document.getElementById('eduSwUpdate')) {
            return;
        }

        const box = document.createElement('div');
        box.id = 'eduSwUpdate';
        box.className = 'edu-sw-update';
        box.setAttribute('role', 'status');

        const text = document.createElement('span');
        text.textContent = body?.dataset.swUpdateText || 'A new version of EduConnect is ready.';

        const reload = document.createElement('button');
        reload.type = 'button';
        reload.className = 'btn btn-sm btn-primary';
        reload.textContent = body?.dataset.swReloadText || 'Reload';

        reload.addEventListener('click', () => {
            registration.waiting?.postMessage({ type: 'SKIP_WAITING' });
            reload.disabled = true;
            reload.textContent = body?.dataset.swReloadingText || 'Reloading…';
        });

        const dismiss = document.createElement('button');
        dismiss.type = 'button';
        dismiss.className = 'btn btn-sm btn-link text-body';
        dismiss.textContent = body?.dataset.swLaterText || 'Later';
        dismiss.addEventListener('click', () => box.remove());

        box.append(text, reload, dismiss);
        document.body.appendChild(box);
    };

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (reloading) {
            return;
        }

        reloading = true;
        window.location.reload();
    });

    navigator.serviceWorker.register('/sw.js', { scope: '/' }).then((registration) => {
        if (registration.waiting && navigator.serviceWorker.controller) {
            showUpdate(registration);
        }

        registration.addEventListener('updatefound', () => {
            const worker = registration.installing;

            worker?.addEventListener('statechange', () => {
                if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                    showUpdate(registration);
                }
            });
        });
    }).catch((error) => {
        console.warn('[EduConnect] service worker registration failed', error);
    });
}

/* -----------------------------------------------------------------------------
 | Table scroll hints (the fade only appears when there is more to scroll)
 | -------------------------------------------------------------------------- */

function syncTableHint(container) {
    if (!(container instanceof Element)) {
        return;
    }

    const max = container.scrollWidth - container.clientWidth;

    container.classList.toggle('is-scrollable', max > 4);
    container.classList.toggle('is-at-start', container.scrollLeft <= 4);
    container.classList.toggle('is-at-end', container.scrollLeft >= max - 4);
}

function bootTableHints() {
    const containers = document.querySelectorAll('.table-responsive');

    containers.forEach((container) => {
        if (!container.hasAttribute('tabindex')) {
            container.setAttribute('tabindex', '0');
        }

        syncTableHint(container);
        container.addEventListener('scroll', () => syncTableHint(container), { passive: true });
    });

    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver((entries) => {
            entries.forEach((entry) => syncTableHint(entry.target));
        });

        containers.forEach((container) => observer.observe(container));
    }
}

/* -----------------------------------------------------------------------------
 | Large-text preference
 | -------------------------------------------------------------------------- */

function syncLargeTextButtons() {
    const active = root.classList.contains('edu-large-text');

    document.querySelectorAll('[data-large-text-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', active ? 'true' : 'false');

        const label = active ? button.dataset.labelOff : button.dataset.labelOn;
        if (label) {
            button.setAttribute('title', label);
            button.setAttribute('aria-label', label);
        }

        // Menu entries show their label as text; icon-only buttons do not.
        const text = button.querySelector('[data-large-text-label]');
        if (text && label) {
            text.textContent = label;
        }
    });
}

function bootLargeText() {
    try {
        if (window.localStorage.getItem('educonnect.largeText') === '1') {
            root.classList.add('edu-large-text');
        }
    } catch (error) {
        // Private mode: the default text size is still perfectly usable.
    }

    document.querySelectorAll('[data-large-text-toggle]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();

            const active = root.classList.toggle('edu-large-text');

            try {
                window.localStorage.setItem('educonnect.largeText', active ? '1' : '0');
            } catch (error) {
                // The class still applies for this visit.
            }

            syncLargeTextButtons();
            emit('edu:large-text-changed', { active });
        });
    });

    syncLargeTextButtons();
}

/* -----------------------------------------------------------------------------
 | Blur-up media: the placeholder never hides a failed image, and lazy images
 | below the fold keep their gradient until they really load.
 | -------------------------------------------------------------------------- */

function bootBlurUp() {
    document.querySelectorAll('img[data-blur-up]').forEach((img) => {
        const media = img.closest('.edu-post-media');

        if (!media) {
            return;
        }

        const done = () => media.classList.add('is-loaded');

        if (img.complete && img.naturalWidth > 0) {
            done();
        } else {
            img.addEventListener('load', done, { once: true });
            img.addEventListener('error', done, { once: true });
        }
    });
}

/* -----------------------------------------------------------------------------
 | Bootstrap + lifecycle wiring
 | -------------------------------------------------------------------------- */

/* -----------------------------------------------------------------------------
 | Keyboard focus mode: switch richer focus rings on when Tab is used,
 | revert to minimal rings when mouse/touch takes over.
 | -------------------------------------------------------------------------- */

function bootKeyboardFocusMode() {
    let usingKeyboard = false;

    const activate = () => {
        if (!usingKeyboard) {
            usingKeyboard = true;
            root.classList.add('edu-keyboard');
        }
    };

    const deactivate = () => {
        if (usingKeyboard) {
            usingKeyboard = false;
            root.classList.remove('edu-keyboard');
        }
    };

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Tab') activate();
    }, { passive: true });

    document.addEventListener('mousedown', deactivate, { passive: true });
    document.addEventListener('touchstart', deactivate, { passive: true });
    document.addEventListener('pointerdown', (event) => {
        if (event.pointerType !== 'mouse') return;
        deactivate();
    }, { passive: true });
}

/* -----------------------------------------------------------------------------
 | Media query binding
 | -------------------------------------------------------------------------- */

function bindMedia() {
    const queries = Object.values(media);

    queries.forEach((query) => {
        if (typeof query.addEventListener === 'function') {
            query.addEventListener('change', scheduleUpdate);
        } else if (typeof query.addListener === 'function') {
            query.addListener(scheduleUpdate);
        }
    });

    // Foldable / viewport-segments change listeners
    [
        '(horizontal-viewport-segments: 2)',
        '(vertical-viewport-segments: 2)',
        '(spanning: single-fold-horizontal)',
        '(spanning: single-fold-vertical)',
    ].forEach((q) => {
        try {
            const mq = window.matchMedia(q);
            if (typeof mq.addEventListener === 'function') {
                mq.addEventListener('change', scheduleUpdate);
            }
        } catch (e) {
            // Browser doesn't support this query yet — ignore silently.
        }
    });
}

function boot() {
    snapshot = readSnapshot();
    writeAttributes(snapshot, null);

    bindMedia();

    window.addEventListener('resize', scheduleUpdate, { passive: true });
    window.addEventListener('orientationchange', () => {
        // iOS reports the old size during orientationchange; re-check next tick.
        scheduleUpdate();
        window.setTimeout(scheduleUpdate, 220);
    }, { passive: true });
    window.addEventListener('online', () => {
        update();
        syncNetworkBanner(snapshot);
    });
    window.addEventListener('offline', () => {
        update();
        showBanner('offline');
    });

    connection()?.addEventListener?.('change', update);

    // The theme module owns data-bs-theme; mirror it for CSS that prefers
    // data-theme (and for the manifest surface colour).
    document.addEventListener('edu:theme-changed', () => {
        const previous = snapshot;
        snapshot = { ...snapshot, theme: currentTheme() };
        writeAttributes(snapshot, previous);
    });

    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver(scheduleUpdate);
        observer.observe(root);
    }

    bootNetworkBanner();
    bootSwipeSidebar();
    bootPullToRefresh();
    bootFormDrafts();
    bootShortcuts();
    bootSpatialNavigation();
    bootInstallPrompt();
    bootServiceWorker();
    bootTableHints();
    bootLargeText();
    bootBlurUp();
    bootKeyboardFocusMode();

    // Expose a tiny, stable API for the other modules. No globals leak beyond
    // this one object, and every method is safe on unsupported browsers.
    window.EduAdaptive = {
        get snapshot() {
            return snapshot;
        },
        isSlow: () => snapshot.network === 'slow' || snapshot.saveData,
        isOffline: () => !snapshot.online,
        isTouch: () => snapshot.input === 'touch',
        isCompact: () => ['xs', 'sm', 'md'].includes(snapshot.breakpoint),
        isPortrait: () => snapshot.orientation === 'portrait',
        isStandalone: () => snapshot.standalone,
        isFoldable: () => snapshot.foldable,
        foldSegments: () => snapshot.foldSegments,
        pollInterval: (base) => (snapshot.network === 'slow' ? Math.max(base * 3, 60000) : base),
        update,
        openShortcuts,
    };

    root.dataset.adaptiveReady = 'true';
    document.dispatchEvent(new CustomEvent('edu:adaptive-ready', { detail: { snapshot } }));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
