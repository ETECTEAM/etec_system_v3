# VPS Sizing for Deployment

## Stack Summary

- **Backend:** Laravel (PHP `^8.2`), Inertia + Vue frontend built with Vite
- **Database:** MySQL 8
- **Queue:** `sync` (no background workers currently)
- **Cache / Session:** `file` driver (no Redis currently)
- **Real-time:** Laravel Reverb (Pusher-compatible websocket server) for live
  dashboard notifications - a persistent process, not just a request-time
  dependency; see the Deployment Note below
- **Other:** Telegram webhook integration, notification system

This is an internal admin / school-management control panel rather than a
public high-traffic app, which keeps requirements modest.

## Chosen Provider: Contabo Cloud VPS 10

| Resource | Spec |
|---|---|
| vCPU | 4 |
| RAM | 8 GB |
| Storage | 75 GB NVMe (free) or 150 GB SSD (free) |
| Port | 200 Mbit/s |

This is above the minimum baseline this app needs (2 vCPU / 4GB would run it
comfortably), so it leaves solid headroom for growth — adding Redis, running
queue workers, or scaling to several hundred concurrent users — without
needing to resize later.

### Storage type: NVMe vs SSD

Pick **75 GB NVMe** over 150 GB SSD. MySQL is the component most sensitive to
disk I/O latency, and NVMe's faster random read/write directly speeds up
query performance under load. 75 GB is far more than this app's DB + code +
uploads will need; the extra 75GB on the SSD tier isn't worth trading away
NVMe speed for.

### Region

Pick based on where actual users (students/staff) are, not the "Best"
default. The latency numbers shown (Singapore 124ms, Japan 153ms, India
138ms) are measured from your current machine, not from your users' location
— re-check from a connection near where the system will actually be used
before deciding. If most users are in Cambodia/SE Asia, **Singapore** is the
right pick among the options offered.

### Image

**Ubuntu** (included, free) — no need for cPanel/Plesk/Windows/RHEL since
deployment is via Docker Compose, not a control panel.

### Backup

Take the **Auto Backup add-on ($1.85/mo)**. This system holds student/exam
data with no other backup mechanism configured yet (no offsite MySQL dumps,
no S3/object storage backups). Given the cost is negligible relative to the
VPS price, there's no good reason to skip it.

### Other add-ons

- **Private Networking:** not needed — single-box deployment, no internal
  service-to-service traffic to isolate.
- **Object Storage:** not needed unless file/media uploads grow large enough
  to want offloading from local disk — skip for now.
- **Monitoring:** optional; skip initially and add later if uptime/alerting
  becomes a concern.

## Trade-offs

- **Going smaller (1 vCPU / 2GB):** MySQL and PHP-FPM workers compete for
  RAM. Build frontend assets (`npm run build`) on your own machine or in CI
  rather than on the VPS — a Vite/npm build can spike past 2GB on its own.
- **Expecting heavier concurrent load:** Add Redis for cache/queue and move
  `QUEUE_CONNECTION` off `sync`. The 4 vCPU / 8GB tier already has headroom
  for this without a resize.

## Deployment Note

The repo's `docker-compose.yml` targets `dev` (runs `composer run dev` and the
Vite dev server, live-mounts the working directory) and is for local
development only - do not use it as-is on the VPS.

For production, use `docker-compose.prod.yml`, which builds the `production`
Dockerfile stage:

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

That stage:

- Builds frontend assets ahead of time (`npm run build` in a throwaway
  `frontend-build` stage) rather than running the Vite dev server.
- Runs PHP-FPM (`php:8.3-fpm-bookworm`) behind the nginx config in
  `deploy/nginx/default.conf` (rate limiting, `.php` proxying to `app:9000`),
  instead of the dev container command.
- Copies the app code in at build time (respecting `.dockerignore`, so
  `docs/`, `tests/`, `.git/` never enter the image) - no bind mount, so a
  container restart can't be affected by uncommitted host changes.
- Persists only runtime state (`storage/`, MySQL data, and the built
  `public/` directory shared with nginx) via named volumes, not the whole
  repo.
- Does not include `phpmyadmin` - exposing a DB admin UI on a production box
  is an unnecessary attack surface. Use an SSH tunnel to `mysql:3306` instead
  if you need direct DB access.
- Runs a `reverb` service (same production image, `php artisan reverb:start`)
  for live dashboard notifications. It has no published host port - nginx
  proxies `/app` to `reverb:8080` internally (see `deploy/nginx/default.conf`),
  so only 80/443 are ever exposed publicly, same as everything else.

Still needed before a real deploy:

- **TLS** - nginx currently serves plain HTTP on port 80 (put Certbot/Let's
  Encrypt or a TLS-terminating reverse proxy in front). Once added, `REVERB_SCHEME`/`VITE_REVERB_SCHEME` need to move to `https` and
  `REVERB_PORT`/`VITE_REVERB_PORT` to `443` in `.env` - the browser connects
  to Reverb directly by these values, so they must match whatever the public
  URL actually uses, not just the app's own `APP_URL`.
- **Migrations** - run manually after each deploy rather than automatically
  on container start: `docker compose -f docker-compose.prod.yml exec app
  php artisan migrate --force`.
- **Reverb secrets** - `REVERB_APP_ID`/`REVERB_APP_KEY`/`REVERB_APP_SECRET` in
  `.env` are generated per-environment by `php artisan reverb:install` - never
  copy dev's values into production.

## Open Question

Sizing above assumes light-to-moderate concurrent usage. If you expect
significantly more (e.g. hundreds of students hitting the system at once
rather than a handful of staff), the current 4 vCPU / 8GB spec already
covers that tier — just make sure Redis + queue workers are added at that
point rather than staying on `sync`/`file` drivers.
