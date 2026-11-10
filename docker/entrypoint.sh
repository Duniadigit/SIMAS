#!/bin/sh
set -e

# Buat symbolic link storage jika belum ada
if [ ! -L "/var/www/public/storage" ]; then
    php artisan storage:link || true
fi

# Pastikan permission folder storage dan bootstrap cache selalu benar
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

exec "$@"
