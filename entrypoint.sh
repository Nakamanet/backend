#!/bin/sh
set -e

# Wait for Postgres to be ready
echo "Waiting for database..."
until php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
  echo "DB not ready, retrying in 2s..."
  sleep 2
done
echo "Database is ready."

if [ ! -d "vendor" ]; then
    composer install --no-interaction --optimize-autoloader
fi

if [ -z "$(grep APP_KEY .env | cut -d '=' -f 2)" ]; then
    php artisan key:generate
fi

php artisan migrate --force

exec "$@"
