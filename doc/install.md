# Installing Biblioteca for the first time
## Pre-requisites

* Docker and docker compose installed
* Ddev <https://ddev.com/>

## Local development with DDEV

1. Clone the repository
2. `cd` to your local directory where you cloned the repository
3. Run `ddev start`
4. Run `ddev composer install`
5. Run `ddev exec bin/console doctrine:database:create`
6. Run `ddev exec bin/console doctrine:migration:sync-metadata-storage`
7. Run `ddev exec bin/console doctrine:migration:migrate`
8. Run `ddev exec bin/console typesense:create`
9. Run `ddev exec bin/console app:create-admin-user <username> <password>`
10. Run `ddev exec npm i`
11. Run `ddev exec npm run build`
12. Run `ddev launch`

### Enable Xdebug
Run `ddev xdebug` 


## With docker compose

1. Copy the [docker-compose.yml](docker-compose.yml) file to your project directory
2. Copy the [Dockerfile](Dockerfile)
3. run `docker-compose up -d`
4. run `docker-compose exec php composer install`
5. run `docker-compose exec php bin/console doctrine:schema:create`
6. run `docker-compose exec php bin/console typesense:create`
7. run `docker-compose exec php bin/console app:create-admin-user <username> <password>`
8. run `docker-compose exec npm i`
9. run `docker-compose exec npm run build`
10. open your browser on `http://localhost:48480`

### Enable Xdebug

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

## With [liip/pontsun](https://github.com/liip/pontsun)
1. Copy the [docker-compose-pontsun.yml](docker-compose-pontsun.yml) file to your project directory 
2. ... Follow steps 2 to 9 from the docker compose installation
3. open your browser on `http://biblioteca.docker.test`


## In your Unraid setup
You can use [dockge](https://github.com/louislam/dockge) and setup a stack. Use the same docker-compose procedure as before.