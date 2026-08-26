#!/usr/bin/env sh
set -eu

cd /app/panel/laravel

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache

composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

if [ -z "${APP_KEY:-}" ]; then
  echo "APP_KEY is required for production." >&2
  exit 1
fi

php artisan config:clear
php artisan route:clear
php artisan view:clear || true
php artisan migrate --force
php artisan storage:link || true

export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"
exec php artisan serve --host=0.0.0.0 --port=8000
