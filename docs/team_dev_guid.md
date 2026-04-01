# Team Development Guide (Laravel + Docker)

## Project Overview
This project runs fully in Docker:
- `app` -> Laravel application
- `node` -> Vite dev server
- `mysql` -> Database
- `phpmyadmin` -> DB UI

Requirements:
- Docker
- Docker Compose v2 (`docker compose`)

---

## One-time Setup (New Developer)

```bash
git clone <repo>
cd etec_system_v3
git checkout dev
```

### Windows (PowerShell)
```powershell
if (!(Test-Path .env)) { Copy-Item .env.example .env }
docker compose up -d --build
docker compose run --rm app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### macOS / Linux
```bash
[ -f .env ] || cp .env.example .env
docker compose up -d --build
docker compose run --rm app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

---

## Daily Development Workflow

1. Pull latest code
```bash
git pull origin dev
```

2. Start containers
```bash
docker compose up -d
```

3. Install dependencies if needed
```bash
docker compose run --rm app composer install
npm install
```

4. Run migrations after pulling DB changes
```bash
docker compose exec app php artisan migrate
```

5. Sync environment example when env vars change
```bash
docker compose run --rm app composer run sync-env
```

---

## Run The Project

- App: `http://localhost:8001`
- Vite: `http://localhost:5173`
- phpMyAdmin: `http://localhost:8080`

phpMyAdmin login:
- Server: `mysql`
- Username: `root`
- Password: from `DB_PASSWORD` in `.env`

---

## Common Commands

Run artisan from host:
```bash
docker compose exec app php artisan <command>
```

Examples:
```bash
docker compose exec app php artisan route:list
docker compose exec app php artisan test
docker compose exec app php artisan optimize:clear
```

Access app container shell:
```bash
docker compose exec app sh
```

Then run:
```bash
php artisan
```

---

## Important Rules

- Always run migrations after pulling schema changes.
- Do not change database manually; use migrations.
- Keep `.env.example` updated when adding env vars.
- Use Docker commands only; no `make` workflow.
