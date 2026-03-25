# 🚀 Team Development Guide (Laravel + Docker + Makefile)

## 📌 Project Overview
This project runs fully in Docker:
- app → Laravel application  
- mysql → Database  
- phpmyadmin → DB UI  

---

# ⚙️ One-time Setup (New Developer)

git clone <repo>
cd project

make setup

---

# 🔁 Daily Development Workflow

## 1. Pull latest code
git pull origin dev

## 2. Install dependencies (if needed)
make install

## 3. Sync environment (if .env changed)
make sync

## 4. Run migrations (IMPORTANT)
make migrate

---

# 🚀 Run the Project

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
make art cmd="make:migration create_table_name"

## 2. Edit migration file
database/migrations/xxxx_create_table_name.php

## 3. Run migration
make migrate

## 4. Create Model
make model name=ModelName

## 5. Create Controller
make controller name=Api/YourController

## 6. Add Route
routes/api.php

---

# 📄 Generate API Docs (Scribe)

make scribe

Access docs:
http://localhost:${APP_PORT}/docs

---

# 🧹 Fix Issues

## Clear cache
make clear

## Restart containers
make down
make up

---

# ⚠️ Important Rules

- ALWAYS run make migrate after pulling new code  
- NEVER change database manually (use migrations)  
- DO NOT use php artisan directly (use Makefile)  
- Ensure .env is correct (DB_HOST=mysql)  

---

# ⚡ Available Commands

make setup        # full project setup  
make up           # start docker  
make down         # stop docker  
make migrate      # run migration  
make fresh        # fresh migrate (dev only)  
make clear        # clear cache  
make scribe       # generate API docs  

make model name=User  
make controller name=Api/UserController  
make art cmd="route:list"  

---

# 🧠 Workflow Summary

1. Pull code  
2. Run make migrate  
3. Develop feature  
4. Run make scribe (if API changed)  

---

# ✅ Final Result

Before:
docker exec -it laravel_app php artisan migrate

Now:
make migrate
