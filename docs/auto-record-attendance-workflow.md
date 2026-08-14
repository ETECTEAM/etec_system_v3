# Auto-Record Attendance — How It Works

An end-to-end explainer of the auto-record attendance feature. Read this first, then follow
the code references if you want to dig deeper.

---

## 1. The idea in one paragraph

Every class has its own weekly schedule (which weekdays + a time slot, e.g. **09:00–10:30**).
When the instructor forgets to submit attendance, the system waits a **grace period** (default
**15 min**, so until **09:15**), then **records attendance itself** on the instructor's behalf
at **09:16**. A superadmin can change the grace number or turn the whole thing off.

The most important rule: **the system never marks a student `absent`** — an instructor
forgetting can never cause a student to fail.

---

## 2. The settings (superadmin only)

Route: `/dashboard/attendance-settings` (sidebar → **Attendance Settings**).
Middleware: `auth + active + role:super_admin`.
Backend: `app/Modules/Attendance/Controllers/AttendanceSettingsController.php`.

Stored in the `grading_settings` table (`database/migrations/2026_08_15_000001_...`), read via
the `setting($key, $default)` helper (`app/Helpers/helpers.php` — single cached query, cache
busted on save).

| Key | Default | Meaning |
|---|---|---|
| `attendance.auto_record_enabled` | `true` | master switch |
| `attendance.auto_record_grace_minutes` | `15` | how long the instructor has |
| `attendance.auto_record_default_status` | `present` | what unmarked students get (`present` or `pending`, never `absent`) |
| `attendance.auto_record_notify_instructor` | `true` | stored, see note in §8 |
| `attendance.auto_record_allow_override` | `true` | may the instructor fix an auto-recorded session |
| `attendance.auto_record_override_hours` | `24` | how long the correction window stays open |

Validation (in `SaveAttendanceSettingsRequest`): grace minutes must be an integer ≥ 1 and
**less than the shortest class duration in the system**; default status must be exactly
`present` or `pending`. All-or-nothing — a failed field saves nothing.

---

## 3. How each class gets its "today" session

`study_classes` does **not** store calendar dates — only `term_id` (weekdays encoded in the
term name) and `time_id` (time range encoded in the time name).

So `GenerateClassSessions` (`app/Modules/Attendance/Actions/GenerateClassSessions.php`) runs
**daily at 00:05** (`bootstrap/app.php`) and creates one `class_sessions` row per class that
**meets today**, using that class's own `scheduled_start` and `scheduled_end`.

A class gets a session today only if:
- status is `upcoming` / `active` / `pre_end`
- today is within its `start_date`–`end_date`
- today matches its term's weekdays (e.g. term "Mon & Wed" → Monday/Wednesday)
- today is not a `holidays` row
- shared classes ("Collapse Class") use whichever instructor's slot covers today

If a class has **no active students**, its session is created as `skipped` instead of `pending`.

---

## 4. The scheduler — every minute

Registered in `bootstrap/app.php:67-91`, all with `->withoutOverlapping()`:

| Command | Runs | Does |
|---|---|---|
| `attendance:auto-record` | every minute | the core logic below |
| `attendance:generate-sessions` | daily 00:05 | creates today's `class_sessions` |
| `attendance:send-digest` | daily 22:00 | one admin notification summarizing the day |

**For the scheduler to actually fire, the Laravel scheduler must run once per minute:**

```bash
# dev
php artisan schedule:work

# production cron
* * * * * php artisan schedule:run
```

You can also trigger it manually: `php artisan attendance:auto-record`.

The `auto-record` command (`app/Console/Commands/AutoRecordAttendanceCommand.php`) does, in order:

1. If `auto_record_enabled` is false → do nothing.
2. Find sessions where: `session_date = today`, `status = pending`,
   `now >= scheduled_start + grace`, `now < scheduled_end`.
3. For each session (own transaction, row re-locked with `lockForUpdate`, status re-checked):
   - student has **approved permission** that day → recorded as `permission`
   - otherwise → recorded as `auto_record_default_status` (never `absent`)
   - `source = auto`, `recorded_by = null`
   - session → `auto_recorded`, `recorded_at = now`, `grace_minutes_used = grace`
   - duplicate insert (unique index) is caught and silently skipped
4. Separately, sessions `status = pending` and `now >= scheduled_end` → `missed` (never
   auto-recorded after the fact).

Source: `app/Modules/Attendance/Queries/GetSessionsDueForAutoRecord.php` +
`app/Modules/Attendance/Actions/AutoRecordSession.php`.

---

## 5. What happens next (the chain after auto-record)

1. **Same run** — student rows exist (`source = auto`), session is `auto_recorded` with a
   `recorded_at` timestamp.
2. **Instructor's next visit** — the class's attendance page shows an amber banner:
   *"The system recorded today's class at HH:MM. You can correct it until <recorded_at + 24h>."*
   (`app/Modules/Attendance/Queries/GetSessionBanner.php`).
3. **No double-record** — the session is no longer `pending`, and the unique index on
   `(study_class_id, student_enrollment_id, attendance_date)` blocks duplicates anyway.
4. **If the instructor corrects it in time** — rows are **updated in place** (never new
   inserts) → `source = manual`, `tracked_by = instructor`; an `attendance_audit_logs` row
   records who / when / old status → new status; the session becomes `recorded`. The banner
   timestamp stays as the original auto-record time.
5. **If the instructor does nothing** — the auto rows stand; the audit trail shows the system
   recorded them.
6. **22:00 digest** — `attendance:send-digest` creates one admin notification:
   "X class(es) auto-recorded today, Y ended with no attendance (missed, needs review)."

---

## 6. Instructor view in detail

| Situation | What the instructor sees / can do |
|---|---|
| Normal day, submitted in time | rows `source = manual`, session `recorded`, nothing else happens |
| Forgot → auto-recorded | amber banner + table **stays editable**; Save becomes "correction" (`PUT`, not `POST`) |
| Correction within window | rows overwritten + audit logged; session → `recorded` |
| Window closed | banner says window closed; save is blocked (`OverrideAttendanceRecord.php`) |

Routes: `routes/web/backend/instructor.php:15-20` — `POST` = normal save, `PUT` = override.

### Step-by-step: how the instructor corrects an auto-recorded session

1. Log in as instructor → Dashboard → open your class. An **amber banner** appears:
   *"The system recorded today's class at `HH:MM`. You can correct it from Track Attendance
   until `<deadline>`."* — the deadline is the auto-record time + override hours (default 24h).
2. Click **Track Attendance** (`AttendanceRecord.vue:77-83`).
3. The roster is **still editable** (statuses + notes), and the save button reads
   **"Save Correction"** (`TrackAttendance.vue:142`).
4. Change any student's status / note, then hit **Save Correction**.

What happens on save:

- The frontend sends **`PUT /dashboard/instructor/classes/{id}/attendance`** instead of the
  normal `POST` (`TrackAttendance.vue:103-107`).
- `OverrideAttendanceRecord` (`app/Modules/Attendance/Actions/OverrideAttendanceRecord.php`):
  1. **Rejects** if the session isn't `auto_recorded`, override is disabled, or now > deadline.
  2. **Updates the existing rows only** — never inserts — setting `status`/`note`,
     `tracked_by = instructor`, `source = manual`.
  3. Writes an **`attendance_audit_logs` row**: who, when, old status → new status,
     old source → new source.
  4. Flips the session to `recorded` (the banner timestamp stays at the original
     auto-record time).

If the window has closed: the banner changes to *"The window to correct it has closed."*,
the button is disabled, and the backend rejects the request too — the auto rows stand with
the audit trail intact.

**Caveats:** correction is only possible within the override window, only for `auto_recorded`
sessions (a normally-recorded one can't be re-edited that day), and only students who were
auto-recorded get updated — someone enrolled after the fact isn't touched.

---

## 7. Edge cases (all intentional)

- **Never `absent`** — even a stray config value is clamped to `present`/`pending`.
- **Never retroactive** — a session already past its end time becomes `missed`, not recorded.
- **No duplicates** — unique index + `withoutOverlapping()`; a row that already exists wins.
- **Grace is global** — one number for every class, but validated to be shorter than the
  shortest class, so it can never exceed a class's own duration.
- **No per-course scopes** — `scope_type`/`scope_id` exist as nullable columns only.
- **Holidays** — no session is generated, so nothing auto-records.
- **Empty class** — `skipped`, never auto-recorded.

---

## 8. What is NOT implemented (yet)

- **Per-instructor Telegram push.** The original spec wanted a Telegram message to the
  instructor when a session auto-records. The code deliberately skipped it — there is no
  per-instructor push channel (`AutoRecordSession.php:72-75`). The `notify_instructor`
  setting is stored but currently does nothing; instructor visibility is the in-app banner.
- **Admin notification** exists as the daily digest only, not per-event.

---

## 9. Quick file map

```
app/Helpers/helpers.php                        setting($key, $default) cached reader
app/Models/GradingSetting.php                  settings row model (busts cache on save)
app/Models/ClassSession.php                    per-class-per-date session
app/Models/StudentAttendance.php               attendance rows (source, tracked_by, session_date)
app/Models/AttendanceAuditLog.php              override audit trail
app/Models/StudentPermission.php               approved leave/permission
app/Models/Holiday.php                         off days
app/Modules/Attendance/Controllers/            settings page (superadmin)
app/Modules/Attendance/Requests/               settings validation
app/Modules/Attendance/Actions/                GenerateClassSessions / AutoRecordSession / OverrideAttendanceRecord
app/Modules/Attendance/Queries/                GetSessionsDueForAutoRecord / GetSessionBanner / HasApprovedPermission ...
app/Console/Commands/                          auto-record / generate-sessions / send-digest
bootstrap/app.php                              scheduler registration
routes/web/backend/attendance-settings.php     superadmin settings routes
resources/js/pages/backend/attendance-settings/Edit.vue
resources/js/pages/backend/instructors/        AttendanceRecord / TrackAttendance (banner + override UI)
database/migrations/2026_08_15_00000*.php      sessions / permissions / attendance source / audit logs / holidays
```
