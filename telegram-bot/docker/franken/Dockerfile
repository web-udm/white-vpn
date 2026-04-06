FROM dunglas/frankenphp:php8.5-alpine

RUN apk add --no-cache git unzip

RUN install-php-extensions \
    intl \
    zip \
    pdo_mysql

WORKDIR /app

COPY composer.json composer.lock ./
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --prefer-dist --no-progress

COPY . .

COPY docker/franken/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
ENTRYPOINT ["docker-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
