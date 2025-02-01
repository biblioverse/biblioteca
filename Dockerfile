FROM ghcr.io/biblioverse/biblioteca-docker:2.0.2 AS base
WORKDIR /var/www/html

USER root
RUN mkdir -p /tmp && chown -R www-data:www-data /tmp && chmod -R 777 /tmp
RUN mkdir -p /var/run/ && chown -R www-data:www-data /var/run && chmod -R 777 /var/run
USER www-data

FROM node:22 AS frontend

WORKDIR /var/www/html
COPY . /var/www/html

RUN npm install
RUN npm run build

FROM base AS prod
USER root
COPY . /var/www/html

RUN composer install

RUN chown -R www-data:www-data /var/www/html \
    && rm -rf /var/www/html/public/build

COPY --from=frontend /var/www/html/public/build /var/www/html/public/build

USER www-data

FROM base AS dev
USER root

RUN DEBIAN_FRONTEND=noninteractive apt-get update && apt-get install -y \
    wget \
    git \
    curl \
    sudo \
    vim \
    nodejs

RUN /usr/local/bin/install-php-extensions xdebug

RUN echo ' \n\
[xdebug] \n\
xdebug.idekey=PHPSTORM \n\
xdebug.mode=off \n\
xdebug.client_host=host.docker.internal\n ' >> /usr/local/etc/php/conf.d/biblioteca.ini
USER www-data