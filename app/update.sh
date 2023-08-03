#!/bin/bash

echo "Stopping the app"
php artisan down

echo "Pulling updates"
sudo git pull --rebase

echo " composer install"
php composer.phar install

echo "npm install"
sudo npm install

echo "Building"
npm run build

echo "Running migrations"
php artisan migrate

function cache_and_start_server {
        echo "Refreshing caches"
        php artisan config:cache
        php artisan view:cache
        php artisan route:cache

        echo "Starting the app"
        php artisan up
}

trap cache_and_start_server EXIT
