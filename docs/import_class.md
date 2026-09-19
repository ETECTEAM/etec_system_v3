# Import Class Feature — Old System vs New System

## TL;DR

There's no literal "import class" feature in the old system. What actually exists is **one half of a migration bridge**:

- **Old system (`etec_old`)**: has an **"Export Attendance CSV"** button per class — dumps that class's students + attendance history to a CSV file.
- **New system (`etec_system_v3`)**: already has the **matching other half**, fully built — an **"Import Legacy CSV"** action on the instructor dashboard that reads that exact CSV and creates/matches students, enrollments, and attendance rows for one existing class.

They were clearly designed as a pair (column names/order line up, including the UTF-8 BOM trick for Khmer names), even though the two codebases never reference each other directly.

**Important scope note**: this only imports students + attendance history *into a class that already exists* in the new system. It does **not** bulk-create classes/courses from a file. If that's what you meant by "import class," that part doesn't exist yet in either repo.

---

## 1. Old system side — "Export Attendance CSV"

**Design doc**: `etec_old/docs/attendance_csv_export_idea.md` — states the goal plainly: *"Export attendance data from the current system (`student_records`) as a CSV file that can be imported into the new system."* (This doc is now stale — it says "not yet implemented," but the export was built in the same commit that touched the doc.)

**How it works:**

1. **UI**: `etec_old/pages/frontend/classes.php:1101` — "Export Attendance CSV" button in each class's action-menu dropdown.
2. **Trigger**: `pages/frontend/classes.php:1555` — plain navigation (not AJAX) to `api.php?endpoint=export_attendance_csv&class_id=...`, which triggers a browser download.
3. **Router**: `etec_old/api.php:707-714` — old-style `switch($endpoint)` dispatcher, calls `StudentController::exportAttendanceCsv(...)`.
4. **Implementation**: `etec_old/controllers/frontend/StudentController.php:1013-1092`
   - Joins `student_records` → `students` → `classes` (one row per student per attendance day).
   - Columns selected: `full_name, gender, tel, att_record_date, present, absent, permission, reason`.
   - Optional filters: `class_id`, `start_date`/`end_date`.
   - Streams CSV via `fputcsv()` with a UTF-8 BOM prefix so Excel renders Khmer names correctly.
   - Header row: `Name, Gender, Tel, Date, Present, Absent, Permission, Reason`.
   - Filename: generic `attendance_export_<date>.csv`.

**Notable gap**: this endpoint has **no auth/permission check** — anyone who knows or guesses a `class_id` can download that class's roster and attendance without logging in.

---

## 2. New system side — "Import Legacy CSV" (already implemented)

This is a complete, working feature already in the codebase.

| Layer | File |
|---|---|
| Frontend trigger | `resources/js/pages/backend/InstructorDashboard.vue` — hidden file input + "Import Legacy CSV" menu item on each class card |
| Frontend logic | same file, `importLegacyCsv()` (~129-132), `onCsvSelected()` (~134-151) — POSTs the file via axios, shows a toast with the result |
| Route | `routes/web/backend/instructor.php:48` — `POST /dashboard/instructor/classes/{studyClass}/attendance/import-csv`, throttled `5,1`, inside `auth + active + role:instructor + onboarding` middleware |
| Controller | `app/Modules/Instructor/Controllers/InstructorClassController.php:418-422` — `importAttendanceCsv()`, validates ownership of the class + file (`mimes:csv,txt`, max 10MB) |
| Core logic | `app/Modules/Instructor/Services/ImportInstructorAttendanceCsv.php` (84 lines) |
| Student helper | `app/Modules/EnRoll/Services/StudentRegistrationService.php` (`createStudent`, `findStudentByPhone`) |
| Attendance helper | `app/Models/StudentAttendance.php` (`flagsFor()`, source constants) |

### How it processes a CSV (`ImportInstructorAttendanceCsv::handle()`)

1. Parses with plain PHP `fgetcsv()` — **no Excel package used**, CSV/TXT only (confirmed: `composer.json` has no `maatwebsite/excel` or `phpoffice/phpspreadsheet`).
2. Detects the header row, strips BOM, lowercases/trims each column name.
3. **Required headers** (case-insensitive): `name, gender, tel, date, present, absent, permission`. `reason` is optional. This matches the old export's header almost exactly (case differs, handled).
4. For each row, inside one DB transaction:
   - Skips rows with empty name/phone or an invalid `date` (must be `YYYY-MM-DD`) — counted in `rows_skipped`.
   - **Student matching**: looked up by `phone` only, no normalization. If not found, a new student is created (gender defaults to `'male'` if blank/unparseable — silent data-quality risk).
   - **Enrollment**: `firstOrCreate` on `(study_class_id, student_id)` — idempotent, won't duplicate on re-import. New enrollments get `source: 'legacy_csv'`, `payment_status: 'unpaid'`, fees at 0.
   - **Attendance status**: `permission` > `absent` > `present` priority; none set → `'pending'`.
   - **Attendance write**: `updateOrInsert` on `(study_class_id, student_enrollment_id, attendance_date)` — re-importing the same CSV overwrites that day's row rather than duplicating it.
5. Returns a JSON summary: `{students_created, enrollments_created, attendance_imported, rows_skipped}`.

---

## 3. Data model (new system)

Relevant tables the importer touches:

- **`study_classes`** — the class being imported into (must already exist).
- **`students`** — matched by phone, created if missing.
- **`student_enrollments`** — one per (class, student), idempotent via `firstOrCreate`.
- **`student_attendances`** — one per (class, enrollment, date), upserted; unique index `student_attendance_unique_day`.

Note: there's a separate, older `classes`/`enrollments` pair of tables in the new system (models `ScheduleClass`/`Enrollment`) marked as **dead/legacy, unused** per `AGENT_GUIDE.md`. Don't confuse that with `study_classes`, which is what this import actually uses.

---

## 4. Quirks & things to watch if extending this

1. **Header mismatch is fragile**: if the old export's header text/order ever changes, the new importer's check would just throw a generic "CSV headers must include..." error without saying which column is missing.
2. **Gender silently defaults to `'male'`** for new students when the CSV value is blank/unparseable.
3. **Phone matching has no normalization** — `012345678` vs `+855 12 345 678` would be treated as two different students.
4. **No chunking/queueing** — whole file processed in one transaction; fine for typical class sizes, could be slow for very large legacy exports.
5. **Auth posture differs**: old export has none; new import requires auth + role + ownership + throttling. Keep the stricter posture if this gets extended.
6. **Idempotent by design** — safe to re-run the same CSV; enrollments won't duplicate, attendance rows get overwritten for that date.
7. **Instructor-only today** — the "Import Legacy CSV" button only exists on the instructor dashboard; there's no equivalent on admin/super_admin class-management pages.
8. **No automated tests** exist for this feature yet.
9. **Doesn't create classes** — only fills in roster + attendance for a class that's already been created in the new system.

---

## 5. File reference index

**Old system:**
- `etec_old/controllers/frontend/StudentController.php:1010-1092`
- `etec_old/api.php:707-714`
- `etec_old/pages/frontend/classes.php:1101,1555`
- `etec_old/docs/attendance_csv_export_idea.md`

**New system:**
- `app/Modules/Instructor/Services/ImportInstructorAttendanceCsv.php`
- `app/Modules/Instructor/Controllers/InstructorClassController.php:418-422`
- `routes/web/backend/instructor.php:47-48`
- `resources/js/pages/backend/InstructorDashboard.vue`
- `app/Modules/EnRoll/Services/StudentRegistrationService.php`
- `app/Models/StudentAttendance.php`
- Migrations: `2026_07_12_111753_create_study_classes_table.php`, `2026_08_06_000001_refactor_study_classes_schedule_columns.php`, `2026_06_15_075520_create_students_table.php`, `2026_07_12_111819_create_student_enrollments_table.php`, `2026_08_13_000001_create_student_attendances_table.php`

---

## 6. Idea: collapsing old split classes (e.g. "Basic IT: Network" + "Basic IT: Coding") into one new class

**Scenario**: in the old system, a concept like "Basic IT" was split into two separate `classes` rows — e.g. `IT101-Network` and `IT101-Coding` — each with its own roster and its own exported CSV. In the new system you want a single collapsed class, e.g. `Basic IT`, that holds all those students together.

### Does the current importer support this? Yes, with no code changes. (Verified against source: `app/Modules/Instructor/Services/ImportInstructorAttendanceCsv.php` has no "already imported" tracking — it's driven entirely by `firstOrCreate`/`updateOrInsert` keys, so importing a second CSV into the same class is safe by construction, not by accident.)

The importer is designed around one target class per upload, but it's **idempotent per student** (matched by phone) and **idempotent per attendance day** (matched by class+enrollment+date). That means you can import multiple old CSVs into the *same* new class, one after another, and it will merge correctly rather than duplicate:

1. In the new system, create **one** class: `Basic IT` (e.g. `study_class_id = 501`).
2. Export the old `IT101-Network` class → `network_export.csv`. Import it into `Basic IT`.
3. Export the old `IT101-Coding` class → `coding_export.csv`. Import it into the **same** `Basic IT` class.
4. A student who appears in both old classes (same phone number) is only created/enrolled **once** — the second import's `firstOrCreate` on `(study_class_id, student_id)` finds the existing enrollment instead of duplicating it. This is exactly the "collapse" behavior you want for rosters.

### Sample data

**Old export #1 — `IT101-Network` (class_id 201):**

| Name | Gender | Tel | Date | Present | Absent | Permission | Reason |
|---|---|---|---|---|---|---|---|
| Sok Dara | male | 012345678 | 2026-08-04 | 1 | 0 | 0 | |
| Chan Sophea | female | 012987654 | 2026-08-04 | 1 | 0 | 0 | |
| Sok Dara | male | 012345678 | 2026-08-06 | 0 | 1 | 0 | Sick |
| Chan Sophea | female | 012987654 | 2026-08-06 | 1 | 0 | 0 | |

**Old export #2 — `IT101-Coding` (class_id 202):**

| Name | Gender | Tel | Date | Present | Absent | Permission | Reason |
|---|---|---|---|---|---|---|---|
| Sok Dara | male | 012345678 | 2026-08-05 | 1 | 0 | 0 | |
| Chan Sophea | female | 012987654 | 2026-08-05 | 0 | 0 | 1 | Family event |
| Ly Hong | male | 012111222 | 2026-08-05 | 1 | 0 | 0 | |
| Sok Dara | male | 012345678 | 2026-08-07 | 1 | 0 | 0 | |

**Result after importing both into new class `Basic IT` (study_class_id 501), in order:**

- `students_created`: 3 total across both imports — Sok Dara, Chan Sophea (created on import #1), Ly Hong (created on import #2). Sok Dara/Chan Sophea are **not** recreated on import #2 — matched by phone.
- `enrollments_created`: 3 — one per student, in `Basic IT`. Sok Dara/Chan Sophea get exactly one enrollment each, not one per old class.
- `student_attendances` rows (merged roster view):

| Student | 08-04 | 08-05 | 08-06 | 08-07 |
|---|---|---|---|---|
| Sok Dara | Present | Present | Absent (Sick) | Present |
| Chan Sophea | Present | Permission (Family event) | Present | — |
| Ly Hong | — | Present | — | — |

This is exactly the collapsed view you'd want: one roster, one attendance timeline, regardless of which old class a session originally belonged to.

### The one real gotcha: same-date collisions

Attendance is unique per `(study_class_id, student_enrollment_id, attendance_date)` — **not** per subject. If a student had a Network session **and** a Coding session on the *same calendar date* in the old system, importing both CSVs will not give you two attendance rows for that day — the second import's row silently **overwrites** the first (`updateOrInsert`). Example: if Sok Dara was marked absent from Network on 2026-08-04 but present in Coding on that same date, whichever CSV you import last wins, and the other subject's record for that day is lost.

Two ways to handle it if this matters for your data:
- **Decide it doesn't matter** — if "Basic IT" is now one combined class, a student either showed up that day or didn't, and per-subject granularity isn't meaningful anymore. This is the simplest option and matches how the new schema models attendance (one flag per class per day).
- **Preserve context in the note** — before importing, edit the `Reason` column in each old CSV to prefix which subject it came from (e.g. `Reason: "Network — Sick"` vs `Reason: "Coding — present"`), so at least the `student_attendances.note` field keeps a trace of the source class when dates collide. Requires a manual CSV edit pass, not a code change.

### Practical steps checklist

1. Create the new collapsed class (`Basic IT`) in the new system first — the importer requires the target class to already exist.
2. Export each old split class individually from `etec_old` (`Export Attendance CSV` button, per class).
3. Check both CSVs for overlapping dates for the same student before importing, if per-subject attendance detail matters to you.
4. Import the CSVs one at a time, in any order, into the same new class via **Import Legacy CSV** on the instructor dashboard.
5. Spot-check the resulting roster and attendance calendar for the collapsed class after both imports.

### Yes — confirming the workflow: each old teacher exports their own class, one person imports both into the new collapsed class

Right — in the old system, `IT101-Network` and `IT101-Coding` likely have **different instructors** (`instructor_id` differs per class). Each of those teachers only has access to export *their own* class in `etec_old`. So the real workflow looks like this:

**Example — Teacher Ravy (Network) and Teacher Sina (Coding):**

| Step | Who | Where | Action |
|---|---|---|---|
| 1 | Teacher Ravy | Old system | Opens `IT101-Network`, clicks **Export Attendance CSV** → downloads `network_export.csv` |
| 2 | Teacher Sina | Old system | Opens `IT101-Coding`, clicks **Export Attendance CSV** → downloads `coding_export.csv` |
| 3 | Teacher Ravy | New system | On her own Instructor Dashboard, uses **Add Class** to create the collapsed class `Basic IT` — she becomes its `teacher_id` automatically, since she's the one creating it |
| 4 | Teacher Sina | — | Sends `coding_export.csv` to Ravy — e.g. via Telegram/email, since Sina isn't assigned to `Basic IT` in the new system |
| 5 | Teacher Ravy | New system | Opens `Basic IT` → **Import Legacy CSV** → uploads `network_export.csv` (her own data) |
| 6 | Teacher Ravy | New system | Repeats **Import Legacy CSV** on the same `Basic IT` class → uploads `coding_export.csv` (Sina's data, handed to her in step 4) |
| 7 | Everyone | New system | `Basic IT` roster now shows all students from both old classes, merged and deduped by phone, as shown in the sample tables above |

**Instructors can create classes themselves** — not just admin. `routes/web/backend/instructor.php:30-31` exposes `GET/POST /dashboard/instructor/classes` (Add Class) to any instructor, gated by a `can-create-classes` middleware (`app/Http/Middleware/EnsureInstructorCanCreateClasses.php`) that checks the `create-classes` permission (the "Classes → Create" tick on the Role & Permission page for every instructor, or granted to one instructor on User & Permission) — an instructor without it gets a 403 telling them to ask an admin. So in practice: if Ravy has the permission, she creates `Basic IT` herself (step 3 above); if not, an admin creates it and assigns her as `teacher_id` instead — either way, whoever ends up as the class's `teacher_id` is the only one who can later import into it.

**Why it has to funnel through one person**: the import endpoint (`routes/web/backend/instructor.php:48`) is guarded by `role:instructor` plus an ownership check (`InstructorClassService::findForInstructor`, `InstructorClassController.php:418-422`) — a teacher can only import into a class where they are the assigned `teacher_id`. Since only one instructor can own the collapsed `Basic IT` class, only that person can actually click Import — the other teacher's role in this process is just producing their CSV and handing it off. There's currently no admin-side import screen (see §4, point 9) that would let an admin do this centrally instead — if you're collapsing classes often, that might be worth adding so it doesn't depend on teacher-to-teacher file handoff.
