# 🚀 ETEC System v3

![Version](https://img.shields.io/badge/version-v3-blue)
![Laravel](https://img.shields.io/badge/Laravel-12-red)
![Vue](https://img.shields.io/badge/Vue-3-green)
![Docker](https://img.shields.io/badge/Docker-ready-blue)

---

## 📌 Overview

**ETEC System v3** is a modern web-based admin system built with **Laravel 12** and **Vue 3**.

This project follows a **Single Page Application (SPA)** architecture powered by **Vite** and is fully containerized using **Docker** for easy setup and consistent development.

---

## 🧱 Tech Stack

* **Backend:** Laravel 12 (PHP 8.2+)
* **Frontend:** Vue 3 + Vue Router
* **Build Tool:** Vite
* **Database:** MySQL 8
* **Styling:** Tailwind CSS
* **Containerization:** Docker & Docker Compose

---

## 🐳 Docker Setup (Recommended)

### 📦 Requirements

* Docker
* Docker Compose (`docker compose`)

---

### 🚀 1. Clone Repository

```bash
git clone https://github.com/knr-smey/etec_system_v3.git
cd etec_system_v3
git checkout dev
```

---

### ⚙️ 2. Setup Environment

```bash
cp .env.example .env
```

Edit `.env`:

```env
APP_NAME="ETEC SYSTEM"
APP_URL=http://127.0.0.1:8001
APP_PORT=8001

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=etec_system
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

---

### 🏗️ 3. Build & Run Containers

```bash
docker compose up -d --build
```

---

### 🔑 4. Generate Key & Run Migration

```bash
docker exec -it laravel_app php artisan key:generate
docker exec -it laravel_app php artisan migrate
```
or

```bash
docker exec -it laravel_app bash

php artisan key:generate
php artisan migrate

exit
```

---

### 🌐 5. Access Application

| Service     | URL                   |
| ----------- | --------------------- |
| Laravel App | http://localhost:8001 |
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
├── docker-compose.yml
└── .env
```

---

## 🔧 Development Workflow

```bash
git pull origin dev
composer install
git checkout -b feature/your-feature-name
git commit -m "feat: add your feature"
git push origin feature/your-feature-name
```

---

## 🔄 Environment Sync (Important)

When adding new environment variables:

```bash
composer run sync-env
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
* API Documentation (Scribe)
* Nginx Reverse Proxy + HTTPS
* CI/CD Pipeline

---

## 📄 License

This project is an upgraded version of the ETEC Center system.

---

## 👨‍💻 Author

Prepared by **RAKSMEY**
Developer: **ETEC Team**
