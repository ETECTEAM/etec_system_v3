# Attendance QR Scan Feature

## Summary

The system supports a **generate-and-scan QR check-in** flow for attendance. It is *not* a live in-app camera/barcode scanner (no `html5-qrcode`, `zxing`, or `react-qr-reader`). Instead:

- The **instructor/admin side generates** a QR code per class session using `qrcode.vue` (`^3.10.0`, see `package.json:24`).
- The **student side scans** it with their phone's native camera app, which opens a public web page — the "scanning" happens outside the app, in the OS camera/QR reader.

## Where QR codes are generated

| File | Notes |
|---|---|
| `resources/js/pages/backend/instructors/TrackAttendance.vue:695` | `<QrcodeCanvas :value="qrUrl">` — main per-session QR |
| `resources/js/pages/backend/students/List.vue:156` | QR rendering in student list context |
| `resources/js/components/ui/card/ClassCrad.vue:605,644` | QR shown on class card |
| `resources/js/components/.../ClassActionMenu.vue:271,309` | QR action from class menu |
| `resources/js/pages/backend/students/components/ReceiptPrint.vue:247` | QR on enrollment receipt — links to a public attendance **summary** view, not a check-in |
| `resources/js/pages/backend/attendance-settings/Edit.vue` | Settings UI related to QR attendance config |

## Scan / check-in flow

1. QR encodes a public URL: `GET /attendance/qr/{token}`.
2. Route defined in `routes/web/frontend/attendance.php:8` → `AttendanceQrController::show` (`app/Modules/Attendance/Controllers/AttendanceQrController.php:18-21`).
3. Controller renders the Inertia page `resources/js/pages/frontend/attendance/Scan.vue`.
4. Page captures:
   - GPS geolocation (`Scan.vue:48-61`)
   - A device identifier
5. Page submits via `axios.post` to the same URL.
6. `POST /attendance/qr/{token}` → `AttendanceQrController::store` (`AttendanceQrController.php:23-64`), route defined at `attendance.php:10`, guarded by throttle middleware `attendance-qr-submit`.
7. Controller validates lat/long/accuracy/device, then calls `AttendanceQrService::recordAttendanceFromQr` (`app/Modules/Attendance/Services/AttendanceQrService.php`).
8. Service records attendance and dispatches `AttendanceQrSubmitted`.

## Admin on/off control

`attendance.auto_record_allow_qr_attendance` (`resources/js/pages/backend/attendance-settings/Edit.vue`, "Enable QR attendance" toggle) is a **global master switch**:

- **ON** — instructors can start/use QR sessions for any class; students can scan and self-check-in.
- **OFF** — fully disabled everywhere, no exceptions (previously there was an "Internship classes always allowed" fallback — this was removed).

Enforced at every entry point via `AttendanceQrService::allowsQrAttendance()`:

| Entry point | File |
|---|---|
| Instructor auto-load session | `InstructorClassController::trackAttendance` (checks before rendering QR panel) |
| Instructor manual "Start session" | `InstructorClassController::startAttendanceSession` (explicit check added) |
| `AttendanceQrService::startSession` | Guards both callers above at the service layer too |
| Student scan page | `AttendanceQrService::publicViewData` → `Scan.vue` shows a "disabled" state |
| Student submit | `AttendanceQrService::recordAttendanceFromQr` (defense in depth) |

## Open questions / discussion points

- [ ] Should this be upgraded to an in-app camera scanner (e.g. via `html5-qrcode`) instead of relying on the phone's native camera app?
- [ ] Token lifetime / rotation for `{token}` — how long is a generated QR valid before it must be regenerated?
- [ ] Geolocation accuracy threshold — what `accuracy` value is rejected, and is spoofing a concern?
- [ ] Rate limiting on `attendance-qr-submit` — current throttle limits and whether they're sufficient against abuse.
- [ ] Relationship to `auto-record-attendance-workflow.md` and `instructor-attendance-block-proposal.md` — does QR check-in feed into the same auto-record pipeline?
