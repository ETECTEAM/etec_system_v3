# Agent Guide — ETEC System v3

Guidance for AI coding agents working in this repository. Read this before making changes.

## What this project is

Laravel 12 + Inertia.js + Vue 3 school/enrollment management system (students, classes,
courses, instructors, terms, schedules, rooms/buildings, registration, notifications,
QR-based attendance, etc). Tailwind v4 for styling. Real-time via Laravel Reverb + Echo.
Permissions via `spatie/laravel-permission`.

- Backend entry points: `routes/web.php`, `routes/api.php`, `routes/web/backend/*.php`,
  `routes/web/frontend/*.php` (route files are split per feature, not one monolith).
- Frontend pages: `resources/js/pages/backend/**` (admin dashboard) and
  `resources/js/pages/frontend/**` (public site). Inertia maps controller `render()` calls
  to these `.vue` files.
- Shared Vue building blocks: `resources/js/components/{ui,backend,frontend}`,
  `resources/js/composables/`, `resources/js/layouts/`.

## Module structure (important — non-standard)

This app uses `nwidart/laravel-modules`, but modules live under **`app/Modules/<Name>/`**,
not the package's usual root-level `Modules/`. Each module is organized by concern, not MVC:

```
app/Modules/<Name>/
  Controllers/
  Requests/
  Data/            # DTO-like value objects
  Notifications/
  ...
```

`modules_statuses.json` only lists `User`, so don't trust it as the source of truth for
which modules are active — check `app/Modules/` directly. Current modules include: Account,
Auth, Class, Course, EnRoll, Enroll (note: both exist, likely one is stale — verify before
touching either), Floor, Instructor, Notification, Registration, Room, Schedules,
ShiftTemplate, Terms, Times, User, Website, building.

When adding a feature, put backend logic in the matching `app/Modules/<Name>/` folder rather
than `app/Http/Controllers` — the latter is nearly empty and not the active pattern.

## Conventions this codebase already follows — match them

### Route file comments (see `routes/web/backend/user.php` as the reference)
- One-line comment directly above each route: `// Route to <verb> <what>.` — describes
  *what*, not *how*.
- One-line comment above a `Route::middleware(...)->group()` block explaining *why* routes
  are grouped that way (e.g. throttle tier reasoning), not just restating the middleware.
- Don't comment routes that are already self-evident from URI + controller method.
- Preserve the top-of-file docblock describing the route file's purpose.

### Vue component style
- Plain HTML element attributes (`input`, `button`, `Link`, `p`, `span`, `td`, `th`, etc.)
  stay on a single line — don't wrap attributes across multiple lines.
- Custom PascalCase components (`SelectSearch`, `Pagination`, `PageHero`, etc.) are exempt
  and may span multiple lines when they have several props.
- Comments are one line max, only for non-obvious logic (computed props, watchers, form
  defaults, submit behavior, disabled/locked state, why a value is blank). Don't restate
  what's already obvious from the code.

### Save/form logic
- Global, feature-agnostic form logic belongs in `resources/js/composables/` (e.g. a generic
  `useSaveForm` wrapping Inertia's `useForm`). It must stay generic — no feature-specific
  logic in there.
- Feature-only logic (derived data, toggles, formatting tied to one feature's data shape)
  goes in `resources/js/pages/backend/<feature>/composables/use<Feature>.js` and may compose
  the global composable.
- Before adding a new composable, check whether an equivalent already exists and reuse it.

See `prompt.md` in the repo root for the fuller versions of these conventions (written by
the project owner as reusable prompts) — treat it as authoritative style reference.

## Running things

```bash
composer install && npm install
php artisan key:generate && php artisan migrate
composer run dev     # server + queue listener + vite + reverb, concurrently
php artisan test     # PHPUnit
```

Docker-based setup is also supported — see `README.md` for the full matrix (Windows/macOS/
Linux, Docker vs. non-Docker). Default local ports: app `8001` (or `8000` via `composer
serve`), Vite `5173`, Reverb `8080`.

## Things to double check before assuming

- `Class` module vs `class.php`/`schdule.php` (note: **misspelled** "schdule" — not a typo
  to silently rename mid-task, it's the real filename/route path in use).
- `EnRoll` vs `Enroll` modules both exist — confirm which is live before editing either.
- `app/Http/Controllers/Test.txt` and `chii.txt` look like stray scratch files, not part of
  the app — don't treat them as real controllers, and don't delete them without asking.
- `.phpunit.result.cache`, `code-audit-report.md` are generated/report artifacts, not
  hand-maintained docs.

## Docs already in the repo

`docs/` has workflow write-ups worth reading before touching related areas:
`auth-controller-workflow.md`, `login-workflow.md`, `notification-workflow.md`,
`registration-workflow.md` (instructor self-registration),
`public-class-registration-workflow.md` (public `/classes` self-registration —
a separate flow, don't conflate the two), `vps-deployment-specs.md`.
