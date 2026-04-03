#!/bin/sh
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash git curl unzip icu-dev \
    postgresql-dev \
    libzip-dev

RUN docker-php-ext-install pdo pdo_pgsql intl mbstring zip opcache

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-interaction

COPY . .

RUN composer dump-autoload --optimize

RUN addgroup -g 1000 www && adduser -G www -g www -s /bin/sh -D -u 1000 www
RUN chown -R www:www /var/www/html

# Already correct in your Dockerfile — but the volume is the problem
COPY entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER www


ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
