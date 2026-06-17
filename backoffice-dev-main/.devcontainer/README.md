## Dockerised development overview

- There are base `compose.yaml` file loads 2 other `compose.*.yaml` files that are enough to start backoffice running and make it available from the browser on http://back.dev.local
- The first compose file `compose.common.yaml` defines the common services
  - MariaDB database
  - Mailcatcher
- The second compose file, one of either `compose.php_fpm_nginx.yaml` or `compose.frankenphp.yaml` defines the web server and PHP executor
  - `compose.php_fpm_nginx.yaml` defines
    - Nginx web server
    - PHP-FPM
  - `compose.frankenphp.yaml` defines
    - frankenphp - a Caddy web server with a built-in PHP executor
- The `devcontainer.json` config used by VSCode (or other compatible editors) uses both the base `compose.yaml` and the `compose.development.yaml`. The latter starts up additional services for development purposes:
  - Debian based PHP env used by VSCode/code-edito
    - Also contains NodeJS (for Webpack)
  - Adminer - provides a web GUI to browse the database
  - A background PHP process for consuming messages from a queue - this will eventually move into the `compose.common.yaml` once development work on PHP messages have been completed
- The devcontainer config also defines a `postCreateCommand` that will run a number of scripts to setup the devcontainer. These will do the following:
  - Setup dotenv files for local development
  - Setup file permissions for the `var/` directory (where cache and logs) and files/documents used in tests
  - Install composer managed dependencies
  - Setup codeception for running functional tests
  - Setup database and load test/development data fixtures
  - Install git completion script to allow you to use tab to complete git commands in command line
  - Install the Symfony binary - which provides the `symfony` cli - primarily used for the PHP development server used by codeception functional tests
  - Install node modules with npm

## Using docker compose

#### Backoffice development

- It is generally recommended you use VSCode with the "Remote Containers" extension
- You can then open this project with the "Reopen in Container" command (F1 in VSCode to open the palette)
- This will start up all the containers you need for development
- If you want to use a different IDE/Editor, you can startup all the services in command line
  - You must specify both of the docker compose files to be used
  - Use the `-d` option to have them run in the background so your current terminal remains free (this is optional but recommended)

```shell
# From the backoffice-dev base directory
docker compose -f .devcontainer/compose.yaml -f .devcontainer/compose.development.yaml up -d

# From the backoffice/.devcontainer directory
docker compose -f compose.yaml -f compose.development.yaml up -d
```

- If you have Docker Desktop and previously started the project up via VSCode or CLI
- You can also use Docker Desktop to start up all the containers by just clicking the "Start" button on the backoffice-dev_devcontainer project in the "Containers/Apps" tab

#### Frontend development with backoffice dependency

- While working on frontend clients for the Yielders API, you will want backoffice running in the background
- In this case, you may not want the development containers running to reduce system resource usage (mainly RAM)
- For these scenarios, you will just need to the core services (defined in the main compose) running

```shell
# From the backoffice-dev base directory
docker compose -f .devcontainer/compose.yaml up -d
```

```shell
# From the backoffice/.devcontainer directory
docker compose up -d
```

- If you have plenty of system resources, then starting up all the containers is still an option
- Again, if you have Docker Desktop and previously started the backoffice project up via VSCode or CLI
- You can startup services with the Docker Desktop GUI as well