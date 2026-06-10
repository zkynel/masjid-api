#!/usr/bin/env bash
set -e

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force
php artisan storage:link || true

php artisan config:cache
php artisan route:cache

apache2-foreground