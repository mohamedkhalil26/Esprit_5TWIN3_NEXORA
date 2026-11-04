# syntax=docker/dockerfile:1
FROM node:22-alpine AS assets
WORKDIR /src
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /src
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --optimize-autoloader
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts

FROM php:8.3-fpm-alpine AS app
RUN apk add --no-cache icu-libs libpng libjpeg-turbo freetype libzip oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl opcache pdo_mysql zip \
    && apk del .build-deps
WORKDIR /var/www/html
COPY --from=vendor --chown=www-data:www-data /src /var/www/html
COPY --from=assets --chown=www-data:www-data /src/public/build /var/www/html/public/build
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache
COPY docker/php/zz-nexora.ini /usr/local/etc/php/conf.d/zz-nexora.ini
EXPOSE 9000
CMD ["php-fpm"]
