#!/usr/bin/env bash
# Deploys code only: pulls the latest branch, refreshes composer/npm
# dependencies and rebuilds assets through the bind mount, recreates the
# application containers and warms caches.
#
# Database schema changes are NOT applied here. Migrations and seeders are
# run manually by the administrator when required - the script prints the
# exact commands at the end of every run.
#
# Run from the deploy directory on the VPS (e.g. /opt/etec-system), not from
# a dev machine.
#
# Usage: ./deploy/deploy.sh [branch]

set -euo pipefail

COMPOSE_FILE="docker-compose.prod.yml"
BRANCH="${1:-${DEPLOY_BRANCH:-production}}"

main() {
  # Everything below runs inside main() so a failure mid-way (notably during
  # git reset --hard) exits the whole script instead of continuing half-done.
  cd "$(git rev-parse --show-toplevel)"

  echo "==> Fetching latest ${BRANCH}"
  git fetch origin "${BRANCH}"
  git checkout "${BRANCH}"
  git reset --hard "origin/${BRANCH}"

  echo "==> Building runtime images (code is bind-mounted, images carry PHP/Node only)"
  docker compose -f "${COMPOSE_FILE}" build app reverb nginx

  echo "==> Installing composer/npm deps and building assets onto the host"
  docker compose -f "${COMPOSE_FILE}" run --rm --no-deps app sh -c \
    "composer install --no-dev --optimize-autoloader --no-interaction && npm ci && npm run build"

  echo "==> Recreating app, reverb, queue and scheduler so every long-running process loads the new code"
  docker compose -f "${COMPOSE_FILE}" up -d --force-recreate app reverb queue scheduler

  # Must run via `exec` against the now-recreated, long-running app container,
  # not `run --rm` - a `run --rm` container is a throwaway instance with its
  # own writable layer, so the bootstrap/cache/*.php files it writes vanish
  # the moment it exits and never reach the container that actually serves
  # traffic (app has no bind mount for /var/www here, so each container's
  # writable layer is independent even though they share the same image).
  echo "==> Warming config/route/view/event caches on the running app container"
  docker compose -f "${COMPOSE_FILE}" exec -T app sh -c \
    "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache"

  echo "==> Reloading Nginx configuration & re-resolving upstream IPs"
  docker compose -f "${COMPOSE_FILE}" up -d nginx
  docker compose -f "${COMPOSE_FILE}" exec -T nginx nginx -s reload || docker compose -f "${COMPOSE_FILE}" restart nginx

  echo "==> Pruning old images"
  docker image prune -f

  echo "==> Done. Current containers:"
  docker compose -f "${COMPOSE_FILE}" ps

  cat <<'EOF'

============================================================================
 DATABASE STEPS ARE MANUAL NOW - this deploy did NOT touch the database.

 Pending migrations (safe, additive):
   docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

 Seeders (WARNING: destructive - seeders truncate real tables first):
   docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force

 Only run these when you know the deploy includes schema/data changes.
============================================================================
EOF
}

main "$@"
