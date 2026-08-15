#!/usr/bin/env bash
# Pulls the latest production branch, rebuilds images, and rolls the stack
# forward with a migration + cache-warm step. Run from the deploy directory
# on the VPS (e.g. /opt/etec-system), not from a dev machine.
#
# Usage: ./deploy/deploy.sh

set -euo pipefail

COMPOSE_FILE="docker-compose.prod.yml"
BRANCH="dev"

# Everything runs inside this function, called only at the very bottom. Bash
# parses a function body fully into memory the moment it's defined, so the
# `git reset --hard` below - which overwrites this very file - can't corrupt
# execution the way it would if these commands ran inline at the top level
# (bash reads a running script from disk as it goes; rewriting it mid-run,
# especially to a different length, can make it jump to the wrong byte offset
# and skip or garble whatever comes next).
main() {
  cd "$(git rev-parse --show-toplevel)"

  echo "==> Fetching latest ${BRANCH}"
  git fetch origin "${BRANCH}"
  git checkout "${BRANCH}"
  git reset --hard "origin/${BRANCH}"

  echo "==> Building images (composer install --no-dev + npm run build happen inside the Dockerfile)"
  docker compose -f "${COMPOSE_FILE}" build app reverb nginx

  echo "==> Starting mysql first so migrations have something to run against"
  docker compose -f "${COMPOSE_FILE}" up -d mysql
  docker compose -f "${COMPOSE_FILE}" exec -T mysql sh -c \
    'until mysqladmin ping -h localhost -u root -p"$MYSQL_ROOT_PASSWORD" --silent; do sleep 2; done'

  echo "==> Running migrations"
  docker compose -f "${COMPOSE_FILE}" run --rm app php artisan migrate --force

  echo "==> Recreating app, reverb with the new images"
  docker compose -f "${COMPOSE_FILE}" up -d app reverb

  # app/reverb bind-mount the repo (.:/var/www — see docker-compose.prod.yml),
  # so the git reset above already updated the code those containers see; no
  # rebuild needed for that part. But opcache.validate_timestamps=0 (deploy/php/
  # opcache.ini) means PHP-FPM keeps serving whatever it already compiled into
  # shared memory regardless — `up -d` above only recreates a container when
  # Compose thinks its image/config changed, which most deploys don't touch, so
  # without an explicit restart here PHP-FPM's opcache never learns the code
  # under it moved and the site keeps serving the previous deploy indefinitely.
  echo "==> Restarting app, reverb to clear opcache and pick up the new code"
  docker compose -f "${COMPOSE_FILE}" restart app reverb

  # `up -d` only recreates nginx when nginx's own image/config changed - but app
  # and reverb just got new container IPs above, and nginx caches their resolved
  # IP for its whole process lifetime. Without this, nginx keeps proxying to the
  # old (now-gone) IP and every request 502s until someone notices and manually
  # restarts it.
  echo "==> Restarting nginx so it re-resolves app/reverb's current IPs"
  docker compose -f "${COMPOSE_FILE}" up -d nginx
  docker compose -f "${COMPOSE_FILE}" restart nginx

  echo "==> Warming config/route/view caches"
  docker compose -f "${COMPOSE_FILE}" exec -T app php artisan config:cache
  docker compose -f "${COMPOSE_FILE}" exec -T app php artisan route:cache
  docker compose -f "${COMPOSE_FILE}" exec -T app php artisan view:cache

  echo "==> Pruning old images"
  docker image prune -f

  echo "==> Done. Current containers:"
  docker compose -f "${COMPOSE_FILE}" ps
}

main "$@"
