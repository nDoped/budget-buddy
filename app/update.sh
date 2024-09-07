#!/bin/bash

echo "Stopping the app"
php artisan down
echo "Creating database dump"


if [ ! -d "${HOME}/database-dumps" ]; then
    mkdir "${HOME}/database-dumps"
fi
file_date=$(date +%F_%H_%M_%S)
sudo mysqldump  mykickass_db > "${HOME}/database-dumps/${file_date}_mykickass_db.sql"

echo "Pulling updates"
git pull --rebase

echo " composer install"
php composer.phar install

echo "npm install"
npm install

echo "Building"
npm run build

echo "Running migrations"
php artisan migrate --force

function cache_and_start_server {
    echo "Refreshing caches"
    php artisan config:cache
    php artisan view:cache
    php artisan route:cache

    echo "Starting the app"
    php artisan up
}

trap cache_and_start_server EXIT
