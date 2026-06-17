#!/bin/sh

# Use this script to dump the backoffice database to a dump
# And clean out DEFINER statements that affect loading the dump into other databases (like the CI)

# Load in variables from the devcontainer config .env
source $(dirname "$0")/../.env

# Load frontend test database dump
echo "Creating new test db dump"
mysqldump --hex-blob -h db -u $DATABASE_USER -p$DATABASE_PASSWORD $DATABASE_NAME > tests/_data/test_db_with_fixtures.sql

# Remove definer statements from the dump - usually in the final view structures
# See https://stackoverflow.com/a/67673897 for stackoverflow post on this issue
# Alternatively see https://stackoverflow.com/a/24613430 - but this doesn't remove the entire line
echo "Removing DEFINER statements"
sed -i '/^[/][*]!50013 DEFINER=/d' tests/_data/test_db_with_fixtures.sql