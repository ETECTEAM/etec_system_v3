.PHONY: up down install sync key migrate fresh clear art setup model controller

DOCKER_COMPOSE = docker compose
APP = $(DOCKER_COMPOSE) exec app
APP_RUN = $(DOCKER_COMPOSE) run --rm app

# Start containers
up:
	$(DOCKER_COMPOSE) up -d --build

# Stop containers
down:
	$(DOCKER_COMPOSE) down

# Install PHP dependencies inside Docker
install:
	$(APP_RUN) composer install

# Sync env example inside Docker
sync:
	$(APP_RUN) composer run sync-env

# Generate Laravel app key
key:
	$(APP) php artisan key:generate

# Run migration
migrate:
	$(APP) php artisan migrate

# Fresh migration (optional)
fresh:
	$(APP) php artisan migrate:fresh --seed

# Clear cache
clear:
	$(APP) php artisan optimize:clear

# Custom artisan command
art:
	$(APP) php artisan $(cmd)

# First time setup (NEW DEV)
setup:
	cp -n .env.example .env
	$(DOCKER_COMPOSE) up -d --build
	$(APP_RUN) composer install
	$(APP) php artisan key:generate
	$(APP) php artisan migrate

model:
	$(APP) php artisan make:model $(name)

controller:
	$(APP) php artisan make:controller $(name)
