# AGENT_GUIDE — ETEC System v3

Guidance for AI coding agents working in this repository. **Read this before making any
changes.** It documents the real, sometimes inconsistent, patterns in this codebase and the
traps that will break things if you "fix" them.

> This file is the authoritative, up-to-date guide (an older, smaller `agent_guide.md` that
> used to sit alongside it has since been deleted — don't recreate it). `prompt.md` contains
> the project owner's reusable style prompts and is the authoritative style reference for
> frontend refactors.

---

## 1. Project Overview

Laravel 12 + Inertia.js v3 + Vue 3 school/enrollment management system (students, classes,
courses, instructors, terms, schedules, buildings/floors/rooms, registration, notifications,
QR-based attendance, public class registration). Tailwind CSS v4, real-time via Laravel
Reverb + Echo, permissions via `spatie/laravel-permission`, Telegram auth/notifications via
`irazasyed/telegram-bot-sdk`, i18n English + Khmer.

### Tech stack

| Area | Choice |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Frontend | Inertia.js v3, Vue 3 (SFC, `<script setup>`), Tailwind v4 |
| Modules | `nwidart/laravel-modules` v12 — **but modules live under `app/Modules/`, not root `Modules/`** |
| Auth/perms | Laravel auth + `spatie/laravel-permission`, custom lockout tiers |
| Real-time | Laravel Reverb + `laravel-echo` / `pusher-js` (lazy `getEcho()`) |
| Icons | `@lucide/vue` |
| Toasts | `vue-toastification` |
| Routing helpers | Ziggy (`ziggy-js`), `tightenco/ziggy` |
| DB | MySQL (Docker default), timezone `Asia/Phnom_Penh` |
| Locales | English (`en.json`) + Khmer (`km.json`) |

### Entry points

- **Web routes**: `routes/web.php` → `includeRouteFiles(__DIR__.'/web/backend')` and
  `...'/web/frontend'` auto-load **every** `.php` file in those folders (see
  `app/Helpers/helpers.php`). Adding a new file to either folder auto-registers it.
- **API routes**: `routes/api.php`; **console**: `routes/console.php`; **channels**:
  `routes/channels.php`.
- **Frontend bootstrap**: `resources/js/app.js` (`createInertiaApp` +
  `resolvePageComponent('./pages/${name}.vue', import.meta.glob('./pages/**/*.vue'))`).
  Inertia maps a controller's `Inertia::render('backend/Foo/Bar')` to
  `resources/js/pages/backend/Foo/Bar.vue`.
- **No SSR**: there is no `resources/js/ssr.js`.

---

## 2. Module Architecture

Modules live under **`app/Modules/<Name>/`** organized by concern. Standard sub-folders:

```
app/Modules/<Name>/
  Controllers/
  Requests/        # FormRequest validation
  Data/            # DTO-like value objects (e.g. StoreUserData)
  Services/        # Business logic + presenters
  Actions/         # Write-side use cases (Enroll, Website)
  Queries/         # Read-side presenters (Enroll)
  Notifications/
  Events/ Listeners/ Policies/ Responses/
```

**Backend logic goes in `app/Modules/<Name>/` — NOT `app/Http/Controllers`.** The latter is
nearly empty (only `Controller.php` base + `LocaleController.php`) and is not the active pattern.

### Current modules and their non-standard quirks

| Module dir | Status | Quirk |
|---|---|---|
| `Account` | live | standard |
| `Auth` | live | `Controllers/Telegram/`, `Data/Permissions/`, `Requests/Permissions/`, `Responses/Permissions/` subfolders |
| `Class` | live | only `Controllers/` (ClassType + ClassList) |
| `Course` | live | **controllers at module ROOT** (`CourseController.php`, `CategoryController.php`, …) — no `Controllers/` folder |
| `EnRoll` | **DEAD/legacy** | 3 files, imported by nothing; redirects to non-existent `students.*` routes; uses old `ScheduleClass`/`Enrollment` models |
| `Enroll` | **LIVE** | route-wired via `routes/web/backend/enroll.php`; `Actions/` + `Queries/` pattern; uses `StudyClass`/`StudentEnrollment` |
| `Floor` | live | **`Controller/` (singular)** folder — differs from every other module |
| `Instructor` | live | standard; includes `InstructorScheduleBlockController` for instructors to self-block unavailable time slots |
| `Notification` | live | standard |
| `Registration` | live | standard |
| `Room` | live | standard (`Controllers/`, `Data/`, `Requests/`, `Services/`) |
| `Schedules` | live | plural name; route file is the misspelled `schdule.php` |
| `Terms` / `Times` | live | plural module names, singular route files `term.php`/`time.php` |
| `User` | live | richest module: `Controllers/`, `Data/`, `Policies/`, `Requests/`, `Services/` |
| `Website` | live | `Actions/` + `Services/`; also owns the public `/join-class/{studyClass}` flow (`ClassJoinController`) alongside `/student-register` |
| `building` | live | **lowercase directory name** |
| `Attendance` | live | `Actions/`, `Queries/`, `Requests/`, `Controllers/` (settings only) — **no `Services/`**. Its `Actions/` (`GenerateClassSessions`, `AutoRecordSession`, `FinalizeAutoRecordedSession`, `OverrideAttendanceRecord`) are driven by scheduled console commands (see §5) and consumed from `Instructor` — the actual attendance-recording UI/routes live under `Instructor`, not here. |
| `WorkSchedule` | live | **only `Controllers/WorkScheduleController.php`** — no `Requests/`/`Data/`/`Services/`; validation is inline `$request->validate()` in the controller. **Replaced the old `ShiftTemplate` module** (deleted outright — see traps below). |

> **`ShiftTemplate` no longer exists.** It was fully removed (module dir, controller, route
> file, frontend `shift-templates/` pages, DB table) and replaced by `WorkSchedule`
> (`work-schedule.php` route, `work_schedule.*` permissions, `resources/js/pages/backend/work-schedules/`).
> If you see `ShiftTemplate` referenced anywhere, it's stale — don't resurrect it.

### Module traps

- **`EnRoll` vs `Enroll`** — only `Enroll` (lowercase *r*) is wired to routes. Do **not**
  rename/harmonize the folders or namespaces during a normal task (PSR-4 is case-sensitive on
  Linux). The UI string `"Start EnRoll"` in `commonText.js` / `ClassForm.vue` is **legitimate
  copy**, not a typo pointing at the module.
- **`Floor` and `building` use `Controller/` (singular)** while all others use `Controllers/`.
  Match whichever the module you're editing already uses.
- **`Course` module has root-level controllers** — don't create a `Controllers/` folder there
  expecting auto-discovery; follow the existing root-level pattern instead.
- **`app/Models/` is flat** — all Eloquent models (40+) live directly in `app/Models/`, not
  per-module. Models are named after tables (`StudyClass`, `StudentEnrollment`, `ScheduleClass`).
- **`teams` / `team_members` (instructor "class groups" feature) have no Eloquent model at
  all** — `InstructorClassService` reads/writes them purely via `DB::table('teams')` /
  `DB::table('team_members')`. Don't assume a `Team` model exists; don't add one speculatively.
- Shared code: `app/Enums/` (e.g. `UserStatus`), `app/Helpers/helpers.php` (composer
  autoloads it), `app/Console/Commands/` (e.g. `SyncEnvExample` for `composer run sync-env`).
- **Do not trust `modules_statuses.json`** — it only lists `User`. Source of truth is
  `app/Modules/` + the route files.

---

## 3. Routing Conventions

### File organization

- One route file per feature under `routes/web/backend/` (and public-site routes under
  `routes/web/frontend/`). All are auto-loaded by `includeRouteFiles()`.
- Route files are grouped with `Route::middleware([...])->prefix('/dashboard/<feature>')->group(...)`.
- **Route names**: grouped files use `->name('<feature>.')` (e.g. `enroll.*`, `schdule.*`,
  `class-types.*`). Note `class.php` uses the older `Route::controller(...)->prefix(...)`
  style — follow the newer `enroll.php` style for new routes.

### Comment standards (reference: `routes/web/backend/enroll.php`, `user.php`)

- One-line comment directly above each route: `// Route to <verb> <what>.` — describes
  **what**, not how.
- One-line comment above a `Route::middleware(...)->group()` explaining **why** routes are
  grouped that way (throttle tier reasoning, ownership rules), not restating the middleware.
- Multi-line comments (rare) only for genuinely non-obvious flows (e.g. the QR self-registration
  flow in `enroll.php`).
- Don't comment routes already obvious from URI + controller method.
- Preserve top-of-file docblocks on older files; new files may omit them.

### Middleware conventions

| Middleware | Meaning |
|---|---|
| `auth` | `Authenticate` |
| `active` | `EnsureAccountIsActive` (bootstrap/app.php alias) |
| `role:` / `permission:` | spatie `RoleMiddleware` / `PermissionMiddleware` |
| `throttle:60,1` / `throttle:20,1` | reads vs mutations (see `user.php`) |

Aliases are registered in `bootstrap/app.php`. Standard groups: `['auth', 'active']` for
logged-in pages, `['auth', 'active', 'role:...']` for role-restricted, no middleware for
public routes (e.g. QR self-registration, `frontend.classes.*`).

### Route-file / module mapping gotchas

- `routes/web/backend/schdule.php` (misspelled) → `App\Modules\Schedules\...`, prefix
  `/dashboard/schdule`, name `schdule.`. **The misspelling is the real, in-use value.**
- `building.php` → `app/Modules/building` (lowercase).
- `floor.php` → `App\Modules\Floor\Controller\FloorController` (singular `Controller`).
- `instructor-schedule-block.php` → `App\Modules\Instructor\Controllers\InstructorScheduleBlockController`; prefix `/dashboard/instructor-schedule-blocks`, name `instructor-schedule-blocks.`; gated by `role:instructor` middleware (instructor-only, not admin). Routes have no `{instructor}` parameter — the controller resolves the authenticated instructor via `$request->user()->instructorData()`.
- `certificate.php` and `dashboard.php` define closures/Inertia renders inline with **no**
  module controller — read them before assuming a module exists.
- `class.php` uses `prefix('dashboard')` **without a leading slash** — a pre-existing
  inconsistency; don't "fix" it unless you verify all route names still resolve.
- `work-schedule.php` → `App\Modules\WorkSchedule\Controllers\WorkScheduleController`, prefix
  `/dashboard/work-schedules`, name `work-schedules.`, gated by `work_schedule.*` permissions.
  This is the replacement for the deleted `shift-template.php` / `ShiftTemplate` module.
- `instructor.php` has **no route-name prefix on the group** (unlike other files) — most of
  its routes are named explicitly per-route (`instructor.classes.*`); the bare `/`,
  `/profile` (GET+PUT), and the attachment-delete route are **unnamed**. `POST` and `PUT` to
  the same `/classes/{studyClass}/attendance` URI are two different actions
  (`storeAttendance` vs. `overrideAttendance`, the latter for correcting an auto-recorded
  session) — not a duplicate, just verb-disambiguated.
- `routes/web/frontend/class_data.php` (snake_case filename, unlike its sibling frontend
  files) holds **both** the `/student-register` flow (`StudentRegisterController`) and the
  newer `/join-class/{studyClass}` flow (`ClassJoinController`) — two related but distinct
  public registration entry points. `docs/public-class-registration-workflow.md` predates
  `ClassJoinController` and does not describe it — read the controller directly.

---

## 4. Frontend Standards

### Directory layout (`resources/js/`)

```
resources/js/
  app.js              # Inertia bootstrap (only real entry)
  bootstrap.js        # axios setup
  echo.js             # lazy Reverb/Pusher singleton (getEcho())
  components/
    ui/               # shared UI component sets (one folder per component)
    frontend/         # public-site components
  composables/        # GLOBAL composables (module-scoped state)
  config/sidebar.js   # role-based sidebar config
  i18n/               # custom i18n system (backendText.js etc.)
  layouts/            # DashboardLayout, Sidebar, Header, menu/
  locales/            # en.json, km.json
  pages/
    backend/<feature>/...   # admin pages
    frontend/...            # public site pages
```

- **UI components** (`resources/js/components/ui/<name>/`): most folders expose a barrel
  `index.js` (`export { default as X } from './X.vue'`) — import via
  `import { X } from '@/components/ui/<name>'` or a relative path. There is **no top-level
  `ui/index.js`**. A few folders have no barrel (`rightclick/`, `bug-annotation/`,
  `notification-badge/`) — import by direct file path. Some components ship a `README.md`
  with props/emits tables.
- **Pages**: one folder per feature under `pages/backend/`; nested folders for sub-entities
  (`buildings/Building/`, `buildings/Floor/`, `buildings/Room/`; `classes/class-list/`,
  `classes/class-type/`). CRUD pages are entity-prefixed — `<Entity><Action>.vue`
  (`BuildingIndex`, `FloorCreate`, `RoomEdit`; also `UserIndex`/`UserCreate` in `users/`).
  Feature-local shared components go in a
  `components/` subfolder, feature-local composables in a `composables/` subfolder.
- Some feature folders are **empty placeholders** (`attendances/`, `categories/`,
  `contacts/`, `docs/`, `permissions/`, `qr/`, `results/`) — don't treat emptiness as a bug.
  The real attendance UI is **not** in `attendances/` — it lives under `instructors/`
  (`AttendanceRecord.vue`, `TrackAttendance.vue`, `ClassGroups.vue`), rendered by
  `InstructorClassController`; `attendance-settings/Edit.vue` is the separate superadmin
  auto-record config page.
- `work-schedules/` (`Index.vue`/`Create.vue`/`Edit.vue`) replaced the deleted
  `shift-templates/` folder — same CRUD shape, new module/route/permission names (see §2, §3).
- `frontend/class-join/JoinClass.vue` is the newer public "join an existing class" page
  (`/join-class/{studyClass}`), a sibling flow to `frontend/student-register/StudentRegister.vue`.
- **Dead code** (don't resurrect): `resources/js/App.vue` (unused legacy), 
  `resources/js/router/index.js` (empty vue-router stub). The app uses Inertia `router`,
  not vue-router.

### Attribute formatting rules

- **Plain HTML elements** (`div`, `input`, `button`, `span`, `p`, `td`, `th`, `Link`, …)
  keep all attributes on a **single line**, even with long Tailwind class lists.
- **Custom PascalCase components** (`SelectSearch`, `Pagination`, `PageHero`, `Card`, …)
  are exempt and may span multiple lines when they have several props; a single prop pair may
  stay inline (e.g. `<PageHero eyebrow="..." :title="$t('...')" />`).
- `<script setup>` SFCs; PascalCase component imports; named imports for composables/i18n.

### Commenting rules

- Comments are **one line max**, and only for non-obvious logic (computed props, watchers,
  module-scoped state sharing, why a value is blank, dangling-confirm handling).
- Don't restate what the code already shows. `/* */` block comments are reserved almost
  exclusively for `<style>`/CSS scope.
- Larger component documentation lives in `README.md` files, not inline comments.

### Form & save-logic separation

- **Global composables** live in `resources/js/composables/` and share module-scoped state:
  - `useSaveForm.js` — wraps Inertia `useForm`; `save(url, { method = 'put', ... })` defaults
    to PUT with `preserveScroll: true`. **Must stay feature-agnostic.**
  - `useConfirm.js` — app-wide confirm dialog; `useConfirm()` → `{ confirm }` returns
    `Promise<boolean>`. Backed by the single `<ConfirmDialog />` mounted in
    `DashboardLayout.vue`.
  - `useTheme.js` — light/dark/system, persisted to `localStorage('theme')`.
  - `useRouteLoading.js` — `isNavigating`/`targetUrl` refs bound to Inertia navigation.
- **Feature composables** live in `pages/backend/<feature>/composables/use<Feature>.js`
  (e.g. `useUserIndex.js`). They own that feature's data-fetching/form/confirm/toast logic,
  may compose the global composables, and open with a 1-line `// Owns …` comment. Pages keep
  only UI state (search, pagination, selection) and template bindings.
- **Before adding a composable, check `resources/js/composables/` and the feature's
  `composables/` folder and reuse what exists.** See `prompt.md` for the full refactor prompt.

### i18n (custom system, NOT vue-i18n)

- `createI18n(initialLocale)` (in `resources/js/i18n/index.js`) provides `t()`/`$t()`.
- `t(key, replacements)` resolution order (first hit wins):
  `locales/{locale}.json` → `en.json` → `commonText.js` → `backendText.js` →
  `backendExtraText.js` → `backendFinalText.js` → `backendPlaceholderText.js` → the key itself.
- Translation files are flat maps `km: { "<English phrase>": "<Khmer>" }` — the English phrase
  **is** the key. Add Khmer for any new UI string (usually in `backendFinalText.js`; match
  where siblings live). `:name` placeholders are interpolated.
- Use `$t()` in templates and `t()` (from `useI18n()`) in `<script setup>`.

### Common frontend patterns

- Confirmations: `const { confirm } = useConfirm()` then
  `const ok = await confirm({ title, message, confirmText, cancelText, danger: true })` —
  **never `window.confirm`**.
- Toasts: `vue-toastification` (global `FlashToasts.vue` host).
- Icons: `@lucide/vue`. **Gotcha**: passing an icon component as a prop with a function
  default crashes Vue (`Cannot destructure property 'slots'`); use `default: null` + a
  computed fallback (`props.icon ?? SomeIcon`) rendered via `<component :is="icon">`
  (see `components/ui/empty-state/EmptyState.vue`).
- Data lists: axios to JSON endpoints (`/dashboard/<feature>/data`) with manual pagination,
  or Inertia props for server-rendered pages. Shared `<Pagination>` component emits
  `@page-change`.
- Dropdowns with search use `SelectSearch` (`modelValue` is a **String**).

---

## 5. Local Development Commands

### Install & setup

```bash
composer install
npm install
cp .env.example .env          # or .env.docker for Docker
php artisan key:generate
php artisan migrate --seed
npm run build                 # production assets
```

`composer run setup` does all of the above except the env copy.

### Dev servers (ports)

| Service | Command | Port |
|---|---|---|
| Laravel server | `composer run serve` (or `php artisan serve --port=8001`) | 8000/8001 |
| Vite | `npm run dev` | 5173 |
| Reverb (WebSockets) | `php artisan reverb:start --host=0.0.0.0 --port=8080` | 8080 |
| Queue worker | `php artisan queue:listen --tries=1 --timeout=0` | — |
| **Everything at once** | `composer run dev` | server + queue + Vite + Reverb |

> `.env.example` currently defaults to `APP_URL=http://127.0.0.1:8002` /
> `APP_PORT=8002`, while README references 8001 — keep `APP_URL` aligned with whatever
> port you actually open in the browser (session/CSRF issues otherwise).

### Migrations & seeding

```bash
php artisan migrate            # apply new migrations
php artisan migrate:fresh --seed   # DESTRUCTIVE — wipes and reseeds all data
```

Composer seed shortcuts (`composer run …`):

| Script | Runs |
|---|---|
| `seed` | `php artisan db:seed` |
| `seed-core` | `Database\Seeders\Core\CoreSeeder` |
| `seed-permission` | `Database\Seeders\Core\CoreSeeder` (**name is misleading** — points at Core) |
| `seed-class` | `Database\Seeders\Class\ClassSeeder` |
| `seed-report` / `seed-landrent` / `seed-invoice-test` | `Feature\Report\…`, `Feature\Invoice\…` stubs |
| `sync-env` | `php artisan app:sync-env-example` |

### Scheduled commands (`bootstrap/app.php` → `withSchedule()`, not `routes/console.php`)

`routes/console.php` only has the stock `inspire` command — the real schedule lives in
`bootstrap/app.php`. Three attendance-automation commands run there, backed by
`app/Modules/Attendance/Actions/`: `AutoRecordAttendanceCommand` (→ `AutoRecordSession`),
`GenerateClassSessionsCommand` (→ `GenerateClassSessions`), `SendAttendanceDigestCommand`.
Run them manually with `php artisan <command>` to test without waiting on the scheduler.

### Tests (pure PHPUnit — no Pest)

```bash
php artisan test               # or: composer run test  (config:clear + test)
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

- Tests mirror module layout: `tests/Unit/<Module>/…`, `tests/Feature/<Module>/…`.
- **No test database**: tests run against the real MySQL from `.env` wrapped in
  `RefreshDatabase` transactions (see `tests/README.md`).
- Docker caveat (`tests/README.md`): the container exports an empty `APP_KEY` and
  `CACHE_STORE=file` that shadow phpunit.xml — pass `-e APP_KEY=...` on `docker exec`.

### Docker

```bash
docker compose up -d --build
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan test
docker compose exec app bash    # shell into the app container
```

---

## 6. Known Quirks & Edge Cases

Read this list before touching anything that looks wrong — most of it is intentional.

### Naming & case (DO NOT "fix")

- **`EnRoll` vs `Enroll`**: `Enroll` is live, `EnRoll` is dead/legacy. Both folders exist.
  Renaming either is a breaking change — verify with the owner first.
- **`schdule`** is misspelled everywhere (route file, prefix `/dashboard/schdule`, name
  `schdule.`) — it's the real in-use value.
- **`app/Modules/building`** is lowercase; **`Floor`/`building` use `Controller/` (singular)**;
  **`Course` controllers sit at the module root**.
- **`modules_statuses.json` only lists `User`** — not a source of truth.
- UI string **"Start EnRoll"** is deliberate copy, not a module reference.

### Scratch / generated files (leave alone, don't confuse with source)

| Path | What it is |
|---|---|
| `app/Http/Controllers/Test.txt` | stray scratch file, **not a controller** |
| `app/Http/Controllers/chii.txt` | stray scratch file, **not a controller** |
| `code-audit-report.md` | generated audit artifact |
| `.phpunit.result.cache` | PHPUnit cache (untracked) |
| `.claude/settings.local.json` | local tool config (untracked) |

Don't delete the scratch files without asking. `database/seeders/Feature/Report/*` and
`Feature/Invoice/InvoiceTestSeeder.php` are empty seeder stubs, not scratch.

### Seeders are destructive

- `migrate:fresh --seed` (and several `db:seed` classes) **truncate** tables: `users`,
  roles/permissions pivots, courses, categories, tracks, lessons, class_list, terms, times,
  buildings. Any manually-entered rows in those tables are wiped.
- `Database\Seeders\Core\TruncateSeeder` truncates **every table except `migrations`**
  (nuclear reset, not called by `DatabaseSeeder`).
- Two parallel permission/user families exist: `Database\Seeders\Permission\*` and
  `Database\Seeders\Core\*` (both truncate + recreate).
- Demo credentials from `Core\UserSeeder`: `teststudent@etec.com` / `password`
  (also `superadmin@etec.com`, `admin@etec.com`, `instructor@etec.com`, `student@etec.com`).

### Other gotchas

- `bootstrap/app.php` aliases `active` → `EnsureAccountIsActive` and registers spatie
  `role`/`permission`/`role_or_permission` aliases. Throttle responses stay on the form
  (inline errors + `retryAfter` countdown) rather than redirecting.
- **The `notifications` table is named `dashboard_notifications`.** `User` uses Laravel's
  `Notifiable` trait, which claims the conventional `notifications` table
  (`notifiable_type`/`notifiable_id` columns); the hand-rolled `Notification` model's table
  was renamed to `dashboard_notifications` to stop colliding with it
  (`$table = 'dashboard_notifications'` in `app/Models/Notification.php`). Don't rename it
  back, and don't assume `$user->notifications` and the dashboard notification bell are the
  same data.
- **`users.is_active` no longer exists** — it duplicated `status` as a second, unread source
  of account state (`EnsureAccountIsActive` has always gated on `status` only). If you see
  code or docs referencing `is_active` on `User`, it's stale.
- **OTP replaced `verification_codes`.** The `verification_codes` table is dropped;
  `OtpVerification` (`app/Models/OtpVerification.php`) is the live model for account
  verification. `docs/login-workflow.md` may still describe the old code-based flow — verify
  against `TelegramService`/`Auth` module before trusting it.
- **Active-only uniqueness on `student_enrollments`**: the unique `(study_class_id,
  student_id)` constraint now only applies to `active` enrollments (via a generated
  `active_enrollment_key` column that's `NULL`, and thus unconstrained, for non-active rows).
  This lets a student be re-enrolled into a class they were previously moved/cancelled out of.
  Don't "simplify" this back to a plain composite unique index — it will reintroduce the bug
  `MoveStudentEnrollment` was fixed for.
- Instructor-facing "class groups" (teams) and student scoring are newer features:
  `InstructorClassController::groups/saveTeams` and `::saveScores` (routes
  `instructor.classes.groups*` / `instructor.classes.scores.update`), backed by the `teams`/
  `team_members` tables (see Module traps above) and the `student_scores` table
  (`app/Models/StudentScore.php`, one row per `student_enrollment_id`).
- Migrations are **flat** in `database/migrations/` (111 files, no feature folders) and mix two
  timestamp styles (`YYYY_MM_DD_HHMMSS` and `YYYY_MM_DD_00000N`). One non-standard name:
  `2026_07_05_000001_enrich_classes_table.php`.
- `DashboardLayout.vue` renders route-specific skeletons during navigation (`/dashboard/users*`)
  and mounts the single shared `<ConfirmDialog />`. Lines 1–47 contain a commented-out older
  layout (dead code).
- Some pages fetch data via axios from `<feature>/data` endpoints (Rooms, Floors, Users) with
  their own pagination; the backend pagination endpoints accept `per_page` (default 10,
  `'all'` supported).
- Controllers render Inertia components with **exact** casing (`Floor/FloorIndex`,
  `buildings/Room/RoomIndex`) — a case mismatch 404s (PSR-4 file resolution is case-sensitive).

---

## 7. Existing Documentation

Read the relevant doc before touching a workflow:

| Doc | Covers |
|---|---|
| `docs/auth-controller-workflow.md` | Auth module: routes, login/logout/reset, OTP, Telegram, redirects |
| `docs/login-workflow.md` | Login deep-dive: timing-safe hash check, two-layer lockout — **predates the code→OTP switch and the `verification_codes` drop**; verify against `Auth`/`OtpVerification` before trusting the code-based details |
| `docs/notification-workflow.md` | `PendingUserRegistered` → Telegram + dashboard notifications (table is now `dashboard_notifications`, see §6) |
| `docs/registration-workflow.md` | Instructor self-registration end-to-end |
| `docs/public-class-registration-workflow.md` | Public `/classes` QR self-registration — **predates `ClassJoinController`**; doesn't cover the newer `/join-class/{studyClass}` flow (§3, §4) |
| `docs/auto-record-attendance-workflow.md` | Auto-record attendance end-to-end: `Attendance` module Actions + the three scheduled commands (§5) |
| `docs/architecture.md` | Snapshot of prod Docker/deploy architecture (`docker-compose.prod.yml`, `Dockerfile`, `deploy/`, `bootstrap/app.php`) |
| `docs/production-deployment.md` | Fresh-VPS-to-running-prod-stack runbook; companion to `vps-deployment-specs.md` |
| `docs/senior-architecture-review.md` | Point-in-time backend/DB architecture review (dated — a snapshot, not living docs) |
| `docs/vps-deployment-specs.md` | VPS sizing + prod Docker notes |
| `app/Modules/Auth/LOGIN_SECURITY.md` | Login security deep-dive |
| `prompt.md` | Owner's authoritative style prompts (frontend) |
| `database/seeders/README.md`, `database/seeders/Class/readme.md`, `tests/README.md` | Seeder + test runbooks |
