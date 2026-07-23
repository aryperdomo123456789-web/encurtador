#!/usr/bin/env bash
set -euo pipefail

# Garante APP_KEY, roda migrations e caches, depois entrega ao processo passado.
cd /app

if [ -z "${APP_KEY:-}" ]; then
    echo "[entrypoint] APP_KEY vazio; gerando um novo (defina APP_KEY no .env para persistir)."
    php artisan key:generate --force --no-interaction
fi

php artisan migrate --force --no-interaction || {
    echo "[entrypoint] Falha ao rodar migrations" >&2
    exit 1
}

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
