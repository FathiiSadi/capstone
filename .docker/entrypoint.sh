#!/bin/sh

# Exit on error
set -e

# Run migrations (careful in production with auto-migrate, usually explicit is better, but this ensures functionality)
# only if FORCE_MIGRATE is set
if [ "$FORCE_MIGRATE" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
fi

# Cache configuration, routes, and views if in production
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configuration..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

exec "$@"
