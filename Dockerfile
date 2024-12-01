FROM ghcr.io/biblioverse/biblioteca-docker:latest

USER root
COPY . /var/www/html



WORKDIR /var/www/html

RUN composer install
RUN npm install
RUN npm run build

RUN chown -R www-data:www-data /var/www/html

USER www-data