# Intended for replicating the CI setup in an alpine PHP image

echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/docker-php-mem-limit.ini

apk add --no-cache libjpeg-turbo-dev libpng-dev libwebp-dev freetype-dev libzip-dev mariadb-client

docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp

docker-php-ext-install pdo pdo_mysql opcache calendar gd zip > /dev/null

curl --silent --show-error https://getcomposer.org/installer | php

php composer.phar install --prefer-dist

php vendor/bin/codecept build

apk update && apk add bash

curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | version=any-version bash

apk add symfony-cli=5.10.2
