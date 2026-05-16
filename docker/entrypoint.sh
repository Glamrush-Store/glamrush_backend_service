#!/usr/bin/env sh
set -e

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${CACHE_CONFIG:-true}" = "true" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan event:cache
    php artisan view:cache
fi

exec "$@"
