ARG BASE_FRANKENPHP_TAG=php8.5-alpine
FROM dunglas/frankenphp:${BASE_FRANKENPHP_TAG}

# Either copy over default development config
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
# Or copy over a custom one
# COPY --link ./conf.d/app.ini $PHP_INI_DIR/conf.d/

RUN apk add --no-cache \
		acl \
		file \
		gettext \
		git \
        icu-dev \
	;

RUN set -eux; \
	install-php-extensions \
		@composer \
		bcmath \
		intl \
		pdo \
		pdo_mysql \
		redis \
		zip \
	;
