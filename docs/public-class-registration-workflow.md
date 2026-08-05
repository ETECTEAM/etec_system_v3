# Public Class Registration Workflow

This document explains the "Register" modal on the public `/classes` page — a
no-login self-registration flow where a prospective student signs up for a class
directly from the marketing site. It's a **third**, separate registration flow from
the two covered in `docs/registration-workflow.md` (instructor self-registration at
`/instructor-register` and the unrelated student `/register` form) — don't confuse
the three.

## Overview

```text
GET  /classes                          -> PublicPageController::classes (browse/filter)
POST /classes/{studyClass}/register    -> RegisterClassRequest validates input
  -> RegisterClassStudent::handle() (one DB transaction)
       -> lock the class row, re-check capacity
       -> create throwaway User + Student + StudentEnrollment (source: public_website)
       -> create a dashboard Notification (type: class_registration)
  -> NotificationsUpdated dispatched (dashboard badge/list refetch)
  -> redirect back with a flash success message

Admin follow-up (no approval step — the enrollment is already active):
  Dashboard "Registrations" tab -> GetPublicRegistrations query
  -> admin collects payment via POST /dashboard/enroll/enrollments/{enrollment}/deposit
```

There is no approval/verification step here, unlike the instructor flow — the
account and enrollment are created active immediately. The only thing still
"pending" afterward is payment.

## Step 1 — Browsing and the registration form

`PublicPageController::classes` (`app/Modules/Website/Controllers/PublicPageController.php:22`)
renders `frontend/classes/Index.vue` with paginated, filtered classes from
`WebsiteContentService::paginatedPublicClasses()`. Each class is presented with
`available_seats = max(capacity - current_students, 0)`; the "Register" button is
disabled client-side (shows "Class Full") once seats hit zero, but that's UX only —
the real check happens server-side in Step 2.

Clicking "Register" opens a modal (`registerModalOpen` in `Index.vue`) with an
Inertia `useForm({ name, gender, phone })` that posts to
`frontend.classes.register` (`POST /classes/{studyClass}/register`).

## Step 2 — Validation

`RegisterClassRequest` (`app/Modules/Website/Requests/RegisterClassRequest.php`)
authorizes everyone (`return true` — intentionally public, no login required) and
validates:

- `name` — required, string, max 255
- `gender` — required, one of `male`/`female`
- `phone` — required, string, max 20

No email is collected — the flow generates one (see Step 3).

## Step 3 — `RegisterClassStudent` creates the account and enrollment

`RegisterClassStudent::handle()` (`app/Modules/Website/Actions/RegisterClassStudent.php`),
inside one DB transaction:

1. Locks the `StudyClass` row (`lockForUpdate`) and re-counts active enrollments
   against `capacity`, throwing a validation error ("This class is full.") if it's
   already at capacity. This is the authoritative capacity check — the
   `available_seats` shown in the UI is just a snapshot that can go stale between
   page load and submit.
2. Creates a `User` (`role: student`, `status: active`) with a random password and a
   generated placeholder email (`student-{16 random chars}@etec.local` — retried
   until unique). The registrant never sees or sets this email or password; there's
   no login credential handoff in this flow.
3. Creates a `Student` row with the submitted `full_name`/`gender`/`phone`.
4. Creates a `StudentEnrollment`: `enrollment_status: active`, `payment_status:
   unpaid`, **`source: public_website`**, `fee_amount`/`document_fee_amount` copied
   from the class, `amount_paid: 0`.
5. Creates a dashboard `Notification` (`type: class_registration`) with the
   student's name and class title, purely informational.

After the transaction commits, `NotificationsUpdated::dispatch()` fires so any open
dashboard refetches the notification feed — see `docs/notification-workflow.md` for
how that broadcast/refetch mechanism works generally.

The controller then redirects back with a flash `success` message; the modal closes
on success (`onSuccess: () => closeRegisterModal()` in `Index.vue`).

### Related but different: the QR self-registration flow

`RegisterClassStudent` is a near-duplicate of `Enroll\Actions\CreateClassStudent`
(the flow behind an instructor's shareable "Generate QR" link, `POST
/dashboard/enroll/{studyClass}/students`) — same capacity-lock-then-create shape.
The differences that matter:

- QR flow leaves `source` `null`; this flow tags `public_website`. That tag is the
  only thing that makes a registration show up in the dashboard's "Registrations"
  tab (Step 4) — a QR signup does not appear there.
- This flow raises a `Notification` + `NotificationsUpdated` broadcast; the QR flow
  raises neither, so nothing pings the dashboard live for a QR signup.

If you're changing one of these two actions, check whether the other needs the same
change.

## Step 4 — The notification is informational only, not actionable

`class_registration` notifications show up in the bell/popup and
`/dashboard/notifications` like any other, but they carry no Approve/Reject
buttons: `NotificationController::approvalStatusFor()`
(`app/Modules/Notification/Controllers/NotificationController.php:166-179`) returns
`null` for any type other than `instructor_approval`, and `resolve()` (backing the
approve/reject endpoints) explicitly rejects acting on any notification whose type
isn't `instructor_approval`. So this notification is just a heads-up — the real
follow-up workflow is Step 5.

## Step 5 — Admin follow-up: the "Registrations" tab

`resources/js/pages/backend/students/ClassList.vue` has a `registrations` view mode
that calls `GET /dashboard/enroll/registrations/data`
(`EnrollmentClassController::publicRegistrations`, super_admin only), backed by
`GetPublicRegistrations` (`app/Modules/Enroll/Queries/GetPublicRegistrations.php`):

- Lists `StudentEnrollment` rows `where('source', 'public_website')`, latest first,
  capped at 50.
- Shapes each row with student name/gender/phone, class/course info, schedule,
  `fee_amount`/`document_fee_amount`/`amount_paid`, and `payment_status`.

This is where an admin actually acts on a public registration — not by
approving/rejecting an identity (there's no identity check in this flow), but by
collecting payment via `POST
/dashboard/enroll/enrollments/{enrollment}/deposit`, which updates `amount_paid`/
`payment_status` on the enrollment. The tab's unpaid count badge
(`registrations.value.filter(row => row.payment_status !== "Paid").length`) is
purely a client-side count of what's already loaded — it's not a separate
server-computed metric.

## Summary

| Step | Where | What happens |
|---|---|---|
| Browse | `PublicPageController::classes` | Public, filterable class listing |
| Submit | `RegisterClassRequest` | Validates name/gender/phone, no auth required |
| Create | `RegisterClassStudent` | Locks class, checks capacity, creates User+Student+StudentEnrollment (`source: public_website`), raises a `Notification` |
| Notify | `NotificationsUpdated` | Live-refetches the dashboard notification feed (informational only — no approve/reject) |
| Follow up | `GetPublicRegistrations` + deposit endpoint | Admin views/collects payment on public registrations from the "Registrations" tab |

Unlike the instructor flow, there is exactly one path through this one — no OTP, no
approval branching, no Telegram. The only asynchronous part is payment collection,
which happens entirely outside this flow.
