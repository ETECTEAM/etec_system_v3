FROM php:8.2-fpm AS php-base

RUN apt-get update && apt-get install -y \
    curl \
    git \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    libsqlite3-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    zip \
    && docker-php-ext-install \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

FROM php-base AS dev

COPY --from=node:20-bullseye-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-bullseye-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

CMD ["php-fpm"]

FROM composer:2 AS composer-deps

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-dev \
    --no-scripts

FROM node:20-alpine AS frontend-build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources resources
COPY public public
COPY vite.config.js ./

RUN npm run build

FROM php-base AS production

COPY . /var/www
COPY --from=composer-deps /app/vendor /var/www/vendor
COPY --from=frontend-build /app/public/build /var/www/public/build

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

USER www-data

EXPOSE 9000

CMD ["php-fpm"]

FROM nginx:1.27-alpine AS nginx

WORKDIR /var/www

COPY public /var/www/public
COPY --from=frontend-build /app/public/build /var/www/public/build
COPY deploy/nginx/default.conf /etc/nginx/conf.d/default.conf
