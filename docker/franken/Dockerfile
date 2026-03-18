FROM dunglas/frankenphp:php8.5-alpine

RUN apk add --no-cache git unzip

RUN install-php-extensions \
    intl \
    zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY docker/franken/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
ENTRYPOINT ["docker-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
