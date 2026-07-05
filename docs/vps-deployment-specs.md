# VPS Sizing for Deployment

## Stack Summary

- **Backend:** Laravel (PHP `^8.2`), Inertia + Vue frontend built with Vite
- **Database:** MySQL 8
- **Queue:** `sync` (no background workers currently)
- **Cache / Session:** `file` driver (no Redis currently)
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

The repo's `docker-compose.yml` currently targets `dev` (runs `composer run
dev` and the Vite dev server, live-mounts the working directory). That's fine
for local development but should **not** be used as-is on the VPS. Production
deployment needs a separate compose/Dockerfile target that:

- Builds frontend assets ahead of time (`npm run build`) rather than running
  the Vite dev server.
- Runs PHP-FPM + Nginx (or `php artisan serve` behind a reverse proxy) instead
  of the dev container command.
- Does not bind-mount the whole working directory as a live volume.

## Open Question

Sizing above assumes light-to-moderate concurrent usage. If you expect
significantly more (e.g. hundreds of students hitting the system at once
rather than a handful of staff), the current 4 vCPU / 8GB spec already
covers that tier — just make sure Redis + queue workers are added at that
point rather than staying on `sync`/`file` drivers.
