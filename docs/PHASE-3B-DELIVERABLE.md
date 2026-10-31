# Phase 3B — Premium dashboard redesign

**Project:** EduConnect · Laravel 12.70.2 · PHP 8.3 · Bootstrap 5.3.8 ·
Bootstrap Icons · Chart.js 4 · Vite 6
**Scope:** shared application shell, Admin dashboard, Teacher dashboard,
Student dashboard, shared dashboard components
**Status:** implemented, formatted, built and covered by the test suite

Every file below is copied verbatim from the working repository. This document
is the complete Phase 3B deliverable: design tokens, shell, all three
dashboards, components, controllers, metrics layer, chart endpoints, scripts,
tests, commands, visual checklist and acceptance criteria.

---

## 1. What this phase delivers

- One design system (`resources/sass/_variables.scss` + `app.scss`): CSS custom
  properties, light/dark tokens, Inter, tabular numbers, 16 px card radius,
  soft borders, subtle shadows and hover lift.
- A dark indigo→violet gradient sidebar with grouped icon menus, active
  indicator bar, desktop icon-only collapse, mobile offcanvas drawer and a user
  mini-card.
- A sticky blurred top bar with page title + breadcrumbs, global search,
  persisted dark-mode toggle, live notification bell and avatar menu.
- Shared components: `stat-card` (trend + sparkline), `chart-card` (range
  select, canvas, skeleton, text fallback), `activity-item`, `countdown`,
  `avatar-stack`, `progress-bar`, `empty-state`, `data-table`.
- Three role-protected JSON chart endpoints, cached for five minutes through
  `App\Support\DashboardMetrics`.
- Rebuilt Admin, Teacher and Student dashboards with heroes, charts, ranges,
  panels, quick actions and empty states.
- JavaScript modules: `theme.js`, `charts.js`, `countdown.js`, `data-table.js`,
  `page-transitions.js` (plus `layout.js` for the sidebar).
- Feature coverage: `DashboardRedesignTest` (5), `DashboardTest` (9),
  `PhaseThreeUiTest` (12).

All earlier contracts are preserved:

| Dashboard | Contract | State |
| --- | --- | --- |
| Admin | `stats` keys `users, teachers, students, posts, classes` | unchanged |
| Admin | 12-month `chart` = `labels / values / total` | unchanged |
| Teacher | `stats` keys `classes, upcoming, students, followers` | unchanged |
| Teacher | `classes` paginator and `activity` collection | unchanged |
| Student | `stats` keys `classes, upcoming, following` | unchanged |
| Student | `upcomingClasses` and `recommendedTeachers` collections | unchanged |
| Layout | `data-sidebar-toggle`, `data-bs-target="#appSidebar"`, `educonnect.sidebar.collapsed`, footer copy | unchanged |
| Student | scheduled `Join` keeps `aria-disabled="true"` and `disabled` | unchanged |

### Verification snapshot

```text
Dashboard set (DashboardTest + DashboardRedesignTest + PhaseThreeUiTest)
  → 26 passed, 235 assertions
Full suite
  → 266 passed, 1,401 assertions
npm run build
  → 139 modules, built in ~14 s
Pint (app tests routes config database)
  → passed
Blade cache
  → all 86 templates compiled
```

---

## 2. File list

### Design system

| Path | Purpose |
| --- | --- |
| `resources/sass/_variables.scss` | Brand palette, surfaces, spacing, type scale, radius, shadows, z-index, sidebar and chart tokens, dark overrides |
| `resources/sass/app.scss` | 2,775-line component layer: shell, sidebar, navbar, cards, stats, charts, tables, heroes, schedule, timeline, skeleton, reduced motion |

### Shell

| Path | Purpose |
| --- | --- |
| `resources/views/layouts/app.blade.php` | Document shell, theme bootstrap, page transitions, stacks |
| `resources/views/layouts/navigation.blade.php` | Sticky blurred navbar, search, theme, bell, avatar menu |
| `resources/views/layouts/sidebar.blade.php` | Gradient role sidebar, grouped links, collapse, user mini-card |
| `resources/views/layouts/mobile-nav.blade.php` | Bottom bar for phones |

### Dashboards

| Path | Purpose |
| --- | --- |
| `resources/views/dashboards/admin.blade.php` | Greeting, 5 stat cards, 4 charts, moderation, users, posts, live classes |
| `resources/views/dashboards/teacher.blade.php` | Next-class hero, 4 cards, 2 charts, week strip, table, timeline |
| `resources/views/dashboards/student.blade.php` | Next-class hero, 4 cards, upcoming grid, recommendations, activity, feed preview |

### Components

`stat-card`, `chart-card`, `activity-item`, `countdown`, `avatar-stack`,
`progress-bar`, `data-table`, `empty-state`, `class-card`, `user-card`,
`status-badge`, `avatar`, `pagination-links`

### Backend

| Path | Purpose |
| --- | --- |
| `app/Support/DashboardMetrics.php` | All aggregates, trends, sparklines, schedule, chart series; 300 s cache |
| `app/Http/Controllers/AdminDashboardController.php` | Admin view data |
| `app/Http/Controllers/TeacherDashboardController.php` | Teacher view data, activity, schedule |
| `app/Http/Controllers/StudentDashboardController.php` | Student hero, recommendations, feed preview |
| `app/Http/Controllers/DashboardChartController.php` | JSON endpoints for the range selectors |

### JavaScript

`theme.js`, `charts.js`, `countdown.js`, `data-table.js`, `page-transitions.js`,
`layout.js`, `app.js`

### Routes and tests

`routes/web.php` · `tests/Feature/DashboardRedesignTest.php` ·
`tests/Feature/DashboardTest.php` · `tests/Feature/PhaseThreeUiTest.php`

---

## 3. Full code


### `resources/sass/_variables.scss`

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

```

### `resources/js/theme.js`

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

### `resources/js/countdown.js`

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

### `resources/js/data-table.js`

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

### `resources/js/page-transitions.js`

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

### `resources/js/app.js`

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

### `resources/views/layouts/app.blade.php`

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

### `resources/views/layouts/navigation.blade.php`

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

### `resources/views/layouts/sidebar.blade.php`

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

### `resources/views/layouts/mobile-nav.blade.php`

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

### `resources/views/components/stat-card.blade.php`

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

### `resources/views/components/chart-card.blade.php`

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

### `resources/views/components/activity-item.blade.php`

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

### `resources/views/components/countdown.blade.php`

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

### `resources/views/components/avatar-stack.blade.php`

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

### `resources/views/components/progress-bar.blade.php`

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

### `resources/views/components/data-table.blade.php`

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

### `resources/views/components/empty-state.blade.php`

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

### `resources/views/components/class-card.blade.php`

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

### `resources/views/components/user-card.blade.php`

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

### `resources/views/components/status-badge.blade.php`

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

### `resources/views/components/avatar.blade.php`

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

### `resources/views/components/pagination-links.blade.php`

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

### `resources/views/dashboards/admin.blade.php`

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

### `resources/views/dashboards/teacher.blade.php`

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

### `resources/views/dashboards/student.blade.php`

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

### `app/Support/DashboardMetrics.php`

```php
<?php

namespace App\Support;

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Aggregated dashboard numbers (Phase 3B).
 *
 * Every method that runs more than a trivial query is cached for five minutes.
 * Keys are scoped per role (and per user for the teacher/student dashboards),
 * so one person's numbers never leak into somebody else's page.
 *
 * The public "stats" arrays keep the exact keys the Phase 3 tests expect:
 *   admin   users, teachers, students, posts, classes
 *   teacher classes, upcoming, students, followers
 *   student classes, upcoming, following
 */
class DashboardMetrics
{
    /** Five minutes, as required by the phase brief. */
    private const TTL_SECONDS = 300;

    private const RANGES = ['7d', '30d', '12m'];

    /* =====================================================================
     | Admin
     * ===================================================================== */

    /**
     * Platform totals (FR-7). Exact five-key contract.
     *
     * @return array{users: int, teachers: int, students: int, posts: int, classes: int}
     */
    public function adminStats(): array
    {
        return Cache::remember('dashboard.admin.stats.v1', self::TTL_SECONDS, function (): array {
            // One aggregate query for the three user counters. SUM(CASE ...)
            // works on MySQL and on the SQLite database used by the test suite.
            $userCounts = User::query()
                ->selectRaw(
                    'COUNT(*) as users,
                     SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as teachers,
                     SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as students',
                    [User::ROLE_TEACHER, User::ROLE_STUDENT]
                )
                ->first();

            return [
                'users' => (int) ($userCounts->users ?? 0),
                'teachers' => (int) ($userCounts->teachers ?? 0),
                'students' => (int) ($userCounts->students ?? 0),
                'posts' => Post::query()->count(),
                'classes' => ClassRoom::query()->count(),
            ];
        });
    }

    /**
     * Pending reports badge on the admin dashboard (FR-33).
     * The queue itself stays uncached; only this small counter is.
     */
    public function adminPendingReports(): int
    {
        return (int) Cache::remember(
            'dashboard.admin.pending-reports.v1',
            self::TTL_SECONDS,
            static fn (): int => Report::query()->pending()->count()
        );
    }

    /**
     * Month-over-month movement for the five stat cards.
     *
     * @return array<string, array{percent: int, direction: string}>
     */
    public function adminTrends(): array
    {
        return Cache::remember('dashboard.admin.trends.v1', self::TTL_SECONDS, function (): array {
            $thisMonth = now()->copy()->startOfMonth();
            $lastMonth = now()->copy()->subMonthNoOverflow()->startOfMonth();

            $userNow = User::query()->where('created_at', '>=', $thisMonth)->count();
            $userBefore = User::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $teachersNow = User::query()
                ->where('role', User::ROLE_TEACHER)
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $teachersBefore = User::query()
                ->where('role', User::ROLE_TEACHER)
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $studentsNow = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $studentsBefore = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $postsNow = Post::query()->where('created_at', '>=', $thisMonth)->count();
            $postsBefore = Post::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $classesNow = ClassRoom::query()->where('created_at', '>=', $thisMonth)->count();
            $classesBefore = ClassRoom::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            return [
                'users' => $this->trend($userNow, $userBefore),
                'teachers' => $this->trend($teachersNow, $teachersBefore),
                'students' => $this->trend($studentsNow, $studentsBefore),
                'posts' => $this->trend($postsNow, $postsBefore),
                'classes' => $this->trend($classesNow, $classesBefore),
            ];
        });
    }

    /**
     * Seven-day sparkline data for every admin stat card.
     *
     * @return array<string, list<int>>
     */
    public function adminSparklines(): array
    {
        return Cache::remember('dashboard.admin.sparklines.v1', self::TTL_SECONDS, function (): array {
            $since = now()->copy()->startOfDay()->subDays(6);

            return [
                'users' => $this->daySeries(
                    User::query()->where('created_at', '>=', $since)->pluck('created_at'),
                    7
                )['values'],
                'teachers' => $this->daySeries(
                    User::query()
                        ->where('role', User::ROLE_TEACHER)
                        ->where('created_at', '>=', $since)
                        ->pluck('created_at'),
                    7
                )['values'],
                'students' => $this->daySeries(
                    User::query()
                        ->where('role', User::ROLE_STUDENT)
                        ->where('created_at', '>=', $since)
                        ->pluck('created_at'),
                    7
                )['values'],
                'posts' => $this->daySeries(
                    Post::query()->where('created_at', '>=', $since)->pluck('created_at'),
                    7
                )['values'],
                'classes' => $this->daySeries(
                    ClassRoom::query()->where('created_at', '>=', $since)->pluck('created_at'),
                    7
                )['values'],
            ];
        });
    }

    /**
     * Every admin chart series for a range.
     *
     * @return array<string, array{labels: list<string>, values: list<int>, total: int}>
     */
    public function adminCharts(string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        return Cache::remember("dashboard.admin.charts.{$range}.v1", self::TTL_SECONDS, function () use ($range): array {
            $roles = $this->adminRoleSeries();
            $classes = $this->adminClassSeries();

            return [
                'newUsers' => $this->adminNewUsers($range),
                'posts' => $this->adminPosts($range),
                'roles' => $roles,
                'classes' => $classes,
            ];
        });
    }

    /**
     * New registrations for 7 days, 30 days or 12 months.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminNewUsers(string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        $timestamps = User::query()
            ->when($range !== '12m', fn ($query) => $query->where('created_at', '>=', $this->rangeStart($range)))
            ->pluck('created_at');

        return $this->seriesForRange($timestamps, $range);
    }

    /**
     * Posts per day/week/month, depending on the selected range.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminPosts(string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        $timestamps = Post::query()
            ->when($range !== '12m', fn ($query) => $query->where('created_at', '>=', $this->rangeStart($range)))
            ->pluck('created_at');

        if ($range === '12m') {
            return $this->weekSeries($timestamps, 12);
        }

        return $this->seriesForRange($timestamps, $range);
    }

    /**
     * Users by role as a doughnut series.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminRoleSeries(): array
    {
        $counts = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $labels = [];
        $values = [];

        foreach ([User::ROLE_ADMIN, User::ROLE_TEACHER, User::ROLE_STUDENT] as $role) {
            $labels[] = ucfirst($role).'s';
            $values[] = (int) ($counts[$role] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /**
     * Classes by status as a doughnut series.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminClassSeries(): array
    {
        $counts = ClassRoom::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $values = [];

        foreach (ClassRoom::STATUSES as $status) {
            $labels[] = ucfirst($status);
            $values[] = (int) ($counts[$status] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /* =====================================================================
     | Teacher
     * ===================================================================== */

    /**
     * The teacher's own counters. Exact four-key contract.
     *
     * @return array{classes: int, upcoming: int, students: int, followers: int}
     */
    public function teacherStats(User $teacher): array
    {
        return Cache::remember("dashboard.teacher.{$teacher->id}.stats.v1", self::TTL_SECONDS, function () use ($teacher): array {
            $mine = $teacher->classes();

            return [
                'classes' => (clone $mine)->count(),
                'upcoming' => (clone $mine)->upcoming()->count(),
                // Distinct students across all classes (nobody counted twice).
                'students' => ClassEnrollment::query()
                    ->whereIn('class_id', (clone $mine)->select('id'))
                    ->distinct()
                    ->count('student_id'),
                'followers' => $teacher->followers()->count(),
            ];
        });
    }

    /**
     * Month-over-month growth for the teacher cards.
     *
     * @return array<string, array{percent: int, direction: string}>
     */
    public function teacherTrends(User $teacher): array
    {
        return Cache::remember("dashboard.teacher.{$teacher->id}.trends.v1", self::TTL_SECONDS, function () use ($teacher): array {
            $thisMonth = now()->copy()->startOfMonth();
            $lastMonth = now()->copy()->subMonthNoOverflow()->startOfMonth();

            $classesNow = $teacher->classes()->where('created_at', '>=', $thisMonth)->count();
            $classesBefore = $teacher->classes()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $enrollmentsNow = ClassEnrollment::query()
                ->whereIn('class_id', $teacher->classes()->select('id'))
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $enrollmentsBefore = ClassEnrollment::query()
                ->whereIn('class_id', $teacher->classes()->select('id'))
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $followersNow = Follow::query()
                ->where('following_id', $teacher->id)
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $followersBefore = Follow::query()
                ->where('following_id', $teacher->id)
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            return [
                'classes' => $this->trend($classesNow, $classesBefore),
                'students' => $this->trend($enrollmentsNow, $enrollmentsBefore),
                'followers' => $this->trend($followersNow, $followersBefore),
            ];
        });
    }

    /**
     * Chart series for the teacher dashboard.
     *
     * @return array<string, array{labels: list<string>, values: list<int>, total: int}>
     */
    public function teacherCharts(User $teacher, string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        return Cache::remember("dashboard.teacher.{$teacher->id}.charts.{$range}.v1", self::TTL_SECONDS, function () use ($teacher, $range): array {
            $classes = $teacher->classes()
                ->withCount('students')
                ->when($range !== '12m', fn ($query) => $query->whereDate('date', '>=', $this->rangeStart($range)))
                ->orderByDesc('students_count')
                ->orderBy('title')
                ->take(8)
                ->get(['id', 'title']);

            $labels = $classes->map(fn (ClassRoom $class) => Str::limit($class->title, 18))->all();
            $values = $classes->map(fn (ClassRoom $class) => (int) $class->students_count)->all();

            $followers = Follow::query()
                ->where('following_id', $teacher->id)
                ->when($range !== '12m', fn ($query) => $query->where('created_at', '>=', $this->rangeStart($range)))
                ->pluck('created_at');

            return [
                'enrollments' => [
                    'labels' => $labels,
                    'values' => $values,
                    'total' => array_sum($values),
                ],
                'followers' => $this->seriesForRange($followers, $range),
            ];
        });
    }

    /**
     * The next seven days with the number of classes on each day.
     *
     * @return list<array{date: Carbon, weekday: string, day: string, count: int, isToday: bool}>
     */
    public function teacherSchedule(User $teacher): array
    {
        return Cache::remember("dashboard.teacher.{$teacher->id}.schedule.v1", self::TTL_SECONDS, function () use ($teacher): array {
            $start = now()->copy()->startOfDay();
            $end = $start->copy()->addDays(6)->endOfDay();

            $counts = $teacher->classes()
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->pluck('date')
                ->countBy(fn ($date) => Carbon::parse($date)->toDateString());

            $days = [];

            for ($offset = 0; $offset < 7; $offset++) {
                $date = $start->copy()->addDays($offset);

                $days[] = [
                    'date' => $date->copy(),
                    'weekday' => $date->format('D'),
                    'day' => $date->format('j'),
                    'count' => (int) ($counts[$date->toDateString()] ?? 0),
                    'isToday' => $date->isToday(),
                ];
            }

            return $days;
        });
    }

    /* =====================================================================
     | Student
     * ===================================================================== */

    /**
     * The student's counters. Exact three-key contract.
     *
     * @return array{classes: int, upcoming: int, following: int}
     */
    public function studentStats(User $student): array
    {
        return Cache::remember("dashboard.student.{$student->id}.stats.v1", self::TTL_SECONDS, function () use ($student): array {
            return [
                'classes' => $student->enrolledClasses()->count(),
                'upcoming' => $student->enrolledClasses()->upcoming()->count(),
                // Only teachers count, so the card matches what the page offers.
                'following' => $student->following()->where('role', User::ROLE_TEACHER)->count(),
            ];
        });
    }

    /**
     * Classes attended per week (a proxy based on enrolled class dates).
     *
     * @return array<string, array{labels: list<string>, values: list<int>, total: int}>
     */
    public function studentCharts(User $student, string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        return Cache::remember("dashboard.student.{$student->id}.charts.{$range}.v1", self::TTL_SECONDS, function () use ($student, $range): array {
            $dates = $student->enrolledClasses()
                ->when($range !== '12m', fn ($query) => $query->whereDate('classes.date', '>=', $this->rangeStart($range)))
                ->select('classes.date')
                ->pluck('classes.date');

            return [
                'activity' => $this->seriesForRange($dates, $range),
            ];
        });
    }

    /* =====================================================================
     | Series helpers
     * ===================================================================== */

    public function normalizeRange(?string $range): string
    {
        return in_array($range, self::RANGES, true) ? $range : '12m';
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function seriesForRange(Collection $timestamps, string $range): array
    {
        return match ($range) {
            '7d' => $this->daySeries($timestamps, 7),
            '30d' => $this->weekSeries($timestamps, 5),
            default => $this->monthSeries($timestamps, 12),
        };
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function daySeries(Collection $timestamps, int $days): array
    {
        $start = now()->copy()->startOfDay()->subDays($days - 1);
        $counts = $timestamps->countBy(
            static fn ($timestamp) => Carbon::parse($timestamp)->toDateString()
        );

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $day = $start->copy()->addDays($offset);

            $labels[] = $day->format('d M');
            $values[] = (int) ($counts[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function weekSeries(Collection $timestamps, int $weeks): array
    {
        $start = now()->copy()->startOfWeek()->subWeeks($weeks - 1);
        $counts = $timestamps->countBy(
            static fn ($timestamp) => Carbon::parse($timestamp)->startOfWeek()->toDateString()
        );

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $weeks; $offset++) {
            $week = $start->copy()->addWeeks($offset);

            $labels[] = $week->format('d M');
            $values[] = (int) ($counts[$week->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function monthSeries(Collection $timestamps, int $months): array
    {
        // First month of the window: 11 months back, so today's month is included.
        $start = now()->copy()->startOfMonth()->subMonths($months - 1);
        $counts = $timestamps->countBy(
            static fn ($timestamp) => Carbon::parse($timestamp)->format('Y-m')
        );

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $months; $offset++) {
            $month = $start->copy()->addMonths($offset);

            $labels[] = $month->format('M Y');
            $values[] = (int) ($counts[$month->format('Y-m')] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    private function rangeStart(string $range): Carbon
    {
        return match ($range) {
            '7d' => now()->copy()->startOfDay()->subDays(6),
            '30d' => now()->copy()->startOfDay()->subDays(29),
            default => now()->copy()->startOfMonth()->subMonths(11),
        };
    }

    /**
     * @return array{percent: int, direction: string}
     */
    private function trend(int $current, int $previous): array
    {
        if ($previous === 0) {
            $percent = $current === 0 ? 0 : 100;
        } else {
            $percent = (int) round((($current - $previous) / $previous) * 100);
        }

        return [
            'percent' => $percent,
            'direction' => $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat'),
        ];
    }
}

```

### `app/Http/Controllers/AdminDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Support\DashboardMetrics;
use Illuminate\View\View;

/**
 * Admin dashboard (FR-2, FR-26, FR-33 chart and moderation).
 *
 * Phase 3B keeps the five-key stats contract and the 12-month "chart" data,
 * then adds trends, sparklines, four themed charts and the moderation/activity
 * panels. Expensive aggregates are cached for five minutes in DashboardMetrics.
 */
class AdminDashboardController extends Controller
{
    public function __invoke(DashboardMetrics $metrics): View
    {
        // The cached chart set always contains the 12 month series used by the
        // original test contract.
        $charts = $metrics->adminCharts('12m');

        return view('dashboards.admin', [
            'stats' => $metrics->adminStats(),
            'chart' => $charts['newUsers'],
            'trends' => $metrics->adminTrends(),
            'sparklines' => $metrics->adminSparklines(),

            // Themed chart series (FR-31).
            'roleChart' => $charts['roles'],
            'postsChart' => $charts['posts'],
            'classesChart' => $charts['classes'],

            // Newest accounts for the "recent users" table (FR-26).
            'recentUsers' => User::query()
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'email', 'role', 'photo', 'created_at']),

            // Moderation shortcuts (FR-33): cached pending counter + newest five.
            'pendingReports' => $metrics->adminPendingReports(),
            'recentReports' => Report::query()
                ->pending()
                ->with(['reporter:id,name', 'reportable'])
                ->latest()
                ->take(5)
                ->get(),

            // Latest activity panels (FR-26, FR-33).
            'recentPosts' => Post::query()
                ->visible()
                ->with('user:id,name,photo,role')
                ->withCount(['likes', 'comments'])
                ->latest()
                ->take(5)
                ->get(),
            'liveClasses' => ClassRoom::query()
                ->visible()
                ->where('status', ClassRoom::STATUS_LIVE)
                ->whereDate('date', now()->toDateString())
                ->with('teacher:id,name,photo')
                ->withCount('students')
                ->orderBy('time')
                ->take(4)
                ->get(),
        ]);
    }
}

```

### `app/Http/Controllers/TeacherDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Like;
use App\Models\User;
use App\Support\DashboardMetrics;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Teacher dashboard (FR-2, FR-29).
 *
 * Phase 3B adds the "next class" hero, a seven-day schedule, enrollment
 * progress, two charts and a richer activity timeline. The original four-key
 * stats array, the classes paginator and the activity collection are kept
 * exactly as they were.
 */
class TeacherDashboardController extends Controller
{
    /** How many activity entries the stream shows. */
    private const ACTIVITY_LIMIT = 10;

    /** Classes per page in the table. */
    private const CLASSES_PER_PAGE = 8;

    public function __invoke(Request $request, DashboardMetrics $metrics): View
    {
        $teacher = $request->user();
        $mine = $teacher->classes();

        // Hero: the next scheduled class, or a class that is live right now.
        $nextClass = (clone $mine)
            ->upcoming()
            ->withCount('students')
            ->with(['students' => fn ($query) => $query->orderBy('name')->limit(6)])
            ->first();

        if (! $nextClass) {
            $nextClass = (clone $mine)
                ->where('status', ClassRoom::STATUS_LIVE)
                ->withCount('students')
                ->with(['students' => fn ($query) => $query->orderBy('name')->limit(6)])
                ->orderByDesc('date')
                ->orderByDesc('time')
                ->first();
        }

        // The progress bars show enrollment relative to the teacher's biggest
        // class, because there is no capacity column in Phase 3.
        $maxEnrollment = (int) ClassEnrollment::query()
            ->whereIn('class_id', (clone $mine)->select('id'))
            ->selectRaw('class_id, COUNT(*) as total')
            ->groupBy('class_id')
            ->pluck('total')
            ->max();

        $maxEnrollment = max(1, $maxEnrollment);

        $charts = $metrics->teacherCharts($teacher, '12m');

        // My classes, newest first, with the number of enrolled students.
        // withCount() keeps the table free of N+1 queries.
        $classes = $mine
            ->withCount('students')
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate(self::CLASSES_PER_PAGE)
            ->withQueryString();

        return view('dashboards.teacher', [
            'stats' => $metrics->teacherStats($teacher),
            'trends' => $metrics->teacherTrends($teacher),
            'classes' => $classes,
            'activity' => $this->recentActivity($teacher),
            'nextClass' => $nextClass,
            'maxEnrollment' => $maxEnrollment,
            'schedule' => $metrics->teacherSchedule($teacher),
            'enrollmentChart' => $charts['enrollments'],
            'followerChart' => $charts['followers'],
        ]);
    }

    /**
     * The latest likes, comments and new followers that concern this teacher.
     * Each source is limited to ACTIVITY_LIMIT rows and eager loads its author,
     * then the three lists are merged and cut down to the newest entries.
     *
     * @return Collection<int, array{icon: string, color: string, text: string, excerpt: string|null, at: CarbonInterface|null}>
     */
    private function recentActivity(User $teacher): Collection
    {
        // Likes on my posts.
        $likes = Like::query()
            ->with(['user:id,name', 'post:id,user_id,body'])
            ->whereHas('post', fn ($query) => $query->where('user_id', $teacher->id))
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Like $like) => [
                'icon' => 'bi-hand-thumbs-up',
                'color' => 'primary',
                'text' => __(':name liked your post', ['name' => $like->user?->name ?? __('Someone')]),
                'excerpt' => $like->post?->body,
                'at' => $like->created_at,
            ]);

        // Comments on my posts.
        $comments = Comment::query()
            ->with(['user:id,name', 'post:id,user_id,body'])
            ->whereHas('post', fn ($query) => $query->where('user_id', $teacher->id))
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Comment $comment) => [
                'icon' => 'bi-chat',
                'color' => 'success',
                'text' => __(':name commented on your post', ['name' => $comment->user?->name ?? __('Someone')]),
                'excerpt' => $comment->body,
                'at' => $comment->created_at,
            ]);

        // Students who enrolled in one of my classes.
        $enrollments = ClassEnrollment::query()
            ->with(['student:id,name', 'classRoom:id,teacher_id,title'])
            ->whereHas('classRoom', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (ClassEnrollment $enrollment) => [
                'icon' => 'bi-person-check',
                'color' => 'info',
                'text' => __(':name enrolled in :class', [
                    'name' => $enrollment->student?->name ?? __('Someone'),
                    'class' => $enrollment->classRoom?->title ?? __('your class'),
                ]),
                'excerpt' => null,
                'at' => $enrollment->created_at,
            ]);

        // People who started following me.
        $followers = Follow::query()
            ->with('follower:id,name')
            ->where('following_id', $teacher->id)
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Follow $follow) => [
                'icon' => 'bi-person-plus',
                'color' => 'warning',
                'text' => __(':name started following you', ['name' => $follow->follower?->name ?? __('Someone')]),
                'excerpt' => null,
                'at' => $follow->created_at,
            ]);

        return $likes
            ->concat($comments)
            ->concat($enrollments)
            ->concat($followers)
            ->sortByDesc('at')
            ->take(self::ACTIVITY_LIMIT)
            ->values();
    }
}

```

### `app/Http/Controllers/StudentDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Follow;
use App\Models\Post;
use App\Models\User;
use App\Support\DashboardMetrics;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Student dashboard (FR-2, FR-30).
 *
 * Phase 3B adds the next-class hero with a live countdown, upcoming class
 * cards, recommended classes, a learning-activity chart and a three-post feed
 * preview. The original stats array, upcomingClasses collection and
 * recommendedTeachers collection keep their exact shape.
 */
class StudentDashboardController extends Controller
{
    /** How many upcoming classes the list shows. */
    private const UPCOMING_LIMIT = 6;

    /** How many teacher suggestions the list shows. */
    private const RECOMMENDATION_LIMIT = 3;

    /** How many recommended classes the student sees. */
    private const CLASS_RECOMMENDATION_LIMIT = 3;

    /** How many feed posts the preview shows. */
    private const FEED_PREVIEW_LIMIT = 3;

    public function __invoke(Request $request, DashboardMetrics $metrics): View
    {
        $student = $request->user();

        // Upcoming classes I am enrolled in, soonest first.
        // Eager loading the teacher and counting the students avoids N+1.
        $upcomingClasses = $student->enrolledClasses()
            ->upcoming()
            ->with('teacher:id,name,subject,photo')
            ->withCount('students')
            ->limit(self::UPCOMING_LIMIT)
            ->get();

        // Hero: the next scheduled class, falling back to a class that is live.
        $nextClass = $upcomingClasses->first();

        if (! $nextClass) {
            $nextClass = $student->enrolledClasses()
                ->where('classes.status', ClassRoom::STATUS_LIVE)
                ->with('teacher:id,name,subject,photo')
                ->withCount('students')
                ->first();
        }

        $heroClass = $nextClass?->load([
            'students' => fn ($query) => $query->orderBy('name')->limit(6),
        ]);

        $followingIds = $student->following()->pluck('users.id');

        $charts = $metrics->studentCharts($student, '12m');

        return view('dashboards.student', [
            'stats' => $metrics->studentStats($student),
            'upcomingClasses' => $upcomingClasses,
            'recommendedTeachers' => $this->recommendedTeachers($student),
            'recommendedClasses' => $this->recommendedClasses($student),
            'heroClass' => $heroClass,
            'classmates' => $heroClass?->students ?? collect(),
            'followingIds' => $followingIds,
            'notificationCount' => $student->unreadNotifications()->count(),
            'activityChart' => $charts['activity'],
            'feedPreview' => $this->feedPreview($student),
        ]);
    }

    /**
     * Teachers that this student does not follow yet, most followed first.
     *
     * @return EloquentCollection<int, User>
     */
    private function recommendedTeachers(User $student): EloquentCollection
    {
        // Sub-query of everybody the student already follows.
        $alreadyFollowed = Follow::query()
            ->select('following_id')
            ->where('follower_id', $student->id);

        return User::query()
            ->where('role', User::ROLE_TEACHER)
            ->whereNotIn('users.id', $alreadyFollowed)
            ->withCount('followers')
            ->orderByDesc('followers_count')
            ->orderBy('name')
            ->limit(self::RECOMMENDATION_LIMIT)
            ->get();
    }

    /**
     * Classes from followed teachers or from subjects the student already
     * studies, excluding anything they are enrolled in.
     *
     * @return EloquentCollection<int, ClassRoom>
     */
    private function recommendedClasses(User $student): EloquentCollection
    {
        $followedTeacherIds = $student->following()
            ->where('users.role', User::ROLE_TEACHER)
            ->pluck('users.id');

        $subjects = $student->enrolledClasses()
            ->whereNotNull('classes.subject')
            ->distinct()
            ->pluck('classes.subject');

        if ($followedTeacherIds->isEmpty() && $subjects->isEmpty()) {
            return new EloquentCollection;
        }

        $enrolledIds = $student->enrolledClasses()->select('classes.id');

        return ClassRoom::query()
            ->visible()
            ->upcoming()
            ->whereNotIn('classes.id', $enrolledIds)
            ->where(function ($query) use ($followedTeacherIds, $subjects): void {
                if ($followedTeacherIds->isNotEmpty()) {
                    $query->whereIn('teacher_id', $followedTeacherIds);
                }

                if ($subjects->isNotEmpty()) {
                    $query->orWhereIn('subject', $subjects);
                }
            })
            ->with('teacher:id,name,subject,photo')
            ->withCount('students')
            ->limit(self::CLASS_RECOMMENDATION_LIMIT)
            ->get();
    }

    /**
     * The newest posts from people the student follows.
     *
     * @return EloquentCollection<int, Post>
     */
    private function feedPreview(User $student): EloquentCollection
    {
        $following = $student->following()->pluck('users.id');

        if ($following->isEmpty()) {
            return new EloquentCollection;
        }

        return Post::query()
            ->visible()
            ->whereIn('user_id', $following)
            ->withFeedData($student)
            ->latest()
            ->limit(self::FEED_PREVIEW_LIMIT)
            ->get();
    }
}

```

### `app/Http/Controllers/DashboardChartController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Support\DashboardMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoints behind the dashboard range selectors (Phase 3B).
 *
 * Every route lives inside its role's existing middleware group, so a teacher
 * cannot read the admin series and a student cannot read the teacher series.
 * The returned object is keyed by the chart "series" names used on the page:
 *   admin   newUsers, posts, roles, classes
 *   teacher enrollments, followers
 *   student activity
 */
class DashboardChartController extends Controller
{
    public function admin(Request $request, DashboardMetrics $metrics): JsonResponse
    {
        $range = $metrics->normalizeRange($request->query('range'));

        return response()->json([
            'range' => $range,
            ...$metrics->adminCharts($range),
        ]);
    }

    public function teacher(Request $request, DashboardMetrics $metrics): JsonResponse
    {
        $range = $metrics->normalizeRange($request->query('range'));

        return response()->json([
            'range' => $range,
            ...$metrics->teacherCharts($request->user(), $range),
        ]);
    }

    public function student(Request $request, DashboardMetrics $metrics): JsonResponse
    {
        $range = $metrics->normalizeRange($request->query('range'));

        return response()->json([
            'range' => $range,
            ...$metrics->studentCharts($request->user(), $range),
        ]);
    }
}

```

### `routes/web.php`

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

### `tests/Feature/DashboardRedesignTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3B: the premium dashboard redesign.
 *
 * These tests protect the new UI contracts (theme toggle, chart endpoints,
 * dashboard panels and the per-role hero sections) without duplicating the
 * data contracts that DashboardTest already covers.
 */
class DashboardRedesignTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Shared layout and design system
     * --------------------------------------------------------------- */

    public function test_the_layout_ships_the_theme_toggle_and_the_design_tokens(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('teacher.dashboard'))
            ->assertOk()
            // Theme is restored before the first paint and persisted by theme.js.
            ->assertSee('data-theme-toggle', false)
            ->assertSee('educonnect.theme', false)
            ->assertSee('prefers-color-scheme', false)
            // Inter and the sticky shell are part of the redesign.
            ->assertSee('fonts.googleapis.com', false)
            ->assertSee('edu-navbar', false)
            ->assertSee('edu-sidebar', false)
            // Every dashboard uses the shared page wrapper.
            ->assertSee('edu-dashboard', false)
            // The desktop sidebar toggle and the footer copy stay intact.
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('Learn together, anywhere.');
    }

    /* ---------------------------------------------------------------
     | Chart JSON endpoints and role isolation
     * --------------------------------------------------------------- */

    public function test_the_dashboard_chart_endpoints_are_role_protected(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        // Admin series: five keys, including the 12-month compatible shape.
        $this->actingAs($admin)
            ->getJson(route('admin.dashboard.charts', ['range' => '7d']))
            ->assertOk()
            ->assertJsonPath('range', '7d')
            ->assertJsonStructure([
                'range',
                'newUsers' => ['labels', 'values', 'total'],
                'posts' => ['labels', 'values', 'total'],
                'roles' => ['labels', 'values', 'total'],
                'classes' => ['labels', 'values', 'total'],
            ]);

        // A teacher cannot read the admin series.
        $this->actingAs($teacher)
            ->getJson(route('admin.dashboard.charts'))
            ->assertForbidden();

        // Teacher series.
        $this->actingAs($teacher)
            ->getJson(route('teacher.dashboard.charts'))
            ->assertOk()
            ->assertJsonStructure([
                'range',
                'enrollments' => ['labels', 'values', 'total'],
                'followers' => ['labels', 'values', 'total'],
            ]);

        // A student cannot read the teacher series.
        $this->actingAs($student)
            ->getJson(route('teacher.dashboard.charts'))
            ->assertForbidden();

        // Student series: seven daily buckets for the 7-day range.
        $response = $this->actingAs($student)
            ->getJson(route('student.dashboard.charts', ['range' => '7d']))
            ->assertOk()
            ->assertJsonPath('range', '7d')
            ->assertJsonStructure(['range', 'activity' => ['labels', 'values', 'total']]);

        $this->assertCount(7, $response->json('activity.labels'));

        // The student cannot read the admin series either.
        $this->actingAs($student)
            ->getJson(route('admin.dashboard.charts'))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Admin dashboard panels
     * --------------------------------------------------------------- */

    public function test_the_admin_dashboard_renders_the_new_charts_panels_and_moderation_shortcuts(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->student()->create(['name' => 'Report Author']);
        $post = Post::factory()->create([
            'user_id' => $reporter->id,
            'body' => 'A reported post that needs a moderator.',
        ]);
        Report::factory()->create([
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $teacher = User::factory()->teacher()->create();
        ClassRoom::factory()->live()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Live Algebra Room',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('New users per month')
            ->assertSee('Users by role')
            ->assertSee('Posts per week')
            ->assertSee('Classes by status')
            ->assertSee('Pending reports')
            ->assertSee('Latest posts')
            ->assertSee("Today's live classes")
            ->assertSee('Recent users')
            ->assertSee('Live Algebra Room')
            // The four Chart.js canvases and the JSON endpoint are wired up.
            ->assertSee('data-chart-card', false)
            ->assertSee(route('admin.dashboard.charts'), false)
            // Quick moderation buttons appear for a pending report.
            ->assertSee(route('admin.reports.hide', Report::first()), false)
            ->assertSee(route('admin.reports.dismiss', Report::first()), false);

        // The contracted stats keep exactly five keys.
        $this->assertSame(
            ['users', 'teachers', 'students', 'posts', 'classes'],
            array_keys($response->viewData('stats'))
        );

        // Trend + sparkline data is available for every card.
        $this->assertSame(
            ['users', 'teachers', 'students', 'posts', 'classes'],
            array_keys($response->viewData('trends'))
        );
    }

    /* ---------------------------------------------------------------
     | Teacher dashboard hero, schedule and table
     * --------------------------------------------------------------- */

    public function test_the_teacher_dashboard_has_the_hero_schedule_and_enrollment_progress(): void
    {
        $teacher = User::factory()->teacher()->create(['name' => 'Tina Teacher']);
        $student = User::factory()->student()->create(['name' => 'Hero Student']);
        $class = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Hero Algebra',
        ]);
        $class->students()->attach($student->id);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk()
            ->assertSee('Next class')
            ->assertSee('Hero Algebra')
            // Live countdown and the seven-day strip.
            ->assertSee('data-countdown', false)
            ->assertSee('edu-schedule', false)
            ->assertSee('This week')
            // Charts, table tools, actions and the activity feed.
            ->assertSee('Enrollments per class')
            ->assertSee('Follower growth')
            ->assertSee('data-table-search', false)
            ->assertSee('Actions')
            ->assertSee('Recent activity')
            ->assertSee(route('classes.students', $class), false)
            ->assertSee(route('teacher.dashboard.charts'), false);

        $this->assertSame($class->id, $response->viewData('nextClass')->id);
        $this->assertCount(7, $response->viewData('schedule'));
        $this->assertSame(1, $response->viewData('maxEnrollment'));
    }

    /* ---------------------------------------------------------------
     | Student dashboard hero, recommendations and feed preview
     * --------------------------------------------------------------- */

    public function test_the_student_dashboard_has_the_hero_recommendations_chart_and_feed_preview(): void
    {
        $student = User::factory()->student()->create(['name' => 'Nina Student']);
        $followed = User::factory()->teacher()->create(['name' => 'Followed Teacher']);
        $suggested = User::factory()->teacher()->create(['name' => 'Suggested Teacher']);
        $student->follow($followed);

        $class = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $followed->id,
            'title' => 'Nina Next Class',
            'subject' => 'Mathematics',
        ]);
        $class->students()->attach($student->id);

        // Same subject as the enrolled class, so it is recommended even though
        // the teacher is not followed yet.
        ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $suggested->id,
            'title' => 'Suggested Mathematics Class',
            'subject' => 'Mathematics',
        ]);

        Post::factory()->create([
            'user_id' => $followed->id,
            'body' => 'A post from a followed teacher.',
        ]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertOk()
            ->assertSee('Your next class')
            ->assertSee('Nina Next Class')
            ->assertSee('data-countdown', false)
            ->assertSee('Recommended teachers')
            ->assertSee('Suggested Teacher')
            // The existing AJAX follow form is reused.
            ->assertSee('data-follow-form', false)
            ->assertSee('Recommended for you')
            ->assertSee('Suggested Mathematics Class')
            ->assertSee('Learning activity')
            ->assertSee('From people you follow')
            ->assertSee('A post from a followed teacher.')
            ->assertSee('Open full feed')
            ->assertSee(route('student.dashboard.charts'), false);

        // Contracted collections keep their shape.
        $this->assertSame(
            ['classes', 'upcoming', 'following'],
            array_keys($response->viewData('stats'))
        );
        $this->assertSame([$class->id], $response->viewData('upcomingClasses')->pluck('id')->all());
        $this->assertSame(
            ['Suggested Teacher'],
            $response->viewData('recommendedTeachers')->pluck('name')->all()
        );
        $this->assertCount(1, $response->viewData('feedPreview'));
    }
}

```

### `tests/Feature/DashboardTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3: the three role dashboards (FR-2, FR-19, FR-20, FR-21, FR-22, FR-31).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Role isolation
     * --------------------------------------------------------------- */

    /**
     * Every role only reaches its own dashboard (FR-2, FR-5).
     */
    public function test_each_role_is_locked_out_of_the_other_dashboards(): void
    {
        // A teacher cannot open the admin or the student dashboard.
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($teacher)->get('/student/dashboard')->assertForbidden();

        // A student cannot open the admin or the teacher dashboard.
        $student = User::factory()->student()->create();
        $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($student)->get('/teacher/dashboard')->assertForbidden();

        // An admin cannot open the teacher or the student dashboard.
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/teacher/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/student/dashboard')->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Admin dashboard
     * --------------------------------------------------------------- */

    /**
     * The admin cards show the real platform totals (FR-7).
     */
    public function test_the_admin_dashboard_shows_the_platform_totals(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->teacher()->create();
        $students = User::factory()->count(3)->student()->create();

        Post::factory()->create(['user_id' => $students->first()->id]);
        ClassRoom::factory()->create(['teacher_id' => User::where('role', User::ROLE_TEACHER)->first()->id]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats === [
            'users' => 6,       // 1 admin + 2 teachers + 3 students
            'teachers' => 2,
            'students' => 3,
            'posts' => 1,
            'classes' => 1,
        ]);
    }

    /**
     * The chart covers exactly 12 months, keeps the months in order and fills
     * months without registrations with 0 (FR-31).
     */
    public function test_the_admin_chart_counts_new_users_per_month(): void
    {
        // Freeze time so the 12 month window is predictable.
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create();          // 3 registrations in June 2026

        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        User::factory()->count(2)->create();          // 2 registrations in April 2026

        $this->travelTo(Carbon::parse('2025-01-01 09:00:00'));
        User::factory()->create();                    // outside the window -> ignored

        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();

        $chart = $response->viewData('chart');

        // 12 months, oldest first: July 2025 ... June 2026.
        $this->assertCount(12, $chart['labels']);
        $this->assertSame('Jul 2025', $chart['labels'][0]);
        $this->assertSame('Jun 2026', $chart['labels'][11]);

        // April 2026 is index 9, June 2026 is index 11.
        $this->assertSame(2, $chart['values'][9]);
        $this->assertSame(3, $chart['values'][11]);

        // Months without new users are 0, not missing.
        $this->assertSame(0, $chart['values'][0]);
        $this->assertSame(0, $chart['values'][10]);

        // The user from January 2025 is not part of the last 12 months.
        $this->assertSame(5, $chart['total']);

        // The canvas that app.js turns into a chart is on the page.
        $response->assertSee('data-chart', false);
    }

    /* ---------------------------------------------------------------
     | Teacher dashboard
     * --------------------------------------------------------------- */

    /**
     * The teacher cards only count the teacher's own content (FR-22).
     */
    public function test_the_teacher_dashboard_only_counts_my_own_content(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();

        // Two of my classes: one still ahead, one already finished.
        $upcoming = $this->makeClass($teacher, 'scheduled');
        $finished = $this->makeClass($teacher, 'completed');

        // A class that belongs to somebody else and must stay invisible.
        $this->makeClass($otherTeacher, 'scheduled', 'Secret other class');

        // Two students, one of them enrolled in both of my classes.
        [$sina, $paul] = User::factory()->count(2)->student()->create()->all();
        $upcoming->students()->attach([$sina->id, $paul->id]);
        $finished->students()->attach($sina->id);

        // Both students follow me.
        $sina->follow($teacher);
        $paul->follow($teacher);

        $response = $this->actingAs($teacher)->get('/teacher/dashboard');

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats === [
            'classes' => 2,
            'upcoming' => 1,
            'students' => 2,   // Sina counts once, even though she is in two classes
            'followers' => 2,
        ]);

        // The table only lists my classes.
        $classes = $response->viewData('classes');
        $this->assertSame(2, $classes->total());
        $response->assertDontSee('Secret other class', false);
        $this->assertStringNotContainsString('Secret other class', $response->getContent());

        // The activity stream reports the new followers (FR-25).
        $response->assertSee('started following you');
    }

    /* ---------------------------------------------------------------
     | Student dashboard
     * --------------------------------------------------------------- */

    /**
     * The student dashboard lists the enrolled upcoming classes, the correct
     * counters and only teachers that are not followed yet (FR-19, FR-20, FR-21).
     */
    public function test_the_student_dashboard_shows_upcoming_classes_and_teacher_suggestions(): void
    {
        $student = User::factory()->student()->create();

        $followed = User::factory()->teacher()->create();
        $suggested = User::factory()->teacher()->create();
        $alsoSuggested = User::factory()->teacher()->create();
        $student->follow($followed);

        // I am enrolled in an upcoming and in a finished class of $followed.
        $upcoming = $this->makeClass($followed, 'scheduled');
        $finished = $this->makeClass($followed, 'completed');
        $upcoming->students()->attach($student->id);
        $finished->students()->attach($student->id);

        // An upcoming class I am NOT enrolled in: it must not be listed.
        $notMine = $this->makeClass($suggested, 'scheduled');

        $response = $this->actingAs($student)->get('/student/dashboard');

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats === [
            'classes' => 2,
            'upcoming' => 1,
            'following' => 1,
        ]);

        // Only the upcoming class I am enrolled in is listed.
        $upcomingClasses = $response->viewData('upcomingClasses');
        $this->assertSame([$upcoming->id], $upcomingClasses->pluck('id')->all());
        $this->assertNotContains($finished->id, $upcomingClasses->pluck('id')->all());
        $this->assertNotContains($notMine->id, $upcomingClasses->pluck('id')->all());

        // Teachers I do not follow yet are recommended, the followed one is not.
        $recommended = $response->viewData('recommendedTeachers')->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$suggested->id, $alsoSuggested->id], $recommended);
        $this->assertNotContains($followed->id, $recommended);

        // The Join button is on the page (it becomes a real link in Phase 5).
        $response->assertSee('Join');
    }

    /* ---------------------------------------------------------------
     | Follow / unfollow (FR-11)
     * --------------------------------------------------------------- */

    /**
     * A student can follow and unfollow a teacher.
     */
    public function test_a_student_can_follow_and_unfollow_a_teacher(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)
            ->post(route('follows.store', $teacher))
            ->assertRedirect();

        $this->assertDatabaseHas('follows', [
            'follower_id' => $student->id,
            'following_id' => $teacher->id,
        ]);

        // Following twice does not create a second row.
        $this->actingAs($student)->post(route('follows.store', $teacher))->assertRedirect();
        $this->assertDatabaseCount('follows', 1);

        $this->actingAs($student)
            ->delete(route('follows.destroy', $teacher))
            ->assertRedirect();

        $this->assertDatabaseCount('follows', 0);
    }

    /**
     * Members can follow each other, but never themselves and never an admin.
     */
    public function test_a_student_can_follow_another_student_but_not_themselves_or_an_admin(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();

        // FR-11: following is not limited to teachers.
        $this->actingAs($student)
            ->post(route('follows.store', $otherStudent))
            ->assertRedirect();

        // Following yourself is forbidden.
        $this->actingAs($student)
            ->post(route('follows.store', $student))
            ->assertForbidden();

        // Admin accounts are not followable -> treated as not found.
        $this->actingAs($student)
            ->post(route('follows.store', $admin))
            ->assertNotFound();

        $this->assertDatabaseCount('follows', 1);
    }

    /**
     * Teachers can use the follow routes, admins cannot.
     */
    public function test_teachers_can_follow_but_admins_cannot(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();

        // A teacher may follow a student (FR-11: users follow users).
        $this->actingAs($teacher)
            ->post(route('follows.store', $student))
            ->assertRedirect();

        // The admin area is not part of the social graph.
        $this->actingAs($admin)
            ->post(route('follows.store', $teacher))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Error pages
     * --------------------------------------------------------------- */

    /**
     * The custom Bootstrap error pages are in place.
     */
    public function test_the_custom_error_pages_are_rendered(): void
    {
        // A guest asking for a page that does not exist.
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found');

        // A student opening an admin page.
        $this->actingAs(User::factory()->student()->create())
            ->get('/admin/dashboard')
            ->assertForbidden()
            ->assertSee('You are not allowed to open this page');

        // The 500 page is rendered directly (the debug page replaces it while
        // APP_DEBUG is on, so it cannot be triggered through a request).
        $this->view('errors.500')->assertSee('500');
    }

    /* ---------------------------------------------------------------
     | Helpers
     * --------------------------------------------------------------- */

    /**
     * Build a class for a teacher with a predictable status and title.
     */
    private function makeClass(User $teacher, string $status, ?string $title = null): ClassRoom
    {
        $factory = match ($status) {
            ClassRoom::STATUS_LIVE => ClassRoom::factory()->live(),
            ClassRoom::STATUS_COMPLETED => ClassRoom::factory()->completed(),
            default => ClassRoom::factory()->scheduled(),
        };

        $attributes = ['teacher_id' => $teacher->id];

        if ($title) {
            $attributes['title'] = $title;
        }

        return $factory->create($attributes);
    }
}

```

### `tests/Feature/PhaseThreeUiTest.php`

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

## 4. Commands

### First-time setup

```bash
composer install
npm install
cp .env.example .env          # PowerShell: Copy-Item .env.example .env
php artisan key:generate
```

### Database, assets, server

```bash
php artisan migrate:fresh --seed
npm run build                 # or: npm run dev
php artisan serve
```

### Rebuild and verify

```bash
vendor/bin/pint --test app tests routes config database
npm run build
php artisan view:cache && php artisan view:clear
php artisan test --compact
php artisan test tests/Feature/DashboardTest.php \
                 tests/Feature/DashboardRedesignTest.php \
                 tests/Feature/PhaseThreeUiTest.php
```

Current result:

```text
Tests:    266 passed (1,401 assertions)
Phase 3:  26 passed (235 assertions)  [dashboards + UI]
Pint:     passed
Vite:     139 modules, built successfully
Views:    cached and cleared successfully
```

### Demo logins

- Admin — `admin@educonnect.test` / `EduConnect#Admin2026!`
- Teachers — `teacher1@educonnect.test` … `teacher3@educonnect.test` / `password`
- Students — `student1@educonnect.test` … `student6@educonnect.test` / `password`

### Chart endpoints

```text
GET /admin/dashboard/charts?range=7d|30d|12m    admin.dashboard.charts
GET /teacher/dashboard/charts?range=7d|30d|12m  teacher.dashboard.charts
GET /student/dashboard/charts?range=7d|30d|12m  student.dashboard.charts
```

Each sits inside its role's existing middleware group (`auth`, `not.blocked`,
`role:…`), so series cannot leak across roles. Responses are keyed by the
`series-key` used by the page (`newUsers`, `posts`, `roles`, `classes`,
`enrollments`, `followers`, `activity`).

### Performance notes

- `DashboardMetrics` caches every aggregate set for 300 seconds with
  role/user-scoped keys (`dashboard.admin.*`, `dashboard.teacher.{id}.*`,
  `dashboard.student.{id}.*`).
- Admin totals are one `SUM(CASE …)` query instead of five counts.
- Teacher table uses `withCount('students')`; the enrollment ceiling is one
  grouped query.
- Student recommendations use a `whereNotIn` sub-query, eager-load
  `teacher:id,name,subject,photo`, and limit to 3 / 6 / 3 rows.
- Chart series are computed from one grouped query per series and zero-filled
  in PHP, so empty periods still render.

---

## 5. Per-screen visual checklist

Use this list for the manual pass at **360 px, 768 px, 1200 px and 1920 px**.

### Shared shell — every role

- [ ] Sidebar uses the dark indigo→violet gradient, not a flat colour.
- [ ] Group headings (`Overview`, `Teaching`, `Learning`, `Community`, `Account`) are small and uppercase.
- [ ] The current page has a soft tinted background **and** a visible left indicator bar.
- [ ] On desktop the collapse button hides labels and leaves readable icons; the state survives a reload.
- [ ] On phones the hamburger opens the offcanvas drawer; the close button is visible and focusable.
- [ ] The user mini-card shows avatar, name, role and a settings shortcut.
- [ ] The top bar is sticky, blurred and has a soft bottom border.
- [ ] Page title and breadcrumbs are visible from 1200 px upwards.
- [ ] Global search works on desktop; the search icon appears on phones.
- [ ] The theme toggle switches light/dark instantly, updates its icon and survives a reload.
- [ ] With no stored choice, the page follows `prefers-color-scheme`.
- [ ] The bell shows the unread badge and opens the newest notifications.
- [ ] The avatar menu opens, contains profile settings and logout, and closes on outside click.
- [ ] Cards use a 16 px radius, 1 px soft border and subtle shadow.
- [ ] Hovering a card lifts it ~2 px with a stronger shadow in ~150 ms.
- [ ] Primary, light, outline and soft buttons line up and have visible hover/focus states.
- [ ] Icon-only buttons have a visible focus ring and a 44 px touch target on phones.
- [ ] Tables scroll horizontally instead of breaking the layout at 360 px.
- [ ] Modals, dropdowns and alerts follow the dark theme.
- [ ] `prefers-reduced-motion` removes page fade and hover movement.
- [ ] No horizontal scrollbar at 360 px.

### Admin dashboard

- [ ] Greeting changes with the time of day and uses the first name.
- [ ] Today’s date is visible under the greeting.
- [ ] Quick actions link to the moderation queue, users and posts.
- [ ] Five stat cards: total users, teachers, students, posts, classes.
- [ ] Each card has a coloured icon circle, a big tabular number, `vs last month` and a mini sparkline.
- [ ] `New users per month` is an indigo area line with a soft gradient.
- [ ] The range select offers 7 days / 30 days / 12 months.
- [ ] Changing the range shows the skeleton, then updates labels and values without a reload.
- [ ] `Users by role` is a doughnut with a readable legend.
- [ ] `Posts per week` is a bar chart with rounded bars.
- [ ] `Classes by status` is a doughnut showing scheduled / live / completed.
- [ ] Chart text stays readable in dark mode and charts re-render on toggle.
- [ ] Pending reports show type, reason, excerpt, reporter and relative time.
- [ ] Hide and Dismiss buttons are present and labelled for screen readers.
- [ ] The empty moderation state says the queue is clear.
- [ ] Recent users can be searched and sorted; role badges are coloured.
- [ ] Latest posts show author, excerpt, likes and comments.
- [ ] Today’s live classes show teacher, time, student count and a red join button.
- [ ] The live-class empty state appears when nothing is running.

### Teacher dashboard

- [ ] The hero shows the next scheduled (or currently live) class.
- [ ] The hero includes title, subject, date, time, duration and context.
- [ ] A live countdown ticks every second for a scheduled class.
- [ ] A live class shows the pulsing `Live now` chip instead of a countdown.
- [ ] The enrolled avatar stack overlaps correctly and shows `+N` when needed.
- [ ] The hero has a large `Start class` (scheduled) or `Join live` (live) action.
- [ ] With no class, the hero is replaced by a create-class call to action.
- [ ] Four stat cards: my classes, upcoming, enrolled students, followers.
- [ ] Growth percentages appear next to the relevant cards.
- [ ] The seven-day strip highlights today and colours days with classes.
- [ ] `Enrollments per class` is a bar chart.
- [ ] `Follower growth` is a line chart with a gradient.
- [ ] Both charts have working range selects and skeleton states.
- [ ] The classes table shows title, date/time, status badge and enrollment progress.
- [ ] The progress bar is relative to the largest class and shows a percentage.
- [ ] The action dropdown contains Start/Join, Students, Edit and View details.
- [ ] Search and sortable headers work on the current page.
- [ ] Pagination stays in the table footer and keeps the query string.
- [ ] The activity timeline shows likes, comments, enrollments and followers.
- [ ] Every activity entry has an icon, relative time and an excerpt where relevant.

### Student dashboard

- [ ] The hero shows the next enrolled scheduled class (or a live one).
- [ ] The hero includes teacher, subject, date, time, duration and classmates.
- [ ] A live countdown ticks for a scheduled class.
- [ ] `Join` is visibly disabled with `aria-disabled="true"` until the class is live.
- [ ] When live, the button becomes a real `Join live` link.
- [ ] Four stat cards: enrolled classes, upcoming, teachers followed, notifications.
- [ ] The notifications card links to the notification list.
- [ ] Upcoming classes render as cards with status badge, teacher and student count.
- [ ] The scheduled-card `Join` is disabled; `Details` is always available.
- [ ] The upcoming empty state offers a browse-classes action.
- [ ] Recommended teachers show name, role, subject and follower count.
- [ ] `Follow` posts through the AJAX helper and flips to `Following` without a reload.
- [ ] Recommended classes come from followed teachers or studied subjects.
- [ ] The recommended-class `+` button posts to the enrollment route.
- [ ] Both recommendation empty states explain how to get suggestions.
- [ ] `Learning activity` renders as a bar chart with a working range select.
- [ ] The feed preview shows exactly the three newest posts from followed users.
- [ ] Each preview has author, relative time, excerpt, likes, comments and a post link.
- [ ] `Open full feed` links to the feed.
- [ ] With no followed users, the preview shows a find-people empty state.

### Responsive spot checks

- [ ] **360 px:** single-column cards, no horizontal page scroll, 44 px buttons, scrolling tables, countdown fits inside the hero.
- [ ] **768 px:** two stat cards per row, sidebar still offcanvas, charts keep aspect ratio.
- [ ] **1200 px:** sidebar visible, navbar title/breadcrumbs visible, two-column chart/panel rhythm.
- [ ] **1920 px:** content capped by `container-xxl`, roomier cards, text lines stay readable.

---

## 6. Done when

- [x] Design tokens and global stylesheet compile with Vite; light and dark share one token set.
- [x] Sidebar: gradient, grouped headings, icons, active bar, desktop collapse with localStorage, mobile offcanvas, user mini-card.
- [x] Navbar: sticky with blur, page title + breadcrumbs, global search, theme toggle, bell with badge, avatar dropdown.
- [x] Theme toggle writes `educonnect.theme`, respects `prefers-color-scheme`, and paints before first paint.
- [x] Page transitions and skeleton loaders are present and reduced-motion safe.
- [x] Admin: greeting + date + quick actions; five stat cards with trend and sparkline; four themed charts; 7d/30d/12m fetch ranges; pending reports with Hide/Dismiss; recent users; latest posts; today’s live classes.
- [x] Teacher: next-class hero with countdown and avatar stack; four stat cards with growth; seven-day schedule; classes table with progress bars and action dropdown; enrollment bar + follower line; activity timeline.
- [x] Student: next-class hero with live-gated Join; four stat cards; upcoming card grid; AJAX-follow recommended teachers; recommended classes; learning-activity chart; three-post feed preview.
- [x] All eight shared components exist and are reused rather than duplicated.
- [x] Charts have text fallbacks (`visually-hidden` + `noscript`) and ARIA labels.
- [x] Icon-only controls labelled; focus rings visible; 44 px targets on phones; `prefers-reduced-motion` honoured.
- [x] Aggregates cached 300 s with scoped keys; eager loading and `withCount` throughout; no N+1 in the dashboard paths.
- [x] All earlier dashboard contracts preserved; scheduled `Join` still disabled with `aria-disabled`.
- [x] `vendor/bin/pint --test` passes on application code.
- [x] `npm run build` succeeds (139 modules).
- [x] `php artisan view:cache` compiles every view.
- [x] `php artisan test` → **266 passed, 1,401 assertions**.
- [ ] Manual visual sign-off at 360 / 768 / 1200 / 1920 px in a real browser (no browser automation installed here).

### Environment limits (not code gaps)

- MySQL 8 is not installed here, so the MySQL migration path was not executed.
  Queries use portable `whereDate` / `whereTime` / `SUM(CASE …)` and the suite
  runs on SQLite `:memory:`.
- No Playwright/Puppeteer is available, so the four viewport checks remain a
  manual checklist rather than an automated screenshot suite.
- Real SMTP delivery, production queue workers and load testing are out of
  scope for this phase.
