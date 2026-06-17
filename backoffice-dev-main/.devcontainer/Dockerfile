# [Choice] PHP version (use -bookworm variants on local arm64/Apple Silicon): 8, 8.1, 8.0, 8-bookworm, 8.1-bookworm, 8.1-bookworm,
ARG VARIANT=8-bookworm
FROM mcr.microsoft.com/vscode/devcontainers/php:${VARIANT}
# FROM php:8.0

# Install MariaDB client and other dev tools
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y \
        mariadb-client \
        iputils-ping \ 
        acl \ 
        libicu-dev \
    && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# Install https://github.com/mlocati/docker-php-extension-installer from Docker image
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

RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Temp workaround for disabling xdebug until the trigger options are correctly set by default
COPY php/conf.d/xdebug.ini "$PHP_INI_DIR/conf.d/xdebug.ini"

# [Optional] Uncomment this section to install additional OS packages.
# RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
#     && apt-get -y install --no-install-recommends <your-package-list-here>

# [Optional] Uncomment this line to install global node packages.
# RUN su vscode -c "source /usr/local/share/nvm/nvm.sh && npm install -g <your-package-here>" 2>&1

