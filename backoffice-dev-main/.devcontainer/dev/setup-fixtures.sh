#!/bin/sh

# Script options variables
FIXTURE_SET="DevFixtures"

# Print instructions and exit
usage () {
        echo
        echo "Usage: $(basename $0) [-thmrd]" 2>&1
        echo
        echo "Re-setup database schema and load fixtures"
        echo
        echo "   -t   Use extended testing fixtures set"
        echo "   -m   Use massed volume fixtures set"
        echo "   -r   Use trade system fixtures set"
        echo "   -d   Use demo fixtures set"
        echo "   -h   Show available options"
        echo
        exit 1
}

# Resolve options
while getopts :thmrd arg; do
  case ${arg} in
    t)
      FIXTURE_SET="DevTestFixtures"
      echo "Using extended testing fixture set"
      ;;
    m)
      FIXTURE_SET="DevVolumeFixtures"
      echo "Using massed volume fixture set"
      ;;
    r)
      FIXTURE_SET="DevTradeFixtures"
      echo "Using trade system fixture set"
      ;;
    d)
      FIXTURE_SET="DemoFixtures"
      echo "Using demo fixture set"
      ;;
    h)
      usage
      ;;
    ?)
      echo "Invalid option: -${OPTARG}."
      usage
      ;;
  esac
done

# Load in variables from the .env defaults
source $(dirname "$0")/.env.docker

echo "Setting up database schema and loading initial data fixtures"
php bin/console doctrine:schema:drop --full-database --force
php bin/console doctrine:schema:update --force --complete
php bin/console doctrine:fixtures:load --no-interaction --group=$FIXTURE_SET

echo "Loading database views"
mysql -h db -u $DATABASE_USER -p$DATABASE_PASSWORD crowddb < src/SQLReports/ViewSetup.sql

echo "Syncing doctrine migrations"
php bin/console doctrine:migrations:sync-metadata-storage
php bin/console doctrine:migrations:version --add --all --no-interaction