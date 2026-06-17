## Unit tests

- PHP unit tests can be run with the standard PHPUnit command
- Due to the legacy nature of this project, most of the older code is not unit tested or at least to the extent backoffice is

```shell
vendor/bin/phpunit
```

## Performance tests

- `k6` should be installed as a devcontainer feature if you are using devcontainers

```shell
# Can use other test scripts, mini is the simplest as it only runs against the homepage
k6 run tests/performance/mini.js

# Override script config for VUs and duration via options
k6 run tests/performance/mini.js --vus 5 --duration 60s
```

## Running acceptance tests with selenium

- The selenium container isn't started by default when you start the dev container
- This is due to the system resource cost of the selenium container
  - 2GB RAM
  - 450MB download (compressed)
  - 1.2GB storage (uncompressed)

### Start the selenium container

- If you need to run acceptance tests, first start the selenium container
- You can run this inside the devcontainer or if you prefer, on your host-machine

```shell
docker compose -f .devcontainer/compose.selenium.yaml up
```

- If you want to reuse the current terminal window, run docker compose up in detached mode with the `-d` option

```shell
docker compose -f .devcontainer/compose.selenium.yaml up -d
```

### Run tests on the selenium container

- To actually run tests, use the codeception environment called `docker-chrome`
  - You can compare how this differs with what the CI does at the moment (as of 2022-02-23)

```shell
# Example command to run the commit suite of tests
vendor/bin/codecept run tests/acceptance/commit/ --env docker-chrome
```

```shell
# Example command to run a single test class/file
vendor/bin/codecept run tests/acceptance/commit/public-page-testing/PageAboutUsCest.php --env docker-chrome
```

- You can watch the tests running in selenium by going to http://localhost:7900/

### Stop the selenium container

- This depends on whether you started the selenium container in the foreground or as a background process.
- If you originally ran the docker compose up in the foreground, just end the process with `Ctrl-C`
- If you did run the docker compose in the background with detached mode, use the `down` command instead of `up`

```shell
docker compose -f .devcontainer/compose.selenium.yaml down
```

## Manually loading the testing database

- The following instructions assume you are using the devcontainer setup and running the commands inside your primary devcontainer used by the code editor (e.g. VS Code)
- The default database credentials are configured in backoffice's devcontainer setup
- They can be locally found in `.devcontainer/.env`

### Directly

```shell
mysql -h db -u ydev -pYankeeDelta522 crowddb < tests/_data/test_db_with_fixtures.sql
```

### Via devcontainer scripts

```shell
bash .devcontainer/dev/setup-fresh-test-db.sh
```

## Updating the testing database

### Setup with current testing database

- This will recreate the database and load the test database dump
- If you want a checkpoint of this database, use the `-c` option
  - This will create a copy of the testing database dump with the datetime appended to the filename
  - You may find this useful

```shell
bash .devcontainer/dev/setup-fresh-test-db.sh

# With checkpoint
bash .devcontainer/dev/setup-fresh-test-db.sh -c
```

### Make any changes to the database

- You'll do this via backoffice as the frontend repo has no control over the database schema
- You can directly make changes in the CMS
- Or apply any database schema migrations from backoffice

```shell
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:list
php bin/console doctrine:migrations:migrate
```

### Create new testing database dump

- This will overwrite the existing testing database dump
- If you want a copy of the original, you should create a checkpoint when loading the original testing databsae dump

```shell
bash .devcontainer/dev/setup-new-test-db-dump.sh
```


#### Manual Action: Ensuring `onboarding_profile` `categoryReviewedAt` is recent in the test db dump

- The `onboarding_profile` `categoryReviewedAt` needs to be set to a date time within the last 12 months to ensure the user is not prompted to update their category
- The easiest way to do this is to use `NOW()` instead of a date time string in the database dump
- Alternatively, just change the date of `categoryReviewedAt` to something more recent
- Currently the `setup-new-test-db-dump.sh` does not automatically do this for you, so you'll need to manually check this
  - If the datetime goes beyond 12 months ago, certain acceptance tests will start failing

```sql
--- With datetime string
INSERT INTO `onboarding_profile` VALUES (1,'2019-12-10 12:23:00',1,1,'sophisticated','2025-10-22 15:30:08',1,'2024-08-12 12:56:00','2024-08-12 12:58:39',1,1);
--- With NOW() function
INSERT INTO `onboarding_profile` VALUES (1,'2019-12-10 12:23:00',1,1,'sophisticated',NOW(),1,'2024-08-12 12:56:00','2024-08-12 12:58:39',1,1);
```

## Code fixer

```shell
# Identify issues
vendor/bin/ecs check

# Fix issues
vendor/bin/ecs check --fix
```