FROM ghcr.io/biblioverse/biblioteca-docker:latest as base
USER root
RUN install-php-extensions xdebug
ENV XDEBUG_MODE=debug
ENV XDEBUG_CONFIG="client_host=host.docker.internal"
USER www-data