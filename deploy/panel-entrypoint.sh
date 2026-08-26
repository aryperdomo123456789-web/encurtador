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

fpm_children="${FPM_MAX_CHILDREN:-12}"
fpm_start="${FPM_START_SERVERS:-3}"
fpm_min_spare="${FPM_MIN_SPARE_SERVERS:-2}"
fpm_max_spare="${FPM_MAX_SPARE_SERVERS:-6}"

case "$fpm_children:$fpm_start:$fpm_min_spare:$fpm_max_spare" in
  *[!0-9:]*|'0'*|*:[!1-9]*|*:[!0-9]*:*|*:*:0:*|*:*:*:0*)
    echo "FPM process settings must be positive integers." >&2
    exit 1
    ;;
esac

sed -ri "s!^pm\.max_children = .*!pm.max_children = ${fpm_children}!" /usr/local/etc/php-fpm.d/www.conf
sed -ri "s!^pm\.start_servers = .*!pm.start_servers = ${fpm_start}!" /usr/local/etc/php-fpm.d/www.conf
sed -ri "s!^pm\.min_spare_servers = .*!pm.min_spare_servers = ${fpm_min_spare}!" /usr/local/etc/php-fpm.d/www.conf
sed -ri "s!^pm\.max_spare_servers = .*!pm.max_spare_servers = ${fpm_max_spare}!" /usr/local/etc/php-fpm.d/www.conf

php-fpm -t
nginx -t
php-fpm -D
exec nginx -g 'daemon off;'
