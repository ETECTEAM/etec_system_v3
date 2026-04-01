# ETEC System v3

Docker-first setup guide for Windows, macOS, and Linux.

## Requirements

- Git
- Docker Desktop (Windows/macOS) or Docker Engine + Docker Compose plugin (Linux)
- Docker Compose v2 (`docker compose`)

Check install:

```bash
docker --version
docker compose version
```

## Clone Project

Use the same commands on all OS:

```bash
git clone https://github.com/knr-smey/etec_system_v3.git
cd etec_system_v3
git checkout dev
```

## Setup With Docker

### Windows (PowerShell)

```powershell
if (!(Test-Path .env)) { Copy-Item .env.example .env }
docker compose up -d --build
docker compose run --rm app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### macOS (Terminal)

```bash
[ -f .env ] || cp .env.example .env
docker compose up -d --build
docker compose run --rm app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### Linux (Terminal)

```bash
[ -f .env ] || cp .env.example .env
docker compose up -d --build
docker compose run --rm app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

## Daily Commands (All OS)

Start:

```bash
docker compose up -d
```

Stop:

```bash
docker compose down
```

Run tests:

```bash
docker compose exec app php artisan test
```

Run migrations:

```bash
docker compose exec app php artisan migrate
```

## Access Services

- App: http://localhost:8001
- Vite: http://localhost:5173
- phpMyAdmin: http://localhost:8080

phpMyAdmin login:

- Server: `mysql`
- Username: `root`
- Password: value from `DB_PASSWORD` in `.env`

## Access App Container Shell

```bash
docker compose exec app sh
```

Then run artisan directly inside container:

```bash
php artisan
```

## Common Fixes

If app cannot connect to database, check `.env`:

```env
DB_HOST=mysql
```

Then clear config:

```bash
docker compose exec app php artisan config:clear
```
