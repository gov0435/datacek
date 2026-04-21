############################################
# Base Image
# Pakai Debian (bukan alpine) karena Node.js
# diinstall via nodesource apt repository
############################################
FROM serversideup/php:8.4-fpm-nginx AS base

USER root

# Install PHP extensions yang dibutuhkan
# (pdo_pgsql, redis, zip, sodium, pcntl sudah ada di base image)
RUN install-php-extensions intl gd bcmath

# Install Node.js 22 untuk build frontend assets
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

USER www-data

############################################
# CI Stage: Install deps & copy source
# Node ada di sini → vendor/ tersedia saat
# npm run build → Filament CSS resolve ✅
############################################
FROM base AS ci

USER root

WORKDIR /var/www/html

# Copy composer files dulu → layer cache efisien
COPY composer.json composer.lock ./

# Install tanpa scripts (artisan belum ada)
RUN composer install \
    --no-scripts \
    --no-dev \
    --no-interaction \
    --prefer-dist

# Copy semua source (vendor/ sudah ada → Filament CSS path resolve)
COPY . .

# Dump autoload + fix permissions
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/html

USER www-data

############################################
# Build Stage: Compile frontend assets
############################################
FROM ci AS build

RUN npm ci \
    && npm run build \
    && rm -rf node_modules

############################################
# Production Image
############################################
FROM base AS production

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
# Autorun diatur via compose, bukan hardcode di sini
# supaya bisa di-override per service (queue/scheduler = false)

USER root

WORKDIR /var/www/html

# Copy full build result dari stage build
COPY --from=build /var/www/html /var/www/html

# Buat direktori cache yang dibutuhkan Filament & Laravel
RUN mkdir -p \
        /var/www/html/bootstrap/cache/filament/panels \
        /var/www/html/storage/framework/cache \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Entrypoint script untuk php artisan optimize saat container start
COPY --chmod=755 docker/optimize.sh /etc/entrypoint.d/99-optimize.sh

USER www-data