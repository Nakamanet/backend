FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash git curl unzip icu-dev oniguruma-dev \
    postgresql-dev \
    libzip-dev \
  && docker-php-ext-install pdo pdo_pgsql intl mbstring zip opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Optional: speed up local dev permissions
RUN addgroup -g 1000 www && adduser -G www -g www -s /bin/sh -D -u 1000 www
USER www
