#!/bin/sh
set -e

if [ -d /opt/vendor ]; then
    mkdir -p vendor
    find vendor -mindepth 1 -maxdepth 1 -exec rm -rf {} +
    cp -a /opt/vendor/. vendor/
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

if [ "$#" -eq 0 ]; then
    set -- php artisan serve --host=0.0.0.0 --port=8000
fi

php artisan config:clear
php artisan route:clear
php artisan view:clear

if [ "$1" = "php" ] && [ "$2" = "artisan" ] && [ "$3" = "serve" ]; then
    attempt=1
    until php artisan migrate --force; do
        if [ "$attempt" -ge 30 ]; then
            echo "Database migration failed after $attempt attempts."
            exit 1
        fi

        echo "Database is not ready yet. Retrying migration in 2 seconds..."
        attempt=$((attempt + 1))
        sleep 2
    done

    php artisan l5-swagger:generate
fi

exec "$@"
