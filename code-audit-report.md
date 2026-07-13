# Codebase Audit Report — system_v3

**Stack:** Laravel 12 + Inertia.js + Vue 3, `nwidart/laravel-modules` (business logic under `app/Modules/<Name>/`), `spatie/laravel-permission` (RBAC), `laravel/sanctum`, Telegram bot integration (`irazasyed/telegram-bot-sdk`). Academic scheduling/registration domain: courses, classes, rooms, floors, buildings, terms, times, schedules, instructors, students.

**Scope:** `app/`, `app/Modules/**`, `routes/`, `config/`, `database/`, `resources/js/**`, `resources/views/**`, `tests/`, root config files. Excluded: `vendor/`, `node_modules/`, `storage/`, `public/build`, `bootstrap/cache`.

---

## Executive Summary

**Overall risk: Critical.** This application has an authentication bypass that lets anyone log in as any user (including super_admin) by knowing only their email/username, and a live-looking Telegram bot token plus DB passwords committed to git. Combined with broad missing-authorization gaps across CRUD modules, the app is not safe to run in production in its current state.

**Top 3 to fix before anything else:**

1. **Login never verifies the password** (`app/Modules/Auth/Controllers/AuthController.php:173-195`) — full authentication bypass, including admin accounts. This alone is a "stop and fix now" issue.
2. **Secrets committed to git** (`.env.example:75-78`) — a real-format Telegram bot token, admin chat ID, and DB passwords are in version control history. Rotate the token immediately and purge history.
3. **Missing authorization on most admin CRUD routes** (Room, Course, Class, Term, Time, Schedule, Registration, Students) — gated only by `auth`, so any logged-in user (student/instructor) can create/edit/delete rooms, courses, schedules, etc. The `/dashboard/students` routes are fully unauthenticated (middleware commented out).

Secondary but real risk: an admin-only privilege-escalation path lets a plain `admin` grant itself `super_admin`. Performance-wise, class/registration listing pages run unbounded, N+1-laden queries that will degrade badly as data grows. Test coverage is thin — core scheduling and registration logic (the app's actual business purpose) has zero tests.

---

## Critical

### [Critical] Authentication bypass — password never checked
**File:** `app/Modules/Auth/Controllers/AuthController.php:173-195`

`loginWeb()` looks up the user via `findUserForLogin($data->login)`, checks only that `status !== Inactive`, then calls `Auth::login($user)` directly. `$data->password` is validated as `required|string` but is **never compared against the stored hash**. Anyone who knows a valid email/username — including a `super_admin`'s — can log in as that user with any password (or none that matches).

**Fix:**
```php
if (! Hash::check($data->password, $user->password)) {
    throw ValidationException::withMessages(['login' => ['Invalid credentials.']]);
}
Auth::login($user);
```
Or replace the whole flow with `Auth::attempt(['email' => ..., 'password' => $data->password])`.

---

### [Critical] Secrets committed to git
**File:** `.env.example:75-78`

A real-format Telegram bot token (`7959018611:AAFA92y8e0iUgMsnc15d9dWAiTbKuiZMlL0`), a real admin chat ID, and DB passwords (`secret123` / `root123`) are committed — confirmed present across multiple commits in `git log -- .env.example`, not placeholder values. `TELEGRAM_WEBHOOK_SECRET` is also set to an unrelated external URL (`https://pokeapi.co/api/v2/pokemon/charizard`) rather than a generated secret, meaning the webhook's `X-Telegram-Bot-Api-Secret-Token` check (`TelegramWebhookController.php:24`) was likely never properly configured.

**Fix:** Rotate/revoke the Telegram token via BotFather now. Change the DB passwords. Purge these values from git history (`git filter-repo` or BFG). Replace `.env.example` with dummy placeholders only. Generate a real random `TELEGRAM_WEBHOOK_SECRET`.

---

### [Critical] Privilege escalation — admin can grant itself super_admin
**File:** `app/Modules/User/Controllers/UserManagementController.php:108-129` (`assignRolePermissions`, `assignUsersToRole`)

Gated only by `abort_unless($request->user()?->hasAnyRole(['super_admin','admin']), 403)`. A plain `admin` (not `super_admin`) can call `PUT /dashboard/users/roles/{role}/users` to assign any user — including themselves — into the `super_admin` role, or grant any role arbitrary permissions via the sibling endpoint. Contrast with `PermissionController::assignRoleToUser` (`routes/web/backend/permission.php`), correctly restricted to `role:super_admin`.

**Fix:** Restrict both actions to `hasRole('super_admin')` only.

---

### [Critical] Unauthenticated student dashboard routes
**File:** `routes/web/backend/student.php:9`

The entire `/dashboard/students/*` route group has its `auth` middleware commented out:
```php
// Route::middleware('auth')->prefix('/dashboard/students')...
```
Currently backed by mock data only, but it's a live, unauthenticated route inconsistent with every other module.

**Fix:** Uncomment the `auth` middleware and add an appropriate role/permission check.

---

### [Critical] Missing authorization across most admin CRUD modules
**Files:**
- `routes/web/backend/room.php:6`, `class.php:7`, `course.php:10`, `term.php:6`, `time.php:6`, `schdule.php:6`, `registration.php:7` — all gated by `auth` only (course.php also adds `verified`), no `role:`/`permission:` middleware.
- Corresponding controllers have zero internal authorization checks: `RoomController`, `ClassListController`, `ClassTypeController`, `CategoryController`, `SubCategoryController`, `CourseController`, `CourseTrackController`, `CourseLessonController`, `TermController`, `TimeController`, `ScheduleController`, `AdminClassController`, `AdminRegistrationController`.

Any authenticated user — including a self-registered student or instructor — can create/edit/delete rooms, courses, class types, terms, times, schedules, and admin registrations. This is inconsistent with `Floor`/`Building`/`Notification`/`ShiftTemplate`/`Permission`, which all correctly enforce `role:super_admin|admin[|instructor]` middleware.

**Fix:** Add `role:`/`permission:` middleware to all of these route groups, matching the pattern already used by Floor/Building/ShiftTemplate.

---

### [Critical] Unbounded, N+1-laden class/enrollment listings
**Files:**
- `app/Models/ScheduleClass.php:32` — `getRegisteredCountAttribute()` runs a fresh `enrollments()->where('status','active')->count()` query per model, invoked inside `->get()->map()` loops in `AdminClassController@index`, `AdminRegistrationController@index/@create`, `StudentRegistrationController@create` — 1+N queries every time a class list renders, and these lists are loaded unpaginated in full.
- `app/Modules/Registration/Controllers/AdminRegistrationController.php:17` — `Enrollment::with(['student','scheduleClass.time.term'])->latest()->get()` loads the entire enrollments table on every page visit, no pagination, no limit.

**Fix:**
```php
ScheduleClass::with('time.term')
    ->withCount(['enrollments as registered_count' => fn ($q) => $q->where('status', 'active')])
    ->paginate($perPage);
```
Paginate the enrollments query as well; move client-side filtering (currently done over the full unpaginated array in `resources/js/pages/backend/class/Index.vue` and `registration/Index.vue`) to server-side filtered/paginated queries.

---

### [Critical] Duplicate namespace mismatch causes dead/broken class
**File:** `app/Modules/building/Services/FloorService.php:1-62`

File lives at `app/Modules/building/Services/FloorService.php` but declares `namespace App\Modules\Floor\Services;` — an apparent copy-paste of the real `app/Modules/Floor/Services/FloorService.php`. Violates the project's PSR-4 mapping; any code referencing the class at its actual path would fatal. Currently unreferenced (dead), but a landmine for the next developer who edits it expecting it to be the real building-floor service.

**Fix:** Delete the file (the real logic already lives in `app/Modules/Floor/Services/FloorService.php`; `BuildingService` implements its own floor-creation logic inline).

---

## High

- **[High] `app/Modules/Auth/Listeners/SendTelegramAdminApproval.php:12` + `.env:49`** — `QUEUE_CONNECTION=sync` makes this `ShouldQueue` listener run synchronously in-request, so user registration (`AuthController@registerWeb`) blocks on a live Telegram API call (`TelegramService.php:48`) before responding. **Fix:** set `QUEUE_CONNECTION=database` (or redis) in production and run a queue worker.

- **[High] `app/Modules/User/Controllers/UserManagementController.php:93-107`** — `roles()` eager-loads only `roles`, then calls `getPermissionsViaRoles()`/`getDirectPermissions()`/`getAllPermissions()` per user in a loop — each call lazy-loads relations Spatie needs, causing 2+ extra queries per user. **Fix:** `->with(['roles.permissions', 'permissions'])`.

- **[High] `app/Modules/User/Controllers/UserManagementController.php:207-225`** — Same N+1 pattern in `permissions()` (missing nested `roles.permissions`). **Fix:** same as above.

- **[High] `app/Modules/Auth/Controllers/PermissionController.php:40-73`** — `userPermissionsPage()` has the identical N+1 via `getPermissionsViaRoles()` per user without eager-loading `roles.permissions`. **Fix:** same as above.

- **[High] `app/Modules/User/Controllers/CourseController.php`** (whole 82-line file) — Dead code: a full duplicate `CourseController` under `App\Modules\User\Controllers`, unwired to any route (verified via grep). **Fix:** delete.

- **[High] `app/Modules/building/API/BuildingApiController.php`** — Empty file (0 bytes), unreferenced. **Fix:** delete.

- **[High] `app/Http/Middleware/CheckRoleMiddleWare.php:9-19`** — No-op middleware (`handle()` just calls `$next($request)`), not registered in `bootstrap/app.php`, misleadingly named. **Fix:** delete, or implement and wire it up if it was meant to replace scattered `abort_unless` calls.

- **[High] `app/Modules/Course/{CategoryController,SubCategoryController,CourseTrackController,CourseController,CourseLessonController}.php`** (~480 lines) — Near-identical CRUD scaffolding repeated 5x: inline `$request->validate()`, repeated `'status' => $validated['status'] ?? 'active'`, identical slug generation and redirect patterns, stray leftover comments (`// Change: use 'status' instead of 'is_active'`, file-path comments). **Fix:** extract shared FormRequests + a `CourseCatalogService`, mirroring the Room/Floor pattern.

- **[High] `app/Modules/Registration/Controllers/AdminRegistrationController.php:42-70` & `StudentRegistrationController.php:28-57`** — `store()` methods are near-identical (same validation, same `DB::transaction` creating `Student` + `Enrollment`), differing only in redirect. **Fix:** extract `RegistrationService::register(array $data): Enrollment`.

- **[High] `AuthController.php:55` + Course module (~17 places)** — `'status' => 'pending'` set as a raw string instead of `UserStatus::Pending` (the enum exists and is used correctly elsewhere in the same file); Course module hardcodes `'active'`/`'inactive'` string literals with no backing enum at all. **Fix:** use `UserStatus::Pending`; introduce a `CourseStatus`/`RecordStatus` enum.

- **[High] `tests/Feature/CourseApiTest.php:27-61`** — All 4 tests target `/api/courses` and `/api/courses/hi`, routes that don't exist anywhere in the app (verified via project-wide grep). Provides false confidence — the real `CourseController` has zero test coverage. **Fix:** remove this stale test file and write real coverage against the actual Course routes.

---

## Medium

- **[Medium] `routes/web/frontend/registration.php:8-9`** — Public student self-registration `POST /register` has no rate limiting (backend `/register` uses `throttle:register`, this one doesn't). **Fix:** add a throttle limiter.

- **[Medium] `app/Modules/User/Controllers/UserManagementController.php`** (9 methods) — `abort_unless($request->user()?->hasAnyRole(['super_admin','admin']), 403)` copy-pasted verbatim 9 times (one variant differs, line 183), while the sibling `UserController` uses Laravel policies exclusively. A future method that forgets this line becomes silently unauthenticated. **Fix:** extract a Gate/Policy and use consistently.

- **[Medium] `FloorController.php:109-117`, `BuildingController.php:159-167`, `NotificationController.php:19-23,30-34`** — Same `abort_unless(hasRole(...))` block independently re-implemented in 3 controllers with slightly different role lists, redundant with route-level `role:` middleware already applied to Floor/Building. **Fix:** consolidate into middleware or a shared policy/trait.

- **[Medium] `app/Modules/Auth/Controllers/PermissionController.php`** (7 occurrences: lines 121,172,197,217,241,266,286) — `app()[PermissionRegistrar::class]->forgetCachedPermissions();` repeated after every mutating method. **Fix:** wrap in a `PermissionCacheService` or model observer.

- **[Medium] `app/Modules/ShiftTemplate/Controllers/ShiftTemplateController.php:37-71, 82-118`** — `store()`/`update()` duplicate the full validation rules and block-recreation loop. **Fix:** extract shared `validatedBlocks()`/`syncBlocks()` helpers.

- **[Medium] `app/Modules/Instructor/Controllers/InstructorProfileController.php:70-88`** — `$hasChanges` computed via a 19-line chained boolean comparing ~14 fields manually; easy to forget to extend when adding a field. **Fix:** use `array_diff_assoc` or rely on Eloquent's dirty-checking.

- **[Medium] `routes/web/backend/schdule.php`, `app/Modules/Schedules/Controllers/ScheduleController.php`** — "schedule" misspelled "schdule" throughout (file, route prefix, route names, view paths, variable `$schdule`), inconsistent with the rest of the codebase. **Fix:** rename (note: breaking change for any bookmarked URLs).

- **[Medium] `app/Modules/building` module** — Directory is lowercase (`building`) unlike every sibling PascalCase module; its controller path is singular `Controller/` while Room/Auth/User use plural `Controllers/`. **Fix:** standardize naming/casing across modules.

- **[Medium] `app/Modules/Course/*Controller.php`** — Bare `App\Modules\Course` namespace (no `\Controllers` sub-namespace), unlike nearly every other module. **Fix:** move to `App\Modules\Course\Controllers`.

- **[Medium] `database/migrations/2026_06_12_151454_create_rooms_table.php:16`** — `$table->foreignId('floor_id')->nullable();` has no `->constrained()`/`->index()` despite being filtered/joined on in `RoomService`, `ClassListController`, `BuildingService::hierarchy`. **Fix:** add `->constrained('floors')->nullOnDelete()` (or at minimum an index) in a follow-up migration.

- **[Medium] `database/migrations/2026_04_17_000004_create_notifications_table.php`** — `notifications.is_read` has no index; queried via `where('is_read', false)->count()` on every dashboard header load. **Fix:** add an index (composite `['is_read','id']` if helpful).

- **[Medium] `app/Modules/Room/Services/RoomService.php:60-79` & `app/Modules/building/Services/BuildingService.php:102-109`** — Bulk room/floor creation issues one INSERT (plus a `Validator::make`) per row instead of a bulk insert; "create 50 rooms" = 50 sequential queries. **Fix:** build attribute arrays and use `Room::insert($rows)` / `Floor::insert($rows)`, mirroring `InstructorProfileService::generateInstructorAvailabilities`, which already does this correctly.

- **[Medium] Repeated lookup-table queries with no caching** — `Term::select(...)->get()`, `ClassType::select(...)->get()`, `Time::select(...)->get()`, `ShiftTemplate::where('is_active', true)->get()` re-queried from scratch on nearly every backend page load in `ScheduleController`, `TimeController`, `ClassListController`, `ShiftTemplateController`, `InstructorProfileController`. **Fix:** `Cache::remember()` these small, rarely-changing tables and invalidate on `store`/`update`/`destroy`.

- **[Medium] `app/Modules/User/Controllers/UserManagementController.php:32-43, 93-107`** — `index()` and `roles()` call `User::query()->...->get()` with no pagination (unlike `UserController::paginatedIndex`, which correctly paginates). **Fix:** reuse `UserService::paginateVisibleUsers`.

- **[Medium] `deploy/nginx/default.conf`** — Only sets `X-Frame-Options`/`X-Content-Type-Options`; missing `Content-Security-Policy`, `Strict-Transport-Security`, `Referrer-Policy`. **Fix:** add these headers at the nginx layer.

---

## Low

- **[Low] `docker-compose.yml`** — phpMyAdmin exposed on host port 8081, MySQL on 3307, root credentials from `.env`. Fine for local dev; risky if reused on a shared/staging host. **Fix:** move into a local-only compose override.

- **[Low] `resources/js/App.vue`, `resources/js/router/index.js`** — Dead code: import `RouterView`/define an empty route table, but neither is used by `resources/js/app.js` (Inertia supplies its own page resolution). `vue-router` is an unused dependency. **Fix:** delete both files and drop `vue-router` from `package.json` if truly unused.

- **[Low] `app/Modules/Auth/Controllers/AuthController.php:59`** — `Role::findOrCreate('instructor', 'web')` runs a query on every registration request for a role that should already exist post-boot. **Fix:** seed the role once via seeder/migration.

- **[Low] `app/Modules/Auth/Controllers/AuthController.php:173-195`** — Method body indentation (4 spaces) is inconsistent with the file's 8-space convention. **Fix:** run Pint.

- **[Low] `app/Modules/User/Services/UserService.php:137`** — Nested ternary reduces readability (`$user->role === 'student' ? (...) : ($user->role === 'instructor' ? (...) : $user->name)`). **Fix:** replace with `match`/early returns.

- **[Low] `app/Modules/User/Services/UserService.php:151-153,163-164,190-193,197`** — Statements compressed onto single lines inconsistent with rest of codebase. **Fix:** run Pint.

- **[Low] `app/Modules/Class/Controllers/ClassTypeController.php:82`** — Stale comment referencing an `if` statement that no longer exists (`// If you deleted the categories table, delete this IF statement`). **Fix:** remove.

- **[Low] `resources/js/pages/backend/students/Form.vue:68-70,84,113,125`, `resources/js/pages/backend/courses/CourseForm.vue:317`** — Leftover debug `console.log`s (one literally labeled `"NEW VERSION 2026"`). **Fix:** remove before shipping.

- **[Low] Hardcoded role/guard strings** (`'super_admin'`, `'admin'`, `'instructor'`, `'student'`, `'web'`) scattered as literals across many controllers/services. Spatie roles are DB-driven so a true enum can't fully replace them, but repeated literals invite typos. **Fix:** define `class Roles { const SUPER_ADMIN = 'super_admin'; ... }` and reference consistently.

---

## Checked and found OK (no action needed)

- No string-concatenated SQL (`DB::raw`/`whereRaw`/`selectRaw`) built from user input — the only raw usages take no user input.
- No `eval`/`exec`/`shell_exec`/`system`/`unserialize()` on user input anywhere in `app/`.
- File uploads (avatars, instructor CV/photo/attachments, course thumbnails) use `store()` with generated filenames and MIME/size-limited FormRequest validation — no path traversal found.
- Passwords hashed via `Hash::make` / `password` cast to `hashed`; no MD5/SHA1 for passwords.
- `v-html` usages in Vue pages only render Laravel paginator link labels, not user-controlled content — no XSS found there.
- No routes disable CSRF verification; the Telegram webhook is appropriately CSRF-exempt (stateless API route) and instead protected by a shared-secret header check (though see the Critical finding above about that secret's actual value).
- `composer.json`/`package.json` dependency versions (Laravel 12, Sanctum 4.3, spatie/laravel-permission 6.25) are current — nothing flaggable from known-CVE history.

---

## Test Coverage Summary

7 Feature test files + 1 Unit smoke test (~724 lines total) cover: registration/login/OTP/logout, login throttling, instructor-code auto-generation, user creation with role-assignment guardrails, and the permission-management API. Of roughly 26 controllers across 14 modules, **only Auth, Permission, User-creation, and Instructor-code-generation logic have any test coverage.**

**Zero test coverage** on: Registration (Admin/Student), Schedules, Room, Floor, Building, ShiftTemplate, Terms, Times, Class (List/Type), Course CRUD, Notification, and the Telegram webhook — i.e., essentially the entire scheduling/registration domain that is this application's actual purpose, and precisely the areas flagged above for missing authorization and N+1/pagination issues. `CourseApiTest` additionally targets non-existent routes and should be treated as broken, not as coverage.

**Recommendation:** prioritize Feature tests for (1) the login/auth fix once patched, (2) authorization boundaries per role across the newly-gated CRUD modules, and (3) the Schedule/Registration business logic given it currently has zero coverage and the highest concentration of bugs found in this audit.
