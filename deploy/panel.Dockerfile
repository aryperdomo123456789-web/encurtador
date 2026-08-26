FROM php:8.3-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        nginx \
        unzip \
        libicu-dev \
        libonig-dev \
        libsqlite3-dev \
        libzip-dev \
    && docker-php-ext-install \
        mbstring \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && sed -ri 's!^listen = 9000!listen = 127.0.0.1:9000!' /usr/local/etc/php-fpm.d/www.conf \
    && sed -ri 's!^;clear_env = no!clear_env = no!' /usr/local/etc/php-fpm.d/www.conf \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY deploy/nginx/panel.conf /etc/nginx/conf.d/default.conf

WORKDIR /app/panel/laravel
