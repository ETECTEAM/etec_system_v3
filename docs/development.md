
# 🚀 Team Development Guide (Laravel + Docker)

## 📌 Project Overview
This project runs fully in Docker:
- `app` → Laravel application
- `mysql` → Database
- `phpmyadmin` → DB UI

---

# 🔁 Daily Setup

## 1. Pull latest code
git pull origin dev

## 2. Install dependencies
composer install

## 3. Sync environment
composer run sync-env

## 4. Start Docker (if not running)
docker compose up -d

## 5. Run migrations (IMPORTANT)
docker exec -it laravel_app php artisan migrate

---

# 🧪 Running the Project

## App
http://localhost:${APP_PORT}

## phpMyAdmin
http://localhost:8080

Login:
- Server: mysql
- Username: root
- Password: (from .env)

---

# 🛠 When Adding New Feature (API + Table)

## 1. Create Migration
docker exec -it laravel_app php artisan make:migration create_table_name

## 2. Edit migration file
database/migrations/xxxx_create_table_name.php

## 3. Run migration
docker exec -it laravel_app php artisan migrate

## 4. Create Model
docker exec -it laravel_app php artisan make:model ModelName

## 5. Create Controller
docker exec -it laravel_app php artisan make:controller Api/YourController

## 6. Add Route
routes/api.php

---

# 📄 Generate API Docs (Scribe)

docker exec -it laravel_app php artisan scribe:generate

Access docs:
http://localhost:${APP_PORT}/docs

---

# 🧹 Fix Issues

## Clear cache
docker exec -it laravel_app php artisan optimize:clear

## Restart containers
docker compose down
docker compose up -d

---

# ⚠️ Important Rules

- ALWAYS run migration after pulling new code
- NEVER change DB manually (use migrations)
- ALWAYS use Docker commands (not local PHP)
- Keep `.env` correct (DB_HOST=mysql)

---

# ⚡ Useful Shortcut (Optional)

Add alias:
alias art="docker exec -it laravel_app php artisan"

Then use:
art migrate
art scribe:generate
art optimize:clear

---

# 🧠 Workflow Summary

1. Pull code
2. Install dependencies
3. Sync env
4. Start Docker
5. Run migration
6. Develop feature
7. Generate docs

---

# ✅ Done
