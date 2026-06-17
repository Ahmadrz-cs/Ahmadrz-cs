#!/bin/sh

php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:list
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console dbal:run-sql "$(cat src/SQLReports/ViewSetup.sql)"