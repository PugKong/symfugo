# syntax=docker/dockerfile:1.4

FROM composer/composer:2-bin AS composer-upstream
FROM mlocati/php-extension-installer:latest AS php-extension-installer-upstream
FROM caddy:2.7-alpine AS caddy-upstream


FROM php:8.3-fpm-alpine AS php-base

COPY --from=composer-upstream --link /composer /usr/local/bin
COPY --from=php-extension-installer-upstream --link /usr/bin/install-php-extensions /usr/local/bin

RUN apk add --no-cache shadow
RUN set -eux; install-php-extensions apcu intl opcache zip pcntl http xdebug

COPY --link docker/php/app.ini $PHP_INI_DIR/conf.d/

WORKDIR /app


FROM php-base AS php-dev

RUN mv $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

COPY --link docker/php/app.dev.ini $PHP_INI_DIR/conf.d/

ARG UID=1000
ARG GID=1000
RUN usermod -u $UID www-data && groupmod -g $GID www-data

USER www-data


FROM php-base AS php-e2e

RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini

COPY --link docker/php/app.prod.ini $PHP_INI_DIR/conf.d/

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV XDEBUG_MODE=coverage
ENV APP_ENV=prod
ENV APP_RUNTIME="App\\Tests\\CoverageRuntime"

ARG UID=1000
ARG GID=1000
RUN usermod -u $UID www-data && groupmod -g $GID www-data

COPY --link ./assets ./assets
COPY --link ./bin ./bin
COPY --link ./config ./config
COPY --link ./node_modules ./node_modules
COPY --link ./public/index.php ./public/index.php
COPY --link ./src ./src
COPY --link ./templates ./templates
COPY --link ./tests/CoverageRuntime.php ./tests/CoverageRuntime.php
COPY --link ./vendor ./vendor
COPY --link ./var/tailwind ./var/tailwind
COPY --link ./.env ./composer.* ./symfony.lock ./importmap.php ./tailwind.config.js ./

RUN set -eux; \
  mkdir -p var/cache var/log; \
  composer install --no-cache --prefer-dist --no-autoloader --no-scripts --no-progress; \
  composer dump-autoload --classmap-authoritative; \
  composer dump-env prod; \
  composer run-script post-install-cmd; \
  chmod +x bin/console; \
  php bin/console tailwind:build --minify; \
  php bin/console asset-map:compile; \
  sync;

USER www-data


FROM caddy-upstream AS caddy-e2e

COPY --link docker/caddy/Caddyfile.e2e /etc/caddy/Caddyfile
COPY --link --from=php-e2e /app/public/assets /app/assets
