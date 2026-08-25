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
# `up --force-recreate` stops+removes+creates each container in one compose
# call. Docker's container removal is asynchronous under the hood (the
# daemon returns before overlay/volume cleanup finishes), so a container
# recreated too soon after being removed can fail with "removal of
# container ... is already in progress". A separate `rm -f` step right
# before this loop used to trigger exactly that race every deploy; retrying
# here instead rides it out.
for svc in app reverb queue scheduler; do
  attempt=1
  until docker compose -f "${COMPOSE_FILE}" up -d --force-recreate --no-deps "${svc}"; do
    if [ "$attempt" -ge 10 ]; then
      echo "ERROR: could not recreate ${svc} after ${attempt} attempts"
      exit 1
    fi
    echo "    ${svc}: container removal still in progress, retrying (${attempt})..."
    attempt=$((attempt + 1))
    sleep 3
  done
  sleep 2
done

echo "==> Ensuring containers are actually running"
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

echo "==> Pruning dev dependencies"
docker compose -f "${COMPOSE_FILE}" exec -T app npm prune --omit=dev

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

echo "==> Cleaning up old images"
docker image prune -f

echo "==> Status:"
docker compose -f "${COMPOSE_FILE}" ps

if [ "$RUN_MIGRATE" = false ]; then
  echo ""
  echo "Tip: Run with --migrate to also apply database migrations"
fi
