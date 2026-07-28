FROM php:8.3-cli-bookworm AS base

WORKDIR /var/www

RUN apt-get update && apt-get install -y --no-install-recommends \
        bash \
        ca-certificates \
        curl \
        git \
        unzip \
        zip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libwebp-dev \
        libfreetype6-dev \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libsqlite3-dev \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY deploy/php/uploads.ini /usr/local/etc/php/conf.d/zz-uploads.ini

RUN mkdir -p \
        /home/app/.composer/cache \
        /home/app/.npm \
        /var/www/vendor \
        /var/www/node_modules \
    && chown -R 1000:1000 /home/app /var/www

FROM base AS dev

COPY --from=node:20-bullseye-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-bullseye-slim /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

RUN apt-get update && apt-get install -y --no-install-recommends \
        vim \
        nano \
        iputils-ping \
    && rm -rf /var/lib/apt/lists/*

CMD ["bash"]

# ---------------------------------------------------------------------------
# Production: PHP-FPM + pre-built frontend assets, no bind mount, no dev
# tooling. Runs behind the nginx config in deploy/nginx (fastcgi_pass app:9000).
# ---------------------------------------------------------------------------

# Installs vendor/ once, reused by both the frontend build (vite.config.js
# resolves the ziggy-js alias to vendor/tightenco/ziggy) and the final image.
FROM base AS composer-deps

COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

FROM node:20-bullseye-slim AS frontend-build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
COPY --from=composer-deps /var/www/vendor ./vendor
RUN npm run build

FROM php:8.3-fpm-bookworm AS production

WORKDIR /var/www

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        unzip \
        zip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libwebp-dev \
        libfreetype6-dev \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libsqlite3-dev \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        zip \
    && docker-php-ext-enable opcache

COPY deploy/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY deploy/php/uploads.ini /usr/local/etc/php/conf.d/zz-uploads.ini

COPY --from=composer-deps /var/www /var/www
COPY --from=frontend-build /app/public/build ./public/build

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

USER www-data
EXPOSE 9000
CMD ["php-fpm"]
