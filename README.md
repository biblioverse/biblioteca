# Biblioteca


## Pre-requirements
- Docker

## Setup the docker environment and dependencies

- Run `docker compose up -d`
- Run `docker compose exec biblioteca composer install`
- Run `docker compose exec biblioteca npm install`
- Run `docker compose exec biblioteca npm run build`

## Create the database schema and first admin user

- Run `docker compose exec biblioteca bin/console doctrine:schema:create`
- Run `docker compose exec biblioteca bin/console app:create-admin-user <username> <password>`

# Setup typesense indexes and data

- Run `docker compose exec biblioteca php bin/console typesense:create`

## If you already have book indexed, you can update the index by running:
- Run `docker compose exec biblioteca php bin/console typesense:import`

# Upload some books

* Push your books to the `public/books` folder
* Run `docker compose exec biblioteca php bin/console books:scan`

## Visit the website

- Go to `http://localhost:48480` or `https://biblioteca.docker.test` if you have configured the hosts file and traefik

## Xdebug

1. Create a `docker-compose-overrides.yml` file with the following content:

```yaml
services:
  biblioteca:
    build:
      target: debug
    environment:
      - XDEBUG_MODE=debug
      - PHP_IDE_CONFIG=serverName=biblioteca.docker.test
```

2. Make sure your container is up-to-date with `docker compose up -d --build --force-recreate`
3. Create a new server in your IDE with the following settings:
   - Host: `biblioteca.docker.test`
   - Mapping: <your local project root dir> => `/var/www/html`
4. Be sure that you are listening to the Xdebug port in your IDE
5. Set a breakpoint in your code
6. Start debugging.

Note: On command line, you can debug with this: 
```bash
    docker-compose exec -e XDEBUG_MODE=debug -e XDEBUG_TRIGGER=1 biblioteca ./vendor/bin/phpunit
```