# 📦 Changelog

All notable changes to this project will be documented in this file.

---

## [v3.0.0] - 2026-03-24

### 🚀 Initial Release

### ✨ Added
- Laravel 12 backend setup
- Vue 3 SPA frontend with Vue Router
- Tailwind CSS integration
- Docker environment (MySQL + phpMyAdmin)

### 📦 Backend Packages
- laravel/sanctum (API auth)
- spatie/laravel-permission (roles & permissions)
- darkaonline/l5-swagger (API docs)

### 📦 Frontend Packages
- vue 3
- vue-router
- vite
- axios
- tailwindcss

---

## ⚠️ Required Actions

After pulling latest changes:

```bash
composer install
npm install
docker compose up -d --build