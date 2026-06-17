# Yielders Backoffice - CMS and API

[![pipeline status](https://gitlab.com/yielders2/backoffice-dev/badges/main/pipeline.svg)](https://gitlab.com/yielders2/backoffice-dev/commits/main)

Yielders backoffice application consisting of the administrative CMS and the developer orientated API

## Development Overview and Guidelines

### Branching Strategy

Our branching strategy is based on [Microsoft's Azure DevOps guidance](https://docs.microsoft.com/en-us/azure/devops/repos/git/git-branching-guidance).

- Use feature branches for all new features and bug fixes.
- Merge feature branches into the `main` branch using merge requests.
- Keep a high quality, up-to-date `main` branch.

Releases branches are created from `main`. The naming convention for release branches is: `release/<year>-<month>-Release-<count>`. E.g. `release/2022-November-Release-2`. 

Port release changes back to the `main` branch:

1. Create a new feature branch off the main branch to port the changes.
2. Cherry-pick the changes from the release branch to your new feature branch.
3. Merge the feature branch back into the main branch in a second pull request.

### Code Review Process

Merge requests should be approved by 2 developers that are not the author. Although it depends on availability of staff.

Take a look at the [code review guidelines](https://gitlab.com/yielders2/backoffice-dev/-/wikis/Guides/Reviewing-Merge-Requests) for what to look out for.

### Coding Standards

We follow the [PER-3.0 coding style](https://www.php-fig.org/per/coding-style/). The easiest way to adopt this in your workflow is to enable format on save in your code editor.

We use [PHP Easy Coding Standard](https://github.com/symplify/easy-coding-standard) to perform checks and fixes on git commits using Husky.

You can also manually run the checks and fixes with the commands below (`--match-git-diff` option will limit checks to changes detected by git).

```shell
# Check for fixes in uncommitted files
vendor/bin/ecs check --match-git-diff

# Apply fixes to uncommited files
vendor/bin/ecs check --fix --match-git-diff
```

### Static analysis tools

We use PHPStan and Psalm to perform static analysis to identify potential errors. 

The more widespread use of these tools is currently paused until there is time to commit to code upgrades.

```shell
# Run Psalm with our base config psalm.xml
vendor/bin/psalm

# Run Psalm in parallel mode (4 threads demonstrated)
vendor/bin/psalm --threads=4

# Run Psalm on specific paths
vendor/bin/psalm src/Controller/

# Run PHPStan with the our base config phpstan.neon
vendor/bin/phpstan analyse

# Run PHPStan on specific paths like src/ and tests/
vendor/bin/phpstan analyse src tests

# Run PHPStan with a different config
vendor/bin/phpstan analyse src tests -c phpstan-deprecations.neon
```

Additionally, you can install the relavant VSCode plugins to have live analysis as you code

## Setup and installation

- If you are using the devcontainer setup, the majority of the setup should be completed for you
- The only steps that need to manually completed are the setup of missing envvars in your `.env.local` and `.env.test.local`
  - See next step for more info

### Additional Environment Variables

- By default, the `setup-dotenv.sh` will prepare suitable dev defaults into the `.env.local` and `.env.test.local` dotenv files
- However, there is a (growing) list of environment variables that are no longer commited to git
  - Mostly external service integration secrets like API keys, which will move over as they get refreshed
- The dev envvar values are store in AWS SSM Parameter Store on the dev account `376373716954`
- You will need to manually set-up these envvars by copying the values as strings into your `.env.local` and `.env.test.local` files where necessary
  - Certain functionality, such as anything related to Mangopay, may not function properly if not setup
- You can check if 3rd party services are connected properly by going to the Service Status page in backoffice: `/admin/status`
  - Note that dev has no Mailchimp integration as we don't use real emails
  - However, Contego (Northrow) does have a sandbox environment, but the service check requires a real/active KYC report to check against (which usually expire after 30 days, so the data fixtures won't have a valid recent one to load)
- The current list (as of 2026-06) are as follows
  - `MANGOPAY_PASSWORD` -> `/devops/mangopay/client-secret`
  - `MANGOPAY_MTLS_CERT` -> `/devops/mangopay/mtls-cert`
  - `MANGOPAY_MTLS_KEY` -> `/devops/mangopay/mtls-key` 
- For example

```shell
MANGOPAY_PASSWORD="ExampleMangopayClientSecret"
MANGOPAY_MTLS_CERT="LS0...etc...Q=="
MANGOPAY_MTLS_KEY="LS0...etc...Q=="
```

### Available helper scripts

- `.devcontainer/dev/setup-dotenv.sh`
  - Run on initial devcontainer setup
  - Use the `-f` option to override your existing files if you need to "reset" back to original
- `.devcontainer/dev/setup-project.sh`
  - Run on initial devcontainer setup
  - Use the `-d` option to also setup the database dev-test data fixtures (this option is used on initial devcontainer setup)
  - Use the `-f` option to reapply first time setup defaults
- `.devcontainer/dev/setup-fixtures.sh`
  - Useful for resetting the dev database with dev-test data
  - Use the `-t` option to load the extended testing data fixtures set (typically used if running full suite of codeception tests)
  - More info in the "Reloading Fixtures" section
- `.devcontainer/dev/setup-file-permissions.sh`
  - Intended for fixing file permissions for log and cache files
  - Largely deprecated for current devcontainer setup 

### Reinstalling composer managed packages

- If you've recently resynced your working branch with origin and a lot has changed, in particular the `composer.lock` file
- You should ensure your composer managed packages are up-to-date

```shell
composer install
```

## (Re)loading Fixtures

Two main sets of fixtures available

- Use `DevFixtures` for development
- Use `DevTestFixtures` for automated testing like codeception

### Using the fixture loading script

- This script will fully reset the database
  - Recreate the database
  - Load fixtures
  - Run views
  - Sync migrations

```shell
# Load the standard DevFixtures set
bash .devcontainer/dev/setup-fixtures.sh

# Load the extended automated test set
bash .devcontainer/dev/setup-fixtures.sh -t
```

### Manual loading

```shell
# Reset db and load dev fixtures
php bin/console doctrine:schema:drop --full-database --force \
&& php bin/console doctrine:schema:update --force --complete \
&& php bin/console doctrine:fixtures:load -n --group=DevFixtures
```

```shell
# Reset db and load dev test fixtures
php bin/console doctrine:schema:drop --full-database --force \
&& php bin/console doctrine:schema:update --force --complete \
&& php bin/console doctrine:fixtures:load -n --group=DevTestFixtures
```

For first time setups, or after recreating the database (drop and create). Add the views (virtual tables) to the db as well. 

These are used for:

- Custom export reports
- Shareholdings system

```shell
php bin/console dbal:run-sql "$(cat src/SQLReports/ViewSetup.sql)"
```

Alternatively you can load the `ViewSetup.sql` script with a MySQL or MariaDB client. 

Note that the setup-project script with the `-d` option as outlined below will also apply the ViewSetup but at the same time as loading fixtures.

### Using the project setup script

- The project setup script is run when you first startup or rebuild the devcontainer
  - The script will reload fixtures and apply the ViewSetup script as well
- It also offers a `-d` option that can be used any time to reload the `DevFixture` set of fixtures and ViewSetup
  - This can be a useful shortcut during development when the database schema or views have changed upstream

```shell
bash .devcontainer/dev/setup-project.sh -d
```

## Webpack managed static assets

### Install NPM packages

- Should be done as part of devcontainer setup.
- If not using devcontainers, then you'll need to manually do the package installation (into `node_modules/`)

```shell
npm install
```

### Build

```shell
# For ongoing development
npm run watch

# For once-off dev build
npm run dev

# For production (simulation)
npm run build
```

## Running Tests

### Unit tests

```shell
# Run non-email tests parallelised
find tests/ -name "*Test.php" | grep -v "/Email/" | ./vendor/liuggio/fastest/fastest -b "php bin/console doctrine:schema:create --env=test" "vendor/phpunit/phpunit/phpunit {};"

# Run email tests sequentially
vendor/bin/phpunit --group email

# Run custom test file or directory
vendor/bin/phpunit tests/path_to_tests/

# Run tests sequentially but exclude some groups - example shown for multiple
# Useful during development
vendor/bin/phpunit tests/ --exclude-group email
```

If the testing data fixtures have changed (from your own branch or upstream), you should clear the testing cache to ensure new fixtures are used

```shell
php bin/console cache:clear --env=test
```

After clearing cache, if you ony run tests that don't load fixtures (those that extend `KernelTestCase`) but still attempt to call the database, you should ensure the test database **schema** is correctly loaded for the test environment's SQLite db

```shell
php bin/console doctrine:schema:create --env=test
```

### Codeception functional tests

```shell
# Build codeception support files
vendor/bin/codecept build

# Run all codeception tests - sends requests to the frankenphp (Docker-based) web server service
vendor/bin/codecept run Functional
```

#### Run Codeception Suites

```shell
# Reload test fixtures and run cms suite
php bin/console doctrine:schema:drop --full-database --force \
  && php bin/console doctrine:schema:update --force --complete \
  && php bin/console doctrine:fixtures:load -n --group=DevTestFixtures \
  && vendor/bin/codecept run tests/Functional/Cms

# Reload test fixtures and run ops suite
php bin/console doctrine:schema:drop --full-database --force \
  && php bin/console doctrine:schema:update --force --complete \
  && php bin/console doctrine:fixtures:load -n --group=DevTestFixtures \
  && vendor/bin/codecept run tests/Functional/Ops
```

#### Symfony dev server

- The Symfony dev server (part of the Symfony CLI) is available as an alternative to the frankenphp (Docker-based) web server service for testing/debugging
- With the current devcontainer setup, you should not normally need to use these

```shell
# Run development server in the foreground - use ctrl-C to stop the server
symfony server:start

# Start development server in the background (-d for detached/daemon mode)
symfony server:start -d
# Stop development server if running in background
symfony server:stop

# Run codeception tests (change Functional to whatever file path you want)
vendor/bin/codeception run Functional --env symfony-dev-server
```

## Testing the API

- We use the [Bruno API Client](https://www.usebruno.com/) rather than Postman or Insomnia for testing/checking the API
- The `.bruno` directory contains the config, so you can just us the "Open Collection" option in Bruno to get started
- Note that not all routes are configured
- Several environemnts are configured that vary by where the backoffice is (local vs AWS hosted) and which user to login as
- Note on the subdirectories
  - `[Auth not required]` OAuth2 is for manually interacting with the OAuth2 server in backoffice, not usually needed unless you're debugging the auth system
  - `[Auth required]` v1 (current and primary, but a mish-mash of old and new)
  - `[Auth required]` v2 (largely deprecated)
  - `[Auth required]` v3 (still in R&D for future)
  - `[Auth not required]` Webhooks - primarily for simulating Mangopay webhooks being sent to us

### Authentication

- Should be setup by default for you to use the "ben.auto" user on local dev
- While the "Autofetch" and "Auto-refresh" option are checked by default, they have a habit of not working as expected
- To manually "login", do the following
  - Click on the Yielders API collection (not the subdirectories) in the collections list on the left
  - Click the "Auth" tab in the main Bruno area
  - Click "Get Access Token"
- If you need to change user, you can edit the username or switch environments, then click "Get Access Token" again to login again
- If you've edited/reloaded fixtures and/or made code changes, sometimes the auth can be invalidated.
  - Click "Clear Cache" next to the "Get Access Token" to clear the token in Bruno
  - Then click "Get Access Token" to log back in

## Other Development Features

### [WIP] Logging stacks

- Currently, the logging stack is NOT spun up by default when you start the dev containers as it has not been maintained in a while
- You can manually start up the Elastic logging stack with the following command from your **host** machine where Docker is installed

```shell
# Run from the root of your backoffice project
docker compose -f .devcontainer/compose.elastic.yaml up
```

- This stack can be run independently of backoffice
- Access the Kiabana dashboard on http://localhost:5601
  - Logs can be found in the "Observability" section

#### [WIP] Grafana

- Grafana can be spun up to view metrics from the docker containers
- The grafana stack is NOT spun up by default when you start the dev containers
- You can manually start up the Grafana stack with the following command from your **host** machine where Docker is installed

```shell
# Run from the root of your backoffice project
docker compose -f .devcontainer/compose.grafana.yaml up
```

- This stack can be run independently of backoffice
- Access the Grafana dashboard on http://localhost:3000
  - Both the username and password are set to `admin` by default
  - The docker dashboard can be found at http://localhost:3000/dashboards
  - Dashboards can be imported from https://grafana.com/grafana/dashboards

### Email Templates

- New email templates introduced as part of https://gitlab.com/yielders2/backoffice-dev/-/issues/2199 are implemented in [mjml](https://mjml.io/).
- These need to be converted into HTML before they can be used as Twig templates
  - Note that you can use Twig elements in mjml as they should be ignored by the converter
  - If not, you can modify the generated HTML with Twig elements
- The source `.mjml` files are stored in `templates/mail/mjml/`
- The usable `.html.twig` email templates themselves are subsequently stored in `templates/mail/`
- An the list of supported email templates are defined in the enum `src/Entity/Enum/EmailTemplate.php`
- These templates are used via the `sendTemplatedEmail()` method in `src/Service/MailerService.php`
  - An example can be found in `src/Service/NotificationService.php:notifyUserByEmail()`

## Building Docker Images

- Test building the production Docker image locally
- Docker is required, so either
  - Run from your native machine (if using devcontainers)
  - Install docker-in-docker or docker-from-docker extension in your supported editor (e.g. VSCode)

```shell
docker build --target frankenphp_prod -t yielderverse/backoffice-frankenphp/devcontainer-test:0.1
```

## More Info

- Our [release cadence](https://gitlab.com/yielders2/release/-/wikis/Reference/Release-Cadence) is roughly twice a month
- The [versioning system](https://gitlab.com/yielders2/release/-/wikis/Reference/Version-Number-System) we use is based on semantic versioning
