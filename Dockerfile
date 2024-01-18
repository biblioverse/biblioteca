FROM ghcr.io/biblioverse/biblioteca-docker:1.0.11 as base

FROM base as debug
USER root
RUN /usr/local/bin/install-php-extensions xdebug

# Somehow echo -e is not working
RUN echo ' \n\
[xdebug] \n\
xdebug.idekey=PHPSTORM \n\
xdebug.mode=off \n\
xdebug.client_host=host.docker.internal\n ' >> /usr/local/etc/php/conf.d/biblioteca.ini
USER www-data
