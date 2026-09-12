# Feature Idea: Import Legacy Attendance CSV into a New Class

## Goal

Let an instructor take an attendance export from the old system (CSV with
`Name, Gender, Tel, Date, Present, Absent, Permission, Reason`) and import it
into a class they've created in the new system, so historical attendance
isn't lost during migration.

## Why it's not a simple CSV → table insert

`student_attendances` requires a `student_enrollment_id`
(non-nullable FK, part of the unique key
`(study_class_id, student_enrollment_id, attendance_date)` — see
`database/migrations/2026_08_13_000001_create_student_attendances_table.php:14,22`).
So the import can't skip straight to attendance rows. For each CSV row it
must resolve, in order:

1. **Student** — an existing `students` row, or a newly created one
2. **Enrollment** — an existing `student_enrollments` row linking that
   student to the target class, or a newly created one
   (`app/Modules/EnRoll/Actions/EnrollStudent.php:20-45` is the existing
   entry point for creating one)
3. **Attendance** — one `student_attendances` row per CSV date, with
   `status` set to `present` / `absent` / `permission` (all three are
   already valid values elsewhere in the codebase — see
   `app/Modules/Attendance/Services/AttendanceQrService.php:292,319` and
   `app/Models/AttendanceRule.php:16`) and `note` from the CSV's `Reason`
   column.

## Student matching: by phone number

Per your call — match each CSV row to an existing `students` row via the
`phone` column.

Caveats to design around, found while checking the schema:

- **`students.phone` has no unique constraint** (confirmed — no unique index
  on `phone` or `full_name` in any migration). Matching is a lookup, not a
  guaranteed 1:1 join — duplicates are possible and must be handled
  (surface them for manual pick rather than silently taking the first hit).
- **Phone formatting is inconsistent in the export itself** — e.g.
  `"096 555 3546"`, `" 0884482946"` (leading space), `011416703` vs a
  10-digit mobile format. Normalize both sides (strip spaces/dashes,
  compare digits-only) before comparing.
- **No match found** → don't silently skip. Two options, pick one:
  - Create a new `Student` row from the CSV's `Name`/`Gender`/`Tel`, or
  - Queue it for the instructor to resolve manually (link to an existing
    student they know is a rename, or confirm "yes, create new").
  Given phone numbers can legitimately change between old and new system
  records, a lightweight manual-review step for unmatched rows is worth
  keeping even though the primary path is automatic phone matching.

## Proposed flow

1. **Upload** — instructor picks the class, uploads the CSV.
   Note: file uploads are currently disabled/unwired in a few other
   features in this codebase (see `// FILE: disabled` comments in
   `WebsiteContentService.php`, `UpdateUserData.php`,
   `InstructorProfileService.php`) — this feature would need its own
   storage/validation wiring, it can't piggyback on existing plumbing.
2. **Parse & preview** — parse CSV server-side (no CSV/Excel library is
   currently in `composer.json`; add a small one, e.g. `league/csv`, rather
   than pulling in a full Maatwebsite/PhpSpreadsheet dependency for a
   plain-CSV need), normalize phone numbers, attempt matches, and show the
   instructor a preview table before committing anything:
   - Row matched to existing student → shown as such
   - Row unmatched → flagged, instructor chooses "create new student" or
     manually links to an existing one
   - Any date collisions with attendance already recorded for that
     class+date → flagged (unique key will reject silently-conflicting
     rows otherwise)
3. **Commit** (on confirm) — inside a transaction per student:
   - Find-or-create `Student`
   - Find-or-create `StudentEnrollment` for (student, target class) —
     reuse `EnrollStudent` action where possible so seat-capacity/duplicate
     checks stay consistent with normal enrollment
   - Bulk-insert `StudentAttendance` rows, mapping
     `Present→present / Absent→absent / Permission→permission`,
     `Reason→note`
4. **Result summary** — how many students matched vs. created, how many
   attendance rows inserted vs. skipped (already existing), any rows that
   still need manual attention.

## Open question worth deciding before building

Imported dates are historical and predate the class's own
`class_sessions` generation (`app/Modules/Attendance/Actions/
GenerateClassSessions.php`). `student_attendances` has no FK to
`class_sessions`, so nothing breaks at the DB level — but it's worth
confirming with whoever owns reporting/dashboards whether attendance rate
calculations, digests, etc. expect a `class_sessions` row to exist for
every attendance date, or whether they aggregate `student_attendances`
directly. If the former, the import should also backfill matching
`class_sessions` rows for the imported dates.

## Rough scope estimate

- New: CSV upload UI + storage/validation wiring, parser + normalizer,
  preview/match-review screen, commit action, result summary
- Reused: `EnrollStudent` action, existing `StudentAttendance` model/status
  values
- New dependency: a CSV parsing library (`league/csv` recommended — small,
  no Excel/binary format overhead for a plain CSV need)
