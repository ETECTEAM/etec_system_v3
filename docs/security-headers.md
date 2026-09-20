# Production security headers

The production Docker Nginx config (`deploy/nginx/default.conf`) sends CSP,
Referrer-Policy, Permissions-Policy, X-Frame-Options and X-Content-Type-Options
on successful responses, redirects and errors. It sends one-year HSTS when
the trusted host proxy forwards `X-Forwarded-Proto: https`. Keep the container
port bound to loopback as configured in `docker-compose.prod.yml`.

The HTTPS host proxy owns HSTS and the other static headers, hides their
upstream copies, and passes CSP through. Its existing HSTS policy includes
subdomains: use that setting only when all subdomains support HTTPS. Both
Nginx layers hide version numbers, and PHP's X-Powered-By is stripped.

## Policy compatibility

- Scripts allow same-origin assets, Cloudflare Turnstile, and the two pinned
  PDF-export library URLs. Inline scripts/event handlers and eval are blocked.
  The early theme initializer is now `/js/theme-init.js`.
- Inline styles remain allowed for Vue bindings, printing and UI libraries.
- Google Fonts, Font Awesome, Turnstile and the existing map frames are allowed.
- HTTPS images/media and local blob previews remain available for CMS content.
- Geolocation and camera are restricted to the app origin; microphone, payment,
  USB and browsing topics are disabled.
- Reverb uses `wss://$host` on port 443. If `VITE_REVERB_HOST` differs from the
  page hostname (including www versus apex), add its exact `wss://host[:port]`
  to `connect-src` before deploying. Rebuild assets after changing Vite env vars.
- This policy is for production builds. Local `artisan serve`/Vite development
  does not use the production Nginx config or its HTTPS upgrade directive.

The Turnstile allowlist follows [Cloudflare's CSP guidance](https://developers.cloudflare.com/turnstile/reference/content-security-policy/).
The `always` flag covers error responses as described in the
[Nginx header documentation](https://nginx.org/en/docs/http/ngx_http_headers_module.html).

## Deploy and verify

1. Deploy the code and assets through the normal deployment workflow. It reloads
   the container Nginx config. Ensure `public/js/theme-init.js` is included.
2. Merge the security-header and `proxy_hide_header` changes from
   `deploy/nginx/host-reverse-proxy.conf` into the **active** host HTTPS server
   block. Keep the real domain, certificate paths and existing routing. The
   template file is not automatically installed by an application deployment.
   Keep `proxy_set_header X-Forwarded-Proto $scheme` in the proxy location.
3. Validate each Nginx configuration before reloading it:

   ```bash
   docker compose -f docker-compose.prod.yml exec -T nginx nginx -t
   docker compose -f docker-compose.prod.yml exec -T nginx nginx -s reload
   sudo nginx -t && sudo systemctl reload nginx
   ```

4. Inspect public responses (GET, following redirects), then run a fresh
   SecurityHeaders.com scan:

   ```bash
   curl -sS -L -D - -o /dev/null https://www.etec.space/student-register
   curl -sS -D - -o /dev/null https://www.etec.space/js/theme-init.js
   ```

   Expect all six scored headers, one copy of each, no `X-Powered-By`, and no
   Nginx version. Also check an error response and the initial HTTP redirect.
   HSTS belongs on HTTPS responses. Host-generated errors retain static security
   headers but do not receive the upstream application CSP.
5. In a browser, exercise registration/Turnstile, login, attendance geolocation,
   map embeds, notification WebSockets, uploads/previews and PDF/certificate
   printing. Check the console for CSP violations. Add only the specific origin
   needed for an actual integration; do not enable unsafe-inline/unsafe-eval
   for scripts to silence errors.

A+ is a target, not a verified result until the deployed public response has
been rescanned. Cookie-prefix suggestions are separate from the six headers;
renaming a session cookie would log existing users out and is not part of this change.
