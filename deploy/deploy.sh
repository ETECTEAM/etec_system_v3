#!/usr/bin/env bash
# Pulls the latest branch, refreshes composer/npm dependencies and rebuilds
# assets through the bind mount, rolls the stack forward with a migration +
# cache-warm step. Run from the deploy directory on the VPS (e.g.
# /opt/etec-system), not from a dev machine.
#
# Usage: ./deploy/deploy.sh [branch]
#   branch defaults to $DEPLOY_BRANCH, then "production". e.g.:
#     ./deploy/deploy.sh            # -> production
#     ./deploy/deploy.sh dev        # -> dev

set -euo pipefail

COMPOSE_FILE="docker-compose.prod.yml"
BRANCH="${1:-${DEPLOY_BRANCH:-production}}"

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

  # The Dockerfile's `npm run build` above runs inside the image, but app/nginx
  # both bind-mount the host repo over it at runtime (.:/var/www and ./public —
  # see docker-compose.prod.yml), so that built-in-the-image public/build/ is
  # immediately shadowed and never actually reaches anything that serves
  # traffic. Nginx keeps serving whatever public/build/ last existed on the
  # host, so dependencies + assets must be produced HERE, through the bind
  # mount, into the host working tree.
  #
  # This runs in a throwaway container from the freshly built image (which has
  # composer + node), writing vendor/, node_modules/ and public/build/ straight
  # onto the host repo via the mount. Doing it with `docker exec` against the
  # long-running app container instead is what produces "vite: not found" —
  # that container only has whatever deps were installed through the mount,
  # which before this step existed was: nothing.
  echo "==> Installing composer/npm deps and building assets onto the host (bind-mounted, so this is what nginx actually serves)"
  docker compose -f "${COMPOSE_FILE}" run --rm app sh -c \
    "composer install --no-dev --optimize-autoloader --no-interaction && npm ci && npm run build"

  echo "==> Starting mysql first so migrations have something to run against"
  docker compose -f "${COMPOSE_FILE}" up -d mysql
  docker compose -f "${COMPOSE_FILE}" exec -T mysql sh -c \
    'until mysqladmin ping -h localhost -u root -p"$MYSQL_ROOT_PASSWORD" --silent; do sleep 2; done'

  echo "==> Running migrations"
  docker compose -f "${COMPOSE_FILE}" run --rm app php artisan migrate --force

  echo "==> Recreating app, reverb, queue with the new images"
  docker compose -f "${COMPOSE_FILE}" up -d app reverb queue

  # app/reverb bind-mount the repo (.:/var/www — see docker-compose.prod.yml),
  # so the git reset above already updated the code those containers see; no
  # rebuild needed for that part. But opcache.validate_timestamps=0 (deploy/php/
  # opcache.ini) means PHP-FPM keeps serving whatever it already compiled into
  # shared memory regardless — `up -d` above only recreates a container when
  # Compose thinks its image/config changed, which most deploys don't touch, so
  # without an explicit restart here PHP-FPM's opcache never learns the code
  # under it moved and the site keeps serving the previous deploy indefinitely.
  # queue:work is a long-running CLI process that loads app code once at boot
  # and keeps running it from memory - same as app/reverb, it needs a restart
  # to ever see new code, opcache setting aside.
  echo "==> Restarting app, reverb, queue to clear opcache and pick up the new code"
  docker compose -f "${COMPOSE_FILE}" restart app reverb queue

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
