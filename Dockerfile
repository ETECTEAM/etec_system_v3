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

# In dev the repo is bind-mounted into the container and owned by the host
# user, which git treats as "dubious ownership" and refuses to operate on.
# Mark it safe system-wide so git works for every user in the image.
RUN git config --system --add safe.directory /var/www

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
# start (see docker/entrypoint-dev.sh) so the image itself stays empty ---
FROM base AS dev

# App user with uid 1000 = the host user that owns the bind-mounted repo.
# Pre-create its home and cache dirs owned by uid 1000 so Docker seeds the
# composer/npm named cache volumes (mounted over these paths) writable.
# Without this, composer fails writing ~/.composer and the container exits
# immediately -> crash loop.
# setpriv (util-linux) lets the entrypoint drop from root to the app user.
RUN useradd --uid 1000 --user-group --home-dir /home/app --create-home --shell /bin/sh app \
    && mkdir -p /home/app/.composer/cache /home/app/.npm \
    && chown -R app:app /home/app

COPY docker/entrypoint-dev.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 8000 5173 8080

ENTRYPOINT ["/usr/local/bin/entrypoint"]

# --- production: runtime only, no application code baked in. Code comes
# from the deploy host's bind mount (.:/var/www, see docker-compose.prod.yml)
# - deploy.sh runs composer install / npm build against that mount directly,
# so doing it again here would be redundant, and worse: baking the ever-
# changing repo into the image meant `docker compose build` produced a new
# image on every single deploy, forcing every container to be destroyed and
# recreated every time even though the running code never actually came from
# the image in the first place (bind mount shadows it entirely). Keeping
# this stage code-free means the image - and the need to recreate containers
# from it - only changes when the Dockerfile itself changes. ---
FROM base AS production

# storage/ and bootstrap/cache are separate named volumes (see
# docker-compose.prod.yml), not part of the bind mount. These directories
# exist only to seed those volumes with the right structure and a
# www-data-writable owner the first time each volume is created - unrelated
# to app source, so they don't change on ordinary code deploys.
RUN mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing/disks \
        storage/framework/views \
        storage/logs \
    && touch storage/logs/laravel.log \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
