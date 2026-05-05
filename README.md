# ETEC System v3

Docker-first setup guide for Windows, macOS, and Linux.

## Requirements

### Docker setup

- Git
- Docker Desktop (Windows/macOS) or Docker Engine + Docker Compose plugin (Linux)
- Docker Compose v2 (`docker compose`)

Check install:

```bash
docker --version
docker compose version
```

### Normal setup without Docker

- Git
- PHP 8.2+
- Composer
- Node.js + npm
- MySQL or MariaDB

Check install:

```bash
php --version
composer --version
node --version
npm --version
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
docker compose run --rm app npm install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### macOS (Terminal)

```bash
[ -f .env ] || cp .env.example .env
docker compose up -d --build
docker compose run --rm app composer install
docker compose run --rm app npm install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### Linux (Terminal)

```bash
[ -f .env ] || cp .env.example .env
docker compose up -d --build
docker compose run --rm app composer install
docker compose run --rm app npm install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

## Setup Without Docker

### 1. Create environment file

Windows (PowerShell):

```powershell
if (!(Test-Path .env)) { Copy-Item .env.example .env }
```

macOS/Linux (Terminal):

```bash
[ -f .env ] || cp .env.example .env
```

### 2. Update local database settings

Open `.env` and change the Docker database host to your local database host.

Example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=etec_system
DB_USERNAME=root
DB_PASSWORD=secret
```

If you want to run the app locally on port `8001`, keep or update:

```env
APP_URL=http://127.0.0.1:8001
APP_PORT=8001
```

### 3. Create the database

Create a database named `etec_system` in MySQL before running migrations.

### 4. Install dependencies

```bash
composer install
npm install
```

### 5. Generate app key and run migrations

```bash
php artisan key:generate
php artisan migrate
```

If you need seed data:

```bash
php artisan db:seed
```

### 6. Start the project

Option 1: Run everything with one command:

```bash
composer run dev
```

This starts:

- Laravel server on `http://127.0.0.1:8001`
- Queue listener
- Laravel Pail logs
- Vite dev server on `http://127.0.0.1:5173`

Option 2: Run server and frontend separately:

Terminal 1:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

Terminal 2:

```bash
npm run dev
```

Optional queue worker in another terminal:

```bash
php artisan queue:listen --tries=1 --timeout=0
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

## Daily Commands Without Docker

Start Laravel:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

Start Vite:

```bash
npm run dev
```

Run everything together:

```bash
composer run dev
```

Run tests:

```bash
php artisan test
```

Run migrations:

```bash
php artisan migrate
```

## Access Services

- App: http://localhost:8001
- Vite: http://localhost:5173
- phpMyAdmin: http://localhost:8080

The `app` container now runs `composer run dev`, which starts:

- Laravel server
- Queue listener
- Laravel Pail
- Vite dev server

Without Docker:

- App: http://127.0.0.1:8001
- Vite: http://127.0.0.1:5173

phpMyAdmin login:

- Server: `mysql`
- Username: `root`
- Password: value from `DB_PASSWORD` in `.env`

## Access App Container Shell

```bash
docker compose exec app bash
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

If running without Docker, use:

```bash
php artisan config:clear
```
