# Idea: Restrict `/student-register` to One Area/School Only

## Goal

`/student-register` ([routes/web/frontend/class_data.php:7-12](routes/web/frontend/class_data.php#L7-L12)) is a public, unauthenticated route — no login required, open to anyone who has the URL. You want it so that only people physically at your school/area can open it; other branches/areas should not be able to load it at all.

## Important: there's already a location-lock system in this codebase, but it doesn't cover this page

The app already has a full GPS-based "location lock" feature (`app/Modules/AccessLocation/`) — worth knowing about before building something new, so you don't duplicate it or get confused by it:

- **Middleware**: [EnforceLocationAccess.php](app/Http/Middleware/EnforceLocationAccess.php) — when the feature is on, it checks the browser's GPS against admin-defined locations (`access_locations` table: coordinates + radius) before letting a request through.
- **Config**: [config/access-location.php](config/access-location.php) — session TTL for a GPS check, accuracy tolerance, bypass roles, etc. Managed live from `/dashboard/access-locations`.
- **Catalog of lockable routes**: [LockableRoutes.php](app/Modules/AccessLocation/Support/LockableRoutes.php) — an admin can only lock routes that are listed here.

**The catch — two things rule it out as-is for your case:**

1. It only ever runs for logged-in users: `EnforceLocationAccess.php:27-31` returns early with `if (! $user || ! $this->gate->featureEnabled())`. A guest hitting `/student-register` skips the check entirely.
2. Every pattern in `LockableRoutes::catalog()` is a `dashboard/*` path. There's actually a key literally named `student_register` in that catalog already ([LockableRoutes.php:29-36](app/Modules/AccessLocation/Support/LockableRoutes.php#L29-L36)) — **but it locks `/dashboard/enroll/students`, the admin's manual pre-registration form, not the public self-service page at `/student-register`.** Don't confuse the two; they're unrelated despite the similar name.

So today, nothing in the codebase restricts the public registration page by location. Two realistic ways to add it:

---

## Option A (recommended): IP/CIDR allowlist middleware

Simplest fit if "my area" means "on my school's own network/WiFi." No browser permission prompts, no extra UI for the student filling the form.

**How it would work:**
- New middleware, e.g. `RestrictToSchoolNetwork`, checks `$request->ip()` against a configured allowlist of IPs/CIDR ranges (your school's fixed public IP, or your router/WiFi gateway's public IP).
- Applied only to the two `/student-register` routes (GET + POST).
- Allowlist lives in `.env`/config so it can be updated without a code change if your ISP IP changes.

**Why this will actually work reliably here**: the app already trusts its reverse proxy correctly — [bootstrap/app.php:49-53](bootstrap/app.php#L49-L53) restricts `trustProxies` to the private Docker network (nginx), so `$request->ip()` resolves the *real* client IP via `X-Forwarded-For`, not the proxy's own IP. That's exactly what this middleware would need.

**Caveats to know going in:**
- Only works for devices actually on your school's network. A student on mobile data (not WiFi) would get a different public IP and be blocked too — worth deciding if that's acceptable, or if you want to allow mobile carrier ranges too (usually impractical — carrier IPs are huge and shared).
- Needs a static or otherwise known public IP for the location. Most home/small-office ISPs hand out dynamic IPs — check with your ISP or use a router that supports Dynamic DNS if your IP isn't fixed.
- If you ever put a CDN/WAF (e.g. Cloudflare) in front of nginx, you'd need to extend `trustProxies` and use the CDN's real-client-IP header, or this breaks silently.

**Sample shape:**

```php
// app/Http/Middleware/RestrictToSchoolNetwork.php
public function handle(Request $request, Closure $next): Response
{
    $allowed = (array) config('school-access.allowed_cidrs'); // e.g. ['203.0.113.0/24']

    if (! collect($allowed)->contains(fn ($cidr) => IpUtils::checkIp($request->ip(), $cidr))) {
        abort(403, 'Registration is only available on-site.');
    }

    return $next($request);
}
```

```php
// routes/web/frontend/class_data.php
Route::middleware('restrict.school-network')->group(function () {
    Route::get('/student-register', [StudentRegisterController::class, 'create'])->name('frontend.student-register.create');
    Route::post('/student-register', [StudentRegisterController::class, 'store'])->name('frontend.student-register.store');
});
```

(`Symfony\Component\HttpFoundation\IpUtils::checkIp()` already ships with Laravel's dependencies — handles CIDR matching, no new package needed.)

---

## Option B: Extend the existing GPS location-lock system to cover guests — CHOSEN

Better fit here since school WiFi is slow/unreliable — GPS works over mobile data too, so registration isn't dependent on network quality. Reuses the admin UI you already have for managing locations/radius at `/dashboard/access-locations`. I checked the actual code paths (not just the models above) to confirm exactly what needs to change — this is real multi-file work, not a config flip. Concrete findings:

1. **`EnforceLocationAccess.php:27-31`** bails immediately with `if (! $user || ! $this->gate->featureEnabled())`. This line needs to stop short-circuiting on `! $user` — guests must still go through the location check. The `bypass_roles` loop right after it (lines 44-48) calls `$user->hasRole($role)`, which would need to be guarded with `if ($user)` since a guest has no roles to check.

2. **`routes/web/backend/access-location.php:25-33`** — the entire `/dashboard/location/gate` + `/dashboard/location/verify` group is wrapped in `['auth', 'active']`. A guest hitting `/student-register` literally cannot reach this interstitial today — it 302s to login first. **This means Option B needs a second, public route group** (e.g. `/student-register/location-check` + `/student-register/location-check/verify`) with no `auth` middleware, not a tweak to the existing one — keeping the staff-facing dashboard gate untouched and unregressed.

3. **`LocationGateController::verify()`** hardcodes `'user_id' => $request->user()->id` when writing to `location_access_logs` — that's a null-pointer crash for a guest. Low-risk fix though: I checked the migration ([2026_08_29_000003_create_location_access_logs_table.php](database/migrations/2026_08_29_000003_create_location_access_logs_table.php)) and `user_id` is **already nullable** at the DB level (`->nullable()->constrained()->nullOnDelete()`), so this is just `$request->user()?->id` in the controller — no migration needed.

4. **`LockableRoutes::catalog()`** — add a new entry for the real public path, e.g. `public_student_register` → patterns `['student-register', 'student-register/*']`. Don't reuse the existing `student_register` key ([LockableRoutes.php:29-36](app/Modules/AccessLocation/Support/LockableRoutes.php#L29-L36)) — that one is already wired to `/dashboard/enroll/students` (a different, admin-only route) and repurposing it would silently change what that existing lock covers.

5. **Frontend**: `LocationGateController::show()` renders `backend/location/Gate` — almost certainly styled with dashboard chrome (sidebar/header for logged-in staff), wrong for an anonymous student on their phone. Needs a lightweight public equivalent, e.g. `frontend/student-register/LocationGate.vue`, reusing the same "ask for GPS, POST to verify, redirect on success" logic but with public-page styling.

6. **Session mechanics already just work for guests** — Laravel's session cookie applies to unauthenticated visitors too, so the `location_gate` session stamp + `session_ttl` freshness check in `EnforceLocationAccess.php:56-65` needs no changes.

**Decided — GPS denied/inaccurate = hard block.** If a student declines the location permission, or their GPS fix doesn't match an approved location, they simply cannot submit the form — show a clear message ("Registration is only available on-site — please enable location access and try again") and stop there, no fallback path. Mirrors the existing staff-facing `deny()` behavior in spirit, just with public-facing copy instead of a redirect to `/dashboard`.

**Caveats to keep in mind:**
- Requires the student to grant browser location permission on first load — some will decline it, hence the open question above.
- GPS can be spoofed on rooted/jailbroken or dev-mode devices — same limitation the existing staff-facing feature already accepts, not a new gap you're introducing.
- Accuracy near the edges of the configured radius can be noisy indoors — the existing `min_accuracy_slack` config ([config/access-location.php](config/access-location.php)) already accounts for this by rejecting low-accuracy fixes rather than guessing.

---

## Concrete file-change list for Option B

| File | Change |
|---|---|
| `app/Http/Middleware/EnforceLocationAccess.php` | Stop bailing on `! $user`; guard the bypass-roles loop with `if ($user)`; when sending an unauthenticated request to the gate, redirect to the new public gate route instead of `route('location.gate')` |
| `routes/web/frontend/class_data.php` (or a new `routes/web/frontend/location-gate.php`) | New unauthenticated route group: `GET /student-register/location-check`, `POST /student-register/location-check/verify` (throttled) |
| New controller, e.g. `app/Modules/AccessLocation/Controllers/PublicLocationGateController.php` | Guest-safe version of `show()`/`verify()`; `verify()` uses `$request->user()?->id` for the log write |
| `app/Modules/AccessLocation/Support/LockableRoutes.php` | New catalog entry `public_student_register` → `['student-register', 'student-register/*']` |
| New Vue page, e.g. `resources/js/pages/frontend/student-register/LocationGate.vue` | Public-styled GPS interstitial, mirrors `backend/location/Gate.vue`'s request/verify flow |

Nothing about `AccessLocationGate.php` (the core matching service — distance/radius math against `access_locations`) needs to change; it's already request-driven, not user-driven, so it's reusable as-is.

---

## Practical steps checklist (Option B)

1. Decide the guest-denied-GPS behavior (see open question above) — affects both backend response and frontend copy.
2. Implement the middleware, route, controller, and catalog changes above.
3. Build the public location-gate page.
4. In `/dashboard/access-locations`, tick the new "Register Student (public)" entry once it appears in the lockable-routes checklist, and confirm your school's coordinates + radius are set there (this is admin-configured data, not something hardcoded — you'll set the actual lat/long/radius through that existing screen).
5. Test end-to-end from on-site (should pass), and from off-site on both WiFi and mobile data (should be blocked with a clear message either way, since this checks GPS not network).
6. Confirm the existing staff-facing `/dashboard/location/gate` flow still works unchanged — this work must not touch that path's behavior.
