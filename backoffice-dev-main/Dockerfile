#syntax=docker/dockerfile:1.4

# Versions
ARG BASE_FRANKENPHP_TAG=1.11-php8.5-alpine
FROM dunglas/frankenphp:${BASE_FRANKENPHP_TAG} AS frankenphp_upstream
# FROM dunglas/frankenphp:1-alpine AS frankenphp_upstream

# The different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target

# Build webpack assets first
FROM node:lts-alpine AS webpack_build

WORKDIR /app

COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Base FrankenPHP image
FROM frankenphp_upstream AS frankenphp_base

# persistent / runtime deps
# hadolint ignore=DL3018
RUN apk add --no-cache \
		acl \
		file \
		gettext \
		git \
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

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --link frankenphp/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY --link --chmod=755 frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link frankenphp/Caddyfile /etc/caddy/Caddyfile

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1
CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile" ]

# Dev FrankenPHP image
# FROM frankenphp_base AS frankenphp_dev

# ENV APP_ENV=dev XDEBUG_MODE=off
# VOLUME /app/var/

# RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# RUN set -eux; \
# 	install-php-extensions \
# 		xdebug \
# 	;

# COPY --link frankenphp/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

# CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch" ]

# Prod FrankenPHP image
FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod
# ENV FRANKENPHP_CONFIG="import worker.Caddyfile"

# prevent the reinstallation of vendors at every changes in the source code
COPY --link composer.* symfony.* ./
RUN set -eux; \
	# composer install --no-cache --prefer-dist --no-autoloader --no-scripts --no-progress
	composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

# copy sources
COPY --link . ./
# overwrite configs
COPY --link ./frankenphp/symfony/config/packages/monolog.yaml ./config/packages/monolog.yaml

# Copy over webpack assets build from previous stage
COPY --link --from=webpack_build /app/public ./public/
# Cleanup directories not needed for deployment
RUN rm -Rf frankenphp/

RUN set -eux; \
	mkdir -p var/cache var/log; \
	composer dump-autoload --classmap-authoritative --no-dev; \
	composer dump-env prod; \
	composer run-script --no-dev post-install-cmd; \
	chmod +x bin/console; sync;

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link frankenphp/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/
# COPY --link frankenphp/worker.Caddyfile /etc/caddy/worker.Caddyfile

# Download AWS RDS certificate bundle for eu-west-2
ADD https://truststore.pki.rds.amazonaws.com/eu-west-2/eu-west-2-bundle.pem ./config/certificates/rds-eu-west-2-bundle.pem
