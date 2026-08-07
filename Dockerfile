# syntax=docker/dockerfile:1

FROM php:8.3-fpm

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
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip bcmath

# Node.js, so Vite/Inertia assets can be built and hot-reloaded
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Composer binary, copied straight from the official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

EXPOSE 8000 5173

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
