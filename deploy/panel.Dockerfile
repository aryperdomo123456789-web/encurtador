# Imagem de produção do painel Laravel.
# Runtime: PHP 8.3 + servidor web embutido (php-fpm + `php artisan serve` seria
# ok para dev; em produção usamos o PHP built-in atrás do Caddy para simplificar).
# Para escala maior, trocar por php-fpm + nginx.

FROM php:8.3-cli-alpine

RUN apk add --no-cache \
        bash git icu-dev libzip-dev oniguruma-dev postgresql-dev mariadb-connector-c-dev \
        libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-install \
        bcmath intl mbstring pdo pdo_mysql opcache zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Instala dependências primeiro para aproveitar cache
COPY panel/laravel/composer.json panel/laravel/composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

# Copia o resto do código
COPY panel/laravel /app
RUN composer dump-autoload --optimize --no-dev \
    && chown -R nobody:nobody storage bootstrap/cache

COPY deploy/panel/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

EXPOSE 8000
USER nobody
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
