#!/bin/sh
set -eu
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
if [ ! -f vendor/autoload.php ]; then composer install --no-interaction --prefer-dist; fi
exec "$@"
