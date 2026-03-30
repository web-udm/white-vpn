#!/bin/sh
set -e

if [ "$APP_ENV" = "dev" ]; then
    composer install --no-interaction --prefer-source
fi

exec "$@"