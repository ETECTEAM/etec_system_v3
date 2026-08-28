# ETEC System v3

Docker-optional setup guide for Windows, macOS, and Linux.

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

| Tool | Use this | Hard minimum | Why |
|---|---|---|---|
| Git | any recent | — | |
| PHP | **8.3** | 8.2 | production runs 8.3; `composer install` aborts on < 8.2 |
| Composer | 2.x | 2.0 | |
| Node.js + npm | **20 LTS** | 20.19 | Vite 7 refuses older Node; production builds on 20 |
| MySQL | 8.x | 8.0 | MariaDB 10.6+ also works for local dev only |

> If `php -v` or `node -v` show the wrong version, read
> [Toolchain versions & switching](#toolchain-versions--switching) first — that is
> the single most common cause of a broken local setup on this team.

Check install:

```bash
php --version
composer --version
node --version
npm --version
```

## Toolchain versions & switching

Most "it works on my machine" setup failures on the team come down to a **wrong
PHP or Node version**. Typical symptoms:

- `composer install` aborts with `requires php ^8.2 -> your php version (8.1.x) does not satisfy it`
- `npm run dev` / `vite` throws `Unsupported engine`, or a build fails with syntax
  errors in a dependency
- the app boots but Reverb / queue commands crash with `pcntl`-related errors

### Pinned versions

| Tool | Use | Hard minimum | Reason |
|---|---|---|---|
| PHP | 8.3 | 8.2 | production is 8.3; `composer.json` requires `^8.2` |
| Node | 20 LTS | 20.19 | Vite 7 + `@vitejs/plugin-vue` 6 need Node ≥ 20.19 (or ≥ 22.12) |
| Composer | 2.x | 2.0 | |
| MySQL | 8.x | 8.0 | production is MySQL 8 |

The repo carries version hints that most managers pick up automatically:

- `.nvmrc` → `20` — read by `nvm use`, `fnm use`, asdf-nodejs
- `.tool-versions` → `php 8.3`, `nodejs 20` — read by asdf and mise
  (older asdf may want an exact patch, e.g. `php 8.3.15`)

### Easiest fix: use Docker

The dev image pins PHP 8.3 + Node 20, so version drift is impossible. If you keep
fighting your host toolchain, switch to the [Docker setup](#setup-with-docker)
and skip this whole section.

### Switching Node

```bash
# nvm — https://github.com/nvm-sh/nvm
nvm install 20
nvm use            # reads .nvmrc

# fnm — https://github.com/Schniz/fnm
fnm install 20 && fnm use
```

### Switching PHP

**Linux (Ubuntu, ondrej/php PPA):**

```bash
sudo add-apt-repository -y ppa:ondrej/php && sudo apt update
sudo apt install -y php8.3-cli php8.3-{mysql,gd,zip,bcmath,mbstring,xml,curl,intl,pcntl}
sudo update-alternatives --config php     # select 8.3 for the `php` CLI
```

**macOS (Homebrew):**

```bash
brew install php@8.3
brew unlink php 2>/dev/null; brew link --overwrite --force php@8.3
```

**Cross-platform (asdf / mise) — respects `.tool-versions`:**

```bash
# asdf
asdf plugin add php && asdf plugin add nodejs
asdf install        # installs the versions from .tool-versions

# mise — https://mise.jdx.dev
mise install        # same, from .tool-versions
```

**macOS GUI:** [Laravel Herd](https://herd.laravel.com) switches PHP per project.
**Windows:** use WSL2 with the Linux steps above, or the Docker setup.

### Verify before installing anything

```bash
php -v          # expect 8.3.x  (8.2.x acceptable)
node -v         # expect v20.x  (>= v20.19)
composer -V     # expect 2.x
which php        # make sure it's the version-manager shim, not /usr/bin/php
```

If `composer install` still complains about the PHP version after you switched,
your shell is resolving a different `php` than you think — check `which php` and
your `PATH` / shim order.

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
if (!(Test-Path .env)) { Copy-Item .env.docker .env }
docker compose up -d --build
docker compose run --rm app composer install
docker compose run --rm app npm install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

### macOS (Terminal)

```bash
[ -f .env ] || cp .env.docker .env
docker compose up -d --build
docker compose run --rm app composer install
docker compose run --rm app npm install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

### Linux (Terminal)

```bash
[ -f .env ] || cp .env.docker .env
docker compose up -d --build
docker compose run --rm app composer install
docker compose run --rm app npm install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
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

The `.env.example` file already uses local defaults (127.0.0.1). Update the
values if your local database credentials differ.

If you want to run the app locally on port `8001`, keep or update:

```env
APP_URL=http://127.0.0.1:8001
APP_PORT=8001
```

### 3. Create the database

Create a database matching `DB_DATABASE` in your `.env` (default `etec_db`) before
running migrations:

```sql
CREATE DATABASE etec_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Install dependencies

```bash
composer install
npm install
```

### 5. Generate app key and run migrations with seeders

```bash
php artisan key:generate
php artisan migrate --seed
```

> **Note**: Always use `migrate --seed` (or `migrate:fresh --seed`) when pulling new updates so default categories, tracks, and courses are populated properly.

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

## Daily Commands & Pulling Code (With Docker)

Pull latest changes:

```bash
git pull origin dev
docker compose exec app php artisan migrate --seed
```

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

Run migrations with seeders:

```bash
docker compose exec app php artisan migrate --seed
```

## Daily Commands & Pulling Code (Without Docker)

Pull latest changes:

```bash
git pull origin dev
php artisan migrate --seed
```

Start Laravel:

```bash
php artisan serve --port=8001
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

Run migrations with seeders:

```bash
php artisan migrate --seed
```

## Access Services

- App: http://localhost:8001
- Vite: http://localhost:5173
- phpMyAdmin: http://localhost:8080

The `app` container runs `composer run dev`, which starts:

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
- Password: value from `DB_PASSWORD` in `.env.docker`

## Environment Notes

- Local development uses `.env` copied from `.env.example`.
- Docker development uses `.env.docker` (docker-compose loads it), and it is also copied to `.env`
	so `php artisan key:generate` can write the APP_KEY.
- Keep `APP_URL` aligned with the URL you use in the browser to avoid session/CSRF issues.
- Vite runs on `http://127.0.0.1:5173` locally and maps to the same port in Docker.

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

### Wrong PHP / Node version

`composer install` aborting on a platform requirement, or `npm run dev` failing
with `Unsupported engine` / unexpected syntax errors, means your host is on the
wrong runtime version. See
[Toolchain versions & switching](#toolchain-versions--switching). Quickest
unblock: run the project via [Docker](#setup-with-docker) instead.
