# VPS Sizing for Deployment

## Stack Summary

- **Backend:** Laravel (PHP `^8.2`), Inertia + Vue frontend built with Vite
- **Database:** MySQL 8
- **Queue:** `sync` (no background workers currently)
- **Cache / Session:** `file` driver (no Redis currently)
- **Other:** Telegram webhook integration, notification system

This is an internal admin / school-management control panel rather than a
public high-traffic app, which keeps requirements modest.

## Recommended Baseline

| Resource | Spec |
|---|---|
| vCPU | 2 |
| RAM | 4 GB |
| Storage | 60 GB SSD |

This comfortably runs Nginx + PHP-FPM + MySQL on a single box for roughly
**50-100 concurrent users**, with headroom for the Telegram webhook and
notification traffic already in the app.

## Trade-offs

- **Going smaller (1 vCPU / 2GB):** MySQL and PHP-FPM workers compete for
  RAM. Build frontend assets (`npm run build`) on your own machine or in CI
  rather than on the VPS — a Vite/npm build can spike past 2GB on its own.
- **Expecting heavier concurrent load:** Add Redis for cache/queue and move
  `QUEUE_CONNECTION` off `sync`. At that point, bump to **4 vCPU / 8GB**.

## Open Question

Sizing above assumes light-to-moderate concurrent usage. If you expect
significantly more (e.g. hundreds of students hitting the system at once
rather than a handful of staff), the spec should move up to the 4 vCPU / 8GB
tier with Redis from day one.
