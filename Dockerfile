############################################
# Base Image
############################################
FROM serversideup/php:8.4-fpm-nginx AS base

USER root

# Install PHP extensions yang dibutuhkan
RUN install-php-extensions intl gd bcmath

USER www-data

############################################
# Production Image
############################################
FROM base AS production

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

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

# Copy semua source (termasuk public/build yang sudah di-commit)
COPY . .

# Dump autoload + fix permissions + buat direktori cache
RUN composer dump-autoload --optimize \
    && mkdir -p \
        bootstrap/cache/filament/panels \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache

# Entrypoint script untuk php artisan optimize saat container start
COPY --chmod=755 docker/optimize.sh /etc/entrypoint.d/99-optimize.sh

USER www-data
