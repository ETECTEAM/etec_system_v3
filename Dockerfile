# Use official PHP 8.2 image with PHP-FPM
# PHP-FPM is the PHP process manager used with Nginx
FROM php:8.2-fpm

# Update Linux package list
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev

# Install PHP extensions required by Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

# Copy Composer from the official Composer image
# Composer is Laravel's dependency manager
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory inside the container
# Laravel project will run from this directory
WORKDIR /var/www
