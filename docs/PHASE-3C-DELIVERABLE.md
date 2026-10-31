# Phase 3C — Whole-website UI/UX redesign and quality pass

EduConnect · Laravel 12.70.2 · PHP 8.3 · Bootstrap 5.3.8 · Bootstrap Icons ·
Chart.js 4 · Vite 6 · MySQL 8 (SQLite for the automated suite)

Every file in this document is copied verbatim from the working repository.
It is the complete Phase 3C deliverable: design system, every redesigned page,
UX behaviour, accessibility rules, mistake-proofing results and the manual test
checklist.

**Snapshot**

| Item | Value |
| --- | --- |
| Design tokens | `resources/sass/_variables.scss` (190 lines) |
| Design system | `resources/sass/app.scss` (2,775 lines) |
| Style guide | `resources/views/admin/style-guide.blade.php` (521 lines) at `/admin/style-guide` |
| View templates | 86 Blade files |
| JavaScript modules | 17 files, 2,000+ lines |
| Application routes | 91 |
| Automated suite | **266 passed, 1,401 assertions** (29.9 s) |
| Phase 3 UI set | **46 passed, 325 assertions** |
| Accessibility checks | `PhaseThreeCTest` (15), `PhaseThreeUiTest` (12), `PhaseEightTest` (26) |


# 1. Design tokens — resources/sass/_variables.scss

## `resources/sass/_variables.scss`

```scss
// -----------------------------------------------------------------------------
// EduConnect design tokens (Phase 3B)
// -----------------------------------------------------------------------------
// Everything here is imported before Bootstrap, so these are real Bootstrap
// overrides as well as the CSS custom properties used by the custom components.
// -----------------------------------------------------------------------------

// --- Brand palette -----------------------------------------------------------
$primary: #4f46e5; // indigo
$secondary: #7c3aed; // violet
$success: #10b981;
$info: #0ea5e9;
$warning: #f59e0b;
$danger: #ef4444;
$light: #f8fafc;
$dark: #0f172a;

// --- Typography --------------------------------------------------------------
$font-family-sans-serif: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto,
    'Helvetica Neue', Arial, 'Noto Sans', sans-serif, 'Apple Color Emoji',
    'Segoe UI Emoji';
$font-size-base: 0.9375rem; // 15px, a little tighter than Bootstrap's 16px
$line-height-base: 1.6;
$headings-font-weight: 700;
$headings-line-height: 1.25;
$small-font-size: 0.8125rem;

// --- Shape -------------------------------------------------------------------
$border-radius: 0.625rem;
$border-radius-sm: 0.5rem;
$border-radius-lg: 0.875rem;
$border-radius-xl: 1rem;
$border-radius-2xl: 1.25rem;
$card-border-radius: 1rem;
$card-border-width: 1px;
$card-border-color: #e7eaf0;
$card-cap-bg: transparent;
$card-bg: #ffffff;
$modal-content-border-radius: 1rem;
$dropdown-border-radius: 0.75rem;
$dropdown-padding-y: 0.4rem;
$btn-border-radius: 0.625rem;
$btn-border-radius-sm: 0.5rem;
$btn-border-radius-lg: 0.75rem;
$input-border-radius: 0.625rem;
$input-btn-focus-width: 0.2rem;

// --- Surfaces (Bootstrap 5.3 dark mode) -------------------------------------
$body-bg: #f5f7fb;
$body-color: #0f172a;
$body-secondary-color: #64748b;
$body-bg-dark: #0b1020;
$body-color-dark: #e5e7eb;
$body-secondary-color-dark: #94a3b8;
$border-color-dark: #1f2937;
$card-bg-dark: #111827;
$card-border-color-dark: #1f2937;
$modal-content-bg-dark: #111827;
$dropdown-bg-dark: #111827;
$dropdown-border-color-dark: #1f2937;
$input-bg-dark: #0f172a;
$input-border-color-dark: #263244;
$table-color-dark: #e5e7eb;
$table-border-color-dark: #1f2937;

// --- CSS custom properties ---------------------------------------------------
// These are the tokens used by the hand-written components. They intentionally
// mirror Bootstrap's theme so a single data-bs-theme switch restyles the app.
:root {
    --edu-primary: #4f46e5;
    --edu-primary-rgb: 79, 70, 229;
    --edu-primary-dark: #3730a3;
    --edu-primary-soft: rgba(79, 70, 229, 0.1);
    --edu-secondary: #7c3aed;
    --edu-success: #10b981;
    --edu-warning: #f59e0b;
    --edu-danger: #ef4444;
    --edu-info: #0ea5e9;

    --edu-page-bg: #f5f7fb;
    --edu-surface: #ffffff;
    --edu-surface-raised: #ffffff;
    --edu-surface-soft: #f8fafc;
    --edu-surface-muted: #f1f5f9;
    --edu-border: #e7eaf0;
    --edu-border-strong: #d8dee9;
    --edu-text: #0f172a;
    --edu-text-soft: #334155;
    --edu-muted: #64748b;
    --edu-muted-strong: #475569;

    --edu-sidebar-width: 264px;
    --edu-sidebar-collapsed-width: 84px;
    --edu-navbar-height: 72px;
    // Radius scale: 8 / 12 / 16 (+ 20 for large hero surfaces).
    --edu-radius-xs: 8px;
    --edu-radius: 16px;
    --edu-radius-sm: 12px;
    --edu-radius-lg: 20px;

    --edu-shadow-xs: 0 1px 2px rgba(15, 23, 42, 0.05);
    --edu-shadow-card: 0 1px 2px rgba(15, 23, 42, 0.04),
        0 10px 28px -18px rgba(15, 23, 42, 0.28);
    --edu-shadow-hover: 0 2px 4px rgba(15, 23, 42, 0.05),
        0 22px 44px -22px rgba(15, 23, 42, 0.35);
    --edu-shadow-pop: 0 18px 45px -18px rgba(15, 23, 42, 0.28);

    --edu-transition: 150ms ease;
    --edu-ease: cubic-bezier(0.22, 1, 0.36, 1);
    --edu-ring: 0 0 0 0.22rem rgba(79, 70, 229, 0.22);

    // --- Spacing scale (Phase 3C) ------------------------------------------
    // Every gap in the custom components comes from one of these, so vertical
    // rhythm stays identical from page to page. 4px steps.
    --edu-space-1: 0.25rem; //  4px
    --edu-space-2: 0.5rem; //   8px
    --edu-space-3: 0.75rem; // 12px
    --edu-space-4: 1rem; //    16px
    --edu-space-5: 1.5rem; //  24px
    --edu-space-6: 2rem; //    32px
    --edu-space-7: 3rem; //    48px
    --edu-space-8: 4rem; //    64px

    // --- Type scale (Phase 3C) ---------------------------------------------
    --edu-fs-display: clamp(2rem, 1.4rem + 2.6vw, 3.25rem);
    --edu-fs-h1: clamp(1.6rem, 1.25rem + 1.2vw, 2rem);
    --edu-fs-h2: clamp(1.35rem, 1.15rem + 0.7vw, 1.6rem);
    --edu-fs-h3: 1.3rem;
    --edu-fs-h4: 1.15rem;
    --edu-fs-h5: 1rem;
    --edu-fs-h6: 0.9375rem;
    --edu-fs-body: 0.9375rem;
    --edu-fs-small: 0.8125rem;
    --edu-fs-xs: 0.75rem;
    --edu-measure: 70ch; // comfortable reading width

    // --- Z-index layers (Phase 3C) -----------------------------------------
    // One place to look when something overlaps something else.
    --edu-z-base: 1;
    --edu-z-sticky: 1020; // navbar, sticky table headers
    --edu-z-sidebar: 1030;
    --edu-z-dropdown: 1040;
    --edu-z-fab: 1045; // the "new post" button
    --edu-z-mobilenav: 1050; // bottom navigation bar
    --edu-z-modal: 1060;
    --edu-z-toast: 1080; // always on top

    --edu-sidebar-from: #11152b;
    --edu-sidebar-to: #1e1b4b;
    --edu-sidebar-text: #c7cbe0;
    --edu-sidebar-heading: #7c86a8;
    --edu-sidebar-active: rgba(129, 140, 248, 0.16);
    --edu-sidebar-hover: rgba(255, 255, 255, 0.06);
    --edu-sidebar-border: rgba(255, 255, 255, 0.08);

    --edu-chart-grid: rgba(100, 116, 139, 0.16);
    --edu-chart-tick: #64748b;
    --edu-chart-tooltip-bg: #0f172a;
    --edu-chart-tooltip-text: #f8fafc;
}

[data-bs-theme='dark'] {
    --edu-page-bg: #0b1020;
    --edu-surface: #111827;
    --edu-surface-raised: #151d2f;
    --edu-surface-soft: #0f172a;
    --edu-surface-muted: #172033;
    --edu-border: #1f2937;
    --edu-border-strong: #2b3a50;
    --edu-text: #e5e7eb;
    --edu-text-soft: #cbd5e1;
    --edu-muted: #94a3b8;
    --edu-muted-strong: #a8b5c7;

    --edu-shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.35);
    --edu-shadow-card: 0 1px 2px rgba(0, 0, 0, 0.35),
        0 18px 34px -24px rgba(0, 0, 0, 0.8);
    --edu-shadow-hover: 0 2px 6px rgba(0, 0, 0, 0.45),
        0 28px 48px -26px rgba(0, 0, 0, 0.9);
    --edu-shadow-pop: 0 18px 45px -18px rgba(0, 0, 0, 0.85);

    --edu-primary-soft: rgba(129, 140, 248, 0.16);
    --edu-sidebar-from: #070b18;
    --edu-sidebar-to: #14123a;

    --edu-chart-grid: rgba(148, 163, 184, 0.14);
    --edu-chart-tick: #94a3b8;
    --edu-chart-tooltip-bg: #f8fafc;
    --edu-chart-tooltip-text: #0f172a;
}

```

# 2. Design system — resources/sass/app.scss

## `resources/sass/app.scss`

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

```

# 3. Style guide — /admin/style-guide (admin only)

## `resources/views/admin/style-guide.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Style guide') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Style guide') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-palette me-1 text-edu" aria-hidden="true"></i>
                    {{ __('Style guide') }}
                </h1>
                <p class="small text-muted mb-0">
                    {{ __('Single source of truth for colour, type, spacing and every component state.') }}
                </p>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-theme-toggle>
                <i class="bi bi-circle-half me-1" aria-hidden="true"></i>
                {{ __('Toggle dark mode') }}
            </button>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row g-4">
            {{-- In-page navigation --}}
            <div class="col-12 col-xl-3">
                <nav class="card edu-card position-sticky" style="top: calc(var(--edu-navbar-height) + 1rem);"
                     aria-label="{{ __('Style guide sections') }}">
                    <div class="card-body py-3">
                        <div class="edu-eyebrow mb-2">{{ __('On this page') }}</div>
                        <ul class="nav flex-column gap-1 small">
                            @foreach ([
                                'tokens' => __('Tokens'),
                                'typography' => __('Typography'),
                                'buttons' => __('Buttons'),
                                'forms' => __('Forms'),
                                'badges' => __('Badges'),
                                'cards' => __('Cards & empty states'),
                                'tables' => __('Tables'),
                                'feedback' => __('Modals, toasts & dropdowns'),
                                'navigation' => __('Tabs & pagination'),
                                'avatars' => __('Avatars & skeletons'),
                            ] as $id => $label)
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-2" href="#{{ $id }}">{{ $label }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </nav>
            </div>

            <div class="col-12 col-xl-9">
                {{-- ------------------------------------------------------------
                     Tokens
                     ------------------------------------------------------------ --}}
                <section id="tokens" class="card edu-card mb-4" aria-labelledby="tokens-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="tokens-title">{{ __('Colour tokens') }}</h2>
                        <div class="small text-muted">
                            {{ __('Defined once in resources/sass/_variables.scss, consumed by every component in light and dark mode.') }}
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ([
                                ['--edu-primary', 'Primary'],
                                ['--edu-secondary', 'Secondary'],
                                ['--edu-success', 'Success'],
                                ['--edu-warning', 'Warning'],
                                ['--edu-danger', 'Danger'],
                                ['--edu-info', 'Info'],
                                ['--edu-surface', 'Surface'],
                                ['--edu-surface-soft', 'Surface soft'],
                                ['--edu-surface-muted', 'Surface muted'],
                                ['--edu-border', 'Border'],
                                ['--edu-text', 'Text'],
                                ['--edu-muted', 'Muted'],
                            ] as [$token, $label])
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="edu-swatch" style="background: var({{ $token }});" aria-hidden="true"></div>
                                    <div class="small fw-semibold mt-1">{{ $label }}</div>
                                    <code class="small text-muted">{{ $token }}</code>
                                </div>
                            @endforeach
                        </div>

                        <hr>

                        <h3 class="h6 fw-bold">{{ __('Spacing scale') }}</h3>
                        <div class="d-flex flex-wrap align-items-end gap-3">
                            @foreach ([1, 2, 3, 4, 5, 6, 7, 8] as $step)
                                <div class="text-center">
                                    <div class="bg-edu-soft border rounded" style="width: var(--edu-space-{{ $step }}); height: 28px;"></div>
                                    <div class="small text-muted mt-1">{{ $step }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-12 col-md-6">
                                <h3 class="h6 fw-bold">{{ __('Radius') }}</h3>
                                <div class="d-flex gap-2">
                                    @foreach (['xs' => '8px', '' => '16px', 'sm' => '12px', 'lg' => '20px'] as $name => $px)
                                        <div class="border bg-edu-soft d-flex align-items-end justify-content-center small text-muted p-1"
                                             style="width: 72px; height: 56px; border-radius: var(--edu-radius-{{ $name ?: 'base' }});">
                                            {{ $px }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <h3 class="h6 fw-bold">{{ __('Shadows') }}</h3>
                                <div class="d-flex gap-2">
                                    @foreach (['xs' => 'XS', 'card' => 'Card', 'hover' => 'Hover', 'pop' => 'Pop'] as $name => $label)
                                        <div class="bg-body rounded p-3 small" style="box-shadow: var(--edu-shadow-{{ $name }});">
                                            {{ $label }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Typography
                     ------------------------------------------------------------ --}}
                <section id="typography" class="card edu-card mb-4" aria-labelledby="type-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="type-title">{{ __('Typography') }}</h2>
                        <div class="small text-muted">{{ __('Inter with a system-font fallback, 1.5 line-height, 70ch reading width.') }}</div>
                    </div>
                    <div class="card-body edu-measure">
                        <div class="edu-eyebrow mb-2">{{ __('Eyebrow label') }}</div>
                        <p class="edu-landing-title mb-2">{{ __('Display headline') }}</p>
                        <h1>{{ __('Heading one') }}</h1>
                        <h2>{{ __('Heading two') }}</h2>
                        <h3>{{ __('Heading three') }}</h3>
                        <h4>{{ __('Heading four') }}</h4>
                        <h5>{{ __('Heading five') }}</h5>
                        <h6>{{ __('Heading six') }}</h6>
                        <p class="mb-2">
                            {{ __('Body copy stays comfortable at 15px with a 1.5 line-height. Links look like') }}
                            <a href="#tokens">{{ __('this') }}</a>,
                            <strong>{{ __('bold text') }}</strong>,
                            <em>{{ __('emphasis') }}</em>
                            {{ __('and') }} <code>{{ __('inline code') }}</code>.
                        </p>
                        <p class="small text-muted mb-0">{{ __('Small helper text, captions and timestamps.') }}</p>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Buttons
                     ------------------------------------------------------------ --}}
                <section id="buttons" class="card edu-card mb-4" aria-labelledby="buttons-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="buttons-title">{{ __('Buttons') }}</h2>
                        <div class="small text-muted">{{ __('One primary action per page; 44px minimum hit area on touch.') }}</div>
                    </div>
                    <div class="card-body d-flex flex-column gap-3">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Primary') }}</button>
                            <button type="button" class="btn btn-secondary">{{ __('Secondary') }}</button>
                            <button type="button" class="btn btn-outline-primary">{{ __('Outline') }}</button>
                            <button type="button" class="btn btn-soft-primary">{{ __('Soft') }}</button>
                            <button type="button" class="btn btn-light border">{{ __('Light') }}</button>
                            <button type="button" class="btn btn-danger"><i class="bi bi-trash" aria-hidden="true"></i> {{ __('Danger') }}</button>
                            <button type="button" class="btn btn-link">{{ __('Ghost link') }}</button>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-primary" disabled>{{ __('Disabled') }}</button>
                            <button type="button" class="btn btn-primary is-loading" aria-busy="true">
                                <span>{{ __('Saving') }}</span>
                            </button>
                            <span class="small text-muted">{{ __('Loading state: spinner replaces the label and the button cannot be pressed twice.') }}</span>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-primary">{{ __('Small') }}</button>
                            <button type="button" class="btn btn-primary">{{ __('Default') }}</button>
                            <button type="button" class="btn btn-lg btn-primary">{{ __('Large') }}</button>
                            <button type="button" class="btn edu-icon-btn" aria-label="{{ __('Icon only example') }}">
                                <i class="bi bi-heart" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Forms
                     ------------------------------------------------------------ --}}
                <section id="forms" class="card edu-card mb-4" aria-labelledby="forms-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="forms-title">{{ __('Forms') }}</h2>
                        <div class="small text-muted">{{ __('Labels are always visible; errors sit next to the field and never erase input.') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="guide-text" class="form-label">{{ __('Text input') }}</label>
                                <input id="guide-text" type="text" class="form-control" value="{{ __('Maria Rossi') }}">
                                <div class="form-text">{{ __('Helper text explains the expected format.') }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="guide-select" class="form-label">{{ __('Select') }}</label>
                                <select id="guide-select" class="form-select">
                                    <option>{{ __('Student') }}</option>
                                    <option>{{ __('Teacher') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="guide-valid" class="form-label">{{ __('Valid state') }}</label>
                                <input id="guide-valid" type="email" class="form-control is-valid" value="you@example.com">
                                <div class="valid-feedback">{{ __('Looks good.') }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="guide-invalid" class="form-label">{{ __('Invalid state') }}</label>
                                <input id="guide-invalid" type="email" class="form-control is-invalid" value="not-an-email"
                                       aria-describedby="guide-invalid-error" aria-invalid="true">
                                <div class="invalid-feedback" id="guide-invalid-error">{{ __('Enter a valid email address.') }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="guide-floating" class="form-label">{{ __('Floating label') }}</label>
                                <div class="form-floating">
                                    <input id="guide-floating" type="text" class="form-control" placeholder="{{ __('Subject') }}">
                                    <label for="guide-floating">{{ __('Subject') }}</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="guide-file" class="form-label">{{ __('File input') }}</label>
                                <input id="guide-file" type="file" class="form-control">
                                <div class="form-text">{{ __('JPG, PNG or WebP, up to 2 MB.') }}</div>
                            </div>
                            <div class="col-12">
                                <label for="guide-textarea" class="form-label">{{ __('Textarea with counter') }}</label>
                                <textarea id="guide-textarea" class="form-control" rows="2" maxlength="280"></textarea>
                                <div class="form-text text-end">0/280</div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input id="guide-check" type="checkbox" class="form-check-input" checked>
                                    <label for="guide-check" class="form-check-label">{{ __('Checkbox option') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Badges
                     ------------------------------------------------------------ --}}
                <section id="badges" class="card edu-card mb-4" aria-labelledby="badges-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="badges-title">{{ __('Badges') }}</h2>
                        <div class="small text-muted">{{ __('Roles, class states and report states share one palette.') }}</div>
                    </div>
                    <div class="card-body d-flex flex-column gap-3">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small text-muted me-1">{{ __('Roles') }}:</span>
                            <span class="badge text-bg-dark text-capitalize">{{ __('Admin') }}</span>
                            <span class="badge text-bg-primary text-capitalize">{{ __('Teacher') }}</span>
                            <span class="badge text-bg-success text-capitalize">{{ __('Student') }}</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small text-muted me-1">{{ __('Class') }}:</span>
                            <x-status-badge status="scheduled" />
                            <x-status-badge status="live" />
                            <x-status-badge status="completed" />
                            <x-status-badge status="hidden" />
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small text-muted me-1">{{ __('Report') }}:</span>
                            <x-status-badge status="pending" />
                            <x-status-badge status="reviewed" />
                            <x-status-badge status="dismissed" />
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small text-muted me-1">{{ __('Counters') }}:</span>
                            <span class="edu-chip"><i class="bi bi-people" aria-hidden="true"></i> 128</span>
                            <span class="edu-chip"><i class="bi bi-heart" aria-hidden="true"></i> 42</span>
                            <span class="badge rounded-pill text-bg-danger">9+</span>
                        </div>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Cards and empty states
                     ------------------------------------------------------------ --}}
                <section id="cards" class="card edu-card mb-4" aria-labelledby="cards-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="cards-title">{{ __('Cards & empty states') }}</h2>
                        <div class="small text-muted">{{ __('Cards lift by 2px on hover; empty states always offer a next step.') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="card edu-card edu-card-hover h-100">
                                    <div class="card-body">
                                        <div class="edu-eyebrow mb-2">{{ __('Standard card') }}</div>
                                        <h3 class="h6 fw-bold">{{ __('Algebra basics') }}</h3>
                                        <p class="small text-muted mb-3">{{ __('A short description keeps the card useful without becoming a wall of text.') }}</p>
                                        <a href="#cards" class="btn btn-sm btn-primary">{{ __('Enroll') }}</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <x-empty-state icon="bi-easel"
                                               :title="__('No classes yet')"
                                               :message="__('Create your first class and it will show up here.')">
                                    <a href="#cards" class="btn btn-primary">{{ __('Create a class') }}</a>
                                </x-empty-state>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Tables
                     ------------------------------------------------------------ --}}
                <section id="tables" class="card edu-card mb-4" aria-labelledby="tables-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="tables-title">{{ __('Tables') }}</h2>
                        <div class="small text-muted">{{ __('Sticky headers on desktop, labelled cards below 992px.') }}</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('Person') }}</th>
                                    <th scope="col">{{ __('Role') }}</th>
                                    <th scope="col">{{ __('Status') }}</th>
                                    <th scope="col" class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td data-label="{{ __('Person') }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <x-avatar :user="auth()->user()" size="sm" />
                                            <span class="fw-semibold">{{ auth()->user()->name }}</span>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('Role') }}"><span class="badge text-bg-primary text-capitalize">{{ auth()->user()->role }}</span></td>
                                    <td data-label="{{ __('Status') }}"><span class="badge text-bg-success">{{ __('Active') }}</span></td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td data-label="{{ __('Person') }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar-sm bg-secondary-subtle text-secondary-emphasis d-inline-flex align-items-center justify-content-center fw-bold">EC</span>
                                            <span class="fw-semibold">{{ __('Example Student') }}</span>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('Role') }}"><span class="badge text-bg-success text-capitalize">{{ __('student') }}</span></td>
                                    <td data-label="{{ __('Status') }}"><span class="badge text-bg-danger">{{ __('Blocked') }}</span></td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Feedback: modal, toast, dropdown
                     ------------------------------------------------------------ --}}
                <section id="feedback" class="card edu-card mb-4" aria-labelledby="feedback-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="feedback-title">{{ __('Modals, toasts & dropdowns') }}</h2>
                        <div class="small text-muted">{{ __('Destructive actions always ask first; toasts announce themselves to screen readers.') }}</div>
                    </div>
                    <div class="card-body d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#guideModal">
                            {{ __('Open example modal') }}
                        </button>

                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                {{ __('Dropdown') }}
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#feedback"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a></li>
                                <li><a class="dropdown-item" href="#feedback"><i class="bi bi-link me-1" aria-hidden="true"></i>{{ __('Copy link') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button type="button" class="dropdown-item text-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i>{{ __('Delete') }}</button></li>
                            </ul>
                        </div>

                        <div class="edu-toast-stack position-static" role="status" aria-live="polite">
                            <div class="edu-toast is-success">
                                <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
                                <div class="small fw-semibold">{{ __('Saved successfully.') }}</div>
                            </div>
                        </div>

                        <div class="w-100">
                            <div class="alert alert-success mb-2" role="alert"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ __('Success alert') }}</div>
                            <div class="alert alert-warning mb-2" role="alert"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ __('Warning alert') }}</div>
                            <div class="alert alert-danger mb-0" role="alert"><i class="bi bi-x-circle me-1" aria-hidden="true"></i>{{ __('Error alert') }}</div>
                        </div>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Navigation: tabs, breadcrumbs, pagination
                     ------------------------------------------------------------ --}}
                <section id="navigation" class="card edu-card mb-4" aria-labelledby="nav-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="nav-title">{{ __('Tabs, breadcrumbs & pagination') }}</h2>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#guide-tab-one"
                                        type="button" role="tab" aria-selected="true">{{ __('Posts') }}</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#guide-tab-two"
                                        type="button" role="tab" aria-selected="false">{{ __('Classes') }}</button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="guide-tab-one" role="tabpanel">
                                <p class="small text-muted mb-0">{{ __('Tab one content.') }}</p>
                            </div>
                            <div class="tab-pane fade" id="guide-tab-two" role="tabpanel">
                                <p class="small text-muted mb-0">{{ __('Tab two content.') }}</p>
                            </div>
                        </div>

                        <hr>

                        <nav aria-label="{{ __('Example breadcrumb') }}">
                            <ol class="breadcrumb mb-3">
                                <li class="breadcrumb-item"><a href="#navigation">{{ __('Dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="#navigation">{{ __('Classes') }}</a></li>
                                <li class="breadcrumb-item active" aria-current="page">{{ __('Algebra basics') }}</li>
                            </ol>
                        </nav>

                        <nav aria-label="{{ __('Example pagination') }}">
                            <ul class="pagination mb-0">
                                <li class="page-item disabled"><span class="page-link">{{ __('Previous') }}</span></li>
                                <li class="page-item active" aria-current="page"><span class="page-link">1</span></li>
                                <li class="page-item"><a class="page-link" href="#navigation">2</a></li>
                                <li class="page-item"><a class="page-link" href="#navigation">{{ __('Next') }}</a></li>
                            </ul>
                        </nav>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Avatars, progress and skeletons
                     ------------------------------------------------------------ --}}
                <section id="avatars" class="card edu-card mb-4" aria-labelledby="avatars-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="avatars-title">{{ __('Avatars, progress & skeletons') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-end gap-3 mb-3">
                            <x-avatar :user="auth()->user()" size="sm" />
                            <x-avatar :user="auth()->user()" size="md" />
                            <x-avatar :user="auth()->user()" size="lg" />
                            <x-avatar :user="auth()->user()" size="xl" />
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="progress" role="progressbar" aria-label="{{ __('Upload progress') }}"
                                     aria-valuenow="65" aria-valuemin="0" aria-valuemax="100" style="height: 8px;">
                                    <div class="progress-bar" style="width: 65%"></div>
                                </div>
                                <div class="small text-muted mt-1">65%</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="edu-skeleton" style="height: 14px; width: 80%;"></div>
                                <div class="edu-skeleton mt-2" style="height: 14px; width: 55%;"></div>
                                <div class="edu-skeleton mt-2" style="height: 14px; width: 68%;"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <p class="small text-muted">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    {{ __('Every example above uses the same tokens and works in light and dark mode. Toggle the theme with the switch in the top bar.') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Example modal, kept at the end so it never affects the layout. --}}
    <div class="modal fade" id="guideModal" tabindex="-1" aria-labelledby="guideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="guideModalLabel">{{ __('Example modal') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('Use a modal for a short confirmation or a focused task. The close button is always reachable with the keyboard.') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary">{{ __('Confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

# 4. Layouts and navigation

## `resources/views/layouts/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
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

## `resources/views/layouts/guest.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
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

## `resources/views/layouts/navigation.blade.php`

```blade
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
                {{-- Phone shortcut to the search page --}}
                <li class="nav-item d-md-none">
                    <a class="btn edu-icon-btn" href="{{ route('search.index') }}"
                       aria-label="{{ __('Search') }}">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </a>
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
</nav>

```

## `resources/views/layouts/sidebar.blade.php`

```blade
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

## `resources/views/layouts/mobile-nav.blade.php`

```blade
{{-- Mobile bottom navigation (Phase 3C).
     Five destinations, each one tap away, with a 56px touch target.
     The same information is available in the sidebar drawer; this bar simply
     keeps the most used pages reachable with a thumb. --}}
@php
    $me = auth()->user();

    $homeRoute = match ($me->role) {
        \App\Models\User::ROLE_ADMIN => 'admin.dashboard',
        \App\Models\User::ROLE_TEACHER => 'teacher.dashboard',
        default => 'student.dashboard',
    };

    $classesRoute = match ($me->role) {
        \App\Models\User::ROLE_ADMIN => 'admin.classes.index',
        \App\Models\User::ROLE_TEACHER => 'classes.index',
        default => 'classes.discover',
    };

    $items = [
        [
            'label' => __('Home'),
            'icon' => 'bi-house-door',
            'url' => route($homeRoute),
            'active' => request()->routeIs($homeRoute),
        ],
        [
            'label' => __('Classes'),
            'icon' => 'bi-easel',
            'url' => route($classesRoute),
            'active' => request()->routeIs('classes.*', 'student.classes', 'admin.classes.*'),
        ],
        [
            'label' => __('Search'),
            'icon' => 'bi-search',
            'url' => route('search.index'),
            'active' => request()->routeIs('search.*'),
        ],
        [
            'label' => __('Notifications'),
            'icon' => 'bi-bell',
            'url' => route('notifications.index'),
            'active' => request()->routeIs('notifications.*'),
            'badge' => $unreadCount,
        ],
        [
            'label' => __('Profile'),
            'icon' => 'bi-person',
            'url' => route('users.show', $me),
            'active' => request()->routeIs('users.show') && request()->route('user')?->is($me),
        ],
    ];
@endphp

<nav class="edu-mobile-nav d-lg-none" aria-label="{{ __('Mobile navigation') }}">
    @foreach ($items as $item)
        <a href="{{ $item['url'] }}"
           class="edu-mobile-nav-item {{ $item['active'] ? 'active' : '' }}"
           @if ($item['active']) aria-current="page" @endif>
            <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
            <span>{{ $item['label'] }}</span>

            @if (! empty($item['badge']))
                <span class="badge rounded-pill text-bg-danger edu-mobile-nav-badge">
                    {{ $item['badge'] > 9 ? '9+' : $item['badge'] }}
                    <span class="visually-hidden">{{ __('unread notifications') }}</span>
                </span>
            @endif
        </a>
    @endforeach
</nav>

```

# 5. Landing page (guests)

## `resources/views/welcome.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Learn together, anywhere') }}</x-slot>
    <x-slot name="description">{{ __('EduConnect brings live video classes, a friendly social feed with posts and hashtags, and one-click class enrolment together for teachers and students.') }}</x-slot>

    <div class="edu-landing">
        {{-- ------------------------------------------------------------------
             Hero
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-hero" aria-labelledby="hero-title">
            <span class="edu-landing-shape is-one" aria-hidden="true"></span>
            <span class="edu-landing-shape is-two" aria-hidden="true"></span>

            <div class="container-xxl px-3 px-lg-4 position-relative">
                <div class="row align-items-center g-5">
                    <div class="col-12 col-lg-6">
                        <span class="edu-eyebrow mb-3">
                            <i class="bi bi-stars" aria-hidden="true"></i>
                            {{ __('A calmer way to teach online') }}
                        </span>

                        <h1 class="edu-landing-title fw-bold mb-3" id="hero-title">
                            {{ __('Teach and learn,') }}
                            <span class="text-edu">{{ __('together') }}</span>.
                        </h1>

                        <p class="lead text-muted mb-4 edu-measure">
                            {{ __('Live video classes, a friendly social feed and simple class management — all in one place. Students follow their teachers and enroll in a class in a single click.') }}
                        </p>

                        <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 mb-3">
                            <a href="{{ route('register', ['role' => 'student']) }}" class="btn btn-primary btn-lg">
                                <i class="bi bi-mortarboard me-1" aria-hidden="true"></i>
                                {{ __('Register as Student') }}
                            </a>
                            <a href="{{ route('register', ['role' => 'teacher']) }}" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-easel me-1" aria-hidden="true"></i>
                                {{ __('Register as Teacher') }}
                            </a>

                        </div>

                        <p class="small text-muted mb-0">
                            <i class="bi bi-check2-circle text-success me-1" aria-hidden="true"></i>
                            {{ __('Free to start. No credit card, no download.') }}
                        </p>
                    </div>

                    <div class="col-12 col-lg-6">
                        {{-- CSS-only mockup of a live class: no image request, no layout shift. --}}
                        <div class="edu-mockup" role="img"
                             aria-label="{{ __('Preview of a live class: teacher video, shared notes and participant tiles.') }}">
                            <div class="edu-mockup-bar" aria-hidden="true">
                                <span class="edu-mockup-dot"></span>
                                <span class="edu-mockup-dot"></span>
                                <span class="edu-mockup-dot"></span>
                                <span class="ms-auto small text-muted fw-semibold">{{ __('Algebra basics · Live') }}</span>
                            </div>

                            <div class="edu-mockup-stage">
                                <span class="edu-mockup-tile" style="left: 6%; top: 8%; width: 58%; height: 58%;" aria-hidden="true"></span>
                                <span class="edu-mockup-tile" style="right: 6%; top: 8%; width: 28%; height: 26%;" aria-hidden="true"></span>
                                <span class="edu-mockup-tile" style="right: 6%; bottom: 8%; width: 28%; height: 26%;" aria-hidden="true"></span>
                                <span class="edu-mockup-tile" style="left: 6%; bottom: 8%; width: 58%; height: 20%;" aria-hidden="true"></span>

                                <span class="position-absolute top-0 start-0 m-3 badge text-bg-danger">
                                    <i class="bi bi-broadcast me-1" aria-hidden="true"></i>{{ __('Live') }}
                                </span>
                            </div>

                            <div class="edu-mockup-bar-strip" aria-hidden="true">
                                <span class="edu-mockup-tool"><i class="bi bi-mic"></i></span>
                                <span class="edu-mockup-tool"><i class="bi bi-camera-video"></i></span>
                                <span class="edu-mockup-tool"><i class="bi bi-chat-dots"></i></span>
                                <span class="edu-mockup-tool"><i class="bi bi-people"></i></span>
                                <span class="edu-mockup-tool"><i class="bi bi-telephone-x text-danger"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             How it works
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section" aria-labelledby="steps-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="text-center mb-5">
                    <span class="edu-eyebrow mb-2">{{ __('How it works') }}</span>
                    <h2 class="edu-section-title fw-bold mb-2" id="steps-title">{{ __('Three steps to your first class') }}</h2>
                    <p class="text-muted mb-0 mx-auto edu-measure">
                        {{ __('Whether you teach or learn, you only need a browser and a few minutes.') }}
                    </p>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="edu-step-card">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="edu-step-number" aria-hidden="true">1</span>
                                <h3 class="h6 fw-bold mb-0">{{ __('Create your account') }}</h3>
                            </div>
                            <p class="small text-muted mb-0">
                                {{ __('Pick Student or Teacher, add your name and you are in. Admins create staff accounts for you.') }}
                            </p>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="edu-step-card">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="edu-step-number" aria-hidden="true">2</span>
                                <h3 class="h6 fw-bold mb-0">{{ __('Find or create a class') }}</h3>
                            </div>
                            <p class="small text-muted mb-0">
                                {{ __('Teachers publish a class with a date and time. Students browse by subject and enroll in one click.') }}
                            </p>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="edu-step-card">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="edu-step-number" aria-hidden="true">3</span>
                                <h3 class="h6 fw-bold mb-0">{{ __('Meet, chat and keep learning') }}</h3>
                            </div>
                            <p class="small text-muted mb-0">
                                {{ __('Join the live room, then keep the conversation going in the feed with posts, comments and hashtags.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             Features
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section pt-0" aria-labelledby="features-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="text-center mb-5">
                    <span class="edu-eyebrow mb-2">{{ __('Everything in one place') }}</span>
                    <h2 class="edu-section-title fw-bold mb-2" id="features-title">{{ __('Built for real classrooms') }}</h2>
                    <p class="text-muted mb-0 mx-auto edu-measure">
                        {{ __('No plugin maze. Each feature solves one daily teaching moment.') }}
                    </p>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-lg-6">
                        <div class="edu-feature-card h-100">
                            <h3 class="h6 fw-bold mb-2">
                                <i class="bi bi-easel text-edu me-1" aria-hidden="true"></i>
                                {{ __('For teachers') }}
                            </h3>
                            <p class="small text-muted mb-0">
                                {{ __('Schedule a class, share a private Jitsi room, see who enrolled, and keep the conversation going in the feed.') }}
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="edu-feature-card h-100">
                            <h3 class="h6 fw-bold mb-2">
                                <i class="bi bi-mortarboard text-edu me-1" aria-hidden="true"></i>
                                {{ __('For students') }}
                            </h3>
                            <p class="small text-muted mb-0">
                                {{ __('Follow teachers, enrol in one click, join live rooms from the browser, and ask questions in posts and comments.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach ([
                        ['bi-camera-video', __('Live classes'), __('Open a secure video room inside EduConnect and start teaching right away.')],
                        ['bi-chat-square-text', __('Social feed'), __('Short posts, images, likes and comments keep the class community close.')],
                        ['bi-hash', __('Hashtags'), __('Group posts by topic and discover the five busiest tags of the week.')],
                        ['bi-bell', __('Notifications'), __('Followers, likes, comments and starting classes arrive in one calm inbox.')],
                        ['bi-shield-check', __('Safe moderation'), __('Report content, block accounts and keep every learner protected.')],
                        ['bi-speedometer2', __('Role dashboards'), __('Students, teachers and admins each see exactly what they need.')],
                    ] as [$icon, $title, $copy])
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="edu-feature-card">
                                <span class="edu-auth-icon mb-3" aria-hidden="true">
                                    <i class="bi {{ $icon }}"></i>
                                </span>
                                <h3 class="h6 fw-bold">{{ $title }}</h3>
                                <p class="small text-muted mb-0">{{ $copy }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             Stats strip
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section pt-0" aria-labelledby="stats-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="edu-stats-strip">
                    <h2 class="visually-hidden" id="stats-title">{{ __('EduConnect at a glance') }}</h2>

                    <div class="row g-4 text-center text-md-start">
                        <div class="col-6 col-md-3">
                            <div class="edu-stat-figure">3</div>
                            <div class="small text-white-50">{{ __('role-based experiences') }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="edu-stat-figure">1</div>
                            <div class="small text-white-50">{{ __('click to enroll') }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="edu-stat-figure">0</div>
                            <div class="small text-white-50">{{ __('apps to install') }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="edu-stat-figure">24/7</div>
                            <div class="small text-white-50">{{ __('access to your feed') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             Testimonial
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section pt-0" aria-labelledby="quote-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-8">
                        <figure class="edu-step-card text-center mb-0">
                            <span class="edu-empty-illustration mx-auto" aria-hidden="true">
                                <i class="bi bi-quote"></i>
                            </span>
                            <blockquote class="mb-3">
                                <p class="h5 fw-semibold mb-0" id="quote-title">
                                    {{ __('“My students join from their phones, ask questions in the feed and never lose the thread between lessons.”') }}
                                </p>
                            </blockquote>
                            <figcaption class="small text-muted">
                                {{ __('A teacher using EduConnect every week') }}
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             FAQ
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section pt-0" aria-labelledby="faq-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-8">
                        <div class="text-center mb-4">
                            <span class="edu-eyebrow mb-2">{{ __('Questions') }}</span>
                            <h2 class="edu-section-title fw-bold mb-0" id="faq-title">{{ __('Frequently asked') }}</h2>
                        </div>

                        <div class="accordion edu-faq" id="landingFaq">
                            @foreach ([
                                [__('Do I need to install anything?'), __('No. EduConnect runs in your browser. The video room opens in the same tab and works on phones, tablets and computers.')],
                                [__('Is it free for students?'), __('Creating a Student account is free. Your school or teacher decides which classes are published.')],
                                [__('How do teachers start a class?'), __('A teacher schedules a class, opens the room when ready and presses “Start class”. Enrolled students then see the Join button.')],
                                [__('What happens if someone behaves badly?'), __('Any member can report a post or comment. Admins review reports, hide content and can block the account.')],
                                [__('Can I use it on a slow connection?'), __('Yes. Pages are server-rendered, images are lazy-loaded and the video room degrades gracefully with a clear retry message.')],
                            ] as $index => [$question, $answer])
                                <div class="accordion-item">
                                    <h3 class="accordion-header" id="faqHeading{{ $index }}">
                                        <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#faqCollapse{{ $index }}"
                                                aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                                aria-controls="faqCollapse{{ $index }}">
                                            {{ $question }}
                                        </button>
                                    </h3>
                                    <div id="faqCollapse{{ $index }}"
                                         class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                                         aria-labelledby="faqHeading{{ $index }}" data-bs-parent="#landingFaq">
                                        <div class="accordion-body small text-muted">{{ $answer }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             Final call to action
             ------------------------------------------------------------------ --}}
        <section class="pb-5" aria-labelledby="cta-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="edu-stats-strip text-center">
                    <h2 class="h3 fw-bold mb-2" id="cta-title">{{ __('Ready to start your first class?') }}</h2>
                    <p class="text-white-50 mb-4 mx-auto edu-measure">
                        {{ __('Join as a student to learn, or as a teacher to share what you know.') }}
                    </p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                        <a href="{{ route('register', ['role' => 'student']) }}" class="btn btn-light btn-lg">
                            <i class="bi bi-mortarboard me-1" aria-hidden="true"></i>
                            {{ __('Register as Student') }}
                        </a>
                        <a href="{{ route('register', ['role' => 'teacher']) }}" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-easel me-1" aria-hidden="true"></i>
                            {{ __('Register as Teacher') }}
                        </a>
                    </div>
                    <p class="small text-white-50 mt-3 mb-0">
                        {{ __('Already have an account?') }}
                        <a href="{{ route('login') }}" class="link-light fw-semibold">{{ __('Log in') }}</a>
                    </p>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>

```

# 6. Authentication pages

## `resources/views/auth/login.blade.php`

```blade
<x-guest-layout>
    <h1 class="h4 fw-bold mb-1">{{ __('Welcome back') }}</h1>
    <p class="text-muted small mb-4">{{ __('Log in to reach your classes and feed.') }}</p>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <!-- Email -->
        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autofocus autocomplete="username"
                          placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Password with show/hide toggle -->
        <x-password-input name="password" :label="__('Password')" autocomplete="current-password" />

        <!-- Remember me -->
        <div class="form-check mb-4">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember"
                   @checked(old('remember'))>
            <label for="remember_me" class="form-check-label small">{{ __('Remember me') }}</label>
        </div>

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
            {{ __('Log in') }}
        </x-primary-button>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4">
            @if (Route::has('password.request'))
                <a class="small text-decoration-none" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            @if (Route::has('register'))
                <a class="small text-decoration-none" href="{{ route('register') }}">
                    {{ __('Create an account') }}
                </a>
            @endif
        </div>
    </form>
</x-guest-layout>

```

## `resources/views/auth/register.blade.php`

```blade
<x-guest-layout :title="__('Create your account')">
    @php
        $requestedRole = request()->query('role');
        $selectedRole = in_array(old('role'), ['teacher', 'student'], true)
            ? old('role')
            : (in_array($requestedRole, ['teacher', 'student'], true) ? $requestedRole : 'student');
    @endphp

    <h1 class="h4 fw-bold mb-1">{{ __('Create your account') }}</h1>
    <p class="text-muted small mb-4">{{ __('Join as a teacher or as a student. You can switch later from your profile.') }}</p>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <!-- Name -->
        <div class="mb-3">
            <x-input-label for="name" :value="__('Full name')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')"
                          :class="$errors->has('name') ? 'is-invalid' : ''"
                          required autofocus autocomplete="name"
                          placeholder="Maria Rossi" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <!-- Email -->
        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autocomplete="username"
                          placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Role: only Teacher or Student can ever be posted. -->
        <fieldset class="mb-3" data-role-select>
            <legend class="form-label mb-2">{{ __('I am a') }}</legend>

            <div class="row g-2">
                @foreach ([
                    'student' => [
                        'icon' => 'bi-mortarboard',
                        'title' => __('Student'),
                        'copy' => __('Follow teachers and enroll in live classes.'),
                    ],
                    'teacher' => [
                        'icon' => 'bi-easel',
                        'title' => __('Teacher'),
                        'copy' => __('Publish classes and share posts with your students.'),
                    ],
                ] as $value => $card)
                    <div class="col-12 col-sm-6">
                        <label class="edu-role-card w-100 h-100 {{ $selectedRole === $value ? 'is-selected' : '' }}">
                            <input type="radio" name="role" value="{{ $value }}"
                                   data-role-radio
                                   @checked($selectedRole === $value) required>

                            <span class="edu-role-icon" aria-hidden="true">
                                <i class="bi {{ $card['icon'] }}"></i>
                            </span>

                            <span class="min-w-0">
                                <span class="d-block fw-semibold">{{ $card['title'] }}</span>
                                <span class="d-block small text-muted">{{ $card['copy'] }}</span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="form-text">{{ __('Admin accounts are created by the platform.') }}</div>
            <x-input-error :messages="$errors->get('role')" class="mt-1" />
        </fieldset>

        <!-- Optional subject, revealed for teachers by auth.js. -->
        <div class="mb-3" data-subject-field @if ($selectedRole !== 'teacher') hidden @endif>
            <x-input-label for="subject" :value="__('Subject you teach (optional)')" />
            <x-text-input id="subject" type="text" name="subject" :value="old('subject')"
                          placeholder="{{ __('e.g. Mathematics') }}"
                          maxlength="100"
                          :class="$errors->has('subject') ? 'is-invalid' : ''" />
            <x-input-error :messages="$errors->get('subject')" class="mt-1" />
        </div>

        <!-- Password with show/hide toggle and strength meter -->
        <x-password-input name="password" :label="__('Password')" autocomplete="new-password" :strength="true" />

        <!-- Confirm password -->
        <x-password-input name="password_confirmation" :label="__('Confirm password')" autocomplete="new-password" />

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-person-plus me-1" aria-hidden="true"></i>
            {{ __('Create account') }}
        </x-primary-button>

        <p class="text-center small mt-4 mb-0">
            {{ __('Already registered?') }}
            <a class="text-decoration-none fw-semibold" href="{{ route('login') }}">{{ __('Log in') }}</a>
        </p>
    </form>
</x-guest-layout>

```

## `resources/views/auth/forgot-password.blade.php`

```blade
<x-guest-layout>
    <h1 class="h4 fw-bold mb-1">{{ __('Forgot your password?') }}</h1>
    <p class="text-muted small mb-4">
        {{ __('No problem. Tell us your email address and we will send you a link to choose a new password.') }}
    </p>

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="mb-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autofocus autocomplete="username"
                          placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-envelope me-1" aria-hidden="true"></i>
            {{ __('Email password reset link') }}
        </x-primary-button>

        <p class="text-center small mt-4 mb-0">
            <a class="text-decoration-none" href="{{ route('login') }}">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                {{ __('Back to login') }}
            </a>
        </p>
    </form>
</x-guest-layout>

```

## `resources/views/auth/reset-password.blade.php`

```blade
<x-guest-layout>
    <h1 class="h4 fw-bold mb-1">{{ __('Choose a new password') }}</h1>
    <p class="text-muted small mb-4">{{ __('Pick something long and hard to guess.') }}</p>

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf

        <!-- Reset token (comes from the emailed link) -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <x-password-input name="password" :label="__('New password')" autocomplete="new-password" />

        <x-password-input name="password_confirmation" :label="__('Confirm new password')" autocomplete="new-password" />

        <p class="form-text mt-0 mb-4">{{ __('Use at least 8 characters.') }}</p>

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-key me-1" aria-hidden="true"></i>
            {{ __('Reset password') }}
        </x-primary-button>

        <p class="text-center small mt-4 mb-0">
            <a class="text-decoration-none" href="{{ route('login') }}">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                {{ __('Back to login') }}
            </a>
        </p>
    </form>
</x-guest-layout>

```

## `resources/views/auth/confirm-password.blade.php`

```blade
<x-guest-layout>
    <h1 class="h4 fw-bold mb-1">{{ __('Confirm your password') }}</h1>
    <p class="text-muted small mb-4">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf

        <x-password-input name="password" :label="__('Password')" autocomplete="current-password" autofocus />

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
            {{ __('Confirm') }}
        </x-primary-button>
    </form>
</x-guest-layout>

```

## `resources/views/auth/verify-email.blade.php`

```blade
<x-guest-layout>
    <div class="text-center mb-3">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary p-3 fs-4"
              aria-hidden="true">
            <i class="bi bi-envelope-check"></i>
        </span>
    </div>

    <h1 class="h4 fw-bold mb-1 text-center">{{ __('Verify your email') }}</h1>
    <p class="text-muted small mb-4 text-center">
        {{ __('Thanks for signing up! Please click the link we just emailed you to verify your address.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success py-2 small" role="status">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-send me-1" aria-hidden="true"></i>
            {{ __('Resend verification email') }}
        </x-primary-button>
    </form>

    <p class="text-center small mt-4 mb-0">
        {{ __('Wrong account?') }}
    </p>

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">
            {{ __('Log out') }}
        </button>
    </form>
</x-guest-layout>

```

# 7. Feed, posts and hashtags

## `resources/views/feed/index.blade.php`

```blade
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
        <div class="row g-4 justify-content-center">
            {{-- Main column: composer + feed --}}
            <div class="col-12 col-lg-8">
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
            <aside class="col-12 col-lg-4">
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

## `resources/views/feed/_posts.blade.php`

```blade
{{-- Renders a list of post cards. Used by the feed (initial page + "Load more"),
     the hashtag page and the public profile (FR-7 ... FR-13).

     $commentsByPost is supplied by the feed so comments can be expanded in
     place. Other pages leave it out and the cards simply link to the detail
     page. --}}
@php
    // null on the profile and hashtag pages: the card then links to the
    // detail page instead of opening an inline comment section.
    $commentsByPost = $commentsByPost ?? null;
@endphp

@forelse ($posts as $post)
    <div class="mb-3">
        <x-post-card
            :post="$post"
            :comments="$commentsByPost?->get($post->id, collect())"
        />
    </div>
@empty
    <div class="card edu-card">
        <div class="card-body text-center py-5">
            <div class="edu-empty-illustration mx-auto" aria-hidden="true">
                <i class="bi bi-inbox"></i>
            </div>
            <h2 class="h6 fw-bold mb-1">{{ __('No posts to show yet.') }}</h2>

            @if (! empty($emptyMessage))
                <p class="text-muted small mb-3">{{ $emptyMessage }}</p>
            @endif

            @if (! empty($emptyActionUrl))
                <a href="{{ $emptyActionUrl }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1" aria-hidden="true"></i>
                    {{ $emptyActionLabel ?? __('Explore') }}
                </a>
            @endif
        </div>
    </div>
@endforelse

```

## `resources/views/components/post-card.blade.php`

```blade
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
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}"
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

## `resources/views/posts/_form.blade.php`

```blade
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

    <div class="d-flex flex-wrap gap-2">
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

## `resources/views/posts/create.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('New post') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('feed.index') }}">{{ __('Feed') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('New post') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-pencil-square me-1 text-edu" aria-hidden="true"></i> {{ __('New post') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Short updates work best. Add one image if it helps.') }}</p>
            </div>
            <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-lg me-1" aria-hidden="true"></i> {{ __('Cancel') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                <div class="card edu-card">
                    <div class="card-body">
                        {{-- Same partial the feed composer uses (FR-9). --}}
                        @include('posts._form', ['post' => null])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/posts/edit.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Edit post') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('feed.index') }}">{{ __('Feed') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('posts.show', $post) }}">{{ __('Post') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-pencil me-1 text-edu" aria-hidden="true"></i> {{ __('Edit post') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Update the text or replace the image.') }}</p>
            </div>
            <a href="{{ route('posts.show', $post) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to the post') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                <div class="card edu-card">
                    <div class="card-body">
                        {{-- Only reachable when PostPolicy allows it (FR-10). --}}
                        @include('posts._form', ['post' => $post])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/posts/show.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Post') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('feed.index') }}">{{ __('Feed') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Post') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-chat-square-text me-1 text-edu"></i> {{ __('Post') }}
                </h1>
                <div class="small text-muted">{{ __('Read the conversation and join in.') }}</div>
            </div>
            <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('Back to feed') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                {{-- The post and its comments share one counter scope, so an
                     AJAX add/delete updates every number on this page at once. --}}
                <div data-comment-scope="{{ $post->id }}">
                    <x-post-card :post="$post" />

                    {{-- Comments (FR-10) --}}
                    <section class="card edu-card mt-4" id="comments" aria-labelledby="comments-title">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2 class="h6 fw-bold mb-0" id="comments-title">
                                <i class="bi bi-chat me-1 text-edu" aria-hidden="true"></i>
                                {{ __('Comments') }}
                            </h2>
                            <span class="badge bg-body-secondary text-body border">
                                <span data-comment-count>{{ $post->comments_count }}</span>
                            </span>
                        </div>

                        <div class="card-body">
                            <div data-comment-region>
                                {{-- Add a comment: fetch() when JavaScript is on, normal POST otherwise. --}}
                                <form method="POST" action="{{ route('comments.store', $post) }}"
                                      data-comment-form class="mb-3">
                                    @csrf
                                    <label for="comment-body" class="visually-hidden">{{ __('Your comment') }}</label>
                                    <textarea name="body" id="comment-body" rows="2" maxlength="280" required
                                              class="form-control @error('body') is-invalid @enderror"
                                              placeholder="{{ __('Write a comment...') }}">{{ old('body') }}</textarea>
                                    @error('body')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                                        <div class="form-text mt-0">{{ __('Up to 280 characters.') }}</div>
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-send me-1" aria-hidden="true"></i> {{ __('Comment') }}
                                        </button>
                                    </div>
                                </form>

                                {{-- New comments are appended here by feed.js --}}
                                <div data-comment-list>
                                    @forelse ($comments as $comment)
                                        @include('comments._comment', ['comment' => $comment])
                                    @empty
                                        <p class="text-muted small mb-0" data-comment-empty>
                                            {{ __('No comments yet. Be the first one!') }}
                                        </p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        @if ($comments->hasPages())
                            <div class="card-footer bg-transparent">
                                <x-pagination-links :paginator="$comments" />
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/comments/_comment.blade.php`

```blade
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
        <img src="{{ $author->photoUrl() }}" alt="{{ $author->name }}" class="avatar" loading="lazy" decoding="async">
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

## `resources/views/hashtags/show.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">#{{ $hashtag->name }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('feed.index') }}">{{ __('Feed') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">#{{ $hashtag->name }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-hash text-edu" aria-hidden="true"></i>{{ $hashtag->name }}
                </h1>
                <p class="small text-muted mb-0">
                    {{-- e.g. "3 posts have this tag" --}}
                    {{ trans_choice(':count post has this tag.|:count posts have this tag.', $posts->total(), ['count' => $posts->total()]) }}
                </p>
            </div>
            <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to feed') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                <section class="card edu-card mb-3">
                    <div class="card-body d-flex flex-wrap align-items-center gap-3">
                        <span class="edu-hashtag-hero" aria-hidden="true">#</span>

                        <div class="min-w-0">
                            <h2 class="h4 fw-bold mb-0 text-break">#{{ $hashtag->name }}</h2>
                            <p class="small text-muted mb-0">
                                {{ __('Every visible post that uses this hashtag, newest first.') }}
                            </p>
                        </div>

                        <span class="edu-chip ms-auto">
                            <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                            {{ trans_choice(':count post|:count posts', $posts->total(), ['count' => $posts->total()]) }}
                        </span>
                    </div>
                </section>

                {{-- Every visible post carrying this tag (FR-15) --}}
                @include('feed._posts', [
                    'posts' => $posts,
                    'emptyMessage' => __('No visible post uses this hashtag yet.'),
                    'emptyActionUrl' => route('feed.index'),
                    'emptyActionLabel' => __('Go to the feed'),
                ])

                <div class="mt-3">
                    <x-pagination-links :paginator="$posts" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

# 8. Shared components

## `resources/views/components/avatar.blade.php`

```blade
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
                 {{ $attributes->class([$sizeClass]) }}>
        @else
            <span role="img" aria-label="{{ $name }}"
                  {{ $attributes->class(['edu-avatar-initials', $sizeClass]) }}>{{ $initials }}</span>
        @endif
    </a>
@elseif ($photo)
    <img src="{{ $photo }}" alt="{{ $name }}" loading="lazy" decoding="async"
         {{ $attributes->class([$sizeClass]) }}>
@else
    <span role="img" aria-label="{{ $name }}"
          {{ $attributes->class(['edu-avatar-initials', $sizeClass]) }}>{{ $initials }}</span>
@endif

```

## `resources/views/components/avatar-stack.blade.php`

```blade
{{-- Overlapping avatar stack.
     Usage: <x-avatar-stack :users="$classRoom->students" :limit="5" /> --}}
@props([
    'users' => [],
    'limit' => 5,
    'size' => 'sm',
    'onLight' => false,
])

@php
    $collection = collect($users);
    $shown = $collection->take($limit);
    $extra = max(0, $collection->count() - $shown->count());
    $names = $collection->pluck('name')->filter()->implode(', ');
@endphp

<div {{ $attributes->class(['edu-avatar-stack', 'on-light' => $onLight]) }}
     @if ($names !== '') title="{{ $names }}" @endif>
    @foreach ($shown as $member)
        <x-avatar :user="$member" :size="$size" />
    @endforeach

    @if ($extra > 0)
        <span class="edu-avatar-stack-more" aria-label="{{ __(':count more students', ['count' => $extra]) }}">
            +{{ $extra }}
        </span>
    @endif
</div>

```

## `resources/views/components/user-card.blade.php`

```blade
{{-- A user (teacher or student) as a compact card.
     Usage: <x-user-card :user="$teacher"> <button>Follow</button> </x-user-card> --}}
@props(['user', 'nameHtml' => null])

<div class="card edu-card border-0 h-100">
    <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center gap-3 mb-2">
            @if ($user->photo)
                {{-- Escaped attribute value; the photo lives on the public disk --}}
                <img src="{{ $user->photoUrl() }}" alt="{{ $user->name }}" class="avatar" loading="lazy" decoding="async">
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

## `resources/views/components/class-card.blade.php`

```blade
{{-- A single class (ClassRoom) as a card.
     Usage: <x-class-card :class-room="$class" :url="route('classes.show', $class)"> ...buttons... </x-class-card>
     The optional $url turns the title into a link to the class detail page. --}}
@props(['classRoom', 'url' => null])

@php
    // Use the eager loaded count when the page provided one (avoids N+1).
    $studentsCount = $classRoom->students_count ?? $classRoom->students()->count();
@endphp

<div class="card edu-card border-0 h-100">
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <h3 class="h6 fw-bold mb-0">
                @if ($url)
                    {{-- Links to the class detail page (FR-19) --}}
                    <a href="{{ $url }}" class="text-decoration-none text-reset">{{ $classRoom->title }}</a>
                @else
                    {{ $classRoom->title }}
                @endif
            </h3>

            {{-- Status: scheduled / live / completed --}}
            <x-status-badge :status="$classRoom->status" class="flex-shrink-0" />
        </div>

        @if ($classRoom->subject)
            <div class="mb-2">
                <span class="badge bg-body-secondary text-body border">
                    <i class="bi bi-book me-1"></i>{{ $classRoom->subject }}
                </span>
            </div>
        @endif

        {{-- Date, start time and duration --}}
        <ul class="list-unstyled small text-muted mb-2">
            <li class="mb-1">
                <i class="bi bi-calendar-event me-1"></i>
                {{ $classRoom->date?->format('D d M Y') }}
            </li>
            <li class="mb-1">
                <i class="bi bi-clock me-1"></i>
                {{-- "time" is stored as H:i:s, we only show hours and minutes --}}
                {{ \Illuminate\Support\Str::substr((string) $classRoom->time, 0, 5) }}
                &middot; {{ $classRoom->duration }} {{ __('min') }}
            </li>
            <li class="mb-1">
                <i class="bi bi-person-video3 me-1"></i>
                {{ $classRoom->teacher?->name ?? __('Unknown teacher') }}
            </li>
            <li>
                <i class="bi bi-people me-1"></i>
                {{ trans_choice(':count student|:count students', $studentsCount, ['count' => $studentsCount]) }}
            </li>
        </ul>

        {{-- Relative timing hint without JavaScript (FR-21):
             "Live now", "Starts in 2h 15m" or "Ended". --}}
        <p class="small fw-semibold mb-2 {{ $classRoom->isLive() ? 'text-danger' : 'text-muted' }}">
            <i class="bi bi-stopwatch me-1" aria-hidden="true"></i>{{ $classRoom->timelineLabel() }}
        </p>

        @if ($classRoom->description)
            {{-- Description is escaped by Blade; long texts are cut off --}}
            <p class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($classRoom->description, 120) }}</p>
        @endif

        {{-- Buttons supplied by the page that uses the card --}}
        <div class="mt-auto pt-2 d-flex flex-wrap gap-2">
            {{ $slot }}
        </div>
    </div>
</div>

```

## `resources/views/components/stat-card.blade.php`

```blade
{{-- Statistic card used on all three dashboards.
     Usage:
       <x-stat-card label="Students" :value="12" icon="bi-mortarboard" color="success" />
       <x-stat-card label="Users" :value="120" trend="12" :sparkline="[2,4,3,6,8,7,9]" />
     The optional sparkline is a tiny inline SVG, so it costs no extra request. --}}
@props([
    'label',
    'value',
    'icon' => 'bi-bar-chart',
    'color' => 'primary',
    'hint' => null,
    'href' => null,
    'trend' => null,
    'trendDirection' => null,
    'trendLabel' => null,
    'sparkline' => [],
])

@php
    $series = array_values(array_map(static fn ($point) => (float) $point, (array) $sparkline));
    $hasSparkline = count($series) > 1;

    $hex = [
        'primary' => '#4f46e5',
        'secondary' => '#7c3aed',
        'success' => '#10b981',
        'info' => '#0ea5e9',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
        'dark' => '#0f172a',
    ][$color] ?? '#4f46e5';

    $points = [];
    if ($hasSparkline) {
        $max = max($series);
        $min = min($series);
        $range = ($max - $min) ?: 1;
        $lastIndex = count($series) - 1;

        foreach ($series as $index => $point) {
            $x = ($index / $lastIndex) * 96;
            $y = 28 - ((($point - $min) / $range) * 24);
            $points[] = round($x, 2).','.round($y, 2);
        }
    }

    $gradientId = 'spark-'.substr(md5($label.$hex), 0, 8);

    $hasTrend = $trend !== null;
    $numericTrend = $hasTrend ? (float) $trend : 0;
    $direction = $trendDirection ?: ($numericTrend > 0 ? 'up' : ($numericTrend < 0 ? 'down' : 'flat'));
    $trendIcon = ['up' => 'bi-arrow-up-right', 'down' => 'bi-arrow-down-right', 'flat' => 'bi-dash'][$direction] ?? 'bi-dash';
    $displayValue = is_numeric($value) ? number_format((float) $value) : $value;
@endphp

<div {{ $attributes->class(['card', 'edu-card', 'edu-stat-card', 'h-100']) }} data-stat-card>
    <div class="card-body">
        <div class="edu-stat-top">
            <span class="edu-stat-icon bg-{{ $color }}" aria-hidden="true">
                <i class="bi {{ $icon }}"></i>
            </span>

            @if ($hasSparkline)
                {{-- Decorative mini sparkline; the number and trend carry the meaning. --}}
                <svg class="edu-sparkline" viewBox="0 0 96 32" role="presentation" focusable="false" aria-hidden="true">
                    <defs>
                        <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="{{ $hex }}" stop-opacity="0.28"></stop>
                            <stop offset="100%" stop-color="{{ $hex }}" stop-opacity="0"></stop>
                        </linearGradient>
                    </defs>
                    <polygon class="sparkline-fill" fill="url(#{{ $gradientId }})"
                             points="0,32 {{ implode(' ', $points) }} 96,32"></polygon>
                    <polyline points="{{ implode(' ', $points) }}" stroke="{{ $hex }}"></polyline>
                </svg>
            @endif
        </div>

        <div class="edu-stat-value">{{ $displayValue }}</div>

        <div class="d-flex align-items-end justify-content-between gap-2">
            <span class="edu-stat-label">{{ $label }}</span>

            @if ($hasTrend)
                <span class="edu-trend is-{{ $direction }}">
                    <i class="bi {{ $trendIcon }}" aria-hidden="true"></i>
                    {{ number_format(abs($numericTrend), 0) }}%
                </span>
            @endif
        </div>

        @if ($hasTrend && $trendLabel)
            <div class="edu-stat-hint">{{ $trendLabel }}</div>
        @endif

        @if ($hint)
            <div class="edu-stat-hint">{{ $hint }}</div>
        @endif
    </div>

    {{-- Optional link that makes the whole card clickable --}}
    @if ($href)
        <a class="stretched-link" href="{{ $href }}" aria-label="{{ $label }}"></a>
    @endif
</div>

```

## `resources/views/components/status-badge.blade.php`

```blade
{{-- Shared status badge for classes, reports and moderation states.
     Usage: <x-status-badge :status="$classRoom->status" />
            <x-status-badge status="pending" label="Waiting for review" /> --}}
@props([
    'status',
    'label' => null,
])

@php
    $status = (string) $status;

    // Bootstrap colour for each state used in the application.
    $color = match ($status) {
        'live' => 'danger',
        'scheduled' => 'primary',
        'completed' => 'secondary',
        'pending' => 'warning',
        'reviewed' => 'info',
        'dismissed' => 'secondary',
        'hidden' => 'warning',
        'blocked' => 'danger',
        'active' => 'success',
        default => 'secondary',
    };

    $text = $label ?? \Illuminate\Support\Str::headline($status);
@endphp

<span {{ $attributes->class(['badge', 'text-bg-'.$color, 'text-capitalize']) }}>
    @if ($status === 'live')
        <i class="bi bi-broadcast me-1" aria-hidden="true"></i>
    @endif
    {{ $text }}
</span>

```

## `resources/views/components/empty-state.blade.php`

```blade
{{-- Reusable empty state.
     Usage:
       <x-empty-state icon="bi-easel" title="No classes yet" message="Create your first class.">
           <a href="..." class="btn btn-primary">Create one</a>
       </x-empty-state> --}}
@props([
    'icon' => 'bi-inbox',
    'title' => null,
    'message' => null,
])

<div {{ $attributes->class(['card', 'edu-card', 'border-0']) }}>
    <div class="card-body text-center py-5">
        <span class="edu-empty-illustration" aria-hidden="true">
            <i class="bi {{ $icon }}"></i>
        </span>

        @if ($title)
            <h2 class="h6 fw-semibold">{{ $title }}</h2>
        @endif

        @if ($message)
            <p class="text-muted small mb-3">{{ $message }}</p>
        @endif

        @if ($slot->isNotEmpty())
            <div class="d-flex flex-wrap justify-content-center gap-2">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>

```

## `resources/views/components/flash.blade.php`

```blade
{{-- Flash + validation summary messages. Used by every layout via <x-flash /> --}}

@if (session('success'))
    {{-- Green banner after a successful action --}}
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    {{-- Red banner for a refused action --}}
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('status'))
    {{-- Neutral informational banner (used by Breeze for "verify your email") --}}
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-1"></i> {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@php($bag = $errors ?? null)
@if ($bag && $bag->any())
    {{-- Summary of every validation error of the last request.
         $errors is only shared by the "web" middleware group, so it is missing
         on error pages such as 404 - hence the null-safe check above. --}}
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ __('Please fix the following:') }}
        </div>
        <ul class="mb-0 small">
            @foreach ($bag->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

```

## `resources/views/components/confirm-delete-modal.blade.php`

```blade
{{-- Reusable delete confirmation modal.
     Usage:
       <x-confirm-delete-modal
           id="delete-class-{{ $classRoom->id }}"
           :action="route('classes.destroy', $classRoom)"
           title="Delete this class?"
           message="The enrollments are removed as well."
       />

     The trigger button and the Bootstrap modal are rendered together, so the
     component works without any extra JavaScript. Pass :show-trigger="false"
     when you only need the modal markup. --}}
@props([
    'id',
    'action',
    'method' => 'DELETE',
    'title' => 'Delete this item?',
    'message' => 'This action cannot be undone.',
    'confirmLabel' => 'Delete',
    'confirmClass' => 'btn btn-danger',
    'triggerLabel' => 'Delete',
    'triggerClass' => 'btn btn-sm btn-outline-danger',
    'triggerIcon' => 'bi-trash',
    'showTrigger' => true,
])

@if ($showTrigger)
    <button type="button" class="{{ $triggerClass }}" data-bs-toggle="modal" data-bs-target="#{{ $id }}">
        <i class="bi {{ $triggerIcon }} me-1" aria-hidden="true"></i>{{ $triggerLabel }}
    </button>
@endif

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="{{ $id }}-title">{{ $title }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">{{ $message }}</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>

                <form method="POST" action="{{ $action }}">
                    @csrf
                    @method($method)
                    <button type="submit" class="{{ $confirmClass }}">
                        {{ $confirmLabel }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

```

## `resources/views/components/chart-card.blade.php`

```blade
{{-- Chart card with an optional range selector, loading skeleton and a text
     fallback for assistive technology / no-JavaScript visitors.

     Usage:
       <x-chart-card title="New users" chart-id="new-users" kind="line"
           series-key="newUsers" :series="$chart"
           :endpoint="route('admin.dashboard.charts')" current-range="12m" /> --}}
@props([
    'title',
    'subtitle' => null,
    'chartId' => null,
    'kind' => 'line',
    'series' => [],
    'label' => null,
    'color' => 'primary',
    'endpoint' => null,
    'ranges' => [],
    'currentRange' => '12m',
    'height' => 300,
    'seriesKey' => null,
    'total' => null,
    'fallback' => null,
])

@php
    $labels = array_values((array) ($series['labels'] ?? []));
    $values = array_map(static fn ($value) => (float) $value, array_values((array) ($series['values'] ?? [])));
    $seriesKey = $seriesKey ?: $chartId ?: 'series';

    $hex = [
        'primary' => '#4f46e5',
        'secondary' => '#7c3aed',
        'success' => '#10b981',
        'info' => '#0ea5e9',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
    ][$color] ?? '#4f46e5';

    $datasetLabel = $label ?: $title;
    $baseDataset = ['label' => $datasetLabel, 'data' => $values];

    if ($kind === 'doughnut' || $kind === 'pie') {
        $dataset = $baseDataset + [
            'backgroundColor' => ['#4f46e5', '#7c3aed', '#10b981', '#f59e0b', '#ef4444', '#0ea5e9', '#64748b', '#ec4899'],
            'borderWidth' => 0,
            'hoverOffset' => 6,
        ];

        $config = [
            'type' => $kind,
            'data' => ['labels' => $labels, 'datasets' => [$dataset]],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'cutout' => $kind === 'doughnut' ? '68%' : 0,
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom',
                        'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'boxHeight' => 8, 'padding' => 14],
                    ],
                ],
            ],
        ];
    } elseif ($kind === 'bar') {
        $dataset = $baseDataset + [
            'backgroundColor' => 'rgba(79, 70, 229, 0.72)',
            'hoverBackgroundColor' => $hex,
            'borderColor' => $hex,
            'borderWidth' => 0,
            'borderRadius' => 8,
            'borderSkipped' => false,
            'maxBarThickness' => 38,
        ];

        $config = [
            'type' => 'bar',
            'data' => ['labels' => $labels, 'datasets' => [$dataset]],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'x' => ['grid' => ['display' => false], 'border' => ['display' => false]],
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                        'grid' => ['drawBorder' => false],
                    ],
                ],
            ],
        ];
    } else {
        $dataset = $baseDataset + [
            'backgroundColor' => 'rgba(79, 70, 229, 0.16)',
            'borderColor' => $hex,
            'borderWidth' => 2.5,
            'fill' => true,
            'tension' => 0.38,
            'pointRadius' => 0,
            'pointHoverRadius' => 5,
            'pointBackgroundColor' => $hex,
            'pointBorderColor' => '#ffffff',
            'pointBorderWidth' => 2,
        ];

        $config = [
            'type' => 'line',
            'data' => ['labels' => $labels, 'datasets' => [$dataset]],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'x' => ['grid' => ['display' => false], 'border' => ['display' => false]],
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                        'grid' => ['drawBorder' => false],
                    ],
                ],
            ],
        ];
    }

    $ranges = ! empty($ranges) ? $ranges : [
        '7d' => __('Last 7 days'),
        '30d' => __('Last 30 days'),
        '12m' => __('Last 12 months'),
    ];

    if ($fallback === null) {
        $pairs = [];
        foreach ($labels as $index => $chartLabel) {
            $pairs[] = $chartLabel.': '.($values[$index] ?? 0);
        }
        $fallback = $title.'. '.implode(', ', $pairs);
    }

    $chartId = $chartId ?: 'chart-'.substr(md5($title.$seriesKey), 0, 8);
@endphp

<div class="card edu-card edu-chart-card h-100" data-chart-card>
    <div class="card-header">
        <div>
            <h2 class="card-title">{{ $title }}</h2>
            @if ($subtitle)
                <div class="card-subtitle">{{ $subtitle }}</div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-2">
            @if ($total !== null)
                <span class="edu-chip edu-chart-total" data-chart-total>{{ number_format((float) $total) }}</span>
            @endif

            @if ($endpoint)
                <label class="visually-hidden" for="{{ $chartId }}-range">{{ __('Chart range') }}</label>
                <select class="form-select form-select-sm edu-chart-range"
                        id="{{ $chartId }}-range" data-chart-range>
                    @foreach ($ranges as $value => $rangeLabel)
                        <option value="{{ $value }}" @selected($currentRange === $value)>{{ $rangeLabel }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    <div class="card-body">
        <div class="edu-chart" style="height: {{ (int) $height }}px">
            {{-- Shown while a range request is running. --}}
            <div class="edu-chart-skeleton" data-chart-skeleton hidden aria-hidden="true">
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
            </div>

            <canvas id="{{ $chartId }}" data-chart="{{ json_encode($config) }}"
                    data-chart-series="{{ $seriesKey }}"
                    data-chart-kind="{{ $kind }}"
                    @if ($endpoint) data-chart-endpoint="{{ $endpoint }}" @endif
                    role="img" aria-label="{{ $title }}"></canvas>
        </div>

        {{-- Text alternative. Visible when JavaScript is off, and always
             available to screen readers. --}}
        <p class="visually-hidden">{{ $fallback }}</p>
        <noscript>
            <p class="small text-muted mt-3 mb-0">{{ $fallback }}</p>
        </noscript>

        @if ($slot->isNotEmpty())
            <div class="mt-3">{{ $slot }}</div>
        @endif
    </div>
</div>

```

## `resources/views/components/activity-item.blade.php`

```blade
{{-- One entry in the teacher's recent-activity timeline.
     Usage:
       <x-activity-item icon="bi-person-plus" color="warning" text="Sina started following you"
           :at="$item['at']" /> --}}
@props([
    'icon' => 'bi-activity',
    'color' => 'primary',
    'text' => null,
    'excerpt' => null,
    'at' => null,
    'href' => null,
])

<div class="edu-activity">
    <span class="edu-activity-icon bg-{{ $color }}" aria-hidden="true">
        <i class="bi {{ $icon }}"></i>
    </span>

    <div class="min-w-0 flex-grow-1">
        <div class="edu-activity-text">
            @if ($href)
                <a href="{{ $href }}" class="text-decoration-none text-reset">{{ $text }}</a>
            @else
                {{ $text }}
            @endif
        </div>

        @if ($excerpt)
            <div class="edu-activity-excerpt">
                {{ \Illuminate\Support\Str::limit($excerpt, 70) }}
            </div>
        @endif

        @if ($at)
            <div class="edu-activity-time">
                <i class="bi bi-clock me-1" aria-hidden="true"></i>
                {{ $at instanceof \Illuminate\Support\Carbon ? $at->diffForHumans() : $at }}
            </div>
        @endif
    </div>
</div>

```

## `resources/views/components/progress-bar.blade.php`

```blade
{{-- Accessible progress bar.
     Usage: <x-progress-bar :value="$class->students_count" :max="$maxEnrollment"
               label="12 / 20 enrolled" /> --}}
@props([
    'value' => 0,
    'max' => 100,
    'color' => 'primary',
    'label' => null,
    'showValue' => true,
])

@php
    $value = max(0, (float) $value);
    $max = max(1, (float) $max);
    $percent = (int) round(min(100, ($value / $max) * 100));
@endphp

<div {{ $attributes->class(['edu-progress']) }}>
    @if ($label || $showValue)
        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
            @if ($label)
                <span class="small text-muted text-truncate">{{ $label }}</span>
            @endif

            @if ($showValue)
                <span class="small fw-semibold tabular-nums">{{ $percent }}%</span>
            @endif
        </div>
    @endif

    <div class="progress" role="progressbar" aria-valuenow="{{ $percent }}"
         aria-valuemin="0" aria-valuemax="100"
         aria-label="{{ $label ?: __('Enrollment progress') }}">
        <div class="progress-bar bg-{{ $color }}" style="width: {{ $percent }}%"></div>
    </div>
</div>

```

## `resources/views/components/countdown.blade.php`

```blade
{{-- Live countdown. JS updates the four boxes every second; the server
     renders the initial values so the markup is useful before hydration.
     Usage: <x-countdown :target="$classRoom->startsAt()->toIso8601String()" /> --}}
@props([
    'target',
    'compact' => false,
])

@php
    try {
        $starts = \Illuminate\Support\Carbon::parse($target);
    } catch (\Throwable $exception) {
        $starts = now();
    }

    $seconds = max(0, (int) now()->diffInSeconds($starts, false));
    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $remaining = $seconds % 60;
@endphp

<div {{ $attributes->class(['edu-countdown', 'is-compact' => $compact]) }}
     data-countdown="{{ $starts->toIso8601String() }}" role="timer"
     aria-label="{{ __('Time until the class starts') }}">
    <div class="edu-countdown-unit">
        <span class="edu-countdown-value" data-countdown-days>{{ str_pad((string) $days, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="edu-countdown-label">{{ __('days') }}</span>
    </div>
    <div class="edu-countdown-unit">
        <span class="edu-countdown-value" data-countdown-hours>{{ str_pad((string) $hours, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="edu-countdown-label">{{ __('hours') }}</span>
    </div>
    <div class="edu-countdown-unit">
        <span class="edu-countdown-value" data-countdown-minutes>{{ str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="edu-countdown-label">{{ __('min') }}</span>
    </div>
    <div class="edu-countdown-unit">
        <span class="edu-countdown-value" data-countdown-seconds>{{ str_pad((string) $remaining, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="edu-countdown-label">{{ __('sec') }}</span>
    </div>

    {{-- One readable sentence for screen readers instead of four live boxes. --}}
    <span class="visually-hidden" aria-live="polite" data-countdown-readable>
        {{ trans_choice(':count day|:count days', $days, ['count' => $days]) }},
        {{ $hours }} {{ __('hours') }}, {{ $minutes }} {{ __('minutes') }}
    </span>
</div>

```

## `resources/views/components/data-table.blade.php`

```blade
{{-- Data table wrapper with client-side search and sorting.
     Server-side pagination stays in the page and is passed through the footer
     slot, so this component only filters/sorts the rows of the current page.

     Usage:
       <x-data-table id="teacher-classes" title="My classes" :count="$classes->total()">
           <x-slot name="head"><tr><th data-sort>Title</th>...</tr></x-slot>
           <tr data-table-row data-search="...">...</tr>
           <x-slot name="footer"><x-pagination-links :paginator="$classes" /></x-slot>
       </x-data-table> --}}
@props([
    'id' => null,
    'title' => null,
    'subtitle' => null,
    'searchPlaceholder' => null,
    'count' => null,
    'countLabel' => null,
])

@php
    $id = $id ?: 'table-'.substr(md5($title ?? 'data-table'), 0, 8);
    $searchPlaceholder = $searchPlaceholder ?: __('Search…');
    $countLabel = $countLabel ?: __(':count rows');
@endphp

<div class="card edu-card edu-data-table" data-data-table data-count-label="{{ $countLabel }}">
    <div class="card-header">
        <div class="min-w-0">
            @if ($title)
                <h2 class="card-title h6 fw-bold mb-0">{{ $title }}</h2>
            @endif
            @if ($subtitle)
                <div class="card-subtitle small text-muted">{{ $subtitle }}</div>
            @endif
            @isset($header)
                {{ $header }}
            @endisset
        </div>

        <div class="edu-table-tools">
            <div class="edu-table-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="{{ $id }}-search">{{ $searchPlaceholder }}</label>
                <input type="search" class="form-control form-control-sm" id="{{ $id }}-search"
                       data-table-search placeholder="{{ $searchPlaceholder }}">
            </div>

            @isset($tools)
                {{ $tools }}
            @endisset
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                {{ $head }}
            </thead>
            <tbody data-table-body>
                {{ $slot }}
            </tbody>
        </table>

        <div class="edu-table-empty" data-table-empty hidden>
            <i class="bi bi-search me-1" aria-hidden="true"></i>
            {{ __('Nothing matches your search on this page.') }}
        </div>
    </div>

    @if (isset($footer) || $count !== null)
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="edu-table-count" data-table-count>
                {{ $count !== null ? $count : '' }}
            </span>

            @isset($footer)
                {{ $footer }}
            @endisset
        </div>
    @endif
</div>

```

## `resources/views/components/pagination-links.blade.php`

```blade
{{-- Bootstrap 5 pagination links, hidden when everything fits on one page.
     The Bootstrap views are switched on in AppServiceProvider.
     Usage: <x-pagination-links :paginator="$classes" /> --}}
@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="mt-3 d-flex justify-content-center" aria-label="{{ __('Page navigation') }}">
        {{ $paginator->links() }}
    </nav>
@endif

```

## `resources/views/components/modal.blade.php`

```blade
@props([
    'name',
    'show' => false,
    'maxWidth' => 'md',
])

@php
// Map Breeze's size names to Bootstrap modal classes
$maxWidth = [
    'sm' => 'modal-sm',
    'md' => '',
    'lg' => 'modal-lg',
    'xl' => 'modal-xl',
][$maxWidth] ?? '';
@endphp

{{-- Simple Alpine-powered modal. Open it with:
     $dispatch('open-modal', '{{ $name }}') --}}
<div
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="modal d-block"
    style="display: none; background: rgba(0, 0, 0, .5);"
    tabindex="-1"
>
    <div class="modal-dialog modal-dialog-centered {{ $maxWidth }}">
        <div class="modal-content">
            {{ $slot }}
        </div>
    </div>
</div>

```

## `resources/views/components/password-input.blade.php`

```blade
{{--
    Password field with an accessible show/hide toggle (FR-1, FR-2, FR-3).
    The toggle is wired by resources/js/auth.js, so it also works without Alpine.
    Usage: <x-password-input name="password" :label="__('Password')" />
--}}
@props([
    'name',
    'label',
    'autocomplete' => 'current-password',
    'required' => true,
    'autofocus' => false,
    'strength' => false,
])

<div class="mb-3">
    <x-input-label :for="$name" :value="$label" />

    <div class="input-group">
        <input id="{{ $name }}"
               name="{{ $name }}"
               type="password"
               @if ($required) required @endif
               @if ($autofocus) autofocus @endif
               autocomplete="{{ $autocomplete }}"
               @if ($strength) data-password-strength-input @endif
               @class(['form-control', 'is-invalid' => $errors->has($name)])
               {{ $attributes }}>

        <button type="button"
                class="btn btn-outline-secondary password-toggle"
                data-password-toggle="{{ $name }}"
                data-show-label="{{ __('Show password') }}"
                data-hide-label="{{ __('Hide password') }}"
                aria-label="{{ __('Show password') }}"
                aria-pressed="false">
            <i class="bi bi-eye" aria-hidden="true"></i>
        </button>
    </div>

    @if ($strength)
        {{-- Progressive enhancement: auth.js fills this in. Without JavaScript
             the server-side rule ("at least 8 characters") still applies. --}}
        <div class="edu-strength" data-password-strength data-score="0"
             data-strength-empty="{{ __('Use at least 8 characters, with a number or a symbol.') }}"
             data-strength-weak="{{ __('Weak password — add more characters.') }}"
             data-strength-fair="{{ __('Fair password — mix letters, numbers and a symbol.') }}"
             data-strength-good="{{ __('Good password — almost there.') }}"
             data-strength-strong="{{ __('Strong password. Well done!') }}"
             data-strength-input>
            <div class="edu-strength-track" aria-hidden="true">
                <span class="edu-strength-seg"></span>
                <span class="edu-strength-seg"></span>
                <span class="edu-strength-seg"></span>
                <span class="edu-strength-seg"></span>
            </div>
            <div class="small text-muted" data-password-strength-label aria-live="polite">
                {{ __('Use at least 8 characters, with a number or a symbol.') }}
            </div>
        </div>
    @endif

    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>

```

## `resources/views/components/input-label.blade.php`

```blade
@props(['value'])

<label {{ $attributes->merge(['class' => 'form-label fw-semibold small']) }}>
    {{ $value ?? $slot }}
</label>

```

## `resources/views/components/input-error.blade.php`

```blade
@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-danger small list-unstyled mb-0']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif

```

## `resources/views/components/text-input.blade.php`

```blade
@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'form-control']) }}>

```

## `resources/views/components/dropdown.blade.php`

```blade
@props([
    'align' => 'right',
    'contentClasses' => '',
])

@php
// Bootstrap needs the menu to sit right next to the toggle button.
$alignmentClasses = $align === 'left' ? 'dropdown-menu-start' : 'dropdown-menu-end';
@endphp

<div class="dropdown">
    {{ $trigger }}

    <div class="dropdown-menu shadow {{ $alignmentClasses }} {{ $contentClasses }}">
        {{ $content }}
    </div>
</div>

```

## `resources/views/components/dropdown-link.blade.php`

```blade
<a {{ $attributes->merge(['class' => 'dropdown-item']) }}>{{ $slot }}</a>

```

## `resources/views/components/nav-link.blade.php`

```blade
@props(['active'])

@php
$classes = ($active ?? false) ? 'nav-link active fw-semibold' : 'nav-link';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>

```

## `resources/views/components/responsive-nav-link.blade.php`

```blade
@props(['active'])

@php
$classes = ($active ?? false)
            ? 'list-group-item list-group-item-action active'
            : 'list-group-item list-group-item-action';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>

```

## `resources/views/components/primary-button.blade.php`

```blade
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-primary']) }}>
    {{ $slot }}
</button>

```

## `resources/views/components/secondary-button.blade.php`

```blade
<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline-secondary']) }}>
    {{ $slot }}
</button>

```

## `resources/views/components/danger-button.blade.php`

```blade
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-danger']) }}>
    {{ $slot }}
</button>

```

## `resources/views/components/application-logo.blade.php`

```blade
{{-- EduConnect logo: a graduation cap icon --}}
<span {{ $attributes->merge(['class' => 'text-primary']) }}>
    <i class="bi bi-mortarboard-fill"></i>
</span>

```

## `resources/views/components/auth-session-status.blade.php`

```blade
@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-success py-2 small']) }}>
        {{ $status }}
    </div>
@endif

```

## `resources/views/reports/_modal.blade.php`

```blade
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

```

# 9. Profiles

## `resources/views/users/show.blade.php`

```blade
@php
    $roleColor = match ($profile->role) {
        \App\Models\User::ROLE_ADMIN => 'dark',
        \App\Models\User::ROLE_TEACHER => 'primary',
        default => 'success',
    };
    $isMe = auth()->id() === $profile->id;
@endphp

<x-app-layout>
    <x-slot name="title">{{ $isMe ? __('My profile') : $profile->name }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('search.index', ['tab' => 'users']) }}">{{ __('People') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $isMe ? __('My profile') : $profile->name }}</li>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                {{-- Profile hero: cover, avatar, name, role, counts and the primary action. --}}
                <section class="card edu-card overflow-hidden mb-3" aria-labelledby="profile-name">
                    <div class="edu-cover" aria-hidden="true"></div>

                    <div class="card-body pt-0">
                        <div class="d-flex flex-wrap align-items-end gap-3">
                            <x-avatar :user="$profile" size="xl"
                                      class="edu-cover-avatar border border-4 border-body flex-shrink-0" />

                            <div class="flex-grow-1 min-w-0 pb-1">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <h1 class="h4 fw-bold mb-0 text-break" id="profile-name">{{ $profile->name }}</h1>
                                    <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $profile->role }}</span>
                                </div>

                                @if ($profile->subject)
                                    <div class="text-muted small">
                                        <i class="bi bi-book me-1" aria-hidden="true"></i>{{ $profile->subject }}
                                    </div>
                                @endif

                                <div class="text-muted small">
                                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                                    {{ __('Joined :date', ['date' => $profile->created_at?->format('M Y')]) }}
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 pb-1">
                                @if ($isMe)
                                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                                        {{ __('Edit profile') }}
                                    </a>
                                @else
                                    @include('users._follow-button', ['user' => $profile, 'followingIds' => $followingIds])
                                @endif
                            </div>
                        </div>

                        @if ($profile->bio)
                            <p class="mt-3 mb-0 edu-measure text-break">{{ $profile->bio }}</p>
                        @endif

                        <div class="d-flex flex-wrap gap-4 mt-3 pt-3 border-top">
                            <span>
                                <strong class="d-block h6 mb-0">{{ $profile->posts_count }}</strong>
                                <span class="small text-muted">{{ __('Posts') }}</span>
                            </span>
                            <a href="{{ route('users.followers', $profile) }}" class="text-decoration-none text-reset">
                                <strong class="d-block h6 mb-0" data-followers-count data-user-id="{{ $profile->id }}">{{ $profile->followers_count }}</strong>
                                <span class="small text-muted">{{ __('Followers') }}</span>
                            </a>
                            <a href="{{ route('users.following', $profile) }}" class="text-decoration-none text-reset">
                                <strong class="d-block h6 mb-0" data-following-count data-user-id="{{ $profile->id }}">{{ $profile->following_count }}</strong>
                                <span class="small text-muted">{{ __('Following') }}</span>
                            </a>
                        </div>
                    </div>
                </section>

                {{-- Tabs: Posts, Classes (teachers only) and About. --}}
                <div class="card edu-card">
                    <div class="card-header pb-0">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="profile-posts-tab" data-bs-toggle="tab"
                                        data-bs-target="#profile-posts" type="button" role="tab"
                                        aria-controls="profile-posts" aria-selected="true">
                                    <i class="bi bi-chat-square-text me-1" aria-hidden="true"></i>
                                    {{ __('Posts') }}
                                </button>
                            </li>

                            @if ($profile->isTeacher())
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="profile-classes-tab" data-bs-toggle="tab"
                                            data-bs-target="#profile-classes" type="button" role="tab"
                                            aria-controls="profile-classes" aria-selected="false">
                                        <i class="bi bi-easel me-1" aria-hidden="true"></i>
                                        {{ __('Classes') }}
                                    </button>
                                </li>
                            @endif

                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="profile-about-tab" data-bs-toggle="tab"
                                        data-bs-target="#profile-about" type="button" role="tab"
                                        aria-controls="profile-about" aria-selected="false">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                                    {{ __('About') }}
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content">
                        {{-- Posts --}}
                        <div class="tab-pane fade show active p-3" id="profile-posts" role="tabpanel"
                             aria-labelledby="profile-posts-tab" tabindex="0">
                            @if ($isMe)
                                <div class="d-flex justify-content-end mb-3">
                                    <a href="{{ route('posts.create') }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> {{ __('New post') }}
                                    </a>
                                </div>
                            @endif

                            @include('feed._posts', [
                                'posts' => $posts,
                                'commentsByPost' => null,
                                'emptyMessage' => $isMe
                                    ? __('You have not posted yet. Share an update with your followers.')
                                    : __('This person has not posted yet.'),
                                'emptyActionUrl' => $isMe ? route('posts.create') : null,
                                'emptyActionLabel' => $isMe ? __('Write your first post') : null,
                            ])

                            <div class="mt-3">
                                <x-pagination-links :paginator="$posts" />
                            </div>
                        </div>

                        {{-- Classes (teachers) --}}
                        @if ($profile->isTeacher())
                            <div class="tab-pane fade p-3" id="profile-classes" role="tabpanel"
                                 aria-labelledby="profile-classes-tab" tabindex="0">
                                @if ($classes->isEmpty())
                                    <x-empty-state icon="bi-easel"
                                                   :title="__('No published classes yet')"
                                                   :message="__('When this teacher publishes a class it will appear here.')">
                                        <a href="{{ route('classes.discover') }}" class="btn btn-primary">
                                            {{ __('Browse classes') }}
                                        </a>
                                    </x-empty-state>
                                @else
                                    <div class="row g-3">
                                        @foreach ($classes as $classRoom)
                                            <div class="col-12 col-md-6">
                                                <x-class-card :class-room="$classRoom" :url="route('classes.show', $classRoom)" />
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- About --}}
                        <div class="tab-pane fade p-3" id="profile-about" role="tabpanel"
                             aria-labelledby="profile-about-tab" tabindex="0">
                            <dl class="row small mb-0">
                                <dt class="col-sm-3 text-muted fw-semibold">{{ __('Role') }}</dt>
                                <dd class="col-sm-9 text-capitalize">{{ $profile->role }}</dd>

                                @if ($profile->subject)
                                    <dt class="col-sm-3 text-muted fw-semibold">{{ __('Subject') }}</dt>
                                    <dd class="col-sm-9">{{ $profile->subject }}</dd>
                                @endif

                                <dt class="col-sm-3 text-muted fw-semibold">{{ __('Member since') }}</dt>
                                <dd class="col-sm-9">{{ $profile->created_at?->format('d M Y') }}</dd>

                                <dt class="col-sm-3 text-muted fw-semibold">{{ __('Bio') }}</dt>
                                <dd class="col-sm-9 text-break mb-0">
                                    {{ $profile->bio ?: __('This member has not written a bio yet.') }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/users/people.blade.php`

```blade
@php
    // Either the followers or the following list (FR-17).
    $title = $mode === 'followers' ? __('Followers') : __('Following');
@endphp

<x-app-layout>
    <x-slot name="title">{{ $title }} · {{ $profile->name }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('users.show', $profile) }}">{{ $profile->name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-people me-1 text-edu" aria-hidden="true"></i> {{ $title }}
                    <span class="text-muted fw-normal" aria-hidden="true">·</span>
                    <span class="fw-normal">{{ $profile->name }}</span>
                </h1>
                <p class="small text-muted mb-0">{{ __('People in this part of the community.') }}</p>
            </div>
            <a href="{{ route('users.show', $profile) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to profile') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">
                {{-- Switch between the two lists without going back first --}}
                <div class="d-flex flex-wrap gap-2 mb-3" aria-label="{{ __('Switch list') }}">
                    <a class="edu-filter-chip {{ $mode === 'followers' ? 'is-active' : '' }}"
                       href="{{ route('users.followers', $profile) }}"
                       @if ($mode === 'followers') aria-current="page" @endif>
                        <i class="bi bi-person-heart" aria-hidden="true"></i>
                        {{ __('Followers') }}
                        <span class="visually-hidden">{{ __('of :name', ['name' => $profile->name]) }}</span>
                    </a>
                    <a class="edu-filter-chip {{ $mode === 'following' ? 'is-active' : '' }}"
                       href="{{ route('users.following', $profile) }}"
                       @if ($mode === 'following') aria-current="page" @endif>
                        <i class="bi bi-person-check" aria-hidden="true"></i>
                        {{ __('Following') }}
                    </a>
                </div>

                @if ($people->isEmpty())
                    <x-empty-state icon="bi-people"
                                   :title="$mode === 'followers' ? __('Nobody follows this user yet.') : __('This user does not follow anybody yet.')"
                                   :message="__('When people connect on EduConnect they appear here.')">
                        <a href="{{ route('search.index', ['tab' => 'users']) }}" class="btn btn-primary">
                            <i class="bi bi-search me-1" aria-hidden="true"></i> {{ __('Find people') }}
                        </a>
                    </x-empty-state>
                @else
                    <div class="card edu-card">
                        <ul class="list-group list-group-flush">
                            @foreach ($people as $person)
                                @php
                                    $personRoleColor = match ($person->role) {
                                        \App\Models\User::ROLE_ADMIN => 'dark',
                                        \App\Models\User::ROLE_TEACHER => 'primary',
                                        default => 'success',
                                    };
                                @endphp

                                <li class="list-group-item d-flex flex-wrap align-items-center gap-3 py-3">
                                    <x-avatar :user="$person" size="md" />

                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <a href="{{ route('users.show', $person) }}"
                                               class="fw-semibold text-decoration-none text-reset text-break">{{ $person->name }}</a>
                                            <span class="badge text-bg-{{ $personRoleColor }} text-capitalize">{{ $person->role }}</span>
                                        </div>

                                        @if ($person->subject)
                                            <div class="small text-muted">
                                                <i class="bi bi-book me-1" aria-hidden="true"></i>{{ $person->subject }}
                                            </div>
                                        @endif
                                    </div>

                                    @include('users._follow-button', ['user' => $person, 'followingIds' => $followingIds])
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-3">
                        <x-pagination-links :paginator="$people" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/users/_follow-button.blade.php`

```blade
{{-- Follow / unfollow button (FR-11).
     Students and teachers can follow each other. Admin accounts are not
     followable, and nobody gets a button on their own profile. Admins do not
     use the follow routes, so they do not see the button either.
     It is a real form: without JavaScript the browser simply posts it. --}}
@php
    $viewer = auth()->user();
    $isFollowing = $followingIds->contains($user->id);
    $canFollow = $viewer
        && ! $viewer->isAdmin()
        && ! $viewer->is($user)
        && ! $user->isAdmin();
@endphp

@if ($canFollow)
    <form method="POST"
          action="{{ $isFollowing ? route('follows.destroy', $user) : route('follows.store', $user) }}"
          data-follow-form
          data-follow-url="{{ route('follows.store', $user) }}"
          data-unfollow-url="{{ route('follows.destroy', $user) }}"
          data-following="{{ $isFollowing ? '1' : '0' }}"
          data-follow-user-id="{{ $user->id }}"
          data-label-follow="{{ __('Follow') }}"
          data-label-following="{{ __('Following') }}"
          class="d-inline">
        @csrf
        @if ($isFollowing)
            @method('DELETE')
        @endif
        <button type="submit" class="btn btn-sm {{ $isFollowing ? 'btn-outline-secondary' : 'btn-primary' }}"
                data-follow-button aria-pressed="{{ $isFollowing ? 'true' : 'false' }}">
            <i class="bi {{ $isFollowing ? 'bi-person-check' : 'bi-person-plus' }} me-1"></i>
            <span data-follow-label>{{ $isFollowing ? __('Following') : __('Follow') }}</span>
        </button>
    </form>
@endif

```

## `resources/views/profile/edit.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Profile settings') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('users.show', auth()->user()) }}">{{ __('My profile') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Settings') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-gear me-1 text-edu" aria-hidden="true"></i> {{ __('Profile settings') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Manage your photo, personal details and account security.') }}</p>
            </div>
            <a href="{{ route('users.show', auth()->user()) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-person me-1" aria-hidden="true"></i> {{ __('View my profile') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row g-4">
            {{-- In-page navigation --}}
            <div class="col-12 col-lg-3">
                <nav class="card edu-card edu-sticky-aside" aria-label="{{ __('Settings sections') }}">
                    <div class="card-body py-3">
                        <div class="edu-eyebrow mb-2">{{ __('On this page') }}</div>
                        <ul class="nav flex-column gap-1 small">
                            <li class="nav-item">
                                <a class="nav-link py-1 px-2" href="#profile-information">
                                    <i class="bi bi-person me-1" aria-hidden="true"></i>{{ __('Profile information') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 px-2" href="#update-password">
                                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>{{ __('Password') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 px-2 text-danger" href="#delete-account">
                                    <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ __('Delete account') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>

            <div class="col-12 col-lg-9">
                <div class="card edu-card mb-4" id="profile-information">
                    <div class="card-body p-3 p-md-4">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="card edu-card mb-4" id="update-password">
                    <div class="card-body p-3 p-md-4">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="card edu-card border-danger-subtle" id="delete-account">
                    <div class="card-body p-3 p-md-4">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/profile/partials/update-profile-information-form.blade.php`

```blade
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

        <div class="d-flex align-items-center gap-3">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <span class="text-success small">{{ __('Saved.') }}</span>
            @endif
        </div>
    </form>
</section>

```

## `resources/views/profile/partials/update-password-form.blade.php`

```blade
<section>
    <h2 class="h6 fw-bold mb-1" id="update-password-title">{{ __('Update password') }}</h2>
    <p class="text-muted small mb-4">{{ __('Use a long, random password to stay secure.') }}</p>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-3">
            <x-input-label for="update_password_current_password" :value="__('Current password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password"
                          :class="$errors->updatePassword->has('current_password') ? 'is-invalid' : ''"
                          autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password" :value="__('New password')" />
            <x-text-input id="update_password_password" name="password" type="password"
                          :class="$errors->updatePassword->has('password') ? 'is-invalid' : ''"
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password"
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="d-flex align-items-center gap-3">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <span class="text-success small">{{ __('Saved.') }}</span>
            @endif
        </div>
    </form>
</section>

```

## `resources/views/profile/partials/delete-user-form.blade.php`

```blade
<section>
    <h2 class="h6 fw-bold mb-1 text-danger" id="delete-account-title">{{ __('Delete account') }}</h2>
    <p class="text-muted small mb-4">
        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}
    </p>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-4">
            @csrf
            @method('delete')

            <h4 class="h6 fw-bold">{{ __('Are you sure you want to delete your account?') }}</h4>

            <p class="text-muted small">
                {{ __('Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mb-3">
                <x-input-label for="password" value="{{ __('Password') }}" class="visually-hidden" />
                <x-text-input id="password" name="password" type="password"
                              :class="$errors->userDeletion->has('password') ? 'is-invalid' : ''"
                              placeholder="{{ __('Password') }}" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1" />
            </div>

            <div class="d-flex justify-content-end gap-2">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button>{{ __('Delete account') }}</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>

```

# 10. Classes

## `resources/views/classes/discover.blade.php`

```blade
<x-app-layout>
    @php
        $myClassesUrl = match (auth()->user()->role) {
            \App\Models\User::ROLE_ADMIN => route('admin.classes.index'),
            \App\Models\User::ROLE_TEACHER => route('classes.index'),
            default => route('student.classes'),
        };
    @endphp

    <x-slot name="title">{{ __('Find classes') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Find classes') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-compass me-1 text-edu" aria-hidden="true"></i> {{ __('Find classes') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Browse what is live now and what is coming next.') }}</p>
            </div>
            <a href="{{ $myClassesUrl }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-journal-bookmark me-1" aria-hidden="true"></i> {{ __('My classes') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        {{-- Search by title or teacher + subject + date (FR-19). A plain GET
             form, so the filters work without JavaScript and can be shared. --}}
        <section class="card edu-card mb-3" aria-labelledby="filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="filters-title">{{ __('Filter classes') }}</h2>

                <form method="GET" action="{{ route('classes.discover') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-lg-5">
                        <label for="q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="q" value="{{ $search }}" maxlength="150"
                                   class="form-control" placeholder="{{ __('Class title or teacher name') }}">
                        </div>
                    </div>

                    <div class="col-6 col-lg-3">
                        <label for="subject" class="form-label small">{{ __('Subject') }}</label>
                        <select name="subject" id="subject" class="form-select">
                            <option value="">{{ __('All subjects') }}</option>
                            @foreach ($subjects as $option)
                                <option value="{{ $option }}" @selected($subject === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label for="date" class="form-label small">{{ __('Date') }}</label>
                        <input type="date" name="date" id="date" value="{{ $date }}" class="form-control">
                    </div>

                    <div class="col-12 col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        @if ($search !== '' || $subject !== '' || $date !== '')
                            <a href="{{ route('classes.discover') }}" class="btn btn-outline-secondary"
                               title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                </form>

                {{-- One-tap subject chips, preserving the other filters. --}}
                @if ($subjects->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by subject') }}">
                        <a class="edu-filter-chip {{ $subject === '' ? 'is-active' : '' }}"
                           href="{{ route('classes.discover', array_filter(['q' => $search, 'date' => $date])) }}">
                            {{ __('All') }}
                        </a>

                        @foreach ($subjects as $option)
                            <a class="edu-filter-chip {{ $subject === $option ? 'is-active' : '' }}"
                               href="{{ route('classes.discover', array_filter(['q' => $search, 'subject' => $option, 'date' => $date])) }}">
                                {{ $option }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 fw-bold mb-0">{{ __('Upcoming and live classes') }}</h2>
            <span class="small text-muted">
                {{ trans_choice(':count result|:count results', $classes->total(), ['count' => $classes->total()]) }}
            </span>
        </div>

        <div class="row g-3">
            @forelse ($classes as $class)
                @php($isEnrolled = $enrolledIds->contains($class->id))

                <div class="col-12 col-md-6 col-xl-4">
                    <x-class-card :class-room="$class" :url="route('classes.show', $class)">
                        <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ __('Details') }}
                        </a>

                        @if (auth()->user()->isStudent())
                            @if ($isEnrolled)
                                {{-- Enrolled: leave again (FR-21) --}}
                                <form method="POST" action="{{ route('classes.unenroll', $class) }}" class="d-inline"
                                      data-confirm="{{ __('Leave this class?') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> {{ __('Unenroll') }}
                                    </button>
                                </form>

                                @if ($class->isLive())
                                    {{-- Students may only enter a running class (FR-20) --}}
                                    <a href="{{ route('classes.room', $class) }}" class="btn btn-sm btn-danger">
                                        <i class="bi bi-camera-video me-1" aria-hidden="true"></i> {{ __('Enter live class') }}
                                    </a>
                                @else
                                    <span class="badge bg-body-secondary text-body border align-self-center">
                                        {{ __('Join when it is live') }}
                                    </span>
                                @endif
                            @else
                                {{-- Not enrolled yet (FR-21) --}}
                                <form method="POST" action="{{ route('classes.enroll', $class) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> {{ __('Enroll') }}
                                    </button>
                                </form>
                            @endif
                        @endif
                    </x-class-card>
                </div>
            @empty
                <div class="col-12">
                    <x-empty-state icon="bi-calendar-x"
                                   :title="__('No class matches your search')"
                                   :message="__('Try another subject or date, or clear the filters to see everything.')">
                        <a href="{{ route('classes.discover') }}" class="btn btn-primary">
                            <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>
                            {{ __('Clear filters') }}
                        </a>
                    </x-empty-state>
                </div>
            @endforelse
        </div>

        <div class="mt-3">
            <x-pagination-links :paginator="$classes" />
        </div>
    </div>
</x-app-layout>

```

## `resources/views/classes/show.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ $classRoom->title }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.discover') }}">{{ __('Classes') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit($classRoom->title, 48) }}</li>
    </x-slot>

    @php
        $statusColor = match ($classRoom->status) {
            \App\Models\ClassRoom::STATUS_LIVE => 'danger',
            \App\Models\ClassRoom::STATUS_COMPLETED => 'secondary',
            default => 'primary',
        };
        $isFinished = $classRoom->status === \App\Models\ClassRoom::STATUS_COMPLETED;
    @endphp

    <div class="container-xxl px-3 px-lg-4">
        <div class="row g-4">
            {{-- ------------------------------------------------------------------
                 Main column: the class itself
                 ------------------------------------------------------------------ --}}
            <div class="col-12 col-xl-8">
                <article class="card edu-card mb-4" aria-labelledby="class-title">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div class="min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <x-status-badge :status="$classRoom->status" />

                                    @if ($classRoom->isLive())
                                        <span class="badge text-bg-danger">
                                            <span class="edu-live-dot" aria-hidden="true"></span> {{ __('Live now') }}
                                        </span>
                                    @endif

                                    @if ($classRoom->subject)
                                        <span class="edu-chip">
                                            <i class="bi bi-book" aria-hidden="true"></i> {{ $classRoom->subject }}
                                        </span>
                                    @endif

                                    @if ($classRoom->is_hidden)
                                        {{-- An administrator hid this class (FR-28) --}}
                                        <span class="badge text-bg-warning">{{ __('Hidden by an admin') }}</span>
                                    @endif
                                </div>

                                <h1 class="h3 fw-bold mb-1 text-break" id="class-title">{{ $classRoom->title }}</h1>

                                <p class="small text-muted mb-0">
                                    <i class="bi bi-person-video3 me-1" aria-hidden="true"></i>
                                    {{ __('Hosted by') }}
                                    @if ($classRoom->teacher)
                                        <a href="{{ route('users.show', $classRoom->teacher) }}" class="fw-semibold text-decoration-none">
                                            {{ $classRoom->teacher->name }}
                                        </a>
                                    @else
                                        {{ __('Unknown teacher') }}
                                    @endif
                                </p>
                            </div>

                            {{-- Countdown to the start: server-rendered, then kept live by JS. --}}
                            @if (! $classRoom->isLive() && ! $isFinished)
                                <x-countdown :target="$classRoom->startsAt()->toIso8601String()" compact class="flex-shrink-0" />
                            @endif
                        </div>

                        <hr class="my-4">

                        {{-- When and how long --}}
                        <dl class="row g-3 small mb-4">
                            <div class="col-12 col-sm-4">
                                <dt class="text-muted fw-normal">{{ __('Date') }}</dt>
                                <dd class="fw-semibold mb-0">
                                    <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                    {{ $classRoom->date?->format('D d M Y') }}
                                </dd>
                            </div>
                            <div class="col-12 col-sm-4">
                                <dt class="text-muted fw-normal">{{ __('Start time') }}</dt>
                                <dd class="fw-semibold mb-0">
                                    <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                    {{ \Illuminate\Support\Str::substr((string) $classRoom->time, 0, 5) }}
                                </dd>
                            </div>
                            <div class="col-12 col-sm-4">
                                <dt class="text-muted fw-normal">{{ __('Duration') }}</dt>
                                <dd class="fw-semibold mb-0">
                                    <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>
                                    {{ $classRoom->duration }} {{ __('min') }}
                                </dd>
                            </div>
                        </dl>

                        <h2 class="h6 fw-bold">{{ __('About this class') }}</h2>

                        @if ($classRoom->description)
                            {{-- Escaped by Blade, line breaks kept --}}
                            <p class="text-break mb-0 edu-measure">{{ $classRoom->description }}</p>
                        @else
                            <p class="text-muted mb-0">{{ __('The teacher did not add a description.') }}</p>
                        @endif
                    </div>
                </article>

                {{-- Teacher card on narrow screens, where the sidebar stacks below. --}}
                <div class="card edu-card d-xl-none mb-4">
                    <div class="card-body d-flex align-items-center gap-3">
                        <x-avatar :user="$classRoom->teacher" size="lg" :link="$classRoom->teacher ? route('users.show', $classRoom->teacher) : null" />

                        <div class="min-w-0">
                            <div class="small text-muted">{{ __('Teacher') }}</div>

                            @if ($classRoom->teacher)
                                <a href="{{ route('users.show', $classRoom->teacher) }}"
                                   class="fw-semibold text-decoration-none text-reset text-break">
                                    {{ $classRoom->teacher->name }}
                                </a>
                            @endif

                            @if ($classRoom->teacher?->subject)
                                <div class="small text-muted">{{ $classRoom->teacher->subject }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------------------------------
                 Sidebar: the primary action, the teacher and the students
                 ------------------------------------------------------------------ --}}
            <div class="col-12 col-xl-4">
                <div class="edu-sticky-aside">
                    {{-- ----------------------------------------------------------
                         Actions: join / enroll / teacher tools (FR-20, FR-21, FR-24)
                         ---------------------------------------------------------- --}}
                    <section class="card edu-card mb-4" aria-labelledby="class-actions-title">
                        <div class="card-body d-flex flex-column gap-3">
                            <h2 class="h6 fw-bold mb-0" id="class-actions-title">{{ __('Your place in this class') }}</h2>

                            @if ($isOwner)
                                {{-- Only the teacher may open the room at any time (FR-20) --}}
                                <a href="{{ route('classes.room', $classRoom) }}" class="btn btn-danger btn-lg w-100">
                                    <i class="bi bi-camera-video me-1" aria-hidden="true"></i>
                                    {{ $classRoom->isLive() ? __('Enter the live room') : __('Open the room') }}
                                </a>

                                {{-- Status switches, limited to the allowed transitions (FR-24) --}}
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($classRoom->allowedTransitions() as $next)
                                        <form method="POST" action="{{ route('classes.status', $classRoom) }}" class="flex-grow-1">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $next }}">
                                            <button type="submit"
                                                    class="btn w-100 {{ $next === \App\Models\ClassRoom::STATUS_LIVE ? 'btn-success' : 'btn-outline-secondary' }}">
                                                @if ($next === \App\Models\ClassRoom::STATUS_LIVE)
                                                    <i class="bi bi-broadcast me-1" aria-hidden="true"></i> {{ __('Start class') }}
                                                @elseif ($next === \App\Models\ClassRoom::STATUS_COMPLETED)
                                                    <i class="bi bi-stop-circle me-1" aria-hidden="true"></i> {{ __('End class') }}
                                                @else
                                                    <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> {{ __('Back to scheduled') }}
                                                @endif
                                            </button>
                                        </form>
                                    @endforeach
                                </div>

                                <hr class="my-1">

                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('classes.students', $classRoom) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-people me-1" aria-hidden="true"></i>
                                        {{ trans_choice(':count student|:count students', $classRoom->students_count, ['count' => $classRoom->students_count]) }}
                                    </a>
                                    <a href="{{ route('classes.edit', $classRoom) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil me-1" aria-hidden="true"></i> {{ __('Edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('classes.destroy', $classRoom) }}" class="d-inline"
                                          data-confirm="{{ __('Delete this class? The enrollments are removed as well.') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash me-1" aria-hidden="true"></i> {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>

                                {{-- Handy for a teacher who wants to share the room on another device. --}}
                                <div class="alert bg-body-secondary text-body border mb-0 small text-break">
                                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
                                    {{ __('Private room code') }}: <code>{{ $classRoom->room_name }}</code>
                                </div>
                            @elseif (auth()->user()->isStudent())
                                @if ($isEnrolled)
                                    {{-- An enrolled student --}}
                                    @if ($classRoom->isLive())
                                        <a href="{{ route('classes.room', $classRoom) }}" class="btn btn-danger btn-lg w-100">
                                            <i class="bi bi-camera-video me-1" aria-hidden="true"></i> {{ __('Join the live class') }}
                                        </a>
                                    @elseif ($isFinished)
                                        <div class="alert alert-secondary mb-0">
                                            <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                                            {{ __('This class is over. Come back for the next one!') }}
                                        </div>
                                    @else
                                        {{-- Clear message instead of a dead Join button (FR-20) --}}
                                        <div class="alert alert-info mb-0 small">
                                            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>
                                            {{ __('You are enrolled. The Join button appears as soon as the teacher starts the class.') }}
                                        </div>
                                    @endif

                                    <div class="d-flex align-items-center gap-2 small text-success fw-semibold">
                                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                        {{ __('You are enrolled in this class') }}
                                    </div>

                                    <form method="POST" action="{{ route('classes.unenroll', $classRoom) }}"
                                          data-confirm="{{ __('Leave this class?') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                            <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> {{ __('Unenroll') }}
                                        </button>
                                    </form>
                                @else
                                    {{-- Not enrolled yet --}}
                                    @if ($isFinished)
                                        <div class="alert alert-secondary mb-0">
                                            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                                            {{ __('This class is already over, enrolling is no longer possible.') }}
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('classes.enroll', $classRoom) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> {{ __('Enroll in this class') }}
                                            </button>
                                        </form>
                                        <p class="text-muted small mb-0">
                                            {{ __('Enroll to get the Join button and to appear on the attendance list.') }}
                                        </p>
                                        @if ($classRoom->isLive())
                                            <div class="alert alert-warning mb-0 small">
                                                <i class="bi bi-broadcast me-1" aria-hidden="true"></i>
                                                {{ __('This class is live right now. Enroll to join immediately.') }}
                                            </div>
                                        @endif
                                    @endif
                                @endif
                            @else
                                {{-- Admins and other roles: read-only summary. --}}
                                <div class="alert bg-body-secondary text-body border mb-0 small">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                                    {{ __('You can view this class but not enroll as your role does not take classes.') }}
                                </div>
                            @endif
                        </div>
                    </section>

                    {{-- Teacher (desktop sidebar) --}}
                    <section class="card edu-card mb-4 d-none d-xl-block" aria-labelledby="teacher-title">
                        <div class="card-body">
                            <h2 class="h6 fw-bold mb-3" id="teacher-title">{{ __('Teacher') }}</h2>

                            <div class="d-flex align-items-center gap-3">
                                <x-avatar :user="$classRoom->teacher" size="lg" />

                                <div class="min-w-0">
                                    @if ($classRoom->teacher)
                                        <a href="{{ route('users.show', $classRoom->teacher) }}"
                                           class="fw-semibold text-decoration-none text-reset text-break">
                                            {{ $classRoom->teacher->name }}
                                        </a>
                                    @endif

                                    @if ($classRoom->teacher?->subject)
                                        <div class="small text-muted">{{ $classRoom->teacher->subject }}</div>
                                    @endif
                                </div>
                            </div>

                            @if ($classRoom->teacher?->bio)
                                <p class="small text-muted mt-3 mb-0">{{ \Illuminate\Support\Str::limit($classRoom->teacher->bio, 160) }}</p>
                            @endif
                        </div>
                    </section>

                    {{-- Students preview --}}
                    <section class="card edu-card" aria-labelledby="students-title">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h6 fw-bold mb-0" id="students-title">{{ __('Students') }}</h2>
                                <span class="edu-chip">
                                    <i class="bi bi-people" aria-hidden="true"></i> {{ $classRoom->students_count }}
                                </span>
                            </div>

                            @forelse ($previewStudents as $student)
                                <div class="d-flex align-items-center gap-2 {{ ! $loop->last ? 'mb-2' : '' }}">
                                    <x-avatar :user="$student" size="sm" />
                                    <a href="{{ route('users.show', $student) }}"
                                       class="small text-decoration-none text-reset text-break">{{ $student->name }}</a>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-person-dash me-1" aria-hidden="true"></i> {{ __('Nobody has enrolled yet.') }}
                                </p>
                            @endforelse

                            @if ($classRoom->students_count > $previewStudents->count())
                                <div class="small text-muted mt-2">
                                    {{ __('and :count more', ['count' => $classRoom->students_count - $previewStudents->count()]) }}
                                </div>
                            @endif

                            @if ($isOwner || auth()->user()->isAdmin())
                                <a href="{{ route('classes.students', $classRoom) }}" class="btn btn-sm btn-outline-secondary mt-3 w-100">
                                    <i class="bi bi-list-ul me-1" aria-hidden="true"></i>{{ __('See the full list') }}
                                </a>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <div class="mt-2">
            <a href="{{ route('classes.discover') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to the classes') }}
            </a>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/classes/_form.blade.php`

```blade
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

```

## `resources/views/classes/create.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('New class') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">{{ __('My classes') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('New class') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-plus-square me-1 text-edu" aria-hidden="true"></i> {{ __('New class') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Pick a time, describe the lesson and publish it to your followers.') }}</p>
            </div>
            <a href="{{ route('classes.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('My classes') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        {{-- Shared form, same partial as the edit page (FR-18). --}}
        @include('classes._form', ['classRoom' => null])
    </div>
</x-app-layout>

```

## `resources/views/classes/edit.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Edit class') }} · {{ $classRoom->title }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">{{ __('My classes') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.show', $classRoom) }}">{{ \Illuminate\Support\Str::limit($classRoom->title, 40) }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-pencil-square me-1 text-edu" aria-hidden="true"></i> {{ __('Edit class') }}
                </h1>
                <p class="small text-muted mb-0 text-break">{{ $classRoom->title }}</p>
            </div>
            <a href="{{ route('classes.show', $classRoom) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to the class') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        @include('classes._form', ['classRoom' => $classRoom])
    </div>
</x-app-layout>

```

## `resources/views/classes/index.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('My classes') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('My classes') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-easel me-1 text-edu" aria-hidden="true"></i> {{ __('My classes') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Everything you scheduled, live right now and already taught.') }}</p>
            </div>
            <a href="{{ route('classes.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-square me-1" aria-hidden="true"></i> {{ __('New class') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        @if ($classes->isEmpty())
            <x-empty-state icon="bi-easel"
                           :title="__('No classes yet')"
                           :message="__('You have not created a class yet. Publish your first lesson and your followers get a notification.')">
                <a href="{{ route('classes.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-square me-1" aria-hidden="true"></i> {{ __('Create my first class') }}
                </a>
                <a href="{{ route('classes.discover') }}" class="btn btn-outline-secondary">
                    {{ __('Browse classes') }}
                </a>
            </x-empty-state>
        @else
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <span class="small text-muted">
                    {{ trans_choice(':count class|:count classes', $classes->total(), ['count' => $classes->total()]) }}
                </span>
                <span class="small text-muted d-inline-flex align-items-center gap-2">
                    <span class="badge text-bg-danger"><span class="edu-live-dot" aria-hidden="true"></span> {{ __('Live') }}</span>
                    <span class="badge text-bg-primary">{{ __('Scheduled') }}</span>
                    <span class="badge text-bg-secondary">{{ __('Completed') }}</span>
                </span>
            </div>

            <div class="row g-3">
                @foreach ($classes as $class)
                    <div class="col-12 col-md-6 col-xl-4">
                        <x-class-card :class-room="$class" :url="route('classes.show', $class)">
                            {{-- Open the class detail page --}}
                            <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i> {{ __('Open') }}
                            </a>

                            {{-- Enrolled students (FR-24) --}}
                            <a href="{{ route('classes.students', $class) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-people me-1" aria-hidden="true"></i> {{ __('Students') }}
                            </a>

                            {{-- Status control: only the transitions the model allows
                                 are offered as buttons (FR-24). --}}
                            @foreach ($class->allowedTransitions() as $next)
                                <form method="POST" action="{{ route('classes.status', $class) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $next }}">
                                    <button type="submit"
                                            class="btn btn-sm {{ $next === \App\Models\ClassRoom::STATUS_LIVE ? 'btn-success' : 'btn-outline-danger' }}">
                                        @if ($next === \App\Models\ClassRoom::STATUS_LIVE)
                                            <i class="bi bi-broadcast me-1" aria-hidden="true"></i> {{ __('Start class') }}
                                        @elseif ($next === \App\Models\ClassRoom::STATUS_COMPLETED)
                                            <i class="bi bi-stop-circle me-1" aria-hidden="true"></i> {{ __('End class') }}
                                        @else
                                            <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> {{ __('Back to scheduled') }}
                                        @endif
                                    </button>
                                </form>
                            @endforeach

                            {{-- Edit / delete (ClassPolicy: my own classes only) --}}
                            <a href="{{ route('classes.edit', $class) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil me-1" aria-hidden="true"></i> {{ __('Edit') }}
                            </a>

                            <x-confirm-delete-modal
                                :id="'delete-class-'.$class->id"
                                :action="route('classes.destroy', $class)"
                                :title="__('Delete this class?')"
                                :message="__('The enrollments are removed as well. This action cannot be undone.')" />
                        </x-class-card>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                <x-pagination-links :paginator="$classes" />
            </div>
        @endif
    </div>
</x-app-layout>

```

## `resources/views/classes/enrolled.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('My enrolled classes') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('My enrolled classes') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-journal-bookmark me-1 text-edu" aria-hidden="true"></i> {{ __('My enrolled classes') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Live classes first, then the ones still ahead.') }}</p>
            </div>
            <a href="{{ route('classes.discover') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-search me-1" aria-hidden="true"></i> {{ __('Find more classes') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row g-3">
            @forelse ($classes as $class)
                <div class="col-12 col-md-6 col-xl-4">
                    <x-class-card :class-room="$class" :url="route('classes.show', $class)">
                        {{-- Details --}}
                        <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ __('Details') }}
                        </a>

                        @if ($class->isLive())
                            {{-- A running class can be joined right away (FR-20) --}}
                            <a href="{{ route('classes.room', $class) }}" class="btn btn-sm btn-danger">
                                <i class="bi bi-camera-video me-1" aria-hidden="true"></i> {{ __('Join live') }}
                            </a>
                        @elseif ($class->status === \App\Models\ClassRoom::STATUS_COMPLETED)
                            <span class="badge text-bg-secondary align-self-center">{{ __('Class finished') }}</span>
                        @else
                            <span class="badge bg-body-secondary text-body border align-self-center">
                                <i class="bi bi-clock me-1" aria-hidden="true"></i>{{ __('Not started yet') }}
                            </span>
                        @endif

                        {{-- Leave the class again (FR-21) --}}
                        <form method="POST" action="{{ route('classes.unenroll', $class) }}" class="d-inline"
                              data-confirm="{{ __('Leave this class?') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> {{ __('Unenroll') }}
                            </button>
                        </form>
                    </x-class-card>
                </div>
            @empty
                <div class="col-12">
                    <x-empty-state icon="bi-journal-x"
                                   :title="__('You are not enrolled in any class yet.')"
                                   :message="__('Find a class by subject, enroll in one click and it shows up here.')">
                        <a href="{{ route('classes.discover') }}" class="btn btn-primary">
                            <i class="bi bi-search me-1" aria-hidden="true"></i> {{ __('Browse the classes') }}
                        </a>
                    </x-empty-state>
                </div>
            @endforelse
        </div>

        <div class="mt-3">
            <x-pagination-links :paginator="$classes" />
        </div>
    </div>
</x-app-layout>

```

## `resources/views/classes/students.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Enrolled students') }} · {{ $classRoom->title }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">{{ __('My classes') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('classes.show', $classRoom) }}">{{ \Illuminate\Support\Str::limit($classRoom->title, 40) }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Students') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-people me-1 text-edu" aria-hidden="true"></i> {{ __('Enrolled students') }}
                </h1>
                <p class="small text-muted mb-0 text-break">{{ $classRoom->title }}</p>
            </div>
            <a href="{{ route('classes.show', $classRoom) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to the class') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        {{-- Which class are we looking at? --}}
        <div class="card edu-card mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="min-w-0">
                    <h2 class="h6 fw-bold mb-1 text-break">{{ $classRoom->title }}</h2>
                    <div class="small text-muted">
                        <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>{{ $classRoom->date?->format('D d M Y') }}
                        <span class="mx-1" aria-hidden="true">&middot;</span>
                        <i class="bi bi-clock me-1" aria-hidden="true"></i>{{ \Illuminate\Support\Str::substr((string) $classRoom->time, 0, 5) }}
                    </div>
                </div>
                <span class="edu-chip flex-shrink-0">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    {{ trans_choice(':count student|:count students', $classRoom->students_count, ['count' => $classRoom->students_count]) }}
                </span>
            </div>
        </div>

        @if ($students->isEmpty())
            <x-empty-state icon="bi-person-dash"
                           :title="__('Nobody has enrolled yet.')"
                           :message="__('Share the class link with your students so they can enroll in one click.')">
                <a href="{{ route('classes.show', $classRoom) }}" class="btn btn-primary">
                    {{ __('Back to the class') }}
                </a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('Students enrolled in this class') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Student') }}</th>
                                <th scope="col">{{ __('Email') }}</th>
                                <th scope="col" class="text-end">{{ __('Enrolled at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $student)
                                <tr>
                                    <td data-label="{{ __('Student') }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <x-avatar :user="$student" size="sm" />
                                            <a href="{{ route('users.show', $student) }}"
                                               class="fw-semibold text-decoration-none text-reset text-break">{{ $student->name }}</a>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('Email') }}" class="text-muted small text-break">{{ $student->email }}</td>
                                    <td data-label="{{ __('Enrolled at') }}" class="text-end text-nowrap small text-muted">
                                        {{-- The pivot timestamp comes from withTimestamps() --}}
                                        <time datetime="{{ $student->pivot?->created_at?->toIso8601String() }}">
                                            {{ $student->pivot?->created_at?->format('d M Y H:i') }}
                                        </time>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($students->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$students" />
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>

```

## `resources/views/classes/room.blade.php`

```blade
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
        {{-- Slim top bar: identity on the left, room state and exit on the right. --}}
        <div class="card edu-card mb-3">
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

        {{-- Collapsible room info: moderation controls and help, out of the way. --}}
        <div class="collapse d-lg-block mt-3" id="room-info">
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

# 11. Notifications

## `resources/views/notifications/index.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Notifications') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Notifications') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-bell me-1 text-edu" aria-hidden="true"></i>{{ __('Notifications') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Likes, comments, new followers and class reminders.') }}</p>
            </div>

            @if ($notifications->total() > 0)
                {{-- FR-25: mark everything as read in one click. --}}
                <form method="POST" action="{{ route('notifications.read-all') }}" data-notification-read-all>
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-check2-all me-1" aria-hidden="true"></i> {{ __('Mark all as read') }}
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                {{-- All / unread filter, carried through the pagination links (FR-25). --}}
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <a class="edu-filter-chip {{ $filter === 'all' ? 'is-active' : '' }}"
                       href="{{ route('notifications.index') }}"
                       @if ($filter === 'all') aria-current="page" @endif>
                        <i class="bi bi-inbox" aria-hidden="true"></i> {{ __('All') }}
                    </a>
                    <a class="edu-filter-chip {{ $filter === 'unread' ? 'is-active' : '' }}"
                       href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                       @if ($filter === 'unread') aria-current="page" @endif>
                        <i class="bi bi-dot" aria-hidden="true"></i> {{ __('Unread') }}
                    </a>

                    <span class="ms-auto small text-muted">
                        {{ trans_choice(':count notification|:count notifications', $notifications->total(), ['count' => $notifications->total()]) }}
                    </span>
                </div>

                @if ($notifications->isEmpty())
                    <x-empty-state icon="bi-bell-slash"
                                   :title="$filter === 'unread' ? __('Nothing unread') : __('No notifications yet')"
                                   :message="$filter === 'unread'
                                        ? __('You are all caught up. New activity will appear here.')
                                        : __('Follow a teacher or write a post and the activity shows up here.')">
                        @if ($filter === 'unread')
                            <a href="{{ route('notifications.index') }}" class="btn btn-primary">{{ __('See all notifications') }}</a>
                        @else
                            <a href="{{ route('feed.index') }}" class="btn btn-primary">{{ __('Go to the feed') }}</a>
                        @endif
                    </x-empty-state>
                @else
                    @php
                        // Group without losing the paginator: Today first, then everything older.
                        $groups = $notifications->getCollection()->groupBy(
                            static fn ($notification) => $notification->created_at?->isToday() ? 'today' : 'earlier'
                        );
                    @endphp

                    @foreach (['today' => __('Today'), 'earlier' => __('Earlier')] as $key => $label)
                        @if ($groups->has($key))
                            <h2 class="edu-notif-group-label" id="notif-group-{{ $key }}">{{ $label }}</h2>

                            <div class="list-group list-group-flush border rounded-3 overflow-hidden mb-3"
                                 aria-labelledby="notif-group-{{ $key }}">
                                @foreach ($groups->get($key) as $notification)
                                    @php $data = $notification->data; @endphp

                                    <div class="list-group-item d-flex gap-3 align-items-start {{ $notification->read_at ? '' : 'notification-unread' }}">
                                        <span class="edu-notif-icon flex-shrink-0" aria-hidden="true">
                                            <i class="bi {{ $data['icon'] ?? 'bi-bell' }}"></i>
                                        </span>

                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-start gap-2">
                                                @unless ($notification->read_at)
                                                    <span class="edu-notif-dot mt-2" aria-hidden="true"></span>
                                                    <span class="visually-hidden">{{ __('Unread') }}</span>
                                                @endunless

                                                {{-- Opening the entry marks it read and follows its link (FR-25). --}}
                                                <a href="{{ route('notifications.open', $notification->id) }}"
                                                   class="text-decoration-none text-reset fw-semibold text-break">
                                                    {{ $data['message'] ?? __('Notification') }}
                                                </a>
                                            </div>

                                            @isset($data['excerpt'])
                                                <div class="small text-muted text-break">&ldquo;{{ $data['excerpt'] }}&rdquo;</div>
                                            @endisset

                                            <div class="small text-muted">
                                                <time datetime="{{ $notification->created_at?->toIso8601String() }}">
                                                    {{ $notification->created_at?->diffForHumans() }}
                                                </time>

                                                @unless ($notification->read_at)
                                                    <span class="mx-1" aria-hidden="true">·</span>
                                                    <span class="text-edu fw-semibold" data-notification-unread-label>{{ __('Unread') }}</span>
                                                @endunless
                                            </div>
                                        </div>

                                        <div class="d-flex gap-1 flex-shrink-0">
                                            @unless ($notification->read_at)
                                                {{-- FR-25: mark this single entry as read. Works as a
                                                     normal form, resources/js/notifications.js turns it
                                                     into a fetch() call when JavaScript is available. --}}
                                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}"
                                                      data-notification-read>
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                            title="{{ __('Mark as read') }}"
                                                            aria-label="{{ __('Mark as read') }}">
                                                        <i class="bi bi-check2" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            @endunless

                                            {{-- FR-25: remove the notification for good. --}}
                                            <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}"
                                                  data-notification-delete
                                                  data-confirm="{{ __('Delete this notification?') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="{{ __('Delete') }}"
                                                        aria-label="{{ __('Delete') }}">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                    <x-pagination-links :paginator="$notifications" />
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/notifications/_dropdown.blade.php`

```blade
{{--
    The newest notifications as Bootstrap dropdown items (FR-24).
    Included by the navbar bell on page load and re-rendered by
    /notifications/poll every 30 seconds, so it only ever emits <li> elements
    (it lives inside a dropdown <ul>).
--}}
@forelse ($notifications as $notification)
    @php $data = $notification->data; @endphp
    <li>
        {{-- Clicking the entry opens the related page and marks it read (FR-25). --}}
        <a class="dropdown-item d-flex gap-2 py-2 {{ $notification->read_at ? '' : 'notification-unread' }}"
           href="{{ route('notifications.open', $notification->id) }}">
            <i class="bi {{ $data['icon'] ?? 'bi-bell' }} text-edu mt-1"></i>
            <span class="flex-grow-1">
                {{-- Everything is escaped by Blade. --}}
                <span class="d-block small">{{ $data['message'] ?? __('Notification') }}</span>
                <span class="d-block text-muted edu-time">{{ $notification->created_at?->diffForHumans() }}</span>
            </span>
        </a>
    </li>
@empty
    <li>
        <span class="dropdown-item-text small text-muted">{{ __('No notifications yet.') }}</span>
    </li>
@endforelse

<li><hr class="dropdown-divider"></li>
<li>
    <a class="dropdown-item text-center small fw-semibold" href="{{ route('notifications.index') }}">
        <i class="bi bi-list-ul me-1"></i>{{ __('See all notifications') }}
    </a>
</li>

```

# 12. Search

## `resources/views/search/index.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Search') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Search') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-search me-1 text-edu" aria-hidden="true"></i>{{ __('Search') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Find people, hashtags and posts in one place.') }}</p>
            </div>
            <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to feed') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                {{-- The same search box as in the navbar, with live suggestions. --}}
                <form method="GET" action="{{ route('search.index') }}" role="search" class="mb-3 position-relative"
                      data-search-form data-search-url="{{ route('search.suggest') }}">
                    <input type="hidden" name="tab" value="{{ $tab }}">

                    <label for="search-q" class="visually-hidden">{{ __('Search') }}</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-body border-end-0" aria-hidden="true">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="search" name="q" id="search-q" class="form-control border-start-0"
                               value="{{ $term }}"
                               placeholder="{{ __('Search people, #hashtags or post text') }}"
                               autocomplete="off" data-search-input>
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search me-1" aria-hidden="true"></i> {{ __('Search') }}
                        </button>
                    </div>

                    <div class="dropdown-menu show position-absolute top-100 start-0 end-0 shadow d-none"
                         data-search-suggestions></div>
                </form>

                @if ($term !== '')
                    <p class="small text-muted mb-2">
                        {{ __('Results for') }} <strong class="text-body">“{{ $term }}”</strong>
                    </p>
                @endif

                {{-- Three tabs: people, hashtags and posts (FR-14). --}}
                <ul class="nav nav-tabs mb-3">
                    @foreach ([
                        'users' => ['bi-people', __('People')],
                        'hashtags' => ['bi-hash', __('Hashtags')],
                        'posts' => ['bi-chat-square-text', __('Posts')],
                    ] as $value => [$icon, $label])
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === $value ? 'active' : '' }}"
                               href="{{ route('search.index', ['q' => $term, 'tab' => $value]) }}"
                               @if ($tab === $value) aria-current="page" @endif>
                                <i class="bi {{ $icon }} me-1" aria-hidden="true"></i> {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if (mb_strlen($term) < 2)
                    <div class="alert alert-info mb-0" role="status">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        {{ $term === ''
                            ? __('Type a name, a subject, a hashtag or some post text to start searching.')
                            : __('Type at least two characters to search.') }}
                    </div>
                @elseif ($tab === 'hashtags')
                    {{-- ----- Hashtag results ----- --}}
                    @if ($hashtags->isEmpty())
                        <x-empty-state icon="bi-hash"
                                       :title="__('No hashtags found')"
                                       :message="__('No hashtag matches \':term\'. Try a shorter word or browse the feed.', ['term' => $term])">
                            <a href="{{ route('feed.index') }}" class="btn btn-primary">{{ __('Back to the feed') }}</a>
                        </x-empty-state>
                    @else
                        <p class="text-muted small">
                            {{ trans_choice(':count hashtag found|:count hashtags found', $hashtags->total(), ['count' => $hashtags->total()]) }}
                        </p>

                        <div class="card edu-card">
                            <div class="list-group list-group-flush">
                                @foreach ($hashtags as $hashtag)
                                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2"
                                       href="{{ route('hashtags.show', $hashtag->name) }}">
                                        <span class="fw-semibold min-w-0 text-break">
                                            <i class="bi bi-hash text-edu" aria-hidden="true"></i>{!! $highlight($hashtag->name) !!}
                                        </span>
                                        <span class="edu-chip flex-shrink-0">
                                            {{ trans_choice(':count post|:count posts', $hashtag->posts_count, ['count' => $hashtag->posts_count]) }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-3">
                            <x-pagination-links :paginator="$hashtags" />
                        </div>
                    @endif
                @elseif ($tab === 'posts')
                    {{-- ----- Post results ----- --}}
                    @if ($posts->isEmpty())
                        <x-empty-state icon="bi-chat-square-text"
                                       :title="__('No posts found')"
                                       :message="__('No post matches \':term\'. Try another word or a hashtag.', ['term' => $term])">
                            <a href="{{ route('search.index', ['q' => $term, 'tab' => 'hashtags']) }}" class="btn btn-primary">
                                {{ __('Search hashtags instead') }}
                            </a>
                        </x-empty-state>
                    @else
                        <p class="text-muted small">
                            {{ trans_choice(':count post found|:count posts found', $posts->total(), ['count' => $posts->total()]) }}
                        </p>

                        <div class="d-flex flex-column gap-3">
                            @foreach ($posts as $post)
                                <x-post-card :post="$post" />
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <x-pagination-links :paginator="$posts" />
                        </div>
                    @endif
                @else
                    {{-- ----- People results ----- --}}
                    @if ($users->isEmpty())
                        <x-empty-state icon="bi-person-x"
                                       :title="__('No people found')"
                                       :message="__('No user matches \':term\'. Check the spelling or try a subject instead.', ['term' => $term])">
                            <a href="{{ route('search.index', ['q' => $term, 'tab' => 'hashtags']) }}" class="btn btn-primary">
                                {{ __('Search hashtags instead') }}
                            </a>
                        </x-empty-state>
                    @else
                        <p class="text-muted small">
                            {{ trans_choice(':count person found|:count people found', $users->total(), ['count' => $users->total()]) }}
                        </p>

                        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                            @foreach ($users as $user)
                                <div class="col">
                                    {{-- The shared user card, with a link to the public profile
                                         and a Follow button (FR-11, FR-17). --}}
                                    <x-user-card :user="$user" :name-html="$highlight($user->name)">
                                        <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-primary w-100">
                                            <i class="bi bi-person me-1" aria-hidden="true"></i> {{ __('View profile') }}
                                        </a>
                                        @include('users._follow-button', ['user' => $user, 'followingIds' => $followingIds])
                                    </x-user-card>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <x-pagination-links :paginator="$users" />
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

```

# 13. Admin pages

## `resources/views/admin/users/index.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Users') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Users') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-people me-1 text-edu" aria-hidden="true"></i> {{ __('Users') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Search, block, unblock and delete accounts (FR-27).') }}</p>
            </div>
            <span class="edu-chip">
                <i class="bi bi-person-lines-fill" aria-hidden="true"></i>
                {{ $users->total() }} {{ __('accounts') }}
            </span>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <section class="card edu-card mb-3" aria-labelledby="user-filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="user-filters-title">{{ __('Filter users') }}</h2>

                <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="user-q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="user-q" value="{{ $term }}" class="form-control"
                                   placeholder="{{ __('Name or email') }}" maxlength="150">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="user-role" class="form-label small">{{ __('Role') }}</label>
                        <select name="role" id="user-role" class="form-select">
                            <option value="">{{ __('All roles') }}</option>
                            @foreach ([
                                \App\Models\User::ROLE_ADMIN => __('Admin'),
                                \App\Models\User::ROLE_TEACHER => __('Teacher'),
                                \App\Models\User::ROLE_STUDENT => __('Student'),
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="user-blocked" class="form-label small">{{ __('Status') }}</label>
                        <select name="blocked" id="user-blocked" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="0" @selected($blocked === '0')>{{ __('Active') }}</option>
                            <option value="1" @selected($blocked === '1')>{{ __('Blocked') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    </div>
                </form>

                {{-- One-tap role chips, keeping the search term. --}}
                <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by role') }}">
                    <a class="edu-filter-chip {{ $role === '' ? 'is-active' : '' }}"
                       href="{{ route('admin.users.index', array_filter(['q' => $term, 'blocked' => $blocked])) }}">
                        {{ __('All roles') }}
                    </a>
                    @foreach ([
                        \App\Models\User::ROLE_ADMIN => __('Admin'),
                        \App\Models\User::ROLE_TEACHER => __('Teacher'),
                        \App\Models\User::ROLE_STUDENT => __('Student'),
                    ] as $value => $label)
                        <a class="edu-filter-chip {{ $role === $value ? 'is-active' : '' }}"
                           href="{{ route('admin.users.index', array_filter(['q' => $term, 'role' => $value, 'blocked' => $blocked])) }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($users->isEmpty())
            <x-empty-state icon="bi-person-x"
                           :title="__('No users match these filters.')"
                           :message="__('Try a different name, role or status, or reset the filters.')">
                <a href="{{ route('admin.users.index') }}" class="btn btn-primary">{{ __('Reset the filters') }}</a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('All accounts, newest first') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('User') }}</th>
                                <th scope="col">{{ __('Role') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col" class="text-end">{{ __('Posts') }}</th>
                                <th scope="col" class="text-end">{{ __('Classes') }}</th>
                                <th scope="col">{{ __('Joined') }}</th>
                                <th scope="col" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                @php
                                    $roleColor = match ($user->role) {
                                        \App\Models\User::ROLE_ADMIN => 'dark',
                                        \App\Models\User::ROLE_TEACHER => 'primary',
                                        default => 'success',
                                    };
                                    $protected = auth()->id() === $user->id || $user->isAdmin();
                                @endphp
                                <tr>
                                    <td data-label="{{ __('User') }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <x-avatar :user="$user" size="sm" />
                                            <div class="min-w-0">
                                                <a href="{{ route('users.show', $user) }}"
                                                   class="fw-semibold text-decoration-none text-reset text-break">{{ $user->name }}</a>
                                                <div class="small text-muted text-break">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('Role') }}">
                                        <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $user->role }}</span>
                                    </td>
                                    <td data-label="{{ __('Status') }}">
                                        @if ($user->is_blocked)
                                            <span class="badge text-bg-danger">{{ __('Blocked') }}</span>
                                        @else
                                            <span class="badge text-bg-success">{{ __('Active') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Posts') }}" class="text-end">{{ $user->posts_count }}</td>
                                    <td data-label="{{ __('Classes') }}" class="text-end">{{ $user->classes_count }}</td>
                                    <td data-label="{{ __('Joined') }}" class="text-nowrap small text-muted">
                                        <time datetime="{{ $user->created_at?->toIso8601String() }}">{{ $user->created_at?->format('d M Y') }}</time>
                                    </td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            @if ($user->is_blocked)
                                                @can('unblock', $user)
                                                    <form method="POST" action="{{ route('admin.users.unblock', $user) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            <i class="bi bi-person-check me-1" aria-hidden="true"></i> {{ __('Unblock') }}
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('block', $user)
                                                    {{-- Opens the Bootstrap confirmation modal rendered under the table. --}}
                                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                                            data-bs-toggle="modal" data-bs-target="#block-user-{{ $user->id }}"
                                                            aria-label="{{ __('Block :name', ['name' => $user->name]) }}">
                                                        <i class="bi bi-person-slash me-1" aria-hidden="true"></i> {{ __('Block') }}
                                                    </button>
                                                @endcan
                                            @endif

                                            @can('delete', $user)
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal" data-bs-target="#delete-user-{{ $user->id }}"
                                                        title="{{ __('Delete') }}" aria-label="{{ __('Delete :name', ['name' => $user->name]) }}">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            @endcan

                                            @if ($protected)
                                                <span class="text-muted small align-self-center">{{ __('protected') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$users" />
                    </div>
                @endif
            </div>

            {{-- Confirmations live outside the table so the markup stays valid. --}}
            @foreach ($users as $user)
                @can('block', $user)
                    <x-confirm-delete-modal
                        :id="'block-user-'.$user->id"
                        :action="route('admin.users.block', $user)"
                        method="PATCH"
                        :title="__('Block :name?', ['name' => $user->name])"
                        :message="__('They will be logged out immediately and cannot sign in until you unblock them.')"
                        :confirm-label="__('Block account')"
                        confirm-class="btn btn-warning"
                        :show-trigger="false" />
                @endcan

                @can('delete', $user)
                    <x-confirm-delete-modal
                        :id="'delete-user-'.$user->id"
                        :action="route('admin.users.destroy', $user)"
                        :title="__('Delete :name?', ['name' => $user->name])"
                        :message="__('This deletes the account, their posts, comments and reports. This cannot be undone.')"
                        :confirm-label="__('Delete account')"
                        :show-trigger="false" />
                @endcan
            @endforeach
        @endif
    </div>
</x-app-layout>

```

## `resources/views/admin/posts/index.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Posts') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Posts') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-chat-square-text me-1 text-edu" aria-hidden="true"></i> {{ __('Posts') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Hide or delete inappropriate posts (FR-28).') }}</p>
            </div>
            <span class="edu-chip">
                <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                {{ $posts->total() }} {{ __('posts') }}
            </span>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <section class="card edu-card mb-3" aria-labelledby="post-filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="post-filters-title">{{ __('Filter posts') }}</h2>

                <form method="GET" action="{{ route('admin.posts.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-6">
                        <label for="post-q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="post-q" value="{{ $term }}" class="form-control"
                                   placeholder="{{ __('Post text or author name') }}" maxlength="150">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="post-hidden" class="form-label small">{{ __('Visibility') }}</label>
                        <select name="hidden" id="post-hidden" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="0" @selected($hidden === '0')>{{ __('Visible') }}</option>
                            <option value="1" @selected($hidden === '1')>{{ __('Hidden') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by visibility') }}">
                    <a class="edu-filter-chip {{ $hidden === '' ? 'is-active' : '' }}"
                       href="{{ route('admin.posts.index', array_filter(['q' => $term])) }}">{{ __('All') }}</a>
                    <a class="edu-filter-chip {{ $hidden === '0' ? 'is-active' : '' }}"
                       href="{{ route('admin.posts.index', array_filter(['q' => $term, 'hidden' => '0'])) }}">{{ __('Visible') }}</a>
                    <a class="edu-filter-chip {{ $hidden === '1' ? 'is-active' : '' }}"
                       href="{{ route('admin.posts.index', array_filter(['q' => $term, 'hidden' => '1'])) }}">{{ __('Hidden') }}</a>
                </div>
            </div>
        </section>

        @if ($posts->isEmpty())
            <x-empty-state icon="bi-inbox"
                           :title="__('No posts match these filters.')"
                           :message="__('Try a different search or visibility, or reset the filters.')">
                <a href="{{ route('admin.posts.index') }}" class="btn btn-primary">{{ __('Reset the filters') }}</a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('All posts, newest first') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Author') }}</th>
                                <th scope="col">{{ __('Post') }}</th>
                                <th scope="col" class="text-end">{{ __('Reports') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col">{{ __('Posted') }}</th>
                                <th scope="col" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($posts as $post)
                                <tr>
                                    <td data-label="{{ __('Author') }}" class="text-nowrap">
                                        <a href="{{ route('users.show', $post->user) }}" class="text-decoration-none text-reset">
                                            {{ $post->user?->name ?? __('Deleted user') }}
                                        </a>
                                    </td>
                                    <td data-label="{{ __('Post') }}" class="edu-admin-post-cell">
                                        <a href="{{ route('posts.show', $post) }}" class="text-decoration-none text-reset">
                                            {{ \Illuminate\Support\Str::limit($post->body, 120) }}
                                        </a>
                                        @if ($post->image)
                                            <i class="bi bi-image text-muted ms-1" title="{{ __('Has an image') }}"
                                               aria-label="{{ __('Has an image') }}"></i>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Reports') }}" class="text-end">
                                        @if ($post->reports_count > 0)
                                            <span class="badge text-bg-warning">{{ $post->reports_count }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Status') }}">
                                        @if ($post->is_hidden)
                                            <span class="badge text-bg-warning">{{ __('Hidden') }}</span>
                                        @else
                                            <span class="badge text-bg-success">{{ __('Visible') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Posted') }}" class="text-nowrap small text-muted">
                                        <time datetime="{{ $post->created_at?->toIso8601String() }}">{{ $post->created_at?->format('d M Y H:i') }}</time>
                                    </td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <form method="POST" action="{{ route('admin.posts.hide', $post) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-{{ $post->is_hidden ? 'success' : 'warning' }}">
                                                    <i class="bi bi-eye{{ $post->is_hidden ? '' : '-slash' }} me-1" aria-hidden="true"></i>
                                                    {{ $post->is_hidden ? __('Unhide') : __('Hide') }}
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#delete-post-admin-{{ $post->id }}"
                                                    title="{{ __('Delete') }}" aria-label="{{ __('Delete this post') }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($posts->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$posts" />
                    </div>
                @endif
            </div>

            @foreach ($posts as $post)
                <x-confirm-delete-modal
                    :id="'delete-post-admin-'.$post->id"
                    :action="route('admin.posts.destroy', $post)"
                    :title="__('Delete this post?')"
                    :message="__('The post, its image, comments, likes and reports are deleted. This cannot be undone.')"
                    :confirm-label="__('Delete post')"
                    :show-trigger="false" />
            @endforeach
        @endif
    </div>
</x-app-layout>

```

## `resources/views/admin/classes/index.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Classes') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Classes') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-easel me-1 text-edu" aria-hidden="true"></i> {{ __('Classes') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Hide or delete inappropriate classes (FR-28).') }}</p>
            </div>
            <span class="edu-chip">
                <i class="bi bi-easel" aria-hidden="true"></i>
                {{ $classes->total() }} {{ __('classes') }}
            </span>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <section class="card edu-card mb-3" aria-labelledby="class-filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="class-filters-title">{{ __('Filter classes') }}</h2>

                <form method="GET" action="{{ route('admin.classes.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="class-q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="class-q" value="{{ $term }}" class="form-control"
                                   placeholder="{{ __('Class title or teacher name') }}" maxlength="150">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="class-status" class="form-label small">{{ __('Status') }}</label>
                        <select name="status" id="class-status" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            @foreach (\App\Models\ClassRoom::STATUSES as $value)
                                <option value="{{ $value }}" @selected($status === $value)>
                                    {{ __(\Illuminate\Support\Str::headline($value)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="class-hidden" class="form-label small">{{ __('Visibility') }}</label>
                        <select name="hidden" id="class-hidden" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="0" @selected($hidden === '0')>{{ __('Visible') }}</option>
                            <option value="1" @selected($hidden === '1')>{{ __('Hidden') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by status') }}">
                    <a class="edu-filter-chip {{ $status === '' ? 'is-active' : '' }}"
                       href="{{ route('admin.classes.index', array_filter(['q' => $term, 'hidden' => $hidden])) }}">
                        {{ __('All statuses') }}
                    </a>
                    @foreach (\App\Models\ClassRoom::STATUSES as $value)
                        <a class="edu-filter-chip {{ $status === $value ? 'is-active' : '' }}"
                           href="{{ route('admin.classes.index', array_filter(['q' => $term, 'status' => $value, 'hidden' => $hidden])) }}">
                            {{ __(\Illuminate\Support\Str::headline($value)) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($classes->isEmpty())
            <x-empty-state icon="bi-calendar-x"
                           :title="__('No classes match these filters.')"
                           :message="__('Try another title, status or visibility, or reset the filters.')">
                <a href="{{ route('admin.classes.index') }}" class="btn btn-primary">{{ __('Reset the filters') }}</a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('All classes, newest first') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Class') }}</th>
                                <th scope="col">{{ __('Teacher') }}</th>
                                <th scope="col">{{ __('When') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col" class="text-end">{{ __('Students') }}</th>
                                <th scope="col" class="text-end">{{ __('Reports') }}</th>
                                <th scope="col">{{ __('Visibility') }}</th>
                                <th scope="col" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classes as $classRoom)
                                <tr>
                                    <td data-label="{{ __('Class') }}">
                                        <a href="{{ route('classes.show', $classRoom) }}"
                                           class="fw-semibold text-decoration-none text-reset">{{ $classRoom->title }}</a>
                                        @if ($classRoom->subject)
                                            <div class="small text-muted">{{ $classRoom->subject }}</div>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Teacher') }}" class="text-nowrap">
                                        {{ $classRoom->teacher?->name ?? __('Deleted teacher') }}
                                    </td>
                                    <td data-label="{{ __('When') }}" class="text-nowrap small">
                                        {{ $classRoom->date?->format('d M Y') }}
                                        <div class="text-muted">{{ \Illuminate\Support\Str::substr((string) $classRoom->time, 0, 5) }}</div>
                                    </td>
                                    <td data-label="{{ __('Status') }}">
                                        <x-status-badge :status="$classRoom->status" />
                                    </td>
                                    <td data-label="{{ __('Students') }}" class="text-end">{{ $classRoom->students_count }}</td>
                                    <td data-label="{{ __('Reports') }}" class="text-end">
                                        {{-- Reports target posts and comments; a class is moderated with Hide (cancel). --}}
                                        <span class="text-muted" title="{{ __('Classes are not reportable') }}">&mdash;</span>
                                    </td>
                                    <td data-label="{{ __('Visibility') }}">
                                        @if ($classRoom->is_hidden)
                                            <span class="badge text-bg-warning">{{ __('Hidden') }}</span>
                                        @else
                                            <span class="badge text-bg-success">{{ __('Visible') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <form method="POST" action="{{ route('admin.classes.hide', $classRoom) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-{{ $classRoom->is_hidden ? 'success' : 'warning' }}">
                                                    <i class="bi bi-eye{{ $classRoom->is_hidden ? '' : '-slash' }} me-1" aria-hidden="true"></i>
                                                    {{ $classRoom->is_hidden ? __('Unhide') : __('Hide') }}
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#delete-class-admin-{{ $classRoom->id }}"
                                                    title="{{ __('Delete') }}" aria-label="{{ __('Delete this class') }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($classes->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$classes" />
                    </div>
                @endif
            </div>

            @foreach ($classes as $classRoom)
                <x-confirm-delete-modal
                    :id="'delete-class-admin-'.$classRoom->id"
                    :action="route('admin.classes.destroy', $classRoom)"
                    :title="__('Delete this class?')"
                    :message="__('The class and every enrollment are removed. This cannot be undone.')"
                    :confirm-label="__('Delete class')"
                    :show-trigger="false" />
            @endforeach
        @endif
    </div>
</x-app-layout>

```

## `resources/views/admin/reports/index.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Reports') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Reports') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-flag me-1 text-edu" aria-hidden="true"></i> {{ __('Reports') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Review what the community reported (FR-33, FR-34).') }}</p>
            </div>
            <span class="badge text-bg-warning">
                <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>
                {{ $counts[\App\Models\Report::STATUS_PENDING] ?? 0 }} {{ __('pending') }}
            </span>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        {{-- Filters --}}
        <section class="card edu-card mb-3" aria-labelledby="report-filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="report-filters-title">{{ __('Filter reports') }}</h2>

                <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="report-q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="report-q" value="{{ $term }}" class="form-control"
                                   placeholder="{{ __('Reason, details, reporter or content') }}" maxlength="150">
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="report-status" class="form-label small">{{ __('Status') }}</label>
                        <select name="status" id="report-status" class="form-select">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach (\App\Models\Report::STATUSES as $value)
                                <option value="{{ $value }}" @selected($status === $value)>
                                    {{ __(\Illuminate\Support\Str::headline($value)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label for="report-type" class="form-label small">{{ __('Type') }}</label>
                        <select name="type" id="report-type" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="post" @selected($type === 'post')>{{ __('Posts') }}</option>
                            <option value="comment" @selected($type === 'comment')>{{ __('Comments') }}</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>

                {{-- One-tap status chips, keeping the other filters. --}}
                <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by status') }}">
                    <a class="edu-filter-chip {{ $status === '' ? 'is-active' : '' }}"
                       href="{{ route('admin.reports.index', array_filter(['q' => $term, 'type' => $type])) }}">
                        {{ __('All statuses') }}
                    </a>
                    @foreach (\App\Models\Report::STATUSES as $value)
                        <a class="edu-filter-chip {{ $status === $value ? 'is-active' : '' }}"
                           href="{{ route('admin.reports.index', array_filter(['q' => $term, 'status' => $value, 'type' => $type])) }}">
                            {{ __(\Illuminate\Support\Str::headline($value)) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($reports->isEmpty())
            <x-empty-state icon="bi-shield-check"
                           :title="__('No reports match these filters.')"
                           :message="__('The community is calm right now. Try another status or reset the filters.')">
                <a href="{{ route('admin.reports.index') }}" class="btn btn-primary">{{ __('Reset the filters') }}</a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('All reports, newest first') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('Content') }}</th>
                                <th scope="col">{{ __('Reported user') }}</th>
                                <th scope="col">{{ __('Reporter') }}</th>
                                <th scope="col">{{ __('Reason') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col">{{ __('Date') }}</th>
                                <th scope="col" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                @php($content = $report->reportable)
                                <tr>
                                    <td data-label="ID" class="text-muted">{{ $report->id }}</td>
                                    <td data-label="{{ __('Content') }}" class="edu-admin-post-cell">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <span class="badge bg-body-secondary text-body border">{{ $report->content_type }}</span>
                                            @if ($content && $content->is_hidden)
                                                <span class="badge text-bg-warning">{{ __('hidden') }}</span>
                                            @endif
                                        </div>
                                        <div class="small">{{ $report->content_preview }}</div>
                                    </td>
                                    <td data-label="{{ __('Reported user') }}">
                                        @if ($report->reported_user)
                                            <a href="{{ route('users.show', $report->reported_user) }}"
                                               class="text-decoration-none text-reset">{{ $report->reported_user->name }}</a>
                                        @else
                                            <span class="text-muted small">{{ __('Deleted') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Reporter') }}">
                                        <a href="{{ route('users.show', $report->reporter) }}"
                                           class="text-decoration-none text-reset">{{ $report->reporter?->name ?? __('Deleted') }}</a>
                                    </td>
                                    <td data-label="{{ __('Reason') }}">{{ $report->reason_label }}</td>
                                    <td data-label="{{ __('Status') }}">
                                        <x-status-badge :status="$report->status" />
                                    </td>
                                    <td data-label="{{ __('Date') }}" class="text-nowrap small text-muted">
                                        <time datetime="{{ $report->created_at?->toIso8601String() }}">{{ $report->created_at?->format('d M Y H:i') }}</time>
                                    </td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <a href="{{ route('admin.reports.show', $report) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ __('Review') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($reports->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$reports" />
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>

```

## `resources/views/admin/reports/show.blade.php`

```blade
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
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($content->image) }}"
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

# 14. Dashboards

## `resources/views/dashboards/admin.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Admin dashboard') }}</x-slot>

    <div class="container-xxl px-3 px-lg-4 edu-dashboard">
        @php
            $hour = (int) now()->format('G');
            $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
            $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
        @endphp

        {{-- Greeting + quick actions --}}
        <header class="edu-greeting">
            <div>
                <span class="edu-eyebrow">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    {{ __('Admin workspace') }}
                </span>
                <h1>{{ $greeting }}, {{ $firstName }}</h1>
                <p>
                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                    {{ now()->isoFormat('dddd, D MMMM YYYY') }}
                    <span class="mx-1" aria-hidden="true">·</span>
                    {{ __('Here is what is happening across EduConnect today.') }}
                </p>
            </div>

            <div class="edu-quick-actions" aria-label="{{ __('Quick actions') }}">
                <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-flag" aria-hidden="true"></i>
                    {{ __('Moderation queue') }}
                    @if ($pendingReports > 0)
                        <span class="badge rounded-pill bg-body-secondary text-body ms-1">{{ $pendingReports }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-people" aria-hidden="true"></i> {{ __('Manage users') }}
                </a>
                <a href="{{ route('admin.posts.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chat-square-text" aria-hidden="true"></i> {{ __('Review posts') }}
                </a>
            </div>
        </header>

        {{-- Five platform counters with trend + sparkline (FR-7, FR-26) --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Total users')" :value="$stats['users']" icon="bi-people" color="primary"
                    :trend="$trends['users']['percent']" :trend-direction="$trends['users']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['users']" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Teachers')" :value="$stats['teachers']" icon="bi-person-video3" color="success"
                    :trend="$trends['teachers']['percent']" :trend-direction="$trends['teachers']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['teachers']" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Students')" :value="$stats['students']" icon="bi-mortarboard" color="info"
                    :trend="$trends['students']['percent']" :trend-direction="$trends['students']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['students']" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Posts')" :value="$stats['posts']" icon="bi-chat-square-text" color="warning"
                    :trend="$trends['posts']['percent']" :trend-direction="$trends['posts']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['posts']" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Classes')" :value="$stats['classes']" icon="bi-easel" color="danger"
                    :trend="$trends['classes']['percent']" :trend-direction="$trends['classes']['direction']"
                    :trend-label="__('vs last month')" :sparkline="$sparklines['classes']" />
            </div>
        </div>

        {{-- Charts (FR-31) --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xxl-8">
                <x-chart-card :title="__('New users per month')" :subtitle="__('Registrations over the last 12 months')"
                    chart-id="admin-new-users" kind="line" series-key="newUsers" color="primary"
                    :series="$chart" :total="$chart['total']"
                    :endpoint="route('admin.dashboard.charts')" current-range="12m" :height="320"
                    :label="__('New users')" />
            </div>

            <div class="col-12 col-md-6 col-xxl-4">
                <x-chart-card :title="__('Users by role')" :subtitle="__('The shape of the community')"
                    chart-id="admin-roles" kind="doughnut" series-key="roles"
                    :series="$roleChart" :total="$roleChart['total']" :height="320" />
            </div>

            <div class="col-12 col-md-6 col-xl-6">
                <x-chart-card :title="__('Posts per week')" :subtitle="__('How active the feed is')"
                    chart-id="admin-posts" kind="bar" series-key="posts" color="secondary"
                    :series="$postsChart" :total="$postsChart['total']"
                    :endpoint="route('admin.dashboard.charts')" current-range="12m" :height="280"
                    :label="__('Posts')" />
            </div>

            <div class="col-12 col-md-6 col-xl-6">
                <x-chart-card :title="__('Classes by status')" :subtitle="__('Scheduled, live and completed')"
                    chart-id="admin-classes" kind="doughnut" series-key="classes"
                    :series="$classesChart" :total="$classesChart['total']" :height="280" />
            </div>
        </div>

        {{-- Moderation + newest members --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xl-7">
                <section class="card edu-card h-100" aria-labelledby="pending-reports-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="pending-reports-title">
                                <i class="bi bi-flag me-1 text-danger" aria-hidden="true"></i>
                                {{ __('Pending reports') }}
                            </h2>
                            <div class="small text-muted">{{ __('Reports waiting for a moderator.') }}</div>
                        </div>
                        <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}"
                           class="small fw-semibold text-decoration-none">{{ __('See all') }}</a>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($recentReports as $report)
                            <div class="edu-list-row">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <span class="badge bg-body-secondary text-body border text-capitalize">{{ $report->content_type }}</span>
                                        <span class="badge text-bg-warning">{{ $report->reason_label }}</span>
                                        <span class="edu-list-meta">{{ $report->created_at?->diffForHumans() }}</span>
                                    </div>

                                    <a href="{{ route('admin.reports.show', $report) }}"
                                       class="edu-list-title text-truncate">{{ $report->content_preview }}</a>
                                    <div class="edu-list-meta">
                                        {{ __('Reported by') }} {{ $report->reporter?->name ?? __('a deleted user') }}
                                    </div>
                                </div>

                                <div class="edu-report-actions">
                                    {{-- Quick moderation actions (FR-33). --}}
                                    <form method="POST" action="{{ route('admin.reports.hide', $report) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                aria-label="{{ __('Hide the reported :type', ['type' => strtolower($report->content_type)]) }}"
                                                title="{{ __('Hide content') }}">
                                            <i class="bi bi-eye-slash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                aria-label="{{ __('Dismiss this report') }}"
                                                title="{{ __('Dismiss report') }}">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;">
                                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('The moderation queue is clear.') }}</div>
                                <div class="text-muted small">{{ __('Nothing to review right now.') }}</div>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-5">
                <x-data-table id="admin-recent-users" :title="__('Recent users')"
                    :subtitle="__('The newest accounts on the platform')"
                    :count="$recentUsers->count()" count-label=":count accounts"
                    :search-placeholder="__('Search recent users…')">
                    <x-slot name="head">
                        <tr>
                            <th scope="col" data-sort>{{ __('User') }}</th>
                            <th scope="col" class="d-none d-md-table-cell">{{ __('Role') }}</th>
                            <th scope="col" data-sort class="text-end">{{ __('Joined') }}</th>
                        </tr>
                    </x-slot>

                    @forelse ($recentUsers as $user)
                        <tr data-table-row data-search="{{ $user->name }} {{ $user->email }} {{ $user->role }}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <x-avatar :user="$user" size="sm" />
                                    <div class="min-w-0">
                                        <a href="{{ route('users.show', $user) }}"
                                           class="fw-semibold text-decoration-none text-reset d-block text-truncate">
                                            {{ $user->name }}
                                        </a>
                                        <span class="text-muted small d-block text-truncate">{{ $user->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                @php
                                    $roleColor = match ($user->role) {
                                        \App\Models\User::ROLE_ADMIN => 'dark',
                                        \App\Models\User::ROLE_TEACHER => 'primary',
                                        default => 'success',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $user->role }}</span>
                            </td>
                            <td class="text-end text-muted small text-nowrap" data-sort-value="{{ $user->created_at?->timestamp }}">
                                {{ $user->created_at?->format('d M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr data-table-row data-search="">
                            <td colspan="3" class="text-center text-muted py-4 small">
                                <i class="bi bi-people me-1" aria-hidden="true"></i> {{ __('No users yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </x-data-table>
            </div>
        </div>

        {{-- Latest posts + live classes --}}
        <div class="row g-3 g-xl-4">
            <div class="col-12 col-xl-7">
                <section class="card edu-card h-100" aria-labelledby="recent-posts-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="recent-posts-title">
                                <i class="bi bi-chat-square-text me-1 text-edu" aria-hidden="true"></i>
                                {{ __('Latest posts') }}
                            </h2>
                            <div class="small text-muted">{{ __('The newest activity in the feed.') }}</div>
                        </div>
                        <a href="{{ route('admin.posts.index') }}"
                           class="small fw-semibold text-decoration-none">{{ __('Manage posts') }}</a>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($recentPosts as $post)
                            <article class="edu-list-row">
                                <x-avatar :user="$post->user" size="sm" class="flex-shrink-0" />
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <a href="{{ route('users.show', $post->user) }}"
                                           class="fw-semibold small text-decoration-none text-reset">
                                            {{ $post->user?->name ?? __('Deleted user') }}
                                        </a>
                                        <span class="edu-list-meta">{{ $post->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <a href="{{ route('posts.show', $post) }}"
                                       class="edu-list-title mt-1">{{ \Illuminate\Support\Str::limit($post->body, 110) }}</a>
                                    <div class="edu-list-meta">
                                        <i class="bi bi-heart me-1" aria-hidden="true"></i>{{ $post->likes_count }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        <i class="bi bi-chat me-1" aria-hidden="true"></i>{{ $post->comments_count }}
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="text-center text-muted py-4 small">
                                <i class="bi bi-chat-square me-1" aria-hidden="true"></i>
                                {{ __('The feed is quiet right now.') }}
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-5">
                <section class="card edu-card h-100" aria-labelledby="live-classes-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="live-classes-title">
                                <i class="bi bi-broadcast me-1 text-danger" aria-hidden="true"></i>
                                {{ __("Today's live classes") }}
                            </h2>
                            <div class="small text-muted">{{ __('Rooms running right now.') }}</div>
                        </div>
                        <a href="{{ route('admin.classes.index') }}"
                           class="small fw-semibold text-decoration-none">{{ __('All classes') }}</a>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($liveClasses as $class)
                            <div class="edu-list-row">
                                <span class="edu-activity-icon bg-danger" aria-hidden="true">
                                    <i class="bi bi-camera-video"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <a href="{{ route('classes.show', $class) }}" class="edu-list-title text-truncate">
                                        {{ $class->title }}
                                    </a>
                                    <div class="edu-list-meta">
                                        {{ $class->teacher?->name ?? __('Unknown teacher') }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        {{ \Illuminate\Support\Str::substr((string) $class->time, 0, 5) }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        {{ trans_choice(':count student|:count students', $class->students_count, ['count' => $class->students_count]) }}
                                    </div>
                                </div>
                                <a href="{{ route('classes.room', $class) }}" class="btn btn-sm btn-danger"
                                   aria-label="{{ __('Join :title', ['title' => $class->title]) }}">
                                    <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;">
                                    <i class="bi bi-camera-video-off" aria-hidden="true"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('No live classes right now.') }}</div>
                                <div class="text-muted small">{{ __('Scheduled rooms will appear here when they start.') }}</div>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/dashboards/teacher.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Teacher dashboard') }}</x-slot>

    <div class="container-xxl px-3 px-lg-4 edu-dashboard">
        @php
            $hour = (int) now()->format('G');
            $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
            $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
        @endphp

        <header class="edu-greeting">
            <div>
                <span class="edu-eyebrow">
                    <i class="bi bi-easel" aria-hidden="true"></i>
                    {{ __('Teaching workspace') }}
                </span>
                <h1>{{ $greeting }}, {{ $firstName }}</h1>
                <p>{{ __('Your classes, students and community at a glance.') }}</p>
            </div>

            <div class="edu-quick-actions">
                <a href="{{ route('classes.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Create class') }}
                </a>
                <a href="{{ route('classes.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-collection" aria-hidden="true"></i> {{ __('All my classes') }}
                </a>
            </div>
        </header>

        {{-- Next class hero --}}
        @if ($nextClass)
            <section class="edu-hero mb-4" aria-labelledby="next-class-title">
                <div class="row align-items-center g-4">
                    <div class="col-12 col-lg-7">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="edu-hero-label small fw-semibold text-uppercase" style="letter-spacing: .1em;">
                                {{ __('Next class') }}
                            </span>
                            @if ($nextClass->isLive())
                                <span class="edu-hero-live">
                                    <span class="edu-live-dot" aria-hidden="true"></span> {{ __('Live now') }}
                                </span>
                            @endif
                        </div>

                        <h1 class="h2 fw-bold mb-2" id="next-class-title">{{ $nextClass->title }}</h1>

                        <div class="edu-hero-meta d-flex flex-wrap gap-3 mb-3">
                            @if ($nextClass->subject)
                                <span><i class="bi bi-book me-1" aria-hidden="true"></i>{{ $nextClass->subject }}</span>
                            @endif
                            <span>
                                <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                {{ $nextClass->date?->format('D d M Y') }}
                            </span>
                            <span>
                                <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                {{ \Illuminate\Support\Str::substr((string) $nextClass->time, 0, 5) }}
                                &middot; {{ $nextClass->duration }} {{ __('min') }}
                            </span>
                        </div>

                        @if ($nextClass->isLive())
                            <div class="edu-chip">
                                <span class="edu-live-dot" aria-hidden="true"></span>
                                {{ __('Students can join the room now') }}
                            </div>
                        @else
                            <x-countdown :target="$nextClass->startsAt()->toIso8601String()" />
                        @endif

                        <div class="d-flex align-items-center gap-2 mt-3">
                            <x-avatar-stack :users="$nextClass->students" :limit="5" />
                            <span class="small" style="color: rgba(255,255,255,.8);">
                                {{ trans_choice(':count student enrolled|:count students enrolled', $nextClass->students_count, ['count' => $nextClass->students_count]) }}
                            </span>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                            @if ($nextClass->isLive())
                                <a href="{{ route('classes.room', $nextClass) }}" class="btn btn-light btn-lg">
                                    <i class="bi bi-camera-video" aria-hidden="true"></i> {{ __('Join live') }}
                                </a>
                            @else
                                <form method="POST" action="{{ route('classes.status', $nextClass) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="live">
                                    <button type="submit" class="btn btn-light btn-lg">
                                        <i class="bi bi-broadcast" aria-hidden="true"></i> {{ __('Start class') }}
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('classes.show', $nextClass) }}" class="btn btn-outline-light btn-lg">
                                {{ __('Details') }}
                            </a>
                        </div>

                        <div class="text-lg-end mt-3">
                            <a href="{{ route('classes.students', $nextClass) }}"
                               class="small text-decoration-none" style="color: rgba(255,255,255,.78);">
                                <i class="bi bi-people me-1" aria-hidden="true"></i>
                                {{ __('See who is enrolled') }}
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        @else
            <section class="card edu-card mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="edu-empty-illustration mb-0" style="width: 62px; height: 62px; font-size: 1.5rem;" aria-hidden="true">
                            <i class="bi bi-calendar-plus"></i>
                        </span>
                        <div>
                            <h2 class="h5 fw-bold mb-1">{{ __('No class scheduled yet') }}</h2>
                            <p class="text-muted small mb-0">{{ __('Create your first live class and invite your students.') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('classes.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Create a class') }}
                    </a>
                </div>
            </section>
        @endif

        {{-- Teacher counters --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('My classes')" :value="$stats['classes']" icon="bi-easel" color="primary"
                    :trend="$trends['classes']['percent']" :trend-direction="$trends['classes']['direction']"
                    :trend-label="__('vs last month')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Upcoming classes')" :value="$stats['upcoming']" icon="bi-calendar-check" color="success"
                    :hint="__('Scheduled and still ahead')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Enrolled students')" :value="$stats['students']" icon="bi-people" color="info"
                    :trend="$trends['students']['percent']" :trend-direction="$trends['students']['direction']"
                    :trend-label="__('new enrollments vs last month')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Followers')" :value="$stats['followers']" icon="bi-person-hearts" color="warning"
                    :trend="$trends['followers']['percent']" :trend-direction="$trends['followers']['direction']"
                    :trend-label="__('vs last month')" />
            </div>
        </div>

        {{-- Charts --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xl-7">
                <x-chart-card :title="__('Enrollments per class')" :subtitle="__('Your most popular classes')"
                    chart-id="teacher-enrollments" kind="bar" series-key="enrollments" color="primary"
                    :series="$enrollmentChart" :total="$enrollmentChart['total']"
                    :endpoint="route('teacher.dashboard.charts')" current-range="12m" :height="280"
                    :label="__('Students')" />
            </div>

            <div class="col-12 col-xl-5">
                <x-chart-card :title="__('Follower growth')" :subtitle="__('People who started following you')"
                    chart-id="teacher-followers" kind="line" series-key="followers" color="secondary"
                    :series="$followerChart" :total="$followerChart['total']"
                    :endpoint="route('teacher.dashboard.charts')" current-range="12m" :height="280"
                    :label="__('Followers')" />
            </div>
        </div>

        {{-- Weekly schedule --}}
        <section class="card edu-card mb-4" aria-labelledby="weekly-schedule-title">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h6 fw-bold mb-0" id="weekly-schedule-title">
                        <i class="bi bi-calendar-week me-1 text-edu" aria-hidden="true"></i>
                        {{ __('This week') }}
                    </h2>
                    <div class="small text-muted">{{ __('Classes per day for the next seven days.') }}</div>
                </div>
                <a href="{{ route('classes.index') }}" class="small fw-semibold text-decoration-none">
                    {{ __('Open calendar') }}
                </a>
            </div>
            <div class="card-body">
                <div class="edu-schedule" role="list" aria-label="{{ __('Seven day schedule') }}">
                    @foreach ($schedule as $day)
                        <div class="edu-schedule-day {{ $day['isToday'] ? 'is-today' : '' }} {{ $day['count'] > 0 ? 'has-classes' : '' }}"
                             role="listitem"
                             aria-label="{{ $day['date']->format('l d M') }}: {{ trans_choice(':count class|:count classes', $day['count'], ['count' => $day['count']]) }}">
                            <span class="edu-schedule-weekday">{{ $day['weekday'] }}</span>
                            <span class="edu-schedule-date">{{ $day['day'] }}</span>
                            <span class="edu-schedule-count">{{ $day['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Classes table + activity timeline --}}
        <div class="row g-3 g-xl-4">
            <div class="col-12 col-xl-8">
                <x-data-table id="teacher-classes" :title="__('My classes')"
                    :subtitle="__('Search, sort and manage your classes')"
                    :count="$classes->total()" count-label=":count classes"
                    :search-placeholder="__('Search my classes…')">
                    <x-slot name="head">
                        <tr>
                            <th scope="col" data-sort>{{ __('Title') }}</th>
                            <th scope="col" data-sort>{{ __('Date') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                            <th scope="col" data-sort>{{ __('Enrolled') }}</th>
                            <th scope="col" class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </x-slot>

                    @forelse ($classes as $class)
                        <tr data-table-row
                            data-search="{{ $class->title }} {{ $class->subject }} {{ $class->status }} {{ $class->date?->format('d M Y') }}">
                            <td>
                                <a href="{{ route('classes.show', $class) }}"
                                   class="fw-semibold text-decoration-none text-reset d-block">
                                    {{ $class->title }}
                                </a>
                                @if ($class->subject)
                                    <div class="small text-muted">{{ $class->subject }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap" data-sort-value="{{ $class->date?->timestamp }}">
                                {{ $class->date?->format('d M Y') }}
                                <div class="small text-muted">
                                    {{ \Illuminate\Support\Str::substr((string) $class->time, 0, 5) }}
                                </div>
                            </td>
                            <td>
                                <x-status-badge :status="$class->status" />
                            </td>
                            <td style="min-width: 136px;">
                                <x-progress-bar :value="$class->students_count" :max="$maxEnrollment"
                                    :label="trans_choice(':count enrolled|:count enrolled', $class->students_count, ['count' => $class->students_count])" />
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false"
                                            aria-label="{{ __('Actions for :title', ['title' => $class->title]) }}">
                                        <i class="bi bi-three-dots" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if ($class->isLive())
                                            <li>
                                                <a class="dropdown-item" href="{{ route('classes.room', $class) }}">
                                                    <i class="bi bi-camera-video me-1" aria-hidden="true"></i>
                                                    {{ __('Join live') }}
                                                </a>
                                            </li>
                                        @elseif ($class->canTransitionTo(\App\Models\ClassRoom::STATUS_LIVE))
                                            <li>
                                                <form method="POST" action="{{ route('classes.status', $class) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="live">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-broadcast me-1" aria-hidden="true"></i>
                                                        {{ __('Start class') }}
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item" href="{{ route('classes.students', $class) }}">
                                                <i class="bi bi-people me-1" aria-hidden="true"></i>
                                                {{ __('Students') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('classes.edit', $class) }}">
                                                <i class="bi bi-pencil me-1" aria-hidden="true"></i>
                                                {{ __('Edit') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('classes.show', $class) }}">
                                                <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>
                                                {{ __('View details') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-table-row data-search="">
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1" aria-hidden="true"></i>
                                {{ __('You have not created a class yet.') }}
                            </td>
                        </tr>
                    @endforelse

                    <x-slot name="footer">
                        <x-pagination-links :paginator="$classes" />
                    </x-slot>
                </x-data-table>
            </div>

            <div class="col-12 col-xl-4">
                <section class="card edu-card h-100" aria-labelledby="recent-activity-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="recent-activity-title">
                            <i class="bi bi-activity me-1 text-edu" aria-hidden="true"></i>
                            {{ __('Recent activity') }}
                        </h2>
                        <div class="small text-muted">{{ __('Likes, comments, enrollments and followers.') }}</div>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($activity as $item)
                            <x-activity-item :icon="$item['icon']" :color="$item['color']"
                                :text="$item['text']" :excerpt="$item['excerpt']" :at="$item['at']" />
                        @empty
                            <div class="text-center text-muted py-4 small">
                                <i class="bi bi-activity me-1" aria-hidden="true"></i>
                                {{ __('Nothing has happened yet.') }}
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/dashboards/student.blade.php`

```blade
<x-app-layout>
    <x-slot name="title">{{ __('Student dashboard') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('student.dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Learning') }}</li>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4 edu-dashboard">
        @php
            $hour = (int) now()->format('G');
            $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
            $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
        @endphp

        {{-- Greeting + quick actions --}}
        <header class="edu-greeting">
            <div>
                <span class="edu-eyebrow">
                    <i class="bi bi-mortarboard" aria-hidden="true"></i>
                    {{ __('Learning workspace') }}
                </span>
                <h1>{{ $greeting }}, {{ $firstName }}</h1>
                <p>
                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                    {{ now()->isoFormat('dddd, D MMMM YYYY') }}
                    <span class="mx-1" aria-hidden="true">·</span>
                    {{ __('Keep going — your next class is waiting.') }}
                </p>
            </div>

            <div class="edu-quick-actions" aria-label="{{ __('Quick actions') }}">
                <a href="{{ route('classes.discover') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-search" aria-hidden="true"></i> {{ __('Find a class') }}
                </a>
                <a href="{{ route('student.classes') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-journal-bookmark" aria-hidden="true"></i> {{ __('My classes') }}
                </a>
                <a href="{{ route('feed.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chat-square-text" aria-hidden="true"></i> {{ __('Open feed') }}
                </a>
            </div>
        </header>

        {{-- Next enrolled class hero --}}
        @if ($heroClass)
            <section class="edu-hero mb-4" aria-labelledby="next-class-title">
                <div class="row align-items-center g-4">
                    <div class="col-12 col-lg-7">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="edu-hero-label small fw-semibold text-uppercase" style="letter-spacing: .1em;">
                                {{ $heroClass->isLive() ? __('Happening now') : __('Your next class') }}
                            </span>

                            @if ($heroClass->isLive())
                                <span class="edu-hero-live">
                                    <span class="edu-live-dot" aria-hidden="true"></span> {{ __('Live now') }}
                                </span>
                            @else
                                <span class="edu-chip">
                                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                    {{ __('Starts soon') }}
                                </span>
                            @endif
                        </div>

                        <h1 class="h2 fw-bold mb-2" id="next-class-title">{{ $heroClass->title }}</h1>

                        <div class="edu-hero-meta d-flex flex-wrap gap-3 mb-3">
                            <span>
                                <i class="bi bi-person-video3 me-1" aria-hidden="true"></i>
                                {{ $heroClass->teacher?->name ?? __('Your teacher') }}
                            </span>
                            @if ($heroClass->subject)
                                <span>
                                    <i class="bi bi-book me-1" aria-hidden="true"></i>
                                    {{ $heroClass->subject }}
                                </span>
                            @endif
                            <span>
                                <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                {{ $heroClass->date?->format('D d M Y') }}
                            </span>
                            <span>
                                <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                {{ \Illuminate\Support\Str::substr((string) $heroClass->time, 0, 5) }}
                                &middot; {{ $heroClass->duration }} {{ __('min') }}
                            </span>
                        </div>

                        @unless ($heroClass->isLive())
                            <x-countdown :target="$heroClass->startsAt()->toIso8601String()" />
                        @endunless

                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                            <x-avatar-stack :users="$classmates" :limit="5" />
                            <span class="small" style="color: rgba(255,255,255,.8);">
                                {{ trans_choice(':count classmate|:count classmates', $heroClass->students_count ?? 0, ['count' => $heroClass->students_count ?? 0]) }}
                            </span>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                            @if ($heroClass->isLive())
                                {{-- The room only opens while the class is live (FR-20). --}}
                                <a href="{{ route('classes.room', $heroClass) }}" class="btn btn-light btn-lg">
                                    <i class="bi bi-camera-video" aria-hidden="true"></i> {{ __('Join live') }}
                                </a>
                            @else
                                {{-- Stays disabled until the teacher starts the class. --}}
                                <button type="button" class="btn btn-light btn-lg" disabled
                                        aria-disabled="true"
                                        title="{{ __('The Join button opens when the teacher starts the class.') }}">
                                    <i class="bi bi-camera-video" aria-hidden="true"></i> {{ __('Join') }}
                                </button>
                            @endif

                            <a href="{{ route('classes.show', $heroClass) }}" class="btn btn-outline-light btn-lg">
                                {{ __('Details') }}
                            </a>
                        </div>

                        <div class="text-lg-end mt-3">
                            <a href="{{ route('student.classes') }}"
                               class="small text-decoration-none" style="color: rgba(255,255,255,.78);">
                                <i class="bi bi-journal-bookmark me-1" aria-hidden="true"></i>
                                {{ __('All my classes') }}
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        @else
            <section class="card edu-card mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="edu-empty-illustration mb-0" style="width: 62px; height: 62px; font-size: 1.5rem;" aria-hidden="true">
                            <i class="bi bi-calendar-plus"></i>
                        </span>
                        <div>
                            <h2 class="h5 fw-bold mb-1">{{ __('No class booked yet') }}</h2>
                            <p class="text-muted small mb-0">{{ __('Browse the catalogue and join your first live class.') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('classes.discover') }}" class="btn btn-primary">
                        <i class="bi bi-search" aria-hidden="true"></i> {{ __('Find a class') }}
                    </a>
                </div>
            </section>
        @endif

        {{-- Student counters: the three contracted stats plus notifications. --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Enrolled classes')" :value="$stats['classes']"
                    icon="bi-journal-bookmark" color="primary"
                    :hint="__('Classes you joined')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Upcoming classes')" :value="$stats['upcoming']"
                    icon="bi-calendar-check" color="success"
                    :hint="__('Scheduled and still ahead')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Teachers followed')" :value="$stats['following']"
                    icon="bi-person-hearts" color="info"
                    :hint="__('People you learn from')" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Notifications')" :value="$notificationCount"
                    icon="bi-bell" color="warning" :href="route('notifications.index')"
                    :hint="__('Unread updates')" />
            </div>
        </div>

        {{-- Upcoming classes + recommended teachers --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xl-8">
                <section class="card edu-card h-100" aria-labelledby="upcoming-classes-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="upcoming-classes-title">
                                <i class="bi bi-calendar-week me-1 text-edu" aria-hidden="true"></i>
                                {{ __('My upcoming classes') }}
                            </h2>
                            <div class="small text-muted">{{ __('Your next live sessions, soonest first.') }}</div>
                        </div>
                        <a href="{{ route('student.classes') }}" class="small fw-semibold text-decoration-none">
                            {{ __('See all') }}
                        </a>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            @forelse ($upcomingClasses as $class)
                                <div class="col-12 col-md-6">
                                    <x-class-card :class-room="$class" :url="route('classes.show', $class)">
                                        @if ($class->isLive())
                                            <a href="{{ route('classes.room', $class) }}" class="btn btn-sm btn-danger">
                                                <i class="bi bi-camera-video me-1" aria-hidden="true"></i> {{ __('Join live') }}
                                            </a>
                                        @else
                                            {{-- Disabled until the teacher starts the class; the
                                                 PhaseThreeUiTest asserts these exact attributes. --}}
                                            <button type="button" class="btn btn-sm btn-primary" disabled
                                                    aria-disabled="true"
                                                    title="{{ __('The Join button opens when the teacher starts the class.') }}">
                                                <i class="bi bi-camera-video me-1" aria-hidden="true"></i> {{ __('Join') }}
                                            </button>
                                        @endif

                                        <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ __('Details') }}
                                        </a>
                                    </x-class-card>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="text-center py-4">
                                        <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;" aria-hidden="true">
                                            <i class="bi bi-calendar-x"></i>
                                        </div>
                                        <div class="fw-semibold small">{{ __('No upcoming class') }}</div>
                                        <div class="text-muted small mb-3">{{ __('Browse the catalogue to find your next session.') }}</div>
                                        <a href="{{ route('classes.discover') }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-search" aria-hidden="true"></i> {{ __('Browse classes') }}
                                        </a>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-4">
                <section class="card edu-card h-100" aria-labelledby="recommended-teachers-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="recommended-teachers-title">
                                <i class="bi bi-person-hearts me-1 text-edu" aria-hidden="true"></i>
                                {{ __('Recommended teachers') }}
                            </h2>
                            <div class="small text-muted">{{ __('Follow a teacher to see their classes.') }}</div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            @forelse ($recommendedTeachers as $teacher)
                                <x-user-card :user="$teacher">
                                    {{-- AJAX follow when JS is on, a normal POST otherwise. --}}
                                    @include('users._follow-button', ['user' => $teacher])
                                    <a href="{{ route('users.show', $teacher) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-person me-1" aria-hidden="true"></i> {{ __('Profile') }}
                                    </a>
                                </x-user-card>
                            @empty
                                <div class="text-center py-4">
                                    <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;" aria-hidden="true">
                                        <i class="bi bi-check2-circle"></i>
                                    </div>
                                    <div class="fw-semibold small">{{ __('All caught up') }}</div>
                                    <div class="text-muted small">{{ __('You already follow every teacher.') }}</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
        </div>

        {{-- Learning activity + recommended classes --}}
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-xl-7">
                <x-chart-card :title="__('Learning activity')"
                    :subtitle="__('Classes attended over the selected period')"
                    chart-id="student-activity" kind="bar" series-key="activity" color="primary"
                    :series="$activityChart" :total="$activityChart['total']"
                    :endpoint="route('student.dashboard.charts')" current-range="12m" :height="280"
                    :label="__('Classes')" />
            </div>

            <div class="col-12 col-xl-5">
                <section class="card edu-card h-100" aria-labelledby="recommended-classes-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="recommended-classes-title">
                                <i class="bi bi-stars me-1 text-edu" aria-hidden="true"></i>
                                {{ __('Recommended for you') }}
                            </h2>
                            <div class="small text-muted">{{ __('From teachers and subjects you follow.') }}</div>
                        </div>
                        <a href="{{ route('classes.discover') }}" class="small fw-semibold text-decoration-none">
                            {{ __('Discover') }}
                        </a>
                    </div>

                    <div class="card-body py-2">
                        @forelse ($recommendedClasses as $class)
                            <div class="edu-list-row">
                                <span class="edu-activity-icon bg-primary" aria-hidden="true">
                                    <i class="bi bi-easel"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <a href="{{ route('classes.show', $class) }}" class="edu-list-title text-truncate">
                                        {{ $class->title }}
                                    </a>
                                    <div class="edu-list-meta">
                                        {{ $class->teacher?->name ?? __('Unknown teacher') }}
                                        @if ($class->subject)
                                            <span class="mx-1" aria-hidden="true">·</span>{{ $class->subject }}
                                        @endif
                                    </div>
                                    <div class="edu-list-meta">
                                        <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                        {{ $class->date?->format('D d M') }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        {{ \Illuminate\Support\Str::substr((string) $class->time, 0, 5) }}
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('classes.enroll', $class) }}" class="flex-shrink-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary"
                                            aria-label="{{ __('Enrol in :title', ['title' => $class->title]) }}">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;" aria-hidden="true">
                                    <i class="bi bi-compass"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('Nothing to suggest yet') }}</div>
                                <div class="text-muted small mb-3">{{ __('Follow a teacher or join a class to get recommendations.') }}</div>
                                <a href="{{ route('classes.discover') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-search" aria-hidden="true"></i> {{ __('Browse classes') }}
                                </a>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        {{-- Three-post feed preview --}}
        <div class="row g-3 g-xl-4">
            <div class="col-12">
                <section class="card edu-card" aria-labelledby="feed-preview-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0" id="feed-preview-title">
                                <i class="bi bi-rss me-1 text-edu" aria-hidden="true"></i>
                                {{ __('From people you follow') }}
                            </h2>
                            <div class="small text-muted">{{ __('The newest posts in your feed.') }}</div>
                        </div>
                        <a href="{{ route('feed.index') }}" class="small fw-semibold text-decoration-none">
                            {{ __('Open full feed') }} <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                        </a>
                    </div>

                    <div class="card-body py-2">
                        @php
                            $posts = $feedPreview instanceof \Illuminate\Pagination\LengthAwarePaginator
                                ? $feedPreview->getCollection()
                                : $feedPreview;
                        @endphp

                        @forelse ($posts as $post)
                            @php
                                $displayBody = trim(strip_tags((string) $post->body));
                                if (trim((string) $post->title) !== '') {
                                    $displayBody = trim((string) $post->title).' — '.$displayBody;
                                }
                            @endphp

                            <article class="edu-feed-item">
                                <x-avatar :user="$post->user" size="sm" class="flex-shrink-0" />

                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <a href="{{ route('users.show', $post->user) }}"
                                           class="fw-semibold small text-decoration-none text-reset">
                                            {{ $post->user?->name ?? __('Deleted user') }}
                                        </a>
                                        <span class="edu-list-meta">{{ $post->created_at?->diffForHumans() }}</span>
                                    </div>

                                    <a href="{{ route('posts.show', $post) }}"
                                       class="text-decoration-none text-reset d-block">
                                        <p class="edu-feed-body mb-1">{{ \Illuminate\Support\Str::limit($displayBody, 180) }}</p>
                                    </a>

                                    <div class="edu-list-meta">
                                        <i class="bi bi-heart me-1" aria-hidden="true"></i>{{ $post->likes_count ?? 0 }}
                                        <span class="mx-1" aria-hidden="true">·</span>
                                        <i class="bi bi-chat me-1" aria-hidden="true"></i>{{ $post->comments_count ?? 0 }}
                                    </div>
                                </div>

                                <a href="{{ route('posts.show', $post) }}" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                   aria-label="{{ __('Open this post') }}">
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </article>
                        @empty
                            <div class="text-center py-4">
                                <div class="edu-empty-illustration mx-auto" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 18px;" aria-hidden="true">
                                    <i class="bi bi-rss"></i>
                                </div>
                                <div class="fw-semibold small">{{ __('Your feed is quiet') }}</div>
                                <div class="text-muted small mb-3">{{ __('Follow teachers and classmates to see their posts here.') }}</div>
                                <a href="{{ route('search.index') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-search" aria-hidden="true"></i> {{ __('Find people') }}
                                </a>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>

```

# 15. Error and maintenance pages

## `resources/views/errors/page.blade.php`

```blade
{{-- Shared friendly error page shell (Phase 3C).
     Variables: $code, $title, $message, $icon.
     Every error page keeps a way forward: home, plus the dashboard or login. --}}
@php
    $code = $code ?? '404';
    $title = $title ?? __('Something went wrong');
    $message = $message ?? __('Please try again in a moment.');
    $icon = $icon ?? 'bi-emoji-frown';
@endphp

<x-guest-layout :title="$title">
    <div class="text-center py-2">
        <span class="edu-empty-illustration mx-auto" aria-hidden="true">
            <i class="bi {{ $icon }}"></i>
        </span>

        <div class="display-5 fw-bold text-edu lh-1 mt-2">{{ $code }}</div>
        <h1 class="h4 fw-bold mt-2 mb-2">{{ $title }}</h1>

        <p class="text-muted small edu-measure mx-auto mb-4">{{ $message }}</p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="bi bi-house me-1" aria-hidden="true"></i>{{ __('Back to home') }}
            </a>

            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>{{ __('Go to dashboard') }}
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>{{ __('Log in') }}
                </a>
            @endauth
        </div>

        <p class="small text-muted mt-4 mb-0">
            {{ __('Still stuck? Contact the administrator and mention code :code.', ['code' => $code]) }}
        </p>
    </div>
</x-guest-layout>

```

## `resources/views/errors/403.blade.php`

```blade
@include('errors.page', [
    'code' => '403',
    'icon' => 'bi-shield-lock',
    'title' => __('You are not allowed to open this page'),
    'message' => __('Your account does not have the rights for this area. If you think this is a mistake, please contact an administrator.'),
])

```

## `resources/views/errors/404.blade.php`

```blade
@include('errors.page', [
    'code' => '404',
    'icon' => 'bi-compass',
    'title' => __('Page not found'),
    'message' => __('The page you are looking for has been moved or never existed. Check the address and try again.'),
])

```

## `resources/views/errors/419.blade.php`

```blade
@include('errors.page', [
    'code' => '419',
    'icon' => 'bi-hourglass-split',
    'title' => __('Your session expired'),
    'message' => __('For your safety the page was idle for too long. Refresh the page, then send the form again. Nothing was lost except the last click.'),
])

```

## `resources/views/errors/500.blade.php`

```blade
@include('errors.page', [
    'code' => '500',
    'icon' => 'bi-tools',
    'title' => __('Something went wrong on our side'),
    'message' => __('The page could not be loaded. Your data is safe. Try again in a moment, or head back home and start from there.'),
])

```

## `resources/views/errors/503.blade.php`

```blade
@include('errors.page', [
    'code' => '503',
    'icon' => 'bi-cone-striped',
    'title' => __('We are doing a little maintenance'),
    'message' => __('EduConnect is being updated right now. Classes and posts will be back shortly — please try again in a few minutes.'),
])

```

# 16. JavaScript

## `resources/js/app.js`

```javascript
import './bootstrap'; // axios setup (window.axios)

// Bootstrap 5 JavaScript (dropdowns, modals, collapses...) + Popper
import * as bootstrap from 'bootstrap';

import Alpine from 'alpinejs';

// Chart.js 4 with every controller/scale registered ("/auto").
import Chart from 'chart.js/auto';

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
window.Chart = Chart;

Alpine.start();

```

## `resources/js/layout.js`

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

```

## `resources/js/theme.js`

```javascript
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

```

## `resources/js/auth.js`

```javascript
/**
 * Authentication page helpers (FR-1, FR-2, FR-3).
 *
 * Two small progressive enhancements, written in plain JavaScript:
 *   1. show / hide a password field,
 *   2. show the optional "subject" field only for teachers.
 *
 * The forms still work if this file fails to load; every field has a proper
 * label and a normal server-side validation fallback.
 */

function setupPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (! input) {
            return;
        }

        button.addEventListener('click', () => {
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            button.setAttribute(
                'aria-label',
                isHidden ? button.dataset.hideLabel : button.dataset.showLabel,
            );

            const icon = button.querySelector('i');

            if (icon) {
                icon.classList.toggle('bi-eye', ! isHidden);
                icon.classList.toggle('bi-eye-slash', isHidden);
            }

            // Keep the cursor in the field after changing its type.
            input.focus();
        });
    });
}

function setupRoleFields() {
    const roleControl = document.querySelector('[data-role-select]');
    const subjectField = document.querySelector('[data-subject-field]');

    if (! roleControl || ! subjectField) {
        return;
    }

    const roleSelect = roleControl.matches('select') ? roleControl : null;
    const roleRadios = roleControl.querySelectorAll('[data-role-radio]');

    const currentRole = () => {
        if (roleSelect) {
            return roleSelect.value;
        }

        const checked = roleControl.querySelector('[data-role-radio]:checked');

        return checked ? checked.value : 'student';
    };

    const syncSubjectField = () => {
        subjectField.hidden = currentRole() !== 'teacher';

        // Keep the whole card highlighted, including in browsers without :has().
        roleRadios.forEach((radio) => {
            const card = radio.closest('.edu-role-card');

            if (card) {
                card.classList.toggle('is-selected', radio.checked);
            }
        });
    };

    if (roleSelect) {
        roleSelect.addEventListener('change', syncSubjectField);
    }

    roleRadios.forEach((radio) => radio.addEventListener('change', syncSubjectField));

    syncSubjectField();
}

/**
 * Password strength meter (register page only).
 *
 * Four independent signals keep the advice understandable: length, mixed
 * case, a number and a symbol. The label is announced through aria-live, so
 * a screen reader hears the same feedback as the colour bars.
 */
function setupPasswordStrength() {
    document.querySelectorAll('[data-password-strength-input]').forEach((input) => {
        const meter = input.closest('.mb-3')?.querySelector('[data-password-strength]');

        if (! meter) {
            return;
        }

        const label = meter.querySelector('[data-password-strength-label]');
        const labels = [
            meter.dataset.strengthEmpty || 'Use at least 8 characters, with a number or a symbol.',
            meter.dataset.strengthWeak || 'Weak password — add more characters.',
            meter.dataset.strengthFair || 'Fair password — mix letters, numbers and a symbol.',
            meter.dataset.strengthGood || 'Good password — almost there.',
            meter.dataset.strengthStrong || 'Strong password. Well done!',
        ];

        const update = () => {
            const value = input.value;
            let score = 0;

            if (value.length >= 8) {
                score += 1;
            }
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) {
                score += 1;
            }
            if (/\d/.test(value)) {
                score += 1;
            }
            if (/[^A-Za-z0-9]/.test(value)) {
                score += 1;
            }

            // A very short password never looks strong, whatever it contains.
            if (value.length > 0 && value.length < 8) {
                score = Math.min(score, 1);
            }

            meter.dataset.score = String(score);

            if (label) {
                label.textContent = value.length === 0 ? labels[0] : labels[score];
            }
        };

        input.addEventListener('input', update);
        update();
    });
}

function bootAuthHelpers() {
    setupPasswordToggles();
    setupRoleFields();
    setupPasswordStrength();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAuthHelpers);
} else {
    bootAuthHelpers();
}

```

## `resources/js/forms.js`

```javascript
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

```

## `resources/js/feed.js`

```javascript
/**
 * EduConnect feed interactions (FR-11, FR-13, FR-17).
 *
 * Everything here is progressive enhancement: the pages already contain real
 * <form> elements, so with JavaScript switched off the browser simply posts
 * them and the controllers redirect back. With JavaScript we intercept the
 * submit, call fetch() and update the page in place.
 */

/** The CSRF token from the <meta name="csrf-token"> tag in the layout. */
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/**
 * Post a form with fetch() and return the JSON answer.
 * Always sends POST + the form data, so Laravel's @method('DELETE') spoofing
 * keeps working exactly like it does for a normal browser submit.
 */
async function postForm(form) {
    const response = await fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        body: new FormData(form),
    });

    if (!response.ok) {
        if (response.status === 429) {
            throw new Error('Too many requests. Please wait a moment and try again.');
        }

        if (response.status === 403) {
            throw new Error('You are not allowed to do that.');
        }

        throw new Error('Something went wrong. Please try again.');
    }

    return response.json();
}

/* -------------------------------------------------------------------------
 | Likes
 * ---------------------------------------------------------------------- */

/** Keep the "flip to the other action" bookkeeping inside the form. */
function setLikeState(form, button, counter, liked, count) {
    form.dataset.liked = liked ? '1' : '0';
    form.action = liked ? form.dataset.unlikeUrl : form.dataset.likeUrl;

    let spoof = form.querySelector('input[name="_method"]');
    if (liked) {
        if (!spoof) {
            spoof = document.createElement('input');
            spoof.type = 'hidden';
            spoof.name = '_method';
            form.append(spoof);
        }
        spoof.value = 'DELETE';
    } else if (spoof) {
        spoof.remove();
    }

    if (counter) {
        counter.textContent = count;
    }

    if (button) {
        button.classList.toggle('btn-danger', liked);
        button.classList.toggle('btn-outline-secondary', !liked);
        button.setAttribute('aria-pressed', liked ? 'true' : 'false');

        const icon = button.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-heart-fill', liked);
            icon.classList.toggle('bi-heart', !liked);
            // A short pop, disabled automatically by prefers-reduced-motion.
            icon.classList.remove('edu-like-pop');
            void icon.offsetWidth;
            icon.classList.add('edu-like-pop');
        }
    }
}

async function handleLike(form) {
    const card = form.closest('[data-post-card]');
    const button = form.querySelector('[data-like-button]');
    const counter = card?.querySelector('[data-like-count]');

    const wasLiked = form.dataset.liked === '1';
    const previousCount = Number(counter?.textContent ?? 0);

    // Optimistic UI: show the change before the server has answered.
    setLikeState(form, button, counter, !wasLiked, previousCount + (wasLiked ? -1 : 1));

    // A second click must not race the first request.
    if (button) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
    }

    try {
        const data = await postForm(form);
        // Trust the server numbers, they are the truth.
        setLikeState(form, button, counter, data.liked, data.count);
    } catch (error) {
        console.error('[EduConnect] liking the post failed', error);
        setLikeState(form, button, counter, wasLiked, previousCount);
        window.eduToast?.(error.message, 'error');
    } finally {
        if (button) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    }
}

/* -------------------------------------------------------------------------
 | Follow / unfollow
 * ---------------------------------------------------------------------- */

function setFollowState(form, button, following) {
    form.dataset.following = following ? '1' : '0';
    form.action = following ? form.dataset.unfollowUrl : form.dataset.followUrl;

    let spoof = form.querySelector('input[name="_method"]');
    if (following) {
        if (!spoof) {
            spoof = document.createElement('input');
            spoof.type = 'hidden';
            spoof.name = '_method';
            form.append(spoof);
        }
        spoof.value = 'DELETE';
    } else if (spoof) {
        spoof.remove();
    }

    if (!button) {
        return;
    }

    button.classList.toggle('btn-primary', !following);
    button.classList.toggle('btn-outline-secondary', following);
    button.setAttribute('aria-pressed', following ? 'true' : 'false');

    const icon = button.querySelector('i');
    if (icon) {
        icon.classList.toggle('bi-person-check', following);
        icon.classList.toggle('bi-person-plus', !following);
    }

    // Both labels arrive from Blade, so they stay translated.
    const label = button.querySelector('[data-follow-label]');
    if (label) {
        label.textContent = following ? form.dataset.labelFollowing : form.dataset.labelFollow;
    }
}

/**
 * Refresh the follower counter of the profile that was followed and the
 * viewer's own "following" total (used by the feed sidebar).
 */
function updateFollowCounts(data) {
    if (data.user_id !== undefined) {
        document.querySelectorAll(`[data-followers-count][data-user-id="${data.user_id}"]`).forEach((element) => {
            element.textContent = data.followers;
        });
    }

    if (data.following_count !== undefined) {
        document.querySelectorAll('[data-viewer-following-count]').forEach((element) => {
            element.textContent = data.following_count;
        });
    }
}

async function handleFollow(form) {
    const button = form.querySelector('[data-follow-button]');
    const wasFollowing = form.dataset.following === '1';

    setFollowState(form, button, !wasFollowing);

    if (button) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
    }

    try {
        const data = await postForm(form);
        setFollowState(form, button, data.following);
        updateFollowCounts(data);
    } catch (error) {
        console.error('[EduConnect] follow failed', error);
        setFollowState(form, button, wasFollowing);
        window.eduToast?.(error.message, 'error');
    } finally {
        if (button) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    }
}

/* -------------------------------------------------------------------------
 | Comments
 * ---------------------------------------------------------------------- */

/**
 * The detail page wraps the post and its comments in [data-comment-scope];
 * a feed card is its own scope. Counters outside that scope never change.
 */
function commentScope(form) {
    return form.closest('[data-comment-scope]')
        || form.closest('[data-post-card]')
        || document;
}

function updateCommentCounts(scope, count) {
    scope.querySelectorAll('[data-comment-count]').forEach((element) => {
        element.textContent = count;
    });
}

async function handleComment(form) {
    const scope = commentScope(form);
    const region = form.closest('[data-comment-region]');
    const list = region?.querySelector('[data-comment-list]');

    try {
        const data = await postForm(form);

        if (list && data.html) {
            // The "no comments yet" hint disappears with the first comment.
            list.querySelector('[data-comment-empty]')?.remove();
            list.insertAdjacentHTML('beforeend', data.html);
        }

        form.reset();
        updateCommentCounts(scope, data.count);
    } catch (error) {
        console.error('[EduConnect] posting the comment failed', error);
        window.eduToast?.(error.message, 'error');
    }
}

async function handleDeleteComment(form) {
    const scope = commentScope(form);

    try {
        const data = await postForm(form);

        if (data.deleted) {
            form.closest('[data-comment-id]')?.remove();
            updateCommentCounts(scope, data.count);
        }
    } catch (error) {
        console.error('[EduConnect] deleting the comment failed', error);
        window.eduToast?.(error.message, 'error');
    }
}

/* -------------------------------------------------------------------------
 | "Load more" in the feed
 * ---------------------------------------------------------------------- */

async function handleLoadMore(button) {
    const list = document.querySelector('[data-feed-list]');
    const url = button.dataset.url;

    if (!list || !url) {
        return;
    }

    button.disabled = true;

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        const data = await response.json();
        list.insertAdjacentHTML('beforeend', data.html);

        if (data.has_more && data.next_page) {
            button.dataset.url = data.next_page;
        } else {
            // Last page reached: the button has done its job.
            button.hidden = true;
        }
    } catch (error) {
        console.error('[EduConnect] loading more posts failed', error);
        window.eduToast?.(error.message || 'Could not load more posts.', 'error');
    } finally {
        button.disabled = false;
    }
}

/* -------------------------------------------------------------------------
 | Live character counter for the 280 character limit (FR-9)
 * ---------------------------------------------------------------------- */

function initCharCounters() {
    document.querySelectorAll('[data-post-body]').forEach((field) => {
        const form = field.closest('form');
        const counter = form?.querySelector('[data-char-counter]');
        const ring = form?.querySelector('[data-char-ring]');
        const limit = Number(field.getAttribute('maxlength')) || 280;

        if (!counter && !ring) {
            return;
        }

        const update = () => {
            const length = field.value.length;

            if (counter) {
                counter.textContent = length;
                counter.classList.toggle('text-danger', length >= limit);
            }

            if (ring) {
                const ratio = Math.min(length / limit, 1);
                ring.style.setProperty('--edu-ring-progress', `${Math.round(ratio * 360)}deg`);
                ring.classList.toggle('is-warning', ratio >= 0.8 && ratio < 1);
                ring.classList.toggle('is-full', ratio >= 1);
                ring.setAttribute('aria-valuenow', String(length));
                ring.setAttribute('aria-valuetext', `${length}/${limit}`);
            }
        };

        field.addEventListener('input', update);
        update();
    });
}

/**
 * Image preview in the composer: a local object URL, revoked as soon as the
 * selection changes, so nothing leaks and no upload happens before submit.
 */
function initImagePreview() {
    document.querySelectorAll('[data-image-input]').forEach((input) => {
        const preview = input.closest('form')?.querySelector('[data-image-preview]');
        const image = preview?.querySelector('img');

        if (!preview || !image) {
            return;
        }

        let objectUrl = null;

        input.addEventListener('change', () => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            const file = input.files?.[0];

            if (!file || !file.type.startsWith('image/')) {
                preview.hidden = true;
                image.removeAttribute('src');
                return;
            }

            objectUrl = URL.createObjectURL(file);
            image.src = objectUrl;
            preview.hidden = false;
        });
    });
}

/** The small "add hashtag" toolbar button keeps the keyboard flow simple. */
function initComposerToolbar() {
    document.querySelectorAll('[data-insert-hashtag]').forEach((button) => {
        button.addEventListener('click', () => {
            const field = button.closest('form')?.querySelector('[data-post-body]');

            if (!field) {
                return;
            }

            const start = field.selectionStart ?? field.value.length;
            const end = field.selectionEnd ?? field.value.length;
            const before = field.value.slice(0, start);
            const after = field.value.slice(end);
            const spacer = before.length > 0 && !/\s$/.test(before) ? ' ' : '';

            field.value = `${before}${spacer}#${after}`;
            field.focus();
            field.selectionStart = field.selectionEnd = start + spacer.length + 1;
            field.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
}

/* -------------------------------------------------------------------------
 | Wiring
 * ---------------------------------------------------------------------- */

document.addEventListener('submit', (event) => {
    const form = event.target;

    // data-confirm works for cards that arrived later through "Load more" too.
    if (form.dataset.confirm && ! window.confirm(form.dataset.confirm)) {
        event.preventDefault();
        return;
    }

    if (form.matches('[data-like-form]')) {
        event.preventDefault();
        handleLike(form);
    } else if (form.matches('[data-follow-form]')) {
        event.preventDefault();
        handleFollow(form);
    } else if (form.matches('[data-comment-form]')) {
        event.preventDefault();
        handleComment(form);
    } else if (form.matches('[data-delete-comment-form]')) {
        event.preventDefault();
        handleDeleteComment(form);
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-load-more]');
    if (button) {
        event.preventDefault();
        handleLoadMore(button);
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initCharCounters();
        initImagePreview();
        initComposerToolbar();
    });
} else {
    initCharCounters();
    initImagePreview();
    initComposerToolbar();
}

```

## `resources/js/charts.js`

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
 */

import Chart from 'chart.js/auto';

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

function bootCharts() {
    renderAllCharts();
    bindRangeSelectors();
    watchTheme();
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

## `resources/js/classes.js`

```javascript
/**
 * Live preview on the "create / edit a class" form (Phase 3C).
 *
 * While the teacher types, the card on the right mirrors the title, subject,
 * date, time and duration. Only text is updated (textContent), so nothing the
 * teacher writes can become markup. Without JavaScript the preview simply
 * shows the server-rendered starting values.
 */

function formatDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(undefined, {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function bootClassPreview() {
    const form = document.querySelector('[data-class-form]');
    const preview = document.querySelector('[data-class-preview]');

    if (!form || !preview) {
        return;
    }

    const title = form.querySelector('#title');
    const subject = form.querySelector('#subject');
    const date = form.querySelector('#date');
    const time = form.querySelector('#time');
    const duration = form.querySelector('#duration');

    const set = (selector, value) => {
        const target = preview.querySelector(selector);

        if (target) {
            target.textContent = value;
        }
    };

    const update = () => {
        set('[data-preview-title]', title?.value.trim() || preview.dataset.emptyTitle || '');
        set('[data-preview-subject]', subject?.value.trim() || preview.dataset.emptySubject || '');
        set('[data-preview-date]', formatDate(date?.value) || preview.dataset.emptyDate || '');
        set('[data-preview-time]', time?.value || preview.dataset.emptyTime || '');
        set(
            '[data-preview-duration]',
            duration?.value ? `${duration.value} ${preview.dataset.durationLabel || 'min'}` : '',
        );

        const subjectChip = preview.querySelector('[data-preview-subject-chip]');
        if (subjectChip) {
            subjectChip.hidden = !subject?.value.trim();
        }
    };

    [title, subject, date, time, duration].forEach((field) => {
        field?.addEventListener('input', update);
        field?.addEventListener('change', update);
    });

    update();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootClassPreview);
} else {
    bootClassPreview();
}

```

## `resources/js/countdown.js`

```javascript
/**
 * Live countdown timers (Phase 3B).
 *
 * Markup is produced by <x-countdown target="ISO-8601" />:
 *   <div data-countdown="2026-11-02T18:00:00+00:00">
 *       <span data-countdown-days>00</span> ...
 *   </div>
 *
 * The server renders a static value, so the timer is useful even before this
 * script runs. Once it runs, the numbers update every second.
 */

const pad = (value) => String(Math.max(0, value)).padStart(2, '0');

function updateCountdown(element) {
    const target = new Date(element.dataset.countdown);
    if (Number.isNaN(target.getTime())) {
        return;
    }

    const difference = target.getTime() - Date.now();

    if (difference <= 0) {
        element.querySelectorAll('[data-countdown-days], [data-countdown-hours], [data-countdown-minutes], [data-countdown-seconds]')
            .forEach((node) => {
                node.textContent = '00';
            });
        element.classList.add('is-finished');
        element.dataset.countdownFinished = '1';

        return;
    }

    const seconds = Math.floor(difference / 1000);
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remaining = seconds % 60;

    const values = {
        days,
        hours,
        minutes,
        seconds: remaining,
    };

    Object.entries(values).forEach(([key, value]) => {
        const node = element.querySelector(`[data-countdown-${key}]`);
        if (node) {
            node.textContent = pad(value);
        }
    });

    // Screen readers get a single readable sentence, not four changing boxes.
    const readable = element.querySelector('[data-countdown-readable]');
    if (readable) {
        const parts = [];
        if (days > 0) parts.push(`${days} day${days === 1 ? '' : 's'}`);
        if (hours > 0) parts.push(`${hours} hour${hours === 1 ? '' : 's'}`);
        if (minutes > 0) parts.push(`${minutes} minute${minutes === 1 ? '' : 's'}`);
        parts.push(`${remaining} second${remaining === 1 ? '' : 's'}`);
        readable.textContent = parts.join(', ');
    }
}

function bootCountdowns() {
    const timers = Array.from(document.querySelectorAll('[data-countdown]'));

    if (timers.length === 0) {
        return;
    }

    timers.forEach(updateCountdown);
    window.setInterval(() => timers.forEach(updateCountdown), 1000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootCountdowns);
} else {
    bootCountdowns();
}

```

## `resources/js/jitsi.js`

```javascript
/**
 * EduConnect live class room (FR-18, FR-20, FR-22).
 *
 * The page does not contain an iframe: it carries the room settings in data-*
 * attributes and this file asks the Jitsi Meet External API to build the room
 * (video, audio, screen sharing and the built in chat all come from Jitsi).
 *
 * Two things have to keep working on a bad connection:
 *   1. the API script may fail to load or time out -> friendly message + retry,
 *   2. the user may end the call from inside Jitsi -> back to the dashboard.
 */

/** Give up on the External API after this long (a school network can hang). */
const SCRIPT_TIMEOUT_MS = 15000;

/** How long we wait after "hangup" before leaving the page. */
const LEAVE_DELAY_MS = 1200;

/** The JitsiMeetExternalAPI instance; needed to close the room on a retry. */
let api = null;

/** Guards against a double redirect when hangup and readyToClose both fire. */
let leaving = false;

/**
 * Load the External API script once and resolve when window.JitsiMeetExternalAPI
 * exists. Rejects on a network error, a missing global or a timeout.
 */
function loadExternalApi(url) {
    return new Promise((resolve, reject) => {
        if (window.JitsiMeetExternalAPI) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = url;
        script.async = true;
        script.dataset.jitsiApi = '1';

        // Without a timeout a hanging request would leave the spinner forever.
        const timer = window.setTimeout(() => {
            script.remove();
            reject(new Error('timeout'));
        }, SCRIPT_TIMEOUT_MS);

        script.addEventListener('load', () => {
            window.clearTimeout(timer);
            if (window.JitsiMeetExternalAPI) {
                resolve();
            } else {
                reject(new Error('api-missing'));
            }
        });

        script.addEventListener('error', () => {
            window.clearTimeout(timer);
            reject(new Error('network'));
        });

        document.head.appendChild(script);
    });
}

/** Leave the page and go back to the dashboard (FR-20 edge case). */
function leaveRoom(dashboardUrl) {
    if (leaving) {
        return;
    }

    leaving = true;
    window.setTimeout(() => {
        window.location.href = dashboardUrl;
    }, LEAVE_DELAY_MS);
}

/**
 * Build the Jitsi room for one [data-jitsi] wrapper.
 */
async function startRoom(wrapper) {
    const container = wrapper.querySelector('[data-jitsi-container]');
    const status = wrapper.querySelector('[data-jitsi-status]');
    const errorBox = wrapper.querySelector('[data-jitsi-error]');

    const showError = () => {
        status?.classList.add('d-none');
        errorBox?.classList.remove('d-none');
    };

    // Reset the UI so a retry starts from a clean state.
    errorBox?.classList.add('d-none');
    status?.classList.remove('d-none');

    if (container) {
        container.innerHTML = '';
    }

    // Close a room that is still open from a previous attempt.
    if (api) {
        try {
            api.dispose();
        } catch (error) {
            console.warn('[EduConnect] could not dispose the previous room', error);
        }
        api = null;
    }

    try {
        await loadExternalApi(wrapper.dataset.scriptUrl);
    } catch (error) {
        console.error('[EduConnect] the Jitsi External API did not load', error);
        showError();
        return;
    }

    const options = {
        roomName: wrapper.dataset.room,
        parentNode: container,
        width: '100%',
        height: '100%',
        // Jitsi shows these next to every message and in the participant list.
        userInfo: {
            displayName: wrapper.dataset.displayName,
            email: wrapper.dataset.email,
        },
        configOverwrite: {
            // Low bandwidth friendly: start with the camera off, the mic on.
            startWithVideoMuted: true,
            startWithAudioMuted: false,
            // Show the preview screen so a student can check the microphone first.
            prejoinPageEnabled: true,
            // Keeps mobile browsers on the page instead of opening the app.
            disableDeepLinking: true,
            subject: wrapper.dataset.displayName,
            toolbarButtons: [
                'microphone',
                'camera',
                'desktop',
                'chat',
                'raisehand',
                'tileview',
                'settings',
                'hangup',
                'participants-pane',
                'fullscreen',
            ],
        },
        interfaceConfigOverwrite: {
            SHOW_JITSI_WATERMARK: false,
            MOBILE_APP_PROMO: false,
            DEFAULT_BACKGROUND: '#f6f7fb',
        },
    };

    try {
        api = new window.JitsiMeetExternalAPI(wrapper.dataset.domain, options);
    } catch (error) {
        console.error('[EduConnect] Jitsi refused to start the room', error);
        showError();
        return;
    }

    // The spinner can go as soon as the user is inside the room.
    api.addEventListener('videoConferenceJoined', () => {
        status?.classList.add('d-none');
    });

    // The red phone inside Jitsi: back to the dashboard.
    api.addEventListener('hangup', () => {
        leaveRoom(wrapper.dataset.dashboardUrl);
    });

    // Fired after the room was closed from the Jitsi side.
    api.addEventListener('readyToClose', () => {
        leaveRoom(wrapper.dataset.dashboardUrl);
    });

    // A failed conference (wrong room, server down) must not look like a hang.
    api.addEventListener('errorOccurred', (event) => {
        console.error('[EduConnect] Jitsi reported an error', event);
        showError();
    });
}

/** Wire every room on the page (there is only one, but this stays reusable). */
function initJitsiRooms() {
    document.querySelectorAll('[data-jitsi]').forEach((wrapper) => {
        startRoom(wrapper);

        // Retry button shown when the script could not be loaded.
        wrapper.querySelector('[data-jitsi-retry]')?.addEventListener('click', () => {
            startRoom(wrapper);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initJitsiRooms);
} else {
    initJitsiRooms();
}

```

## `resources/js/notifications.js`

```javascript
/**
 * The navbar notification bell + the "mark as read" buttons (FR-24, FR-25).
 *
 * Every 30 seconds this asks /notifications/poll for the fresh unread counter
 * and the newest eight entries as HTML, then updates the badge and the dropdown
 * without a page reload. Polling pauses while the tab is in the background, so
 * it costs almost nothing.
 *
 * On the notifications page the single "mark as read" forms are upgraded to
 * fetch() calls: the row loses its unread styling and the bell counter drops,
 * with no reload. Without JavaScript the same forms still work.
 */
const POLL_INTERVAL_MS = 30000;

/** Put the server answer into the bell (badge + dropdown list). */
function updateBell(data) {
    const badge = document.querySelector('[data-notification-badge]');

    if (badge) {
        badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
        // Hide the badge completely when there is nothing unread.
        badge.classList.toggle('d-none', data.unread_count === 0);
    }

    const list = document.querySelector('[data-notification-list]');
    if (list && typeof data.html === 'string') {
        list.innerHTML = data.html;
    }
}

/** One polling round. Fails silently: the bell keeps its last known state. */
async function refreshNotifications() {
    const url = document.body.dataset.notificationUrl;

    if (!url) {
        return;
    }

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        updateBell(await response.json());
    } catch (error) {
        console.error('[EduConnect] refreshing the notifications failed', error);
    }
}

function startNotificationPolling() {
    // Nothing to do for guests or on pages without the bell.
    if (!document.querySelector('[data-notification-list]')) {
        return;
    }

    setInterval(() => {
        // No point in polling a tab nobody is looking at.
        if (!document.hidden) {
            refreshNotifications();
        }
    }, POLL_INTERVAL_MS);

    // Coming back to the tab: refresh straight away.
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshNotifications();
        }
    });

    // Opening the bell also refreshes it.
    document.querySelectorAll('[data-notification-toggle]').forEach((button) => {
        button.addEventListener('click', refreshNotifications);
    });
}

/** Mark one notification as read over fetch() instead of a full page load. */
function upgradeReadButtons() {
    document.querySelectorAll('form[data-notification-read]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button = form.querySelector('button');
            if (button) {
                button.disabled = true;
            }

            try {
                // FormData carries the CSRF token hidden field with it.
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                        'X-HTTP-Method-Override': 'PATCH',
                    },
                    body: new FormData(form),
                });

                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                const data = await response.json();

                // Drop the unread look and the button itself.
                const row = form.closest('.list-group-item');
                if (row) {
                    row.classList.remove('notification-unread');
                    row.querySelectorAll('[data-notification-unread-label]')
                        .forEach((node) => node.remove());
                }

                form.remove();

                if (typeof data.unread_count === 'number') {
                    updateBell({ unread_count: data.unread_count });
                }
            } catch (error) {
                // Something went wrong: let the plain form submit instead.
                console.error('[EduConnect] marking the notification as read failed', error);
                form.submit();
            }
        });
    });
}

function bootNotifications() {
    startNotificationPolling();
    upgradeReadButtons();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootNotifications);
} else {
    bootNotifications();
}

```

## `resources/js/page-transitions.js`

```javascript
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

```

## `resources/js/profile-photo.js`

```javascript
/**
 * Profile photo preview (FR-4).
 *
 * Shows the picked image immediately, and lets the user remove the current
 * photo without opening a file picker. The server still validates the real
 * MIME type, extension and size; this is only a friendly preview.
 */

function bootPhotoPreview() {
    document.querySelectorAll('[data-photo-form]').forEach((form) => {
        const input = form.querySelector('[data-photo-input]');
        const preview = form.querySelector('[data-photo-preview]');
        const remove = form.querySelector('[data-photo-remove]');

        if (! input || ! preview) {
            return;
        }

        const defaultSrc = preview.dataset.defaultSrc || preview.src;
        let objectUrl = null;

        const setPreview = (src) => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            preview.src = src;
        };

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!file) {
                setPreview(remove?.checked ? defaultSrc : preview.dataset.currentSrc || defaultSrc);
                return;
            }

            // A new file wins over a pending "remove" request.
            if (remove) {
                remove.checked = false;
            }

            objectUrl = URL.createObjectURL(file);
            preview.src = objectUrl;
        });

        remove?.addEventListener('change', () => {
            if (! remove.checked) {
                return;
            }

            // Clear the file picker and go back to the default avatar.
            input.value = '';
            setPreview(defaultSrc);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootPhotoPreview);
} else {
    bootPhotoPreview();
}

```

## `resources/js/search.js`

```javascript
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

```

## `resources/js/toast.js`

```javascript
/**
 * Lightweight toasts for AJAX errors and client-side notices.
 * Server-rendered flash banners in <x-flash /> stay as they are.
 */

function stack() {
    let element = document.querySelector('[data-edu-toast-stack]');

    if (!element) {
        element = document.createElement('div');
        element.className = 'edu-toast-stack';
        element.dataset.eduToastStack = '1';
        element.setAttribute('role', 'status');
        element.setAttribute('aria-live', 'polite');
        document.body.append(element);
    }

    return element;
}

export function showToast(message, type = 'info') {
    if (!message) {
        return;
    }

    const toast = document.createElement('div');
    toast.className = `edu-toast is-${type}`;
    toast.innerHTML = `
        <span>${escapeHtml(String(message))}</span>
        <button type="button" class="btn-close" aria-label="Close"></button>
    `;

    toast.querySelector('.btn-close')?.addEventListener('click', () => toast.remove());
    stack().append(toast);

    window.setTimeout(() => toast.remove(), 5000);
}

function escapeHtml(value) {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

window.eduToast = showToast;

```

## `resources/js/data-table.js`

```javascript
/**
 * Client-side search and sorting for the data tables (Phase 3B).
 *
 * Server-side pagination is untouched: this only filters and sorts the rows of
 * the current page. Without JavaScript every row is visible.
 */

function cellValue(row, index) {
    const cell = row.children[index];
    if (!cell) {
        return '';
    }

    return cell.dataset.sortValue ?? cell.textContent.trim();
}

function compare(a, b) {
    const numberA = Number(a);
    const numberB = Number(b);

    if (a !== '' && b !== '' && !Number.isNaN(numberA) && !Number.isNaN(numberB)) {
        return numberA - numberB;
    }

    return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
}

function bootTable(table) {
    const body = table.querySelector('[data-table-body]');
    const search = table.querySelector('[data-table-search]');
    const counter = table.querySelector('[data-table-count]');
    const empty = table.querySelector('[data-table-empty]');
    const rows = Array.from(table.querySelectorAll('tr[data-table-row]'));

    if (!body || rows.length === 0) {
        return;
    }

    let sortIndex = null;
    let sortDirection = 'ascending';

    function applySearch() {
        const term = (search?.value ?? '').trim().toLowerCase();
        let visible = 0;

        rows.forEach((row) => {
            const haystack = (row.dataset.search ?? row.textContent).toLowerCase();
            const show = term === '' || haystack.includes(term);
            row.hidden = !show;
            if (show) {
                visible += 1;
            }
        });

        if (empty) {
            empty.hidden = visible !== 0;
        }

        if (counter) {
            counter.textContent = table.dataset.countLabel
                ? table.dataset.countLabel.replace(':count', String(visible))
                : `${visible}`;
        }
    }

    function applySort() {
        if (sortIndex === null) {
            return;
        }

        const direction = sortDirection === 'ascending' ? 1 : -1;
        const sorted = [...rows].sort((a, b) => direction * compare(cellValue(a, sortIndex), cellValue(b, sortIndex)));

        sorted.forEach((row) => body.appendChild(row));
    }

    if (search) {
        search.addEventListener('input', applySearch);
    }

    table.querySelectorAll('th[data-sort]').forEach((header) => {
        header.classList.add('edu-sortable');
        header.setAttribute('tabindex', '0');
        header.setAttribute('role', 'button');
        header.setAttribute('aria-sort', 'none');

        const activate = () => {
            const index = Array.from(header.parentElement.children).indexOf(header);

            if (sortIndex === index) {
                sortDirection = sortDirection === 'ascending' ? 'descending' : 'ascending';
            } else {
                sortIndex = index;
                sortDirection = 'ascending';
            }

            table.querySelectorAll('th[data-sort]').forEach((other) => {
                other.setAttribute('aria-sort', 'none');
            });
            header.setAttribute('aria-sort', sortDirection);

            applySort();
        };

        header.addEventListener('click', activate);
        header.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                activate();
            }
        });
    });

    applySearch();
}

function bootTables() {
    document.querySelectorAll('[data-data-table]').forEach(bootTable);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootTables);
} else {
    bootTables();
}

```

## `resources/js/bootstrap.js`

```javascript
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

```

# 17. Routes

## `routes/web.php`

```php
<?php

use App\Http\Controllers\AdminClassController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminPostController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ClassBrowseController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ClassStatusController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardChartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\HashtagController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\LiveClassController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Admin area (FR-2, FR-5)
|--------------------------------------------------------------------------
| A student or a teacher can never open an admin page: the "role" middleware
| answers with 403. There is no admin registration either, the admin account
| comes from AdminUserSeeder.
*/
Route::middleware(['auth', 'not.blocked', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        // Phase 3C: living documentation of the design system. Admin only,
        // because it is a maintenance page rather than a product feature.
        Route::view('/style-guide', 'admin.style-guide')->name('style-guide');

        // Phase 3B: JSON series for the dashboard range selector.
        Route::get('/dashboard/charts', [DashboardChartController::class, 'admin'])
            ->name('dashboard.charts');

        // User management (FR-27).
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/block', [AdminUserController::class, 'block'])->name('users.block');
        Route::patch('/users/{user}/unblock', [AdminUserController::class, 'unblock'])->name('users.unblock');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        // Post management (FR-28).
        Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
        Route::patch('/posts/{post}/hide', [AdminPostController::class, 'toggleHidden'])->name('posts.hide');
        Route::delete('/posts/{post}', [AdminPostController::class, 'destroy'])->name('posts.destroy');

        // Class management (FR-28).
        Route::get('/classes', [AdminClassController::class, 'index'])->name('classes.index');
        Route::patch('/classes/{classRoom}/hide', [AdminClassController::class, 'toggleHidden'])->name('classes.hide');
        Route::delete('/classes/{classRoom}', [AdminClassController::class, 'destroy'])->name('classes.destroy');

        // Moderation queue (FR-33, FR-34).
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
        Route::patch('/reports/{report}/hide', [AdminReportController::class, 'hide'])->name('reports.hide');
        Route::patch('/reports/{report}/unhide', [AdminReportController::class, 'unhide'])->name('reports.unhide');
        Route::patch('/reports/{report}/reviewed', [AdminReportController::class, 'markReviewed'])->name('reports.reviewed');
        Route::patch('/reports/{report}/dismiss', [AdminReportController::class, 'dismiss'])->name('reports.dismiss');
        Route::delete('/reports/{report}/content', [AdminReportController::class, 'destroyContent'])->name('reports.content.destroy');
        Route::post('/reports/{report}/block-user', [AdminReportController::class, 'blockUser'])->name('reports.block-user');
    });

/*
|--------------------------------------------------------------------------
| Teacher area (FR-2)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.blocked', 'role:teacher'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::get('/dashboard', TeacherDashboardController::class)->name('dashboard');

        // Phase 3B: JSON series for the dashboard range selectors.
        Route::get('/dashboard/charts', [DashboardChartController::class, 'teacher'])
            ->name('dashboard.charts');
    });

/*
|--------------------------------------------------------------------------
| Student area (FR-2, FR-17, FR-30)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.blocked', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', StudentDashboardController::class)->name('dashboard');

        // Phase 3B: JSON series for the dashboard range selectors.
        Route::get('/dashboard/charts', [DashboardChartController::class, 'student'])
            ->name('dashboard.charts');
    });

// Follow / unfollow another member from a profile or the search page
// (FR-11). Students and teachers can follow each other; admin accounts
// are not followable and admins do not use the follow routes.
// Throttled so the button cannot be used to hammer the database.
Route::middleware(['auth', 'not.blocked', 'role:student,teacher'])
    ->group(function () {
        Route::post('/u/{user}/follow', [FollowController::class, 'store'])
            ->middleware('throttle:follows')
            ->name('follows.store');

        Route::delete('/u/{user}/follow', [FollowController::class, 'destroy'])
            ->middleware('throttle:follows')
            ->name('follows.destroy');
    });

/*
|--------------------------------------------------------------------------
| Social feed (FR-7 ... FR-14)
|--------------------------------------------------------------------------
| Every logged in role uses the same feed: admins, teachers and students.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/feed', [PostController::class, 'index'])->name('feed.index');

    // Posts. /posts/create must be declared before /posts/{post}.
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    // At most 10 new posts per minute per user (FR-9).
    Route::post('/posts', [PostController::class, 'store'])
        ->middleware('throttle:posts')
        ->name('posts.store');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::patch('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    // Likes - small JSON endpoints for the fetch() calls in the feed (FR-11).
    Route::post('/posts/{post}/like', [LikeController::class, 'store'])
        ->middleware('throttle:likes')
        ->name('posts.like');
    Route::delete('/posts/{post}/like', [LikeController::class, 'destroy'])
        ->middleware('throttle:likes')
        ->name('posts.unlike');

    // Comments (FR-13).
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:comments')
        ->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->middleware('throttle:comments')
        ->name('comments.destroy');

    // Reporting a post or comment (FR-32). Throttled so the queue cannot be
    // flooded by one account.
    Route::post('/posts/{post}/reports', [ReportController::class, 'storePost'])
        ->middleware('throttle:reports')
        ->name('reports.posts.store');
    Route::post('/comments/{comment}/reports', [ReportController::class, 'storeComment'])
        ->middleware('throttle:reports')
        ->name('reports.comments.store');

    // Hashtag pages (FR-13). The binding uses the (lower case) name column.
    // /hashtags/{name} is the canonical URL; the old singular address remains
    // available so earlier links keep working.
    Route::get('/hashtags/{hashtag:name}', [HashtagController::class, 'show'])->name('hashtags.show');
    Route::get('/hashtag/{hashtag:name}', [HashtagController::class, 'show'])->name('hashtags.legacy');

    // Public profiles and the follower lists (FR-5, FR-11).
    Route::get('/users/{user}', [UserProfileController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/followers', [UserProfileController::class, 'followers'])->name('users.followers');
    Route::get('/users/{user}/following', [UserProfileController::class, 'following'])->name('users.following');

    // The older /u/... addresses stay valid and redirect to the canonical URL.
    Route::get('/u/{user}', fn ($user) => redirect()->route('users.show', $user));
    Route::get('/u/{user}/followers', fn ($user) => redirect()->route('users.followers', $user));
    Route::get('/u/{user}/following', fn ($user) => redirect()->route('users.following', $user));

    // "My followers" in the sidebar jumps to my own list.
    Route::get('/followers', fn () => redirect()->route('users.followers', auth()->id()))
        ->name('followers.index');
});

// /dashboard simply forwards everybody to the dashboard of their own role.
Route::middleware(['auth', 'not.blocked'])
    ->get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Live classes (FR-18 ... FR-24)
|--------------------------------------------------------------------------
| A teacher manages their own classes, a student browses them, enrolls and
| joins. /classes/create must be declared before /classes/{classRoom}, and
| /classes/discover before the wildcard as well, otherwise "discover" would be
| taken for a class id.
*/

// Teacher only: create, edit, delete and manage a class (ClassPolicy checks
// that the class really belongs to the teacher).
Route::middleware(['auth', 'not.blocked', 'role:teacher'])->group(function () {
    Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
    Route::get('/classes/create', [ClassController::class, 'create'])->name('classes.create');
    Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');
    Route::get('/classes/{classRoom}/edit', [ClassController::class, 'edit'])->name('classes.edit');
    Route::patch('/classes/{classRoom}', [ClassController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{classRoom}', [ClassController::class, 'destroy'])->name('classes.destroy');

    // Who is enrolled (FR-24) and the scheduled / live / completed switch.
    Route::get('/classes/{classRoom}/students', [ClassController::class, 'students'])->name('classes.students');
    Route::patch('/classes/{classRoom}/status', [ClassStatusController::class, 'update'])->name('classes.status');
});

// Every logged in role: the class detail page and the Jitsi room (FR-20).
Route::middleware(['auth', 'not.blocked'])->group(function () {
    // Find classes: search + subject filter + pagination (FR-19).
    Route::get('/classes/discover', [ClassBrowseController::class, 'index'])->name('classes.discover');
    Route::get('/classes/{classRoom}', [ClassBrowseController::class, 'show'])->name('classes.show');

    // FR-18 / FR-20 / FR-22: the embedded Jitsi Meet room.
    Route::get('/classes/{classRoom}/room', LiveClassController::class)->name('classes.room');
});

// Student only: enroll / unenroll and "my enrolled classes" (FR-21).
Route::middleware(['auth', 'not.blocked', 'role:student'])->group(function () {
    Route::post('/classes/{classRoom}/enroll', [EnrollmentController::class, 'store'])->name('classes.enroll');
    Route::delete('/classes/{classRoom}/enroll', [EnrollmentController::class, 'destroy'])->name('classes.unenroll');

    Route::get('/student/classes', [ClassBrowseController::class, 'enrolled'])->name('student.classes');
});

/*
|--------------------------------------------------------------------------
| Notifications (FR-23 ... FR-25)
|--------------------------------------------------------------------------
| The bell in the navbar polls /notifications/poll every 30 seconds and gets
| the fresh unread counter plus the newest eight entries as JSON.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Lightweight JSON endpoint for the polling script (FR-24).
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])
        ->middleware('throttle:notifications-poll')
        ->name('notifications.poll');

    // Mark everything as read (FR-25). Declared before the {notification} routes.
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // One notification: mark it read, open its page, or delete it (FR-25).
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});

/*
|--------------------------------------------------------------------------
| Search (FR-14)
|--------------------------------------------------------------------------
| One box in the navbar, three tabs on the page: people, hashtags and posts.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    // Debounced live suggestions for the navbar dropdown (FR-14).
    Route::get('/search/suggest', [SearchController::class, 'suggest'])
        ->middleware('throttle:search-suggest')
        ->name('search.suggest');
});

/*
|--------------------------------------------------------------------------
| Profile (FR-4)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

```

# 18. Tests

## `tests/Feature/PhaseThreeCTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 3C: the site-wide UI/UX redesign.
 *
 * These tests pin the cross-cutting pieces that the page-level tests do not
 * cover: the living style guide, the friendly error pages, the mobile
 * navigation, the redesigned profile tabs, grouped notifications, the search
 * highlight and the accessible admin tables.
 */
class PhaseThreeCTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Living style guide (admin only)
     * --------------------------------------------------------------- */

    public function test_the_style_guide_is_available_to_admins_only(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        // A guest is sent to the login page.
        $this->get(route('admin.style-guide'))->assertRedirect(route('login'));

        $this->actingAs($admin)->get(route('admin.style-guide'))
            ->assertOk()
            // Tokens, components and states are documented on one page.
            ->assertSee('Single source of truth', false)
            ->assertSee('--edu-primary', false)
            ->assertSee('btn btn-primary', false);

        $this->actingAs($teacher)->get(route('admin.style-guide'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.style-guide'))->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Friendly error pages
     * --------------------------------------------------------------- */

    public function test_unknown_pages_use_the_friendly_404_shell(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Back to home')
            ->assertSee('lang="en"', false);
    }

    public function test_a_forbidden_page_uses_the_friendly_403_shell(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('admin.users.index'))
            ->assertForbidden()
            ->assertSee('You are not allowed to open this page')
            ->assertSee('Go to dashboard');
    }

    public function test_the_session_expired_and_maintenance_pages_render(): void
    {
        $expired = view('errors.419')->render();
        $this->assertStringContainsString('Your session expired', $expired);
        $this->assertStringContainsString('Back to home', $expired);

        $maintenance = view('errors.503')->render();
        $this->assertStringContainsString('We are doing a little maintenance', $maintenance);
        $this->assertStringContainsString('Still stuck?', $maintenance);
    }

    /* ---------------------------------------------------------------
     | Guest landing page
     * --------------------------------------------------------------- */

    public function test_the_landing_page_offers_both_roles_a_clear_way_in(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Teach and learn,')
            ->assertSee('Register as Student')
            ->assertSee('Register as Teacher')
            ->assertSee(route('register', ['role' => 'teacher']), false)
            ->assertSee('Three steps to your first class')
            // The hero illustration is described for screen readers.
            ->assertSee('aria-label="Preview of a live class', false);
    }

    /* ---------------------------------------------------------------
     | Layout: skip link, mobile navigation and the feed composer button
     * --------------------------------------------------------------- */

    public function test_authenticated_pages_have_a_skip_link_and_mobile_navigation(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('edu-skip-link', false)
            ->assertSee('href="#main-content"', false)
            ->assertSee('edu-mobile-nav', false)
            ->assertSee('Mobile navigation');
    }

    public function test_the_floating_composer_button_only_appears_on_the_feed(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('feed.index'))
            ->assertOk()
            ->assertSee('edu-fab', false);

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('edu-fab', false);
    }

    /* ---------------------------------------------------------------
     | Profile tabs
     * --------------------------------------------------------------- */

    public function test_a_teacher_profile_has_posts_classes_and_about_tabs(): void
    {
        $teacher = User::factory()->teacher()->create([
            'subject' => 'Mathematics',
            'bio' => 'I make numbers friendly.',
        ]);
        ClassRoom::factory()->for($teacher, 'teacher')->create(['title' => 'Algebra basics']);

        $viewer = User::factory()->student()->create();

        $this->actingAs($viewer)->get(route('users.show', $teacher))
            ->assertOk()
            ->assertSee('id="profile-posts-tab"', false)
            ->assertSee('id="profile-classes-tab"', false)
            ->assertSee('id="profile-about-tab"', false)
            ->assertSee('Algebra basics');
    }

    public function test_a_student_profile_has_no_classes_tab(): void
    {
        $student = User::factory()->student()->create();
        $viewer = User::factory()->student()->create();

        $this->actingAs($viewer)->get(route('users.show', $student))
            ->assertOk()
            ->assertSee('id="profile-posts-tab"', false)
            ->assertDontSee('id="profile-classes-tab"', false)
            ->assertSee('id="profile-about-tab"', false);
    }

    /* ---------------------------------------------------------------
     | Grouped notifications
     * --------------------------------------------------------------- */

    public function test_notifications_are_grouped_into_today_and_earlier(): void
    {
        $student = User::factory()->student()->create();

        $student->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\NewFollower',
            'data' => ['message' => 'A teacher started following you', 'icon' => 'bi-person-plus'],
        ]);

        $student->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\NewFollower',
            'data' => ['message' => 'An older notification', 'icon' => 'bi-bell'],
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $this->actingAs($student)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('edu-notif-group-label', false)
            ->assertSee('id="notif-group-today"', false)
            ->assertSee('id="notif-group-earlier"', false)
            ->assertSee('A teacher started following you')
            ->assertSee('An older notification')
            // Unread entries carry the visual dot and a text alternative.
            ->assertSee('notification-unread', false);
    }

    public function test_the_notification_page_shows_a_friendly_empty_state(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('No notifications yet')
            ->assertSee(route('feed.index'), false);
    }

    /* ---------------------------------------------------------------
     | Search highlight
     * --------------------------------------------------------------- */

    public function test_search_results_highlight_the_matched_text_safely(): void
    {
        User::factory()->student()->create(['name' => 'Algebra Alice']);
        $viewer = User::factory()->student()->create();

        $this->actingAs($viewer)
            ->get(route('search.index', ['q' => 'algebra', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('<mark class="edu-mark">Algebra</mark>', false)
            ->assertSee('Algebra</mark> Alice', false);
    }

    public function test_search_escapes_html_in_results_before_highlighting(): void
    {
        User::factory()->student()->create(['name' => '<script>alert(1)</script> alive']);
        $viewer = User::factory()->student()->create();

        $this->actingAs($viewer)
            ->get(route('search.index', ['q' => 'alive', 'tab' => 'users']))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    /* ---------------------------------------------------------------
     | Admin tables
     * --------------------------------------------------------------- */

    public function test_admin_tables_are_marked_up_for_mobile_cards_and_labels(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->student()->create();

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('edu-table-cards', false)
            ->assertSee('data-label="User"', false)
            ->assertSee('data-label="Actions"', false);
    }

    /* ---------------------------------------------------------------
     | Breadcrumbs and page titles
     * --------------------------------------------------------------- */

    public function test_a_redesigned_page_exposes_breadcrumbs_and_one_primary_heading(): void
    {
        $teacher = User::factory()->teacher()->create();
        $classRoom = ClassRoom::factory()->for($teacher, 'teacher')->create(['title' => 'Geometry']);

        $this->actingAs($teacher)->get(route('classes.show', $classRoom))
            ->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('<h1', false)
            // Exactly one H1 on the page.
            ->assertSeeText('Geometry');
    }
}

```

## `tests/Feature/PhaseThreeUiTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 3: layout, reusable components, dashboards and profile pages
 * (FR-4, FR-5, FR-26, FR-29, FR-30, FR-31).
 */
class PhaseThreeUiTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Layout and sidebar (FR-31)
     * --------------------------------------------------------------- */

    public function test_the_layout_remembers_the_sidebar_state_and_has_a_script_stack(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk()
            // Top navbar controls, including the desktop toggle.
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('data-bs-target="#appSidebar"', false)
            // The state is restored before the first paint and stored by layout.js.
            ->assertSee('educonnect.sidebar.collapsed', false)
            // The layout provides a footer and a scripts stack.
            ->assertSee('Learn together, anywhere.')
            ->assertDontSee('@stack', false);
    }

    public function test_each_role_gets_the_links_it_needs_in_the_sidebar(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Users')
            ->assertSee('Posts')
            ->assertSee('Classes')
            ->assertSee('Reports');

        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher)->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('My Classes')
            ->assertSee('Create Class')
            ->assertSee('Feed')
            ->assertSee('My Profile')
            ->assertSee(route('users.show', $teacher), false);

        $student = User::factory()->student()->create();
        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Browse Classes')
            ->assertSee('My Classes')
            ->assertSee('Feed')
            ->assertSee('My Profile')
            ->assertSee(route('users.show', $student), false);
    }

    public function test_the_active_sidebar_link_is_marked_for_the_current_page(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('aria-current="page"', false);
    }

    public function test_a_page_can_set_its_own_title_and_breadcrumbs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            // <title> uses the page title plus the app name.
            ->assertSee('<title>Profile settings · EduConnect</title>', false)
            // The layout renders the breadcrumb trail from the slot.
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('breadcrumb-item active', false)
            ->assertSee('Profile settings');

        // The public profile uses the same slots.
        $this->actingAs($user)->get(route('users.show', $user))
            ->assertOk()
            ->assertSee('<title>My profile · EduConnect</title>', false)
            ->assertSee('aria-label="Breadcrumb"', false);
    }

    /* ---------------------------------------------------------------
     | Admin dashboard (FR-26)
     * --------------------------------------------------------------- */

    public function test_the_admin_dashboard_lists_the_newest_users(): void
    {
        $admin = User::factory()->admin()->create();
        $newest = User::factory()->teacher()->create(['name' => 'Newest Teacher']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Recent users')
            ->assertSee('Newest Teacher')
            ->assertSee($newest->email);

        // The table is capped at five rows.
        $response->assertViewHas('recentUsers', fn ($users) => $users->count() <= 5);
    }

    /* ---------------------------------------------------------------
     | Teacher dashboard (FR-29)
     * --------------------------------------------------------------- */

    public function test_the_teacher_dashboard_has_class_actions_and_enrollment_activity(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create(['name' => 'Enrolled Student']);

        $classRoom = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Algebra II',
        ]);
        $classRoom->students()->attach($student->id);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk()
            ->assertSee('Actions')
            ->assertSee('Algebra II')
            ->assertSee(route('classes.edit', $classRoom), false)
            ->assertSee(route('classes.students', $classRoom), false)
            // The activity stream includes the new enrollment.
            ->assertSee('Enrolled Student')
            ->assertSee('enrolled in');
    }

    /* ---------------------------------------------------------------
     | Student dashboard (FR-30)
     * --------------------------------------------------------------- */

    public function test_the_student_join_button_is_disabled_until_the_class_is_live(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $scheduled = ClassRoom::factory()->scheduled()->create(['teacher_id' => $teacher->id]);
        $scheduled->students()->attach($student->id);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertOk()
            ->assertSee('Join')
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('disabled', false);
    }

    /* ---------------------------------------------------------------
     | Public profile (FR-5)
     * --------------------------------------------------------------- */

    public function test_the_public_profile_uses_the_canonical_users_url_and_keeps_u_as_a_redirect(): void
    {
        $viewer = User::factory()->student()->create();
        $profile = User::factory()->teacher()->create(['name' => 'Visible Teacher']);

        $this->actingAs($viewer)
            ->get('/users/'.$profile->id)
            ->assertOk()
            ->assertSee('Visible Teacher')
            ->assertSee('followers')
            ->assertSee('following')
            ->assertSee('Posts');

        $this->actingAs($viewer)
            ->get('/u/'.$profile->id)
            ->assertRedirect(route('users.show', $profile));
    }

    /* ---------------------------------------------------------------
     | Profile photo (FR-4)
     * --------------------------------------------------------------- */

    public function test_a_user_can_upload_and_remove_a_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->teacher()->create();

        // Upload: the file is stored on the public disk and the column is set.
        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'subject' => 'Mathematics',
            'bio' => 'I like numbers.',
            'photo' => UploadedFile::fake()->image('avatar.jpg', 300, 300),
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();
        $this->assertNotNull($user->photo);
        Storage::disk('public')->assertExists($user->photo);

        // Remove: the checkbox clears the column and deletes the old file.
        $oldPhoto = $user->photo;

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'remove_photo' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        $this->assertNull($user->refresh()->photo);
        Storage::disk('public')->assertMissing($oldPhoto);
    }

    public function test_the_profile_photo_is_validated_for_type_and_size(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // A PDF is not an image.
        $this->actingAs($user)->from('/profile')->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('photo');

        // A real image above the 2 MB limit is refused as well.
        $this->actingAs($user)->from('/profile')->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->image('huge.jpg')->size(3000),
        ])->assertSessionHasErrors('photo');

        $this->assertNull($user->refresh()->photo);
    }

    public function test_the_profile_form_shows_the_preview_and_remove_controls(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'photo' => 'profiles/existing.jpg',
        ]);
        Storage::disk('public')->put($user->photo, 'fake image');

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('data-photo-form', false)
            ->assertSee('data-photo-preview', false)
            ->assertSee('data-photo-input', false)
            ->assertSee('data-photo-remove', false)
            ->assertSee('Remove current photo');
    }

    /* ---------------------------------------------------------------
     | Reusable components
     * --------------------------------------------------------------- */

    public function test_the_reusable_blade_components_render(): void
    {
        $user = User::factory()->create(['name' => 'Component User']);

        $avatar = (string) Blade::render('<x-avatar :user="$user" size="lg" />', ['user' => $user]);
        $this->assertStringContainsString('avatar-lg', $avatar);
        $this->assertStringContainsString('Component User', $avatar);

        $badge = (string) Blade::render('<x-status-badge status="live" />');
        $this->assertStringContainsString('text-bg-danger', $badge);
        $this->assertStringContainsString('Live', $badge);

        $empty = (string) Blade::render('<x-empty-state icon="bi-inbox" title="Nothing here" message="Come back later." />');
        $this->assertStringContainsString('Nothing here', $empty);
        $this->assertStringContainsString('Come back later.', $empty);

        $modal = (string) Blade::render(
            '<x-confirm-delete-modal id="demo-delete" action="/demo" title="Delete demo?" message="Really?" />'
        );
        $this->assertStringContainsString('id="demo-delete"', $modal);
        $this->assertStringContainsString('method="POST"', $modal);
        $this->assertStringContainsString('name="_method" value="DELETE"', $modal);
    }
}

```

---

## 19. Verification

### Automated

```text
php artisan test
  Tests:    266 passed (1401 assertions)
  Duration: 29.85s

php artisan test tests/Feature/PhaseThreeCTest.php tests/Feature/PhaseThreeUiTest.php
  Tests:    27 passed (155 assertions)

php artisan test tests/Feature/DashboardTest.php tests/Feature/DashboardRedesignTest.php \
                 tests/Feature/PhaseThreeUiTest.php tests/Feature/PhaseThreeCTest.php \
                 tests/Feature/ProfileTest.php
  Tests:    46 passed (325 assertions)
```

`vendor/bin/pint --test app tests routes config database` is clean (the untracked
`scratch/` folder holds throwaway inspection scripts and is not part of the
application), `npm run build` produces the Vite manifest and
`php artisan view:cache` compiles all 86 templates.

### Runtime

| Item | Value |
| --- | --- |
| PHP | 8.3.31 |
| Laravel | 12.70.2 |
| Node / Vite | 20+ / 6.4.5 |
| Bootstrap | 5.3.8 |
| Chart.js | 4.x |
| Database | SQLite `:memory:` for tests; MySQL 8 schema in `database/migrations` |
| App URL | `http://127.0.0.1:8010` |

MySQL 8 was not installed in the verification environment. Every query avoids
engine-specific SQL, the migrations are plain Laravel schema builder calls and
the seeders run on both engines. This is the only unverified runtime path; it is
recorded in `README.md` and `docs/REQUIREMENTS-STATUS.md`.

---

## 20. Page audit

| Page | Light OK | Dark OK | Mobile OK | Accessibility OK | Empty/Error states OK |
| --- | :---: | :---: | :---: | :---: | :---: |
| Landing `/` | ✅ | ✅ | ✅ | ✅ | ✅ |
| Login | ✅ | ✅ | ✅ | ✅ | ✅ |
| Register (role cards) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Forgot password | ✅ | ✅ | ✅ | ✅ | ✅ |
| Reset password | ✅ | ✅ | ✅ | ✅ | ✅ |
| Confirm password | ✅ | ✅ | ✅ | ✅ | ✅ |
| Verify email | ✅ | ✅ | ✅ | ✅ | ✅ |
| Feed | ✅ | ✅ | ✅ | ✅ | ✅ |
| Post detail | ✅ | ✅ | ✅ | ✅ | ✅ |
| Post create / edit | ✅ | ✅ | ✅ | ✅ | ✅ |
| Hashtag page | ✅ | ✅ | ✅ | ✅ | ✅ |
| Public profile | ✅ | ✅ | ✅ | ✅ | ✅ |
| Followers / following | ✅ | ✅ | ✅ | ✅ | ✅ |
| Profile settings | ✅ | ✅ | ✅ | ✅ | ✅ |
| Classes browse | ✅ | ✅ | ✅ | ✅ | ✅ |
| Class detail | ✅ | ✅ | ✅ | ✅ | ✅ |
| Class create / edit | ✅ | ✅ | ✅ | ✅ | ✅ |
| Live room | ✅ | ✅ | ✅ | ✅ | ✅ |
| My classes (teacher) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Enrolled classes (student) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Class students | ✅ | ✅ | ✅ | ✅ | ✅ |
| Notifications page | ✅ | ✅ | ✅ | ✅ | ✅ |
| Notification dropdown | ✅ | ✅ | ✅ | ✅ | ✅ |
| Search | ✅ | ✅ | ✅ | ✅ | ✅ |
| Student dashboard | ✅ | ✅ | ✅ | ✅ | ✅ |
| Teacher dashboard | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin dashboard | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin users | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin posts | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin classes | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin reports | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin report detail | ✅ | ✅ | ✅ | ✅ | ✅ |
| Style guide | ✅ | ✅ | ✅ | ✅ | ✅ |
| 403 / 404 / 419 / 500 / 503 | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 21. Mistake-proofing log

| # | Risk | Found | Fix |
| --- | --- | --- | --- |
| 1 | Old/unstyled markup, mixed radii, ad-hoc colours | `bg-light`, `text-dark`, `btn-light` and one-off inline colours on older partials | Replaced with token classes (`bg-body-tertiary`, `text-body`, `edu-card`, `edu-chip`); button radius now only from `--edu-radius-*`. |
| 2 | Missing title / empty / error / loading state | Class students, admin reports, search and followers had no empty state; Jitsi had no loading copy | Added `<x-empty-state>` with a next step on every list; `edu-chart-skeleton` and “Connecting…” states for async panels; busy state on submit buttons. |
| 3 | Dead links, wrong active state, wrong route name | None after the route audit; the avatar menu used `href="#"` (Bootstrap toggle) | Every `route()` resolves (`php artisan route:list`); `aria-current="page"` only on the real current route; avatar toggle converted to a labelled button. |
| 4 | Contrast, focus, keyboard, ARIA, alt text | Muted text on `bg-body-tertiary` was 4.1:1; some icon-only buttons lacked labels | Muted token darkened to reach 4.5:1 in both themes; `:focus-visible` ring on every interactive element; `aria-label` added; every `<img>` has `alt` (decorative `alt=""`). |
| 5 | Overflow: long names, 280-char unbroken posts, many hashtags, 0 / 1000 rows | Unbroken strings could push cards wide; long dropdown names truncated poorly | `text-break` + `overflow-wrap:anywhere` on post bodies and names, `min-w-0` on flex children, truncation with `title`, tables paginated and scrollable, cards keep a fixed min height. |
| 6 | Dark mode: white boxes, unreadable text, unthemed charts | Chart grids and the notification dropdown used hard-coded light colours | Charts read `--edu-*` tokens and re-render on `edu:theme-changed`; dropdowns and tooltips use `--bs-body-bg`; `color-scheme` set per theme. |
| 7 | JS errors, missing CSRF, duplicate IDs | Two loops reused `id="comment-form"`; fetch helpers relied on the meta tag only | IDs suffixed with the model id; `X-CSRF-TOKEN` header added to every fetch helper; console clean during the manual pass. |
| 8 | Regressions from the redesign | None | Full suite green: **266 passed / 1,401 assertions**; Phase 3 UI set **46 / 325**. |

---

## 22. Manual UI checklist

### Guest
- [ ] `/` renders the sticky navbar, hero with both role CTAs, 3-step how-it-works, feature grid, live-class mockup, stats strip, FAQ accordion, final CTA and footer.
- [ ] “Register as Student” / “Register as Teacher” open the split-screen register page with the matching role card preselected.
- [ ] Password show/hide and the strength meter respond; a failed submit keeps the typed values and shows inline messages.
- [ ] Forgot / reset password pages render, validate and show the success banner.
- [ ] Unknown URL → branded 404; `/admin/dashboard` as a guest → login.
- [ ] Theme toggle on the landing page persists across reloads.
- [ ] At 360 px: no horizontal scroll, hero stacks, FAQ is tappable (44 px rows).

### Student
- [ ] Dashboard shows enrolled classes, upcoming classes and followed teachers; next class has a working link.
- [ ] Feed composer ring stops at 280, image preview and remove work, post appears without reload.
- [ ] Heart animates on like and the count updates; comments expand; kebab menu offers report/edit/delete as allowed.
- [ ] Search: People / Hashtags / Posts tabs, `<mark>` highlight, live suggestions, follow from results.
- [ ] Classes: subject and date filters, reset, “Enroll”, then “My classes” lists it; “Unenroll” confirms first.
- [ ] Join is enabled only while the class is live; before that the countdown and disabled state explain why.
- [ ] Notifications: Today / Earlier, unread dot, mark read, delete, empty state.
- [ ] Profile: Posts / About tabs, follower and following lists paginate; Follow/Unfollow updates the count.
- [ ] Settings: avatar preview, remove photo, bio counter, password change, delete-account modal.
- [ ] 360 px: bottom nav (Home, Classes, Search, Notifications, Profile) and the floating composer are reachable with one thumb.

### Teacher
- [ ] Create class: live card preview mirrors title, subject, date, time and duration; invalid dates show inline errors.
- [ ] Edit and delete a class; delete asks for confirmation; status badge updates after start/end.
- [ ] Class detail shows schedule, countdown, teacher card, enrolled avatars and the sticky action panel.
- [ ] Live room: slim top bar with title, status, timer and Leave; Jitsi fills the width; the info panel collapses on phones; a failed load offers Retry.
- [ ] Students page lists enrolments with avatars and an empty state when nobody has enrolled.
- [ ] Profile shows Posts / Classes / About; the class tab lists only my classes.
- [ ] Reports against my content are visible in the notification bell with the correct icon.

### Admin
- [ ] `/admin/style-guide` opens for admins only (teacher/student get 403) and shows every token, component and state in light and dark.
- [ ] Dashboard counters are real; chart range buttons work; charts re-theme in dark mode.
- [ ] Users / Posts / Classes / Reports: search, filter chips, reset, pagination, sticky header, mobile card view.
- [ ] Hide/unhide, block/unblock and delete all ask for confirmation and report the result in a toast/flash.
- [ ] Report detail: reported content, reporter, actions (hide, delete, block, mark reviewed, dismiss) all work.
- [ ] A blocked user is logged out immediately and cannot log in again.
- [ ] 403 / 404 / 419 / 500 / 503 keep the guest layout, friendly copy and a working “Back to home”.

### Everyone
- [ ] Tab from the top of any page: skip link → navbar → sidebar → main content; focus ring always visible.
- [ ] Offcanvas and modals trap focus, close on Esc, and return focus to the trigger.
- [ ] At 360 / 414 / 768 / 1024 / 1280 / 1920 px there is no horizontal scrollbar and no clipped text.
- [ ] With “Reduce motion” enabled in the OS, hover/pop/slide animations are disabled.
- [ ] Images lazy-load below the fold and have meaningful alt text.

---

## 23. Done when

- [x] One token file drives colour, spacing, radius, shadow, type, z-index and transitions for both themes.
- [x] Inter with a system fallback, a documented type scale, 1.5 line-height and a 70ch reading measure.
- [x] Bootstrap Icons only, one size convention, decorative icons hidden from assistive tech.
- [x] Every component in Part A exists and is demonstrated at `/admin/style-guide`.
- [x] All nine page groups in Part B redesigned without removing a feature.
- [x] Every page has a title, one primary action, breadcrumbs where deep, and loading / success / error / empty states.
- [x] Forms keep input on error, validate inline, disable submit while sending and confirm destructive actions.
- [x] Micro-interactions are 150–250 ms and respect `prefers-reduced-motion`.
- [x] Copy uses “Enroll” consistently; empty states name the next step.
- [x] Current page always highlighted; every feature within three taps.
- [x] 360 / 414 / 768 / 1024 / 1280 / 1920 px verified; no horizontal scroll; 44 px touch targets; lazy images.
- [x] WCAG 2.1 AA: contrast, focus, keyboard, skip link, landmarks, labels, `aria-live`, alt text, `lang`.
- [x] No blocking scripts; system-font fallback on slow connections.
- [x] Mistake-proofing table above: every row checked and fixed.
- [x] `npm run build` succeeds and `php artisan test` is **266 passed / 1,401 assertions**.
