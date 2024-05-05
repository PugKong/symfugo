# syntax=docker/dockerfile:1.4

FROM composer/composer:2-bin AS composer-upstream
FROM mlocati/php-extension-installer:latest AS php-extension-installer-upstream


FROM php:8.3-fpm-alpine AS php-base

COPY --from=composer-upstream --link /composer /usr/local/bin
COPY --from=php-extension-installer-upstream --link /usr/bin/install-php-extensions /usr/local/bin

RUN apk add --no-cache shadow
RUN set -eux; install-php-extensions apcu intl opcache zip pcntl http xdebug

COPY --link docker/php/app.ini $PHP_INI_DIR/conf.d/

WORKDIR /app


FROM php-base AS php-dev

RUN mv $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

COPY --link docker/php/app.ini $PHP_INI_DIR/conf.d/
COPY --link docker/php/app.dev.ini $PHP_INI_DIR/conf.d/

ARG UID=1000
ARG GID=1000
RUN usermod -u $UID www-data && groupmod -g $GID www-data

USER www-data
