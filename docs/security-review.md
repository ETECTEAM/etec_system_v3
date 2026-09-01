# Security Review

**Scope:** authentication, routing/authorization, input handling, raw SQL, file uploads, secrets, session/cookie config, the CMS render path, and the production Docker/nginx/deploy setup.

**Date:** 2026-09-01 &nbsp;·&nbsp; **Branch:** `dev` &nbsp;·&nbsp; **Commit:** `b80237c`

**Overall:** a security-conscious codebase. The auth flow, rate limiting, broadcast channel authorization, proxy trust, and Telegram webhook are all done properly. One real hole, one moderate XSS surface, and a handful of hardening items.

---

## 🔴 High — Unauthenticated student + enrollment creation &nbsp;— ✅ FIXED (2026-09-01)

> `enroll.class-students.create` / `.store` were moved into the
> `['auth','active','role:super_admin|admin|instructor']` group with
> `throttle:20,1` on the POST; the controller now calls
> `ensureInstructorCanManageClassStudents()` (assigned teacher or co-instructor
> only). Delivered alongside the "register a student from the class list" feature.
> Original finding below for the record.

**Route:** `POST /dashboard/enroll/{studyClass}/students` → `EnrollmentClassController::storeStudent`
([app/Modules/Enroll/Controllers/EnrollmentClassController.php:465](../app/Modules/Enroll/Controllers/EnrollmentClassController.php#L465))

In [routes/web/backend/enroll.php:79-85](../routes/web/backend/enroll.php#L79-L85) this route pair sits **outside every `auth` group** — only the `/dashboard/enroll` prefix, no `auth`, no `throttle`. `StoreClassStudentRequest::authorize()` returns `true`
([app/Modules/Enroll/Requests/StoreClassStudentRequest.php](../app/Modules/Enroll/Requests/StoreClassStudentRequest.php)).

The GET form `createStudent` ([EnrollmentClassController.php:177](../app/Modules/Enroll/Controllers/EnrollmentClassController.php#L177)) redirects guests to the throttled `frontend.class-join.*` flow — but `storeStudent` (POST) has **no such guard**. A guest can POST directly, and `CreateClassStudent` ([app/Modules/Enroll/Actions/CreateClassStudent.php](../app/Modules/Enroll/Actions/CreateClassStudent.php)) → `createEnrollment` ([app/Modules/Enroll/Services/StudentRegistrationService.php:53](../app/Modules/Enroll/Services/StudentRegistrationService.php#L53)) creates a `Student` row plus an **`active`** enrollment — not `pending`; it skips the approval step the QR flow enforces.

### Impact

`{studyClass}` is a sequential numeric id, and `join-class/{id}` links are shared publicly so ids are known:

- Mass-create fake students + active enrollments in any class, fully automated (no throttle, no captcha, no device lock).
- `ensureClassHasSeat` is enforced → an attacker can **fill any class to capacity and lock out real students**.

### Fix

The success message ("Student added to class successfully") and redirect target indicate this is a staff tool. Move both routes inside an authenticated group:

```php
Route::middleware(['auth', 'active', 'role:super_admin|admin|instructor'])->group(function () {
    Route::get('/{studyClass}/students/create', [EnrollmentClassController::class, 'createStudent'])->name('enroll.class-students.create');
    Route::post('/{studyClass}/students', [EnrollmentClassController::class, 'storeStudent'])->name('enroll.class-students.store');
});
```

and add the instructor-ownership check (`ensureInstructorOwnsClass`) in `storeStudent`, like the sibling routes. If it genuinely must stay public, mirror `ClassJoinController` ([app/Modules/Website/Controllers/ClassJoinController.php](../app/Modules/Website/Controllers/ClassJoinController.php)): `throttle:` + `signed` URL or per-device lock + `createPendingEnrollment`.

---

## 🟠 Medium — Stored XSS surface via `v-html` on CMS content

- [resources/js/pages/frontend/pages/Show.vue:195,350](../resources/js/pages/frontend/pages/Show.vue#L195)
- [resources/js/components/frontend/pages/AboutLayout.vue:93](../resources/js/components/frontend/pages/AboutLayout.vue#L93)
- [resources/js/pages/backend/website/PageShow.vue:27](../resources/js/pages/backend/website/PageShow.vue#L27)

All render `pageData.content` as raw HTML, and the frontend ones serve **unauthenticated public visitors**.

Authoring is `role:super_admin|admin` only ([routes/web/backend/website.php:9](../routes/web/backend/website.php#L9)), so this isn't remotely exploitable — but one compromised or rogue admin session = persistent XSS on every public page view. Sanitize server-side on save (HTMLPurifier / `mews/purifier` with an allowlist) or client-side with DOMPurify before `v-html`.

The `v-html="link.label"` pagination cases ([Blacklist.vue:188](../resources/js/pages/backend/absence-blocks/Blacklist.vue#L188), [ClassList.vue:870](../resources/js/pages/backend/students/ClassList.vue#L870), etc.) are **safe** — Laravel-generated static strings.

---

## 🟡 Low / hardening

1. **Privilege fields mass-assignable** — `role`, `status`, `access_expires_at`, `created_by` are in `User::$fillable` ([app/Models/User.php:33](../app/Models/User.php#L33)). Safe today because every write goes through a validated DTO, but one `User::update($request->validated())` where the request validates a `role` field = privilege escalation. Remove them from `$fillable` and set explicitly.

2. **Session cookie** — `SESSION_SECURE_COOKIE` is unset → [config/session.php:172](../config/session.php#L172) resolves to `null` (auto). Set `SESSION_SECURE_COOKIE=true` in production. Consider `SESSION_ENCRYPT=true` since sessions are DB-stored.

3. **Prod `.env` discipline** — `.env` / `.env.example` ship `APP_DEBUG=true`, `APP_ENV=local`, `DB_PASSWORD=secret123`. `.env` is gitignored (good) and [deploy/deploy.sh:110](../deploy/deploy.sh#L110) runs `config:cache` — just make sure the prod `env_file` has `APP_DEBUG=false`, `APP_ENV=production`, a strong `APP_KEY` + DB creds before that cache is built.

4. **nginx headers** — [deploy/nginx/default.conf:25-26](../deploy/nginx/default.conf#L25-L26) sets `X-Frame-Options` + `X-Content-Type-Options` but not `Referrer-Policy`, `Content-Security-Policy`, or HSTS (add HSTS at the TLS-terminating `host-reverse-proxy.conf`). Also add the `always` flag so headers survive error responses and `location` blocks.

5. **`phone` validation** — `['required','string','max:20']` with no format rule across the registration requests; the value later lands in PDFs and Telegram messages. Add `regex:/^[0-9+\-\s()]{6,20}$/`.

6. **No `config/cors.php`** — relying on the framework default (`allowed_origins: ['*']`) for `api/*`. Fine while `api/public/*` is read-only and unauthenticated; publish and scope it before adding any authenticated API route.

---

## ✅ Done well (keep it this way)

- **Login** (`AuthController::loginWeb`): dummy-hash timing equalisation, generic errors, account status revealed only after password check, account-wide + IP lockout tiers, audit logging.
- **Telegram webhook**: `hash_equals`, fail-closed, throttled.
- **Broadcast channels** ([routes/channels.php](../routes/channels.php)): every channel authorizes role/ownership.
- **Proxy trust**: restricted to RFC1918 ranges + nginx `HTTP_X_FORWARDED_FOR` override → IP-keyed rate limiting can't be spoofed.
- Consistent `auth` + `active` + `role:` / `permission:` + `throttle:` on routes; compound-key rate limiters (email + IP).
- No injectable raw SQL (all `DB::raw` are static aggregates; the one valued `selectRaw` is parameterized); no `eval` / `unserialize`; the lone `shell_exec` uses `escapeshellarg` on a hardcoded list.
- `password` cast `hashed` + in `$hidden`; explicit `$fillable` allowlists everywhere; secrets via env / GitHub Secrets; `composer install --no-dev` on deploy.
- nginx: dotfile deny (except `.well-known`), rate-limit + connection-limit zones.
