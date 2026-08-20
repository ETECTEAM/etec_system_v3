# Senior Backend Architect — Code & Database Review

**Project:** ETEC System v3 — Laravel 12 + Inertia/Vue 3 school-management system (students, classes, courses, instructors, attendance, public website)
**Branch:** `production` @ `7615481`
**Date:** 2026-08-20
**Scope:** Full-stack review. No files were modified.

---

## A. Project Understanding

### Architecture discovered

**Stack:** PHP 8.2+ / Laravel 12, Inertia.js v3 + Vue 3 SFC, Tailwind v4, MySQL (timezone `Asia/Phnom_Penh`), spatie/laravel-permission, Laravel Reverb + Echo real-time, `irazasyed/telegram-bot-sdk`, custom i18n (en/km JSON maps), Ziggy.

**Module layout (non-standard):** Business code lives in `app/Modules/<Name>/` with `Controllers/`, `Requests/`, `Data/`, `Services/`, `Actions/` (write use-cases), `Queries/` (read presenters), `Notifications/`, `Events/`, `Listeners/`. `nwidart/laravel-modules` is installed but **not actually used** — modules are plain PSR-4 folders, not nwidart modules. `app/Models/` is flat (40+ models named after tables).

**Two parallel "stack layers" exist and both are live:**

1. **New stack (primary):** `study_classes` / `student_enrollments` / `students` / `course_enroll_configs` / `class_sessions` / `student_attendances` — handled by `Enroll`, `Instructor`, `Attendance`, `Website` modules.
2. **Legacy stack (still routed):** `classes` (via `ScheduleClass` model) / `enrollments` (via `Enrollment` model) / `schedule_time` — handled by the `Registration` module (`dashboard/admin/*` routes) and referenced by dead `EnRoll` module code.

**Routing:** `routes/web.php` glob-loads every file in `routes/web/backend` and `routes/web/frontend` (alphabetical order). Middleware aliases in `bootstrap/app.php`: `auth`, `active` (custom account-status check), spatie `role`/`permission`/`role_or_permission`, `throttle` with named limiters in `AppServiceProvider`.

**Scheduled jobs** (`bootstrap/app.php:67-89`): `AutoRecordAttendanceCommand` (every 1 min), `GenerateClassSessionsCommand` (daily 00:05), `SendAttendanceDigestCommand` (daily 22:00).

### Key feature flows (API → action → DB)

**Public self-registration:** `POST /student-register` (throttle 5/10min, unauthenticated) → `RegisterStudentForSchedule::handle()` → finds-or-creates student by phone → dedupes by course+term+time → finds/creates class (prices from `course_enroll_configs.resolvedPrice()`) → `student_enrollments` row (`source=public_website`) + `notifications` row. Falls back to `pending_registrations` if no room/instructor free.

**Admin class creation:** `POST /dashboard/enroll` (`role:super_admin|admin|instructor`) → `SaveStudyClassRequest` (schedule-consistency + instructor-availability validation) → `CreateStudyClass` → `study_classes` row.

**Enrollment:** `POST /dashboard/enroll/{class}/enrollments` (super_admin) → `EnrollStudent` → row-locks class (`SELECT ... FOR UPDATE`), checks active-duplicate + capacity → `student_enrollments` snapshot of `price`/`document_price`.

**Attendance:** `GenerateClassSessions` creates per-date `class_sessions` from `term_id`/`time_id` (days/time parsed out of `term_name`/`time_name` strings); `AutoRecordSession` marks students present/absent/permission; instructors can override within a window, logged to `attendance_audit_logs`.

---

## B. Feature Review

| Feature | Status | Problems | Severity |
| ------- | ------ | -------- | -------- |
| Login + tiered lockout | OK | Well-designed (timing-safe, dual-layer lockout, session regeneration). Minor: `is_active` flag drifts unused | Low |
| Password reset / recovery email | OK | Good anti-enumeration; `remember_token` not rotated on reset | Low |
| OTP verification | Good | Code stored hashed; but plaintext OTP leaked into dashboard notifications + Telegram | Medium |
| Instructor self-registration + approval | Partial | Telegram approval is unauthenticated when secret unset; `OTP_VERIFICATION_ENABLED=false` auto-approves; unique-email rule is an enumeration oracle | High |
| Role/permission management | Broken | Admin can self-escalate to super_admin; role creation uses `sanctum` guard while app uses `web`; seeder families produce different final DB states | **Critical** |
| User management | Good | Policy-backed, role-assignability re-checked server-side | Low |
| Class management | Inconsistent | **3 different create paths** with different validation; instructor-created classes have price 0; delete is hard-cascade (data loss) | Medium |
| Student enrollment | Partial | Move-back breaks unique constraint; two transfer implementations diverge; price drifts across 4 stores | High |
| Public website registration | Partial | Server-side doesn't re-verify config `open`; QR enroll route unauthenticated + unthrottled; dozens of queries/registration | High |
| Course catalog + enroll configs | Partial | Unit/course price model is sound; but `course.php` routes unauthenticated-by-role and `verified` middleware 500s | High |
| Building/Floor/Room | Partial | No DB unique constraints (app-layer only); non-atomic bulk inserts; no cascade at DB level | Medium |
| Attendance auto-record | Fragile | **Scheduler not run in production at all**; no backfill; today-only; skipped/missed frozen; silent parse failures | **Critical** |
| Instructor dashboard (scores/teams/attendance) | Partial | 792-line service; raw DB writes; team save deletes+reinserts; transfer race | Medium |
| Schedule blocks | Partial | **IDOR on destroy**; only affects new registrations, not attendance | High |
| Website CMS + public API | Partial | Page content uneditable; slugs regenerate on rename; contact endpoint is a stub; base64 images | Medium |
| Notifications | Partial | Schema collides with Laravel `Notifiable`; mixed raw-insert/model styles; no per-user notifications | Medium |
| Terms/Times/Schedules CRUD | Partial | Routes `auth`+`active` only — any logged-in user can mutate | High |

---

## C. Database Review

74 migrations, ~50 tables. Two timestamp styles mixed (`YYYY_MM_DD_HHMMSS` and `YYYY_MM_DD_00000N`), one non-standard name (`2026_07_05_000001_enrich_classes_table.php`).

| Table | Problem | Risk | Recommendation | Priority |
| ----- | ------- | ---- | -------------- | -------- |
| `student_enrollments` | Unique `(study_class_id, student_id)` spans **all** statuses, not just active | Move A→B then B→A throws unhandled `QueryException`; re-enroll after cancel impossible | Unique on active only: partial unique or a nullable `cancelled_at`/status-aware constraint | **P0** |
| `students.user_id` | Nullable since 08/13 migration; **semantically now "created by", not the student's login**; `AdminRegistrationController` stores `auth()->id()` there | Confuses data meaning; future student-login features break | Rename to `created_by`; add a real link when student accounts exist | **P1** |
| `rooms.floor_id` | `nullable()` with **no `constrained()` FK** | Orphan rooms; building delete leaves stray rooms | Add FK + cascade/nullOnDelete; enforce uniqueness of room_number per floor | **P1** |
| `floors` / `rooms` | No DB unique constraints for name/level-per-building, number-per-floor | Duplicate rows under concurrency (bulk insert is non-atomic) | Composite unique indexes + transactional insert | P2 |
| `study_classes` / `student_enrollments` | No soft-delete; hard cascade wipes enrollments/attendances/scores/teams | Permanent data loss; no audit trail for a financial entity | Soft-delete + audit columns; keep deposits history | **P1** |
| `class_sessions` | `scheduled_start/end` stored as naive DATETIME, commands hardcode `Asia/Phnom_Penh` | If `APP_TIMEZONE` ever changes, every class instantly reads past-end → mass `missed` | Store with explicit tz or validate env consistency; document | **P1** |
| `class_sessions` | Only today's rows ever created; `whereDate('session_date', today)` in both due-queries | Scheduler outage = permanently lost attendance (no backfill, no catch-up) | Backfill/repair command; widen overdue queries | **P1** |
| `notifications` | Custom schema (`title/message/is_read/type`) collides with Laravel's `Notifiable` morph `notifications()` relation | Any `$user->notifications` use breaks; can't have per-user inboxes | Rebuild as Laravel notifications or rename table | P2 |
| `students.fee_amount/document_fee_amount/course_id/term_id/time_id` | Denormalized snapshot that never reconciles with enrollments/classes | Receipts vs actual enrollment fee diverge | Keep as historical snapshot but define source-of-truth + reconciliation | P2 |
| `users.status` + `users.is_active` | Two status flags, only `status` enforced; `is_active` drifts | Dead/inconsistent account state | Merge or enforce both consistently | P2 |
| `course_enroll_configs` | Solid design (unit/course price + selected type, per-time-slot rows) | Minor: `2026_08_20_000001` times dedupe migration didn't repoint `study_classes`/`students`/`course_enroll_configs` `time_id` FKs — deleting dup time rows **silently NULLs** referencing rows (set-null) | Add data-safety checks / repoint FKs before deleting | P2 |
| `grading_settings` | `scope_type`/`scope_id` columns but **no scoped settings are ever consumed** | Dead abstraction | Remove or implement scoping | P3 |
| `verification_codes` | Dead table storing **plaintext** codes, never written | Attack surface by neglect | Drop it | P3 |
| `teams.team_name` | No unique per group; `team_members` has `(group_id, student_id)` unique but group is in both tables | Team rename/dupe drift | Consider removing `group_id` from `team_members` (derivable) | P3 |
| Legacy tables `classes`, `enrollments` | Still used by `Registration` module (hybrid) while new stack uses `study_classes`/`student_enrollments` | Same student concept split across two enrollment systems | Migrate off legacy; delete | **P1** |
| Enum usage | All statuses are free `string` columns (enrollment_status, payment_status, class_sessions.status, rooms.status) | No DB-level constraint; invalid values silently accepted; status transitions unenforced | MySQL ENUM or CHECK constraints | P2 |

**Schema strengths:** good FK usage overall, row-locking discipline, snapshot pricing on enrollment, audit tables for attendance overrides, unique time slot names now enforced, JSON casts for specialization.

---

## D. Architecture Problems

1. **Two live enrollment/class stacks.** The new `study_classes` stack and legacy `classes`/`enrollments` stack both serve live routes. `AdminRegistrationController::store` (`app/Modules/Registration/Controllers/AdminRegistrationController.php:55-63`) creates a student via the *new* service then enrolls into the *legacy* `enrollments` table. This is the single biggest structural debt.
2. **Mixed data-access styles.** Eloquent models coexist with raw `DB::table()` writes (`StudentRegistrationService`, `InstructorClassService`). Raw writes bypass model casts, fillables, events and cache-busting hooks (e.g. `Term`/`Time`/`GradingSetting` cache invalidation).
3. **Three class-creation paths** (`CreateStudyClass`, `InstructorClassService::createClass`, `ClassListController::store`) with different validation strictness — schedule-consistency and availability are only enforced in one.
4. **Two student-transfer implementations** with different semantics (admin `MoveStudentEnrollment` vs instructor `transferStudent`).
5. **Duplicate presentation/parsing logic.** `parseTermDays`, `parseTimeRange`, `presentClass`, `toHm` are copy-pasted across `StudyClass`, `GetClassList`, `GetClassDetails`, `GetPublicRegistrations`, `InstructorClassService`, `RegisterStudentForSchedule`, `InstructorScheduleBlockController` — 5+ copies, high drift risk.
6. **Fat classes.** `EnrollmentClassController` (467 lines) and `InstructorClassService` (792 lines) mix HTTP concerns with business logic; role checks repeated via `abort_unless` inline (6x in `UserManagementController`).
7. **`nwidart/laravel-modules` installed but unused**; `modules_statuses.json` is fiction. Module naming chaos: `EnRoll` (dead) vs `Enroll` (live), lowercase `building`, singular `Controller/` in Floor/building, root-level controllers in Course.
8. **No scheduler/queue worker in production.** `bootstrap/app.php` defines a real schedule, but neither `docker-compose.prod.yml` nor the systemd unit runs `schedule:run`/`schedule:work`, and `QUEUE_CONNECTION=sync`. **The entire attendance system is dead in production.**
9. **Dead code shipped:** `EnRoll` module, `CheckRoleMiddleWare`, `RedirectAdminFromFrontend`, unrouted `PermissionController::userPermissionsPage`, empty `BuildingApiController`, empty `Registration` model, scratch files `Test.txt`/`chii.txt`.

---

## E. Code Problems

- `GetClassList::presentClass()` sets `'status' => $classTypeLabel` (class-type label) while the real status is in `class_status` (`app/Modules/Enroll/Queries/GetClassList.php:137`) — the UI status column shows "Online/Physical Class" instead of the class state.
- `GetClassList::summary()` double-counts partial students across the unpaid and partial cards (`GetClassList.php:166-167`).
- `StudentRegistrationService::createStudent` sets `user_id = creatorUserId` (`:15`) — creator stored as the student's user link (see DB finding).
- `InstructorClassService::saveTeams` re-runs two attendance aggregate queries just to validate membership (`:524`).
- `CourseController` validates `price` as `numeric` and maps it to `course_price` on `CourseEnrollConfig` while the `courses` table no longer has price columns — misleading legacy validation.
- `UpdateUserRequest::rules()` patches `$rules['student_code'][4]` — index-based, brittle (`app/Modules/User/Requests/UpdateUserRequest.php:47-48`).
- `AttendanceSettingsController::update` uses `?->update()` — missing settings silently skipped with a success flash (`:49`).
- `AdminRegistrationController` / `AdminClassController` reference `$cls->registered_count` claiming "triggers the accessor" — no such accessor exists, assignments are no-ops.
- `AutoRecordSession` catches MySQL-specific exception text `str_contains(..., 'student_attendance_unique_day')` (`:102`) — breaks on PostgreSQL/SQLite.
- `GenerateClassSessions` counts `skipped` rows as `created` (`:86`).
- Many string statuses and magic strings; no enum classes except `UserStatus`.

---

## F. Security Problems

### Critical

1. **Admin self-escalation to super_admin.** `UserManagementController::assignUsersToRole` (`:122-143`) and `assignRolePermissions` (`:75-88`) are gated only by `hasAnyRole(['super_admin','admin'])`, and their routes (`routes/web/backend/user.php:58,61`) carry only `['auth','active']`. An `admin` can `syncRoles(['super_admin'])` on themselves. Verified in source.

### High

2. **Telegram webhook unauthenticated when secret unset.** `TelegramWebhookController.php:24` — `if ($secret && ...)`. `TELEGRAM_WEBHOOK_SECRET` is empty in `.env`, so the endpoint requires nothing and anyone can `approve:{otp_id}`/`reject:{otp_id}` on sequential integer ids — approve instructors without review or DoS their registration.
3. **Routes missing role/permission checks:** `registration.php` (`dashboard/admin/*`, only `auth|active`), `course.php` (full CRUD with only `auth|active|verified`), `term.php`/`time.php`/`schdule.php` (CRUD with only `auth|active`), `GET /roles` (`permission.php:6`). Any logged-in student/instructor can mutate curricula, terms, schedules, and manage registrations.
4. **`verified` middleware dead-end:** `course.php` requires `email_verified_at`, but `UserService::create` never sets it and **no `verification.notice` route exists** — any unverified user hitting a course route 500s with `RouteNotFoundException`.
5. **`trustProxies(at: '*')`** (`bootstrap/app.php:39`) — any client can spoof `X-Forwarded-For`, defeating every IP-keyed rate limiter and corrupting audit IPs.
6. **Instructor schedule-block IDOR:** `InstructorScheduleBlockController::destroy` (`:170-175`) deletes any block by ID with no `instructor_id` scoping (unlike `destroyRow` at `:250-258`).

### Medium

7. Plaintext OTP in `notifications.message` and Telegram message (`CreateAdminApprovalNotification.php:20`, `TelegramService.php:85`).
8. Real Gmail app-password + weak DB passwords (`secret123`/`root123`) in `.env`; phpMyAdmin exposed on host in dev.
9. No `config/cors.php` — public API unusable cross-origin; Reverb `allowed_origins: ['*']`, `rate_limiting.enabled=false`.
10. SVG allowed for school logo (`SchoolSettingRequest.php:21`) -> stored XSS via `/storage` SVG.
11. Public QR enroll route (`POST /dashboard/enroll/{studyClass}/students`) — unauthenticated, **unthrottled**, no duplicate-check on re-submission (raw unique-constraint error), unlimited student creation.
12. Account enumeration via `unique:users,email` on registration.

### Low

13. `is_active` flag drift; `verification_codes` plaintext dead table; session cookie not `Secure`/encrypted in prod; seeded password `'password'`; public news endpoint leaks `users.email`.

---

## G. Performance Problems (ranked by impact)

1. **Public registration path (`RegisterStudentForSchedule`)** — per registration, inside a transaction: per-class lock+count loop, per-room lock+`exists` loop, per-instructor `conflict`+`block` queries. Dozens of queries/registration; locks serialize concurrent registrations.
   **Why slow:** lock + query per candidate + re-verification. **Fix:** single bulk query with `NOT EXISTS` for conflicts/blocks; reserve only after candidate selection. **Impact:** High under load.
2. **`AutoRecordSession` N+1** — per session, per student: 1 `HasApprovedPermission` `exists` query + 1 insert -> `2M + 2ME` queries/run. **Fix:** one permission query per session with `WHERE student_id IN (...)`. **Impact:** Medium-High.
3. **`GetClassDetails` N+1** — lazy-loads `term`, `time`, `classType` per class detail view (`:18-27` vs `presentClass()`). **Fix:** eager-load all three. **Impact:** Medium.
4. **`studentsForSelect`** loads **all** students (no pagination) on every class-detail render (`EnrollmentClassController:389-401`). **Impact:** Medium at scale.
5. **Base64-embedded images** in public API JSON (`PublicApiService::publicImageDataUri`) — no size cap, no HTTP cache, multi-MB payloads. **Impact:** Medium.
6. **Every-minute double scan** of today's pending sessions (`handle()` + `pastEnd()`), residual unindexed `whereDate(session_date, today)` + `scheduled_end` filters. **Impact:** Low-Medium.
7. **`GetClassList::summary()`** — 5 aggregate queries per page load; collapsible into one `GROUP BY payment_status`. **Impact:** Low.

---

## H. Business Logic Problems

- **Price drift across 4 stores** (`study_classes.price`, `student_enrollments.fee_amount`, `students.fee_amount`, `course_enroll_configs.resolvedPrice()`) with no reconciliation — receipts can disagree with class price and with the public site.
- **Instructor-created classes price 0** — `InstructorClassService::createClass` never sets price; students enrolled later snapshot 0.
- **Instructor `transferStudent`** mutates the same enrollment row in place: keeps old class's fee/payment snapshot, never recomputes against target price, never checks target status (`cancelled`/`ended` allowed), capacity check unlocked (`:642-651`).
- **Move-back deadlock** — cancelled rows block re-enrollment into the origin class.
- **`skipped` sessions frozen at 00:05** — a class empty at generation time stays `skipped`; students enrolling later that day are never auto-recorded, and manual rows don't transition the session.
- **`missed` never heals** — manual correction after a missed session leaves the session/status/digest reporting missed.
- **No backfill** — any generator/scheduler downtime permanently loses a day's sessions.
- **`pending_registrations` never resolved** — created when no room/instructor, but nothing ever marks them `resolved`; staff re-book via a different path.
- **Capacity override surprise** — `SaveStudyClassRequest::prepareForValidation` replaces the submitted capacity with `room->capacity` for physical classes (`:23-44`); the form value is silently discarded.
- **Room capacity always wins / public auto-created classes** inherit previous class capacity, but `availableClass` returns a class with free seats, then `ensureClassAssignments` may swap the room and change capacity mid-flight — a student can be seated in a class that no longer fits them.
- **Public registration doesn't re-verify `course_enroll_configs.status === 'open'`** or schedule membership server-side — crafted POST bypasses frontend filtering.
- **`study_class_instructors` pivot not updated when the owner's `term_id`/`time_id` change** (`UpdateStudyClass`) — stale shared-instructor schedules.
- **Shared-instructor session attribution** picks the first pivot matching the weekday; overlapping co-instructor terms resolve arbitrarily.
- **Class delete is permanent cascade** — no soft delete, no retention of financial records.
- **`students.user_id` = creator**, not student login — breaks the mental model and any future student-portal login.

---

## I. Recommended Architecture (based on existing project — no rewrite)

Keep the module + Actions/Queries pattern that works. Changes:

1. **Kill the legacy stack.** Retire `classes`/`enrollments`/`ScheduleClass`/`Enrollment`/`EnRoll`/`Registration` module. Point `dashboard/admin/*` at the new stack (`study_classes` + `student_enrollments`) or delete the routes. One enrollment truth.
2. **Unify the two transfer flows** into one `MoveStudentEnrollment` with explicit policy (recompute payment vs preserve snapshot; check target status).
3. **Single class-creation path** — make `SaveStudyClassRequest` + `CreateStudyClass` the only writer; make `InstructorClassService::createClass` and `ClassListController::store` delegate to it.
4. **Extract shared schedule-domain code** into one place (e.g. a `ScheduleParser` / `TermTimeService` used by `StudyClass`, queries, actions, and the schedule-block controller).
5. **Move role/permission authorization to route middleware + policies** (matching the floor/room/website pattern) instead of inline `abort_unless`.
6. **Standardize DB access** — prefer Eloquent models (casts/events/cache hooks); keep `DB::table()` only where locking/pagination genuinely needs it, and document why.
7. **Make notification writes go through the `Notification` model** (or migrate to Laravel's DB notifications) — one abstraction.
8. **Run the scheduler and queue for real:** add a `scheduler` + `queue` container to `docker-compose.prod.yml` (or cron `schedule:run`) and a `queue:work` process; fix `QUEUE_CONNECTION=sync`.
9. **Introduce soft-deletes + audit** for `study_classes` and `student_enrollments` (financial entities).
10. **Tighten the module conventions** for new code: `Controllers/`, plural module names, named routes everywhere (user/building/floor/room/permission currently unnamed -> Ziggy can't reference them).

---

## J. Recommended Database Changes (each with concrete benefit)

**1. `student_enrollments` — allow re-enrollment after cancel/move**
- Current: unique `(study_class_id, student_id)` across all statuses.
- Problem: move-back throws; cancelled students can't re-enroll.
- Proposed: `student_enrollments.unique_active_class_student` as a **partial/computed unique** — e.g. add `active_enrollment_key` nullable column = `study_class_id:student_id` set only when `enrollment_status='active'`, unique on that; or a migration-level trigger.
- Why: fixes move-back; keeps dedupe safety for the active case.
- Migration risk: Low-Medium — backfill key only for active rows; index rename.

**2. `students.user_id` semantics**
- Current: nullable FK to users, but holds the *creator's* id.
- Problem: data meaning is wrong; blocks future student-login.
- Proposed: rename to `created_by` (nullable, FK users), and add `users.student_profile_id` when student accounts are introduced.
- Why: correctness; unambiguous ownership.
- Migration risk: Low — column rename, re-point existing values.

**3. `rooms.floor_id` FK + unique constraints**
- Current: `nullable()` no FK; floors/rooms uniqueness app-layer only.
- Problem: orphans; duplicate rows under concurrent bulk insert.
- Proposed: FK with `nullOnDelete` (or `cascadeOnDelete`), composite unique `(floor_id, room_number)` and `(building_id, name[, level])`.
- Why: referential integrity; concurrency safety.
- Migration risk: Medium — dedupe existing rows first (mirror the times dedupe pattern).

**4. `class_sessions` backfill + timezone-safe storage**
- Current: naive DATETIME; today-only generation.
- Problem: downtime loses history; tz flip mass-misses classes.
- Proposed: store `scheduled_start/end` as timestamps w/ explicit zone or keep naive but add a `BACKFILL_SESSION_DAYS` repair command; validate `APP_TIMEZONE === Asia/Phnom_Penh` at boot.
- Why: resilience.
- Migration risk: Low.

**5. Soft-delete + audit for classes/enrollments**
- Current: hard cascade.
- Problem: permanent financial-record loss.
- Proposed: `deleted_at` columns + an `enrollment_events`/`class_events` audit table; change cascade FK on `student_enrollments.study_class_id` from cascade to restrict once soft-delete is in place.
- Why: auditability.
- Migration risk: Medium — bulk backfill `deleted_at = NULL`; update delete paths.

**6. Times dedupe data-safety (2026_08_20_000001)**
- Current: dedupe only repoints `schedule_time`, `instructor_schedule_blocks`, `work_schedule_times`; other `time_id` FK tables (`study_classes`, `students`, `course_enroll_configs`) get silently NULLed on delete.
- Problem: silent data loss of class time slots.
- Proposed: extend the dedupe to repoint all `time_id` FKs before deletion.
- Why: data integrity.
- Migration risk: Low — already-run migration; add a corrective migration for any NULLed rows (re-match by name).

**7. Enforce status values**
- Current: free-string statuses everywhere.
- Proposed: ENUM/CHECK on `enrollment_status`, `payment_status`, `class_sessions.status`, `study_classes.status`.
- Why: prevents invalid states; makes transitions auditable.
- Migration risk: Medium — validate existing values first.

**8. Notifications schema**
- Current: custom table colliding with Laravel `Notifiable`.
- Proposed: rename table to `dashboard_notifications` or migrate to Laravel's `notifications` (with `notifiable_type/id`); add `user_id` if per-user inboxes are wanted.
- Why: unblocks standard notification features.
- Migration risk: Medium.

---

## K. Priority Roadmap

### P0 — Fix immediately

1. **Remove admin self-escalation** — `assignUsersToRole`/`assignRolePermissions` restricted to `super_admin` only (`UserManagementController:75-88,122-143`).
2. **Run the scheduler in production** — add cron/`schedule:work` + `queue:work` container; attendance is currently non-functional in prod.
3. **Enforce Telegram webhook secret (fail closed)** and scope `trustProxies` to the real proxy — `TelegramWebhookController:24`, `bootstrap/app.php:39`.
4. **Close the role-less admin routes** — `registration.php`, `course.php`, `term.php`, `time.php`, `schdule.php`, `GET /roles` get `role:super_admin|admin` (or `permission:`).

### P1 — Before next release

5. **Fix the `verified` dead-end** on `course.php` (add `verification.notice` or drop `verified`; set `email_verified_at` for admin-created users).
6. **Fix schedule-block IDOR** (`InstructorScheduleBlockController::destroy`) + the unthrottled public QR enroll route.
7. **Unify/repair enrollment moves** — fix move-back unique failure; align instructor `transferStudent` with admin semantics; reject moving into `cancelled`/`ended` classes.
8. **Stop persisting plaintext OTPs** in notifications/Telegram; decide on the stale `is_active` flag.
9. **Migrate off the legacy `classes`/`enrollments` stack** (`Registration` module) or delete it.
10. **Session hardening in prod** — `SESSION_SECURE_COOKIE=true`, encrypt sessions, rotate the Gmail app-password + DB passwords, add `config/cors.php`.

### P2 — Important improvement

11. `class_sessions` backfill/repair; fix `skipped`/`missed` healing; single permission-query in auto-record (kill N+1).
12. Eager-load `term/time/classType` in `GetClassDetails`; paginate `studentsForSelect`.
13. Reconcile price sources (enrollment = class price; class price sourced from config on creation; students.fee_amount only for pre-registration receipts).
14. Soft-delete + audit for classes/enrollments; DB uniques for floors/rooms; status ENUMs; fix times-dedupe data safety.
15. Make notifications table a proper Laravel notifications table; single notification abstraction.

### P3 — Nice to have

16. Unify the three class-creation paths; extract the duplicated schedule-parsing/presenting code.
17. Cache public API responses; serve images via URLs not base64; cap image sizes.
18. Add missing tests (enroll moves, attendance command integration, permission-authorization, Telegram webhook auth).
19. Drop dead code (`EnRoll`, `CheckRoleMiddleWare`, `RedirectAdminFromFrontend`, empty controllers/models, `verification_codes`).
20. Normalize module folder conventions and route names; add `content` editing for pages.

---

## Top 10 things to fix first

1. **Admin -> super_admin self-escalation** (`UserManagementController`) — privilege escalation, live today.
2. **Scheduler not running in production** — the entire attendance pipeline silently does nothing.
3. **Role-less admin routes** (registration, course, term, time, schdule, roles) — any user can mutate core data.
4. **Telegram webhook fails open** + `trustProxies('*')` — auth bypass + broken rate limiting.
5. **`verified` middleware 500 on course routes** — broken feature for unverified users.
6. **Move-back unique-constraint failure** + divergent transfer logic — data corruption on a core workflow.
7. **Public QR enroll: unauthenticated + unthrottled + no duplicate check** — abuse/bot risk.
8. **Plaintext OTP in notifications/Telegram** + stale `is_active` drift.
9. **Attendance fragility** — no backfill, `skipped`/`missed` frozen, N+1 auto-record.
10. **Legacy stack (`classes`/`enrollments`) still wired into live routes** — dual-source-of-truth.

---

All findings are verified against the current tree (HEAD `7615481`). Nothing has been changed.