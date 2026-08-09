# syntax=docker/dockerfile:1

FROM php:8.3-fpm AS base

WORKDIR /var/www

# System libraries required to build the gd and zip PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libwebp-dev \
        libfreetype6-dev \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Required PHP extensions for Laravel + MySQL + media/zip handling
# pcntl is required by `php artisan reverb:start` for signal handling (SIGINT/SIGTERM)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip bcmath pcntl

# Node.js, so Vite/Inertia assets can be built and hot-reloaded
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Composer binary, copied straight from the official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --- dev: code comes from a bind mount, composer/npm install at container
# start (see docker-compose.yml's `command`) so the image itself stays empty ---
FROM base AS dev

EXPOSE 8000 5173 8080

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# --- production: self-contained image, code + built assets baked in, served
# by php-fpm on 9000 (nginx proxies *.php there, see deploy/nginx/default.conf) ---
FROM base AS production

COPY --chown=www-data:www-data . .

# Recreate the runtime-only storage/cache directories excluded by .dockerignore
# (they're empty in the repo, kept only via .gitignore, so the build context
# never has them) before anything tries to write into them.
RUN mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing/disks \
        storage/framework/views \
        storage/logs \
    && touch storage/logs/laravel.log

RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

RUN npm ci \
    && npm run build \
    && rm -rf node_modules

# public/storage was excluded from the build context (it's a symlink into a
# path that only exists once storage/ is mounted), so recreate it here.
RUN php artisan storage:link

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
