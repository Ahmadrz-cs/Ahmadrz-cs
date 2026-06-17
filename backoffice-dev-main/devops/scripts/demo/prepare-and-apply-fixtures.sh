#!/bin/sh

# Note that this script is intended for dev deployments as it wipes the database
# It should NOT be used in production

COMPOSER_MEMORY_LIMIT=512M composer install --prefer-dist --no-progress --no-interaction

echo "Dropping and applying database schema"
php bin/console doctrine:schema:drop --full-database --force
php bin/console doctrine:schema:update --force --complete

echo "Applying dev fixtures with increased memory limit"
php -d memory_limit=512M bin/console doctrine:fixtures:load --no-interaction --group=DemoFixtures  --env=dev

echo "Loading database views"
php bin/console dbal:run-sql "$(cat src/SQLReports/ViewSetup.sql)"

echo "Setting up doctrine migrations metadata"
php bin/console doctrine:migrations:sync-metadata-storage
php bin/console doctrine:migrations:version --add --all --no-interaction