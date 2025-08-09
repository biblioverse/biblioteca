ARG NODE_VERSION=22

FROM ghcr.io/biblioverse/biblioteca-docker:frankenphp AS base
WORKDIR /var/www/html

USER root
RUN mkdir -p /tmp && chmod -R 777 /tmp
RUN mkdir -p /var/run/ && chmod -R 777 /var/run

# We install the dependencies in a separate layer as the frontend image also needs them
FROM base AS vendor

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

COPY . /var/www/html/

COPY --from=vendor /var/www/html/vendor /var/www/html/vendor
COPY --from=frontend /var/www/html/public/build /var/www/html/public/build

RUN composer install --no-interaction --no-progress --no-dev --optimize-autoloader

FROM base AS dev

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
