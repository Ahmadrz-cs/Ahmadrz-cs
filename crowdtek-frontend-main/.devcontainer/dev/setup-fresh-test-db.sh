#!/bin/sh

# Use this script to recreate the backoffice database (for devcontainer setup)
# And load the frontend test database to it

# Script options variables
CREATE_CHECKPOINT="false"

# Print instructions and exit
usage () {
        echo
        echo "Usage: $(basename $0) [-ch]" 2>&1
        echo
        echo "Recreate database and load test database dump"
        echo
        echo "   -c   Create a checkpoint database dump with current date"
        echo "   -h   Show available options"
        echo
        exit 1
}

# Resolve options
while getopts :ch arg; do
  case ${arg} in
    c)
      CREATE_CHECKPOINT="true"
      echo "Creating checkpoint dump with date suffix"
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

# Load in variables from the devcontainer config .env
source $(dirname "$0")/../.env

# Recreate the database
echo "Recreating database: $DATABASE_NAME"
mysql -h db -u $DATABASE_USER -p$DATABASE_PASSWORD -e "drop database $DATABASE_NAME;"
mysql -h db -u $DATABASE_USER -p$DATABASE_PASSWORD -e "create database $DATABASE_NAME;"

# Load frontend test database dump
echo "Loading test db dump"
mysql -h db -u $DATABASE_USER -p$DATABASE_PASSWORD $DATABASE_NAME < tests/_data/test_db_with_fixtures.sql

# Run composer install if vendor directory doesn't exist or forcing defaults
if [ $CREATE_CHECKPOINT = 'true' ] ; then
    echo "Creating dated checkpoint copy of test db at: tests/_data/test_$(date +%F).sql"
    cp tests/_data/test_db_with_fixtures.sql tests/_data/test_$(date +%F).sql
else
    echo "Test database checkpoint not requested"
fi
