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

echo "==> Pruning dev dependencies"
docker exec system_app_prod npm prune --omit=dev

echo "==> Recreating containers"
docker compose -f "${COMPOSE_FILE}" up -d --force-recreate app reverb queue scheduler

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
