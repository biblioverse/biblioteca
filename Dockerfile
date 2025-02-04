ARG NODE_VERSION=22

FROM ghcr.io/biblioverse/biblioteca-docker:2.0.2 AS base
WORKDIR /var/www/html

USER root
RUN mkdir -p /tmp && chown -R www-data:www-data /tmp && chmod -R 777 /tmp
RUN mkdir -p /var/run/ && chown -R www-data:www-data /var/run && chmod -R 777 /var/run
USER www-data

# We install the dependencies in a separate layer as the frontend image also needs them
FROM base AS vendor
USER root

# Copying the full context is not great for caching
# But composer install executes symfony commands that need the full context
COPY composer.json composer.lock /var/www/html/

RUN composer install --no-interaction --no-progress --no-dev --no-scripts --optimize-autoloader

FROM node:${NODE_VERSION} AS frontend

WORKDIR /var/www/html

# Needed for some symfony specific modules
COPY --from=vendor /var/www/html/vendor /var/www/html/vendor

# Only copy what is needed, improves cacheability
COPY package.json package-lock.json webpack.config.js /var/www/html/
COPY assets/ /var/www/html/assets/

RUN npm install
RUN npm run build

FROM base AS prod
USER root

COPY --chown=www-data:www-data . /var/www/html/

COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor /var/www/html/vendor
COPY --chown=www-data:www-data --from=frontend /var/www/html/public/build /var/www/html/public/build

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