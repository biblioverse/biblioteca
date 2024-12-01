FROM ghcr.io/biblioverse/biblioteca-docker:latest AS base
WORKDIR /var/www/html

FROM base AS prod
USER root
COPY . /var/www/html

RUN composer install
RUN npm install
RUN npm run build

RUN chown -R www-data:www-data /var/www/html

USER www-data

FROM base AS dev
USER root
RUN /usr/local/bin/install-php-extensions xdebug

RUN echo ' \n\
[xdebug] \n\
xdebug.idekey=PHPSTORM \n\
xdebug.mode=off \n\
xdebug.client_host=host.docker.internal\n ' >> /usr/local/etc/php/conf.d/biblioteca.ini
USER www-data