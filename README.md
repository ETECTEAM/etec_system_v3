# 🚀 ETEC System v3

![Version](https://img.shields.io/badge/version-v3-blue)
![Laravel](https://img.shields.io/badge/Laravel-12-red)
![Vue](https://img.shields.io/badge/Vue-3-green)
![Docker](https://img.shields.io/badge/Docker-ready-blue)

---

## 📌 Overview

**ETEC System v3** is a modern web-based admin system built with **Laravel 12** and **Vue 3**.

This project follows a **Single Page Application (SPA)** architecture powered by **Vite** and is fully containerized using **Docker** for local development and deployment builds.

---

## 🧱 Tech Stack

* **Backend:** Laravel 12 (PHP 8.2+)
* **Frontend:** Vue 3 + Vue Router
* **Build Tool:** Vite
* **Database:** MySQL 8
* **Styling:** Tailwind CSS
* **Containerization:** Docker & Docker Compose

---

## 🐳 Quick Start For Teammates

If you only want to run the project locally, use these commands:

```bash
git clone https://github.com/knr-smey/etec_system_v3.git
cd etec_system_v3
git checkout dev
make init
```

After first setup:

```bash
make up
make test
```

Open:
- App: `http://localhost:8001`
- Vite: `http://localhost:5173`
- phpMyAdmin: `http://localhost:8080`

`make init` does the first-time setup:
- creates `.env` if missing
- builds the Docker containers
- installs Composer dependencies
- generates the Laravel app key
- runs database migrations

---

## 🐳 Docker Setup

### 📦 Requirements

* `make`
* Docker
* Docker Compose (`docker compose`)

### 🪟 Windows Setup

If you are using Windows, the recommended setup is `WSL + Docker Desktop`.

#### 1. Install WSL

Open PowerShell as Administrator and run:

```powershell
wsl --install
```

Restart your computer if Windows asks.

#### 2. Open Ubuntu

After restart, open the `Ubuntu` app from the Start menu and finish the first-time setup.

#### 3. Install `make`

Inside Ubuntu, run:

```bash
sudo apt update
sudo apt install make
```

Check that it is installed:

```bash
make --version
```

#### 4. Enable Docker Desktop WSL integration

- Install Docker Desktop
- Open Docker Desktop Settings
- Go to `Resources` → `WSL Integration`
- Enable integration for your Ubuntu distro

After that, run this project from the Ubuntu terminal, not from Command Prompt.

---

### 🚀 1. Clone Repository

```bash
git clone https://github.com/knr-smey/etec_system_v3.git
cd etec_system_v3
git checkout dev
```

---

### ⚙️ 2. First-Time Setup

```bash
make init
```

This command will:
- create `.env` from `.env.example` if needed
- build and start Docker containers
- install Composer dependencies inside Docker
- generate `APP_KEY`
- run database migrations

---

### 🌐 3. Access Development Services

| Service     | URL                   |
| ----------- | --------------------- |
| Laravel App | http://localhost:8001 |
| Vite Dev    | http://localhost:5173 |
| phpMyAdmin  | http://localhost:8080 |

---

### 🔐 phpMyAdmin Login

```
Server: mysql
Username: root
Password: 00100
```

---

## 📂 Project Structure

```bash
etec_system_v3/
├── app/
├── resources/js/
├── routes/
├── database/
├── public/
├── docker-compose.dev.yml
├── docker-compose.prod.example.yml
├── deploy/
│   ├── nginx/default.conf
│   └── private/docker-compose.prod.yml  # local/server only, not in git
└── .env
```

## Development Docker

Use the development stack for coding, hot reload, and database work:

```bash
make init
make up
make down
make test
```

Services:
- `app`: Laravel development server
- `node`: Vite dev server
- `mysql`: MySQL 8
- `phpmyadmin`: database UI

## Deployment Docker

You do not need this for normal teammate onboarding.

Use the production stack to build a deployment image with:
- PHP-FPM application container
- Nginx web container
- built Vite assets

Commands:

```bash
make prod-init
make prod-build
make prod-up
make prod-down
```

Production files:
- `Dockerfile`
- `docker-compose.prod.example.yml`
- `deploy/private/docker-compose.prod.yml` (ignored by git)
- `deploy/nginx/default.conf`

`make prod-init` copies the tracked example file into the private deploy path. Put any server-only values or host-specific changes in `deploy/private/docker-compose.prod.yml`, and do not commit that file.

---

## 🔧 Development Workflow

```bash
git pull origin dev
make install
npm install
git checkout -b feature/your-feature-name
git commit -m "feat: add your feature"
git push origin feature/your-feature-name
```

---

## 📬 API Testing With Postman

This project uses Postman for API testing instead of generated API docs.

### Base URL

```text
http://localhost:8001/api
```

### Recommended Postman Setup

- Create a Postman environment named `Local`
- Add `base_url` = `http://localhost:8001/api`
- If you use authenticated routes, add `token` for your Bearer token

### Example Headers

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{token}}
```

### Example Request

```http
GET {{base_url}}/courses
```

### Team Workflow

- Use Postman collections to organize endpoints by module
- Update the collection when API routes or request bodies change
- Share the exported Postman collection with the team if needed

### Starter Collection

Import this file into Postman:

[`docs/postman/etec_system_v3.postman_collection.json`](/home/raksmey/Downloads/etec_system_v3/docs/postman/etec_system_v3.postman_collection.json)

---

## 🔄 Environment Sync (Important)

When adding new environment variables:

```bash
make sync
```

✔ Keeps `.env.example` updated
✔ Prevents missing config for teammates

---

## ⚠️ Common Issues & Fixes

### ❌ SQLSTATE[HY000] Connection refused

✔ Fix `.env`:

```env
DB_HOST=mysql
```

Then:

```bash
docker exec -it laravel_app php artisan config:clear
```

---

### ❌ Docker command not found

Use:

```bash
docker compose up -d
```

---

### ❌ MySQL not ready

Check logs:

```bash
docker logs laravel_mysql
```

Wait until:

```
ready for connections
```

---

### ❌ Permission issues

```bash
docker exec -it laravel_app chmod -R 775 storage bootstrap/cache
```

---

## 🧪 Useful Commands

```bash
# Stop containers
docker compose down

# Rebuild containers
docker compose up -d --build

# Enter container
docker exec -it laravel_app bash

# View logs
docker logs laravel_app
```

---

## 📚 Documentation

* Development Workflow: `docs/development.md`

---

## ✨ Features

* SPA Admin Dashboard (Vue 3)
* RESTful API (Laravel)
* Authentication System
* Docker-ready environment
* Scalable architecture

---

## 🚀 Future Improvements

* Role & Permission System
* Nginx Reverse Proxy + HTTPS
* CI/CD Pipeline

---

## 📄 License

This project is an upgraded version of the ETEC Center system.

---

## 👨‍💻 Author

Prepared by **RAKSMEY**
Developer: **ETEC Team**
