## Fixing file permissions

- The following directories require write access by the PHP-FPM server
  - `var/log/`
  - `var/cache/`
- If using devcontainers, the setup scripts should sort out file permissions for you
- It is recommended you add `front.dev.local` to your host machine's hosts file as the default devcontainer setup uses Traefik as a reverse proxy to allow both backoffice and frontend to be accessible on port 80 from your host machine
  - If you have done so, frontend should be accessible from http://front.dev.local
- If you haven't added the hostname to your hosts file, you will need to modify `.devcontainer/compose.frankenphp.yaml` to expose a port to your host machine
    - By default this is port 8001 (used by our legacy setup before Traefik was introduced), so accessible on http://localhost:8001 or http://127.0.0.1:8001 
    - If you want to use another port, edit the `HTTP_PORT` variable in `.devcontainer/.env`
    - You'll need to update the backoffice API (OAuth2) client `RedirectUris` to allow your frontend to authenticate with backoffice (see next section)

## Adding new frontend urls to OAuth2 API client allow-list

- The OAuth2 login mechanism has an allow-list of permitted urls that are considered safe to send users to
- The allow-list for the main development API client is found at http://back.dev.local/admin/administration/clients/904c1b4d9a15529ed70ff5e686345a9f
- The default is preconfigured for you http://front.dev.local/auth/callback (requires front.dev.local configured in your host machine's hosts file)
- For any other custom config, e.g. localhost and a port, add http://localhost:8001/auth/callback 


## Running acceptance tests with selenium

- The selenium container isn't started by default when you start the devcontainer
- This is due to the system resource cost of the selenium container
  - 2GB RAM
  - 450MB download (compressed)
  - 1.2GB storage (uncompressed)
- There is a separate docker compose file that defines the selenium container required to run e2e tests with Codeception
- You can run the docker compose up and down commands from inside the devcontainer or from your host-machine

```shell
docker compose -f .devcontainer/compose.selenium.yaml up -d
```

```shell
docker compose -f .devcontainer/compose.selenium.yaml down
```

