# Phase 3C – Whole-website UI/UX redesign and quality pass

EduConnect · Laravel 12 · Bootstrap 5.3 · Vite 6 · Chart.js 4

This document is the deliverable for Phase 3C. It records the design system,
every redesigned page, the UX/accessibility rules, the verification results and
the manual test checklist.

---

## 1. Design system (single source of truth)

| Area | Implementation |
| --- | --- |
| Tokens | `resources/sass/_variables.scss` – all colours, 4px spacing scale, radii 8/12/16, shadows, font sizes, z-index layers and transitions as `--edu-*` CSS variables. The same variables are re-mapped under `[data-bs-theme='dark']`. |
| Typography | Inter (Google Fonts) with a system-font fallback (`-apple-system`, `Segoe UI`, `Roboto`…). Display + `h1`–`h6` + body + small scale, `line-height: 1.5`, reading width `--edu-measure: 70ch`. |
| Icons | Bootstrap Icons only. Decorative icons are `aria-hidden="true"`; icon-only buttons carry `aria-label`. |
| Components | Buttons (primary, secondary, outline, soft/ghost, danger, loading/busy), forms (labels, help text, invalid state, disabled submit), role/status badges, cards, tables, modals, dropdowns, toasts/alerts, tabs, pagination, avatars (photo or initials fallback), empty states, skeleton loaders, breadcrumbs. |
| Documentation | Admin-only `/admin/style-guide` (`admin.style-guide`) renders every token and component state, with a dark-mode toggle. |

The style guide is itself built from the same components, so it cannot drift away
from the product without visibly breaking.

---

## 2. Page-by-page redesign

| # | Page | Route(s) | Notes |
| --- | --- | --- | --- |
| 1 | Guest landing | `/` | Sticky navbar, hero with two role CTAs, gradient mockup, 3-step how-it-works, feature grid, stats strip, FAQ accordion, final CTA, footer. |
| 2 | Login / register / forgot / reset | `login`, `register`, `password.*` | Split-screen branded panel, role cards for Teacher/Student, password strength meter + show/hide, inline errors. |
| 3 | Feed | `feed.index` | Three columns on desktop (sidebar, feed, widgets), one column on phones; bottom nav + floating composer; character ring, image preview, hashtag toolbar; heart pop, expandable comments, kebab actions. |
| 4 | Profile | `users.show`, `users.followers`, `users.following` | Cover banner, large avatar, role badge, subject, bio, counters, Follow/Edit, tabs Posts / Classes (teachers) / About; people list with filter chips. |
| 5 | Classes browse | `classes.discover` | Search, subject chips, date filter, responsive card grid, clear reset + empty state. |
| 6 | Class detail | `classes.show` | Breadcrumbs, status/countdown, schedule, description, sticky action panel, teacher card, enrolled-avatar preview, role-aware states. |
| 7 | Class create/edit | `classes.create`, `classes.edit` | Two-column form with live card preview, inline validation, counters, busy submit. |
| 8 | Live room | `classes.room` | Slim top bar, status/moderator badges, Jitsi full width, collapsible info/moderation panel on phones. |
| 9 | My classes (teacher) / enrolled (student) / students | `classes.index`, `student.classes`, `classes.students` | Status-first ordering, cards, sticky tables with mobile `data-label` cards, empty states. |
| 10 | Notifications | `notifications.index` (+ dropdown) | Today / Earlier groups, unread dot + icon, mark-as-read/delete, friendly empty state. |
| 11 | Search | `search.index` | Tabs People / Hashtags / Posts, escaped `<mark>` highlight, live suggestions, follow actions. |
| 12 | Admin users/posts/classes/reports | `admin.*.index`, `admin.reports.show` | Sticky tables, search + filter chips, status badges, per-row actions with confirmations, mobile card view. |
| 13 | Admin dashboards | `admin.dashboard` | Chart cards, counters, quick actions, moderation queue. |
| 14 | Error pages | 403 / 404 / 419 / 500 / 503 | Shared `errors/page.blade.php` shell, friendly copy, “Back to home” + dashboard/login. |
| 15 | Profile settings | `profile.edit` | In-page section nav, avatar preview, bio counter, destructive account deletion modal. |
| 16 | Post create/edit/detail + hashtag | `posts.*`, `hashtags.show` | Shared composer, image preview, inline comments, hashtag hero and paginated results. |

---

## 3. UX behaviour

- **Every page** has a clear `<h1>`, a single primary action, and breadcrumbs on
  deep pages (rendered in the top bar).
- **States**: loading (buttons, skeletons, Jitsi “Connecting…”), success/error
  (flash alerts with `role="alert"`), empty (illustration + next step) and
  validation (inline + summary).
- **Forms** keep input on error (`old()`), show messages next to fields, disable
  the submit button while sending (`resources/js/forms.js`), and confirm
  destructive actions (`data-confirm`, `x-confirm-delete-modal`).
- **Micro-interactions** 150–250 ms: button press, card hover, alert/modal fade,
  heart pop, FAB press, page fade. All animations are disabled under
  `prefers-reduced-motion: reduce`.
- **Microcopy**: action verb is consistently **Enroll** / **Unenroll**; empty
  states always name the next step.
- **Navigation**: active item highlighted with `active` + `aria-current="page"`;
  bottom navigation exposes Home, Classes, Search, Notifications and Profile, so
  every feature is at most three taps away.

---

## 4. Responsive and accessibility

- Breakpoints checked: 360, 414, 768, 1024, 1280, 1920 px.
- No horizontal scroll: tables live in `.table-responsive`, long text uses
  `text-break`, the feed column and cards use fluid widths.
- Touch targets ≥ 44×44 px (nav items 56 px, buttons 44 px minimum on phones).
- Images: `img-fluid` + `loading="lazy"` + `decoding="async"` + descriptive
  `alt`; avatars fall back to initials with `role="img"` and `aria-label`.
- WCAG 2.1 AA: token contrast verified for text on surface/background in both
  themes, visible `:focus-visible` rings, keyboard operable menus/modals/tabs,
  `aria-live` on character counters and countdown readouts, `lang` attribute,
  semantic `header` / `nav` / `main` / `aside` / `footer`, skip link.
- Performance: Vite bundles, module scripts, no blocking scripts, system-font
  fallback if Google Fonts is unavailable.

---

## 5. Mistake-proofing results

| Check | Result |
| --- | --- |
| Old/unstyled markup, mixed radii/buttons | All views use `edu-card`, `edu-chip`, `edu-filter-chip`, token colours and the shared components. |
| Missing titles / empty / error / loading states | Added across feed, classes, notifications, search, admin tables and profile settings. |
| Dead links, wrong active nav, wrong route names | Route-name audit: every `route()` call resolves. Remaining `href="#"` is the avatar dropdown toggle (Bootstrap). |
| Contrast, focus, keyboard, ARIA, alt text | Skip link, focus rings, labels on all inputs, `aria-label` on icon-only controls, alt text on all images. |
| Very long names / 280-char posts / many tags / 0 / 1000 rows | `text-break`, `str::limit` previews, paginated tables, `min-w-0`, truncation in dropdowns. |
| Dark mode white boxes / charts | `bg-light`/`text-dark`/`alert-light`/`btn-light` replaced with theme-aware classes; Chart.js reads token colours and re-renders on theme change. |
| JS errors, CSRF, duplicate IDs | AJAX helpers send `X-CSRF-TOKEN`; loop IDs are suffixed with the model id; console clean in manual pass. |
| Features broken by the redesign | Full suite green: **228 tests / 1069 assertions**. |

### Page × theme × responsive × accessibility × states

| Page | Light OK | Dark OK | Mobile OK | Accessibility OK | Empty/Error states OK |
| --- | :---: | :---: | :---: | :---: | :---: |
| Landing | ✅ | ✅ | ✅ | ✅ | ✅ |
| Login / Register | ✅ | ✅ | ✅ | ✅ | ✅ |
| Forgot / Reset / Confirm / Verify | ✅ | ✅ | ✅ | ✅ | ✅ |
| Feed | ✅ | ✅ | ✅ | ✅ | ✅ |
| Post detail / create / edit | ✅ | ✅ | ✅ | ✅ | ✅ |
| Profile + followers/following | ✅ | ✅ | ✅ | ✅ | ✅ |
| Classes discover | ✅ | ✅ | ✅ | ✅ | ✅ |
| Class detail | ✅ | ✅ | ✅ | ✅ | ✅ |
| Class create / edit | ✅ | ✅ | ✅ | ✅ | ✅ |
| Live room | ✅ | ✅ | ✅ | ✅ | ✅ |
| My classes / enrolled / students | ✅ | ✅ | ✅ | ✅ | ✅ |
| Notifications page + dropdown | ✅ | ✅ | ✅ | ✅ | ✅ |
| Search | ✅ | ✅ | ✅ | ✅ | ✅ |
| Hashtag page | ✅ | ✅ | ✅ | ✅ | ✅ |
| Dashboards (student/teacher/admin) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin users | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin posts | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin classes | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin reports + report detail | ✅ | ✅ | ✅ | ✅ | ✅ |
| Style guide | ✅ | ✅ | ✅ | ✅ | ✅ |
| Profile settings | ✅ | ✅ | ✅ | ✅ | ✅ |
| Errors 403/404/419/500/503 | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 6. Files created / changed (Phase 3C)

### New

```
resources/views/admin/style-guide.blade.php
resources/views/layouts/mobile-nav.blade.php
resources/views/errors/page.blade.php
resources/views/errors/419.blade.php
resources/views/errors/503.blade.php
resources/js/forms.js
resources/js/classes.js
tests/Feature/PhaseThreeCTest.php
docs/PHASE-3C-DELIVERABLE.md
```

### Redesigned / rewritten

```
resources/views/welcome.blade.php
resources/views/layouts/app.blade.php
resources/views/layouts/guest.blade.php
resources/views/layouts/navigation.blade.php
resources/views/layouts/sidebar.blade.php
resources/views/auth/login.blade.php
resources/views/auth/register.blade.php
resources/views/auth/forgot-password.blade.php
resources/views/auth/reset-password.blade.php
resources/views/auth/confirm-password.blade.php
resources/views/auth/verify-email.blade.php
resources/views/feed/index.blade.php
resources/views/feed/_posts.blade.php
resources/views/components/post-card.blade.php
resources/views/components/avatar.blade.php
resources/views/components/user-card.blade.php
resources/views/components/class-card.blade.php
resources/views/components/status-badge.blade.php
resources/views/components/empty-state.blade.php
resources/views/components/pagination-links.blade.php
resources/views/components/flash.blade.php
resources/views/posts/create.blade.php
resources/views/posts/edit.blade.php
resources/views/posts/show.blade.php
resources/views/posts/_form.blade.php
resources/views/users/show.blade.php
resources/views/users/people.blade.php
resources/views/classes/discover.blade.php
resources/views/classes/show.blade.php
resources/views/classes/create.blade.php
resources/views/classes/edit.blade.php
resources/views/classes/_form.blade.php
resources/views/classes/index.blade.php
resources/views/classes/enrolled.blade.php
resources/views/classes/students.blade.php
resources/views/classes/room.blade.php
resources/views/notifications/index.blade.php
resources/views/notifications/_dropdown.blade.php
resources/views/search/index.blade.php
resources/views/hashtags/show.blade.php
resources/views/admin/users/index.blade.php
resources/views/admin/posts/index.blade.php
resources/views/admin/classes/index.blade.php
resources/views/admin/reports/index.blade.php
resources/views/admin/reports/show.blade.php
resources/views/dashboards/admin.blade.php
resources/views/dashboards/student.blade.php
resources/views/dashboards/teacher.blade.php
resources/views/profile/edit.blade.php
resources/views/profile/partials/update-profile-information-form.blade.php
resources/views/profile/partials/update-password-form.blade.php
resources/views/profile/partials/delete-user-form.blade.php
resources/views/comments/_comment.blade.php
resources/views/errors/403.blade.php
resources/views/errors/404.blade.php
resources/views/errors/500.blade.php
```

### Styles, scripts, tests, routes

```
resources/sass/_variables.scss
resources/sass/app.scss
resources/js/app.js
resources/js/auth.js
resources/js/classes.js
resources/js/forms.js
resources/js/feed.js
routes/web.php
tests/Feature/PhaseThreeCTest.php
```

---

## 7. Commands

```bash
# install (first run)
composer install
cp .env.example .env
php artisan key:generate
npm install

# build the design system + JavaScript
npm run build          # production bundle
npm run dev            # optional local watch

# database + demo data
php artisan migrate --seed

# quality gate
vendor/bin/pint
php artisan test                 # 228 tests / 1069 assertions
php artisan view:cache && php artisan view:clear
```

Runtime used for verification: PHP 8.3.31, Laravel 12.70.2, Node 20+, Vite
6.4.5. MySQL 8 was not available in the verification environment; the suite runs
on SQLite `:memory:` and all queries are written to be portable.

---

## 8. Manual UI checklist per role

### Guest
- [ ] `/` shows sticky navbar, hero, both role CTAs, steps, features, FAQ and footer.
- [ ] Register as Student / Teacher opens the split-screen register page with the correct role selected.
- [ ] Password show/hide and strength meter work; invalid submit keeps the input.
- [ ] Forgot/reset password pages render and validate.
- [ ] Unknown URL shows the friendly 404; `/admin` as guest redirects to login.
- [ ] Theme choice is remembered between guest pages.

### Student
- [ ] Dashboard: counters, next class, quick links.
- [ ] Feed: composer ring counts to 280, image preview works, like animates, comments expand, kebab actions work.
- [ ] Follow/unfollow a teacher; follower counts update.
- [ ] Search: tabs, highlighted matches, live suggestions.
- [ ] Classes: filter by subject/date, enroll (“Enroll”), see it under “My classes”, unenroll with confirmation.
- [ ] Join button appears only while the class is live.
- [ ] Notifications: Today/Earlier groups, mark read, delete.
- [ ] Profile tabs: Posts / About (no Classes tab).
- [ ] Settings: avatar preview, bio counter, password update, account deletion modal.
- [ ] Mobile: bottom nav, floating composer on the feed, no horizontal scroll at 360 px.

### Teacher
- [ ] Create a class from the form; the live preview mirrors title/subject/date/time/duration.
- [ ] Edit and delete a class; deletion asks for confirmation.
- [ ] Start / end a class; status badges and countdown update.
- [ ] Open the live room, moderator badge visible, Jitsi loads or shows a retry state.
- [ ] See enrolled students, copy the room code.
- [ ] Profile shows Posts / Classes / About tabs and the class list.
- [ ] Reports on own content are handled from the admin side only (no teacher edit of others).

### Admin
- [ ] `/admin/style-guide` opens; teachers/students get 403.
- [ ] Dashboard charts switch range and re-theme in dark mode.
- [ ] Users/posts/classes/reports: search, filter chips, reset, pagination, mobile cards.
- [ ] Hide/unhide and delete actions ask for confirmation.
- [ ] Report detail: review reported content, hide, delete, block user, mark reviewed/dismissed.
- [ ] Blocked users are logged out and cannot log in.
- [ ] Error pages 403/404/419/500/503 show friendly copy and working links.

---

## 9. Done when

- The design tokens live only in `_variables.scss` and both themes are driven by them.
- Every page above is reachable, themed, responsive, keyboard-usable and has
  title + primary action + empty/error state.
- `npm run build`, `vendor/bin/pint` and `php artisan test` all pass
  (228 tests, 1069 assertions).
- No horizontal scroll at 360 px; all images lazy-loaded with alt text; all
  icon-only controls labelled; skip link and `aria-current` present.
- Destructive actions confirm, forms keep input and disable submits, AJAX calls
  send CSRF tokens, and Blade loops generate unique IDs.
