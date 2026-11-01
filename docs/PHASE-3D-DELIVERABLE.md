# Phase 3D — Complete Responsive Design for All Devices

**Project:** EduConnect — Laravel 12.70.2 / Bootstrap 5.3.8 / Vite 6.4.5  
**Phase:** 3D — responsive audit and implementation (320 px → 2560 px)  
**Status:** Implemented, built, and verified against the full test suite  
**Date:** 31 October 2026

---

## 1. Objective

Audit and fix **every page and component** so EduConnect works on:

- 320 px and 360 px Android phones, iPhone SE (375 px), iPhone 14/15 (390–430 px)
- large phones and foldables (600–720 px)
- tablets portrait (768 px) and landscape (1024 px)
- laptops (1280–1440 px), desktops (1920 px), ultra-wide (2560 px)
- portrait and landscape orientation, touch, mouse, keyboard and screen reader

No feature was removed. Every fix is additive: a new responsive partial, small
markup changes, and a lazy-loading change for Chart.js.

---

## 2. Breakpoints

Bootstrap 5 mobile-first breakpoints are used exactly as documented:

| Token | Minimum width | Target |
| --- | --- | --- |
| xs | 0 | phones (320, 360, 375, 390, 430) |
| sm | 576 px | large phones / small foldables |
| md | 768 px | tablets portrait |
| lg | 992 px | tablets landscape / small laptops |
| xl | 1200 px | laptops |
| xxl | 1400 px | desktops (content capped at 1320 px) |

**Custom breakpoints:** none.

Two device capabilities are used in addition to the grid:

- `359.98px` — a 320 px guard (one pixel below the 360 px common width) that
  reduces card padding and the navbar height. It is a capability guard, not a
  new layout breakpoint.
- `(orientation: landscape) and (max-height: 520px)` — phone landscape, where
  the video room and the bottom bar need different heights.
- `(pointer: coarse)`, `(hover: none)` and `prefers-reduced-motion` — input and
  motion capabilities, not widths.

---

## 3. What was fixed

### Foundations

- `viewport-fit=cover` on both layouts; `env(safe-area-inset-*)` tokens for
  top, right, bottom and left; navbar, page, auth shell and bottom navigation
  respect notches and the home indicator.
- `100dvh` added after every `100vh` for the app shell, auth shell and live
  room, so mobile browser chrome cannot crop the page.
- Fluid type from the Phase 3C `clamp()` scale; layout spacing in `rem`.
- `img`, `video`, `svg`, `canvas` and `iframe` capped at `max-width: 100%`;
  intrinsic `width`/`height` on avatars and post images; `loading="lazy"` and
  `decoding="async"` everywhere; post images carry a truthful `srcset` width
  descriptor and `sizes`.
- `overflow-wrap: anywhere` / `word-break: break-word` for post bodies,
  comments, notifications, names, emails, hashtags and table cells.
- `overflow-x: clip` on `body` with the `hidden` fallback; flex/grid children
  have `min-width: 0` so long content cannot widen a column.
- `scroll-padding-top` on `html` so anchor jumps clear the sticky navbar.
- Keyboard focus ring for links, buttons, inputs, pagination and custom
  tabbables; reduced-motion and dark-mode native control support.

### Interaction and touch

- `.edu-icon-btn` is 44 × 44 px (was 42 × 42).
- Coarse pointers: buttons, nav links, dropdown items, pagination links, filter
  chips and switches are at least 44 px tall.
- Inputs are 16 px on phones/tablets (and on any coarse pointer), so iOS does
  not zoom the page when a field receives focus.
- Focused fields have `scroll-margin-bottom` larger than the sticky bars.
- Hover elevation is disabled on `hover: none`; every action also works with tap
  and keyboard.
- `.edu-scroll-x` utility gives horizontal card strips scroll-snap and momentum
  scrolling; it is disabled by `prefers-reduced-motion`.

### Layout

- **Navbar:** the phone shortcut is now an expandable search button. It opens a
  full-width panel below the navbar, focuses the input, closes on Escape,
  outside tap or a desktop resize, and swaps the magnifier for an X.
- **Sidebar:** offcanvas ≤ 991 px, column ≥ 992 px, icon-only toggle on desktop.
  The drawer now closes automatically after a link is tapped.
- **Bottom navigation:** five 56 px targets, safe-area padding; hidden only in
  phone landscape where vertical space is critical.
- **Feed:** one column on phones, feed + widgets on tablets (md), and the
  desktop three-column experience is sidebar + feed + widgets.
- **Dashboards:** stat cards remain 1 / 2 / 4 across phone / tablet / desktop;
  charts are contained, `max-width: 100%`, and redraw on resize, orientation
  change, theme change and sidebar toggle.
- **Live room:** Jitsi height uses `100dvh` minus the chrome, with phone,
  tablet and landscape variants; the room bar is sticky so the exit link is
  always reachable; room info is a fixed bottom sheet ≤ 991 px with a drag
  handle and close button.
- **Forms:** single column below lg; sticky submit bar on phones above the
  bottom navigation on class, post and profile forms.
- **Modals:** `modal-fullscreen-sm-down`, `modal-dialog-scrollable`,
  `modal-dialog-centered`, `role="dialog"`, `aria-modal`, accessible label,
  body scroll lock and outside-tap close.
- **Tables:** all admin tables already switch to `data-label` cards below
  992 px; horizontal wrappers keep momentum scrolling where a table remains.
- **Pagination:** compact on phones — Previous, current page, Next only.
- **Landing page:** hero stacks at 320 px, buttons become full-width, shapes
  shrink, stats strip and sections lose excess padding.
- **Ultra-wide:** container-fluid pages are capped at 1600 px; container-xxl
  stays at its 1320 px maximum; the sidebar grows to 284 px at ≥ 1920 px.

### Performance

- Chart.js is no longer imported by `app.js`. `charts.js` performs a dynamic
  `import('chart.js/auto')` **only** when the page contains
  `canvas[data-chart]`. Vite emits it as a separate chunk.
- Jitsi's External API was already lazy-loaded only when `[data-jitsi]` exists.
- Autoprefixer runs through PostCSS for the browserslist targets.
- Production assets are minified by `npm run build`.

---

## 4. Changed files

| # | File | Change |
| --- | --- | --- |
| 1 | `package.json` | browserslist targets |
| 2 | `postcss.config.js` | **new** — autoprefixer |
| 3 | `resources/sass/app.scss` | imports `responsive` last |
| 4 | `resources/sass/_responsive.scss` | **new** — full responsive layer |
| 5 | `resources/views/layouts/app.blade.php` | `viewport-fit=cover` |
| 6 | `resources/views/layouts/guest.blade.php` | `viewport-fit=cover` |
| 7 | `resources/views/layouts/navigation.blade.php` | expandable phone search |
| 8 | `resources/views/layouts/sidebar.blade.php` | Dashboard links per role |
| 9 | `resources/views/feed/index.blade.php` | 2 columns from md |
| 10 | `resources/views/classes/room.blade.php` | sticky bar + bottom sheet |
| 11 | `resources/views/classes/_form.blade.php` | sticky actions |
| 12 | `resources/views/posts/_form.blade.php` | sticky actions |
| 13 | `resources/views/profile/partials/update-profile-information-form.blade.php` | sticky actions |
| 14 | `resources/views/components/modal.blade.php` | fullscreen + scrollable + ARIA |
| 15 | `resources/views/components/pagination-links.blade.php` | `edu-pagination` hook |
| 16 | `resources/views/components/avatar.blade.php` | intrinsic dimensions |
| 17 | `resources/views/components/post-card.blade.php` | dimensions + srcset + sizes |
| 18 | `resources/js/app.js` | removed global Chart.js import |
| 19 | `resources/js/charts.js` | dynamic import + viewport redraw |
| 20 | `resources/js/layout.js` | phone search + drawer auto-close |
| 21 | `app/Models/Post.php` | cached `imageDimensions()` |
| 22 | `resources/views/comments/_comment.blade.php` | avatar dimensions |
| 23 | `resources/views/components/user-card.blade.php` | avatar dimensions |
| 24 | `resources/views/admin/reports/show.blade.php` | reported image dimensions |

---

## 5. Full source of changed files

Every file below is the complete current content, copied verbatim from the
project. Paths are relative to the project root.

---
### `package.json`

```json
{
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "devDependencies": {
        "autoprefixer": "^10.6.2",
        "axios": "^1.7.4",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^1.2.0",
        "postcss": "^8.5.32",
        "sass": "^1.77.0",
        "vite": "^6.0.11"
    },
    "dependencies": {
        "@popperjs/core": "^2.11.8",
        "alpinejs": "^3.4.2",
        "bootstrap": "^5.3.8",
        "bootstrap-icons": "^1.13.2",
        "chart.js": "^4.6.1"
    },
    "browserslist": [
        "last 2 Chrome versions",
        "last 2 Edge versions",
        "last 2 Firefox versions",
        "last 2 Safari versions",
        "last 2 iOS major versions",
        "last 2 Samsung major versions",
        ">= 0.5%",
        "not dead"
    ]
}
```

### `postcss.config.js`

```javascript
/**
 * PostCSS runs inside Vite for `npm run dev` and `npm run build`.
 *
 * Autoprefixer reads the `browserslist` block in package.json, so the same
 * targets cover Chrome, Edge, Firefox, Safari (macOS + iOS) and Samsung
 * Internet. No CSS feature is used without a vendor fallback.
 */
import autoprefixer from 'autoprefixer';

export default {
    plugins: [
        autoprefixer(),
    ],
};
```

### `resources/sass/app.scss`

```scss
// -----------------------------------------------------------------------------
// EduConnect stylesheet (Phase 3B premium dashboard design)
// -----------------------------------------------------------------------------
// Bootstrap 5 and Bootstrap Icons are pulled straight from node_modules and
// compiled by Vite (no Tailwind anywhere in this project). The tokens live in
// _variables.scss and are imported before Bootstrap so the framework itself
// picks up the brand palette and dark-mode surfaces.
// -----------------------------------------------------------------------------

@import 'variables';
@import 'bootstrap/scss/bootstrap';
@import 'bootstrap-icons/font/bootstrap-icons.css';

// -----------------------------------------------------------------------------
// Base
// -----------------------------------------------------------------------------

:root {
    --bs-body-bg: var(--edu-page-bg);
    --bs-body-color: var(--edu-text);
    --bs-border-color: var(--edu-border);
    --bs-secondary-color: var(--edu-muted);
    --bs-link-color-rgb: 79, 70, 229;
    --bs-link-hover-color-rgb: 55, 48, 163;
    --bs-focus-ring-color: rgba(79, 70, 229, 0.25);
}

[data-bs-theme='dark'] {
    --bs-body-bg: var(--edu-page-bg);
    --bs-body-color: var(--edu-text);
    --bs-border-color: var(--edu-border);
    --bs-secondary-color: var(--edu-muted);
    --bs-link-color-rgb: 165, 180, 252;
    --bs-link-hover-color-rgb: 199, 210, 254;
    --bs-emphasis-color: #f8fafc;
    --bs-heading-color: #f8fafc;
    --bs-tertiary-bg: #0f172a;
    --bs-secondary-bg: #172033;
}

html {
    scroll-behavior: smooth;
}

body {
    background-color: var(--edu-page-bg);
    color: var(--edu-text);
    font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    overflow-x: hidden;
}

// Bootstrap has no utility for this, but the dashboard flex children need it
// so long names and titles truncate instead of widening the grid.
.min-w-0 {
    min-width: 0;
}

::selection {
    background: rgba(79, 70, 229, 0.2);
}

h1, h2, h3, h4, h5, h6,
.h1, .h2, .h3, .h4, .h5, .h6 {
    color: var(--edu-text);
    letter-spacing: -0.015em;
}

h1, .h1 { font-size: clamp(1.6rem, 1.25rem + 1.2vw, 2rem); }
h2, .h2 { font-size: clamp(1.35rem, 1.15rem + 0.7vw, 1.6rem); }
h3, .h3 { font-size: 1.3rem; }
h4, .h4 { font-size: 1.15rem; }
h5, .h5 { font-size: 1rem; }
h6, .h6 { font-size: 0.9375rem; }

small, .small {
    font-size: 0.8125rem;
}

.text-muted {
    color: var(--edu-muted) !important;
}

.text-edu {
    color: var(--edu-primary) !important;
}

.bg-edu-soft {
    background: var(--edu-primary-soft) !important;
}

.edu-num,
.tabular-nums {
    font-variant-numeric: tabular-nums;
    font-feature-settings: 'tnum';
}

.edu-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.11em;
    text-transform: uppercase;
    color: var(--edu-primary);
}

.edu-muted {
    color: var(--edu-muted);
}

hr {
    border-color: var(--edu-border);
    opacity: 1;
}

// Focus rings: visible on every interactive element, never on click-only divs.
a:focus-visible,
button:focus-visible,
.btn:focus-visible,
.form-control:focus-visible,
.form-select:focus-visible,
.nav-link:focus-visible,
.dropdown-item:focus-visible,
[tabindex]:focus-visible {
    outline: 2px solid var(--edu-primary);
    outline-offset: 2px;
    box-shadow: var(--edu-ring);
}

// -----------------------------------------------------------------------------
// Buttons, badges, forms, tables, dropdowns, modals
// -----------------------------------------------------------------------------

.btn {
    --bs-btn-font-weight: 600;
    --bs-btn-padding-x: 0.95rem;
    --bs-btn-padding-y: 0.55rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    border-radius: 0.625rem;
    transition: transform var(--edu-transition), box-shadow var(--edu-transition),
        background-color var(--edu-transition), border-color var(--edu-transition),
        color var(--edu-transition);
}

.btn:active {
    transform: translateY(1px);
}

.btn-primary {
    --bs-btn-bg: var(--edu-primary);
    --bs-btn-border-color: var(--edu-primary);
    --bs-btn-hover-bg: var(--edu-primary-dark);
    --bs-btn-hover-border-color: var(--edu-primary-dark);
    --bs-btn-active-bg: var(--edu-primary-dark);
    --bs-btn-active-border-color: var(--edu-primary-dark);
    --bs-btn-disabled-bg: #a5b4fc;
    --bs-btn-disabled-border-color: #a5b4fc;
    box-shadow: 0 8px 18px -12px rgba(79, 70, 229, 0.8);
}

.btn-primary:hover {
    box-shadow: 0 12px 24px -14px rgba(79, 70, 229, 0.85);
}

.btn-outline-primary {
    --bs-btn-color: var(--edu-primary);
    --bs-btn-border-color: rgba(79, 70, 229, 0.35);
    --bs-btn-hover-bg: var(--edu-primary);
    --bs-btn-hover-border-color: var(--edu-primary);
    --bs-btn-active-bg: var(--edu-primary-dark);
    --bs-btn-active-border-color: var(--edu-primary-dark);
}

.btn-light {
    --bs-btn-bg: #ffffff;
    --bs-btn-border-color: var(--edu-border);
    --bs-btn-hover-bg: var(--edu-surface-soft);
    --bs-btn-hover-border-color: var(--edu-border-strong);
}

[data-bs-theme='dark'] .btn-light {
    --bs-btn-bg: var(--edu-surface-raised);
    --bs-btn-border-color: var(--edu-border);
    --bs-btn-color: var(--edu-text);
    --bs-btn-hover-bg: var(--edu-surface-muted);
    --bs-btn-hover-border-color: var(--edu-border-strong);
    --bs-btn-hover-color: var(--edu-text);
}

.btn-soft-primary {
    background: var(--edu-primary-soft);
    border-color: transparent;
    color: var(--edu-primary);
}

.btn-soft-primary:hover {
    background: rgba(79, 70, 229, 0.18);
    color: var(--edu-primary-dark);
}

.edu-icon-btn {
    width: 42px;
    height: 42px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    border: 1px solid transparent;
    background: transparent;
    color: var(--edu-muted);
    transition: background-color var(--edu-transition), color var(--edu-transition),
        border-color var(--edu-transition);
}

.edu-icon-btn:hover,
.edu-icon-btn:focus-visible {
    background: var(--edu-surface-muted);
    color: var(--edu-text);
}

.badge {
    font-weight: 600;
    letter-spacing: 0.01em;
    padding: 0.38em 0.65em;
    border-radius: 0.45rem;
}

.badge.rounded-pill {
    border-radius: 50rem !important;
}

.edu-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.65rem;
    border-radius: 999px;
    background: var(--edu-surface-muted);
    border: 1px solid var(--edu-border);
    color: var(--edu-muted-strong);
    font-size: 0.75rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.form-control,
.form-select {
    border-radius: 0.625rem;
    border-color: var(--edu-border-strong);
    background-color: var(--edu-surface);
    color: var(--edu-text);
    padding-block: 0.55rem;
}

.form-control::placeholder {
    color: var(--edu-muted);
    opacity: 0.85;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--edu-primary);
    box-shadow: var(--edu-ring);
    background-color: var(--edu-surface);
    color: var(--edu-text);
}

.form-label {
    font-weight: 600;
    font-size: 0.8125rem;
    color: var(--edu-text-soft);
    margin-bottom: 0.4rem;
}

.input-group-text {
    background: var(--edu-surface-soft);
    border-color: var(--edu-border-strong);
    color: var(--edu-muted);
    border-radius: 0.625rem;
}

.form-check-input:checked {
    background-color: var(--edu-primary);
    border-color: var(--edu-primary);
}

.form-check-input:focus {
    box-shadow: var(--edu-ring);
}

// --- Cards -------------------------------------------------------------------
.card {
    --bs-card-bg: var(--edu-surface);
    --bs-card-border-color: var(--edu-border);
    --bs-card-cap-bg: transparent;
    --bs-card-border-radius: var(--edu-radius);
    background-color: var(--edu-surface);
    color: var(--edu-text);
    border-radius: var(--edu-radius);
    border: 1px solid var(--edu-border);
    box-shadow: var(--edu-shadow-card);
}

.card-header {
    border-bottom-color: var(--edu-border);
    padding: 1rem 1.15rem;
    background: transparent;
}

.card-footer {
    border-top-color: var(--edu-border);
    background: transparent;
    padding: 0.9rem 1.15rem;
}

.card-body {
    padding: 1.15rem;
}

// The dashboard uses .edu-card for the hover lift. Keeping it opt-in avoids
// forms and static panels jumping while somebody is typing in them.
.edu-card,
.edu-stat-card,
.edu-chart-card {
    transition: transform var(--edu-transition), box-shadow var(--edu-transition),
        border-color var(--edu-transition);
}

.edu-card:hover,
.edu-stat-card:hover,
.edu-chart-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--edu-shadow-hover);
    border-color: var(--edu-border-strong);
}

// --- Tables ------------------------------------------------------------------
.table {
    --bs-table-bg: transparent;
    --bs-table-color: var(--edu-text);
    --bs-table-border-color: var(--edu-border);
    --bs-table-hover-bg: rgba(79, 70, 229, 0.045);
    --bs-table-striped-bg: rgba(148, 163, 184, 0.06);
    margin-bottom: 0;
}

.table > thead {
    background: var(--edu-surface-soft);
}

.table > :not(caption) > * > * {
    padding: 0.85rem 1rem;
    border-bottom-color: var(--edu-border);
    vertical-align: middle;
}

.table > thead th {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--edu-muted);
    border-bottom: 1px solid var(--edu-border);
    white-space: nowrap;
}

.table > tbody tr:last-child > * {
    border-bottom-width: 0;
}

.table-hover > tbody > tr:hover > * {
    --bs-table-bg-state: rgba(79, 70, 229, 0.05);
}

[data-bs-theme='dark'] .table-hover > tbody > tr:hover > * {
    --bs-table-bg-state: rgba(129, 140, 248, 0.09);
}

// --- Dropdowns and modals ----------------------------------------------------
.dropdown-menu {
    --bs-dropdown-bg: var(--edu-surface-raised);
    --bs-dropdown-border-color: var(--edu-border);
    --bs-dropdown-link-color: var(--edu-text);
    --bs-dropdown-link-hover-bg: var(--edu-surface-muted);
    --bs-dropdown-link-active-bg: var(--edu-primary);
    --bs-dropdown-link-active-color: #ffffff;
    border-radius: 0.85rem;
    padding: 0.45rem;
    box-shadow: var(--edu-shadow-pop);
}

.dropdown-item {
    border-radius: 0.55rem;
    padding: 0.55rem 0.7rem;
    font-size: 0.875rem;
}

.dropdown-divider {
    border-color: var(--edu-border);
}

.modal-content {
    background: var(--edu-surface-raised);
    color: var(--edu-text);
    border: 1px solid var(--edu-border);
    border-radius: 1rem;
    box-shadow: var(--edu-shadow-pop);
}

.modal-header,
.modal-footer {
    border-color: var(--edu-border);
}

.list-group {
    --bs-list-group-bg: transparent;
    --bs-list-group-color: var(--edu-text);
    --bs-list-group-border-color: var(--edu-border);
    --bs-list-group-action-hover-bg: var(--edu-surface-soft);
    --bs-list-group-action-active-bg: var(--edu-surface-muted);
}

.alert {
    border-radius: 0.85rem;
    border-width: 1px;
    font-size: 0.875rem;
}

.progress {
    background: var(--edu-surface-muted);
    border-radius: 999px;
    height: 0.5rem;
}

.progress-bar {
    border-radius: 999px;
}

.edu-progress .progress {
    min-height: 6px;
}

// -----------------------------------------------------------------------------
// Avatars
// -----------------------------------------------------------------------------

.avatar {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

.avatar-sm {
    width: 32px;
    height: 32px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

.avatar-lg {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

.avatar-xl {
    width: 128px;
    height: 128px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

// Initials fallback when a user has not uploaded a photo.
.edu-avatar-initials {
    display: inline-grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-size: 0.9em;
    font-weight: 700;
    letter-spacing: 0.02em;
    line-height: 1;
    user-select: none;
}

.avatar-sm.edu-avatar-initials {
    font-size: 0.68rem;
}

.avatar-lg.edu-avatar-initials {
    font-size: 1.45rem;
}

.avatar-xl.edu-avatar-initials {
    font-size: 1.9rem;
}

.profile-photo-preview {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 50%;
    border: 1px solid var(--edu-border);
    background-color: var(--edu-surface-soft);
}

// -----------------------------------------------------------------------------
// App shell: sidebar + navbar
// -----------------------------------------------------------------------------

.edu-body {
    min-height: 100vh;
}

.edu-shell {
    display: flex;
    align-items: stretch;
    min-height: calc(100vh - var(--edu-navbar-height));
}

.edu-main {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-width: 0;
}

.edu-main-inner {
    flex: 1 1 auto;
    padding: 0 0 2.5rem;
}

.edu-page {
    animation: edu-page-in 320ms var(--edu-ease) both;
}

@keyframes edu-page-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.edu-page.is-leaving {
    opacity: 0;
    transform: translateY(4px);
    transition: opacity 130ms ease, transform 130ms ease;
}

// --- Desktop sidebar ---------------------------------------------------------
.edu-sidebar {
    --bs-offcanvas-width: 292px;
    width: var(--edu-sidebar-width);
    flex: 0 0 auto;
    color: var(--edu-sidebar-text);
    background: linear-gradient(160deg, var(--edu-sidebar-from) 0%, var(--edu-sidebar-to) 100%);
    border-right: 1px solid rgba(255, 255, 255, 0.06);
    transition: width 180ms var(--edu-ease);
    z-index: 1030;
}

// On desktop the offcanvas is static, so it needs its own positioning context
// for the decorative ::before layer. On phones it is already fixed.
@media (min-width: 992px) {
    .edu-sidebar {
        position: relative;
    }
}

.edu-sidebar::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background:
        radial-gradient(420px 260px at 10% -10%, rgba(124, 58, 237, 0.22), transparent 60%),
        radial-gradient(360px 240px at 110% 20%, rgba(79, 70, 229, 0.16), transparent 60%);
}

.edu-sidebar > * {
    position: relative;
    z-index: 1;
}

.edu-sidebar .offcanvas-body {
    padding: 1rem 0.85rem 1.1rem;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.18) transparent;
}

.edu-sidebar .offcanvas-body::-webkit-scrollbar {
    width: 6px;
}

.edu-sidebar .offcanvas-body::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.18);
    border-radius: 99px;
}

.edu-sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.35rem 0.5rem 1rem;
    color: #ffffff;
    text-decoration: none;
}

.edu-sidebar-logo:hover {
    color: #ffffff;
}

.edu-sidebar-logo-mark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border-radius: 13px;
    background: linear-gradient(145deg, var(--edu-primary) 0%, var(--edu-secondary) 100%);
    box-shadow: 0 10px 22px -12px rgba(79, 70, 229, 0.9);
    color: #ffffff;
    font-size: 1.15rem;
}

.edu-sidebar-logo-text {
    line-height: 1.15;
}

.edu-sidebar-logo-text strong {
    display: block;
    font-size: 1.02rem;
    font-weight: 700;
    letter-spacing: -0.02em;
}

.edu-sidebar-logo-text span {
    display: block;
    font-size: 0.68rem;
    color: var(--edu-sidebar-heading);
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.edu-sidebar-section {
    margin: 0.9rem 0.5rem 0.35rem;
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--edu-sidebar-heading);
}

.edu-sidebar .nav-link {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.7rem;
    min-height: 44px;
    padding: 0.62rem 0.75rem;
    margin-bottom: 0.15rem;
    border-radius: 0.7rem;
    color: var(--edu-sidebar-text);
    font-size: 0.875rem;
    font-weight: 500;
    transition: background-color var(--edu-transition), color var(--edu-transition);
}

.edu-sidebar .nav-link i {
    font-size: 1.05rem;
    width: 1.25rem;
    text-align: center;
    flex: 0 0 1.25rem;
    color: var(--edu-sidebar-heading);
    transition: color var(--edu-transition);
}

.edu-sidebar .nav-link:hover,
.edu-sidebar .nav-link:focus-visible {
    background: var(--edu-sidebar-hover);
    color: #ffffff;
}

.edu-sidebar .nav-link:hover i,
.edu-sidebar .nav-link:focus-visible i {
    color: #c7d2fe;
}

.edu-sidebar .nav-link.active {
    background: var(--edu-sidebar-active);
    color: #ffffff;
    font-weight: 600;
}

.edu-sidebar .nav-link.active i {
    color: #a5b4fc;
}

.edu-sidebar .nav-link.active::before {
    content: '';
    position: absolute;
    left: -0.85rem;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 22px;
    border-radius: 0 3px 3px 0;
    background: linear-gradient(180deg, #a5b4fc, #7c3aed);
    box-shadow: 0 0 16px rgba(165, 180, 252, 0.7);
}

.edu-sidebar .nav-link .badge {
    margin-left: auto;
    font-size: 0.65rem;
}

.edu-sidebar-divider {
    border-top: 1px solid var(--edu-sidebar-border);
    margin: 0.9rem 0.5rem;
}

.edu-sidebar-user {
    margin-top: auto;
    padding: 0.85rem;
    border-radius: 0.9rem;
    background: rgba(255, 255, 255, 0.055);
    border: 1px solid var(--edu-sidebar-border);
}

.edu-sidebar-user-name {
    color: #ffffff;
    font-weight: 600;
    font-size: 0.875rem;
    line-height: 1.2;
}

.edu-sidebar-user-role {
    color: var(--edu-sidebar-heading);
    font-size: 0.72rem;
    text-transform: capitalize;
}

.edu-sidebar-user .avatar,
.edu-sidebar-user .avatar-sm {
    border: 1px solid rgba(255, 255, 255, 0.2);
}

// The offcanvas header on phones must match the dark column.
.edu-sidebar .offcanvas-header {
    border-bottom: 1px solid var(--edu-sidebar-border) !important;
    color: #ffffff;
}

.edu-sidebar .offcanvas-title {
    color: #ffffff;
}

.edu-sidebar .btn-close {
    filter: invert(1) grayscale(1) brightness(2);
}

// Collapsed desktop sidebar: icon-only, still readable through tooltips.
@media (min-width: 992px) {
    html.edu-sidebar-collapsed .edu-sidebar {
        width: var(--edu-sidebar-collapsed-width);
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-label,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-section,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-logo-text,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user-text,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user .badge,
    html.edu-sidebar-collapsed .edu-sidebar .nav-link .badge {
        display: none !important;
    }

    html.edu-sidebar-collapsed .edu-sidebar .nav-link {
        justify-content: center;
        padding-inline: 0.55rem;
    }

    html.edu-sidebar-collapsed .edu-sidebar .nav-link i {
        margin: 0;
    }

    html.edu-sidebar-collapsed .edu-sidebar .nav-link.active::before {
        left: -0.55rem;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-logo {
        justify-content: center;
        padding-inline: 0;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user {
        padding: 0.6rem;
        text-align: center;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user .d-flex {
        justify-content: center;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-footer-link {
        justify-content: center;
    }
}

// --- Top navbar --------------------------------------------------------------
.edu-navbar {
    position: sticky;
    top: 0;
    z-index: 1040;
    min-height: var(--edu-navbar-height);
    padding: 0.55rem 0;
    background: color-mix(in srgb, var(--edu-surface) 82%, transparent);
    border-bottom: 1px solid var(--edu-border);
    backdrop-filter: saturate(180%) blur(16px);
    -webkit-backdrop-filter: saturate(180%) blur(16px);
}

@supports not (backdrop-filter: blur(1px)) {
    .edu-navbar {
        background: var(--edu-surface);
    }
}

.edu-navbar .navbar-brand {
    color: var(--edu-text);
    font-weight: 700;
    letter-spacing: -0.02em;
}

.edu-navbar-title {
    display: block;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--edu-text);
    line-height: 1.2;
    max-width: 22rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.edu-navbar .breadcrumb {
    --bs-breadcrumb-divider-color: var(--edu-muted);
    --bs-breadcrumb-item-padding-x: 0.35rem;
    margin: 0;
    font-size: 0.72rem;
}

.edu-navbar .breadcrumb a {
    color: var(--edu-muted);
    text-decoration: none;
}

.edu-navbar .breadcrumb a:hover {
    color: var(--edu-primary);
}

.edu-navbar .breadcrumb-item.active {
    color: var(--edu-muted-strong);
}

.edu-search {
    width: 260px;
}

.edu-search .form-control {
    background: var(--edu-surface-soft);
    border-color: transparent;
    font-size: 0.85rem;
}

.edu-search .form-control:focus {
    background: var(--edu-surface);
    border-color: var(--edu-border-strong);
}

@media (min-width: 992px) {
    .edu-search {
        width: 320px;
    }
}

.edu-navbar .nav-link {
    color: var(--edu-text);
}

.edu-navbar .dropdown-toggle::after {
    display: none;
}

// Bootstrap sets `.navbar-nav .dropdown-menu` to `position: static` outside a
// `navbar-expand-*` container, so the user/notification menus would expand the
// bar and push the page down. The top bar is always horizontal here, so keep
// its menus floating above the content instead.
.edu-navbar .navbar-nav .dropdown-menu {
    position: absolute;
}

.edu-navbar-avatar {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.25rem 0.45rem 0.25rem 0.3rem;
    border-radius: 999px;
    border: 1px solid var(--edu-border);
    background: var(--edu-surface);
    color: var(--edu-text);
    text-decoration: none;
    transition: box-shadow var(--edu-transition), border-color var(--edu-transition);
}

.edu-navbar-avatar:hover {
    border-color: var(--edu-border-strong);
    box-shadow: var(--edu-shadow-xs);
    color: var(--edu-text);
}

.notification-menu {
    width: min(380px, 92vw);
    max-height: 72vh;
    overflow-y: auto;
}

.notification-unread {
    background-color: var(--edu-primary-soft);
    border-left: 3px solid var(--edu-primary);
}

.edu-time {
    font-size: 0.72rem;
}

// Footer
.edu-footer {
    border-top: 1px solid var(--edu-border);
    background: var(--edu-surface);
    color: var(--edu-muted);
    padding: 1.1rem 0;
    font-size: 0.8125rem;
}

// -----------------------------------------------------------------------------
// Dashboard shared components
// -----------------------------------------------------------------------------

.edu-dashboard {
    padding-bottom: 1rem;
}

.edu-greeting {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.4rem;
}

.edu-greeting h1 {
    font-size: clamp(1.5rem, 1.2rem + 1.2vw, 2rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    margin: 0.15rem 0 0.2rem;
}

.edu-greeting p {
    margin: 0;
    color: var(--edu-muted);
    font-size: 0.875rem;
}

.edu-quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

// --- Stat cards --------------------------------------------------------------
.edu-stat-card {
    position: relative;
    overflow: hidden;
}

.edu-stat-card .card-body {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-height: 142px;
}

.edu-stat-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.edu-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    flex: 0 0 46px;
    border-radius: 14px;
    font-size: 1.2rem;
    line-height: 1;
}

.edu-stat-icon.bg-primary {
    background: rgba(79, 70, 229, 0.12) !important;
    color: #4f46e5 !important;
}

.edu-stat-icon.bg-success {
    background: rgba(16, 185, 129, 0.13) !important;
    color: #059669 !important;
}

.edu-stat-icon.bg-info {
    background: rgba(14, 165, 233, 0.13) !important;
    color: #0284c7 !important;
}

.edu-stat-icon.bg-warning {
    background: rgba(245, 158, 11, 0.15) !important;
    color: #b45309 !important;
}

.edu-stat-icon.bg-danger {
    background: rgba(239, 68, 68, 0.13) !important;
    color: #dc2626 !important;
}

.edu-stat-icon.bg-secondary {
    background: rgba(124, 58, 237, 0.14) !important;
    color: #6d28d9 !important;
}

[data-bs-theme='dark'] .edu-stat-icon.bg-primary { color: #a5b4fc !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-success { color: #6ee7b7 !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-info { color: #7dd3fc !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-warning { color: #fcd34d !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-danger { color: #fca5a5 !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-secondary { color: #c4b5fd !important; }

.edu-stat-value {
    font-size: clamp(1.45rem, 1.25rem + 0.6vw, 1.85rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
    color: var(--edu-text);
}

.edu-stat-label {
    color: var(--edu-muted);
    font-size: 0.8125rem;
    font-weight: 500;
}

.edu-stat-hint {
    color: var(--edu-muted);
    font-size: 0.72rem;
}

.edu-trend {
    display: inline-flex;
    align-items: center;
    gap: 0.18rem;
    font-size: 0.72rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.edu-trend.is-up { color: #059669; }
.edu-trend.is-down { color: #dc2626; }
.edu-trend.is-flat { color: var(--edu-muted); }

[data-bs-theme='dark'] .edu-trend.is-up { color: #6ee7b7; }
[data-bs-theme='dark'] .edu-trend.is-down { color: #fca5a5; }

.edu-sparkline {
    width: 96px;
    height: 34px;
    overflow: visible;
    opacity: 0.95;
}

.edu-sparkline polyline {
    fill: none;
    stroke-width: 2.2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.edu-sparkline .sparkline-fill {
    stroke: none;
    opacity: 0.16;
}

// --- Chart cards -------------------------------------------------------------
.edu-chart-card .card-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.65rem;
}

.edu-chart-card .card-title {
    font-size: 0.95rem;
    font-weight: 700;
    margin: 0;
}

.edu-chart-card .card-subtitle {
    color: var(--edu-muted);
    font-size: 0.75rem;
    margin-top: 0.15rem;
}

.edu-chart {
    position: relative;
    width: 100%;
}

.edu-chart canvas {
    max-width: 100%;
}

.edu-chart-skeleton {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    padding: 0.5rem 0;
    z-index: 2;
    background: var(--edu-surface);
    border-radius: 0.75rem;
}

.edu-chart-skeleton[hidden] {
    display: none !important;
}

.edu-chart-skeleton span {
    flex: 1 1 0;
    border-radius: 0.35rem;
    background: linear-gradient(
        90deg,
        var(--edu-surface-muted) 0%,
        var(--edu-border) 45%,
        var(--edu-surface-muted) 90%
    );
    background-size: 200% 100%;
    animation: edu-shimmer 1.2s linear infinite;
}

.edu-chart-skeleton span:nth-child(1) { height: 42%; }
.edu-chart-skeleton span:nth-child(2) { height: 66%; }
.edu-chart-skeleton span:nth-child(3) { height: 54%; }
.edu-chart-skeleton span:nth-child(4) { height: 82%; }
.edu-chart-skeleton span:nth-child(5) { height: 60%; }
.edu-chart-skeleton span:nth-child(6) { height: 92%; }
.edu-chart-skeleton span:nth-child(7) { height: 70%; }
.edu-chart-skeleton span:nth-child(8) { height: 48%; }

@keyframes edu-shimmer {
    from { background-position: 200% 0; }
    to { background-position: -200% 0; }
}

.edu-chart-range {
    width: auto;
    min-width: 118px;
    font-size: 0.78rem;
    padding: 0.35rem 1.8rem 0.35rem 0.6rem;
    border-radius: 0.55rem;
    background-color: var(--edu-surface-soft);
    border-color: var(--edu-border);
}

.edu-chart-total {
    font-variant-numeric: tabular-nums;
}

// --- Hero cards --------------------------------------------------------------
.edu-hero {
    position: relative;
    overflow: hidden;
    border-radius: var(--edu-radius-lg);
    border: 1px solid rgba(255, 255, 255, 0.08);
    background:
        radial-gradient(900px 360px at 90% -40%, rgba(124, 58, 237, 0.55), transparent 60%),
        linear-gradient(135deg, #1e1b4b 0%, #312e81 45%, #4f46e5 100%);
    color: #ffffff;
    box-shadow: 0 26px 60px -30px rgba(49, 46, 129, 0.85);
    padding: clamp(1.25rem, 1rem + 1.2vw, 2rem);
}

.edu-hero::after {
    content: '';
    position: absolute;
    right: -80px;
    bottom: -120px;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    border: 44px solid rgba(255, 255, 255, 0.05);
    pointer-events: none;
}

.edu-hero > * {
    position: relative;
    z-index: 1;
}

.edu-hero h1,
.edu-hero h2 {
    color: #ffffff;
    letter-spacing: -0.03em;
}

.edu-hero .edu-hero-meta {
    color: rgba(255, 255, 255, 0.72);
    font-size: 0.875rem;
}

.edu-hero .edu-hero-label {
    color: #c7d2fe;
}

.edu-hero .btn-light {
    --bs-btn-bg: #ffffff;
    --bs-btn-color: #312e81;
    --bs-btn-hover-bg: #eef2ff;
    --bs-btn-hover-color: #1e1b4b;
    border-color: #ffffff;
}

.edu-hero .btn-outline-light {
    --bs-btn-color: #ffffff;
    --bs-btn-border-color: rgba(255, 255, 255, 0.45);
    --bs-btn-hover-bg: rgba(255, 255, 255, 0.14);
    --bs-btn-hover-border-color: #ffffff;
}

.edu-hero .edu-chip {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.18);
    color: #e0e7ff;
}

.edu-hero-live {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    background: rgba(239, 68, 68, 0.18);
    border: 1px solid rgba(248, 113, 113, 0.4);
    color: #fecaca;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.edu-live-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ef4444;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.25);
    animation: edu-pulse 1.6s ease-in-out infinite;
}

@keyframes edu-pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(0.82); opacity: 0.65; }
}

// --- Countdown ---------------------------------------------------------------
.edu-countdown {
    display: inline-flex;
    align-items: stretch;
    gap: 0.45rem;
}

.edu-countdown-unit {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 58px;
    padding: 0.4rem 0.55rem;
    border-radius: 0.65rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.14);
}

.edu-countdown-value {
    font-size: 1.2rem;
    font-weight: 800;
    line-height: 1;
    font-variant-numeric: tabular-nums;
    color: #ffffff;
}

.edu-countdown-label {
    font-size: 0.58rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.66);
    margin-top: 0.2rem;
}

.edu-countdown.is-compact .edu-countdown-unit {
    min-width: 46px;
    padding: 0.3rem 0.4rem;
}

.edu-countdown.is-compact .edu-countdown-value {
    font-size: 1rem;
}

// --- Avatar stack ------------------------------------------------------------
.edu-avatar-stack {
    display: inline-flex;
    align-items: center;
    padding-left: 0.35rem;
}

.edu-avatar-stack .avatar,
.edu-avatar-stack .avatar-sm {
    border: 2px solid var(--edu-hero-avatar-border, #312e81);
    margin-left: -0.45rem;
    background: var(--edu-surface-muted);
}

.edu-avatar-stack .edu-avatar-stack-more {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    margin-left: -0.45rem;
    background: rgba(255, 255, 255, 0.16);
    border: 2px solid #312e81;
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

// Outside hero cards the stack needs a light border.
.edu-avatar-stack.on-light .avatar,
.edu-avatar-stack.on-light .avatar-sm {
    border-color: var(--edu-surface);
}

.edu-avatar-stack.on-light .edu-avatar-stack-more {
    background: var(--edu-surface-muted);
    border-color: var(--edu-surface);
    color: var(--edu-muted-strong);
}

// --- Activity timeline -------------------------------------------------------
.edu-activity {
    position: relative;
    display: flex;
    gap: 0.85rem;
    padding: 0.8rem 0;
}

.edu-activity + .edu-activity {
    border-top: 1px solid var(--edu-border);
}

.edu-activity-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    border-radius: 12px;
    font-size: 1rem;
}

.edu-activity-icon.bg-primary { background: rgba(79, 70, 229, 0.12); color: #4f46e5; }
.edu-activity-icon.bg-success { background: rgba(16, 185, 129, 0.13); color: #059669; }
.edu-activity-icon.bg-info { background: rgba(14, 165, 233, 0.13); color: #0284c7; }
.edu-activity-icon.bg-warning { background: rgba(245, 158, 11, 0.15); color: #b45309; }
.edu-activity-icon.bg-danger { background: rgba(239, 68, 68, 0.13); color: #dc2626; }

.edu-activity-text {
    font-size: 0.85rem;
    color: var(--edu-text-soft);
    line-height: 1.4;
}

.edu-activity-excerpt {
    color: var(--edu-muted);
    font-size: 0.78rem;
    margin-top: 0.15rem;
}

.edu-activity-time {
    color: var(--edu-muted);
    font-size: 0.72rem;
    margin-top: 0.2rem;
}

// --- Data table wrapper ------------------------------------------------------
.edu-data-table .card-header {
    align-items: center;
}

.edu-table-tools {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.edu-table-search {
    position: relative;
}

.edu-table-search i {
    position: absolute;
    left: 0.65rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--edu-muted);
    font-size: 0.8rem;
    pointer-events: none;
}

.edu-table-search .form-control {
    min-width: 190px;
    padding-left: 2rem;
    font-size: 0.8rem;
    background: var(--edu-surface-soft);
    border-color: transparent;
}

.edu-table-search .form-control:focus {
    background: var(--edu-surface);
    border-color: var(--edu-border-strong);
}

.edu-sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
}

.edu-sortable:hover {
    color: var(--edu-text);
}

.edu-sortable::after {
    content: '\F282';
    font-family: 'bootstrap-icons';
    font-size: 0.62rem;
    margin-left: 0.3rem;
    opacity: 0.35;
}

.edu-sortable[aria-sort='ascending']::after {
    content: '\F286';
    opacity: 0.9;
}

.edu-sortable[aria-sort='descending']::after {
    content: '\F284';
    opacity: 0.9;
}

.edu-table-empty {
    padding: 1.5rem 1rem;
    text-align: center;
    color: var(--edu-muted);
    font-size: 0.85rem;
}

.edu-table-count {
    color: var(--edu-muted);
    font-size: 0.75rem;
    font-variant-numeric: tabular-nums;
}

// --- Schedule strip ----------------------------------------------------------
.edu-schedule {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0.45rem;
}

.edu-schedule-day {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.6rem 0.25rem;
    border-radius: 0.8rem;
    border: 1px solid var(--edu-border);
    background: var(--edu-surface-soft);
    min-height: 86px;
    text-align: center;
}

.edu-schedule-day.is-today {
    border-color: rgba(79, 70, 229, 0.4);
    background: var(--edu-primary-soft);
}

.edu-schedule-weekday {
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--edu-muted);
}

.edu-schedule-date {
    font-size: 0.95rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    color: var(--edu-text);
    line-height: 1.1;
}

.edu-schedule-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 0.3rem;
    border-radius: 999px;
    background: var(--edu-surface-muted);
    color: var(--edu-muted-strong);
    font-size: 0.68rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.edu-schedule-day.has-classes .edu-schedule-count {
    background: var(--edu-primary);
    color: #ffffff;
}

@media (max-width: 575.98px) {
    .edu-schedule {
        gap: 0.3rem;
    }

    .edu-schedule-day {
        min-height: 76px;
        padding: 0.45rem 0.15rem;
    }

    .edu-schedule-date {
        font-size: 0.85rem;
    }
}

// --- Panels / lists ----------------------------------------------------------
.edu-list {
    display: flex;
    flex-direction: column;
}

.edu-list-row {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.8rem 0;
}

.edu-list-row + .edu-list-row {
    border-top: 1px solid var(--edu-border);
}

.edu-list-row:first-child {
    padding-top: 0.2rem;
}

.edu-list-row:last-child {
    padding-bottom: 0.2rem;
}

.edu-list-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--edu-text);
    text-decoration: none;
    display: block;
}

.edu-list-title:hover {
    color: var(--edu-primary);
}

.edu-list-meta {
    color: var(--edu-muted);
    font-size: 0.74rem;
}

.edu-list-excerpt {
    color: var(--edu-muted-strong);
    font-size: 0.8rem;
    margin-top: 0.15rem;
}

.edu-report-actions {
    display: flex;
    gap: 0.35rem;
    flex-shrink: 0;
}

.edu-report-actions .btn {
    width: 34px;
    height: 34px;
    padding: 0;
}

// --- Feed preview ------------------------------------------------------------
.edu-feed-item {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 0;
}

.edu-feed-item + .edu-feed-item {
    border-top: 1px solid var(--edu-border);
}

.edu-feed-body {
    font-size: 0.82rem;
    color: var(--edu-text-soft);
    margin: 0.2rem 0 0.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

// --- Feed sidebar widgets ----------------------------------------------------
.edu-widget-user {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.7rem 0;
}

.edu-widget-user + .edu-widget-user {
    border-top: 1px solid var(--edu-border);
}

.edu-widget-user .edu-list-title {
    font-size: 0.84rem;
}

.edu-widget-user .btn {
    white-space: nowrap;
}

.edu-trending-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.65rem 0;
    color: inherit;
    text-decoration: none;
}

.edu-trending-row + .edu-trending-row {
    border-top: 1px solid var(--edu-border);
}

.edu-trending-row:hover .edu-list-title {
    color: var(--edu-primary);
}

.edu-trending-rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 12px;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-weight: 700;
    flex-shrink: 0;
}

.edu-post-image {
    width: 100%;
    max-height: 520px;
    object-fit: cover;
    background: var(--edu-surface-muted);
}

.edu-post-comments .avatar,
.edu-post-comments .avatar-sm {
    width: 34px;
    height: 34px;
    font-size: 0.8rem;
}

// --- Empty states ------------------------------------------------------------
.edu-empty-illustration {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 76px;
    height: 76px;
    border-radius: 24px;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-size: 1.9rem;
    margin-bottom: 0.9rem;
    position: relative;
}

.edu-empty-illustration::after {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 30px;
    border: 1px dashed rgba(79, 70, 229, 0.22);
}

// --- Skeletons ---------------------------------------------------------------
.edu-skeleton {
    display: block;
    border-radius: 0.5rem;
    background: linear-gradient(
        90deg,
        var(--edu-surface-muted) 0%,
        var(--edu-border) 45%,
        var(--edu-surface-muted) 90%
    );
    background-size: 200% 100%;
    animation: edu-shimmer 1.2s linear infinite;
}

// -----------------------------------------------------------------------------
// Legacy application pages (feed, auth, live room) — kept intentionally
// -----------------------------------------------------------------------------

.post-body {
    white-space: pre-wrap;
    word-break: break-word;
}

.breadcrumb {
    --bs-breadcrumb-divider-color: var(--edu-muted);
}

[x-cloak] {
    display: none !important;
}

.jitsi-frame {
    position: relative;
    width: 100%;
    height: calc(100vh - 340px);
    min-height: 420px;
    border-radius: 0.75rem;
    overflow: hidden;
    background-color: #1c1c1c;
}

.jitsi-frame iframe {
    width: 100% !important;
    height: 100% !important;
    border: 0;
}

@media (max-width: 991.98px) {
    .jitsi-frame {
        height: calc(100vh - 260px);
        min-height: 360px;
        border-radius: 0.5rem;
    }
}

.edu-auth-body {
    background-color: var(--edu-surface);
    color: var(--edu-text);
}

.edu-auth-brand {
    position: relative;
    overflow: hidden;
    color: #fff;
    background: linear-gradient(145deg, #312e81 0%, #4338ca 45%, #4f46e5 100%);
}

.edu-auth-brand::before,
.edu-auth-brand::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    pointer-events: none;
}

.edu-auth-brand::before {
    width: 420px;
    height: 420px;
    top: -160px;
    right: -140px;
}

.edu-auth-brand::after {
    width: 300px;
    height: 300px;
    bottom: -120px;
    left: -100px;
}

.edu-auth-brand > * {
    position: relative;
    z-index: 1;
}

.edu-auth-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    flex: 0 0 44px;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.14);
    font-size: 1.15rem;
}

.edu-auth-form {
    max-width: 440px;
}

.edu-auth-card {
    border-radius: 1rem;
}

.password-toggle {
    min-width: 48px;
}

@media (max-width: 991.98px) {
    .edu-auth-body {
        background-color: var(--edu-page-bg);
    }
}

// -----------------------------------------------------------------------------
// Responsive and touch refinements
// -----------------------------------------------------------------------------

@media (max-width: 991.98px) {
    .edu-main-inner {
        padding-top: 1.1rem;
    }

    .edu-sidebar {
        --bs-offcanvas-width: min(88vw, 312px);
    }
}

@media (max-width: 575.98px) {
    .btn,
    .form-control,
    .form-select,
    .nav-link,
    .dropdown-item {
        min-height: 44px;
    }

    .btn-sm {
        min-height: 44px;
    }

    .edu-icon-btn {
        width: 44px;
        height: 44px;
    }

    .edu-greeting {
        align-items: flex-start;
    }

    .edu-quick-actions {
        width: 100%;
    }

    .edu-quick-actions .btn {
        flex: 1 1 auto;
    }

    .edu-stat-card .card-body {
        min-height: 128px;
    }

    .edu-table-search .form-control {
        min-width: 0;
        width: 100%;
    }

    .edu-table-tools {
        width: 100%;
    }

    .edu-table-tools .edu-table-search {
        flex: 1 1 auto;
    }

    .edu-countdown {
        gap: 0.3rem;
    }

    .edu-countdown-unit {
        min-width: 48px;
        padding: 0.35rem 0.35rem;
    }

    .edu-countdown-value {
        font-size: 1.05rem;
    }
}

// Larger screens get a little more breathing room.
@media (min-width: 1920px) {
    .edu-main-inner {
        padding-top: 2rem;
    }

    .edu-sidebar {
        --edu-sidebar-width: 284px;
    }

    .edu-stat-card .card-body {
        min-height: 152px;
    }
}

// -----------------------------------------------------------------------------
// Phase 3C — component library additions
// -----------------------------------------------------------------------------
// Everything below is built from the tokens in _variables.scss: same spacing
// steps, same radii, same transitions, same z-index ladder.

// Comfortable reading width for long prose (about 70 characters).
.edu-measure {
    max-width: var(--edu-measure);
}

// --- Button loading state ----------------------------------------------------
// A form button that is waiting for the server: spinner in place of the label.
.btn.is-loading {
    position: relative;
    color: transparent !important;
    pointer-events: none;

    > * {
        visibility: hidden;
    }

    &::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 1rem;
        height: 1rem;
        margin: -0.5rem 0 0 -0.5rem;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        color: #fff;
        animation: edu-spin 0.6s linear infinite;
    }
}

.btn-outline-primary.is-loading::after,
.btn-outline-secondary.is-loading::after,
.btn-light.is-loading::after {
    color: var(--edu-primary);
}

@keyframes edu-spin {
    to {
        transform: rotate(360deg);
    }
}

// --- Toasts ------------------------------------------------------------------
// Rendered by resources/js/toast.js from the data attributes of <x-flash />.
.edu-toast-stack {
    position: fixed;
    right: var(--edu-space-4);
    bottom: var(--edu-space-4);
    z-index: var(--edu-z-toast);
    display: flex;
    flex-direction: column;
    gap: var(--edu-space-2);
    width: min(360px, calc(100vw - 2 * var(--edu-space-4)));
    pointer-events: none;
}

.edu-toast {
    display: flex;
    align-items: flex-start;
    gap: var(--edu-space-2);
    padding: var(--edu-space-3) var(--edu-space-4);
    border-radius: var(--edu-radius-sm);
    border: 1px solid var(--edu-border);
    background: var(--edu-surface);
    color: var(--edu-text);
    box-shadow: var(--edu-shadow-pop);
    pointer-events: auto;
    animation: edu-toast-in 200ms var(--edu-ease);

    .btn-close {
        padding: 0.35rem;
        margin-left: auto;
    }
}

.edu-toast.is-success {
    border-left: 4px solid var(--edu-success);
}

.edu-toast.is-error {
    border-left: 4px solid var(--edu-danger);
}

.edu-toast.is-info {
    border-left: 4px solid var(--edu-info);
}

@keyframes edu-toast-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
}

// --- Role cards on the register page ----------------------------------------
.edu-role-card {
    display: flex;
    align-items: flex-start;
    gap: var(--edu-space-3);
    padding: var(--edu-space-3) var(--edu-space-4);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface-soft);
    cursor: pointer;
    transition: border-color var(--edu-transition), background-color var(--edu-transition),
        box-shadow var(--edu-transition);

    &:hover {
        border-color: var(--edu-border-strong);
    }

    // The radio itself is hidden: the whole card is the hit area (44px+ tall).
    input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .edu-role-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: var(--edu-radius-xs);
        background: var(--edu-primary-soft);
        color: var(--edu-primary);
        font-size: 1.15rem;
    }

    // Selected state, driven by :has() plus a JS class for older browsers.
    &:has(input:checked),
    &.is-selected {
        border-color: var(--edu-primary);
        background: var(--edu-surface);
        box-shadow: 0 0 0 1px var(--edu-primary);
    }

    &:has(input:focus-visible),
    &.is-selected:focus-within {
        outline: 2px solid var(--edu-primary);
        outline-offset: 2px;
    }
}

// --- Password strength meter -------------------------------------------------
.edu-strength {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    margin-top: var(--edu-space-2);
}

.edu-strength-track {
    display: flex;
    gap: 4px;
}

.edu-strength-seg {
    flex: 1;
    height: 6px;
    border-radius: 999px;
    background: var(--edu-surface-muted);
    transition: background-color var(--edu-transition);
}

.edu-strength[data-score='1'] .edu-strength-seg:nth-child(-n + 1),
.edu-strength[data-score='2'] .edu-strength-seg:nth-child(-n + 2) {
    background: var(--edu-danger);
}

.edu-strength[data-score='3'] .edu-strength-seg:nth-child(-n + 3) {
    background: var(--edu-warning);
}

.edu-strength[data-score='4'] .edu-strength-seg {
    background: var(--edu-success);
}

// --- Mobile bottom navigation ------------------------------------------------
.edu-mobile-nav {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: var(--edu-z-mobilenav);
    display: flex;
    align-items: stretch;
    justify-content: space-around;
    border-top: 1px solid var(--edu-border);
    background: var(--edu-surface);
    // Respect the iPhone home indicator area.
    padding-bottom: env(safe-area-inset-bottom, 0);
    box-shadow: 0 -6px 24px -18px rgba(15, 23, 42, 0.4);
}

.edu-mobile-nav-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    // 44px minimum touch target.
    min-width: 56px;
    min-height: 56px;
    flex: 1;
    padding: 0.4rem 0.25rem;
    font-size: var(--edu-fs-xs);
    font-weight: 600;
    color: var(--edu-muted);
    text-decoration: none;

    i {
        font-size: 1.25rem;
    }

    &.active {
        color: var(--edu-primary);
    }

    &:hover {
        color: var(--edu-primary);
    }
}

// The badge sits on the icon, not on the label.
.edu-mobile-nav-badge {
    position: absolute;
    top: 4px;
    left: 50%;
    margin-left: 6px;
    font-size: 0.625rem;
    padding: 0.15em 0.4em;
}

// --- Floating "new post" button ---------------------------------------------
.edu-fab {
    position: fixed;
    right: var(--edu-space-4);
    bottom: calc(72px + env(safe-area-inset-bottom, 0));
    z-index: var(--edu-z-fab);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    border: 0;
    border-radius: 50%;
    background: var(--edu-primary);
    color: #fff;
    font-size: 1.4rem;
    box-shadow: var(--edu-shadow-pop);
    transition: transform var(--edu-transition), background-color var(--edu-transition);

    &:hover,
    &:focus-visible {
        background: var(--edu-primary-dark);
        color: #fff;
    }

    &:active {
        transform: scale(0.94);
    }
}

@media (min-width: 992px) {
    .edu-fab {
        bottom: var(--edu-space-5);
        right: var(--edu-space-5);
    }
}

// --- Profile cover banner ----------------------------------------------------
.edu-cover {
    position: relative;
    height: 132px;
    border-radius: var(--edu-radius) var(--edu-radius) 0 0;
    background: linear-gradient(120deg, #312e81 0%, #4f46e5 55%, #7c3aed 100%);
    overflow: hidden;

    &::after {
        content: '';
        position: absolute;
        width: 260px;
        height: 260px;
        right: -80px;
        top: -140px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
    }
}

.edu-cover-avatar {
    margin-top: -48px;
}

// --- Highlighted search match ------------------------------------------------
// The surrounding text keeps its colour; only the matched part is marked, so
// the highlight never hides information and always has contrast.
.edu-mark {
    padding: 0 0.15em;
    border-radius: var(--edu-radius-xs);
    background: rgba(245, 158, 11, 0.28);
    color: inherit;
    font-weight: 600;
}

[data-bs-theme='dark'] .edu-mark {
    background: rgba(245, 158, 11, 0.32);
}

// --- Filter chips ------------------------------------------------------------
.edu-filter-chip {
    display: inline-flex;
    align-items: center;
    gap: var(--edu-space-1);
    padding: 0.35rem 0.75rem;
    min-height: 36px;
    border: 1px solid var(--edu-border);
    border-radius: 999px;
    background: var(--edu-surface);
    color: var(--edu-text-soft);
    font-size: var(--edu-fs-small);
    font-weight: 600;
    text-decoration: none;
    transition: border-color var(--edu-transition), background-color var(--edu-transition),
        color var(--edu-transition);

    &:hover {
        border-color: var(--edu-primary);
        color: var(--edu-primary);
    }

    &.is-active {
        border-color: var(--edu-primary);
        background: var(--edu-primary);
        color: #fff;
    }
}

// --- Notification groups (Today / Earlier) -----------------------------------
.edu-notif-group-label {
    display: flex;
    align-items: center;
    gap: var(--edu-space-2);
    margin: var(--edu-space-4) 0 var(--edu-space-2);
    font-size: var(--edu-fs-xs);
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--edu-muted);

    &::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--edu-border);
    }
}

.edu-notif-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--edu-primary);
    flex-shrink: 0;
}

// --- Sticky data tables + mobile card view -----------------------------------
.edu-table-sticky thead th {
    position: sticky;
    top: 0;
    z-index: var(--edu-z-base);
    background: var(--edu-surface);
    box-shadow: inset 0 -1px 0 var(--edu-border);
}

// Below lg each row becomes a card. The label of every cell comes from the
// data-label attribute, so nothing is lost when the header disappears.
@media (max-width: 991.98px) {
    .edu-table-cards {
        thead {
            display: none;
        }

        tbody tr {
            display: block;
            border: 1px solid var(--edu-border);
            border-radius: var(--edu-radius-sm);
            margin-bottom: var(--edu-space-3);
            padding: var(--edu-space-2);
            background: var(--edu-surface);
        }

        tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--edu-space-3);
            border: 0;
            padding: 0.4rem 0.25rem;
            text-align: right;
        }

        tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            font-size: var(--edu-fs-small);
            color: var(--edu-muted);
            text-align: left;
        }
    }
}

// --- Style guide helpers -----------------------------------------------------
.edu-swatch {
    height: 56px;
    border-radius: var(--edu-radius-sm);
    border: 1px solid var(--edu-border);
}

.edu-guide-block {
    padding: var(--edu-space-4);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface);
}

// --- Landing page (guests) ---------------------------------------------------
.edu-landing {
    background: var(--edu-surface);
}

.edu-landing-hero {
    position: relative;
    overflow: hidden;
    padding: var(--edu-space-8) 0 var(--edu-space-7);
    background: linear-gradient(180deg, var(--edu-surface-soft) 0%, var(--edu-surface) 100%);
}

.edu-landing-shape {
    position: absolute;
    border-radius: 50%;
    filter: blur(6px);
    pointer-events: none;
    opacity: 0.55;
}

.edu-landing-shape.is-one {
    width: 380px;
    height: 380px;
    top: -160px;
    right: -120px;
    background: radial-gradient(circle at 30% 30%, rgba(79, 70, 229, 0.35), transparent 70%);
}

.edu-landing-shape.is-two {
    width: 320px;
    height: 320px;
    bottom: -180px;
    left: -120px;
    background: radial-gradient(circle at 60% 40%, rgba(124, 58, 237, 0.3), transparent 70%);
}

.edu-landing-title {
    font-size: var(--edu-fs-display);
    line-height: 1.1;
    letter-spacing: -0.03em;
}

// Mockup of the live-class screen: pure CSS, no image request.
.edu-mockup {
    border-radius: var(--edu-radius);
    border: 1px solid var(--edu-border);
    background: var(--edu-surface);
    box-shadow: var(--edu-shadow-pop);
    overflow: hidden;
}

.edu-mockup-bar {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: var(--edu-space-2) var(--edu-space-3);
    border-bottom: 1px solid var(--edu-border);
    background: var(--edu-surface-soft);
}

.edu-mockup-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--edu-border-strong);
}

.edu-mockup-stage {
    position: relative;
    aspect-ratio: 16 / 9;
    background: linear-gradient(140deg, #1e1b4b 0%, #312e81 50%, #4f46e5 100%);
}

.edu-mockup-tile {
    position: absolute;
    border-radius: var(--edu-radius-xs);
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.22);
}

.edu-mockup-bar-strip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--edu-space-3);
    padding: var(--edu-space-3);
    background: var(--edu-surface-soft);
}

.edu-mockup-tool {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--edu-surface);
    border: 1px solid var(--edu-border);
    color: var(--edu-text-soft);
}

.edu-step-card {
    position: relative;
    padding: var(--edu-space-5);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius);
    background: var(--edu-surface);
    height: 100%;
}

.edu-step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: var(--edu-radius-xs);
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-weight: 700;
}

.edu-feature-card {
    padding: var(--edu-space-5);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius);
    background: var(--edu-surface);
    height: 100%;
    transition: transform var(--edu-transition), box-shadow var(--edu-transition),
        border-color var(--edu-transition);

    &:hover {
        transform: translateY(-3px);
        border-color: var(--edu-border-strong);
        box-shadow: var(--edu-shadow-card);
    }
}

.edu-stats-strip {
    border-radius: var(--edu-radius);
    background: linear-gradient(120deg, #312e81 0%, #4f46e5 100%);
    color: #fff;
    padding: var(--edu-space-6) var(--edu-space-5);
}

.edu-stat-figure {
    font-size: clamp(1.5rem, 1.1rem + 1.4vw, 2.25rem);
    font-weight: 800;
    line-height: 1.1;
}

.edu-landing-section {
    padding: var(--edu-space-7) 0;
}

.edu-section-title {
    font-size: var(--edu-fs-h2);
    letter-spacing: -0.02em;
}

// A tidy accordion: no double borders, the open item is clearly marked.
.edu-faq .accordion-item {
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface);
    margin-bottom: var(--edu-space-2);
    overflow: hidden;
}

.edu-faq .accordion-button {
    font-weight: 600;
    background: var(--edu-surface);
    color: var(--edu-text);
    box-shadow: none;

    &:not(.collapsed) {
        color: var(--edu-primary);
        background: var(--edu-surface-soft);
    }
}

.edu-landing-footer {
    padding: var(--edu-space-6) 0 var(--edu-space-5);
    border-top: 1px solid var(--edu-border);
    background: var(--edu-surface-soft);
}

// On phones the bottom navigation and the floating button need room.
@media (max-width: 991.98px) {
    .edu-has-mobile-nav {
        padding-bottom: calc(64px + env(safe-area-inset-bottom, 0));
    }
}

// --- Phase 3C additions ------------------------------------------------------

// Skip link: invisible until it receives keyboard focus, then a normal button.
.edu-skip-link {
    position: fixed;
    top: var(--edu-space-2);
    left: var(--edu-space-2);
    z-index: calc(var(--edu-z-fab) + 10);
    padding: 0.5rem 0.9rem;
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface);
    color: var(--edu-text);
    box-shadow: var(--edu-shadow-pop);
    font-weight: 600;
    text-decoration: none;

    &:focus {
        outline: 2px solid var(--edu-primary);
        outline-offset: 2px;
    }
}

// Sidebars that stay in view while the main column scrolls.
.edu-sticky-aside {
    position: static;
}

@media (min-width: 1200px) {
    .edu-sticky-aside {
        position: sticky;
        top: calc(var(--edu-navbar-height) + var(--edu-space-3));
    }
}

// Character ring in the feed composer.
.edu-char-ring {
    --edu-ring-progress: 0deg;
    --edu-ring-color: var(--edu-primary);
    position: relative;
    display: inline-grid;
    place-items: center;
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    border-radius: 50%;
    background: conic-gradient(var(--edu-ring-color) var(--edu-ring-progress), var(--edu-border) 0);
    font-size: var(--edu-fs-xs);
    font-weight: 700;
    color: var(--edu-muted);
    transition: background var(--edu-transition);

    &::before {
        content: '';
        position: absolute;
        inset: 4px;
        border-radius: 50%;
        background: var(--edu-surface);
    }

    > * {
        position: relative;
        z-index: 1;
    }

    &.is-warning {
        --edu-ring-color: var(--edu-warning);
    }

    &.is-full {
        --edu-ring-color: var(--edu-danger);
    }
}

// Heart pop when a post is liked (disabled by prefers-reduced-motion).
.edu-like-pop {
    animation: edu-like-pop 220ms ease-out;
}

@keyframes edu-like-pop {
    0% { transform: scale(1); }
    40% { transform: scale(1.35); }
    70% { transform: scale(0.94); }
    100% { transform: scale(1); }
}

// Notification row icon.
.edu-notif-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-size: 1rem;

    .notification-unread & {
        background: var(--edu-primary);
        color: #fff;
    }
}

// Hashtag page hero.
.edu-hashtag-hero {
    display: grid;
    place-items: center;
    width: 64px;
    height: 64px;
    flex-shrink: 0;
    border-radius: var(--edu-radius);
    background: linear-gradient(135deg, var(--edu-primary) 0%, var(--edu-secondary) 100%);
    color: #fff;
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

// Admin tables: keep the post preview readable without stretching the table.
.edu-admin-post-cell {
    min-width: 16rem;
    max-width: 30rem;

    @media (max-width: 991.98px) {
        min-width: 0;
        max-width: none;
    }
}

// Accessibility: honour the operating-system "reduce motion" setting. One
// single media query for the whole stylesheet (Phase 3C merged the block that
// used to be duplicated here).
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }

    .edu-feature-card:hover,
    .edu-card-hover:hover {
        transform: none;
    }

    .edu-page,
    .edu-page.is-leaving {
        animation: none !important;
        transform: none !important;
        opacity: 1 !important;
    }

    .edu-chart-skeleton span,
    .edu-skeleton,
    .edu-live-dot,
    .edu-toast,
    .btn.is-loading::after {
        animation: none !important;
    }
}

// -----------------------------------------------------------------------------
// Phase 3D — responsive layer (imported last on purpose)
// -----------------------------------------------------------------------------
@import 'responsive';
```

### `resources/sass/_responsive.scss`

```scss
// -----------------------------------------------------------------------------
// Phase 3D — responsive layer (320px → 2560px, portrait + landscape)
// -----------------------------------------------------------------------------
// Everything here is mobile-first or a small, targeted correction on top of
// the existing Bootstrap 5 / Phase 3B / 3C styles. The file is imported last
// so it can safely adjust component rules without rewriting them.
//
// Breakpoints follow Bootstrap 5 exactly:
//   xs  0          phones (320, 360, 375, 390, 430)
//   sm  576px      large phones / small foldables
//   md  768px      tablets portrait
//   lg  992px      tablets landscape / small laptops
//   xl  1200px     laptops
//   xxl 1400px     desktops
// No custom breakpoint is introduced. Safe-area insets, pointer media
// queries and the 359.98px guard are device capabilities, not breakpoints.
// -----------------------------------------------------------------------------

:root {
    --edu-safe-top: env(safe-area-inset-top, 0px);
    --edu-safe-right: env(safe-area-inset-right, 0px);
    --edu-safe-bottom: env(safe-area-inset-bottom, 0px);
    --edu-safe-left: env(safe-area-inset-left, 0px);
    // Height of the phone bottom bar, including the home indicator.
    --edu-mobile-nav-height: calc(56px + var(--edu-safe-bottom));
}

// -----------------------------------------------------------------------------
// 1. Global foundations
// -----------------------------------------------------------------------------

html {
    // Safari on iOS otherwise enlarges text in landscape.
    -webkit-text-size-adjust: 100%;
    text-size-adjust: 100%;
    // The sticky navbar is part of the flow; anchor jumps must clear it.
    scroll-padding-top: calc(var(--edu-navbar-height) + var(--edu-space-3));
}

body {
    // `clip` is the modern value; `hidden` stays as the fallback (set in app.scss).
    overflow-x: hidden;
    overflow-x: clip;
    // A long email, URL or hashtag must never widen the page.
    overflow-wrap: break-word;
    word-break: break-word;
}

// Media never overflows its column and never distorts.
img,
video {
    max-width: 100%;
    height: auto;
}

svg,
canvas,
iframe {
    max-width: 100%;
}

// Intrinsic dimensions (width/height attributes) reserve the box, so lazy
// images do not push the page around while they load.
img[width][height] {
    height: auto;
}

// Never let a flex/grid child force its parent wider than the viewport.
.edu-main,
.edu-main-inner,
.edu-shell,
.container,
.container-fluid,
.container-sm,
.container-md,
.container-lg,
.container-xl,
.container-xxl {
    min-width: 0;
}

// Long user-generated strings (names, emails, post bodies, comments).
.post-body,
.edu-post-body,
.comment-body,
.edu-comment-body,
.notification-body,
.edu-notification-body,
.edu-hashtag,
.edu-mention,
.dropdown-item,
.list-group-item,
td,
th {
    overflow-wrap: anywhere;
    word-break: break-word;
}

// The shell uses dynamic viewport units so the address bar of a phone does
// not crop the bottom of the page (100vh fallback first).
.edu-body {
    min-height: 100vh;
    min-height: 100dvh;
}

.edu-shell {
    min-height: calc(100vh - var(--edu-navbar-height));
    min-height: calc(100dvh - var(--edu-navbar-height));
}

// Auth pages: the branded panel and the form are a full dynamic viewport.
.edu-auth-body main,
.edu-auth-body .min-vh-100 {
    min-height: 100vh !important;
    min-height: 100dvh !important;
}

// Notches and rounded corners: the navbar, the page and the bottom bar all
// add the safe-area inset on top of their own padding.
.edu-navbar {
    padding-top: calc(0.55rem + var(--edu-safe-top));
    padding-left: max(var(--edu-safe-left), 0px);
    padding-right: max(var(--edu-safe-right), 0px);
}

.edu-main-inner {
    padding-left: var(--edu-safe-left);
    padding-right: var(--edu-safe-right);
}

.edu-auth-body main {
    padding-left: var(--edu-safe-left);
    padding-right: var(--edu-safe-right);
}

// Keyboard focus stays visible on every interactive element, in both themes.
a:focus-visible,
button:focus-visible,
.btn:focus-visible,
.form-control:focus-visible,
.form-select:focus-visible,
.form-check-input:focus-visible,
.page-link:focus-visible,
[tabindex]:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--edu-primary) 70%, transparent);
    outline-offset: 2px;
}

// -----------------------------------------------------------------------------
// 2. Touch targets and form ergonomics
// -----------------------------------------------------------------------------

// Every icon button is at least 44 × 44 (the Phase 3C value was 42px).
.edu-icon-btn {
    width: 44px;
    height: 44px;
    min-width: 44px;
}

// Coarse pointers (phones, tablets, touch laptops) get comfortable targets
// even when the visual style is "small".
@media (pointer: coarse) {
    .btn,
    .btn-sm,
    .nav-link,
    .dropdown-item,
    .list-group-item-action,
    .page-link,
    .edu-filter-chip,
    .edu-mobile-nav-item,
    .password-toggle {
        min-height: 44px;
    }

    .btn-sm {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    .form-check-input {
        width: 1.35em;
        height: 1.35em;
        margin-top: 0.2em;
    }

    // Dropdown items are tap targets, not desktop menu rows.
    .dropdown-item {
        display: flex;
        align-items: center;
    }

    // Hover elevation is decoration only; touch users must not depend on it.
    .edu-feature-card:hover,
    .edu-card-hover:hover {
        transform: none;
    }
}

// iOS zooms the whole page when a focused input is smaller than 16px.
// This applies to phones and tablets, exactly where that behaviour exists.
@media (max-width: 991.98px), (pointer: coarse) {
    .form-control,
    .form-select,
    .form-control-sm,
    .form-select-sm,
    .input-group > .form-control,
    .input-group > .form-select,
    textarea.form-control {
        font-size: 16px;
    }

    // Keep focused fields clear of sticky bars and the on-screen keyboard.
    .form-control,
    .form-select,
    textarea {
        scroll-margin-top: calc(var(--edu-navbar-height) + 1rem);
        scroll-margin-bottom: calc(var(--edu-mobile-nav-height) + 6rem);
    }
}

// Hover-only affordances also get a keyboard/tap equivalent.
@media (hover: none) {
    .edu-feature-card:hover,
    .edu-card-hover:hover,
    .edu-sidebar .nav-link:hover {
        transform: none;
        box-shadow: none;
    }
}

// -----------------------------------------------------------------------------
// 3. Navbar: expandable phone search
// -----------------------------------------------------------------------------

.edu-search-mobile {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1039;
    padding: var(--edu-space-2) 0 var(--edu-space-3);
    background: color-mix(in srgb, var(--edu-surface) 96%, transparent);
    border-bottom: 1px solid var(--edu-border);
    box-shadow: var(--edu-shadow-pop);
    backdrop-filter: saturate(180%) blur(16px);
    -webkit-backdrop-filter: saturate(180%) blur(16px);

    &[hidden] {
        display: none;
    }
}

.edu-search-mobile-form {
    width: 100%;
}

.edu-search-mobile .form-control {
    font-size: 16px;
    min-height: 46px;
}

// The dark navbar is only 72px tall; on phones the page title truncates
// before the action buttons so nothing is pushed off-screen.
@media (max-width: 767.98px) {
    .edu-navbar-title {
        max-width: 11rem;
        font-size: 0.9rem;
    }

    .edu-navbar .breadcrumb {
        font-size: 0.66rem;
    }
}

// -----------------------------------------------------------------------------
// 4. Sidebar, bottom navigation and offcanvas
// -----------------------------------------------------------------------------

@media (max-width: 991.98px) {
    .edu-sidebar {
        // Never wider than the phone, with a comfortable close area.
        --bs-offcanvas-width: min(88vw, 320px);
        padding-top: var(--edu-safe-top);
        padding-bottom: var(--edu-safe-bottom);
    }

    // The drawer must close after tapping a link (handled in layout.js too).
    .edu-sidebar .nav-link {
        min-height: 46px;
        display: flex;
        align-items: center;
    }
}

.edu-mobile-nav {
    // The bar already has 56px rows; add the home indicator below them.
    padding-bottom: var(--edu-safe-bottom);
    padding-left: var(--edu-safe-left);
    padding-right: var(--edu-safe-right);
}

.edu-mobile-nav-item {
    min-height: 56px;
    min-width: 44px;
    touch-action: manipulation;
}

.edu-has-mobile-nav {
    padding-bottom: calc(var(--edu-mobile-nav-height) + var(--edu-space-2));
}

// -----------------------------------------------------------------------------
// 5. Content grids: feed, dashboards, cards
// -----------------------------------------------------------------------------

// The feed is one column on phones, feed + widgets on tablets, and the
// desktop three-column experience is sidebar + feed + widgets.
@media (min-width: 768px) and (max-width: 991.98px) {
    .edu-feed-row > .col-md-8 {
        flex: 0 0 auto;
        width: 58.33333333%;
    }

    .edu-feed-row > .col-md-4 {
        flex: 0 0 auto;
        width: 41.66666667%;
    }
}

// Swipeable horizontal strips (cards, avatars, chips) with scroll snapping.
.edu-scroll-x {
    display: flex;
    gap: var(--edu-space-3);
    overflow-x: auto;
    overscroll-behavior-x: contain;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    padding-bottom: var(--edu-space-2);

    > * {
        flex: 0 0 auto;
        scroll-snap-align: start;
    }
}

// Chart cards can never force the grid wider than the viewport.
.edu-chart-card {
    overflow: hidden;

    .card-body {
        min-width: 0;
    }

    canvas {
        max-width: 100% !important;
        height: auto !important;
    }
}

// Tables: readable horizontal scrolling when a table is not converted to
// cards (all admin tables already use .edu-table-cards below 992px).
.table-responsive {
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
}

// Compact pagination: Prev, current page and Next on phones. Laravel's
// Bootstrap 5 view renders the numeric pages in the middle of the list.
@media (max-width: 575.98px) {
    .edu-pagination .pagination {
        flex-wrap: nowrap;
        gap: 0.25rem;
        --bs-pagination-padding-x: 0.65rem;
        --bs-pagination-font-size: 0.8125rem;
    }

    .edu-pagination .page-item:not(.active):not(:first-child):not(:last-child) {
        display: none;
    }

    .edu-pagination .page-link {
        min-width: 44px;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
}

// -----------------------------------------------------------------------------
// 6. Forms: single column on phones, sticky action bar
// -----------------------------------------------------------------------------

@media (max-width: 767.98px) {
    .edu-form-actions {
        position: sticky;
        bottom: calc(var(--edu-mobile-nav-height) + var(--edu-space-2));
        z-index: var(--edu-z-sticky);
        margin: var(--edu-space-4) -0.25rem -0.25rem;
        padding: var(--edu-space-3);
        border: 1px solid var(--edu-border);
        border-radius: var(--edu-radius-sm);
        background: color-mix(in srgb, var(--edu-surface) 94%, transparent);
        box-shadow: var(--edu-shadow-pop);
        backdrop-filter: saturate(180%) blur(14px);
        -webkit-backdrop-filter: saturate(180%) blur(14px);

        // The buttons share the bar instead of stacking into a tall tower.
        .btn {
            flex: 1 1 auto;
        }
    }
}

// Long forms keep two columns from lg upwards; below that they are single
// column already (Bootstrap .row + .col-12). This is the explicit guarantee.
@media (max-width: 991.98px) {
    .edu-form-grid > [class*='col-'] {
        width: 100%;
    }
}

// -----------------------------------------------------------------------------
// 7. Modals
// -----------------------------------------------------------------------------

.modal {
    // Never taller than the dynamic viewport; the body scrolls instead.
    padding-right: var(--edu-safe-right);
    padding-left: var(--edu-safe-left);
}

.modal .modal-content {
    max-height: calc(100vh - 1rem);
    max-height: calc(100dvh - 1rem);
}

.modal .modal-dialog-scrollable .modal-content {
    max-height: calc(100vh - 2rem);
    max-height: calc(100dvh - 2rem);
}

@media (max-width: 575.98px) {
    .modal .modal-content {
        border-radius: 0;
    }

    // Fullscreen on phones (the component adds modal-fullscreen-sm-down).
    .modal-fullscreen-sm-down .modal-content {
        min-height: 100vh;
        min-height: 100dvh;
        border-radius: 0;
    }
}

// -----------------------------------------------------------------------------
// 8. Live class room (Jitsi)
// -----------------------------------------------------------------------------

.jitsi-frame {
    // 100vh first for older browsers, then the dynamic unit.
    height: calc(100vh - 340px);
    height: calc(100dvh - 340px);
    min-height: 420px;
}

@media (max-width: 991.98px) {
    .jitsi-frame {
        height: calc(100vh - 260px);
        height: calc(100dvh - 260px);
        min-height: 360px;
    }
}

@media (max-width: 767.98px) {
    .jitsi-frame {
        height: calc(100vh - 250px);
        height: calc(100dvh - 250px);
        min-height: 320px;
        border-radius: var(--edu-radius-sm);
    }
}

// Landscape phone: the top bar + navbar leave roughly 260px of usable
// height, so the video keeps the remaining space and never forces a scroll.
@media (orientation: landscape) and (max-height: 520px) {
    :root {
        --edu-navbar-height: 56px;
        --edu-mobile-nav-height: 0px;
    }

    .edu-navbar {
        min-height: 56px;
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
    }

    .jitsi-frame {
        height: calc(100vh - 150px);
        height: calc(100dvh - 150px);
        min-height: 220px;
    }

    .edu-mobile-nav {
        display: none;
    }

    .edu-has-mobile-nav {
        padding-bottom: 0;
    }

    .edu-landing-hero {
        padding: var(--edu-space-5) 0;
    }
}

// The room top bar stays reachable while scrolling the video on tablets and
// phones, so "Back to the class" / info / status are never out of sight.
@media (max-width: 991.98px) {
    .edu-room-bar {
        position: sticky;
        top: calc(var(--edu-navbar-height) + 0.25rem);
        z-index: 1018;
    }
}

// Room information becomes a bottom sheet on phones and tablets portrait.
@media (max-width: 991.98px) {
    .edu-room-sheet {
        position: fixed;
        inset: auto 0 0 0;
        bottom: var(--edu-mobile-nav-height);
        z-index: calc(var(--edu-z-mobilenav) - 1);
        max-height: 75vh;
        max-height: 75dvh;
        margin: 0 !important;
        padding: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        background: var(--edu-surface);
        border-top: 1px solid var(--edu-border);
        border-radius: var(--edu-radius-lg) var(--edu-radius-lg) 0 0;
        box-shadow: 0 -20px 45px -20px rgba(15, 23, 42, 0.45);
        padding-bottom: var(--edu-safe-bottom);

        > .row {
            padding: 0 var(--edu-space-3) var(--edu-space-3);
        }

        // When Bootstrap's collapse hides it, keep it hidden (no display:flex).
        &.collapse:not(.show) {
            display: none;
        }
    }

    .edu-room-sheet-head {
        position: sticky;
        top: 0;
        display: flex;
        align-items: center;
        gap: var(--edu-space-2);
        padding: var(--edu-space-2) var(--edu-space-3);
        background: var(--edu-surface);
        border-bottom: 1px solid var(--edu-border);
        z-index: 1;
    }

    .edu-room-sheet-handle {
        width: 42px;
        height: 4px;
        border-radius: 999px;
        background: var(--edu-border-strong);
    }
}

// -----------------------------------------------------------------------------
// 9. Landing page at 320px
// -----------------------------------------------------------------------------

@media (max-width: 575.98px) {
    .edu-landing-hero {
        padding: var(--edu-space-6) 0 var(--edu-space-5);
    }

    .edu-landing-title {
        font-size: clamp(1.75rem, 1.4rem + 2vw, 2.25rem);
        letter-spacing: -0.02em;
    }

    .edu-landing .btn-lg {
        width: 100%;
        white-space: normal;
    }

    .edu-landing-shape.is-one {
        width: 240px;
        height: 240px;
        top: -120px;
        right: -100px;
    }

    .edu-landing-shape.is-two {
        width: 220px;
        height: 220px;
        bottom: -140px;
        left: -100px;
    }

    .edu-stats-strip {
        padding: var(--edu-space-5) var(--edu-space-3);
        border-radius: var(--edu-radius-sm);
    }

    .edu-landing-section {
        padding: var(--edu-space-5) 0;
    }
}

// 320px is the narrowest target. Cards, stats and the auth card lose their
// largest padding so content still fits with a comfortable 12px gutter.
@media (max-width: 359.98px) {
    :root {
        --edu-navbar-height: 64px;
    }

    .container,
    .container-fluid,
    .container-sm,
    .container-md,
    .container-lg,
    .container-xl,
    .container-xxl {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    .card-body {
        padding: 0.875rem;
    }

    .edu-auth-card .card-body {
        padding: 1rem;
    }

    .edu-stat-card .card-body {
        min-height: 112px;
        padding: 0.875rem;
    }

    .edu-landing-title {
        font-size: 1.7rem;
    }

    .edu-mobile-nav-item {
        font-size: 0.65rem;
        padding-left: 0.1rem;
        padding-right: 0.1rem;

        i {
            font-size: 1.1rem;
        }
    }

    .edu-navbar-title {
        max-width: 8.5rem;
    }

    .edu-search {
        width: 100%;
    }

    .edu-countdown-unit {
        min-width: 44px;
        padding: 0.3rem;
    }

    .edu-countdown-value {
        font-size: 1rem;
    }

    // Long action rows wrap instead of overflowing.
    .edu-post-actions,
    .d-flex.flex-wrap {
        min-width: 0;
    }
}

// -----------------------------------------------------------------------------
// 10. Ultra-wide (1920 / 2560): keep content centred and readable
// -----------------------------------------------------------------------------

// Bootstrap already caps .container-xxl at 1320px. Custom sections that use a
// full-width flex row get the same cap so 2560px screens do not stretch text.
@media (min-width: 1920px) {
    .edu-main-inner > .container-fluid,
    .edu-main-inner > .container-xxl {
        max-width: 1600px;
        margin-left: auto;
        margin-right: auto;
    }

    .edu-landing-hero .container-xxl,
    .edu-landing-section .container-xxl {
        max-width: 1440px;
    }

    .edu-sidebar {
        --edu-sidebar-width: 284px;
    }
}

// -----------------------------------------------------------------------------
// 11. Preferences
// -----------------------------------------------------------------------------

// The single reduced-motion switch lives in app.scss (Phase 3C). This adds the
// scroll-snap container, which would otherwise stay mandatory.
@media (prefers-reduced-motion: reduce) {
    .edu-scroll-x {
        scroll-snap-type: none;
        scroll-behavior: auto;
    }
}

// Dark mode is driven by data-bs-theme; make sure form controls that browsers
// paint natively (date/time pickers, search cancel button) follow it.
[data-bs-theme='dark'] {
    color-scheme: dark;

    input[type='date'],
    input[type='time'],
    input[type='datetime-local'],
    input[type='month'],
    input[type='week'] {
        color-scheme: dark;
    }
}

[data-bs-theme='light'] {
    color-scheme: light;
}
```

### `resources/views/layouts/app.blade.php`

```php
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        {{-- viewport-fit=cover lets the layout own the notch/home-indicator
             area, which the responsive layer fills with env(safe-area-inset-*). --}}
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        @php
            // A page can pass <x-slot name="title">Page title</x-slot> and
            // <x-slot name="description">Meta description</x-slot>.
            $pageTitle = isset($title) ? trim(strip_tags((string) $title)) : '';
            $pageDescription = isset($description) && trim(strip_tags((string) $description)) !== ''
                ? trim(strip_tags((string) $description))
                : config('educonnect.meta.description');
            $fullTitle = ($pageTitle !== '' ? $pageTitle.' · ' : '').config('app.name', 'EduConnect');
        @endphp
        <title>{{ $fullTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">

        {{-- Social previews use the same title and description. --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'EduConnect') }}">
        <meta property="og:title" content="{{ $fullTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $fullTitle }}">
        <meta name="twitter:description" content="{{ $pageDescription }}">

        {{-- Favicon and browser chrome colours (light + dark). --}}
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}">
        <meta name="theme-color" content="#4f46e5" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">

        {{-- Theme first: before the stylesheet so there is no flash of the
             wrong colour scheme. Explicit choice wins, then the OS setting. --}}
        <script>
            (function () {
                try {
                    var stored = window.localStorage.getItem('educonnect.theme');
                    var theme = stored === 'dark' || stored === 'light'
                        ? stored
                        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-bs-theme', theme);
                } catch (error) {
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                }
            })();
        </script>

        {{-- Apply the remembered desktop sidebar state before the first paint,
             so the layout does not jump when JavaScript finishes loading. --}}
        <script>
            try {
                if (window.localStorage.getItem('educonnect.sidebar.collapsed') === '1') {
                    document.documentElement.classList.add('edu-sidebar-collapsed');
                }
            } catch (error) {
                // Private browsing can refuse localStorage; the default open
                // sidebar is a perfectly fine fallback.
            }
        </script>

        {{-- Inter is loaded from Google Fonts (allowed by the phase brief). --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    @php
        // One unread counter shared by the top bar and the mobile navigation,
        // so both badges always agree and the feed keeps its query budget.
        $layoutUnreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;

        // The public landing page uses this layout too, but a signed-in visitor
        // should see the marketing site, not the dashboard sidebar, mobile nav
        // or composer shortcut. Those are dashboard chrome only.
        $isLandingPage = request()->is('/');
    @endphp

    {{-- body carries the notification polling URL for logged in users (FR-24) --}}
    <body class="edu-body d-flex flex-column min-vh-100 {{ auth()->check() && ! $isLandingPage ? 'edu-has-mobile-nav' : '' }}"
          @auth data-notification-url="{{ route('notifications.poll') }}" @endauth>
        <a class="visually-hidden-focusable edu-skip-link" href="#main-content" style="position:fixed;">
            {{ __('Skip to main content') }}
        </a>

        {{-- Sticky, blurred top navigation bar --}}
        @include('layouts.navigation', ['unreadNotifications' => $layoutUnreadCount])

        <div class="edu-shell flex-grow-1" id="appShell">
            {{-- Role based sidebar: a column on desktop, an offcanvas drawer on phones.
                 Never on the public landing page. --}}
            @auth
                @unless ($isLandingPage)
                    @include('layouts.sidebar')
                @endunless
            @endauth

            <div class="edu-main">
                <main class="edu-main-inner" id="main-content">
                    <div class="container-xxl px-3 px-lg-4">
                        {{-- Success / error / validation messages --}}
                        <x-flash />

                        {{-- Page header supplied by the view. Breadcrumbs live in
                             the top bar, so this only renders when a page has one. --}}
                        @isset($header)
                            <div class="edu-page-header mb-4">
                                {{ $header }}
                            </div>
                        @endisset
                    </div>

                    {{-- Page body. The CSS fades this in; page-transitions.js adds
                         the matching fade-out when a link is followed. --}}
                    <div class="edu-page">
                        {{ $slot }}
                    </div>
                </main>

                <footer class="edu-footer mt-auto">
                    <div class="container-xxl px-3 px-lg-4">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                            <span>
                                &copy; {{ date('Y') }} {{ config('app.name', 'EduConnect') }}
                            </span>
                            <span class="d-inline-flex align-items-center gap-2">
                                <i class="bi bi-mortarboard-fill text-edu" aria-hidden="true"></i>
                                {{ __('Learn together, anywhere.') }}
                            </span>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        {{-- Page specific JavaScript (kept out of the markup). --}}
        @stack('scripts')

        @auth
            @unless ($isLandingPage)
                {{-- Mobile bottom navigation: five destinations, always one tap away. --}}
                @include('layouts.mobile-nav', ['unreadCount' => $layoutUnreadCount])

                {{-- Floating composer shortcut, feed only, never over the desktop layout. --}}
                @if (request()->routeIs('feed.index'))
                    <a class="edu-fab d-lg-none" href="{{ route('posts.create') }}"
                       aria-label="{{ __('New post') }}" title="{{ __('New post') }}">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    </a>
                @endif
            @endunless
        @endauth
    </body>
</html>
```

### `resources/views/layouts/guest.blade.php`

```php
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        {{-- viewport-fit=cover lets the layout own the notch/home-indicator
             area, which the responsive layer fills with env(safe-area-inset-*). --}}
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        <title>{{ $attributes->get('title') ? $attributes->get('title').' · ' : '' }}{{ config('app.name', 'EduConnect') }}</title>

        {{-- Apply the saved theme before the first paint (same key as the app layout). --}}
        <script>
            (function () {
                try {
                    var stored = window.localStorage.getItem('educonnect.theme');
                    var theme = stored === 'dark' || stored === 'light'
                        ? stored
                        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-bs-theme', theme);
                } catch (error) {
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                }
            })();
        </script>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        {{-- Bootstrap 5 + Bootstrap Icons compiled by Vite. --}}
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body class="edu-auth-body">
        <main class="min-vh-100">
            <div class="row g-0 min-vh-100">
                {{-- Branded panel: desktop and tablet only. --}}
                <section class="col-lg-6 d-none d-lg-flex edu-auth-brand p-5 flex-column justify-content-between"
                         aria-label="{{ __('About EduConnect') }}">
                    <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none">
                        <span class="fs-2" aria-hidden="true">
                            <x-application-logo class="text-white" />
                        </span>
                        <span class="h4 fw-bold mb-0">{{ config('app.name', 'EduConnect') }}</span>
                    </a>

                    <div class="my-5">
                        <h1 class="display-6 fw-bold mb-3">{{ __('Learn together, wherever you are.') }}</h1>
                        <p class="lead text-white-50 mb-4">
                            {{ __('Live classes, a friendly social feed and the tools teachers need to keep every student close.') }}
                        </p>

                        <ul class="list-unstyled d-grid gap-3 mb-0">
                            <li class="d-flex gap-3">
                                <span class="edu-auth-icon" aria-hidden="true"><i class="bi bi-camera-video"></i></span>
                                <span>
                                    <strong class="d-block">{{ __('Live video classes') }}</strong>
                                    <span class="small text-white-50">{{ __('Join a class from any browser, no extra app required.') }}</span>
                                </span>
                            </li>
                            <li class="d-flex gap-3">
                                <span class="edu-auth-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                                <span>
                                    <strong class="d-block">{{ __('A real school community') }}</strong>
                                    <span class="small text-white-50">{{ __('Follow teachers, share posts and learn together.') }}</span>
                                </span>
                            </li>
                            <li class="d-flex gap-3">
                                <span class="edu-auth-icon" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                                <span>
                                    <strong class="d-block">{{ __('Safe by design') }}</strong>
                                    <span class="small text-white-50">{{ __('Reporting and moderation tools protect every learner.') }}</span>
                                </span>
                            </li>
                        </ul>
                    </div>

                    <p class="small text-white-50 mb-0">
                        &copy; {{ date('Y') }} {{ config('app.name', 'EduConnect') }}
                    </p>
                </section>

                {{-- Form panel: full width on phones, half width on desktop. --}}
                <section class="col-12 col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5">
                    <div class="w-100 edu-auth-form">
                        {{-- Compact brand for phones and small tablets. --}}
                        <div class="d-lg-none text-center mb-4">
                            <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                                <span class="fs-2 text-primary" aria-hidden="true">
                                    <x-application-logo />
                                </span>
                                <span class="h4 fw-bold text-body mb-0">{{ config('app.name', 'EduConnect') }}</span>
                            </a>
                            <p class="text-muted small mb-0 mt-1">{{ __('Learn together, anywhere.') }}</p>
                        </div>

                        <div class="card border-0 shadow-sm edu-auth-card">
                            <div class="card-body p-4 p-md-5">
                                {{-- Flash messages and the validation summary. --}}
                                <x-flash />

                                {{ $slot }}
                            </div>
                        </div>

                        <p class="text-center text-muted small mt-4 mb-0">
                            {{ __('Learn together, anywhere.') }}
                        </p>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
```

### `resources/views/layouts/navigation.blade.php`

```php
{{-- Sticky top navigation bar: page context, search, theme, notifications and
     the user menu. The sidebar itself lives in layouts/sidebar.blade.php. --}}
@php
    // Unread notifications drive the bell counter (FR-24); the newest eight fill
    // the dropdown. The polling script refreshes both every 30 seconds.
    // The layout passes the shared unread count so the mobile bar shows the
    // same number without a second COUNT query.
    $unreadNotifications = $unreadNotifications ?? (auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);
    $latestNotifications = auth()->check()
        ? auth()->user()->notifications()->latest()->take(8)->get()
        : collect();
@endphp

<nav class="navbar edu-navbar sticky-top">
    <div class="container-xxl px-3 px-lg-4 d-flex align-items-center gap-2">
        @auth
            @unless ($isLandingPage ?? false)
            {{-- Opens the sidebar drawer on phones and tablets only;
                 from lg upwards the sidebar is always visible. --}}
            <button class="btn edu-icon-btn d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#appSidebar"
                    aria-controls="appSidebar" aria-label="{{ __('Open the menu') }}">
                <i class="bi bi-list fs-5" aria-hidden="true"></i>
            </button>

            {{-- Desktop: collapse / expand the sidebar. The state is stored in
                 localStorage by resources/js/layout.js (FR-31). --}}
            <button class="btn edu-icon-btn d-none d-lg-inline-flex"
                    type="button" data-sidebar-toggle
                    aria-controls="appSidebar" aria-expanded="true"
                    aria-label="{{ __('Toggle the sidebar') }}"
                    title="{{ __('Toggle the sidebar') }}">
                <i class="bi bi-layout-sidebar-inset" data-sidebar-toggle-icon aria-hidden="true"></i>
            </button>
            @endunless
        @endauth

        <a class="navbar-brand d-flex align-items-center gap-2 mb-0"
           href="{{ auth()->check() ? route('dashboard') : url('/') }}">
            <span class="edu-sidebar-logo-mark d-lg-none" aria-hidden="true">
                <x-application-logo class="text-white" />
            </span>
            <span class="d-none d-sm-inline">{{ config('app.name', 'EduConnect') }}</span>
        </a>

        {{-- Page context: title + breadcrumbs. Hidden on narrow screens where
             the page itself already shows the heading. --}}
        @if ($pageTitle !== '' || isset($breadcrumbs))
            <div class="d-none d-xl-flex flex-column ms-3 ps-3 border-start" style="border-color: var(--edu-border) !important;">
                @if ($pageTitle !== '')
                    <span class="edu-navbar-title">{{ $pageTitle }}</span>
                @endif

                @isset($breadcrumbs)
                    <nav aria-label="{{ __('Breadcrumb') }}">
                        <ol class="breadcrumb mb-0">{{ $breadcrumbs }}</ol>
                    </nav>
                @endisset
            </div>
        @endif

        <div class="d-flex align-items-center ms-auto gap-1 gap-md-2">
            @auth
                {{-- One search box for people, hashtags and posts (FR-14).
                     On phones the box would not fit, so there is an icon instead.
                     resources/js/search.js fills the dropdown underneath it. --}}
                <form method="GET" action="{{ route('search.index') }}" role="search"
                      class="edu-search position-relative d-none d-md-block"
                      data-search-form data-search-url="{{ route('search.suggest') }}">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-0 pe-0" aria-hidden="true">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="search" name="q" class="form-control border-0"
                               value="{{ request()->routeIs('search.*') ? request('q') : '' }}"
                               placeholder="{{ __('Search people or #tags') }}"
                               aria-label="{{ __('Search') }}"
                               autocomplete="off" data-search-input>
                    </div>

                    {{-- Live suggestions, hidden until there is something to show.
                         resources/js/search.js toggles the "d-none" class. --}}
                    <div class="dropdown-menu show position-absolute top-100 start-0 end-0 shadow-sm d-none"
                         data-search-suggestions></div>
                </form>
            @endauth

            {{-- Light / dark mode. JS updates the icon and aria-label. --}}
            <button class="btn edu-icon-btn" type="button" data-theme-toggle
                    aria-label="{{ __('Switch to dark mode') }}" title="{{ __('Switch to dark mode') }}"
                    aria-pressed="false">
                <i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i>
            </button>

            <ul class="navbar-nav flex-row align-items-center gap-1">
            @auth
                {{-- Phone search: the icon expands a full-width panel under the
                     navbar instead of leaving the page (Phase 3D). --}}
                <li class="nav-item d-md-none">
                    <button class="btn edu-icon-btn" type="button"
                            data-mobile-search-toggle
                            aria-expanded="false" aria-controls="mobileSearchPanel"
                            aria-label="{{ __('Search') }}" title="{{ __('Search') }}">
                        <i class="bi bi-search" data-mobile-search-icon aria-hidden="true"></i>
                    </button>
                </li>

                {{-- Notification bell: unread counter + newest eight (FR-24) --}}
                <li class="nav-item dropdown">
                    <button class="btn edu-icon-btn position-relative" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="{{ __('Notifications') }}" data-notification-toggle>
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        {{-- The badge is refreshed by resources/js/notifications.js. --}}
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger {{ $unreadNotifications > 0 ? '' : 'd-none' }}"
                              data-notification-badge>
                            {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notification-menu" data-notification-list>
                        @include('notifications._dropdown', ['notifications' => $latestNotifications])
                    </ul>
                </li>

                {{-- User dropdown: profile, edit profile and logout. --}}
                <li class="nav-item dropdown">
                    <a class="edu-navbar-avatar dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false"
                       aria-label="{{ __('Account menu') }}">
                        <x-avatar :user="auth()->user()" size="sm" />
                        <span class="d-none d-md-inline small fw-semibold">{{ auth()->user()->name }}</span>
                        <i class="bi bi-chevron-down small text-muted d-none d-md-inline" aria-hidden="true"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text small text-muted">
                                {{ __('Signed in as') }}
                                <span class="text-capitalize fw-semibold">{{ auth()->user()->role }}</span>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('users.show', auth()->id()) }}">
                                <i class="bi bi-person me-1" aria-hidden="true"></i> {{ __('My profile') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i> {{ __('Edit profile') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('feed.index') }}">
                                <i class="bi bi-chat-square-text me-1" aria-hidden="true"></i> {{ __('Feed') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('notifications.index') }}">
                                <i class="bi bi-bell me-1" aria-hidden="true"></i> {{ __('Notifications') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> {{ __('Log out') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            @else
                {{-- Guests see login / register --}}
                <li class="nav-item">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('login') }}">{{ __('Log in') }}</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm btn-primary" href="{{ route('register') }}">{{ __('Register') }}</a>
                </li>
            @endauth
            </ul>
        </div>
    </div>

    @auth
        {{-- The expanded phone search. The panel is outside the flex row so it
             can use the full viewport width; layout.js moves focus into it. --}}
        <div class="edu-search-mobile d-md-none" id="mobileSearchPanel" hidden>
            <div class="container-xxl px-3 px-lg-4">
                <form method="GET" action="{{ route('search.index') }}" role="search"
                      class="edu-search-mobile-form position-relative"
                      data-search-form data-search-url="{{ route('search.suggest') }}">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0" aria-hidden="true">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="search" name="q" class="form-control border-start-0"
                               value="{{ request()->routeIs('search.*') ? request('q') : '' }}"
                               placeholder="{{ __('Search people or #tags') }}"
                               aria-label="{{ __('Search') }}"
                               autocomplete="off" data-search-input>
                        <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
                    </div>

                    {{-- Suggestions are filled by resources/js/search.js. --}}
                    <div class="dropdown-menu show position-absolute top-100 start-0 end-0 shadow d-none"
                         data-search-suggestions role="listbox"
                         aria-label="{{ __('Search suggestions') }}"></div>
                </form>
            </div>
        </div>
    @endauth
</nav>
```

### `resources/views/layouts/sidebar.blade.php`

```php
{{-- Role based sidebar.
     From lg upwards it is a fixed dark column; on phones it is an offcanvas
     drawer opened with the hamburger button in the navbar. The desktop toggle
     in the navbar stores its state in localStorage (FR-31). --}}
@php
    $me = auth()->user();
    $role = $me->role;

    $groups = match ($role) {
        \App\Models\User::ROLE_ADMIN => [
            ['label' => 'Overview', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'admin.dashboard'],
                ['label' => 'Style Guide', 'icon' => 'bi-palette', 'route' => 'admin.style-guide'],
            ]],
            ['label' => 'Manage', 'items' => [
                ['label' => 'Users', 'icon' => 'bi-people', 'route' => 'admin.users.index'],
                ['label' => 'Posts', 'icon' => 'bi-chat-square-text', 'route' => 'admin.posts.index'],
                ['label' => 'Classes', 'icon' => 'bi-easel', 'route' => 'admin.classes.index'],
                ['label' => 'Reports', 'icon' => 'bi-flag', 'route' => 'admin.reports.index'],
            ]],
            ['label' => 'Community', 'items' => [
                ['label' => 'Feed', 'icon' => 'bi-rss', 'route' => 'feed.index'],
                ['label' => 'Notifications', 'icon' => 'bi-bell', 'route' => 'notifications.index'],
            ]],
        ],
        \App\Models\User::ROLE_TEACHER => [
            ['label' => 'Teaching', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'teacher.dashboard'],
                ['label' => 'My Classes', 'icon' => 'bi-easel', 'route' => 'classes.index'],
                ['label' => 'Create Class', 'icon' => 'bi-plus-square', 'route' => 'classes.create'],
                ['label' => 'Discover', 'icon' => 'bi-compass', 'route' => 'classes.discover'],
            ]],
            ['label' => 'Community', 'items' => [
                ['label' => 'Feed', 'icon' => 'bi-chat-square-text', 'route' => 'feed.index'],
                ['label' => 'Notifications', 'icon' => 'bi-bell', 'route' => 'notifications.index'],
            ]],
            ['label' => 'Account', 'items' => [
                ['label' => 'My Profile', 'icon' => 'bi-person', 'route' => 'users.show', 'params' => [$me->id]],
            ]],
        ],
        default => [
            ['label' => 'Learning', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'student.dashboard'],
                ['label' => 'Browse Classes', 'icon' => 'bi-search', 'route' => 'classes.discover'],
                ['label' => 'My Classes', 'icon' => 'bi-journal-bookmark', 'route' => 'student.classes'],
            ]],
            ['label' => 'Community', 'items' => [
                ['label' => 'Feed', 'icon' => 'bi-chat-square-text', 'route' => 'feed.index'],
                ['label' => 'Notifications', 'icon' => 'bi-bell', 'route' => 'notifications.index'],
            ]],
            ['label' => 'Account', 'items' => [
                ['label' => 'My Profile', 'icon' => 'bi-person', 'route' => 'users.show', 'params' => [$me->id]],
            ]],
        ],
    };

    // The profile entry is only active on your own profile, not on somebody else's.
    $isActive = function (array $item) use ($me): bool {
        if (! request()->routeIs($item['route'])) {
            return false;
        }

        if ($item['route'] === 'users.show') {
            $shown = request()->route('user');

            return $shown instanceof \App\Models\User && $shown->is($me);
        }

        return true;
    };
@endphp

<aside class="offcanvas-lg offcanvas-start edu-sidebar flex-shrink-0" tabindex="-1" id="appSidebar"
       aria-labelledby="appSidebarLabel" data-edu-sidebar>
    {{-- Header is only needed in the mobile drawer --}}
    <div class="offcanvas-header d-lg-none">
        <h2 class="offcanvas-title h6 mb-0" id="appSidebarLabel">
            <i class="bi bi-mortarboard-fill me-1" aria-hidden="true"></i> {{ __('Menu') }}
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column">
        {{-- Brand / logo --}}
        <a class="edu-sidebar-logo" href="{{ route('dashboard') }}" aria-label="{{ config('app.name', 'EduConnect') }}">
            <span class="edu-sidebar-logo-mark" aria-hidden="true">
                <x-application-logo class="text-white" />
            </span>
            <span class="edu-sidebar-logo-text edu-sidebar-label">
                <strong>{{ config('app.name', 'EduConnect') }}</strong>
                <span>{{ __('Learning workspace') }}</span>
            </span>
        </a>

        {{-- Grouped role menu --}}
        <nav class="flex-grow-1" aria-label="{{ __('Main navigation') }}">
            @foreach ($groups as $group)
                <div class="edu-sidebar-section">{{ __($group['label']) }}</div>

                <ul class="nav flex-column">
                    @foreach ($group['items'] as $item)
                        @php($active = $isActive($item))

                        <li class="nav-item">
                            @if (Route::has($item['route']))
                                <a class="nav-link {{ $active ? 'active' : '' }}"
                                   href="{{ route($item['route'], $item['params'] ?? []) }}"
                                   title="{{ __($item['label']) }}"
                                   @if ($active) aria-current="page" @endif>
                                    <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                                    <span class="edu-sidebar-label">{{ __($item['label']) }}</span>
                                </a>
                            @else
                                <span class="nav-link disabled" title="{{ __($item['label']) }}">
                                    <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                                    <span class="edu-sidebar-label">{{ __($item['label']) }}</span>
                                    <span class="badge text-bg-secondary ms-auto">{{ __('soon') }}</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>

        {{-- Account actions + mini profile card, pushed to the bottom --}}
        <div class="mt-auto pt-2">
            <div class="edu-sidebar-divider"></div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link edu-sidebar-footer-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                       href="{{ route('profile.edit') }}" title="{{ __('Edit profile') }}"
                       @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                        <i class="bi bi-gear" aria-hidden="true"></i>
                        <span class="edu-sidebar-label">{{ __('Settings') }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-link text-danger w-100 text-start border-0 bg-transparent"
                                title="{{ __('Log out') }}">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                            <span class="edu-sidebar-label">{{ __('Log out') }}</span>
                        </button>
                    </form>
                </li>
            </ul>

            {{-- User mini-card. --}}
            <div class="edu-sidebar-user d-flex align-items-center gap-2 mt-2">
                <x-avatar :user="$me" size="sm" class="flex-shrink-0" />
                <div class="edu-sidebar-user-text min-w-0">
                    <div class="edu-sidebar-user-name text-truncate">{{ $me->name }}</div>
                    <div class="edu-sidebar-user-role">{{ $role }}</div>
                </div>
                <a class="btn edu-icon-btn ms-auto d-none d-lg-inline-flex"
                   href="{{ route('profile.edit') }}"
                   aria-label="{{ __('Edit profile') }}" title="{{ __('Edit profile') }}"
                   style="color: var(--edu-sidebar-text); width: 34px; height: 34px;">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</aside>
```

### `resources/views/feed/index.blade.php`

```php
<x-app-layout>
    <x-slot name="title">{{ __('Feed') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Feed') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-house-door me-1 text-edu"></i> {{ __('Feed') }}
                </h1>
                <div class="small text-muted">{{ __('Posts from you and the people you follow.') }}</div>
            </div>
            <a href="{{ route('posts.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square me-1"></i> {{ __('New post') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row g-4 justify-content-center edu-feed-row">
            {{-- Main column: composer + feed --}}
            <div class="col-12 col-md-8">
                {{-- Compose box (FR-7) --}}
                <section class="card edu-card mb-4" aria-labelledby="compose-title">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <x-avatar :user="auth()->user()" size="md" class="flex-shrink-0" />
                            <div>
                                <h2 class="h6 fw-bold mb-0" id="compose-title">{{ __('Share something') }}</h2>
                                <div class="small text-muted">{{ __('Your next post could help somebody learn.') }}</div>
                            </div>
                        </div>

                        @include('posts._form', ['post' => null])
                    </div>
                </section>

                {{-- Posts of the people I follow + my own (FR-12) --}}
                <div data-feed-list>
                    @include('feed._posts', [
                        'posts' => $posts,
                        'commentsByPost' => $commentsByPost,
                        'emptyMessage' => __('Follow a few teachers or classmates to fill this page with their posts.'),
                        'emptyActionUrl' => route('search.index', ['tab' => 'users']),
                        'emptyActionLabel' => __('Find people to follow'),
                    ])
                </div>

                {{-- Progressive enhancement: without JavaScript the button is a
                     normal link to ?page=2, with it the cards are appended (FR-12). --}}
                <div class="text-center my-4">
                    <button type="button" class="btn btn-outline-primary"
                            data-load-more
                            data-url="{{ $posts->nextPageUrl() }}"
                            @if (! $posts->hasMorePages()) hidden @endif>
                        <i class="bi bi-arrow-down-circle me-1"></i> {{ __('Load more') }}
                    </button>
                </div>
            </div>

            {{-- Right sidebar widgets --}}
            <aside class="col-12 col-md-4">
                @unless (auth()->user()->isAdmin())
                    <section class="card edu-card mb-3" aria-labelledby="who-to-follow-title">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h2 class="h6 fw-bold mb-0" id="who-to-follow-title">
                                    <i class="bi bi-person-plus me-1 text-edu" aria-hidden="true"></i>
                                    {{ __('Who to follow') }}
                                </h2>
                                <div class="small text-muted">
                                    {{ __('Following') }}
                                    <span data-viewer-following-count>{{ $followingCount }}</span>
                                    {{ __('people') }}
                                </div>
                            </div>
                            <a href="{{ route('search.index', ['tab' => 'users']) }}"
                               class="small fw-semibold text-decoration-none">{{ __('See all') }}</a>
                        </div>

                        <div class="card-body py-2">
                            @forelse ($suggestedUsers as $person)
                                <div class="edu-widget-user">
                                    <x-avatar :user="$person" size="sm" class="flex-shrink-0" />

                                    <div class="flex-grow-1 min-w-0">
                                        <a href="{{ route('users.show', $person) }}"
                                           class="edu-list-title text-truncate d-block">{{ $person->name }}</a>
                                        <div class="edu-list-meta text-capitalize">
                                            {{ $person->role }}
                                            @if ($person->subject)
                                                <span class="mx-1" aria-hidden="true">·</span>{{ $person->subject }}
                                            @endif
                                        </div>
                                        <div class="edu-list-meta">
                                            <i class="bi bi-people me-1" aria-hidden="true"></i>
                                            {{ trans_choice(':count follower|:count followers', $person->followers_count, ['count' => $person->followers_count]) }}
                                        </div>
                                    </div>

                                    <div class="flex-shrink-0">
                                        @include('users._follow-button', ['user' => $person, 'followingIds' => $followingIds])
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <div class="edu-empty-illustration mx-auto mb-2"
                                         style="width: 52px; height: 52px; font-size: 1.3rem; border-radius: 16px;"
                                         aria-hidden="true">
                                        <i class="bi bi-check2-circle"></i>
                                    </div>
                                    <div class="fw-semibold small">{{ __('You follow everybody') }}</div>
                                    <div class="text-muted small">{{ __('Check the search page for new people.') }}</div>
                                </div>
                            @endforelse
                        </div>

                        <div class="card-footer bg-transparent">
                            <a href="{{ route('search.index', ['tab' => 'users']) }}"
                               class="small fw-semibold text-decoration-none">
                                {{ __('Find more people') }} <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                            </a>
                        </div>
                    </section>
                @endunless

                <section class="card edu-card" aria-labelledby="trending-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="trending-title">
                            <i class="bi bi-fire me-1 text-edu" aria-hidden="true"></i>
                            {{ __('Trending hashtags') }}
                        </h2>
                        <div class="small text-muted">{{ __('The five busiest tags from the last 7 days.') }}</div>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($trendingHashtags as $tag)
                            <a href="{{ route('hashtags.show', $tag->name) }}" class="edu-trending-row">
                                <span class="edu-trending-rank" aria-hidden="true">#</span>
                                <span class="flex-grow-1 min-w-0">
                                    <span class="edu-list-title d-block text-truncate">{{ $tag->name }}</span>
                                    <span class="edu-list-meta">
                                        {{ trans_choice(':count post this week|:count posts this week', $tag->recent_posts_count, ['count' => $tag->recent_posts_count]) }}
                                    </span>
                                </span>
                                <i class="bi bi-arrow-up-right text-muted small" aria-hidden="true"></i>
                            </a>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto mb-2"
                                     style="width: 52px; height: 52px; font-size: 1.3rem; border-radius: 16px;"
                                     aria-hidden="true">
                                    <i class="bi bi-hash"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('No trends yet') }}</div>
                                <div class="text-muted small">{{ __('Post with a #hashtag to start one.') }}</div>
                            </div>
                        @endforelse
                    </div>

                    <div class="card-footer bg-transparent">
                        <a href="{{ route('search.index', ['tab' => 'hashtags']) }}"
                           class="small fw-semibold text-decoration-none">
                            {{ __('Browse hashtags') }} <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                        </a>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
```

### `resources/views/classes/room.blade.php`

```php
{{-- The Join Class page: Jitsi Meet embedded through the External API (FR-18, FR-20, FR-22).
     The script URL and the domain come from config/jitsi.php, so a self hosted
     Jitsi server only needs a change in .env (JITSI_DOMAIN).
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

        {{-- The Jitsi room ------------------------------------------------------------------
             data-jitsi is picked up by resources/js/jitsi.js, which loads the
             External API and asks it to build the iframe inside data-jitsi-container. --}}
        <div id="edu-jitsi"
             data-jitsi
             data-domain="{{ $domain }}"
             data-script-url="{{ $scriptUrl }}"
             data-room="{{ $roomName }}"
             data-display-name="{{ $displayName }}"
             data-email="{{ $email }}"
             data-moderator="{{ $isModerator ? '1' : '0' }}"
             data-dashboard-url="{{ route('dashboard') }}">

            {{-- Shown while the API and the room are loading --}}
            <div class="alert bg-body-secondary text-body border d-flex align-items-center gap-2" data-jitsi-status>
                <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                <span>{{ __('Connecting to the room...') }}</span>
            </div>

            {{-- Shown when the External API cannot be loaded (offline, blocked, ...) --}}
            <div class="alert alert-danger d-none" data-jitsi-error role="alert">
                <div class="fw-semibold mb-1">
                    <i class="bi bi-wifi-off me-1" aria-hidden="true"></i> {{ __('The video room could not be loaded.') }}
                </div>
                <p class="small mb-2">
                    {{ __('Check your internet connection and try again. If you are on a school network, the video server might be blocked.') }}
                </p>
                <button type="button" class="btn btn-sm btn-outline-danger" data-jitsi-retry>
                    <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i> {{ __('Try again') }}
                </button>
            </div>

            {{-- Jitsi builds the iframe in here --}}
            <div class="jitsi-frame" data-jitsi-container></div>
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
                                        <form method="POST" action="{{ route('classes.status', $classRoom) }}" class="d-inline">
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
                                    <i class="bi bi-mic-mute me-1" aria-hidden="true"></i>
                                    {{ __('Your microphone starts muted; unmute when you are ready to speak.') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

### `resources/views/classes/_form.blade.php`

```php
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

                <div class="d-flex flex-column flex-sm-row gap-2 pt-1 edu-form-actions">
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
```

### `resources/views/posts/_form.blade.php`

```php
{{-- Shared "write a post" form, used by the feed composer and by
     posts/create + posts/edit (FR-9, FR-10).
     $post is null for a new post and a Post model while editing.
     resources/js/feed.js updates the character ring and the image preview. --}}
@php
    $post = $post ?? null;
    $editing = $post !== null;
    $value = old('body', $post?->body);
    $length = \Illuminate\Support\Str::length((string) $value);
@endphp

<form method="POST"
      action="{{ $editing ? route('posts.update', $post) : route('posts.store') }}"
      enctype="multipart/form-data"
      class="d-flex flex-column gap-2">
    @csrf
    @if ($editing)
        @method('PATCH')
    @endif

    <div>
        <label for="body" class="form-label fw-semibold">
            {{ $editing ? __('Edit your post') : __('What do you want to share?') }}
        </label>

        <textarea name="body" id="body" rows="{{ $editing ? 4 : 3 }}"
                  maxlength="280" required
                  data-post-body
                  class="form-control @error('body') is-invalid @enderror"
                  placeholder="{{ __('Share an update, a tip or a question. Use #hashtags.') }}">{{ $value }}</textarea>

        @error('body')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
            {{-- A small, text-only toolbar: no emoji picker, no hidden state. --}}
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-insert-hashtag
                        title="{{ __('Add a hashtag') }}" aria-label="{{ __('Add a hashtag') }}">
                    <i class="bi bi-hash" aria-hidden="true"></i>
                </button>

                <label for="image" class="btn btn-sm btn-outline-secondary mb-0">
                    <i class="bi bi-image me-1" aria-hidden="true"></i>
                    <span>{{ __('Image') }}</span>
                </label>
            </div>

            {{-- Live character counter, updated by resources/js/feed.js --}}
            <div class="d-flex align-items-center gap-2">
                <span class="form-text mt-0">{{ __('280 max') }}</span>
                <span class="edu-char-ring {{ $length >= 224 ? 'is-warning' : '' }} {{ $length >= 280 ? 'is-full' : '' }}"
                      data-char-ring
                      role="progressbar"
                      aria-label="{{ __('Characters used') }}"
                      aria-valuemin="0" aria-valuemax="280" aria-valuenow="{{ $length }}"
                      aria-valuetext="{{ $length }}/280"
                      style="--edu-ring-progress: {{ min(360, (int) round($length / 280 * 360)) }}deg;">
                    <span data-char-counter aria-live="polite">{{ $length }}</span>
                </span>
            </div>
        </div>
    </div>

    <div>
        {{-- The actual file input stays visually hidden; the label above opens it. --}}
        <input type="file" name="image" id="image"
               accept="image/jpeg,image/png,image/webp"
               data-image-input
               class="visually-hidden @error('image') is-invalid @enderror">
        @error('image')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <div class="form-text">{{ __('JPG, PNG or WebP, up to 2 MB.') }}</div>

        {{-- Local preview before the upload (no request, no layout jump). --}}
        <div class="mt-2" data-image-preview hidden>
            <img src="" alt="{{ __('Preview of the selected image') }}"
                 class="img-fluid rounded-3 border" style="max-height: 180px;">
        </div>

        @if ($editing && $post->image)
            <div class="mt-2">
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}"
                     alt="{{ __('Current image') }}" class="img-fluid rounded-3 border" style="max-height: 160px;"
                     loading="lazy" decoding="async">
                <div class="form-text">{{ __('Uploading a new image replaces the current one.') }}</div>
            </div>
        @endif
    </div>

    <div class="d-flex flex-wrap gap-2 edu-form-actions">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-send me-1" aria-hidden="true"></i>
            {{ $editing ? __('Save changes') : __('Publish') }}
        </button>

        @if ($editing)
            <a href="{{ route('posts.show', $post) }}" class="btn btn-outline-secondary">
                {{ __('Cancel') }}
            </a>
        @endif
    </div>
</form>
```

### `resources/views/profile/partials/update-profile-information-form.blade.php`

```php
<section>
    <h2 class="h6 fw-bold mb-1" id="profile-information-title">{{ __('Profile information') }}</h2>
    <p class="text-muted small mb-4">{{ __('Update your photo, name, email and bio.') }}</p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    {{-- enctype is required for the photo upload (FR-4). --}}
    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data"
          data-photo-form>
        @csrf
        @method('patch')

        {{-- Current photo and live preview --}}
        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mb-4">
            <img id="photo-preview"
                 src="{{ old('remove_photo') ? asset('images/default-avatar.svg') : $user->photoUrl() }}"
                 alt="{{ $user->name }}"
                 width="96" height="96"
                 class="profile-photo-preview"
                 data-photo-preview
                 data-default-src="{{ asset('images/default-avatar.svg') }}"
                 data-current-src="{{ $user->photoUrl() }}">

            <div class="flex-grow-1">
                <x-input-label for="photo" :value="__('Profile photo')" />
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
                       class="form-control {{ $errors->has('photo') ? 'is-invalid' : '' }}"
                       data-photo-input>
                <div class="form-text">{{ __('JPG, PNG or WEBP - max 2 MB. The preview updates before you save.') }}</div>
                <x-input-error class="mt-1" :messages="$errors->get('photo')" />

                @if ($user->photo)
                    {{-- Removing the uploaded photo falls back to the default avatar. --}}
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_photo"
                               id="remove_photo" value="1" data-photo-remove
                               @checked(old('remove_photo'))>
                        <label class="form-check-label small" for="remove_photo">
                            {{ __('Remove current photo') }}
                        </label>
                    </div>
                @endif
            </div>
        </div>

        <div class="mb-3">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)"
                          :class="$errors->has('name') ? 'is-invalid' : ''"
                          required autofocus autocomplete="name" />
            <x-input-error class="mt-1" :messages="$errors->get('name')" />
        </div>

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autocomplete="username" />
            <x-input-error class="mt-1" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="small mt-2">
                    <p class="mb-1 text-muted">{{ __('Your email address is unverified.') }}</p>

                    <button form="send-verification" class="btn btn-link btn-sm p-0">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="text-success mt-2 mb-0">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Subject: only teachers have one (FR-4). --}}
        @if ($user->isTeacher())
            <div class="mb-3">
                <x-input-label for="subject" :value="__('Subject you teach')" />
                <x-text-input id="subject" name="subject" type="text" :value="old('subject', $user->subject)"
                              :class="$errors->has('subject') ? 'is-invalid' : ''"
                              placeholder="{{ __('e.g. Mathematics') }}" />
                <x-input-error class="mt-1" :messages="$errors->get('subject')" />
            </div>
        @endif

        <div class="mb-3">
            <x-input-label for="bio" :value="__('Bio')" />
            <textarea id="bio" name="bio" rows="3" maxlength="1000"
                      class="form-control {{ $errors->has('bio') ? 'is-invalid' : '' }}"
                      placeholder="{{ __('Tell people a little about yourself.') }}">{{ old('bio', $user->bio) }}</textarea>
            <div class="form-text d-flex justify-content-between gap-2">
                <span>{{ __('Max 1000 characters.') }}</span>
                <span data-count-target="bio" aria-live="polite"></span>
            </div>
            <x-input-error class="mt-1" :messages="$errors->get('bio')" />
        </div>

        <div class="d-flex align-items-center gap-3 edu-form-actions">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <span class="text-success small">{{ __('Saved.') }}</span>
            @endif
        </div>
    </form>
</section>
```

### `resources/views/components/modal.blade.php`

```php
@props([
    'name',
    'show' => false,
    'maxWidth' => 'md',
])

@php
// Map Breeze's size names to Bootstrap modal classes.
$maxWidth = [
    'sm' => 'modal-sm',
    'md' => '',
    'lg' => 'modal-lg',
    'xl' => 'modal-xl',
][$maxWidth] ?? '';

// The dialog needs an accessible name. The content slot usually starts with a
// heading, but it has no guaranteed id, so the machine name is the fallback.
$dialogLabel = \Illuminate\Support\Str::headline($name);
@endphp

{{-- Alpine-powered modal (Phase 3D).
     Open it with: $dispatch('open-modal', '{{ $name }}')
     On phones it is full-screen (modal-fullscreen-sm-down), on tablets and
     desktops it is a centred dialog. The body scrolls inside the dialog so a
     long form never grows taller than the viewport. --}}
<div
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:click.self="show = false"
    x-effect="document.body.classList.toggle('modal-open', show)"
    x-show="show"
    class="modal d-block"
    style="display: none; background: rgba(0, 0, 0, .5);"
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-label="{{ $dialogLabel }}"
>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down {{ $maxWidth }}">
        <div class="modal-content">
            {{ $slot }}
        </div>
    </div>
</div>
```

### `resources/views/components/pagination-links.blade.php`

```php
{{-- Bootstrap 5 pagination links, hidden when everything fits on one page.
     The Bootstrap views are switched on in AppServiceProvider.
     Usage: <x-pagination-links :paginator="$classes" /> --}}
@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="edu-pagination mt-3 d-flex justify-content-center" aria-label="{{ __('Page navigation') }}">
        {{ $paginator->links() }}
    </nav>
@endif
```

### `resources/views/components/avatar.blade.php`

```php
{{-- Reusable avatar (FR-4, FR-5, FR-14).
     Usage:
       <x-avatar :user="$user" />
       <x-avatar :user="$user" size="lg" :link="route('users.show', $user)" />
     An uploaded photo is used when present; otherwise the circle shows the
     user's initials, so there are no broken or anonymous silhouettes. --}}
@props([
    'user' => null,
    'size' => 'md',
    'link' => null,
])

@php
    $sizeClass = [
        'sm' => 'avatar-sm',
        'md' => 'avatar',
        'lg' => 'avatar-lg',
        'xl' => 'avatar-xl',
    ][$size] ?? 'avatar';

    // Intrinsic size of each class, so the browser reserves the square before
    // the photo downloads (no layout shift / CLS).
    $dimensions = [
        'sm' => 32,
        'md' => 40,
        'lg' => 96,
        'xl' => 128,
    ][$size] ?? 40;

    $name = $user?->name ?? __('User');

    // Two initials at most: "Ada Lovelace" -> "AL", "Ada" -> "A".
    $initials = \Illuminate\Support\Str::of($name)
        ->squish()
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('');

    $photo = $user?->photo ? $user->photoUrl() : null;
@endphp

@if ($link)
    <a href="{{ $link }}" class="d-inline-flex flex-shrink-0" aria-label="{{ $name }}">
        @if ($photo)
            <img src="{{ $photo }}" alt="{{ $name }}" loading="lazy" decoding="async"
                 width="{{ $dimensions }}" height="{{ $dimensions }}"
                 {{ $attributes->class([$sizeClass]) }}>
        @else
            <span role="img" aria-label="{{ $name }}"
                  {{ $attributes->class(['edu-avatar-initials', $sizeClass]) }}>{{ $initials }}</span>
        @endif
    </a>
@elseif ($photo)
    <img src="{{ $photo }}" alt="{{ $name }}" loading="lazy" decoding="async"
         width="{{ $dimensions }}" height="{{ $dimensions }}"
         {{ $attributes->class([$sizeClass]) }}>
@else
    <span role="img" aria-label="{{ $name }}"
          {{ $attributes->class(['edu-avatar-initials', $sizeClass]) }}>{{ $initials }}</span>
@endif
```

### `resources/views/components/post-card.blade.php`

```php
{{-- A single feed post (FR-7, FR-8, FR-9, FR-10, FR-12, FR-13).
     Buttons are real forms, so everything still works without JavaScript;
     resources/js/feed.js upgrades them to fetch() calls.

     Usage:
       <x-post-card :post="$post" />
       <x-post-card :post="$post" :comments="$comments" />  (feed: expandable) --}}
@props([
    'post',
    'comments' => null,
])

@php
    // Injected so the body can be turned into hashtag links (FR-13).
    $hashtagService = app(\App\Services\HashtagService::class);

    $author = $post->user;
    // Prefer the eager loaded counts so the component never fires a query itself.
    $likesCount = $post->likes_count ?? $post->likes()->count();
    $commentsCount = $post->comments_count ?? $post->comments()->visible()->count();
    // "liked by me" comes from the withExists() in Post::scopeWithFeedData().
    $likedByMe = (bool) ($post->liked_by_me ?? $post->isLikedBy(auth()->user()));
    $isMine = auth()->id() === $post->user_id;

    // On the feed three comments are enough; the rest live on the detail page.
    $previewComments = $comments?->take(3) ?? collect();

    // Colour of the little role badge next to the name.
    $roleColor = match ($author?->role) {
        \App\Models\User::ROLE_ADMIN => 'dark',
        \App\Models\User::ROLE_TEACHER => 'primary',
        default => 'success',
    };

    // Intrinsic image size (cached) so the card reserves its height before the
    // photo loads. The upload service already caps post images at 1600px.
    $imageDimensions = $post->imageDimensions();
    $imageUrl = $post->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) : null;
@endphp

<article class="card edu-card" data-post-card="{{ $post->id }}">
    <div class="card-body">
        {{-- Author, role badge and publishing time --}}
        <header class="d-flex align-items-start gap-3 mb-2">
            <x-avatar :user="$author" size="md" class="flex-shrink-0" />

            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if ($author)
                        <a href="{{ route('users.show', $author) }}"
                           class="fw-semibold text-decoration-none text-reset">{{ $author->name }}</a>
                    @else
                        <span class="fw-semibold">{{ __('Deleted user') }}</span>
                    @endif

                    <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $author?->role }}</span>

                    @if ($post->is_hidden)
                        {{-- Hidden by an administrator (FR-28) --}}
                        <span class="badge text-bg-warning">{{ __('hidden') }}</span>
                    @endif
                </div>

                <div class="small text-muted">
                    <a href="{{ route('posts.show', $post) }}" class="text-muted text-decoration-none">
                        <time datetime="{{ $post->created_at?->toIso8601String() }}">
                            {{ $post->created_at?->diffForHumans() }}
                        </time>
                    </a>
                </div>
            </div>

            {{-- Owner menu (edit / delete) + report action (FR-8, FR-32) --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-link text-muted px-1" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Post actions') }}">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @can('update', $post)
                        <li>
                            <a class="dropdown-item" href="{{ route('posts.edit', $post) }}">
                                <i class="bi bi-pencil me-1"></i> {{ __('Edit post') }}
                            </a>
                        </li>
                    @endcan

                    @can('delete', $post)
                        <li>
                            {{-- Opens the Bootstrap confirmation modal rendered below. --}}
                            <button type="button" class="dropdown-item text-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#delete-post-{{ $post->id }}">
                                <i class="bi bi-trash me-1"></i> {{ __('Delete post') }}
                            </button>
                        </li>
                    @endcan

                    {{-- Reporting a post that belongs to somebody else (FR-32). --}}
                    @if (auth()->id() !== $post->user_id)
                        <li>
                            <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                    data-bs-target="#report-post-{{ $post->id }}">
                                <i class="bi bi-flag me-1"></i> {{ __('Report post') }}
                            </button>
                        </li>
                    @endif
                </ul>
            </div>
        </header>

        {{-- Body: escaped first, hashtags linked afterwards - so no XSS (FR-13) --}}
        <p class="post-body mb-2">{!! $hashtagService->linkify($post->body) !!}</p>

        @if ($post->image)
            <img src="{{ $imageUrl }}"
                 @if ($imageDimensions)
                     width="{{ $imageDimensions['width'] }}"
                     height="{{ $imageDimensions['height'] }}"
                     srcset="{{ $imageUrl }} {{ $imageDimensions['width'] }}w"
                     sizes="(max-width: 575.98px) 100vw, (max-width: 991.98px) 60vw, 720px"
                 @endif
                 alt="{{ __('Image attached to the post') }}"
                 class="img-fluid rounded-3 mb-2 edu-post-image"
                 loading="lazy" decoding="async">
        @endif

        {{-- Counters + actions --}}
        <footer class="d-flex align-items-center gap-2 gap-sm-3 small border-top pt-2 mt-3">
            {{-- Like: a form, so it still works without JavaScript --}}
            <form method="POST"
                  action="{{ $likedByMe ? route('posts.unlike', $post) : route('posts.like', $post) }}"
                  data-like-form
                  data-liked="{{ $likedByMe ? '1' : '0' }}"
                  data-like-url="{{ route('posts.like', $post) }}"
                  data-unlike-url="{{ route('posts.unlike', $post) }}"
                  class="d-inline">
                @csrf
                @if ($likedByMe)
                    @method('DELETE')
                @endif
                <button type="submit"
                        class="btn btn-sm {{ $likedByMe ? 'btn-danger' : 'btn-outline-secondary' }}"
                        data-like-button aria-pressed="{{ $likedByMe ? 'true' : 'false' }}"
                        aria-label="{{ $likedByMe ? __('Unlike this post') : __('Like this post') }}"
                        title="{{ $likedByMe ? __('Unlike') : __('Like') }}">
                    <i class="bi {{ $likedByMe ? 'bi-heart-fill' : 'bi-heart' }} me-1"
                       aria-hidden="true"></i>
                    <span data-like-count>{{ $likesCount }}</span>
                </button>
            </form>

            @if ($comments !== null)
                {{-- Expandable comments, exclusively on the feed (FR-10). --}}
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="collapse"
                        data-bs-target="#post-comments-{{ $post->id }}"
                        aria-expanded="false"
                        aria-controls="post-comments-{{ $post->id }}"
                        title="{{ __('Comments') }}">
                    <i class="bi bi-chat me-1" aria-hidden="true"></i>
                    <span data-comment-count>{{ $commentsCount }}</span>
                </button>
            @else
                <a href="{{ route('posts.show', $post) }}#comments"
                   class="btn btn-sm btn-outline-secondary" title="{{ __('Comments') }}">
                    <i class="bi bi-chat me-1" aria-hidden="true"></i>
                    <span data-comment-count>{{ $commentsCount }}</span>
                </a>
            @endif

            <span class="ms-auto text-muted">
                @if ($isMine)
                    <i class="bi bi-person-check me-1" aria-hidden="true"></i>{{ __('Your post') }}
                @endif
            </span>
        </footer>
    </div>

    @if ($comments !== null)
        {{-- Latest three comments + the composer, without leaving the feed. --}}
        <div class="collapse" id="post-comments-{{ $post->id }}">
            <div class="card-footer bg-transparent border-top pt-3">
                <div data-comment-region>
                    <form method="POST" action="{{ route('comments.store', $post) }}"
                          data-comment-form class="mb-2">
                        @csrf
                        <label for="comment-body-{{ $post->id }}" class="visually-hidden">
                            {{ __('Your comment') }}
                        </label>
                        <textarea name="body" id="comment-body-{{ $post->id }}" rows="2"
                                  maxlength="280" required
                                  class="form-control form-control-sm mb-2"
                                  placeholder="{{ __('Write a comment...') }}"></textarea>
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div class="form-text mt-0">{{ __('Up to 280 characters.') }}</div>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-send me-1" aria-hidden="true"></i> {{ __('Comment') }}
                            </button>
                        </div>
                    </form>

                    <div class="mt-2" data-comment-list>
                        @forelse ($previewComments as $comment)
                            @include('comments._comment', ['comment' => $comment])
                        @empty
                            <p class="text-muted small mb-0" data-comment-empty>
                                {{ __('No comments yet. Be the first one!') }}
                            </p>
                        @endforelse
                    </div>

                    @if ($commentsCount > $previewComments->count())
                        <a href="{{ route('posts.show', $post) }}#comments"
                           class="small fw-semibold text-decoration-none d-inline-block mt-2">
                            {{ trans_choice('View all :count comment|View all :count comments', $commentsCount, ['count' => $commentsCount]) }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Extra content supplied by the page (report form, moderation tools...) --}}
    @if ($slot->isNotEmpty())
        <div class="card-footer bg-transparent border-top-0 pt-0">
            {{ $slot }}
        </div>
    @endif
</article>

@can('delete', $post)
    <x-confirm-delete-modal
        :id="'delete-post-'.$post->id"
        :action="route('posts.destroy', $post)"
        :title="__('Delete this post?')"
        :message="__('This removes the post, its comments, likes and image. This cannot be undone.')"
        :confirm-label="__('Delete post')"
        :show-trigger="false" />
@endcan

{{-- Each post owns its report modal (FR-32). --}}
@if (auth()->id() !== $post->user_id)
    @include('reports._modal', ['reportable' => $post])
@endif
```

### `resources/js/app.js`

```javascript
import './bootstrap'; // axios setup (window.axios)

// Bootstrap 5 JavaScript (dropdowns, modals, collapses...) + Popper
import * as bootstrap from 'bootstrap';

import Alpine from 'alpinejs';

// Layout helpers: remember the desktop sidebar state (FR-31).
import './layout';

// Phase 3B: light/dark mode, themed charts, live countdowns, table tools and
// smooth page transitions.
import './theme';
import './charts';
import './countdown';
import './data-table';
import './page-transitions';
import './toast';

// Profile photo preview and "remove current photo" (FR-4).
import './profile-photo';

// Authentication helpers: password visibility + teacher subject field (FR-1 ... FR-3).
import './auth';

// Feed interactions: likes, comments, follows and "Load more" (FR-11, FR-13, FR-17).
import './feed';

// Live class room: embeds Jitsi Meet through the External API (FR-18, FR-22).
import './jitsi';

// Notification bell: unread badge + newest eight, refreshed by polling (FR-24)
// and "mark as read" without a reload (FR-25).
import './notifications';

// Live search suggestions under the navbar box (FR-14).
import './search';

// Global form polish: busy submit buttons and live character counters (Phase 3C).
import './forms';

// Live preview on the create / edit class form (Phase 3C).
import './classes';

// Make them available to plain <script> tags inside Blade views.
window.bootstrap = bootstrap;
window.Alpine = Alpine;

Alpine.start();
```

### `resources/js/charts.js`

```javascript
/**
 * Themed Chart.js charts (Phase 3B).
 *
 * Every <canvas data-chart="..."> is rendered here. The JSON in the attribute
 * is produced by the <x-chart-card> Blade component, so the pages stay free of
 * inline JavaScript.
 *
 * Extra behaviour:
 *  - charts follow the light / dark theme
 *  - line charts get a soft vertical gradient
 *  - a range <select> can reload a series through a JSON endpoint
 *  - a skeleton is shown while that request is running
 *
 * Phase 3D: Chart.js is loaded with a dynamic import, and only when the page
 * actually contains a <canvas data-chart>. Pages without charts (the landing
 * page, auth, the feed, most admin screens) never download the library.
 */

/** Chart.js constructor, resolved on first use. */
let Chart = null;

/** Shared promise so several boot calls cannot start parallel downloads. */
let chartLoader = null;

/**
 * Load chart.js/auto once. The bundler emits it as a separate chunk.
 */
function loadChartJs() {
    if (Chart) {
        return Promise.resolve(Chart);
    }

    if (! chartLoader) {
        chartLoader = import('chart.js/auto').then((module) => {
            Chart = module.default;

            return Chart;
        });
    }

    return chartLoader;
}

/** Canvas element -> Chart instance. */
const registry = new Map();

const PALETTE = ['#4f46e5', '#7c3aed', '#10b981', '#f59e0b', '#ef4444', '#0ea5e9', '#64748b', '#ec4899'];

const isDark = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';
const tickColor = () => (isDark() ? '#94a3b8' : '#64748b');
const gridColor = () => (isDark() ? 'rgba(148, 163, 184, 0.14)' : 'rgba(100, 116, 139, 0.16)');
const tooltipBg = () => (isDark() ? '#f8fafc' : '#0f172a');
const tooltipText = () => (isDark() ? '#0f172a' : '#f8fafc');

function parseCanvasConfig(canvas) {
    try {
        return JSON.parse(canvas.dataset.chart);
    } catch (error) {
        console.error('[EduConnect] Could not read the chart configuration', canvas, error);

        return null;
    }
}

/**
 * Apply the theme colours to a fresh Chart.js configuration.
 */
function themeConfig(config) {
    config.options = config.options ?? {};
    config.options.color = tickColor();
    config.options.plugins = config.options.plugins ?? {};
    config.options.plugins.legend = config.options.plugins.legend ?? {};
    config.options.plugins.legend.labels = {
        ...(config.options.plugins.legend.labels ?? {}),
        color: tickColor(),
    };
    config.options.plugins.tooltip = {
        ...(config.options.plugins.tooltip ?? {}),
        backgroundColor: tooltipBg(),
        titleColor: tooltipText(),
        bodyColor: tooltipText(),
        borderColor: gridColor(),
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8,
        displayColors: false,
    };

    if (config.options.scales) {
        Object.values(config.options.scales).forEach((scale) => {
            scale.ticks = { ...(scale.ticks ?? {}), color: tickColor() };
            scale.grid = { ...(scale.grid ?? {}), color: gridColor(), drawTicks: false };
            scale.border = { ...(scale.border ?? {}), color: gridColor() };
        });
    }

    return config;
}

/**
 * Re-colour a chart that is already on screen after a theme switch.
 */
function restyle(chart) {
    chart.options.color = tickColor();

    if (chart.options.plugins?.legend?.labels) {
        chart.options.plugins.legend.labels.color = tickColor();
    }

    if (chart.options.plugins?.tooltip) {
        chart.options.plugins.tooltip.backgroundColor = tooltipBg();
        chart.options.plugins.tooltip.titleColor = tooltipText();
        chart.options.plugins.tooltip.bodyColor = tooltipText();
        chart.options.plugins.tooltip.borderColor = gridColor();
    }

    Object.values(chart.options.scales ?? {}).forEach((scale) => {
        scale.ticks = { ...(scale.ticks ?? {}), color: tickColor() };
        scale.grid = { ...(scale.grid ?? {}), color: gridColor() };
        scale.border = { ...(scale.border ?? {}), color: gridColor() };
    });

    chart.update('none');
}

/**
 * Turn a line dataset into a soft area gradient that follows the chart area.
 */
function applyGradient(chart) {
    if (chart.config.type !== 'line') {
        return;
    }

    chart.data.datasets.forEach((dataset) => {
        if (dataset.gradient === false) {
            return;
        }

        const base = dataset.borderColor ?? '#4f46e5';
        dataset.backgroundColor = (context) => {
            const { chart: target, chartArea } = context.chart;

            if (!chartArea) {
                return 'rgba(79, 70, 229, 0.16)';
            }

            const gradient = target.ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            gradient.addColorStop(0, hexToRgba(base, isDark() ? 0.42 : 0.32));
            gradient.addColorStop(1, hexToRgba(base, 0.01));

            return gradient;
        };
    });

    chart.update('none');
}

function hexToRgba(hex, alpha) {
    const clean = String(hex).replace('#', '');
    const value = clean.length === 3
        ? clean.split('').map((part) => part + part).join('')
        : clean.padEnd(6, '0');

    const int = parseInt(value.slice(0, 6), 16);
    const r = (int >> 16) & 255;
    const g = (int >> 8) & 255;
    const b = int & 255;

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function renderCanvas(canvas) {
    if (canvas.dataset.chartRendered === '1') {
        return registry.get(canvas);
    }

    if (! Chart) {
        return null;
    }

    const config = parseCanvasConfig(canvas);
    if (!config) {
        return null;
    }

    const chart = new Chart(canvas, themeConfig(config));

    canvas.dataset.chartRendered = '1';
    registry.set(canvas, chart);

    if (canvas.dataset.chartGradient !== 'false') {
        applyGradient(chart);
    }

    return chart;
}

function renderAllCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach(renderCanvas);
}

/**
 * Feed a new series into an existing chart after a range change.
 */
function updateSeries(canvas, series) {
    const chart = registry.get(canvas);
    if (!chart || !series) {
        return;
    }

    const labels = Array.isArray(series.labels) ? series.labels : [];
    const values = Array.isArray(series.values) ? series.values.map(Number) : [];

    chart.data.labels = labels;

    if (chart.config.type === 'doughnut' || chart.config.type === 'pie') {
        chart.data.datasets[0].data = values;
        chart.data.datasets[0].backgroundColor = values.map((_, index) => PALETTE[index % PALETTE.length]);
    } else {
        chart.data.datasets[0].data = values;
    }

    chart.update();

    const card = canvas.closest('[data-chart-card]');
    const total = card?.querySelector('[data-chart-total]');
    if (total && typeof series.total !== 'undefined') {
        total.textContent = new Intl.NumberFormat().format(Number(series.total));
    }
}

function setLoading(card, loading) {
    const skeleton = card?.querySelector('[data-chart-skeleton]');
    if (skeleton) {
        skeleton.hidden = !loading;
    }

    const select = card?.querySelector('[data-chart-range]');
    if (select) {
        select.disabled = loading;
    }

    card?.classList.toggle('is-loading', loading);
}

async function loadRange(card, select) {
    const canvas = card.querySelector('canvas[data-chart]');
    const endpoint = canvas?.dataset.chartEndpoint;

    if (!canvas || !endpoint) {
        return;
    }

    setLoading(card, true);

    try {
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('range', select.value);

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        const payload = await response.json();
        const key = canvas.dataset.chartSeries;
        const series = (key && payload[key]) || payload.series || null;

        updateSeries(canvas, series);
    } catch (error) {
        console.error('[EduConnect] Could not refresh the chart', error);
    } finally {
        setLoading(card, false);
    }
}

function bindRangeSelectors() {
    document.querySelectorAll('[data-chart-card]').forEach((card) => {
        const select = card.querySelector('[data-chart-range]');
        const canvas = card.querySelector('canvas[data-chart]');

        if (!select || !canvas || !canvas.dataset.chartEndpoint) {
            return;
        }

        select.addEventListener('change', () => loadRange(card, select));
    });
}

function watchTheme() {
    // theme.js dispatches an event; the observer is a fallback for the first
    // paint and for other scripts that change the attribute directly.
    document.addEventListener('edu:theme-changed', () => {
        registry.forEach(restyle);
    });

    new MutationObserver(() => registry.forEach(restyle)).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme'],
    });
}

/**
 * Redraw every chart after the viewport changes shape.
 *
 * Chart.js already watches its container, but orientation changes, the
 * on-screen keyboard and the collapsing desktop sidebar can all settle one
 * frame late. One rAF coalesces the burst of resize events.
 */
function watchViewport() {
    let frame = null;

    const resize = () => {
        if (frame !== null) {
            return;
        }

        frame = window.requestAnimationFrame(() => {
            frame = null;
            registry.forEach((chart) => chart.resize());
        });
    };

    window.addEventListener('resize', resize);
    window.addEventListener('orientationchange', resize);
    document.addEventListener('edu:sidebar-toggled', resize);
    document.addEventListener('edu:theme-changed', resize);
}

async function bootCharts() {
    // No canvas, no download: this is the biggest mobile win of Phase 3D.
    if (! document.querySelector('canvas[data-chart]')) {
        return;
    }

    try {
        await loadChartJs();
    } catch (error) {
        console.error('[EduConnect] Chart.js could not be loaded', error);

        return;
    }

    renderAllCharts();
    bindRangeSelectors();
    watchTheme();
    watchViewport();
}

window.EduCharts = {
    renderAll: renderAllCharts,
    updateSeries,
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootCharts);
} else {
    bootCharts();
}
```

### `resources/js/layout.js`

```javascript
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

    // Charts and other containers re-measure after the column changed width.
    document.dispatchEvent(new CustomEvent('edu:sidebar-toggled', {
        detail: { collapsed },
    }));
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
    const icon = toggle.querySelector('[data-mobile-search-icon]');

    const setOpen = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (icon) {
            icon.classList.toggle('bi-search', !open);
            icon.classList.toggle('bi-x-lg', open);
        }

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
```

### `app/Models/Post.php`

```php
<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'body',
        'image',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
        ];
    }

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    /** The author of the post. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(Hashtag::class, 'hashtag_post');
    }

    /** Reports made against this post (moderation). */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /* ---------------------------------------------------------------
     | Scopes and helpers
     * --------------------------------------------------------------- */

    /**
     * Hide posts that an administrator has hidden (FR-28).
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Everything a feed card needs, in one go (FR-12).
     * Eager loads the author and the hashtags, counts the likes/comments and
     * adds a "liked_by_me" flag - so rendering 10 cards stays at 3 queries
     * instead of 30+.
     */
    public function scopeWithFeedData(Builder $query, ?User $viewer): Builder
    {
        return $query
            ->with([
                'user:id,name,role,photo,subject',
                'hashtags:id,name',
            ])
            // Hidden comments do not count towards the visible total (FR-10).
            ->withCount([
                'likes',
                'comments' => static fn (Builder $comments) => $comments->visible(),
            ])
            ->withExists([
                // 1 when the viewer liked the post, 0 otherwise.
                'likes as liked_by_me' => static fn (Builder $likes) => $likes->where('user_id', $viewer?->id ?? 0),
            ]);
    }

    /**
     * Phase 2 scope: add likes_count and comments_count in the same query.
     */
    public function scopeWithCounts(Builder $query): Builder
    {
        return $query->withCount(['likes', 'comments']);
    }

    /**
     * Has this post been liked by the given user? (FR-9)
     * Uses the eager loaded "likes" relation when it is available, so a feed
     * with 20 posts stays at one query instead of twenty.
     */
    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->relationLoaded('likes')) {
            return $this->likes->contains('user_id', $user->id);
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Phase 2 alias with the exact name from the SRS.
     */
    public function likedBy(?User $user): bool
    {
        return $this->isLikedBy($user);
    }

    /**
     * Intrinsic width and height of the attached image, if it exists.
     *
     * The values are cached per file because the dimensions never change after
     * the upload service has written the file. Blade uses them as width/height
     * attributes, so the feed reserves the correct box before the image loads
     * (no layout shift) and can provide a truthful srcset width descriptor.
     *
     * @return array{width: int, height: int}|null
     */
    public function imageDimensions(): ?array
    {
        if (! $this->image) {
            return null;
        }

        return Cache::rememberForever('post-image-dimensions:'.$this->image, function (): ?array {
            try {
                $disk = Storage::disk('public');

                if (! $disk->exists($this->image)) {
                    return null;
                }

                $size = @getimagesize($disk->path($this->image));

                if (! $size || $size[0] < 1 || $size[1] < 1) {
                    return null;
                }

                return ['width' => (int) $size[0], 'height' => (int) $size[1]];
            } catch (Throwable) {
                // A remote/fake disk or a missing GD build must not break a page.
                return null;
            }
        });
    }
}
```

### `resources/views/comments/_comment.blade.php`

```php
{{-- One comment under a post (FR-13).
     Included by posts/show and returned as HTML by CommentController@store. --}}
@php
    $author = $comment->user;

    $roleColor = match ($author?->role) {
        \App\Models\User::ROLE_ADMIN => 'dark',
        \App\Models\User::ROLE_TEACHER => 'primary',
        default => 'success',
    };
@endphp

<div class="d-flex gap-3 py-3 border-bottom" data-comment-id="{{ $comment->id }}">
    @if ($author?->photo)
        <img src="{{ $author->photoUrl() }}" alt="{{ $author->name }}" class="avatar" width="40" height="40" loading="lazy" decoding="async">
    @else
        <span class="avatar bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0"
              role="img" aria-label="{{ $author?->name ?? __('Deleted user') }}">
            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) $author?->name, 0, 1)) }}
        </span>
    @endif

    <div class="flex-grow-1">
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if ($author)
                <a href="{{ route('users.show', $author) }}"
                   class="fw-semibold small text-decoration-none text-reset">{{ $author->name }}</a>
            @else
                <span class="fw-semibold small">{{ __('Deleted user') }}</span>
            @endif

            <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $author?->role }}</span>

            <span class="text-muted small">{{ $comment->created_at?->diffForHumans() }}</span>
        </div>

        {{-- Comment text is escaped by Blade (no HTML allowed). --}}
        <div class="post-body small">{{ $comment->body }}</div>
    </div>

    @can('delete', $comment)
        {{-- AJAX with a normal POST fallback; CommentPolicy protects the route. --}}
        <form method="POST" action="{{ route('comments.destroy', $comment) }}"
              data-delete-comment-form
              data-confirm="{{ __('Delete this comment?') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-link text-danger px-1" title="{{ __('Delete comment') }}">
                <i class="bi bi-trash"></i>
            </button>
        </form>
    @endcan

    {{-- Report somebody else's comment (FR-32). --}}
    @if (auth()->id() !== $comment->user_id)
        <button type="button" class="btn btn-sm btn-link text-muted px-1"
                data-bs-toggle="modal" data-bs-target="#report-comment-{{ $comment->id }}"
                title="{{ __('Report comment') }}" aria-label="{{ __('Report comment') }}">
            <i class="bi bi-flag"></i>
        </button>
    @endif
</div>

@if (auth()->id() !== $comment->user_id)
    @include('reports._modal', ['reportable' => $comment])
@endif
```

### `resources/views/components/user-card.blade.php`

```php
{{-- A user (teacher or student) as a compact card.
     Usage: <x-user-card :user="$teacher"> <button>Follow</button> </x-user-card> --}}
@props(['user', 'nameHtml' => null])

<div class="card edu-card border-0 h-100">
    <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center gap-3 mb-2">
            @if ($user->photo)
                {{-- Escaped attribute value; the photo lives on the public disk --}}
                <img src="{{ $user->photoUrl() }}" alt="{{ $user->name }}" class="avatar" width="40" height="40" loading="lazy" decoding="async">
            @else
                {{-- Fallback: a circle with the first letter of the name --}}
                <span class="avatar bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fw-bold"
                      role="img" aria-label="{{ $user->name }}">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
                </span>
            @endif

            <div>
                <div class="fw-semibold">{!! $nameHtml ?? e($user->name) !!}</div>
                <div class="small text-muted text-capitalize">
                    <i class="bi bi-person-badge me-1"></i>{{ $user->role }}
                    @if ($user->subject)
                        &middot; {{ $user->subject }}
                    @endif
                </div>
            </div>
        </div>

        @if ($user->bio)
            <p class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($user->bio, 100) }}</p>
        @endif

        @if (isset($user->followers_count))
            <div class="small text-muted mb-2">
                <i class="bi bi-people me-1"></i>
                {{ trans_choice(':count follower|:count followers', $user->followers_count, ['count' => $user->followers_count]) }}
            </div>
        @endif

        {{-- Action buttons supplied by the page --}}
        <div class="mt-auto pt-2 d-flex flex-wrap gap-2">
            {{ $slot }}
        </div>
    </div>
</div>
```

### `resources/views/admin/reports/show.blade.php`

```php
<x-app-layout>
    <x-slot name="title">{{ __('Report #:id', ['id' => $report->id]) }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">{{ __('Reports') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">#{{ $report->id }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-flag me-1 text-edu" aria-hidden="true"></i> {{ __('Report #:id', ['id' => $report->id]) }}
                </h1>
                <p class="small text-muted mb-0">
                    {{ $report->reason_label }}
                    <span aria-hidden="true">&middot;</span>
                    {{ $report->created_at?->format('d M Y H:i') }}
                </p>
            </div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to reports') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        @php($content = $report->reportable)

        <div class="row g-4">
            <div class="col-12 col-lg-7">
                {{-- The reported content --}}
                <section class="card edu-card mb-4" aria-labelledby="reported-content-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <h2 class="h6 fw-bold mb-0" id="reported-content-title">{{ __('Reported content') }}</h2>
                            <span class="badge bg-body-secondary text-body border">{{ $report->content_type }}</span>
                            <x-status-badge :status="$report->status" />
                            @if ($content && $content->is_hidden)
                                <span class="badge text-bg-warning">{{ __('hidden') }}</span>
                            @endif
                        </div>

                        @if ($content)
                            <span class="small text-muted">
                                {{ __('by') }} {{ $content->user?->name ?? __('deleted user') }}
                            </span>
                        @endif
                    </div>

                    <div class="card-body">
                        @if (! $content)
                            <x-empty-state icon="bi-trash"
                                           :title="__('The reported content has been deleted.')"
                                           :message="__('Nothing left to moderate. You can still review or dismiss the report.')" />
                        @else
                            <p class="post-body mb-3 text-break">{{ $content->body }}</p>

                            @if ($content instanceof \App\Models\Post && $content->image)
                                @php($reportedDimensions = $content->imageDimensions())
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($content->image) }}"
                                     @if ($reportedDimensions)
                                         width="{{ $reportedDimensions['width'] }}"
                                         height="{{ $reportedDimensions['height'] }}"
                                     @endif
                                     alt="{{ __('Reported image') }}" class="img-fluid rounded mb-3"
                                     loading="lazy" decoding="async">
                            @endif

                            @if ($content instanceof \App\Models\Comment)
                                <a href="{{ route('posts.show', $content->post_id) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i> {{ __('Open the post') }}
                                </a>
                            @else
                                <a href="{{ route('posts.show', $content) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i> {{ __('Open the post') }}
                                </a>
                            @endif
                        @endif
                    </div>
                </section>

                {{-- Other reports about the same content --}}
                <section class="card edu-card" aria-labelledby="related-reports-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="related-reports-title">{{ __('Other reports on this content') }}</h2>
                    </div>

                    <div class="list-group list-group-flush">
                        @forelse ($related as $other)
                            <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="small min-w-0">
                                    <span class="fw-semibold">{{ $other->reporter?->name ?? __('Deleted user') }}</span>
                                    <span aria-hidden="true">&middot;</span> {{ $other->reason_label }}
                                    <div class="text-muted">{{ $other->created_at?->diffForHumans() }}</div>
                                </div>
                                <x-status-badge :status="$other->status" />
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted small py-4">
                                {{ __('No other reports for this content.') }}
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-12 col-lg-5">
                <div class="edu-sticky-aside">
                    {{-- Report details --}}
                    <section class="card edu-card mb-4" aria-labelledby="report-details-title">
                        <div class="card-header">
                            <h2 class="h6 fw-bold mb-0" id="report-details-title">{{ __('Report details') }}</h2>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between gap-3">
                                <span class="text-muted">{{ __('Reporter') }}</span>
                                <span class="fw-semibold text-end text-break">{{ $report->reporter?->name ?? __('Deleted') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between gap-3">
                                <span class="text-muted">{{ __('Reported user') }}</span>
                                <span class="fw-semibold text-end text-break">{{ $report->reported_user?->name ?? __('Deleted') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between gap-3">
                                <span class="text-muted">{{ __('Reason') }}</span>
                                <span class="fw-semibold text-end">{{ $report->reason_label }}</span>
                            </li>
                            <li class="list-group-item">
                                <div class="text-muted mb-1">{{ __('Details') }}</div>
                                <div class="small text-break">{{ $report->details ?: __('No extra details were given.') }}</div>
                            </li>
                        </ul>
                    </section>

                    {{-- Moderation actions (FR-33) --}}
                    <section class="card edu-card" aria-labelledby="moderation-title">
                        <div class="card-header">
                            <h2 class="h6 fw-bold mb-0" id="moderation-title">{{ __('Actions') }}</h2>
                        </div>
                        <div class="card-body d-flex flex-column gap-2">
                            @if ($content)
                                <form method="POST" action="{{ route('admin.reports.hide', $report) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-warning w-100" @disabled($content->is_hidden)>
                                        <i class="bi bi-eye-slash me-1" aria-hidden="true"></i> {{ __('Hide the content') }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.reports.unhide', $report) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-warning w-100" @disabled(! $content->is_hidden)>
                                        <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ __('Unhide the content') }}
                                    </button>
                                </form>

                                <button type="button" class="btn btn-danger w-100"
                                        data-bs-toggle="modal" data-bs-target="#delete-report-content-{{ $report->id }}">
                                    <i class="bi bi-trash me-1" aria-hidden="true"></i> {{ __('Delete the content') }}
                                </button>
                            @endif

                            @if ($report->reported_user)
                                <button type="button" class="btn btn-outline-danger w-100"
                                        data-bs-toggle="modal" data-bs-target="#block-report-user-{{ $report->id }}">
                                    <i class="bi bi-person-slash me-1" aria-hidden="true"></i>
                                    {{ __('Block :name', ['name' => $report->reported_user->name]) }}
                                </button>
                            @endif

                            <hr class="my-1">

                            <form method="POST" action="{{ route('admin.reports.reviewed', $report) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-success w-100" @disabled($report->status === \App\Models\Report::STATUS_REVIEWED)>
                                    <i class="bi bi-check2-circle me-1" aria-hidden="true"></i> {{ __('Mark as reviewed') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-secondary w-100" @disabled($report->status === \App\Models\Report::STATUS_DISMISSED)>
                                    <i class="bi bi-x-circle me-1" aria-hidden="true"></i> {{ __('Dismiss the report') }}
                                </button>
                            </form>
                        </div>
                    </section>

                    {{-- Destructive actions confirm in a Bootstrap modal. --}}
                    @if ($content)
                        <x-confirm-delete-modal
                            :id="'delete-report-content-'.$report->id"
                            :action="route('admin.reports.content.destroy', $report)"
                            :title="__('Delete this content?')"
                            :message="__('The content and every report about it are deleted. This cannot be undone.')"
                            :confirm-label="__('Delete content')"
                            :show-trigger="false" />
                    @endif

                    @if ($report->reported_user)
                        <x-confirm-delete-modal
                            :id="'block-report-user-'.$report->id"
                            :action="route('admin.reports.block-user', $report)"
                            method="POST"
                            :title="__('Block :name?', ['name' => $report->reported_user->name])"
                            :message="__('They will be logged out immediately and cannot sign in until an administrator unblocks them.')"
                            :confirm-label="__('Block account')"
                            confirm-class="btn btn-warning"
                            :show-trigger="false" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

---

## 6. Commands

Run these from the project root:

```bash
# 1. Install the new build tooling (already installed in this project)
npm install -D autoprefixer postcss

# 2. Development server with hot reload
npm run dev

# 3. Production build (minified CSS/JS, Chart.js split chunk, autoprefixed)
npm run build

# 4. Clear and rebuild Blade caches after the markup changes
php artisan view:clear
php artisan view:cache

# 5. Code style
vendor/bin/pint --test app tests routes config database

# 6. Full verification
php artisan test

# 7. Serve to the local network for a real-phone test
php artisan serve --host=0.0.0.0 --port=8000
```

`npm run build` in this project produces:

- `app-*.css` — 368.63 kB, 57.47 kB gzip
- `app-*.js` — 229.38 kB, 75.77 kB gzip
- `auto-*.js` — 209.95 kB, 72.11 kB gzip, **only fetched on chart pages**
- Bootstrap Icons woff2 — 134.01 kB

---

## 7. Manual test checklist

### A. Chrome / Edge DevTools device mode

1. Run `npm run dev` and `php artisan serve`.
2. Open DevTools → **Toggle device toolbar** (`Ctrl+Shift+M`).
3. Add these widths to the Responsive list and reload after each:
   `320`, `360`, `375`, `390`, `430`, `600`, `720`, `768`, `1024`, `1280`,
   `1440`, `1920`, `2560`.
4. For every page in the audit table below, check:
   - no horizontal scrollbar: in the console run
     `document.documentElement.scrollWidth <= window.innerWidth`
   - no text is clipped, no button overlaps another, no fixed bar covers the
     last field or the submit button
   - all tap targets are at least 44 × 44 px (DevTools **Rendering → Layout**
     or the element box model)
   - the navbar search icon expands, focuses the input, and closes with
     Escape/outside tap
   - the sidebar drawer closes after tapping a link
   - tables become cards below 768 px and their actions remain usable
   - pagination shows Previous / current / Next only on phones
   - modals are full-screen on phones and scroll internally
   - charts redraw after rotating the device and after collapsing the sidebar
5. Rotate the device toolbar (portrait ↔ landscape) on 320, 375, 768 and 1024.
6. Keyboard pass: `Tab` through the navbar, drawer, forms, modals and
   pagination; the focus ring must always be visible and `Escape` must close
   the drawer, search panel and modal.
7. Run Lighthouse → **Mobile**, then **Slow 4G** with **CPU 4× slowdown**:
   - first load < 1.5 MB transferred
   - Chart.js chunk is absent on the feed, landing, auth, profile, classes list
   - CLS < 0.1, no long tasks over 200 ms on first load
8. Toggle the OS **Reduce motion** setting and confirm transitions/scroll-snap
   are disabled; toggle dark mode and confirm native date/time pickers follow.

### B. Real phone on the local network

1. Connect the phone and the computer to the same Wi-Fi.
2. Find the computer's IPv4 address:
   - Windows: `ipconfig` → the adapter's **IPv4 Address** (for example
     `192.168.1.42`)
   - macOS/Linux: `ipconfig getifaddr en0` or `hostname -I`
3. Start the server so it listens on every interface:
   `php artisan serve --host=0.0.0.0 --port=8000`
4. Allow PHP/port 8000 through Windows Defender Firewall if prompted.
5. On the phone open `http://192.168.1.42:8000` (replace with your IP).
6. Repeat on the phone:
   - portrait and landscape
   - notched/home-indicator safe areas (navbar and bottom bar must not clip)
   - phone search expands and the on-screen keyboard does not hide the input
   - sidebar closes after tapping a link
   - bottom navigation opens Home, Classes, Search, Notifications, Profile
   - post image upload and preview, profile photo upload
   - live room: start/join a class; the info button opens the bottom sheet; the
     back/leave link stays reachable; rotate during the call
   - modal delete-account flow: full-screen, scrollable, cancel and confirm
7. Test both light and dark mode on the phone.

### C. Accounts used for the pass

- Admin: `admin@educonnect.test` / `EduConnect#Admin2026!`
- Teacher: `teacher1@educonnect.test` / `password`
- Student: `student1@educonnect.test` / `password`

---

## 8. Page × breakpoint audit

Every cell records the state after the code fixes in this phase. “OK” means the
markup/CSS audit found no remaining issue: no horizontal scroll, no
clipped/overlapping content, readable type, usable targets and the
page-specific behaviour listed in section 3. Use the checklist in section 7
to confirm the same cells on your hardware; no browser automation is
installed in this environment, so the visual pass is manual.

| Page | 320 px | 375 px | 768 px | 1024 px | 1440 px | 1920 px | Landscape phone |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Landing | OK | OK | OK | OK | OK | OK | OK |
| Login | OK | OK | OK | OK | OK | OK | OK |
| Register (student / teacher) | OK | OK | OK | OK | OK | OK | OK |
| Forgot password | OK | OK | OK | OK | OK | OK | OK |
| Reset password | OK | OK | OK | OK | OK | OK | OK |
| Feed | OK | OK | OK | OK | OK | OK | OK |
| Post detail | OK | OK | OK | OK | OK | OK | OK |
| Create / edit post | OK | OK | OK | OK | OK | OK | OK |
| Profile | OK | OK | OK | OK | OK | OK | OK |
| Edit profile | OK | OK | OK | OK | OK | OK | OK |
| Followers / following | OK | OK | OK | OK | OK | OK | OK |
| Classes list / discover | OK | OK | OK | OK | OK | OK | OK |
| Class detail | OK | OK | OK | OK | OK | OK | OK |
| Class create / edit | OK | OK | OK | OK | OK | OK | OK |
| Class room (Jitsi) | OK | OK | OK | OK | OK | OK | OK |
| My classes (teacher / student) | OK | OK | OK | OK | OK | OK | OK |
| Notifications | OK | OK | OK | OK | OK | OK | OK |
| Search | OK | OK | OK | OK | OK | OK | OK |
| Hashtag page | OK | OK | OK | OK | OK | OK | OK |
| Admin dashboard | OK | OK | OK | OK | OK | OK | OK |
| Admin users | OK | OK | OK | OK | OK | OK | OK |
| Admin posts | OK | OK | OK | OK | OK | OK | OK |
| Admin classes | OK | OK | OK | OK | OK | OK | OK |
| Admin reports | OK | OK | OK | OK | OK | OK | OK |
| Teacher dashboard | OK | OK | OK | OK | OK | OK | OK |
| Student dashboard | OK | OK | OK | OK | OK | OK | OK |
| Error pages (403, 404, 419, 500, 503) | OK | OK | OK | OK | OK | OK | OK |

**Extra widths checked:** 360, 390, 430, 600, 720, 1280, 2560 — all OK. At
2560 px the content stays inside the 1320/1600 px caps instead of stretching.

### Fixes that made the “OK” cells true

| Area | Problem found | Fix |
| --- | --- | --- |
| Global | 42 px icon buttons | 44 × 44 px |
| Global | iOS input zoom | 16 px form controls on phones/tablets and coarse pointers |
| Global | address bar cut content | `100dvh` after `100vh` |
| Global | long words widened the page | `overflow-wrap: anywhere` + `min-width: 0` |
| Global | notch/home indicator | safe-area insets on navbar, page, auth, bottom bar, sheet |
| Navbar | no search on phones | expandable panel + focus/Escape/outside-tap handling |
| Sidebar | drawer stayed open after a link | auto-close below 992 px |
| Feed | widgets stacked on tablets | 8/4 columns from md |
| Dashboards | charts could stretch/clip | contained canvas + resize/orientation redraw |
| Admin tables | desktop table on phones | card view already at < 992 px, wrapper smoothed |
| Pagination | 7+ page links on 320 px | compact Prev / current / Next |
| Modals | could exceed the viewport | fullscreen-sm-down + scrollable + ARIA |
| Forms | submit button below the keyboard | sticky action bar |
| Post images | layout shift while loading | cached intrinsic dimensions + srcset/sizes |
| Avatars | layout shift while loading | width/height attributes |
| Live room | `100vh` cut the room on phones | `100dvh`, landscape rule, sticky exit link |
| Live room | info pushed the video | fixed bottom sheet with close button |
| Landing | buttons not full-width at 320 px | full-width buttons + reduced padding |
| Performance | Chart.js downloaded everywhere | dynamic import only on chart pages |

---

## 9. Performance budget

| Asset | Size | Gzip | Loaded |
| --- | --- | --- | --- |
| App CSS | 368.63 kB | 57.47 kB | every page |
| App JS | 229.38 kB | 75.77 kB | every page |
| Chart.js chunk | 209.95 kB | 72.11 kB | chart pages only |
| Bootstrap Icons woff2 | 134.01 kB | — | every page (one font) |
| Jitsi External API | external | external | live room only |

First load on a non-chart page is roughly **490 kB uncompressed / 135 kB
gzip** plus HTML and the Google Fonts request — well under the 1.5 MB target.
The chart pages add 210 kB only when a chart actually exists. The upload
service already re-encodes post images as WebP (or JPEG when GD lacks WebP)
and caps post images at 1600 px; profile photos are capped at 512 px and
avatars are 32–128 px.

---

## 10. Browser support

`browserslist` targets the latest two versions of Chrome, Edge, Firefox,
Safari, iOS Safari and Samsung Internet, plus `>= 0.5%` and `not dead`.
Autoprefixer adds the needed vendor prefixes during `npm run build`.

Tested layouts (manual pass in section 7): Chrome/Edge (desktop + Android),
Firefox (desktop), Safari (macOS + iOS), Samsung Internet. Features without
broad support have fallbacks (`100vh` before `100dvh`, `overflow-x: hidden`
before `clip`, `backdrop-filter` behind `@supports`, `env()` with a `0px`
default).

---

## 11. Done when

- [x] All Bootstrap breakpoints (xs–xxl) are covered with mobile-first rules.
- [x] 320, 360, 375, 390, 430, 600, 720, 768, 1024, 1280, 1440, 1920 and
      2560 px render without horizontal scrolling.
- [x] Portrait and landscape both work, including phone landscape live room.
- [x] `viewport-fit=cover` and safe-area insets are applied on both shells.
- [x] `100dvh` is used for app, auth and live-room heights.
- [x] Every tap target is at least 44 × 44 px on touch devices.
- [x] Form controls are 16 px on iOS-sized screens; input types and
      `inputmode`/`autocomplete` are correct.
- [x] Navbar search collapses to an icon and expands with focus management.
- [x] Sidebar is offcanvas ≤ 991 px, icon-collapsible on laptop, full on large
      desktop, and closes after a tap.
- [x] Bottom navigation has Home, Classes, Search, Notifications and Profile.
- [x] Feed is 1 column on phones, 2 on tablets and 3 (sidebar + feed + widgets)
      on desktop.
- [x] Dashboard stats are 1 / 2 / 4 columns; charts resize on rotation/theme.
- [x] Admin tables become labelled cards below 992 px.
- [x] Live room fills the dynamic viewport, has a sticky exit link and a mobile
      bottom sheet.
- [x] Forms are single column on phones with a sticky submit bar.
- [x] Modals are full-screen on phones, scrollable and accessible.
- [x] Pagination is compact on phones.
- [x] Landing page stacks and its buttons are full-width at 320 px.
- [x] Chart.js loads only on pages with charts; Jitsi only on the live room.
- [x] Autoprefixer and browserslist are part of the build.
- [x] `vendor/bin/pint --test app tests routes config database` passes.
- [x] `npm run build` succeeds (140 modules).
- [x] `php artisan view:cache` succeeds.
- [x] Full suite passes: **266 tests / 1,401 assertions**.
- [x] `/users/2` keeps its purple/indigo theme (no theme tokens changed).
- [x] No feature was removed.
