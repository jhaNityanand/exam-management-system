# Theme System (Dark / Light Mode)

This application uses **class-based dark mode** with Tailwind CSS v3.

## How it works

1. **Tailwind** — `darkMode: 'class'` in `tailwind.config.js` makes all `dark:` utilities respond to a `.dark` class on `<html>`.
2. **JavaScript** — `resources/js/core/theme.js` toggles the class, persists preference, and listens for OS theme changes.
3. **FOUC prevention** — `resources/views/partials/theme-init.blade.php` runs inline in `<head>` before CSS loads so the correct theme applies on first paint.
4. **Persistence** — Preference is stored in `localStorage` under key `ems.theme` (`light`, `dark`, or `system`).

## Theme resolution order

1. `localStorage.getItem('ems.theme')` if set
2. `data-theme-default` on `<html>` (from `user_app_settings.theme` for authenticated users, otherwise `system`)
3. When preference is `system`, `prefers-color-scheme: dark` is used

## Toggle UI

- **Backend panel** — Sun/moon button in the top bar (`<x-theme-toggle />`)
- **Auth / guest pages** — Same component, top-right corner
- **Public home** — Same component

Reusable component: `resources/views/components/theme-toggle.blade.php`

## Styling layers

| Layer | Location | Dark mode approach |
|-------|----------|-------------------|
| Global Tailwind | `resources/css/app.css` | `dark:` utilities + shared components (`.panel-card`, `.panel-input`, etc.) |
| Blade templates | `resources/views/**` | Inline `dark:` Tailwind classes |
| Page CSS | `public/css/backend/*.css`, `public/css/modules/*.css` | `.dark .selector { ... }` overrides |
| Third-party widgets | Tom Select, CKEditor, SweetAlert2 | Custom `.dark` rules in page/global CSS |

## Key files changed

- `tailwind.config.js` — Added `darkMode: 'class'`
- `resources/views/partials/theme-init.blade.php` — Blocking init script
- `resources/views/backend/layouts/base.blade.php` — Theme init + `data-theme-default`
- `resources/js/core/theme.js` — Theme engine (localStorage, system preference, toggle)
- `app/Providers/AppServiceProvider.php` — View composer wired to active layouts
- `public/css/backend/exam-create.css` — Full dark mode overrides for exam create flow
- `public/css/backend/category-list.css` — Dark mode for modals and SweetAlert dialogs

## Adding dark mode to new pages

**Prefer Tailwind** in Blade:

```html
<div class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100">
```

**For custom CSS** in `public/css/`:

```css
.my-component { background: #fff; color: #0f172a; }
.dark .my-component { background: #1e293b; color: #f1f5f9; }
```

**For CSS variables** (recommended for complex pages):

```css
:root { --card-bg: #ffffff; }
.dark { --card-bg: #1e293b; }
.my-card { background: var(--card-bg); }
```

## Build

After changing Tailwind classes or `app.css`, rebuild assets:

```bash
npm run dev    # development
npm run build  # production
```

## Future improvements

- API endpoint to persist theme to `user_app_settings` for cross-device sync
- Three-way picker UI (Light / Dark / System) using existing `data-theme-option` hooks in `theme.js`
