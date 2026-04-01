# Changelog

All notable changes to this project will be documented in this file.

---

## [v3.0.1] - 2026-04-01

### Changed
- Standardized local setup to a single Compose file: `docker-compose.yml`.
- Removed dev/prod split compose files from root workflow.
- Removed Makefile-based onboarding in favor of direct `docker compose` commands.
- Rewrote README setup instructions for Windows, macOS, and Linux using Docker-only steps.
- Updated team development guide to Docker-only workflow.

### Migration Notes
- Use `docker compose ...` directly for all setup and daily commands.
- Run artisan through container exec, e.g. `docker compose exec app php artisan migrate`.

---

## [v3.0.0] - 2026-03-24

### Initial Release

### Added
- Laravel 12 backend setup
- Vue 3 SPA frontend with Vue Router
- Tailwind CSS integration
- Docker environment (MySQL + phpMyAdmin)

### Backend Packages
- laravel/sanctum (API auth)
- spatie/laravel-permission (roles & permissions)

### Frontend Packages
- vue 3
- vue-router
- vite
- axios
- tailwindcss

---

## Required Actions

After pulling latest changes:

```bash
composer install
npm install
docker compose up -d --build
```
