# ETEC System v2

ETEC System v2 is a web application built using **Laravel 12**, **Vue 3**, **Vite**, and **MySQL**.
This project is designed as a modern SPA-style admin system.

---

# Tech Stack

* Backend: Laravel 12
* Frontend: Vue 3
* Build Tool: Vite
* Database: MySQL
* Styling: TailwindCSS

---

# Project Setup Guide

Follow the steps below to clone and run the project locally.

---

# 1. Clone Repository

```bash
git clone https://github.com/knr-smey/etec_system_v2.git
```

Navigate into the project folder:

```bash
cd etec_system_v2
```

Checkout the development branch:

```bash
git checkout dev
```

---

# 2. Install PHP Dependencies

Make sure you have **PHP 8.2+** and **Composer** installed.

```bash
composer install
```

---

# 3. Install Node Dependencies

Make sure you have **Node.js 18+** installed.

```bash
npm install
```

---

# 4. Environment Setup

Copy the environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

---

# 5. Configure Database

Open `.env` and update database settings:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=etec_system
DB_USERNAME=root
DB_PASSWORD=
```

Create the database in MySQL:

```
etec_system
```

---

# 6. Run Migration

```bash
php artisan migrate
```

(Optional seed if available)

```bash
php artisan db:seed
```

---

# 7. Run Development Server

Start Laravel server:

```bash
php artisan serve
```

Run Vite frontend:

```bash
npm run dev
```

---

# 8. Open Application

Visit:

```
http://127.0.0.1:8000
```

---

Frontend uses **Vue Router for SPA navigation**.

---

# Development Workflow

Typical workflow for contributors:

```
git pull origin dev
git checkout -b feature/your-feature-name
git commit -m "Add feature"
git push origin feature/your-feature-name
```

Create a Pull Request to the **dev branch**.

---

# Requirements

* PHP >= 8.2
* Composer
* Node.js >= 18
* MySQL >= 8
* Git

---

# License

This project is new verstion of etec center
