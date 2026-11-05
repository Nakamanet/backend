#!/bin/sh
set -e

echo "Waiting for database..."
until php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
  echo "DB not ready, retrying in 2s..."
  sleep 2
done
echo "Database is ready."

php artisan config:clear
php artisan migrate --force
php artisan db:seed --class=ForumDefaultPinsSeeder --force

exec "$@"