# Start containers
up:
	docker compose up -d

# Stop containers
down:
	docker compose down

# Install dependencies
install:
	composer install

# Sync env
sync:
	composer run sync-env

# Run migration
migrate:
	docker compose exec app php artisan migrate

# Fresh migration (optional)
fresh:
	docker compose exec app php artisan migrate:fresh --seed

# Clear cache
clear:
	docker compose exec app php artisan optimize:clear

# Generate API docs
scribe:
	docker compose exec app php artisan scribe:generate

# Custom artisan command
art:
	docker compose exec app php artisan $(cmd)

# First time setup (NEW DEV)
setup:
	composer install
	docker compose up -d
	docker compose exec app php artisan migrate

model:
	docker compose exec app php artisan make:model $(name)

controller:
	docker compose exec app php artisan make:controller $(name)