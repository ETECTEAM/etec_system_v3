.PHONY: up down install sync key migrate fresh clear art init setup model controller test prod-init prod-build prod-up prod-down

DEV_COMPOSE = docker compose -f docker-compose.dev.yml
PROD_COMPOSE_FILE = deploy/private/docker-compose.prod.yml
PROD_COMPOSE = docker compose -f $(PROD_COMPOSE_FILE)
APP = $(DEV_COMPOSE) exec app
APP_RUN = $(DEV_COMPOSE) run --rm app

# Start containers
up:
	$(DEV_COMPOSE) up -d --build

# Stop containers
down:
	$(DEV_COMPOSE) down

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

# Run test suite inside Docker using the app container PHP extensions
test:
	$(DEV_COMPOSE) run --rm \
		-e APP_ENV=testing \
		-e DB_CONNECTION=sqlite \
		-e DB_DATABASE=:memory: \
		-e CACHE_STORE=array \
		-e SESSION_DRIVER=array \
		-e QUEUE_CONNECTION=sync \
		-e MAIL_MAILER=array \
		app php artisan test

# Custom artisan command
art:
	$(APP) php artisan $(cmd)

# First time setup (NEW DEV)
init:
	$(MAKE) setup

setup:
	cp --update=none .env.example .env
	$(DEV_COMPOSE) up -d --build
	$(APP_RUN) composer install
	$(APP) php artisan key:generate
	$(APP) php artisan migrate

model:
	$(APP) php artisan make:model $(name)

controller:
	$(APP) php artisan make:controller $(name)

prod-init:
	mkdir -p deploy/private
	cp --update=none docker-compose.prod.example.yml $(PROD_COMPOSE_FILE)

prod-build:
	$(PROD_COMPOSE) build

prod-up:
	$(PROD_COMPOSE) up -d --build

prod-down:
	$(PROD_COMPOSE) down
