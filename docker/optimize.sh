#!/bin/sh
set -e

if [ "$AUTORUN_ENABLED" = "true" ]; then
    echo "Running Filament optimizations..."
    php /var/www/html/artisan filament:optimize-clear
    php /var/www/html/artisan filament:optimize
    php /var/www/html/artisan icons:cache
    echo "Filament optimizations complete."
else
    echo "AUTORUN_ENABLED is false, skipping Filament optimizations..."
fi
