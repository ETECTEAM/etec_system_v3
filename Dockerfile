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