# Notification Workflow

This document explains how a pending instructor registration reaches admins/super_admins through two channels — Telegram and the dashboard notification feed — and how approvals from either channel resolve the same user.

## Overview

```text
Registration
  -> User + OtpVerification created (one DB transaction)
  -> PendingUserRegistered event dispatched
       -> SendTelegramAdminApproval        (Telegram push)
       -> CreateAdminApprovalNotification  (dashboard row)
  -> Admin approves/rejects via either channel
       -> UserApprovalService::approve()/reject()  (single source of truth)
```

## Step 1 — Registration creates the User + OTP

`AuthController::registerWeb` (`app/Modules/Auth/Controllers/AuthController.php:63-113`) runs inside one DB transaction:

1. Creates the `User` (`role: instructor`, `status: pending`) and its `InstructorData`.
2. Calls `OtpService::createForUser($user)` (`app/Modules/Auth/Services/OtpService.php:17`), which generates a random 6-digit code and stores only `Hash::make($plainCode)` on a new `OtpVerification` row. The plain code is never persisted — it only exists in memory from this point on.

## Step 2 — One event, dispatched once

After the transaction commits, the controller dispatches:

```php
PendingUserRegistered::dispatch($user, $otp, $plainCode);
```

The event is a plain data carrier (`app/Modules/Auth/Events/PendingUserRegistered.php`) — it does no work itself.

## Step 3 — Two listeners handle the event independently

Registered in `app/Providers/EventServiceProvider.php`:

```php
PendingUserRegistered::class => [
    SendTelegramAdminApproval::class,
    CreateAdminApprovalNotification::class,
],
```

Both implement `ShouldQueue`. With `QUEUE_CONNECTION=sync` they run inline, one after the other.

### 3a. `SendTelegramAdminApproval` (Telegram channel)

`app/Modules/Auth/Listeners/SendTelegramAdminApproval.php` calls `TelegramService::sendAdminApprovalRequest($user, $otp, $plainCode)`, which:

- Reads `telegram.admin_chat_id` and the bot token from config; silently logs a warning and returns if either is missing.
- Locks on cache key `telegram:approval-request:{otp->id}` for a day so the same OTP is never sent twice.
- Calls the Telegram Bot API `sendMessage` with the user's name/email and the **plain OTP code** in the message text, plus an inline keyboard:
  - `Approve` → `callback_data: approve:{otp_id}`
  - `Reject` → `callback_data: reject:{otp_id}`

### 3b. `CreateAdminApprovalNotification` (dashboard channel)

`app/Modules/Auth/Listeners/CreateAdminApprovalNotification.php` writes one row to the `notifications` table:

```php
Notification::create([
    'title' => 'New Instructor Registration',
    'message' => "{$user->name} ({$user->email}) — verification code: {$plainCode}",
    'type' => 'instructor_approval',
    'otp_verification_id' => $otp->id,
]);
```

Same OTP code, same underlying `OtpVerification` row — just a second surface for it.

Alongside creating the row, this listener (and every action listed in Step 5) also dispatches `App\Modules\Notification\Events\NotificationsUpdated`, an `ShouldBroadcastNow` event broadcast on a private `admin-notifications` channel (authorized in `routes/channels.php` for `super_admin`/`admin` only). The event carries no payload on purpose - it's just a "something changed" nudge, so the client always refetches `GET /notifications/data` rather than trusting a second, duplicated serialization of the same data.

## Step 4 — Dashboard picks it up live (Reverb), with polling as a fallback

The app runs a Laravel Reverb websocket server (Pusher-compatible protocol). Both `resources/js/layouts/DashboardHeader.vue` and `resources/js/pages/backend/notifications/Index.vue` independently do, on mount (if the user is `super_admin`/`admin`):

```js
getEcho().private('admin-notifications').listen('.notifications.updated', fetchNotifications)
```

`resources/js/echo.js` lazily creates a single shared Echo/Pusher-js client (so pages that never touch notifications - students, instructors - never open a websocket at all). When the event fires, connected dashboards refetch almost instantly instead of waiting on a timer.

A 60-second `setInterval` poll still runs alongside this as a safety net in case the websocket connection drops without reconnecting - not the primary delivery mechanism anymore, just a backstop.

Because both components can be mounted at the same time (the header is part of the layout wrapping the notifications page) and both subscribe to the same channel independently, cleanup on unmount uses `channel.stopListening(event, callback)`, not `echo.leave(channel)` - `leave()` would drop the *entire* channel subscription, including the other still-mounted component's listener.

`NotificationController::getNotificationData` (`app/Modules/Notification/Controllers/NotificationController.php:37-64`):

- Loads the latest 20 `Notification` rows, eager-loading `otpVerification.user`.
- Derives `approval_status` per row **live from the linked user's current status** rather than a stored flag:

  ```php
  UserStatus::Rejected => 'rejected',
  UserStatus::Active   => 'approved',
  default               => 'pending',
  ```
- Returns `{ unread_count, data: [...] }`. The header bell badge shows `unread_count`; the popup and the full `/dashboard/notifications` page both list notifications and only show Approve/Reject buttons when `approval_status === 'pending'`.

## Step 5 — Resolving from either channel

Two independent action paths converge on one service call.

**Telegram** — admin taps a button → `TelegramWebhookController` (`app/Modules/Auth/Controllers/Telegram/TelegramWebhookController.php`) parses `callback_data`, loads the `OtpVerification` + `user`, and calls:

```php
$approvalService->approve($user, null, 'telegram');
// or
$approvalService->reject($user, null, 'telegram');
```

Telegram resolves the `User`/`OtpVerification` directly and has no idea the dashboard's copy of this request even exists, so `syncDashboardNotification()` marks that `Notification` row `is_read = true` and dispatches `NotificationsUpdated` afterward - otherwise it would stay stuck "unread" forever, and any admin watching the dashboard wouldn't see the resolution until the 60s fallback poll.

**Dashboard** — admin clicks a button in the popup or on the notifications page → `POST /notifications/{id}/approve` or `/reject` → `NotificationController::resolve()` (`app/Modules/Notification/Controllers/NotificationController.php:76-112`) loads `$notification->otpVerification->user`, guards against an already-rejected user, then calls:

```php
$this->approvalService->approve($user, $request->user()->id, 'dashboard');
// or
$this->approvalService->reject($user, $request->user()->id, 'dashboard');
```

marks the notification `is_read = true`, and dispatches `NotificationsUpdated`.

Both paths land in `UserApprovalService::approve()` (`app/Modules/User/Services/UserApprovalService.php:17-33`):

```php
$user->forceFill([
    'status' => UserStatus::Active,
    'is_active' => true,
    'verified_at' => $user->verified_at ?? now(),
    'email_verified_at' => $user->email_verified_at ?? now(),
])->save();

$this->markLatestOtpVerified($user); // force-fills the OTP row's verified_at
```

Whichever channel acts first wins. Because `approval_status` is computed live from the user's `status`, the *other* channel's next poll automatically reflects the resolution — there's no per-channel state to keep in sync.

## Step 6 — The registrant's own path (parallel, optional)

If the registrant is given the code directly (an admin would need to relay it from Telegram or the dashboard — there is no email/SMS delivery to the registrant), they can enter it at `/code-verify`:

```text
AuthController::verifyCodeApi
  -> OtpService::verify($user, $code)
  -> UserApprovalService::approve($user, null, 'otp')
```

This is the same terminal call as the other two paths, just reached a third way.

## Summary

| Channel | Trigger | Entry point | Resolves via |
|---|---|---|---|
| Telegram | Admin taps Approve/Reject | `TelegramWebhookController` | `UserApprovalService` (`source: telegram`) |
| Dashboard | Admin clicks Approve/Reject | `NotificationController::approve/reject` | `UserApprovalService` (`source: dashboard`) |
| Self-service | Registrant enters the code | `AuthController::verifyCodeApi` | `UserApprovalService` (`source: otp`) |

One registration produces one event, two notification channels, and three possible approval triggers — all funneling through a single `UserApprovalService` call that is the only place user state actually changes.
