ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-fpm-alpine

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN apk add icu-dev 

# Install https://github.com/mlocati/docker-php-extension-installer? from Docker image
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# pcntl was included to resolve https://gitlab.com/yielders2/backoffice-dev/-/issues/2267
RUN set -eux; \
	install-php-extensions \
		@composer \
		bcmath \
		intl \
        pcntl \
		pdo \
		pdo_mysql \
		redis \
		zip \
	;