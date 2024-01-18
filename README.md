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