#!/bin/sh
set -e

if [ "$APP_ENV" = "dev" ]; then
    composer install --no-interaction --prefer-source
fi

mkdir -p var/data

if [ "$1" != "php" ]; then
    php bin/console doctrine:migrations:migrate --no-interaction
fi

exec "$@"