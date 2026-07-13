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
# Production
# ---------------------------------------------------------------------------

FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-scripts \
        --no-autoloader \
        --prefer-dist

COPY . .
RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

FROM node:20-bullseye-slim AS frontend

WORKDIR /var/www
COPY package.json package-lock.json ./
RUN npm ci
COPY --from=vendor /var/www /var/www
RUN npm run build

FROM php:8.3-fpm-bookworm AS prod

WORKDIR /var/www

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        libpng-dev \
        libjpeg62-turbo-dev \
        libwebp-dev \
        libfreetype6-dev \
        libicu-dev \
        libonig-dev \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip

COPY deploy/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY deploy/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf

COPY --from=vendor --chown=www-data:www-data /var/www /var/www
COPY --from=frontend --chown=www-data:www-data /var/www/public/build /var/www/public/build

RUN mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]

FROM nginx:1.27-alpine AS nginx-prod

COPY deploy/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=vendor /var/www/public /var/www/public
COPY --from=frontend /var/www/public/build /var/www/public/build

EXPOSE 80