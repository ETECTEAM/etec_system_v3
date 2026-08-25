#!/usr/bin/env bash
# Production deploy script
# Usage: ./deploy/deploy.sh [branch] [--migrate]

set -euo pipefail

COMPOSE_FILE="docker-compose.prod.yml"
BRANCH="${1:-${DEPLOY_BRANCH:-production}}"
RUN_MIGRATE=false

for arg in "$@"; do
  case "$arg" in
    --migrate) RUN_MIGRATE=true ;;
  esac
done

cd "$(git rev-parse --show-toplevel)"

echo "==> Pulling latest ${BRANCH}"
git fetch origin "${BRANCH}"
git checkout "${BRANCH}"
git reset --hard "origin/${BRANCH}"

echo "==> Building images"
docker compose -f "${COMPOSE_FILE}" build app reverb nginx

echo "==> Installing dependencies and building assets"
docker compose -f "${COMPOSE_FILE}" run --rm --no-deps app sh -c \
  "composer install --no-dev --optimize-autoloader --no-interaction && npm ci && npm run build"

echo "==> Recreating containers"
# Deliberately NOT --force-recreate: code is bind-mounted (.:/var/www), so a
# container whose image is unchanged doesn't need to be destroyed and
# recreated at all - the restart below is enough to make it load the fresh
# code. Plain `up -d` still recreates automatically on the rare deploy where
# the image itself changed (Dockerfile edits), which is the only case where
# recreation is actually needed. Forcing it on every deploy was the real
# cause of the "container name already in use" / "removal of container ...
# is already in progress" churn seen on prior runs: Docker's container
# removal is asynchronous under the hood (the daemon returns before
# overlay/volume cleanup finishes), so recreating a container too soon after
# removing it can race. The retry loop stays as a safety net for the rare
# case `up -d` does need to recreate.
for svc in app reverb queue scheduler; do
  attempt=1
  until docker compose -f "${COMPOSE_FILE}" up -d --no-deps "${svc}"; do
    if [ "$attempt" -ge 10 ]; then
      echo "ERROR: could not bring up ${svc} after ${attempt} attempts"
      exit 1
    fi
    echo "    ${svc}: container removal still in progress, retrying (${attempt})..."
    attempt=$((attempt + 1))
    sleep 3
  done
done

echo "==> Restarting so every long-running process loads the latest code"
docker compose -f "${COMPOSE_FILE}" restart app reverb queue scheduler

echo "==> Waiting for app container to be ready..."
for i in $(seq 1 30); do
  if docker compose -f "${COMPOSE_FILE}" exec -T app php -r "echo 1;" 2>/dev/null | grep -q 1; then
    echo "    App ready after ${i}s"
    break
  fi
  if [ "$i" -eq 30 ]; then
    echo "ERROR: App container did not become ready within 30s"
    docker compose -f "${COMPOSE_FILE}" logs app --tail=30
    exit 1
  fi
  sleep 1
done

echo "==> Ensuring public/storage symlink exists"
# The production image no longer bakes this in (it's fully shadowed by the
# code bind mount anyway) - creating it here instead makes a first-ever
# deploy to a fresh server self-sufficient. Guarded by -L so this stays a
# no-op on every deploy after the first.
docker compose -f "${COMPOSE_FILE}" exec -T app sh -c \
  "[ -L public/storage ] || php artisan storage:link"

echo "==> Warming caches"
docker compose -f "${COMPOSE_FILE}" exec -T app sh -c \
  "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache"

echo "==> Reloading nginx"
docker compose -f "${COMPOSE_FILE}" up -d nginx
docker compose -f "${COMPOSE_FILE}" exec -T nginx nginx -s reload 2>/dev/null || \
  docker compose -f "${COMPOSE_FILE}" restart nginx

if [ "$RUN_MIGRATE" = true ]; then
  echo "==> Running migrations"
  docker compose -f "${COMPOSE_FILE}" exec -T app php artisan migrate --force
fi

# Hygiene only (shrinks the bind-mounted node_modules / reclaims disk) - not
# required for the app to serve traffic, so a hiccup here must never stop the
# script before the steps above (cache warming, nginx reload) have run.
echo "==> Pruning dev dependencies"
docker compose -f "${COMPOSE_FILE}" exec -T app npm prune --omit=dev || \
  echo "    WARNING: npm prune failed/was killed, continuing anyway"

echo "==> Cleaning up old images"
docker image prune -f || echo "    WARNING: docker image prune failed, continuing anyway"

echo "==> Status:"
docker compose -f "${COMPOSE_FILE}" ps

if [ "$RUN_MIGRATE" = false ]; then
  echo ""
  echo "Tip: Run with --migrate to also apply database migrations"
fi
