#!/bin/sh
# Installer les dépendances si le dossier vendor est vide
if [ ! -d "vendor" ]; then
    composer install --no-interaction --optimize-autoloader
fi

# Générer la clé si elle manque dans le .env
if [ -z "$(grep APP_KEY .env | cut -d '=' -f 2)" ]; then
    php artisan key:generate
fi

# Lancer les migrations
php artisan migrate --force

# Lancer PHP-FPM (la commande par défaut de l'image)
exec php-fpm