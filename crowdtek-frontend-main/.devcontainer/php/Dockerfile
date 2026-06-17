ARG PHP_VERSION=8.0
FROM php:${PHP_VERSION}-fpm-alpine

RUN apk add --no-cache \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        freetype-dev \
        libzip-dev

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Install https://github.com/mlocati/docker-php-extension-installer from Docker image
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN set -eux; \
        install-php-extensions \
            @composer \
            apcu \
            bcmath \
            calendar \
            gd \
            intl \
            opcache \
            redis \
            zip \
        ;